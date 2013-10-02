<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class ActionEntity extends Entity {
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
