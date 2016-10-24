<?php

namespace EllisLab\ExpressionEngine\Controller\Logs;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Logs extends CP_Controller {

	var $perpage		= 25;
	var $params			= array();
	var $base_url;
	protected $search_installed = FALSE;

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

		$this->base_url = ee('CP/URL')->make('logs');

		$this->search_installed = ee('Model')->get('Module')
			->filter('module_name', 'Search')
			->first();

		$this->search_installed = ! is_null($this->search_installed);

		$this->params['perpage'] = $this->perpage; // Set a default

		$this->generateSidebar();

		// Add in any submitted search phrase
		ee()->view->search_value = htmlentities(ee()->input->get_post('search'), ENT_QUOTES, 'UTF-8');
		if ( ! empty(ee()->view->search_value))
		{
			$this->base_url->setQueryStringVariable('search', ee()->input->get_post('search'));
		}
	}

	protected function generateSidebar()
	{
		$sidebar = ee('CP/Sidebar')->make();
		$logs = $sidebar->addHeader(lang('logs'))
			->addBasicList();

		if (ee()->session->userdata('group_id') == 1)
		{
			$item = $logs->addItem(lang('developer_log'), ee('CP/URL')->make('logs/developer'));
		}

		$item = $logs->addItem(lang('cp_log'), ee('CP/URL')->make('logs/cp'));
		$item = $logs->addItem(lang('throttle_log'), ee('CP/URL')->make('logs/throttle'));
		$item = $logs->addItem(lang('email_log'), ee('CP/URL')->make('logs/email'));

		if ($this->search_installed)
		{
			$item = $logs->addItem(lang('search_log'), ee('CP/URL')->make('logs/search'));
		}
	}


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
			ee()->functions->redirect(ee('CP/URL')->make('logs/developer'));
		}
		else
		{
			ee()->functions->redirect(ee('CP/URL')->make('logs/cp'));
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
		if ( ! ee()->cp->allowed_group('can_access_logs'))
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

// EOF
