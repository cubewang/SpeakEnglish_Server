<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR. '/lib/apiauth.php';

class ApiSuggestionsHotAction extends ApiAction
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
        
        $auth = new ApiAuthAction();
        $auth->checkBasicAuthUser(false);
        
        $this->user = $auth->auth_user;
        
        $type = $this->trimmed('type');
        $hotResult = null;
        
        switch ($type) {
            case 'tags':
                $hotResult = $this->getHotTags();
                break;
            case 'users';
                $hotResult = $this->getHotUsers();
                break;

            default:
                $this->clientError(_('invalid type'));
                return;
                break;
        }
        
        $this->initDocument('json');
        $this->showJsonObjects($hotResult);
        $this->endDocument('json');
    }
    
    function getHotTags()
    {
        $tags = array(array('name' => '每日英语'),
                        array('name' => '每日新闻'),
                        array('name' => '6分钟英语'),
                        array('name' => 'VOA慢速英语'),
                        array('name' => 'ESL英语'),
                    );
        return $tags;
    }
    
    function getHotUsers()
    {
        $userIds = array('2','3');
        
        $hotUserList = array();
        
        foreach ($userIds as $userId) {
            $profile = Profile::staticGet($userId);
            $twitter_user = $this->twitterUserArray($profile, false);
            if (!empty($this->user))
            {
                $twitter_user['following'] = $this->user->isSubscribed($profile);
            }
            
            $hotUserList[] = $twitter_user;
        }
        
        return $hotUserList;
    }
}