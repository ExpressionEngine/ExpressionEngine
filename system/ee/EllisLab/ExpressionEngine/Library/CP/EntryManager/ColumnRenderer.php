<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Library\CP\EntryManager;

/**
 * Entry Manager Column Renderer
 */
class ColumnRenderer {

	private $columns = [];

	/**
	 * Constructor
	 *
	 * @param array[ColumnInterface] $columns Array of objects implementing ColumnInterface
	 */
	public function __construct(array $columns)
	{
		$this->columns = $columns;
	}

	/**
	 * Returns an array compatible with the CP/Table service's setColumns() method
	 *
	 * @return array Complete CP/Table service column config
	 */
	public function getTableColumnsConfig()
	{
		$config = [];

		foreach ($this->columns as $column)
		{
			$label = ['label' => $column->getTableColumnLabel()];
			$config[$column->getTableColumnIdentifier()] = $column->getTableColumnConfig() + $label;
		}

		return $config;
	}

	/**
	 * Returns an array of values for a single row for use building a data array
	 * for the CP/Table service's setData method
	 *
	 * @param ChannelEntry Entry object we are basing the row on
	 * @return array[string]
	 */
	public function getRenderedTableRowForEntry($entry)
	{
		return array_map(function($column) use ($entry) {
			return $column->renderTableCell($entry);
		}, $this->columns);
	}
}
