<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Fluid Field Fieldtype
 */
class Fluid_field_ft extends EE_Fieldtype {

	public $info = array();

	public $has_array_data = TRUE;

	private $errors;

	/**
	 * Fetch the fieldtype's name and version from its addon.setup.php file.
	 */
	public function __construct()
	{
		$addon = ee('Addon')->get('fluid_field');
		$this->info = array(
			'name'    => $addon->getName(),
			'version' => $addon->getVersion()
		);

		$this->errors = new \EllisLab\ExpressionEngine\Service\Validation\Result;
	}

	public function validate($field_data)
	{
		$this->errors = new \EllisLab\ExpressionEngine\Service\Validation\Result;

		if (empty($field_data))
		{
			return TRUE;
		}

		$field_templates = ee('Model')->get('ChannelField', $this->settings['field_channel_fields'])
			->order('field_label')
			->all()
			->indexByIds();

		foreach ($field_data['fields'] as $key => $data)
		{
			$field_id = NULL;
			$fluid_field_data_id = NULL;

			foreach (array_keys($data) as $datum)
			{
				if (strpos($datum, 'field_id_') === 0)
				{
					$field_id = str_replace('field_id_', '', $datum);
					break;
				}
			}

			$field_name = $this->name() . '[fields][' . $key . '][field_id_' . $field_id . ']';

			// Is this AJAX validation? If so, just return the result for the field
			// we're validating by skipping the others
			if (ee()->input->is_ajax_request() && strpos(ee()->input->post('ee_fv_field'), $field_name) === FALSE)
			{
				continue;
			}

			if (strpos($key, 'field_') === 0)
			{
				$fluid_field_data_id = (int) str_replace('field_', '', $key);
			}

			$field = clone $field_templates[$field_id];

			$f = $field->getField();
			$ft_instance = $f->getNativeField();

			if (isset($ft_instance->has_array_data)
				&& $ft_instance->has_array_data
				&& ! is_array($data['field_id_' . $field_id]))
			{
				$data['field_id_' . $field_id] = array();
			}

			$f->setName($field_name);
			$f = $this->setupFieldInstance($f, $data, $fluid_field_data_id);

			$validator = ee('Validation')->make();
			$validator->defineRule('validateField', function($key, $value, $parameters, $rule) use ($f) {
				return $f->validate($value);
			});

			$validator->setRules(array(
				$f->getName() => 'validateField'
			));

			$result = $validator->validate(array($f->getName() => $f->getData()));

			if ($result->isNotValid())
			{
				foreach($result->getFailed() as $field_name => $rules)
				{
					foreach ($rules as $rule)
					{
						$this->errors->addFailed($field_name, $rule);
					}
				}
			}
		}

		if (ee()->input->is_ajax_request())
		{
			if ($this->errors->hasErrors($field_name))
			{
				$errors = $this->errors->getErrors($field_name);
				return $errors['callback'];
			}

			return TRUE;
		}

		return ($this->errors->isValid()) ? TRUE : 'form_validation_error';
	}

	// Actual saving takes place in post_save so we have an entry_id
	public function save($data)
	{
		if (is_null($data))
		{
			$data = array('fields' => []);
		}

		ee()->session->set_cache(__CLASS__, $this->name(), $data);

		$fluid_field_data = $this->getFieldData()->indexBy('id');

		$compiled_data_for_search = array();

		foreach ($data['fields'] as $key => $value)
		{
			if ($key == 'new_field_0')
			{
				continue;
			}

			$fluid_field_data_id = 0;

			// Existing field
			if (strpos($key, 'field_') === 0)
			{
				$id = str_replace('field_', '', $key);
				$field = $fluid_field_data[$id]->getField();
				$fluid_field_data_id = $fluid_field_data[$id]->getId();
			}
			// New field
			elseif (strpos($key, 'new_field_') === 0)
			{
				foreach (array_keys($value) as $k)
				{
					if (strpos($k, 'field_id_') === 0)
					{
						$field_id = str_replace('field_id_', '', $k);

						$fluid_field = ee('Model')->make('fluid_field:FluidField');
						$fluid_field->fluid_field_id = $this->field_id;
						$fluid_field->field_id = $field_id;

						$field = $fluid_field->getField();
						$fluid_field_data_id = $key;
						break;
					}
				}
			}

			$field->setItem('field_search', true);
			$field->setItem('fluid_field_data_id', $fluid_field_data_id);

			foreach ($value as $field_data)
			{
				$field->setData($field_data);
				$compiled_data_for_search[] = $field->save($field_data);
			}
		}

		return implode(' ', $compiled_data_for_search);
	}

