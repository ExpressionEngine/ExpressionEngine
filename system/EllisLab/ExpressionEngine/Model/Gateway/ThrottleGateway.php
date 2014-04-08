<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class ThrottleGateway extends RowDataGateway {
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
