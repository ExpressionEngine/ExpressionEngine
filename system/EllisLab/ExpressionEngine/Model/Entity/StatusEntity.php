<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class StatusEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'statuses',
		'primary_key' => 'status_id',
		'related_entities' => array(
			'group_id' => array(
				'entity' => 'StatusGroupEntity',
				'key' => 'group_id'
			),
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key' => 'site_id'
			)
		)
	);


	public $status_id;
	public $group_id;
	public $site_id;
	public $status;
	public $status_order;
	public $highlight;
}
