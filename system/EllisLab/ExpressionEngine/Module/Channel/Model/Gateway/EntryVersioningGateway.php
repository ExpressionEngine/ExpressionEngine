<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class EntryVersioningGateway extends Gateway {
	protected static $meta = array(
		'table_name' => 'entry_versioning',
		'primary_key' => 'version_id',
		'related_gateways' => array(
			'entry_id' => array(
				'gateway' => 'ChannelEntryGateway',
				'key' => 'entry_id'
			),
			'channel_id' => array(
				'gateway' => 'ChannelGateway',
				'key' => 'channel_id'
			),
			'author_id' => array(
				'gateway' => 'MemberGateway',
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
