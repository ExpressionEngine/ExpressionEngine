<?php
namespace EllisLab\ExpressionEngine\Module\Member\Model;

use EllisLab\ExpressionEngine\Model\Model;

class MemberGroup extends Model {
	protected static $meta = array(
		'primary_key' => 'group_id',
		'entity_names' => array('MemberGroupEntity'),
	);

	public function getMembers()
	{
		return $this->oneToMany('Members', 'Member', 'group_id', 'group_id');
	}

	public function setMembers(array $members)
	{
		$this->setRelated('Members', $members);
		foreach($members as $member)
		{
			$member->group_id = $this->group_id;
		}

		return $this;
	}

}
