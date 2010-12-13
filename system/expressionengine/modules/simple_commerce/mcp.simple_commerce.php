<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Simple Commerce Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Simple_commerce_mcp {

	var $export_type		= 'tab';
	var $perform_redirects	= TRUE;
	var $menu_email			= array();
	var $menu_groups		= array();
	var $nest_categories	= 'y';
	var $perpage			= 50;
	var $pipe_length 		= 5;
	var $base_url			= '';

	/**
	 * Constructor
	 *
	 * @access	public
	 */

	function Simple_commerce_mcp($switch = TRUE)
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		$this->base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce';

		$this->EE->cp->set_right_nav(array(
								'items'				=> $this->base_url.AMP.'method=edit_items',
								'purchases'			=> $this->base_url.AMP.'method=edit_purchases',
								'email_templates'	=> $this->base_url.AMP.'method=edit_emails',
								'simple_commerce_module_name' => $this->base_url)
							);
	}

	// --------------------------------------------------------------------

	/**
	 * Control Panel Index
	 *
	 * @access	public
	 */

	function index($message = '')
	{
		$this->EE->load->library('table');
		$this->EE->load->library('javascript');
		$this->EE->load->helper('form');

		$vars = array(
			'message' => $message,
			'cp_page_title'	=> $this->EE->lang->line('simple_commerce_module_name'),
			'api_url'		=> 
				$this->EE->functions->fetch_site_index(0,0).QUERY_MARKER.'ACT='.$this->EE->cp->fetch_action_id('Simple_commerce', 'incoming_ipn'),
			'action_url'	=> 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=various_settings',
			'paypal_account'=> $this->EE->config->item('sc_paypal_account')
		);

		$base = $this->EE->functions->remove_double_slashes(str_replace('/public_html', '', substr(BASEPATH, 0, - strlen(SYSDIR.'/'))).'/encryption/');

		foreach(array('certificate_id', 'public_certificate', 'private_key', 'paypal_certificate', 'temp_path') as $val)
		{

			if ($val == 'certificate_id')
			{
				$vars[$val] = ($this->EE->config->item('sc_'.$val) === FALSE) ? '' : $this->EE->config->item('sc_'.$val);
			}
			else
			{
				$vars[$val] = ($this->EE->config->item('sc_'.$val) === FALSE OR $this->EE->config->item('sc_'.$val) == '') ? $base.$val.'.pem' : $this->EE->config->item('sc_'.$val);
			}
		}

		if ($this->EE->config->item('sc_encrypt_buttons') == 'y')
		{
			$vars['encrypt_y'] = TRUE;
			$vars['encrypt_n'] = FALSE;
		}
		else
		{
			$vars['encrypt_y'] = FALSE;
			$vars['encrypt_n'] = TRUE;
		}

	return $this->EE->load->view('index', $vars, TRUE);

	}

	// --------------------------------------------------------------------

	/** -------------------------------------------
	/**  Save Encryption Settings
	/** -------------------------------------------*/
	function various_settings()
	{
		$prefs = array('encrypt_buttons', 'paypal_account', 'certificate_id', 'public_certificate', 'private_key', 'paypal_certificate', 'temp_path');

		$insert = array();

		if ( ! isset($_POST['sc_paypal_account']))
		{
			return $this->index();
		}

		foreach($prefs as $val)
		{
			if (isset($_POST['sc_'.$val]))
			{
				if ($val != 'encrypt_buttons')
				{
					if ($insert['sc_encrypt_buttons'] == 'y' && $val != 'paypal_account' && $val != 'certificate_id')
					{
						if ( ! file_exists($_POST['sc_'.$val]))
						{
							show_error(str_replace('%pref%', $this->EE->lang->line($val), $this->EE->lang->line('file_does_not_exist')));
						}

						if ($val == 'temp_path' && ! is_really_writable($_POST['sc_'.$val]))
						{
							show_error($this->EE->lang->line('temporary_directory_unwritable'));
						}
					}

					$insert['sc_'.$val] = $this->EE->security->xss_clean($_POST['sc_'.$val]);
				}
				else
				{
					$insert['sc_'.$val] = ($_POST['sc_'.$val] == 'y') ? 'y' : 'n';
				}
			}
		}

		if (count($insert) == 0)
		{
			return $this->index();
		}


		$this->EE->config->_update_config($insert);

		//$this->EE->config->core_ini = array_merge($this->EE->config->core_ini, $insert);

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('settings_updated'));

		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP
		.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=index');

	}

	// --------------------------------------------------------------------

	/** -------------------------------------------
	/**  Add Item
	/** -------------------------------------------*/
	function add_item()
	{
		$entry_ids = array();

		$this->EE->load->library('table');

		//  Must be Assigned to Channels
		if (count($this->EE->session->userdata['assigned_channels']) == 0)
		{
			show_error($this->EE->lang->line('no_entries_matching_that_criteria').BR.BR.$this->EE->lang->line('site_specific_data'));
		}

		//  Either Show Search Form or Process Entries
		if ($this->EE->input->get_post('entry_id') !== FALSE)
		{
			$entry_ids[] = $this->EE->input->get_post('entry_id');
		}
		elseif (isset($_POST['toggle']) && is_array($_POST['toggle']))
		{
			foreach ($_POST['toggle'] as $key => $val)
			{
				if ($val != '' && is_numeric($val))
				{
					$entry_ids[] = $val;
				}
			}
		}

		if (count($entry_ids) > 0)
		{
			return $this->_items_form($entry_ids, 'y');
		}
		else
		{
			return $this->add_items();
		}
	}

	// --------------------------------------------------------------------

	// For items, we need to make certain they can only view/assign items to entries
	// the have permissions for - both on the edit table display, the add/edit form,
	// and when entering the data

	function weed_entries($entry_ids = array(), $new = 'y')
	{
		$this->EE->db->select('entry_id, channel_id, title');
		$this->EE->db->where_in('entry_id', $entry_ids);
		$query = $this->EE->db->get('channel_titles');

		$entry_ids = array();

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				if (isset($this->EE->session->userdata['assigned_channels'][$row['channel_id']]))
				{
					$entry_ids[$row['entry_id']] = $row['title'];
				}
			}
		}

		if ($new == 'y')
		{
			//  Weed Out Any Entries that are already items
			$this->EE->db->select('entry_id');
			$this->EE->db->where_in('entry_id', $entry_ids);
			$query = $this->EE->db->get('simple_commerce_items');

			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					unset($entry_ids[$row['entry_id']]);
				}
			}
		}

		return $entry_ids;
	}

	// --------------------------------------------------------------------

	function _items_form($entry_ids = array(), $new = 'y')
	{
		$this->EE->load->model('member_model');
		$this->EE->load->helper(array('form', 'date'));
		$this->EE->load->library('table');

		$vars['items'] = array();
		$vars['form_hidden'] = NULL;

		$safe_ids = $this->weed_entries($entry_ids, $new);
		unset($entry_ids);

		if (count($safe_ids) == 0)
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('invalid_entries'));
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP
				.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_items');
		}

		$vars['email_templates_dropdown'] = array(0 => $this->EE->lang->line('send_no_email'));

		$this->EE->db->select('email_id, email_name');
		$query = $this->EE->db->get('simple_commerce_emails');

		foreach($query->result_array() as $row)
		{
			$vars['email_templates_dropdown'][$row['email_id']] = $row['email_name'];
		}

		// get all member groups for the dropdown list
		$member_groups = $this->EE->member_model->get_member_groups();

		// first dropdown item is "all"
		$vars['member_groups_dropdown'] = array(0 => $this->EE->lang->line('no_change'));

		foreach($member_groups->result() as $group)
		{
			$vars['member_groups_dropdown'][$group->group_id] = $group->group_title;
		}

		// get subsubscription frequency options
		$vars['subscription_frequency_unit']['day'] = $this->EE->lang->line('days');
		$vars['subscription_frequency_unit']['week'] = $this->EE->lang->line('weeks');
		$vars['subscription_frequency_unit']['month'] = $this->EE->lang->line('months');
		$vars['subscription_frequency_unit']['year'] = $this->EE->lang->line('years');

		if ($new == 'y')
		{
			$type = (count($safe_ids) == 1) ? 'add_item' : 'add_items';
			$vars['type'] = $type;
			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=adding_items';

			foreach($safe_ids as $id => $title)
			{
				$vars['items'][$id]['entry_title'] = $title;
				$vars['items'][$id]['regular_price'] = '0.00';
				$vars['items'][$id]['sale_price'] = '0.00';
				$vars['items'][$id]['item_enabled'] =  TRUE;
				$vars['items'][$id]['sale_price_enabled'] =  FALSE;
				$vars['items'][$id]['recurring'] =  FALSE;
				$vars['items'][$id]['admin_email_address'] = '';
				$vars['items'][$id]['admin_email_template'] = '';
				$vars['items'][$id]['admin_email_template_unsubscribe'] = '';
				$vars['items'][$id]['customer_email_template'] = '';
				$vars['items'][$id]['customer_email_template_unsubscribe'] = '';
				$vars['items'][$id]['new_member_group'] = '';
				$vars['items'][$id]['member_group_unsubscribe'] = '';
				$vars['items'][$id]['current_subscriptions'] = '';
				$vars['items'][$id]['subscription_frequency'] = '';
				$vars['items'][$id]['subscription_frequency_unit'] = '';
				$vars['items'][$id]['entry_id'] = $id;

			}
		}
		else
		{
			$type = (count($safe_ids) == 1) ? 'update_item' : 'update_items';
			$vars['type'] = $type;
			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=updating_items';


			//  Valid Entries Selected?

			$this->EE->db->where_in('entry_id', array_keys($safe_ids));
			$query = $this->EE->db->get('simple_commerce_items');

			if ($query->num_rows() == 0)
			{
				$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('invalid_entries'));
				$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP
				.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_items');
			}

			foreach($query->result_array() as $row)
			{
				$vars['items'][$row['entry_id']]['entry_title'] = $safe_ids[$row['entry_id']];
				$vars['items'][$row['entry_id']]['regular_price'] = $row['item_regular_price'];
				$vars['items'][$row['entry_id']]['sale_price'] = $row['item_sale_price'];
				$vars['items'][$row['entry_id']]['item_enabled'] =  ($row['item_enabled'] == 'y') ? TRUE : FALSE;
				$vars['items'][$row['entry_id']]['sale_price_enabled'] =  ($row['item_use_sale'] == 'y') ? TRUE : FALSE;
				$vars['items'][$row['entry_id']]['recurring'] =  ($row['recurring'] == 'y') ? TRUE : FALSE;
				$vars['items'][$row['entry_id']]['admin_email_address'] = $row['admin_email_address'];
				$vars['items'][$row['entry_id']]['admin_email_template'] = $row['admin_email_template'];
				$vars['items'][$row['entry_id']]['customer_email_template'] = $row['customer_email_template'];
				$vars['items'][$row['entry_id']]['new_member_group'] = $row['new_member_group'];
				$vars['items'][$row['entry_id']]['subscription_frequency'] = $row['subscription_frequency'];
				$vars['items'][$row['entry_id']]['subscription_frequency_unit'] = $row['subscription_frequency_unit'];
				$vars['items'][$row['entry_id']]['current_subscriptions'] = $row['current_subscriptions'];
				$vars['items'][$row['entry_id']]['entry_id'] = $row['entry_id'];
				$vars['items'][$row['entry_id']]['member_group_unsubscribe'] = $row['member_group_unsubscribe'];
				$vars['items'][$row['entry_id']]['customer_email_template_unsubscribe'] = $row['customer_email_template_unsubscribe'];
				$vars['items'][$row['entry_id']]['admin_email_template_unsubscribe'] = $row['admin_email_template_unsubscribe'];
			}
		}

		$vars['cp_page_title']  = $this->EE->lang->line($type);
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce', $this->EE->lang->line('simple_commerce_module_name'));

		$this->EE->javascript->compile();

		return $this->EE->load->view('edit_item', $vars, TRUE);
	}
	
	// --------------------------------------------------------------------

	function _items_validate($entry_id = array())
	{
		$this->EE->load->library('form_validation');

		foreach ($entry_id as $id)
		{
			$this->EE->form_validation->set_rules("regular_price[{$id}]", 'lang:regular_price', 'required|numeric|callback__valid_price[{$id}]');
			$this->EE->form_validation->set_rules("sale_price[{$id}]", 'lang:regular_price', 'required|numeric|callback__valid_price[{$id}]');

			$this->EE->form_validation->set_rules("item_enabled[{$id}]", '', '');
			$this->EE->form_validation->set_rules("sale_price_enabled[{$id}]", '', '');
			$this->EE->form_validation->set_rules("admin_email_address[{$id}]", '', '');
			$this->EE->form_validation->set_rules("admin_email_template[{$id}]", '', '');
			$this->EE->form_validation->set_rules("'customer_email_template[{$id}]", '', '');
			$this->EE->form_validation->set_rules("new_member_group[{$id}]", '', '');
			$this->EE->form_validation->set_rules("entry_id[{$id}]", '', '');
		}

		$this->EE->form_validation->set_error_delimiters('<br /><span class="notice">', '</span>');
	}

	// --------------------------------------------------------------------

	/** -------------------------------------------
	/**  Modify Store Items - Add/Update
	/** -------------------------------------------*/

	function adding_items()		{ return $this->modify_items('y'); 	}
	function updating_items()	{ return $this->modify_items('n');	}

	function modify_items($new = 'y')
	{
		if ( ! isset($_POST['entry_id']) OR ! is_array($_POST['entry_id']))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		foreach ($_POST['entry_id'] as $id)
		{
			$entry_ids[] = $id;
		}

		$this->_items_validate($entry_ids);

		if ($this->EE->form_validation->run() === FALSE)
		{
			return $this->_items_form($entry_ids, $new);
		}

		$safe_ids = $this->weed_entries($entry_ids, $new);
		unset($entry_ids);

		if (count($safe_ids) == 0)
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('invalid_entries'));
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP
				.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_items');
		}

		foreach(array_keys($safe_ids) as $id)
		{
			$data = array(
							'entry_id'					=> $_POST['entry_id'][$id],
							'item_enabled'				=> ( ! isset($_POST['enabled'][$id])) ? 'n' : 'y',
							'item_regular_price'		=> $_POST['regular_price'][$id],
							'item_sale_price'			=> $_POST['sale_price'][$id],
							'item_use_sale'				=> ( ! isset($_POST['use_sale'][$id])) ? 'n' : 'y',
							'subscription_frequency'	=> ($_POST['subscription_frequency'][$id] == '') ? NULL : $_POST['subscription_frequency'][$id],
							'subscription_frequency_unit'	=> ($_POST['subscription_frequency_unit'][$id] == '') ? NULL : $_POST['subscription_frequency_unit'][$id],
							'new_member_group'			=> ($_POST['member_group'][$id] == 'no_change') ? 0 : $_POST['member_group'][$id],
							'admin_email_address'		=> $_POST['admin_email_address'][$id],
							'admin_email_template'		=> $_POST['admin_email_template'][$id],
							'customer_email_template'	=> $_POST['customer_email_template'][$id],

							'recurring'				=> ( ! isset($_POST['recurring'][$id])) ? 'n' : 'y',
							'admin_email_template_unsubscribe'		=> $_POST['admin_email_template_unsubscribe'][$id],
							'customer_email_template_unsubscribe'	=> $_POST['customer_email_template_unsubscribe'][$id],
							'member_group_unsubscribe'			=> $_POST['member_group_unsubscribe'][$id]

							);



			/** ---------------------------------
			/**  Do our insert or update
			/** ---------------------------------*/

			if ($new == 'y')
			{
				$this->EE->db->query($this->EE->db->insert_string('exp_simple_commerce_items', $data));
				$cp_message = $this->EE->lang->line('item_added');
			}
			else
			{
				$this->EE->db->query($this->EE->db->update_string('exp_simple_commerce_items', $data, "entry_id = '$id'"));
				$cp_message = $this->EE->lang->line('updated');
			}
		}

		$this->EE->functions->clear_caching('page');

		$this->EE->session->set_flashdata('message_success', $cp_message);

		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP
		.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_items');

	}
	
	// --------------------------------------------------------------------


	/** -------------------------------------------
	/**  Edit Store Items
	/** -------------------------------------------*/
	function edit_items()
	{
		$this->EE->load->library('javascript');
		$this->EE->load->helper('form');

		$vars['form_hidden'] = NULL;
		$vars['items'] = array();

		//  Either Show Search Form or Process Entries

		if ($this->EE->input->post('toggle') !== FALSE OR $this->EE->input->get_post('entry_id') !== FALSE)
		{
			$entry_ids = array();

			if ($this->EE->input->get_post('entry_id') !== FALSE)
			{
				$entry_ids[] = $this->EE->input->get_post('entry_id');
			}
			else
			{
				foreach ($_POST['toggle'] as $key => $val)
				{
					if ($val != '' && is_numeric($val))
					{
						$entry_ids[] = $val;
					}
				}
			}



			if ($this->EE->input->get_post('action') == 'delete')
			{
					return $this->_delete_confirmation_forms(
												array(
														'method'	=> 'delete_items',
														'heading'	=> 'delete_items_confirm',
														'message'	=> 'delete_items_confirm',
														'hidden'	=> array('entry_ids' => implode('|', $entry_ids))
													)
												);
			}
			else
			{
				return $this->_items_form($entry_ids, 'n');

			}
		}
		else
		{
			$this->EE->load->library('table');
			$vars['cp_page_title']  = $this->EE->lang->line('edit_items');
			$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.
			AMP.'M=show_module_cp'.AMP.'module=simple_commerce', $this->EE->lang->line('simple_commerce_module_name'));

			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_items';

			// Add javascript

			$this->EE->cp->add_js_script(array('plugin' => 'dataTables'));

			$this->EE->javascript->output($this->ajax_filters('edit_items_ajax_filter', 8));


			$this->EE->javascript->output(array(
					'$(".toggle_all").toggle(
						function(){
							$("input.toggle").each(function() {
								this.checked = true;
							});
						}, function (){
							var checked_status = this.checked;
							$("input.toggle").each(function() {
								this.checked = false;
							});
						}
					);'
				)
			);

			$this->EE->javascript->compile();

			//  Check for pagination

			$total = $this->EE->db->count_all('simple_commerce_items');
			// crap- should only be ones they have access to....

			if ($total == 0)
			{
				return $this->EE->load->view('edit_items', $vars, TRUE);
			}

			if ( ! $rownum = $this->EE->input->get_post('rownum'))
			{
				$rownum = 0;
			}

			/*
			$this->EE->db->order_by("item_id", "desc");
			$this->EE->db->limit($this->perpage, $rownum);
			$query = $this->EE->db->get('simple_commerce_items');
			*/

			$query = $this->EE->db->query("SELECT sc.*, wt.title FROM exp_simple_commerce_items sc, exp_channel_titles wt
				 WHERE sc.entry_id = wt.entry_id
				 ORDER BY item_id desc LIMIT $rownum, $this->perpage");

			$i = 0;

			//$entry_ids = $this->weed_items($entry_ids);

			foreach($query->result_array() as $row)
			{
				$subscription_period = ($row['subscription_frequency'] != '') ? $row['subscription_frequency'].' x '.$row['subscription_frequency_unit'] : '--';

   				$vars['items'][$row['entry_id']]['entry_title'] = $row['title'];
   				$vars['items'][$row['entry_id']]['edit_link'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_items'.AMP.'entry_id='.$row['entry_id'];

   				$vars['items'][$row['entry_id']]['regular_price'] = $row['item_regular_price'];
   				$vars['items'][$row['entry_id']]['sale_price'] = $row['item_sale_price'];
   				$vars['items'][$row['entry_id']]['use_sale_price'] = $row['item_use_sale'];
   				$vars['items'][$row['entry_id']]['item_purchases'] = $row['item_purchases'];
  				$vars['items'][$row['entry_id']]['subscription_frequency_unit'] = $row['subscription_frequency_unit'];
   				$vars['items'][$row['entry_id']]['subscription_frequency'] = $row['subscription_frequency_unit'];
   				$vars['items'][$row['entry_id']]['subscription_period'] = $subscription_period;
  				$vars['items'][$row['entry_id']]['current_subscriptions'] = $row['current_subscriptions'];

				// Toggle checkbox
				$vars['items'][$row['entry_id']]['toggle'] = array(
																		'name'		=> 'toggle[]',
																		'id'		=> 'edit_box_'.$row['entry_id'],
																		'value'		=> $row['entry_id'],
																		'class'		=>'toggle'
																	);


			}

			// Pass the relevant data to the paginate class so it can display the "next page" links
			$this->EE->load->library('pagination');
			$p_config = $this->pagination_config('edit_items', $total);

			$this->EE->pagination->initialize($p_config);

			$vars['pagination'] = $this->EE->pagination->create_links();

			return $this->EE->load->view('edit_items', $vars, TRUE);
		}
	}
	
	// --------------------------------------------------------------------
	
	function edit_items_ajax_filter()
	{
		$this->EE->output->enable_profiler(FALSE);
		$this->EE->load->helper('text');

		$col_map = array('title', 'item_regular_price', 'item_sale_price', 'item_use_sale', 'subscription_frequency', 'current_subscriptions', 'item_purchases');

		$id = ($this->EE->input->get_post('id')) ? $this->EE->input->get_post('id') : '';


		// Note- we pipeline the js, so pull more data than are displayed on the page
		$perpage = $this->EE->input->get_post('iDisplayLength');
		$offset = ($this->EE->input->get_post('iDisplayStart')) ? $this->EE->input->get_post('iDisplayStart') : 0; // Display start point
		$sEcho = $this->EE->input->get_post('sEcho');


		/* Ordering */
		$order_by = 'item_id desc';

		if ($this->EE->input->get('iSortCol_0') !== FALSE)
		{
			for ( $i=0; $i < $this->EE->input->get('iSortingCols'); $i++ )
			{
				if (isset($col_map[$this->EE->input->get('iSortCol_'.$i)]))
				{
					$o = ($this->EE->input->get('sSortDir_'.$i) == 'asc') ? 'asc' : 'desc';
					$order[] = $col_map[$this->EE->input->get('iSortCol_'.$i)].' '.$o;
				}
			}
			
			$order_by = implode(', ', $order);
		}		
		

		$total = $this->EE->db->count_all('simple_commerce_items');

		$j_response['sEcho'] = $sEcho;
		$j_response['iTotalRecords'] = $total;
		$j_response['iTotalDisplayRecords'] = $total;

		$tdata = array();
		$i = 0;

		$query = $this->EE->db->query("SELECT sc.*, wt.title FROM exp_simple_commerce_items sc, exp_channel_titles wt
				 WHERE sc.entry_id = wt.entry_id
				 ORDER BY {$order_by} LIMIT $offset, $perpage");

		// Note- empty string added because otherwise it will throw a js error
		foreach ($query->result_array() as $item)
		{
			$subscription_period = ($item['subscription_frequency'] != '') ? $item['subscription_frequency'].' x '.$item['subscription_frequency_unit'] : '--';
			$m[] = '<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_items'.AMP.'entry_id='.$item['entry_id'].'">'.$item['title'].'</a>';
			$m[] = $item['item_regular_price'];
			$m[] = $item['item_sale_price'];
			$m[] = $item['item_use_sale'];
			$m[] = $subscription_period;
			$m[] = $item['current_subscriptions'];
			$m[] = $item['item_purchases'];
			$m[] = '<input class="toggle" id="edit_box_'.$item['entry_id'].'" type="checkbox" name="toggle[]" value="'.$item['entry_id'].'" />';

			$tdata[$i] = $m;
			$i++;
			unset($m);
		}

		$j_response['aaData'] = $tdata;
		$sOutput = $this->EE->javascript->generate_json($j_response, TRUE);

		die($sOutput);
	}

	// --------------------------------------------------------------------

	function _delete_confirmation_forms($data)
	{
		$required = array('method', 'heading', 'message', 'hidden');

		$vars['cp_page_title']  = $this->EE->lang->line($data['heading']);
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce', $this->EE->lang->line('simple_commerce_module_name'));
		$vars['damned'] = $data['hidden'];

		$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method='.$data['method'];
		$vars['message'] = $data['message'];

		return $this->EE->load->view('delete_confirmation', $vars, TRUE);

	}

	// --------------------------------------------------------------------
	
	/** -------------------------------------------
	/**  Delete Store Items
	/** -------------------------------------------*/
	function delete_items()
	{
		if ($this->EE->input->post('entry_ids') !== FALSE)
		{
			$entry_ids = array();

			foreach(explode('|', $this->EE->input->get_post('entry_ids')) as $id)
			{
				$entry_ids[] = $this->EE->db->escape_str($id);
			}

			$this->EE->db->query("DELETE FROM exp_simple_commerce_items
						WHERE entry_id IN ('".implode("','", $entry_ids)."')");
		}

		return $this->edit_items($this->EE->lang->line('items_deleted'));
	}

	// --------------------------------------------------------------------
	
	/** -------------------------------------------
	/**  Add Email Template
	/** -------------------------------------------*/
	function add_email()
	{
		return $this->_emails_form(array(0));
	}

	// --------------------------------------------------------------------

	function _emails_form($email_ids = array(), $new = 'y')
	{
		$this->EE->load->library('table');

		$this->EE->load->helper('form');
		$type = 'add_email';

		$this->EE->jquery->plugin(BASE.AMP.'C=javascript'.AMP.'M=load'.AMP.'file=ee_txtarea', TRUE);

		$vars['template_directions'] = $this->EE->load->view('template_directions', '', TRUE);

		$this->EE->javascript->output('

			$(".glossary_content a").click(function(){
				var replacement = $(this).attr("title");
				var id_group = $(this).parents("div:first").attr("id");
				var active_field = id_group.replace("directions", "email_body");

				$("#"+active_field).insertAtCursor("{"+replacement+"}");
				return false;
			});

		');

		$vars['form_hidden'] = '';

		if ($new == 'y')
		{
			$vars['email_template']['0']['email_id'] = '';
			$vars['email_template']['0']['email_name'] = '';
			$vars['email_template']['0']['email_subject'] = '';
			$vars['email_template']['0']['email_body'] = '';
			$vars['email_template']['0']['possible_post'] = TRUE;

			$vars['cp_page_title']  = $this->EE->lang->line('add_emails');
			$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce', $this->EE->lang->line('simple_commerce_module_name'));

			$vars['form_hidden']['email_id']['0'] = '';

			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=adding_email';

		}
		else
		{
			$type = (count($email_ids) == 1) ? 'update_email' : 'update_emails';
			$vars['email_template'] = array();

			$query = $this->EE->db->query("SELECT * FROM exp_simple_commerce_emails WHERE email_id IN ('".implode("','", $email_ids)."')");

			foreach ($query->result_array() as $key => $row)
			{
				$vars['email_template'][$row['email_id']]['email_id'] = $row['email_id'];
				$vars['email_template'][$row['email_id']]['email_name'] = $row['email_name'];
				$vars['email_template'][$row['email_id']]['email_subject'] = $row['email_subject'];
				$vars['email_template'][$row['email_id']]['email_body'] = $row['email_body'];
				$vars['email_template'][$row['email_id']]['possible_post'] = ($key == 0) ? TRUE : FALSE;
			}

			$vars['cp_page_title']  = $this->EE->lang->line($type);
			$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce', $this->EE->lang->line('simple_commerce_module_name'));


			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=update_emails';
		}


		$vars['type'] = $this->EE->lang->line($type);
		$this->EE->javascript->compile();
		return $this->EE->load->view('email_template', $vars, TRUE);

	}
	
	// --------------------------------------------------------------------

	function _email_form_validation($email_id = array())
	{
		$this->EE->load->library('form_validation');

		foreach ($email_id as $id)
		{
			$this->EE->form_validation->set_rules("email_name[{$id}]", 'lang:email_name', 'required');
			$this->EE->form_validation->set_rules("email_subject[{$id}]", 'lang:email_subject', 'required');
			$this->EE->form_validation->set_rules("email_body[{$id}]", 'lang:email_body', 'required');
		}

		$this->EE->form_validation->set_error_delimiters('<br /><span class="notice">', '</span>');
	}


	/** -------------------------------------------
	/**  Modify Email Templates- Add/Update
	/** -------------------------------------------*/

	function adding_email()		{ return $this->modify_emails('y', array(0)); 	}
	function update_emails()	{ return $this->modify_emails('n');	}

	function modify_emails($new = 'y', $email_ids = array())
	{
		if ( ! is_array($_POST['email_id']))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		/** -------------------------------------------
		/**  Valid Email Templates Selected?
		/** -------------------------------------------*/

		if ($new !== 'y')
		{
			$this->EE->db->select('email_id');
			$this->EE->db->where_in('email_id', $_POST['email_id']);
			$query = $this->EE->db->get('simple_commerce_emails');

			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					$email_ids[$row['email_id']] = $row['email_id'];
				}
			}

			if (count($email_ids) == 0)
			{
				unset($_POST['email_id']);
				$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('invalid_emails'));

				$this->EE->functions->redirect(
					BASE.AMP.'C=addons_modules'.AMP
					.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_emails');
			}
		}

		$this->_email_form_validation($email_ids);

		if ($this->EE->form_validation->run() === FALSE)
		{
			return $this->_emails_form($email_ids, $new);
		}

		foreach($email_ids as $id)
		{
			$data = array(
							'email_id'			=> $_POST['email_id'][$id],
							'email_name'		=> $_POST['email_name'][$id],
							'email_subject'		=> $_POST['email_subject'][$id],
							'email_body'		=> $_POST['email_body'][$id],
							);

			//  Do our insert or update

			if ($new == 'y')
			{
				$cp_message = 'new';
				unset($data['email_id']);

				$this->EE->db->insert('simple_commerce_emails', $data);
			}
			else
			{
				$cp_message = 'update';

				$this->EE->db->where('email_id', $id);
				$this->EE->db->update('simple_commerce_emails', $data);
			}
		}

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line($cp_message));

		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'
		.AMP.'module=simple_commerce'.AMP.'method=edit_emails');
	}



	/** -------------------------------------------
	/**  Delete Email Templates
	/** -------------------------------------------*/
	function delete_emails()
	{
		if ($this->EE->input->post('email_ids') !== FALSE)
		{
			$email_ids = explode('|', $this->EE->input->post('email_ids'));

			$this->EE->db->where_in('email_id', $email_ids);
			$this->EE->db->delete('simple_commerce_emails');

			$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('emails_deleted'));
		}

		$this->EE->functions->redirect(
			BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'
			.AMP.'module=simple_commerce'.AMP.'method=edit_emails');
	}



	/** -------------------------------------------
	/**  Edit Email Templates
	/** -------------------------------------------*/
	function edit_emails()
	{
		$this->EE->load->library('javascript');
		$this->EE->load->helper('form');

		$vars['email_templates'] = array();
		$vars['form_hidden'] = NULL;

		/** -------------------------------------------
		/**  Either Show Search Form or Process Entries
		/** -------------------------------------------*/

		if ($this->EE->input->post('toggle') !== FALSE OR $this->EE->input->get_post('email_id') !== FALSE)
		{
			$email_ids = array();

			if ($this->EE->input->get_post('email_id') !== FALSE)
			{
				$email_ids[] = $this->EE->input->get_post('email_id');
			}
			else
			{
				foreach ($_POST['toggle'] as $key => $val)
				{
					if ($val != '' && is_numeric($val))
					{
						$email_ids[] = $val;
					}
				}
			}

			/** -------------------------------------------
			/**  Removed cause couldn't figure the point- Weed Out Any Entries that are already items
			/** -------------------------------------------*/


			if ($this->EE->input->get_post('action') == 'delete')
			{
				return $this->_delete_confirmation_forms(
												array(
														'method'	=> 'delete_emails',
														'heading'	=> 'delete_emails_confirm',
														'message'	=> 'delete_emails_confirm',
														'hidden'	=> array('email_ids' => implode('|', $email_ids))
													)
												);
			}
			else
			{
				return $this->_emails_form($email_ids, 'n');
			}
		}
		else
		{
			$this->EE->load->library('table');
			$vars['cp_page_title']  = $this->EE->lang->line('edit_email_templates');
			$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'
			.AMP.'module=simple_commerce', $this->EE->lang->line('simple_commerce_module_name'));

			// Add javascript

			$this->EE->cp->add_js_script(array('plugin' => 'dataTables'));

			$this->EE->javascript->output($this->ajax_filters('edit_emails_ajax_filter', 2));

			$this->EE->javascript->output(array(
					'$(".toggle_all").toggle(
						function(){
							$("input.toggle").each(function() {
								this.checked = true;
							});
						}, function (){
							var checked_status = this.checked;
							$("input.toggle").each(function() {
								this.checked = false;
							});
						}
					);'
				)
			);

			$this->EE->javascript->compile();

			//  Check for pagination

			$total = $this->EE->db->count_all('simple_commerce_emails');

			if ($total == 0)
			{
				return $this->EE->load->view('edit_templates', $vars, TRUE);
			}

			if ( ! $rownum = $this->EE->input->get_post('rownum'))
			{
				$rownum = 0;
			}

			$this->EE->db->select('email_id, email_name');
			$this->EE->db->order_by("email_name", "desc");
			$this->EE->db->limit($this->perpage, $rownum);
			$query = $this->EE->db->get('simple_commerce_emails');

			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_emails';

			$i = 0;

			foreach($query->result_array() as $row)
			{
   				$vars['email_templates'][$row['email_id']]['email_name'] = $row['email_name'];
   				$vars['email_templates'][$row['email_id']]['edit_link'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_emails'.AMP.'email_id='.$row['email_id'];
				// Toggle checkbox
				$vars['email_templates'][$row['email_id']]['toggle'] = array(
																			'name'		=> 'toggle[]',
																			'id'		=> 'edit_box_'.$row['email_id'],
																			'value'		=> $row['email_id'],
																			'class'		=>'toggle'
																	);

			}

			// Pass the relevant data to the paginate class so it can display the "next page" links
			$this->EE->load->library('pagination');
			$p_config = $this->pagination_config('edit_emails', $total);

			$this->EE->pagination->initialize($p_config);

			$vars['pagination'] = $this->EE->pagination->create_links();

			return $this->EE->load->view('edit_templates', $vars, TRUE);
		}
	}

	function edit_emails_ajax_filter()
	{

		$this->EE->output->enable_profiler(FALSE);

		$col_map = array('email_name');

		$id = ($this->EE->input->get_post('id')) ? $this->EE->input->get_post('id') : '';


		// Note- we pipeline the js, so pull more data than are displayed on the page
		$perpage = $this->EE->input->get_post('iDisplayLength');
		$offset = ($this->EE->input->get_post('iDisplayStart')) ? $this->EE->input->get_post('iDisplayStart') : 0; // Display start point
		$sEcho = $this->EE->input->get_post('sEcho');


		/* Ordering */
		$order = array();
		
		if ($this->EE->input->get('iSortCol_0') !== FALSE)
		{
			for ( $i=0; $i < $this->EE->input->get('iSortingCols'); $i++ )
			{
				if (isset($col_map[$this->EE->input->get('iSortCol_'.$i)]))
				{
					$order[$col_map[$this->EE->input->get('iSortCol_'.$i)]] = ($this->EE->input->get('sSortDir_'.$i) == 'asc') ? 'asc' : 'desc';
				}
			}
		}
		
		$total = $this->EE->db->count_all('simple_commerce_emails');

		$j_response['sEcho'] = $sEcho;
		$j_response['iTotalRecords'] = $total;
		$j_response['iTotalDisplayRecords'] = $total;

		$tdata = array();
		$i = 0;

			$this->EE->db->select('email_id, email_name');

			if (count($order) > 0)
			{
				foreach ($order as $k => $v)
				{
					$this->EE->db->order_by($k, $v);
				}
			}
			else
			{
				$this->EE->db->order_by("email_name", "desc");
			}

			$this->EE->db->limit($perpage, $offset);
			$query = $this->EE->db->get('simple_commerce_emails');

			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_emails';

			$i = 0;


		foreach ($query->result_array() as $email)
		{
			$m[] = '<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_emails'.AMP.'email_id='.$email['email_id'].'">'.$email['email_name'].'</a>';
			$m[] = '<input class="toggle" id="edit_box_'.$email['email_id'].'" type="checkbox" name="toggle[]" value="'.$email['email_id'].'" />';

			$tdata[$i] = $m;
			$i++;
			unset($m);
		}

		$j_response['aaData'] = $tdata;
		$sOutput = $this->EE->javascript->generate_json($j_response, TRUE);

		die($sOutput);
	}


