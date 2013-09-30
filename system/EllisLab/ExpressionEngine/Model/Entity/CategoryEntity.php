<?php
namespace Ellislab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class CategoryEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'categories',
		'primary_key' => 'cat_id',
		'related_entities' => array(
			'cat_id' => array(
				'entity' => 'CategoryFieldDataEntity',
				'key'	 => 'cat_id'
			),
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key'	 => 'site_id'
			)
			'group_id' => array(
				'entity' => 'CategoryGroupEntity',
				'key'	 => 'group_id'
			),
			'parent_id' => array(
				'entity' => 'CategoryEntity',
				'key'	 => 'cat_id'
			)
		)
	);

	// Properties
	public $cat_id;
	public $site_id;
	public $group_id;
	public $parent_id;
	public $cat_name;
	public $cat_url_title;
	public $cat_description;
	public $cat_image;
	public $cat_order;
}
