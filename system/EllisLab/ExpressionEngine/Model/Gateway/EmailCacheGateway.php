<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

/**
 * Email Cache
 *
 * We store all email messages that are sent from the CP
 */
class EmailCacheGateway extends Gateway {

	protected static $_table_name = 'email_cache';
	protected static $_primary_key = 'cache_id';
	protected static $_related_gateways = array(
		'cache_id' => array(
			'gateway' => 'MemberGroupGateway',
			'key' => 'group_id',
			'pivot_table' => 'email_cache_mg',
			'pivot_key' => 'cache_id',
			'pivot_foreign_key' => 'group_id'
		)
	);

	// Properties
	protected $cache_id;
	protected $cache_date;
	protected $total_sent;
	protected $from_name;
	protected $from_email;
	protected $recipient;
	protected $cc;
	protected $bcc;
	protected $recipient_array;
	protected $subject;
	protected $message;
	protected $plaintext_alt;
	protected $mailtype;
	protected $text_fmt;
	protected $wordwrap;
	protected $attachments;

	public function setRecipientArray(array $recipients)
	{
		$this->recipient_array = serialize($recipients);
		return $this;
	}

	public function getRecipientArray()
	{
		return unserialize($this->recipient_array);
	}

	public function setAttachments(array $attachments)
	{
		$this->attachments = serialize($attachments);
		return $this;
	}

	public function getAttachments()
	{
		return unserialize($this->attachments);
	}
}
