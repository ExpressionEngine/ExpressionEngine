<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class SessionGateway extends RowDataGateway {
	protected static $_table_name = 'sessions';
	protected static $_primary_key = 'session_id';
	protected static $_related_gateways = array(
		'member_id' => array(
			'gateway' => 'member_id',
			'key' => 'member_id',
		)
	);


	protected $session_id;
	protected $member_id;
	protected $admin_sess;
	protected $ip_address;
	protected $user_agent;
	protected $fingerprint;
	protected $sess_start;
	protected $last_activity;
}
