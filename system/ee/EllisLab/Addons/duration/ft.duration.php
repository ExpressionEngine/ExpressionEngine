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
 * Duration Fieldtype
 */
class Duration_Ft extends EE_Fieldtype {

	/**
	 * @var array $info Legacy Fieldtype info array
	 */
	public $info = array(
		'name'    => 'Duration',
		'version' => '1.0.0'
	);

	/**
	 * @var bool $has_array_data Whether or not this Fieldtype is setup to parse as a tag pair
	 */
	public $has_array_data = FALSE;

	/**
	 * Validate Field
	 *
	 * @param  array  $data  Field data
	 * @return mixed  TRUE when valid, an error string when not
	 */
	public function validate($data)
	{
		ee()->lang->loadfile('fieldtypes');

		if ($data == '')
		{
			return TRUE;
		}

		if (strpos($data, ':'))
		{
			$data = $this->convertFromColonNotation($data);
		}

		if ( ! is_numeric($data))
		{
			return lang('numeric');
		}

		return TRUE;
	}

	/**
	 * Save Field
	 *
	 * @param  array   $data  Field data
	 * @return string  Prepped Form field
	 */
	public function save($data)
	{
		// Make sure empty is truly empty
		if (trim($data) == '')
		{
			$data = NULL;
		}

		return $data;
	}

	/**
	 * Display Field
	 *
	 * @param  array   $data  Field data
	 * @return string  Form field
	 */
	public function display_field($data)
	{
		ee()->lang->loadfile('fieldtypes');

		$field = array(
			'name'        => $this->field_name,
			'value'       => $data,
			'placeholder' => sprintf(
				lang('duration_ft_placeholder'),
				lang('duration_ft_'.$this->settings['units']),
				$this->getColonNotationFormat()
			),
		);

		if ($this->get_setting('field_disabled'))
		{
			$field['disabled'] = 'disabled';
		}

		return form_input($field);
	}

	/**
	 * Replace Tag
	 *
	 * @param  string  $data     The URL
	 * @param  array   $params   Variable tag parameters
	 * @param  mixed   $tagdata  The tagdata if a var pair, FALSE if not
	 * @return string  Parsed string
	 */
	public function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		if (strpos($data, ':'))
		{
			$data = $this->convertFromColonNotation($data);
		}
		else
		{
			$data = $this->applyMultiplier($data);
		}

		$data = ee('Format')->make('Number', $data)->duration($params);

		// Duration formatter could return one of ## sec., ##:##, or ##:##:##
		$parts = explode(':', $data);

		if (isset($params['format']))
		{
			switch (count($parts))
			{
				// hh:mm:ss
				case 3:
					$units = ['%h' => $parts[0], '%m' => $parts[1], '%s' => $parts[2]];
					break;
				// mm:ss
				case 2:
					$units = ['%h' => 0, '%m' => $parts[0], '%s' => $parts[1]];
					break;
				// ss sec.
				case 1:
				default:
					// cast to int because the Number formatter will include a seconds abbreviation based on the locale
					$units = ['%h' => 0, '%m' => 0, '%s' => (int) $parts[0]];
					break;
			}

			$data = str_replace(array_keys($units), array_values($units), $params['format']);
		}
		elseif (isset($params['include_seconds']) && get_bool_from_string($params['include_seconds']) === FALSE)
		{
			array_pop($parts);
			$data = implode(':', $parts);
		}

		return $data;
	}

	/**
	 * Display Settings
	 *
	 * @param  array  $data  Field Settings
	 * @return array  Field options
	 */
	public function display_settings($data)
	{
		ee()->lang->loadfile('fieldtypes');

		$settings = array(
			array(
				'title' => 'duration_ft_units',
				'desc' => 'duration_ft_units_desc',
				'fields' => array(
					'units' => array(
						'type' => 'radio',
						'choices' => $this->getUnits(),
						'value' => (isset($data['units'])) ? $data['units'] : 'minutes',
						'required' => TRUE
					)
				)
			),
		);

		if ($this->content_type() == 'grid')
		{
			return array('field_options' => $settings);
		}

		return array('field_options_duration' => array(
			'label'    => 'field_options',
			'group'    => 'duration',
			'settings' => $settings
		));
	}

	/**
	 * Save Settings
	 *
	 * @param  array  $data  Field data
	 * @return array  Settings to save
	 */
	public function save_settings($data)
	{
		$defaults = array(
			'units' => 'minutes',
		);

		$all = array_merge($defaults, $data);

		return array_intersect_key($all, $defaults);
	}

	/**
	 * Accept all content types.
	 *
	 * @param  string  The name of the content type
	 * @return bool    Accepts all content types
	 */
	public function accepts_content_type($name)
	{
		return TRUE;
	}

	/**
	 * Get Units options
	 *
	 * @return array Units options
	 */
	private function getUnits()
	{
		return [
			'seconds' => lang('duration_ft_seconds'),
			'minutes' => lang('duration_ft_minutes'),
			'hours' => lang('duration_ft_hours'),
		];
	}

	/**
	 * Convert from ##:##:## notation
	 * @param  string $duration Duration, in ##:##:## notation
	 * @return int Duration, in terms of the field's units
	 */
	private function convertFromColonNotation($duration)
	{
		$parts = explode(':', $duration);

		switch (count($parts))
		{
			// hh:mm:ss
			case 3:
				$seconds = ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
				break;
			// mm:ss
			case 2:
				$seconds = ($parts[0] * 60) + $parts[1];

				// if they input ##:## with a "minutes" field, the implied format is hh:mm rather than mm:ss
				if ($this->settings['units'] == 'minutes')
				{
					$seconds = $seconds * 60;
				}
				break;
			// ss
			case 1:
			default:
				$seconds = $parts[0];
				break;
		}

		return $seconds;
	}

	/**
	 * Apply a multiplier based on the field's units setting
	 *
	 * @param  int $number Number to apply the multiplier to
	 * @return int Duration, in terms of the field's units
	 */
	private function applyMultiplier($number)
	{
		switch ($this->settings['units'])
		{
			case 'hours':
				$multiplier = 3600;
				break;
			case 'minutes':
				$multiplier = 60;
				break;
			case 'seconds':
			default:
				$multiplier = 1;
				break;
		}

		return $number * $multiplier;
	}

	/**
	 * Get the colon notation format based on field's units setting
	 * e.g. hh:mm, hh:mm:ss, etc.
	 *
	 * @return string colon notation format
	 */
	private function getColonNotationFormat()
	{
		switch ($this->settings['units'])
		{
			case 'hours':
				return lang('duration_ft_hh');
			case 'minutes':
				return lang('duration_ft_hhmm');
			case 'seconds':
			default:
				return lang('duration_ft_hhmmss');
		}
	}
}
// END CLASS

// EOF
