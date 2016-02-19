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
		$steps = new ProgressIterator(
			array(
				'add_email_address_field'
			)
		);

		foreach ($steps as $k => $v)
		{
			try
			{
				$this->$v();
			}
			catch (Exception $e)
			{
				$this->errors[] = $e->getMessage();
			}
		}

		return empty($this->errors);
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
}
// END CLASS

// EOF
