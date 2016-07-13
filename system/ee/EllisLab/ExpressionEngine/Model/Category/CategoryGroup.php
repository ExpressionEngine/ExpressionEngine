<?php

namespace EllisLab\ExpressionEngine\Model\Category;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Model\Content\StructureModel;

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
 * ExpressionEngine Category Group Model
 *
 * @package		ExpressionEngine
 * @subpackage	Category
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
		),
		'Channel' => array(
			'type' => 'hasMany',
			'model' => 'Channel',
			'to_key' => 'cat_group'
		),
	);

	// Properties
	protected $group_id;
	protected $site_id;
	protected $group_name;
	protected $sort_order;
	protected $exclude_group;
	protected $field_html_formatting;
	protected $can_edit_categories;
	protected $can_delete_categories;

	public function getCustomFields()
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
			'editing'				=> FALSE, // Not currently in editing state
			'deletable'				=> $deletable,
			'populateCallback'		=> array($this, 'populateCategories'),
			'manage_toggle_label'	=> lang('manage_categories'),
			'content_item_label'	=> lang('category')
		);

		return $metadata;
	}

	/**
	 * Sets a field's data based on which categories are selected
	 */
	public function populateCategories($field)
	{
		$categories = ee('Model')->get('Category')
			->with(array('Children as C0' => array('Children as C1' => 'Children as C2')))
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
		if ( ! $object->isNew())
		{
			$set_categories = $object->Categories->filter('group_id', $field->getItem('group_id'))->pluck('cat_id');
			$field->setData(implode('|', $set_categories));
		}
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
