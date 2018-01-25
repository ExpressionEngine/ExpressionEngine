<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Library\DataStructure\Tree;

/**
 * Tree Iterator
 */
class TreeIterator extends \RecursiveArrayIterator {

	/**
	 * Override RecursiveArrayIterator's child detection method.
	 * We really don't want to count object properties as children.
	 *
	 * @return boolean
	 */
	public function hasChildren()
	{
		return ! $this->current()->isLeaf();
	}

	/**
	 * Override RecursiveArrayIterator's get child method to skip
	 * ahead into the children array and not try to iterate over the
	 * over the public name property.
	 *
	 * @return TreeIterator
	 */
	public function getChildren()
	{
		$children = $this->current()->getChildren();

		// Using ref as per PHP source
		if (empty($this->ref))
		{
			$this->ref = new \ReflectionClass($this);
		}

		return $this->ref->newInstance($children);
	}
}

// EOF
