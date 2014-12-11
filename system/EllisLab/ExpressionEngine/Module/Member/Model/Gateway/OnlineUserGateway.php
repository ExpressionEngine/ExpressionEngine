<?php

namespace EllisLab\ExpressionEngine\Module\Member\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class OnlineUserGateway extends Gateway {

	protected static $_primary_key = 'online_id';
	protected static $_table_name = 'online_users';

	protected static $_related_gateways = array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key' => 'site_id'
		),
		'member_id' => array(
			'gateway' => 'MemberGateway',
			'key' => 'member_id'
		)
	);


	// properties
	public $online_id;
	public $site_id;
	public $member_id;
	public $in_forum;
	public $name;
	public $ip_address;
	public $date;
	public $anon;
}
