<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Addon Installer Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Addons_installer {

	/**
	 * Constructor
	 */
	function __construct()
	{
		ee()->load->library('api');
		ee()->load->library('addons');
		ee()->lang->loadfile('modules');
	}

	// --------------------------------------------------------------------

	/**
	 * Addon Installer
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function install($addon, $type = 'module', $show_package = TRUE)
	{
		$this->_update_addon($addon, $type, 'install', $show_package);
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Addon Uninstaller
	 *
	 * Install one or more components
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function uninstall($addon, $type = 'module', $show_package = TRUE)
	{
		$this->_update_addon($addon, $type, 'uninstall', $show_package);
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
			show_error(lang('module_can_not_be_found'));
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
			show_error(lang('module_can_not_be_found'));
		}
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
		ee()->load->model('addons_model');

		if ( ! ee()->addons_model->extension_installed($extension))
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
			ee()->addons_model->update_extension($class, array('enabled' => 'y'));
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
		ee()->load->model('addons_model');
		$EXT = $this->_extension_install_setup($extension);

		ee()->addons_model->update_extension(ucfirst(get_class($EXT)), array('enabled' => 'n'));

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
		ee()->legacy_api->instantiate('channel_fields');

		if (ee()->api_channel_fields->include_handler($fieldtype))
		{
			$default_settings = array();
			$FT = ee()->api_channel_fields->setup_handler($fieldtype, TRUE);

			$default_settings = $FT->install();

			ee()->db->insert('fieldtypes', array(
				'name'					=> $fieldtype,
				'version'				=> $FT->info['version'],
				'settings'				=> base64_encode(serialize((array)$default_settings)),
				'has_global_settings'	=> method_exists($FT, 'display_global_settings') ? 'y' : 'n'
			));

			ee()->load->library('content_types');

			foreach (ee()->content_types->all() as $content_type)
			{
				if ($FT->accepts_content_type($content_type))
				{
					ee()->api_channel_fields->apply('register_content_type', array($content_type));
				}
			}
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
		ee()->legacy_api->instantiate('channel_fields');

		if (ee()->api_channel_fields->include_handler($fieldtype))
		{
			ee()->load->dbforge();

			// Drop columns
			ee()->db->select('channel_fields.field_id, channels.channel_id');
			ee()->db->from('channel_fields');
			ee()->db->join('channels', 'channels.field_group = channel_fields.group_id');
			ee()->db->where('channel_fields.field_type', $fieldtype);
			$query = ee()->db->get();

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
					ee()->dbforge->drop_column('channel_data', 'field_id_'.$id);
					ee()->dbforge->drop_column('channel_data', 'field_ft_'.$id);
				}

				// Remove from layouts
				$c_ids = array_unique($channel_ids);

				ee()->load->library('layout');
				ee()->layout->delete_layout_fields($ids, $c_ids);

				ee()->db->where_in('field_id', $ids);
				ee()->db->delete(array('channel_fields'));
			}

			// Uninstall
			$FT = ee()->api_channel_fields->setup_handler($fieldtype, TRUE);
			$FT->uninstall();

			ee()->db->delete('fieldtypes', array('name' => $fieldtype));
		}
	}
	// --------------------------------------------------------------------

	/**
	 * RTE Tool Installer
	 *
	 * @access	private
	 * @param String $tool The name of the tool, with or without spaces, but
	 *     without _rte at the end
	 */
	function install_rte_tool($tool)
	{
		ee()->load->add_package_path(PATH_ADDONS.'rte', FALSE);
		ee()->load->model('rte_tool_model');
		ee()->rte_tool_model->add($tool);
	}

	// --------------------------------------------------------------------

	/**
	 * RTE Tool Uninstaller
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function uninstall_rte_tool($tool)
	{
		ee()->load->add_package_path(PATH_ADDONS.'rte', FALSE);
		ee()->load->model('rte_tool_model');
		ee()->rte_tool_model->delete($tool);
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
		if ( ! ee()->cp->allowed_group('can_admin_addons'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ($module == '')
		{
			show_error(lang('module_can_not_be_found'));
		}

		try
		{
			$info = ee('App')->get($module);
		}
		catch (\Exception $e)
		{
			show_error(lang('module_can_not_be_found'));
		}

		$path = $info->getPath() . '/upd.'.$module.'.php';

		if ( ! is_file($path))
		{
			show_error(lang('module_can_not_be_found'));
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
		if ( ! ee()->cp->allowed_group('can_access_extensions'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ($extension == '')
		{
			show_error(lang('no_extension_id'));
		}

		$class = ucfirst($extension).'_ext';

		if ( ! $instantiate)
		{
			return $class;
		}

		if ( ! class_exists($class))
		{
			include(ee()->addons->_packages[$extension]['extension']['path'].'ext.'.$extension.'.php');
		}
		return new $class();
	}

	// --------------------------------------------------------------------

	/**
	 * Universal Addon (Un)Installer
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	private function _update_addon($addon, $type, $action, $show_package)
	{
		// accepts arrays
		if (is_array($type))
		{
			foreach($type as $component)
			{
				$this->_update_addon($addon, $component, $action, $show_package);
			}

			return;
		}

		// first party
		if ( ! ee()->addons->is_package($addon))
		{
			return $this->{$action.'_'.$type}($addon);
		}

		ee()->load->model('addons_model');

		// third party - do entire package
		if ($show_package && count(ee()->addons->_packages[$addon]) > 1)
		{
			ee()->functions->redirect(BASE.AMP.'C=addons'.AMP.'M=package_settings'.AMP.'package='.$addon.AMP.'return='.$_GET['C']);
		}
		else
		{
			$method = $action.'_'.$type;

			if (method_exists($this, $method))
			{
				// Fieldtypes provide an array of multiple fieldtypes
				if ($type === 'fieldtype')
				{
					foreach (ee()->addons->_packages[$addon][$type] as $fieldtype_name => $fieldtype_settings)
					{
						$installed = ee()->addons_model->fieldtype_installed($fieldtype_name);

						//don't perform action if it's not necessary, ie it's already installed or uninstalled
						if (($action === 'install' && ! $installed) || ($action === 'uninstall' && $installed))
						{
							ee()->load->add_package_path($fieldtype_settings['path'], FALSE);

							$this->$method($fieldtype_name);

							ee()->load->remove_package_path($fieldtype_settings['path']);
						}
					}
				}
				else
				{
					ee()->load->add_package_path(ee()->addons->_packages[$addon][$type]['path'], FALSE);

					$this->$method($addon);

					ee()->load->remove_package_path(ee()->addons->_packages[$addon][$type]['path']);
				}
			}
		}
	}
}

// END Addons_installer class

// EOF
