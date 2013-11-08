<?php
namespace EllisLab\ExpressionEngine\Module\Member\Model;

use EllisLab\ExpressionEngine\Model\Model;

class MemberGroup extends Model {
	protected static $meta = array(
		'primary_key' => 'group_id',
		'entity_names' => array('MemberGroupEntity'),
	);

}
