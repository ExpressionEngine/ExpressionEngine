<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Rich Text Editor Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		Aaron Gustafson
 * @link		http://easy-designs.net
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

		$this->EE->load->helper('form');
		
		$this->_base_url	= BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=rte';
		$this->_form_base	= 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=rte';

		$this->_load_tools_into_db();
	}

	// --------------------------------------------------------------------

	/**
	 * Homepage
	 *
	 * @access	public
	 * @return	string
	 */
	public function index()
	{
		$this->_permissions_check();
		
		$this->EE->load->library(array('table','javascript'));
		
		$this->EE->load->model(array('rte_toolset_model','rte_tool_model'));

		$this->EE->cp->set_right_nav(array('create_new_rte_toolset' => $this->_base_url.AMP.'method=create_toolset'));
		
		$vars = array(
			'cp_page_title'				=> $this->EE->lang->line('rte_module_name'),
			'module_base'				=> $this->_base_url,
			'form_base'					=> $this->_form_base,
			'rte_enabled'				=> $this->EE->config->item('rte_enabled'),
			'rte_forum_enabled'			=> $this->EE->config->item('rte_forum_enabled'),
			'rte_default_toolset_id'	=> $this->EE->config->item('rte_default_toolset_id'),
			'toolset_opts'				=> $this->EE->rte_toolset_model->get_active(TRUE),
			'toolsets'					=> $this->EE->rte_toolset_model->get_all(),
			'tools'						=> $this->EE->rte_tool_model->get_all()
		);
		
		return $this->EE->load->view('index', $vars, TRUE);
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Update prefs form action
	 *
	 * @access	public
	 */
	public function update_prefs()
	{
		$this->_permissions_check();
		
		$this->EE->load->library('form_validation');
		
		$this->EE->form_validation->set_rules(
			'rte_enabled',
			lang('enabled_question'),
			'required|enum[y,n]'
		);
		$this->EE->form_validation->set_rules(
			'rte_forum_enabled',
			lang('forum_enabled_question'),
			'required|enum[y,n]'
		);
		$this->EE->form_validation->set_rules(
			'rte_default_toolset_id',
			lang('choose_default_toolset'),
			'required|is_numeric'
		);
		
		if ( $this->EE->form_validation->run() )
		{
			// update the prefs
			$this->_do_update_prefs();
			
			// flash
			$this->EE->session->set_flashdata('message_success', lang('settings_saved'));
		}
		// Fail!
		else
		{
			// flash
			$this->EE->session->set_flashdata('message_failure', lang('settings_not_saved'));
		}

		// buh-bye
		$this->EE->functions->redirect($this->_base_url);

	}

	// --------------------------------------------------------------------
	
	/**
	 * Update prefs form action
	 *
	 * @access	public
	 */
	public function enable_toolset()
	{
		$this->_permissions_check();
		
		$this->_update_toolset(
			array( 'enabled' => 'y' ),
			$this->EE->input->get_post('rte_toolset_id'),
			lang('toolset_enabled'),
			lang('toolset_update_failed')
		);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Update prefs form action
	 *
	 * @access	public
	 */
	public function disable_toolset()
	{
		$this->_permissions_check();
		
		$this->_update_toolset(
			array( 'enabled' => 'n' ),
			$this->EE->input->get_post('rte_toolset_id'),
			lang('toolset_disabled'),
			lang('toolset_update_failed')
		);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Update prefs form action
	 *
	 * @access	private
	 */
	private function _update_toolset( $change=array(), $toolset_id=0, $success_msg, $fail_msg )
	{
		$this->EE->db->query(
			$this->EE->db->update_string(
				'rte_toolsets',
				$change,
				array( 'rte_toolset_id' => $toolset_id )
			)
		);
		
		if ( $this->EE->db->affected_rows() )
		{
			$this->EE->session->set_flashdata('message_success', $success_msg);
		}
		// Fail!
		else
		{
			$this->EE->session->set_flashdata('message_failure', $fail_msg);
		}

		// buh-bye
		$this->EE->functions->redirect($this->_base_url);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Update prefs form action
	 *
	 * @access	public
	 */
	public function enable_tool()
	{
		$this->_permissions_check();
		
		$this->_update_tool(
			array( 'enabled' => 'y' ),
			$this->EE->input->get_post('rte_tool_id'),
			lang('tool_enabled'),
			lang('tool_update_failed')
		);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Update prefs form action
	 *
	 * @access	public
	 */
	public function disable_tool()
	{
		$this->_permissions_check();
		
		$this->_update_tool(
			array( 'enabled' => 'n' ),
			$this->EE->input->get_post('rte_tool_id'),
			lang('tool_disabled'),
			lang('tool_update_failed')
		);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Update prefs form action
	 *
	 * @access	private
	 */
	private function _update_tool( $change=array(), $tool_id=0, $success_msg, $fail_msg )
	{
		$this->EE->db->query(
			$this->EE->db->update_string(
				'rte_tools',
				$change,
				array( 'rte_tool_id' => $tool_id )
			)
		);
		
		if ( $this->EE->db->affected_rows() )
		{
			$this->EE->session->set_flashdata('message_success', $success_msg);
		}
		// Fail!
		else
		{
			$this->EE->session->set_flashdata('message_failure', $fail_msg);
		}

		// buh-bye
		$this->EE->functions->redirect($this->_base_url);
	}

	// --------------------------------------------------------------------

	/**
	 * Actual preference-updating code
	 */
	private function _do_update_prefs()
	{
		// update the config
		$this->EE->config->_update_config(
			array(
				'rte_enabled'				=> $this->EE->input->get_post('rte_enabled'),
				'rte_forum_enabled'			=> $this->EE->input->get_post('rte_forum_enabled'),
				'rte_default_toolset_id'	=> $this->EE->input->get_post('rte_default_toolset_id')
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Makes sure users can access
	 */
	private function _permissions_check()
	{
		$can_access = ( $this->EE->session->userdata('group_id') == '1' );
		
		if ( ! $can_access )
		{
			# get the group_ids with access
			$result = $this->EE->db
						->select('module_member_groups.group_id')
						->from('module_member_groups')
						->join('modules', 'modules.module_id = module_member_groups.module_id')
						->where('modules.module_name',$this->name)
						->get();
			if ( $result->num_rows() )
			{
				foreach ( $result->result_array() as $r )
				{
					if ( $this->EE->session->userdata('group_id') == $r['group_id'] )
					{
						$can_access = TRUE;
						break;
					}
				}
			}
		}
		
		if ( ! $can_access )
		{
			show_error(lang('unauthorized_access'));
		}		
	}
	
	// --------------------------------------------------------------------

	/**
	 * Makes sure the DB has the latest list of tools
	 */
	private function _load_tools_into_db()
	{
		$this->EE->load->library('addons');
		
		$files		= $this->EE->addons->get_files('rte_tools');
		$installed	= $this->EE->addons->get_installed('rte_tools');

		foreach ( $files as $package => $details )
		{
			if ( ! isset($installed[$package]) )
			{
				// make a record of the add-on in the DB
				$this->EE->db->query(
					$this->EE->db->insert_string(
						'rte_tools',
						array(
							'name'		=> $details['name'],
							'class'		=> $details['class'],
							'enabled'	=> 'y'
						)
					)
				);
			}
		}
	}
	
}
// END CLASS

/* End of file mcp.rte.php */
/* Location: ./system/expressionengine/modules/rte/mcp.rte.php */