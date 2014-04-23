<?php
namespace EllisLab\ExpressionEngine\Model;

class Throttle extends Model {
	// Meta data
	protected static $_primary_key = 'throttle_id';
	protected static $_gateway_names = array('ThrottleGateway');

	// Properties
	protected $throttle_id;
	protected $ip_address;
	protected $last_activity;
	protected $hits;
	protected $locked_out;

}
