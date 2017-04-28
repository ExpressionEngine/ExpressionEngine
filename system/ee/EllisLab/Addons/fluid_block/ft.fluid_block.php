<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Radio Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Fluid_block_ft extends EE_Fieldtype {

	public $info = array();

	public $has_array_data = TRUE;

	/**
	 * Fetch the fieldtype's name and version from it's addon.setup.php file.
	 */
	public function __construct()
	{
		$addon = ee('Addon')->get('fluid_block');
		$this->info = array(
			'name'    => $addon->getName(),
			'version' => $addon->getVersion()
		);
	}

	public function validate($data)
	{
		return TRUE;
	}

	// Actual saving takes place in post_save so we have an entry_id
	public function save($data)
	{
		if (is_null($data))
		{
			$data = array();
		}

		ee()->session->set_cache(__CLASS__, $this->name(), $data);

		return ' ';
	}

	public function post_save($data)
	{
		// Prevent saving if save() was never called, happens in Channel Form
		// if the field is missing from the form
		if (($data = ee()->session->cache(__CLASS__, $this->name(), FALSE)) === FALSE)
		{
			return;
		}

		$blockData = ee('Model')->get('fluid_block:FluidBlock')
			->with('ChannelField')
			->filter('block_id', $this->field_id)
			->filter('entry_id', $this->content_id)
			->all()
			->indexBy('id');

		$i = 1;
		foreach ($data['rows'] as $key => $value)
		{
			// Existing field
			if (strpos($key, 'row_') === 0)
			{
				$id = str_replace('row_', '', $key);
				$this->updateField($blockData[$id], $i, $value);
				unset($blockData[$id]);
			}
			// New field
			elseif (strpos($key, 'new_row_') === 0)
			{
				foreach (array_keys($value) as $k)
				{
					if (strpos($k, 'field_id_') === 0)
					{
						$field_id = str_replace('field_id_', '', $k);
						$this->addField($i, $field_id, $value);
						break;
					}
				}
			}

			$i++;
		}

		// Remove fields
		foreach ($blockData as $block)
		{
			$this->removeField($block);
		}
	}

	private function prepareData($block, array $values)
	{
		$field_data = $block->getFieldData();
		$field_data->set($values);
		$field = $block->getField($field_data);
		$field->save();

		$values['field_id_' . $field->getId()] = $field->getData();

		$field->postSave();

		$format = $field->getFormat();

		if ( ! is_null($format))
		{
			$values['field_ft_' . $field->getId()] = $format;
		}

		$timezone = $field->getTimezone();

		if ( ! is_null($timezone))
		{
			$values['field_dt_' . $field->getId()] = $timezone;
		}

		return $values;
	}

	private function updateField($block, $order, array $values)
	{
		$values = $this->prepareData($block, $values);

		$block->order = $order;
		$block->save();

		$query = ee('Model/Datastore')->rawQuery();
		$query->set($values);
		$query->where('id', $block->field_data_id);
		$query->update($block->ChannelField->getTableName());
	}

	private function addField($order, $field_id, array $values)
	{
		$block = ee('Model')->make('fluid_block:FluidBlock');
		$block->block_id = $this->field_id;
		$block->entry_id = $this->content_id;
		$block->field_id = $field_id;
		$block->order = $order;

		$values = $this->prepareData($block, $values);

		$values = array_merge($values, array(
			'entry_id' => 0,
		));

		$field = ee('Model')->get('ChannelField', $field_id)->first();

		$query = ee('Model/Datastore')->rawQuery();
		$query->set($values);
		$query->insert($field->getTableName());
		$id = $query->insert_id();

		$block->field_data_id = $id;
		$block->save();
	}

	private function removeField($block)
	{
		$query = ee('Model/Datastore')->rawQuery();
		$query->where('id', $block->field_data_id);
		$query->delete($block->ChannelField->getTableName());

		$block->delete();
	}

	/**
	 * Displays the field for the CP or Frontend, and accounts for grid
	 *
	 * @param string $data Stored data for the field
	 * @return string Field display
	 */
	public function display_field($data)
	{
		$fields = '';

		$fieldTemplates = ee('Model')->get('ChannelField', $this->settings['field_channel_fields'])
			->order('field_label')
			->all();

		$filters = ee('View')->make('fluid_block:filters')->render(array('fields' => $fieldTemplates));

		if ($this->content_id)
		{
			$blockData = ee('Model')->get('fluid_block:FluidBlock')
				->with('ChannelField')
				->filter('block_id', $this->field_id)
				->filter('entry_id', $this->content_id)
				->order('order')
				->all();

			foreach ($blockData as $data)
			{
				$field = $data->getField();

				$field->setName($this->name() . '[rows][row_' . $data->getId() . '][field_id_' . $field->getId() . ']');

				$fields .= ee('View')->make('fluid_block:field')->render(array('field' => $data->ChannelField, 'filters' => $filters));
			}
		}

		$templates = '';

		foreach ($fieldTemplates as $field)
		{
			$f = $field->getField();
			$f->setName($this->name() . '[rows][new_row_0][field_id_' . $field->getId() . ']');

			$templates .= ee('View')->make('fluid_block:field')->render(array('field' => $field, 'filters' => $filters));
		}

		if (REQ == 'CP')
		{
			ee()->cp->add_js_script(array(
				'ui' => array(
					'sortable'
				),
				'file' => array(
					'fields/fluid_block/cp',
					'cp/sort_helper'
				),
			));

			return ee('View')->make('fluid_block:publish')->render(array(
				'fields'         => $fields,
				'fieldTemplates' => $templates,
				'filters'        => $filters,
			));
		}
	}

	public function display_settings($data)
	{
		$custom_field_options = ee('Model')->get('ChannelField')
			->fields('field_id', 'field_label')
			->filter('site_id', 'IN', array(0, ee()->config->item('site_id')))
			->filter('field_type', '!=', 'fluid_block')
			->order('field_label')
			->all()
			->getDictionary('field_id', 'field_label');

		$settings = array(
			array(
				'title'     => 'custom_fields',
				'fields'    => array(
					'field_channel_fields' => array(
						'type' => 'checkbox',
						'wrap' => TRUE,
						'choices' => $custom_field_options,
						'value' => isset($data['field_channel_fields']) ? $data['field_channel_fields'] : array()
					)
				)
			),
		);

		return array('field_options_field_block' => array(
			'label' => 'field_options',
			'group' => 'field_block',
			'settings' => $settings
		));
	}

	public function save_settings($data)
	{
		$defaults = array(
			'field_channel_fields' => array(),
		);

		$all = array_merge($defaults, $data);

		return array_intersect_key($all, $defaults);
	}

	/**
	 * Accept all content types.
	 *
	 * @param string  The name of the content type
	 * @return bool   Accepts all content types
	 */
	public function accepts_content_type($name)
	{
		$incompatible = array('grid', 'fluid_block');
		return ( ! in_array($name, $incompatible));
	}

	/**
	 * Update the fieldtype
	 *
	 * @param string $version The version being updated to
	 * @return boolean TRUE if successful, FALSE otherwise
	 */
	public function update($version)
	{
		return TRUE;
	}

	private function lexTagdata($tagdata)
	{
		$possible_fields = ee('Model')->get('ChannelField', $this->settings['field_channel_fields'])
			->fields('field_name')
			->all()
			->pluck('field_name');

		$tags = array();

		$block = ee('Model')->get('ChannelField', $this->id)
			->fields('field_name')
			->first();

		foreach($possible_fields as $field)
		{
			$tags[$field] = array();

			$tag_variable = $block->field_name . ':' . $field;
			$pattern = '/'.LD.$tag_variable.RD.'(.*)'.LD.'\/'.$tag_variable.RD.'/is';

			if (preg_match($pattern, $tagdata, $matches))
			{
				$tags[$field][] = $matches[1];
			}
		}

		return $tags;
	}

	public function replace_tag($data, $params = '', $tagdata = '')
	{
		$output = '';

		$tags = $this->lexTagdata($tagdata);

		$blockData = ee('Model')->get('fluid_block:FluidBlock')
			->with('ChannelField')
			->filter('block_id', $this->field_id)
			->filter('entry_id', $this->content_id)
			->order('order')
			->all();

		foreach ($blockData as $block)
		{
			$field_name = $block->ChannelField->field_name;

			// Have no tags for this field?
			if ( ! array_key_exists($field_name, $tags))
			{
				continue;
			}

			foreach ($tags[$field_name] as $tag)
			{
				$tag = str_replace("{content}", '{' . $field_name . '}', $tag);
				$tag = str_replace("{content:", '{' . $field_name . ':', $tag);

				$field = $block->getField();

				$field->setItem('row', array_merge($this->row, $block->getFieldData()->getValues()));

				$field_output = $field->replaceTag($tag);
				$output .= ee()->TMPL->swap_var_single($field_name, $field_output, $tag);
			}
		}

		return $output;
	}
}

// EOF
