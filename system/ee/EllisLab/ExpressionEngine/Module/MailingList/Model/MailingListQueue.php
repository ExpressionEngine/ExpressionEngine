<?php

namespace EllisLab\ExpressionEngine\Module\MailingList\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class MailingListQueue extends Model
{
	protected static $_primary_key = 'queue_id';
	protected static $_table_name = 'mailing_list_queue';
	protected static $_relationships = array(
		'MailingList' => array(
			'type' => 'belongsTo'
		)
	);

	protected $queue_id;
	protected $email;
	protected $list_id;
	protected $authcode;
	protected $date;

}
