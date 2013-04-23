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
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Tools extends CP_Controller {

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		parent::__construct();
		
		if ( ! $this->cp->allowed_group('can_access_tools'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('tools');
	}
	
	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */	
	function index()
	{
		$this->view->cp_page_title = lang('tools');
		$this->view->controller = 'tools';

		$this->cp->render('_shared/overview');
	}
	
}

/* End of file tools.php */
/* Location: ./system/expressionengine/controllers/cp/tools.php */