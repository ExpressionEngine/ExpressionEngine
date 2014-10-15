<?php

namespace EllisLab\ExpressionEngine\Controllers\Members;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * ExpressionEngine CP MemberList Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class MemberList extends Members {

	/**
	 * MemberList
	 */
	public function index()
	{
		$base_url = new URL('members/member-list', ee()->session->session_id());

		$members = ee()->api->get('Member')->order('username', 'asc')->all();
		$groups = ee()->api->get('MemberGroup')->order('group_title', 'asc')->all();
		$vars = array();
		$member_groups = array();

		foreach ($groups as $group)
		{
			$member_groups[cp_url('members/member-list/' . $group->group_id)] = $group->group_title;
		}

		$vars['groups'] = array(
			'filters' => array(
				array(
					'label' => 'member group',
					'value' => 'Members',
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
}
// END CLASS

/* End of file MemberList.php */
/* Location: ./system/expressionengine/controllers/cp/Members/MemberList.php */
