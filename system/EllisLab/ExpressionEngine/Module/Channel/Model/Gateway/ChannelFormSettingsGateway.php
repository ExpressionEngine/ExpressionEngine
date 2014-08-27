<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway\RowDataGateway;

class ChannelFormSettingsGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'channel_form_settings',
		'primary_key' => 'channel_form_settings_id',
		'related_gateways' => array(
			'site_id' => array(
				'gateway' => 'SiteGateway',
				'key' => 'site_id'
			),
			'channel_id' => array(
				'gateway' => 'ChannelGateway',
				'key' => 'channel_id'
			),
			'default_author' => array(
				'gateway' => 'MemberGateway',
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
