
<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/mailbox.php';
require_once INSTALLDIR. '/lib/apiauth.php';

class ApiMessageBoxAction extends ApiAuthAction
{
    var $fromUserId = null;
    var $toUserId = null;
    var $sinceId = 0;
    var $maxId = MESSAGES_PER_PAGE;
    var $count = MESSAGES_PER_PAGE;
    
    function prepare($args)
    {
        parent::prepare($args);

        return true;
    }
    
    function handle($args)
    {
        parent::handle($args);
        
        if (empty($this->auth_user)) {
            $this->clientError(_('No such user.'), 404, $this->format);
            return;
        }      
        
        $from = $this->auth_user->id;
        $to = $this->trimmed('id');
        
        if (empty($from) || empty($to))
        {
            $this->clientError(
                // TRANS: Client error displayed when no message text was submitted (406).
                _('must set fromUser or toUser'),
                406,
                $this->format
            );
            
            return;
        }
        
        $this->count = $this->trimmed('count');
        if (empty($this->count))
        {
            $this->count = MESSAGES_PER_PAGE;
        }
        
        if ($this->count > MESSAGES_PER_PAGE * 2)
            $this->count = MESSAGES_PER_PAGE;
        
        $this->sinceId = $this->trimmed('since_id');
        $this->max_id = $this->trimmed('max_id');
        
        if (empty($this->sinceId))
        {
            $this->sinceId = 0;
        }
        
        if (empty($this->max_id))
        {
            $this->max_id = 0;
        }
     
        if (!is_numeric($from) 
                || !is_numeric($to) 
                || !is_numeric($this->sinceId) 
                || !is_numeric($this->max_id)
                || !is_numeric($this->count))
        {
            $this->clientError(_('param error'));
            return;
        }
        
        if ($from == $to)
        {
            $this->clientError(
                // TRANS: Client error displayed when no message text was submitted (406).
                _('can not get conversation for your self'),
                406,
                $this->format
            );
            
            return;
        }
        
        $fromUser =  $this->getTargetUser($from);
        $toUser = $this->getTargetUser($to);
        
        if (empty($fromUser) || empty($toUser))
        {
            $this->clientError(
                // TRANS: Client error displayed when no message text was submitted (406).
                _('invalid fromUser or toUser'),
                406,
                $this->format
            );
        }
        
        $this->fromUserId = $fromUser->id;
        $this->toUserId = $toUser->id;
        
        $message = $this->getMessages();
        
        $result = NULL;
        if (!empty($message))
        {
            $result = $this->fillMessage($message);
        }
        
        if ($result == NULL)
        {
            $result = array();
        }
        
        $this->showFullJsonObjects($result);
    }
    
    function getMessages()
    {
        $message = new Message();
        
        $from = $this->fromUserId;
        $to = $this->toUserId;
        
        /*
        $qry = 
                'SELECT * FROM message WHERE'. 
                            '('.
                                '((from_profile=%d AND to_profile=%d) OR( from_profile=%d AND to_profile=%d))'.
                                'AND (id > %d or id = %d)'.
                                'AND (id < %d or id = %d)'.
                             ')'.
                 'ORDER BY  created DESC LIMIT %d';
        
        $var = sprintf($qry, $from, $to, $to, $from, 
                $this->sinceId, $this->sinceId, $this->max_id, $this->max_id, $this->count);
        
        $message->query($var);*/
        
        $sql = null;
        if ($this->since_id != 0 && $this->max_id == 0)
        {
            $str =  '((from_profile=%d AND to_profile=%d) OR( from_profile=%d AND to_profile=%d))'.
                                'AND (id > %d)';
            
            $sql = sprintf($str, $from, $to, $to, $from, $this->sinceId);
        }
        else if ($this->since_id == 0 && $this->max_id != 0)
        {
            $str =  '((from_profile=%d AND to_profile=%d) OR( from_profile=%d AND to_profile=%d))'.
                                'AND (id < %d)';
            $sql = sprintf($str, $from, $to, $to, $from, $this->max_id);
        }
        else if ($this->since_id != 0 && $this->max_id != 0)
        {
            $str =  '((from_profile=%d AND to_profile=%d) OR( from_profile=%d AND to_profile=%d))'.
                                'AND (id > %d)'.
                                'AND (id < %d)';
            $sql = sprintf($str, $from, $to, $to, $from, $this->sinceId, $this->max_id); 
        }
        else
        {
            $str =  '((from_profile=%d AND to_profile=%d) OR( from_profile=%d AND to_profile=%d))';
            $sql = sprintf($str, $from, $to, $to, $from); 
        }
        
        $message->whereAdd($sql);
        $message->limit($this->count);
        $message->orderBy('created DESC, id DESC');
        
        $message->find();
        
        return $message;
    }
    
    function fillMessage($message)
    {
        $messageList = array();
        $messageItem = array();
        $cnt = 0;
        while ($message->fetch() && $cnt <= $this->count) 
        {
            $cnt++;

            if ($cnt > $this->count) {
                break;
            }
            
            $messageItem = $this->directMessageArray($message);
            
            array_push($messageList, $messageItem);
        }
        
        return $messageList;
    }
}