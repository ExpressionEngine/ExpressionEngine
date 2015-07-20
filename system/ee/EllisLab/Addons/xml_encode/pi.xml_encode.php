<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Xml_encode Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			EllisLab Dev Team
 * @copyright		Copyright (c) 2004 - 2015, EllisLab, Inc.
 * @link			http://ellislab.com
 */


class Xml_encode {

	var $return_data;

	/**
	 * Constructor
	 *
	 */
	public function __construct($str = '')
	{
		$protect_all = (ee()->TMPL->fetch_param('protect_entities') === 'yes') ? TRUE : FALSE;

		$str = ($str == '') ? ee()->TMPL->tagdata : $str;

		// Load the XML Helper
		ee()->load->helper('xml');

		$str = xml_convert(strip_tags($str), $protect_all);

		// Strip [email] tags
		$str = preg_replace("/\[email=(.*?)\](.*?)\[\/email\]/i", '\\2', $str);
		$str = preg_replace("/\[email\](.*?)\[\/email\]/i", '\\1', $str);

		$this->return_data = trim(str_replace('&nbsp;', '&#160;', $str));
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file pi.xml_encode.php */
/* Location: ./system/expressionengine/plugins/pi.xml_encode.php */