<?php

namespace EllisLab\ExpressionEngine\Controller\Channels;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Controller\Channels\AbstractChannels as AbstractChannelsController;
use EllisLab\Addons\FilePicker\FilePicker as FilePicker;
use EllisLab\ExpressionEngine\Model\Content\FieldFacade as FieldFacade;

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
 * ExpressionEngine CP Channel Categories Controller Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Cat extends AbstractChannelsController {

	private $new_order_reference = array();

	/**
	 * Constructor to set permissions
	 */
	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group_any(
			'can_create_categories',
			'can_edit_categories',
			'can_delete_categories'
		))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->generateSidebar('category');
	}

	/**
	 * Category Groups Manager
	 */
	public function index()
	{
		$cat_groups = ee('Model')->get('CategoryGroup')
			->filter('site_id', ee()->config->item('site_id'));

		$total_rows = $cat_groups->count();

		$table = $this->buildTableFromCategoryGroupsQuery($cat_groups, array(), ee()->cp->allowed_group('can_delete_categories'));

		$vars['table'] = $table->viewData(ee('CP/URL')->make('channels/cat'));

		$vars['pagination'] = ee('CP/Pagination', $total_rows)
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		$vars['can_create_categories'] = ee()->cp->allowed_group('can_create_categories');
		$vars['can_delete_categories'] = ee()->cp->allowed_group('can_delete_categories');

		ee()->view->cp_page_title = lang('category_groups');

		ee()->javascript->set_global('lang.remove_confirm', lang('category_groups') . ': <b>### ' . lang('category_groups') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		ee()->cp->render('channels/cat/index', $vars);
	}

	/**
	 * Remove category groups handler
	 */
	public function remove()
	{
		if ( ! $this->cp->allowed_group('can_delete_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

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
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->functions->redirect(ee('CP/URL')->make('channels/cat', ee()->cp->get_url_state()));
	}

	/**
	 * New category group form
	 */
	public function create()
	{
		if ( ! $this->cp->allowed_group('can_create_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->form();
	}

	/**
	 * Edit category group form
	 */
	public function edit($group_id)
	{
		if ( ! $this->cp->allowed_group('can_edit_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

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
			$alert_key = 'created';
			ee()->view->cp_page_title = lang('create_category_group');
			ee()->view->base_url = ee('CP/URL')->make('channels/cat/create');
			$cat_group = ee('Model')->make('CategoryGroup');
		}
		else
		{
			$cat_group = ee('Model')->get('CategoryGroup')
				->filter('group_id', $group_id)
				->first();

			if ( ! $cat_group)
			{
				show_error(lang('unauthorized_access'), 403);
			}

			$alert_key = 'updated';
			ee()->view->cp_page_title = lang('edit_category_group');
			ee()->view->base_url = ee('CP/URL')->make('channels/cat/edit/'.$group_id);
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
					'fields' => array(
						'field_html_formatting' => array(
							'type' => 'select',
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
				ee('CP/Alert')->makeInline('permissions-warn')
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
								'link_href' => ee('CP/URL')->make('members/groups')
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
								'link_href' => ee('CP/URL')->make('members/groups')
							)
						)
					)
				),
				array(
					'title' => 'exclude_group_form',
					'desc' => 'exclude_group_form_desc',
					'fields' => array(
						'exclude_group' => array(
							'type' => 'radio',
							'choices' => array(
								0 => lang('none'),
								1 => lang('channels'),
								2 => lang('files')
							),
							'value' => ($cat_group->exclude_group) ?: 0
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
			$group = $this->saveCategoryGroup($group_id);

			if (is_null($group_id))
			{
				ee()->session->set_flashdata('highlight_id', $group->getId());
			}

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('category_group_'.$alert_key))
				->addToBody(sprintf(lang('category_group_'.$alert_key.'_desc'), $group->group_name))
				->defer();

			ee()->functions->redirect(ee('CP/URL')->make('channels/cat'));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('category_group_not_'.$alert_key))
				->addToBody(lang('category_group_not_'.$alert_key.'_desc'))
				->now();
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text = sprintf(lang('btn_save'), lang('category_group'));
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('channels/cat'), lang('category_groups'));

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

		if ($cat_group->count() > 0)
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
		if ($group_id)
		{
			$cat_group = ee('Model')->get('CategoryGroup', $group_id)->first();
		}
		else
		{
			$cat_group = ee('Model')->make('CategoryGroup');
			$cat_group->group_id = $group_id;
			$cat_group->site_id = ee()->config->item('site_id');
		}

		$cat_group->set($_POST);
		$cat_group->can_edit_categories = (ee()->input->post('can_edit_categories'))
			? implode('|', $_POST['can_edit_categories']) : '';
		$cat_group->can_delete_categories = (ee()->input->post('can_delete_categories'))
			? implode('|', $_POST['can_delete_categories']) : '';

		return $cat_group->save();
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
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->cp->add_js_script('plugin', 'nestable');

		if (ee()->cp->allowed_group('can_edit_categories'))
		{
			ee()->cp->add_js_script('file', 'cp/channel/category_reorder');
		}

		// Get the category tree with a single query
		ee()->load->library('datastructures/tree');
		ee()->view->categories = $cat_group->getCategoryTree(ee()->tree);

		ee()->view->base_url = $cat_group->group_name . ' &mdash; ' . lang('categories');
		ee()->view->cp_page_title = $cat_group->group_name . ' &mdash; ' . lang('categories');
		ee()->view->cat_group = $cat_group;

		ee()->javascript->set_global('lang.remove_confirm', lang('categories') . ': <b>### ' . lang('categories') . '</b>');
		ee()->cp->add_js_script('file', 'cp/confirm_remove');

		$reorder_ajax_fail = ee('CP/Alert')->makeBanner('reorder-ajax-fail')
			->asIssue()
			->canClose()
			->withTitle(lang('category_ajax_reorder_fail'))
			->addToBody(lang('category_ajax_reorder_fail_desc'));

		ee()->javascript->set_global('cat.reorder_url', ee('CP/URL')->make('channels/cat/cat-reorder/'.$group_id)->compile());
		ee()->javascript->set_global('alert.reorder_ajax_fail', $reorder_ajax_fail->render());

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('channels/cat'), lang('category_groups'));

		$data = array(
			'can_create_categories' => ee()->cp->allowed_group('can_create_categories'),
			'can_edit_categories' => ee()->cp->allowed_group('can_edit_categories'),
			'can_delete_categories' => ee()->cp->allowed_group('can_delete_categories')
		);

		ee()->cp->render('channels/cat/list', $data);
	}

	/**
	 * AJAX end point for reordering categories on catList page
	 */
	public function catReorder($group_id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$cat_group = ee('Model')->get('CategoryGroup')
			->filter('group_id', $group_id)
			->first();

		$cat_group->sort_order = 'c';
		$cat_group->save();

		$new_order = ee()->input->post('order');

		if ( ! AJAX_REQUEST OR ! $cat_group OR empty($new_order))
		{
			show_error(lang('unauthorized_access'), 403);
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
		if ( ! ee()->cp->allowed_group('can_delete_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$cat_ids = ee()->input->post('categories');

		if ( ! empty($cat_ids) && ee()->input->post('bulk_action') == 'remove')
		{
			// Filter out junk
			$cat_ids = array_filter($cat_ids, 'is_numeric');

			if ( ! empty($cat_ids))
			{
				$cats = ee('Model')->get('Category')
					->filter('cat_id', 'IN', $cat_ids);

				// Grab the group ID for the possible AJAX return below
				$group_id = ee('Model')->get('Category', $cat_ids[0])->first()->CategoryGroup->getId();

				$cats->delete();

				if ( ! AJAX_REQUEST)
				{
					ee('CP/Alert')->makeInline('shared-form')
						->asSuccess()
						->withTitle(lang('categories_removed'))
						->addToBody(sprintf(lang('categories_removed_desc'), count($cat_ids)))
						->defer();
				}
			}
		}
		else
		{
			show_error(lang('unauthorized_access'), 403);
		}

		// Response for publish form category management
		if (AJAX_REQUEST)
		{
			return array(
				'messageType' => 'success',
				'body' => $this->categoryGroupPublishField($group_id, TRUE)
			);
		}

		ee()->functions->redirect(
			ee('CP/URL')->make('channels/cat/cat-list/'.ee()->input->post('cat_group_id'))
		);
	}

	/**
	 * Category create
	 *
	 * @param	int		$group_id	ID of category group category is to be in
	 * @param	bool	$editing	If coming from the publish form, indicates whether
	 *	or not the category list is in an editing state
	 */
	public function createCat($group_id, $editing = FALSE)
	{
		if ( ! ee()->cp->allowed_group('can_create_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		return $this->categoryForm($group_id, NULL, (bool) $editing);
	}

	/**
	 * Category edit
	 *
	 * @param	int	$group_id		ID of category group category is in
	 * @param	int	$category_id	ID of category to edit
	 */
	public function editCat($group_id, $category_id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		return $this->categoryForm($group_id, $category_id, TRUE);
	}

	/**
	 * Category creation/edit form
	 *
	 * @param	int		$group_id	ID of category group category is (to be) in
	 * @param	int	$	category_id	ID of category to edit
	 * @param	bool	$editing	If coming from the publish form, indicates whether or
	 *	or not the category list is in an editing state
	 */
	private function categoryForm($group_id, $category_id = NULL, $editing = FALSE)
	{
		if (empty($group_id) OR ! is_numeric($group_id))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$cat_group = ee('Model')->get('CategoryGroup')
			->filter('group_id', $group_id)
			->first();

		if ( ! $cat_group)
		{
			show_error(lang('unauthorized_access'), 403);
		}

		//  Check discrete privileges when editig (we have no discrete create
		//  permissions)
		if (AJAX_REQUEST && $editing)
		{
			$can_edit = explode('|', rtrim($cat_group->can_edit_categories, '|'));

			if (ee()->session->userdata('group_id') != 1 AND ! in_array(ee()->session->userdata('group_id'), $can_edit))
			{
				show_error(lang('unauthorized_access'), 403);
			}
		}

		if (is_null($category_id))
		{
			$alert_key = 'created';
			ee()->view->cp_page_title = lang('create_category');
			$create_url = 'channels/cat/create-cat/'.$group_id;
			if ($editing)
			{
				$create_url .= '/editing';
			}
			ee()->view->base_url = ee('CP/URL')->make($create_url);

			$category = ee('Model')->make('Category');
			$category->setCategoryGroup($cat_group);
			$category->site_id = ee()->config->item('site_id');

			// Only auto-complete channel short name for new channels
			ee()->cp->add_js_script('plugin', 'ee_url_title');

			//	Create Foreign Character Conversion JS
			$foreign_characters = ee()->config->loadFile('foreign_chars');

			/* -------------------------------------
			/*  'foreign_character_conversion_array' hook.
			/*  - Allows you to use your own foreign character conversion array
			/*  - Added 1.6.0
			* 	- Note: in 2.0, you can edit the foreign_chars.php config file as well
			*/
				if (ee()->extensions->active_hook('foreign_character_conversion_array') === TRUE)
				{
					$foreign_characters = ee()->extensions->call('foreign_character_conversion_array');
				}
			/*
			/* -------------------------------------*/

			ee()->javascript->set_global(array(
				'publish.foreignChars'   => $foreign_characters,
				'publish.word_separator' => ee()->config->item('word_separator') != "dash" ? '_' : '-'
			));

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
				show_error(lang('unauthorized_access'), 403);
			}

			$alert_key = 'updated';
			ee()->view->cp_page_title = lang('edit_category');
			ee()->view->base_url = ee('CP/URL')->make('channels/cat/edit-cat/'.$group_id.'/'.$category_id);
		}

		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_categories');
		ee()->api_channel_categories->category_tree($group_id, $category->parent_id);

		$parent_id_options[0] = $this->lang->line('none');
		foreach(ee()->api_channel_categories->categories as $cat)
		{
			// Don't give option to set self as parent
			if ( ! is_null($category_id) && $category_id == $cat[0])
			{
				continue;
			}

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
					'desc' => 'alphadash_desc',
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
					'fields' => array(
						'cat_description' => array(
							'type' => 'textarea',
							'value' => $category->cat_description
						)
					)
				)
			)
		);

		if ( ! AJAX_REQUEST)
		{
			$vars['sections'][0][] = array(
				'title' => 'image',
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
			);
		}

		$vars['sections'][0][] = array(
			'title' => 'parent_category',
			'fields' => array(
				'parent_id' => array(
					'type' => 'select',
					'value' => $category->parent_id,
					'choices' => $parent_id_options,
					'encode' => FALSE
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

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text = sprintf(lang('btn_save'), lang('category'));
		ee()->view->save_btn_text_working = 'btn_saving';

		if ( ! empty($_POST))
		{
			$category->set($_POST);
			$result = $category->validate();

			// Handles saving from the category modal on the publish form
			if (isset($_POST['save_modal']))
			{
				if ($result->isValid())
				{
					$category->save();
					return array(
						'messageType' => 'success',
						'body' => $this->categoryGroupPublishField($group_id, $editing)
					);
				}
				else
				{
					ee()->load->library('form_validation');
					ee()->form_validation->_error_array = $result->renderErrors();
					return array(
						'messageType' => 'error',
						'body' => ee()->cp->render('_shared/form', $vars, TRUE)
					);
				}
			}

			if ($response = $this->ajaxValidation($result))
			{
				return $response;
			}

			if ($result->isValid())
			{
				$category = $category->save();

				if (is_null($category_id))
				{
					ee()->session->set_flashdata('highlight_id', $category->getId());
				}

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('category_'.$alert_key))
					->addToBody(sprintf(lang('category_'.$alert_key.'_desc'), $category->cat_name))
					->defer();

				ee()->functions->redirect(ee('CP/URL')->make('channels/cat/cat-list/'.$cat_group->group_id));
			}
			else
			{
				ee()->load->library('form_validation');
				ee()->form_validation->_error_array = $result->renderErrors();
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('category_group_not_'.$alert_key))
					->addToBody(lang('category_group_not_'.$alert_key.'_desc'))
					->now();
			}
		}

		if (AJAX_REQUEST)
		{
			return ee()->cp->render('_shared/form', $vars);
		}

		$filepicker = new FilePicker();
		$filepicker->inject(ee()->view);
		ee()->cp->add_js_script('file', 'cp/channel/category_edit');
		ee()->javascript->set_global(
			'category_edit.filepicker_url',
			ee('CP/URL')->make($filepicker->controller, array('directory' => 'all', 'type' => 'img'))->compile()
		);

		ee()->javascript->output('$(document).ready(function () {
			EE.cp.categoryEdit.init();
		});');

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('channels/cat'), lang('category_groups'));
		ee()->cp->set_breadcrumb(ee('CP/URL')->make('channels/cat/cat-list/'.$cat_group->group_id), $cat_group->group_name . ' &mdash; ' . lang('categories'));

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * AJAX return body for adding a new category via the publish form; when a
	 * new category is added, we have to refresh the category list
	 *
	 * @param	int		$group_id	Category group ID
	 * @param	bool	$editing	If coming from the publish form, indicates whether or
	 *	or not the category list is in an editing state
	 */
	private function categoryGroupPublishField($group_id, $editing = FALSE)
	{
		$group = ee('Model')->get('CategoryGroup', $group_id)->first();

		$entry = ee('Model')->make('ChannelEntry');
		$entry->Categories = NULL;

		// Initialize a new category group field so we can return its publish form
		$category_group_field = $group->getFieldMetadata();
		$category_group_field['editing'] = $editing;
		$category_group_field['categorized_object'] = $entry;

		$field_id = 'categories[cat_group_id_'.$group_id.']';
		$field = new FieldFacade($field_id, $category_group_field);
		$field->setName($field_id);

		$group->populateCategories($field);

		// Reset the categories they already have selected
		$selected_cats = ee('Model')->get('Category')->filter('cat_id', 'IN', ee()->input->post('categories'))->all();
		$field->setData(implode('|', $selected_cats->pluck('cat_name')));

		return $field->getForm();
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
			show_error(lang('unauthorized_access'), 403);
		}

		$table = ee('CP/Table', array(
			'reorder' => TRUE,
			'sortable' => FALSE
		));
		$table->setColumns(
			array(
				'col_id' => array(
					'encode' => FALSE
				),
				'label',
				'short_name_col' => array(
					'encode' => FALSE
				),
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
			ee('CP/URL')->make('channels/cat/create-field/'.$group_id)
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
			$edit_url = ee('CP/URL')->make('channels/cat/edit-field/'.$group_id.'/'.$field->getId());

			$columns = array(
				$field->getId().form_hidden('order[]', $field->getId()),
				array(
					'content' => $field->field_label,
					'href' => $edit_url
				),
				'<var>'.LD.$field->field_name.RD.'</var>',
				strtolower($type_map[$field->field_type]),
				array('toolbar_items' => array(
					'edit' => array(
						'href' => $edit_url,
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'fields[]',
					'value' => $field->getId(),
					'data'	=> array(
						'confirm' => lang('category_field') . ': <b>' . htmlentities($field->field_label, ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);

			$attrs = array();
			if (ee()->session->flashdata('highlight_id') == $field->getId())
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs' => $attrs,
				'columns' => $columns
			);
		}

		$table->setData($data);

		$vars['table'] = $table->viewData(ee('CP/URL')->make('channels/cat/field/'.$group_id));
		$vars['group_id'] = $group_id;

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('channels/cat'), lang('category_groups'));
		ee()->view->cp_page_title = lang('category_fields') . ' ' . lang('for') . ' ' . $cat_group->group_name;

		ee()->javascript->set_global('lang.remove_confirm', lang('category_fields') . ': <b>### ' . lang('category_fields') . '</b>');
		ee()->cp->add_js_script('file', 'cp/confirm_remove');
		ee()->cp->add_js_script('plugin', 'ee_table_reorder');
		ee()->cp->add_js_script('file', 'cp/channel/cat_field_reorder');

		$reorder_ajax_fail = ee('CP/Alert')->makeBanner('reorder-ajax-fail')
			->asIssue()
			->canClose()
			->withTitle(lang('cat_field_ajax_reorder_fail'))
			->addToBody(lang('cat_field_ajax_reorder_fail_desc'));

		ee()->javascript->set_global('cat_fields.reorder_url', ee('CP/URL')->make('channels/cat/cat-field-reorder/'.$group_id)->compile());
		ee()->javascript->set_global('alert.reorder_ajax_fail', $reorder_ajax_fail->render());

		ee()->cp->render('channels/cat/field', $vars);
	}

	/**
	 * AJAX end point for reordering category fields on fields listing page
	 */
	public function catFieldReorder($group_id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$cat_group = ee('Model')->get('CategoryGroup')
			->filter('group_id', $group_id)
			->first();

		// Parse out the serialized inputs sent by the JavaScript
		$new_order = array();
		parse_str(ee()->input->post('order'), $new_order);

		if ( ! AJAX_REQUEST OR ! $cat_group OR empty($new_order['order']))
		{
			show_error(lang('unauthorized_access'), 403);
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
		if ( ! ee()->cp->allowed_group('can_delete_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

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

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('category_fields_removed'))
					->addToBody(sprintf(lang('category_fields_removed_desc'), count($field_ids)))
					->defer();
			}
		}
		else
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->functions->redirect(
			ee('CP/URL')->make('channels/cat/field/'.ee()->input->post('group_id'), ee()->cp->get_url_state())
		);
	}

	/**
	 * Category field create
	 *
	 * @param	int	$group_id		ID of category group field is to be in
	 */
	public function createField($group_id)
	{
		if ( ! ee()->cp->allowed_group('can_create_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		return $this->categoryFieldForm($group_id);
	}

	/**
	 * Category field edit
	 *
	 * @param	int	$group_id	ID of category group field is in
	 * @param	int	$field_id	ID of Field to edit
	 */
	public function editField($group_id, $field_id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_categories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		return $this->categoryFieldForm($group_id, $field_id);
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
			show_error(lang('unauthorized_access'), 403);
		}

		$cat_group = ee('Model')->get('CategoryGroup', $group_id)->first();

		if ($field_id)
		{
			$cat_field = ee('Model')->get('CategoryField')
				->filter('field_id', (int) $field_id)
				->first();

			$alert_key = 'updated';
			ee()->view->cp_page_title = lang('edit_category_field');
			ee()->view->base_url = ee('CP/URL')->make('channels/cat/edit-field/'.$group_id.'/'.$field_id);
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
			$cat_field->site_id = ee()->config->item('site_id');
			$cat_field->field_type = 'text';

			$alert_key = 'created';
			ee()->view->cp_page_title = lang('create_category_field');
			ee()->view->base_url = ee('CP/URL')->make('channels/cat/create-field/'.$group_id);
		}

		if ( ! $cat_field)
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->lang->loadfile('admin_content');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'type',
					'desc' => '',
					'fields' => array(
						'field_type' => array(
							'type' => 'select',
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
					'title' => 'name',
					'desc' => 'name_desc',
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
					'desc' => 'alphadash_desc',
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
			)
		);

		$vars['sections'] += $cat_field->getSettingsForm();

		// These are currently the only fieldtypes we allow; get their settings forms
		foreach (array('text', 'textarea', 'select') as $fieldtype)
		{
			if ($cat_field->field_type != $fieldtype)
			{
				$dummy_field = ee('Model')->make('CategoryField');
				$dummy_field->setCategoryGroup($cat_group);
				$dummy_field->field_type = $fieldtype;
				$vars['sections'] += $dummy_field->getSettingsForm();
			}
		}

		if ( ! empty($_POST))
		{
			$cat_field->set($_POST);
			$cat_field->field_default_fmt = isset($_POST['field_fmt']) ? $_POST['field_fmt'] : NULL;
			$result = $cat_field->validate();

			if ($response = $this->ajaxValidation($result))
			{
				return $response;
			}

			if ($result->isValid())
			{
				$cat_field->save();

				if (isset($_POST['update_formatting']) && $_POST['update_formatting'] == 'y')
				{
					$cat_field->updateFormattingOnExisting();
				}

				if (is_null($field_id))
				{
					ee()->session->set_flashdata('highlight_id', $cat_field->getId());
				}

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('category_field_'.$alert_key))
					->addToBody(sprintf(lang('category_field_'.$alert_key.'_desc'), $cat_field->field_label))
					->defer();

				ee()->functions->redirect(ee('CP/URL')->make('channels/cat/field/'.$group_id));
			}
			else
			{
				ee()->load->library('form_validation');
				ee()->form_validation->_error_array = $result->renderErrors();
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('category_field_not_'.$alert_key))
					->addToBody(lang('category_field_not_'.$alert_key.'_desc'))
					->now();
			}
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text = sprintf(lang('btn_save'), lang('category_field'));
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('channels/cat'), lang('category_groups'));
		ee()->cp->set_breadcrumb(ee('CP/URL')->make('channels/cat/field/'.$group_id), lang('category_fields'));

		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/form_group',
				'cp/channel/fields'
			)
		));

		ee()->cp->render('settings/form', $vars);
	}
}

// EOF
