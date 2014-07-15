<?php

namespace EllisLab\ExpressionEngine\Controllers\Utilities;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Utilities Class
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

		ee()->lang->loadfile('utilities');

		// Register our menu
		ee()->menu->register_left_nav(array(
			'communicate' => cp_url('utilities/communicate'),
			array(
				'sent' => cp_url('utilities/communicate-sent')
			),
			'cp_translation',
			array(
				// Show installed languages?
				'English (Default)' => cp_url('utilities/communicate')
			),
			'php_info' => cp_url('utilities/php'),
			'import_tools',
			array(
				'file_converter' => cp_url('utilities/import_converter'),
				'member_import' => cp_url('utilities/member_import')
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
		ee()->functions->redirect(cp_url('utilities/communicate'));
	}
}
// END CLASS

/* End of file Utilities.php */
/* Location: ./system/expressionengine/controllers/cp/Utilities/Utilities.php */