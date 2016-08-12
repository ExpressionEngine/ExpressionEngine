<?php

namespace EllisLab\ExpressionEngine\Controller\Members;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Controller\Members;

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
 * ExpressionEngine CP Members Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Groups extends Members\Members {

	private $base_url;
	private $index_url;
	private $site_id;
	private $super_admin;
	private $group;
	private $group_id;
	private $query_string = array();
	private $no_delete		= array('1', '2', '3', '4'); // Member groups that can not be deleted

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_admin_mbr_groups'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->base_url = ee('CP/URL')->make('members/groups');
		$this->site_id = (int) ee()->config->item('site_id');
		$this->super_admin = $this->session->userdata('group_id') == 1;
		$this->set_view_header($this->base_url, lang('search_member_groups_button'));
		$this->generateSidebar('groups');
	}

	/**
	 * Group List Index
	 */
	public function index()
	{
		$perpage = $this->config->item('memberlist_row_limit');
		$sort_col = ee()->input->get('sort_col') ?: 'group_id';
		$sort_col = ($sort_col == 'id') ? 'group_id' : $sort_col;
		$sort_dir = ee()->input->get('sort_dir') ?: 'asc';
		$page = ee()->input->get('page') > 0 ? ee()->input->get('page') : 1;
		$offset = ! empty($page) ? ($page - 1) * $perpage : 0;

		$table = ee('CP/Table', array(
			'sort_col' => $sort_col,
			'sort_dir' => $sort_dir,
			'limit' => $perpage
		));

		$columns = array(
			'id' => array(
				'type'	=> Table::COL_ID,
			),
			'group_title' => array(
				'encode' => FALSE
			),
			'status' => array(
				'type' => Table::COL_STATUS
			),
			'manage' => array(
				'type'	=> Table::COL_TOOLBAR
			)
		);

		if ( ! ee()->cp->allowed_group_any('can_create_member_groups', 'can_edit_member_groups'))
		{
			unset($columns['manage']);
		}

		if (ee()->cp->allowed_group('can_delete_member_groups'))
		{
			$columns[] = array(
				'type'	=> Table::COL_CHECKBOX
			);
		}

		$table->setColumns($columns);

		$data = array();
		$groupData = array();
		$total = ee()->api->get('MemberGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->count();

		$groups = ee()->api->get('MemberGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->order($sort_col, $sort_dir)
			->limit($perpage)
			->offset($offset);

		$search = ee()->input->post('search');

		if ( ! empty($search))
		{
			$groups = $groups->filter('group_title', 'LIKE', "%$search%");
		}

		$groups = $groups->all();

		foreach ($groups as $group)
		{
			$edit_link = ee('CP/URL')->make('members/groups/edit/' . $group->group_id);
			$toolbar = array('toolbar_items' => array(
				'edit' => array(
					'href' => $edit_link,
					'title' => strtolower(lang('edit'))
				),
				'copy' => array(
					'href' => ee('CP/URL')->make('members/groups/copy/' . $group->group_id),
					'title' => strtolower(lang('copy'))
				)
			));


			$status = ($group->is_locked == 'y') ? 'locked' : 'unlocked';
			$count = ee('Model')->get('Member')->filter('group_id', $group->group_id)->count();
			$href = ee('CP/URL')->make('members', array('group' => $group->group_id));
			$title = '<a href="' . $edit_link . '">' . htmlentities($group->group_title, ENT_QUOTES, 'UTF-8') . '</a>';

			if ( ! ee()->cp->allowed_group('can_create_member_groups'))
			{
				unset($toolbar['toolbar_items']['copy']);
			}

			if ( ! ee()->cp->allowed_group('can_edit_member_groups') || ($group->is_locked == 'y' && ee()->session->userdata('group_id') != 1))
			{
				$title = $group->group_title;
				unset($toolbar['toolbar_items']['edit']);
			}

			$title .= " <a href='$href' alt='" . lang('view_members') . $group->group_title ."'>($count)</a>";

			$bulk_checkbox_diabled = (in_array($group->group_id, $this->no_delete)) ? TRUE : NULL;

			$row = array(
				'id' => $group->group_id,
				'title' => $title,
				'status' => array('class' => $status, 'content' => lang($status))
			);

			if (isset($columns['manage']))
			{
				$row[] = $toolbar;
			}

			if (ee()->cp->allowed_group('can_delete_member_groups'))
			{
				$row[] = array(
					'name' => 'selection[]',
					'value' => $group->group_id,
					'disabled' => $bulk_checkbox_diabled,
					'data'	=> array(
						'confirm' => lang('group') . ': <b>' . htmlentities($group->group_title, ENT_QUOTES, 'UTF-8') . '</b>'
					)
				);
			}

			$groupData[] = $row;
		}

		$table->setNoResultsText('no_search_results');
		$table->setData($groupData);
		$data['table'] = $table->viewData($this->base_url);
		$data['form_url'] = ee('CP/URL')->make('members/groups/delete')->compile();

		$base_url = $data['table']['base_url'];

		if ( ! empty($data['table']['data']))
		{
			$data['pagination'] = ee('CP/Pagination', $total)
				->perPage($perpage)
				->currentPage($page)
				->render($base_url);
		}

		// Set search results heading
		if ( ! empty($data['table']['search']))
		{
			ee()->view->cp_heading = sprintf(
				lang('search_results_heading'),
				$data['table']['total_rows'],
				$data['table']['search']
			);
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('members') . ': <b>### ' . lang('members') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('all_member_groups');
		ee()->cp->render('members/member_group_manager', $data);
	}

	public function create()
	{
		if ( ! ee()->cp->allowed_group('can_create_member_groups'))
		{
			show_error(lang('unauthorized_access'));
		}

		$vars = array(
			'cp_page_title' => lang('create_member_group'),
			'website_access' => 'can_view_online_system',
		);
		$this->base_url = ee('CP/URL')->make('members/groups/create/', $this->query_string);

		$vars['sections'] = $this->buildForm($vars);
		$this->form($vars);
	}

	public function copy($group_id)
	{
		if ( ! ee()->cp->allowed_group('can_create_member_groups'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->base_url = ee('CP/URL')->make('members/groups/create/', $this->query_string);

		$this->group = ee('Model')->get('MemberGroup', $group_id)->first();
		$master = $this->groupData($this->group);
		unset($master['group_id'], $master['site_id']);

		$vars = $this->group->getValues();
		$vars['cp_page_title'] = sprintf(lang('copy_member_group'), $this->group->group_title);

		unset($vars['group_id'], $vars['group_title']);
		$sections = $this->buildForm($vars);

		$current = $this->groupData($this->group, $sections);

		unset($current['group_id'], $current['group_title']);
		$vars['sections'] = $this->buildForm(array_merge($vars, $current));

		$this->group = NULL;
		$this->form($vars, $master);
	}

	public function edit($group_id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_member_groups'))
		{
			show_error(lang('unauthorized_access'));
		}

        $this->group = ee('Model')->get('MemberGroup')
            ->filter('group_id', $group_id)
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

		if ($this->group->is_locked == 'y' && ! $this->super_admin)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->group_id = (int) $this->group->group_id;
		$this->base_url = ee('CP/URL')->make('members/groups/edit/' . $group_id, $this->query_string);
		$vars = $this->group->getValues();
		$vars['cp_page_title'] = lang('edit_member_group');

		$sections = $this->buildForm($vars);
		$current = $this->groupData($this->group, $sections);
		$vars['sections'] = $this->buildForm(array_merge($vars, $current));
		$this->form($vars, $current);
	}

	/**
	 * Delete member group selection
	 *
	 * @access public
	 * @return void
	 */
	public function delete()
	{
		if ( ! ee()->cp->allowed_group('can_delete_member_groups'))
		{
			show_error(lang('unauthorized_access'));
		}

		$replacement = ee()->input->post('replacement');
		$groups = ee()->input->post('selection');

		if ($replacement == 'delete')
		{
			$replacement = NULL;
		}

		$group_info = ee('Model')->get('MemberGroup', $groups)->all();

		foreach ($group_info as $group)
		{
			if ($group->is_locked == 'y' && ! $this->super_admin)
			{
				show_error(lang('unauthorized_access'));
			}
		}

		$group_names = $group_info->pluck('group_title');
		$group_names = array_map(function($group_name)
		{
			return htmlentities($group_name, ENT_QUOTES, 'UTF-8');
		}, $group_names);


		if (is_array($groups))
		{
			foreach ($groups as $group)
			{
				$this->delete_member_group($group, $replacement);
			}
		}

		ee('CP/Alert')->makeInline('member_groups')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('member_groups_removed_desc'))
			->addToBody($group_names)
			->defer();

		ee()->functions->redirect($this->base_url);
	}

	/**
	 * Delete member group confirm
	 *
	 * Warning message shown when you try to delete a group
	 *
	 * @return	mixed
	 */
	public function confirm()
	{
		//  Only super admins can delete member groups
		if ( ! ee()->cp->allowed_group('can_delete_member_groups'))
		{
			show_error(lang('unauthorized_access'));
		}

		$groups = ee()->input->post('selection');

		// You can't delete these groups
		$no_delete = array_intersect($groups, $this->no_delete);

		if ( ! empty($no_delete))
		{
			show_error(lang('unauthorized_access'));
		}

		$vars['groups'] = ee('Model')->get('MemberGroup', $groups)
					->all()
					->filter(function($group) {
						return count($group->Members) > 0;
					});

		$vars['new_groups'] = array('delete' => 'None');
		$vars['new_groups'] += ee('Model')->get('MemberGroup')
								->filter('group_id', 'NOT IN', $groups)
								->all()
								->getDictionary('group_id', 'group_title');

		ee()->view->cp_page_title = lang('delete_member_group');
		ee()->cp->render('members/delete_member_group_conf', $vars);
	}

	/**
	 * delete_member_group
	 *
	 * @param mixed $group_id
	 * @param mixed $replacement
	 * @access public
	 * @return void
	 */
	private function delete_member_group($group_id, $replacement = null)
	{
		if (in_array($group_id, $this->no_delete))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('member_model');
		$this->member_model->delete_member_group($group_id, $replacement);
	}

	private function form($vars = array(), $values = array())
	{
		ee()->load->helper('array');

		ee()->form_validation->set_rules(array(
			array(
				 'field' => 'group_title',
				 'label' => 'lang:group_title',
				 'rules' => 'valid_xss_check|required|unique'
			),
			array(
				 'field' => 'group_description',
				 'label' => 'lang:group_description',
				 'rules' => 'valid_xss_check'
			),
			array(
				 'field' => 'mbr_delete_notify_emails',
				 'label' => 'lang:mbr_delete_notify_emails',
				 'rules' => 'valid_xss_check|valid_emails'
			),
			array(
				 'field' => 'search_flood_control',
				 'label' => 'lang:search_flood_control',
				 'rules' => 'is_natural'
			),
			array(
				 'field' => 'prv_msg_storage_limit',
				 'label' => 'lang:prv_msg_storage_limit',
				 'rules' => 'is_natural'
			),
			array(
				 'field' => 'prv_msg_send_limit',
				 'label' => 'lang:prv_msg_send_limit',
				 'rules' => 'is_natural'
			),
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			if ($this->save($vars['sections']))
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('member_group_updated'))
					->addToBody(lang('member_group_updated_desc'))
					->defer();
			}

			ee()->functions->redirect(ee('CP/URL', $this->index_url, $this->query_string));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('settings_save_error'))
				->addToBody(lang('settings_save_error_desc'))
				->now();
		}

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text = sprintf(lang('btn_save'), lang('member_group'));
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}

	private function groupData($group, $form = array())
	{
		$result = $group->getValues();

		foreach ($form as $section)
		{
			if (array_key_exists('settings', $section))
			{
				$section = $section['settings'];
			}

			foreach ($section as $fieldset)
			{
				foreach ($fieldset['fields'] as $name => $field)
				{
					if ($field['type'] == 'checkbox')
					{
						if ((bool)count(array_filter(array_keys($field['choices']), 'is_string')))
						{
							$result[$name] = array();
							foreach ($field['choices'] as $choice => $lang)
							{
								if ($result[$choice] === TRUE)
								{
									$result[$name][] = $choice;
								}
							}
						}
					}
				}
			}
		}

		$result['addons_access'] = $this->group->AssignedModules->pluck('module_id');
		$result['template_groups'] = $this->group->AssignedTemplateGroups->pluck('group_id');
		$result['allowed_channels'] = $this->group->AssignedChannels->pluck('channel_id');

		return $result;
	}

	private function defaults()
	{
		return $defaults;
	}

	private function save($sections)
	{
		$this->index_url = 'members/groups';
		$allowed_channels = ee()->input->post('allowed_channels') ?: array();
		$allowed_template_groups = ee()->input->post('allowed_template_groups');
		$allowed_addons = ee()->input->post('addons_access');
		$ignore = array('allowed_template_groups', 'allowed_channels', 'addons_access');

		if ( ! empty($this->group))
		{
			$group = $this->group;
		}
		else
		{
			$group = ee('Model')->make('MemberGroup');
		}

		// Set our various permissions if we're not editing the Super Admin
		if ($group->group_id !== 1)
		{
			$group->AssignedModules = ee('Model')->get('Module', $allowed_addons)->all();
			$group->AssignedTemplateGroups = ee('Model')->get('TemplateGroup', $allowed_template_groups)->all();
			$group->assignChannels($allowed_channels);
		}

		foreach ($sections as $section)
		{
			if (count(array_keys($section)) == 2 && array_key_exists('settings', $section))
			{
				$section = $section['settings'];
			}

			foreach ($section as $item)
			{
				foreach ($item['fields'] as $field => $options)
				{
					if ( ! in_array($field, $ignore))
					{
						$submitted = ee()->input->post($field);
						$default = $group->hasProperty($field) ? $group->$field : NULL;

						if ($options['type'] == 'checkbox')
						{
							$default = array();
							$submitted = ee()->input->post($field);
						}

						$submitted = $submitted === FALSE ? $default : $submitted;

						if (is_array($submitted))
						{
							$choices = array_keys($options['choices']);
							$deselected = array_diff($choices, $submitted);

							// Validate submitted against choices to prevent
							// arbitrary properties from being set
							$submitted = array_intersect($choices, $submitted);

							foreach ($submitted as $item)
							{
								$group->$item = 'y';
							}

							foreach ($deselected as $item)
							{
								$group->$item = 'n';
							}
						}
						else
						{
							$group->$field = $submitted;
						}
					}
				}
			}
		}

		// This field isn't present in the section array, it's shimmy'd into a radio selection
		$group->cp_homepage_channel = ee()->input->post('cp_homepage_channel');

		if ( empty($group->site_id))
		{
			$group->site_id = ee()->config->item('site_id');
		}

		$group->save();

		$this->query_string['group'] = $group->group_id;

		return TRUE;
	}

	private function buildForm($values)
	{
		// @TODO: This should be refactored to remove the need for the
		// `element()` method
		ee()->load->helper('array');

		ee()->cp->add_js_script(array(
			'file' => array('cp/form_group'),
		));

		if (isset($values['group_id']) && $values['group_id'] == 1)
		{
			$vars = array(
				array(
					array(
						'title' => 'group_name',
						'fields' => array(
							'group_title' => array(
								'type' => 'text',
								'value' => element('group_title', $values),
								'required' => TRUE
							)
						)
					),
					array(
						'title' => 'group_description',
						'fields' => array(
							'group_description' => array(
								'type' => 'textarea',
								'value' => element('group_description', $values)
							)
						)
					),
					array(
						'title' => 'include_members_in',
						'desc' => 'include_members_in_desc',
						'fields' => array(
							'include_members_in' => array(
								'type' => 'checkbox',
								'choices' => array(
									'include_in_authorlist' => lang('include_in_authorlist'),
									'include_in_memberlist' => lang('include_in_memberlist'),
								),
								'value' => element('include_members_in', $values)
							),
						)
					)
				)
			);
		}
		else
		{

			$template_groups = ee('Model')->get('TemplateGroup')
				->filter('site_id', ee()->config->item('site_id'))
				->all()
				->getDictionary('group_id', 'group_name');

			$addons = ee('Model')->get('Module')
				->fields('module_id', 'module_name')
				->filter('module_name', 'NOT IN', array('channel', 'comment', 'filepicker')) // @TODO This REALLY needs abstracting.
				->all()
				->filter(function($addon) {
					$provision = ee('Addon')->get(strtolower($addon->module_name));

					if ( ! $provision)
					{
						return FALSE;
					}

					$addon->module_name = $provision->getName();
					return TRUE;
				})
				->getDictionary('module_id', 'module_name');

			$allowed_channels = ee('Model')->get('Channel')
				->filter('site_id', ee()->config->item('site_id'))
				->all()
				->getDictionary('channel_id', 'channel_title');

			$vars = array(
				array(
					array(
						'title' => 'group_name',
						'fields' => array(
							'group_title' => array(
								'type' => 'text',
								'value' => element('group_title', $values),
								'required' => TRUE
							)
						)
					),
					array(
						'title' => 'group_description',
						'fields' => array(
							'group_description' => array(
								'type' => 'textarea',
								'value' => element('group_description', $values)
							)
						)
					),
					array(
						'title' => 'security_lock',
						'desc' => 'lock_description',
						'fields' => array(
							'is_locked' => array(
								'type' => 'inline_radio',
								'choices' => array(
									'y' => 'enable',
									'n' => 'disable'
								),
								'value' => element('is_locked', $values)
							)
						)
					),
				),
				'general_access' => array(
					array(
						'title' => 'site_access',
						'desc' => 'site_access_desc',
						'fields' => array(
							'website_access' => array(
								'type' => 'checkbox',
								'choices' => array(
									'can_view_online_system' => lang('can_view_online_system'),
									'can_view_offline_system' => lang('can_view_offline_system')
								),
								'value' => element('website_access', $values)
							),
						)
					),
					array(
						'title' => 'can_view_profiles',
						'desc' => 'can_view_profiles_desc',
						'fields' => array(
							'can_view_profiles' => array(
								'type' => 'yes_no',
								'value' => element('can_view_profiles', $values)
							)
						)
					),
					array(
						'title' => 'can_delete_self',
						'desc' => 'can_delete_self_desc',
						'fields' => array(
							'can_delete_self' => array(
								'type' => 'yes_no',
								'value' => element('can_delete_self', $values)
							)
						)
					),
					array(
						'title' => 'mbr_delete_notify_emails',
						'desc' => 'mbr_delete_notify_emails_desc',
						'fields' => array(
							'mbr_delete_notify_emails' => array(
								'type' => 'text',
								'value' => element('mbr_delete_notify_emails', $values)
							)
						)
					),
					array(
						'title' => 'include_members_in',
						'desc' => 'include_members_in_desc',
						'fields' => array(
							'include_members_in' => array(
								'type' => 'checkbox',
								'choices' => array(
									'include_in_authorlist' => lang('include_in_authorlist'),
									'include_in_memberlist' => lang('include_in_memberlist'),
								),
								'value' => element('include_members_in', $values)
							),
						)
					)
				),
				'commenting' => array(
					array(
						'title' => 'can_post_comments',
						'desc' => 'can_post_comments_desc',
						'fields' => array(
							'can_post_comments' => array(
								'type' => 'yes_no',
								'value' => element('can_post_comments', $values),
								'group_toggle' => array(
									'y' => 'can_post_comments'
								)
							)
						)
					),
					array(
						'title' => 'exclude_from_moderation',
						'desc' => sprintf(lang('exclude_from_moderation_desc'), ee('CP/URL', 'settings/comments')),
						'group' => 'can_post_comments',
						'fields' => array(
							'exclude_from_moderation' => array(
								'type' => 'yes_no',
								'value' => element('exclude_from_moderation', $values)
							)
						)
					),
					array(
						'title' => 'comment_actions',
						'desc' => 'comment_actions_desc',
						'caution' => TRUE,
						'fields' => array(
							'comment_actions' => array(
								'type' => 'checkbox',
								'choices' => array(
									'can_moderate_comments' => lang('can_moderate_comments'),
									'can_edit_own_comments' => lang('can_edit_own_comments'),
									'can_delete_own_comments' => lang('can_delete_own_comments'),
									'can_edit_all_comments' => lang('can_edit_all_comments'),
									'can_delete_all_comments' => lang('can_delete_all_comments')
								),
								'value' => element('comment_actions', $values)
							),
						)
					)
				),
				'search' => array(
					array(
						'title' => 'can_search',
						'desc' => 'can_search_desc',
						'fields' => array(
							'can_search' => array(
								'type' => 'yes_no',
								'value' => element('can_search', $values),
								'group_toggle' => array(
									'y' => 'can_search'
								)
							)
						)
					),
					array(
						'title' => 'search_flood_control',
						'desc' => 'search_flood_control_desc',
						'group' => 'can_search',
						'fields' => array(
							'search_flood_control' => array(
								'type' => 'text',
								'value' => element('search_flood_control', $values, 0)
							)
						)
					)
				),
				'personal_messaging' => array(
					array(
						'title' => 'can_send_private_messages',
						'desc' => 'can_send_private_messages_desc',
						'fields' => array(
							'can_send_private_messages' => array(
								'type' => 'yes_no',
								'value' => element('can_send_private_messages', $values),
								'group_toggle' => array(
									'y' => 'can_access_pms'
								)
							)
						)
					),
					array(
						'title' => 'prv_msg_send_limit',
						'desc' => 'prv_msg_send_limit_desc',
						'group' => 'can_access_pms',
						'fields' => array(
							'prv_msg_send_limit' => array(
								'type' => 'text',
								'value' => element('prv_msg_send_limit', $values, 0)
							)
						)
					),
					array(
						'title' => 'prv_msg_storage_limit',
						'desc' => 'prv_msg_storage_limit_desc',
						'group' => 'can_access_pms',
						'fields' => array(
							'prv_msg_storage_limit' => array(
								'type' => 'text',
								'value' => element('prv_msg_storage_limit', $values, 0)
							)
						)
					),
					array(
						'title' => 'can_attach_in_private_messages',
						'desc' => 'can_attach_in_private_messages_desc',
						'group' => 'can_access_pms',
						'fields' => array(
							'can_attach_in_private_messages' => array(
								'type' => 'yes_no',
								'value' => element('can_attach_in_private_messages', $values)
							)
						)
					),
					array(
						'title' => 'can_send_bulletins',
						'desc' => 'can_send_bulletins_desc',
						'group' => 'can_access_pms',
						'fields' => array(
							'can_send_bulletins' => array(
								'type' => 'yes_no',
								'value' => element('can_send_bulletins', $values)
							)
						)
					)
				),
				'control_panel' => array(
					array(
						'title' => 'can_access_cp',
						'desc' => 'can_access_cp_desc',
						'caution' => TRUE,
						'fields' => array(
							'can_access_cp' => array(
								'type' => 'yes_no',
								'value' => element('can_access_cp', $values),
								'group_toggle' => array(
									'y' => 'can_access_cp'
								)
							)
						)
					),
					array(
						'title' => 'default_cp_homepage',
						'desc' => 'default_cp_homepage_desc',
						'group' => 'can_access_cp',
						'fields' => array(
							'cp_homepage' => array(
								'type' => 'radio',
								'choices' => array(
									'overview' => lang('cp_overview').' &mdash; <i>'.lang('default').'</i>',
									'entries_edit' => lang('edit_listing'),
									'publish_form' => lang('publish_form').' &mdash; '.
										form_dropdown('cp_homepage_channel', $allowed_channels, element('cp_homepage_channel', $values)),
									'custom' => lang('custom_uri'),
								),
								'value' => element('cp_homepage', $values, 'overview')
							),
							'cp_homepage_custom' => array(
								'type' => 'text',
								'value' => element('cp_homepage_custom', $values)
							)
						)
					),
					array(
						'title' => 'footer_helper_links',
						'desc' => 'footer_helper_links_desc',
						'group' => 'can_access_cp',
						'fields' => array(
							'footer_helper_links' => array(
								'type' => 'checkbox',
								'choices' => array(
									'can_access_footer_report_bug' => lang('report_bug'),
									'can_access_footer_new_ticket' => lang('new_ticket'),
									'can_access_footer_user_guide' => lang('user_guide'),
								),
								'value' => element('footer_helper_links', $values)
							)
						)
					),
					array(
						'title'  => 'homepage_news',
						'desc'   => 'homepage_news_desc',
						'group'  => 'can_access_cp',
						'fields' => array(
							'can_view_homepage_news' => array(
								'type' => 'yes_no',
								'value' => element('can_view_homepage_news', $values)
							)
						)
					)
				),
				'channels' => array(
					'group' => 'can_access_cp',
					'settings' => array(
						array(
							'title' => 'can_admin_channels',
							'desc' => 'can_admin_channels_desc',
							'caution' => TRUE,
							'fields' => array(
								'can_admin_channels' => array(
									'type' => 'yes_no',
									'value' => element('can_admin_channels', $values),
									'group_toggle' => array(
										'y' => 'can_admin_channels'
									)
								)
							)
						),
						array(
							'title' => 'channels',
							'desc' => 'allowed_actions_desc',
							'group' => 'can_admin_channels',
							'fields' => array(
								'channel_permissions' => array(
									'choices' => array(
										'can_create_channels' => lang('create_channels'),
										'can_edit_channels' => lang('edit_channels'),
										'can_delete_channels' => lang('delete_channels')
									),
									'type' => 'checkbox',
									'value' => element('channel_permissions', $values)
								)
							)
						),
						array(
							'title' => 'channel_fields',
							'desc' => 'allowed_actions_desc',
							'group' => 'can_admin_channels',
							'fields' => array(
								'channel_field_permissions' => array(
									'choices' => array(
										'can_create_channel_fields' => lang('create_channel_fields'),
										'can_edit_channel_fields' => lang('edit_channel_fields'),
										'can_delete_channel_fields' => lang('delete_channel_fields')
									),
									'type' => 'checkbox',
									'value' => element('channel_field_permissions', $values)
								)
							)
						),
						array(
							'title' => 'channel_categories',
							'desc' => 'allowed_actions_desc',
							'group' => 'can_admin_channels',
							'fields' => array(
								'channel_category_permissions' => array(
									'choices' => array(
										'can_create_categories' => lang('create_categories'),
										'can_edit_categories' => lang('edit_categories'),
										'can_delete_categories' => lang('delete_categories')
									),
									'type' => 'checkbox',
									'value' => element('channel_category_permissions', $values)
								)
							)
						),
						array(
							'title' => 'channel_statuses',
							'desc' => 'allowed_actions_desc',
							'group' => 'can_admin_channels',
							'fields' => array(
								'channel_status_permissions' => array(
									'choices' => array(
										'can_create_statuses' => lang('create_statuses'),
										'can_edit_statuses' => lang('edit_statuses'),
										'can_delete_statuses' => lang('delete_statuses')
									),
									'type' => 'checkbox',
									'value' => element('channel_status_permissions', $values)
								)
							)
						)
					)
				),
				'channel_entries_management' => array(
					array(
						'title' => 'channel_entry_actions',
						'desc' => 'channel_entry_actions_desc',
						'caution' => TRUE,
						'fields' => array(
							'channel_entry_actions' => array(
								'type' => 'checkbox',
								'choices' => array(
									'can_create_entries' => lang('can_create_entries'),
									'can_edit_self_entries' => lang('can_edit_self_entries'),
									'can_delete_self_entries' => lang('can_delete_self_entries'),
									'can_edit_other_entries' => lang('can_edit_other_entries'),
									'can_delete_all_entries' => lang('can_delete_all_entries'),
									'can_assign_post_authors' => lang('can_assign_post_authors')
								),
								'value' => element('channel_entry_actions', $values)
							),
						)
					),
					array(
						'title' => 'allowed_channels',
						'desc' => 'allowed_channels_desc',
						'fields' => array(
							'allowed_channels' => array(
								'type' => 'checkbox',
								'choices' => $allowed_channels,
								'value' => element('allowed_channels', $values)
							),
						)
					)
				),
				'file_manager' => array(
					'group' => 'can_access_cp',
					'settings' => array(
						array(
							'title' => 'can_access_file_manager',
							'desc' => 'file_manager_desc',
							'fields' => array(
								'can_access_files' => array(
									'type' => 'yes_no',
									'value' => element('can_access_files', $values),
									'group_toggle' => array(
										'y' => 'can_access_files'
									)
								)
							)
						),
						array(
							'title' => 'file_upload_directories',
							'desc' => 'allowed_actions_desc',
							'group' => 'can_access_files',
							'fields' => array(
								'file_upload_directories' => array(
									'choices' => array(
										'can_create_upload_directories' => lang('create_upload_directories'),
										'can_edit_upload_directories' => lang('edit_upload_directories'),
										'can_delete_upload_directories' => lang('delete_upload_directories'),
										),
									'type' => 'checkbox',
									'value' => element('file_upload_directories', $values)
								)
							)
						),
						array(
							'title' => 'files',
							'desc' => 'allowed_actions_desc',
							'group' => 'can_access_files',
							'fields' => array(
								'files' => array(
									'choices' => array(
										'can_upload_new_files' => lang('upload_new_files'),
										'can_edit_files' => lang('edit_files'),
										'can_delete_files' => lang('delete_files'),
									),
									'type' => 'checkbox',
									'value' => element('files', $values)
								)
							)
						)
					)
				),
				'members' => array(
					'group' => 'can_access_cp',
					'settings' => array(
						array(
							'title' => 'can_access_members',
							'desc' => 'can_access_members_desc',
							'fields' => array(
								'can_access_members' => array(
									'type' => 'yes_no',
									'value' => element('can_access_members', $values),
									'group_toggle' => array(
										'y' => 'can_access_members'
									)
								)
							)
						),
						array(
							'title' => 'can_admin_mbr_groups',
							'desc' => 'can_admin_mbr_groups_desc',
							'caution' => TRUE,
							'group' => 'can_access_members',
							'fields' => array(
								'can_admin_mbr_groups' => array(
									'type' => 'yes_no',
									'value' => element('can_admin_mbr_groups', $values)
								)
							)
						),
						array(
							'title' => 'member_groups',
							'desc' => 'allowed_actions_desc',
							'group' => 'can_access_members',
							'caution' => TRUE,
							'fields' => array(
								'member_group_actions' => array(
									'type' => 'checkbox',
									'choices' => array(
										'can_create_member_groups' => lang('create_member_groups'),
										'can_edit_member_groups' => lang('edit_member_groups'),
										'can_delete_member_groups' => lang('delete_member_groups'),
									),
									'value' => element('member_group_actions', $values)
								)
							)
						),
						array(
							'title' => 'members',
							'desc' => 'allowed_actions_desc',
							'group' => 'can_access_members',
							'caution' => TRUE,
							'fields' => array(
								'member_actions' => array(
									'type' => 'checkbox',
									'choices' => array(
										'can_create_members' => lang('create_members'),
										'can_edit_members' => lang('edit_members'),
										'can_delete_members' => lang('can_delete_members'),
										'can_ban_users' => lang('can_ban_users'),
										'can_email_from_profile' => lang('can_email_from_profile'),
										'can_edit_html_buttons' => lang('can_edit_html_buttons')
									),
									'value' => element('member_actions', $values)
								)
							)
						)
					)
				),
				'template_manager' => array(
					'group' => 'can_access_cp',
					'settings' => array(
						array(
							'title' => 'can_access_design',
							'desc' => 'can_access_design_desc',
							'fields' => array(
								'can_access_design' => array(
									'type' => 'yes_no',
									'value' => element('can_access_design', $values),
									'group_toggle' => array(
										'y' => 'can_access_design'
									)
								)
							)
						),
						array(
							'title' => 'can_admin_design',
							'desc' => 'can_admin_design_desc',
							'group' => 'can_access_design',
							'caution' => TRUE,
							'fields' => array(
								'can_admin_design' => array(
									'type' => 'yes_no',
									'value' => element('can_admin_design', $values)
								)
							)
						),
						array(
							'title' => 'template_groups',
							'desc' => 'allowed_actions_desc',
							'group' => 'can_access_design',
							'caution' => TRUE,
							'fields' => array(
								'template_group_permissions' => array(
									'choices' => array(
										'can_create_template_groups' => lang('create_template_groups'),
										'can_edit_template_groups' => lang('edit_template_groups'),
										'can_delete_template_groups' => lang('delete_template_groups'),
									),
									'type' => 'checkbox',
									'value' => element('template_group_permissions', $values)
								)
							)
						),
						array(
							'title' => 'template_partials',
							'desc' => 'allowed_actions_desc',
							'group' => 'can_access_design',
							'caution' => TRUE,
							'fields' => array(
								'template_partials' => array(
									'choices' => array(
										'can_create_template_partials' => lang('create_template_partials'),
										'can_edit_template_partials' => lang('edit_template_partials'),
										'can_delete_template_partials' => lang('delete_template_partials'),
									),
									'type' => 'checkbox',
									'value' => element('template_partials', $values)
								)
							)
						),
						array(
							'title' => 'template_variables',
							'desc' => 'allowed_actions_desc',
							'group' => 'can_access_design',
							'caution' => TRUE,
							'fields' => array(
								'template_variables' => array(
									'choices' => array(
										'can_create_template_variables' => lang('create_template_variables'),
										'can_edit_template_variables' => lang('edit_template_variables'),
										'can_delete_template_variables' => lang('delete_template_variables'),
									),
									'type' => 'checkbox',
									'value' => element('template_variables', $values)
								)
							)
						),
						array(
							'title' => 'templates',
							'desc' => 'template_permissions_desc',
							'group' => 'can_access_design',
							'caution' => TRUE,
							'fields' => array(
								'template_permissions' => array(
									'type' => 'checkbox',
									'choices' => array(
										'can_create_new_templates' => lang('create_new_templates'),
										'can_edit_templates' => lang('edit_templates'),
										'can_delete_templates' => lang('delete_templates')
									),
									'value' => element('template_permissions', $values)
								),
							)
						),
						array(
							'title' => 'allowed_template_groups',
							'desc' => 'allowed_template_groups_desc',
							'group' => 'can_access_design',
							'fields' => array(
								'allowed_template_groups' => array(
									'type' => 'checkbox',
									'choices' => $template_groups,
									'value' => element('template_groups', $values)
								),
							)
						)
					)
				),
				'addons' => array(
					'group' => 'can_access_cp',
					'settings' => array(
						array(
							'title' => 'can_access_addons',
							'desc' => 'can_access_addons_desc',
							'fields' => array(
								'can_access_addons' => array(
									'type' => 'yes_no',
									'value' => element('can_access_addons', $values),
									'group_toggle' => array(
										'y' => 'can_access_addons'
									)
								)
							)
						),
						array(
							'title' => 'can_admin_addons',
							'desc' => 'can_admin_addons_desc',
							'group' => 'can_access_addons',
							'caution' => TRUE,
							'fields' => array(
								'can_admin_addons' => array(
									'type' => 'yes_no',
									'value' => element('can_admin_addons', $values)
								)
							)
						),
						array(
							'title' => 'addons_access',
							'desc' => 'addons_access_desc',
							'group' => 'can_access_addons',
							'caution' => TRUE,
							'fields' => array(
								'addons_access' => array(
									'type' => 'checkbox',
									'choices' => $addons,
									'value' => element('addons_access', $values)
								)
							)
						),
						array(
							'title' => 'rte_toolsets',
							'desc' => 'allowed_actions_desc',
							'group' => 'can_access_addons',
							'fields' => array(
								'rte_toolsets' => array(
									'choices' => array(
										'can_upload_new_toolsets' => lang('upload_new_toolsets'),
										'can_edit_toolsets' => lang('edit_toolsets'),
										'can_delete_toolsets' => lang('delete_toolsets')
									),
									'type' => 'checkbox',
									'value' => element('rte_toolsets', $values)
								),
							)
						)
					)

				),
				'tools_utilities' => array(
					'group' => 'can_access_cp',
					'settings' => array(
						array(
							'title' => 'access_utilities',
							'desc' => 'access_utilities_desc',
							'fields' => array(
								'can_access_utilities' => array(
									'type' => 'yes_no',
									'value' => element('can_access_utilities', $values),
									'group_toggle' => array(
										'y' => 'can_access_utilities'
									)
								)
							)
						),
						array(
							'title' => 'utilities_section',
							'desc' => 'utilities_section_desc',
							'group' => 'can_access_utilities',
							'caution' => TRUE,
							'fields' => array(
								'access_tools' => array(
									'type' => 'checkbox',
									'choices' => array(
										'can_access_comm' => lang('can_access_communicate'),
										'can_access_translate' => lang('can_access_translate'),
										'can_access_import' => lang('can_access_import'),
										'can_access_sql_manager' => lang('can_access_sql'),
										'can_access_data' => lang('can_access_data')
									),
									'value' => element('access_tools', $values)
								)
							)
						)
					)
				),
				'logs' => array(
					'group' => 'can_access_cp',
					'settings' => array(
						array(
							'title' => 'can_access_logs',
							'desc' => 'can_access_logs_desc',
							'fields' => array(
								'can_access_logs' => array(
									'type' => 'yes_no',
									'value' => element('can_access_logs', $values)
								)
							)
						)
					)
				),
				'settings' => array(
					'group' => 'can_access_cp',
					'settings' => array(
						array(
							'title' => 'can_access_sys_prefs',
							'desc' => 'can_access_sys_prefs_desc',
							'caution' => TRUE,
							'fields' => array(
								'can_access_sys_prefs' => array(
									'type' => 'yes_no',
									'value' => element('can_access_sys_prefs', $values),
									'group_toggle' => array(
										'y' => 'can_access_sys_prefs'
									)
								)
							)
						),
						array(
							'title' => 'can_access_security_settings',
							'desc' => 'can_access_security_settings_desc',
							'group' => 'can_access_sys_prefs',
							'caution' => TRUE,
							'fields' => array(
								'can_access_security_settings' => array(
									'type' => 'yes_no',
									'value' => element('can_access_security_settings', $values)
								)
							)
						)
					)
				)
			);

			ee('CP/Alert')->makeInline('shared-form')
				->asWarning()
				->cannotClose()
				->addToBody(lang('access_privilege_warning'))
				->addToBody(lang('access_privilege_caution'), 'caution')
				->now();
		}

		return $vars;
	}

}
// END CLASS

// EOF
