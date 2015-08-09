<?php

namespace EllisLab\Addons\SimpleCommerce\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Simple Commerce ITem Model
 *
 * @package		ExpressionEngine
 * @subpackage	Moblog Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Item extends Model {

	protected static $_primary_key = 'item_id';
	protected static $_table_name = 'simple_commerce_items';

	protected static $_relationships = array(
		'Purchases' => array(
			'type' => 'hasMany',
			'model' => 'Purchase',
			'to_key' => 'item_id',
			'weak' => TRUE
		),
		'ChannelEntry' => array(
			'type' => 'hasOne',
			'model' => 'ee:ChannelEntry',
			'from_key' => 'entry_id',
			'weak' => TRUE
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
