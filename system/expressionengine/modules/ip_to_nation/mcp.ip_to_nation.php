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
 * ExpressionEngine IP to Nation Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Ip_to_nation_mcp {

	/**
	  * Constructor
	  */
	function Ip_to_nation_mcp()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		$this->EE->load->helper('form');
	}

	// ------------------------------------------------------------------------

	/**
	  * Nation Home Page
	  */
	function index()
	{
		if ( ! include_once(APPPATH.'config/countries.php'))
		{
			show_error($this->EE->lang->line('countryfile_missing'));
		}
		
		if (isset($_POST['ip']))
		{
		    $ip_address = trim($_POST['ip']);
		}
				
		$vars['cp_page_title'] = $this->EE->lang->line('ip_to_nation_module_name');
		$vars['country'] = '';
		$vars['ip'] = (isset($ip_address) AND $this->EE->input->valid_ip($ip_address)) ? $ip_address : '';

		if ($vars['ip'] != '')
		{
			$this->EE->db->select('country');
			$this->EE->db->from('ip2nation');
			$this->EE->db->where("ip < INET_ATON('".trim($vars['ip'])."')", '', FALSE);
			$this->EE->db->order_by('ip', 'desc');
			$this->EE->db->limit(1, 0);
			$query = $this->EE->db->get();

			if ($query->num_rows() == 1)
			{
				if (@isset($countries[$query->row('country') ]))
				{
					$vars['country'] = $countries[$query->row('country')];
				}
			}
		}

		return $this->EE->load->view('index', $vars, TRUE);
	}

	// ------------------------------------------------------------------------

	/**
	  * Ban list table
	  */
	function banlist($updated = FALSE)
	{
		if ( ! include(APPPATH.'config/countries.php'))
		{
			show_error($this->EE->lang->line('countryfile_missing'));
		}

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=ip_to_nation', $this->EE->lang->line('ip_to_nation_module_name'));

		$vars['cp_page_title'] = $this->EE->lang->line('banlist');

		$query = $this->EE->db->get('ip2nation_countries');
		$status = array();

		foreach ($query->result() as $row)
		{
			$status[$row->code] = $row->banned;
		}

		$vars['countries'] = array();

		foreach ($countries as $key => $val)
		{
			$vars['countries'][$key]['code'] = $key;
			$vars['countries'][$key]['name'] = $val;

			if ($key == 'gb')
			{
				$vars['countries'][$key]['status'] = ($status['uk'] == 'y') ? TRUE : FALSE;				
			}
			else
			{
				$vars['countries'][$key]['status'] = ($status[$key] == 'y') ? TRUE : FALSE;				
			}
		}

		return $this->EE->load->view('banlist', $vars, TRUE);
	}

	// ------------------------------------------------------------------------

	/**
	  * Update Ban List
	  */
	function update()
	{
		if ( ! include(APPPATH.'config/countries.php'))
		{
			show_error($this->EE->lang->line('countryfile_missing'));
		}

		// Set all countries to unbanned
		$this->EE->db->update('ip2nation_countries', array('banned'=>'n'));

		// Unset everything that isn't an explictly banned country
		foreach ($_POST as $key => $val)
		{
			if ($key == 'gb')
			{
				$_POST['uk'] = $val;
				unset($_POST['gb']);
			}

			if ( ! isset($countries[$key]) AND $val != 'y')
			{
				unset($_POST[$key]);
			}
		}

		// Everything left was banned
		if (count($_POST) > 0)
		{
			$this->EE->db->where_in('CODE', array_keys($_POST));
			$this->EE->db->update('ip2nation_countries', array('banned'=>'y'));
		}

		// Countries array will be reset in banlist(), so wipe it here
		unset($countries);
		
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('banlist_updated'));
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=ip_to_nation'.AMP.'method=banlist');
	}


}
// END CLASS

/* End of file mcp.ip_to_nation.php */
/* Location: ./system/expressionengine/modules/ip_to_nation/mcp.ip_to_nation.php */