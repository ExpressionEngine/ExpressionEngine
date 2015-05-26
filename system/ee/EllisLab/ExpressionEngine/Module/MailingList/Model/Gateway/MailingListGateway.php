<?php

namespace EllisLab\ExpressionEngine\Module\MailingList\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class MailingListGateway extends Gateway {

	protected static $_primary_key = 'list_id';
	protected static $_table_name = 'mailing_lists';

	protected static $_related_gateways = array(
		'list_id' => array(
			'gateway' => 'MailingListUserGateway',
			'key' => 'list_id'
		)
	);

	public $list_id;
	public $list_name;
	public $list_title;
	public $list_template;

}
