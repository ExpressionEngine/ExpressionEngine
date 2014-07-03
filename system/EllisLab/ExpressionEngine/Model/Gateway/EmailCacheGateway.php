<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

/**
 * Email Cache
 *
 * We store all email messages that are sent from the CP
 */
class EmailCacheGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'email_cache',
		'primary_id' => 'cache_id'
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
