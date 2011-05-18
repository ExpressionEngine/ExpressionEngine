<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Action Handler Class
 *
 * Actions are events that require processing. Normally when you use ExpressionEngine, 
 * either a web page (template), or the control panel is displayed. There are times, 
 * however, when we need to process user-submitted data. Examples of these include:
 * 
 * - Logging in
 * - Logging out
 * - New member registration
 *	etc...
 * 
 * In these examples, information submitted from a user needs to be received and processed.  Since
 * ExpressionEngine uses only one execution file (index.php) we need a way to know that an
 * action is being requested.
 * 
 * The way actions work is this: 
 * 
 * Anytime a GET or POST request contains the ACT variable, ExpressionEngine will run the Actions class and 
 * process the requested action.
 * 
 * Note: The database contains a table called "exp_actions".  This table contains a list
 * of every available action (and the associated class and method).  When an action is requested,
 * the database is queried to get the information needed to process the action.
 * 
 * When a new module is installed, ExpressionEngine will update the action table.  When a module
 * is de-installed, the actions are deleted. 
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Actions {

	/**
	 * Constructor
	 *
	 * Loads the class and calls the method associated with 
	 * a particular action request
	 *
	 */	
		
	function __construct($can_view_system = FALSE)
	{  		
		// Set the EE super object to a local variable
		$EE =& get_instance();
  
		// Define special actions
		// These are actions that are triggered manually
		// rather than doing a lookup in the actions table.
		$specials = array(
							'jquery'			=> array('Jquery', 'output_javascript'),
							'comment_editor'	=> array('Comment', 'comment_editor'),
							'saef'				=> array('Channel', 'saef_filebrowser')
						 );
		
		// Make sure the ACT variable is set		
		if ( ! $action_id = $EE->input->get_post('ACT'))
		{
			return FALSE;
		}

		// Fetch the class and method name (checks to make sure module is installed too)
		// If the ID is numeric we need to do an SQL lookup
		if (is_numeric($action_id))
		{
			$EE->db->select('class, method');
			$EE->db->where('action_id', $action_id);
			$query = $EE->db->get('actions');			

			if ($query->num_rows() == 0)
			{
				if ($EE->config->item('debug') >= 1)
				{
					$EE->output->fatal_error($EE->lang->line('invalid_action'));
				}
				else
				{
					return FALSE;					
				}
			}
			
			$class  = ucfirst($query->row('class'));
			$method = strtolower($query->row('method'));
		}
		else
		{
			// If the ID is not numeric we'll invoke the class/method manually	
			if ( ! isset($specials[$action_id]))
			{
				return FALSE;
			}
		
			$class  = $specials[$action_id]['0'];
			$method = $specials[$action_id]['1'];

			// Double check that the module is actually installed			
			$EE->db->select('module_version');
			$EE->db->where('module_name', ucfirst($class));
			$query = $EE->db->get('modules');

			if ($query->num_rows() == 0)
			{
				if ($EE->config->item('debug') >= 1)
				{
					$EE->output->fatal_error($EE->lang->line('invalid_action'));
				}
				else
				{
					return FALSE;					
				}
			}
		}

		// What type of module is being requested?
		if (substr($class, -4) == '_mcp')
		{
			$type = 'mcp'; 
			
			$base_class = strtolower(substr($class, 0, -4));
		}
		else
		{
			if ($can_view_system === FALSE)
			{
				$EE->output->system_off_msg();
				exit;
			}

			$type = 'mod';
		
			$base_class = strtolower($class);
		}
		
		// Assign the path
		$package_path = PATH_MOD.$base_class.'/';
		
		// Third parties have a different package and view path
		if ( ! in_array($base_class, $EE->core->native_modules))
		{
			$package_path = PATH_THIRD.$base_class.'/';
		}
				
		$EE->load->add_package_path($package_path, FALSE);
		
		$path = $package_path.$type.'.'.$base_class.'.php';

		// Does the path exist?		
		if ( ! file_exists($path))
		{
			if ($EE->config->item('debug') >= 1)
			{						
				$EE->output->fatal_error($EE->lang->line('invalid_action'));
			}
			else
			{
				return FALSE;				
			}
		}
		
		// Require the class file
		if ( ! class_exists($class))
		{
			require $path;
		}
		
		// Instantiate the class/method		
		$ACT = new $class(0);
		
		if ($method != '')
		{
			if ( ! method_exists($ACT, $method))
			{
				if ($EE->config->item('debug') >= 1)
				{						
					$EE->output->fatal_error($EE->lang->line('invalid_action'));
				}
				else
				{
					return FALSE;
				}
			}
		
			$ACT->$method();
		}
		
		// remove the temporarily added path for local libraries, models, etc.
		$EE->load->remove_package_path($package_path);
	}

}
// END CLASS

/* End of file Actions.php */
/* Location: ./system/expressionengine/libraries/Actions.php */