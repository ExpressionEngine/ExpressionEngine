<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------------

/**
 * ExpressionEngine IP to Nation Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://expressionengine.com
 */
class Ip_to_nation_mcp {

	/**
	  * Constructor
	  */
	function __construct()
	{
		$this->load->helper('array');
		$this->load->model('ip_to_nation_model', 'ip_data');
		
		$this->base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=ip_to_nation';

		if ($this->cp->allowed_group('can_moderate_comments', 'can_edit_all_comments', 'can_delete_all_comments'))
		{
			$this->cp->set_right_nav(array(
				'update_ips' => $this->base_url.AMP.'method=import_form'
			));	
		}
	}

	// ----------------------------------------------------------------------

	/**
	  * Nation Home Page
	  */
	function index()
	{
		$countries = $this->_country_names();

		$ip = '';
		$country = '';

		if (isset($_POST['ip']))
		{
			$ip_address = trim($_POST['ip']);

			if ($this->input->valid_ip($ip_address))
		    {
		    	$ip = $ip_address;
   				$c_code = $this->ip_data->find($ip);
   				$country = element($c_code, $countries, '');
		    }
		}

		$data = compact('ip', 'country');
		$this->view->cp_page_title = lang('ip_to_nation_module_name');
		return $this->load->view('index', $data, TRUE);
	}

	// ----------------------------------------------------------------------

	/**
	  * Ban list table
	  */
	function banlist()
	{
		$countries = $this->_country_names();

		$query = $this->db->get('ip2nation_countries')->result();
		$status = array();

		foreach ($query as $row)
		{
			$status[$row->code] = $row->banned;
		}

		$country_list = array();

		foreach ($countries as $key => $val)
		{
			// Don't show countries for which we lack IP information
			if (isset($status[$key]))
			{
				$country_list[$key] = array(
					'code' => $key,
					'name' => $val,
					'status' => ($status[$key] == 'y')
				);
			}
		}

		$this->cp->set_breadcrumb(
			$this->base_url,
			lang('ip_to_nation_module_name')
		);

		$data = compact('country_list');
		$this->view->cp_page_title = lang('banlist');
		return $this->load->view('banlist', $data, TRUE);
	}

	// ----------------------------------------------------------------------

	/**
	  * Update Ban List
	  */
	function update()
	{
		$countries = $this->_country_names();

		// remove unknowns and 'n's
		$ban = array_intersect_key($_POST, $countries);
		$ban = preg_grep('/y/', $ban);

		$this->ip_data->ban(array_keys($ban));

		$this->session->set_flashdata('message_success', lang('banlist_updated'));
		$this->functions->redirect($this->base_url.AMP.'method=index');
	}
	
	// ----------------------------------------------------------------------

	/**
	  * Import Form
	  */
	function import_form()
	{
		$this->cp->set_breadcrumb($this->base_url, lang('ip_to_nation_module_name'));

		$this->load->library('form_validation');
		$this->form_validation->set_rules('ip2nation_file',	'File',	'required|callback__file_exists');
		$this->form_validation->set_error_delimiters('<p class="notice">', '</p>');
		
		if ($this->form_validation->run() === FALSE)
		{
			$last_update = $this->config->item('ip2nation_db_date');

			$data = array(
				'update_info' => str_replace('%d', $this->cp->masked_url('http://www.maxmind.com/app/geolite'), lang('update_info')),
				'last_update' => ($last_update) ? $this->localize->set_human_time($last_update) : FALSE
			);

			$this->view->cp_page_title = lang('update_ips');
			return $this->load->view('import', $data, TRUE);
		}

		if ( ! $result)
		{
			$this->session->set_flashdata('message_failure', lang('ip_db_failed'));
		}
		else
		{
			$this->session->set_flashdata('message_success', lang('ip_db_updated'));
		}
		
		$this->functions->redirect($this->base_url.AMP.'method=index');
	}

	// ----------------------------------------------------------------------

	/**
	 * Grab the country name file
	 */
	function _country_names()
	{
		if ( ! include(APPPATH.'config/countries.php'))
		{
			show_error(lang('countryfile_missing'));
		}

		return $countries;
	}
	
	// ----------------------------------------------------------------------

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
		if (file_exists($file) && ! is_dir($file))
		{
			return TRUE;
		}
		
		$this->lang->loadfile('admin');
		$this->form_validation->set_message('_file_exists', lang('invalid_path').' '.$file);
		return FALSE;
	}

	// ----------------------------------------------------------------------

	/**
	 * Easier superobject access
	 */
	function __get($key)
	{
		return get_instance()->$key;
	}
}
// END CLASS

/* End of file mcp.ip_to_nation.php */
/* Location: ./system/expressionengine/modules/ip_to_nation/mcp.ip_to_nation.php */