<?php

namespace EllisLab\ExpressionEngine\Model\Category;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Interfaces\Content\ContentStructure;

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
 * ExpressionEngine Category Group Model
 *
 * @package		ExpressionEngine
 * @subpackage	Category
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CategoryGroup extends Model implements ContentStructure {

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
		'Parent' => array(
			'type' => 'belongsTo',
			'model' => 'Category',
			'key' => 'parent_id'
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

	/**
	 * Display the CP entry form
	 *
	 * @param Content $content  An object implementing the Content interface
	 * @return Array of HTML field elements for the entry / edit form
	 */
	public function getPublishForm($content)
	{}

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
