<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

/**
 * Email Cache
 *
 * We store all email messages that are sent from the CP
 */
class EmailCacheGateway extends RowDataGateway {

	protected static $_table_name = 'email_cache';
	protected static $_primary_key = 'cache_id';
	protected static $_related_gateways = array(
		'cache_id' => array(
			'gateway' => 'MailingListGateway',
			'key' => 'list_id',
			'pivot_table' => 'email_cache_ml',
			'pivot_key' => 'cache_id',
			'pivot_foreign_key' => 'list_id'
		),
		'cache_id' => array(
			'gateway' => 'MemberGroupGateway',
			'key' => 'group_id',
			'pivot_table' => 'email_cache_mg',
			'pivot_key' => 'cache_id',
			'pivot_foreign_key' => 'group_id'
		)
	);

	// Properties
	public $cache_id;
	public $cache_date;
	public $total_sent;
	public $from_name;
	public $from_email;
	public $recipient;
	public $cc;
	public $bcc;
	public $recipient_array;
	public $subject;
	public $message;
	public $plaintext_alt;
	public $mailinglist;
	public $mailtype;
	public $text_fmt;
	public $wordwrap;
	public $priority;

}
