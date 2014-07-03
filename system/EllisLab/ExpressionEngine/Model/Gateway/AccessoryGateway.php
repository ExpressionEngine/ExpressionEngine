<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class AccessoryGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'accessories',
		'primary_key' => 'accessory_id'
	);

	// Propeties
	public $accessory_id;
	public $class;
	public $member_groups;
	public $controllers;
	public $accessory_version;


}
