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

class ApiDataMigrationPlugin extends Plugin
{
    function onAutoload($cls)
    {
        $dir = dirname(__FILE__);

        switch ($cls)
        {
        case 'ApidatamigrationprofileAction':
            include_once $dir . '/' . strtolower(mb_substr($cls, 0, -6)) . '.php';
            return false;
            
        case 'ApichangedatabaseAction';
            include_once $dir . '/' . 'changedatabase' . '.php';
            return false;
            
        default:
            return true;
        }
    }
    
    function onRouterInitialized($m)
    {
        $m->connect('api/datamigration/profile.:format',
                        array('action' => 'apidatamigrationprofile',
                              'format' => 'json'));
        $m->connect('api/datamigration/update_activity.:format',
                        array('action' => 'apichangedatabase',
                              'format' => 'json'));
              
        return true;
    }
}
