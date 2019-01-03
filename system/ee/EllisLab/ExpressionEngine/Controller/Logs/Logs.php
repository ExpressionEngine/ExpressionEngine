<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Logs;

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;

/**
 * Logs Controller
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
			show_error(lang('unauthorized_access'), 403);
		}

		$this->base_url = ee('CP/URL')->make('logs');

		$this->search_installed = ee('Model')->get('Module')
			->filter('module_name', 'Search')
			->first();

		$this->search_installed = ! is_null($this->search_installed);

		$this->params['perpage'] = $this->perpage; // Set a default

		$this->generateSidebar();
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

		if (ee('Permission')->has('can_manage_consents'))
		{
			$item = $logs->addItem(lang('consent_log'), ee('CP/URL')->make('logs/consent'));
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
			show_error(lang('unauthorized_access'), 403);
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
