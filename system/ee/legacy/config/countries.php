<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed.');
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * ExpressionEngine Country Codes
 */
ee()->load->library('logger');
ee()->logger->deprecated('3.4.0', 'ee()->config->loadFile("countries") to load this config file', TRUE, 604800);

$conf = ee()->config->loadFile('countries');

$countries = $conf['countries'];
$timezones = $conf['timezones'];

// EOF
