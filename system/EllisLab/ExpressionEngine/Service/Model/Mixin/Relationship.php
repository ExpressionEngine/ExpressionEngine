<?php

namespace EllisLab\ExpressionEngine\Service\Model\Mixin;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin;
use EllisLab\ExpressionEngine\Service\Model\Association\Association;

class Relationship implements Mixin {

	protected $scope;
	protected $associations = array();

	public function __construct($scope, $manager)
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
	 * Intercept calls to get<AssociationName>()
	 */
	public function getAssociationActionFromMethod($method)
	{
		$actions = 'has|get|set|add|remove|create|delete|fill';

		if (preg_match("/^({$actions})(.+)/", $method, $matches))
		{
			list($_, $action, $name) = $matches;

			return $this->getAssociationAction($name, $action);
		}

		return FALSE;
	}

	/**
	 *
	 */
	public function getAssociationAction($name, $action)
	{
		if ($this->hasAssociation($name))
		{
			$assoc = $this->getAssociation($name);
			return array($assoc, $action);
		}
	}

	/**
	 *
	 */
	protected function runAssociationAction($action, $args)
	{
		$result = call_user_func_array($action, $args);

		if ($action[1] == 'has' || $action[1] == 'get')
		{
			return $result;
		}

		return $this;
	}

	/**
	 * Get all associations
	 *
	 * @return array associations
	 */
	public function getAllAssociations()
	{
		return $this->associations;
	}

	/**
	 * Check if an association of a given name exists
	 *
	 * @param String $name Name of the association
	 * @return bool has association?
	 */
	public function hasAssociation($name)
	{
		return array_key_exists($name, $this->associations);
	}

	/**
	 * Get an association of a given name
	 *
	 * @param String $name Name of the association
	 * @return Mixed the association
	 */
	public function getAssociation($name)
	{
		return $this->associations[$name];
	}

	/**
	 * Set a given association
	 *
	 * @param String $name Name of the association
	 * @param Association $association Association to set
	 * @return $this;
	 */
	public function setAssociation($name, Association $association)
	{
		$association->setFrontend($this->scope->getFrontend());

		$this->associations[$name] = $association;

		return $this->scope;
	}
}