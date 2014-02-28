<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Plugin Administration Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Addons_plugins extends CP_Controller {

	var $paths = array();

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_addons', 'can_access_plugins'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('admin');
	}

	// --------------------------------------------------------------------

	/**
	 * Plugin Homepage
	 *
	 * @access	public
	 * @return	void
	 */
	function index()
	{
		$this->load->library('table');

		$this->view->cp_page_title = lang('plugins');
		$this->cp->set_breadcrumb(BASE.AMP.'C=addons', lang('addons'));

		$this->jquery->tablesorter('.mainTable', '{
			headers: {2: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->javascript->output('
			$(".toggle_all").toggle(
				function(){
					$(".mainTable tbody tr").addClass("selected");
					$("input.toggle").each(function() {
						this.checked = true;
					});
				}, function (){
					$(".mainTable tbody tr").removeClass("selected");
					$("input.toggle").each(function() {
						this.checked = false;
					});
				}
			);
		');

		$this->javascript->compile();

		// Grab the data
		$plugins = $this->_get_installed_plugins();

		// Check folder permissions for all paths
		$is_writable = TRUE;
		foreach(array(PATH_PI, PATH_THIRD) as $path)
		{
			if ( ! is_really_writable($path))
			{
				$is_writable = FALSE;
			}
		}

		$sortby = FALSE;
		$sort_url = FALSE;

		// Assemble view variables
		$vars['is_writable'] = $is_writable;
		$vars['plugins'] = $plugins;

		$this->cp->render('addons/plugin_manager', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Plugin Details
	 *
	 * Show all the plugin information
	 *
	 * @access	public
	 * @return	void
	 */
	function info()
	{
		$name = $this->input->get('name');

		// Basic security check
		if ( ! $name OR ! preg_match("/^[a-z0-9][\w.-]*$/i", $name))
		{
			$this->session->set_flashdata('message_failure', lang('no_additional_info'));
			$this->functions->redirect(BASE.AMP.'C=addons_plugins');
		}

		$this->load->library('table');

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->javascript->compile();

		$plugin = $this->_get_plugin_info($name);

		$this->view->cp_page_title = $plugin['pi_name'];

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=addons' => lang('addons'),
			BASE.AMP.'C=addons_plugins'=> lang('addons_plugins')
		);

		$this->cp->render('addons/plugin_info', array('plugin' => $plugin));
	}


	// --------------------------------------------------------------------

	/**
	 * Plugin Remove Confirm
	 *
	 * Confirm Plugin Deletion
	 *
	 * @access	public
	 * @return	void
	 */
	function remove_confirm()
	{
		if ($this->config->item('demo_date') != FALSE)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->helper('file');

		$this->view->cp_page_title = lang('plugin_delete_confirm');
		$this->cp->set_breadcrumb(BASE.AMP.'C=addons', lang('addons'));

		$hidden = $this->input->post('toggle');

		if (count($hidden) == 0)
		{
			$this->functions->redirect(BASE.AMP.'C=addons_plugins');
		}

		$vars['message'] = (count($hidden) > 1) ? 'plugin_multiple_confirm' : 'plugin_single_confirm';
		$vars['hidden'] = $hidden;

		$this->cp->render('addons/plugin_delete', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Plugins
	 *
	 * Remove Plugin Files
	 *
	 * @access	public
	 * @return	void
	 */
	function remove()
	{
		if ($this->config->item('demo_date') != FALSE)
		{
			show_error(lang('unauthorized_access'));
		}

		$plugins = $this->input->post('deleted');

		$cp_message_success = '';
		$cp_message_failure = '';

		if ( ! is_array($plugins))
		{
			$this->functions->redirect(BASE.AMP.'C=addons_plugins');
		}

		foreach($plugins as $name)
		{
			// We now have more than one path, so we try them all
			$success = FALSE;

			if (@unlink(PATH_PI.'pi.'.$name.'.php'))
			{
				$success = TRUE;
			}
			else
			{
				// first thing's first, let's make sure this isn't part of a package
				$files = glob(PATH_THIRD.$name.'/*.php');
				$pi_key = array_search(PATH_THIRD.$name.'/pi.'.$name.'.php', $files);

				// remove this file from the list
				unset($files[$pi_key]);

				// any other PHP files in this directory?  If not, balleet!
				if (empty($files))
				{
					$this->functions->delete_directory(PATH_THIRD.$name, TRUE);
					$success = TRUE;
				}
			}

			if ($success)
			{
				$cp_message_success .= ($success) ? lang('plugin_removal_success') : lang('plugin_removal_error');
				$cp_message_success .= ' '.ucwords(str_replace("_", " ", $name)).'<br>';
			}
			else
			{
				$cp_message_failure .= ($success) ? lang('plugin_removal_success') : lang('plugin_removal_error');
				$cp_message_failure .= ' '.ucwords(str_replace("_", " ", $name)).'<br>';
			}
		}

		if ($cp_message_success != '')
		{
			$cp_message['message_success'] = $cp_message_success;
		}

		if ($cp_message_failure != '')
		{
			$cp_message['message_failure'] = $cp_message_failure;
		}

		$this->session->set_flashdata($cp_message);
		$this->functions->redirect(BASE.AMP.'C=addons_plugins');
	}

	// --------------------------------------------------------------------

	/**
	 * Install a Plugin
	 *
	 * Downloads and Installs a Plugin
	 *
	 * @access	public
	 * @return	void
	 */
	function install()
	{
		if ($this->config->item('demo_date') != FALSE)
		{
			show_error(lang('unauthorized_access'));
		}

		@include_once(APPPATH.'libraries/Pclzip.php');

		if ( ! is_really_writable(PATH_THIRD))
		{
			$this->session->set_flashdata('message_failure', lang('plugin_folder_not_writable'));
			$this->functions->redirect(BASE.AMP.'C=addons_plugins');
		}

		if ( ! extension_loaded('curl') OR ! function_exists('curl_init'))
		{
			$this->session->set_flashdata('message_failure', lang('plugin_no_curl_support'));
			$this->functions->redirect(BASE.AMP.'C=addons_plugins');
		}

		$file = $this->input->get_post('file');

		$local_name = basename($file);
		$local_file = PATH_THIRD.$local_name;

		// Get the remote file
		$c = curl_init($file);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

		// prevent a PHP warning on certain servers
		if ( ! ini_get('safe_mode') && ! ini_get('open_basedir'))
		{
			curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
		}

		$code = curl_exec($c);
		curl_close($c);

		$file_info = pathinfo($local_file);

		if ($file_info['extension'] == 'txt' ) // Get rid of any notes/headers in the TXT file
		{
			$code = strstr($code, '<?php');
		}

		if ( ! $fp = fopen($local_file, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			$this->session->set_flashdata('message_failure', lang('plugin_problem_creating_file'));
			$this->functions->redirect(BASE.AMP.'C=addons_plugins');
		}

		flock($fp, LOCK_EX);
		fwrite($fp, $code);
		flock($fp, LOCK_UN);
		fclose($fp);

		@chmod($local_file, DIR_WRITE_MODE);

		// Check file information so we know what to do with it

		if ($file_info['extension'] == 'txt' ) // We've got a TXT file!
		{
			$new_file = basename($local_file, '.txt');
			if ( ! rename($local_file, PATH_THIRD.$new_file))
			{
				$cp_type = 'message_failure';
				$cp_message = lang('plugin_install_other');
			}
			else
			{
				@chmod($new_file, DIR_WRITE_MODE);
				$cp_type = 'message_success';
				$cp_message = lang('plugin_install_success');
			}
		}
		else if ($file_info['extension'] == 'zip' ) // We've got a ZIP file!
		{
			// Unzip and install plugin
			if (class_exists('PclZip'))
			{
				// The chdir breaks CI's view loading, so we
				// store a reference and reset after the unzip
				$_ref = getcwd();

				$zip = new PclZip($local_file);

				$temp_dir = PATH_THIRD.'47346fc7580de7596d7df8d115a3545d';
				mkdir($temp_dir);
				chdir($temp_dir);

				$ok = @$zip->extract('');
				unlink($local_file);

				if ($ok)
				{
					// check if the file is sitting right here
					$pi_files = glob($temp_dir.'/pi.*.php');

					if (empty($pi_files))
					{
						// check directories (GLOB_ONLYDIR not available on Windows < PHP 4.3.3, too bad...)
						// stop at first plugin file found to keep things sane
						foreach (glob($temp_dir.'/*', GLOB_ONLYDIR) as $dir)
						{
							$pi_files = glob($dir.'/pi.*.php');

							if ( ! empty($pi_files))
							{
								break;
							}
						}
					}

					if (empty($pi_files))
					{
						$cp_type = 'message_failure';
						$cp_message = lang('plugin_error_no_plugins_found');
					}
					else
					{
						$filename = basename($pi_files[0]);
						$package = substr($filename, 3, -4);

						// does this add-on already exist?
						if (is_dir(PATH_THIRD.$package))
						{
							$cp_type = 'message_failure';
							$cp_message = lang('plugin_error_package_already_exists');
						}
						else
						{
							mkdir(PATH_THIRD.$package);
							rename(rtrim(substr($pi_files[0], 0, - strlen($filename)), '/'), PATH_THIRD.$package);
							$cp_type = 'message_success';
							$cp_message = lang('plugin_install_success');
						}
					}
				}
				else
				{
					$cp_type = 'message_failure';
					$cp_message = lang('plugin_error_uncompress');
				}

				// cleanup temp zip directory
				$this->functions->delete_directory($temp_dir, TRUE);

				// Fix loader scope
				chdir($_ref);
			}
			else
			{
				$cp_type = 'message_failure';
				$cp_message = lang('plugin_error_no_zlib');
			}
		}
		else
		{
			$cp_type = 'message_failure';
			$cp_message = lang('plugin_install_other');
		}

		$this->session->set_flashdata($cp_type, $cp_message);
		$this->functions->redirect(BASE.AMP.'C=addons_plugins');
	}

	// --------------------------------------------------------------------

	/**
	 * Sorting Callback
	 *
	 * @access	private
	 * @return	mixed	array of plugin data
	 */
	function _plugin_title_sorter($a, $b)
	{
		return strnatcasecmp($a['title'], $b['title']);
	}

	// --------------------------------------------------------------------

	/**
	 * Get installed plugins
	 *
	 * Get a list of installed plugins
	 *
	 * @access	private
	 * @return	mixed	array of plugin data
	 */
	function _get_installed_plugins()
	{
		$this->load->helper(array('file', 'directory'));

		$ext_len = strlen('.php');

		$plugin_files = array();
		$plugins = array();

		// Get a list of all plugins
		// first party first!
		if (($list = get_filenames(PATH_PI)) !== FALSE)
		{
			foreach ($list as $file)
			{
				if (strncasecmp($file, 'pi.', 3) == 0 &&
					substr($file, -$ext_len) == '.php' &&
					strlen($file) > 7 &&
					in_array(substr($file, 3, -$ext_len), $this->core->native_plugins))
				{
					$plugin_files[$file] = PATH_PI.$file;
				}
			}
		}


		// third party, in packages
		if (($map = directory_map(PATH_THIRD, 2)) !== FALSE)
		{
			foreach ($map as $pkg_name => $files)
			{
				if ( ! is_array($files))
				{
					$files = array($files);
				}

				foreach ($files as $file)
				{
					if (is_array($file))
					{
						// we're only interested in the top level files for the addon
						continue;
					}

					// we gots a plugin?
					if (strncasecmp($file, 'pi.', 3) == 0 &&
						substr($file, -$ext_len) == '.php' &&
						strlen($file) > strlen('pi.'.'.php'))
					{
						if (substr($file, 3, -$ext_len) == $pkg_name)
						{
							$plugin_files[$file] = PATH_THIRD.$pkg_name.'/'.$file;
						}
					}
				}
			}
		}

		ksort($plugin_files);

		// Grab the plugin data
		foreach ($plugin_files as $file => $path)
		{
			// Used as a fallback name and url identifier
			$filename = substr($file, 3, -$ext_len);

			if ($temp = $this->_magpie_check($filename, $path))
			{
				$plugin_info = $temp;
			};

			@include_once($path);

			if (isset($plugin_info) && is_array($plugin_info))
			{
				// Third party?
				$plugin_info['installed_path'] = $path;

				// fallback on the filename if no name is given
				if ( ! isset($plugin_info['pi_name']) OR $plugin_info['pi_name'] == '')
				{
					$plugin_info['pi_name'] = $filename;
				}

				if ( ! isset($plugin_info['pi_version']))
				{
					$plugin_info['pi_version'] = '--';
				}
				$plugins[$filename] = $plugin_info;
			}
			else
			{
				log_message('error', "Invalid Plugin Data: {$filename}");
			}

			unset($plugin_info);
		}

		return $plugins;
	}

	// --------------------------------------------------------------------

	/**
	 * Get plugin info
	 *
	 * Check for a plugin and get it's information
	 *
	 * @access	private
	 * @param	string	plugin filename
	 * @return	mixed	array of plugin data
	 */
	function _get_plugin_info($filename = '')
	{
		if ( ! $filename)
		{
			return FALSE;
		}

		$path = PATH_PI.'pi.'.$filename.'.php';

		if ( ! file_exists($path))
		{
			$path = PATH_THIRD.$filename.'/pi.'.$filename.'.php';

			if ( ! file_exists($path))
			{
				return FALSE;
			}
		}

		if ($temp = $this->_magpie_check($filename, $path))
		{
			$plugin_info = $temp;
		};

		include_once($path);

		if ( ! isset($plugin_info) OR ! is_array($plugin_info))
		{
			return FALSE;
		}

		// We need to clean up for display, might as
		// well do it here and keep the view tidy

		foreach ($plugin_info as $key => $val)
		{
			if ($key == 'pi_author_url')
			{
				$qm = ($this->config->item('force_query_string') == 'y') ? '' : '?';

				$val = prep_url($val);
				$val = anchor($this->functions->fetch_site_index().$qm.'URL='.$val, $val);
			}
			else if ($key == 'pi_usage')
			{
				$val = nl2br(htmlspecialchars($val));
			}

			$plugin_info[$key] = $val;
		}

		return $plugin_info;
	}

	// -------------------------------------------------------------------------

	/**
	 * Check for usage of the magpie plugin and get the plugin_info manually
	 * @param  string $filename The filename to check
	 * @param  String $path     Path where the file exists
	 * @return Mixed            Returns $plugin_info if it's MagPie, otherwise
	 *                          nothing
	 */
	private function _magpie_check($filename, $path)
	{
		// Magpie maight already be in use for an accessory or other function
		// If so, we still need the $plugin_info, so we'll open it up and
		// harvest what we need. This is a special exception for Magpie.
		if ($filename == 'magpie' AND in_array($path, get_included_files()) AND class_exists('Magpie'))
		{
			$contents = file_get_contents($path);
			$start = strpos($contents, '$plugin_info');
			$length = strpos($contents, 'Class Magpie') - $start;
			return eval(substr($contents, $start, $length));
		}
	}
}

// END CLASS

/* End of file addons_plugins.php */
/* Location: ./system/expressionengine/controllers/cp/addons_plugins.php */