<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed.');

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * ExpressionEngine Pre Defined HTML Buttons
 */

ee()->load->library('logger');
ee()->logger->deprecated('3.4.0', 'ee()->config->loadFile("html_buttons") to load this config file', TRUE, 604800);

$conf = ee()->config->loadFile('html_buttons');

$installation_defaults = $conf['defaults'];
$predefined_buttons = $conf['buttons'];

// EOF
