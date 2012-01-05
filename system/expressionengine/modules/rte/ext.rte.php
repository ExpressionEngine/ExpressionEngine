<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Rte_ext {

	var $name = 'Rich Text Editor';
	var $version = '1,0';
	var $settings_exist = 'n';
	var $docs_url = 'http://expressionengine.com/user_guide/modules/rich-text-editor/index.html';
	var $required_by = array('module');

	private $EE;
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Handle hook call
	 */
	function myaccount_nav_setup()
	{
		$this->EE->lang->loadfile('rte');
		return array(
			'customize_cp' => array(
				lang('rte_prefs')	=> array(
					'module'	=> 'rte',
					'method'	=> 'myaccount_settings'
				)
			)
		);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Handle hook call
	 */
	function cp_menu_array( $menu )
	{
		$menu['admin']['admin_content']['rte_settings'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=rte';
		return $menu;
	}
	 
	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 */
	function activate_extension()
	{
		return TRUE;
		//show_error('This extension is automatically installed with the Rich Text Editor module');
	}

	// --------------------------------------------------------------------

	/**
	 * Update Extension
	 */
	function update_extension($current = FALSE)
	{
		return TRUE;
		//show_error('This extension is automatically updated with the Rich Text Editor module');
	}

	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 */
	function disable_extension()
	{
		return TRUE;
		//show_error('This extension is automatically deleted with the Rich Text Editor module');
	}
	
		// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 */
	function uninstall_extension()
	{
		return TRUE;
		//show_error('This extension is automatically deleted with the Rich Text Editor module');
	}
	
}

/* End of file ext.rte.php */
/* Location: ./system/expressionengine/modules/rte/ext.rte.php */