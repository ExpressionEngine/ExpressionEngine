<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed.');

ee()->load->library('logger');
ee()->logger->deprecated('3.4.0', 'ee()->config->loadFile("doctypes") to load this config file', TRUE, 604800);

$_doctypes = ee()->config->loadFile('doctypes');

// EOF
