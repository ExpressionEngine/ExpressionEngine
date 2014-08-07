<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

/**
 * Email Console Cache
 *
 * Emails sent from the member profile email console are saved here.
 */
class EmailConsoleCacheGateway extends RowDataGateway {

	protected static $_table_name = 'email_console_cache';
	protected static $_primary_key = 'cache_id';
	protected static $_related_entites = array(
		'member_id' => array(
			'gateway' => 'MemberGateway',
			'key' => 'member_id'
		)
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
