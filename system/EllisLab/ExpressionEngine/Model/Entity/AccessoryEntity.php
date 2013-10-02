<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class AccessoryEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'accessories',
		'primary_key' => 'accessory_id'
	);

	// Propeties
	public $accessory_id;
	public $class;
	public $member_groups;
	public $controllers;
	public $accessory_version;


}
