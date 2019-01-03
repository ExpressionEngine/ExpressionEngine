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
 * Simple Commerce Item Model
 */
class Item extends Model {

	protected static $_primary_key = 'item_id';
	protected static $_table_name = 'simple_commerce_items';

	protected static $_typed_columns = array(
		'item_enabled' => 'boolString',
		'item_use_sale' => 'boolString',
		'recurring' => 'boolString',
	);

	protected static $_validation_rules = array(
		'item_enabled'                        => 'enum[y,n]',
		'item_regular_price'                  => 'numeric',
		'item_sale_price'                     => 'numeric',
		'item_use_sale'                       => 'enum[y,n]',
		'recurring'                           => 'enum[y,n]',
		'subscription_frequency'              => 'isNaturalNoZero',
		'subscription_frequency_unit'         => 'enum[day,week,month,year]',
		'item_purchases'                      => 'isNatural',
		'current_subscriptions'               => 'isNatural',
		'new_member_group'                    => 'isNatural',
		'member_group_unsubscribe'            => 'isNatural',
		'admin_email_address'                 => 'email',
		'admin_email_template'                => 'isNatural',
		'customer_email_template'             => 'isNatural',
		'admin_email_template_unsubscribe'    => 'isNatural',
		'customer_email_template_unsubscribe' => 'isNatural'
	);

	protected static $_relationships = array(
		'Purchases' => array(
			'type' => 'hasMany',
			'model' => 'Purchase',
			'to_key' => 'item_id',
			'weak' => TRUE
		),
		'ChannelEntry' => array(
			'type' => 'belongsTo',
			'model' => 'ee:ChannelEntry',
			'from_key' => 'entry_id',
			'weak' => TRUE,
			'inverse' => array(
				'name' => 'Item',
				'type' => 'hasMany'
			)
		)
	);

	protected $item_id;
	protected $entry_id;
	protected $item_enabled;
	protected $item_regular_price;
	protected $item_sale_price;
	protected $item_use_sale;
	protected $recurring;
	protected $subscription_frequency;
	protected $subscription_frequency_unit;
	protected $item_purchases;
	protected $current_subscriptions;
	protected $new_member_group;
	protected $member_group_unsubscribe;
	protected $admin_email_address;
	protected $admin_email_template;
	protected $customer_email_template;
	protected $admin_email_template_unsubscribe;
	protected $customer_email_template_unsubscribe;
}

// EOF
