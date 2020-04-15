<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use EllisLab\Addons\FluidField\Model\FluidField;
use EllisLab\ExpressionEngine\Service\Model\Collection;

/**
 * Fluid Field Parser
 */
class Fluid_field_parser {

	public $modifiers = [];
	protected $data = [];
	protected $possible_fields = [];
	protected $fluid_fields = [];

	public function __construct()
	{
		// The pre processor will accept these modifiers as fields that need querying
		$this->modifiers = [
			'first',
			'last',
			'count',
			'index',
			'current_field_name',
			'next_field_name',
			'prev_field_name',
			'current_field_type',
			'next_fieldtype',
			'prev_fieldtype',
			'length',
			'total_fields'
		];
	}

	/**
	 * Called before each channel entries loop to gather the information
	 * needed to efficiently query the Fluid Field data we need
	 *
	 * @param string $tagdata Tag data for entire channel entries loop
	 * @param object $pre_parser Channel preparser object
	 * @param array $fluid_field_fields An array of fluid field fields
	 * @param string $content_type The type of content being processed
	 */
	public function pre_process($tagdata, $pre_parser, array $fluid_field_fields, $content_type = 'channel')
	{
		// Bail out if there are no fluid field fields present to parse
		if ( ! preg_match_all(
				"/".LD.'\/?('.preg_quote($pre_parser->prefix()).'(?:(?:'.implode('|', array_flip($fluid_field_fields)).'):?))\b([^}{]*)?'.RD."/",
				$tagdata,
				$matches,
				PREG_SET_ORDER)
			)
		{
			return FALSE;
		}

		$this->fluid_fields = array_flip($fluid_field_fields);

		$this->_prefix = $pre_parser->prefix();

		$fluid_field_ids = [];

		// Validate matches
		foreach ($matches as $key => $match)
		{
			$field_name = str_replace($this->_prefix, '', $match[1]);

			// Analyze the field to see if its modifier matches any of our
			// reserved modifier names
			$field = ee('Variables/Parser')->parseVariableProperties($match[2], $field_name);

			// Throw out variables and closing tags, we'll deal with them
			// in the parsing stage
			if (( ! in_array($field['field_name'], $this->modifiers) && substr($match[1], -1) == ':')
				|| substr($match[0], 0, 2) == LD.'/')
			{
				unset($matches[$key]);
				continue;
			}

			$field_name = rtrim($field_name, ':');

			// Make sure the supposed field name is an actual Fluid Field field
			if ( ! isset($fluid_field_fields[$field_name]))
			{
				return FALSE;
			}

			// Collect field IDs so we can gather the column data for these fields
			$fluid_field_ids[] = $fluid_field_fields[$field_name];
		}

		$this->data = $this->fetchFluidFields($pre_parser->entry_ids(), array_unique($fluid_field_ids));

		return TRUE;
	}

	/**
	 * Gets a list of field names for a given set of field ids
	 *
	 * @param array A list of channel field ids
	 * @return array A list of field_names
	 */
	private function getPossibleFields(array $field_channel_fields)
	{
		$cache_key = 'ChannelFields/' . implode(',', $field_channel_fields) . '/field_name';

		if (($possible_fields = ee()->session->cache(__CLASS__, $cache_key, FALSE)) === FALSE)
		{
			$possible_fields = ee('Model')->get('ChannelField', $field_channel_fields)
				->fields('field_id', 'field_name', 'field_type')
				->all()
				->indexBy('field_id');

			ee()->session->set_cache(__CLASS__, $cache_key, $possible_fields);
		}

		return $possible_fields;
	}

	/**
	 * Given a list of entry ids, fluid field ids, and field ids used in the
	 * fluid fields, this bulk-fetches all the needed data for the field fields.
	 *
	 * @param array $entry_id A list of entry ids
	 * @param array $fluid_field_ids A list of fluid field ids
	 * @return obj A Colletion of FluidField model entities
	 */
	private function fetchFluidFields(array $entry_ids, array $fluid_field_ids)
	{
		if (empty($entry_ids) || empty($fluid_field_ids))
		{
			return new Collection([]);
		}

		if (ee('LivePreview')->hasEntryData())
		{
			$data = ee('LivePreview')->getEntryData();
			$entry_ids = array_filter($entry_ids, function($entry_id) use ($data)
			{
				return $entry_id != $data['entry_id'];
			});
		}

		if (empty($entry_ids))
		{
			$fluid_field_data = new Collection([]);
		}
		else
		{
			$fluid_field_data = ee('Model')->get('fluid_field:FluidField')
				->with('ChannelField')
				->filter('fluid_field_id', 'IN', $fluid_field_ids)
				->filter('entry_id', 'IN', $entry_ids)
				->order('fluid_field_id')
				->order('entry_id')
				->order('order')
				->all();

			// Since we store the data in the field's table, and each field has its
			// own table, we'll group our fluid field data by the field_id. This will
			// allow us to run one query per field, fetching all the data across
			// all the fluid fields & entries for each field.
			$fields = [];

			foreach ($fluid_field_data as $fluid_field)
			{
				if ( ! array_key_exists($fluid_field->field_id, $fields))
				{
					$fields[$fluid_field->field_id] = [];
				}

				$fields[$fluid_field->field_id][$fluid_field->field_data_id] = $fluid_field;
			}

			foreach ($fields as $field_id => $fluid_fields)
			{
				$field_data_ids = array_keys($fluid_fields);

				// Captain Obvious says: here we be gettin' the data, Arrrr!
				ee()->db->where_in('id', $field_data_ids);
				$rows = ee()->db->get('channel_data_field_' . $field_id)->result_array();

				foreach($rows as $row)
				{
					$fluid_fields[$row['id']]->setFieldData($row);
				}
			}
		}

		return $this->overrideWithPreviewData($fluid_field_data, $fluid_field_ids);
	}

