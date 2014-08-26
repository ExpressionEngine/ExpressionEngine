<?php

namespace EllisLab\ExpressionEngine\Core;

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