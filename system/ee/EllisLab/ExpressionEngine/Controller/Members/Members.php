<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Members;

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder;

/**
 * Members Controller
 */
class Members extends CP_Controller {

	protected $base_url;

	public function __construct()
	{
		parent::__construct();

		if ( ! ee('Permission')->can('access_members'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->lang->loadfile('members');

		$this->base_url = ee('CP/URL')->make('members');
		$this->stdHeader($this->base_url);
	}

	public function index()
	{
		$members = ee('Model')->get('Member');

		$filters = $this->makeAndApplyFilters($members, TRUE);
		$vars['filters'] = $filters->render($this->base_url);

		$filter_values = $filters->values();

		$page = ((int) ee('Request')->get('page')) ?: 1;
		$offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

		$total_members = $members->count();

		$members->limit($filter_values['perpage'])
			->offset($offset);

		$table = $this->buildTableFromMemberQuery($members);

		$vars['table'] = $table->viewData($this->base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			$vars['pagination'] = ee('CP/Pagination', $total_members)
				->perPage($filter_values['perpage'])
				->currentPage($page)
				->render($this->base_url);
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('members') . ': <b>### ' . lang('members') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove', 'cp/members/members'),
		));

		if ( ! ee('Session')->isWithinAuthTimeout())
		{
			$vars['confirm_remove_secure_form_ctrls'] = [
				'title' => 'your_password',
				'desc' => 'your_password_delete_members_desc',
				'group' => 'verify_password',
				'fields' => [
					'verify_password' => [
						'type'      => 'password',
						'required'  => TRUE,
						'maxlength' => PASSWORD_MAX_LENGTH
					]
				]
			];
		}

		$vars['can_delete_members'] = ee('Permission')->can('delete_members');

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = ee()->view->cp_page_title ?: lang('all_members');
		ee()->cp->render('members/view_members', $vars);
	}

	public function pending()
	{
		$members = ee('Model')->get('Member')
			->filter('role_id', 4);

		$filters = $this->makeAndApplyFilters($members, FALSE);
		$vars['filters'] = $filters->render($this->base_url);

		$filter_values = $filters->values();

		$page = ((int) ee('Request')->get('page')) ?: 1;
		$offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

		$total_members = $members->count();

		$members->limit($filter_values['perpage'])
			->offset($offset);

		$table = $this->buildTableFromMemberQuery($members);

		$vars['table'] = $table->viewData($this->base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			$vars['pagination'] = ee('CP/Pagination', $total_members)
				->perPage($filter_values['perpage'])
				->currentPage($page)
				->render($this->base_url);
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('members') . ': <b>### ' . lang('members') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove', 'cp/members/members'),
		));

		if ( ! ee('Session')->isWithinAuthTimeout())
		{
			$vars['confirm_remove_secure_form_ctrls'] = [
				'title' => 'your_password',
				'desc' => 'your_password_delete_members_desc',
				'group' => 'verify_password',
				'fields' => [
					'verify_password' => [
						'type'      => 'password',
						'required'  => TRUE,
						'maxlength' => PASSWORD_MAX_LENGTH
					]
				]
			];
		}

		$vars['can_edit'] = ee('Permission')->can('edit_members');
		$vars['can_delete'] = ee('Permission')->can('delete_members');

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = ee()->view->cp_page_title ?: lang('pending_members');
		ee()->cp->render('members/pending', $vars);
	}

