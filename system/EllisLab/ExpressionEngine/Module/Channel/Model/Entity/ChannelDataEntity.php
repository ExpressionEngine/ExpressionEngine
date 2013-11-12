<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\FieldDataEntity;

class ChannelDataEntity extends FieldDataEntity {
	protected static $meta = array(
		'table_name' => 'channel_data',
		'field_table' => 'channel_fields',
		'field_id_name' => 'field_id',
		'primary_key' => 'entry_id',
		'related_entities' => array(
			'entry_id' => array(
				'entity' => 'ChannelTitleEntity',
				'key'	 => 'entry_id'
			),
			'channel_id' => array(
				'entity' => 'ChannelEntity',
				'key'	 => 'channel_id'
			),
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key'	 => 'site_id'
			)
		)
	);

	// Properties
	public $entry_id;
	public $channel_id;
	public $site_id;

}



