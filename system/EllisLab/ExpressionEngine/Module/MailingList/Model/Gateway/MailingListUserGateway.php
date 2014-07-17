<?php
namespace EllisLab\ExpressionEngine\Module\MailingList\Model\Gateway;

use EllisLab\ExpessionEngine\Model\Gateway\RowDataGateway;

class MailingListUserGateway extends RowDataGateway
{
	protected static $_primary_key = 'user_id';
	protected static $_table_name = 'mailing_list';

	protected static $_related_gateways = array(
		'list_id' => array(
			'gateway' => 'MailingListGateway',
			'key' => 'list_id'
		)
	);

	protected $user_id;
	protected $list_id;
	protected $authcode;
	protected $email;
	protected $ip_address;


}
