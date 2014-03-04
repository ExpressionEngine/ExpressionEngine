<?PHP
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Model\Model;

class Site extends Model {
	protected static $_meta = array(
		'primary_key' => 'site_id',
		'gateway_names' => array('SiteGateway'),
		'key_map' => array(
			'site_id' => 'SiteGateway'
		)
	);

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
