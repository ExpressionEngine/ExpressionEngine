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
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Addons_extensions extends CP_Controller {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_addons', 'can_access_extensions'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('addons');
		$this->lang->loadfile('addons');
		$this->load->model('addons_model');
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */
	function index()
	{
		$this->lang->loadfile('admin'); 
		$this->load->library('extensions');
		$this->load->library('table');

		$this->view->cp_page_title = lang('extensions');
		$this->cp->set_breadcrumb(BASE.AMP.'C=addons', lang('addons'));

		$this->jquery->tablesorter('.mainTable', '{
			widgets: ["zebra"]
		}');

		$this->load->model('addons_model');

		$installed_ext = array();
		$extension_files = $this->addons->get_files('extensions');

		$installed_ext_q = $this->addons_model->get_installed_extensions();
		
		foreach ($installed_ext_q->result_array() as $row)
		{
			// Check the meta data
			$installed_ext[$row['class']] = $row;
		}
		
		$installed_ext_q->free_result();
		$names = array();
		$extcount = 1;
		// var_dump($extension_files); exit;
		foreach($extension_files as $ext_name => $ext)
		{
			// Add the package path so things don't hork in the constructor
			$this->load->add_package_path($ext['path']);

			// Include the file so we can grab its meta data
			$class_name = $ext['class'];
			
			if ( ! class_exists($class_name))
			{
				if ($this->config->item('debug') == 2
					OR ($this->config->item('debug') == 1
						AND $this->session->userdata('group_id') == 1))
				{
					include($ext['path'].$ext['file']);
				}
				else
				{
					@include($ext['path'].$ext['file']);
				}
				
				if ( ! class_exists($class_name))
				{
					trigger_error(str_replace(array('%c', '%f'), array(htmlentities($class_name), htmlentities($ext['path'].$ext['file'])), lang('extension_class_does_not_exist')));
					unset($extension_files[$ext_name]);
					continue;
				}
			}

			$OBJ = new $class_name();

			$meta_keys = array('version', 'name', 'docs_url', 'settings_exist', 'description');
			
			foreach($meta_keys as $meta)
			{
				if ( ! isset($OBJ->$meta))
				{
					$OBJ->meta = '';
				}
			}
			
			// Compare Versions
			if ($this->config->item('allow_extensions') == 'y' && isset($installed_ext[$class_name]))
			{
				if (version_compare($OBJ->version, $installed_ext[$class_name]['version'], '>') && method_exists($OBJ, 'update_extension') === TRUE)
    			{
    				$update = $OBJ->update_extension($installed_ext[$class_name]['version']);
    				
					$this->extensions->version_numbers[$class_name] = $OBJ->version;
	    		}
			}
			
			// View Table Columns
			
			$extension_files[$ext_name]['name'] = (isset($OBJ->name)) ? $OBJ->name : $extension_files[$ext_name]['name'];
			$names[$ext_name] = strtolower($extension_files[$ext_name]['name']);
			
			$extension_files[$ext_name]['status'] = ( ! isset($installed_ext[$ext['class']]) ) ? 'extension_disabled' : 'extension_enabled';
			$extension_files[$ext_name]['status_switch'] = ( ! isset($installed_ext[$ext['class']]) ) ? 'enable_extension' : 'disable_extension';

			$extension_files[$ext_name]['settings_enabled'] = (isset($installed_ext[$ext['class']]) AND $this->config->item('allow_extensions') == 'y' AND $OBJ->settings_exist == 'y');
			$extension_files[$ext_name]['no_settings'] = $OBJ->settings_exist == 'y' ? lang('settings') : '--';
			$extension_files[$ext_name]['settings_url'] = BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file='.$ext_name;
			
			$extension_files[$ext_name]['documentation'] = ($OBJ->docs_url) ? $this->config->item('base_url').$this->config->item('index_page').'?URL='.urlencode($OBJ->docs_url) : '';
			$extension_files[$ext_name]['version'] = $OBJ->version;
			
			if ($this->config->item('allow_extensions') != 'y')
			{
				$extension_files[$ext_name]['status'] = 'extension_disabled';
			}

			$this->load->remove_package_path($ext['path']);
		}

		$vars['extensions_enabled'] = ($this->config->item('allow_extensions') == 'y');
		$vars['extensions_toggle'] = ($this->config->item('allow_extensions') == 'y') ? 'disable_extensions' : 'enable_extensions';
		
		
		// Let's order by name just in case
		asort($names);

		$vars['extension_info'] = array();

		foreach ($names as $k => $v)
		{
			$vars['extension_info'][$k] = $extension_files[$k];
		}

		$extensions_toggle = ($this->config->item('allow_extensions') == 'y') ? 'disable_extensions' : 'enable_extensions';
		$this->cp->set_right_nav(array(
		        $extensions_toggle => BASE.AMP.'C=addons_extensions'.AMP.'M=toggle_extension_confirm'
		    ));

		$this->cp->render('addons/extensions', $vars);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Toggle Extension Confirmation
	 *
	 * Shows a confirmation screen when toggling _all_ extensions
	 *
	 * @access	public
	 * @return	mixed
	 */
	function toggle_extension_confirm()
	{
		$this->lang->loadfile('admin');
		
		$message = ($this->config->item('allow_extensions') == 'y') ? 'disable_extensions_conf' : 'enable_extensions_conf';
		
		$vars = array();
		$vars['form_action'] = 'C=addons_extensions'.AMP.'M=toggle_extension';
		$vars['form_hidden'] = array('which' => 'all');
		$vars['message'] = lang($message);

		$this->view->cp_page_title = lang($message);
		
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=addons' => lang('addons'),
			BASE.AMP.'C=addons_extensions'=> lang('extensions')
		);
		
		$this->cp->render('addons/toggle_confirm', $vars);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Toggle an extension
	 *
	 * If the hidden _which_ field is set - toggle them all
	 *
	 * @access	public
	 * @return	mixed
	 */
	function toggle_extension()
	{
		if ($this->input->post('which') == 'all')
		{
			$new_val = ($this->config->item('allow_extensions') == 'y') ? 'n' : 'y';
			$this->config->_update_config(array('allow_extensions' => $new_val));
			$cp_message = ($new_val == 'y') ? lang('extensions_enabled'): lang('extensions_disabled');
		}
		else
		{
			$file = $this->input->get('which');
			
			$this->load->library('addons');
			
			$installed = $this->addons->get_installed('extensions');
			$extension_files = $this->addons->get_files('extensions');
			
			// It needs to exist and pass the basic security check
			if (isset($extension_files[$file]) AND preg_match("/^[a-z0-9][\w.-]*$/i", $file))
			{
				$this->load->library('addons/addons_installer');
				
				// Which way?
				if (isset($installed[$file]))
				{
					$this->addons_installer->uninstall($file, 'extension');
					$cp_message = lang('extension_disabled');
				}
				else
				{
					$this->addons_installer->install($file, 'extension');
					$cp_message = lang('extension_enabled');
				}
			}
		}
		
		$this->session->set_flashdata('message_success', $cp_message);
		$this->functions->redirect(BASE.AMP.'C=addons_extensions');
	}

	// --------------------------------------------------------------------
	
	/**
	 * Extension Settings
	 *
	 * Displays the extension settings form
	 *
	 * @access	public
	 * @param	message
	 * @return	void
	 */
	function extension_settings($message = '')
	{
		if ($this->config->item('allow_extensions') != 'y')
		{
			show_error(lang('unauthorized_access'));
		}
		
		$file = $this->security->sanitize_filename($this->input->get_post('file'));
			
		if ($this->input->get_post('file') === FALSE 
			OR ! preg_match("/^[a-z0-9][\w.-]*$/i", $file))
		{
			show_error(lang('not_authorized'));
		}

		$this->lang->loadfile('admin');
		$this->load->library('table');
				
		$this->view->cp_page_title = lang('extension_settings');
		$this->cp->set_breadcrumb(BASE.AMP.'C=addons_extensions', lang('extensions'));
		
		$vars['message'] = $message;
		$vars['file'] = $file;
		$class_name = ucfirst($vars['file']).'_ext';
		$current	= array();
		
		/** ---------------------------------------
		/**  Extensions Enabled
		/** ---------------------------------------*/
		
		$this->db->select('settings');
		$this->db->where('enabled', 'y');
		$this->db->where('class', $class_name);
		$this->db->limit(1);
		$query = $this->db->get('extensions');
		
		if ($query->num_rows() > 0 && $query->row('settings')  != '')
		{
			$current = strip_slashes(unserialize($query->row('settings') ));
		}
		
		$name = strtolower($vars['file']);
		$this->addons->get_files('extensions');
		$ext_path = $this->addons->_packages[$name]['extension']['path'];

		/** -----------------------------
		/**  Call Extension File
		/** -----------------------------*/

		if ( ! class_exists($class_name))
		{
			if (file_exists($ext_path.'ext.'.$name.'.php'))
			{
				@include_once($ext_path.'ext.'.$name.'.php');				
			}
	
			if ( ! class_exists($class_name))
			{
				show_error(lang('not_authorized'));
			}
		}

		$OBJ = new $class_name();
		
		foreach(array('description', 'settings_exist', 'docs_url', 'name', 'version') as $meta_item)
		{
			${$meta_item} = ( ! isset($OBJ->{$meta_item})) ? '' : $OBJ->{$meta_item};
		}
				
		if ($name == '')
		{
			$name = ucwords(str_replace('_',' ',$extension_name));
		}
		
		$vars['name'] = $name;
		
		// -----------------------------------
		//  Fetch Extension Language file
		//
		//  If there are settings, then there is a language file
		//  because we need to know all the various variable names in the settings
		//  form.  I was tempted to give these language files a prefix but I 
		//  decided against it for the sake of simplicity and the fact that 
		//  a module might have extension's bundled with them and it would make
		//  sense to have the same language file for both.
		// -----------------------------------

		$this->lang->loadfile(strtolower($vars['file']));
		
		
		/** ---------------------------------------
		/**  Creating Their Own Settings Form?
		/** ---------------------------------------*/


		if (method_exists($OBJ, 'settings_form') === TRUE)
		{
			// we're going to wipe the view vars here in a sec
			$file = $vars['file'];

			// add the package and view paths
			$this->load->add_package_path($ext_path, FALSE);				
			
			// reset view variables
			$vars  = array('_extension_name' => $name);
			

			// fetch the content
			$vars['_extension_settings_body'] = $OBJ->settings_form($current);

			// restore our package paths
			$this->load->remove_package_path($ext_path);


			// load it up, kapowpow!
			$this->view->cp_heading = lang('extension_settings').': '.$name;
			$this->cp->render('addons/extensions_settings_custom', $vars);
			return;
		}

		foreach ($OBJ->settings() as $key => $options)
		{
			if (isset($current[$key]))
			{
				$value = $current[$key];
			}
			elseif (is_array($options))
			{
				$value = $options[2];
			}
			elseif (is_string($options))
			{
				$value = $options;
			}
			else
			{
				$value = '';
			}

			$sub = '';
			$details = '';
			$selected = '';

			if (isset($subtext[$key]))
			{
				foreach ($subtext[$key] as $txt)
				{
					$sub .= lang($txt);
				}
			}
			
			if ( ! is_array($options))
			{
				$vars['fields'][$key] = array('type' => 'i', 'value' => array('name' => $key, 'value' => str_replace("\\'", "'", $value), 'id' => $key),
											'subtext' => $sub, 'selected' => $selected);
				continue;
			}
			
			switch ($options[0])
			{
				case 's':
				case 'ms':
					// Select fields
					foreach ($options[1] as $k => $v)
					{
						$details[$k] = lang($v);
					}

					$selected = $value;
					break;
				case 'r':
				case 'c':
					// Radio buttons and checkboxes
					foreach ($options[1] as $k => $v)
					{
						$checked = ($k == $value OR (is_array($value) && in_array($k, $value))) ? TRUE : FALSE;

						$details[] = array('name' => (($options[0] == 'c') ? $key.'[]' : $key), 'value' => $k, 'id' => $key.'_'.$k, 'label' => $v, 'checked' => $checked);
					}
					break;
				case 't':
					// Textareas

					// The "kill_pipes" index instructs us to turn pipes into newlines
					if (isset($options['1']['kill_pipes']) && $options['1']['kill_pipes'] === TRUE)
					{
						$text = str_replace('|', NL, $value);
					}
					else
					{
						$text = $value;
					}

					$rows = (isset($options['1']['rows'])) ? $options['1']['rows'] : '20';

					$text = str_replace("\\'", "'", $text);

					$details = array('name' => $key, 'value' => $text, 'rows' => $rows, 'id' => $key);
					break;
				case 'i':
					// Input fields
					$details = array('name' => $key, 'value' => str_replace("\\'", "'", $value), 'id' => $key);
					break;
			}

			$vars['fields'][$key] = array('type' => $options[0], 'value' => $details, 'subtext' => $sub, 'selected' => $selected);
		}
		
		$this->view->hidden = array('file' => $vars['file']);
		$this->view->cp_heading = lang('extension_settings').': '.$name;
		$this->cp->render('addons/extensions_settings', $vars);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Save Extension Settings
	 *
	 * @access	public
	 * @param	type
	 * @return	void
	 */
	function save_extension_settings()
	{
		if ($this->config->item('allow_extensions') != 'y')
		{
			show_error(lang('unauthorized_access'));
		}
			
		if ($this->input->get_post('file') === FALSE OR ! preg_match("/^[a-z0-9][\w.-]*$/i",$this->input->get_post('file')))
		{
			return FALSE;
		}

		$this->lang->loadfile('admin');
		
		$this->view->cp_page_title = lang('extension_settings');
						
		$vars['file'] = $this->input->get_post('file');
		$class_name = ucfirst($vars['file']).'_ext';
		
		/** -----------------------------
		/**  Call Extension File
		/** -----------------------------*/
		
		$name = strtolower($vars['file']);
		$this->addons->get_files('extensions');
		$ext_path = $this->addons->_packages[$name]['extension']['path'];
		

		/** -----------------------------
		/**  Call Extension File
		/** -----------------------------*/

		if ( ! class_exists($class_name))
		{
		
			if (file_exists($ext_path.'ext.'.$name.'.php'))
			{

				@include_once($ext_path.'ext.'.$name.'.php');				
			}

			if ( ! class_exists($class_name)) return FALSE;
		}

		$OBJ = new $class_name();
									
		/** ---------------------------------------
		/**  Processing Their Own Settings Form?
		/** ---------------------------------------*/
		
		if (method_exists($OBJ, 'settings_form') === TRUE)
		{
			$OBJ->save_settings();
			
			$this->functions->redirect(BASE.AMP.'C=addons_extensions');
		}		
						
		if (method_exists($OBJ, 'settings') === TRUE)
		{
			$settings = $OBJ->settings();
		}
			
		$insert = array();

		foreach($settings as $key => $value)
		{
			if ( ! is_array($value))
			{
				$insert[$key] = ($this->input->post($key) !== FALSE) ? $this->input->get_post($key) : $value;
			}
			elseif (is_array($value) && isset($value['1']) && is_array($value['1']))
			{
				if(is_array($this->input->post($key)) OR $value[0] == 'ms' OR $value[0] == 'c')
				{
					$data = (is_array($this->input->post($key))) ? $this->input->get_post($key) : array();
					
					$data = array_intersect($data, array_keys($value['1']));
				}
				else
				{
					if ($this->input->post($key) === FALSE)
					{
						$data = ( ! isset($value['2'])) ? '' : $value['2'];
					}
					else
					{
						$data = $this->input->post($key);
					}
				}
				
				$insert[$key] = $data;
			}
			else
			{
				$insert[$key] = ($this->input->post($key) !== FALSE) ? $this->input->get_post($key) : '';
			}
		}
		
		$this->db->where('class', $class_name);
		$this->db->update('extensions', array('settings' => serialize($insert)));
		
		$this->session->set_flashdata('message_success', lang('preferences_updated'));
		$this->functions->redirect(BASE.AMP.'C=addons_extensions');
	}

	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file addons.php */
/* Location: ./system/expressionengine/controllers/cp/addons.php */
