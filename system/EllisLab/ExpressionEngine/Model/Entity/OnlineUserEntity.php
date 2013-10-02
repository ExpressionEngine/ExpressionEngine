<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\Expressionengine\Model\Entity\Entity as Entity;

/**
 *
 */
class OnlineUserEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'online_users',
		'primary_key' => 'online_id',
		'related_entities' => array(
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key' => 'site_id'
			),
			'member_id' => array(
				'entity' => 'MemberEntity',
				'key' => 'member_id'
			)
		)
	);

	// Properties
	public $online_id;
	public $site_id;
	public $member_id;
	public $in_forum;
	public $name;
	public $ip_address;
	public $date;
	public $anon;
}
