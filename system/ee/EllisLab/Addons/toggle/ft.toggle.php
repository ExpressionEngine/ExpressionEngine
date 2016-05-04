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
class Toggle_ft extends EE_Fieldtype {

	var $info = array();

	var $has_array_data = FALSE;

	// used in display_field() below to set
	// some defaults for third party usage
	var $settings_vars = array(
		'field_default_value'	=> '0',
	);

	/**
	 * Fetch the fieldtype's name and version from it's addon.setup.php file.
	 */
	public function __construct()
	{
		$addon = ee('Addon')->get('toggle');
		$this->info = array(
			'name'    => $addon->getName(),
			'version' => $addon->getVersion()
		);
	}

	/**
	 * @see EE_Fieldtype::validate()
	 */
	public function validate($data)
	{
		if ($data === FALSE
			|| $data == ''
			|| $data == '1'
			|| $data == '0')
		{
			return TRUE;
		}

		return ee()->lang->line('invalid_selection');
	}

	/**
	 * @see EE_Fieldtype::save()
	 */
	public function save($data)
	{
		return (int) $data;
	}

	/**
	 * @see EE_Fieldtype::display_field()
	 */
	public function display_field($data)
	{
		return $this->_display_field($data);
	}

	/**
	 * @see _display_field()
	 */
	public function grid_display_field($data)
	{
		return $this->_display_field(form_prep($data), 'grid');
	}

	/**
	 * Displays the field for the CP or Frontend, and accounts for grid
	 *
	 * @param string $data Stored data for the field
	 * @param string $container What type of container is this field in, 'fieldset' or 'grid'?
	 * @return string Field display
	 */
	private function _display_field($data, $container = 'fieldset')
	{
		$this->settings = array_merge($this->settings_vars, $this->settings);

		$data = is_null($data) ? $this->settings['field_default_value'] : $data;

		if (REQ == 'CP')
		{
			ee()->cp->add_js_script(array(
				'file' => array(
					'fields/toggle/cp'
				),
			));

			return ee('View')->make('toggle:publish')->render(array(
				'field_name' => $this->field_name,
				'selected'   => $data,
				'disabled'   => $this->get_setting('field_disabled')
			));
		}

		$field_options = array(
			lang('on') => 1,
			lang('off') => 0
		);

		$html = '';
		$class = 'choice mr';

		foreach($field_options as $key => $value)
		{
			$selected = ($value == $data);

			$html .= '<label>'.form_radio($this->field_name, $value, $selected).NBS.$key.'</label>';
		}

		switch ($container)
		{
			case 'grid':
				$html = $this->grid_padding_container($html);
				break;

			default:
				$html = form_fieldset('').$html.form_fieldset_close();
				break;
		}

		return $html;
	}

	function display_settings($data)
	{
		$defaults = array(
			'field_default_value' => 0
		);

		foreach ($defaults as $setting => $value)
		{
			$data[$setting] = isset($data[$setting]) ? $data[$setting] : $value;
		}

		$this->field_name = 'field_default_value';

		$settings = array(
			array(
				'title'     => 'default_value',
				'desc'      => 'toggle_default_value_desc',
				'desc_cont' => 'toggle_default_value_desc_cont',
				'fields'    => array(
					'field_default_value' => array(
						'type' => 'html',
						'content' => $this->_display_field($data['field_default_value'])
					)
				)
			),
		);

		if ($this->content_type() == 'grid')
		{
			return array('field_options' => $settings);
		}

		return array('field_options_toggle' => array(
			'label' => 'field_options',
			'group' => 'toggle',
			'settings' => $settings
		));
	}

	function save_settings($data)
	{
		$all = array_merge($this->settings_vars, $data);

		return array_intersect_key($all, $this->settings_vars);
	}

	/**
	 * Set the column to be TINYINT
	 *
	 * @param array $data The field data
	 * @return array  [column => column_definition]
	 */
	public function settings_modify_column($data)
	{
		return $this->get_column_type($data);
	}

	/**
	 * Set the grid column to be TINYINT
	 *
	 * @param array $data The field data
	 * @return array  [column => column_definition]
	 */
	public function grid_settings_modify_column($data)
	{
		return $this->get_column_type($data, TRUE);
	}

	/**
	 * Helper method for column definitions
	 *
	 * @param array $data The field data
	 * @param bool  $grid Is grid field?
	 * @return array  [column => column_definition]
	 */
	protected function get_column_type($data, $grid = FALSE)
	{
		$id = ($grid) ? 'col_id' : 'field_id';
		$default_value = ($grid) ? $data['field_default_value'] : $data['field_settings']['field_default_value'];

		return array(
			$id.'_'.$data[$id] => array(
				'type'		=> 'TINYINT',
				'null'      => FALSE,
				'default'   => $default_value
			)
		);
	}

	/**
	 * Accept all content types.
	 *
	 * @param string  The name of the content type
	 * @return bool   Accepts all content types
	 */
	public function accepts_content_type($name)
	{
		return TRUE;
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
}

// EOF
