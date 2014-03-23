<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Pings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Pings {

	protected $ping_result;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Is Registered?
	 *
	 * @return bool
	 **/
	public function is_registered()
	{
		if ( ! IS_CORE && ee()->config->item('license_number') == '')
		{
			return FALSE;
		}

		$cached = ee()->cache->get('software_registration', Cache::GLOBAL_SCOPE);

		if ( ! $cached OR $cached != ee()->config->item('license_number'))
		{
			// hard fail only when no valid license is entered
			if ( ! $this->_do_ping() && ee()->config->item('license_number') == '')
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	public function get_version_info()
	{

	}

	// --------------------------------------------------------------------

	private function _do_ping()
	{
		$registration = 'wefojwefojseofijsef';
		ee()->cache->save('software_registration', $registration, 60*60*24*7, Cache::GLOBAL_SCOPE);

		return TRUE;
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file Pings.php */
/* Location: ./system/expressionengine/libraries/Pings.php */
