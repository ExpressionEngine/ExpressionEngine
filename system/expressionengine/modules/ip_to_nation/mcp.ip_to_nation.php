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
		
		$this->base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=ip_to_nation';

			if ($this->EE->cp->allowed_group('can_moderate_comments') &&  $this->EE->cp->allowed_group('can_edit_all_comments') && $this->EE->cp->allowed_group('can_delete_all_comments'))
			{
				$this->EE->cp->set_right_nav(array(
									'update_ips'				=> $this->base_url.AMP.'method=import_form'
									));	
			}		
		
		
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
			// Don't show countries for which we lack IP information
			if (isset($status[$key]))
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
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=ip_to_nation'.AMP.'method=index');
	}
	
	// ------------------------------------------------------------------------

	/**
	  * Import Form
	  */
	function import_form()
	{
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=ip_to_nation', $this->EE->lang->line('ip_to_nation_module_name'));

		$vars['cp_page_title'] = $this->EE->lang->line('update_ips');
		
		$this->EE->load->library('form_validation');
					
		$this->EE->form_validation->set_rules('ip2nation_file',	'File',	'required|callback__file_exists');
		$this->EE->form_validation->set_error_delimiters('<p class="notice">', '</p>');
		
		$vars['update_info'] = str_replace('%d', $this->EE->cp->masked_url('http://www.ip2nation.com/'), $this->EE->lang->line('update_info'));
		
		$last_update =	$this->EE->config->item('ip2nation_db_date');
		$vars['last_update'] = ($last_update && $last_update != '') ? $this->EE->localize->set_human_time($last_update) : FALSE;

		if ($this->EE->form_validation->run() === FALSE)
		{
			return $this->EE->load->view('import', $vars, TRUE);
		}
		
		$result = $this->_convert_file($this->EE->input->post('ip2nation_file'));
		
		if ( ! $result)
		{
			$message = $this->EE->lang->line('ip_db_failed');

			$this->EE->session->set_flashdata('message_failure', $message);			
		}
		else
		{
			$message = $this->EE->lang->line('ip_db_updated');

			$this->EE->session->set_flashdata('message_success', $message);
		}
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.
				'M=show_module_cp'.AMP.'module=ip_to_nation'.AMP.'method=index');		
	}
	
	// ------------------------------------------------------------------------

	/**
	  * Convert SQL File
	  */
	function _convert_file($ip_file)
	{

		//  Read data file into an array
		$master_array = $this->_datafile_to_array($ip_file);
		
		if ( ! $master_array)
		{
			return FALSE;
		}
		
		// Fetch banned nations
		$query = $this->EE->db->get_where('ip2nation_countries', array('banned'=>'y'));

		// Truncate tables
		$this->EE->db->truncate('ip2nation_countries');
		$this->EE->db->truncate('ip2nation');

		// Re-insert the massive number of records
		
		$this->EE->db->insert_batch('ip2nation', $master_array['ips']);

		$this->EE->db->insert_batch('ip2nation_countries', $master_array['cc']);		

		// update banned nations
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->EE->db->query($this->EE->db->update_string('exp_ip2nation_countries', array('banned' => 'y'), array('code' => $row['code'])));
			}
		}
		
		$this->EE->config->_update_config(array('ip2nation_db_date' => $this->EE->localize->now));
		
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Datafile to Array
	 *
	 * Read ip2nation sql file into an array
	 * 
	 * @access	private
	 * @return	array
	 */	
	function _datafile_to_array($file)
	{
		$contents = file($file);

		//  Parse file into array
		foreach ($contents as $line)
		{
			if (strncmp($line, 'INSERT INTO ip2nation (', 23) == 0)
			{
				//$line = str_replace('INSERT INTO ip2nation (ip, country)', '', $line);
				//$data = str_replace(array('"', "'", ' '), '', substr(trim($line), 43, -2));
				
				if ( ! preg_match_all("/\((.+?)\)/", $line, $matches))
				{
					return FALSE;
				}

				$security_check = explode(',', $matches[1][1]);
				
				if (count($security_check) > 2)
				{
					return FALSE;
				}
				
				if ( ! ctype_digit($security_check[0]))
				{
					return FALSE;
				}

				$country = trim($security_check[1], "' ");
				
				if (strlen($country) != 2)
				{
					return FALSE;
				}								
				
				$master_array['ips'][] = array('ip' => $security_check[0], 'country' => $country);
				$master_array['cc'][$country] = array('code' => $country);
			}
			elseif (strncmp($line, 'INSERT INTO ip2nationCountries', 30) == 0)
			{
				break;
			}
		}
		
		return $master_array;
	}

	// --------------------------------------------------------------------

	/**
	 * File exists
	 *
	 * Validation callback that checks if a file exits
	 *
	 * @access	private
	 * @param	string
	 * @return	boolean
	 */
	function _file_exists($file)
	{
		if ( ! file_exists($file))
		{
			$this->form_validation->set_message('_file_exists', $this->lang->line('invalid_path').$file);
			return FALSE;
		}
		
		return TRUE;
	}




}
// END CLASS

/* End of file mcp.ip_to_nation.php */
/* Location: ./system/expressionengine/modules/ip_to_nation/mcp.ip_to_nation.php */