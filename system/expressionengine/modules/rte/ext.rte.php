<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.5
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Rich Text Editor Module
 *
 * @package		ExpressionEngine
 * @subpackage	Extensions
 * @category	Extensions
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Rte_ext {

	var $name			= 'Rich Text Editor';
	var $version		= '1.0';
	var $settings_exist	= 'n';
	var $docs_url		= 'http://ellislab.com/expressionengine/user-guide/modules/rte/index.html';
	var $required_by	= array('module');

	private $EE;
	private $module = 'rte';
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();

		$this->EE->load->library('rte_lib');
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
	 * My Account Preferences
	 *
	 * @access	public
	 * @return	string The page contents
	 */
	public function myaccount_settings($member_id)
	{
		$this->EE->load->library('javascript');
		$this->EE->load->model('rte_toolset_model');

		// get member preferences
		$prefs = $this->EE->rte_toolset_model->get_member_prefs($member_id);

		// get available toolsets
		$toolsets = $this->EE->rte_toolset_model->get_member_toolsets($member_id);

		// assume we don't have a custom toolset to begin with
		$my_toolset_id = 0;
		
		$options = array();
		
		// build the dropdown
		foreach ($toolsets as $t)
		{
			if ($t['member_id'] == $member_id)
			{
				// we have a custom toolset; grab its id
				$my_toolset_id = $t['toolset_id'];
				continue;
			}

			$options[$t['toolset_id']] = $t['name'];
		}

		// insert our custom toolset at the beginning of the list
		if ($member_id == $this->EE->session->userdata('member_id'))
		{
			$options = array($my_toolset_id => lang('my_toolset')) + $options;
		}

		// Check the rte_toolset_id, if it's not defined, opt for the 
		// install default
		$selected_toolset_id = ($prefs['rte_toolset_id'] == 0) ?  
			$this->EE->config->item('rte_default_toolset_id'):
			$prefs['rte_toolset_id'];
		
		// setup the page
		$vars = array(
			'cp_page_title'		=> lang('rte_prefs'),
			'rte_enabled'		=> $prefs['rte_enabled'],
			'toolset_id'		=> $selected_toolset_id,
			'toolset_id_opts'	=> $options
		);
		
		// JS stuff
		$this->EE->javascript->set_global(array(
			'rte'	=> array(
				'lang' => array(
					'edit_my_toolset'	=> lang('edit_my_toolset')
				),
				'url'	=> array(
					'edit_my_toolset' 	=> BASE.AMP.'C=myaccount'.AMP.'M=custom_action'.AMP.'extension=rte'.AMP.'method=edit_toolset'.AMP.'private=true'.AMP.'toolset_id='.$my_toolset_id,
				),
				'my_toolset_id'			=> $my_toolset_id
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
	
	// -------------------------------------------------------------------------

	/**
	 * MyAccount RTE settings form action
	 *
	 * @access	public
	 * @return	void
	 */
	public function myaccount_settings_save($member_id)
	{
		// set up the validation
		$this->EE->load->library('form_validation');
		$this->EE->lang->loadfile('rte');
		$this->EE->form_validation->set_rules(
			'rte_enabled',
			lang('enabled_question'),
			'required|enum[y,n]'
		);
		$this->EE->form_validation->set_rules(
			'toolset_id',
			lang('default_toolset'),
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
					'rte_toolset_id'	=> $this->EE->input->get_post('toolset_id')
				),
				array('member_id' => $member_id)
			);
			
			$this->EE->session->set_flashdata('message_success', lang('settings_saved'));
		}
		else
		{
			$this->EE->session->set_flashdata('message_failure', lang('settings_not_saved'));
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Passthrough to the library's edit_toolset() method
	 * @param	int $toolset_id The Toolset ID to be edited (optional)
	 * @return	string The page
	 */
	public function edit_toolset()
	{
		$this->EE->rte_lib->form_url = 'C=myaccount'.AMP.'M=custom_action'.AMP.'extension=rte'.AMP.'method=save_toolset';
		return $this->EE->rte_lib->edit_toolset();
	}

	// --------------------------------------------------------------------

	/**
	 * Passthrough to the library's save_toolset method
	 */
	public function save_toolset()
	{
		$this->EE->rte_lib->save_toolset();
	}

	// --------------------------------------------------------------------

	/**
	 * Add RTE prefs to the CP Menu
	 * 
	 * @param	array $menu The CP menu array
	 * @return	array The updated CP menu array
	 */
	function cp_menu_array($menu)
	{
		if ($this->EE->extensions->last_call !== FALSE)
		{
			$menu = $this->EE->extensions->last_call;
		}

		// If this isn't a Super Admin, let's check to see if they can modify 
		// the RTE module
		if ($this->EE->session->userdata('group_id') != 1)
		{
			$access = (bool) $this->EE->db->select('COUNT(m.module_id) AS count')
				->from('modules m')
				->join('module_member_groups mmg', 'm.module_id = mmg.module_id')
				->where(array(
					'mmg.group_id' 	=> $this->EE->session->userdata('group_id'),
					'm.module_name' => ucfirst($this->module)
				))
				->get()
				->row('count');

			$has_access = $access 
				AND $this->EE->cp->allowed_group('can_access_addons') 
				AND $this->EE->cp->allowed_group('can_access_modules');
		}
		
		if ($this->EE->session->userdata('group_id') == 1 OR $has_access)
		{
			$this->EE->lang->loadfile($this->module);
			$menu['admin']['admin_content']['rte_settings'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module;	
		}
		
		return $menu;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Add RTE JS to the Publish/Edit page
	 * 
	 * @param	array $results The row_array for the entry
	 * @return	array Modified result array
	 */
	function publish_form_entry_data($results)
	{
		// Build toolset JS and include CP-only tools
		$this->EE->javascript->output(
			$this->EE->rte_lib->build_js(0, '.WysiHat-field', NULL, TRUE)
		);
		
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
	}

	// --------------------------------------------------------------------

	/**
	 * Update Extension
	 * This extension is automatically updated with the Rich Text Editor module
	 */
	function update_extension( $current = FALSE )
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 * This extension is automatically disabled with the Rich Text Editor module
	 */
	function disable_extension()
	{
		return TRUE;
	}
	
		// --------------------------------------------------------------------

	/**
	 * Uninstall Extension
	 * This extension is automatically uninstalled with the Rich Text Editor module
	 */
	function uninstall_extension()
	{
		return TRUE;
	}
	
}

/* End of file ext.rte.php */
/* Location: ./system/expressionengine/modules/rte/ext.rte.php */