<?php

session_start();

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

//this is for web or client
//because it used session

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

include_once('saetv2.ex.class.php');
include_once('tencent.php');
require_once 'platform_config.php';

class ApiAccountBindPlatformAction extends ApiAction
{
    var $token = null;
    var $user_message = null;

    function prepare($args)
    {
        parent::prepare($args);

        return true;
    }

    function handle($args)
    {
        parent::handle($args);

        $type = $this->trimmed('platform');
        $this->password = "111111";

        switch ($type) {
            case 'sina':
                $this->bindSina();
                break;
            case 'qq';
                $this->bindQQ();
                break;
            default:
                $this->clientError(_('invalid platform type'));
                return;
                break;
        }


        header("Location: ". REDIREDT_WEB_URL);
    }

    /**
     * sina绑定
     */
    function bindSina()
    {
        $code =  $this->trimmed('code');
        if (empty($code))
        {
            $this->clientError('cannot find sina code, oauth failed', $code);
            exit();
        }
        
        $keys = array();
	$keys['code'] = $code;
	$keys['redirect_uri'] = WB_CALLBACK_URL;

	try {
            $sinaOauth = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
            $token = $sinaOauth->getAccessToken( 'code', $keys ) ;
	} catch (OAuthException $e) {
            $this->clientError("oauth failed $e", 400);
            exit();
	}

        $url = 'https://api.weibo.com/2/users/show.json?'.http_build_query(array(
			'access_token' => $token['access_token'],
			'uid' => $token['uid'],
		));
        
        $user = json_decode(file_get_contents($url));

      	if (array_key_exists("error", $user))
        {
            $this->clientError($user, 400);
        }
        
        $userOption = array(
                        'via' => 'weibo',
			'uid' => $user->id,
			'screen_name' => $user->screen_name,
			'name' => $user->name,
			'location' => $user->location,
			'description' => $user->description,
			'image' => $user->profile_image_url,
			'access_token' => $token->access_token,
			'expire_at' => $token->expires,
			'refresh_token' => $token->refresh_token
		);
        
        $this->bind_common($user->id, User::PLATFORM_TYPE_SINA, $userOption);
    }

    /**
     * QQ绑定
     */
    function bindQQ()
    {   
        $token = $this->get_qq_token();
        $openID = $this->get_qq_openid($token);
        
        $userOption = $this->get_qq_user_info($token, $openID);
        
        $this->bind_common($userOption['uid'], User::PLATFORM_TYPE_TENCENT_QQ, $userOption);
    }
    
    function get_qq_openid($token)
    {
        $url = 'https://graph.qq.com/oauth2.0/me?'.http_build_query(array(
			'access_token' => $token['access_token']
            ));
	
        $response = file_get_contents($url);
                
        if (strpos($response, "callback") !== false)
        {
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
        }
        
        $me = json_decode($response);
                
        if (isset($me->error))
        {
            $this->clientError("qq get openid error = ". $me->error, 400);
            exit();
        }
        
        $openID = $me->openid;
        return $openID;
    }
    
    function get_qq_token()
    {
        $code =  $this->trimmed('code');
        $state = $this->trimmed('state');
        if (empty($code) || empty($state))
        {
            $this->clientError('cannot find code or state, oauth failed', $code);
            exit();
        }
        
        $url = 'https://graph.qq.com/oauth2.0/token?'.http_build_query(array(
			'grant_type' => 'authorization_code',
                        'client_id' => QQ_AKEY,
                        'client_secret' => QQ_SKEY,
                        'code' => $code,
                        'state' => $state,
                        'scope' => 'get_simple_userinfo,get_info,get_user_info',
                        'redirect_uri' => QQ_CALLBACK_URL
		));
                
        $response = file_get_contents($url);
        if(strpos($response, "callback") !== false)
        {
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
            $msg = json_decode($response);

            if(isset($msg->error)){
                $this->clientError('qq oauth failed'. $msg->error, $code);
                exit();
            }
        }

        $params = array();
        parse_str($response, $params);
        
        return $params;
    }
    
