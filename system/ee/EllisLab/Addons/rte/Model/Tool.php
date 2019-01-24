<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\Rte\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Tool Model for the Rich Text Editor
 *
 * A model representing a tool in the Rich Text Editor.
 */
class Tool extends Model {

	protected static $_primary_key = 'tool_id';
	protected static $_table_name = 'rte_tools';

	protected $tool_id;
	protected $name;
	protected $class;
	protected $enabled;
}

// EOF
