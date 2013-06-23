<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

//this is for client

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

include_once('saetv2.ex.class.php');
require_once('tencent.php');
require_once 'platform_config.php';

class ApiRegisterPlatformAction extends ApiAction
{
    var $token = null;
    var $user_message = null;

    function prepare($args)
    {
        parent::prepare($args);

        $this->token = $this->trimmed('token');

        return true;
    }

    function handle($args)
    {
        parent::handle($args);

        if (!Event::handle('StartRegistrationTry', array($this))) {
            return;
        }

        if (empty($this->token)) {
            $this->clientError(_('token empty'));
            return;
        }

        $platform_type = $this->trimmed('platform');
        $this->password = "111111";

        switch ($platform_type) {
            case 'sina':
                $this->registerSina();
                break;
            case 'qq';
                $this->registQQ();
                break;
            default:
                $this->clientError(_('invalid platform type'));
                return;
                break;
        }
    }

    /**
     * 新浪注册
     */
    function registerSina()
    {
        $c = null;
        try {
            $c = new SaeTClientV2(WB_AKEY, WB_SKEY, $this->token);
        } catch (Exception $ex) {
            $this->clientError(_('invalid_access_token'));
            return;
        }

        $uid_get = $c->get_uid();

        $error = $uid_get['error'];
        if (!empty($error)) {
            $this->clientError(_('invalid_access_token'));
            return;
        }

        $uid = $uid_get['uid'];
        $this->user_message = $c->show_user_by_id($uid); //根据ID获取用户等基本信息

        $this->regist_common(
                $uid, 
                User::PLATFORM_TYPE_SINA, 
                $this->user_message['screen_name'], 
                $this->user_message['profile_image_url'],
                $this->user_message['description'], 
                $this->user_message['gender'], 
                null);
    }

    /**
     * QQ注册
     */
    function registQQ()
    {
        $openID = $this->trimmed('openid');
        $qqAvatar = null;
        
        $aGetParam = array(
            "access_token" => $this->token,
            "oauth_consumer_key" => QQ_AKEY,
            "format" => "json",
            "openid" => $openID
        );
        
        $url = "https://graph.qq.com/user/get_simple_userinfo";
        $qqResult = tencent::get($url, $aGetParam);
        if ($qqResult !== FALSE) {
            $qqResult = json_decode($qqResult, true);
            
            if ($qqResult["ret"] == 0) {
                $qqAvatar = $qqResult['figureurl_2'];
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
                    $usernick = $qqResult['nickname'];
                    $qqGender = $qqResult["data"]["gender"];
                    $gender = $qqGender == '男' ? 1 : 0; 
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
        
        $this->regist_common($userid, User::PLATFORM_TYPE_TENCENT_QQ, $usernick, $qqAvatar,$userdescription, $gender, $location);
    }

    /**
     * 注册公共函数
     * 由一些条件的判断完成最终注册
     * @param $platform_userid 用户id唯一
     * @param $platform_type 类型：sina、qq
     * @param null $nickname 昵称
     * @param null $description 描述
     * @param null $location 当前所在地
     * @param int $gender 性别
     */
    function regist_common(
            $platform_userid, 
            $platform_type, 
            $nickname = null, 
            $profile_image_url = null,
            $description = null, 
            $gender = 0, 
            $location = null
            )
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
        
        $user = new User();

        $sql = "platform_type='$platform_type' AND platform_userid='$platform_userid'";

        $user->whereAdd($sql);
        $user->limit(1);
        $user->find();

        if ($user->fetch()) {
            $this->showUserResult($user, 1);
            return;
        }

        $originalUsername = $head . $platform_userid;
        $username = $this->nicknameFromName($originalUsername);
        $email = $this->trimmed("email");
        $homepage = $this->trimmed("homepage");
        $password = $this->password;

        if (!User::allowed_nickname($nickname)) {
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
            'fullname' => $nickname,
            'homepage' => $homepage,
            'bio' => $description,
            'location' => $location,
            'code' => $code,
            'gender' => $gender,
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
            common_debug('Adding rememberme cookie for ' . $nickname);
            common_rememberme($user);
        }

        // Re-init language env in case it changed (not yet, but soon)
        common_init_language();
        Event::handle('EndRegistrationTry', array($this));
        
        if (!empty($profile_image_url)) 
        {   
            try 
            {
                $user->getProfile()->setOriginalAvatarUrl($profile_image_url);
                common_broadcast_profile($user->getProfile());
            } catch (Exception $exc) {
                
            }
        }

        $this->showUserResult($user, 0);
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