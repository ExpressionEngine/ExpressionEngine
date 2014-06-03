<?php
namespace EllisLab\ExpressionEngine\Module\Member\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;


class RememberMeGateway extends RowDataGateway {
	protected static $_table_name = 'remember_me';
	protected static $_primary_key = 'remember_me_id';

	protected static $_related_gateways = array(
		'member_id' => array(
			'gateway' => 'MemberGateway',
			'key' => 'member_id'
		),
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key' => 'site_id'
		)
	);

	public $remember_me_id;
	public $member_id;
	public $ip_address;
	public $user_agent;
	public $admin_sess;
	public $site_id;
	public $expiration;
	public $last_refresh;
}
