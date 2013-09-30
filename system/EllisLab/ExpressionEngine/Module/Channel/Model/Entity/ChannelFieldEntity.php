<?php
namespace EllisLab\ExpressionEngine\Module\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class ChannelFieldEntity extends Entity {
	protected static $meta = array(
		'table_name'		=> 'channel_fields',
		'primary_key'		=> 'field_id',
		'related_entities'	=> array(
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key'	 => 'site_id',
			),
			'group_id' => array(
				'entity' => 'FieldGroupEntity',
				'key'	 => 'group_id',
			)
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
