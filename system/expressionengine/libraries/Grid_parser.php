<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
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
 * @subpackage	Libraries
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Grid_parser {

	// Grid field data will persist here throughout the request
	protected $_grid_data = array();

	/**
	 * Called before each channel entries loop to gather the information
	 * needed to efficiently query the Grid data we need
	 *
	 * @param	string	Tag data for entire channel entries loop
	 * @param	object	Channel preparser object
	 * @param	array	Array of known Grid fields in this channel
	 */
	public function pre_process($tagdata, $pre_parser, $grid_fields)
	{
		// Bail out if there are no grid fields present to parse
		if ( ! preg_match_all(
				"/".LD.'\/?('.$pre_parser->prefix().'(?:(?:'.implode('|', array_flip($grid_fields)).'):?)+)\b([^}{]*)?'.RD."/",
				$tagdata,
				$matches,
				PREG_SET_ORDER)
			)
		{
			return FALSE;
		}

		// Validate matches
		foreach ($matches as $key => $match)
		{
			// Throw own variables and closing tags, we'll deal with them
			// in the parsing stage
			if (substr($match[1], -1) == ':' || substr($match[0], 0, 2) == LD.'/')
			{
				unset($matches[$key]);
				continue;
			}

			$field_name = str_replace($pre_parser->prefix(), '', $match[1]);
			
			// Make sure the supposed field name is an actual Grid field
			if ( ! isset($grid_fields[$field_name]))
			{
				return FALSE;
			}

			// Collect field IDs so we can gather the column data for these fields
			$field_ids[] = $grid_fields[$field_name];
		}

		ee()->load->model('grid_model');
		$columns = ee()->grid_model->get_columns_for_field(array_unique($field_ids));

		foreach ($matches as $match)
		{
			$field_name = str_replace($pre_parser->prefix(), '', $match[1]);
			$params = $match[2];
			$field_id = $grid_fields[$field_name];
			$this->_grid_data[$field_id]['field_name'] = $match[1];

			$params = ee()->functions->assign_parameters($params);

			// Create a unique marker for this tag configuration based on its
			// parameters so we can match up the field data later in parse();
			// if there are no parameters, we'll just use 'data'
			$marker = (empty($params)) ? 'data' : md5(json_encode($params));

			// Validate the params after creating the marker because the params
			// we get at parse() are unvalidated
			$params = $this->validate_params($params, $field_id, $columns);

			// Only get the data we don't already have
			$entry_ids = $pre_parser->entry_ids();
			foreach ($entry_ids as $key => $entry_id)
			{
				// If we already have data for this particular tag configuation
				// and entry ID, we don't need to get it again
				if (isset($this->_grid_data[$field_id][$marker][$entry_id]))
				{
					unset($entry_ids[$key]);
				}
			}

			// If there are entries to query for, go get them!
			if ( ! empty($entry_ids))
			{
				$this->_grid_data[$field_id][$marker] = ee()->grid_model->get_entry_rows(
					$entry_ids,
					$field_id,
					$params
				);
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Handles ft.grid.php's replace_tag(), called with each loop of the
	 * channel entries parser
	 *
	 * @param	array	Channel entry row data typically sent to fieldtypes
	 * @param	int		Field ID of field being parsed so we can make sure
	 * @param	array	Parameters array, unvalidated
	 * @param	string	Tag data of our field pair
	 * @return	string	Parsed field data
	 */
	public function parse($channel_row, $field_id, $params, $tagdata)
	{
		$entry_id = $channel_row['entry_id'];

		$marker = (empty($params)) ? 'data' : md5(json_encode($params));

		// See if our unique marker matches anything in _grid_data
		if ( ! isset($this->_grid_data[$field_id][$marker][$entry_id]))
		{
			return '';
		}

		$entry_data = $this->_grid_data[$field_id][$marker][$entry_id];
		$field_name = $this->_grid_data[$field_id]['field_name'];

		// Gather the variables to parse
		if ( ! preg_match_all(
				"/".LD.'?[^\/]((?:(?:'.$field_name.'):?)+)\b([^}{]*)?'.RD."/",
				$tagdata,
				$matches,
				PREG_SET_ORDER)
			)
		{
			return $tagdata;
		}

		ee()->load->model('grid_model');
		$columns = ee()->grid_model->get_columns_for_field($field_id);

		// Create an easily-traversible array of columns by field ID
		// and column name
		$column_names = array();
		foreach ($columns as $col)
		{
			$column_names[$col['col_name']] = $col;
		}

		// Validate our params and set defaults
		$params = $this->validate_params($params, $field_id, array($field_id => $columns));

		// :field_total_rows single variable
		$field_total_rows = count($entry_data);

		// We'll handle limit and offset parameters this way; we can't do
		// it via SQL because we query for multiple entries at once
		$entry_data = array_slice(
			$entry_data,
			$params['offset'],
			$params['limit']
		);

		// :total_rows single variable
		$total_rows = count($entry_data);

		// Grid field output will be stored here
		$grid_tagdata = '';

		// :count single variable
		$count = 1;

		foreach ($entry_data as $row)
		{
			$grid_row = $tagdata;

			// Extra single vars
			$row['count'] = $count;
			$row['index'] = $count - 1;
			$row['total_rows'] = $total_rows;
			$row['field_total_rows'] = $field_total_rows;
			$count++;

			foreach ($matches as $match)
			{
				// Get tag name, modifier and params for this tag
				$field = ee()->api_channel_fields->get_single_field($match[2], $field_name);

				// Get any field pairs
				$pchunks = ee()->api_channel_fields->get_pair_field(
					$tagdata,
					$field['field_name'],
					$field_name.':'
				);

				// Work through field pairs first
				foreach ($pchunks as $chk_data)
				{
					list($modifier, $content, $params, $chunk) = $chk_data;

					$column = $column_names[$field['field_name']];
					$channel_row['col_id_'.$column['col_id']] = $row['col_id_'.$column['col_id']];
					$replace_data = $this->_replace_tag(
						$column,
						$field_id,
						$entry_id,
						array(
							'modifier'	=> $modifier,
							'params'	=> $params
						),
						$channel_row,
						$content
					);

					// Replace tag pair
					$grid_row = str_replace($chunk, $replace_data, $grid_row);
				}

				// Now handle any single variables
				if (isset($column_names[$field['field_name']]) &&
					strpos($grid_row, $match[0]) !== FALSE)
				{
					$column = $column_names[$field['field_name']];
					$channel_row['col_id_'.$column['col_id']] = $row['col_id_'.$column['col_id']];
					$replace_data = $this->_replace_tag(
						$column,
						$field_id,
						$entry_id,
						$field,
						$channel_row
					);
				}
				// Check to see if this is a field in the table for
				// this field, e.g. row_id
				elseif (isset($row[$match[2]]))
				{
					$replace_data = $row[$match[2]];
				}
				else
				{
					$replace_data = '';
				}

				// Finally, do the replacement
				$grid_row = str_replace(
					$match[0],
					$replace_data,
					$grid_row
				);
			}

			$grid_tagdata .= $grid_row;
		}

		return $grid_tagdata;
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
	public function validate_params($params, $field_id, $columns)
	{
		ee()->load->helper('array_helper');

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
		foreach ($columns[$field_id] as $col)
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

		// Gather search:field_name parameters
		$search = array();
		if ($params !== FALSE)
		{
			foreach ($params as $key => $val)
			{
				if (strncmp($key, 'search:', 7) == 0)
				{
					$search[substr($key, 7)] = $val;
				}
			}
		}

		return compact(
			'sort', 'orderby', 'limit', 'offset', 'search',
			'backspace', 'row_id', 'fixed_order'
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Instantiates fieldtype handler and assigns information to the object
	 *
	 * @param	array	Column information
	 * @param	string	Unique row identifier
	 * @return	object	Fieldtype object
	 */
	public function instantiate_fieldtype($column, $row_name = NULL, $field_id = 0, $entry_id = 0)
	{
		// Instantiate fieldtype
		$fieldtype = ee()->api_channel_fields->setup_handler($column['col_type'], TRUE);

		// Assign settings to fieldtype manually so they're available like
		// normal field settings
		$fieldtype->field_id = $column['col_id'];
		$fieldtype->field_name = 'col_id_'.$column['col_id'];

		// Assign fieldtype column settings and any other information that will
		// be helpful to be accessible by fieldtypes
		$fieldtype->settings = array_merge(
			$column['col_settings'],
			array(
				'field_required'	=> $column['col_required'],
				'col_id'			=> $column['col_id'],
				'col_name'			=> $column['col_name'],
				'col_required'		=> $column['col_required'],
				'entry_id'			=> $entry_id,
				'grid_field_id'		=> $field_id,
				'grid_row_name'		=> $row_name
			)
		);

		return $fieldtype;
	}

	// ------------------------------------------------------------------------

	/**
	 * Calls a method on a fieldtype and returns the result. If the method
	 * exists with a prefix of grid_, that will be called in place of it.
	 *
	 * @param	string	Method name to call
	 * @param	string	Data to send to method
	 * @param	bool	Whether or not to expect multiple parameters
	 * @return	string	Returned data from fieldtype method
	 */
	public function call($method, $data, $multi_param = FALSE)
	{
		$ft_api = ee()->api_channel_fields;

		// Add fieldtype package path
		$_ft_path = $ft_api->ft_paths[$ft_api->field_type];
		ee()->load->add_package_path($_ft_path, FALSE);

		$ft_method = $ft_api->check_method_exists('grid_'.$method)
			? 'grid_'.$method : $method;

		// If single parameter, put into an array, otherwise if it's
		// multi-parameter, parameters will already be in an array
		if ( ! $multi_param)
		{
			$data = array($data);
		}

		$result = ($ft_api->check_method_exists($ft_method))
			? $ft_api->apply($ft_method, $data) : NULL;

		ee()->load->remove_package_path($_ft_path);

		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Calls fieldtype's grid_replace_tag/replace_tag given tag properties
	 * (modifier, params) and returns the result
	 *
	 * @param	int		Entry ID of current entry being parsed
	 * @param	string	Tag data at this point of the channel parsing
	 * @return	string	Tag data with all Grid fields parsed
	 */
	protected function _replace_tag($column, $field_id, $entry_id, $field, $data, $content = FALSE)
	{
		$fieldtype = $this->instantiate_fieldtype($column, NULL, $field_id, $entry_id);

		// Return the raw data if no fieldtype found
		if ( ! $fieldtype)
		{
			return ee()->typography->parse_type(
				ee()->functions->encode_ee_tags($data['col_id_'.$column['col_id']])
			);
		}

		// Determine the replace function to call based on presence of modifier
		$modifier = $field['modifier'];
		$parse_fnc = ($modifier) ? 'replace_'.$modifier : 'replace_tag';

		$fieldtype->_init(array('row' => $data));

		$data = $this->call('pre_process', $data['col_id_'.$column['col_id']]);

		// Params sent to parse function
		$params = array($data, $field['params'], $content);

		// Sent to catchall if modifier function doesn't exist
		if ($modifier && ! method_exists($fieldtype, $parse_fnc))
		{
			$parse_fnc = 'replace_tag_catchall';
			$params[] = $modifier;
		}

		return $this->call($parse_fnc, $params, TRUE);
	}
}

/* End of file Grid_parser.php */
/* Location: ./system/expressionengine/modules/grid/libraries/Grid_parser.php */