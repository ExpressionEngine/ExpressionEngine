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

			$html .= '<label>'.form_radio($this->field_name, $value, $selected, $extra).NBS.$key.'</label>';
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
}

// EOF
