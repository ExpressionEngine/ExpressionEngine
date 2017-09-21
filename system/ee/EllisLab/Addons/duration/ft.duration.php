<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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
		$field = array(
			'name'        => $this->field_name,
			'value'       => $data,
			'placeholder' => sprintf(lang('duration_ft_placeholder'), lang('duration_ft_'.$this->settings['units'])),
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

		$data = $data * $multiplier;

		$data = ee('Format')->make('Number', $data)->duration($params);

		// Duration formatter could return one of ## sec., ##:##, or ##:##:##
		$parts = explode(':', $data);

		if (isset($params['format']))
		{
			switch (count($parts))
			{
				case 3:
					$units = ['%h' => $parts[0], '%m' => $parts[1], '%s' => $parts[2]];
					break;
				case 2:
					$units = ['%h' => 0, '%m' => $parts[0], '%s' => $parts[1]];
					break;
				case 1:
				default:
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
				'fields' => array(
					'units' => array(
						'type' => 'select',
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
}
// END CLASS

// EOF
