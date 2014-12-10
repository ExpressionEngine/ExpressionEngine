<?php

namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class EmailCache extends Model
{
	protected static $_primary_key = 'cache_id';
	protected static $_table_name = 'email_cache';

	protected static $_relationships = array(
		'MemberGroups' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'MemberGroup',
			'pivot' => array(
				'table' => 'email_cache_mg'
			)
		)
	);

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

	public function set__recipient_array($recipients)
	{
		$this->recipient_array = serialize($recipients);
		return $this;
	}

	public function get__recipient_array()
	{
		return unserialize($this->recipient_array);
	}

	public function set__attachments(array $attachments)
	{
		$this->attachments = serialize($attachments);
		return $this;
	}

	public function get__attachments()
	{
		return unserialize($this->attachments);
	}
}
