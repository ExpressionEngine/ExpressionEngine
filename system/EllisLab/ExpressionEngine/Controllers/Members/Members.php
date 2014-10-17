<?php

namespace EllisLab\ExpressionEngine\Controllers\Members;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;

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

	private $base_url = 'members';
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
	}

	// --------------------------------------------------------------------

	/**
	 * MemberList
	 */
	public function index()
	{
		$base_url = new URL($this->base_url, ee()->session->session_id());

		if ( ! empty($this->group))
		{
			$members = ee()->api->get('Member')->filter('group_id', $this->group)->order('username', 'asc')->all();
		}
		else
		{
			$members = ee()->api->get('Member')->order('username', 'asc')->all();
		}

		$groups = ee()->api->get('MemberGroup')->order('group_title', 'asc')->all();
		$vars = array();
		$data = array();
		$group_ids = array();
		$member_groups = array(cp_url('members/member-list') => 'All');

		foreach ($groups as $group)
		{
			$group_ids[$group->group_id] = $group->group_title;
			$member_groups[cp_url('members/member-list/filter/' . $group->group_id)] = $group->group_title;
		}

		$vars['groups'] = array(
			'filters' => array(
				array(
					'label' => 'member group',
					'value' => empty($this->group) ? lang('all') : $group_ids[$this->group],
					'name' => '',
					'custom_value' => '',
					'placeholder' => '',
					'options' => $member_groups
				)
			)
		);

		foreach ($members as $member)
		{
			$data[] = array(
				'id'	=> $member->member_id,
				'username'	=> $member->username,
				'member_group'	=> $member->getMemberGroup()->group_title,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('members/edit/' . $member->member_id),
						'title' => strtolower(lang('edit'))
					)
				)),
				array(
					'name' => 'selection[]',
					'value' => $member->member_id
				)
			);
		}

		$table = Table::create(array('autosort' => TRUE, 'autosearch' => TRUE));
		$table->setColumns(
			array(
				'id',
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
		$table->setData($data);
		$vars['table'] = $table->viewData($base_url);

		$base_url = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$pagination = new Pagination(
				$vars['table']['limit'],
				$vars['table']['total_rows'],
				$vars['table']['page']
			);
			$vars['pagination'] = $pagination->cp_links($base_url);
		}

		// Set search results heading
		if ( ! empty($vars['table']['search']))
		{
			ee()->view->cp_heading = sprintf(
				lang('search_results_heading'),
				$vars['table']['total_rows'],
				$vars['table']['search']
			);
		}

		ee()->view->base_url = $base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('all_members');
		ee()->cp->render('members/view_members', $vars);
	}

	/**
	 * Filter the member list by the group
	 * 
	 * @param mixed $group 
	 * @access public
	 * @return void
	 */
	public function filter($group)
	{
		$this->group = $group;
		$this->base_url = 'members/filter/' . $group;
		$this->index();
	}
}
// END CLASS

/* End of file Members.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Members.php */
