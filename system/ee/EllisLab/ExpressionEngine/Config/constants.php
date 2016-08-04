<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

return array(
/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
'FILE_READ_MODE' => 0644,
'FILE_WRITE_MODE' => 0666,
'DIR_READ_MODE' => 0755,
'DIR_WRITE_MODE' => 0777,

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
'FOPEN_READ' => 							'rb',
'FOPEN_READ_WRITE' =>						'r+b',
'FOPEN_WRITE_CREATE_DESTRUCTIVE' => 		'wb',	// truncates existing file data, use with care
'FOPEN_READ_WRITE_CREATE_DESTRUCTIVE' => 	'w+b', // truncates existing file data, use with care
'FOPEN_WRITE_CREATE' => 					'ab',
'FOPEN_READ_WRITE_CREATE' => 				'a+b',
'FOPEN_WRITE_CREATE_STRICT' => 			'xb',
'FOPEN_READ_WRITE_CREATE_STRICT' =>		'x+b',

);
// EOF
