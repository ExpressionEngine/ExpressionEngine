<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Model\Mixin;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin;
use EllisLab\ExpressionEngine\Service\Model\Association\Association;

/**
 * This is NOT the class you're looking for. Just some leftover dust.
 */

/**
 * Model Service Relationship Mixin
 */
class Relationship implements Mixin {

	/**
	 * @var Parent scope
	 */
	protected $scope;

	/**
	 * @var List of association objects
	 */
	protected $associations = array();

	/**
	 * @param Object $scope Current scope
	 */
	public function __construct($scope)
	{
		$this->scope = $scope;
	}

	/**
	 * Get the mixin name
	 */
	public function getName()
	{
		return 'Model:Relationship';
	}

	/**
	 * Helper for __call to extract the association name and action
	 * from the <action><AssociationName>() method.
	 *
	 * @param String $method Called method
	 * @return Callable Association action, if it exists
	 */
	public function getAssociationActionFromMethod($method)
	{
		$actions = 'has|get|set|add|remove|create|delete|fill';

		if (preg_match("/^({$actions})(.+)/", $method, $matches))
		{
			list($_, $action, $name) = $matches;

			return $this->getAssociationAction($name, $action);
		}

		return NULL;
	}

	/**
	 * Get an association action callback
	 *
	 * @param String $name Association name
	 * @param String $action Action to run
	 * @return Callable Association action
	 */
	public function getAssociationAction($name, $action)
	{
		if ($this->scope->hasAssociation($name))
		{
			$assoc = $this->scope->getAssociation($name);
			return array($assoc, $name, $action);
		}
	}

	/**
	 * Run an association action
	 *
	 * @param Callable $action Runable association action
	 * @param Mixed $args Additional arguments to pass to the action
	 * @return Action result or current scope
	 */
	public function runAssociationAction($which, $args)
	{
		list($assoc, $name, $action) = $which;

		switch ($action)
		{
			case 'get':
				return $this->scope->$name;
			case 'fill':
				return $assoc->fill($args[0]);
			case 'set':
				$this->scope->$name = $args[0];
				return $this->scope;
			case 'add':
				$which = $this->scope->$name;
				$which[] = $args[0];
				return $this->scope;
			case 'remove':
				return call_user_func_array(array($assoc, 'remove'), $args);
			case 'create':
				throw new \Exception('Can no longer create relationships, just create the model directly and assign.');
			case 'delete':
				throw new \Exception('Can no longer delete relationships, just delete the model directly.');
		}

		throw new \Exception('Illegal Relationship action: '.$action);
	}
}

// EOF