	public function banned()
	{
		if ( ! ee('Permission')->can('ban_users'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->load->library('form_validation');

		$this->stdHeader($this->base_url);
		$this->base_url = ee('CP/URL', 'members/banned');

		$members = ee('Model')->get('Member')
			->filter('role_id', 2);

		$filters = $this->makeAndApplyFilters($members, FALSE);
		$vars['filters'] = $filters->render($this->base_url);

		$filter_values = $filters->values();

		$page = ((int) ee('Request')->get('page')) ?: 1;
		$offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

		$total_members = $members->count();

		$members->limit($filter_values['perpage'])
			->offset($offset);

		$table = $this->buildTableFromMemberQuery($members);

		$vars['table'] = $table->viewData($this->base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			$vars['pagination'] = ee('CP/Pagination', $total_members)
				->perPage($filter_values['perpage'])
				->currentPage($page)
				->render($this->base_url);
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('members') . ': <b>### ' . lang('members') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove', 'cp/members/members'),
		));

		if ( ! ee('Session')->isWithinAuthTimeout())
		{
			$vars['confirm_remove_secure_form_ctrls'] = [
				'title' => 'your_password',
				'desc' => 'your_password_delete_members_desc',
				'group' => 'verify_password',
				'fields' => [
					'verify_password' => [
						'type'      => 'password',
						'required'  => TRUE,
						'maxlength' => PASSWORD_MAX_LENGTH
					]
				]
			];
		}

		$vars['can_delete_members'] = ee('Permission')->can('delete_members');

		$values = [
			'banned_ips' => '',
			'banned_emails' => '',
			'banned_usernames' => '',
			'banned_screen_names' => '',
		];

		foreach (array_keys($values) as $item)
		{
			$value = ee()->config->item($item);

			if ($value != '')
			{
				foreach (explode('|', $value) as $line)
				{
					$values[$item] .= $line.NL;
				}
			}
		}

		$ban_action = ee()->config->item('ban_action');

		$vars['form'] = array(
			'ajax_validate' => TRUE,
			'base_url'      => $this->base_url,
			'cp_page_title' => lang('manage_bans'),
			'save_btn_text' => sprintf(lang('btn_save'), lang('settings')),
			'save_btn_text_working' => 'btn_saving',
			'sections' => array(
				array(
					array(
						'title' => 'ip_address_banning',
						'desc' => 'ip_banning_instructions',
						'fields' => array(
							'banned_ips' => array(
								'type' => 'textarea',
								'value' => $values['banned_ips']
							)
						)
					),
					array(
						'title' => 'email_address_banning',
						'desc' => 'email_banning_instructions',
						'fields' => array(
							'banned_emails' => array(
								'type' => 'textarea',
								'value' => $values['banned_emails']
							)
						)
					),
					array(
						'title' => 'username_banning',
						'desc' => 'username_banning_instructions',
						'fields' => array(
							'banned_usernames' => array(
								'type' => 'textarea',
								'value' => $values['banned_usernames']
							)
						)
					),
					array(
						'title' => 'screen_name_banning',
						'desc' => 'screen_name_banning_instructions',
						'fields' => array(
							'banned_screen_names' => array(
								'type' => 'textarea',
								'value' => $values['banned_screen_names']
							)
						)
					),
					array(
						'title' => 'ban_options',
						'desc'  => 'ban_options_desc',
						'fields' => array(
							'ban_action_pt1' => array(
								'type' => 'radio',
								'name' => 'ban_action',
								'choices' => array(
									'restrict' => lang('restrict_to_viewing'),
									'message' => lang('show_this_message'),
								),
								'value' => $ban_action
							),
							'ban_message' => array(
								'type' => 'textarea',
								'value' => ee()->config->item('ban_message')
							),
							'ban_action_pt2' => array(
								'type' => 'radio',
								'name' => 'ban_action',
								'choices' => array(
									'bounce' => lang('send_to_site'),
								),
								'value' => $ban_action
							),
							'ban_destination' => array(
								'type' => 'text',
								'value' => ee()->config->item('ban_destination')
							),
						)
					)
				)
			)
		);

		// @TODO: Stop using form_validation
		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'banned_usernames',
				 'label'   => 'lang:banned_usernames',
				 'rules'   => 'valid_xss_check'
			),
			array(
				 'field'   => 'banned_screen_names',
				 'label'   => 'lang:banned_screen_names',
				 'rules'   => 'valid_xss_check'
			),
			array(
				 'field'   => 'banned_emails',
				 'label'   => 'lang:banned_emails',
				 'rules'   => 'valid_xss_check'
			),
			array(
				 'field'   => 'banned_ips',
				 'label'   => 'lang:banned_ips',
				 'rules'   => 'valid_xss_check'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$prefs = [
				'ban_action'      => ee()->input->post('ban_action'),
				'ban_message'     => ee()->input->post('ban_message'),
				'ban_destination' => ee()->input->post('ban_destination'),
			];

			foreach (array_keys($values) as $item)
			{
				$value = ee()->input->post($item);
				$value = implode('|', explode(NL, $value));
				$prefs[$item] = $value;
			}

			ee()->config->update_site_prefs($prefs);

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('ban_settings_updated'))
				->defer();

			ee()->functions->redirect($this->base_url);
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('settings_save_error'))
				->addToBody(lang('settings_save_error_desc'))
				->now();
		}

		ee()->cp->render('members/ban_settings', $vars);
	}

	private function initializeTable()
	{
		$checkboxes = ee('Permission')->can('delete_members');

		// Get order by and sort preferences for our initial state
		$order_by = (ee()->config->item('memberlist_order_by')) ?: 'member_id';
		$sort = (ee()->config->item('memberlist_sort_order')) ?: 'asc';

		// Fix for an issue where users may have 'total_posts' saved
		// in their site settings for sorting members; but the actual
		// column should be total_forum_posts, so we need to correct
		// it until member preferences can be saved again with the
		// right value
		if ($order_by == 'total_posts')
		{
			$order_by = 'total_forum_posts';
		}

		$sort_col = ee()->input->get('sort_col') ?: $order_by;
		$sort_dir = ee()->input->get('sort_dir') ?: $sort;

		$table = ee('CP/Table', array(
			'sort_col' => $sort_col,
			'sort_dir' => $sort_dir,
			'limit' => ee()->config->item('memberlist_row_limit'),
			// 'search' => ee()->input->get_post('filter_by_keyword'),
		));

		$table->setNoResultsText('no_members_found');

 		$columns = array(
			'member_id' => array(
				'type'	=> Table::COL_ID
			),
			'username' => array(
				'encode' => FALSE
			),
			'dates' => array(
				'encode' => FALSE
			),
			'primary_role' => array(
				'encode' => FALSE
			)
		);

		if (ee('Permission')->can('edit_members'))
		{
			$columns['manage'] = array(
				'type'	=> Table::COL_TOOLBAR
			);
		}

		if ($checkboxes)
		{
			$columns[] = array(
				'type'	=> Table::COL_CHECKBOX
			);
		}

		$table->setColumns($columns);

		return $table;
	}

	private function buildTableFromMemberQuery(Builder $members, $checkboxes = NULL)
	{
		$table = $this->initializeTable();

		$sort_map = array(
			'member_id'    => 'member_id',
			'username'     => 'username',
			'dates'        => 'join_date',
			'member_group' => 'group_id'
		);

		$members = $members->order($sort_map[$table->sort_col], $table->config['sort_dir'])
			->all();

		$data = array();

		$member_id = ee()->session->flashdata('highlight_id');

		foreach ($members as $member)
		{
			$can_edit_member = ee('Permission')->isSuperAdmin() || $member->isSuperAdmin();

			$edit_link = ee('CP/URL')->make('members/profile/' . $member->member_id);
			$toolbar = array(
				'edit' => array(
					'href' => $edit_link,
					'title' => strtolower(lang('profile'))
				)
			);

			$attrs = array();

			switch ($member->PrimaryRole->name)
			{
				case 'Banned':
					$group = "<span class='st-banned'>" . lang('banned') . "</span>";
					$attrs['class'] = 'banned';
					break;
				case 'Pending':
					$group = "<span class='st-pending'>" . lang('pending') . "</span>";
					$attrs['class'] = 'pending';
					if (ee('Permission')->can('edit_members'))
					{
						$toolbar['approve'] = array(
							'href' => '#',
							'data-post-url' => ee('CP/URL')->make('members/approve/' . $member->member_id),
							'title' => strtolower(lang('approve'))
						);
					}
					break;
				default:
					$group = $member->PrimaryRole->name;
			}

			$email = "<a href = '" . ee('CP/URL')->make('utilities/communicate/member/' . $member->member_id) . "'>".$member->email."</a>";

			if ($can_edit_member && ee('Permission')->can('edit_members'))
			{
				$username_display = "<a href = '" . $edit_link . "'>". $member->username."</a>";
			}
			else
			{
				$username_display = $member->username;
				unset($toolbar['edit']);
			}

			$username_display .= '<br><span class="meta-info">&mdash; '.$email.'</span>';
			$last_visit = ($member->last_visit) ? ee()->localize->human_time($member->last_visit) : '--';

			$column = array(
				$member->member_id,
				$username_display,
				'<span class="meta-info">
					<b>'.lang('joined').'</b>: '.ee()->localize->format_date(ee()->session->userdata('date_format', ee()->config->item('date_format')), $member->join_date).'<br>
					<b>'.lang('last_visit').'</b>: '.$last_visit.'
				</span>',
				$group
			);

			$toolbar = array('toolbar_items' => $toolbar);

			// add the toolbar if they can edit members
			if ($can_edit_member && ee('Permission')->can('edit_members'))
			{
				$column[] = $toolbar;
			}
			else
			{
				$column[] = ['toolbar_items' => []];
			}

			// add the checkbox if they can delete members
			if (ee('Permission')->can('delete_members'))
			{
				$column[] = array(
					'name' => 'selection[]',
					'value' => $member->member_id,
					'data' => array(
						'confirm' => lang('member') . ': <b>' . htmlentities($member->username, ENT_QUOTES, 'UTF-8') . '</b>'
					),
					'disabled' => ! $can_edit_member
				);
			}

			if ($member_id && $member->member_id == $member_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);
		}

		$table->setData($data);

		return $table;
	}

	protected function makeAndApplyFilters($members, $roles = FALSE)
	{
		$filters = ee('CP/Filter');

		if ($roles)
		{
			$roles = ee('Model')->get('Role')
				->order('name', 'asc')
				->all()
				->getDictionary('role_id', 'name');

			$role_filter = $filters->make('role_id', 'role_filter', $roles);
			$role_filter->setPlaceholder(lang('all'));
			$role_filter->disableCustomValue();

			$filters->add($role_filter);
		}

		$filters->add('Keyword');

		$filter_values = $filters->values();

		foreach ($filter_values as $key => $value)
		{
			if ($value)
			{
				if ($key == 'filter_by_keyword')
				{
					$members->search(['screen_name', 'username', 'email', 'member_id'], $value);
				}
				elseif ($key == 'role_filter')
				{
					$role = ee('Model')->get('Role', $value)->first();

					if ($role)
					{
						$members->filter('member_id', 'IN', $role->Members->pluck('member_id'));
					}
				}
				else
				{
					$members->filter($key, $value);
				}
			}
		}

		$filters->add('Perpage', $members->count(), 'show_all_members');

		return $filters;
	}

	public function delete()
	{

	}

	public function create()
	{

	}

	/**
	 * Approve pending members
	 *
	 * @param int|array $ids The ID(s) of the member(s) being approved
	 * @return void
	 */
	public function approve($ids)
	{
		if ( ! ee('Permission')->can('edit_members') OR
			ee('Request')->method() !== 'POST')
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! is_array($ids))
		{
			$ids = array($ids);
		}

		$members = ee('Model')->get('Member', $ids)
			->fields('member_id', 'username', 'screen_name', 'email', 'role_id')
			->filter('role_id', 4)
			->all();

		if (ee()->config->item('approved_member_notification') == 'y')
		{
			$template = ee('Model')->get('SpecialtyTemplate')
				->filter('template_name', 'validated_member_notify')
				->first();

			foreach ($members as $member)
			{
				$this->pendingMemberNotification($template, $member, array('email' => $member->email));
			}
		}

		$members->role_id = ee()->config->item('default_primary_role');
		$members->save();

		/* -------------------------------------------
		/* 'cp_members_validate_members' hook.
		/*  - Additional processing when member(s) are validated in the CP
		/*  - Added 1.5.2, 2006-12-28
		*/
			ee()->extensions->call('cp_members_validate_members', $ids);
			if (ee()->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		// Update
		ee()->stats->update_member_stats();

		if ($members->count() == 1)
		{
			ee('CP/Alert')->makeInline('view-members')
				->asSuccess()
				->withTitle(lang('member_approved_success'))
				->addToBody(sprintf(lang('member_approved_success_desc'), $members->first()->username))
				->defer();
		}
		else
		{
			ee('CP/Alert')->makeInline('view-members')
				->asSuccess()
				->withTitle(lang('members_approved_success'))
				->addToBody(lang('members_approved_success_desc'))
				->addToBody($members->pluck('username'))
				->defer();
		}

		ee()->functions->redirect(ee('CP/URL', 'members/pending'));
	}

	/**
	 * Set the header for the members section
	 * @param String $form_url Form URL
	 * @param String $search_button_value The text for the search button
	 */
	protected function stdHeader($form_url, $search_button_value = '')
	{
		$search_button_value = ($search_button_value) ?: lang('search_members_button');

		$header = array(
			'title' => lang('member_manager'),
			'toolbar_items' => array(
				'settings' => array(
					'href' => ee('CP/URL')->make('settings/members'),
					'title' => lang('member_settings')
				),
			),
			'form_url' => $form_url,
			'search_button_value' => $search_button_value
		);

		if ( ! ee('Permission')->can('access_settings'))
		{
			unset($header['toolbar_items']);
		}

		ee()->view->header = $header;
	}
}
