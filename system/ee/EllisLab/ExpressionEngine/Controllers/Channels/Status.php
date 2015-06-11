<?php

namespace EllisLab\ExpressionEngine\Controllers\Channels;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Controllers\Channels\AbstractChannels as AbstractChannelsController;

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
class Status extends AbstractChannelsController {

	/**
	 * Status groups listing
	 */
	public function index()
	{
		$table = ee('CP/Table');
		$table->setColumns(
			array(
				'col_id',
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

		$sort_map = array(
			'col_id' => 'group_id',
			'group_name' => 'group_name'
		);

		$status_groups = $status_groups->order($sort_map[$table->sort_col], $table->sort_dir)
			->limit(20)
			->offset(($table->config['page'] - 1) * 20)
			->all();

		$data = array();
		foreach ($status_groups as $group)
		{
			$data[] = array(
				$group->group_id,
				htmlentities($group->group_name, ENT_QUOTES),
				array('toolbar_items' => array(
					'view' => array(
						'href' => cp_url('channels/status/status-list/'.$group->group_id),
						'title' => lang('view')
					),
					'edit' => array(
						'href' => cp_url('channels/status/edit/'.$group->group_id),
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
					'disabled' => ($group->group_name == 'Default') ? 'disabled' : NULL
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

		ee()->cp->render('channels/status/index', $vars);
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

		ee()->functions->redirect(cp_url('channels/status', ee()->cp->get_url_state()));
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
			ee()->view->base_url = cp_url('channels/status/create');
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
			ee()->view->base_url = cp_url('channels/status/edit/'.$group_id);
			ee()->view->save_btn_text = 'edit_status_group';
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'name',
					'desc' => 'status_group_name_desc',
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
				'rules' => 'required|strip_tags|trim|valid_xss_check|alpha_dash_space|callback_validateStatusGroupName['.$group_id.']'
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

			ee()->functions->redirect(cp_url('channels/status/edit/'.$group_id));
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

		ee()->cp->set_breadcrumb(cp_url('channels/status'), lang('status_groups'));

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Custom validator for status group name to check for duplicate
	 * status group names
	 *
	 * @param	model	$name		Status group name
	 * @param	model	$group_id	Group ID for status group
	 * @return	bool	Valid status group name or not
	 */
	public function validateStatusGroupName($name, $group_id)
	{
		$status_group = ee('Model')->get('StatusGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_name', $name);

		if ( ! empty($status_id))
		{
			$status_group->filter('group_id', '!=', $group_id);
		}

		if ($status_group->all()->count() > 0)
		{
			ee()->form_validation->set_message('validateStatusGroupName', lang('duplicate_status_group_name'));
			return FALSE;
		}

		return TRUE;
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

		$table = ee('CP/Table', array(
			'reorder' => TRUE,
			'sortable' => FALSE
		));
		$table->setColumns(
			array(
				'col_id',
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
				$status->getId(),
				htmlentities($status->status, ENT_QUOTES).form_hidden('order[]', $status->getId()),
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('channels/status/edit-status/'.$group_id.'/'.$status->getId()),
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
		ee()->cp->set_breadcrumb(cp_url('channels/status'), lang('status_groups'));

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

		ee()->javascript->set_global('statuses.reorder_url', cp_url('channels/status/status-reorder/'.$group_id));
		ee()->javascript->set_global('alert.reorder_ajax_fail', $reorder_ajax_fail->render());

		ee()->cp->render('channels/status/list', $vars);
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
			cp_url('channels/status/status-list/'.ee()->input->post('status_group_id'), ee()->cp->get_url_state())
		);
	}

	/**
	 * New status form
	 */
	public function createStatus($group_id)
	{
		$this->statusForm($group_id);
	}

	/**
	 * Edit status form
	 */
	public function editStatus($group_id, $status_id)
	{
		$this->statusForm($group_id, $status_id);
	}

	/**
	 * Status creation/edit form
	 *
	 * @param	int	$status_id	ID of status group to edit
	 */
	private function statusForm($group_id, $status_id = NULL)
	{
		$status_group = ee('Model')->get('StatusGroup')
			->filter('group_id', $group_id)
			->first();

		if ( ! $status_group)
		{
			show_error(lang('unauthorized_access'));
		}

		if (is_null($status_id))
		{
			ee()->view->cp_page_title = lang('create_status');
			ee()->view->base_url = cp_url('channels/status/create-status/'.$group_id);
			ee()->view->save_btn_text = 'create_status';
			$status = ee('Model')->make('Status');
		}
		else
		{
			$status = ee('Model')->get('Status')
				->filter('status_id', $status_id)
				->first();

			if ( ! $status_id)
			{
				show_error(lang('unauthorized_access'));
			}

			ee()->view->cp_page_title = lang('edit_status');
			ee()->view->base_url = cp_url('channels/status/edit-status/'.$group_id.'/'.$status_id);
			ee()->view->save_btn_text = 'edit_status';
		}

		// Member IDs NOT in $no_access have access...
		list($allowed_groups, $member_groups) = $this->getAllowedGroups(is_null($status_id) ? NULL : $status);

		$vars['sections'] = array(
			array(
				array(
					'title' => 'name',
					'desc' => 'status_name_desc',
					'fields' => array(
						'status' => array(
							'type' => 'text',
							'value' => $status->status,
							'required' => TRUE,
							'disabled' => ($status->status == 'open' OR $status->status == 'closed') ? 'disabled' : NULL
						)
					)
				),
				array(
					'title' => 'highlight_color',
					'desc' => 'highlight_color_desc',
					'fields' => array(
						'highlight' => array(
							'type' => 'text',
							'value' => $status->highlight ?: '000000'
						)
					)
				)
			),
			'permissions' => array(
				ee('Alert')->makeInline('permissions-warn')
					->asWarning()
					->addToBody(lang('category_permissions_warning'))
					->addToBody(
						sprintf(lang('category_permissions_warning2'), '<span title="excercise caution"></span>'),
						'caution'
					)
					->cannotClose()
					->render(),
				array(
					'title' => 'status_access',
					'desc' => 'status_access_desc',
					'caution' => TRUE,
					'fields' => array(
						'status_access' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $allowed_groups
						)
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'status',
				'label' => 'lang:name',
				'rules' => 'required|strip_tags|trim|valid_xss_check|alpha_dash_space|callback_validateName['.$group_id.','.$status_id.']'
			),
			array(
				'field' => 'highlight',
				'label' => 'lang:highlight_color',
				'rules' => 'strip_tags|trim|valid_xss_check|callback_validateHex'
			)
		));

		ee()->form_validation->validateNonTextInputs($vars['sections']);

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$status_id = $this->saveStatus($group_id, $status_id);

			ee('Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('status_saved'))
				->addToBody(lang('status_saved_desc'))
				->defer();

			ee()->functions->redirect(cp_url('channels/status/edit-status/'.$group_id.'/'.$status_id));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('status_not_saved'))
				->addToBody(lang('status_not_saved_desc'))
				->now();
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(cp_url('channels/status'), lang('status_groups'));
		ee()->cp->set_breadcrumb(
			cp_url('channels/status/status-list/'.$group_id),
			$status_group->group_name . ' &mdash; ' . lang('statuses')
		);

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Returns an array of member group IDs allowed to use this status
	 * in the form of id => title, along with an array of all member
	 * groups in the same format
	 *
	 * @param	model	$status		Model object for status
	 * @return	array	Array containing each of the arrays mentioned above
	 */
	private function getAllowedGroups($status = NULL)
	{
		$groups = ee('Model')->get('MemberGroup')
			->filter('group_id', 'NOT IN', array(1,2,3,4))
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_title')
			->all();

		$member_groups = array();
		foreach ($groups as $group)
		{
			$member_groups[$group->group_id] = $group->group_title;
		}

		if ( ! empty($_POST))
		{
			if (isset($_POST['status_access']))
			{
				return array($_POST['status_access'], $member_groups);
			}

			return array(array(), $member_groups);
		}

		$no_access = array();
		if ($status !== NULL)
		{
			$no_access = $status->getNoAccess()->pluck('group_id');
		}

		$allowed_groups = array_diff(array_keys($member_groups), $no_access);

		// Member IDs NOT in $no_access have access...
		return array($allowed_groups, $member_groups);
	}

	/**
	 * Custom validator for status name to check for duplicate status
	 * names within the same group
	 *
	 * @param	model	$name		Status name
	 * @param	model	$group_id	Group ID for status
	 * @param	model	$status_id	Status ID if editing
	 * @return	bool	Valid status name or not
	 */
	public function validateName($name, $payload)
	{
		list($group_id, $status_id) = explode(',', $payload);

		$status = ee('Model')->get('Status')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('status', $name)
			->filter('group_id', $group_id);

		if ( ! empty($status_id))
		{
			$status->filter('status_id', '!=', $status_id);
		}

		if ($status->all()->count() > 0)
		{
			ee()->form_validation->set_message('validateName', lang('duplicate_status_name'));
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Custom validator for status highlight color to ensure valid
	 * hex value was entered
	 *
	 * @param	model	$hex	Hex code
	 * @return	bool	Valid hex code or not
	 */
	public function validateHex($hex)
	{
		if ($hex != '' && ! preg_match('/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $hex))
		{
			ee()->form_validation->set_message('validateHex', lang('invalid_hex_code'));
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Save a status
	 *
	 * @param	model	$group_id	Group ID for status
	 * @param	model	$status_id	Status ID if editing
	 * @return	int		Status ID of newly-saved status
	 */
	private function saveStatus($group_id, $status_id = NULL)
	{
		if ($status_id)
		{
			$status = ee('Model')->get('Status')
				->filter('status_id', $status_id)
				->first();
		}
		else
		{
			$status = ee('Model')->make('Status');
			$status->site_id = ee()->config->item('site_id');
			$status->group_id = $group_id;
		}

		$status->status = ee()->input->post('status');
		$status->highlight = ee()->input->post('highlight');

		$access = ee()->input->post('status_access') ?: array();

		$no_access = ee('Model')->get('MemberGroup')
			->filter('group_id', 'NOT IN', array_merge(array(1,2,3,4), $access))
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		if ($no_access->count() > 0)
		{
			$status->setNoAccess($no_access);
		}
		else
		{
			// Remove all member groups from this status
			$status->removeNoAccess();
		}

		$status->save();

		return $status->status_id;
	}
}
// EOF