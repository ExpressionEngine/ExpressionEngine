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
class Utility extends CP_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		// Register our menu
		ee()->menu->register_left_nav(array(
			'communicate' => cp_url('utility/communicate'),
			'cp_translation',
			array(
				// Show installed languages?
				'English (Default)' => cp_url('utility/communicate')
			),
			'php_info' => cp_url('utility/php'),
			'import_tools',
			array(
				'file_converter' => cp_url('utility/import-converter'),
				'member_import' => cp_url('utility/member-import')
			),
			'sql_manager' => cp_url('utility/sql'),
			array(
				'query_form' => cp_url('utility/query')
			),
			'data_operations',
			array(
				'cache_manager' => cp_url('utility/cache'),
				'statistics' => cp_url('utility/stats'),
				'search_and_replace' => cp_url('utility/sandr')
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
		ee()->cp->render('utility/communicate');
	}
}

/* End of file ee.php */
/* Location: ./system/expressionengine/controllers/ee.php */