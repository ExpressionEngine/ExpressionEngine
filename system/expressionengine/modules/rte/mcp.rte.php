<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Rte_mcp {

	public $name = 'Rte';

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	public function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		// Let's make sure they're allowed...
		$this->_permissions_check();

		// Load it all
		$this->EE->load->helper('form');
		$this->EE->load->library('rte_lib');
		$this->EE->load->model('rte_tool_model');

		// set some properties
		$this->_base_url	= BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=rte';
		$this->_form_base	= 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=rte';
		$this->EE->rte_lib->form_url = $this->_form_base;

		// Delete missing tools
		$this->EE->rte_tool_model->delete_missing_tools();
	}

	// --------------------------------------------------------------------

	/**
	 * Homepage
	 *
	 * @access	public
	 * @return	string The page
	 */
	public function index()
	{
		$this->EE->load->library(array('table','javascript'));
		$this->EE->load->model('rte_toolset_model');

		$toolsets = $this->EE->rte_toolset_model->get_toolset_list();
		
		// prep the Default Toolset dropdown
		$toolset_opts = array();

		foreach ($toolsets as $t)
		{
			if ($t['enabled'] == 'y')
			{
				$toolset_opts[$t['toolset_id']] = $t['name'];
			}
		}

		$vars = array(
			'cp_page_title'				=> lang('rte_module_name'),
			'module_base'				=> $this->_base_url,
			'action'					=> $this->_form_base.AMP.'method=prefs_update',
			'rte_enabled'				=> $this->EE->config->item('rte_enabled'),
			'rte_default_toolset_id'	=> $this->EE->config->item('rte_default_toolset_id'),
			'toolsets'					=> $toolsets,
			'toolset_opts'				=> $toolset_opts,
			'tools'						=> $this->EE->rte_tool_model->get_tool_list(),
			'new_toolset_link'			=> $this->_base_url.AMP.'method=edit_toolset'.AMP.'toolset_id=0'
		);

		// JS
		$this->EE->cp->add_js_script(array(
			'file'		=> 'cp/rte'
		));

		$this->EE->javascript->set_global(array(
			'rte'	=> array(
				'lang' => array(
					'edit_toolset'		=> lang('edit_toolset'),
					'create_toolset'	=> lang('create_new_toolset')
				)
			)
		));

		// CSS
		$this->EE->cp->add_to_head($this->EE->view->head_link('css/rte.css'));

		// return the page
		return $this->EE->load->view('index', $vars, TRUE);
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Update prefs form action
	 *
	 * @access	public
	 * @return	void
	 */
	public function prefs_update()
	{
		// set up the validation
		$this->EE->load->library('form_validation');
		$this->EE->form_validation->set_rules(
			'rte_enabled',
			lang('enabled_question'),
			'required|enum[y,n]'
		);

		$this->EE->form_validation->set_rules(
			'rte_default_toolset_id',
			lang('default_toolset'),
			'required|is_numeric'
		);
		
		if ($this->EE->form_validation->run())
		{
			// update the prefs
			$this->_do_update_prefs();
			$this->EE->session->set_flashdata('message_success', lang('settings_saved'));
		}
		else
		{
			$this->EE->session->set_flashdata('message_failure', lang('settings_not_saved'));
		}
		
		$this->EE->functions->redirect($this->_base_url);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Provides Edit Toolset Screen HTML
	 *
	 * @access	public
	 * @param	int $toolset_id The Toolset ID to be edited (optional)
	 * @return	string The page
	 */
	public function edit_toolset($toolset_id = FALSE)
	{
		return $this->EE->rte_lib->edit_toolset($toolset_id);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Saves a toolset
	 *
	 * @access	public
	 * @return	void
	 */
	public function save_toolset()
	{
		$this->EE->rte_lib->save_toolset();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Enables or disables a toolset
	 *
	 * @access	public
	 * @return	void
	 */
	public function toggle_toolset()
	{
		$this->EE->load->model('rte_toolset_model');
		
		$toolset_id = $this->EE->input->get_post('toolset_id');
		$enabled = $this->EE->input->get_post('enabled') != 'n' ? 'y' :'n';

		if ($this->EE->rte_toolset_model->save_toolset(array('enabled' => $enabled), $toolset_id))
		{
			$this->EE->session->set_flashdata('message_success', lang('toolset_updated'));
		}
		else
		{
			$this->EE->session->set_flashdata('message_failure', lang('toolset_update_failed'));
		}

		$this->EE->functions->redirect($this->_base_url);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Deletes a toolset
	 *
	 * @access	public
	 * @return	void
	 */
	public function delete_toolset()
	{
		$this->EE->load->model('rte_toolset_model');
		
		$toolset_id = $this->EE->input->get_post('toolset_id');
		
		// Delete
		if ($this->EE->rte_toolset_model->delete($toolset_id))
		{
			$this->EE->session->set_flashdata('message_success', lang('toolset_deleted'));
			
			// If the default toolset was deleted
			if ($toolset_id == $this->EE->config->item('rte_default_toolset_id'))
			{
				$toolsets = $this->EE->rte_toolset_model->get_toolset_list();
				
				// Make the new default toolset the first available
				if ( ! empty($toolsets))
				{
					$default_toolset_pref = array(
						'rte_default_toolset_id' => $toolsets[0]['toolset_id']
					);
				}
				// Or set it to zero if there are no toolsets left
				else
				{
					$default_toolset_pref = array(
						'rte_default_toolset_id' => 0
					);
				}
				
				$this->EE->config->update_site_prefs($default_toolset_pref);
			}
		}
		else
		{
			$this->EE->session->set_flashdata('message_failure', lang('toolset_not_deleted'));
		}
		
		$this->EE->functions->redirect($this->_base_url);
	}

	// --------------------------------------------------------------------

	/**
	 * Enables or disables a tool
	 *
	 * @access	public
	 * @return	void
	 */
	public function toggle_tool()
	{
		$this->EE->load->model('rte_tool_model');
		
		$tool_id = $this->EE->input->get_post('tool_id');
		$enabled = $this->EE->input->get_post('enabled') != 'n' ? 'y' :'n';

		if ($this->EE->rte_tool_model->save_tool(array('enabled' => $enabled), $tool_id))
		{
			$this->EE->session->set_flashdata('message_success', lang('tool_updated'));
		}
		else
		{
			$this->EE->session->set_flashdata('message_failure', lang('tool_update_failed'));
		}

		$this->EE->functions->redirect($this->_base_url);
	}

	// --------------------------------------------------------------------

	/**
	 * Actual preference-updating code
	 * 
	 * @access	private
	 * @return	void
	 */
	private function _do_update_prefs()
	{
		// update the config
		$this->EE->config->update_site_prefs(array(
			'rte_enabled'				=> $this->EE->input->get_post('rte_enabled'),
			'rte_default_toolset_id'	=> $this->EE->input->get_post('rte_default_toolset_id')
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Makes sure users can access a given method
	 * 
	 * @access	private
	 * @return	void
	 */
	private function _permissions_check()
	{
		// super admins always can
		$can_access = ($this->EE->session->userdata('group_id') == '1');
		
		if ( ! $can_access)
		{
			// get the group_ids with access
			$result = $this->EE->db->select('module_member_groups.group_id')
				->from('module_member_groups')
				->join('modules', 'modules.module_id = module_member_groups.module_id')
				->where('modules.module_name',$this->name)
				->get();

			if ($result->num_rows())
			{
				foreach ($result->result_array() as $r)
				{
					if ($this->EE->session->userdata('group_id') == $r['group_id'])
					{
						$can_access = TRUE;
						break;
					}
				}
			}
		}
		
		if ( ! $can_access)
		{
			show_error(lang('unauthorized_access'));
		}		
	}
	
}
// END CLASS

/* End of file mcp.rte.php */
/* Location: ./system/expressionengine/modules/rte/mcp.rte.php */