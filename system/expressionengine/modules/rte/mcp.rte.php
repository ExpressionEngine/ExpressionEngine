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
		
		$this->_base_url		= BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=rte';
		$this->_form_base		= 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=rte';
		$this->_myaccount_url	= BASE.AMP.'C=myaccount'.AMP.'M=custom_screen'.AMP.'module=rte'.AMP.'method=myaccount_settings';
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

		$this->EE->cp->add_js_script(array(
			'file'		=> 'cp/rte',
			'plugin'	=> array( 'overlay', 'toolbox.expose' )
		));
		
		$this->EE->cp->set_right_nav(array('create_new_rte_toolset' => $this->_base_url.AMP.'method=edit_toolset'));
		$vars = array(
			'cp_page_title'				=> lang('rte_module_name'),
			'module_base'				=> $this->_base_url,
			'action'					=> $this->_form_base.AMP.'method=prefs_update',
			'rte_enabled'				=> $this->EE->config->item('rte_enabled'),
			'rte_forum_enabled'			=> $this->EE->config->item('rte_forum_enabled'),
			'rte_default_toolset_id'	=> $this->EE->config->item('rte_default_toolset_id'),
			'toolset_opts'				=> $this->EE->rte_toolset_model->get_active(TRUE),
			'toolsets'					=> $this->EE->rte_toolset_model->get_all(),
			'tools'						=> $this->EE->rte_tool_model->get_all()
		);
		
		$this->EE->javascript->compile();
		$this->EE->cp->add_to_head($this->EE->view->head_link('css/rte.css'));
		return $this->EE->load->view('index', $vars, TRUE);
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Update prefs form action
	 *
	 * @access	public
	 */
	public function prefs_update()
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
	 * Provides Edit Toolset Screen HTML
	 *
	 * @access	public
	 */
	public function edit_toolset( $toolset_id=FALSE )
	{
		$this->_permissions_check();
		$this->EE->load->library(array('table','javascript'));
		$this->EE->load->model(array('rte_toolset_model','rte_tool_model'));
		
		# get the toolset
		if ( ! is_numeric( $toolset_id ) ) $toolset_id = $this->EE->input->get_post('rte_toolset_id');	

		# make sure the user can access this toolset
		$failure	= FALSE;
		$is_private	= FALSE;
		$toolset	= FALSE;
		if ( is_numeric( $toolset_id ) )
		{
			# make sure it exists
			if ( ! $this->EE->rte_toolset_model->exists($toolset_id) )
			{
				$failure = lang('toolset_not_found');
			}
			# make sure the user can access it
			elseif ( ! $this->EE->rte_toolset_model->member_can_access($toolset_id) )
			{
				$failure = lang('cannot_edit_toolset');
			}
			# bow out if the user can’t
			if ( !! $failure )
			{
				$this->EE->session->set_flashdata('message_failure', $failure);
				$this->EE->functions->redirect($this->_base_url);
			}

			# grab the toolset
			$toolset	= $this->EE->rte_toolset_model->get($toolset_id);
			$is_private	= ( $toolset->member_id != 0 );
		}
		else
		{
			$is_new		= TRUE;
			$is_private = $this->EE->input->get_post('private');
			$is_private	= ( $is_private == 'true' );
		}
		
		# JS stuff
		$this->EE->cp->add_js_script(array(
			'ui' 	=> 'sortable',
			'file'	=> 'cp/rte'
		));
		
		# get the tools lists (can only include active tools)
		$available_tools	= $this->EE->rte_tool_model->get_available(TRUE);
		$toolset_tool_ids	= $this->EE->rte_toolset_model->get_tools($toolset_id);
		$unused_tools = $toolset_tools = array();
		foreach ( $available_tools as $tool_id => $tool_name )
		{
			$tool_index = array_search( $tool_id, $toolset_tool_ids );
			if ( $tool_index !== FALSE )
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
		
		$this->EE->cp->set_breadcrumb( $this->_base_url, lang('rte_module_name') );
		$title = $is_private ? lang('define_my_toolset') : lang('define_toolset');
		$vars = array(
			'cp_page_title'		=> $title,
			'module_base'		=> $this->_base_url,
			'action'			=> $this->_form_base.AMP.'method=save_toolset'.( !! $toolset_id ? AMP.'rte_toolset_id='.$toolset_id : '' ),
			'is_private'		=> $is_private,
			'toolset_name'		=> ( ! $toolset || $is_private ? '' : $toolset->name ),
			'available_tools'	=> $available_tools,
			'unused_tools'		=> $unused_tools,
			'toolset_tools'		=> $toolset_tools
		);
		
		$this->EE->javascript->compile();
		$this->EE->cp->add_to_head($this->EE->view->head_link('css/rte.css'));
		return $this->EE->load->view('edit_toolset', $vars, TRUE);
		
	}

	// --------------------------------------------------------------------
	
	/**
	 * Update prefs form action
	 *
	 * @access	public
	 */
	public function save_toolset()
	{
		$this->_permissions_check();
		
		$toolset_id = $this->EE->input->get_post('rte_toolset_id');
		$toolset	= array(
			'name'		=> $this->EE->input->get_post('rte_toolset_name'),
			'rte_tools' => $this->EE->input->get_post('rte_selected_tools'),
			'member_id'	=> ( $this->EE->input->get_post('private') == 'true' ? $this->EE->session->userdata('member_id') : 0 )
		);

		if ( $toolset_id )
		{
			$this->_update_toolset(
				$this->EE->input->get_post('rte_toolset_id'),
				$toolset,
				lang('toolset_updated'),
				lang('toolset_update_failed')
			);
		}
		else
		{
			$this->_update_toolset(
				FALSE,
				$toolset,
				lang('toolset_saved'),
				lang('toolset_not_saved')
			);
		}
		
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
			$this->EE->input->get_post('rte_toolset_id'),
			array( 'enabled' => 'y' ),
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
			$this->EE->input->get_post('rte_toolset_id'),
			array( 'enabled' => 'n' ),
			lang('toolset_disabled'),
			lang('toolset_update_failed')
		);
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
			$this->EE->input->get_post('rte_tool_id'),
			array( 'enabled' => 'y' ),
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
			$this->EE->input->get_post('rte_tool_id'),
			array( 'enabled' => 'n' ),
			lang('tool_disabled'),
			lang('tool_update_failed')
		);
	}

	// --------------------------------------------------------------------
	
	/**
	 * MyAccount Page
	 *
	 * @access	public
	 */
	public function myaccount_settings( $vars )
	{
		$this->EE->load->library('javascript');
		$this->EE->load->model('rte_toolset_model');
		
		$prefs = $this->EE->db
					->select( array( 'rte_enabled','rte_toolset_id' ) )
					->get_where(
						'members',
						array( 'member_id'=>$this->EE->session->userdata('member_id') )
					  )
					->row();
		
		// get the toolset options
		$toolset_opts = $this->EE->rte_toolset_model->get_member_options();
		foreach ( $toolset_opts as $id => $name )
		{
			$toolset_opts[$id] = lang($name);
		}
		
		$vars = array(
			'cp_page_title'			=> lang('rte_prefs'),
			'action'				=> $this->_form_base.AMP.'method=myaccount_settings_update',
			'rte_enabled'			=> $prefs->rte_enabled,
			'rte_toolset_id_opts'	=> $toolset_opts,
			'rte_toolset_id'		=> $prefs->rte_toolset_id
		);
		
		# JS stuff
		$this->EE->javascript->set_global('rte.toolset_builder_url', $this->_base_url.AMP.'method=edit_toolset'.AMP.'private=true');
		$this->EE->javascript->set_global('rte.custom_toolset_text', lang('my_custom_toolset') );
		$this->EE->cp->add_js_script(array(
			'file'	 => 'cp/rte',
			'plugin' => array( 'overlay', 'toolbox.expose' )
		));
		$this->EE->cp->add_to_head($this->EE->view->head_link('css/rte.css'));
		return $this->EE->load->view('myaccount_settings', $vars, TRUE);
	}

	// --------------------------------------------------------------------
	
	/**
	 * MyAccount RTE settings form action
	 *
	 * @access	public
	 */
	public function myaccount_settings_update()
	{
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
		
		if ( $this->EE->form_validation->run() )
		{
			// update the prefs
			$this->EE->db
				->where( 'member_id', $this->EE->session->userdata('member_id') )
				->update(
					'members',
					array(
						'rte_enabled'		=> $this->EE->input->get_post('rte_enabled'),
						'rte_toolset_id'	=> $this->EE->input->get_post('rte_toolset_id')
					)
				  );
			
			// flash
			$this->EE->session->set_flashdata('message_success', lang('preferences_saved'));
		}
		// Fail!
		else
		{
			// flash
			$this->EE->session->set_flashdata('message_failure', lang('preferences_not_saved'));
		}

		// buh-bye
		$this->EE->functions->redirect($this->_myaccount_url);

	}

	// --------------------------------------------------------------------
	
	/**
	 * Build the toolset JS
	 *
	 * @access	public
	 */
	public function build_toolset_js()
	{
		# setup the framework
		$js = '
			$(".rte").each(function(){
				var
				$field	= $(this),
				$parent	= $field.parent(),

				// set up the editor
				$editor	= WysiHat.Editor.attach($field),

				// establish the toolbar
				toolbar	= new WysiHat.Toolbar();
				
				toolbar.initialize($editor);
				
		';
		
		# load the tools
		$this->EE->load->model(array('rte_toolset_model','rte_tool_model'));
		$tools = $this->EE->rte_toolset_model->get_member_toolset_tools();
		foreach ( $tools as $tool_id )
		{
			$js .= $this->EE->rte_tool_model->get_tool_js($tool_id);
		}
		
		$js .= '

			});
		';

		return array($js);
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
	 * Update prefs form action
	 *
	 * @access	private
	 */
	private function _update_toolset( $toolset_id=FALSE, $change=array(), $success_msg, $fail_msg )
	{
		$this->EE->load->model('rte_toolset_model');
		
		$is_members = !! ( $this->EE->input->get_post('private') == 'true' );
		
		if ( $this->EE->rte_toolset_model->save( $change, $toolset_id ) )
		{
			if ( ! $toolset_id &&
				 $is_members )
			{
				$toolset_id = $this->EE->db->insert_id();
				$this->EE->db
					->where( array( 'member_id' => $this->EE->session->userdata('member_id') ) )
					->update( 'members', array( 'rte_toolset_id' => $toolset_id ) );
			}
			
			$this->EE->session->set_flashdata('message_success', $success_msg);
		}
		// Fail!
		else
		{
			$this->EE->session->set_flashdata('message_failure', $fail_msg);
		}

		// buh-bye
		$this->EE->functions->redirect(
			( $is_members ? $this->_myaccount_url : $this->_base_url )
		);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Update prefs form action
	 *
	 * @access	private
	 */
	private function _update_tool( $tool_id=0, $change=array(), $success_msg, $fail_msg )
	{
		$this->EE->load->model('rte_tool_model');
		
		if ( $this->EE->rte_tool_model->save( $change, $tool_id ) )
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
	
}
// END CLASS

/* End of file mcp.rte.php */
/* Location: ./system/expressionengine/modules/rte/mcp.rte.php */