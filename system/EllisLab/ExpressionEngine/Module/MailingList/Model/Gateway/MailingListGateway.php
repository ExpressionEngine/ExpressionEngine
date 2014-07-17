<?php
namespace EllisLab\ExpressionEngine\Module\MailingList\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class MailingListGateway extends RowDataGateway
{
	protected static $_primary_key = 'list_id';
	protected static $_table_name = 'mailing_lists';

	protected static $_related_gateways = array(
		'list_id' => array(
			'gateway' => 'MailingListUserGateway',
			'key' => 'list_id'
		)
	);

	protected $list_id;
	protected $list_name;
	protected $list_title;
	protected $list_template;

}
