<?php

namespace EllisLab\ExpressionEngine\Controllers\Members;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Service\CP\Filter\Filter;
use EllisLab\ExpressionEngine\Service\CP\Filter\FilterRunner;

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

		// Register our menu
		ee()->menu->register_left_nav(array(
			'all_members' => cp_url('members/members-list'),
			array(
				'pending_activation' => cp_url('members/pending-activation'),
				'manage_bans' => cp_url('members/manage-bans')
			),
			'member_groups' => cp_url('members/member-groups'),
			array(
				'custom_member_fields' => cp_url('members/member-fields')
			)
		));

		$this->base_url = new URL('members', ee()->session->session_id());
	}

	// --------------------------------------------------------------------

	/**
	 * MemberList
	 */
	public function index()
	{
		// creating a member automatically fills the search box
		if ( ! ($member_name = $this->input->post('search')) &&
			 ! ($member_name = $this->input->get('search')) &&
			 ! ($member_name = $this->session->flashdata('username')))
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
		$this->filter();

		$table = Table::create(array(
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
				'username',
				'member_group',
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

		$base_url = $data['table']['base_url'];

		if ( ! empty($data['table']['data']))
		{
			$pagination = new Pagination(
				$data['per_page'],
				$data['total_rows'],
				$page
			);
			$data['pagination'] = $pagination->cp_links($base_url);
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

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('all_members');
		ee()->cp->render('members/view_members', $data);
	}

	// ----------------------------------------------------------------

	/**
	 * member search
	 *
	 * @return void
	 */
	public function _member_search($state, $params)
	{
		$search_value = $params['member_name'];
		$group_id = $this->group ?: '';
		$column_filter = ($this->input->get_post('column_filter')) ? $this->input->get_post('column_filter') : 'all';

		// Check for search tokens within the search_value
		$search_value = $this->_check_search_tokens($search_value);

		$perpage = $this->input->get_post('perpage');
		$perpage = $perpage ? $perpage : $params['perpage'];

		$members = $this->member_model->get_members($group_id, $perpage, $state['offset'], $search_value, $state['sort'], $column_filter);
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
			$toolbar = array('toolbar_items' => array(
				'edit' => array(
					'href' => cp_url('members/profile/', array('id' => $member['member_id'])),
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
						'href' => cp_url('members/approve/', array('id' => $member['member_id'])),
						'title' => strtolower(lang('approve'))
					);
					break;
				default:
					$group = $groups[$member['group_id']];
			}

			$rows[] = array(
				'columns' => array(
					'id' => $member['member_id'],
					'username' => $member['username'] . " (<a href='mailto:{$member['email']}'>e-mail</a>)",
					'member_group' => $group,
					$toolbar,
					array(
						'name' => 'selection[]',
						'value' => $member['member_id']
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
		$groups = ee()->api->get('MemberGroup')->order('group_title', 'asc')->all();
		$group_ids = array();

		foreach ($groups as $group)
		{
			$group_ids[$group->group_id] = $group->group_title;
		}

		$options = $group_ids;
		$options['all'] = lang('all');

		$group = ee('Filter')->make('group', 'member_group', $options);
		$group->setPlaceholder(lang('all'));
		$group->disableCustomValue();

		$filters = ee('Filter')->add($group);

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

}
// END CLASS

/* End of file Members.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Members/Members.php */
