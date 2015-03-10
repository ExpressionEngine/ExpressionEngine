<?php

namespace EllisLab\ExpressionEngine\Controllers\Channel;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Controllers\Channel\AbstractChannel as AbstractChannelController;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Channel Status Controller Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Status extends AbstractChannelController {

	/**
	 * Status groups listing
	 */
	public function index()
	{
		$table = CP\Table::create();
		$table->setColumns(
			array(
				'group_name',
				'manage' => array(
					'type'	=> CP\Table::COL_TOOLBAR
				),
				array(
					'type'	=> CP\Table::COL_CHECKBOX
				)
			)
		);

		$status_groups = ee('Model')->get('StatusGroup')
			->filter('site_id', ee()->config->item('site_id'));
		$total_rows = $status_groups->all()->count();

		$status_groups = $status_groups->order($table->sort_col, $table->sort_dir)
			->limit(20)
			->offset(($table->config['page'] - 1) * 20)
			->all();

		$data = array();
		foreach ($status_groups as $group)
		{
			$data[] = array(
				htmlentities($group->group_name, ENT_QUOTES),
				array('toolbar_items' => array(
					'view' => array(
						'href' => cp_url('channel/status/status-list/'.$group->group_id),
						'title' => lang('view')
					),
					'edit' => array(
						'href' => cp_url('channel/status/edit/'.$group->group_id),
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'status_groups[]',
					'value' => $group->group_id,
					'data'	=> array(
						'confirm' => lang('status_group') . ': <b>' . htmlentities($group->group_name, ENT_QUOTES) . '</b>'
					),
					// Cannot delete default group
					'disabled' => ($group->group_id == 1) ? 'disabled' : NULL
				)
			);
		}

		$table->setData($data);

		$base_url = new CP\URL('channel/status', ee()->session->session_id());
		$vars['table'] = $table->viewData($base_url);

		$pagination = new CP\Pagination(
			$vars['table']['limit'],
			$total_rows,
			$vars['table']['page']
		);
		$vars['pagination'] = $pagination->cp_links($vars['table']['base_url']);

		ee()->view->cp_page_title = lang('status_groups');

		ee()->javascript->set_global('lang.remove_confirm', lang('status_groups') . ': <b>### ' . lang('status_groups') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		ee()->cp->render('channel/status/index', $vars);
	}

	/**
	 * Remove status groups handler
	 */
	public function remove()
	{
		$group_ids = ee()->input->post('status_groups');

		if ( ! empty($group_ids) && ee()->input->post('bulk_action') == 'remove')
		{
			// Filter out junk
			$group_ids = array_filter($group_ids, 'is_numeric');

			if ( ! empty($group_ids))
			{
				// TODO: unassign status group from any channels using it
				ee('Model')->get('StatusGroup')
					->filter('group_id', 'IN', $group_ids)
					->delete();

				ee('Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('status_groups_removed'))
					->addToBody(sprintf(lang('status_groups_removed_desc'), count($group_ids)))
					->defer();
			}
		}
		else
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->functions->redirect(cp_url('channel/status', ee()->cp->get_url_state()));
	}

	/**
	 * New status group form
	 */
	public function create()
	{
		$this->form();
	}

	/**
	 * Edit status group form
	 */
	public function edit($group_id)
	{
		$this->form($group_id);
	}

	/**
	 * Status group creation/edit form
	 *
	 * @param	int	$group_id	ID of status group to edit
	 */
	private function form($group_id = NULL)
	{
		if (is_null($group_id))
		{
			ee()->view->cp_page_title = lang('create_status_group');
			ee()->view->base_url = cp_url('channel/status/create');
			ee()->view->save_btn_text = 'create_status_group';
			$status_group = ee('Model')->make('StatusGroup');
		}
		else
		{
			$status_group = ee('Model')->get('StatusGroup')
				->filter('group_id', $group_id)
				->first();

			if ( ! $status_group)
			{
				show_error(lang('unauthorized_access'));
			}

			ee()->view->cp_page_title = lang('edit_status_group');
			ee()->view->base_url = cp_url('channel/status/edit/'.$group_id);
			ee()->view->save_btn_text = 'edit_status_group';
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'name',
					'desc' => 'edit_status_group',
					'fields' => array(
						'group_name' => array(
							'type' => 'text',
							'value' => $status_group->group_name,
							'required' => TRUE
						)
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'group_name',
				'label' => 'lang:name',
				'rules' => 'required|strip_tags|trim|valid_xss_check'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$group_id = $this->saveStatusGroup($group_id);

			ee('Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('status_group_saved'))
				->addToBody(lang('status_group_saved_desc'))
				->defer();

			ee()->functions->redirect(cp_url('channel/status/edit/'.$group_id));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('status_group_not_saved'))
				->addToBody(lang('status_group_not_saved_desc'))
				->now();
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(cp_url('channel/status'), lang('status_groups'));

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Saves a status group
	 *
	 * @param	int $group_id ID of status group to save
	 * @return	int ID of status group saved
	 */
	private function saveStatusGroup($group_id = NULL)
	{
		$status_group = ee('Model')->make('StatusGroup');
		$status_group->group_id = $group_id;
		$status_group->site_id = ee()->config->item('site_id');
		$status_group->group_name = ee()->input->post('group_name');
		$status_group->save();

		return $status_group->group_id;
	}

	/**
	 * Status listing for a group
	 */
	public function statusList($group_id)
	{
		$status_group = ee('Model')->get('StatusGroup')
			->filter('group_id', $group_id)
			->first();

		if ( ! $status_group)
		{
			show_error(lang('unauthorized_access'));
		}

		$table = CP\Table::create(array('reorder' => TRUE));
		$table->setColumns(
			array(
				'status_name',
				'manage' => array(
					'type'	=> CP\Table::COL_TOOLBAR
				),
				array(
					'type'	=> CP\Table::COL_CHECKBOX
				)
			)
		);

		$statuses = $status_group->getStatuses()->sortBy('status_order');

		$data = array();
		foreach ($statuses as $status)
		{
			$data[] = array(
				htmlentities($status->status, ENT_QUOTES).form_hidden('order[]', $status->getId()),
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('channel/status/edit-status/'.$status->getId()),
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'statuses[]',
					'value' => $status->getId(),
					'data'	=> array(
						'confirm' => lang('status') . ': <b>' . htmlentities($status->status, ENT_QUOTES) . '</b>'
					),
					// Cannot delete default statuses
					'disabled' => ($status->status == 'open' OR $status->status == 'closed') ? 'disabled' : NULL
				)
			);
		}

		$table->setData($data);

		$base_url = new CP\URL('channel/status/status-list/'.$group_id, ee()->session->session_id());
		$vars['table'] = $table->viewData($base_url);

		ee()->view->group_id = $group_id;

		ee()->view->cp_page_title = $status_group->group_name . ' &mdash; ' . lang('statuses');
		ee()->cp->set_breadcrumb(cp_url('channel/status'), lang('status_groups'));

		ee()->javascript->set_global('lang.remove_confirm', lang('statuses') . ': <b>### ' . lang('statuses') . '</b>');
		ee()->cp->add_js_script('file', 'cp/v3/confirm_remove');
		ee()->cp->add_js_script('file', 'cp/sort_helper');
		ee()->cp->add_js_script('plugin', 'ee_table_reorder');
		ee()->cp->add_js_script('file', 'cp/v3/status_reorder');

		$reorder_ajax_fail = ee('Alert')->makeBanner('reorder-ajax-fail')
			->asIssue()
			->canClose()
			->withTitle(lang('status_ajax_reorder_fail'))
			->addToBody(lang('status_ajax_reorder_fail_desc'));

		ee()->javascript->set_global('statuses.reorder_url', cp_url('channel/status/status-reorder/'.$group_id));
		ee()->javascript->set_global('alert.reorder_ajax_fail', $reorder_ajax_fail->render());

		ee()->cp->render('channel/status/list', $vars);
	}

	/**
	 * AJAX end point for reordering statuses on status listing page
	 */
	public function statusReorder($group_id)
	{
		$status_group = ee('Model')->get('StatusGroup')
			->filter('group_id', $group_id)
			->first();

		// Parse out the serialized inputs sent by the JavaScript
		$new_order = array();
		parse_str(ee()->input->post('order'), $new_order);

		if ( ! AJAX_REQUEST OR ! $status_group OR empty($new_order['order']))
		{
			show_error(lang('unauthorized_access'));
		}

		$statuses = $status_group->getStatuses()->indexBy('status_id');

		$order = 1;
		foreach ($new_order['order'] as $status_id)
		{
			// Only update status orders that have changed
			if (isset($statuses[$status_id]) && $statuses[$status_id]->status_order != $order)
			{
				$statuses[$status_id]->status_order = $order;
				$statuses[$status_id]->save();
			}

			$order++;
		}

		ee()->output->send_ajax_response(NULL);
		exit;
	}

	/**
	 * Remove status groups handler
	 */
	public function removeStatus()
	{
		$status_ids = ee()->input->post('statuses');

		if ( ! empty($status_ids) && ee()->input->post('bulk_action') == 'remove')
		{
			// Filter out junk
			$status_ids = array_filter($status_ids, 'is_numeric');

			if ( ! empty($status_ids))
			{
				ee('Model')->get('Status')
					->filter('status_id', 'IN', $status_ids)
					->delete();

				ee('Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('statuses_removed'))
					->addToBody(sprintf(lang('statuses_removed_desc'), count($status_ids)))
					->defer();
			}
		}
		else
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->functions->redirect(
			cp_url('channel/status/status-list/'.ee()->input->post('status_group_id'), ee()->cp->get_url_state())
		);
	}
}
// EOF