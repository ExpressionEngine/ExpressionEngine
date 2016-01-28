<?php

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

use EllisLab\ExpressionEngine\Library\CP\Table;

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
 * ExpressionEngine CP Member Profile Ignore Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
		$order_by = 'screen_name';
		$sort = ($this->config->item('memberlist_sort_order')) ? $this->config->item('memberlist_sort_order') : 'asc';
		$perpage = $this->config->item('memberlist_row_limit');
		$sort_col = ee()->input->get('sort_col') ?: $order_by;
		$sort_dir = ee()->input->get('sort_dir') ?: $sort;
		$page = ee()->input->get('page') > 0 ? ee()->input->get('page') : 1;

		$table = ee('CP/Table', array(
			'sort_col' => $sort_col,
			'sort_dir' => $sort_dir,
			'limit' => $perpage
		));

		$ignored = array();
		$data = array();
		$members = ee()->api->get('Member', $this->ignore_list)->order($sort_col, $sort_dir);

		if ( ! empty($search = ee()->input->post('search')))
		{
			$members = $members->filter('screen_name', 'LIKE', "%$search%");
		}

		$members = $members->limit($perpage)->offset(($page - 1) * $perpage)->all();

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
						'id' => $member->member_id,
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
				'id',
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
