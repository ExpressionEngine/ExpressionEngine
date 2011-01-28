<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
						'pi_name'			=> 'XML Encode',
						'pi_version'		=> '1.3',
						'pi_author'			=> 'Rick Ellis',
						'pi_author_url'		=> 'http://expressionengine.com/',
						'pi_description'	=> 'XML Encoding plugin.',
						'pi_usage'			=> Xml_encode::usage()
					);


/**
 * Xml_encode Class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			ExpressionEngine Dev Team
 * @copyright		Copyright (c) 2004 - 2011, EllisLab, Inc.
 * @link			http://expressionengine.com/downloads/details/xml_encode/
 */


class Xml_encode {

	var $return_data;	
	
	/**
	 * Constructor
	 *
	 */
	function Xml_encode($str = '')
	{
		$this->EE =& get_instance();
		
		$protect_all = ($this->EE->TMPL->fetch_param('protect_entities') === 'yes') ? TRUE : FALSE;
		
		$str = ($str == '') ? $this->EE->TMPL->tagdata : $str;

		// Load the XML Helper
		$this->EE->load->helper('xml');
		
		$str = xml_convert(strip_tags($str), $protect_all);
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
	function usage()
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