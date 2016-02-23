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

	public $version_suffix = '';
	public $errors = array();

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
				'add_url_field',
				'add_email_address_field',
				'add_toggle_field',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * New "URL" Field Type in 3.2.0
	 */
	private function add_url_field()
	{
		ee()->db->insert('fieldtypes', array(
				'name' => 'url',
				'version' => '1.0.0',
				'settings' => base64_encode(serialize(array())),
				'has_global_settings' => 'n'
			)
		);
	}

	/**
	 * New "Email Address" Field Type in 3.2.0
	 */
	private function add_email_address_field()
	{
		ee()->db->insert('fieldtypes', array(
				'name' => 'email_address',
				'version' => '1.0.0',
				'settings' => base64_encode(serialize(array())),
				'has_global_settings' => 'n'
			)
		);
	}

	/**
	 * Installs the new toggle fieldtype
	 * @return void
	 */
	public function add_toggle_field()
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
// END CLASS

// EOF
