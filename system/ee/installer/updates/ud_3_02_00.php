<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/license
 * @link		https://ellislab.com
 * @since		Version 3.2.0
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
 * @link		https://ellislab.com
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
				'install_toggle_fieldtype',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Installs the new toggle fieldtype
	 * @return void
	 */
	public function install_toggle_fieldtype()
	{
		ee()->db->insert('fieldtypes',
			array(
				'name'					=> 'toggle',
				'version'				=> '1.0.0',
				'settings'				=> base64_encode(serialize(array())),
				'has_global_settings'	=> 'n',
			)
		);
	}
}
/* END CLASS */

// EOF
