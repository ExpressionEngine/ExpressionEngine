<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

/*
| -------------------------------------------------------------------
| USER AGENT TYPES
| -------------------------------------------------------------------
| This file contains four arrays of user agent data.  It is used by the
| User Agent Class to help identify browser, platform, robot, and
| mobile device data.  The array keys are used to identify the device
| and the array values are used to set the actual name of the item.
|
*/
ee()->load->library('logger');
ee()->logger->deprecated('3.4.0', 'ee()->config->loadFile("user_agents") to load this config file', true, 604800);

$conf = ee()->config->loadFile('user_agents');

$platforms = $conf['platforms'];
$browsers = $conf['browsers'];
$mobiles = $conf['mobiles'];
$robots = $conf['robots'];

// EOF
