<?php

namespace EllisLab\ExpressionEngine\Model\Addon;

use InvalidArgumentException;
use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Extension Model
 *
 * @package		ExpressionEngine
 * @subpackage	Addon
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Extension extends Model {

	protected static $_primary_key = 'extension_id';
	protected static $_table_name = 'extensions';

	protected static $_validation_rules = array(
		'csrf_exempt'  => 'enum[y,n]'
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
	 * Reports if the Extension is enabled or not
	 *
	 * @return bool TRUE for enabled; FALSE for disabled
	 */
	public function isEnabled()
	{
		return ($this->enabled == 'y');
	}

	/**
	 * Marks the Extension as enabled
	 */
	public function enable()
	{
		$this->enabled = 'y';
	}

	/**
	 * Marks the Extension as disabled
	 */
	public function disable()
	{
		$this->enabled = 'n';
	}

	/**
	 * A setter for the enabled property
	 *
	 * @param str|bool $new_value For TRUE or 'y' we enable, for FALSE or 'n' we
	 *   disable
	 * @throws InvalidArgumentException if the provided argument is not a
	 *   boolean or is not 'y' or 'n'.
	 * @return void
	 */
	protected function set__enabled($new_value)
	{
		if ($new_value === TRUE || $new_value == 'y')
		{
			return $this->enable();
		}

		if ($new_value === FALSE || $new_value == 'n')
		{
			return $this->disable();
		}

		throw new InvalidArgumentException('enabled must be TRUE or "y", or FALSE or "n"');
	}

	/**
	 * A getter for the enabled property
	 *
	 * @return bool TRUE for enabled; FALSE for disabled
	 */
	protected function get__enabled()
	{
		return $this->isEnabled();
	}
}
