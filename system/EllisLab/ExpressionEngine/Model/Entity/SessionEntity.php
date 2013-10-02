<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class SessionEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'sessions',
		'primary_key' => 'session_id', 
		'related_entities' => array(
			'member_id' => array(
				'entity' => 'member_id',
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
