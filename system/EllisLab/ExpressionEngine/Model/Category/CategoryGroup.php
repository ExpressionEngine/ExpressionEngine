<?php
namespace EllisLab\ExpressionEngine\Model\Category;

use EllisLab\ExpressionEngine\Model\Model as Model;
use EllisLab\ExpressionEngine\Model\Interfaces\Content\ContentStructure;

class CategoryGroup extends Model implements ContentStructure {
	protected static $_meta = array(
		'primary_key' => 'group_id',
		'gateway_names' => array('CategoryGroupGateway'),
		'key_map' => array(
			'group_id' => 'CategoryGroupGateway',
			'site_id' => 'CategoryGroupGateway',
		)	
	);

	/**
	 * Relationship to the field structure for this category.
	 */
	public function getCategoryFieldStructures()
	{
		return $this->manyToOne(
			'CategoryFieldStructures', 'CategoryFieldStructure', 'group_id', 'group_id');	
	}

	/**
	 * Relationship to ChannelEntries for this Channel.
	 */
	public function getCategories()
	{
		return $this->oneToMany(
			'Categories', 'Category', 'group_id', 'group_id');
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
