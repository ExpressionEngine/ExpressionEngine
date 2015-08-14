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
 * ExpressionEngine Simple Commerce Purchase Model
 *
 * @package		ExpressionEngine
 * @subpackage	Moblog Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Purchase extends Model {

	protected static $_primary_key = 'purchase_id';
	protected static $_table_name = 'simple_commerce_purchases';

	protected static $_validation_rules = array(
		'txn_id'        => 'required',
		'item_id'       => 'required',
		//'member_id'     => 'required',
		'purchase_date' => 'required|integer',
		'item_cost'     => 'required|numeric'
	);

	protected static $_relationships = array(
		'Item' => array(
			'type' => 'hasOne',
			'model' => 'Item',
			'from_key' => 'item_id',
			'weak' => TRUE
		),
		'Member' => array(
			'type' => 'hasOne',
			'model' => 'ee:Member',
			'from_key' => 'member_id',
			'weak' => TRUE
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

	public function set__purchase_date($purchase_date)
	{
		$this->setRawProperty('purchase_date', ee()->localize->string_to_timestamp($purchase_date));
	}
}
