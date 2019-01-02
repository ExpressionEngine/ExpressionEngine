<?php

/*
|--------------------------------------------------------------------------
| ExpressionEngine Config Items
|--------------------------------------------------------------------------
|
| The following items are for use with ExpressionEngine.  The rest of
| the config items are for use with CodeIgniter, some of which are not
| observed by ExpressionEngine, e.g. 'permitted_uri_chars'
|
*/

// Reset opcache, otherwise this file gets cached with old values on
// Circle's PHP 5.5 machine
if (function_exists('opcache_reset'))
{
    opcache_reset();
}

$config['site_url'] = 'http://ee2.test:8080/';
$config['app_version'] = '5.1.2';
$config['license_contact'] = 'ellislab.developers@gmail.com';
$config['license_number'] = '1234-5678-9123-4567';
$config['debug'] = '1';
$config['cp_url'] = 'http://ee2.test:8080/system/index.php';
$config['theme_folder_url'] = 'http://ee2.test:8080/themes/';
$config['theme_folder_path'] = '/home/ubuntu/ExpressionEngine/themes/';
$config['doc_url'] = 'https://ellislab.com/expressionengine/user-guide/';
$config['is_system_on'] = 'y';
$config['allow_extensions'] = 'y';
$config['cookie_prefix'] = '';
$config['cache_driver'] = 'file';
$config['database'] = array(
	'expressionengine' => array(
		'database' => 'circle_test',
		'username' => 'ubuntu'
	),
);
$config['encryption_key'] = '631cbbc6e1de577805226ce0d1cc6c144cfd6c86';
$config['session_crypt_key'] = '549782140d653f8865b6cbabb36600766d5e25a0';

// EOF
