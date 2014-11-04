<?php
namespace EllisLab\ExpressionEngine\Service;

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
 * ExpressionEngine Alias Service Interface
 *
 * Provides an interface for use by alias services.
 *
 * @package		ExpressionEngine
 * @subpackage	Error
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
interface AliasServiceInterface {

	/**
	 * Register a class under a given alias.
	 *
	 * @param String $alias  Name to use when interacting with the service
	 * @param String $fully_qualified_name  Fully qualified class name of the aliased class
	 * @return void
	 */
	public function registerClass($class_name, $fully_qualified_name);

	/**
	 * Get an alias's full qualified name.
	 *
	 * @param String $name Name of the class
	 * @return String Fully qualified name of the class
	 */
	public function getRegisteredClass($class_name);
}