    public function get_qq_user_info($token, $openID)
    {
        $aGetParam = array(
            "access_token" => $token['access_token'],
            "oauth_consumer_key" => QQ_AKEY,
            "format" => "json",
            "openid" => $openID
        );
        
        $qqAvatar = null;
        $url = "https://graph.qq.com/user/get_simple_userinfo";
        $qqContent = tencent::get($url, $aGetParam);
        if ($qqContent !== FALSE) {
            $aResult = json_decode($qqContent, true);
            
            if ($aResult["ret"] == 0) {
                $qqAvatar = $qqContent['figureurl_2'];
            }
        }
        
	$sUrl = "https://graph.qq.com/user/get_info";
        
        $sContent = tencent::get($sUrl, $aGetParam);
        
        if ($sContent !== FALSE) {
            $aResult = json_decode($sContent, true);
            if ($aResult["ret"] == 0) {
                
                $userid = $aResult["data"]["tweetinfo"][0]["id"];
                $usernick = $aResult["data"]["name"];
                $gender = $aResult["data"]["sex"];
                $userdescription = $aResult["data"]["introduction"];
                $location = $aResult["data"]["location"];
                if (empty($userid))
                {
                    // 通过qq 信息的用户
                    $userid = $openID;
                    $usernick = $qqContent['nickname'];
                    $qqGender = $qqContent["data"]["gender"];
                    $gender = $qqGender == '男' ? 1 : 2; 
                }
                else
                {
                    // 通过qq 微博的用户 这里保存这个是因为要支持老用户数据
                    $userid = $aResult["data"]["tweetinfo"][0]["id"];
                    $usernick = $aResult["data"]["name"];
                    $gender = $aResult["data"]["sex"];
                    $userdescription = $aResult["data"]["introduction"];
                    $location = $aResult["data"]["location"];
                }
                
            } else {
                $this->clientError($aResult["ret"] . ":" . $aResult["msg"]);
                return;
            }
        } else {
            $this->clientError(_('invalid_access_token'));
            exit();
        }
                
	return array(
            'via' => 'qq',
			'uid' => $userid,
			'screen_name' => $usernick,
			'name' => $userid,
			'location' => $location,
			'description' => $userdescription,
			'image' => $qqAvatar
		);
    }

    function bind_common($platform_userid, $platform_type, $userOption = null)
    {   
        $head = null; //拼接userid头
        switch ($platform_type) {
            case 2 :
                $head = "qq";
                break;
            case 1:
                $head = 'sina';
                break;
        }
        
        $userFetch = new User();

        $sql = "platform_type='$platform_type' AND platform_userid='$platform_userid'";

        $userFetch->whereAdd($sql);
        $userFetch->limit(1);
        $userFetch->find();

        if ($userFetch->fetch()) {
            
            $this->setLoginUser($userFetch);
            
            return;
        }
        
        $email = $this->trimmed("email");
        $homepage = $this->trimmed("homepage");
        
        $originalUsername = $head . $platform_userid;
        $username = $this->nicknameFromName($originalUsername);
        $password = $this->password;

        if (!User::allowed_nickname($username)) {
            // TRANS: Client error displayed when trying to create a new user with an invalid username.
            $this->clientError(_('username bad'), 400);
            return;
        }

        $user_check = User::staticGet('nickname', $username);
        if ($user_check) {
            $this->clientError('username exists', 400);
            return;
        }

        $user = User::register(array('nickname' => $username,
            'password' => $password,
            'email' => $email,
            'fullname' => $userOption['screen_name'],
            'homepage' => $homepage,
            'bio' => $userOption['description'],
            'location' => $userOption['location'],
            'code' => $code,
            'gender' => $userOption['gender'],
            'platform_userid' => $platform_userid,
            'platform_type' => $platform_type));
        if (!$user) {
            // TRANS: Form validation error displayed when trying to register with an invalid username or password.
            $this->clientError(_('Invalid username or password.', 400, 'json'));
            return;
        }
        // success!
        if (!common_set_user($user)) {
            // TRANS: Server error displayed when saving fails during user registration.
            $this->serverError(_('Error setting user.', '500', 'json'));
            return;
        }
        // this is a real login
        common_real_login(true);
        if ($this->boolean('rememberme')) {
            common_debug('Adding rememberme cookie for ' . $username);
            common_rememberme($user);
        }

        // Re-init language env in case it changed (not yet, but soon)
        common_init_language();
        Event::handle('EndRegistrationTry', array($this));
        
        if (!empty($userOption['image'])) 
        {   
            try 
            {
                $user->getProfile()->setOriginalAvatarUrl($userOption['image']);
            
                common_broadcast_profile($user->getProfile());    
            } catch (Exception $exc) {
            }
        }
        
        $this->setLoginUser($user);
    }
    
    function setLoginUser($user)
    {
        if (common_is_real_login())
        {
            $this->logoutWeb();
        }
        
        common_ensure_session();
        
        // success!
        if (!common_set_user($user)) {
            // TRANS: Server error displayed when during login a server error occurs.
            $this->serverError(_('Error setting user. You are probably not authorized.'));
            return;
        }

        common_real_login(true);
    }
    
    function logoutWeb()
    {
        if (Event::handle('StartLogout', array($this))) 
        {
            common_set_user(null);
            common_real_login(false); // not logged in
            common_forgetme(); // don't log back in!
        }
        
        Event::handle('EndLogout', array($this));
    }
    
    function showUserResult($user, $registered)
    {
        $result = array();

        $result['user'] = $this->twitterUserArray($user->getProfile(), false);
        $result['auth'] = array('username' => $user->nickname,
            'password' => $user->plan_password);
        $result['registered'] = $registered;
        $this->showFullJsonObjects($result);
    }
    
    function nicknameFromName($name)
    {
        $parts = explode('@', $name);

        $nickname = $parts[0];

        $nickname = preg_replace('/[^A-Za-z0-9]/', '', $nickname);

        $nickname = Nickname::normalize($nickname);

        $original = $nickname;

        $n = 0;

        while (User::staticGet('nickname', $nickname)) {
            $n++;
            $nickname = $original . $n;
        }

        return $nickname;
    }
}