<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class ChannelFormSettingsEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'channel_form_settings',
		'primary_key' => 'channel_form_settings_id',
		'related_entities' => array(
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key' => 'site_id'
			),
			'channel_id' => array(
				'entity' => 'ChannelEntity',
				'key' => 'channel_id'
			),
			'default_author' => array(
				'entity' => 'MemberEntity',
				'key' => 'member_id'
			)
		)
	);


	public $channel_form_settings_id;
	public $site_id;
	public $channel_id;
	public $default_status;
	public $require_captcha;
	public $allow_guest_posts;
	public $default_author;
}
