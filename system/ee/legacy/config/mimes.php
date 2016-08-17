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
 * ExpressionEngine Allowed Mime Types
 *
 * These are the mime types that are allowed to be uploaded using the
 * upload class.  For security reasons the list is kept as small as
 * possible.  If you need to upload types that are not in the list you can
 * add them.
 *
 * @package		ExpressionEngine
 * @subpackage	Config
 * @category	Config
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

ee()->load->library('logger');
ee()->logger->deprecated('3.4.0', 'ee()->config->loadFile("mimes") to load this config file', TRUE, 604800);

$whitelist = ee()->config->loadFile('mimes');


// EOF
