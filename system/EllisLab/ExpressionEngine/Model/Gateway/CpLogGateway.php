<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

class CpLogGateway extends RowDataGateway {
	protected static $_table_name 		= 'cp_log';
	protected static $_primary_key 		= 'id';
	protected static $_related_gateways	= array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key'	 => 'site_id'
		),
		'member_id' => array(
			'gateway' => 'MemberGateway',
			'key'	 => 'member_id'
		),
	);


	protected $id;
	protected $site_id;
	protected $member_id;
	protected $username;
	protected $ip_address;
	protected $act_date;
	protected $action;
}
