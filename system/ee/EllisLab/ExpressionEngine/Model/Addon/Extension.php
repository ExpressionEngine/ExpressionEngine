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

use InvalidArgumentException;
use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Extension Model
 */
class Extension extends Model {

	protected static $_primary_key = 'extension_id';
	protected static $_table_name = 'extensions';

	protected static $_validation_rules = array(
		'csrf_exempt'  => 'enum[y,n]'
	);

	protected static $_typed_columns = array(
		'enabled' => 'boolString'
	);

	protected $extension_id;
	protected $class;
	protected $method;
	protected $hook;
	protected $settings;
	protected $priority;
	protected $version;
	protected $enabled;

	/**
	 * Marks the Extension as enabled
	 */
	public function enable()
	{
		$this->setProperty('enabled', 'y');
	}

	/**
	 * Marks the Extension as disabled
	 */
	public function disable()
	{
		$this->setProperty('enabled', 'n');
	}

	public function set__settings($settings)
	{
		$this->setRawProperty('settings', serialize($settings));
	}

	public function get__settings()
	{
		return unserialize($this->settings);
	}
}

// EOF
