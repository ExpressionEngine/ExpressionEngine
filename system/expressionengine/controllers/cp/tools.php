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
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Tools extends Controller {


	function Tools()
	{
		// Call the Controller constructor.  
		// Without this, the world as we know it will end!
		parent::Controller();

		// Does the "core" class exist?  Normally it's initialized
		// automatically via the autoload.php file.  If it doesn't
		// exist it means there's a problem.
		if ( ! isset($this->core) OR ! is_object($this->core))
		{
			show_error('The ExpressionEngine Core was not initialized.  Please make sure your autoloader is correctly set up.');
		}

		if ( ! $this->cp->allowed_group('can_access_tools'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->lang->loadfile('tools');
	}
	
	// --------------------------------------------------------------------

	/**
	 * Index function
	 * 
	 * Every controller must have an index function, which gets called
	 * automatically by CodeIgniter when the URI does not contain a call to
	 * a specific method call
	 *
	 * @access	public
	 * @return	mixed
	 */	
	function index()
	{
		if ( ! $this->cp->allowed_group('can_access_tools'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->cp->set_variable('cp_page_title', $this->lang->line('tools'));

		$this->javascript->compile();

		$this->load->vars(array('controller'=>'tools'));

		$this->load->view('_shared/overview');
	}
	
}

/* End of file tools.php */
/* Location: ./system/expressionengine/controllers/cp/tools.php */