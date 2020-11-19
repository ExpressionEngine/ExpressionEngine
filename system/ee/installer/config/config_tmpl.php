<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// ExpressionEngine Config Items
// Find more configs and overrides at
// https://docs.expressionengine.com/latest/general/system_configuration_overrides.html

$config['app_version'] = '{app_version}';
$config['encryption_key'] = '{encryption_key}';
$config['session_crypt_key'] = '{session_crypt_key}';
$config['database'] = array(
	'expressionengine' => array(
		'hostname' => '{db_hostname}',
		'database' => '{db_database}',
		'username' => '{db_username}',
		'password' => '{db_password}',
		'dbprefix' => '{db_dbprefix}',
		'char_set' => '{db_char_set}',
		'dbcollat' => '{db_collat}',
		'port'     => '{db_port}'
	),
);
{extra_config}
// EOF
