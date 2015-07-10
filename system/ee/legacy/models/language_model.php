<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
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

/* End of file language_model.php */
/* Location: ./system/expressionengine/models/language_model.php */
