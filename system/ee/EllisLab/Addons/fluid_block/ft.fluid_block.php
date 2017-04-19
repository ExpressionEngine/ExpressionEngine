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

	public $has_array_data = FALSE;

	// used in display_field() below to set
	// some defaults for third party usage
	public $settings_vars = array(
		'field_default_value'	=> '',
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
	 * Displays the field for the CP or Frontend, and accounts for grid
	 *
	 * @param string $data Stored data for the field
	 * @return string Field display
	 */
	public function display_field($data)
	{
		$this->settings = array_merge($this->settings_vars, $this->settings);

		$data = is_null($data) ? $this->settings['field_default_value'] : $data;

		ee()->db->select('field_id');
		ee()->db->where('block_id', $this->settings['field_id']);
		$query = ee()->db->get('fluid_block_fields');

		$field_ids = array();

		foreach ($query->result_array() as $row)
		{
			$field_ids[] = $row['field_id'];
		}

		$fields = ee('Model')->get('ChannelField', $field_ids)
			->order('field_label')
			->all();

		if (REQ == 'CP')
		{
			ee()->cp->add_js_script(array(
				'file' => array(
					'fields/fluid_block/cp'
				),
			));

			return ee('View')->make('fluid_block:publish')->render(array(
				'field_name' => $this->field_name,
				'fields'     => $fields,
				'filters'    => ee('View')->make('fluid_block:filters')->render(array('fields' => $fields)),
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

		$html = form_fieldset('').$html.form_fieldset_close();

		return $html;
	}

	public function display_settings($data)
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
						'content' => $this->display_field($data['field_default_value'])
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

	public function save_settings($data)
	{
		$all = array_merge($this->settings_vars, $data);

		return array_intersect_key($all, $this->settings_vars);
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
}

// EOF
