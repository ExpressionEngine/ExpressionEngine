<?php

namespace EllisLab\ExpressionEngine\Service\Model\Column;

use EllisLab\ExpressionEngine\Library\Data\Collection as CoreCollection;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Collection Column
 *
 * Must be comprised of elements that extend CollectionRow
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class Collection implements Column {

	protected $collection;

	abstract protected function serialize($data);

	abstract protected function unserialize($data);

	abstract protected function newRow($row_data);

	public function fill($db_data)
	{
		$this->collection = new CoreCollection;

		$data = $this->unserialize($db_data);

		foreach ($data as $row_data)
		{
			$row = $this->newRow($row_data);
			$this->addRow($row);
		}
	}

	public function getValue()
	{
		return $this->serialize();
	}

	public function addRow(CollectionRow $row)
	{
		$row->setParentColumn($this);
		$this->collection[spl_object_hash($row)] = $row;
	}

	public function deleteRow(CollectionRow $row)
	{
		unset($this->collection[spl_object_hash($row)]);
	}

}