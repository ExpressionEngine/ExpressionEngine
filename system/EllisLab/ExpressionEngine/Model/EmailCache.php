<?php

namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class EmailCache extends Model
{
	protected static $_primary_key = 'cache_id';
	protected static $_gateway_names = array('EmailCacheGateway');

	protected static $_relationships = array(
		'MemberGroups' => array(
			'type' => 'many_to_many',
			'model' => 'MemberGroup'
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

}
