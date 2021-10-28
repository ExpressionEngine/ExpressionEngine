<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed.');
}
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * ExpressionEngine CAPTCHA Word Dictionary
 */
ee()->load->library('logger');
ee()->logger->deprecated('3.4.0', 'ee()->config->loadFile("captcha") to load this config file', true, 604800);

$words = ee()->config->loadFile('captcha');

// EOF
