<?php
namespace EllisLab\ExpressionEngine\Model\Addon;

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
 * ExpressionEngine Plugin Model
 *
 * @package		ExpressionEngine
 * @subpackage	Addon
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Plugin extends Model {
	protected static $_primary_key = 'plugin_id';
	protected static $_table_name = 'plugins';

	protected $plugin_id;
	protected $plugin_name;
	protected $plugin_package;
	protected $plugin_version;
	protected $is_typography_related;

	/**
	 * A setter for the enabled property
	 *
	 * @param str|bool $new_value For TRUE or 'y' we enable, for FALSE or 'n' we
	 *   disable
	 * @throws InvalidArgumentException if the provided argument is not a
	 *   boolean or is not 'y' or 'n'.
	 * @return void
	 */
	protected function set__is_typography_related($new_value)
	{
		if ($new_value === TRUE || $new_value == 'y')
		{
			$this->is_typography_related = 'y';
		}

		elseif ($new_value === FALSE || $new_value == 'n')
		{
			$this->is_typography_related = 'n';
		}

		else
		{
			throw new InvalidArgumentException('is_typography_related must be TRUE or "y", or FALSE or "n"');
		}
	}

	/**
	 * A getter for the enabled property
	 *
	 * @return bool TRUE for enabled; FALSE for disabled
	 */
	protected function get__is_typography_related()
	{
		return ($this->is_typography_related == 'y');
	}
}
