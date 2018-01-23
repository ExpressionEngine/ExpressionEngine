<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * Update
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
		$steps = new ProgressIterator(
			array(
				'install_required_fieldtypes',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Ensure required modules are installed
	 * @return void
	 */
	private function install_required_fieldtypes()
	{
		ee()->load->library('addons/addons_installer');
		ee()->load->library('extensions');

		$installed_fieldtypes = ee('Model')->get('Fieldtype')->all()->pluck('name');

		$required_fieldtypes = array('select', 'text', 'textarea', 'date', 'file', 'grid', 'multi_select', 'checkboxes', 'radio', 'relationship', 'rte');

		foreach ($required_fieldtypes as $fieldtype)
		{
			if ( ! in_array($fieldtype, $installed_fieldtypes))
			{
				ee()->addons_installer->install($fieldtype, 'fieldtype', FALSE);
			}
		}
	}
}

// EOF
