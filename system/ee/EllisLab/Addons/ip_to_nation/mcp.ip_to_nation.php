<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Ip_to_nation_mcp {

	/**
	  * Constructor
	  */
	function __construct()
	{
		ee()->load->helper('array');
		ee()->load->model('ip_to_nation_data', 'ip_data');

		$this->base_url = ee('CP/URL')->make('addons/settings/ip_to_nation')->compile();
	}

	// ----------------------------------------------------------------------

	/**
	  * Nation Home Page
	  */
	function index()
	{
		$ip_search = array(
			'base_url' => ee('CP/URL')->make('addons/settings/ip_to_nation/search'),
			'cp_page_title' => lang('ip_search'),
			'save_btn_text' => 'btn_search',
			'save_btn_text_working' => 'btn_searching',
			'alerts_name' => 'ip_search',
			'sections' => array(
				array(
					array(
						'title' => 'search_for_ip',
						'desc' => 'search_for_ip_desc',
						'fields' => array(
							'ip' => array(
								'type' => 'text',
								'value' => ee()->input->post('ip') ?: ''
							)
						)
					),
				)
			)
		);

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
			ee()->session->set_flashdata('message_failure', lang('cache_full'));
			ee()->functions->redirect($this->base_url.AMP.'method=index');
		}

		// look for data files that they may have
		// uploaded manually
		$data_files = $this->_cache_files('zip,gz');

		$last_update = ee()->config->item('ip2nation_db_date');
		$last_update = ($last_update) ? lang('last_update') . ' ' . $this->localize->human_time($last_update) : '';

		ee()->cp->add_js_script('fp_module', 'ip_to_nation');

		ee()->javascript->set_global(array(
			'ip2n' => array(
				'run_script' => 'update',
				'base_url' => $this->base_url,
				'steps' => array('download_data', 'extract_data', 'insert_data'),
				'lang' => array(
					'ip_db_updating' => lang('ip_db_updating'),
					'ip_db_failed' => lang('ip_db_failed')
				)
			)
		));

		$countries = $this->_country_names();

		$query = $this->db->get('ip2nation_countries')->result();
		$status = array();

		foreach ($query as $row)
		{
			$status[$row->code] = $row->banned;
		}

		$country_list = array();
		$selected = array();

		foreach ($countries as $key => $val)
		{
			// Don't show countries for which we lack IP information
			if (isset($status[$key]))
			{
				$country_list[$key] = $val;

				if ($status[$key] == 'y')
				{
					$selected[] = $key;
				}
			}
		}

		$banned_list = array(
			'base_url' => ee('CP/URL')->make('addons/settings/ip_to_nation/update'),
			'cp_page_title' => lang('banlist'),
			'save_btn_text' => 'btn_save_banlist',
			'save_btn_text_working' => 'btn_saving',
			'alerts_name' => 'banlist',
			'sections' => array(
				array(
					array(
						'title' => 'update_ips',
						'desc' => sprintf(lang('update_info').'<em>'.$last_update.'</em>', $this->cp->masked_url('http://www.maxmind.com/app/geolite')),
						'fields' => array(
							'action_button' => array(
								'type' => 'action_button',
								'text' => 'update_ips',
								'link' => ee('CP/URL')->make('addons/settings/ip_to_nation/download_data'),
								'class' => ''
							)
						)
					),
					array(
						'title' => 'banned_countries',
						'desc' => 'ban_info',
						'fields' => array(
							'countries' => array(
								'type' => 'checkbox',
								'choices' => $country_list,
								'value' => $selected,
								'wrap' => TRUE,
								'no_results' => array(
									'text' => lang('no_countries')
								)
							)
						)
					)
				)
			)
		);

		$vars = array(
			'cp_page_title' => lang('ip_to_nation_module_name'),
			'ip_search' => $ip_search,
			'banned_list' => $banned_list
		);

		return ee('View')->make('ip_to_nation:index')->render($vars);
	}

	/**
	 * Search for countries via IP
	 */
	public function search()
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
   					ee('CP/Alert')->makeInline('ip_search')
						->asIssue()
						->withTitle(lang('ip_address_not_located'))
						->addToBody(lang('ip_not_found'))
						->now();
   				}
   				else
   				{
   					ee('CP/Alert')->makeInline('ip_search')
						->asSuccess()
						->withTitle(lang('ip_address_located'))
						->addToBody(lang('ip_result') . ' <b>' . $country . '</b>')
						->now();
   				}
		    }
		    else
		    {
		    	ee('CP/Alert')->makeInline('ip_search')
					->asIssue()
					->withTitle(lang('ip_address_not_located'))
					->addToBody(lang('ip_not_valid'))
					->now();
		    }
		}

		return $this->index();
	}

	// ----------------------------------------------------------------------

	/**
	  * Update Ban List
	  */
	function update()
	{
		$countries = $this->_country_names();

		$input_countries = (isset($_POST['countries'])) ? $_POST['countries'] : array();

		// remove unknowns and 'n's
		$ban = array_intersect($input_countries, array_keys($countries));

		// ban them
		$this->ip_data->ban($ban);

		ee('CP/Alert')->makeInline('banlist')
			->asSuccess()
			->withTitle(lang('banlist_updated'))
			->addToBody(lang('banlist_updated_desc'))
			->defer();

		ee()->functions->redirect($this->base_url);
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
		$cache_path = PATH_CACHE.'ip2nation/';

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
		$conf = ee()->config->loadFile('countries');
		return $conf['countries'];
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

// EOF
