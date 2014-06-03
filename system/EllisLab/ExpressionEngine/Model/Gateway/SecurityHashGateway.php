<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;


class SecurityHashGateway extends RowDataGateway {
	protected static $_table_name = 'security_hashes';
	protected static $_primary_key = 'hash_id';

	protected static $_related_gateways = array(
		'session_id' => array(
			'gateway' => 'SessionGateway',
			'key' => 'session_id'
		)
	);

	// Properties
	public $hash_id;
	public $date;
	public $session_id;
	public $hash;
	public $used;

}
