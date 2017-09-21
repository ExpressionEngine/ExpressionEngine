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

		if ( ! ctype_digit($data))
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
	public function osave($data)
	{
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
			'placeholder' => 'Duration, in minutes'
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
				$multiplier = 360;
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

		return ee('Format')->make('Number', $data)->duration($params);
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
			'days' => lang('duration_ft_days'),
		];
	}
}
// END CLASS

// EOF
