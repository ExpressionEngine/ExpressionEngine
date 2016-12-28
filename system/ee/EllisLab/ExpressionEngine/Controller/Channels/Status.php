<?php

namespace EllisLab\ExpressionEngine\Controller\Channels;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Controller\Channels\AbstractChannels as AbstractChannelsController;
use Mexitek\PHPColors\Color;

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
 * ExpressionEngine CP Channel Status Controller Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Status extends AbstractChannelsController {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group_any(
			'can_create_statuses',
			'can_edit_statuses',
			'can_delete_statuses'
		))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->generateSidebar('status');
	}

	/**
	 * Status groups listing
	 */
	public function index()
	{
		$status_groups = ee('Model')->get('StatusGroup')
			->filter('site_id', ee()->config->item('site_id'));
		$total_rows = $status_groups->count();

		$table = $this->buildTableFromStatusGroupsQuery($status_groups, array(), ee()->cp->allowed_group('can_delete_statuses'));

		$vars['table'] = $table->viewData(ee('CP/URL')->make('channels/status'));

		$vars['pagination'] = ee('CP/Pagination', $total_rows)
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		$vars['can_create_statuses'] = ee()->cp->allowed_group('can_create_statuses');
		$vars['can_delete_statuses'] = ee()->cp->allowed_group('can_delete_statuses');

		ee()->view->cp_page_title = lang('status_groups');

		ee()->javascript->set_global('lang.remove_confirm', lang('status_groups') . ': <b>### ' . lang('status_groups') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		ee()->cp->render('channels/status/index', $vars);
	}

	/**
	 * Remove status groups handler
	 */
	public function remove()
	{
		if ( ! ee()->cp->allowed_group('can_delete_statuses'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$group_ids = ee()->input->post('status_groups');

		if ( ! empty($group_ids) && ee()->input->post('bulk_action') == 'remove')
		{
			// Filter out junk
			$group_ids = array_filter($group_ids, 'is_numeric');

			if ( ! empty($group_ids))
			{
				ee('Model')->get('StatusGroup')
					->filter('group_id', 'IN', $group_ids)
					->delete();

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('status_groups_removed'))
					->addToBody(sprintf(lang('status_groups_removed_desc'), count($group_ids)))
					->defer();
			}
		}
		else
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->functions->redirect(ee('CP/URL')->make('channels/status', ee()->cp->get_url_state()));
	}

	/**
	 * New status group form
	 */
	public function create()
	{
		if ( ! ee()->cp->allowed_group('can_create_statuses'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->form();
	}

	/**
	 * Edit status group form
	 */
	public function edit($group_id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_statuses'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

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
			$alert_key = 'created';
			ee()->view->cp_page_title = lang('create_status_group');
			ee()->view->base_url = ee('CP/URL')->make('channels/status/create');
			$status_group = ee('Model')->make('StatusGroup');
		}
		else
		{
			$status_group = ee('Model')->get('StatusGroup')
				->filter('group_id', $group_id)
				->first();

			if ( ! $status_group)
			{
				show_error(lang('unauthorized_access'), 403);
			}

			$alert_key = 'updated';
			ee()->view->cp_page_title = lang('edit_status_group');
			ee()->view->base_url = ee('CP/URL')->make('channels/status/edit/'.$group_id);
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'name',
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
			$status_group = $this->saveStatusGroup($group_id);

			if (is_null($group_id))
			{
				ee()->session->set_flashdata('highlight_id', $status_group->getId());
			}

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('status_group_'.$alert_key))
				->addToBody(sprintf(lang('status_group_'.$alert_key.'_desc'), $status_group->group_name))
				->defer();

			ee()->functions->redirect(ee('CP/URL')->make('channels/status'));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('status_group_not_'.$alert_key))
				->addToBody(lang('status_group_not_'.$alert_key.'_desc'))
				->now();
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text = sprintf(lang('btn_save'), lang('status_group'));
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('channels/status'), lang('status_groups'));

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

		if ( ! empty($group_id))
		{
			$status_group->filter('group_id', '!=', $group_id);
		}

		if ($status_group->count() > 0)
		{
			ee()->form_validation->set_message('validateStatusGroupName', lang('duplicate_status_group_name'));
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Saves a status group
	 *
	 * @param	int		$group_id ID of status group to save
	 * @return	Model	Status group model object
	 */
	private function saveStatusGroup($group_id = NULL)
	{
		if ($group_id)
		{
			$status_group = ee('Model')->get('StatusGroup', $group_id)->first();
		}
		else
		{
			$status_group = ee('Model')->make('StatusGroup');
		}

		$status_group->site_id = ee()->config->item('site_id');
		$status_group->group_name = ee()->input->post('group_name');

		return $status_group->save();
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
			show_error(lang('unauthorized_access'), 403);
		}

		$table = ee('CP/Table', array(
			'reorder' => ee()->cp->allowed_group('can_edit_statuses'),
			'sortable' => FALSE
		));

		$columns = array(
			'col_id' => array(
				'encode' => FALSE
			),
			'status_name'
		);

		if (ee()->cp->allowed_group('can_edit_statuses'))
		{
			$columns['manage'] = array(
				'type'	=> CP\Table::COL_TOOLBAR
			);
		}

		if (ee()->cp->allowed_group('can_delete_statuses'))
		{
			$columns[] = array(
				'type'	=> CP\Table::COL_CHECKBOX
			);
		}

		$table->setColumns($columns);

		$statuses = $status_group->getStatuses()->sortBy('status_order');

		$data = array();
		foreach ($statuses as $status)
		{
			$edit_url = ee('CP/URL')->make('channels/status/edit-status/'.$group_id.'/'.$status->getId());
			$columns = array(
				$status->getId().form_hidden('order[]', $status->getId()),
				array(
					'content' => $status->status,
					'href' => $edit_url,
				),
				array('toolbar_items' => array(
					'edit' => array(
						'href' => $edit_url,
						'title' => lang('edit')
					)
				))
			);

			if ( ! ee()->cp->allowed_group('can_edit_statuses'))
			{
				unset($columns[1]['href']);
				unset($columns[2]);
			}

			if (ee()->cp->allowed_group('can_delete_statuses'))
			{
				$columns[] = array(
					'name' => 'statuses[]',
					'value' => $status->getId(),
					'data'	=> array(
						'confirm' => lang('status') . ': <b>' . htmlentities($status->status, ENT_QUOTES, 'UTF-8') . '</b>'
					),
					// Cannot delete default statuses
					'disabled' => ($status->status == 'open' OR $status->status == 'closed') ? 'disabled' : NULL
				);
			}

			$attrs = array();
			if (ee()->session->flashdata('highlight_id') == $status->getId())
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs' => $attrs,
				'columns' => $columns
			);
		}

		$table->setData($data);

		$vars['table'] = $table->viewData(ee('CP/URL')->make('channels/status/status-list/'.$group_id));
		$vars['can_create_statuses'] = ee()->cp->allowed_group('can_create_statuses');

		ee()->view->group_id = $group_id;

		ee()->view->cp_page_title = $status_group->group_name . ' &mdash; ' . lang('statuses');
		ee()->cp->set_breadcrumb(ee('CP/URL')->make('channels/status'), lang('status_groups'));

		ee()->javascript->set_global('lang.remove_confirm', lang('statuses') . ': <b>### ' . lang('statuses') . '</b>');
		ee()->cp->add_js_script('file', 'cp/confirm_remove');
		ee()->cp->add_js_script('plugin', 'ee_table_reorder');
		ee()->cp->add_js_script('file', 'cp/channel/status_reorder');

		$reorder_ajax_fail = ee('CP/Alert')->makeBanner('reorder-ajax-fail')
			->asIssue()
			->canClose()
			->withTitle(lang('status_ajax_reorder_fail'))
			->addToBody(lang('status_ajax_reorder_fail_desc'));

		ee()->javascript->set_global('statuses.reorder_url', ee('CP/URL')->make('channels/status/status-reorder/'.$group_id)->compile());
		ee()->javascript->set_global('alert.reorder_ajax_fail', $reorder_ajax_fail->render());

		ee()->cp->render('channels/status/list', $vars);
	}

	/**
	 * AJAX end point for reordering statuses on status listing page
	 */
	public function statusReorder($group_id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_statuses'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$status_group = ee('Model')->get('StatusGroup')
			->filter('group_id', $group_id)
			->first();

		// Parse out the serialized inputs sent by the JavaScript
		$new_order = array();
		parse_str(ee()->input->post('order'), $new_order);

		if ( ! AJAX_REQUEST OR ! $status_group OR empty($new_order['order']))
		{
			show_error(lang('unauthorized_access'), 403);
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
		if ( ! ee()->cp->allowed_group('can_delete_statuses'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

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

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('statuses_removed'))
					->addToBody(sprintf(lang('statuses_removed_desc'), count($status_ids)))
					->defer();
			}
		}
		else
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->functions->redirect(
			ee('CP/URL')->make('channels/status/status-list/'.ee()->input->post('status_group_id'), ee()->cp->get_url_state())
		);
	}

	/**
	 * New status form
	 */
	public function createStatus($group_id)
	{
		if ( ! ee()->cp->allowed_group('can_create_statuses'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->statusForm($group_id);
	}

	/**
	 * Edit status form
	 */
	public function editStatus($group_id, $status_id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_statuses'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

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
			show_error(lang('unauthorized_access'), 403);
		}

		if (is_null($status_id))
		{
			$alert_key = 'created';
			ee()->view->cp_page_title = lang('create_status');
			ee()->view->base_url = ee('CP/URL')->make('channels/status/create-status/'.$group_id);
			$status = ee('Model')->make('Status');
		}
		else
		{
			$status = ee('Model')->get('Status')
				->filter('status_id', $status_id)
				->first();

			if ( ! $status_id)
			{
				show_error(lang('unauthorized_access'), 403);
			}

			$alert_key = 'updated';
			ee()->view->cp_page_title = lang('edit_status');
			ee()->view->base_url = ee('CP/URL')->make('channels/status/edit-status/'.$group_id.'/'.$status_id);
		}

		// Member IDs NOT in $no_access have access...
		list($allowed_groups, $member_groups) = $this->getAllowedGroups(is_null($status_id) ? NULL : $status);

		// Create the status example
		$status_style = '';
		if ( ! in_array($status->status, array('open', 'closed')) && $status->highlight != '')
		{
			$foreground = $this->calculateForegroundFor($status->highlight);
			$status_style = "style='background-color: #{$status->highlight}; border-color: #{$status->highlight}; color: #{$foreground};'";
		}

		$status_name = (empty($status->status)) ? lang('status') : $status->status;

		$status_class = str_replace(' ', '_', strtolower($status->status));
		$status_example = '<span class="status-tag st-'.$status_class.'" '.$status_style.'>'.$status_name.'</span>';

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
					'example' => $status_example,
					'fields' => array(
						'highlight' => array(
							'type' => 'text',
							'attrs' => 'class="color-picker"',
							'value' => $status->highlight ?: '000000',
							'required' => TRUE
						)
					)
				)
			),
			'permissions' => array(
				ee('CP/Alert')->makeInline('permissions-warn')
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
				'rules' => 'strip_tags|trim|required|valid_xss_check|callback_validateHex'
			)
		));

		ee()->form_validation->validateNonTextInputs($vars['sections']);

		// Put status name back into POST if we're editing one of the default
		// statuses, because the form input is disabled otherwise and it
		// won't be included in POST
		if ( ! empty($_POST) && ($status->status == 'open' OR $status->status == 'closed'))
		{
			$_POST['status'] = $status->status;
		}

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$status = $this->saveStatus($group_id, $status_id);

			if (is_null($status_id))
			{
				ee()->session->set_flashdata('highlight_id', $status->getId());
			}

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('status_'.$alert_key))
				->addToBody(sprintf(lang('status_'.$alert_key.'_desc'), $status->status))
				->defer();

			ee()->functions->redirect(ee('CP/URL')->make('channels/status/status-list/'.$group_id));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('status_not_'.$alert_key))
				->addToBody(lang('status_not_'.$alert_key.'_desc'))
				->now();
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text = sprintf(lang('btn_save'), lang('status'));
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('channels/status'), lang('status_groups'));
		ee()->cp->set_breadcrumb(
			ee('CP/URL')->make('channels/status/status-list/'.$group_id),
			$status_group->group_name . ' &mdash; ' . lang('statuses')
		);

		ee()->javascript->set_global('status.default_name', lang('status'));
		ee()->javascript->set_global('status.foreground_color_url', ee('CP/URL', 'channels/status/get-foreground-color')->compile());
		ee()->cp->add_js_script('file', 'cp/channel/status_edit');
		ee()->cp->add_js_script('plugin', 'minicolors');

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Retrieve the foreground color for a given status color
	 *
	 * @param string $color The hex color for the background
	 * @return void
	 */
	public function getForegroundColor($color = '')
	{
		$color = ee()->input->post('highlight');
		$foreground = $this->calculateForegroundFor($color);
		ee()->output->send_ajax_response($foreground);
	}

	/**
	 * Retrieve the foreground color for a given status color
	 *
	 * @param string $color The hex color for the background
	 * @return string The hex color best suited for the background color
	 */
	protected function calculateForegroundFor($background)
	{
		try
		{
			$background = new Color($background);
			$foreground = ($background->isLight())
				? $background->darken(100)
				: $background->lighten(100);
		}
		catch (\Exception $e)
		{
			$foreground = 'ffffff';
		}

		return $foreground;
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
			$member_groups[$group->group_id] = htmlentities($group->group_title, ENT_QUOTES, 'UTF-8');
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

		if ($status->count() > 0)
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
		if ($hex != '' && ! preg_match('/^#?([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $hex))
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
	 * @return	Model	Status model object
	 */
	private function saveStatus($group_id, $status_id = NULL)
	{
		if ($status_id)
		{
			$status = ee('Model')->get('Status', $status_id)->first();
		}
		else
		{
			$status = ee('Model')->make('Status');
			$status->site_id = ee()->config->item('site_id');
			$status->group_id = $group_id;
		}

		$status->status = ee()->input->post('status');
		$status->highlight = ltrim(ee()->input->post('highlight'), '#');

		$access = ee()->input->post('status_access') ?: array();

		$no_access = ee('Model')->get('MemberGroup')
			->filter('group_id', 'NOT IN', array_merge(array(1,2,3,4), $access))
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		if ($no_access->count() > 0)
		{
			$status->NoAccess = $no_access;
		}
		else
		{
			// Remove all member groups from this status
			$status->NoAccess = NULL;
		}

		return $status->save();
	}
}

// EOF
