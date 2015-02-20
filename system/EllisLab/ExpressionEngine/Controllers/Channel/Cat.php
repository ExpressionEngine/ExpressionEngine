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
			cp_url('channel/cat/create')
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
						'title' => lang('view')
					),
					'edit' => array(
						'href' => cp_url('channel/cat/edit/'.$group->group_id),
						'title' => lang('edit')
					),
					'txt-only' => array(
						'href' => cp_url('channel/cat/field/'.$group->group_id),
						'title' => strtolower(lang('custom_fields')),
						'content' => strtolower(lang('fields'))
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

		ee()->cp->render('channel/cat/index', $vars);
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
							'href' => cp_url('channel/cat/edit-cat/'.$category[0]),
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
		// Only load reorder JS if there's more than one category
		if (count($data) > 1)
		{
			ee()->cp->add_js_script('plugin', 'nestable');
			ee()->cp->add_js_script('file', 'cp/v3/category_reorder');
		}

		$vars['base_url'] = cp_url('channel/cat/cat-list'.$group_id);

		ee()->view->cp_page_title = $cat_group->group_name . ' &mdash; ' . lang('categories');
		ee()->view->cat_group = $cat_group;

		ee()->javascript->set_global('lang.remove_confirm', lang('categories') . ': <b>### ' . lang('categories') . '</b>');
		ee()->cp->add_js_script('file', 'cp/v3/confirm_remove');

		ee()->cp->set_breadcrumb(cp_url('channel/cat'), lang('category_groups'));

		ee()->cp->render('channel/cat/list', $vars);
	}

	/**
	 * Category create
	 *
	 * @param	int	$group_id		ID of category group category is to be in
	 */
	public function createCat($group_id)
	{
		$this->categoryForm($group_id);
	}

	/**
	 * Category edit
	 *
	 * @param	int	$group_id		ID of category group category is in
	 * @param	int	$category_id	ID of category to edit
	 */
	public function editCat($group_id, $category_id)
	{
		$this->categoryForm($group_id, $category_id);
	}

	/**
	 * Category creation/edit form
	 *
	 * @param	int	$group_id		ID of category group category is (to be) in
	 * @param	int	$category_id	ID of category to edit
	 */
	private function categoryForm($group_id, $category_id = NULL)
	{
		if (empty($group_id) OR ! is_numeric($group_id))
		{
			show_error(lang('unauthorized_access'));
		}

		$cat_group = ee('Model')->get('CategoryGroup')
			->filter('group_id', $group_id)
			->first();

		if ( ! $cat_group)
		{
			show_error(lang('unauthorized_access'));
		}

		//  Check discrete privileges
		if (AJAX_REQUEST)
		{
			$can_edit = explode('|', rtrim($cat_group->can_edit_categories, '|'));

			if (ee()->session->userdata('group_id') != 1 AND ! in_array(ee()->session->userdata('group_id'), $can_edit))
			{
				show_error(lang('unauthorized_access'));
			}
		}

		if ($category_id)
		{
			$category = ee('Model')->get('Category')->filter('cat_id', (int) $category_id)->first();
		}
		else
		{
			$category = ee('Model')->make('Category');
		}

		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_categories');
		ee()->api_channel_categories->category_tree($group_id, $category->parent_id);

		$parent_id_options[0] = $this->lang->line('none');
		foreach(ee()->api_channel_categories->categories as $cat)
		{
			$indent = ($cat[5] != 1) ? str_repeat(NBS.NBS.NBS.NBS, $cat[5]) : '';
			$parent_id_options[$cat[0]] = $indent.$cat[1];
		}

		// New categories get URL title JS
		if (is_null($category_id))
		{
			// Only auto-complete channel short name for new channels
			ee()->cp->add_js_script('plugin', 'ee_url_title');
			ee()->javascript->output('
				$("input[name=cat_name]").bind("keyup keydown", function() {
					$(this).ee_url_title("input[name=cat_url_title]");
				});
			');

			ee()->view->cp_page_title = lang('create_category');
			ee()->view->save_btn_text = 'create_category';
			ee()->view->base_url = cp_url('channel/cat/create-cat/'.$group_id);
			$channel = ee('Model')->make('Channel');
		}
		else
		{
			ee()->view->cp_page_title = lang('edit_category');
			ee()->view->save_btn_text = 'edit_category';
			ee()->view->base_url = cp_url('channel/cat/edit-cat/'.$group_id.'/'.$category_id);
		}

		// TODO: file field stuff?

		$vars['sections'] = array(
			array(
				array(
					'title' => 'name',
					'desc' => '',
					'fields' => array(
						'cat_name' => array(
							'type' => 'text',
							'value' => $category->cat_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'url_title',
					'desc' => 'url_title_desc',
					'fields' => array(
						'cat_url_title' => array(
							'type' => 'text',
							'value' => $category->cat_url_title,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'description',
					'desc' => 'cat_description_desc',
					'fields' => array(
						'cat_description' => array(
							'type' => 'textarea',
							'value' => $category->cat_description
						)
					)
				),
				array(
					'title' => 'image',
					'desc' => 'cat_image_desc',
					'fields' => array(
						'cat_image' => array(
							'type' => 'radio',
							'value' => $category->cat_image,
							'choices' => array()
						)
					)
				),
				array(
					'title' => 'parent_category',
					'desc' => 'parent_category_desc',
					'fields' => array(
						'parent_id' => array(
							'type' => 'dropdown',
							'value' => $category->parent_id,
							'choices' => $parent_id_options
						)
					)
				)
			)
		);

		ee()->db->where('group_id', $group_id);
		ee()->db->order_by('field_order');
		$field_query = ee()->db->get('category_fields');

		ee()->db->where('cat_id', $category_id);
		$data_query = ee()->db->get('category_field_data');

		if ($field_query->num_rows() > 0)
		{
			$dq_row = $data_query->row_array();
			ee()->load->model('addons_model');
			$plugins = ee()->addons_model->get_plugin_formatting();

			$vars['custom_format_options']['none'] = 'None';
			foreach ($plugins as $k=>$v)
			{
				$vars['custom_format_options'][$k] = $v;
			}
			foreach ($field_query->result_array() as $row)
			{
				$vars['sections']['custom_fields'][] = array(
					'title' => $row['field_label'],
					'desc' => '',
					'fields' => array(
						'parent_id' => array(
							'type' => $row['field_type'],
							'value' => $category->parent_id,
							'choices' => array(),
							'required' => $row['field_required'],
							'text_direction' => ($row['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr'
						)
					)
				);
			}
		}

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'cat_name',
				'label' => 'lang:name',
				'rules' => 'required|strip_tags|trim|valid_xss_check'
			),
			array(
				'field' => 'cat_url_title',
				'label' => 'lang:url_title',
				'rules' => 'required|strip_tags|trim|valid_xss_check|alpha_dash'
			),
			array(
				'field' => 'cat_description',
				'label' => 'lang:description',
				'rules' => 'trim|valid_xss_check'
			),
			array(
				'field' => 'cat_description',
				'label' => 'lang:description',
				'rules' => 'enum[' . implode(array_keys($parent_id_options), ',') . ']'
			)
		));

		ee()->form_validation->validateNonTextInputs($vars['sections']);

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$this->saveCategory($group_id, $category_id);

			ee('Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('category_saved'))
				->addToBody(lang('category_saved_desc'))
				->defer();

			ee()->functions->redirect(cp_url('channel/cat/cat-edit/'.$group_id.'/'.$category_id));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('category_not_saved'))
				->addToBody(lang('channel_not_saved_desc'))
				->now();
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(cp_url('channel/cat'), lang('category_groups'));
		ee()->cp->set_breadcrumb(cp_url('channel/cat/cat-list/'.$cat_group->group_id), $cat_group->group_name . ' &mdash; ' . lang('categories'));

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Save routine for categories
	 *
	 * @param	int	$group_id		ID of category group category is (to be) in
	 * @param	int	$category_id	ID of category to edit
	 */
	private function saveCategory($group_id, $category_id)
	{

	}

	/**
	 * Category group custom fields listing
	 */
	public function field($group_id)
	{
		$cat_group = ee('Model')->get('CategoryGroup')
			->filter('group_id', $group_id)
			->first();

		if ( ! $cat_group)
		{
			show_error(lang('unauthorized_access'));
		}

		$table = CP\Table::create();
		$table->setColumns(
			array(
				'col_id',
				'label',
				'short_name',
				'type',
				'manage' => array(
					'type'	=> CP\Table::COL_TOOLBAR
				),
				array(
					'type'	=> CP\Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText(
			'no_category_fields',
			'create_category_field',
			cp_url('channel/cat/create-field')
		);

		$sort_map = array(
			'col_id' => 'group_id',
			'group_name' => 'group_name'
		);

		$cat_fields = ee('Model')->get('CategoryFields')
			->filter('group_id', $group_id);
		$total_rows = $cat_fields->all()->count();

		$cat_fields = $cat_fields->order($sort_map[$table->sort_col], $table->sort_dir)
			->limit(20)
			->offset(($table->config['page'] - 1) * 20)
			->all();

		$data = array();
		foreach ($cat_fields as $field)
		{
			$data[] = array(
				$field->field_id,
				$field->field_label,
				LD.$field->field_name.RD,
				$field->field_type,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('channel/cat/edit-field/'.$field->field_id),
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'fields[]',
					'value' => $field->group_id,
					'data'	=> array(
						'confirm' => lang('category_field') . ': <b>' . htmlentities($field->field_label, ENT_QUOTES) . '</b>'
					)
				)
			);
		}

		$table->setData($data);

		$base_url = new CP\URL('channel/cat/field', ee()->session->session_id());
		$vars['table'] = $table->viewData($base_url);

		$pagination = new CP\Pagination(
			$vars['table']['limit'],
			$total_rows,
			$vars['table']['page']
		);
		$vars['pagination'] = $pagination->cp_links($vars['table']['base_url']);

		ee()->cp->set_breadcrumb(cp_url('channel/cat'), lang('category_groups'));
		ee()->view->cp_page_title = lang('category_fields') . ' ' . lang('for') . ' ' . $cat_group->group_name;

		ee()->javascript->set_global('lang.remove_confirm', lang('category_fields') . ': <b>### ' . lang('category_fields') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		ee()->cp->render('channel/cat/field', $vars);
	}
}
// EOF