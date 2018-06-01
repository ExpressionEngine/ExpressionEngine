<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Model\Queue;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Consent Model
 */
class Queue extends Model {

	protected static $_primary_key = 'queue_id';
	protected static $_table_name = 'queue';

	protected static $_typed_columns = [
		'step'  => 'int',
		'total' => 'int',
		'data'  => 'base64Serialized'
	];

	// protected static $_relationships = [];
	// protected static $_validation_rules = [];
	// protected static $_events = [];

	// Properties
	protected $queue_id;
	protected $identifier;
	protected $step;
	protected $total;
	protected $data;

}

// EOF
