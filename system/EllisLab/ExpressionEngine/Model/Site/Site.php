<?PHP
namespace EllisLab\ExpressionEngine\Model\Site;

use EllisLab\ExpressionEngine\Model\Model;



/**
 * The Site model stores preference sets for each site in this installation
 * of ExpressionEngine.  Each site can have a completely different set of
 * settings and prefereces.
 */
class Site extends Model {
	protected static $_primary_key = 'site_id';
	protected static $_gateway_names = array('SiteGateway');
	protected static $_preferences = array(
		'SystemPreferences' => 'site_system_preferences',
		'MailingListPreferences' => 'site_mailinglist_preferences',
		'MemberPreferences' => 'site_member_preferences',
		'TemplatePreferences' => 'site_template_preferences',
		'ChannelPreferences' => 'site_channel_preferences'
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

	/**
	 *
	 */
	public function getSystemPreferences()
	{
		return $this->getPreferences('SystemPreferences');
	}

	/**
	 *
	 */
	public function getMailingListPreferences()
	{
		return $this->getPreferences('MailingListPreferences');
	}

	/**
	 *
	 */
	public function getMemberPreferences()
	{
		return $this->getPreferences('MemberPreferences');
	}

	/**
	 *
	 */
	public function getTemplatePreferences()
	{
		return $this->getPreferences('TemplatePreferences');
	}

	/**
	 *
	 */
	public function getChannelPreferences()
	{
		return $this->getPreferences('ChannelPreferences');
	}

	/**
	 *
	 */
	protected function getPreferences($name)
	{
		$preferences = self::getMetaData('preferences');
		$field = $preferences[$name];

		if ( ! $this->hasRelated($name)
		{
			$this->setRelated($name, new $name($this->$field));
		}
		return $this->getRelated($name);
	}

	/**
	 *
	 */
	protected function map()
	{
		$preferences = self::getMetaData('preferences');
		foreach($preferences as $preference_set => $field)
		{
			$this->$field = $this->getPreference($preference_set)->getCompressed();
		}

		return parent::map();
	}
}
