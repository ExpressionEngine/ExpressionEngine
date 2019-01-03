<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Category;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Model\Content\StructureModel;

/**
 * Category Group Model
 */
class CategoryGroup extends StructureModel {

	protected static $_primary_key = 'group_id';
	protected static $_gateway_names = array('CategoryGroupGateway');

	protected static $_hook_id = 'category_group';

	protected static $_relationships = array(
		'CategoryFields' => array(
			'type' => 'hasMany',
			'model' => 'CategoryField'
		),
		'Categories' => array(
			'type' => 'hasMany',
			'model' => 'Category'
		)
	);

	protected static $_validation_rules = array(
		'group_name'            => 'required|unique[site_id]',
		'sort_order'            => 'enum[a,c]',
		'field_html_formatting' => 'enum[all,safe,none]',
		'exclude_group'         => 'enum[0,1,2]'
	);

	protected static $_events = [
		'afterDelete'
	];

	// Properties
	protected $group_id;
	protected $site_id;
	protected $group_name;
	protected $sort_order;
	protected $exclude_group;
	protected $field_html_formatting;
	protected $can_edit_categories;
	protected $can_delete_categories;

	public function onAfterDelete()
	{
		// Disassociate this group from channels
		foreach ($this->Channels as $channel)
		{
			$groups = explode('|', $channel->cat_group);

			if (($key = array_search($this->getId(), $groups)) !== FALSE)
			{
				unset($groups[$key]);
				$channel->cat_group = implode('|', $groups);
				$channel->save();
			}
		}
	}

	public function __get($name)
	{
		// Fake the Channel relationship since it's stored weird; old
		// relationship name was just "Channel"
		if ($name == 'Channel' || $name == 'Channels')
		{
			return ee('Model')->get('Channel')
				->filter('site_id', ee()->config->item('site_id'))
				->all()
				->filter(function($channel) {
					return in_array($this->getId(), explode('|', $channel->cat_group));
				});
		}

		return parent::__get($name);
	}

	public function getAllCustomFields()
	{
		return $this->getCategoryFields();
	}

	/**
	 * Convenience method to fix inflection
	 */
	public function createCategoryField($data)
	{
		return $this->createCategoryFields($data);
	}


	public function getContentType()
	{
		return 'category';
	}

	/**
	 * Returns the category tree for this category group
	 *
	 * @param 	EE_Tree	$tree		An EE_Tree library object
	 * @return 	Object<ImmutableTree> Traversable tree object
	 */
	public function getCategoryTree(\EE_Tree $tree)
	{
		$sort_column = ($this->sort_order == 'a') ? 'cat_name' : 'cat_order';

		return $tree->from_list(
			$this->getCategories()->sortBy($sort_column),
			array('id' => 'cat_id')
		);
	}

