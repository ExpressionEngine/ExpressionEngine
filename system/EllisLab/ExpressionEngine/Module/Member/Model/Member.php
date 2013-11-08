<?php
namespace EllisLab\ExpressionEngine\Module\Member\Model;

use EllisLab\ExpressionEngine\Model\Model;

class Member extends Model {
	protected static $meta = array(
		'primary_key' => 'member_id',
		'entity_names' => array('MemberEntity'),
		'key_map' => array(
			'group_id' => 'MemberEntity'
		)
	);


	public function getMemberGroup()
	{
		return $this->manyToOne('MemberGroup', 'group_id');
	}

}
