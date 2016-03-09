<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
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
		$appname = (string) ee()->config->item('newrelic_app_name');

		// -------------------------------------------
		//	Hidden Configuration Variable
		//	- newrelic_app_name => Change application name that appears in
		//	  the New Relic dashboard
		// -------------------------------------------*/
		if ( ! empty($appname))
		{
			$appname .= ' - ';
		}

		// -------------------------------------------
		//	Hidden Configuration Variable
		//	- newrelic_include_version_number => Whether or not to include the version
		//    number with the application name
		// -------------------------------------------*/
		$version = (ee()->config->item('newrelic_include_version_number') == 'y') ? ' v'.APP_VER : '';

		newrelic_set_appname($appname.APP_NAME.$version);
	}

	// --------------------------------------------------------------------

	/**
	 * Give New Relic a name for this transaction
	 *
	 * @access	public
	 * @return	void
	 */
	public function name_transaction($transaction_name)
	{
		// Add a custom parameter of the URI string
		newrelic_add_custom_parameter('uri', ee()->uri->uri_string);

		// Append site label if MSM is enabled to easily differentiate
		// between similar requests
		if (ee()->config->item('multiple_sites_enabled') == 'y')
		{
			$transaction_name .= ' - ' . ee()->config->item('site_label');
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

// EOF
