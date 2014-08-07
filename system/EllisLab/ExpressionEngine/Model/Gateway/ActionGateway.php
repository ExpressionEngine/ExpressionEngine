<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class ActionGateway extends RowDataGateway {
	protected static $_table_name = 'actions';
	protected static $_primary_key = 'action_id';

	// Properties
	protected $action_id;
	protected $class;
	protected $method;
	protected $csrf_exempt;

}
