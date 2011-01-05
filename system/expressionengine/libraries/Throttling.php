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
 * ExpressionEngine Core Throttling Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Throttling {

	var $throttling_enabled = FALSE;
	var $max_page_loads = 10;
	var $time_interval	= 5;
	var $lockout_time	= 30;
	var $current_data	= FALSE;

	function __construct()
	{
		$this->EE =& get_instance();
	}

	/** ----------------------------------------------
	/**  Runs the throttling funcitons
	/** ----------------------------------------------*/

	function run()
	{
		if ($this->EE->config->item('enable_throttling') != 'y')
		{
			return;
		}
		
		if ( ! is_numeric($this->EE->config->item('max_page_loads')))
		{
			return;
		}
		
		$this->max_page_loads = $this->EE->config->item('max_page_loads');

		if (is_numeric($this->EE->config->item('time_interval')))
		{
			$this->time_interval = $this->EE->config->item('time_interval');
		}

		if (is_numeric($this->EE->config->item('lockout_time')))
		{
			$this->lockout_time = $this->EE->config->item('lockout_time');
		}
	
		$this->throttle_ip_check();
		$this->throttle_check();
		$this->throttle_update();
	}
	
	/** ----------------------------------------------
	/**  Is there a valid IP for this user?
	/** ----------------------------------------------*/
 
 	function throttle_ip_check()
 	{
		if ($this->EE->config->item('banish_masked_ips') == 'y' AND $this->EE->input->ip_address() == '0.0.0.0' OR $this->EE->input->ip_address() == '')
		{
			$this->banish();
		}
  	}

	/** ----------------------------------------------
	/**  Throttle Check
	/** ----------------------------------------------*/
		
	function throttle_check()
	{
		$expire = time() - $this->time_interval;
		
		$query = $this->EE->db->query("SELECT hits, locked_out, last_activity FROM exp_throttle WHERE ip_address= '".$this->EE->db->escape_str($this->EE->input->ip_address())."'");

		if ($query->num_rows() == 0) $this->current_data = array();
  
  		if ($query->num_rows() == 1)
  		{
  			$this->current_data = $query->row_array();

			$lockout = time() - $this->lockout_time;
	
			if ($query->row('locked_out')  == 'y' AND $query->row('last_activity')  > $lockout)
			{
				$this->banish();
				exit;
			}

  			if ($query->row('last_activity')  > $expire)
  			{
  				if ($query->row('hits') >= $this->max_page_loads)
  				{
  					// Lock them out and banish them...
					$this->EE->db->query("UPDATE exp_throttle SET locked_out = 'y', last_activity = '".time()."' WHERE ip_address= '".$this->EE->db->escape_str($this->EE->input->ip_address())."'");
					$this->banish();
					exit;
  				}
  			}
  		}
	}

  	
	/** ----------------------------------------------
	/**  Throttle Update
	/** ----------------------------------------------*/
	function throttle_update()
	{		
		if ($this->current_data === FALSE)
		{
			$query = $this->EE->db->query("SELECT hits, last_activity FROM exp_throttle WHERE ip_address= '".$this->EE->db->escape_str($this->EE->input->ip_address())."'");
			$this->current_data = ($query->num_rows() == 1) ? $query->row_array() : array();
		}
		
		if (count($this->current_data) > 0)
		{
			$expire = time() - $this->time_interval;
			
			if ($this->current_data['last_activity'] > $expire) 
			{
				$hits = $this->current_data['hits'] + 1;
			}
			else
			{
				$hits = 1;
			}
							
			$this->EE->db->query("UPDATE exp_throttle SET hits = '{$hits}', last_activity = '".time()."', locked_out = 'n' WHERE ip_address= '".$this->EE->db->escape_str($this->EE->input->ip_address())."'");
		}
		else
		{
			$this->EE->db->query("INSERT INTO exp_throttle (ip_address, last_activity, hits) VALUES ('".$this->EE->db->escape_str($this->EE->input->ip_address())."', '".time()."', '1')");
		}
	}


	/** ----------------------------------------------
	/**  Banish User
	/** ----------------------------------------------*/
		
	function banish()
	{
		$type = (($this->EE->config->item('banishment_type') == 'redirect' AND $this->EE->config->item('banishment_url') == '')  OR ($this->EE->config->item('banishment_type') == 'message' AND $this->EE->config->item('banishment_message') == '')) ?  '404' : $this->EE->config->item('banishment_type');
		
		switch ($type)
		{
			case 'redirect' :	$loc = (strncasecmp($this->EE->config->item('banishment_url'), 'http://', 7) != 0) ? 'http://'.$this->EE->config->item('banishment_url') : $this->EE->config->item('banishment_url');
								header("location:$loc");
				break;
			case 'message'	:	echo $this->EE->config->item('banishment_message');
				break;
			default			:	header("Status: 404 Not Found"); echo "Status: 404 Not Found";
				break;
		}
		
		exit;	
	}

	
	
}
// END CLASS

/* End of file Throttling.php */
/* Location: ./system/expressionengine/libraries/Throttling.php */