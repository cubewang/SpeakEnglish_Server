
<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class ApiDataMigrationProfileAction extends ApiAction
{
    function prepare($args)
    {
        parent::prepare($args);
        
        return true;
    }
    
    function handle($args)
    {
        parent::handle($args);
        
        $profile = new Profile();
        $profile->find();
        
        $server = common_config('site', 'server');
        $path = common_config('site', 'path');
        
        $mainpath = 'http://'.$server.'/'.$path.'/index.php/';//'http://192.168.1.123/statusnet_copy/index.php/';
        
        while ($profile->fetch()) {
            //echo $this->ID;
            //$store[] = $object; // builds an array of object lines.
            $nickname = $profile->nickname;
            $profileurl = $mainpath.$nickname;
            
            $data = Profile::staticGet('id',$profile->id);
            
            $orign = clone($data);
            
            $data->profileurl = $profileurl;
            
            if (!$data->update($orign)) {
                echo 'profile update error'.$data->id;
                echo '<br>';
            }
        }
        
    }
}