	/**
	 * Replaces data with preview data when said data is available.
	 *
	 * @param obj Fluid field Collection
	 * @param array An array of fluid field ids
	 * @return obj A Colletion of FluidField model entities
	 */
	public function overrideWithPreviewData(Collection $fluid_field_data, array $fluid_field_ids)
	{
		$fluid_fields = $fluid_field_data->asArray();

		if (ee('LivePreview')->hasEntryData())
		{
			$data = ee('LivePreview')->getEntryData();
			$entry_id = $data['entry_id'];

			// Remove existing fields for the previewed entry, we'll create dummy fields
			// in their place
			$fluid_fields = array_filter($fluid_fields, function($field) use ($entry_id) {
				return $field->entry_id != $entry_id;
			});

			foreach ($fluid_field_ids as $fluid_field_id)
			{
				$i = 1;
				if ( ! isset($data["field_id_{$fluid_field_id}"])
					|| ! isset($data["field_id_{$fluid_field_id}"]['fields']))
				{
					continue;
				}

				foreach ($data["field_id_{$fluid_field_id}"]['fields'] as $key => $value)
				{
					if ($key == 'new_field_0')
					{
						continue;
					}

					foreach (array_keys($value) as $k)
					{
						if (strpos($k, 'field_id_') === 0)
						{
							$field_id = (int) str_replace('field_id_', '', $k);
							break;
						}
					}

					$fluid_field = ee('Model')->make('fluid_field:FluidField');
					$fluid_field->setId("field_id_{$fluid_field_id},{$key}");
					$fluid_field->fluid_field_id = $fluid_field_id;
					$fluid_field->entry_id = $entry_id;
					$fluid_field->field_id = $field_id;
					$fluid_field->order = $i;
					$fluid_field->field_data_id = $i;

					$value['entry_id'] = $entry_id;
					$fluid_field->setFieldData($value);
					$fluid_fields[] = $fluid_field;

					$i++;
				}
			}
		}

		return new Collection($fluid_fields);
	}

