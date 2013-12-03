<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class SecurityHashGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'security_hashes',
		'primary_key' => 'hash_id',
		'related_gateways' => array(
			'session_id' => array(
				'gateway' => 'SessionGateway',
				'key' => 'session_id'
			)
		)
	);

	// Properties
	public $hash_id;
	public $date;
	public $session_id;
	public $hash;
	public $used;

}
