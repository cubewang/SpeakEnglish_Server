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

class ApiAccountPlugin extends Plugin
{
    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'ApiaccountregisterAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        case 'ApiaccountbindplatformAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        case 'ApiaccountloginAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
        case 'ApiregisterplatformAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
            
        default:
            return true;
        }
    }
    
    function onRouterInitialized($m)
    {   
        $m->connect('api/account/register.:format',
                        array('action' => 'apiaccountregister',
                              'format' => 'json'));
        
        $m->connect('api/account/login.:format',
                        array('action' => 'apiaccountlogin',
                              'format' => 'json'));
        
        $m->connect('api/account/bind/:platform.:format',
                        array('action' => 'apiaccountbindplatform',
                              'format' => 'json'));
        
        $m->connect('api/account/register/:platform.:format',
                        array('action' => 'apiregisterplatform',
                              'format' => 'json'));
        
        return true;
    }
}
