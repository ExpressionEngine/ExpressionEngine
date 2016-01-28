<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Language Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Language_model extends CI_Model {

	/**
	 * Language Pack Names
	 *
	 * @return	array
	 */
	public function language_pack_names()
	{
		ee()->logger->deprecated('3.0', 'EE_lang::language_pack_names()');
		ee()->load->model('language_model');
		return ee()->lang->language_pack_names();
	}

}

// EOF
