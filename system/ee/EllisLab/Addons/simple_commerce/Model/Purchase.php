<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\SimpleCommerce\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Simple Commerce Purchase Model
 */
class Purchase extends Model {

	protected static $_primary_key = 'purchase_id';
	protected static $_table_name = 'simple_commerce_purchases';

	protected static $_events = array(
		'afterDelete',
		'afterInsert',
		'afterUpdate'
	);

	protected static $_validation_rules = array(
		'txn_id'        => 'required',
		'item_id'       => 'required',
		'member_id'     => 'required|validateScreenName',
		'purchase_date' => 'required|integer',
		'item_cost'     => 'required|numeric'
	);

	protected static $_relationships = array(
		'Item' => array(
			'type' => 'belongsTo',
			'model' => 'Item',
			'from_key' => 'item_id',
			'weak' => TRUE
		),
		'Member' => array(
			'type' => 'belongsTo',
			'model' => 'ee:Member',
			'from_key' => 'member_id',
			'weak' => TRUE,
			'inverse' => array(
				'name' => 'Purchase',
				'type' => 'hasMany'
			)
		)
	);

	protected $purchase_id;
	protected $txn_id;
	protected $member_id;
	protected $paypal_subscriber_id;
	protected $item_id;
	protected $purchase_date;
	protected $item_cost;
	protected $paypal_details;
	protected $subscription_end_date;

	public function onAfterDelete()
	{
		$this->updateItemCounts($this->item_id);
	}

	public function onAfterInsert()
	{
		$this->updateItemCounts($this->item_id);
	}

	public function onAfterUpdate($previous)
	{
		if (isset($previous['item_id']))
		{
			$this->updateItemCounts($previous['item_id']);
			$this->updateItemCounts($this->item_id);
		}
	}

	/**
	 * Items keep count of how many purchases and subscriptions they have; when a Purchase is
	 * added, deleted, or had its Item change, we need to update the Item's purchase count
	 */
	protected function updateItemCounts($item_id)
	{
		$item = $this->getFrontend()->get('simple_commerce:Item', $item_id)->first();

		$item->item_purchases = $this->getFrontend()
			->get('simple_commerce:Purchase')
			->filter('item_id', $item_id)
			->count();

		$item->current_subscriptions = $this->getFrontend()
			->get('simple_commerce:Purchase')
			->filter('item_id', $item_id)
			->filter('subscription_end_date', 0)
			->filter('paypal_subscriber_id', '!=', 'NULL')
			->count();

		$item->save();
	}

	public function set__purchase_date($purchase_date)
	{
		$this->setRawProperty('purchase_date', ee()->localize->string_to_timestamp($purchase_date));
	}

	/**
	 * Makes sure a valid member is set for the Purchase Member
	 */
	public function validateScreenName($key, $value, $parameters, $rule)
	{
		// They probably passed a screen name, get a member ID for it
		if ( ! is_numeric($value))
		{
			$member = ee('Model')->get('Member')->filter('screen_name', $value);

			// Since we allow duplicate screen names now,
			if ($member->count() > 1 OR $member->count() == 0)
			{
				// Try to find via username
				$member_by_user = ee('Model')->get('Member')->filter('username', $value);

				// Still nothing? Invalidate
				if ($member_by_user->count() == 0)
				{
					if ($member->count() > 1)
					{
						return 'multiple_members_found';
					}
					elseif ($member->count() == 0)
					{
						return 'member_not_found';
					}
				}
			}

			// Got here? Set the member_id
			$this->setRawProperty('member_id', $member->first()->getId());
		}
		else
		{
			if (ee('Model')->get('Member', $value)->count() == 0)
			{
				return 'member_not_found';
			}
		}

		return TRUE;
	}
}

// EOF
