<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class SecurityHashEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'security_hashes',
		'primary_key' => 'hash_id',
		'related_entities' => array(
			'session_id' => array(
				'entity' => 'SessionEntity',
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
