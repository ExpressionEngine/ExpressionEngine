<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;


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
 * ExpressionEngine Extension Gateway
 *
 * A gateway for use in storing data on Extensions installed in the current
 * instance of ExpressionEngine.
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ExtensionGateway extends RowDataGateway {
	protected static $_primary_key = 'extension_id';
	protected static $_table_name = 'extensions';

	protected $extension_id;
	protected $class;
	protected $method;
	protected $hook;
	protected $settings;
	protected $priority;
	protected $version;
	protected $enabled;
}
