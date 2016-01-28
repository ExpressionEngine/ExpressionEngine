<?php

namespace EllisLab\ExpressionEngine\Library\DataStructure\Tree;



/**
 * ExpressionEngine Breadth First Iterator Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core Datastructures
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class BreadthFirstIterator implements \OuterIterator {

	protected $_level;
	protected $_iterator;
	protected $_first_iterator;	// needed for rewind

	protected $_queue = array();

	public function __construct(\RecursiveIterator $it)
	{
		$this->_level = 0;
		$this->_iterator = $it;
		$this->_first_iterator = $it;
	}

	// --------------------------------------------------------------------

	/**
	 * Current Iterator Entry
	 *
	 * @return iterator entry of the current inner iterator
	 */
	public function current()
	{
		return $this->_iterator->current();
	}

	// --------------------------------------------------------------------

	/**
	 * Current Iterator Key
	 *
	 * @return iterator key of the current inner iterator
	 */
	public function key()
	{
		return $this->_iterator->key();
	}

	// --------------------------------------------------------------------

	/**
	 * Next Iterator Step
	 *
	 * Standard level by level iterator using a queue to remember where
	 * the children are.
	 *
	 * @return void
	 */
	public function next()
	{
		if ($this->_iterator->hasChildren())
		{
			$this->_queue[] = array($this->_level + 1, $this->_iterator->getChildren());
		}

		$this->_iterator->next();
	}

	// --------------------------------------------------------------------

	/**
	 * Rewind the Iterator
	 *
	 * All the subiterators are rewound when they're exhausted so we only
	 * have to worry about the current one.
	 *
	 * @return void
	 */
	public function rewind()
	{
		$this->_level = 0;
		$this->_queue = array();

		$this->_iterator->rewind();
		$this->_iterator = $this->_first_iterator;
	}

	// --------------------------------------------------------------------

	/**
	 * Find a valid iterator entry if it exists
	 *
	 * If we have exhausted the current iterator then we need to move on
	 * to the next one on the queue. If they're all exhausted we're out of
	 * entries.
	 *
	 * @return boolean iterator is valid
	 */
	public function valid()
	{
		if ($this->_iterator->valid())
		{
			return TRUE;
		}

		$this->_iterator->rewind(); // we're at the end, @todo this is a little sloppy

		if (count($this->_queue))
		{
			list($this->_level, $this->_iterator) = array_shift($this->_queue);
			return $this->_iterator->valid();
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Get internal iterator
	 *
	 * To the user this iterator is supposed to be mostly transparent.
	 * This is here to satsifty the outeriterator contract. I can't think
	 * of a good reason you would want to use it.
	 *
	 * @return <RecursiveIterator> current sub iterator
	 */
	public function getInnerIterator()
	{
		return $this->_iterator();
	}

	// --------------------------------------------------------------------

	/**
	 * Get iteration depth
	 *
	 * Retrieve the per level depth of the iterator. Using the same method
	 * contract as RecursiveIteratorIterator for consistency.
	 *
	 * @return Integer iteration depth
	 */
	public function getDepth()
	{
		return $this->_level;
	}
}

// EOF
