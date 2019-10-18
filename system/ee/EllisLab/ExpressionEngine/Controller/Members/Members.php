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
use EllisLab\ExpressionEngine\Service\CP\Filter\Filter;
use EllisLab\ExpressionEngine\Service\Filter\FilterFactory;
use EllisLab\ExpressionEngine\Service\CP\Filter\FilterRunner;

/**
 * Members Controller
 */
class Members extends CP_Controller {

	protected $base_url;
	private $group;
	private $filter = TRUE;
	protected $perpage;
	protected $page = 1;
	protected $offset = 0;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		$this->perpage = ee()->config->item('memberlist_row_limit');
		$this->group_id = ($this->input->get_post('group') && $this->input->get_post('group') != 'all') ? $this->input->get_post('group') : '';

		if ( ! ee()->cp->allowed_group('can_access_members'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->lang->loadfile('members');
		ee()->load->model('member_model');
		ee()->load->library('form_validation');

		$this->base_url = ee('CP/URL')->make('members');
		$this->set_view_header($this->base_url);
	}

	protected function generateSidebar($active = NULL)
	{
		$sidebar = ee('CP/Sidebar')->make();

		$header = $sidebar->addHeader(lang('all_members'), ee('CP/URL')->make('members')->compile());

		if (ee()->cp->allowed_group('can_create_members'))
		{
			$header->withButton(lang('new'), ee('CP/URL')->make('members/create'));
		}

		$list = $header->addBasicList();

		if ($active == 'all_members')
		{
			$header->isActive();
		}

		if (ee()->cp->allowed_group('can_edit_members'))
		{
			$pending = $list->addItem(lang('pending_activation'), ee('CP/URL', 'members/pending')->compile());

			if ($active == 'pending')
			{
				$pending->isActive();
			}
		}

		if (ee()->cp->allowed_group('can_ban_users'))
		{
			$list->addItem(lang('manage_bans'), ee('CP/URL')->make('members/ban-settings'));
		}

		if (ee()->cp->allowed_group('can_admin_mbr_groups'))
		{
			$header = $sidebar->addHeader(lang('member_groups'), ee('CP/URL')->make('members/groups'));

			if (ee()->cp->allowed_group('can_create_member_groups'))
			{
				$header->withButton(lang('new'), ee('CP/URL')->make('members/groups/create'));
			}

			$item = $header->addBasicList()
				->addItem(lang('custom_member_fields'), ee('CP/URL')->make('members/fields'));

			if ($active == 'fields')
			{
				$item->isActive();
			}

			if ($active == 'groups')
			{
				$header->isActive();
			}
		}
	}

	/**
	 * MemberList
	 */
	public function index()
	{
		$member_name = $this->input->get_post('filter_by_keyword');

		$table = $this->initializeTable();

		$page = (ee()->input->get('page') > 0) ? ee()->input->get('page') : 1;

		$state = array(
			'sort'	=> array($table->sort_col => $table->sort_dir)
		);

		$params = array(
			'member_name' => $member_name
		);

		$data = $this->_member_search($state, $params);

		$this->generateSidebar('all_members');

		$table->setData($data['rows']);
		$data['table'] = $table->viewData($this->base_url);
		$data['form_url'] = ee('CP/URL')->make('members/delete');

		$base_url = $data['table']['base_url'];

		if ( ! empty($data['table']['data']))
		{
			$data['pagination'] = ee('CP/Pagination', $data['total_rows'])
				->perPage($data['per_page'])
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
			'file' => array('cp/confirm_remove', 'cp/members/members'),
		));

		$session = ee('Model')->get('Session', ee()->session->userdata('session_id'))->first();

		if ( ! $session->isWithinAuthTimeout())
		{
			$data['confirm_remove_secure_form_ctrls'] = [
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

		$data['can_delete_members'] = ee()->cp->allowed_group('can_delete_members');

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = ee()->view->cp_page_title ?: lang('all_members');
		ee()->cp->render('members/view_members', $data);
	}

	public function pending()
	{
		if ( ! ee()->cp->allowed_group('can_edit_members'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$action = ee()->input->post('bulk_action');

		if ($action)
		{
			$ids = ee()->input->post('selection');
			switch ($action)
			{
				case 'approve':
					$this->approve($ids);
					break;

				case 'decline':
					$this->decline($ids);
					break;

				case 'resend':
					$this->resend($ids);
					break;
			}

			ee()->functions->redirect(ee('CP/URL', 'members/pending'));
		}

		$this->generateSidebar('pending');

		$this->base_url = ee('CP/URL')->make('members/pending');

		$vars = array(
			'cp_page_title' => lang('pending_members'),
			'can_delete' => ee()->cp->allowed_group('can_delete_members'),
			'can_edit' => ee()->cp->allowed_group('can_edit_members'),
			'resend_available' => (ee()->config->item('req_mbr_activation') == 'email')
		);

		$checkboxes = $vars['can_delete'] || $vars['can_edit'] || $vars['resend_available'];

		$members = ee('Model')->get('Member')
			->with('MemberGroup')
			->filter('group_id', 4)
			->filter('MemberGroup.site_id', ee()->config->item('site_id'));

		$listings = $this->listingsPage($members, $this->base_url, 'no_pending_members_found', $checkboxes);

		$vars = array_merge($listings, $vars);

		$this->set_view_header($this->base_url);
		ee()->javascript->set_global('lang.remove_confirm', lang('members') . ': <b>### ' . lang('members') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		ee()->cp->render('members/pending', $vars);
	}


	/**
	 * Creates $vars array of filtered member Table/Pagination
	 *
	 * @param	Builder 	$members	Query builder object for Members
	 * @param	object 		$base_url	CP/URL
	 * @param	string 		$no_results_text
	 * @param	mixed 		$checkboxes	Optional T/F indicating permission to use checkboxes.
	 *          default of NULL falls back on using can_delete_members permission
	 * @return  $array 		$vars array of filtered member table data
	 */
	protected function listingsPage($members, $base_url, $no_results_text, $checkboxes = NULL)
	{
		$vars = array();

		// Allow them to tokenize searches
		// possible tokens: id, member_id, username, screen_name, email
		$search_terms = $this->_check_search_tokens(ee()->input->get_post('filter_by_keyword'));

		if ( ! empty($search_terms))
		{
			$keywords = ee()->input->get_post('filter_by_keyword');
			$vars['search_terms'] = ee('Format')->make('Text', $keywords)->convertToEntities();

			if ( ! is_array($search_terms))
			{
				$members->search(array('screen_name', 'username', 'email', 'member_id'), $search_terms);
			}
			else
			{
				foreach ($search_terms as $field => $term)
				{
					$members->search($field, $term);
				}
			}

			// Set search results heading
			ee()->view->cp_heading = sprintf(
				lang('search_results_heading'),
				$members->count(),
				$vars['search_terms']
			);
		}

		$total = $members->count();

		$filter = ee('CP/Filter')
				->add('Keyword')
				->add('Perpage', $total, 'show_all_banned');

		$this->renderFilters($filter);
		$members->limit($this->perpage)
			->offset($this->offset);

		$table = $this->buildTableFromMemberQuery($members, $checkboxes);
		$table->setNoResultsText($no_results_text);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			$vars['pagination'] = ee('CP/Pagination', $total)
				->perPage($this->perpage)
				->currentPage($this->page)
			->render($base_url);
		}

		return $vars;
	}


	public function banSettings()
	{
		if ( ! ee()->cp->allowed_group('can_ban_users'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->generateSidebar('ban');

		$this->base_url = ee('CP/URL', 'members/ban-settings');
		$this->set_view_header($this->base_url);

		$values = array(
			'banned_ips' => '',
			'banned_emails' => '',
			'banned_usernames' => '',
			'banned_screen_names' => '',
		);

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
			$site = ee('Model')->get('Site', ee()->config->item('site_id'))->first();

			foreach (array_keys($values) as $item)
			{
				$value = ee()->input->post($item);
				$value = implode('|', explode(NL, $value));
				$site->site_system_preferences->$item = $value;
			}

			$site->site_system_preferences->ban_action = ee()->input->post('ban_action');
			$site->site_system_preferences->ban_message = ee()->input->post('ban_message');
			$site->site_system_preferences->ban_destination = ee()->input->post('ban_destination');

			$site->save();

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

	private function initializeTable($checkboxes = NULL)
	{
		if (is_null($checkboxes))
		{
			$checkboxes = ee()->cp->allowed_group('can_delete_members');
		}

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
			'search' => ee()->input->get_post('filter_by_keyword'),
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
			'member_group' => array(
				'encode' => FALSE
			)
		);

		if (ee()->cp->allowed_group('can_edit_members'))
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
			$can_edit_member = FALSE;
			if (ee()->cp->allowed_group('can_edit_members'))
			{
				if ($member->MemberGroup->getId() == 1)
				{
					$can_edit_member = (bool) (ee()->session->userdata('group_id') == 1);
				}
				else
				{
					$can_edit_member = TRUE;
				}
			}

			$edit_link = ee('CP/URL')->make('members/profile/', array('id' => $member->member_id));
			$toolbar = array(
				'edit' => array(
					'href' => $edit_link,
					'title' => strtolower(lang('profile'))
				)
			);

			$attrs = array();

			switch ($member->MemberGroup->group_title)
			{
				case 'Banned':
					$group = "<span class='st-banned'>" . lang('banned') . "</span>";
					$attrs['class'] = 'banned';
					break;
				case 'Pending':
					$group = "<span class='st-pending'>" . lang('pending') . "</span>";
					$attrs['class'] = 'pending';
					if (ee()->cp->allowed_group('can_edit_members'))
					{
						$toolbar['approve'] = array(
							'href' => '#',
							'data-post-url' => ee('CP/URL')->make('members/approve/' . $member->member_id),
							'title' => strtolower(lang('approve'))
						);
					}
					break;
				default:
					$group = $member->MemberGroup->group_title;
			}

			$email = "<a href = '" . ee('CP/URL')->make('utilities/communicate/member/' . $member->member_id) . "'>".$member->email."</a>";

			if ($can_edit_member)
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
			if ($can_edit_member)
			{
				$column[] = $toolbar;
			}
			else
			{
				$column[] = ['toolbar_items' => []];
			}

			// add the checkbox if they can delete members
			if (ee()->cp->allowed_group('can_delete_members'))
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

	/**
	 * member search
	 *
	 * @return void
	 */
	private function _member_search($state, $params)
	{
		$search_value = $params['member_name'];
		$group_id = $this->group_id ?: '';
		$column_filter = ($this->input->get_post('column_filter')) ? $this->input->get_post('column_filter') : 'all';

		// Check for search tokens within the search_value
		$search_value = $this->_check_search_tokens($search_value);

		$convert = array(
			'member_group' => 'group_id',
			'dates' => 'join_date'
		);

		$sort_key = key($state['sort']);

		if (isset($convert[$sort_key]))
		{
			$sort = array($convert[$sort_key] => array_pop($state['sort']));
		}
		else
		{
			$sort = $state['sort'];
		}

		$total_rows = $this->member_model->count_members($group_id, $search_value, $column_filter);

		// Create filter object
		$group_ids = ee('Model')->get('MemberGroup')
			// Pending has its own view
			->filter('group_id', 'NOT IN', array(4))
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_title', 'asc')
			->all()
			->getDictionary('group_id', 'group_title');

		$options = $group_ids;
		$options['all'] = lang('all');

		$group = ee('CP/Filter')->make('group', 'member_group_filter', $options);
		$group->setPlaceholder(lang('all'));
		$group->disableCustomValue();


		$filters = ee('CP/Filter')
				->add($group)
				->add('Keyword')
				->add('Perpage', $total_rows, 'show_all_members');

		$this->renderFilters($filters);

		$members = $this->member_model->get_members($group_id, $this->perpage, $this->offset, $search_value, $sort, $column_filter);
		$members = $members ? $members->result_array() : array();
		$member_groups = $this->member_model->get_member_groups();
		$groups = array();

		foreach($member_groups->result() as $group)
		{
			$groups[$group->group_id] = $group->group_title;
		}

		$rows = array();



		foreach ($members as $member)
		{
			$can_edit_member = ee()->session->userdata('group_id') == 1 || $member['group_id'] != 1;

			$attributes = array();
			$edit_link = ee('CP/URL')->make('members/profile/', array('id' => $member['member_id']));
			$toolbar = array('toolbar_items' => array(
				'edit' => array(
					'href' => $edit_link,
					'title' => strtolower(lang('profile'))
				)
			));

			switch ($groups[$member['group_id']])
			{
				case 'Banned':
					$group = "<span class='st-banned'>" . lang('banned') . "</span>";
					$attributes['class'] = 'banned';
					break;
				case 'Pending':
					$group = "<span class='st-pending'>" . lang('pending') . "</span>";
					$attributes['class'] = 'pending';
					if ($can_edit_member && ee()->cp->allowed_group('can_edit_members'))
					{
						$toolbar['toolbar_items']['approve'] = array(
							'href' => '#',
							'data-post-url' => ee('CP/URL')->make('members/approve/' . $member['member_id']),
							'title' => strtolower(lang('approve'))
						);
					}
					break;
				default:
					$group = htmlentities($groups[$member['group_id']], ENT_QUOTES, 'UTF-8');
			}

			if (ee()->session->flashdata('highlight_id') == $member['member_id'])
			{
				$attributes['class'] = 'selected';
			}

			$email = "<a href = '" . ee('CP/URL')->make('utilities/communicate/member/' . $member['member_id']) . "'>".$member['email']."</a>";

			if ($can_edit_member && ee()->cp->allowed_group('can_edit_members'))
			{
				$username_display = "<a href = '" . $edit_link . "'>". $member['username']."</a>";
			}
			else
			{
				$username_display = $member['username'];
			}

			$username_display .= '<br><span class="meta-info">&mdash; '.$email.'</span>';
			$last_visit = ($member['last_visit']) ? ee()->localize->human_time($member['last_visit']) : '--';
			$row = array(
				'columns' => array(
					'id' => $member['member_id'],
					'username' => $username_display,
					'<span class="meta-info">
						<b>'.lang('joined').'</b>: '.ee()->localize->format_date(ee()->session->userdata('date_format', ee()->config->item('date_format')), $member['join_date']).'<br>
						<b>'.lang('last_visit').'</b>: '.$last_visit.'
					</span>',
					'member_group' => $group
				),
				'attrs' => $attributes
			);

			// add the toolbar if they can edit members
			if (ee()->cp->allowed_group('can_edit_members'))
			{
				if ($can_edit_member)
				{
					$row['columns'][] = $toolbar;
				}
				else
				{
					$row['columns'][] = ['toolbar_items' => []];
				}
			}

			// add the checkbox if they can delete members
			if (ee()->cp->allowed_group('can_delete_members'))
			{
				$row['columns'][] = array(
					'name' => 'selection[]',
					'value' => $member['member_id'],
					'data'	=> array(
						'confirm' => lang('member') . ': <b>' . htmlentities($member['username'], ENT_QUOTES, 'UTF-8') . '</b>'
					),
					'disabled' => ! $can_edit_member
				);
			}

			$rows[] = $row;
		}

		return array(
			'rows' => $rows,
			'per_page' => $this->perpage,
			'total_rows' => $total_rows,
			'member_name' => $params['member_name'],
			'member_groups' => $member_groups
		);
	}

	/**
	 * Approve pending members
	 *
	 * @param int|array $ids The ID(s) of the member(s) being approved
	 * @return void
	 */
	public function approve($ids)
	{
		if ( ! ee()->cp->allowed_group('can_edit_members') OR
			ee('Request')->method() !== 'POST')
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! is_array($ids))
		{
			$ids = array($ids);
		}

		$members = ee('Model')->get('Member', $ids)
			->fields('member_id', 'username', 'screen_name', 'email', 'group_id')
			->filter('group_id', 4)
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

		$members->group_id = ee()->config->item('default_member_group');
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
	 * Decline pending members
	 *
	 * @param array $ids The ID(s) of the member(s) being approved
	 * @return void
	 */
	private function decline(array $ids)
	{
		if ( ! ee()->cp->allowed_group('can_delete_members'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$members = ee('Model')->get('Member', $ids)
			->fields('member_id', 'username', 'screen_name', 'email', 'group_id')
			->filter('group_id', 4)
			->all();

		if (ee()->config->item('declined_member_notification') == 'y')
		{
			$template = ee('Model')->get('SpecialtyTemplate')
				->filter('template_name', 'decline_member_validation')
				->first();

			foreach ($members as $member)
			{
				$this->pendingMemberNotification($template, $member);
			}
		}

		$usernames = $members->pluck('username');
		$single = ($members->count() == 1);
		$members->delete();

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

		if ($single)
		{
			ee('CP/Alert')->makeInline('view-members')
				->asSuccess()
				->withTitle(lang('member_declined_success'))
				->addToBody(sprintf(lang('member_declined_success_desc'), $usernames[0]))
				->defer();
		}
		else
		{
			ee('CP/Alert')->makeInline('view-members')
				->asSuccess()
				->withTitle(lang('members_declined_success'))
				->addToBody(lang('members_declined_success_desc'))
				->addToBody($usernames)
				->defer();
		}
	}

	/**
	 * Resend activation emails for pending members
	 *
	 * @param array $ids The ID(s) of the member(s) being approved
	 * @return void
	 */
	private function resend(array $ids)
	{
		if ( ! ee()->cp->allowed_group('can_edit_members') OR
			ee()->config->item('req_mbr_activation') !== 'email')
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$members = ee('Model')->get('Member', $ids)
			->fields('member_id', 'username', 'screen_name', 'email', 'group_id', 'authcode')
			->filter('group_id', 4)
			->all();

		$template = ee('Model')->get('SpecialtyTemplate')
			->filter('template_name', 'mbr_activation_instructions')
			->first();

		$action_id = ee()->functions->fetch_action_id('Member', 'activate_member');

		foreach ($members as $member)
		{
			$swap = array(
				'email'     => $member->email,
				'activation_url' => ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$member->authcode
			);

			$this->pendingMemberNotification($template, $member, $swap);
		}

		if ($members->count() == 1)
		{
			ee('CP/Alert')->makeInline('view-members')
				->asSuccess()
				->withTitle(lang('member_activation_resent_success'))
				->addToBody(sprintf(lang('member_activation_resent_success_desc'), $member->username))
				->defer();
		}
		else
		{
			ee('CP/Alert')->makeInline('view-members')
				->asSuccess()
				->withTitle(lang('member_activation_resent_success'))
				->addToBody(lang('members_activation_resent_success_desc'))
				->addToBody($members->pluck('username'))
				->defer();
		}
	}

	/**
	 * Sends an email to a member based on a provided template.
	 *
	 * @param EllisLab\ExpressionEngine\Model\Template\SpecialtyTemplate $template The email template
	 * @param EllisLab\ExpressionEngine\Model\Member\Member $member The member to be emailed
	 * @return bool TRUE of the email sent, FALSE if it did not
	 */
	private function pendingMemberNotification($template, $member, array $extra_swap = array())
	{
		ee()->load->library('email');
		ee()->load->helper('text');

		$swap = array(
			'name'		=> $member->getMemberName(),
			'site_name'	=> stripslashes(ee()->config->item('site_name')),
			'site_url'	=> ee()->config->item('site_url'),
			'username'	=> $member->username,
			) + $extra_swap;

		$email_title = ee()->functions->var_swap($template->data_title, $swap);
		$email_message = ee()->functions->var_swap($template->template_data, $swap);

		ee()->email->wordwrap = TRUE;
		ee()->email->mailtype = ee()->config->item('mail_format');
		ee()->email->from(
			ee()->config->item('webmaster_email'),
			ee()->config->item('webmaster_name')
		);
		ee()->email->to($member->email);
		ee()->email->subject($email_title);
		ee()->email->message(entities_to_ascii($email_message));
		return ee()->email->send();
	}

	/**
	 * Display filters
	 *
	 * @param int
	 * @return void
	 */
	protected function renderFilters(FilterFactory $filters)
	{
		ee()->view->filters = $filters->render($this->base_url);
		$this->params = $filters->values();
		$this->perpage = $this->params['perpage'];
		$this->page = ((int) ee()->input->get('page')) ?: 1;
		$this->offset = ($this->page - 1) * $this->perpage;

		$this->base_url->addQueryStringVariables($this->params);
	}

	/**
	 * Looks through the member search string for search tokens (e.g. id:3
	 * or username:john)
	 *
	 * @param string $search_string The string to look through for tokens
	 * @return string/array String if there are no tokens within the
	 * 	string, otherwise it's an associative array with the tokens as
	 * 	the keys
	 */
	private function _check_search_tokens($search_string = '')
	{
		if (strpos($search_string, ':') !== FALSE)
		{
			$search_array = array();
			$tokens = array('id', 'member_id', 'username', 'screen_name', 'email');

			foreach ($tokens as $token)
			{
				// This regular expression looks for a token immediately
				// followed by one of three things:
				// - a value within double quotes
				// - a value within single quotes
				// - a value without spaces

				if (preg_match('/'.$token.'\:((?:"(.*?)")|(?:\'(.*?)\')|(?:[^\s:]+?))(?:\s|$)/i', $search_string, $matches))
				{
					// The last item within matches is what we want
					$search_array[$token] = end($matches);
				}
			}

			// If both ID and Member_ID are set, unset ID
			if (isset($search_array['id']) AND isset($search_array['member_id']))
			{
				unset($search_array['id']);
			}

			return $search_array;
		}

		return $search_string;
	}

	/**
	 * Generate post re-assignment view if applicable
	 *
	 * @access public
	 * @return void
	 */
	public function confirm()
	{
		$vars = array();
		$selected = ee()->input->post('selection');
		$vars['selected'] = $selected;

		// Do the users being deleted have entries assigned to them?
		// If so, fetch the member names for reassigment
		if (ee()->member_model->count_member_entries($selected) > 0)
		{
			$group_ids = ee()->member_model->get_members_group_ids($selected);

			$vars['heirs'] = $this->heirFilter($group_ids, $selected);

			$vars['fields'] = array(
				'heir' => array(
					'type' => 'radio',
					'choices' => $vars['heirs'],
					'filter_url' => ee('CP/URL')->make(
						'members/heir-filter',
						[
							'group_ids' => implode('|', $group_ids),
							'selected' => implode('|', $selected)
						]
					)->compile(),
					'no_results' => ['text' => 'no_members_found'],
					'margin_top' => TRUE,
					'margin_left' => TRUE
				)
			);
		}

		ee()->view->cp_page_title = lang('delete_member');
		ee()->cp->render('members/delete_confirm', $vars);
	}

	/**
	 * AJAX endpoint for filtering heir selection
	 *
	 * @param array $group_ids Group IDs to search
	 * @param array $selected Members to exclude from search
	 * @return array List of members normalized for SelectField
	 */
	public function heirFilter($group_ids = NULL, $selected = NULL)
	{
		$search_term = ee('Request')->get('search') ?: '';
		$group_ids = $group_ids ?: explode('|', ee('Request')->get('group_ids'));
		$selected = $selected ?: explode('|', ee('Request')->get('selected'));

		$members = ee('Model')->get('Member')
			->fields('screen_name', 'username')
			->search(
				['screen_name', 'username', 'email', 'member_id'], $search_term
			)
			->filter('group_id', 'IN', $group_ids)
			->filter('member_id', 'NOT IN', $selected)
			->order('screen_name')
			->limit(100)
			->all();

		$heirs = [];
		foreach($members as $heir)
		{
			$name = ($heir->screen_name != '') ? 'screen_name' : 'username';
			$heirs[$heir->getId()] = $heir->$name;
		}

		return ee('View/Helpers')->normalizedChoices($heirs);
	}

	/**
	 * Member Delete
	 *
	 * Delete Members
	 *
	 * @return	mixed
	 */
	public function delete()
	{
		$member_ids = ee()->input->post('selection', TRUE);
		$session = ee('Model')->get('Session', ee()->session->userdata('session_id'))
			->filter('member_id', ee()->session->userdata('member_id'))
			->first();

		if ( ! $session ||
			! ee()->cp->allowed_group('can_delete_members') ||
			! $member_ids)
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! $session->isWithinAuthTimeout())
		{
			$validator = ee('Validation')->make();
			$validator->setRules(array(
				'verify_password'  => 'required|authenticated'
			));
			$password_confirm = $validator->validate($_POST);

			if ($password_confirm->failed())
			{
				ee('CP/Alert')->makeInline('view-members')
					->asIssue()
					->withTitle(lang('member_delete_problem'))
					->addToBody(lang('invalid_password'))
					->defer();

				return ee()->functions->redirect($this->base_url);
			}

			$session->resetAuthTimeout();
		}

		if ( ! is_array($member_ids))
		{
			$member_ids = array($member_ids);
		}

		if (in_array(ee()->session->userdata['member_id'], $member_ids))
		{
			show_error(lang('can_not_delete_self'));
		}

		// Check to see if they're deleting super admins
		$this->_super_admin_delete_check($member_ids);

		// If we got this far we're clear to delete the members
		// First, assign an heir if we are to do so
		if (ee()->input->post('heir_action') == 'assign')
		{
			if ( ! ee()->input->post('heir'))
			{
				show_error(lang('heir_required'));
			}

			$heir = ee('Model')->get('Member', ee()->input->post('heir'))->first();

			ee()->db->where_in('author_id', $member_ids);
			ee()->db->update('entry_versioning', array('author_id' => $heir->getId()));

			ee()->db->where_in('author_id', $member_ids);
			ee()->db->update('channel_titles', array('author_id' => $heir->getId()));

			ee()->db->where_in('uploaded_by_member_id', $member_ids);
			ee()->db->update('files', array('uploaded_by_member_id' => $heir->getId()));

			ee()->db->where_in('modified_by_member_id', $member_ids);
			ee()->db->update('files', array('modified_by_member_id' => $heir->getId()));

			$heir->updateAuthorStats();
		}

		// If we got this far we're clear to delete the members
		ee('Model')->get('Member')->with('MemberGroup')->filter('member_id', 'IN', $member_ids)->delete();

		// Send member deletion notifications
		$this->_member_delete_notifications($member_ids);

		/* -------------------------------------------
		/* 'cp_members_member_delete_end' hook.
		/*  - Additional processing when a member is deleted through the CP
		*/
			ee()->extensions->call('cp_members_member_delete_end', $member_ids);
			if (ee()->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		$cp_message = (count($member_ids) == 1) ?
			lang('member_deleted') : lang('members_deleted');

		ee('CP/Alert')->makeInline('view-members')
			->asSuccess()
			->withTitle(lang('member_delete_success'))
			->addToBody($cp_message)
			->defer();

		ee()->functions->redirect($this->base_url);
	}

	/**
	 * Member Anonymize
	 */
	public function anonymize()
	{
		$member_id = ee()->input->post('selection', TRUE);
		$member = ee('Model')->get('Member')
			->filter('member_id', $member_id)
			->first();

		$session = ee('Model')->get('Session', ee()->session->userdata('session_id'))
			->filter('member_id', ee()->session->userdata('member_id'))
			->first();

		if ( ! $session ||
			! ee()->cp->allowed_group('can_delete_members') ||
			! $member)
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$profile_url = ee('CP/URL')->make('members/profile/settings', ['id' => $member_id]);

		if ( ! $session->isWithinAuthTimeout())
		{
			$validator = ee('Validation')->make();
			$validator->setRules(array(
				'verify_password'  => 'required|authenticated'
			));
			$password_confirm = $validator->validate($_POST);

			if ($password_confirm->failed())
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('member_anonymize_problem'))
					->addToBody(lang('invalid_password'))
					->defer();

				return ee()->functions->redirect($profile_url);
			}

			$session->resetAuthTimeout();
		}

		if ($member_id == ee()->session->userdata('member_id'))
		{
			show_error(lang('can_not_delete_self'));
		}

		$this->_super_admin_delete_check($member_id);

		$member->anonymize();

		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang('member_anonymize_success'))
			->addToBody(lang('member_anonymize_success_desc'))
			->defer();

		ee()->functions->redirect($profile_url);
	}

	/**
	 * Check to see if the members being deleted are super admins. If they are
	 * we need to make sure that the deleting user is a super admin and that
	 * there is at least one more super admin remaining.
	 *
	 * @param  Array  $member_ids Array of member_ids being deleted
	 * @return void
	 */
	private function _super_admin_delete_check($member_ids)
	{
		if ( ! is_array($member_ids))
		{
			$member_ids = array($member_ids);
		}

		$super_admins = ee('Model')->get('Member')
			->filter('group_id', 1)
			->filter('member_id', 'IN', $member_ids)
			->count();

		if ($super_admins > 0)
		{
			// You must be a Super Admin to delete a Super Admin

			if (ee()->session->userdata['group_id'] != 1)
			{
				show_error(lang('must_be_superadmin_to_delete_one'));
			}

			// You can't delete the only Super Admin
			$total_super_admins = ee('Model')->get('Member')
				->filter('group_id', 1)
				->count();

			if ($super_admins >= $total_super_admins)
			{
				show_error(lang('cannot_delete_super_admin'));
			}
		}
	}

	/**
	 * Send email notifications to email addresses for the respective member
	 * group of the users being deleted
	 *
	 * @param  Array  $member_ids Array of member_ids being deleted
	 * @return void
	 */
	private function _member_delete_notifications($member_ids)
	{
		// Email notification recipients
		$group_query = ee()->db->distinct()
			->select('member_id, screen_name, email, mbr_delete_notify_emails')
			->join('member_groups', 'members.group_id = member_groups.group_id', 'left')
			->where('mbr_delete_notify_emails !=', '')
			->where_in('member_id', $member_ids)
			->get('members');

		foreach ($group_query->result() as $member)
		{
			$notify_address = $member->mbr_delete_notify_emails;

			$swap = array(
				'name'		=> $member->screen_name,
				'email'		=> $member->email,
				'site_name'	=> stripslashes(ee()->config->item('site_name'))
			);

			ee()->lang->loadfile('member');
			$email_title = ee()->functions->var_swap(
				lang('mbr_delete_notify_title'),
				$swap
			);
			$email_message = ee()->functions->var_swap(
				lang('mbr_delete_notify_message'),
				$swap
			);

			// No notification for the user themselves, if they're in the list
			if (strpos($notify_address, $member->email) !== FALSE)
			{
				$notify_address = str_replace($member->email, "", $notify_address);
			}

			// Remove multiple commas
			$notify_address = reduce_multiples($notify_address, ',', TRUE);

			if ($notify_address != '')
			{
				ee()->load->library('email');
				ee()->load->helper('text');

				foreach (explode(',', $notify_address) as $addy)
				{
					ee()->email->EE_initialize();
					ee()->email->wordwrap = FALSE;
					ee()->email->from(
						ee()->config->item('webmaster_email'),
						ee()->config->item('webmaster_name')
					);
					ee()->email->to($addy);
					ee()->email->reply_to(ee()->config->item('webmaster_email'));
					ee()->email->subject($email_title);
					ee()->email->message(entities_to_ascii($email_message));
					ee()->email->send();
				}
			}
		}
	}

	/**
	 * Set the header for the members section
	 * @param String $form_url Form URL
	 * @param String $search_button_value The text for the search button
	 */
	protected function set_view_header($form_url, $search_button_value = '')
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

		if ( ! ee()->cp->allowed_group('can_access_settings'))
		{
			unset($header['toolbar_items']);
		}

		ee()->view->header = $header;
	}
}
// END CLASS

// EOF