	/**
	 * Handles ft.fluid_field.php's replace_tag(), called with each loop of the
	 * channel entries parser
	 *
	 * @param array Channel entry row data typically sent to fieldtypes
	 * @param int  Field ID of field being parsed so we can make sure
	 * @param array Parameters array, unvalidated
	 * @param string Tag data of our field pair
	 * @return string Parsed field data
	 */
	public function parse(array $channel_row, $fluid_field_id, array $params, $tagdata, $content_type = 'channel')
	{
		if (empty($tagdata))
		{
			return '';
		}

		$fluid_field_name = $this->_prefix . $this->fluid_fields[$fluid_field_id];

		$vars = ee('Variables/Parser')->extractVariables($tagdata);
		$singles = array_filter($vars['var_single'], function($val) use ($fluid_field_name)
		{
			return (strpos($val, $fluid_field_name . ':') === 0);
		});

		$cond = [];
		foreach(array_keys($vars['var_pair']) as $field)
		{
			// Must start with the fluid field name
			if(strpos($field, $fluid_field_name . ':') === 0)
			{
				$cond[$field] = FALSE;
			}
		}

		// The field blocks inside a Fluid field are essentially `{if fluid:field}...{/if}`
		// so we'll rewrite them and use the Conditional parser to get what we want each pass
		$cond_tagdata = $this->rewriteFluidTagsAsConditionals($tagdata, array_keys($cond));

		$entry_id = $channel_row['entry_id'];

		// We bulk-fetch all entry's fluid fields in the pre parser. This filters that Collection
		// down to just the data for this fluid field on this entry.
		$fluid_field_data = $this->data->filter(function($fluid_field) use($entry_id, $fluid_field_id)
		{
			return ($fluid_field->entry_id == $entry_id && $fluid_field->fluid_field_id == $fluid_field_id);
		});

		$output = '';

		$total_fields = count($fluid_field_data);

		foreach ($fluid_field_data as $i => $fluid_field)
		{
			$field_name = $fluid_field->ChannelField->field_name;

			// Flip this field's conditional to TRUE so all the other fields will be
			// removed from the tagdata
			$cond[$fluid_field_name.':'.$field_name] = TRUE;
			$my_tagdata = ee()->functions->prep_conditionals($cond_tagdata, $cond);
			$cond[$fluid_field_name.':'.$field_name] = FALSE; // Reset for the next pass

			$meta = [
				$fluid_field_name . ':first' => (int) ($i == 0),
				$fluid_field_name . ':last' => (int) (($i + 1) == $total_fields),
				$fluid_field_name . ':count' => $i + 1,
				$fluid_field_name . ':index' => $i,
				$fluid_field_name . ':current_field_name' => $field_name,
				$fluid_field_name . ':next_field_name' => (($i + 1) < $total_fields) ? $fluid_field_data[$i+1]->ChannelField->field_name : NULL,
				$fluid_field_name . ':prev_field_name' => ($i > 0) ? $fluid_field_data[$i-1]->ChannelField->field_name : NULL,
				$fluid_field_name . ':current_fieldtype' => $fluid_field_data[$i]->ChannelField->field_type,
				$fluid_field_name . ':next_fieldtype' => (($i + 1) < $total_fields) ? $fluid_field_data[$i+1]->ChannelField->field_type : NULL,
				$fluid_field_name . ':prev_fieldtype' => ($i > 0) ? $fluid_field_data[$i-1]->ChannelField->field_type : NULL,
			];

			// a couple aliases to cover some additionally intuitive names
			$meta[$fluid_field_name . ':this_field_name'] = $meta[$fluid_field_name . ':current_field_name'];
			$meta[$fluid_field_name . ':this_fieldtype'] = $meta[$fluid_field_name . ':current_fieldtype'];

			// Templates can include things like `{fluid:count type="text"}` which we can easily
			// evaluate and toss into this meta array for processing, so...why not?
			foreach ($singles as $key => $value)
			{
				if ( ! array_key_exists($key, $meta))
				{
					$meta_value = $this->evaluateSingleVariable($value, $fluid_field_data, $fluid_field);
					if ( ! is_null($meta_value))
					{
						$meta[$key] = $meta_value;
					}
				}
			}

			$field = $fluid_field->getField();

			$row = array_merge($channel_row, $fluid_field->getFieldData()->getValues());
			$row['entry_id'] = $entry_id; // the merge can sometimes wipe this out

			$field->setItem('row', $row);

			$tag = ee('fluid_field:Tag', $my_tagdata);

			$output .= $tag->parse($field, $meta);
		}

		return $output;
	}

	/**
	 * Takes the tag data and a list of field names (i.e. 'fluid:field') and
	 * rewrites the field tag pairs as conditionals.
	 *
	 * @param string The template tagdata to change
	 * @param array An array of field names (i.e. 'fluid:field')
	 * @return string The template with conditionals for the field tag pairs
	 */
	private function rewriteFluidTagsAsConditionals($tagdata, array $field_names)
	{
		foreach($field_names as $field)
		{
			$tagdata = str_replace(LD.$field.RD, LD.'if '.$field.RD, $tagdata);
			$tagdata = str_replace(LD.'/'.$field.RD, LD.'/if'.RD, $tagdata);
		}

		return $tagdata;
	}

	/**
	 * Takes a single variable tag (i.e. {fluid:count type="textarea"}) and
	 * evaluates it, returning its value.
	 *
	 * @param string The variable tag
	 * @param obj  A collection of FluidField model entities
	 * @param obj  The current field in the Fluid being processed
	 * @return int  The evaulated value
	 */
	private function evaluateSingleVariable($var, Collection $fluid_field_data, FluidField $current_field)
	{
		$properties = ee('Variables/Parser')->parseVariableProperties($var);
		$params = $properties['params'];

		if (isset($params['type']))
		{
			$fluid_field_data = $fluid_field_data->filter(function($datum) use($params)
			{
				return ($params['type'] == $datum->ChannelField->field_type);
			});
		}

		if (isset($params['name']))
		{
			$fluid_field_data = $fluid_field_data->filter(function($datum) use($params)
			{
				return ($params['name'] == $datum->ChannelField->field_name);
			});
		}

		$return = NULL;

		if ($fluid_field_data->count() < 1)
		{
			return 0;
		}

		switch($properties['modifier'])
		{
			case 'first':
				$return = (int) ($current_field->getId() == $fluid_field_data[0]->getId());
				break;

			case 'last':
				$return = (int) ($current_field->getId() == $fluid_field_data->last()->getId());
				break;

			case 'count':
				$return = "''";
				foreach ($fluid_field_data as $i => $field)
				{
					if ($current_field->getId() == $field->getId())
					{
						$return = $i + 1;
						break;
					}
				}
				break;

			case 'index':
				$return = "''";
				foreach ($fluid_field_data as $i => $field)
				{
					if ($current_field->getId() == $field->getId())
					{
						$return = $i;
						break;
					}
				}
				break;
		}

		return $return;
	}

}

// EOF
