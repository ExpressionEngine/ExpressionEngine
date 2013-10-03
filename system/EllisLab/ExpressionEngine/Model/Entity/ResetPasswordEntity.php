<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class ResetPasswordEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'reset_password',
		'primary_key' => 'reset_id',
		'related_entities' => array(
			'member_id' => array(
				'entity' => 'MemberEntity',
				'key' => 'member_id'
			)
		)
	);

	// Properties
	public $reset_id;
	public $member_id;
	public $resetcode;
	public $date;
}
