<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity extends Entity;

/**
 * Email Console Cache
 *
 * Emails sent from the member profile email console are saved here.
 */
class EmailConsoleCacheEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'email_console_cache',
		'primary_key' => 'cache_id',
		'related_entites' => array(
			'member_id' => array(
				'entity' => 'MemberEntity',
				'key' => 'member_id'
			)
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
