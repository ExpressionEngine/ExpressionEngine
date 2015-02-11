<?php

namespace EllisLab\ExpressionEngine\Controllers\Channel;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Controllers\Channel\AbstractChannel as AbstractChannelController;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Channel Categories Controller Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Cat extends AbstractChannelController {

	/**
	 * Constructor to set permissions
	 */
	public function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_edit_categories'))
		{
			show_error(lang('unauthorized_access'));
		}
	}

	/**
	 * Categpry Groups Manager
	 */
	public function index()
	{
		$table = CP\Table::create();
		$table->setColumns(
			array(
				'col_id',
				'group_name',
				'manage' => array(
					'type'	=> CP\Table::COL_TOOLBAR
				),
				array(
					'type'	=> CP\Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText(
			'no_category_groups',
			'create_category_group',
			cp_url('channel/cat/new')
		);

		$sort_map = array(
			'col_id' => 'group_id',
			'group_name' => 'group_name'
		);

		$cat_groups = ee('Model')->get('CategoryGroup')
			->filter('site_id', ee()->config->item('site_id'));
		$total_rows = $cat_groups->all()->count();

		$cat_groups = $cat_groups->order($sort_map[$table->sort_col], $table->sort_dir)
			->limit(20)
			->offset(($table->config['page'] - 1) * 20)
			->all();

		$data = array();
		foreach ($cat_groups as $group)
		{
			$data[] = array(
				$group->group_id,
				htmlentities($group->group_name, ENT_QUOTES) . ' ('.count($group->getCategories()).')',
				array('toolbar_items' => array(
					'view' => array(
						'href' => cp_url('channel/cat/cat-list/'.$group->group_id),
						'title' => lang('upload_btn_edit')
					),
					'edit' => array(
						'href' => cp_url('channel/cat/edit/'.$group->group_id),
						'title' => lang('upload_btn_sync')
					)
				)),
				array(
					'name' => 'cat_groups[]',
					'value' => $group->group_id,
					'data'	=> array(
						'confirm' => lang('category_group') . ': <b>' . htmlentities($group->group_name, ENT_QUOTES) . '</b>'
					)
				)
			);
		}

		$table->setData($data);

		$base_url = new CP\URL('channel/cat', ee()->session->session_id());
		$vars['table'] = $table->viewData($base_url);

		$pagination = new CP\Pagination(
			$vars['table']['limit'],
			$total_rows,
			$vars['table']['page']
		);
		$vars['pagination'] = $pagination->cp_links($vars['table']['base_url']);

		ee()->view->cp_page_title = lang('category_groups');

		ee()->javascript->set_global('lang.remove_confirm', lang('category_groups') . ': <b>### ' . lang('category_groups') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		ee()->cp->render('channel/cat', $vars);
	}

	/**
	 * Remove channels handler
	 */
	public function remove()
	{
		$group_ids = ee()->input->post('cat_groups');

		if ( ! empty($group_ids) && ee()->input->post('bulk_action') == 'remove')
		{
			// Filter out junk
			$group_ids = array_filter($group_ids, 'is_numeric');

			if ( ! empty($group_ids))
			{
				ee()->load->model('category_model');

				// Do each channel individually because the old category_model only
				// accepts one channel at a time to delete
				foreach ($group_ids as $group_id)
				{
					$group = ee('Model')->get('CategoryGroup', $group_id)->first();

					ee()->category_model->delete_category_group($group_id);

					ee()->logger->log_action(lang('category_groups_removed').':'.NBS.NBS.$group->group_name);

					ee()->functions->clear_caching('all', '');
				}

				ee()->view->set_message('success', lang('category_groups_removed'), sprintf(lang('category_groups_removed_desc'), count($group_ids)), TRUE);
			}
		}
		else
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->functions->redirect(cp_url('channel/cat', ee()->cp->get_url_state()));
	}

	/**
	 * Category listing
	 */
	public function catList($group_id)
	{
		$cat_group = ee('Model')->get('CategoryGroup')
			->filter('group_id', $group_id)
			->first();

		if ( ! $cat_group)
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_categories');

		$categories = ee()->api_channel_categories->category_tree($group_id);

		$table = CP\Table::create(array(
			'reorder' => TRUE,
			'sortable' => FALSE
		));
		$table->setColumns(
			array(
				'col_id',
				'name',
				'url_title',
				'manage' => array(
					'type'	=> CP\Table::COL_TOOLBAR
				),
				array(
					'type'	=> CP\Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText(
			'no_category_groups',
			'create_category_group',
			cp_url('channel/cat/new')
		);

		$data = array();
		$parents = array();
		foreach ($categories as $category)
		{
			$parent_id = $category[6];

			// Are we under a parent we've already logged?
			if (in_array($parent_id, $parents))
			{
				// If we already have this parent ID but it's not at the
				// the end of the array, pop off items until we get to it
				while (end($parents) != $parent_id)
				{
					array_pop($parents);
				}
			}
			// Category hasn't a parent
			elseif ($parent_id === FALSE)
			{
				$parents = array();
			}
			else
			{
				$parents[] = $parent_id;
			}

			// Remove all the falses
			$parents = array_filter($parents);
			
			$data[] = array(
				'attrs'	  => array(
					'data-parent-ids' => json_encode($parents),
					'data-cat-id'  => $category[0]
				),
				'columns' => array(
					$category[0].form_hidden('cat_order[]', $category[0]),
					str_repeat('<span class="child"></span>', count($parents)).' '.htmlentities($category[1], ENT_QUOTES, 'UTF-8'),
					htmlentities($category[9], ENT_QUOTES, 'UTF-8'),
					array('toolbar_items' => array(
						'edit' => array(
							'href' => cp_url('channel/cat/cat-edit/'.$category[0]),
							'title' => lang('edit')
						)
					)),
					array(
						'name' => 'categories[]',
						'value' => $category[0],
						'data'	=> array(
							'confirm' => lang('category') . ': <b>' . htmlentities($category[1], ENT_QUOTES, 'UTF-8') . '</b>'
						)
					)
				)
			);
		}

		$table->setData($data);

		// Only load reorder JS if there's more than one category
		if (count($data) > 1)
		{
			ee()->cp->add_js_script('file', 'cp/sort_helper');
			ee()->cp->add_js_script('plugin', 'ee_table_reorder');
			ee()->cp->add_js_script('file', 'cp/v3/category_reorder');
		}

		$base_url = new CP\URL('channel/cat', ee()->session->session_id());
		$vars['table'] = $table->viewData($base_url);

		ee()->view->cp_page_title = $cat_group->group_name . ' &mdash; ' . lang('categories');

		ee()->javascript->set_global('lang.remove_confirm', lang('categories') . ': <b>### ' . lang('categories') . '</b>');
		ee()->cp->add_js_script('file', 'cp/v3/confirm_remove');

		ee()->cp->set_breadcrumb(cp_url('channel/cat'), lang('category_groups'));

		ee()->cp->render('channel/cat-list', $vars);
	}
}
// EOF