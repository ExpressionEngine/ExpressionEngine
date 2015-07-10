<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class ChannelFieldGroupGateway extends Gateway {
	protected static $_table_name = 'field_groups';
	protected static $_primary_key = 'group_id';
	protected static $_related_gateways = array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key'	 => 'site_id'
		),
		'group_id' => array(
			'gateway' => 'ChannelFieldGateway',
			'key' => 'group_id'
		)
	);

	// Properties
	public $group_id;
	public $site_id;
	public $group_name;
}
