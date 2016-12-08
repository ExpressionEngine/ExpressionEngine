<?php

namespace EllisLab\ExpressionEngine\Controller\Utilities;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP SQL Manager Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Sql extends Utilities {

	/**
	 * SQL Manager
	 */
	public function index()
	{
		if ( ! $this->cp->allowed_group('can_access_sql_manager'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if (($action = ee()->input->post('table_action')) && ! ee()->input->post('search_form'))
		{
			$tables = ee()->input->post('table');

			// Must select an action
			if ($action == 'none')
			{
				ee()->view->set_message('issue', lang('cp_message_issue'), lang('no_action_selected'));
			}
			// Must be either OPTIMIZE or REPAIR
			elseif ( ! in_array($action, array('OPTIMIZE', 'REPAIR')))
			{
				show_error(lang('unauthorized_access'), 403);
			}
			// Must have selected tables
			elseif (empty($tables))
			{
				ee()->view->set_message('issue', lang('cp_message_issue'), lang('no_tables_selected'));
			}
			else
			{
				return $this->opResults();
			}
		}

		ee()->load->library('encrypt');
		ee()->load->model('tools_model');
		$vars = ee()->tools_model->get_sql_info();
		$vars += ee()->tools_model->get_table_status();

		foreach ($vars['status'] as $table)
		{
			$data[] = array(
				$table['name'],
				$table['rows'],
				$table['size'],
				array('toolbar_items' => array(
					'view' => array(
						'href' => ee('CP/URL')->make(
							'utilities/query/run-query/'.$table['name'],
							array(
								'thequery' => rawurlencode(base64_encode('SELECT * FROM '.$table['name'])),
								'signature' => ee()->encrypt->sign('SELECT * FROM '.$table['name'])
							)
						),
						'title' => lang('view')
					)
				)),
				array(
					'name' => 'table[]',
					'value' => $table['name']
				)
			);
		}

		$table = ee('CP/Table', array('autosort' => TRUE, 'autosearch' => TRUE, 'limit' => 0));
		$table->setColumns(
			array(
				'table_name',
				'records',
				'size' => array(
					'encode' => FALSE
				),
				'manage' => array(
					'type'	=> CP\Table::COL_TOOLBAR
				),
				array(
					'type'	=> CP\Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_tables_match');
		$table->setData($data);

		$vars['table'] = $table->viewData(ee('CP/URL')->make('utilities/sql'));

		ee()->view->cp_page_title = lang('sql_manager');
		ee()->view->table_heading = lang('database_tables');

		// Set search results heading
		if ( ! empty($vars['table']['search']))
		{
			ee()->view->table_heading = sprintf(
				lang('search_results_heading'),
				$vars['table']['total_rows'],
				$vars['table']['search']
			);
		}

		ee()->cp->render('utilities/sql/manager', $vars);
	}

	/**
	 * Results of table operation
	 */
	public function opResults()
	{
		$action = ee()->input->post('table_action');
		$tables = ee()->input->post('table');

		// This page can be invoked from a GET request due various ways to
		// sort and filter the table, so we need to check for cached data
		// from the original request
		if ($action == FALSE && $tables == FALSE)
		{
			$cache = ee()->cache->get('sql-op-results', \Cache::GLOBAL_SCOPE);

			if (empty($cache))
			{
				return $this->index();
			}
			else
			{
				$action = $cache['action'];
				$data = $cache['data'];
			}
		}
		else
		{
			// Perform the action on each selected table and store the results
			foreach ($tables as $table)
			{
				$query = ee()->db->query("{$action} TABLE ".ee()->db->escape_str($table));

				foreach ($query->result_array() as $row)
				{
					$row = array_values($row);
					$row[0] = $table;
					$data[] = array(
						$row[0],
						$row[2],
						$row[3]
					);
				}
			}

			$cache = array(
				'action' => $action,
				'data' => $data
			);

			// Cache it so we can access it on subsequent page requests due
			// to sorting and searching of the table
			ee()->cache->save('sql-op-results', $cache, 3600, \Cache::GLOBAL_SCOPE);
		}

		// Set up our table with automatic sorting and search capability
		$table = ee('CP/Table', array('autosort' => TRUE, 'autosearch' => TRUE, 'limit' => 0));
		$table->setColumns(array(
			'table',
			'status' => array(
				'type' => CP\Table::COL_STATUS
			),
			'message'
		));
		$table->setData($data);
		$table->setNoResultsText('no_tables_match');
		$vars['table'] = $table->viewData(ee('CP/URL')->make('utilities/sql/op-results'));

		ee()->view->cp_page_title = lang(strtolower($action).'_tables_results');
		ee()->cp->set_breadcrumb(ee('CP/URL')->make('utilities/sql'), lang('sql_manager'));
		return ee()->cp->render('utilities/sql/ops', $vars);
	}
}
// END CLASS

// EOF
