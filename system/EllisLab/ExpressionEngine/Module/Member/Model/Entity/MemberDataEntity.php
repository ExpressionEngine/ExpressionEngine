<?php
namespace EllisLab\ExpressionEngine\Module\Member\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\FieldDataEntity as FieldDataEntity;

/**
 * Member Data
 * Stores the actual data
 */
class MemberDataEntity extends FieldDataEntity {
	protected static $meta = array(
		'table_name' => 'member_data',
		'primary_id' => 'member_id'
	);

	// Propeties	
	public $member_id;
}
