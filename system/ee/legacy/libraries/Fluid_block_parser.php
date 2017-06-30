<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Fluid Block parser Class
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Fluid_block_parser {

	public $modifiers = array();
	public $reserved_names = array();
	protected $data = array();
	protected $tags = array();

	public function __construct()
	{
		// The pre processor will accept these modifiers as fields that need querying
		$this->modifiers = array('next_field', 'prev_field', 'total_fields');

		// These names cannot be used for column names because they serve
		// other front-end functions as tag modifiers
		$this->reserved_names = array_merge(
			$this->modifiers,
			array('switch', 'count', 'index')
		);
	}

	/**
	 * Called before each channel entries loop to gather the information
	 * needed to efficiently query the Fluid Block data we need
	 *
	 * @param	string	Tag data for entire channel entries loop
	 * @param	object	Channel preparser object
	 * @param	array	Array of known Fluid Block fields in this channel
	 */
	public function pre_process($tagdata, $pre_parser, $fluid_block_fields, $content_type = 'channel')
	{
		// Bail out if there are no fluid block fields present to parse
		if ( ! preg_match_all(
				"/".LD.'\/?('.preg_quote($pre_parser->prefix()).'(?:(?:'.implode('|', array_flip($fluid_block_fields)).'):?))\b([^}{]*)?'.RD."/",
				$tagdata,
				$matches,
				PREG_SET_ORDER)
			)
		{
			return FALSE;
		}

		$block_ids = array();

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

			// Make sure the supposed field name is an actual Fluid Block field
			if ( ! isset($fluid_block_fields[$field_name]))
			{
				return FALSE;
			}

			// Collect field IDs so we can gather the column data for these fields
			$block_ids[] = $fluid_block_fields[$field_name];
		}

		$block_fields = ee('Model')->get('ChannelField', $block_ids)
			->fields('field_id', 'field_settings', 'field_name')
			->all();

		$found_fields = array();

		foreach ($block_fields as $block_field)
		{
			$returned = $this->lexTagdata($tagdata, $block_field);

			$this->tags[$block_field->field_id] = $returned['tags'];
			$found_fields = array_merge($found_fields, $returned['fields_found']);
		}

		$this->data = $this->fetchFieldBlocks($pre_parser->entry_ids(), $block_ids, $found_fields);

		return TRUE;
	}

	/**
	 * Gets a list of possible field names for this block
	 *
	 * @return array A list of field_names assigned to this block
	 */
	private function getPossibleFields($field_channel_fields)
	{
		$cache_key = 'ChannelFields/' . implode($field_channel_fields, ',') . '/field_name';

		if (($possible_fields = ee()->session->cache(__CLASS__, $cache_key, FALSE)) === FALSE)
		{
			$possible_fields = ee('Model')->get('ChannelField', $field_channel_fields)
				->fields('field_id', 'field_name')
				->all()
				->getDictionary('field_id', 'field_name');

			ee()->session->set_cache(__CLASS__, $cache_key, $possible_fields);
		}

		return $possible_fields;
	}

	private function lexTagdata($tagdata, $block_field)
	{
		$possible_fields = $this->getPossibleFields($block_field->field_settings['field_channel_fields']);

		$tags = array();
		$fields_found = array();

		$block_name = $block_field->field_name;

		foreach($possible_fields as $field_id => $field_name)
		{
			$tags[$field_name] = array();

			$tag_variable = $block_name . ':' . $field_name;

			$pchunks = ee()->api_channel_fields->get_pair_field(
				$tagdata,
				$field_name,
				$block_name . ':'
			);

			foreach ($pchunks as $chk_data)
			{
				list($modifier, $content, $params, $chunk) = $chk_data;

				$tags[$field_name][] = ee('fluid_block:Tag', $content);
				$fields_found[] = $field_id;
			}
		}

		return array(
			'tags' => $tags,
			'fields_found' => array_unique($fields_found)
		);
	}

	private function fetchFieldBlocks(array $entry_ids, array $block_ids, array $field_ids)
	{
		$data = array();

		$blockData = ee('Model')->get('fluid_block:FluidBlock')
			->with('ChannelField')
			->filter('block_id', 'IN', $block_ids)
			->filter('entry_id', 'IN', $entry_ids)
			->filter('field_id', 'IN', $field_ids)
			->order('block_id', 'entry_id', 'order')
			->all();

		// For each `field_id` we'll run one query
		// That query will have a WHERE IN accross all the `field_data_id`s
		$fields = array();

		foreach ($blockData as $block)
		{
			if ( ! array_key_exists($block->field_id, $fields))
			{
				$fields[$block->field_id] = array();
			}

			$fields[$block->field_id][$block->field_data_id] = $block;
		}

		foreach ($fields as $field_id => $blocks)
		{
			$field_data_ids = array_keys($blocks);
			ee()->db->where_in('id', $field_data_ids);
			$rows = ee()->db->get('channel_data_field_' . $field_id)->result_array();

			foreach($rows as $row)
			{
				$blocks[$row['id']]->getFieldData($row);
			}
		}

		return $blockData;
	}

	/**
	 * Handles ft.fluid_block.php's replace_tag(), called with each loop of the
	 * channel entries parser
	 *
	 * @param	array	Channel entry row data typically sent to fieldtypes
	 * @param	int		Field ID of field being parsed so we can make sure
	 * @param	array	Parameters array, unvalidated
	 * @param	string	Tag data of our field pair
	 * @return	string	Parsed field data
	 */
	public function parse($channel_row, $block_id, $params, $tagdata, $content_type = 'channel')
	{
		if (empty($tagdata))
		{
			return '';
		}

		$entry_id = $channel_row['entry_id'];

		$blockData = $this->data->filter(function($block) use($entry_id, $block_id)
		{
			return ($block->entry_id == $entry_id && $block->block_id == $block_id);
		});

		$output = '';

		foreach ($blockData as $block)
		{
			$tags = $this->tags[$block->block_id];

			$field_name = $block->ChannelField->field_name;

			// Have no tags for this field?
			if ( ! array_key_exists($field_name, $tags))
			{
				continue;
			}

			foreach ($tags[$field_name] as $tag)
			{
				$field = $block->getField();

				$field->setItem('row', array_merge($channel_row, $block->getFieldData()->getValues()));

				$output .=  $tag->parse($field);
			}
		}

		return $output;
	}

}

// EOF
