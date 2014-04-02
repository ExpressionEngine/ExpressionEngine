<?php
namespace EllisLab\ExpressionEngine\Model\Category;

use EllisLab\ExpressionEngine\Model\FieldDataContentModel;

class Category extends FieldDataContentModel {

	protected static $_primary_key = 'cat_id';
	protected static $_gateway_names = array('CategoryGateway', 'CategoryFieldDataGateway');

	protected static $_field_content_class = 'CategoryFieldContent';
	protected static $_field_content_gateway = 'CategoryFieldDataGateway';

	protected static $_key_map = array(
		'cat_id'	=> 'CategoryGateway',
		'site_id'	=> 'CategoryGateway',
		'group_id'	=> 'CategoryGateway',
		'parent_id'	=> 'CategoryGateway'
	);

	protected static $_relationships = array(
		'CategoryGroup' => array(
			'type' => 'many_to_one'
		),
		'ChannelEntries' => array(
			'type' => 'many_to_many',
			'model' => 'ChannelEntry'
		),
		'Parent' => array(
			'type' => 'many_to_one',
			'model' => 'Category',
			'key' => 'parent_id'
		),
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
		return $this->getRelated('CategoryGroup');
	}

	public function setCategoryGroup($group)
	{
		return $this->setRelated('CategoryGroup', $group);
	}

	public function getChannelEntries()
	{
		return $this->getRelated('ChannelEntries');
	}

	public function setChannelEntries(array $entries)
	{
		return $this->setRelated('ChannelEntries', $entries);
	}

	/**
	 *
	 */
	public function getParent()
	{
		return $this->getRelated('Parent');
	}

	public function setParent($parent)
	{
		return $this->setRelated('Parent', $parent);
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


}
