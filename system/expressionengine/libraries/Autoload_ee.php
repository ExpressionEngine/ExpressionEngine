<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Autoload EE Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Autoload_ee {

	/**
	 * Constructor
	 *
	 */	
	function EE_Autoload_ee()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		// we do this here instead of in the Core lib constructor so code
		// that is subsequently executed has access to the Core class object
		// but only for CP requests.  For PHP 4, this *has* to be done from
		// the front end EE controller or the super object will run afowl		
		if (REQ == 'CP')
		{
			$this->EE->core->_initialize_core();	
		}
	}

	// --------------------------------------------------------------------
}

/* End of file Autoload_ee.php */
/* Location: ./system/expressionengine/libraries/Autoload_ee.php */