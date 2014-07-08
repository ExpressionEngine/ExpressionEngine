<?php

namespace EllisLab\ExpressionEngine\Controllers\Logs;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Pagination;

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
class Developer extends Logs {

	/**
	 * Shows Developer Log page
	 *
	 * @access public
	 * @return void
	 */
	public function index()
	{
		if ($this->session->userdata('group_id') != 1)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->base_url->path = 'logs/developer';
		$this->view->cp_page_title = lang('view_developer_log');
		$this->filters(array('date', 'perpage'));

		$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
		$page = ($page > 0) ? $page : 1;

		$offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

		$logs = ee()->api->get('DeveloperLog');

		if ( ! empty($this->params['filter_by_date']))
		{
			$logs = $logs->filter('timestamp', '>=', ee()->localize->now - $this->params['filter_by_date']);
		}

		// if ( ! empty($this->view->filter_by_phrase_value))
		// {
		// 	$logs = $logs->filter('action', 'LIKE', '%' . $this->view->filter_by_phrase_value . '%');
		// }

		$count = $logs->count();

		$logs = $logs->order('timestamp', 'desc')
			->limit($this->params['perpage'])
			->offset($offset)
			->all();

		$rows = array();
		foreach ($logs as $log)
		{
			if ( ! $log->function)
			{
				$description = '<p>'.$log->description.'</p>';
			}
			else
			{
				$description = '<p>';

				// "Deprecated function %s called"
				$description .= sprintf(lang('deprecated_function'), $log->function);

				// "in %s on line %d."
				if ($log->file && $log->line)
				{
					$description .= NBS.sprintf(lang('deprecated_on_line'), '<code>'.$log->file.'</code>', $log->line);
				}

				$description .= '</p>';

				// "from template tag: %s in template %s"
				if ($log->addon_module && $log->addon_method)
				{
					$description .= '<p>';
					$description .= sprintf(
						lang('deprecated_template'),
						'<code>exp:'.strtolower($log->addon_module).':'.$log->addon_method.'</code>',
						'<a href="'.cp_url('design/edit_template/'.$log->template_id).'">'.$log->template_group.'/'.$log->template_name.'</a>'
					);

					if ($log->snippets)
					{
						$snippets = explode('|', $log->snippets);

						foreach ($snippets as &$snip)
						{
							$snip = '<a href="'.cp_url('design/snippets_edit', array('snippet' => $snip)).'">{'.$snip.'}</a>';
						}

						$description .= '<br>';
						$description .= sprintf(lang('deprecated_snippets'), implode(', ', $snippets));
					}
					$description .= '</p>';
				}

				if ($log->deprecated_since
					|| $log->use_instead)
				{
					// Add a line break if there is additional information
					$description .= '<p>';

					// "Deprecated since %s."
					if ($log->deprecated_since)
					{
						$description .= sprintf(lang('deprecated_since'), $log->deprecated_since);
					}

					// "Use %s instead."
					if ($log->use_instead)
					{
						$description .= NBS.sprintf(lang('deprecated_use_instead'), $log->use_instead);
					}
					$description .= '</p>';
				}
			}

			$rows[] = array(
				'log_id'			=> $log->log_id,
				'timestamp'			=> $this->localize->human_time($log->timestamp),
				'description' 		=> $description
			);
		}

		$pagination = new Pagination($this->params['perpage'], $count, $page);
		$links = $pagination->cp_links($this->base_url);

		$vars = array(
			'rows' => $rows,
			'pagination' => $links
		);

		$this->cp->render('logs/developer', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes log entries, either all at once, or one at a time
	 *
	 * @param mixed  $id	Either the id to delete or "all"
	 */
	public function delete($id = 'all')
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$query = ee()->api->get('DeveloperLog');

		$success_flashdata = lang('cleared_logs');
		if (strtolower($id) != 'all')
		{
			$query = $query->filter('log_id', $id);
			$success_flashdata = lang('logs_deleted');
		}

		$query->all()->delete();

		ee()->view->set_message('success', $success_flashdata, '', TRUE);
		ee()->functions->redirect(cp_url('logs/developer'));
	}
}
// END CLASS

/* End of file Developer.php */
/* Location: ./system/expressionengine/controllers/cp/Logs/Developer.php */