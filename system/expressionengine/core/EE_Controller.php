<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * ExpressionEngine Controller
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Controller extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->library('core');

		$this->core->bootstrap();
		$this->core->run_ee();
	}
}

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Control Panel Controller
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CP_Controller extends EE_Controller {

	function __construct()
	{
		parent::__construct();
		$this->core->run_cp();
	}
}


/* End of file  */
/* Location: system/expressionengine/libraries/core/EE_Controller.php */