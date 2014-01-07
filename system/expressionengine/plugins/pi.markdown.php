<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.7
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Markdown Plugin
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			EllisLab Dev Team
 * @copyright		Copyright (c) 2004 - 2013, EllisLab, Inc.
 * @link			http://ellislab.com
 */

$plugin_info = array(
	'pi_name'		=> 'Markdown',
	'pi_version'	=> '1.0',
	'pi_author'		=> 'EllisLab',
	'pi_author_url'	=> 'http://ellislab.com/',
	'pi_description'=> 'Parse text using Markdown and Smartypants',
	'pi_usage'		=> Markdown::usage()
);


class Markdown {

	public $return_data;

	/**
	 * Constructor
	 */
	public function __construct($tagdata = '')
	{
		$tagdata		= (empty($tagdata)) ? ee()->TMPL->tagdata : $tagdata;
		$encode_ee_tags	= ee()->TMPL->fetch_param('encode_ee_tags', 'yes');
		$smartypants	= ee()->TMPL->fetch_param('smartypants', 'yes');

		ee()->load->library('typography');
		$this->return_data = ee()->typography->markdown(
			$tagdata,
			compact('encode_ee_tags', 'smartypants')
		);

		return $this->return_data;
	}

	// ----------------------------------------------------------------

	/**
	 * Plugin Usage
	 */
	public static function usage()
	{
		ob_start();
?>
This plugin parses text using Markdown and Smartypants. To use this plugin wrap
any text in this tag pair:

{exp:markdown}
Text to be **parsed**.
{/exp:markdown}

There are two parameters you can set:

- encode_ee_tags - ('yes'/'no') defaults to 'yes', when set to 'no' allows EE
  code to be rendered
- smartypants - ('yes'/'no') defaults to 'yes', when set to 'no' stops
  SmartyPants from running which leaves your quotes and hyphens alone
<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}


/* End of file pi.markdown.php */
/* Location: /system/expressionengine/third_party/markdown/pi.markdown.php */
