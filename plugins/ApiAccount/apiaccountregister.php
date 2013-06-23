<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class ApiAccountRegisterAction extends ApiAction
{
    const MAX_DESCRIPTION = 70;
    
    function prepare($args)
    {
        parent::prepare($args);
        
        return true;
    }
    
    function handle($args)
    {
        parent::handle($args);
        
        if (!Event::handle('StartRegistrationTry', array($this))) 
        {
            return;
        }
        
        //database use nickname we change it into username for more
        //easier to understand
        
        $nickname = $this->trimmed('username');
        $email    = $this->trimmed('email');
        $fullname = $this->trimmed('nickname');
        $homepage = NULL;//$this->trimmed('homepage');
        $bio      = $this->trimmed('description');
        $location = $this->trimmed('location');
        $genderStr = $this->trimmed('gender');
        
        if (!empty($bio))
        {
            if (mb_strlen($bio) > self::MAX_DESCRIPTION) {
                $this->clientError(_('description must be set less than 70'));
                return;
            }
        }
        
        if (empty($email) && empty($nickname))
        {
            $this->clientError(_('must set nickname or email'));
            return;
        }
        
        if (empty($nickname) && !empty($email))
        {
            $user_email_check = User::staticGet('email', $email);
            if ($user_email_check)
            {
                $this->clientError(_('email exists'));
                return;
            }
            
            $nickname = $this->nicknameFromEmail($email);
        }

        // We don't trim these... whitespace is OK in a password!
        $password = $this->arg('password');
        
        try {
            $nickname = Nickname::normalize($nickname);
        } catch (NicknameException $e) {
            $this->clientError(_('username error'));
            return;
        }
        
        if (!User::allowed_nickname($nickname)) {
            // TRANS: Client error displayed when trying to create a new user with an invalid username.
            $this->clientError(_('username bad'), 400);
            return;
        }
        
        $gender = 0;
        if (!empty($genderStr))
        {
            if ($genderStr == 'f')
            {
                $gender = 1;
            }
            else if ($genderStr == 'm')
            {
                $gender = 2;
            }
        }
        
        $user_check = User::staticGet('nickname', $nickname);
        if ($user_check)
        {
            $this->clientError('username exists', 400);
            return;
        }
        
        if (empty($password)) {
            $this->clientError(_('password empty'), 400);
        
            return;
        }
        
        //no need to confirmed email
        $email_confirmed = !empty($email);
        
        $user = User::register(array('nickname' => $nickname,
                                                    'password' => $password,
                                                    'email' => $email,
                                                    'fullname' => $fullname,
                                                    'homepage' => $homepage,
                                                    'bio' => $bio,
                                                    'location' => $location,
                                                    'code' => $code,
                                                    'gender' => $gender,
                                                    'email_confirmed' => $email_confirmed));
        if (!$user) 
        {
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
	
        $resultUser = $this->twitterUserArray($user->getProfile(),false);
        
        $this->initDocument('json');
        $this->showJsonObjects($resultUser);
        $this->endDocument('json');
    }
    
    function showForm($error=null)
    {
        echo $error;
    }
    
    function nicknameFromEmail($email)
    {
        $parts = explode('@', $email);

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