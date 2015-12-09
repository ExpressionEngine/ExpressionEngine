<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0.6
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		ee()->load->dbforge();

		$steps = new ProgressIterator(
			array(
				'_synchronize_layouts',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	// -------------------------------------------------------------------------

	/**
	 * Fields added after a layout was crated, never made it into the layout.
	 *
	 * @return void
	 */
	private function _synchronize_layouts()
	{
		$layouts = ee('Model')->get('ChannelLayout')->all();

		foreach ($layouts as $layout)
		{
			// Account for any new fields that have been added to the channel
			// since the last edit
			$custom_fields = $layout->Channel->CustomFields->getDictionary('field_id', 'field_id');

			foreach ($layout->field_layout as $section)
			{
				foreach ($section['fields'] as $field_info)
				{
					if (strpos($field_info['field'], 'field_id_') == 0)
					{
						$id = str_replace('field_id_', '', $field_info['field']);
						unset($custom_fields[$id]);
					}
				}
			}

			$field_layout = $layout->field_layout;

			foreach ($custom_fields as $id => $val)
			{
				$field_info = array(
					'field'     => 'field_id_' . $id,
					'visible'   => TRUE,
					'collapsed' => FALSE
				);
				$field_layout[0]['fields'][] = $field_info;
			}

			$layout->field_layout = $field_layout;
		}
	}
}
/* END CLASS */

/* End of file ud_3_00_06.php */
/* Location: ./system/expressionengine/installer/updates/ud_3_00_06.php */
