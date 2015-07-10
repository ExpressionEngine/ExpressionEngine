<?php

namespace EllisLab\ExpressionEngine\Model\Site\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Site Table
 *
 * @package		ExpressionEngine
 * @subpackage	Site\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class SiteGateway extends Gateway {

	protected static $_table_name = 'sites';
	protected static $_primary_key = 'site_id';

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

	public function compress(array $preferences)
	{
		return base64_encode(serialize($preferences));
	}

	public function decompress($preferences)
	{
		return unserialize(base64_decode($preferences));
	}

	public function getSiteSystemPreferences()
	{
		return $this->decompress($this->site_system_preferences);
	}

	public function setSiteSystemPreferences(array $site_system_preferences)
	{
		$this->site_system_preferences = $this->compress($site_system_preferences);
		return $this;
	}

	public function getSiteMailinglistPreferences()
	{
		return $this->decompress($this->site_mailinglist_preferences);
	}

	public function setSiteMailinglistPreferences(array $site_mailinglist_preferences)
	{
		$this->site_mailinglist_preferences = $this->compress($site_mailinglist_preferences);
		return $this;
	}

	public function getSiteMemberPreferences()
	{
		return $this->decompress($this->site_member_preferences);
	}

	public function setSiteMemberPreferences(array $site_member_preferences)
	{
		$this->site_member_preferences = $this->compress($site_member_preferences);
		return $this;
	}


	public function getSiteTemplatePreferences()
	{
		return $this->decompress($this->site_template_preferences);
	}

	public function setSiteTemplatePreferences(array $site_template_preferences)
	{
		$this->site_template_preferences = $this->compress($site_template_preferences);
		return $this;
	}

	public function getSiteChannelPreferences()
	{
		return $this->decompress($this->site_channel_preferences);
	}

	public function setSiteChannelPreferences(array $site_channel_preferences)
	{
		$this->site_channel_preferences = $this->compress($site_channel_preferences);
		return $this;
	}

	public function getSiteBootstrapChecksums()
	{
		return $this->decompress($this->site_bootstrap_checksums);
	}

	public function setSiteBootstrapChecksums(array $site_bootstrap_checksums)
	{
		$this->site_bootstrap_checksums = $this->compress($site_bootstrap_checksums);
		return $this;
	}


}
