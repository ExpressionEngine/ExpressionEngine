<?php
namespace EllisLab\ExpressionEngine\Model\Category;

use EllisLab\ExpressionEngine\Model\Model as Model;
use EllisLab\ExpressionEngine\Model\Interfaces\Content\ContentStructure;

class CategoryGroup extends Model implements ContentStructure {
	protected static $meta = array(
		'primary_key' => 'group_id',
		'entity_names' => array('CategoryGroupEntity'),
		'key_map' => array(
			'group_id' => 'CategoryGroupEntity',
			'site_id' => 'CategoryGroupEntity',
		)	
	);

	/**
	 * Relationship to the FieldGroup for this Channel.
	 */
	public function getCategoryFieldStructures()
	{
		return $this->manyToOne(
			'CategoryFieldStructure', 'group_id', 'group_id');	
	}

	/**
	 * Relationship to ChannelEntries for this Channel.
	 */
	public function getCategories()
	{
		return $this->oneToMany(
			'Category', 'group_id', 'group_id');
	}

	/**
	 * Display the specified settings section
	 *
	 * @return String   HTML Settings form
	 */
	public function getSettings($name = NULL)
	{}

	/**
	 * Validate the setting data
	 *
	 * @return Errors
	 */
	public function validate()
	{}

	/**
	 * Display the CP entry form
	 *
	 * @param Content $content  An object implementing the Content interface
	 * @return Array of HTML field elements for the entry / edit form
	 */
	public function getPublishForm($content)
	{}

}
