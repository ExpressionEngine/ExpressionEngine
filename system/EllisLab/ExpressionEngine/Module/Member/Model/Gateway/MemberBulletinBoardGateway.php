<?php

namespace EllisLab\ExpressionEngine\Module\Member\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class MemberBuilletinBoardGateway extends Gateway {

	protected static $_primary_key = 'bulletin_id';
	protected static $_table_name = 'member_bulletin_board';

	public static $_related_gateways = array(
		'sender_id' => array(
			'gateway' => 'MemberGateway',
			'key' => 'member_id'
		)
	);

	public $bulletin_id;
	public $sender_id;
	public $bulletin_group;
	public $bulletin_date;
	public $hash;
	public $bulletin_expires;
	public $bulletin_message;
}
