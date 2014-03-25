<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class ResetPasswordGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'reset_password',
		'primary_key' => 'reset_id',
		'related_gateways' => array(
			'member_id' => array(
				'gateway' => 'MemberGateway',
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
