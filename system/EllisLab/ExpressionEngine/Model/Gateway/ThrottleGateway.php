<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

class ThrottleGateway extends RowDataGateway {

	protected static $_table_name = 'throttle';
	protected static $_primary_key = 'throttle_id';

	// Properties
	protected $throttle_id;
	protected $ip_address;
	protected $last_activity;
	protected $hits;
	protected $locked_out;
}
