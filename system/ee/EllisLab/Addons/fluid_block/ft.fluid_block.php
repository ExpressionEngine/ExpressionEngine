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

	function validate($data)
	{
		return TRUE;
	}

	/**
	 * Displays the field for the CP or Frontend, and accounts for grid
	 *
	 * @param string $data Stored data for the field
	 * @return string Field display
	 */
	public function display_field($data)
	{
		if ($this->content_id)
		{
			$block = ee('Model')->get('fluid_block:FluidBlock')
				->with('ChannelFields')
				->filter('entry_id', $this->content_id)
				->filter('block_id', $this->settings['field_id'])
				->all();

		}
		else
		{
			$block = ee('Model')->make('fluid_block:FluidBlock');
		}

		$fieldTemplates = ee('Model')->get('ChannelField', $this->settings['field_channel_fields'])
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
				'field_name'     => $this->field_name,
				'fieldTemplates' => $fieldTemplates,
				'filters'        => ee('View')->make('fluid_block:filters')->render(array('fields' => $fieldTemplates)),
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
		return $data;
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
