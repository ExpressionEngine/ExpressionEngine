<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

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
 * ExpressionEngine Module Gateway
 *
 * A gateway to store information on what addon modules are installed in this
 * instance of ExpressionEngine.
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ModuleGateway extends RowDataGateway {
	protected static $_table_name = 'modules';
	protected static $_primary_key = 'module_id';

	protected $module_id;
	protected $module_name;
	protected $module_version;
	protected $has_cp_backend;
	protected $has_publish_fields;
}
