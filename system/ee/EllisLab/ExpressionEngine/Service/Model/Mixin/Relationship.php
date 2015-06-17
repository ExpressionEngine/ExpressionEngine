<?php

namespace EllisLab\ExpressionEngine\Service\Model\Mixin;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin;
use EllisLab\ExpressionEngine\Service\Model\Association\Association;

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
 * ExpressionEngine Model Relationship Mixin
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
			return array($assoc, $action);
		}
	}

	/**
	 * Run an association action
	 *
	 * @param Callable $action Runable association action
	 * @param Mixed $args Additional arguments to pass to the action
	 * @return Action result or current scope
	 */
	public function runAssociationAction($action, $args)
	{
		$result = call_user_func_array($action, $args);

		if ($action[1] == 'has' || $action[1] == 'get' || $action[1] == 'create')
		{
			return $result;
		}

		return $this->scope;
	}
}
