<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * Forum extension
 */
class Forum_ext
{
	public function __construct()
	{
		$this->version = ee('Addon')->get('forum')->getVersion();
	}

	/**
	 * Activate extension
	 */
	public function activate_extension()
	{
		$hooks = array(
			'member_anonymize' => 'anonymizeMember'
		);

		foreach ($hooks as $hook => $method)
		{
			ee('Model')->make('Extension', [
				'class'    => __CLASS__,
				'method'   => $method,
				'hook'     => $hook,
				'settings' => [],
				'version'  => $this->version,
				'enabled'  => 'y'
			])->save();
		}
	}

	/**
	 * Clear out personally-idenfitiable member data we may have
	 */
	public function anonymizeMember($member)
	{
		$ip_address = '0.0.0.0';

		if ($posts = $member->getAssociation('forum:Posts')->get())
		{
			$posts->ip_address = $ip_address;
			$posts->save();
		}

		if ($searches = $member->getAssociation('forum:Search')->get())
		{
			$searches->ip_address = $ip_address;
			$searches->save();
		}

		if ($topics = $member->getAssociation('forum:Topic')->get())
		{
			$topics->ip_address = $ip_address;
			$topics->save();
		}
	}

	/**
	 * Disable extension
	 */
	function disable_extension()
	{
		ee('Model')->get('Extension')
			->filter('class', __CLASS__)
			->delete();
	}

	/**
	 * Update extension
	 */
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
	}
}

// EOF
