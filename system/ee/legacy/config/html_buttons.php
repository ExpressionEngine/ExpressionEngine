<?php
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Pre Defined HTML Buttons
 *
 * @package		ExpressionEngine
 * @subpackage	Config
 * @category	Config
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

ee()->load->library('logger');
ee()->logger->developer('Manually including config files has been deprecated, use ee()->config->loadFile() instead', TRUE, 604800);

$conf = ee()->config->loadFile('html_buttons');

$installation_defaults = $conf['defaults'];
$predefined_buttons = $conf['buttons'];

// EOF
