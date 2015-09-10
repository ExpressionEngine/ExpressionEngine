<?php

namespace EllisLab\ExpressionEngine\Controller\Members;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Library\CP\Table;

use EllisLab\ExpressionEngine\Service\CP\Filter\Filter;
use EllisLab\ExpressionEngine\Service\CP\Filter\FilterRunner;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class Members extends CP_Controller {

	private $base_url;
	private $group;
	private $form;
	private $filter = TRUE;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		$this->perpage = $this->config->item('memberlist_row_limit');

		if ( ! $this->cp->allowed_group('can_access_members'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('members');
		ee()->load->model('member_model');
		ee()->load->library('form_validation');

		$this->base_url = ee('CP/URL', 'members');
		$this->set_view_header($this->base_url);
	}

	protected function generateSidebar($active = NULL)
	{
		$sidebar = ee('CP/Sidebar')->make();

		$header = $sidebar->addHeader(lang('all_members'), ee('CP/URL', 'members')->compile())
			->withButton(lang('new'), ee('CP/URL', 'members/create'));
		$list = $header->addBasicList();

		if ($active == 'all_members')
		{
			$header->isActive();
		}

		$pending = $list->addItem(lang('pending_activation'), ee('CP/URL', 'members', array('group' => 4))->compile());

		if ($active == 'pending')
		{
			$pending->isActive();
		}

		$list->addItem(lang('manage_bans'), ee('CP/URL', 'members/bans'));

		$header = $sidebar->addHeader(lang('member_groups'), ee('CP/URL', 'members/groups'))
			->withButton(lang('new'), ee('CP/URL', 'members/groups/create'));

		$item = $header->addBasicList()
			->addItem(lang('custom_member_fields'), ee('CP/URL', 'members/fields'));

		if ($active == 'fields')
		{
			$item->isActive();
		}

		if ($active == 'groups')
		{
			$header->isActive();
		}
	}

	/**
	 * MemberList
	 */
	public function index()
	{
		if ( ! ($member_name = $this->input->post('search')) &&
			 ! ($member_name = $this->input->get('search')))
		{
			$member_name = '';
		}

		// Get order by and sort preferences for our initial state
		$order_by = ($this->config->item('memberlist_order_by')) ?
			$this->config->item('memberlist_order_by') : 'member_id';
		$sort = ($this->config->item('memberlist_sort_order')) ?
			$this->config->item('memberlist_sort_order') : 'asc';

		// Fix for an issue where users may have 'total_posts' saved
		// in their site settings for sorting members; but the actual
		// column should be total_forum_posts, so we need to correct
		// it until member preferences can be saved again with the
		// right value
		if ($order_by == 'total_posts')
		{
			$order_by = 'total_forum_posts';
		}

		$perpage = $this->config->item('memberlist_row_limit');
		$sort_col = ee()->input->get('sort_col') ?: $order_by;
		$sort_dir = ee()->input->get('sort_dir') ?: $sort;
		$page = ee()->input->get('page') > 0 ? ee()->input->get('page') : 1;

		// Add the group filter
		if ($this->filter === TRUE)
		{
			$this->filter();
		}

		$table = ee('CP/Table', array(
			'sort_col' => $sort_col,
			'sort_dir' => $sort_dir,
			'limit' => $perpage
		));

		$state = array(
			'sort'	=> array($sort_col => $sort_dir),
			'offset' => ! empty($page) ? ($page - 1) * $perpage : 0
		);

		$params = array(
			'member_name' => $member_name,
			'perpage'	=> $perpage
		);

		$data = $this->_member_search($state, $params);

		$table->setColumns(
			array(
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
				),
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);

		$table->setNoResultsText('no_search_results');
		$table->setData($data['rows']);
		$data['table'] = $table->viewData($this->base_url);
		$data['form_url'] = ee('CP/URL', 'members/delete');
		$data['form'] = $this->form;

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
			'file' => array('cp/confirm_remove'),
		));

		switch ($this->group)
		{
			case 2: $active = 'ban'; break;
			case 4: $active = 'pending'; break;
			default: $active = 'all_members'; break;
		}
		$this->generateSidebar($active);

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = ee()->view->cp_page_title ?: lang('all_members');
		ee()->cp->render('members/view_members', $data);
	}

	public function bans()
	{
		$this->base_url = ee('CP/URL', 'members/bans');
		$this->group = 2;
		$this->filter = FALSE;

		$banned_ips	= $this->config->item('banned_ips');
		$banned_emails  = $this->config->item('banned_emails');
		$banned_usernames = $this->config->item('banned_usernames');
		$banned_screen_names = $this->config->item('banned_screen_names');
		$ban_action = $this->config->item('ban_action');
		$ban_message = $this->config->item('ban_message');
		$ban_destination = $this->config->item('ban_destination');

		$ips		= '';
		$emails  	= '';
		$users  	= '';
		$screens	= '';

		if ($banned_ips != '')
		{
			foreach (explode('|', $banned_ips) as $val)
			{
				$ips .= $val.NL;
			}
		}

		if ($banned_emails != '')
		{
			foreach (explode('|', $banned_emails) as $val)
			{
				$emails .= $val.NL;
			}
		}

		if ($banned_usernames != '')
		{
			foreach (explode('|', $banned_usernames) as $val)
			{
				$users .= $val.NL;
			}
		}

		if ($banned_screen_names != '')
		{
			foreach (explode('|', $banned_screen_names) as $val)
			{
				$screens .= $val.NL;
			}
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'ip_address_banning',
					'desc' => 'ip_banning_instructions',
					'fields' => array(
						'banned_ips' => array(
							'type' => 'textarea',
							'value' => $ips
						)
					)
				),
				array(
					'title' => 'email_address_banning',
					'desc' => 'email_banning_instructions',
					'fields' => array(
						'banned_emails' => array(
							'type' => 'textarea',
							'value' => $emails
						)
					)
				),
				array(
					'title' => 'username_banning',
					'desc' => 'username_banning_instructions',
					'fields' => array(
						'banned_usernames' => array(
							'type' => 'textarea',
							'value' => $users
						)
					)
				),
				array(
					'title' => 'screen_name_banning',
					'desc' => 'screen_name_banning_instructions',
					'fields' => array(
						'banned_screen_names' => array(
							'type' => 'textarea',
							'value' => $screens
						)
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'banned_username',
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
			$sections = $vars['sections'][0];
			$data = array();

			foreach ($sections as $section)
			{
				foreach ($section['fields'] as $field => $options)
				{
					$val = ee()->input->post($field);
					$val = implode('|', explode(NL, $val));
					$data[$field] = $val;
				}
			}

			ee()->config->update_site_prefs($data);

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

		ee()->view->cp_page_title = lang('banned_members');
		$this->form = $vars;
		$this->form['cp_page_title'] = lang('user_banning');
		$this->form['ajax_validate'] = TRUE;
		$this->form['save_btn_text'] = sprintf(lang('btn_save'), lang('settings'));
		$this->form['save_btn_text_working'] = 'btn_saving';

		$this->index();
	}

	// ----------------------------------------------------------------

	/**
	 * member search
	 *
	 * @return void
	 */
	private function _member_search($state, $params)
	{
		$search_value = $params['member_name'];
		$group_id = $this->group ?: '';
		$column_filter = ($this->input->get_post('column_filter')) ? $this->input->get_post('column_filter') : 'all';

		// Check for search tokens within the search_value
		$search_value = $this->_check_search_tokens($search_value);

		$perpage = $this->input->get_post('perpage');
		$perpage = $perpage ? $perpage : $params['perpage'];

		if (key($state['sort']) == 'member_group')
		{
			$sort = array('group_id' => array_pop($state['sort']));
		}
		else
		{
			$sort = $state['sort'];
		}

		$members = $this->member_model->get_members($group_id, $perpage, $state['offset'], $search_value, $sort, $column_filter);
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
			$attributes = array();
			$edit_link = ee('CP/URL', 'members/profile/', array('id' => $member['member_id']));
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
					$attributes['class'] = 'alt banned';
					break;
				case 'Pending':
					$group = "<span class='st-pending'>" . lang('pending') . "</span>";
					$attributes['class'] = 'alt pending';
					$toolbar['toolbar_items']['approve'] = array(
						'href' => ee('CP/URL', 'members/approve/', array('id' => $member['member_id'])),
						'title' => strtolower(lang('approve'))
					);
					break;
				default:
					$group = $groups[$member['group_id']];
			}

			if (ee()->session->flashdata('highlight_id') == $member['member_id'])
			{
				$attributes['class'] = 'selected';
			}

			$email = "<a href = '" . ee('CP/URL', 'utilities/communicate/member/' . $member['member_id']) . "'>".$member['email']."</a>";
			$username_display = "<a href = '" . $edit_link . "'>". $member['username']."</a>";
			$username_display .= '<br><span class="meta-info">&mdash; '.$email.'</span>';
			$last_visit = ($member['last_visit']) ? ee()->localize->human_time($member['last_visit']) : '--';
			$rows[] = array(
				'columns' => array(
					'id' => $member['member_id'],
					'username' => $username_display,
					'<span class="meta-info">
						<b>'.lang('joined').'</b>: '.ee()->localize->format_date(ee()->session->userdata('date_format', ee()->config->item('date_format')), $member['join_date']).'<br>
						<b>'.lang('last_visit').'</b>: '.$last_visit.'
					</span>',
					'member_group' => $group,
					$toolbar,
					array(
						'name' => 'selection[]',
						'value' => $member['member_id'],
						'data'	=> array(
							'confirm' => lang('member') . ': <b>' . htmlentities($member['screen_name'], ENT_QUOTES) . '</b>'
						)
					)
				),
				'attrs' => $attributes
			);
		}

		return array(
			'rows' => $rows,
			'per_page' => $perpage,
			'total_rows' => $this->member_model->count_members($group_id, $search_value, $column_filter),
			'member_name' => $params['member_name'],
			'member_groups' => $member_groups
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Sets up the display filters
	 *
	 * @param int
	 * @return void
	 */
	private function filter()
	{
		$groups = ee('Model')->get('MemberGroup')->order('group_title', 'asc')->all();
		$group_ids = array();

		foreach ($groups as $group)
		{
			$group_ids[$group->group_id] = $group->group_title;
		}

		$options = $group_ids;
		$options['all'] = lang('all');

		$group = ee('CP/Filter')->make('group', 'member_group', $options);
		$group->setPlaceholder(lang('all'));
		$group->disableCustomValue();

		$filters = ee('CP/Filter')->add($group);

		ee()->view->filters = $filters->render($this->base_url);
		$this->params = $filters->values();
		$this->group = $this->params['group'];
		$this->base_url->addQueryStringVariables($this->params);
	}

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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

			// Find Valid Member Replacements
			ee()->db->select('member_id, username, screen_name')
				->from('members')
				->where_in('group_id', $group_ids)
				->where_not_in('member_id', $selected)
				->order_by('screen_name');
			$heirs = ee()->db->get();

			foreach ($heirs->result() as $heir)
			{
				$name_to_use = ($heir->screen_name != '') ? $heir->screen_name : $heir->username;
				$vars['heirs'][$heir->member_id] = $name_to_use;
			}
		}

		ee()->view->cp_page_title = lang('delete_member');
		ee()->cp->render('members/delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Member Delete
	 *
	 * Delete Members
	 *
	 * @return	mixed
	 */
	public function delete()
	{
		// Verify the member is allowed to delete
		if ( ! ee()->cp->allowed_group('can_access_members')
			OR ! ee()->cp->allowed_group('can_delete_members'))
		{
			show_error(lang('unauthorized_access'));
		}

		//  Fetch member ID numbers and build the query
		$member_ids = ee()->input->post('selection', TRUE);

		// Check to see if they're deleting super admins
		$this->_super_admin_delete_check($member_ids);

		// If we got this far we're clear to delete the members
		ee()->load->model('member_model');
		$heir = (ee()->input->post('heir_action') == 'assign') ?
			ee()->input->post('heir') : NULL;
		ee()->member_model->delete_member($member_ids, $heir);

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

		// Update
		ee()->stats->update_member_stats();

		$cp_message = (count($member_ids) == 1) ?
			lang('member_deleted') : lang('members_deleted');

		ee('CP/Alert')->makeInline('view-members')
			->asSuccess()
			->withTitle(lang('member_delete_success'))
			->addToBody($cp_message)
			->defer();

		ee()->functions->redirect($this->base_url);
	}

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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
		$group_query = ee()->db->distinct('member_id')
			->select('screen_name, email, mbr_delete_notify_emails')
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

	// -------------------------------------------------------------------------

	/**
	 * Set the header for the members section
	 * @param String $form_url Form URL
	 * @param String $search_button_value The text for the search button
	 */
	protected function set_view_header($form_url, $search_button_value = '')
	{
		$search_button_value = ($search_button_value) ?: lang('search_members_button');

		ee()->view->header = array(
			'title' => lang('member_manager'),
			'form_url' => $form_url,
			'search_button_value' => $search_button_value
		);
	}
}
// END CLASS

/* End of file Members.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controller/Members/Members.php */
