<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class SiteGateway extends RowDataGateway {
	protected static $_table_name = 'sites';
	protected static $_primary_key = 'site_id';


	// Properties
	protected $site_id;
	protected $site_label;
	protected $site_name;
	protected $site_description;
	protected $site_system_preferences;
	protected $site_mailinglist_preferences;
	protected $site_member_preferences;
	protected $site_template_preferences;
	protected $site_channel_preferences;
	protected $site_bootstrap_checksums;

}
