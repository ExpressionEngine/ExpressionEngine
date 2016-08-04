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
 * ExpressionEngine CAPTCHA Word Dictionary
 *
 * @package		ExpressionEngine
 * @subpackage	Config
 * @category	Config
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

ee()->load->library('logger');
ee()->logger->developer('Manually including config files has been deprecated, use ee()->config->loadFile() instead', TRUE, 604800);

$words = ee()->config->loadFile('captcha');

// EOF
