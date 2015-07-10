<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class ChannelFieldGateway extends Gateway {

	protected static $_table_name		= 'channel_fields';
	protected static $_primary_key		= 'field_id';
	protected static $_related_gateways	= array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key'	 => 'site_id',
		),
		'group_id' => array(
			'gateway' => 'FieldGroupGateway',
			'key'	 => 'group_id',
		)
	);

	// Properties
	public $field_id;
	public $site_id;
	public $group_id;
	public $field_name;
	public $field_label;
	public $field_instructions;
	public $field_type;
	public $field_list_items;
	public $field_pre_populate;
	public $field_pre_channel_id;
	public $field_pre_field_id;
	public $field_ta_rows;
	public $field_maxl;
	public $field_required;
	public $field_text_direction;
	public $field_search;
	public $field_is_hidden;
	public $field_fmt;
	public $field_show_fmt;
	public $field_order;
	public $field_content_type;
	public $field_settings;

}
