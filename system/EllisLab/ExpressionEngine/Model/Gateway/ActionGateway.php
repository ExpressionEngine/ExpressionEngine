<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class ActionGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'actions',
		'primary_key' => 'action_id'
	);

	// Properties
	public $action_id;
	public $class;
	public $method;
	public $csrf_exempt;

}
