<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Grid parser Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Grid_parser {

	public function __construct()
	{
		ee()->load->model('grid_model');
		ee()->load->helper('array_helper');
	}

 	// --------------------------------------------------------------------

	/**
	 * Get a Grid parser object, populated with the information we'll need
	 * to parse the Grid fields.
	 *
	 * @param	array	The gfields array from the Channel Module at the
	 *                  time of parsing.
	 *
	 * @return	EE_Grid_field_parser object
	 */
	public function create($grid_fields, $entry_ids, $tagdata)
	{
		$parser = new EE_Grid_field_parser($grid_fields, $entry_ids);

		if ($parser->pre_process($tagdata))
		{
			return $parser;
		}

		return NULL;
	}
}

class EE_Grid_field_parser {

	protected $_grid_fields;
	protected $_grid_tree;
	protected $_entry_ids;
	protected $_columns;
	protected $_field_data;

	/**
	 * Constructor
	 *
	 * @param	array	Grid fields matched to field ID
	 * @param	array	Entry IDs for the current Channel Entries loop
	 */
	public function __construct($grid_fields, $entry_ids)
	{
		$this->_grid_fields = $grid_fields;
		$this->_entry_ids = $entry_ids;
	}

	// --------------------------------------------------------------------

	/**
	 * Performs pre-processing before the Channel Entries loop runs, such
	 * as determining what (if any) Grid fields are present in the loop,
	 * building a tag tree of each Grid field so we know what needs
	 * parsing, then running the queries to get all data for the fields
	 *
	 * @param	string	Tag data for the channel loop
	 * @return	EE_Grid_field_parser object
	 */
	public function pre_process($tagdata)
	{
		// Bail out if there are no grid fields present to parse
		if ( ! preg_match_all(
				"/".LD.'\/?((?:(?:'.implode('|', array_flip($this->_grid_fields)).'):?)+)\b([^}{]*)?'.RD."/",
				$tagdata,
				$matches,
				PREG_SET_ORDER)
			)
		{
			return FALSE;
		}

		// Build the Grid tag tree; the above regex finds things like this:
		// 
		//     {test_grid orderby="text" sort="desc"}
		//         {test_grid:text}<br>
		//     {/test_grid}
		// 
		// And returns a $matches array like this:
		// 
		//     array(
		//         array(
		//             '{test_grid orderby="text" sort="desc"}',
		//             'test_grid',
		//             ' orderby="text" sort="desc"'
		//         ),
		//         array('{test_grid:text}', 'test_grid:', 'text'),
		//         array('{/test_grid}', 'test_grid', '')
		//     );
		// 
		// We want to turn it into this for easier traversing later:
		// 
		//     array(
		//         '{test_grid orderby="text" sort="desc"}' => array(
		//             'field_name' => 'test_grid',
		//             'params'     => array(
		//                 'orderby' => 'text',
		//                 'sort'    => 'desc',
		//             ),
		//             'field_id'   => '54',
		//             'fields'     => array(
		//                 array('{test_grid:text}', 'test_grid:', 'text')
		//             ),
		//         )
		//     );
		//     
		$open_tag = '';
		foreach ($matches as $match)
		{
			// If open_tag is blank, we should be at an open tag
			if ($open_tag == '')
			{
				// We're starting a new Grid field, get the opening tag that tells us
				// how to query and get the field name
				$open_tag = $match[0];
				$field_name = $match[1];
				$this->_grid_tree[$open_tag]['field_name'] = $field_name;

				// Assign field parameters, we'll validate later once we have more
				// information about the available columns
				$this->_grid_tree[$open_tag]['params'] = ee()->functions->assign_parameters($match[2]);

				// The supposed Grid field should be in the grid_fields array,
				// otherwise something is wrong
				if (isset($this->_grid_fields[$field_name]))
				{
					$this->_grid_tree[$open_tag]['field_id'] = $this->_grid_fields[$field_name];
					$field_ids[] = $this->_grid_fields[$field_name];
				}
				else
				{
					return FALSE;
				}

				continue;
			}

			// Capture all Grid variables in between tags
			// TODO: Handle variable pais
			if (substr($match[1], -1) == ':')
			{
				$this->_grid_tree[$open_tag]['fields'][] = $match;

				continue;
			}

			// Closing tag, reset open_tag var
			if ($match[0] == LD.'/'.$match[1].RD)
			{
				$open_tag = '';
			}
		}
		
		// Get the column settings for the fields we're parsing
		$this->_columns = ee()->grid_model->get_columns_for_field($field_ids);

		// Query for each Grid field
		foreach ($this->_grid_tree as $open_tag => &$data)
		{
			// Validate params now that we have column data
			$data['params'] = $this->_validate_params($data['params'], $data['field_id']);

			$this->_field_data[$open_tag] = ee()->grid_model->get_entry_rows(
				$this->_entry_ids,
				$data['field_id'],
				$data['params']
			);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Assigns some default parameters and makes sure parameters can be
	 * safely used in an SQL query or otherwise used to help parsing
	 *
	 * @param	array	Array of parameters from Functions::assign_parameters
	 * @param	int		Field ID of field being parsed so we can make sure
	 * 					the orderby parameter is ordering via a real column
	 * @return	array	Array of validated and default parameters to use for parsing
	 */
	protected function _validate_params($params, $field_id)
	{
		// Gather params and defaults
		$sort 			= element('sort', $params);
		$orderby		= element('orderby', $params);
		$limit			= element('limit', $params, 100);
		$offset			= element('offset', $params, 0);
		$backspace		= element('backspace', $params, 0);
		$row_id			= element('row_id', $params, 0); // Grid model will handle this
		$fixed_order	= element('fixed_order', $params, 0);
		// TODO: Search
		// TODO: Dynamic_parameters?
		// TODO: Fixed_order
		
		// Validate sort parameter, only 'asc' and 'desc' allowed, default to 'asc'
		if ( ! in_array($sort, array('asc', 'desc')))
		{
			$sort = 'asc';
		}

		$sortable_columns = array();
		foreach ($this->_columns[$field_id] as $col)
		{
			$sortable_columns[$col['col_name']] = $col['col_id'];
		}

		// orderby parameter can only order by the columns available to it,
		// default to 'row_id'
		if ( ! in_array($orderby, array_keys($sortable_columns)))
		{
			$orderby = 'row_id';
		}
		// Convert the column name to its matching table column name to hand
		// off to the query for proper sorting
		else
		{
			$orderby = 'col_id_'.$sortable_columns[$orderby];
		}

		return compact(
			'sort', 'orderby', 'limit', 'offset',
			'backspace', 'row_id', 'fixed_order'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Parses all Grid fields in the current Channel Entries row
	 *
	 * @param	int		Entry ID of current entry being parsed
	 * @param	string	Tag data at this point of the channel parsing
	 * @return	string	Tag data with all Grid fields parsed
	 */
	public function parse($entry_id, $tagdata)
	{
		// Create an easily-traversible array of columns by field ID
		// and column name
		$cols_by_field = array();
		foreach ($this->_columns as $field_id => $columns)
		{
			foreach ($columns as $col)
			{
				$cols_by_field[$field_id][$col['col_name']] = $col;
			}
		}

		// Loop through each Grid field tag pair and parse
		foreach ($this->_grid_tree as $open_tag => $data)
		{
			if (isset($this->_field_data[$open_tag][$entry_id]))
			{
				// We'll handle limit and offset parameters this way; we can't do
				// it via SQL because we query for multiple entries at once
				$field_data = array_slice(
					$this->_field_data[$open_tag][$entry_id],
					$data['params']['offset'],
					$data['params']['limit']
				);
			}
			else
			{
				$field_data = array();
			}

			$open_tag_quoted = preg_quote($open_tag, '/');
			$closing_tag = preg_quote($data['field_name'], '/');

			// Match the current Grid tag chunk in the template
			if (preg_match_all(
					'/'.$open_tag_quoted.'(.+?){\/'.$closing_tag.'}/is',
					$tagdata,
					$matches,
					PREG_SET_ORDER)
				)
			{
				$field_id = $data['field_id'];

				// Loop through all matching Grid fields
				foreach ($matches as $match)
				{
					$grid_chunk = '';

					foreach ($field_data as $row)
					{
						// Chunk in between tag pairs
						$grid_row = $match[1];

						foreach ($data['fields'] as $key => $value)
						{
							// Check to see if this field is a column field
							if (isset($cols_by_field[$field_id][$value[2]]))
							{
								// TODO: pass to fieldtype for parsing
								$column = $cols_by_field[$field_id][$value[2]];
								$replace_data = $row['col_id_'.$column['col_id']];
							}
							// Check to see if this is a field in the table for
							// this field, e.g. row_id
							elseif (isset($row[$value[2]]))
							{
								$replace_data = $row[$value[2]];
							}
							else
							{
								$replace_data = '';
							}

							// Finally, do the replacement
							$grid_row = str_replace(
								$value[0],
								$replace_data,
								$grid_row
							);
						}

						$grid_chunk .= $grid_row;
					}

					// Backspace parameter
					$backspace = $data['params']['backspace'];
					if (is_numeric($backspace) && ! empty($backspace))
					{
						$grid_chunk = substr($grid_chunk, 0, -$backspace);
					}

					// Replace match[0] which is the entire Grid field tag pair
					$tagdata = str_replace($match[0], $grid_chunk, $tagdata);
				}
			}
		}
		
		return $tagdata;
	}
}

/* End of file Grid_parser.php */
/* Location: ./system/expressionengine/libraries/Grid_parser.php */