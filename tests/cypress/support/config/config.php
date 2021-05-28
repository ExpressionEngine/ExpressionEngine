<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// ExpressionEngine Config Items
// Find more configs and overrides at
// https://docs.expressionengine.com/latest/general/system_configuration_overrides.html

$config['save_tmpl_files'] = 'n';
$config['legacy_member_templates'] = 'y';

$config['app_version'] = '6.1.0';
$config['encryption_key'] = '4b9e521eb02751d8466a3e9b764524aff14b91ad';
$config['session_crypt_key'] = '1f307a8afe66e692c2689508a5d9f783606379a8';
$config['base_path'] = $_SERVER['DOCUMENT_ROOT'];
$config['base_url'] = 'http://localhost:8888/';
$config['site_url'] = $config['base_url'];
<<<<<<< HEAD
$config['app_version'] = '6.0.4';
$config['license_contact'] = 'ellislab.developers@gmail.com';
$config['license_number'] = '1234-5678-9123-4567';
$config['debug'] = '1';
$config['cp_url'] = 'http://localhost:8888/system/index.php';
$config['theme_folder_url'] = 'http://localhost:8888/themes/';
$config['theme_folder_path'] = '/home/ubuntu/ExpressionEngine/themes/';
$config['doc_url'] = 'https://ellislab.com/expressionengine/user-guide/';
$config['is_system_on'] = 'y';
$config['allow_extensions'] = 'y';
$config['cookie_prefix'] = '';
$config['cache_driver'] = 'file';
=======
>>>>>>> 6.dev
$config['database'] = array(
	'expressionengine' => array(
		'hostname' => '127.0.0.1',
		'database' => 'ee-test',
		'username' => 'root',
		'password' => 'root',
		'dbprefix' => 'exp_',
		'char_set' => 'utf8mb4',
		'dbcollat' => 'utf8mb4_unicode_ci',
		'port'     => ''
	),
);

// EOF
