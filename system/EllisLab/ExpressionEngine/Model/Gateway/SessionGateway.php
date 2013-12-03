<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class SessionGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'sessions',
		'primary_key' => 'session_id', 
		'related_gateways' => array(
			'member_id' => array(
				'gateway' => 'member_id',
				'key' => 'member_id',
			)
		)
	);


	public $session_id;
	public $member_id;
	public $admin_sess;
	public $ip_address;
	public $user_agent;
	public $fingerprint;
	public $sess_start;
	public $last_activity;
}
