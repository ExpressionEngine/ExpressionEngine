<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Js_tool_mcp {

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Js_tool_mcp()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		$this->EE->load->library('js_tool_api');
	}

	// --------------------------------------------------------------------

	/**
	 * Main Page
	 *
	 * @access	public
	 */
	function index()
	{
		// Testing 3rd party config files...
		$this->EE->config->load('config');
		$vars['cp_page_title'] = $this->EE->config->item('js_tool_title');

		$this->EE->load->library('javascript');

		$this->EE->jquery->plugin(BASE.AMP.'C=javascript'.AMP.'M=load'.AMP.'plugin=tablesorter', TRUE);
		
		$this->EE->jquery->tablesorter('.mainTable', '{
			headers: {2: {sorter: false}},
			widgets: ["zebra"]
		}');
		
		$this->EE->javascript->compile();

		$vars['js_tool_files'] = $this->EE->js_tool_api->get_files();
		
		return $this->EE->load->view('index', $vars, TRUE);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Compress File(s)
	 *
	 * @access	public
	 */
	function compress()
	{
		$this->EE->load->model('js_tool_model');
		
		$files = $this->EE->input->post('compress');

		foreach($files as $file)
		{
			$file = $this->EE->js_tool_api->_convert_url_path($file, TRUE);
			$file = trim($file, '/');
			
			$path = $this->EE->js_tool_api->_get_file_name($file, TRUE);
			$path = $this->EE->js_tool_api->js_path.'compressed/'.$path;

			// @todo Create folder if it doesn't exist (do it in java instead?)

			$this->EE->js_tool_api->_yui_compress($file);
			$checksum = $this->EE->js_tool_api->get_checksum($file);

			$this->EE->js_tool_model->store_checksum($file, $checksum);
		}

		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=js_tool');
	}
	
	// --------------------------------------------------------------------

	/**
	 * Resync Files
	 *
	 * @access	public
	 */
	function resync()
	{
		$this->EE->load->model('js_tool_model');
		
		$files = $this->EE->input->post('compress');
		
		if ( ! $files)
		{
			$files = $this->EE->js_tool_api->get_files('resync');
		}

		foreach($files as $file)
		{
			if (is_array($file))
			{
				// From DB
				$file = $file['filepath'];
			}
			else
			{
				// From POST
				$file = $this->EE->js_tool_api->_convert_url_path($file, TRUE);
			}

			$checksum = $this->EE->js_tool_api->get_checksum($file);
			$this->EE->js_tool_model->store_checksum($file, $checksum);
		}

		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=js_tool');
	}
	
	// --------------------------------------------------------------------

	/**
	 * Update Configuration
	 *
	 * @access	public
	 */
	function update_config()
	{
		if ($new_val = $this->EE->input->get('use_compressed'))
		{
			$msg = $this->EE->js_tool_api->update_config($new_val);
			exit($msg);
		}
		
		$new_val = $this->EE->input->get_post('use_compressed');
		$msg = $this->EE->js_tool_api->update_config($new_val);
		
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=js_tool');
	}


}
// END CLASS

/* End of file mcp.js_tool.php */
/* Location: ./system/expressionengine/third_party/modules/js_tool/mcp.js_tool.php */