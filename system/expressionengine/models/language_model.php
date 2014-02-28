<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
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
	 * @access	public
	 * @return	array
	 */
	function language_pack_names()
	{
		$source_dir = APPPATH.'language/';

		$dirs = array();

		if ($fp = @opendir($source_dir))
		{
			while (FALSE !== ($file = readdir($fp)))
			{
				if (is_dir($source_dir.$file) && substr($file, 0, 1) != ".")
				{
					$dirs[$file] = ucfirst($file);
				}
			}
			closedir($fp);
		}

		 return $dirs;
	}

}

/* End of file language_model.php */
/* Location: ./system/expressionengine/models/language_model.php */