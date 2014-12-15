<?php

namespace EllisLab\ExpressionEngine\Module\Member\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\FieldDataGateway as FieldDataGateway;

/**
 * Member Data
 * Stores the actual data
 */
class MemberDataGateway extends FieldDataGateway {

	protected static $meta = array(
		'table_name' => 'member_data',
		'primary_id' => 'member_id'
	);

	// Propeties
	public $member_id;
}
