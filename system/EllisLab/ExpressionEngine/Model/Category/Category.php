<?php
namespace EllisLab\ExpressionEngine\Model\Category;

use EllisLab\ExpressionEngine\Model\FieldDataContentModel;

class Category extends FieldDataContentModel {
	protected static $meta = array(
		'primary_key' => 'cat_id',
		'entity_names' => array('CategoryEntity', 'CategoryFieldDataEntity'),
		'key_map' => array(
			'cat_id' => 'CategoryEntity',
			'site_id' => 'CategoryEntity',
			'group_id' => 'CategoryEntity',
			'parent_id' => 'CategoryEntity'
		),
		'field_content_class' => 'CategoryFieldContent',
		'field_content_entity' => 'CategoryFieldDataEntity'
	);
	
	/**
	 *
	 */
	public function getCategoryGroup()
	{
		return $this->manyToOne('CategoryGroup', 'CategoryGroup', 'group_id', 'group_id');
	}


	public function getChannelEntries()
	{
		return $this->manyToMany('ChannelEntries', 'ChannelEntry', 'cat_id', 'entry_id');
	}

	/**
	 *
	 */
	public function getParent()
	{
		return $this->manyToOne('Parent', 'Category', 'parent_id', 'cat_id');
	}

	/**
	 * A link back to the owning channel object.
	 *
	 * @return	Structure	A link to the Structure objects that defines this
	 * 						Content's structure.
	 */
	public function getContentStructure()
	{
		return $this->getCategoryGroup();
	}


	/**
	 * Renders the piece of content for the front end, parses the tag data
	 * called by the module when rendering tagdata.
	 *
	 * @param	ParsedTemplate|string	$template	The parsed template from
	 * 						the template engine or a string of tagdata.
	 *
	 * @return	Template|string	The parsed template with relevant tags replaced
	 *							or the tagdata string with relevant tags replaced.
	 */
	public function render($template)
	{
		// call render on all custom fields
	}


	/**
	 * Validates the channel entry before saving
	 *
	 * @return	void
	 *
	 * @throws	ContentInvalidException If content fails to validate a
	 * 						ContentInvalidException will be thrown with errors.
	 */
	public function validate()
	{

	}

}
