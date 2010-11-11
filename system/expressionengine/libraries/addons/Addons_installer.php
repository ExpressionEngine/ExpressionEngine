<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Core Addon Installer Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Addons_installer {

	var $EE;

	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->library('api');
		$this->EE->load->library('addons');
	}
	
	// --------------------------------------------------------------------

	/**
	 * Addon Installer
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function install($addon, $type = 'module', $check_package = TRUE)
	{
		$third_party = $this->EE->addons->is_package($addon);
		
		if (is_array($type))
		{
			foreach($type as $component)
			{
				$this->install($addon, $component, $check_package);
			}
		}
		elseif ($check_package && $this->EE->addons->is_package($addon))
		{
			if (count($this->EE->addons->_packages[$addon]) > 1)
			{
				$this->EE->functions->redirect(BASE.AMP.'C=addons'.AMP.'M=package_settings'.AMP.'package='.$addon.AMP.'return='.$_GET['C']);
			}
			
			$this->install($addon, key($this->EE->addons->_packages[$addon]), FALSE);
		}
		else
		{
			$method = 'install_'.$type;

			if (method_exists($this, $method))
			{
				if ($third_party)
				{
					$this->EE->load->add_package_path($this->EE->addons->_packages[$addon][$type]['path']);
				}
				
				$this->$method($addon);
				
				if ($third_party)
				{
					$this->EE->load->remove_package_path($this->EE->addons->_packages[$addon][$type]['path']);
				}
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Addon Uninstaller
	 *
	 * Install one or more components
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function uninstall($addon, $type = 'module', $check_package = TRUE)
	{
		$third_party = $this->EE->addons->is_package($addon);
		
		if (is_array($type))
		{
			foreach($type as $component)
			{
				$this->uninstall($addon, $component, $check_package);
			}
		}
		elseif ($check_package && $this->EE->addons->is_package($addon))
		{
			if (count($this->EE->addons->_packages[$addon]) > 1)
			{
				$this->EE->functions->redirect(BASE.AMP.'C=addons'.AMP.'M=package_settings'.AMP.'package='.$addon.AMP.'return='.$_GET['C']);
			}
			
			$this->uninstall($addon, key($this->EE->addons->_packages[$addon]), FALSE);
		}
		else
		{
			$method = 'uninstall_'.$type;

			if (method_exists($this, $method))
			{
				if ($third_party)
				{
					$this->EE->load->add_package_path($this->EE->addons->_packages[$addon][$type]['path']);
				}

				$this->$method($addon);

				if ($third_party)
				{
					$this->EE->load->remove_package_path($this->EE->addons->_packages[$addon][$type]['path']);
				}
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function install_module($module)
	{
		$class = $this->_module_install_setup($module);

		$MOD = new $class();
		$MOD->_ee_path = APPPATH;

		if ($MOD->install() !== TRUE)
		{
			show_error($this->EE->lang->line('module_can_not_be_found'));
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function uninstall_module($module)
	{
		$class = $this->_module_install_setup($module);

		$MOD = new $class();
		$MOD->_ee_path = APPPATH;

		if ($MOD->uninstall() !== TRUE)
		{
			show_error($this->EE->lang->line('module_can_not_be_found'));
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Accessory Installer
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function install_accessory($accessory)
	{
		$class = $this->_accessory_install_setup($accessory);
		$ACC = new $class();
		
		if (method_exists($ACC, 'install'))
		{
			$ACC->install();
		}

		$this->EE->db->insert('accessories', array(
				'class'				=> $class,
				'accessory_version'	=> $ACC->version
		));

		$this->EE->accessories->update_placement($class);
	}

	// --------------------------------------------------------------------

	/**
	 * Accessory Uninstaller
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function uninstall_accessory($accessory)
	{
		$class = $this->_accessory_install_setup($accessory, FALSE);
		$ACC = new $class();
		
		if (method_exists($ACC, 'uninstall'))
		{
			$ACC->uninstall();
		}

		$this->EE->db->delete('accessories', array('class' => $class)); 
	}
	
	// --------------------------------------------------------------------

	/**
	 * Extension Installer
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function install_extension($extension, $enable = FALSE)
	{
		$this->EE->load->model('addons_model');
		
		if ( ! $this->EE->addons_model->extension_installed($extension))
		{
			$EXT = $this->_extension_install_setup($extension);
			
			if (method_exists($EXT, 'activate_extension') === TRUE)
			{
				$activate = $EXT->activate_extension();
			}
		}
		else
		{
			$class = $this->_extension_install_setup($extension, FALSE);
			$this->EE->addons_model->update_extension($class, array('enabled' => 'y'));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Extension Uninstaller
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function uninstall_extension($extension)
	{
		$this->EE->load->model('addons_model');
		$EXT = $this->_extension_install_setup($extension);
		
		$this->EE->addons_model->update_extension(ucfirst(get_class($EXT)), array('enabled' => 'n'));

		if (method_exists($EXT, 'disable_extension') === TRUE)
		{
			$disable = $EXT->disable_extension();
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Fieldtype Installer
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function install_fieldtype($fieldtype)
	{
		$this->EE->api->instantiate('channel_fields');
		
		if ($this->EE->api_channel_fields->include_handler($fieldtype))
		{
			$default_settings = array();
			
			$FT = $this->EE->api_channel_fields->setup_handler($fieldtype, TRUE);
			
			$default_settings = $FT->install();
			
			$this->EE->db->insert('fieldtypes', array(
					'name'					=> $fieldtype,
					'version'				=> $FT->info['version'],
					'settings'				=> base64_encode(serialize($default_settings)),
					'has_global_settings'	=> method_exists($FT, 'display_global_settings') ? 'y' : 'n'
			));
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Fieldtype Uninstaller
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function uninstall_fieldtype($fieldtype)
	{
		$this->EE->api->instantiate('channel_fields');
		
		if ($this->EE->api_channel_fields->include_handler($fieldtype))
		{
			$this->EE->load->dbforge();
			
			// Drop columns
			$this->EE->db->select('channel_fields.field_id, channels.channel_id');
			$this->EE->db->from('channel_fields');
			$this->EE->db->join('channels', 'channels.field_group = channel_fields.group_id');
			$this->EE->db->where('channel_fields.field_type', $fieldtype);
			$query = $this->EE->db->get();

			$ids = array();
			$channel_ids = array();
			
			if ($query->num_rows() > 0)
			{
				foreach($query->result() as $row)
				{
					$ids[] = $row->field_id;
					$channel_ids[] = $row->channel_id;
				}
			}
			
			$ids = array_unique($ids);

			if (count($ids))
			{
				foreach($ids as $id)
				{
					$this->EE->dbforge->drop_column('channel_data', 'field_id_'.$id);
					$this->EE->dbforge->drop_column('channel_data', 'field_ft_'.$id);
				}
				
				// Remove from layouts
				$c_ids = array_unique($channel_ids);
				
				$this->EE->load->library('layout');
				$this->EE->layout->delete_layout_fields($id, $c_ids);				

				$this->EE->db->where_in('field_id', $ids);
				$this->EE->db->delete(array('channel_fields', 'field_formatting'));
			}

			// Uninstall
			$FT = $this->EE->api_channel_fields->setup_handler($fieldtype, TRUE);
			$FT->uninstall();
			
			$this->EE->db->delete('fieldtypes', array('name' => $fieldtype)); 
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Module Install Setup
	 *
	 * Contains common code for install and uninstall routines
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function _module_install_setup($module)
	{
		if ( ! $this->EE->cp->allowed_group('can_admin_modules'))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		if ($module == '')
		{
			show_error($this->EE->lang->line('module_can_not_be_found'));
		}

		if (in_array($module, $this->EE->core->native_modules))
		{
			$path = PATH_MOD.$module.'/upd.'.$module.EXT;
		}
		else
		{
			$path = PATH_THIRD.$module.'/upd.'.$module.EXT;
		}

		if ( ! is_file($path))
		{
			show_error($this->EE->lang->line('module_can_not_be_found'));
		}

		$class  = ucfirst($module).'_upd';

		if ( ! class_exists($class))
		{
			require $path;
		}

		return $class;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Accessory Install Setup
	 *
	 * Contains common code for install and uninstall routines
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function _accessory_install_setup($accessory, $install = TRUE)
	{
		if ( ! $this->EE->cp->allowed_group('can_access_accessories'))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}
		
		if ($accessory == '')
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$class = ucfirst($accessory).'_acc';
		$count = $this->EE->super_model->count('accessories', array('class' => $class));

		if (($install && $count) OR ( ! $install && ! $count))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}
		
		$this->EE->load->library('accessories');
		return $this->EE->accessories->_get_accessory_class($accessory);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Extension Install Setup
	 *
	 * Contains common code for install and uninstall routines
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function _extension_install_setup($extension, $instantiate = TRUE)
	{
		if ( ! $this->EE->cp->allowed_group('can_access_extensions'))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}
		
		if ($extension == '')
		{
			show_error($this->EE->lang->line('no_extension_id'));
		}
		
		$class = ucfirst($extension).'_ext';
		
		if ( ! $instantiate)
		{
			return $class;
		}

		if ( ! class_exists($class))
		{
			if ($this->EE->addons->is_package($extension))
			{
				include(PATH_THIRD.$extension.'/ext.'.$extension.EXT);
			}
			else
			{
				include(PATH_EXT.'ext.'.$extension.EXT);
			}
		}
		return new $class();
	}
	
	// --------------------------------------------------------------------
	
}

// END Addons_installer class


/* End of file Addons_installer.php */
/* Location: ./system/expressionengine/libraries/addons/Addons_installer.php */