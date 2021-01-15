<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// ExpressionEngine Config Items
// Find more configs and overrides at
// https://docs.expressionengine.com/latest/general/system_configuration_overrides.html

$config['save_tmpl_files'] = 'n';
$config['legacy_member_templates'] = 'y';

$config['app_version'] = '6.0.1';
$config['encryption_key'] = '4b9e521eb02751d8466a3e9b764524aff14b91ad';
$config['session_crypt_key'] = '1f307a8afe66e692c2689508a5d9f783606379a8';
$config['base_path'] = $_SERVER['DOCUMENT_ROOT'];
$config['base_url'] = 'http://localhost:8888/';
$config['site_url'] = $config['base_url'];
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
