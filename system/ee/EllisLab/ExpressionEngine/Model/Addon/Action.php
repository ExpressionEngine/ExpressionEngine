<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Addon;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Action Model
 */
class Action extends Model {

	protected static $_primary_key = 'action_id';
	protected static $_table_name = 'actions';

	protected static $_validation_rules = array(
		'csrf_exempt' => 'enum[0,1]'
	);

	protected $action_id;
	protected $class;
	protected $method;
	protected $csrf_exempt;
}

// EOF
