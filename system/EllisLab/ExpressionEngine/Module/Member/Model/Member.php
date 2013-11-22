<?php
namespace EllisLab\ExpressionEngine\Module\Member\Model;

use EllisLab\ExpressionEngine\Model\Model;

/**
 * Member
 *
 * A member of the website.  Represents the user functionality
 * provided by the Member module.  This is a single user of
 * the website.  
 */
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
		return $this->manyToOne('MemberGroup', 'MemberGroup', 'group_id', 'group_id');
	}

	public function setMemberGroup(MemberGroup $group)
	{
		$this->setRelated('MemberGroup', $group);
		$this->group_id = $group->group_id;
		return $this;
	}

	public function getChannelEntries()
	{
		return $this->oneToMany('ChannelEntries', 'ChannelEntry', 'member_id', 'author_id');
	}

	public function setChannelEntries(array $entries)
	{
		$this->setRelated('ChannelEntries', $entries);
		
		foreach($entries as $entry)
		{
			$entry->author_id = $this->member_id;
		}

		return $this;
	}

}
