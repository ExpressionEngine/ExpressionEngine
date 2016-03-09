<?php

namespace EllisLab\ExpressionEngine\Model\Addon;

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
 * ExpressionEngine Fieldtype Model
 *
 * @package		ExpressionEngine
 * @subpackage	Addon
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Fieldtype extends Model {

	protected static $_primary_key = 'fieldtype_id';
	protected static $_table_name = 'fieldtypes';

	protected static $_typed_columns = array(
		'has_global_settings' => 'boolString',
		'settings'            => 'base64Serialized',
	);

	protected $fieldtype_id;
	protected $name;
	protected $version;
	protected $settings;
	protected $has_global_settings;

}

// EOF
