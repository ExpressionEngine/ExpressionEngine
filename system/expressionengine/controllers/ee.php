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
 * This class doesn't do much work.  Most of the heavy lifting is done via
 * libraries/Core.php, which runs automatically behind the scenes.  
 */
class EE extends EE_Controller {

	/**
	 * Index
	 */
	function index()
	{
		// If the REQ constant isn't defined it means the EE has not
		// been initialized.  This can happen if the config/autoload.php
		// file is not set-up to automatically run the libraries/Core.php class.
		// We'll set REQ to FALSE so that it shows an error message below
		if ( ! defined('REQ'))
		{
			define('REQ', FALSE);
		}
		
		$can_view_system =  FALSE;
		
		if ($this->config->item('is_system_on') == 'y' && 
			($this->config->item('multiple_sites_enabled') != 'y' OR $this->config->item('is_site_on') == 'y'))
		{
			if ($this->session->userdata('can_view_online_system') == 'y')
			{
				$can_view_system =  TRUE;
			}			
		}
		else
		{
			if ($this->session->userdata('can_view_offline_system') == 'y')
			{
				$can_view_system =  TRUE;
			}
		}	
		
		$can_view_system = ($this->session->userdata('group_id') == 1) ? TRUE : $can_view_system;

		if (REQ != 'ACTION' && $can_view_system != TRUE)
		{
			$this->output->system_off_msg();
			exit;		
		}

		if (REQ == 'ACTION')
		{
			$this->core->generate_action($can_view_system);
		}
		elseif (REQ == 'PAGE')
		{
			$this->core->generate_page();
		}
		else
		{
			show_error('Unable to initialize ExpressionEngine.  The EE core does not appear to be defined in your autoload file.  For more information please contact technical support.');
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Add the template debugger to the output if required and then
	 * run the garbage collection routine.
	 *
	 * @access	private
	 * @return	void
	 */
	function _output($output)
	{
		// If 'debug' is turned off, we will remove any variables that didn't get parsed due to syntax errors.
		// this needs to happen here as the CI output library does the elapsed_time and memory_usage replacements.

		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- remove_unparsed_vars => Whether or not to remove unparsed EE variables
		/*  This is most helpful if you wish for debug to be set to 0, as EE will not
		/*  strip out javascript.
		/* -------------------------------------------*/		
		$remove_vars = ($this->config->item('remove_unparsed_vars') == 'y') ? TRUE : FALSE;
		$this->output->remove_unparsed_variables($remove_vars);

		if ($this->config->item('debug') == 0 && 
			$this->output->remove_unparsed_variables === TRUE)
		{
			$output = preg_replace("/".LD."[^;\n]+?".RD."/", '', $output);
		}
		
		// Add the template debugger to the output
		
		if (isset($this->TMPL) && 
			is_object($this->TMPL) && 
			isset($this->TMPL->debugging) && 
			$this->TMPL->debugging === TRUE && 
			$this->TMPL->template_type != 'js')
		{
			if ($this->session->userdata['group_id'] == 1)
			{		
				$output .= '<div style="color: #333; background-color: #ededed; margin:10px; padding-bottom:10px;" id="expressionengine_template_debug">';
				$output .= "<div style=\"text-align: left; font-family: Sans-serif; font-size: 11px; margin: 12px; padding: 6px\"><hr size='1'><b>TEMPLATE DEBUGGING</b><hr size='1'></div>";
				
				foreach ($this->TMPL->log as $val)
				{
					$val = str_replace(array("\t", '&amp;nbsp;'), array(' ', '&nbsp;'), htmlentities($val, ENT_QUOTES));
					
					$x = explode(':', $val, 2);
					
					if (count($x) > 1)
					{
						$val = '<strong>'.$x['0'].':</strong>'.$x['1'];
					}
					else
					{
						$val = '<strong>'.$val.'</strong>';
					}
									
					$output .= "<div style=\"text-align: left; font-family: Sans-serif; font-size: 11px; margin: 12px 12px 6px 22px;\">".$val."</div>";
				}
				
				if (function_exists('memory_get_usage') AND ($usage = memory_get_usage()) != '')
				{
					$output .= "<div style='text-align: left; font-family: Sans-serif; font-size: 11px; margin: 12px 12px 6px 22px;'><strong>Memory Usage: ".number_format($usage)." bytes</strong></div>";
				}
								
				$output .= '</div>';
			}
		}
				
		echo $output;
		
		// Garbage Collection
		if (REQ == 'PAGE')
		{
			if ($this->config->item('log_referrers') == 'y')
			{
				$this->load->library('referrer');
				$this->referrer->log_referrer();
			}
	
			$this->core->_garbage_collection();
		}
	}	
}

/* End of file ee.php */
/* Location: ./system/expressionengine/controllers/ee.php */