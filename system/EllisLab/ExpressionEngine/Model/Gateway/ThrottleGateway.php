<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

class ThrottleGateway extends RowDataGateway {

	protected static $_table_name = 'throttle';
	protected static $_primary_key = 'throttle_id';

	// Properties
	public $throttle_id;
	public $ip_address;
	public $last_activity;
	public $hits;
	public $locked_out;
}
