<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.7
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

	public $modifiers = array();
	public $reserved_names = array();
	public $grid_field_names = array();

	public function __construct()
	{
		// The pre processor will accept these modifiers as fields that need querying
		$this->modifiers = array('next_row', 'prev_row', 'total_rows', 'table',
			'sum', 'average', 'lowest', 'highest');

		// These names cannot be used for column names because they serve
		// other front-end functions as tag modifiers
		$this->reserved_names = array_merge(
			$this->modifiers,
			array('switch', 'count', 'index', 'field_total_rows')
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Called before each channel entries loop to gather the information
	 * needed to efficiently query the Grid data we need
	 *
	 * @param	string	Tag data for entire channel entries loop
	 * @param	object	Channel preparser object
	 * @param	array	Array of known Grid fields in this channel
	 */
	public function pre_process($tagdata, $pre_parser, $grid_fields, $content_type = 'channel')
	{
		// Bail out if there are no grid fields present to parse
		if ( ! preg_match_all(
				"/".LD.'\/?('.preg_quote($pre_parser->prefix()).'(?:(?:'.implode('|', array_flip($grid_fields)).'):?)+)\b([^}{]*)?'.RD."/",
				$tagdata,
				$matches,
				PREG_SET_ORDER)
			)
		{
			return FALSE;
		}

		$field_ids = array();

		// Validate matches
		foreach ($matches as $key => $match)
		{
			$field_name = str_replace($pre_parser->prefix(), '', $match[1]);

			// Analyze the field to see if its modifier matches any of our
			// reserved modifier names
			$field = ee()->api_channel_fields->get_single_field($match[2], $field_name);

			// Throw out variables and closing tags, we'll deal with them
			// in the parsing stage
			if (( ! in_array($field['field_name'], $this->modifiers) && substr($match[1], -1) == ':')
				|| substr($match[0], 0, 2) == LD.'/')
			{
				unset($matches[$key]);
				continue;
			}

			$field_name = rtrim($field_name, ':');

			// Make sure the supposed field name is an actual Grid field
			if ( ! isset($grid_fields[$field_name]))
			{
				return FALSE;
			}

			// Collect field IDs so we can gather the column data for these fields
			$field_ids[] = $grid_fields[$field_name];
		}

		ee()->load->model('grid_model');

		// Cache column data for all fields used in the Channel loop
		$columns = ee()->grid_model->get_columns_for_field(array_unique($field_ids), $content_type);

		// Attempt to gather all data needed for the entries loop before
		// the loop runs
		foreach ($matches as $match)
		{
			$field_name = rtrim(str_replace($pre_parser->prefix(), '', $match[1]), ':');
			$params = $match[2];
			$field_id = $grid_fields[$field_name];
			$this->grid_field_names[$field_id] = rtrim($match[1], ':');

			ee()->grid_model->get_entry_rows($pre_parser->entry_ids(), $field_id, $content_type, $params);
		}

		// Handle EE_Fieldtype::pre_loop()
		$this->_pre_loop($columns);

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
	public function parse($channel_row, $field_id, $params, $tagdata, $content_type = 'channel')
	{
		if (empty($tagdata))
		{
			return '';
		}

		$entry_id = $channel_row['entry_id'];

		ee()->load->model('grid_model');
		$entry_data = ee()->grid_model->get_entry_rows($entry_id, $field_id, $content_type, $params);

		// Bail out if no entry data
		if ($entry_data === FALSE OR ! isset($entry_data[$entry_id]))
		{
			return '';
		}

		$params = $entry_data['params'];
		$entry_data = $entry_data[$entry_id];
		$field_name = $this->grid_field_names[$field_id];

		// Add field_row_index and field_row_count variables to get the index
		// and count of rows in the field regardless of front-end output
		$field_row_count = 1;
		foreach ($entry_data as &$entry_data_row)
		{
			$entry_data_row['field_row_index'] = $field_row_count - 1;
			$entry_data_row['field_row_count'] = $field_row_count;
			$field_row_count++;
		}

		// :field_total_rows single variable
		// Currently does not work well with fixed_order and search params
		$field_total_rows = count($entry_data);

		$row_ids = $params['row_id'];
		$not = (strncmp($row_ids, 'not ', 4) == 0);

		// row_id parameter
		if ($row_ids !== 0)
		{
			if ($not)
			{
				$row_ids = substr($row_ids, 4);
			}

			$row_ids = explode('|', $row_ids);

			// Unset the "not" row_ids from entry_data
			if ($not)
			{
				foreach ($row_ids as $row_id)
				{
					if (isset($entry_data[$row_id]))
					{
						unset($entry_data[$row_id]);
					}
				}
			}
			else
			{
				// If there are mutliple row IDs
				if (count($row_ids) > 1)
				{
					// Unset all rows that AREN'T in the row_id parameter
					foreach (array_diff(array_keys($entry_data), $row_ids) as $row_id)
					{
						unset($entry_data[$row_id]);
					}
				}
				// Otherwise, if there is just one row_id, we're likely inside
				// a next_row or prev_row tag, don't modify the entry_data
				// so we still have access to next and previous rows
				elseif (count($row_ids) == 1)
				{
					$row_index = array_search(current($row_ids), array_keys($entry_data));

					$params['offset'] += $row_index;
					$params['limit'] = 1;
				}
			}
		}

		// Order by random
		if ($params['orderby'] == 'random')
		{
			shuffle($entry_data);
		}

		// We'll handle limit and offset parameters this way; we can't do
		// it via SQL because we query for multiple entries at once
		$display_entry_data = array_slice(
			$entry_data,
			$params['offset'],
			$params['limit'],
			TRUE
		);

		// Collect row IDs
		$row_ids = array();
		foreach ($display_entry_data as $row)
		{
			$row_ids[] = $row['row_id'];
		}

		// :total_rows single variable
		$total_rows = count($display_entry_data);

		// Grid field output will be stored here
		$grid_tagdata = '';

		// :count single variable
		$count = 1;

		$prefix = $field_name.':';

		$columns = ee()->grid_model->get_columns_for_field($field_id, $content_type);

		// Prepare the relationship data
		$relationships = array();

		foreach ($columns as $col)
		{
			if ($col['col_type'] == 'relationship')
			{
				$relationships[$prefix.$col['col_name']] = $col['col_id'];
			}
		}

		ee()->load->library('relationships_parser');
		$channel = ee()->session->cache('mod_channel', 'active');

		try
		{
			if (! empty($relationships))
			{
				$relationship_parser = ee()->relationships_parser->create(
					(isset($channel->rfields[config_item('site_id')]) ? $channel->rfields[config_item('site_id')] : array()),
					$row_ids, // array(#, #, #)
					$tagdata,
					$relationships, // field_name => field_id
					$field_id
				);
			}
			else
			{
				$relationship_parser = NULL;
			}
		}
		catch (EE_Relationship_exception $e)
		{
			$relationship_parser = NULL;
		}

		foreach ($display_entry_data as $row)
		{
			$grid_row = $tagdata;

			$position = array_search($row['row_id'], $row_ids);

			if ($relationship_parser)
			{
				try
				{
					$grid_row = $relationship_parser->parse($row['row_id'], $grid_row, $channel);
				}
				catch (EE_Relationship_exception $e)
				{
					ee()->TMPL->log_item($e->getMessage());
				}
			}


			// Extra single vars
			$row['count'] = $count;
			$row['index'] = $count - 1;
			$row['total_rows'] = $total_rows;
			$row['field_total_rows'] = $field_total_rows;

			$grid_row = ee()->TMPL->parse_switch($grid_row, $row['index'], $prefix);

			$count++;

			// Compile conditional vars
			$cond = array();

			// Map column names to their values in the DB
			foreach ($columns as $col_id => $col)
			{
				$value = (isset($row['col_id_'.$col_id])) ? $row['col_id_'.$col_id] : '';

				$cond[$prefix.$col['col_name']] = $value;
			}

			// Anything in the $row array can be checked in a conditional
			foreach ($row as $key => $value)
			{
				$cond[$prefix.$key] = $value;
			}

			$grid_row = ee()->functions->prep_conditionals($grid_row, $cond);

			// Parse next_row and prev_row tags inside a Grid field tag pair
			foreach (array('next_row', 'prev_row') as $modifier)
			{
				// Get any field pairs
				$pchunks = ee()->api_channel_fields->get_pair_field(
					$grid_row,
					$modifier,
					$prefix
				);

				foreach ($pchunks as $chk_data)
				{
					list($pair_modifier, $content, $params, $chunk) = $chk_data;

					$next_prev_row = array();

					// Advance or go back in the entry data array
					if ($modifier == 'next_row' && isset($row_ids[$position + 1]))
					{
						$next_prev_row = $entry_data[$row_ids[$position + 1]];
					}
					elseif ($modifier == 'prev_row' && isset($row_ids[$position - 1]))
					{
						$next_prev_row = $entry_data[$row_ids[$position - 1]];
					}

					// Send the next or previous row to _parse_row for parsing
					$replace_data = ( ! empty($next_prev_row))
						? $this->_parse_row($channel_row, $field_id, $content, $next_prev_row, $content_type) : '';

					// Replace tag pair
					$grid_row = str_replace($chunk, $replace_data, $grid_row);
				}
			}

			$grid_tagdata .= $this->_parse_row($channel_row, $field_id, $grid_row, $row, $content_type);
		}

		return $grid_tagdata;
	}

	// --------------------------------------------------------------------

	/**
	 * Parses individual row in Grid field
	 *
	 * @param	array	Channel entry row data typically sent to fieldtypes
	 * @param	int		Field ID of field being parsed so we can make sure
	 * @param	string	Tagdata with variables to replace
	 * @param	array	Grid single row data
	 * @return	string	Parsed field data
	 */
	private function _parse_row($channel_row, $field_id, $tagdata, $row, $content_type = 'channel')
	{
		$grid_row = $tagdata;
		$field_name = $this->grid_field_names[$field_id];
		$entry_id = $channel_row['entry_id'];

		// Gather the variables to parse
		if ( ! preg_match_all(
				"/".LD.'?[^\/]((?:(?:'.preg_quote($field_name).'):?)+)\b([^}{]*)?'.RD."/",
				$tagdata,
				$matches,
				PREG_SET_ORDER) || empty($row)
			)
		{
			return $tagdata;
		}

		$columns = ee()->grid_model->get_columns_for_field($field_id, $content_type);

		// Create an easily-traversible array of columns by field ID
		// and column name
		$column_names = array();
		$relationships = array();

		foreach ($columns as $col)
		{
			$column_names[$col['col_name']] = $col;

			if ($col['col_type'] == 'relationship')
			{
				$relationships[$col['col_name']] = $col['col_id'];
			}
		}

		foreach ($matches as $match)
		{

			// Get tag name, modifier and params for this tag
			$field = ee()->api_channel_fields->get_single_field($match[2], $field_name.':');

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

				if ( ! isset($column_names[$field['field_name']]))
				{
					$grid_row = str_replace($chunk, '', $grid_row);
					continue;
				}

				$column = $column_names[$field['field_name']];

				$channel_row['col_id_'.$column['col_id']] = $row['col_id_'.$column['col_id']];
				$replace_data = $this->_replace_tag(
					$column,
					$field_id,
					$entry_id,
					$row['row_id'],
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
					$row['row_id'],
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
				$replace_data = $match[0];
			}

			// Finally, do the replacement
			$grid_row = str_replace(
				$match[0],
				$replace_data,
				$grid_row
			);
		}

		return $grid_row;
	}

	// ------------------------------------------------------------------------

	/**
	 * Handle EE_Fieldtype::pre_loop() so fieldtypes can query more efficiently
	 *
	 * @param string $entries_data
	 * @return void
	 */
	protected function _pre_loop($cols)
	{
		$grid_data = ee()->grid_model->get_grid_data();

		$columns = array();

		// Get an array of unique columns not segmented by field ID
		foreach ($cols as $field_id => $column)
		{
			foreach ($column as $col_id => $value)
			{
				if ( ! isset($columns[$col_id]))
				{
					$columns[$col_id] = $value;
				}
			}
		}

		$col_data = array();

		// Gather data by column TYPE so multiple columns of the same type
		// get data passed to the fieldtype in one go
		foreach ($columns as $column)
		{
			if ( ! isset($grid_data[$column['field_id']]))
			{
				continue;
			}

			foreach ($grid_data[$column['field_id']] as $marker)
			{
				foreach ($marker as $row)
				{
					foreach ($row as $row_id => $data)
					{
						if ( ! is_array($data) || ! isset($data['col_id_'.$column['col_id']]))
						{
							continue;
						}

						// Group data by column type
						$col_data[$column['col_type']][] = $data['col_id_'.$column['col_id']];
					}
				}
			}
		}

		// Send data for entire channel entires loop to fieldtype only once
		foreach ($columns as $column)
		{
			if (isset($col_data[$column['col_type']]))
			{
				$this->instantiate_fieldtype($column, NULL, $column['field_id']);
				$this->call('pre_loop', $col_data[$column['col_type']]);
			}

			// Unset this column type to make sure we don't send it again if
			// other columns exist with the same type
			unset($col_data[$column['col_type']]);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Instantiates fieldtype handler and assigns information to the object
	 *
	 * @param	array	Column information
	 * @param	string	Unique row identifier
	 * @param	int		Field ID of Grid field
	 * @param	int		Entry ID being processed or parsed
	 * @return	object	Fieldtype object
	 */
	public function instantiate_fieldtype($column, $row_name = NULL, $field_id = 0, $entry_id = 0)
	{
		if ( ! isset(ee()->api_channel_fields->field_types[$column['col_type']]))
		{
			ee()->load->library('api');
			ee()->api->instantiate('channel_fields');
			ee()->api_channel_fields->fetch_installed_fieldtypes();
		}

		// Instantiate fieldtype
		$fieldtype = ee()->api_channel_fields->setup_handler($column['col_type'], TRUE);

		if ( ! $fieldtype)
		{
			return NULL;
		}

		// Assign settings to fieldtype manually so they're available like
		// normal field settings
		$fieldtype->_init(
			array(
				'field_id'		=> $column['col_id'],
				'field_name'	=> 'col_id_'.$column['col_id'],
				'content_id'	=> $entry_id,
				'content_type'	=> 'grid'
			)
		);

		// Assign fieldtype column settings and any other information that will
		// be helpful to be accessible by fieldtypes
		$fieldtype->settings = array_merge(
			$column['col_settings'],
			array(
				'field_label'		=> $column['col_label'],
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
	 * @param	array	Column array from database
	 * @param	int		Field ID of Grid field being parsed
	 * @param	int		Entry ID of entry being parsed
	 * @param	int		Grid row ID of row being parsed
	 * @param	array	Array containing modifier and params for field
	 * 					being parsed
	 * @param	string	Field data to send to fieldtype for processing and
	 * 					parsing
	 * @param	string	Tag data for tag pairs being parsed
	 * @return	string	Tag data with all Grid fields parsed
	 */
	protected function _replace_tag($column, $field_id, $entry_id, $row_id, $field, $data, $content = FALSE)
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

		$fieldtype->_init(array(
			'row' => $data,
			'content_id' => $entry_id
		));

		// Add row ID to settings array
		$fieldtype->settings['grid_row_id'] = $row_id;

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
