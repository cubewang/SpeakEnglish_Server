
<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class ApiAccountLoginAction extends ApiAction
{
    function prepare($args)
    {
        parent::prepare($args);
        
        return true;
    }
    
    function handle($args)
    {
        parent::handle($args);
        
        $this->checkLogin();
    }
    
    function checkLogin($user_id=null, $token=null)
    {
        // XXX: login throttle

        //database use nickname we change it into username for more
        //easier to understand
        
        $nickname = $this->trimmed('username');
        
        if (empty($nickname))
        {
            $this->clientError(_('username empty'));
            return;
        }
        
        try {
            $nickname = Nickname::normalize($nickname);
        } catch (NicknameException $e) {
            $this->clientError(_('username error'));
            return;
        }
        
        $password = $this->arg('password');

        $user = common_check_user($nickname, $password);

        if (!$user) {
            // TRANS: Form validation error displayed when trying to log in with incorrect credentials.
            $this->clientError(_('Incorrect username or password.'));
            return;
        }

        // success!
        if (!common_set_user($user)) {
            // TRANS: Server error displayed when during login a server error occurs.
            $this->serverError(_('Error setting user. You are probably not authorized.'));
            return;
        }
        
        common_real_login(true);
        
        $result = $this->twitterUserArray($user->getProfile(),false);
        
        $this->initDocument('json');
        $this->showJsonObjects($result);
        $this->endDocument('json');
    }
}