<?php
namespace EllisLab\ExpressionEngine\Model;

class EmailConsoleCache extends Model {
	protected static $_primary_key = 'cache_id';
	protected static $_gateway_names = array('EmailConsoleCacheGateway');

	protected static $_relationships = array(
		'Member' => array(
			'type' => 'many_to_one'
		),
	);

	// Properties
	public $cache_id;
	public $cache_date;
	public $member_id;
	public $member_name;
	public $ip_address;
	public $recipient;
	public $recipient_name;
	public $subject;
	public $message;


}
