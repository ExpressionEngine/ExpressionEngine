<?php

namespace EllisLab\ExpressionEngine\Module\MailingList\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class MailingListQueueGateway extends Gateway {

	protected static $_primary_key = 'queue_id';
	protected static $_table_name = 'mailing_list_queue';

	protected static $_related_gateways = array(
		'list_id' => array(
			'gateway' => 'MailingListGateway',
			'key' => 'list_id'
		)
	);

	public $queue_id;
	public $email;
	public $list_id;
	public $authcode;
	public $date;
}
