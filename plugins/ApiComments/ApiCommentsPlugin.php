<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class ApiCommentsPlugin extends Plugin
{
    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'ApicommentsbymeAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        
        case 'ApicommentstomeAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
            
        case 'ApicommentsshowAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
           
        default:
            return true;
        }
    }
    
    function onRouterInitialized($m)
    {
        $m->connect('api/comments/show/:id.:format',
                        array('action' => 'apicommentsshow',
                              'format' => 'json'));
        
        $m->connect('api/comments/by_me.:format',
                        array('action' => 'apicommentsbyme',
                              'format' => 'json'));
        
        $m->connect('api/comments/to_me.:format',
                        array('action' => 'apicommentstome',
                              'format' => 'json'));
        
        return true;
    }
}
