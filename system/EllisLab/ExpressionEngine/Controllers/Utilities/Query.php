<?php

namespace EllisLab\ExpressionEngine\Controllers\Utilities;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * ExpressionEngine CP Query Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Query extends Utilities {

	/**
	 * Query form
	 */
	public function index()
	{
		// Super Admins only, please
		if (ee()->session->userdata('group_id') != '1')
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'thequery',
				 'label'   => 'lang:sql_query_to_run',
				 'rules'   => 'required'
			),
			array(
				'field' => 'password_auth',
				'label' => 'lang:current_password',
				'rules' => 'required|auth_password'
			)
		));

		if (ee()->form_validation->run() !== FALSE)
		{
			// Do something...
		}

		ee()->view->cp_page_title = lang('sql_query_form');
		ee()->cp->render('utilities/query');
	}
}
// END CLASS

/* End of file Query.php */
/* Location: ./system/expressionengine/controllers/cp/Utilities/Query.php */
