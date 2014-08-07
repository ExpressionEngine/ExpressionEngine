<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class AccessoryGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'accessories',
		'primary_key' => 'accessory_id'
	);

	// Propeties
	protected $accessory_id;
	protected $class;
	protected $member_groups;
	protected $controllers;
	protected $accessory_version;


}
