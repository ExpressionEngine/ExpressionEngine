<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class Ip_to_nation_mcp {

	/**
	  * Constructor
	  */
	function __construct()
	{
		$this->load->helper('array');
		$this->load->model('ip_to_nation_data', 'ip_data');

		$this->base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=ip_to_nation';

		if ($this->cp->allowed_group('can_moderate_comments', 'can_edit_all_comments', 'can_delete_all_comments'))
		{
			$this->cp->set_right_nav(array(
				'update_ips' => $this->base_url.AMP.'method=update_data'
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
		$error = FALSE;

		if (isset($_POST['ip']))
		{
			$ip_address = trim($_POST['ip']);

			if ($this->input->valid_ip($ip_address))
		    {
		    	$ip = $ip_address;
   				$c_code = $this->ip_data->find($ip);
   				$country = element($c_code, $countries, '');

   				if ($c_code === FALSE)
   				{
   					$error = lang('ip_not_found');
   				}
		    }
		    else
		    {
		    	$error = lang('ip_not_valid');
		    }
		}

		$data = compact('ip', 'country', 'error');
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

		// ban them
		$this->ip_data->ban(array_keys($ban));

		$this->session->set_flashdata('message_success', lang('banlist_updated'));
		$this->functions->redirect($this->base_url.AMP.'method=index');
	}

	// ----------------------------------------------------------------------

	/**
	  * Update data
	  */
	function update_data()
	{
		$this->cp->set_breadcrumb($this->base_url, lang('ip_to_nation_module_name'));

		$last_update = $this->config->item('ip2nation_db_date');
		$cache_files = $this->_cache_files('csv');

		// clear out stale data before we start
		if ( ! empty($cache_files))
		{
			foreach ($cache_files as $file)
			{
				unlink($file);
			}
		}

		// check again, if we can't clear them, the user will
		// have to do something about it or we end up killing
		// their database in the next step.
		if (count($this->_cache_files('csv')))
		{
			$this->session->set_flashdata('message_failure', lang('cache_full'));
			$this->functions->redirect($this->base_url.AMP.'method=index');
		}

		// look for data files that they may have
		// uploaded manually
		$data_files = $this->_cache_files('zip,gz');

		$data = array(
			'update_data_provider' => str_replace(
				'%d',
				$this->cp->masked_url('http://www.maxmind.com/app/geolite'),
				lang('update_data_provider')
			),
			'last_update' => ($last_update) ? $this->localize->human_time($last_update) : FALSE
		);

		$this->cp->add_js_script('fp_module', 'ip_to_nation');

		$this->javascript->set_global(array(
			'ip2n' => array(
				'run_script' => 'update',
				'base_url' => str_replace(AMP, '&', $this->base_url),
				'steps' => array('download_data', 'extract_data', 'insert_data'),
				'lang' => array(
					'ip_db_updating' => lang('ip_db_updating'),
					'ip_db_failed' => lang('ip_db_failed')
				)
			)
		));

		$this->view->cp_page_title = lang('update_ips');
		return $this->load->view('import', $data, TRUE);
	}

	// ----------------------------------------------------------------------

	/**
	 * Download new data files
	 */
	function download_data()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error(lang('unauthorized_access'));
		}

		$cache_path = $this->_cache_path();
		$valid_response = TRUE;
		$out_files = array();

		// download
		$files = array(
			'http://geolite.maxmind.com/download/geoip/database/GeoIPCountryCSV.zip',
			'http://geolite.maxmind.com/download/geoip/database/GeoIPv6.csv.gz'
		);

		foreach ($files as $file)
		{
			$out_fh = fopen($cache_path.basename($file), "w");
			$out_files[] = $cache_path.basename($file);

			$timeout = 5;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $file);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_FILE, $out_fh);
			curl_exec($ch);
			$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($http_status != '200')
			{
				$valid_response = FALSE;
				$response_code[] = $http_status;
			}
		}

		if ( ! $valid_response)
		{
			// cleanup
			array_map('unlink', $out_files);

			$msg = (in_array('403', $response_code)) ? 'ip_db_connection_403' : 'ip_db_connection_error';

			$this->output->send_ajax_response(array(
				'error' => lang($msg)
				));
		}

		$this->output->send_ajax_response(array(
			'success' => lang('ip_db_downloaded')
		));
	}

	// ----------------------------------------------------------------------

	/**
	 * Extract all data files
	 */
	function extract_data()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error(lang('unauthorized_access'));
		}

		$cache_files = $this->_cache_files('zip, gz');

		foreach ($cache_files as $file)
		{
			$filename = basename($file);
			$ext = end(explode('.', $filename));

			$fn = '_extract_'.$ext;
			$this->$fn($filename);
		}

		$this->output->send_ajax_response(array(
			'success' => lang('ip_db_unpacked')
		));
	}

	// ----------------------------------------------------------------------

	function insert_data()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error(lang('unauthorized_access'));
		}

		$files = $this->_cache_files('csv');
		$this->ip_data->load($files);

		// cleanup
		array_map('unlink', $this->_cache_files('csv,gz,zip'));

		$this->config->_update_config(array('ip2nation_db_date' => $this->localize->now));

		$this->output->send_ajax_response(array(
			'success' => lang('ip_db_updated')
		));
	}

	// ----------------------------------------------------------------------

	function _cache_files($extensions)
	{
		$extensions = str_replace(' ', '', $extensions);
		$path = $this->_cache_path();
		$matches = array();

		// The GLOB_BRACE flag isn't available on some non-GNU systems
		foreach (explode(',', $extensions) as $ext)
		{
			if ($files = glob($path.'*.'.$ext))
			{
				$matches = array_merge($matches, $files);
			}
		}

		return $matches;
	}

	// ----------------------------------------------------------------------

	function _cache_path()
	{
		$cache_path = $this->config->item('cache_path');

		if (empty($cache_path))
		{
			$cache_path = APPPATH.'cache/';
		}

		$cache_path .= 'ip2nation/';

		if ( ! is_dir($cache_path))
		{
			mkdir($cache_path, DIR_WRITE_MODE);
			@chmod($cache_path, DIR_WRITE_MODE);
		}

		return $cache_path;
	}

	// ----------------------------------------------------------------------

	/**
	 * Extract gz file
	 */
	private function _extract_gz($source)
	{
		$cache_path = $this->_cache_path();
		ob_start();

		readgzfile($cache_path.$source);

		$file_contents = ob_get_contents();
		ob_end_clean();

		$outname = str_replace('.gz', '', $source);
		file_put_contents($cache_path.$outname, $file_contents);
		@chmod($cache_path.$outname, FILE_WRITE_MODE);
	}

	// ----------------------------------------------------------------------

	/**
	 * Extract zip archive
	 *
	 * Extracts into same directory as the source
	 */
	private function _extract_zip($source)
	{
		$cache_path = $this->_cache_path();

		// unzip
		$zip = zip_open($cache_path.$source);

		if (is_resource($zip))
		{
			while ($zip_entry = zip_read($zip))
			{
				$outfile = $cache_path.zip_entry_name($zip_entry);
				$fp = fopen($outfile, "w");

				if (zip_entry_open($zip, $zip_entry, "r"))
				{
					$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
					fwrite($fp, "$buf");
					zip_entry_close($zip_entry);
				}

				fclose($fp);
				@chmod($outfile, FILE_WRITE_MODE);
			}

			zip_close($zip);
		}
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
	 * Easier superobject access
	 */
	function __get($key)
	{
		return ee()->$key;
	}
}
// END CLASS

/* End of file mcp.ip_to_nation.php */
/* Location: ./system/expressionengine/modules/ip_to_nation/mcp.ip_to_nation.php */