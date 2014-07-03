<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\FieldDataGateway;

class ChannelDataGateway extends FieldDataGateway {
	protected static $_table_name = 'channel_data';
	protected static $_field_table = 'channel_fields';
	protected static $_field_id_name = 'field_id';
	protected static $_primary_key = 'entry_id';
	protected static $_related_gateways = array(
		'entry_id' => array(
			'gateway' => 'ChannelTitleGateway',
			'key'	 => 'entry_id'
		),
		'channel_id' => array(
			'gateway' => 'ChannelGateway',
			'key'	 => 'channel_id'
		),
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key'	 => 'site_id'
		)
	);

	// Properties
	public $entry_id;
	public $channel_id;
	public $site_id;

}