	/**
	 * Generates the metadata needed to hand off to the old channel field API
	 * in order to instantiate a field.
	 *
	 * @return array An associative array.
	 */
	public function getFieldMetadata()
	{
		$can_edit = explode('|', rtrim($this->can_edit_categories, '|'));
		$editable = FALSE;

		if (ee()->session->userdata['group_id'] == 1
			|| (ee()->session->userdata['can_edit_categories'] == 'y'
				&& in_array(ee()->session->userdata['group_id'], $can_edit)
				))
			{
				$editable = TRUE;
			}

		$can_delete = explode('|', rtrim($this->can_delete_categories, '|'));
		$deletable = FALSE;

		if (ee()->session->userdata['group_id'] == 1
			|| (ee()->session->userdata['can_delete_categories'] =='y'
				&& in_array(ee()->session->userdata['group_id'], $can_delete)
				))
			{
				$deletable = TRUE;
			}

		$no_results = [
			'text' => sprintf(lang('no_found'), lang('categories'))
		];

		if ( ! INSTALLER && ee('Permission')->has('can_create_categories'))
		{
			$no_results['link_text'] = 'add_new';
			$no_results['link_href'] = ee('CP/URL')->make('categories/create/'.$this->getId());
		}

		$metadata = array(
			'field_id'				=> 'categories',
			'group_id'				=> $this->getId(),
			'field_label'			=> $this->group_name,
			'field_required'		=> 'n',
			'field_show_fmt'		=> 'n',
			'field_instructions'	=> lang('categories_desc'),
			'field_text_direction'	=> 'ltr',
			'field_type'			=> 'checkboxes',
			'field_list_items'      => '',
			'field_maxl'			=> 100,
			'editable'				=> $editable,
			'editing'				=> FALSE,
			'deletable'				=> $deletable,
			'populateCallback'		=> array($this, 'populateCategories'),
			'manage_toggle_label'	=> lang('manage_categories'),
			'add_btn_label'	        => REQ == 'CP' && ee()->cp->allowed_group('can_create_categories')
				? lang('add_category')
				: NULL,
			'content_item_label'	=> lang('category'),
			'reorder_ajax_url'		=> ! INSTALLER
				? ee('CP/URL')->make('categories/reorder/'.$this->getId())->compile()
				: '',
			'auto_select_parents'	=> ee()->config->item('auto_assign_cat_parents') == 'y',
			'no_results'			=> $no_results
		);

		return $metadata;
	}

	/**
	 * Sets a field's data based on which categories are selected
	 */
	public function populateCategories($field)
	{
		$categories = $this->getModelFacade()->get('Category')
			->with(
				['Children as C0' =>
					['Children as C1' =>
						['Children as C2' => 'Children as C3']
					]
				]
			)
			->with('CategoryGroup')
			->filter('CategoryGroup.group_id', $field->getItem('group_id'))
			->filter('Category.parent_id', 0)
			->all();

		// Sorting alphabetically or custom?
		$sort_column = 'cat_order';
		if ($categories->count() && $categories->first()->CategoryGroup->sort_order == 'a')
		{
			$sort_column = 'cat_name';
		}

		$category_list = $this->buildCategoryList($categories->sortBy($sort_column), $sort_column);
		$field->setItem('field_list_items', $category_list);

		$object = $field->getItem('categorized_object');

		// isset() and empty() don't work here on $object->Channel because it hasn't been dynamically fetched yet,
		// is_object() apparently works differently and lets it dynamically load it before evaluating
		$has_default = ($object->getName() == 'ee:ChannelEntry' && is_object($object->Channel)) ? TRUE : FALSE;

		// New Channel Entries might have a default category selected, but File
		// entities should not have categories pre-selected for new entries
		if ( ! $object->isNew() OR ($object->getName() == 'ee:ChannelEntry' && $has_default))
		{
			$set_categories = $object->Categories->filter('group_id', $field->getItem('group_id'))->pluck('cat_id');
			$field->setData(implode('|', $set_categories));
		}
	}

	/**
	 * Builds a tree of categories in the current category group for use in a
	 * SelectField form
	 *
	 * @param array Category tree
	 */
	public function buildCategoryOptionsTree()
	{
		$sort_column = 'cat_order';
		if ($this->sort_order == 'a')
		{
			$sort_column = 'cat_name';
		}

		return $this->buildCategoryList(
			$this->Categories->filter('parent_id', 0),
			$sort_column
		);
	}

	/**
	 * Turn the categories collection into a nested array of ids => names
	 *
	 * @param	Collection	$categories		Top level categories to construct tree out of
	 * @param	string		$sort_column	Either 'cat_name' or 'cat_order', sorts the
	 *	categories by the given column
	 */
	protected function buildCategoryList($categories, $sort_column)
	{
		$list = array();

		foreach ($categories as $category)
		{
			$children = $category->Children->sortBy($sort_column);

			if (count($children))
			{
				$list[$category->cat_id] = array(
					'name' => $category->cat_name,
					'children' => $this->buildCategoryList($children, $sort_column)
				);

				continue;
			}

			$list[$category->cat_id] = $category->cat_name;
		}

		return $list;
	}
}

// EOF
