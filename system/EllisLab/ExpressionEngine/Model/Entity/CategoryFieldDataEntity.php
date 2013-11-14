<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity;

class CategoryFieldDataEntity extends FieldDataEntity {
	protected static $meta = array(
		'table_name' => 'category_field_data',
		'primary_key' => 'cat_id',
		'field_table' => 'category_fields',
		'field_id_name' => 'field_id',
		'related_entities' => array(
			'cat_id' => array(
				'entity' => 'CategoryEntity',
				'key'	 => 'cat_id'
			),
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key'	 => 'site_id'
			),
			'group_id' => array(
				'entity' => 'CategoryGroupEntity',
				'key'	 => 'group_id'
			),
		)
	);

	// Properties
	public $cat_id;
	public $site_id;
	public $group_id;

}
