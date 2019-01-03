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
 * Plugin Model
 */
class Plugin extends Model {
	protected static $_primary_key = 'plugin_id';
	protected static $_table_name = 'plugins';

	protected static $_typed_columns = array(
		'is_typography_related' => 'boolString'
	);

	protected $plugin_id;
	protected $plugin_name;
	protected $plugin_package;
	protected $plugin_version;
	protected $is_typography_related;

}

// EOF