	public function post_save($data)
	{
		// Prevent saving if save() was never called, happens in Channel Form
		// if the field is missing from the form
		if (($data = ee()->session->cache(__CLASS__, $this->name(), FALSE)) === FALSE)
		{
			return;
		}

		$fluid_field_data = $this->getFieldData()->indexBy('id');

		$i = 1;
		foreach ($data['fields'] as $key => $value)
		{
			if ($key == 'new_field_0')
			{
				continue;
			}

			// Existing field
			if (strpos($key, 'field_') === 0)
			{
				$id = str_replace('field_', '', $key);
				$this->updateField($fluid_field_data[$id], $i, $value);
				unset($fluid_field_data[$id]);
			}
			// New field
			elseif (strpos($key, 'new_field_') === 0)
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
		foreach ($fluid_field_data as $fluid_field)
		{
			$this->removeField($fluid_field);
		}
	}

	private function prepareData($fluid_field, array $values)
	{
		$field_data = $fluid_field->getFieldData();
		$field_data->set($values);
		$field = $fluid_field->getField($field_data);
		$field->setItem('fluid_field_data_id', $fluid_field->getId());
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

	private function updateField($fluid_field, $order, array $values)
	{
		$values = $this->prepareData($fluid_field, $values);

		$fluid_field->order = $order;
		$fluid_field->save();

		$query = ee('db');
		$query->set($values);
		$query->where('id', $fluid_field->field_data_id);
		$query->update($fluid_field->ChannelField->getTableName());
	}

	private function addField($order, $field_id, array $values)
	{
		$fluid_field = ee('Model')->make('fluid_field:FluidField');
		$fluid_field->fluid_field_id = $this->field_id;
		$fluid_field->entry_id = $this->content_id;
		$fluid_field->field_id = $field_id;
		$fluid_field->order = $order;
		$fluid_field->field_data_id = 0;
		$fluid_field->save();

		$values = $this->prepareData($fluid_field, $values);

		$values = array_merge($values, array(
			'entry_id' => 0,
		));

		$field = ee('Model')->get('ChannelField', $field_id)->first();

		$query = ee('db');
		$query->set($values);
		$query->insert($field->getTableName());
		$id = $query->insert_id();

		$fluid_field->field_data_id = $id;
		$fluid_field->save();
	}

	private function removeField($fluid_field)
	{
		$query = ee('db');
		$query->where('id', $fluid_field->field_data_id);
		$query->delete($fluid_field->ChannelField->getTableName());

		$fluid_field->delete();
	}

	/**
	 * Displays the field for the CP or Frontend, and accounts for grid
	 *
	 * @param string $data Stored data for the field
	 * @return string Field display
	 */
	public function display_field($field_data)
	{
		$fields = '';

		$field_templates = ee('Model')->get('ChannelField', $this->settings['field_channel_fields'])
			->order('field_label')
			->all();

		$filter_options = $field_templates->map(function($field) {
			return $field->getField();
		});

		$filters = ee('View')->make('fluid_field:filters')->render(array('fields' => $filter_options));

		$field_templates = $field_templates->indexByIds();

		if ( ! is_array($field_data))
		{
			if ($this->content_id)
			{
				$fluid_field_data = $this->getFieldData();

				foreach ($fluid_field_data as $data)
				{
					$field = $data->getField();

					$field->setName($this->name() . '[fields][field_' . $data->getId() . '][field_id_' . $field->getId() . ']');

					$fields .= ee('View')->make('fluid_field:field')->render([
						'field' => $field,
						'field_name' => $data->ChannelField->field_name,
						'filters' => $filters,
						'errors' => $this->errors,
						'reorderable' => TRUE,
						'show_field_type' => TRUE
					]);
				}
			}

		}
		else
		{
			foreach ($field_data['fields'] as $key => $data)
			{
				$field_id = NULL;

				foreach (array_keys($data) as $datum)
				{
					if (strpos($datum, 'field_id_') === 0)
					{
						$field_id = str_replace('field_id_', '', $datum);
						break;
					}
				}

				$field = clone $field_templates[$field_id];

				$f = $field->getField();

				$f->setName($this->name() . '[fields][' . $key . '][field_id_' . $field->getId() . ']');

				$f = $this->setupFieldInstance($f, $data, $field_id);

				$fields .= ee('View')->make('fluid_field:field')->render([
					'field' => $f,
					'field_name' => $field->field_name,
					'filters' => $filters,
					'errors' => $this->errors,
					'reorderable' => TRUE,
					'show_field_type' => TRUE
				]);
			}
		}

		$templates = '';

		foreach ($field_templates as $field)
		{
			$f = $field->getField();
			$f->setItem('fluid_field_data_id', NULL);
			$f->setName($this->name() . '[fields][new_field_0][field_id_' . $field->getId() . ']');

			$templates .= ee('View')->make('fluid_field:field')->render([
				'field' => $f,
				'field_name' => $field->field_name,
				'filters' => $filters,
				'errors' => $this->errors,
				'reorderable' => TRUE,
				'show_field_type' => TRUE
			]);
		}

		if (REQ == 'CP')
		{
			ee()->cp->add_js_script(array(
				'ui' => array(
					'sortable'
				),
				'file' => array(
					'fields/fluid_field/cp',
					'cp/sort_helper'
				),
			));

			return ee('View')->make('fluid_field:publish')->render(array(
				'fields'          => $fields,
				'field_templates' => $templates,
				'filters'         => $filters,
			));
		}
	}

	public function display_settings($data)
	{
		$custom_field_options = ee('Model')->get('ChannelField')
			->filter('site_id', 'IN', [ee()->config->item('site_id'), 0])
			->filter('field_type', '!=', 'fluid_field')
			->order('field_label')
			->all()
			->filter(function($field) {
				return $field->getField()->acceptsContentType('fluid_field');
			})
			->map(function($field) {
				return [
					'label' => $field->field_label,
					'value' => $field->getId(),
					'instructions' => LD.$field->field_name.RD
				];
			});

		$settings = array(
			array(
				'title'     => 'custom_fields',
				'fields'    => array(
					'field_channel_fields' => array(
						'type' => 'checkbox',
						'choices' => $custom_field_options,
						'value' => isset($data['field_channel_fields']) ? $data['field_channel_fields'] : array(),
						'no_results' => [
							'text' => sprintf(lang('no_found'), lang('fields')),
							'link_text' => 'add_new',
							'link_href' => ee('CP/URL')->make('fields/create')
						]
					)
				)
			),
		);

		if ( ! $this->isNew())
		{
			ee()->javascript->set_global(array(
				'fields.fluid_field.fields' => $data['field_channel_fields']
			));

			ee()->cp->add_js_script(array(
				'file' => 'fields/fluid_field/settings',
			));

			$modal = ee('View')->make('fluid_field:modal')->render();
			ee('CP/Modal')->addModal('remove-field', $modal);
		}

		return array('field_options_fluid_field' => array(
			'label' => 'field_options',
			'group' => 'fluid_field',
			'settings' => $settings
		));
	}

	public function save_settings($data)
	{
		$defaults = array(
			'field_channel_fields' => array(),
		);

		$all = array_merge($defaults, $data);

		$fields = ee('Model')->get('ChannelField', $all['field_channel_fields'])
			->filter('legacy_field_data', 'y')
			->all();

		foreach ($fields as $field)
		{
			$field->createTable();
		}

		if (isset($this->settings['field_channel_fields']))
		{
			$this->settings['field_channel_fields'] = array_filter($this->settings['field_channel_fields'], function($value) {
				return is_numeric($value);
			});

			$removed_fields = (array_diff($this->settings['field_channel_fields'], $all['field_channel_fields']));

			if ( ! empty($removed_fields))
			{
				$fluid_field_data = ee('Model')->get('fluid_field:FluidField')
					->filter('fluid_field_id', $this->field_id)
					->filter('field_id', 'IN', $removed_fields)
					->all()
					->delete();

				$fields = ee('Model')->get('ChannelField', $removed_fields)
					->fields('field_label')
					->all()
					->pluck('field_label');

				if ( ! empty($fields))
				{
					ee()->logger->log_action(sprintf(lang('removed_fields_from_fluid_field'), $this->settings['field_label'], '<b>' . implode('</b>, <b>', $fields) . '</b>'));
				}
			}
		}

		return array_intersect_key($all, $defaults);
	}

	public function settings_modify_column($data)
	{
		if (isset($data['ee_action']) && $data['ee_action'] == 'delete')
		{
			$fluid_field_data = ee('Model')->get('fluid_field:FluidField')
				->filter('fluid_field_id', $data['field_id'])
				->all()
				->delete();
		}

		$columns['field_id_' . $data['field_id']] = [
			'type' => 'mediumtext',
			'null' => TRUE
		];

		return $columns;
	}

	/**
	 * Called when entries are deleted
	 *
	 * @param	array	Entry IDs to delete data for
	 */
	public function delete($entry_ids)
	{
		$fluid_field_data = ee('Model')->get('fluid_field:FluidField')
			->filter('fluid_field_id', $this->field_id)
			->filter('entry_id', 'IN', $entry_ids)
			->all()
			->delete();
	}

	/**
	 * Accept all but grid and fluid_field content types.
	 *
	 * @param string  The name of the content type
	 * @return bool   Accepts all content types
	 */
	public function accepts_content_type($name)
	{
		$incompatible = array('grid', 'fluid_field');
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

	/**
	 * Gets the fluid field's data for a given field and entry
	 *
	 * @param int $fluid_field_id The id for the field
	 * @param int $entry_id The id for the entry
	 * @return obj A Collection of FluidField objects
	 */
	private function getFieldData($fluid_field_id = '', $entry_id = '')
	{
		$fluid_field_id = ($fluid_field_id) ?: $this->field_id;
		$entry_id = ($entry_id) ?: $this->content_id;

		$cache_key = "FluidField/{$fluid_field_id}/{$entry_id}";

		if (($fluid_field_data = ee()->session->cache("FluidField", $cache_key, FALSE)) === FALSE)
		{
			$fluid_field_data = ee('Model')->get('fluid_field:FluidField')
				->with('ChannelField')
				->filter('fluid_field_id', $fluid_field_id)
				->filter('entry_id', $entry_id)
				->order('order')
				->all();

			ee()->session->set_cache("FluidField", $cache_key, $fluid_field_data);
		}

		return $fluid_field_data;
	}

	/**
	 * Sets the data, format, and timzeone for a field
	 *
	 * @param FieldFacade $field The field
	 * @param array $data An associative array containing the data to set
	 * @return FieldFacade The field.
	 */
	private function setupFieldInstance($field, array $data, $fluid_field_data_id = NULL)
	{
		$field_id = $field->getId();

		$field->setContentId($this->content_id);

		$field->setData($data['field_id_' . $field_id]);

		if (isset($data['field_ft_' . $field_id]))
		{
			$field->setFormat($data['field_ft_' . $field_id]);
		}

		if (isset($data['field_dt_' . $field_id]))
		{
			$field->setTimezone($data['field_dt_' . $field_id]);
		}

		$field->setItem('fluid_field_data_id', $fluid_field_data_id);

		return $field;
	}

	/**
	 * Replace Fluid Field template tags
	 */
	public function replace_tag($data, $params = '', $tagdata = '')
	{
		ee()->load->library('fluid_field_parser');

		// not in a channel scope? pre-process may not have been run.
		if ($this->content_type() != 'channel')
		{
			ee()->load->library('api');
			ee()->legacy_api->instantiate('channel_fields');
			ee()->grid_parser->fluid_field_field_names[$this->id()] = $this->name();
		}

		return ee()->fluid_field_parser->parse($this->row, $this->id(), $params, $tagdata, $this->content_type());
	}

	/**
	 * :length modifier
	 */
	public function replace_length($data, $params = '', $tagdata = '')
	{
		return $this->replace_total_fields($data, $params, $tagdata);
	}

	/**
	 * :total_fields modifier
	 */
	public function replace_total_fields($data, $params = '', $tagdata = '')
	{
		ee()->load->library('fluid_field_parser');

		$fluid_field_data = $this->getFieldData();

		if (ee('LivePreview')->hasEntryData())
		{
			$data = ee('LivePreview')->getEntryData();

			if ($data['entry_id'] == $this->content_id())
			{
				$fluid_field_data = ee()->fluid_field_parser->overrideWithPreviewData(
					$fluid_field_data,
					[$this->id()]
				);

				if ( ! isset($data["field_id_{$this->id()}"])
					|| ! isset($data["field_id_{$this->id()}"]['fields']))
				{
					return 0;
				}
			}
		}

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

		return ($fluid_field_data) ? count($fluid_field_data) : 0;
	}
}

// EOF
