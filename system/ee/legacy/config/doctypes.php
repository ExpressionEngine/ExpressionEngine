<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

ee()->load->library('logger');
ee()->logger->developer('Manually including config files has been deprecated, use ee()->config->loadFile() instead', TRUE, 604800);

$_doctypes = ee()->config->loadFile('doctypes');

// EOF
