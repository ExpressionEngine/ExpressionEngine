<?php

namespace EllisLab\ExpressionEngine\Controllers\Logs;

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
class Logs extends CP_Controller {

	var $perpage		= 20;
	var $params			= array();
	var $base_url;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		ee()->lang->loadfile('logs');

		if ( ! ee()->cp->allowed_group('can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->base_url = new CP\URL('logs', ee()->session->session_id());

		// Sidebar Menu
		$menu = array(
			'logs',
			array(
				'developer_log' => cp_url('logs/developer'),
				'cp_log'        => cp_url('logs/cp'),
				'throttle_log'  => cp_url('logs/throttle'),
				'email_log'     => cp_url('logs/email'),
				'search_log'    => cp_url('logs/search'),
			)
		);

		if (ee()->session->userdata('group_id') != 1)
		{
			unset($menu[1]['developer_log']);
		}

		ee()->menu->register_left_nav($menu);

		$this->params['perpage'] = $this->perpage; // Set a default

		// Add in any submitted search phrase
		ee()->view->search_value = ee()->input->get_post('search');
		if ( ! empty(ee()->view->search_value))
		{
			$this->base_url->setQueryStringVariable('search', ee()->view->search_value);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */
	public function index()
	{
		if (ee()->session->userdata('group_id') == 1)
		{
			ee()->functions->redirect(cp_url('logs/developer'));
		}
		else
		{
			ee()->functions->redirect(cp_url('logs/cp'));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes log entries, either all at once, or one at a time
	 *
	 * @param string	$model		The name of the model to pass to
	 *								ee('Model')->get()
	 * @param string	$log_type	The text used in the delete message
	 *								describing the type of log deleted
	 */
	protected function delete($model, $log_type)
	{
		if ( ! ee()->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$id = ee()->input->post('delete');

		$flashdata = FALSE;
		if (strtolower($id) == 'all')
		{
			$id = NULL;
			$flashdata = TRUE;
		}

		$query = ee('Model')->get($model, $id);

		$count = $query->count();
		$query->all()->delete();

		$message = sprintf(lang('logs_deleted_desc'), $count, lang($log_type));

		ee()->view->set_message('success', lang('logs_deleted'), $message, $flashdata);
	}
}
// END CLASS

/* End of file Logs.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Logs/Logs.php */