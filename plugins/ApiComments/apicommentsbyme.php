<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('STATUSNET')) {
    exit(1);
}

require_once INSTALLDIR . '/lib/apiauth.php';

class ApiCommentsByMeAction extends ApiAuthAction
{
    var $m_replyNotices = null;
    var $m_notices = null;
    var $userId = null;
    
    function prepare($args)
    {
        parent::prepare($args);
        
        $this->userId = $this->auth_user->id;

        $this->getNotices();
        
        return true;
    }
    
    function handle($args)
    {
        parent::handle($args);
        
        $this->showJsonTimeline($this->m_notices, $this->m_replyNotices);
    }
    
    function getNotices()
    {
        $notices = array();

        $stream = new CommentByUserNoticeStream($userId);

        $notice = $stream->getNotices(($this->page - 1) * $this->count,
                                      $this->count,
                                      $this->since_id,
                                      $this->max_id);

        while ($notice->fetch()) {
            $notices[] = clone($notice);
        }
        
        $replyNoticeIds = array();
        
        foreach ($notices as $notice) {
            
            $reply_toId = $notice->reply_to;
            if (!empty($reply_toId))
            {
                $replyNoticeIds[] = $notice->reply_to;
            }
        }
        
        $replyNotice = Notice::multiGet('id', $replyNoticeIds);
        
        while ($replyNotice->fetch()) {
            $replyNoticeArray[] = clone($replyNotice);
        }

        $this->m_notices = new ArrayWrapper($notices);
        $this->m_replyNotices = new ArrayWrapper($replyNoticeArray);
    }
    
    function showJsonTimeline($notice, $original)
    {
        $this->initDocument('json');

        $statuses = array();
        $originals = array();
        
        if (is_array($original)) {
            $original = new ArrayWrapper($original);
        }
        
        while ($original->fetch()) {
            try {
                $twitter_status = $this->twitterStatusArray($original);
                $originals[$twitter_status['id']] = $twitter_status;
                
                //array_push($originals, $twitter_status);
            } catch (Exception $e) {
                common_log(LOG_ERR, $e->getMessage());
                continue;
            }
        }

        if (is_array($notice)) {
            $notice = new ArrayWrapper($notice);
        }

        while ($notice->fetch()) {
            try {
                $twitter_status = $this->twitterStatusArray($notice);
                $twitter_status['in_reply_to_status'] = $originals[$twitter_status['in_reply_to_status_id']];
                array_push($statuses, $twitter_status);
            } catch (Exception $e) {
                common_log(LOG_ERR, $e->getMessage());
                continue;
            }
        }
        
        $this->showJsonObjects($statuses);

        $this->endDocument('json');
    }
    
    function isReadOnly($args)
    {
        return true;
    }
}