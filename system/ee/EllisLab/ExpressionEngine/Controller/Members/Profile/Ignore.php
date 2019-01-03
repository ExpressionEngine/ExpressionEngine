<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

use CP_Controller;

use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * Member Profile Ignore Settings Controller
 */
class Ignore extends Profile {

	private $base_url = 'members/profile/ignore';

	public function __construct()
	{
		parent::__construct();
		ee()->load->model('member_model');
		$this->index_url = $this->base_url;
		$this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);
		$this->ignore_list = explode('|', $this->member->ignore_list);
	}

	/**
	 * Ignore index
	 */
	public function index()
	{
		$sort = ($this->config->item('memberlist_sort_order')) ? $this->config->item('memberlist_sort_order') : 'asc';
		$perpage = $this->config->item('memberlist_row_limit');
		$sort_col = ee()->input->get('sort_col') ?: 'username';
		$sort_dir = ee()->input->get('sort_dir') ?: $sort;
		$page = ee()->input->get('page') > 0 ? ee()->input->get('page') : 1;

		$sort_map = array(
			'member_id'    => 'member_id',
			'username'     => 'username',
			'member_group' => 'MemberGroup.group_title'
		);

		$table = ee('CP/Table', array(
			'sort_col' => $sort_col,
			'sort_dir' => $sort_dir,
			'limit' => $perpage
		));

		$ignored = array();
		$data = array();
		$members = ee('Model')->get('Member', $this->ignore_list)
			->with('MemberGroup')
			->order($sort_map[$sort_col], $sort_dir);

		$search = ee()->input->post('search');
		if ( ! empty($search))
		{
			// $members = $members->filter('screen_name', 'LIKE', "%$search%");
			$members = $members->search('screen_name', $search);
		}

		$members = $members->limit($perpage)
			->offset(($page - 1) * $perpage)
			->all();

		if (count($members) > 0)
		{
			foreach ($members as $member)
			{
				$attributes = array();
				$group = $member->getMemberGroup()->group_title;

				if ($group == 'Banned')
				{
					$group = "<span class='st-banned'>" . lang('banned') . "</span>";
					$attributes['class'] = 'alt banned';
				}

				$email = "<a href = '" . ee('CP/URL')->make('utilities/communicate') . "'>e-mail</a>";
				$ignored[] = array(
					'columns' => array(
						'member_id' => $member->member_id,
						'username' => "{$member->screen_name} ($email)",
						'member_group' => $group,
						array(
							'name' => 'selection[]',
							'value' => $member->member_id,
							'data'	=> array(
								'confirm' => lang('member') . ': <b>' . htmlentities($member->screen_name, ENT_QUOTES, 'UTF-8') . '</b>'
							)
						)
					),
					'attrs' => $attributes
				);
			}
		}

		$table->setColumns(
			array(
				'member_id',
				'username' => array('encode' => FALSE),
				'member_group' => array('encode' => FALSE),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);

		$table->setNoResultsText('no_blocked_members_found');
		$table->setData($ignored);

		$data['table'] = $table->viewData($this->base_url);

		// Set search results heading
		if ( ! empty($data['table']['search']))
		{
			ee()->view->cp_heading = sprintf(
				lang('search_results_heading'),
				$data['table']['total_rows'],
				$data['table']['search']
			);
		}

		if ( ! empty($data['table']['data']))
		{
			$data['pagination'] = ee('CP/Pagination', count($this->ignore_list))
				->perPage($perpage)
				->currentPage($page)
				->render($this->base_url);
		}

		$data['form_url'] = ee('CP/URL')->make('members/profile/ignore/delete', $this->query_string);

		ee()->javascript->set_global('lang.remove_confirm', lang('members') . ': <b>### ' . lang('members') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		ee()->view->base_url = $this->base_url;
		ee()->view->cp_page_title = lang('blocked_members');
		ee()->cp->render('account/ignore_list', $data);
	}

	/**
	 * Remove users from ignore list
	 *
	 * @access public
	 * @return void
	 */
	public function delete()
	{
		$selection = $this->input->post('selection');
		$ignore = implode('|', array_diff($this->ignore_list, $selection));
		$this->member->ignore_list = $ignore;
		$this->member->save();

		ee()->functions->redirect(ee('CP/URL')->make($this->index_url, $this->query_string));
	}

}
// END CLASS

// EOF
