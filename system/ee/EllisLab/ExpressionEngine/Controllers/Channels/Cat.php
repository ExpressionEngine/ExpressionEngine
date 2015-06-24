<?php

namespace EllisLab\ExpressionEngine\Controllers\Channels;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Controllers\Channels\AbstractChannels as AbstractChannelsController;
use EllisLab\Addons\FilePicker\FilePicker as FilePicker;

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
class Cat extends AbstractChannelsController {

	private $new_order_reference = array();

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
		$table = ee('CP/Table');
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
			cp_url('channels/cat/create')
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
			$columns = array(
				$group->getId(),
				htmlentities($group->group_name, ENT_QUOTES) . ' ('.count($group->getCategories()).')',
				array('toolbar_items' => array(
					'view' => array(
						'href' => cp_url('channels/cat/cat-list/'.$group->getId()),
						'title' => lang('view')
					),
					'edit' => array(
						'href' => cp_url('channels/cat/edit/'.$group->getId()),
						'title' => lang('edit')
					),
					'txt-only' => array(
						'href' => cp_url('channels/cat/field/'.$group->getId()),
						'title' => strtolower(lang('custom_fields')),
						'content' => strtolower(lang('fields'))
					)
				)),
				array(
					'name' => 'cat_groups[]',
					'value' => $group->getId(),
					'data'	=> array(
						'confirm' => lang('category_group') . ': <b>' . htmlentities($group->group_name, ENT_QUOTES) . '</b>'
					)
				)
			);

