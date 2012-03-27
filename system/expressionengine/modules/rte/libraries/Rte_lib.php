<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine RTE Module Library 
 *
 * @package		ExpressionEngine
 * @subpackage	Libraries
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://expressionengine.com
 */

class Rte_lib
{
	public $module_url = '';
	public $cancel_url = '';
	public $form_url = '';

	public function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->lang->loadfile('rte');
		$this->module_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=rte';

		if (AJAX_REQUEST)
		{
			// Turn off the profiler, everything is in a modal
			$this->EE->output->enable_profiler(FALSE);
		}
	}

	// -------------------------------------------------------------------------

	/**
	 * Provides Edit Toolset Screen HTML
	 *
	 * @access	public
	 * @param	int $toolset_id The Toolset ID to be edited (optional)
	 * @return	string The page
	 */
	public function edit_toolset($toolset_id = FALSE)
	{
		$this->EE->load->library(array('table','javascript'));
		$this->EE->load->model(array('rte_toolset_model','rte_tool_model'));
		
		// get the toolset
		if ( ! is_numeric($toolset_id)) 
		{
			$toolset_id = $this->EE->input->get_post('rte_toolset_id');
		}

		// make sure the user can access this toolset
		$failure	= FALSE;
		$is_private	= FALSE;
		$toolset	= FALSE;
		$is_new		= FALSE;

		if (is_numeric($toolset_id))
		{
			// make sure the user can access it
			if ( ! $this->EE->rte_toolset_model->member_can_access($toolset_id))
			{
				$this->EE->session->set_flashdata('message_failure', lang('cannot_edit_toolset'));
				$this->EE->functions->redirect($this->module_url);
			}

			// grab the toolset
			$toolset	= $this->EE->rte_toolset_model->get($toolset_id);
			$is_private	= ($toolset->member_id != 0);
		}
		else
		{
			$is_new		= TRUE;
			$is_private = ($this->EE->input->get_post('private') == 'true');
		}
		
		// JS stuff
		$this->EE->cp->add_js_script(array(
			'ui' 	=> 'sortable',
			'file'	=> 'cp/rte'
		));
		
		// get the tools lists (can only include active tools)
		$unused_tools 		= $toolset_tools = array();
		$available_tools	= $this->EE->rte_tool_model->get_available(TRUE);
		$toolset_tool_ids	= array();

		if ( ! $is_new && $toolset = $this->EE->rte_toolset_model->get($toolset_id))
		{
			$toolset_tool_ids = $toolset->rte_tools;
		}

		foreach ($available_tools as $tool_id => $tool_name)
		{
			$tool_index = array_search($tool_id, $toolset_tool_ids);

			if ($tool_index !== FALSE)
			{
				$toolset_tools[$tool_index] = $tool_id;
			}
			else
			{
				$unused_tools[] = $tool_id;
			}
		}

		// ensure the proper order
		ksort( $toolset_tools, SORT_NUMERIC );
		sort( $unused_tools );
		
		// set up the page
		$this->EE->cp->set_breadcrumb($this->module_url, lang('rte_module_name'));
		$title = $is_private ? lang('define_my_toolset') : lang('define_toolset');
		$vars = array(
			'cp_page_title'		=> $title,
			'module_base'		=> $this->cancel_url,
			'action'			=> $this->form_url.AMP.'method=save_toolset'.( !! $toolset_id ? AMP.'rte_toolset_id='.$toolset_id : ''),
			'is_private'		=> $is_private,
			'toolset_name'		=> ( ! $toolset || $is_private ? '' : $toolset->name ),
			'available_tools'	=> $available_tools,
			'unused_tools'		=> $unused_tools,
			'toolset_tools'		=> $toolset_tools
		);
		
		// JS
		$this->EE->javascript->set_global(array(
			'rte'	=> array(
				'toolset_modal.title'		=> $title,
				'validate_toolset_name_url'	=> BASE.AMP.'C=myaccount'.AMP.'M=custom_action'.AMP.'extension=rte'.AMP.'method=validate_toolset_name',
				'name_required'				=> lang('name_required')
			)
		));
		$this->EE->javascript->compile();
		
		// CSS
		$this->EE->cp->add_to_head($this->EE->view->head_link('css/rte.css'));
		
		// page
		return $this->EE->load->view('edit_toolset', $vars, TRUE);
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
		$this->EE->load->model('rte_toolset_model');
		
		// get the toolset
		$toolset_id = $this->EE->input->get_post('rte_toolset_id');
		$toolset	= array(
			'name'		=> $this->EE->input->get_post('rte_toolset_name'),
			'rte_tools' => $this->EE->input->get_post('rte_selected_tools'),
			'member_id'	=> ($this->EE->input->get_post('private') == 'true' ? $this->EE->session->userdata('member_id') : 0)
		);
		
		// is this an individual’s private toolset?
		$is_members = ($this->EE->input->get_post('private') == 'true');

		// did an empty name sneak through?
		if (empty($toolset['name']))
		{
			$this->EE->output->send_ajax_response(array(
				'error' => lang('name_required')
			));
		}
				
		// Updating? Make sure the toolset exists and they aren't trying any
		// funny business...
		if ($toolset_id)
		{
			$orig = $this->EE->rte_toolset_model->get($toolset_id);
			
			if ( ! $orig || $is_members && $orig->member_id != $this->EE->session->userdata('member_id'))
			{
				$this->EE->output->send_ajax_response(array(
					'error' => lang('toolset_update_failed')
				));
			}
		}
		
		// save it
		if ($this->EE->rte_toolset_model->save_toolset($toolset, $toolset_id))
		{
			// if it’s new, get the ID
			if ( ! $toolset_id)
			{
				$toolset_id = $this->EE->db->insert_id();
			}
			
			// update the member profile
			if ($is_members && $toolset_id)
			{
				$this->EE->db
					->where( array( 'member_id' => $this->EE->session->userdata('member_id') ) )
					->update( 'members', array( 'rte_toolset_id' => $toolset_id ) );
			}
			
			$this->EE->output->send_ajax_response(array(
				'success' 		=> lang('toolset_updated'),
				'force_refresh' => ! $is_members
			));
		}
		else
		{
			$this->EE->output->send_ajax_response(array(
				'error' => lang('toolset_update_failed')
			));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Validates a toolset name for existance and uniqueness
	 *
	 * @access	public
	 * @return	mixed JSON or Boolean for validity
	 */
	public function validate_toolset_name()
	{
		$this->EE->load->library('javascript');
		$this->EE->load->model('rte_toolset_model');
		
		$valid = $this->EE->rte_toolset_model->check_name(
			$this->EE->input->get_post('name'),
			$this->EE->input->get_post('rte_toolset_id')
		);

		if ($this->EE->input->is_ajax_request())
		{
			$this->EE->output->send_ajax_response(array(
				'valid' => $valid
			));
		}
		else
		{
			return $valid;
		}
	}
}

/* End of file rte_lib.php */
/* Location: ./system/expressionengine/modules/safecracker/libraries/rte_lib.php */