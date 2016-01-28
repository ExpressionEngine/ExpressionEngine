<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.7
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Form Javascript Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Channel_form_javascript
{
	private $js_path;

	/**
	 * Constructor
	 */
	public function __construct($params = array())
	{
		$this->js_path = PATH_THEMES.'asset/javascript/'.PATH_JS.'/';

		if ( ! defined('PATH_JQUERY'))
		{
			define('PATH_JQUERY', $this->js_path.'jquery/');
		}

		ee()->lang->loadfile('jquery');
	}

	// --------------------------------------------------------------------

	/**
	 * Combo Load
	 */
	public function combo_load()
	{
		ee()->load->library('javascript_loader');
		ee()->javascript_loader->combo_load();

		if (ee()->input->get('include_jquery') == 'y')
		{
			ee()->output->set_output(file_get_contents(PATH_JQUERY.'jquery.js')."\n\n".ee()->output->get_output());
		}

		if (ee()->input->get('use_live_url') == 'y')
		{
			ee()->output->append_output(ee()->channel_form->_url_title_js()."\n\n");
		}

		ee()->load->helper('smiley');

		ee()->output->append_output(((PATH_JS !== 'src') ? str_replace(array("\n", "\t"), '', smiley_js('', '', FALSE)) : smiley_js('', '', FALSE))."\n\n");

		ee()->output->append_output(file_get_contents($this->js_path.'channel_form.js'));

		ee()->output->set_header('Content-Length: '.strlen(ee()->output->get_output()));
	}
}

// EOF
