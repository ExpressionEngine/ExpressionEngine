<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine New Relic Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Newrelic {

	/**
	 * Set the application name
	 *
	 * @access	public
	 * @return	void
	 */
	public function set_appname()
	{
		newrelic_set_appname(APP_NAME.' v'.APP_VER);
	}

	// --------------------------------------------------------------------

	/**
	 * Give New Relic a name for this transaction
	 *
	 * @access	public
	 * @return	void
	 */
	public function name_transaction()
	{
		$transaction_name = (string) ee()->uri->segment(1);

		if (ee()->uri->segment(2) !== FALSE)
		{
			$transaction_name .= '/'.ee()->uri->segment(2);
		}

		newrelic_name_transaction($transaction_name);
	}

	// --------------------------------------------------------------------

	/**
	 * Prevent the New Relic PHP extension from inserting its JavaScript
	 * for this transaction
	 *
	 * @access	public
	 * @return	void
	 */
	public function disable_autorum()
	{
		newrelic_disable_autorum();
	}

	// --------------------------------------------------------------------
}
// END CLASS

/* End of file Newrelic.php */
/* Location: ./system/expressionengine/libraries/Newrelic.php */
