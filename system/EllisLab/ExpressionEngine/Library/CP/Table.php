<?php

namespace EllisLab\ExpressionEngine\Library\CP;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * ExpressionEngine Table Class
 *
 * @package		ExpressionEngine
 * @subpackage	Library
 * @category	CP
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Table {

	const COL_TEXT = 1;
	const COL_CHECKBOX = 2;
	const COL_STATUS = 3;
	const COL_TOOLBAR = 4;

	private $columns = array();
	private $config = array();
	private $data = array();

	/**
	 * Config can have these keys:
	 *
	 * 'sort_col' - Name of the column currently sorting
	 * 'sort_dir' - Direction of the sort, 'asc' or 'desc'
	 * 'search' - Search text
	 * 'wrap' - Whether or not to wrap the table in a div that allows overflow scrolling
	 * 'autosort' - Handle sorting automatically, this is good for non-paginated data
	 * 
	 * @param	array 	$config	See above for options
	 */
	public function __construct($config = array())
	{
		$defaults = array(
			'wrap'		=> TRUE,
			'sort_col'	=> NULL,
			'sort_dir'	=> 'asc',
			'search'	=> NULL,
			'autosort'	=> FALSE
		);

		$this->config = array_merge($defaults, $config);
	}

	/**
	 * Convenience method for initializing a Table object with current
	 * sort parameters within an EE controller
	 * 
	 * @param	array 	$columns	Column names and settings
	 * @return  object	New Table object
	 */
	public static function create($config = array())
	{
		$defaults = array(
			'sort_col'	=> ee()->input->get('sort_col'),
			'sort_dir'	=> ee()->input->get('sort_dir'),
			'search'	=> ee()->input->post('search') !== FALSE
				? ee()->input->post('search') : ee()->input->get('search')
		);

		return new Table(array_merge($defaults, $config));
	}

	/**
	 * Set the columns for this table, main argument is an array passed
	 * in like this, for example:
	 *
	 * array(
	 *	    'Table Name',
	 *	    'Records',
	 *	    'Size',
	 *	    'Manage' => array(
	 *	    	'type'	=> Table::COL_TOOLBAR
	 *	    ),
	 *	    array(
	 *	    	'type'	=> Table::COL_CHECKBOX
	 *	    )
	 *	);
	 *
	 * It's an array of column names, with optional settings for each
	 * column. Right now, the current options are
	 *
	 * 	'encode': Whether or not run column contents through htmlspecialchars()
	 *	'sort': Whether or not the column can be sortable
	 *	'type': The type of column, derived from the constants above
	 *
	 * If no column name is needed, just pass an array with your settings
	 * 
	 * @param	array 	$columns	Column names and settings
	 * @return  void
	 */
	public function setColumns($columns = array())
	{
		$this->columns = array();

		// Default settings for columns
		$defaults = array(
			'encode'	=> FALSE,
			'sort'		=> TRUE,
			'type'		=> self::COL_TEXT
		);

		foreach ($columns as $label => $settings)
		{
			// If column has no label, like for a select-all checkbox column
			$empty_label = (is_int($label) && is_array($settings));

			// No column settings, just label
			if (is_int($label) && is_string($settings))
			{
				$label = $settings;
			}

			// Combine desired settings with defaults
			if (is_array($settings))
			{
				$settings = array_merge($defaults, $settings);

				// Only these columns are sortable
				if ($settings['type'] !== self::COL_TEXT &&
					$settings['type'] !== self::COL_STATUS)
				{
					$settings['sort'] = FALSE;
				}
			}
			else
			{
				$settings = $defaults;
			}

			if ($empty_label)
			{
				$this->columns[] = $settings;
			}
			else
			{
				$this->columns[$label] = $settings;
			}
		}
	}

	/**
	 * Set and normalizes the data for this table, main argument is an
	 * array passed in like this, for example:
	 *
	 * 	$data = array(
	 * 		// Row 1
	 * 		array(
	 * 			'col 1 data',
	 * 			'col 2 data',
	 * 			'col 3 data',
	 * 			// COL_TOOLBAR, array of buttons associated with links
	 * 			array('toolbar_items' =>
	 * 				array('view' => 'http://test/')
	 * 			),
	 * 			'status',
	 * 			// COL_CHECKBOX, name and value for checkbox
	 * 			array('name' => 'table[]', 'value' => 'test')
	 * 		),
	 * 		// Row 2
	 * 		array(
	 * 			'col 1 data 2',
	 * 			'col 2 data 2',
	 * 			NULL, // Can have null values
	 * 			array('toolbar_items' =>
	 * 				array('view' => 'http://test/2')
	 * 			),
	 * 			'status',
	 * 			array('name' => 'table[]', 'value' => 'test2')
	 * 		)
	 * 	 );
	 *
	 * This goes with the columns example above, where certain types of
	 * columns require an array of settings to populate.
	 *
	 * COL_TOOLBAR: Needs an array of toolbar_items where it's the name of
	 * the toolbar icon assocated to the URL it should go to upon click
	 *
	 * COL_CHECKBOX: Needs the name and value of the checkbox
	 * 
	 * @param	array 	$columns	Table data
	 * @return  void
	 */
	public function setData($data)
	{
		if ( ! empty($data))
		{
			if (count($data[0]) != count($this->columns))
			{
				throw new \InvalidArgumentException('Data must have the same number of columns as the set columns.');
			}

			$this->data = array();

			// Default settings for columns
			$defaults = array(
				'type'		=> self::COL_TEXT,
				'encode'	=> FALSE
			);

			// Normalize the table data for plugging into table view
			foreach ($data as $row)
			{
				$i = 0;
				$data_row = array();
				foreach ($row as $item)
				{
					// Get the settings for this column, we'll set some on
					// cell for easy access by the view
					$col_settings = array_values(array_slice($this->columns, $i, 1));

					// Normal cell content
					if ( ! is_array($item))
					{
						$settings = array(
							'content' 	=> $item,
							'type' 		=> $col_settings[0]['type'],
							'encode' 	=> $col_settings[0]['encode']
						);
						$data_row[] = array_merge($defaults, $settings);
					}
					else
					{
						$settings = array_merge($defaults, $item);
						$settings['type'] = $col_settings[0]['type'];

						$data_row[] = array_merge(array('content' => ''), $settings);
					}

					// Validate the some of the types
					switch ($settings['type'])
					{
						case self::COL_CHECKBOX:
							if ( ! isset($settings['name']) OR ! isset($settings['value']))
							{
								throw new \InvalidArgumentException('Checkboxes require a name and value.');
							}
							break;
						case self::COL_TOOLBAR:
							if ( ! isset($settings['toolbar_items']))
							{
								throw new \InvalidArgumentException('No toolbar items set for toolbar column type.');
							}
							break;
						default:
							break;
					}

					$i++;
				}

				$this->data[] = $data_row;
			}

			// If this table is not paginated, handle sorting automatically
			if ($this->config['autosort'])
			{
				$this->sortData();
			}

			// Handle search with a simple strpos()
			if ( ! empty($this->config['search']))
			{
				$this->searchData();
			}
		}
	}

	/**
	 * For data that is only ever contained to one table, likely
	 * non-paginated data, we can automatically handle the sorting by
	 * sorting the given array by the item corresponding to the current
	 * sort column. But if data is paginated and changing the sort also
	 * changes the data, it's best not to use this and instead handle
	 * it with setFilteredData().
	 * 
	 * @return  void
	 */
	private function sortData()
	{
		$columns = $this->columns;

		usort($this->data, function ($a, $b) use ($columns)
		{
			$search = array_keys($columns);
			$index = array_search($this->getSortCol(), $search);
			$a = $a[$index]['content'];
			$b = $b[$index]['content'];

			// Sort numbers as numbers
			if (is_numeric($a) && is_numeric($b))
			{
				$cmp = $a - $b;
			}
			// String sorting
			else
			{
				$cmp = strcmp($a, $b);
			}

			return ($this->getSortDir() == 'asc') ? $cmp : -$cmp;
		});
	}

	/**
	 * For data that is only ever contained to one table, likely
	 * non-paginated data, we can automatically handle table searching
	 * by using a strpos() search on each row and column. But if data
	 * is paginated and searching can add extra data to the table not
	 * in the current table scope, it's best not to use this and
	 * instead handle it with setFilteredData().
	 * 
	 * @return  void
	 */
	private function searchData()
	{
		// Bail if there's no search data
		if (empty($this->config['search']))
		{
			return;
		}

		foreach ($this->data as $key => $row)
		{
			$match = FALSE;

			foreach ($row as $column)
			{
				// Only search searchable columns
				if ($column['type'] == self::COL_TEXT OR
					$column['type'] == self::COL_STATUS)
				{
					if (strpos(strtolower($column['content']), strtolower($this->config['search'])) !== FALSE)
					{
						// Found a match, move on to the next row
						$match = TRUE;
						continue 2;
					}
				}
			}

			// Finally, remove the row if no match was found in any
			// searchable columns
			if ( ! $match)
			{
				unset($this->data[$key]);
			}
		}
	}

	/**
	 * Sets the same data as setData(), but provides the caller with
	 * sorting and filtering information via a callback. Best for
	 * paginated data. This method would typically be used like this:
	 *
	 * 	$table->setFilteredData(function($sort_col, $sort_dir, $search))
	 * 	{
	 * 		return ee()->api->get('Entity')
	 * 			->filter('column1', 'LIKE', $search)
	 * 			->or_filter('column2', 'LIKE', $search)
	 * 			->order($sort_col, $sort_dir)
	 * 			->getResult();
	 * 	});
	 *
	 * The above probably wouldn't work, the $sort_col is the actual
	 * text label of the column so it may need some mapping to a
	 * database column. Or if your data is already in an associative
	 * array, you just need to sort on that key. The idea is this
	 * method gives you want to need to sort and search your data so
	 * you can return the data to the table, whether or not any
	 * database work is involved
	 * 
	 * @param	callback 	$method	Callable method that accepts three
	 *                          	arguments: $sort_col, $sort_dir, $search
	 * @return  void
	 */
	public function setFilteredData($method)
	{
		if (is_callable($method))
		{
			$this->setData($method($this->getSortCol(), $this->getSortDir(), $this->config['search']));
		}
	}

	/**
	 * Returns the table configuration and data in a format ready to be
	 * processed by the _shared/table view
	 * 
	 * @param	URL	$base_url	URL object of the base URL used for setting
	 *                      	the search and sort criteria for sorting and
	 *                      	pagination URLs
	 * @return	array			Array of view variables, structure is below
	 */
	public function viewData($base_url = NULL)
	{
		if ($base_url != NULL)
		{
			if ($this->config['search'] === FALSE)
			{
				$this->config['search'] = '';
			}
			
			$base_url->setQueryStringVariable('search', $this->config['search']);
			$base_url->setQueryStringVariable('sort_col', $this->getSortCol());
			$base_url->setQueryStringVariable('sort_dir', $this->getSortDir());
		}

		return array(
			'base_url'	=> $base_url,
			'search'	=> $this->config['search'],
			'wrap'		=> $this->config['wrap'],
			'sort_col'	=> $this->getSortCol(),
			'sort_dir'	=> $this->getSortDir(),
			'columns'	=> $this->columns,
			'data'		=> $this->data
		);
	}

	/**
	 * Returns the current sorting column, or the first column if none is
	 * specified in the config
	 * 
	 * @return  string	Name of column to sort by
	 */
	private function getSortCol()
	{
		if ((empty($this->config['sort_col']) && count($this->columns) > 0) OR
			! in_array($this->config['sort_col'], array_keys($this->columns)))
		{
			return key($this->columns);
		}

		return $this->config['sort_col'];
	}

	/**
	 * Returns the current sorting direction
	 * 
	 * @return  string	Sort direction, either 'asc' or 'desc'
	 */
	private function getSortDir()
	{
		return ( ! in_array($this->config['sort_dir'], array('asc', 'desc')))
			? 'asc' : $this->config['sort_dir'];
	}
}

// END CLASS

/* End of file URL.php */
/* Location: ./system/EllisLab/ExpressionEngine/Library/CP/Table.php */
