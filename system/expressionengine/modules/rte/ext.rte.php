<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Rte_ext {

	var $name			= 'Rich Text Editor';
	var $version		= '1.0';
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

		$this->_base_url		= BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=rte';
		$this->_form_base		= 'C=myaccount'.AMP.'M=custom_screen_save'.AMP.'extension=rte'.AMP.'method=myaccount_settings_update';
		$this->_myaccount_url	= BASE.AMP.'C=myaccount'.AMP.'M=custom_screen'.AMP.'extension=rte'.AMP.'method=myaccount_settings';
	}

	// --------------------------------------------------------------------

	/**
	 * Loads My RTE Prefs into the My Account page
	 * 
	 * @return	array	Hash of new items to add to the MyAccount Nav
	 */
	function myaccount_nav_setup()
	{
		// Check for the last_call
		$additional_nav = ($this->EE->extensions->last_call) ? 
			$this->EE->extensions->last_call :
			array();

		$this->EE->lang->loadfile($this->module);
		return array_merge_recursive(
			$additional_nav,
			array(
				'customize_cp' => array(
					lang('rte_prefs')	=> array(
						'extension'	=> 'rte',
						'method'	=> 'myaccount_settings'
					)
				)
			)
		);
	}
	
	// --------------------------------------------------------------------

	/**
	 * MyAccount Rich Text Editor Preferences Page
	 *
	 * @access	public
	 * @param	array $vars Hash of page vars
	 * @return	string The page contents
	 */
	public function myaccount_settings($vars)
	{
		$this->EE->load->library('javascript');
		$this->EE->load->model('rte_toolset_model');
		
		// get the member prefs
		$prefs = $this->EE->db->select( array( 'rte_enabled','rte_toolset_id' ))
			->get_where(
				'members',
				array('member_id'=>$this->EE->session->userdata('member_id'))
			)
			->row();
		
		// get the toolset options
		$toolset_opts = $this->EE->rte_toolset_model->get_member_options();
		foreach ($toolset_opts as $id => $name)
		{
			$toolset_opts[$id] = lang($name);
		}
		
		// setup the page
		$vars = array(
			'cp_page_title'			=> lang('rte_prefs'),
			'action'				=> $this->_form_base.AMP.'method=myaccount_settings_update',
			'rte_enabled'			=> $prefs->rte_enabled,
			'rte_toolset_id_opts'	=> $toolset_opts,
			'rte_toolset_id'		=> $prefs->rte_toolset_id
		);
		
		// JS stuff
		$this->EE->javascript->set_global(array(
			'rte'	=> array(
				'toolset_builder_url'	=> $this->_base_url.AMP.'method=edit_toolset'.AMP.'private=true',
				'custom_toolset_text'	=> lang('my_custom_toolset'),
				'edit_text'				=> lang('edit')
			)
		));
		$this->EE->cp->add_js_script(array(
			'file'	=> 'cp/rte',
			'ui'	=> 'dialog'
		));
		$this->EE->javascript->compile();
		
		// add the CSS
		$this->EE->cp->add_to_head($this->EE->view->head_link('css/rte.css'));
		
		// return the page
		return $this->EE->load->view('myaccount_settings', $vars, TRUE);
	}

	// --------------------------------------------------------------------
	
	/**
	 * MyAccount RTE settings form action
	 *
	 * @access	public
	 * @return	void
	 */
	public function myaccount_settings_update()
	{
		// set up the validation
		$this->EE->load->library('form_validation');
		$this->EE->form_validation->set_rules(
			'rte_enabled',
			lang('enabled_question'),
			'required|enum[y,n]'
		);
		$this->EE->form_validation->set_rules(
			'rte_toolset_id',
			lang('choose_default_toolset'),
			'required|is_numeric'
		);
		
		// success
		if ($this->EE->form_validation->run())
		{
			// update the prefs
			$this->EE->db->update(
				'members',
				array(
					'rte_enabled'		=> $this->EE->input->get_post('rte_enabled'),
					'rte_toolset_id'	=> $this->EE->input->get_post('rte_toolset_id')
				),
				array('member_id' => $this->EE->session->userdata('member_id'))
			);
			
			$this->EE->session->set_flashdata('message_success', lang('preferences_saved'));
		}
		else
		{
			$this->EE->session->set_flashdata('message_failure', lang('preferences_not_saved'));
		}

		$this->EE->functions->redirect($this->_myaccount_url);
	}

	// --------------------------------------------------------------------

	/**
	 * Add RTE prefs to the CP Menu
	 * 
	 * @param	array $menu The CP menu array
	 * @return	array The updated CP menu array
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
	 * @param	array $results The row_array for the entry
	 * @return	array Modified result array
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
		$this->EE->javascript->compile();
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
	function update_extension( $current = FALSE )
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