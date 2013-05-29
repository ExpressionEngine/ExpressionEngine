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
	'pi_description'=> '...',
	'pi_usage'		=> Markdown::usage()
);


class Markdown {

	public $return_data;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$encode_ee_tags	= ee()->TMPL->fetch_param('encode_ee_tags', 'yes');
		$smartypants	= ee()->TMPL->fetch_param('smartypants', 'yes');

		$this->return_data = ee()->typography->markdown(
			ee()->TMPL->tagdata,
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

 Since you did not provide instructions on the form, make sure to put plugin documentation here.
<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}


/* End of file pi.markdown.php */
/* Location: /system/expressionengine/third_party/markdown/pi.markdown.php */
