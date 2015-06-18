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

	public static $name        = 'XML Encode';
	public static $version     = '1.3';
	public static $author      = 'Rick Ellis';
	public static $author_url  = 'http://ellislab.com/';
	public static $description = 'XML Encoding plugin';
	public static $typography  = FALSE;

	var $return_data;

	/**
	 * Constructor
	 *
	 */
	function Xml_encode($str = '')
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

	/**
	 * Usage
	 *
	 * Plugin Usage
	 *
	 * @access	public
	 * @return	string
	 */
	public static function usage()
	{
		ob_start();
		?>
			This plugin converts reserved XML characters to entities.  It is used in the RSS templates.

			To use this plugin, wrap anything you want to be processed by it between these tag pairs:

			{exp:xml_encode}

			text you want processed

			{/exp:xml_encode}

			Note: Because quotes are converted into &quot; by this plugin, you cannot use
			ExpressionEngine conditionals inside of this plugin tag.

			If you have existing entities in the text that you do not wish to be converted, you may use
			the parameter protect_entities="yes", e.g.:

			{exp:xml_encode}Text &amp; Entities{/exp:xml_encode}

			results in: Text &amp;amp; Entities

			{exp:xml_encode protect_entities="yes"}Text &amp; Entities{/exp:xml_encode}

			results in: Text &amp; Entities

			Version 1.3
		******************
		- Updated plugin to be 2.0 compatible

		<?php
		$buffer = ob_get_contents();

		ob_end_clean();

		return $buffer;
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file pi.xml_encode.php */
/* Location: ./system/expressionengine/plugins/pi.xml_encode.php */