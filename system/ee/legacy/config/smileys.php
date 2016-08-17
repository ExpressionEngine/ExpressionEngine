<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| SMILEYS
| -------------------------------------------------------------------
| This file contains an array of smileys for use with the emoticon helper.
| Individual images can be used to replace multiple simileys.  For example:
| :-) and :) use the same image replacement.
|
| Please see user guide for more info:
| http://codeigniter.com/user_guide/helpers/smiley_helper.html
|
*/

ee()->load->library('logger');
ee()->logger->deprecated('3.4.0', 'ee()->config->loadFile("smileys") to load this config file', TRUE, 604800);

$smileys = ee()->config->loadFile('smileys');

// EOF
