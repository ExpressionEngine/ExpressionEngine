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

	protected static $_key_map = array(
		'group_id' => 'CategoryGroupGateway',
		'site_id'  => 'CategoryGroupGateway',
	);

	protected static $_relationships = array(
		'CategoryFieldStructures' => array(
			'type' => 'many_to_one'
		),
		'Categories' => array(
			'type' => 'one_to_many',
			'model' => 'Category'
		),
		'Parent' => array(
			'type' => 'many_to_one',
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
	 * Relationship to the field structure for this category.
	 */
	public function getCategoryFieldStructures()
	{
		return $this->getRelated('CategoryFieldStructures');
	}

	public function setCategoryFieldStructures(array $structures)
	{
		return $this->setRelated('CategoryFieldStructures', $structures);
	}

	/**
	 * Relationship to ChannelEntries for this Channel.
	 */
	public function getCategories()
	{
		return $this->getRelated('Categories');
	}

	public function setCategories(array $categories)
	{
		return $this->setRelated('Categories', $categories);
	}

	/**
	 * Display the CP entry form
	 *
	 * @param Content $content  An object implementing the Content interface
	 * @return Array of HTML field elements for the entry / edit form
	 */
	public function getPublishForm($content)
	{}

}
