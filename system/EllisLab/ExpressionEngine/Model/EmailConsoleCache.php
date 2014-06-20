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
	protected $cache_id;
	protected $cache_date;
	protected $member_id;
	protected $member_name;
	protected $ip_address;
	protected $recipient;
	protected $recipient_name;
	protected $subject;
	protected $message;


}