			$attrs = array();
			if (ee()->session->flashdata('highlight_id') == $group->getId())
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs' => $attrs,
				'columns' => $columns
			);
		}

		$table->setData($data);

		$base_url = new CP\URL('channels/cat', ee()->session->session_id());
		$vars['table'] = $table->viewData($base_url);

		$vars['pagination'] = ee('CP/Pagination', $total_rows)
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		ee()->view->cp_page_title = lang('category_groups');

		ee()->javascript->set_global('lang.remove_confirm', lang('category_groups') . ': <b>### ' . lang('category_groups') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		ee()->cp->render('channels/cat/index', $vars);
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

		ee()->functions->redirect(cp_url('channels/cat', ee()->cp->get_url_state()));
	}

	/**
	 * New category group form
	 */
	public function create()
	{
		$this->form();
	}

	/**
	 * Edit category group form
	 */
	public function edit($group_id)
	{
		$this->form($group_id);
	}

	/**
	 * Category group creation/edit form
	 *
	 * @param	int	$group_id	ID of category group to edit
	 */
	private function form($group_id = NULL)
	{
		if (is_null($group_id))
		{
			ee()->view->cp_page_title = lang('create_category_group');
			ee()->view->base_url = cp_url('channels/cat/create');
			ee()->view->save_btn_text = 'create_category_group';
			$cat_group = ee('Model')->make('CategoryGroup');
		}
		else
		{
			$cat_group = ee('Model')->get('CategoryGroup')
				->filter('group_id', $group_id)
				->first();

			if ( ! $cat_group)
			{
				show_error(lang('unauthorized_access'));
			}

			ee()->view->cp_page_title = lang('edit_category_group');
			ee()->view->base_url = cp_url('channels/cat/edit/'.$group_id);
			ee()->view->save_btn_text = 'edit_category_group';
		}

		$member_groups = ee('Model')->get('MemberGroup')
			->filter('group_id', 'NOT IN', array(1,2,3,4))
			->filter('site_id', ee()->config->item('site_id'));

		$can_edit_categories = array();
		foreach ($member_groups->filter('can_edit_categories', 'y')->all() as $member_group)
		{
			$can_edit_categories[$member_group->group_id] = $member_group->group_title;
		}

		$can_delete_categories = array();
		foreach ($member_groups->filter('can_delete_categories', 'y')->all() as $member_group)
		{
			$can_delete_categories[$member_group->group_id] = $member_group->group_title;
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'name',
					'desc' => 'group_name_desc',
					'fields' => array(
						'group_name' => array(
							'type' => 'text',
							'value' => $cat_group->group_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'html_formatting',
					'desc' => 'html_formatting_desc',
					'fields' => array(
						'field_html_formatting' => array(
							'type' => 'dropdown',
							'choices' => array(
								'all'	=> lang('allow_all_html'),
								'safe'	=> lang('allow_safe_html'),
								'none'	=> lang('convert_to_entities')
							),
							'value' => $cat_group->field_html_formatting
						)
					)
				)
			),
			'permissions' => array(
				ee('Alert')->makeInline('permissions-warn')
					->asWarning()
					->addToBody(lang('category_permissions_warning'))
					->addToBody(
						sprintf(lang('category_permissions_warning2'), '<span title="excercise caution"></span>'),
						'caution'
					)
					->cannotClose()
					->render(),
				array(
					'title' => 'edit_categories',
					'desc' => 'edit_categories_desc',
					'caution' => TRUE,
					'fields' => array(
						'can_edit_categories' => array(
							'type' => 'checkbox',
							'choices' => $can_edit_categories,
							'value' => explode('|', rtrim($cat_group->can_edit_categories, '|')),
							'no_results' => array(
								'text' => 'cat_group_no_member_groups_found',
								'link_text' => 'edit_member_groups',
								'link_href' => cp_url('members/groups')
							)
						)
					)
				),
				array(
					'title' => 'delete_categories',
					'desc' => 'delete_categories_desc',
					'caution' => TRUE,
					'fields' => array(
						'can_delete_categories' => array(
							'type' => 'checkbox',
							'choices' => $can_delete_categories,
							'value' => explode('|', rtrim($cat_group->can_edit_categories, '|')),
							'no_results' => array(
								'text' => 'cat_group_no_member_groups_found',
								'link_text' => 'edit_member_groups',
								'link_href' => cp_url('members/groups')
							)
						)
					)
				),
				array(
					'title' => 'exclude_group_form',
					'desc' => 'exclude_group_form_desc',
					'fields' => array(
						'exclude_group' => array(
							'type' => 'dropdown',
							'choices' => array(
								0 => lang('none'),
								1 => lang('channels'),
								2 => lang('files')
							),
							'value' => $cat_group->exclude_group
						)
					)
				)
			)
		);

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'group_name',
				'label' => 'lang:name',
				'rules' => 'required|strip_tags|trim|valid_xss_check|alpha_dash_space|callback_validCategoryGroupName['.$group_id.']'
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
			$group_id = $this->saveCategoryGroup($group_id);

			ee()->session->set_flashdata('highlight_id', $group_id);

			ee('Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('category_group_saved'))
				->addToBody(lang('category_group_saved_desc'))
				->defer();

			ee()->functions->redirect(cp_url('channels/cat'));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('category_group_not_saved'))
				->addToBody(lang('category_group_not_saved_desc'))
				->now();
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(cp_url('channels/cat'), lang('category_groups'));

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Custom validator for category group name to check for duplicate
	 * category group names
	 *
	 * @param	model	$name		Category group name
	 * @param	model	$group_id	Group ID for category group
	 * @return	bool	Valid category group name or not
	 */
	public function validCategoryGroupName($name, $group_id)
	{
		$cat_group = ee('Model')->get('CategoryGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_name', $name);

		if ( ! empty($group_id))
		{
			$cat_group->filter('group_id', '!=', $group_id);
		}

		if ($cat_group->all()->count() > 0)
		{
			ee()->form_validation->set_message('validCategoryGroupName', lang('duplicate_category_group_name'));
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Saves a category group
	 *
	 * @param	int $group_id ID of category group to save
	 * @return	int ID of category group saved
	 */
	private function saveCategoryGroup($group_id = NULL)
	{
		$cat_group = ee('Model')->make('CategoryGroup', $_POST);
		$cat_group->group_id = $group_id;
		$cat_group->site_id = ee()->config->item('site_id');
		$cat_group->can_edit_categories = (ee()->input->post('can_edit_categories'))
			? implode('|', $_POST['can_edit_categories']) : '';
		$cat_group->can_delete_categories = (ee()->input->post('can_delete_categories'))
			? implode('|', $_POST['can_delete_categories']) : '';

		$cat_group->save();
		return $cat_group->group_id;
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

		ee()->cp->add_js_script('plugin', 'nestable');
		ee()->cp->add_js_script('file', 'cp/v3/category_reorder');

		// Get the category tree with a single query
		ee()->load->library('datastructures/tree');
		ee()->view->categories = $cat_group->getCategoryTree(ee()->tree);

		ee()->view->base_url = $cat_group->group_name . ' &mdash; ' . lang('categories');
		ee()->view->cp_page_title = $cat_group->group_name . ' &mdash; ' . lang('categories');
		ee()->view->cat_group = $cat_group;

		ee()->javascript->set_global('lang.remove_confirm', lang('categories') . ': <b>### ' . lang('categories') . '</b>');
		ee()->cp->add_js_script('file', 'cp/v3/confirm_remove');

		$reorder_ajax_fail = ee('Alert')->makeBanner('reorder-ajax-fail')
			->asIssue()
			->canClose()
			->withTitle(lang('category_ajax_reorder_fail'))
			->addToBody(lang('category_ajax_reorder_fail_desc'));

		ee()->javascript->set_global('cat.reorder_url', cp_url('channels/cat/cat-reorder/'.$group_id));
		ee()->javascript->set_global('alert.reorder_ajax_fail', $reorder_ajax_fail->render());

		ee()->cp->set_breadcrumb(cp_url('channels/cat'), lang('category_groups'));

		ee()->cp->render('channels/cat/list');
	}

	/**
	 * AJAX end point for reordering categories on catList page
	 */
	public function catReorder($group_id)
	{
		$cat_group = ee('Model')->get('CategoryGroup')
			->filter('group_id', $group_id)
			->first();

		$cat_group->sort_order = 'c';
		$cat_group->save();

		$new_order = ee()->input->post('order');

		if ( ! AJAX_REQUEST OR ! $cat_group OR empty($new_order))
		{
			show_error(lang('unauthorized_access'));
		}

		// Create a flattened array based on the JSON response
		// from Nestable; we basically want to mirror the data
		// format we have in the database for easy comparison
		$order = 1;
		foreach ($new_order as $category)
		{
			$this->flattenCategoryTree($category, 0, $order);
			$order++;
		}

		// Compare all categories to what we got back from
		// Nestable to see if any parent IDs or orderings
		// changed; if so, ONLY update those categories
		foreach ($cat_group->getCategories() as $category)
		{
			$new_category = $this->new_order_reference[$category->cat_id];

			if ($category->parent_id != $new_category['parent_id'] OR
				$category->cat_order != $new_category['order'])
			{
				$category->parent_id = $new_category['parent_id'];
				$category->cat_order = $new_category['order'];
				$category->save();
			}
		}

		ee()->output->send_ajax_response(NULL);
		exit;
	}

	/**
	 * Recursive function to flatten the category tree we get back
	 * from the Nestable jQuery plugin
	 */
	private function flattenCategoryTree($category, $parent_id, $order)
	{
		$this->new_order_reference[$category['id']] = array(
			'parent_id' => $parent_id,
			'order' => $order
		);

		// Has children? Flatten them to same array
		if (isset($category['children']))
		{
			$order = 1;
			foreach ($category['children'] as $child)
			{
				$this->flattenCategoryTree($child, $category['id'], $order);
				$order++;
			}
		}
	}

	/**
	 * Category removal handler
	 */
	public function removeCat()
	{
		$cat_ids = ee()->input->post('categories');

		if ( ! empty($cat_ids) && ee()->input->post('bulk_action') == 'remove')
		{
			// Filter out junk
			$cat_ids = array_filter($cat_ids, 'is_numeric');

			if ( ! empty($cat_ids))
			{
				ee('Model')->get('Category')
					->filter('cat_id', 'IN', $cat_ids)
					->delete();

				ee('Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('categories_removed'))
					->addToBody(sprintf(lang('categories_removed_desc'), count($cat_ids)))
					->defer();
			}
		}
		else
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->functions->redirect(
			cp_url('channels/cat/cat-list/'.ee()->input->post('cat_group_id'))
		);
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

		if (is_null($category_id))
		{
			ee()->view->cp_page_title = lang('create_category');
			ee()->view->base_url = cp_url('channels/cat/create-cat/'.$group_id);
			ee()->view->save_btn_text = 'create_category';

			$category = ee('Model')->make('Category');
			$category->setCategoryGroup($cat_group);

			// Only auto-complete channel short name for new channels
			ee()->cp->add_js_script('plugin', 'ee_url_title');
			ee()->javascript->output('
				$("input[name=cat_name]").bind("keyup keydown", function() {
					$(this).ee_url_title("input[name=cat_url_title]");
				});
			');
		}
		else
		{
			$category = ee('Model')->get('Category')->filter('cat_id', (int) $category_id)->first();

			if ( ! $category)
			{
				show_error(lang('unauthorized_access'));
			}

			ee()->view->cp_page_title = lang('edit_category');
			ee()->view->base_url = cp_url('channels/cat/edit-cat/'.$group_id.'/'.$category_id);
			ee()->view->save_btn_text = 'edit_category';
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

		ee()->load->library('file_field');

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
					'title' => 'url_title_lc',
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
						'cat_image_select' => array(
							'type' => 'radio',
							'choices' => array(
								'none' => 'cat_image_none',
								'choose' => 'cat_image_choose'
							),
							'value' => 'none'
						),
						'cat_image' => array(
							'type' => 'image',
							'id' => 'cat_image',
							'image' => ee()->file_field->parse_string($category->cat_image),
							'value' => $category->cat_image
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

		foreach ($category->getDisplay()->getFields() as $field)
		{
			$vars['sections']['custom_fields'][] = array(
				'title' => $field->getLabel(),
				'desc' => '',
				'fields' => array(
					$field->getName() => array(
						'type' => 'html',
						'content' => $field->getForm(),
						'required' => $field->isRequired(),
					)
				)
			);
		}

		if ( ! empty($_POST))
		{
			$category->set($_POST);
			$result = $category->validate();

			if (AJAX_REQUEST)
			{
				$field = ee()->input->post('ee_fv_field');

				if ($result->hasErrors($field))
				{
					ee()->output->send_ajax_response(array('error' => $result->renderError($field)));
				}
				else
				{
					ee()->output->send_ajax_response('success');
				}
				exit;
			}

			if ($result->isValid())
			{
				$category_id = $category->save()->getId();

				ee('Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('category_saved'))
					->addToBody(lang('category_saved_desc'))
					->defer();

				ee()->functions->redirect(cp_url('channels/cat/edit-cat/'.$group_id.'/'.$category_id));
			}
			else
			{
				ee()->load->library('form_validation');
				ee()->form_validation->_error_array = $result->renderErrors();
				ee('Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('category_not_saved'))
					->addToBody(lang('category_not_saved_desc'))
					->now();
			}
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text_working = 'btn_saving';

		$filepicker = new FilePicker();
		$filepicker->inject(ee()->view);
		ee()->cp->add_js_script('file', 'cp/channel/category_edit');
		ee()->javascript->set_global(
			'category_edit.filepicker_url',
			cp_url($filepicker->controller, array('directory' => 'all', 'type' => 'img'))
		);

		ee()->cp->set_breadcrumb(cp_url('channels/cat'), lang('category_groups'));
		ee()->cp->set_breadcrumb(cp_url('channels/cat/cat-list/'.$cat_group->group_id), $cat_group->group_name . ' &mdash; ' . lang('categories'));

		ee()->cp->render('settings/form', $vars);
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

		$table = ee('CP/Table', array(
			'reorder' => TRUE,
			'sortable' => FALSE
		));
		$table->setColumns(
			array(
				'col_id',
				'label',
				'short_name_col',
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
			cp_url('channels/cat/create-field/'.$group_id)
		);

		$sort_map = array(
			'col_id' => 'field_id',
			'label' => 'field_label',
			'short_name' => 'field_name',
			'type' => 'field_type'
		);

		$cat_fields = $cat_group->getCategoryFields()->sortBy('field_order');

		$type_map = array(
			'text' => lang('text_input'),
			'textarea' => lang('textarea'),
			'select' => lang('select_dropdown'),
		);

		$data = array();
		foreach ($cat_fields as $field)
		{
			$data[] = array(
				$field->getId().form_hidden('order[]', $field->getId()),
				$field->field_label,
				'<var>'.LD.$field->field_name.RD.'</var>',
				strtolower($type_map[$field->field_type]),
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('channels/cat/edit-field/'.$group_id.'/'.$field->getId()),
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'fields[]',
					'value' => $field->getId(),
					'data'	=> array(
						'confirm' => lang('category_field') . ': <b>' . htmlentities($field->field_label, ENT_QUOTES) . '</b>'
					)
				)
			);
		}

		$table->setData($data);

		$base_url = new CP\URL('channels/cat/field/'.$group_id, ee()->session->session_id());
		$vars['table'] = $table->viewData($base_url);
		$vars['group_id'] = $group_id;

		ee()->cp->set_breadcrumb(cp_url('channels/cat'), lang('category_groups'));
		ee()->view->cp_page_title = lang('category_fields') . ' ' . lang('for') . ' ' . $cat_group->group_name;

		ee()->javascript->set_global('lang.remove_confirm', lang('category_fields') . ': <b>### ' . lang('category_fields') . '</b>');
		ee()->cp->add_js_script('file', 'cp/v3/confirm_remove');
		ee()->cp->add_js_script('file', 'cp/sort_helper');
		ee()->cp->add_js_script('plugin', 'ee_table_reorder');
		ee()->cp->add_js_script('file', 'cp/v3/cat_field_reorder');

		$reorder_ajax_fail = ee('Alert')->makeBanner('reorder-ajax-fail')
			->asIssue()
			->canClose()
			->withTitle(lang('cat_field_ajax_reorder_fail'))
			->addToBody(lang('cat_field_ajax_reorder_fail_desc'));

		ee()->javascript->set_global('cat_fields.reorder_url', cp_url('channels/cat/cat-field-reorder/'.$group_id));
		ee()->javascript->set_global('alert.reorder_ajax_fail', $reorder_ajax_fail->render());

		ee()->cp->render('channels/cat/field', $vars);
	}

	/**
	 * AJAX end point for reordering category fields on fields listing page
	 */
	public function catFieldReorder($group_id)
	{
		$cat_group = ee('Model')->get('CategoryGroup')
			->filter('group_id', $group_id)
			->first();

		// Parse out the serialized inputs sent by the JavaScript
		$new_order = array();
		parse_str(ee()->input->post('order'), $new_order);

		if ( ! AJAX_REQUEST OR ! $cat_group OR empty($new_order['order']))
		{
			show_error(lang('unauthorized_access'));
		}

		$fields = $cat_group->getCategoryFields()->indexBy('field_id');

		$order = 1;
		foreach ($new_order['order'] as $field_id)
		{
			// Only update category fields orders that have changed
			if (isset($fields[$field_id]) && $fields[$field_id]->field_order != $order)
			{
				$fields[$field_id]->field_order = $order;
				$fields[$field_id]->save();
			}

			$order++;
		}

		ee()->output->send_ajax_response(NULL);
		exit;
	}

	/**
	 * Remove channels handler
	 */
	public function removeField()
	{
		$field_ids = ee()->input->post('fields');

		if ( ! empty($field_ids) && ee()->input->post('bulk_action') == 'remove')
		{
			// Filter out junk
			$field_ids = array_filter($field_ids, 'is_numeric');

			if ( ! empty($field_ids))
			{
				ee('Model')->get('CategoryField')
					->filter('field_id', 'IN', $field_ids)
					->delete();

				ee('Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('category_fields_removed'))
					->addToBody(sprintf(lang('category_fields_removed_desc'), count($field_ids)))
					->defer();
			}
		}
		else
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->functions->redirect(
			cp_url('channels/cat/field/'.ee()->input->post('group_id'), ee()->cp->get_url_state())
		);
	}

	/**
	 * Category field create
	 *
	 * @param	int	$group_id		ID of category group field is to be in
	 */
	public function createField($group_id)
	{
		$this->categoryFieldForm($group_id);
	}

	/**
	 * Category field edit
	 *
	 * @param	int	$group_id	ID of category group field is in
	 * @param	int	$field_id	ID of Field to edit
	 */
	public function editField($group_id, $field_id)
	{
		$this->categoryFieldForm($group_id, $field_id);
	}

	/**
	 * Category field creation/edit form
	 *
	 * @param	int	$group_id	ID of category group field is (to be) in
	 * @param	int	$field_id	ID of field to edit
	 */
	private function categoryFieldForm($group_id, $field_id = NULL)
	{
		if (empty($group_id) OR ! is_numeric($group_id))
		{
			show_error(lang('unauthorized_access'));
		}

		$cat_group = ee('Model')->get('CategoryGroup', $group_id)->first();

		if ($field_id)
		{
			$cat_field = ee('Model')->get('CategoryField')
				->filter('field_id', (int) $field_id)
				->first();

			ee()->view->save_btn_text = 'btn_edit_field';
			ee()->view->cp_page_title = lang('edit_category_field');
			ee()->view->base_url = cp_url('channels/cat/edit-field/'.$group_id.'/'.$field_id);
		}
		else
		{
			// Only auto-complete field short name for new fields
			ee()->cp->add_js_script('plugin', 'ee_url_title');
			ee()->javascript->output('
				$("input[name=field_label]").bind("keyup keydown", function() {
					$(this).ee_url_title("input[name=field_name]");
				});
			');

			$cat_field = ee('Model')->make('CategoryField');
			$cat_field->setCategoryGroup($cat_group);
			$cat_field->field_type = 'text';

			ee()->view->save_btn_text = 'btn_create_field';
			ee()->view->cp_page_title = lang('create_category_field');
			ee()->view->base_url = cp_url('channels/cat/create-field/'.$group_id);
		}

		if ( ! $cat_field)
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('admin_content');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'type',
					'desc' => '',
					'fields' => array(
						'field_type' => array(
							'type' => 'dropdown',
							'choices' => array(
								'text'     => lang('text_input'),
								'textarea' => lang('textarea'),
								'select'   => lang('select_dropdown')
							),
							'group_toggle' => array(
								'text' => 'text',
								'textarea' => 'textarea',
								'select' => 'select'
							),
							'value' => $cat_field->field_type
						)
					)
				),
				array(
					'title' => 'label',
					'desc' => 'cat_field_label_desc',
					'fields' => array(
						'field_label' => array(
							'type' => 'text',
							'value' => $cat_field->field_label,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'short_name',
					'desc' => 'cat_field_short_name_desc',
					'fields' => array(
						'field_name' => array(
							'type' => 'text',
							'value' => $cat_field->field_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'require_field',
					'desc' => 'cat_require_field_desc',
					'fields' => array(
						'field_required' => array(
							'type' => 'yes_no',
							'value' => $cat_field->field_required
						)
					)
				)
			),
			'field_options_'.$cat_field->field_type => array(
				'label' => 'field_options',
				'group' => $cat_field->field_type,
				'settings' => $cat_field->getSettingsForm()
			)
		);

		// These are currently the only fieldtypes we allow; get their settings forms
		foreach (array('text', 'textarea', 'select') as $fieldtype)
		{
			if ($cat_field->field_type != $fieldtype)
			{
				$dummy_field = ee('Model')->make('CategoryField');
				$dummy_field->setCategoryGroup($cat_group);
				$dummy_field->field_type = $fieldtype;
				$vars['sections']['field_options_'.$fieldtype] = array(
					'label' => 'field_options',
					'group' => $fieldtype,
					'settings' => $dummy_field->getSettingsForm()
				);
			}
		}

		if ( ! empty($_POST))
		{
			$cat_field->set($_POST);
			$cat_field->field_default_fmt = isset($_POST['field_fmt']) ? $_POST['field_fmt'] : NULL;
			$result = $cat_field->validate();

			if (AJAX_REQUEST)
			{
				$field = ee()->input->post('ee_fv_field');

				if ($result->hasErrors($field))
				{
					ee()->output->send_ajax_response(array('error' => $result->renderError($field)));
				}
				else
				{
					ee()->output->send_ajax_response('success');
				}
				exit;
			}

			if ($result->isValid())
			{
				$field_id = $cat_field->save()->getId();

				ee('Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('category_field_saved'))
					->addToBody(lang('category_field_saved_desc'))
					->defer();

				ee()->functions->redirect(cp_url('channels/cat/edit-field/' . $group_id . '/' . $field_id));
			}
			else
			{
				ee()->load->library('form_validation');
				ee()->form_validation->_error_array = $result->renderErrors();
				ee('Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('category_field_not_saved'))
					->addToBody(lang('category_field_not_saved_desc'))
					->now();
			}
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(cp_url('channels/cat'), lang('category_groups'));
		ee()->cp->set_breadcrumb(cp_url('channels/cat/field/'.$group_id), lang('category_fields'));

		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/form_group'),
		));

		ee()->cp->render('settings/form', $vars);
	}
}
// EOF
