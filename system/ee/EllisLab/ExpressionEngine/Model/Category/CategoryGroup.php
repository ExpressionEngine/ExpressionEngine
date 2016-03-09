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

}

// EOF
