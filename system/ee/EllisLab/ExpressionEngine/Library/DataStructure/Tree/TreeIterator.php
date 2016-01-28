<?php

namespace EllisLab\ExpressionEngine\Library\DataStructure\Tree;



/**
 * ExpressionEngine Tree Iterator Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core Datastructures
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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

	// --------------------------------------------------------------------

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
