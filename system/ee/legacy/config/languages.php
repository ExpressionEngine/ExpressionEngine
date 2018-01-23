<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed.');

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * ExpressionEngine Language Codes
 */

ee()->load->library('logger');
ee()->logger->deprecated('3.4.0', 'ee()->config->loadFile("languages") to load this config file', TRUE, 604800);

$languages = ee()->config->loadFile('languages');


// EOF
