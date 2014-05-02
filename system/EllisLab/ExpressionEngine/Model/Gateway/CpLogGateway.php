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


	public $id;
	public $site_id;
	public $member_id;
	public $username;
	public $ip_address;
	public $act_date;
	public $action;
}
