<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class ResetPasswordGateway extends RowDataGateway {
	protected static $_table_name = 'reset_password';
	protected static $_primary_key = 'reset_id';
	protected static $_related_gateways = array(
		'member_id' => array(
			'gateway' => 'MemberGateway',
			'key' => 'member_id'
		)
	);

	// Properties
	protected $reset_id;
	protected $member_id;
	protected $resetcode;
	protected $date;
}
