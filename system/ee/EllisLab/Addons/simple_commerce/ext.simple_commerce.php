<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Simple Commerce extension
 */
class Simple_commerce_ext
{
	public function __construct()
	{
		$this->version = ee('Addon')->get('simple_commerce')->getVersion();
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
		if ($purchases = $member->getAssociation('simple_commerce:Purchase')->get())
		{
			foreach ($purchases as $purchase)
			{
				if ($paypal_details = @unserialize($purchase->paypal_details))
				{
					$paypal_details['first_name'] = 'redacted';
					$paypal_details['last_name'] = 'redacted';
					$paypal_details['payer_business_name'] = 'redacted';
					$paypal_details['address_name'] = 'redacted';
					$paypal_details['address_street'] = 'redacted';
					$paypal_details['payer_email'] = 'redacted';
					$purchase->paypal_details = serialize($paypal_details);
					$purchase->save();
				}
			}
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
