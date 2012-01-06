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
	 * Handle hook call
	 */
	function api_channel_fields_field_edit_vars( $vars )
	{
		$this->EE->lang->loadfile('rte');
		$this->EE->load->helper('form');
		$new_field = array(
			// yup, it needs to be nested in a second array
			array(
				array(
					'data'	=> '<strong>'.lang('enable_rte_for_field').'</strong>'
				),
				array(
					'data'	=> form_radio('field_enable_rte', 'y', ($vars['field_enable_rte']=='y'), 'id="textarea_field_enable_rte_y"').
					           NBS.lang('yes','textarea_field_enable_rte_y').
							   NBS.NBS.NBS.NBS.NBS.
							   form_radio('field_enable_rte', 'n', (empty($vars['field_enable_rte'])||$vars['field_enable_rte']=='n'), 'id="textarea_field_enable_rte_n"').
							   NBS.lang('no','textarea_field_enable_rte_n')
				)
			)
		);
		# find the field we need to drop it in after
		foreach ( $vars['field_type_tables']['textarea'] as $index => $field )
		{
			if ( strpos( $field[0]['data'], 'textarea_field_fmt' ) !== FALSE  )
			{
				break;
			}
		}
		array_splice( $vars['field_type_tables']['textarea'], $index+1, 0, $new_field );
		return $vars;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Handle hook call
	 */
	function api_channel_fields_update_field()
	{
		return array(
			'field_enable_rte'
		);
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