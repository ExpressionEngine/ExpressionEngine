<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Js_tool_api {

	var $files		= array();

	var $yui		= 'build/yuicompressor.jar';
		
	var $js_path;
	var $ext_path;
	
	var $config_url;
	var $config_setting;
	var $compress_url;
	var $resync_url;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Js_tool_api($config = array())
	{
		$this->EE =& get_instance();

		$this->js_path	= APPPATH.'javascript/';
		$this->ext_path	= PATH_THIRD.'js_tool/external/';

		$this->config_setting	= ($this->EE->config->item('use_compressed_js') == 'n') ? 'n' : 'y';
		$this->config_url		= 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=js_tool'.AMP.'method=update_config';
		$this->compress_url		= 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=js_tool'.AMP.'method=compress';
		$this->resync_url		= 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=js_tool'.AMP.'method=resync';

		foreach($config as $key => $val) {
			$this->$key = $val;
		}
				
		// Required by all output functions
		$this->EE->load->library('javascript');
		$this->EE->load->library('table');
		$this->EE->load->helper('form');
		
		$this->_prep_view();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get a list of certain files
	 *
	 * @access	private
	 * @param	string	type
	 * @return	array	files
	 */
	function _prep_view()
	{
		$this->EE->load->vars(array(
			'config_url'		=> $this->config_url,
			'config_setting'	=> $this->config_setting,
			'compress_url'		=> $this->compress_url,
			'resync_url'		=> $this->resync_url
		));
		
		// @todo move to javascript file
		$this->EE->javascript->output("
			// Hide those annoying things
			$('.compression_checkbox, #update_config').hide();
		
			// Should be done with classes, really
			var selected_color = '#ccffcc',
				table_rows = $('table tr:has(.compression_checkbox)');

			function select_row() {
				$(this).find('td').css({'background': selected_color});
				$(this).find('.compression_checkbox').attr('checked', true);
			}
			function deselect_row() {
				$(this).find('td').css({'background': ''});
				$(this).find('.compression_checkbox').attr('checked', false);
			}

			table_rows.css('cursor', 'pointer');
			table_rows.toggle(select_row, deselect_row);

			$('#select_options').change(function(evt) {
				var select, trigger = '';

				deselect_row.apply($('.compression_checkbox').parents('tr'));

				id = $(this).val();

				select = $.map(id.split(''), function(key) { return '.compression_'+key; });
				select = $(select.join(','));
				select_row.apply(select.parents('tr'));

				return false;
			}).trigger('change');
		");
		
		$this->EE->javascript->click('input[name=use_compressed]', "
			var new_val = $(this).val();		
			var url = $(this).parents('form').attr('action')+'&use_compressed='+new_val;

			$.get(url, function(res) {
				$.ee_notice(res);
			});
			return true;
		");
	}

	// --------------------------------------------------------------------
	
	/**
	 * Get a list of certain files
	 *
	 * @access	public
	 * @param	string	type
	 * @return	array	files
	 */
	function get_files($which = 'all')
	{
		$this->_create_file_array();
		
		if ($which == 'all')
		{
			return array_merge(
				$this->files['error'],
				$this->files['new'],
				$this->files['resync'],
				$this->files['modified'],
				$this->files['compressed']
			);
		}
		
		if (is_string($which) && isset($this->files[$which]))
		{
			return $this->files[$which];
		}
		
		if (is_array($which))
		{
			$keys = array_keys($this->files);
			
			$which = array_intersect($which, $keys);
			$files = array();
			
			foreach($which as $key)
			{
				$files = array_merge($files, $this->files[$key]);
			}
			return $files;
		}

		return array();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get a file's checksum
	 *
	 * @access	public
	 * @param	string	filepath
	 * @return	string	checksum
	 */
	function get_checksum($path = '')
	{
		static $checksums;
		
		if (isset($checksums[$path]))
		{
			return $checksums[$path];
		}
		
		if (file_exists(APPPATH.'javascript/src/'.$path))
		{
			$checksums[$path] = md5(file_get_contents(APPPATH.'javascript/src/'.$path));
		}
		else
		{
			return '';
		}

		return $checksums[$path];
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Update File Serving Config
	 *
	 * @access	public
	 * @param	string	new config value (y / n)
	 * @return	string
	 */
	function update_config($new_val = 'n')
	{
		$this->EE->config->_update_config(array('use_compressed_js' => $new_val));
		
		if ($new_val == 'y')
		{
			return $this->EE->lang->line('serving_compressed');
		}
		return $this->EE->lang->line('serving_source');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Creates an array of all files
	 *
	 * @access	private
	 * @return	string
	 */
	function _create_file_array()
	{
		static $created;
		
		if ($created === TRUE)
		{
			return;
		}
		
		$this->EE->load->model('js_tool_model');
		
		// Get file information
		
		$db_files			= $this->EE->js_tool_model->get_checksums();
		$src_files			= $this->_read_dir('src');
		$compressed_files	= $this->_read_dir('compressed');

		// Extract paths for comparison

		$db_k			= array_keys($db_files);
		$src_k			= array_keys($src_files);
		$compressed_k	= array_keys($compressed_files);
		
		unset($src_files, $compressed_files);

		// Process them
		
		$error	= array_diff($compressed_k, $src_k);
		$resync	= array_diff($compressed_k, $error, $db_k);

		$new	= array_diff($src_k, $compressed_k);
		
		$modified	= array();
		$compressed	= array();
		
		foreach(array_diff($db_k, $resync, $new, $error) as $key)
		{
			$file = $db_files[$key];
			$checksum = $this->get_checksum($key);
			
			if ($checksum != $file->checksum)
			{
				$modified[] = $key;
			}
			else
			{
				$compressed[] = $key;
			}
		}
		
		// Garbage Collect
				
		$this->EE->js_tool_model->remove_checksums($new);
		unset($db_k, $src_k, $compressed_k);		

		// Merge into one big array

		$status_map = array(
			'error'			=> '?',
			'resync'		=> 'R',
			'new'			=> 'N',
			'modified'		=> 'M',
			'compressed'	=> 'C'
		);

		$files = compact(array_keys($status_map));

		unset($error, $resync, $new, $modified, $compressed);
		
		// Set view variables
		
		foreach($files as $key => $files)
		{
			foreach($files as $path)
			{
				$id			= $this->_convert_url_path($path);
				$filename	= $this->_get_file_name($path);
				$status		= $status_map[$key];

				$this->files[$key][$id] = array(
						'id'		=> $id,
						'filename'	=> $filename,
						'filepath'	=> $path,
						'status'	=> $status,
						'checksum'	=> ''
				);

				if ($key == 'compressed')
				{
					$this->files[$key][$id]['checksum'] = $db_files[$path]->checksum;
				}
			}
		}

		foreach(array_keys($status_map) as $key)
		{
			if ( ! isset($this->files[$key]) OR ! is_array($this->files[$key]))
			{
				$this->files[$key] = array();
			}
		}

		$created = TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get file list
	 *
	 * @access	private
	 * @return	void
	 */
	function _read_dir($dir = '')
	{
		$path = ($dir != '') ? APPPATH.'javascript/'.$dir.'/' : APPPATH.'javascript/';
		
		$this->EE->load->helper('directory');
		$map = directory_map($path);

		$map = $this->_flatten_file_map($map);
		
		return (is_array($map)) ? $map : array();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Flatten nested file structure
	 *
	 * @access	private
	 * @param	array	source
	 * @return	array
	 */
	function _flatten_file_map($map = array(), $reset = TRUE, $path = '')
	{
		static $files;
		
		if ($reset)
		{
			$files = array();
		}
		
		foreach (array_keys($map) as $key => $val)
		{
			if (is_numeric($val))
			{
				if (substr($map[$val], -3) == '.js' OR substr($map[$val], -4) == '.css')
				{
					$files[trim($path.'/'.$map[$val], '/')] = $path.'/'.$map[$val];
				}
			}
			else
			{
				$q =& $map[$val];
				$this->_flatten_file_map($q, FALSE, $path.'/'.$val);
			}
		}
		
		return $files;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get filename from path
	 *
	 * @access	private
	 * @param	string	filepath
	 * @return	string	filename
	 */
	function _get_file_name($file = '', $path = FALSE)
	{
		$file = explode('/', $file);
		
		if ($path == TRUE)
		{
			array_pop($file);
			return implode('/', $file);
		}
		
		return end($file);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Create / Decode CI safe url value
	 *
	 * @access	private
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function _convert_url_path($path, $from_qs = FALSE)
	{
		if ( ! $from_qs)
		{
			$path = base64_encode($path);
			return strtr($path, '+/=', '-_~');
		}
		
		$path = strtr($path, '-_~', '+/=');
		return base64_decode($path);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Compress
	 *
	 * Runs the YUI compressor on the file
	 *
	 * @access	private
	 * @return	void
	 */
	function _yui_compress($file)
	{
		$infile = $this->js_path.'src/'.$file;
		$outfile = $this->js_path.'compressed/'.$file;
		$toolpath = $this->ext_path.$this->yui;
		
		shell_exec("java -jar $toolpath $infile -o $outfile");
	}

	// --------------------------------------------------------------------
	
}

/* End of file js_tool_api.php */
/* Location: ./system/expressionengine/third_party/modules/js_tool/js_tool_api.php */