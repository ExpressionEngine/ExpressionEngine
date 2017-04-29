<?php

namespace EllisLab\ExpressionEngine\Model\Log;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Developer Log Model
 *
 * @package		ExpressionEngine
 * @subpackage	Log
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class DeveloperLog extends Model {

	protected static $_primary_key = 'log_id';
	protected static $_table_name = 'developer_log';

	protected static $_relationships = array(
		'Template' => array(
			'type' => 'belongsTo'
		)
	);

	protected $log_id;
	protected $timestamp;
	protected $viewed;
	protected $description;
	protected $function;
	protected $line;
	protected $file;
	protected $deprecated_since;
	protected $use_instead;
	protected $template_id;
	protected $template_name;
	protected $template_group;
	protected $addon_module;
	protected $addon_method;
	protected $snippets;
	protected $hash;

}

// EOF
