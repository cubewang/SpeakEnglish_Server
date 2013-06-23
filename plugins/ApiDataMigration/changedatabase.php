
<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class ApiChangeDatabaseAction extends ApiAction
{
    function prepare($args)
    {
        parent::prepare($args);
        
        return true;
    }
    
    function handle($args)
    {
        parent::handle($args);
        
        $notice = new Notice();
        $notice->source = 'activity';
        $notice->find();
        
        while ($notice->fetch()) {
            
            $data = Notice::staticGet('id',$notice->id);
            
            $orign = clone($data);
            
            $data->content_type = NOTICE::CONTENT_TYPE_ACTIVITY;
            
            if (!$data->update($orign)) {
                echo 'profile update error'.$data->id;
                echo '<br>';
            }
        }
        
    }
}