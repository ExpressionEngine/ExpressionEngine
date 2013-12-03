<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class SiteGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'sites',
		'primary_key' => 'site_id'
	);


	// Properties
	public $site_id;
	public $site_label;
	public $site_name;
	public $site_description;
	public $site_system_preferences;
	public $site_mailinglist_preferences;
	public $site_member_preferences;
	public $site_template_preferences;
	public $site_channel_preferences;
	public $site_bootstrap_checksums;

}
