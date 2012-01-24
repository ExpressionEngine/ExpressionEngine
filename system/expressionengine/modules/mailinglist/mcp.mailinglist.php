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
 * ExpressionEngine Mailing List Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Mailinglist_mcp {

	var $perpage = 100;

	/**
	  *  Constructor
	  */
	function Mailinglist_mcp($switch = TRUE)
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		$this->EE->cp->set_right_nav(array(
			'ml_create_new' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=edit_mailing_list', 
			'mailinglist_preferences' => BASE.AMP.'C=admin_system'.AMP.'M=mailing_list_preferences'
		));
	
	}

	// --------------------------------------------------------------------

	/**
	  *  Mailinglist Home Page
	  */
	function index()
	{
		$this->EE->load->library('table');
		$this->EE->load->library('javascript');
		$this->EE->load->helper('form');

		$this->EE->cp->add_js_script(array('fp_module' => 'mailinglist'));

		$vars['cp_page_title'] = $this->EE->lang->line('ml_mailinglist');

		$this->EE->load->model('mailinglist_model');
		
		$mailinglists = $this->EE->mailinglist_model->get_mailinglists();

		$vars['mailinglists'] = array();
		$vars['list_id_options'] = array();

		foreach ($mailinglists->result() as $list)
		{
			$vars['mailinglists'][$list->list_id]['id'] = $list->list_id;

			$this->EE->db->where('list_id', $list->list_id);
			$vars['mailinglists'][$list->list_id]['count'] = $this->EE->db->count_all_results('mailing_list');

			$vars['mailinglists'][$list->list_id]['shortname'] = $list->list_name;

			$vars['mailinglists'][$list->list_id]['name'] = $list->list_title;
			$vars['mailinglists'][$list->list_id]['toggle'] = array(
																			'name'		=> 'toggle[]',
																			'id'		=> 'delete_box_'.$list->list_id,
																			'value'		=> $list->list_id,
																			'class'		=>'toggle'
			    														);
		
			$vars['list_id_options'][$list->list_id] = $list->list_title;
		}

		$this->EE->javascript->compile();

		return $this->EE->load->view('index', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Mailing List Default Template Data
	  */
	function default_template_data()
	{
return <<<EOF
{message_text}

To remove your email from the "{mailing_list}" mailing list, click here:
{if html_email}<a href="{unsubscribe_url}">{unsubscribe_url}</a>{/if}
{if plain_email}{unsubscribe_url}{/if}
EOF;
	}

	// --------------------------------------------------------------------

	/**
	  *  Create/Edit Mailing List
	  */
	function edit_mailing_list()
	{
		$this->EE->load->library('javascript');
		$this->EE->load->library('form_validation');
		$this->EE->load->helper('form');
		$this->EE->load->model('mailinglist_model');
		
		$this->EE->form_validation->set_rules('list_name', 'lang:ml_mailinglist_short_name', 'required|alpha_dash|callback__unique_short_name');
		$this->EE->form_validation->set_rules('list_title', 'lang:ml_mailinglist_long_name', 'required');
		$this->EE->form_validation->set_message('alpha_dash', $this->EE->lang->line('ml_invalid_short_name'));
		$this->EE->form_validation->set_error_delimiters('<span class="notice">', '</span>');

		$list_id			= 0;
		$vars['list_name']	= '';
		$vars['list_title']	= '';

		if ($this->EE->input->get_post('list_id') != 0)
		{
			$query = $this->EE->mailinglist_model->get_list_by_id(
													$this->EE->input->get_post('list_id'),
													'list_title, list_template, list_id, list_name');			

			if ($query->num_rows() == 1)
			{
				$list_id = $query->row('list_id');
				$vars['list_title'] = $query->row('list_title');
				$vars['list_name'] = $query->row('list_name');
				
				$this->EE->form_validation->set_old_value('list_id', $list_id);
			}
		}

		if ($this->EE->form_validation->run() === FALSE)
		{
			$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist', $this->EE->lang->line('ml_mailinglist'));

			$vars['cp_page_title'] = ($list_id == 0) ? $this->EE->lang->line('ml_create_new') : $this->EE->lang->line('ml_edit_list');
			$vars['form_action'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=edit_mailing_list';
			$vars['form_hidden']['list_id'] = $list_id;
			$vars['button_label'] = ($list_id == 0) ? $this->EE->lang->line('ml_create_new') : $this->EE->lang->line('update');

			$this->EE->javascript->compile();

			return $this->EE->load->view('update', $vars, TRUE);
		}
		else
		{
			$data = array(
							'list_name'		=> $this->EE->input->post('list_name'),
							'list_title'	=> $this->EE->input->post('list_title'),
							'list_template'	=> addslashes($this->default_template_data())
						);

			$this->EE->mailinglist_model->update_mailinglist($list_id, $data);
			
			$message = ($list_id == FALSE) ? $this->EE->lang->line('ml_mailinglist_created') : $this->EE->lang->line('ml_mailinglist_updated');
			
			$this->EE->session->set_flashdata('message_success', $message);
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  Mailing List Short Name Callback
	  */
	function _unique_short_name($str)
	{
		$this->EE->load->model('mailinglist_model');

		if ( ! $this->EE->mailinglist_model->unique_shortname($this->EE->form_validation->old_value('list_id'), $str))
		{
			$this->EE->form_validation->set_message('_unique_short_name', $this->EE->lang->line('ml_short_name_taken'));
			return FALSE;
		}
		
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	  *  Mailing List Template
	  */
	function edit_template()
	{
		$this->EE->load->helper('form');

		if ( ! $list_id = $this->EE->input->get_post('list_id'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->EE->load->model('mailinglist_model');
		
		$list = $this->EE->mailinglist_model->get_list_by_id($list_id, 'list_title, list_template');

		if ($list->num_rows() == 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist', $this->EE->lang->line('ml_mailinglist'));

		$vars['cp_page_title'] = $this->EE->lang->line('mailinglist_template');
		$vars['form_hidden']['list_id'] = $list_id;
		$vars['list_title'] = $list->row('list_title');
		$vars['template_data'] = $list->row('list_template');

		return $this->EE->load->view('edit_template', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Update Mailing List Template
	  */
	function update_template()
	{
		if ( ! $list_id = $this->EE->input->get_post('list_id'))
		{
			show_error($this->EE->lang->line('ml_no_list_id'));
		}

		if ( ! isset($_POST['template_data']))
		{
			return FALSE;
		}
		
		$this->EE->load->model('mailinglist_model');
		
		$this->EE->mailinglist_model->update_template($list_id, $this->EE->input->post('template_data'));
		
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('template_updated'));
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Mailing List Confirm
	  */
	function delete_mailinglist_confirm()
	{
		if ( ! $this->EE->input->post('toggle'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
		}

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist', $this->EE->lang->line('ml_mailinglist'));

		$vars['cp_page_title'] = $this->EE->lang->line('ml_delete_mailinglist');

		$vars['question_key'] = 'ml_delete_list_question';
		$vars['message'] = $this->EE->lang->line('ml_all_data_nuked'); // an extra warning message

		$vars['form_action'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=delete_mailinglists';

		$this->EE->load->helper('form');

		foreach ($_POST['toggle'] as $key => $val)
		{
			$vars['damned'][] = $val;
		}
		
		$this->EE->load->model('mailinglist_model');
		
		$query = $this->EE->mailinglist_model->get_list_by_id($_POST['toggle'], 'list_title');

		$vars['list_names'] = array();

		foreach ($query->result() as $row)
		{
			$vars['list_names'][] = $row->list_title;
		}

		$this->EE->javascript->compile();

		return $this->EE->load->view('delete_confirm', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Mailing List(s)
	  */
	function delete_mailinglists()
	{
		if ($this->EE->input->post('delete') == '')
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
		}

		$this->EE->load->model('mailinglist_model');
		
		$message = $this->EE->mailinglist_model->delete_mailinglist($_POST['delete']);

		$this->EE->session->set_flashdata('message_success', $message);
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
	}

	// --------------------------------------------------------------------

	/**
	  *  Subscribe
	  */
	function subscribe()
	{
		if ($this->EE->input->post('addresses') == '')
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('ml_missing_email'));
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
		}

		$this->EE->load->helper(array('email','string'));

		//  Fetch existing addresses
		$subscribe = ($this->EE->input->get_post('sub_action') == 'unsubscribe') ? FALSE : TRUE;

		$list_id = $this->EE->input->get_post('list_id');
		
		$this->EE->load->model('mailinglist_model');
		
		$query = $this->EE->mailinglist_model->get_emails_by_list($list_id, 'email');

		$current = array();

		if ($query->num_rows() == 0)
		{
			if ($subscribe == FALSE)
			{
				$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
			}
		}
		else
		{
			foreach ($query->result() as $row)
			{
				$current[] = $row->email;
			}
		}

		//  Clean up submitted addresses
		$email	= trim($_POST['addresses']);
		$email	= preg_replace("/[,|\|]/", "", $email);
		$email	= str_replace(array("\r\n", "\r", "\n"), " ", $email);
		$email	= preg_replace("/\t+/", " ", $email);
		$email	= preg_replace("/\s+/", " ", $email);
		$emails	= array_unique(explode(" ", $email));

		//  Insert new addresses
		$vars['good_email'] = 0;
		$vars['dup_email']	= 0;

		$vars['bad_email']  = array();

		foreach($emails as $addr)
		{
			if (preg_match('/<(.*)>/', $addr, $match))
			{
				$addr = $match['1'];
			}

			if ($subscribe == TRUE)
			{
				if ( ! valid_email($addr))
				{
					$vars['bad_email'][] = $addr;
					continue;
				}

				if (in_array($addr, $current))
				{
					$vars['dup_email']++;
					continue;
				}

				$data = array(
								'list_id'		=> $list_id,
								'authcode'		=> random_string('alnum', 10),
								'email'			=> $addr,
								'ip_address'	=> $this->EE->input->ip_address()
							);
				
				$this->EE->mailinglist_model->insert_subscription($data);
			}
			else
			{
				$this->EE->mailinglist_model->delete_subscription($list_id, $addr);
			}

			$vars['good_email']++;
		}

		if (count($vars['bad_email']) == 0 AND $vars['dup_email'] == 0)
		{
			if ($subscribe == TRUE)
			{
				$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('ml_emails_imported'));
				$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
			}
			else
			{
				$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('ml_emails_deleted'));
				$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
			}
		}
		
		$vars['cp_page_title'] = $this->EE->lang->line('ml_batch_subscribe');
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist', $this->EE->lang->line('ml_mailinglist'));

		$vars['notice'] = '';

		$vars['notice_import_del'] = ($subscribe == TRUE) ? 'ml_total_emails_imported' : 'ml_total_emails_deleted';

		if (count($vars['bad_email']) > 0)
		{
			sort($vars['bad_email']);
			
			$vars['notice_bad_email'] = ($subscribe == TRUE) ? 'ml_bad_email_heading' : 'ml_bad_email_del_heading';
		}

		return $this->EE->load->view('subscribe', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  View Mailinglist
	  */
	function view()
	{
		$list_id = $this->EE->input->get_post('list_id');
		
		$this->EE->load->library('table');
		
		$this->EE->table->set_base_url('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=view'.AMP.'list_id='.$list_id);
		$this->EE->table->set_columns(array(
			'email'			=> array(),
			'ip_address'	=> array('html' => FALSE, 'header' => lang('ip_address')),
			'list_id'		=> array('html' => FALSE, 'header' => lang('ml_mailinglist'), 'sort' => FALSE), // @todo sort by name, not id
			'_check'	=> array(
				'sort' => FALSE,
				'header' => form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"')
			)
		));
		
		$initial_state = array(
			'sort'	=> array('email' => 'asc')
		);
		
		$params = array(
			'perpage'	=> $this->perpage
		);
		
		$data = $this->EE->table->datasource('_mailinglist_filter', $initial_state, $params);



		$data['cp_page_title'] = $this->EE->lang->line('ml_view_mailinglist');

		$this->EE->cp->set_breadcrumb(
			BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist',
			$this->EE->lang->line('ml_mailinglist'
		));

		$this->EE->cp->add_js_script(array(
			'fp_module' => 'mailinglist'
		));

		// some page defaults
		$data['form_hidden'] = '';

		if ($list_id != '')
		{
			$data['form_hidden']['list_id'] = $list_id;
		}

		$data['mailinglists'] = array('' => $this->EE->lang->line('all')) + $data['mailinglists'];

		$this->EE->javascript->compile();

		return $this->EE->load->view('view', $data, TRUE);
	}

	// ------------------------------------------------------------------------
	
	/**
	 * View Ajax Filter
	 */
	function _mailinglist_filter($state, $params)
	{
		$this->EE->load->model('mailinglist_model');
		
		$email		= $this->EE->input->get_post('email');
		$list_id	= $this->EE->input->get_post('list_id');
		
		$this->EE->db->select('list_id, list_title');
		$res = $this->EE->db->get('mailing_lists');

		$lists = array();

		foreach ($res->result_array() as $row)
		{
			$lists[$row['list_id']] = $row['list_title'];
		}

		if ($list_id != '')
		{
			$this->EE->db->where('list_id', $list_id);
			$total = $this->EE->db->count_all_results('mailing_list');
		}
		else
		{
			$total = $this->EE->db->count_all('mailing_list');
		}
		
		$mailing_q = $this->EE->mailinglist_model->mailinglist_search(
			$list_id, $email, $state['sort'], $state['offset'], $params['perpage']
		);
		
		$subscribers = $mailing_q->result_array();
		
		$rows = array();
		
		while ($subscriber = array_shift($subscribers))
		{
			$rows[] = array(
				'email'		 => '<a href="mailto:'.$subscriber['email'].'">'.$subscriber['email'].'</a>',
				'ip_address' => $subscriber['ip_address'],
				'list_id'	 => isset($lists[$subscriber['list_id']]) ?  $lists[$subscriber['list_id']] : '',
				'_check'	 => '<input class="toggle" type="checkbox" name="toggle[]" value="'.$subscriber['user_id'].'" />'
			);
		}

		return array(
			'rows' => $rows,
			'no_results' => '<p>'.lang('ml_no_results').'</p>',
			'pagination' => array(
				'per_page' => $params['perpage'],
				'total_rows' => $total
			),
			
			'email'			=> $email,
			'selected_list'	=> $list_id,
			'mailinglists'	=> $lists
		);
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Emails - Confirm
	  */
	function delete_confirm()
	{
		if ( ! $this->EE->input->post('toggle'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=view');
		}

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist', $this->EE->lang->line('ml_mailinglist'));

		$vars['cp_page_title'] = $this->EE->lang->line('ml_delete_confirm');
		$vars['question_key'] = 'ml_delete_question';
		$vars['form_action'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=delete_email_addresses';

		$this->EE->load->helper('form');

		foreach ($_POST['toggle'] as $key => $val)
		{
			$vars['damned'][] = $val;
		}

		$this->EE->javascript->compile();

		return $this->EE->load->view('delete_confirm', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Email Addresses
	  */
	function delete_email_addresses()
	{
		if ($this->EE->input->post('delete') == '')
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=view');
		}

		$this->EE->load->model('mailinglist_model');
		$message = $this->EE->mailinglist_model->delete_email($_POST['delete']);

		$this->EE->session->set_flashdata('message_success', $message);
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist');
	}
}
// END CLASS

/* End of file mcp.mailinglist.php */
/* Location: ./system/expressionengine/modules/mailinglist/mcp.mailinglist.php */