<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class ThrottleEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'throttle',
		'primary_key' => 'throttle_id'
	);

	// Properties
	public $throttle_id;
	public $ip_address;
	public $last_activity;
	public $hits;
	public $locked_out;
}
