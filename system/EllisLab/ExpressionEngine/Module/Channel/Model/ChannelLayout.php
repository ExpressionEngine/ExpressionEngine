<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Model\Content\Display\LayoutInterface;

class ChannelLayout extends Model implements LayoutInterface {

	protected static $_primary_key = 'layout_id';
	protected static $_table_name = 'layout_publish';

	protected static $_relationships = array(
		'Channel' => array(
			'type' => 'belongsTo',
			'key' => 'channel_id'
		),
		'MemberGroup' => array(
			'type' => 'belongsTo',
			'from_key' 	=> 'member_group'
		),
	);

	protected static $_validation_rules = array(
		'site_id'      => 'required|isNatural',
		'member_group' => 'required|isNatural',
		'channel_id'   => 'required|isNatural',
		'layout_name'  => 'required',
	);

	protected $layout_id;
	protected $site_id;
	protected $member_group;
	protected $channel_id;
	protected $layout_name;
	protected $field_layout;

	public function set__field_layout($field_layout)
	{
		$this->field_layout = serialize($field_layout);
	}

	public function get__field_layout()
	{
		return unserialize($this->field_layout);
	}

	public function transform(array $fields)
	{

	}

}