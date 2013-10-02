<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class StatusGroupEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'status_groups',
		'primary_key' => 'group_id',
		'related_entities' => array(
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key' => 'site_id'
			)
		)
	);


	// Properties
	public $group_id;
	public $site_id;
	public $group_name;

}
