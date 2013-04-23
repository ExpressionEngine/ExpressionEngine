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
class Content extends CP_Controller {

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}
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
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('content');

		$this->javascript->output(
			$this->javascript->slidedown("#adminTemplatesSubmenu")
		);
		
		$this->view->cp_page_title = lang('content');
		$this->view->controller = 'content';

		$this->cp->render('_shared/overview');
	}
	
	
}
// END CLASS

/* End of file content.php */
/* Location: ./system/expressionengine/controllers/cp/content.php */