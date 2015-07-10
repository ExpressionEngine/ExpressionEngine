<?php
namespace EllisLab\ExpressionEngine\Model\Addon;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
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

	protected static $_typed_columns = array(
		'is_typography_related' => 'boolString'
	);

	protected $plugin_id;
	protected $plugin_name;
	protected $plugin_package;
	protected $plugin_version;
	protected $is_typography_related;

}
