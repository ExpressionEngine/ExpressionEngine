<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed.');
}
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * ExpressionEngine Country Codes
 */
ee()->load->library('logger');
ee()->logger->deprecated('3.4.0', 'ee()->config->loadFile("countries") to load this config file', true, 604800);

$conf = ee()->config->loadFile('countries');

$countries = $conf['countries'];
$timezones = $conf['timezones'];

// EOF
