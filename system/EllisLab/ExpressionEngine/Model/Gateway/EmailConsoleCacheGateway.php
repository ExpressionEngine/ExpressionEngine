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
