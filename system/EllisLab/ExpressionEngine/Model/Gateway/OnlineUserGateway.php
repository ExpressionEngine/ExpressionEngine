<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\Expressionengine\Model\Gateway\RowDataGateway;

/**
 *
 */
class OnlineUserGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'online_users',
		'primary_key' => 'online_id',
		'related_gateways' => array(
			'site_id' => array(
				'gateway' => 'SiteGateway',
				'key' => 'site_id'
			),
			'member_id' => array(
				'gateway' => 'MemberGateway',
				'key' => 'member_id'
			)
		)
	);

	// Properties
	public $online_id;
	public $site_id;
	public $member_id;
	public $in_forum;
	public $name;
	public $ip_address;
	public $date;
	public $anon;
}
