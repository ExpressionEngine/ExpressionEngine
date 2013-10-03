<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

/**
 * Email Cache
 *
 * We store all email messages that are sent from the CP
 */
class EmailCacheEntity extends Entity {
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
