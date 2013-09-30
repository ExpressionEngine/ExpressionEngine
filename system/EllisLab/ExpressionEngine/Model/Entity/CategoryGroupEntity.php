<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class CategoryGroupEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'category_groups',
		'primary_key' => 'group_id', 
		'related_entities' => array(
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key'	 => 'site_id'
			)
		)	
	);


	// Properties
	public $group_id;
	public $site_id;
	public $group_name;
	public $sort_order;
	public $exclude_group;
	public $field_html_formatting;
	public $can_edit_categories;
	public $can_delete_categories;
}
