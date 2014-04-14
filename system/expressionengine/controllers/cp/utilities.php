<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Utilities extends CP_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		// Register our menu
		ee()->menu->register_left_nav(array(
			'communicate' => cp_url('utilities/communicate'),
			'cp_translation',
			array(
				// Show installed languages?
				'English (Default)' => cp_url('utilities/communicate')
			),
			'php_info' => cp_url('utilities/php'),
			'import_tools',
			array(
				'file_converter' => cp_url('utilities/import-converter'),
				'member_import' => cp_url('utilities/member-import')
			),
			'sql_manager' => cp_url('utilities/sql'),
			array(
				'query_form' => cp_url('utilities/query')
			),
			'data_operations',
			array(
				'cache_manager' => cp_url('utilities/cache'),
				'statistics' => cp_url('utilities/stats'),
				'search_and_replace' => cp_url('utilities/sandr')
			)
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Index
	 *
	 * @access	public
	 * @return	void
	 */
	public function index()
	{
		// Will redirect based on permissions later
		$this->communicate();
	}

	// --------------------------------------------------------------------

	/**
	 * Communicate
	 *
	 * @access	public
	 * @return	void
	 */
	public function communicate()
	{
		ee()->view->cp_page_title = lang('communicate');
		ee()->cp->render('utilities/communicate');
	}

	// --------------------------------------------------------------------

	/**
	 * PHP Info
	 *
	 * @access	public
	 * @return	void
	 */
	public function php()
	{
		// Here, we'll capture the output of PHP Info and modify its markup
		// to appear in our control panel
		ob_start();

		phpinfo();

		$buffer = ob_get_contents();

		ob_end_clean();

		$output = (preg_match("/<body.*?".">(.*)<\/body>/is", $buffer, $match)) ? $match['1'] : $buffer;
		$output = preg_replace("/width\=\".*?\"/", "width=\"100%\"", $output);
		$output = preg_replace("/<hr.*?>/", "<br />", $output); // <?
		$output = preg_replace("/<a href=\"http:\/\/www.php.net\/\">.*?<\/a>/", "", $output);
		$output = preg_replace("/<a href=\"http:\/\/www.zend.com\/\">.*?<\/a>/", "", $output);
		$output = preg_replace("/<a.*?<\/a>/", "", $output);// <?
		$output = preg_replace("/<th(.*?)>/", "<th \\1 >", $output);
		$output = preg_replace("/<tr(.*?).*?".">/", "<tr \\1>\n", $output);
		$output = preg_replace("/<td.*?".">/", "<td valign=\"top\">", $output);
		$output = preg_replace("/<h2 align=\"center\">PHP License<\/h2>.*?<\/table>/si", "", $output);
		$output = preg_replace("/ align=\"center\"/", "", $output);
		$output = preg_replace("/<table(.*?)bgcolor=\".*?\">/", "\n\n<table\\1>", $output);
		$output = preg_replace("/<table(.*?)>/", "\n\n<table\\1 class=\"mainTable\" cellspacing=\"0\">", $output);
		$output = preg_replace("/<h2>PHP License.*?<\/table>/is", "", $output);
		$output = preg_replace("/<br \/>\n*<br \/>/is", "", $output);
		$output = preg_replace('/<h(1|2)\s*(class="p")?/i', '<h\\1', $output);
		$output = str_replace("<h1></h1>", "", $output);
		$output = str_replace("<h2></h2>", "", $output);

		ee()->view->phpinfo = $output;
		ee()->view->cp_page_title = lang('php_info');

		// Need a separate page title because this lang key has an abbr tag,
		// can't also use it in <title/>
		ee()->view->page_title = sprintf(lang('php_info_title'), phpversion());

		ee()->cp->add_to_head($this->view->head_link('css/v3/phpinfo.css'));
		ee()->cp->render('utilities/php-info');
	}

	// --------------------------------------------------------------------

	/**
	 * Cache Manager
	 *
	 * @access	public
	 * @return	void
	 */
	public function cache()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->view->cp_page_title = lang('cache_manager');
		ee()->cp->render('utilities/cache');
	}

	// --------------------------------------------------------------------

	/**
	 * POST handler for the Cache Manager form
	 *
	 * @access	public
	 * @return	void
	 */
	public function clear_caches()
	{
		// Validation
		if (AJAX_REQUEST)
		{
			if ( ! ee()->input->post('cache_type'))
			{
				ee()->output->send_ajax_response(array('error' => lang('caches_cleared_error')));
			}
			else
			{
				ee()->output->send_ajax_response('success');
			}
		}

		if (ee()->input->post('cache_type'))
		{
			foreach (ee()->input->post('cache_type') as $type)
			{
				ee()->functions->clear_caching($type);
			}

			ee()->session->set_flashdata('success', lang('caches_cleared'));
		}
		else
		{
			ee()->session->set_flashdata('issue', lang('caches_cleared_error'));
		}

		ee()->functions->redirect(cp_url('utilities/cache'));
	}
}

/* End of file ee.php */
/* Location: ./system/expressionengine/controllers/ee.php */