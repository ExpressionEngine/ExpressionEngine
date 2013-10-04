<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class EntryVersioningEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'entry_versioning',
		'primary_key' => 'version_id',
		'related_entities' => array(
			'entry_id' => array(
				'entity' => 'ChannelEntryEntity',
				'key' => 'entry_id'
			),
			'channel_id' => array(
				'entity' => 'ChannelEntity',
				'key' => 'channel_id'
			),
			'author_id' => array(
				'entity' => 'MemberEntity',
				'key' => 'member_id'
			)
		)
	);

	// Properties
	public $version_id;
	public $entry_id;
	public $channel_id;
	public $author_id;
	public $version_date;
	public $version_data;

}
