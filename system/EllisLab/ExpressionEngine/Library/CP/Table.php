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
	const COL_ID = 5;

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
	 * 'lang_cols' - Run column names though lang() on the front end
	 *
	 * @param	array 	$config	See above for options
	 */
	public function __construct($config = array())
	{
		$defaults = array(
			'wrap'		 => TRUE,
			'sort_col'	 => NULL,
			'sort_dir'	 => 'asc',
			'limit'		 => 20,
			'page'		 => 1,
			'total_rows' => 0,
			'search'	 => NULL,
			'sortable'	 => TRUE,
			'autosort'	 => FALSE,
			'autosearch' => FALSE,
			'lang_cols'	 => TRUE,
			'grid_input' => FALSE,
			'reorder'	 => FALSE,
			'no_results' => array(
				'text'			=> 'no_rows_returned',
				'action_text'	=> '',
				'action_link'	=> ''
			)
		);

		$this->config = array_merge($defaults, $config);
	}

	/**
	 * Allow read-only access to certain information we have
	 */
	function __get($name)
	{
		switch ($name) {
			case 'sort_col':
				return $this->getSortCol();
				break;
			case 'sort_dir':
				return $this->getSortDir();
				break;
			case 'search':
				return $this->config['search'];
				break;
			default:
				user_error("Invalid property: " . __CLASS__ . "->$name");
				break;
		}
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
				? ee()->input->post('search') : ee()->input->get('search'),
			'page'		=> ee()->input->get('page') > 0 ? ee()->input->get('page') : 1
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
				if ($settings['type'] !== self::COL_ID &&
					$settings['type'] !== self::COL_TEXT &&
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
	 * 			array('toolbar_items' => array(
	 * 				'view' => array( // Button class name
	 * 					// HTML attributes for anchor
	 * 					'href' => 'http://test/2',
	 * 					'title' => 'view'
	 * 				)
	 * 			)),
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
			if (array_key_exists('columns', $data[0]))
			{
				$count = count($data[0]['columns']);
			}
			else
			{
				$count = count($data[0]);
			}
			if ($count != count($this->columns))
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
				$attrs = array();

				if (array_keys($row) == array('attrs', 'columns'))
				{
					$attrs = $row['attrs'];
					$row = $row['columns'];
				}

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

				$this->data[] = array(
					'attrs'		=> $attrs,
					'columns'	=> $data_row
				);
			}

			$this->config['total_rows'] = count($this->data);

			// If this table is not paginated, handle sorting automatically
			if ($this->config['autosort'])
			{
				$this->sortData();
			}

			// Handle search with a simple strpos()
			if ($this->config['autosearch'])
			{
				$this->searchData();
			}

			// Apply pagination after search
			if ($this->config['autosort'])
			{
				$offset = ($this->config['page'] - 1) * $this->config['limit'];

				$this->data = array_slice($this->data, $offset, $this->config['limit']);
			}
		}
	}

	/**
	 * For data that is only ever contained to one table, likely
	 * non-paginated data, we can automatically handle the sorting by
	 * sorting the given array by the item corresponding to the current
	 * sort column. But if data is paginated and changing the sort also
	 * changes the data, it's best not to use this and instead handle
	 * it manually with the sort_col and sort_dir magic properties
	 *
	 * @return  void
	 */
	private function sortData()
	{
		$columns = $this->columns;
		$sort_col = $this->getSortCol();
		$sort_dir = $this->getSortDir();

		usort($this->data, function ($a, $b) use ($columns, $sort_col, $sort_dir)
		{
			$search = array_keys($columns);
			$index = array_search($sort_col, $search);
			$a = $a['columns'][$index]['content'];
			$b = $b['columns'][$index]['content'];

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

			return ($sort_dir == 'asc') ? $cmp : -$cmp;
		});
	}

	/**
	 * For data that is only ever contained to one table, likely
	 * non-paginated data, we can automatically handle table searching
	 * by using a strpos() search on each row and column. But if data
	 * is paginated and searching can add extra data to the table not
	 * in the current table scope, it's best not to use this and
	 * instead handle it manually with the magic search property
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

			foreach ($row['columns'] as $column)
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

		$this->config['total_rows'] = count($this->data);
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
			'base_url'		=> $base_url,
			'lang_cols'		=> $this->config['lang_cols'],
			'search'		=> $this->config['search'],
			'wrap'			=> $this->config['wrap'],
			'no_results'	=> $this->config['no_results'],
			'limit'			=> $this->config['limit'],
			'page'			=> $this->config['page'],
			'total_rows'	=> $this->config['total_rows'],
			'grid_input'	=> $this->config['grid_input'],
			'reorder'		=> $this->config['reorder'],
			'sortable'		=> $this->config['sortable'],
			'sort_col'		=> $this->getSortCol(),
			'sort_dir'		=> $this->getSortDir(),
			'columns'		=> $this->columns,
			'data'			=> $this->data
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

	/**
	 * Set the "no results" text for the table along with an optional action
	 * button and link to create a new whatever is supposed to be displayed.
	 * Text is typically in the format of "No {item} available" with button
	 * text being "Create new {item}"
	 *
	 * @param	string	$text			Text to be shown in the table when there
	 *                       			are no results
	 * @param	string	$action_text	Text for action button to create a new item
	 * @param	string	$action_link	Link for action button to create a new item
	 * @return  void
	 */
	public function setNoResultsText($text, $action_text = '', $action_link = '')
	{
		$this->config['no_results'] = array(
			'text'			=> $text,
			'action_text'	=> $action_text,
			'action_link'	=> $action_link
		);
	}

	/**
	 * Set the empty row elements for new/empty rows in a Grid input table
	 *
	 * @param	array	$row	Array of empty field elements to be duplicated
	 *                   		for each new row the user creates
	 * @return  void
	 */
	public function setBaseGridRow($row)
	{
		$this->config['grid_base_row'] = $row;
	}
}

// END CLASS

/* End of file URL.php */
/* Location: ./system/EllisLab/ExpressionEngine/Library/CP/Table.php */
