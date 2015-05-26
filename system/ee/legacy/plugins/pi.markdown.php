<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
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
 * @copyright		Copyright (c) 2004 - 2015, EllisLab, Inc.
 * @link			http://ellislab.com
 */

class Markdown {

	public static $name        = 'Markdown';
	public static $version     = '1.0';
	public static $author      = 'EllisLab';
	public static $author_url  = 'http://ellislab.com/';
	public static $description = 'Parse text using Markdown and Smartypants';
	public static $typography  = TRUE;

	public $return_data;

	public function __construct($tagdata = '')
	{
		$tagdata       = (empty($tagdata)) ? ee()->TMPL->tagdata : $tagdata;
		$smartypants   = ee()->TMPL->fetch_param('smartypants', 'yes');
		$convert_curly = ee()->TMPL->fetch_param('convert_curly', 'yes');

		ee()->load->library('typography');
		ee()->typography->convert_curly = get_bool_from_string($convert_curly);
		$this->return_data = ee()->typography->markdown(
			$tagdata,
			compact('smartypants')
		);

		return $this->return_data;
	}

	// -------------------------------------------------------------------------

	/**
	 * Plugin Usage
	 *
	 * @return string Usage documentation
	 */
	public static function usage()
	{
		$usage = array(
			'description'	=> 'This plugin parses text using Markdown and Smartypants. To use this plugin wrap any text in this tag pair.',
			'example'		=> '',
			'parameters'	=> array(
				'convert_curly'	=> array(
					'description'	=> "Defaults to <b>yes</b>. When set to <b>no</b> will not convert all curly brackets to entities, which can be useful to display variables.",
					'example'		=> ''
				),
				'smartypants'	=> array(
					'description'	=> "Defaults to <b>yes</b>. When set to <b>no</b> stops SmartyPants from running which leaves your quotes and hyphens alone.",
					'example'		=> ''
				)
			)
		);

		// Usage Example
		$usage['example'] = <<<'EXAMPLE'
{exp:markdown}
	Text to be **parsed**.
{/exp:markdown}
EXAMPLE;

		// convert_curly Example
		$usage['parameters']['convert_curly']['example'] = <<<'EXAMPLE'
{exp:markdown convert_curly="no"}
	Text to be **parsed**.
{/exp:markdown}
EXAMPLE;

		// smartypants Example
		$usage['parameters']['smartypants']['example'] = <<<'EXAMPLE'
{exp:markdown smartypants="no"}
	Text to be **parsed**.
{/exp:markdown}
EXAMPLE;
		return $usage;
	}
}


/* End of file pi.markdown.php */
/* Location: /system/expressionengine/third_party/markdown/pi.markdown.php */
