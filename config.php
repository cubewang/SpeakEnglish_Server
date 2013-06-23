<?php
if (!defined('STATUSNET') && !defined('LACONICA')) { exit(1); }

$config['site']['name'] = 'iknow';

$config['site']['server'] = '192.168.1.200';
$config['site']['path'] = 'statusnet'; 

$config['db']['database'] = 'mysqli://root:root@localhost/statusnet';

$config['db']['type'] = 'mysql';

$config['site']['profile'] = 'public';

$config['attachments']['uploads'] = true;
$config['attachments']['supported'] = true; //allow all file types to be uploaded
$config['attachments']['file_quota'] = 5000000;
$config['attachments']['user_quota'] = 50000000;
$config['attachments']['monthly_quota'] = 15000000;
$config['attachments']['path'] = $config['site']['path']."/file/"; //ignored if site is private
$config['attachments']['dir'] = INSTALLDIR . '/file/'; 

addPlugin('ApiAccount');
addPlugin('ApiMessageBox');

addPlugin('InProcessCache');
addPlugin('Memcache', array('servers' => '127.0.0.1:11211'));

addPlugin('ApiComments');
addPlugin('ApiSuggestions');
addPlugin('ApiDataMigration');

unset($config['plugins']['default']['Mapstraction']);
unset($config['plugins']['default']['OpenID']);
unset($config['plugins']['default']['RSSCloud']);
unset($config['plugins']['default']['WikiHashtags']);
unset($config['plugins']['default']['TightUrl']);
unset($config['plugins']['default']['SimpleUrl']);
unset($config['plugins']['default']['PtitUrl']);
unset($config['plugins']['default']['Geonames']);
unset($config['plugins']['default']['Bookmark']);

unset($config['plugins']['default']['Poll']);
unset($config['plugins']['default']['QnA']);
unset($config['plugins']['default']['Event']);

$config['db']['schemacheck'] = 'script';