<?php

namespace EllisLab\Addons\Rte\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Tool Model for the Rich Text Editor
 *
 * A model representing a tool in the Rich Text Editor.
 *
 * @package		ExpressionEngine
 * @subpackage	Rich Text Editor Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
