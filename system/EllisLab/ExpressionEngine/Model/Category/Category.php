<?php
namespace EllisLab\ExpressionEngine\Model\Category;

use EllisLab\ExpressionEngine\Model\FieldDataContentModel;

class Category extends FieldDataContentModel {
	protected static $_meta = array(
		'primary_key' => 'cat_id',
		'gateway_names' => array('CategoryGateway', 'CategoryFieldDataGateway'),
		'key_map' => array(
			'cat_id' => 'CategoryGateway',
			'site_id' => 'CategoryGateway',
			'group_id' => 'CategoryGateway',
			'parent_id' => 'CategoryGateway'
		),
		'field_content_class' => 'CategoryFieldContent',
		'field_content_gateway' => 'CategoryFieldDataGateway'
	);
	
	// Properties
	protected $cat_id;
	protected $site_id;
	protected $group_id;
	protected $parent_id;
	protected $cat_name;
	protected $cat_url_title;
	protected $cat_description;
	protected $cat_image;
	protected $cat_order;

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
