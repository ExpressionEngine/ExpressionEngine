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
 * ExpressionEngine CP SQL Manager Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Sql extends Utilities {

	/**
	 * SQL Manager
	 */
	public function index()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ($action = ee()->input->post('table_action'))
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
				show_error(lang('unauthorized_access'));
			}
			// Must have selected tables
			elseif (empty($tables))
			{
				ee()->view->set_message('issue', lang('cp_message_issue'), lang('no_tables_selected'));
			}
			else
			{
				// Perform the action on each selected table and store the results
				foreach ($tables as $table)
				{
					$query = ee()->db->query("{$action} TABLE ".ee()->db->escape_str($table));

					foreach ($query->result_array() as $row)
					{
						foreach ($row as $k => $v)
						{
							$vars['results'][$table][] = $v;
						}
					}
				}
				// TODO: Need to know what James wants to do with the
				// results from the table action
			}
		}

		ee()->load->model('tools_model');
		$vars = ee()->tools_model->get_sql_info();
		$vars += ee()->tools_model->get_table_status();
		
		ee()->view->cp_page_title = lang('sql_manager');
		ee()->cp->render('utilities/sql-manager', $vars);
	}
}
// END CLASS

/* End of file Query.php */
/* Location: ./system/expressionengine/controllers/cp/Utilities/Query.php */
