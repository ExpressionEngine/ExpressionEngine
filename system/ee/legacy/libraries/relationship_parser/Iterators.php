<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Parse Node Iteratior
 *
 * Does not go the into query node's children.
 */
class ParseNodeIterator extends EE_TreeIterator {

	public function hasChildren()
	{
		if ( ! parent::hasChildren())
		{
			return FALSE;
		}

		$current = $this->current();
		$children = $current->children();

		foreach ($children as $kid)
		{
			if ( ! $kid instanceOf QueryNode)
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Override RecursiveArrayIterator's get child method to make sure
	 * we skip any QueryNodes and their descendants.
	 *
	 * @return Object<TreeIterator>
	 */
	public function getChildren()
	{
		$current = $this->current();
		$children = array();

		foreach ($current->children() as $kid)
		{
			if ( ! $kid instanceOf QueryNode)
			{
				$children[] = $kid;
			}
		}

		// Using ref as per PHP source
		if (empty($this->ref))
		{
			$this->ref = new ReflectionClass($this);
		}

		return $this->ref->newInstance($children);
	}
}
// END CLASS

/**
 * ExpressionEngine Query Node Iteratior
 *
 * Iterates all of the tree's query nodes even if there are parse
 * nodes in between.
 */
class QueryNodeIterator extends EE_TreeIterator {

	/**
	 * Override RecursiveArrayIterator's child detection method.
	 * We usually have data rows that are arrays so we really only
	 * want to iterate over those that match our custom format.
	 *
	 * @return boolean
	 */
	public function hasChildren()
	{
		$current = $this->current();

		if ( ! $current instanceOf QueryNode)
		{
			return FALSE;
		}

		$children = $current->closureChildren();

		return ! empty($children);
	}

	/**
	 * Override RecursiveArrayIterator's get child method to skip
	 * ahead into the __children__ array and not try to iterate
	 * over the data row's individual columns.
	 *
	 * @return Object<TreeIterator>
	 */
	public function getChildren()
	{
		$current = $this->current();
		$children = $current->closureChildren();

		// Using ref as per PHP source
		if (empty($this->ref))
		{
			$this->ref = new ReflectionClass($this);
		}

		return $this->ref->newInstance($children);
	}
}
// END CLASS

// EOF
