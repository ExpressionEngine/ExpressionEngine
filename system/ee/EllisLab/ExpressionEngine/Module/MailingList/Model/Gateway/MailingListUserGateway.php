<?php

namespace EllisLab\ExpressionEngine\Module\MailingList\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class MailingListUserGateway extends Gateway {

	protected static $_primary_key = 'user_id';
	protected static $_table_name = 'mailing_list';

	protected static $_related_gateways = array(
		'list_id' => array(
			'gateway' => 'MailingListGateway',
			'key' => 'list_id'
		)
	);

	public $user_id;
	public $list_id;
	public $authcode;
	public $email;
	public $ip_address;


}
