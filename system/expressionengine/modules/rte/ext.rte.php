<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Rte_ext {

	var $name			= 'Rich Text Editor';
	var $version		= '1,0';
	var $settings_exist	= 'n';
	var $docs_url		= 'http://expressionengine.com/user_guide/modules/rich-text-editor/index.html';
	var $required_by	= array('module');

	private $EE;
	private $module = 'rte';
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Loads My RTE Prefs into heMy Account page
	 * 
	 * @return	array	Hash of new items to add to the MyAccount Nav
	 */
	function myaccount_nav_setup()
	{
		$this->EE->lang->loadfile($this->module);
		return array(
			'customize_cp' => array(
				lang('rte_prefs')	=> array(
					'module'	=> $this->module,
					'method'	=> 'myaccount_settings'
				)
			)
		);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Add RTE prefs to the CP Menu
	 * 
	 * @param	array	The CP menu array
	 * @return	array	The updated CP menu array
	 */
	function cp_menu_array( $menu )
	{
		$this->EE->lang->loadfile($this->module);
		$menu['admin']['admin_content']['rte_settings'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module;
		return $menu;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Add RTE JS to the Publish/Edit page
	 * 
	 * @param	array	$results	The row_array for the entry
	 * @return	array	Modified result array
	 */
	function publish_form_entry_data( $results )
	{
		# get the Module
		include_once(APPPATH.'modules/'.$this->module.'/'.'mcp.'.$this->module.'.php');
		$class_name	= ucfirst($this->module).'_mcp';
		$RTE		= new $class_name();
		
		# WysiHat
		$this->EE->cp->add_to_head($this->EE->view->head_link('css/rte.css'));
		$this->EE->cp->add_js_script(array('plugin' => 'wysihat'));
		
		# Toolset JS
		$js = array(
			$RTE->build_rte_toggle_js()
		);
		if ($this->EE->session->userdata('rte_enabled') == 'y')
		{
			$js[] = $RTE->build_toolset_js();
		}
		$this->EE->javascript->output($js);

		return $results;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 * This extension is automatically installed with the Rich Text Editor module
	 */
	function activate_extension()
	{
		return TRUE;
		# show_error('This extension is automatically installed with the Rich Text Editor module');
	}

	// --------------------------------------------------------------------

	/**
	 * Update Extension
	 * This extension is automatically updated with the Rich Text Editor module
	 */
	function update_extension($current = FALSE)
	{
		return TRUE;
		# show_error('This extension is automatically updated with the Rich Text Editor module');
	}

	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 * This extension is automatically disabled with the Rich Text Editor module
	 */
	function disable_extension()
	{
		return TRUE;
		# show_error('This extension is automatically deleted with the Rich Text Editor module');
	}
	
		// --------------------------------------------------------------------

	/**
	 * Uninstall Extension
	 * This extension is automatically uninstalled with the Rich Text Editor module
	 */
	function uninstall_extension()
	{
		return TRUE;
		# show_error('This extension is automatically deleted with the Rich Text Editor module');
	}
	
}

/* End of file ext.rte.php */
/* Location: ./system/expressionengine/modules/rte/ext.rte.php */