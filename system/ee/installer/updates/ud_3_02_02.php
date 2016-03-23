<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.2.1
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
		ee()->load->library('addons');
		ee()->load->library('addons/addons_installer');
		ee()->load->library('extensions');

		$installed_fieldtypes = ee()->addons->get_installed('fieldtypes');
		$installed_fieldtypes = array_keys($installed_fieldtypes);

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
