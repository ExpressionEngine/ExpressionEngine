<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| Foreign Characters
| -------------------------------------------------------------------
| This file contains an array of foreign characters for transliteration
| conversion used by the Text helper
|
*/

ee()->load->library('logger');
ee()->logger->deprecated('3.4.0', 'ee()->config->loadFile("foreign_chars") to load this config file', TRUE, 604800);

$foreign_characters = ee()->config->loadFile('foreign_chars');

// EOF