/* ======================================================
/*  PURCHASES
/* ======================================================

	/** -------------------------------------------
	/**  Add Purchase
	/** -------------------------------------------*/
	function add_purchase($message = '')
	{
		return $this->_purchases_form(array(0));
	}


	function _purchases_form($purchase_ids = array(), $new = 'y')
	{
		$this->EE->load->model('member_model');
		$this->EE->load->library('table');
		$this->EE->load->helper(array('form', 'date'));

		$vars['items'] = array();
		$vars['form_hidden'] = array();
		$type = 'add_purchase';

		$this->EE->jquery->ui(BASE.AMP.'C=javascript'.AMP.'M=load'.AMP.'ui=datepicker', TRUE);

		// used in date field
		$this->EE->javascript->output('
			date_obj = new Date();
			date_obj_hours = date_obj.getHours();
			date_obj_mins = date_obj.getMinutes();

			if (date_obj_mins < 10) { date_obj_mins = "0" + date_obj_mins; }

			if (date_obj_hours > 11) {
				date_obj_hours = date_obj_hours - 12;
				date_obj_am_pm = " PM";
			} else {
				date_obj_am_pm = " AM";
			}

			date_obj_time = " \'"+date_obj_hours+":"+date_obj_mins+date_obj_am_pm+"\'";
		');

		// get all items
		//$items_list = $DB->query("SELECT sc.item_id, wt.title FROM exp_simple_commerce_items sc, exp_weblog_titles wt
	    //    				 WHERE sc.entry_id = wt.entry_id");

		$this->EE->db->select('simple_commerce_items.item_id, channel_titles.title');
		$this->EE->db->from('simple_commerce_items');
		$this->EE->db->join('channel_titles', 'simple_commerce_items.entry_id = channel_titles.entry_id');

		$items_list = $this->EE->db->get();

		$vars['items_dropdown'] = array('' => $this->EE->lang->line('choose_item'));

		foreach($items_list->result() as $item)
		{
			$vars['items_dropdown'][$item->item_id] = $item->title;
		}

		if ($new == 'y')
		{
			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=adding_purchase';
			$now_date = ($this->EE->localize->set_localized_time() * 1000);

			foreach($purchase_ids as $id)
			{
				$vars['purchases'][$id]['txn_id'] = '';
				$vars['purchases'][$id]['screen_name'] = '';
				$vars['purchases'][$id]['item_id'] = '';
				$vars['purchases'][$id]['purchase_date'] = $this->EE->localize->set_human_time();
				$vars['purchases'][$id]['subscription_end_date'] = '';
				$vars['purchases'][$id]['item_cost'] =  '';
				$vars['purchases'][$id]['purchase_id'] =  0;

			$this->EE->javascript->output('
			$("#purchase_date_'.$id.'").datepicker({dateFormat: $.datepicker.W3C + date_obj_time, defaultDate: new Date('.$now_date.')});
			$("#subscription_end_date_'.$id.'").datepicker({dateFormat: $.datepicker.W3C + date_obj_time, defaultDate: new Date('.$now_date.')});
		');

			}
		}
		else
		{
			$type = (count($purchase_ids) == 1) ? 'update_purchase' : 'update_purchases';
			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=update_purchases';

			$query = $this->EE->db->query("SELECT sp.*, si.entry_id, si.recurring, m.screen_name AS purchaser FROM exp_simple_commerce_purchases sp, exp_simple_commerce_items si, exp_members m
								 WHERE sp.item_id = si.item_id
								 AND sp.member_id = m.member_id
								 AND sp.purchase_id IN ('".implode("','", $purchase_ids)."')");

			foreach($query->result_array() as $row)
			{
				$vars['purchases'][$row['purchase_id']]['txn_id'] = $row['txn_id'];
				$vars['purchases'][$row['purchase_id']]['member_id']  = $row['member_id'];
				$vars['purchases'][$row['purchase_id']]['item_id'] = $row['item_id'];
				$vars['purchases'][$row['purchase_id']]['purchase_date'] = $this->EE->localize->set_human_time($row['purchase_date']);

				$vars['purchases'][$row['purchase_id']]['item_cost'] =  $row['item_cost'];
				$vars['purchases'][$row['purchase_id']]['purchase_id'] = $row['purchase_id'];
				$vars['purchases'][$row['purchase_id']]['screen_name'] = $row['purchaser'];
				$vars['purchases'][$row['purchase_id']]['recurring'] = $row['recurring'];
				
				$now_p_date = ($row['purchase_date'] * 1000);


			$this->EE->javascript->output('
			$("#purchase_date_'.$row['purchase_id'].'").datepicker({dateFormat: $.datepicker.W3C + date_obj_time, defaultDate: new Date('.$now_p_date.')});
		');

			}
		}

		$vars['cp_page_title']  = $this->EE->lang->line($type);
		$vars['type'] = $this->EE->lang->line($type);
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce', $this->EE->lang->line('simple_commerce_module_name'));

		$this->EE->javascript->compile();

		return $this->EE->load->view('edit_purchase', $vars, TRUE);
	}

	function _purchases_validate($purchase_id = array())
	{
		$this->EE->load->library('form_validation');

		foreach ($purchase_id as $id)
		{
			$this->EE->form_validation->set_rules("txn_id[{$id}]", 'lang:txn_id', 'trim|required');
			$this->EE->form_validation->set_rules("item_id[{$id}]", 'lang:item_id', 'trim|required|numeric');
			$this->EE->form_validation->set_rules("screen_name[{$id}]", 'lang:screen_name', "trim|required|callback__valid_member[{$id}]");
			$this->EE->form_validation->set_rules("purchase_date[{$id}]", 'lang:purchase_date', "trim|required|callback__valid_date[{$id}]");
			$this->EE->form_validation->set_rules("subscription_end_date[{$id}]", 'lang:subscription_end_date', "trim|callback__valid_sub_date[{$id}]");
			$this->EE->form_validation->set_rules("item_cost[{$id}]", 'lang:item_cost', "trim|required|callback__valid_price[{$id}]");
			$this->EE->form_validation->set_rules("purchase_id[{$id}]", '', '');

		}

		$this->EE->form_validation->set_error_delimiters('<br /><span class="notice">', '</span>');
	}

	function _valid_member($str, $key)
	{
		$this->EE->db->select('member_id');
		$this->EE->db->where('screen_name', $str);
		$query = $this->EE->db->get('members');

		if ($query->num_rows() == 0)
		{
			$this->EE->form_validation->set_message('_valid_member', $this->lang->line('member_not_found'));
			return FALSE;
		}
		else
		{
			$str = $query->row('member_id');
		}

		$_POST['clean_screen_name'] = array($key => $str);
		return TRUE;
	}

	function _valid_date($str, $key)
	{
		$str = $this->EE->localize->convert_human_date_to_gmt($str);

		if ( ! is_numeric($str))
		{
			$this->EE->form_validation->set_message('_valid_date', $this->lang->line('invalid_date_formatting'));
			return FALSE;
		}

		$_POST['clean_purchase_date'] = array($key => $str);
		return TRUE;
	}

	function _valid_sub_date($str, $key)
	{
		$str = ($str == '') ? '' : $this->EE->localize->convert_human_date_to_gmt($str);

		if ( ! is_numeric($str) && $str != '' OR ($str < 0))
		{
			$this->EE->form_validation->set_message('_valid_date', $this->lang->line('invalid_date_formatting'));
			return FALSE;
		}

		$_POST['clean_subscription_end_date'] = array($key => $str);
		return TRUE;
	}

	function _valid_price($str, $key)
	{
		$str = str_replace('$', '', $str);

		if (preg_match('/[^0-9]/', str_replace('.', '', $str)))
		{
			$this->EE->form_validation->set_message('_valid_price', $this->lang->line('invalid_amount'));
			return FALSE;
		}

		$_POST['clean_item_price'] = array($key => $str);
		return TRUE;
	}


	/** -------------------------------------------
	/**  Modify Store Items - Add/Update
	/** -------------------------------------------*/

	function adding_purchase()	{ return $this->modify_purchases('y', array('0')); 	}
	function update_purchases()	{ return $this->modify_purchases('n');	}

	function modify_purchases($new = 'y', $purchase_ids = array())
	{
		if ( ! is_array($_POST['purchase_id']))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		//  Valid Purchases Selected?
		if ($new !== 'y')
		{
			$this->EE->db->select('purchase_id');
			$this->EE->db->where_in('purchase_id', $_POST['purchase_id']);
			$query = $this->EE->db->get('simple_commerce_purchases');

			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					$purchase_ids[$row['purchase_id']] = $row['purchase_id'];
				}
			}

			if (count($purchase_ids) == 0)
			{
				unset($_POST['purchase_id']);
				return $this->add_purchase($this->EE->lang->line('invalid_purchases'));
			}
		}

		$this->_purchases_validate($purchase_ids);

		if ($this->EE->form_validation->run() === FALSE)
		{
			return $this->_purchases_form($purchase_ids, $new);
		}

		foreach($purchase_ids as $id)
		{
			$data = array(
							'purchase_id'		=> $_POST['purchase_id'][$id],
							'txn_id'			=> $_POST['txn_id'][$id],
							'purchase_date'		=> $_POST['clean_purchase_date'][$id],
							'subscription_end_date' => $_POST['clean_subscription_end_date'][$id],
							'member_id'			=> $_POST['clean_screen_name'][$id],
							'item_id'			=> $_POST['item_id'][$id],
							'item_cost'			=> $_POST['clean_item_price'][$id],
							);

			//  Do our insert or update

			if ($new == 'y')
			{
				unset($data['purchase_id']);
				$this->EE->db->insert('simple_commerce_purchases', $data);

				$this->EE->db->query("UPDATE exp_simple_commerce_items SET item_purchases = item_purchases + 1 WHERE item_id = '".$data['item_id']."'");
				$this->EE->db->query("UPDATE exp_simple_commerce_items SET current_subscriptions = current_subscriptions + 1 WHERE item_id = '".$data['item_id']."'");

			}
			else
			{
				$this->EE->db->where('purchase_id', $id);
				$this->EE->db->update('simple_commerce_purchases', $data);
			}
		}

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('updated'));

		$this->EE->functions->redirect(
			BASE.AMP.'C=addons_modules'.AMP
			.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_purchases');

	}



	/** -------------------------------------------
	/**  Delete Purchases
	/** -------------------------------------------*/
	function delete_purchases()
	{
		if ($this->EE->input->post('purchase_ids') !== FALSE)
		{
			$purchase_ids = explode('|', $this->EE->input->post('purchase_ids'));
			
			$this->EE->db->select('item_id, purchase_id');
			$this->EE->db->where_in('purchase_id', $purchase_ids);
			$query = $this->EE->db->get('simple_commerce_purchases');

			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					$this->EE->db->where('purchase_id', $row['purchase_id']);
					$this->EE->db->delete('simple_commerce_purchases');

					// Get current count of purchases for the item
					$this->EE->db->where('item_id', $row['item_id']);
					$this->EE->db->from('simple_commerce_purchases');
					$count = $this->EE->db->count_all_results();

					// Update purchases count
					$this->EE->db->where('item_id', $row['item_id']);
					$this->EE->db->update('simple_commerce_items', array('item_purchases' => $count));

					// Get current count of live subscriptions the item
					$this->EE->db->where('item_id', $row['item_id']);
					$this->EE->db->where('subscription_end_date', 0);
					$this->EE->db->from('simple_commerce_purchases');
					$count = $this->EE->db->count_all_results();

					// Update current subscription count
					$this->EE->db->where('item_id', $row['item_id']);
					$this->EE->db->update('simple_commerce_items', array('current_subscriptions' => $count));
				}
			}

			$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('purchases_deleted'));
		}

		$this->EE->functions->redirect(
			BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'
			.AMP.'module=simple_commerce'.AMP.'method=edit_purchases');

	}



	/** -------------------------------------------
	/**  Edit Purchases
	/** -------------------------------------------*/
	function edit_purchases()
	{
		$this->EE->load->library('javascript');
		$this->EE->load->helper('form');

		$vars['purchases'] = array();
		$vars['form_hidden'] = NULL;


		//  Either Show Search Form or Process Entries
		if ($this->EE->input->post('toggle') !== FALSE OR $this->EE->input->get_post('purchase_id') !== FALSE)
		{
			$purchase_ids = array();

			if ($this->EE->input->get_post('purchase_id') !== FALSE)
			{
				$purchase_ids[] = $this->EE->db->escape_str($this->EE->input->get_post('purchase_id'));
			}
			else
			{
				foreach ($_POST['toggle'] as $key => $val)
				{
					if ($val != '' && is_numeric($val))
					{
						$purchase_ids[] = $val;
					}
				}
			}

			if ($this->EE->input->get_post('action') == 'delete')
			{
				return $this->_delete_confirmation_forms(
												array(
														'method'	=> 'delete_purchases',
														'heading'	=> 'delete_purchases_confirm',
														'message'	=> 'delete_purchases_confirm',
														'hidden'	=> array('purchase_ids' => implode('|', $purchase_ids))
													)
												);
			}
			else
			{
				//  Finally!  We can do something!
				return $this->_purchases_form($purchase_ids, 'n');
			}
		}
		else
		{
			$this->EE->load->library('table');
			$vars['cp_page_title']  = $this->EE->lang->line('edit_purchases');
			$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'
			.AMP.'module=simple_commerce', $this->EE->lang->line('simple_commerce_module_name'));

			// Add javascript
			$this->EE->cp->add_js_script(array('plugin' => 'dataTables'));

			$this->EE->javascript->output($this->ajax_filters('edit_purchases_ajax_filter', 6));

			$this->EE->javascript->output(array(
					'$(".toggle_all").toggle(
						function(){
							$("input.toggle").each(function() {
								this.checked = true;
							});
						}, function (){
							var checked_status = this.checked;
							$("input.toggle").each(function() {
								this.checked = false;
							});
						}
					);'
				)
			);


			$this->EE->javascript->compile();

			//  Check for pagination

			$vars['show_add_button'] = TRUE;
			$vars['items'] = array();
			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_purchases';

			$total = $this->EE->db->count_all('simple_commerce_purchases');

			if ($total == 0)
			{
				return $this->EE->load->view('edit_purchases', $vars, TRUE);
			}

			if ( ! $rownum = $this->EE->input->get_post('rownum'))
			{
				$rownum = 0;
			}

			$query = $this->EE->db->query("SELECT scp.*, m.screen_name, wt.title, recurring
								 FROM exp_simple_commerce_purchases scp, exp_simple_commerce_items sci, exp_members m, exp_channel_titles wt
								 WHERE scp.item_id = sci.item_id
								 AND sci.entry_id = wt.entry_id
								 AND scp.member_id = m.member_id
								 ORDER BY scp.purchase_date desc LIMIT $rownum, $this->perpage");


/*

			$this->EE->db->select('scp.*, m.screen_name, wt.title');
			$this->EE->db->order_by("scp.purchase_date", "desc");
			$this->EE->db->limit($this->perpage, $rownum);
			$query = $this->EE->db->get('simple_commerce_emails');
*/


			$i = 0;
			$recurring = $this->EE->lang->line('recurring');

			foreach($query->result_array() as $row)
			{
   				$vars['purchases'][$row['purchase_id']]['entry_title'] = $row['title'];
   				$vars['purchases'][$row['purchase_id']]['edit_link'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_purchases'.AMP.'purchase_id='.$row['purchase_id'];

   				$vars['purchases'][$row['purchase_id']]['item_cost'] = $row['item_cost'];
   				$vars['purchases'][$row['purchase_id']]['purchaser_screen_name'] = $row['screen_name'];
   				$vars['purchases'][$row['purchase_id']]['date_purchased'] = $this->EE->localize->set_human_time($row['purchase_date']);
   				if ($row['recurring'] == 'y')
				{
					$vars['purchases'][$row['purchase_id']]['subscription_end_date'] = $recurring;
				}
				else
				{
					$vars['purchases'][$row['purchase_id']]['subscription_end_date'] = ($row['subscription_end_date'] == 0) ? ' -- ' : $this->EE->localize->set_human_time($row['subscription_end_date']);
				}

				// Toggle checkbox
				$vars['purchases'][$row['purchase_id']]['toggle'] = array(
																			'name'		=> 'toggle[]',
																			'id'		=> 'edit_box_'.$row['purchase_id'],
																			'value'		=> $row['purchase_id'],
																			'class'		=>'toggle'
																	);


			}

			// Pass the relevant data to the paginate class so it can display the "next page" links
			$this->EE->load->library('pagination');
			$p_config = $this->pagination_config('edit_purchases', $total);

			$this->EE->pagination->initialize($p_config);

			$vars['pagination'] = $this->EE->pagination->create_links();

			return $this->EE->load->view('edit_purchases', $vars, TRUE);
		}
	}

	function edit_purchases_ajax_filter()
	{
		$this->EE->output->enable_profiler(FALSE);

		$col_map = array('title', 'screen_name', 'purchase_date', 'subscription_end_date', 'item_cost');

		$id = ($this->EE->input->get_post('id')) ? $this->EE->input->get_post('id') : '';


		// Note- we pipeline the js, so pull more data than are displayed on the page
		$perpage = $this->EE->input->get_post('iDisplayLength');
		$offset = ($this->EE->input->get_post('iDisplayStart')) ? $this->EE->input->get_post('iDisplayStart') : 0; // Display start point
		$sEcho = $this->EE->input->get_post('sEcho');


		/* Ordering */
		$order = array();
		
		if ($this->EE->input->get('iSortCol_0') !== FALSE)
		{
			for ( $i=0; $i < $this->EE->input->get('iSortingCols'); $i++ )
			{
				if (isset($col_map[$this->EE->input->get('iSortCol_'.$i)]))
				{
					$order[$col_map[$this->EE->input->get('iSortCol_'.$i)]] = ($this->EE->input->get('sSortDir_'.$i) == 'asc') ? 'asc' : 'desc';
				}
			}
		}
		
		$total = $this->EE->db->count_all('simple_commerce_emails');

		$j_response['sEcho'] = $sEcho;
		$j_response['iTotalRecords'] = $total;
		$j_response['iTotalDisplayRecords'] = $total;

		$tdata = array();
		$i = 0;

		$this->EE->db->select('email_id, email_name');

		if (count($order) > 0)
		{
			foreach ($order as $k => $v)
			{
				$order_by[] = $k.' '.$v;
			}

			$order_by = implode(',', $order_by);
		}
		else
		{
			$order_by = 'scp.purchase_date desc';
		}

		$query = $this->EE->db->query("SELECT scp.*, m.screen_name, wt.title, recurring
								 FROM exp_simple_commerce_purchases scp, exp_simple_commerce_items sci, exp_members m, exp_channel_titles wt
								 WHERE scp.item_id = sci.item_id
								 AND sci.entry_id = wt.entry_id
								 AND scp.member_id = m.member_id
								 ORDER BY {$order_by} LIMIT $offset, $perpage");


		$i = 0;

		$recurring = $this->EE->lang->line('recurring');

		foreach ($query->result_array() as $purchase)
		{
			if ($purchase['recurring'] == 'y')
			{
				$subscription_end_date = $recurring;
			}
			else
			{
				$subscription_end_date = ($purchase['subscription_end_date'] == 0) ? ' -- ' : $this->EE->localize->set_human_time($purchase['subscription_end_date']);
			}

			$m[] = '<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.
			AMP.'method=edit_purchases'.AMP.'purchase_id='.$purchase['purchase_id'].'">'.$purchase['title'].'</a>';
			$m[] = $purchase['screen_name'];
			$m[] = $this->EE->localize->set_human_time($purchase['purchase_date']);
			$m[] = $subscription_end_date;
			$m[] = $purchase['item_cost'];
			$m[] = '<input class="toggle" id="edit_box_'.$purchase['purchase_id'].'" type="checkbox" name="toggle[]" value="'.$purchase['purchase_id'].'" />';

			$tdata[$i] = $m;
			$i++;
			unset($m);
		}

		$j_response['aaData'] = $tdata;
		$sOutput = $this->EE->javascript->generate_json($j_response, TRUE);

		die($sOutput);
	}

	/** -------------------------------------------
	/**  Export Functions
	/** -------------------------------------------*/

	function export_purchases() { $this->export('purchases'); }
	function export_items() 	{ $this->export('items'); }

	function export($which='purchases')
	{

		$this->EE->load->helper('download');

		$tab  = ($this->export_type == 'csv') ? ',' : "\t";
		$cr	  = "\n";
		$data = '';

		$now = $this->EE->localize->set_localized_time();

        $filename = $which.'_'.date('y', $now).date('m', $now).date('d', $now).'.txt';

		if ($which == 'items')
		{
			$query = $this->EE->db->query("SELECT wt.title as item_name, sc.* FROM exp_simple_commerce_items sc, exp_channel_titles wt
								 WHERE sc.entry_id = wt.entry_id
								 ORDER BY item_name");
		}
		else
		{
			$query = $this->EE->db->query("SELECT wt.title AS item_purchased, m.screen_name AS purchaser, scp.*
								 FROM exp_simple_commerce_purchases scp, exp_simple_commerce_items sci, exp_members m, exp_channel_titles wt
								 WHERE scp.item_id = sci.item_id
								 AND sci.entry_id = wt.entry_id
								 AND scp.member_id = m.member_id
								 ORDER BY scp.purchase_date desc");
		}

		if ($query->num_rows() > 0)
		{
			foreach($query->row_array() as $key => $value)
			{
				if ($key == 'paypal_details') continue;

				$data .= $key.$tab;
			}

			$data = trim($data).$cr; // Remove end tab and add carriage

			foreach($query->result_array() as $row)
			{
				$datum = '';

				foreach($row as $key => $value)
				{
					$datum .= $value.$tab;
				}

				$data .= trim($datum).$cr;
			}
		}
		else
		{
			$data = 'No data';
		}

     force_download($filename, $data);


	}


	function pagination_config($method, $total_rows)
	{
		// Pass the relevant data to the paginate class
		$config['base_url'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method='.$method;
		$config['total_rows'] = $total_rows;
		$config['per_page'] = $this->perpage;
		$config['page_query_string'] = TRUE;
		$config['query_string_segment'] = 'rownum';
		$config['full_tag_open'] = '<p id="paginationLinks">';
		$config['full_tag_close'] = '</p>';
		$config['prev_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />';
		$config['next_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />';
		$config['first_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />';
		$config['last_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />';

		return $config;

	}

	function ajax_filters($ajax_method = '', $cols = '')
	{
		if ($ajax_method == '')
		{
			return;
		}

		$col_defs = '';
		if ($cols != '')
		{
			$col_defs .= '"aoColumns": [ ';
			$i = 1;

			while ($i < $cols)
			{
				$col_defs .= 'null, ';
				$i++;
			}

			$col_defs .= '{ "bSortable" : false } ],';
		}

		$js = '
var oCache = {
	iCacheLower: -1
};

function fnSetKey( aoData, sKey, mValue )
{
	for ( var i=0, iLen=aoData.length ; i<iLen ; i++ )
	{
		if ( aoData[i].name == sKey )
		{
			aoData[i].value = mValue;
		}
	}
}

function fnGetKey( aoData, sKey )
{
	for ( var i=0, iLen=aoData.length ; i<iLen ; i++ )
	{
		if ( aoData[i].name == sKey )
		{
			return aoData[i].value;
		}
	}
	return null;
}

function fnDataTablesPipeline ( sSource, aoData, fnCallback ) {
	var iPipe = '.$this->pipe_length.';  /* Ajust the pipe size */

	var bNeedServer = false;
	var sEcho = fnGetKey(aoData, "sEcho");
	var iRequestStart = fnGetKey(aoData, "iDisplayStart");
	var iRequestLength = fnGetKey(aoData, "iDisplayLength");
	var iRequestEnd = iRequestStart + iRequestLength;
	oCache.iDisplayStart = iRequestStart;

	/* outside pipeline? */
	if ( oCache.iCacheLower < 0 || iRequestStart < oCache.iCacheLower || iRequestEnd > oCache.iCacheUpper )
	{
		bNeedServer = true;
	}

	/* sorting etc changed? */
	if ( oCache.lastRequest && !bNeedServer )
	{
		for( var i=0, iLen=aoData.length ; i<iLen ; i++ )
		{
			if ( aoData[i].name != "iDisplayStart" && aoData[i].name != "iDisplayLength" && aoData[i].name != "sEcho" )
			{
				if ( aoData[i].value != oCache.lastRequest[i].value )
				{
					bNeedServer = true;
					break;
				}
			}
		}
	}

	/* Store the request for checking next time around */
	oCache.lastRequest = aoData.slice();

	if ( bNeedServer )
	{
		if ( iRequestStart < oCache.iCacheLower )
		{
			iRequestStart = iRequestStart - (iRequestLength*(iPipe-1));
			if ( iRequestStart < 0 )
			{
				iRequestStart = 0;
			}
		}

		oCache.iCacheLower = iRequestStart;
		oCache.iCacheUpper = iRequestStart + (iRequestLength * iPipe);
		oCache.iDisplayLength = fnGetKey( aoData, "iDisplayLength" );
		fnSetKey( aoData, "iDisplayStart", iRequestStart );
		fnSetKey( aoData, "iDisplayLength", iRequestLength*iPipe );

		$.getJSON( sSource, aoData, function (json) {
			/* Callback processing */
			oCache.lastJson = jQuery.extend(true, {}, json);

			if ( oCache.iCacheLower != oCache.iDisplayStart )
			{
				json.aaData.splice( 0, oCache.iDisplayStart-oCache.iCacheLower );
			}
			json.aaData.splice( oCache.iDisplayLength, json.aaData.length );

			fnCallback(json)
		} );
	}
	else
	{
		json = jQuery.extend(true, {}, oCache.lastJson);
		json.sEcho = sEcho; /* Update the echo for each response */
		json.aaData.splice( 0, iRequestStart-oCache.iCacheLower );
		json.aaData.splice( iRequestLength, json.aaData.length );
		fnCallback(json);
		return;
	}
}

	var time = new Date().getTime();

	oTable = $(".mainTable").dataTable( {
			"sPaginationType": "full_numbers",
			"bLengthChange": false,
			"bFilter": false,
			"sWrapper": false,
			"sInfo": false,
			"bAutoWidth": false,
			"iDisplayLength": '.$this->perpage.',

			'.$col_defs.'

		"oLanguage": {
			"sZeroRecords": "'.$this->EE->lang->line('invalid_entries').'",

			"oPaginate": {
				"sFirst": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sPrevious": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sNext": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sLast": "<img src=\"'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />"
			}
		},

			"bProcessing": true,
			"bServerSide": true,
			"sAjaxSource": EE.BASE+"&C=addons_modules&M=show_module_cp&module=simple_commerce&method='.$ajax_method.'&time=" + time,
			"fnServerData": fnDataTablesPipeline

	} );';

		return $js;

	}

	// --------------------------------------------
	//	 JavaScript filtering code
	//
	// This function writes some JavaScript functions that
	// are used to switch the various pull-down menus in the
	// EDIT page
	//--------------------------------------------
	function filtering_menus()
	{
		// In order to build our filtering options we need to gather
		// all the channels, categories and custom statuses

		$channel_array	= array();
		$status_array = array();

		$this->EE->api->instantiate('channel_categories');

		$allowed_channels = $this->EE->functions->fetch_assigned_channels(TRUE);

		if (count($allowed_channels) > 0)
		{
			// Fetch channel titles
			$this->EE->db->select('channel_title, channel_id, cat_group, status_group, field_group');
			$this->EE->db->where_in('channel_id', $allowed_channels);
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));

			$this->EE->db->order_by('channel_title');
			$query = $this->EE->db->get('channels');

			foreach ($query->result_array() as $row)
			{
				$channel_array[$row['channel_id']] = array(str_replace('"','',$row['channel_title']), $row['cat_group'], $row['status_group'], $row['field_group']);
			}
		}

		/** -----------------------------
		/**  Category Tree
		/** -----------------------------*/

		$order = ($this->nest_categories == 'y') ? 'group_id, parent_id, cat_name' : 'cat_name';

		$this->EE->db->select('categories.group_id, categories.parent_id, categories.cat_id, categories.cat_name');
		$this->EE->db->from('categories');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->order_by($order);

		$query = $this->EE->db->get();

		// Load the text helper
		$this->EE->load->helper('text');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$categories[] = array($row['group_id'], $row['cat_id'], entities_to_ascii($row['cat_name']), $row['parent_id']);
			}

			if ($this->nest_categories == 'y')
			{
				foreach($categories as $key => $val)
				{
					if (0 == $val['3'])
					{
						$this->EE->api_channel_categories->cat_array[] = array($val['0'], $val['1'], $val['2']);
						$this->EE->api_channel_categories->category_edit_subtree($val['1'], $categories, $depth=1);
					}
				}
			}
			else
			{
				$this->EE->api_channel_categories->cat_array = $categories;
			}
		}

		/** -----------------------------
		/**  Entry Statuses
		/** -----------------------------*/

		$this->EE->db->select('group_id, status');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->order_by('status_order');
		$query = $this->EE->db->get('statuses');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$status_array[]  = array($row['group_id'], $row['status']);
			}
		}

		$default_cats[] = array('', $this->EE->lang->line('filter_by_category'));
		$default_cats[] = array('all', $this->EE->lang->line('all'));
		$default_cats[] = array('none', $this->EE->lang->line('none'));

		$dstatuses[] = array('', $this->EE->lang->line('filter_by_status'));
		$dstatuses[] = array('open', $this->EE->lang->line('open'));
		$dstatuses[] = array('closed', $this->EE->lang->line('closed'));

		$channel_info['0']['categories'] = $default_cats;
		$channel_info['0']['statuses'] = $dstatuses;

		foreach ($channel_array as $key => $val)
		{
			$any = 0;
			$cats = $default_cats;

			if (count($this->EE->api_channel_categories->cat_array) > 0)
			{
				$last_group = 0;

				foreach ($this->EE->api_channel_categories->cat_array as $k => $v)
				{
					if (in_array($v['0'], explode('|', $val['1'])))
					{
						if ($last_group == 0 OR $last_group != $v['0'])
						{
							$cats[] = array('', '-------');
							$last_group = $v['0'];
						}

						$cats[] = array($v['1'], $v['2']);
					}
				}
			}

			$channel_info[$key]['categories'] = $cats;

			$statuses = array();
			$statuses[] = array('', $this->EE->lang->line('filter_by_status'));

			if (count($status_array) > 0)
			{
				foreach ($status_array as $k => $v)
				{
					if ($v['0'] == $val['2'])
					{
						$status_name = ($v['1'] == 'closed' OR $v['1'] == 'open') ?  $this->EE->lang->line($v['1']) : $v['1'];
						$statuses[] = array($v['1'], $status_name);
					}
				}
			}
			else
			{
				$statuses[] = array($v['1'], $this->EE->lang->line('open'));
				$statuses[] = array($v['1'], $this->EE->lang->line('closed'));
			}

			$channel_info[$key]['statuses'] = $statuses;
		}

		$channel_info = $this->EE->javascript->generate_json($channel_info, TRUE);

		$javascript = <<<MAGIC

// The oracle knows everything.

var channel_oracle = $channel_info;
var spaceString = new RegExp('!-!', "g");

// We prep our magic arrays as soons as we can, basically
// converting everything into option elements
(function() {
	jQuery.each(channel_oracle, function(key, details) {

		// Go through each of the individual settings and build a proper dom element
		jQuery.each(details, function(group, values) {
			var newval = new Array();

			// Add the new option fields
			jQuery.each(values, function(a, b) {
				newval.push(new Option(b[1].replace(spaceString, String.fromCharCode(160)), b[0]));
			});

			// Set the new values
			channel_oracle[key][group] = $(newval);
		});
	});

})();


// Change the submenus
// Gets passed the channel id
function changemenu(index)
{
	var channels = 'null';

	if (channel_oracle[index] === undefined) {
		index = 0;
	}
	jQuery.each(channel_oracle[index], function(key, val) {
		switch(key) {
			case 'categories':	$('select[name=cat_id]').empty().append(val);
				break;
			case 'statuses':	$('select[name=status]').empty().append(val);
				break;
		}
	});
}

$('select[name=channel_id]').change(function() {
	changemenu(this.value);
});
MAGIC;
		$this->EE->javascript->output($javascript);
	}



	function add_items($channel_id = '', $message = '', $extra_sql = '', $search_url = '', $form_url = '', $action = '', $extra_fields_search='', $extra_fields_entries='', $heading='')
	{
		$this->EE->lang->loadfile('content');
		$this->EE->load->helper('url');

		$channel_id = '';
		$extra_sql = array();


		$this->EE->db->select('entry_id');

		$query = $this->EE->db->get('simple_commerce_items');

        if ($query->num_rows() > 0)
        {
        	$extra_sql['where'] = " AND exp_channel_titles.entry_id NOT IN ('";

        	foreach($query->result_array() as $row) $extra_sql['where'] .= $row['entry_id']."','";

        	$extra_sql['where'] = substr($extra_sql['where'], 0, -2).') ';
        }

		$this->EE->load->library('api');

		// $action, $extra_fields_*, and $heading are used by move_comments
		$vars['message'] = $message;
		$action = $action ? $action : $this->EE->input->get_post('action');

		// Security check
		if ( ! $this->EE->cp->allowed_group('can_access_edit'))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$this->EE->load->library('pagination');
		$this->EE->load->library('table');
		$this->EE->load->helper(array('form', 'text', 'url', 'snippets'));
		$this->EE->api->instantiate('channel_categories');

		$this->EE->load->model('channel_model');
		$this->EE->load->model('channel_entries_model');
		$this->EE->load->model('category_model');
		$this->EE->load->model('status_model');

		// Load the search helper so we can filter the keywords
		$this->EE->load->helper('search');

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('edit'));

		$this->EE->cp->add_js_script('ui', 'datepicker');

		$this->EE->javascript->output(array(
			$this->EE->javascript->hide(".paginationLinks .first"),
			$this->EE->javascript->hide(".paginationLinks .previous")
		));

		$this->EE->javascript->output('
			$(".toggle_all").toggle(
				function(){
					$("input.toggle").each(function() {
						this.checked = true;
					});
				}, function (){
					var checked_status = this.checked;
					$("input.toggle").each(function() {
						this.checked = false;
					});
				}
			);
		');

		$this->EE->jquery->tablesorter('.mainTable', '{
			headers: {
			2: {sorter: false},
			3: {
				// BLARG!!! This should be human readable sorted...
			},
			5: {dateFormat: "mm/dd/yy"},
			8: {sorter: false}
		},
			widgets: ["zebra"]
		}');


		$this->EE->javascript->output('
			$("#custom_date_start_span").datepicker({
				dateFormat: "yy-mm-dd",
				prevText: "<<",
				nextText: ">>",
				onSelect: function(date) {
					$("#custom_date_start").val(date);
					dates_picked();
				}
			});
			$("#custom_date_end_span").datepicker({
				dateFormat: "yy-mm-dd",
				prevText: "<<",
				nextText: ">>",
				onSelect: function(date) {
					$("#custom_date_end").val(date);
					dates_picked();
				}
			});

			$("#custom_date_start, #custom_date_end").focus(function(){
				if ($(this).val() == "yyyy-mm-dd")
				{
					$(this).val("");
				}
			});

			$("#custom_date_start, #custom_date_end").keypress(function(){
				if ($(this).val().length >= 9)
				{
					dates_picked();
				}
			});

			function dates_picked()
			{
				if ($("#custom_date_start").val() != "yyyy-mm-dd" && $("#custom_date_end").val() != "yyyy-mm-dd")
				{
					// populate dropdown box
					focus_number = $("#date_range").children().length;
					$("#date_range").append("<option id=\"custom_date_option\">" + $("#custom_date_start").val() + " to " + $("#custom_date_end").val() + "</option>");
					document.getElementById("date_range").options[focus_number].selected=true;

					// hide custom date picker again
					$("#custom_date_picker").slideUp("fast");
				}
			}
		');

		$this->EE->javascript->change("#date_range", "
			if ($('#date_range').val() == 'custom_date')
			{
				// clear any current dates, remove any custom options
				$('#custom_date_start').val('yyyy-mm-dd');
				$('#custom_date_end').val('yyyy-mm-dd');
				$('#custom_date_option').remove();

				// drop it down
				$('#custom_date_picker').slideDown('fast');
			}
			else
			{
				$('#custom_date_picker').hide();
			}
		");

		$this->EE->javascript->output('
		$(".paginationLinks a.page").click(function() {
			current_rownum = $("#perpage").val()*$(this).text()-$("#perpage").val();
			current_perpage = $("#perpage").val();

			$.getJSON("' . BASE.'&C=javascript&M=json&perpage="+$("#perpage").val()+"&rownum="+($("#perpage").val()*$(this).text()-$("#perpage").val())' . ', {ajax: "true"}, doPagination);
			return false;
		});

		var current_rownum = 0;
		var current_perpage = 20;
		var total_entries = 60; // needs to be set via PHP
		var next_page = current_perpage;

		function doPagination(e){
			var entries = "";
			for (var i = 0; i < e.length; i++) {
				entries += "<tr>";
				entries += "<td>" + e[i].id + "</td>";
				entries += "<td><a href=\"#\">" + e[i].title + "</a></td>";
				entries += "<td><a href=\"#\">Live Look</a></td>";
				entries += "<td>(" + e[i].comment_count + ")&nbsp;&nbsp;&nbsp;<a href=\"#\">View</a></td>";
				entries += "<td><div class=\'smallLinks\'><a href=\"mailto:" + e[i].author_email + "\">" + e[i].author + "</a></div></td>";
				entries += "<td>" + e[i].entry_date + "</td>";
				entries += "<td>" + e[i].channel_name + "</td>";

				if (e[i].status == "Open")
				{
					entries += "<td><span style=\"color:#339900;\">" + e[i].status + "</span></td>";
				}
				else
				{
					entries += "<td><span style=\"color:#cc0000;\">" + e[i].status + "</span></td>";
				}

				entries += "<td><input class=\'checkbox\' type=\'checkbox\' name=\'toggle[]\' value=\'" + e[i].id + "\' /></td>";
				entries += "</tr>";
			}

			$(".mainTable tbody").html(entries);
			$(".mainTable").trigger("update");
			var current_sort = $(".mainTable").get(0).config.sortList;
			$(".mainTable").trigger("sorton", [current_sort]);

			// add or remove first and last links
			(current_rownum >= current_perpage) ? $(".paginationLinks .first").show() : $(".paginationLinks .first").hide() ;
			(current_rownum >= current_perpage) ? $(".paginationLinks .previous").show() : $(".paginationLinks .previous").hide() ;
			(current_rownum >= (total_entries - current_perpage)) ? $(".paginationLinks .last").hide() : $(".paginationLinks .last").show() ;
			(current_rownum >= (total_entries - current_perpage)) ? $(".paginationLinks .next").hide() : $(".paginationLinks .next").show() ;
			// readjust page numbers for links
		}

		$(".paginationLinks .first").click(function() {
			current_perpage = $("#perpage").val();
			current_rownum = 0;
			$.getJSON("'.BASE.'&C=javascript&M=json&per_page="+current_perpage+"&rownum="+current_rownum, {ajax: "true"}, doPagination);
			return false;
		});

		$(".paginationLinks .previous").click(function() {
			current_perpage = $("#perpage").val();
			current_rownum = Number(current_rownum) - Number($("#perpage").val());
			$.getJSON("'.BASE.'&C=javascript&M=json&per_page="+current_perpage+"&rownum="+current_rownum, {ajax: "true"}, doPagination);
			return false;
		});

		$(".paginationLinks .next").click(function() {
			current_perpage = $("#perpage").val();
			current_rownum = Number(current_rownum) + Number($("#perpage").val());
			$.getJSON("'.BASE.'&C=javascript&M=json&per_page="+current_perpage+"&rownum="+current_rownum, {ajax: "true"}, doPagination);
			return false;
		});

		$(".paginationLinks .last").click(function() {
			current_perpage = $("#perpage").val();
			current_rownum = total_entries;
			$.getJSON("'.BASE.'&C=edit&M=json_entries&per_page="+current_perpage+"&rownum="+current_rownum, {ajax: "true"}, doPagination);
			return false;
		});

	');

		$cp_theme  = ( ! $this->EE->session->userdata('cp_theme')) ? $this->EE->config->item('cp_theme') : $this->EE->session->userdata('cp_theme');
		$turn_on_robot = FALSE;

		// Fetch channel ID numbers assigned to the current user
		$allowed_channels = $this->EE->functions->fetch_assigned_channels();

		if (empty($allowed_channels))
		{
			show_error($this->EE->lang->line('no_channels'));
		}

		//  Fetch Color Library - We use this to assist with our status colors
		if (file_exists(APPPATH.'config/colors'.EXT))
		{
			include (APPPATH.'config/colors'.EXT);
		}
		else
		{
			$colors = '';
		}

		// We need to determine which channel to show entries from
		// if the channel_id combined
		if ($channel_id == '')
		{
			$channel_id = $this->EE->input->get_post('channel_id');
		}

		if ($channel_id == 'null' OR $channel_id === FALSE OR ! is_numeric($channel_id))
		{
			$channel_id = '';
		}

		$cat_group = '';
		$cat_id = $this->EE->input->get_post('cat_id');

		$status = $this->EE->input->get_post('status');
		$order	= $this->EE->input->get_post('order');
		$date_range = $this->EE->input->get_post('date_range');
		$total_channels = count($allowed_channels);

		// If we have more than one channel we'll write the JavaScript menu switching code
		if ($total_channels > 1)
		{
			$this->EE->javascript->output($this->filtering_menus());
		}

		// Do we have a message to show?
		// Note: a message is displayed on this page after editing or submitting a new entry

		if ($this->EE->input->get_post("U") == 'mu')
		{
			$vars['message'] = $this->EE->lang->line('multi_entries_updated');
		}

		// Declare the "filtering" form

		$vars['search_form'] = ($search_url != '') ? $search_url : 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=add_items';

		// If we have more than one channel we'll add the "onchange" method to
		// the form so that it'll automatically switch categories and statuses
		if ($total_channels > 1)
		{
			$vars['channel_select']['onchange'] = 'changemenu(this.selectedIndex);';
		}

		// Design note:	 Because the JavaScript code dynamically switches the information inside the
		// pull-down menus we can't show any particular menu in a "selected" state unless there is only
		// one channel.	 Each channel is fully independent, so it can have its own categories, statuses, etc.

		// Channel selection pull-down menu
		// Fetch the names of all channels and write each one in an <option> field

		$fields = array('channel_title', 'channel_id', 'cat_group');
		$where = array();

		// If the user is restricted to specific channels, add that to the query

		if ($this->EE->session->userdata['group_id'] != 1)
		{
			$where[] = array('channel_id' => $allowed_channels);
		}

		$query = $this->EE->channel_model->get_channels($this->EE->config->item('site_id'), $fields, $where);

		if ($query->num_rows() == 1)
		{
			$channel_id = $query->row('channel_id');
			$cat_group = $query->row('cat_group');
		}
		elseif($channel_id != '')
		{
			foreach($query->result_array() as $row)
			{
				if ($row['channel_id'] == $channel_id)
				{
					$channel_id = $row['channel_id'];
					$cat_group = $row['cat_group'];
				}
			}
		}

		$vars['channel_selected'] = $this->EE->input->get_post('channel_id');

		$vars['channel_select_options'] = array('null' => $this->EE->lang->line('filter_by_channel'));

		if ($query->num_rows() > 1)
		{
			$vars['channel_select_options']['all'] = $this->EE->lang->line('all');
		}

		foreach ($query->result_array() as $row)
		{
			$vars['channel_select_options'][$row['channel_id']] = $row['channel_title'];
		}

		// Category pull-down menu
		$vars['category_selected'] = $cat_id;

		$vars['category_select_options'][''] = $this->EE->lang->line('filter_by_category');

		if ($total_channels > 1)
		{
			$vars['category_select_options']['all'] = $this->EE->lang->line('all');
		}

		$vars['category_select_options']['none'] = $this->EE->lang->line('none');

		if ($cat_group != '')
		{
			foreach($this->EE->api_channel_categories->cat_array as $key => $val)
			{
				if ( ! in_array($val['0'], explode('|',$cat_group)))
				{
					unset($this->EE->api_channel_categories->cat_array[$key]);
				}
			}

			$i=1;
			$new_array = array();

			foreach ($this->EE->api_channel_categories->cat_array as $ckey => $cat)
			{
		    	if ($ckey-1 < 0 OR ! isset($this->EE->api_channel_categories->cat_array[$ckey-1]))
    		   	{
					$vars['category_select_options']['NULL_'.$i] = '-------';
            	}

				$vars['category_select_options'][$cat['1']] = (str_replace("!-!","&nbsp;", $cat['2']));

            	if (isset($this->EE->api_channel_categories->cat_array[$ckey+1]) && $this->EE->api_channel_categories->cat_array[$ckey+1]['0'] != $cat['0'])
	        	{
					$vars['category_select_options']['NULL_'.$i] = '-------';
       			}

       			$i++;
			}
		}

		// Authors list
		$vars['author_selected'] = $this->EE->input->get_post('author_id');

		$query = $this->EE->member_model->get_authors();
		$vars['author_select_options'][''] = $this->EE->lang->line('filter_by_author');

		foreach ($query->result_array() as $row)
		{
			$vars['author_select_options'][$row['member_id']] = ($row['screen_name'] == '') ? $row['username'] : $row['screen_name'];
		}

		// Status pull-down menu
		$vars['status_selected'] = $status;

		$vars['status_select_options'][''] = $this->EE->lang->line('filter_by_status');
		$vars['status_select_options']['all'] = $this->EE->lang->line('all');

		$sel_1 = '';
		$sel_2 = '';

		if ($cat_group != '')
		{
			  $sel_1 = ($status == 'open')	? 1 : '';
			  $sel_2 = ($status == 'closed') ? 1 : '';
		}

		if ($cat_group != '')
		{
			$rez = $this->EE->db->query("SELECT status_group FROM exp_channels WHERE channel_id = '$channel_id'");

			$query = $this->EE->db->query("SELECT status FROM exp_statuses WHERE group_id = '".$this->EE->db->escape_str($rez->row('status_group') )."' ORDER BY status_order");

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$status_name = ($row['status'] == 'closed' OR $row['status'] == 'open') ?  $this->EE->lang->line($row['status']) : $row['status'];
					$vars['status_select_options'][$row['status']] = $status_name;
				}
			}
		}
		else
		{
			 $vars['status_select_options']['open'] = $this->EE->lang->line('open');
			 $vars['status_select_options']['closed'] = $this->EE->lang->line('closed');
		}

		// Date range pull-down menu
		$vars['date_selected'] = $date_range;

		$vars['date_select_options'][''] = $this->EE->lang->line('date_range');
		$vars['date_select_options']['1'] = $this->EE->lang->line('today');
		$vars['date_select_options']['7'] = $this->EE->lang->line('past_week');
		$vars['date_select_options']['31'] = $this->EE->lang->line('past_month');
		$vars['date_select_options']['182'] = $this->EE->lang->line('past_six_months');
		$vars['date_select_options']['365'] = $this->EE->lang->line('past_year');
		$vars['date_select_options']['custom_date'] = $this->EE->lang->line('any_date');

		// Display order pull-down menu
		$vars['order_selected'] = $order;

		$vars['order_select_options'][''] = $this->EE->lang->line('order');
		$vars['order_select_options']['asc'] = $this->EE->lang->line('ascending');
		$vars['order_select_options']['desc'] = $this->EE->lang->line('descending');
		$vars['order_select_options']['alpha'] = $this->EE->lang->line('alpha');

		// Results per page pull-down menu
		if ( ! ($perpage = $this->EE->input->get_post('perpage')))
		{
			$perpage = $this->EE->input->cookie('perpage');
		}

		if ($perpage == ''){
			$perpage = 50;
		}

		$this->EE->functions->set_cookie('perpage' , $perpage, 60*60*24*182);

		$vars['perpage_selected'] = $perpage;

		$vars['perpage_select_options']['10'] = '10 '.$this->EE->lang->line('results');
		$vars['perpage_select_options']['25'] = '25 '.$this->EE->lang->line('results');
		$vars['perpage_select_options']['50'] = '50 '.$this->EE->lang->line('results');
		$vars['perpage_select_options']['75'] = '75 '.$this->EE->lang->line('results');
		$vars['perpage_select_options']['100'] = '100 '.$this->EE->lang->line('results');
		$vars['perpage_select_options']['150'] = '150 '.$this->EE->lang->line('results');

		if (isset($_POST['keywords']))
		{
			$keywords = sanitize_search_terms($_POST['keywords']);
		}
		elseif (isset($_GET['keywords']))
		{
			$keywords = sanitize_search_terms(base64_decode($_GET['keywords']));
		}
		else
		{
			$keywords = '';
		}

		if (substr(strtolower($keywords), 0, 3) == 'ip:')
		{
			$keywords = str_replace('_','.',$keywords);
		}

		// Because of the auto convert we prepare a specific variable with the converted ascii
		// characters while leaving the $keywords variable intact for display and URL purposes
		$search_keywords = ($this->EE->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities($keywords) : $keywords;

		$vars['exact_match'] = $this->EE->input->get_post('exact_match');

		$vars['keywords'] = array(
									'name' 		=> 'keywords',
									'value'		=> stripslashes($keywords),
									'id'		=> 'keywords',
									'maxlength'	=> 200
								);

		$search_in = ($this->EE->input->get_post('search_in') != '') ? $this->EE->input->get_post('search_in') : 'title';

		$vars['search_in_selected'] = $search_in;

		$vars['search_in_options']['title'] =  $this->EE->lang->line('title_only');
		$vars['search_in_options']['body'] =  $this->EE->lang->line('title_and_body');

		if (isset($this->EE->installed_modules['comment']))
		{
			$vars['search_in_options']['everywhere'] =  $this->EE->lang->line('title_body_comments');
			$vars['search_in_options']['comments'] =  $this->lang->line('comments');
		}


		//	 Build the main query

		if ($search_url != '')
		{
			$pageurl = BASE.AMP.$search_url;
		}
		else
		{
			$pageurl = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=add_items';
		}

		$sql_a = "SELECT ";

		if ($search_in == 'comments')
		{
			$sql_b = "DISTINCT(exp_comments.comment_id) ";
		}
		else
		{
			$sql_b = ($cat_id == 'none' OR $cat_id != "") ? "DISTINCT(exp_channel_titles.entry_id) " : "exp_channel_titles.entry_id ";
		}

		$sql = "FROM exp_channel_titles
				LEFT JOIN exp_channels ON exp_channel_titles.channel_id = exp_channels.channel_id ";

		if ($keywords != '')
		{
			if ($search_in != 'title')
				$sql .= "LEFT JOIN exp_channel_data ON exp_channel_titles.entry_id = exp_channel_data.entry_id ";

			if ($search_in == 'everywhere' OR $search_in == 'comments')
			{
				$sql .= "LEFT JOIN exp_comments ON exp_channel_titles.entry_id = exp_comments.entry_id ";
			}
		}
		elseif ($search_in == 'comments')
		{
			$sql .= "LEFT JOIN exp_comments ON exp_channel_titles.entry_id = exp_comments.entry_id ";
		}

		$sql .= "LEFT JOIN exp_members ON exp_members.member_id = exp_channel_titles.author_id ";

		if ($cat_id == 'none' OR $cat_id != "")
		{
			$sql .= "LEFT JOIN exp_category_posts ON exp_channel_titles.entry_id = exp_category_posts.entry_id
					 LEFT JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ";
		}

		if (is_array($extra_sql) && isset($extra_sql['tables']))
		{
			$sql .= ' '.$extra_sql['tables'].' ';
		}

		// Limit to channels assigned to user

		$sql .= " WHERE exp_channels.site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' AND exp_channel_titles.channel_id IN (";

		foreach ($allowed_channels as $val)
		{
			$sql .= "'".$val."',";
		}

		$sql = substr($sql, 0, -1).')';

		if ( ! $this->EE->cp->allowed_group('can_edit_other_entries') AND ! $this->EE->cp->allowed_group('can_view_other_entries'))
		{
			$sql .= " AND exp_channel_titles.author_id = ".$this->EE->session->userdata('member_id');
		}

		if (is_array($extra_sql) && isset($extra_sql['where']))
		{
			$sql .= ' '.$extra_sql['where'].' ';
		}

		if ($keywords != '')
		{
			$pageurl .= AMP.'keywords='.base64_encode($keywords);

			if ($search_in == 'comments')
			{
				// When searching in comments we do not want to search the entry title.
				// However, by removing this we would have to make the rest of the query creation code
				// below really messy so we simply check for an empty title, which should never happen.
				// That makes this check pointless and allows us some cleaner code. -Paul

				$sql .= " AND (exp_channel_titles.title = '' ";
			}
			else
			{
				if ($vars['exact_match'] != 'yes')
				{
					$sql .= " AND (exp_channel_titles.title LIKE '%".$this->EE->db->escape_like_str($search_keywords)."%' ";
				}
				else
				{
					$pageurl .= AMP.'exact_match=yes';

					$sql .= " AND (exp_channel_titles.title = '".$this->EE->db->escape_str($search_keywords)."' OR exp_channel_titles.title LIKE '".$this->EE->db->escape_like_str($search_keywords)." %' OR exp_channel_titles.title LIKE '% ".$this->EE->db->escape_like_str($search_keywords)." %' ";
				}
			}

			$pageurl .= AMP.'search_in='.$search_in;

			if ($search_in == 'body' OR $search_in == 'everywhere')
			{
				// ---------------------------------------
				//	 Fetch the searchable field names
				// ---------------------------------------

				$fields = array();

				$xql = "SELECT DISTINCT(field_group) FROM exp_channels";

				if ($channel_id != '')
				{
					$xql .= " WHERE channel_id = '".$this->EE->db->escape_str($channel_id)."' ";
				}

				$query = $this->EE->db->query($xql);

				if ($query->num_rows() > 0)
				{
					$fql = "SELECT field_id FROM exp_channel_fields WHERE group_id IN (";

					foreach ($query->result_array() as $row)
					{
						$fql .= "'".$row['field_group']."',";
					}

					$fql = substr($fql, 0, -1).')';

					$query = $this->EE->db->query($fql);

					if ($query->num_rows() > 0)
					{
						foreach ($query->result_array() as $row)
						{
							$fields[] = $row['field_id'];
						}
					}
				}

				foreach ($fields as $val)
				{
					if ($exact_match != 'yes')
					{
						$sql .= " OR exp_channel_data.field_id_".$val." LIKE '%".$this->EE->db->escape_like_str($search_keywords)."%' ";
					}
					else
					{
						$sql .= "  OR (exp_channel_data.field_id_".$val." LIKE '".$this->EE->db->escape_like_str($search_keywords)." %' OR exp_channel_data.field_id_".$val." LIKE '% ".$this->EE->db->escape_like_str($search_keywords)." %' OR exp_channel_data.field_id_".$val." = '".$this->EE->db->escape_str($search_keywords)."') ";
					}
				}
			}

			if ($search_in == 'everywhere' OR $search_in == 'comments')
			{
				if ($search_in == 'comments' && (substr(strtolower($search_keywords), 0, 3) == 'ip:' OR substr(strtolower($search_keywords), 0, 4) == 'mid:'))
				{
					if (substr(strtolower($search_keywords), 0, 3) == 'ip:')
					{
						$sql .= " OR (exp_comments.ip_address = '".$this->EE->db->escape_str(str_replace('_','.',substr($search_keywords, 3)))."') ";
					}
					elseif(substr(strtolower($search_keywords), 0, 4) == 'mid:')
					{
						$sql .= " OR (exp_comments.author_id = '".$this->EE->db->escape_str(substr($search_keywords, 4))."') ";
					}
				}
				else
				{
					$sql .= " OR (exp_comments.comment LIKE '%".$this->EE->db->escape_like_str($keywords)."%') "; // No ASCII conversion here!
				}
			}
			$sql .= ")";
		}

		if ($channel_id)
		{
			$pageurl .= AMP.'channel_id='.$channel_id;
			$sql .= " AND exp_channel_titles.channel_id = $channel_id";
		}

		if ($date_range)
		{
			$pageurl .= AMP.'date_range='.$date_range;
			$date_range = time() - ($date_range * 60 * 60 * 24);
			$sql .= " AND exp_channel_titles.entry_date > $date_range";
		}

		if (is_numeric($cat_id))
		{
			$pageurl .= AMP.'cat_id='.$cat_id;
			$sql .= " AND exp_category_posts.cat_id = '$cat_id'
					  AND exp_category_posts.entry_id = exp_channel_titles.entry_id ";
		}

		if ($cat_id == 'none')
		{
			$pageurl .= AMP.'cat_id='.$cat_id;
			$sql .= " AND exp_category_posts.entry_id IS NULL ";
		}

		if ($status && $status != 'all')
		{
			$pageurl .= AMP.'status='.$status;

			$sql .= " AND exp_channel_titles.status = '$status'";
		}

		$end = " ORDER BY ";

		if ($order)
		{
			$pageurl .= AMP.'order='.$order;

			switch ($order)
			{
				case 'asc'	: $end .= "entry_date asc";
					break;
				case 'desc'	 : $end .= "entry_date desc";
					break;
				case 'alpha' : $end .= "title asc";
					break;
				default	  : $end .= "entry_date desc";
			}
		}
		else
		{
			$end .= "entry_date desc";
		}

		// ------------------------------
		//	 Are there results?
		// ------------------------------

		$query = $this->EE->db->query($sql_a.$sql_b.$sql);

		// No result?  Show the "no results" message

		$vars['total_count'] = $query->num_rows();

		if ($vars['total_count'] == 0)
		{
			$this->EE->javascript->compile();
			$vars['heading'] = 'edit_channel_entries';
			$vars['search_form_hidden']  = array();
			$this->EE->load->view('edit_rip', $vars, TRUE);
			return;
		}

		// Get the current row number and add the LIMIT clause to the SQL query

		if ( ! $rownum = $this->EE->input->get_post('rownum'))
		{
			$rownum = 0;
		}


		// --------------------------------------------
		//	 Run the query again, fetching ID numbers
		// --------------------------------------------

		if ($search_in == 'comments')
		{
			$rownum = $this->EE->input->get('current_page') ? $this->EE->input->get('current_page') : 0;
		}
		else
		{
			$pageurl .= AMP.'perpage='.$perpage;
			$vars['form_hidden']['pageurl'] = base64_encode($pageurl); // for pagination
		}

		$query = $this->EE->db->query($sql_a.$sql_b.$sql.$end." LIMIT ".$rownum.", ".$perpage);


		// Filter comments

		if ($search_in == 'comments')
		{
			$comment_array = array();

			foreach ($query->result_array() as $row)
			{
				$comment_array[] = $row['comment_id'];
			}

			if ($keywords == '')
			{
				$pageurl .= AMP.'keywords='.base64_encode($keywords).AMP.'search_in='.$search_in;
			}

			return $this->EE->view_comments('', '', '',	 FALSE, array_unique($comment_array), $vars['total_count'], $pageurl);
		}

		// --------------------------------------------
		//	 Fetch the channel information we need later
		// --------------------------------------------
		$sql = "SELECT channel_id, channel_name FROM exp_channels ";

		$sql .= "WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' ";

		$w_array = array();

		$result = $this->EE->db->query($sql);

		if ($result->num_rows() > 0)
		{
			foreach ($result->result_array() as $rez)
			{
				$w_array[$rez['channel_id']] = $rez['channel_name'];
			}
		}

		// --------------------------------------------
		//	 Fetch the status highlight colors
		// --------------------------------------------

		$cql = "SELECT exp_channels.channel_id, exp_channels.channel_name, exp_statuses.status, exp_statuses.highlight
				 FROM  exp_channels, exp_statuses, exp_status_groups
				 WHERE exp_status_groups.group_id = exp_channels.status_group
				 AND   exp_status_groups.group_id = exp_statuses.group_id
				 AND	exp_statuses.highlight != ''
				 AND	exp_status_groups.site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' ";


		// Limit to channels assigned to user

		$sql .= " AND exp_channels.channel_id IN (";

		foreach ($allowed_channels as $val)
		{
			$sql .= "'".$val."',";
		}

		$sql = substr($sql, 0, -1).')';

		$result = $this->EE->db->query($cql);

		$c_array = array();

		if ($result->num_rows() > 0)
		{
			foreach ($result->result_array() as $rez)
			{
				$c_array[$rez['channel_id'].'_'.$rez['status']] = str_replace('#', '', $rez['highlight']);
			}
		}

		// information for entries table

		$vars['entries_form'] = ($form_url != '') ? $form_url : 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=add_item';


		$vars['form_hidden'] = $extra_fields_entries;
		$vars['search_form_hidden'] = $extra_fields_search ? $extra_fields_search : array();

		// table headings
		$table_headings = array('#', lang('title'), lang('view'));

		// comments module installed?  If so, add it to the list of headings.
		if (isset($this->EE->installed_modules['comment'])){
			$table_headings[] .= $this->EE->lang->line('comments');
		}

		$table_headings = array_merge($table_headings, array(lang('author'), lang('date'), lang('channel'), lang('status'), form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"')));

		$vars['table_headings'] = $table_headings;

		// Build and run the full SQL query
		$sql = "SELECT ";

		$sql .= ($cat_id == 'none' OR $cat_id != "") ? "DISTINCT(exp_channel_titles.entry_id), " : "exp_channel_titles.entry_id, ";

		$sql .= "exp_channel_titles.channel_id,
				exp_channel_titles.title,
				exp_channel_titles.author_id,
				exp_channel_titles.status,
				exp_channel_titles.entry_date,
				exp_channel_titles.dst_enabled,
				exp_channel_titles.comment_total,
				exp_channels.live_look_template,
				exp_members.username,
				exp_members.email,
				exp_members.screen_name";

		$sql .= " FROM exp_channel_titles
				  LEFT JOIN exp_channels ON exp_channel_titles.channel_id = exp_channels.channel_id
				  LEFT JOIN exp_members ON exp_members.member_id = exp_channel_titles.author_id ";

		if ($cat_id != 'none' AND $cat_id != "")
		{
			$sql .= "INNER JOIN exp_category_posts ON exp_channel_titles.entry_id = exp_category_posts.entry_id
					 INNER JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ";
		}

		$sql .= "WHERE exp_channel_titles.entry_id IN (";

		foreach ($query->result_array() as $row)
		{
			$sql .= $row['entry_id'].',';
		}

		$sql = substr($sql, 0, -1).') '.$end;

		$query = $this->EE->db->query($sql);


		// load the site's templates
		$templates = array();

		$tquery = $this->EE->db->query("SELECT exp_template_groups.group_name, exp_templates.template_name, exp_templates.template_id
							FROM exp_template_groups, exp_templates
							WHERE exp_template_groups.group_id = exp_templates.group_id
							AND exp_templates.site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'");

		if ($tquery->num_rows() > 0)
		{
			foreach ($tquery->result_array() as $row)
			{
				$templates[$row['template_id']] = $row['group_name'].'/'.$row['template_name'];
			}
		}

		// Grab all autosaved entries
		// Removed for here

		$vars['autosave_show'] = FALSE;

		// Loop through the main query result and set up data structure for table

		$vars['entries'] = array();

		foreach($query->result_array() as $row)
		{
			// Entry ID number
			$vars['entries'][$row['entry_id']][] = $row['entry_id'];

			// Channel entry title (view entry)
			$output = '<a href="'.BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$row['channel_id'].AMP.'entry_id='.$row['entry_id'].'">'.$row['title'].'</a>';

			$vars['entries'][$row['entry_id']][] = $output;

			// "View"
			if ($row['live_look_template'] != 0 && isset($templates[$row['live_look_template']]))
			{
				$qm = ($this->EE->config->item('force_query_string') == 'y') ? '' : '?';

				$view_link = anchor($this->EE->functions->fetch_site_index().$qm.'URL='.
									$this->EE->functions->create_url($templates[$row['live_look_template']].'/'.$row['entry_id']),
									$this->EE->lang->line('view'), '', TRUE);
			}
			else
			{
					$view_link = '--';
			}

			$vars['entries'][$row['entry_id']][] = $view_link;


			// Comment count
			$show_link = TRUE;

			if ($row['author_id'] == $this->EE->session->userdata('member_id'))
			{
				if ( ! $this->EE->cp->allowed_group('can_edit_own_comments') AND
					 ! $this->EE->cp->allowed_group('can_delete_own_comments') AND
					 ! $this->EE->cp->allowed_group('can_moderate_comments'))
				{
					$show_link = FALSE;
				}
			}
			else
			{
				if ( ! $this->EE->cp->allowed_group('can_edit_all_comments') AND
					 ! $this->EE->cp->allowed_group('can_delete_all_comments') AND
					 ! $this->EE->cp->allowed_group('can_moderate_comments'))
				{
					$show_link = FALSE;
				}
			}

			if ( isset($this->EE->installed_modules['comment']))
			{
				//	Comment Link
				if ($show_link !== FALSE)
				{
					$res = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$row['entry_id']."'");$this->EE->db->query_count--;
					$view_url = BASE.AMP.'C=content_edit'.AMP.'M=view_comments'.AMP.'channel_id='.$row['channel_id'].AMP.'entry_id='.$row['entry_id'];
				}

				$view_link = ($show_link == FALSE) ? '<div class="lightLinks">--</div>' : '<div class="lightLinks">('.($res->row('count') ).')'.NBS.anchor($view_url, $this->EE->lang->line('view')).'</div>';

				$vars['entries'][$row['entry_id']][] = $view_link;
			}

			// Username
			$name = ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
			$vars['entries'][$row['entry_id']][] = mailto($row['email'], $name);

			// Date
			$date_fmt = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');

			if ($date_fmt == 'us')
			{
				$datestr = '%m/%d/%y %h:%i %a';
			}
			else
			{
				$datestr = '%Y-%m-%d %H:%i';
			}

			$vars['entries'][$row['entry_id']][] = $this->EE->localize->decode_date($datestr, $row['entry_date'], TRUE);

			// Channel
			$vars['entries'][$row['entry_id']][] = (isset($w_array[$row['channel_id']])) ? '<div class="smallNoWrap">'. $w_array[$row['channel_id']].'</div>' : '';

			// Status
			$status_name = ($row['status'] == 'open' OR $row['status'] == 'closed') ? $this->EE->lang->line($row['status']) : $row['status'];

			$color_info = '';

			if (isset($c_array[$row['channel_id'].'_'.$row['status']]) AND $c_array[$row['channel_id'].'_'.$row['status']] != '')
			{
				$color = $c_array[$row['channel_id'].'_'.$row['status']];
				$prefix = (is_array($colors) AND ! array_key_exists(strtolower($color), $colors)) ? '#' : '';

				// There are custom colours, override the class above
				$color_info = 'style="color:'.$prefix.$color.';"';
			}

			$vars['entries'][$row['entry_id']][] = '<span class="status_'.$row['status'].'"'.$color_info.'>'.$status_name.'</span>';

			// Delete checkbox
			$vars['entries'][$row['entry_id']][] = form_checkbox('toggle[]', $row['entry_id'], '', ' class="toggle" id="delete_box_'.$row['entry_id'].'"');
		} // End foreach

		// Pass the relevant data to the paginate class
		$config['base_url'] = $pageurl;
		$config['total_rows'] = $vars['total_count'];
		$config['per_page'] = $perpage;
		$config['page_query_string'] = TRUE;
		$config['query_string_segment'] = 'rownum';
		$config['full_tag_open'] = '<p id="paginationLinks">';
		$config['full_tag_close'] = '</p>';
		$config['prev_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />';
		$config['next_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />';
		$config['first_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />';
		$config['last_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />';

		$this->EE->pagination->initialize($config);

		$vars['pagination'] = $this->EE->pagination->create_links();
		$vars['heading'] = $heading ? $heading : 'edit_channel_entries';

		$vars['action_options'] = '';

		if ($action == '')
		{
			$vars['action_options'] = array(
												'add'	=> $this->EE->lang->line('add_items')
											);
		}
		elseif (is_array($action))
		{
			$vars['action_options'] = $action;
		}

		$this->EE->javascript->compile();
		return $this->EE->load->view('edit_rip', $vars, TRUE);
	}




	/*
================
	 NOTES
================

REQUIREMENTS
PayPal Premeire or Business Account
The accounts are free to sign up for and are needed to use the IPN (below). Click here to sign up:
https://www.paypal.com/cgi-bin/webscr?cmd=_registration-run

PayPal IPN (Instant Payment Notification) Activiation
This is needed for the user's account to be upgraded automatically. To activate your IPN:
- Log into your PayPal account
- Click on the "Profile" tab
- Then click "Selling Preferences"
- Instant Payment Notification Preferences'
- From there you have to enter a URL for the IPN to talk to.
This URL must be on your web site (i.e.-http://www.yoursite.com/ipn.asp).
*/

}


/* End of file mcp.simple_commerce.php */
/* Location: ./system/expressionengine/modules/simple_commerce/mcp.simple_commerce.php */
