<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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

		ee()->cp->set_right_nav(array(
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
		ee()->load->library('table');

		$vars = array(
			'message' => $message,
			'cp_page_title'	=> lang('simple_commerce_module_name'),
			'api_url'		=>
				ee()->functions->fetch_site_index(0,0).QUERY_MARKER.'ACT='.ee()->cp->fetch_action_id('Simple_commerce', 'incoming_ipn'),
			'action_url'	=> 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=various_settings',
			'paypal_account'=> ee()->config->item('sc_paypal_account')
		);

		$base = reduce_double_slashes(str_replace('/public_html', '', substr(BASEPATH, 0, - strlen(SYSDIR.'/'))).'/encryption/');

		foreach (array('certificate_id', 'public_certificate', 'private_key', 'paypal_certificate', 'temp_path') as $val)
		{

			if ($val == 'certificate_id')
			{
				$vars[$val] = (ee()->config->item('sc_'.$val) === FALSE) ? '' : ee()->config->item('sc_'.$val);
			}
			else
			{
				$vars[$val] = (ee()->config->item('sc_'.$val) === FALSE OR ee()->config->item('sc_'.$val) == '') ? $base.$val.'.pem' : ee()->config->item('sc_'.$val);
			}
		}

		if (ee()->config->item('sc_encrypt_buttons') == 'y')
		{
			$vars['encrypt_y'] = TRUE;
			$vars['encrypt_n'] = FALSE;
		}
		else
		{
			$vars['encrypt_y'] = FALSE;
			$vars['encrypt_n'] = TRUE;
		}

	return ee()->load->view('index', $vars, TRUE);

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
							show_error(str_replace('%pref%', lang($val), lang('file_does_not_exist')));
						}

						if ($val == 'temp_path' && ! is_really_writable($_POST['sc_'.$val]))
						{
							show_error(lang('temporary_directory_unwritable'));
						}
					}

					$insert['sc_'.$val] = ee()->security->xss_clean($_POST['sc_'.$val]);
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


		ee()->config->_update_config($insert);

		//ee()->config->core_ini = array_merge(ee()->config->core_ini, $insert);

		ee()->session->set_flashdata('message_success', lang('settings_updated'));

		ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP
		.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=index');

	}

	// --------------------------------------------------------------------

	/** -------------------------------------------
	/**  Add Item
	/** -------------------------------------------*/
	function add_item()
	{
		$entry_ids = array();

		ee()->load->library('table');

		//  Must be Assigned to Channels
		if (count(ee()->session->userdata['assigned_channels']) == 0)
		{
			show_error(lang('no_entries_matching_that_criteria').BR.BR.lang('site_specific_data'));
		}

		//  Either Show Search Form or Process Entries
		if (ee()->input->get_post('entry_id') !== FALSE)
		{
			$entry_ids[] = ee()->input->get_post('entry_id');
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
		ee()->db->select('entry_id, channel_id, title');
		ee()->db->where_in('entry_id', $entry_ids);
		$query = ee()->db->get('channel_titles');

		$entry_ids = array();

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				if (isset(ee()->session->userdata['assigned_channels'][$row['channel_id']]))
				{
					$entry_ids[$row['entry_id']] = $row['title'];
				}
			}
		}

		if ($new == 'y')
		{
			//  Weed Out Any Entries that are already items
			ee()->db->select('entry_id');
			ee()->db->where_in('entry_id', $entry_ids);
			$query = ee()->db->get('simple_commerce_items');

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
		ee()->load->model('member_model');
		ee()->load->helper(array('form', 'date'));
		ee()->load->library('table');

		$vars['items'] = array();
		$vars['form_hidden'] = NULL;

		$safe_ids = $this->weed_entries($entry_ids, $new);
		unset($entry_ids);

		if (count($safe_ids) == 0)
		{
			ee()->session->set_flashdata('message_failure', lang('invalid_entries'));
			ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP
				.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_items');
		}

		$vars['email_templates_dropdown'] = array(0 => lang('send_no_email'));

		ee()->db->select('email_id, email_name');
		$query = ee()->db->get('simple_commerce_emails');

		foreach($query->result_array() as $row)
		{
			$vars['email_templates_dropdown'][$row['email_id']] = $row['email_name'];
		}

		// get all member groups for the dropdown list
		$member_groups = ee()->member_model->get_member_groups();

		// first dropdown item is "all"
		$vars['member_groups_dropdown'] = array(0 => lang('no_change'));

		foreach($member_groups->result() as $group)
		{
			$vars['member_groups_dropdown'][$group->group_id] = $group->group_title;
		}

		// get subsubscription frequency options
		$vars['subscription_frequency_unit']['day'] = lang('days');
		$vars['subscription_frequency_unit']['week'] = lang('weeks');
		$vars['subscription_frequency_unit']['month'] = lang('months');
		$vars['subscription_frequency_unit']['year'] = lang('years');

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

			ee()->db->where_in('entry_id', array_keys($safe_ids));
			$query = ee()->db->get('simple_commerce_items');

			if ($query->num_rows() == 0)
			{
				ee()->session->set_flashdata('message_failure', lang('invalid_entries'));
				ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP
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

		$vars['cp_page_title']  = lang($type);
		ee()->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce', lang('simple_commerce_module_name'));

		ee()->javascript->compile();

		return ee()->load->view('edit_item', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	function _items_validate($entry_id = array())
	{
		ee()->load->library('form_validation');

		foreach ($entry_id as $id)
		{
			ee()->form_validation->set_rules("regular_price[{$id}]", 'lang:regular_price', 'required|numeric|callback__valid_price[{$id}]');
			ee()->form_validation->set_rules("sale_price[{$id}]", 'lang:regular_price', 'required|numeric|callback__valid_price[{$id}]');

			ee()->form_validation->set_rules("item_enabled[{$id}]", '', '');
			ee()->form_validation->set_rules("sale_price_enabled[{$id}]", '', '');
			ee()->form_validation->set_rules("admin_email_address[{$id}]", '', '');
			ee()->form_validation->set_rules("admin_email_template[{$id}]", '', '');
			ee()->form_validation->set_rules("'customer_email_template[{$id}]", '', '');
			ee()->form_validation->set_rules("new_member_group[{$id}]", '', '');
			ee()->form_validation->set_rules("entry_id[{$id}]", '', '');
		}

		ee()->form_validation->set_error_delimiters('<br /><span class="notice">', '</span>');
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

		if (ee()->form_validation->run() === FALSE)
		{
			return $this->_items_form($entry_ids, $new);
		}

		$safe_ids = $this->weed_entries($entry_ids, $new);
		unset($entry_ids);

		if (count($safe_ids) == 0)
		{
			ee()->session->set_flashdata('message_failure', lang('invalid_entries'));
			ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP
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
				ee()->db->query(ee()->db->insert_string('exp_simple_commerce_items', $data));
				$cp_message = lang('item_added');
			}
			else
			{
				ee()->db->query(ee()->db->update_string('exp_simple_commerce_items', $data, "entry_id = '$id'"));
				$cp_message = lang('updated');
			}
		}

		ee()->functions->clear_caching('page');

		ee()->session->set_flashdata('message_success', $cp_message);

		ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP
		.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_items');

	}

	// --------------------------------------------------------------------


	/** -------------------------------------------
	/**  Edit Store Items
	/** -------------------------------------------*/
	function edit_items()
	{
		//  Either Show Form or Process Entries

		if (ee()->input->post('toggle') !== FALSE OR ee()->input->get_post('entry_id') !== FALSE)
		{
			$entry_ids = array();

			if (ee()->input->get_post('entry_id') !== FALSE)
			{
				$entry_ids[] = ee()->input->get_post('entry_id');
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


			if (ee()->input->get_post('action') == 'delete')
			{
					return $this->_delete_confirmation_forms(array(
							'method'	=> 'delete_items',
							'heading'	=> 'delete_items_confirm',
							'message'	=> 'delete_items_confirm',
							'hidden'	=> array('entry_ids' => implode('|', $entry_ids))
					));
			}

			return $this->_items_form($entry_ids, 'n');
		}

		ee()->load->library('table');

		ee()->table->set_base_url('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_items');
		ee()->table->set_columns(array(
			'title'						=> array('header' => lang('entry_title')),
			'item_regular_price'		=> array('header' => lang('regular_price')),
			'item_sale_price'			=> array('header' => lang('sale_price')),
			'item_use_sale'				=> array('header' => lang('use_sale_price')),
			'subscription_frequency'	=> array(),
			'current_subscriptions'		=> array(),
			'item_purchases'			=> array(),
			'_check'					=> array(
				'sort' => FALSE,
				'header' => form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"')
			)
		));

		$initial_state = array(
			'sort'	=> array('title' => 'asc')
		);

		$params = array(
			'perpage'	=> $this->perpage
		);

		$data = ee()->table->datasource('_edit_items_filter', $initial_state, $params);


		$data['form_hidden'] = NULL;

		$data['cp_page_title']  = lang('edit_items');
		ee()->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.
		AMP.'M=show_module_cp'.AMP.'module=simple_commerce', lang('simple_commerce_module_name'));

		$data['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_items';

		// Add javascript
		ee()->javascript->output(array(
				'$(".toggle_all").toggle(
					function(){
						$("input.toggle").each(function() {
							this.checked = true;
						});
					}, function (){
						$("input.toggle").each(function() {
							this.checked = false;
						});
					}
				);'
			)
		);

		ee()->javascript->compile();

		return ee()->load->view('edit_items', $data, TRUE);
	}

	// --------------------------------------------------------------------

	function _edit_items_filter($state, $params)
	{
		ee()->load->helper('text');

		$id = (ee()->input->get_post('id')) ? ee()->input->get_post('id') : '';

		if (count($state['sort']))
		{
			foreach ($state['sort'] as $key => $val)
			{
				ee()->db->order_by($key, $val);
			}
		}

		$items = ee()->db->from('simple_commerce_items sc, channel_titles wt')
			->select('sc.*, wt.title')
			->where('sc.entry_id = wt.entry_id', NULL, FALSE)
			->limit($params['perpage'], $state['offset'])
			->get()
			->result_array();

		$rows = array();

		while ($item = array_shift($items))
		{
			$subscription_period = ($item['subscription_frequency'] != '') ? $item['subscription_frequency'].' x '.$item['subscription_frequency_unit'] : '--';

			$rows[] = array(
				'title'					 => '<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_items'.AMP.'entry_id='.$item['entry_id'].'">'.$item['title'].'</a>',
				'item_regular_price'	 => $item['item_regular_price'],
				'item_sale_price'		 => $item['item_sale_price'],
				'item_use_sale'			 => $item['item_use_sale'],
				'subscription_frequency' => $subscription_period,
				'current_subscriptions'	 => $item['current_subscriptions'],
				'item_purchases'		 => $item['item_purchases'],
				'_check'				 => '<input class="toggle" id="edit_box_'.$item['entry_id'].'" type="checkbox" name="toggle[]" value="'.$item['entry_id'].'" />'
			);
		}

		return array(
			'rows' => $rows,
			'no_results' => lang('invalid_entries'),
			'pagination' => array(
				'per_page' => $params['perpage'],
				'total_rows' => ee()->db->count_all('simple_commerce_items')
			)
		);
	}

	// --------------------------------------------------------------------

	function _delete_confirmation_forms($data)
	{
		$required = array('method', 'heading', 'message', 'hidden');

		$vars['cp_page_title']  = lang($data['heading']);
		ee()->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce', lang('simple_commerce_module_name'));
		$vars['damned'] = $data['hidden'];

		$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method='.$data['method'];
		$vars['message'] = $data['message'];

		return ee()->load->view('delete_confirmation', $vars, TRUE);

	}

	// --------------------------------------------------------------------

	/** -------------------------------------------
	/**  Delete Store Items
	/** -------------------------------------------*/
	function delete_items()
	{
		if (ee()->input->post('entry_ids') !== FALSE)
		{
			$entry_ids = array();

			foreach(explode('|', ee()->input->get_post('entry_ids')) as $id)
			{
				$entry_ids[] = ee()->db->escape_str($id);
			}

			ee()->db->query("DELETE FROM exp_simple_commerce_items
						WHERE entry_id IN ('".implode("','", $entry_ids)."')");
		}

		return $this->edit_items(lang('items_deleted'));
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
		ee()->load->library('table');

		$type = 'add_email';

		ee()->cp->add_js_script('plugin', 'ee_txtarea');

		$vars['template_directions'] = ee()->load->view('template_directions', '', TRUE);

		ee()->javascript->output('

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

			$vars['cp_page_title']  = lang('add_emails');
			ee()->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce', lang('simple_commerce_module_name'));

			$vars['form_hidden']['email_id']['0'] = '';

			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=adding_email';

		}
		else
		{
			$type = (count($email_ids) == 1) ? 'update_email' : 'update_emails';
			$vars['email_template'] = array();

			$query = ee()->db->query("SELECT * FROM exp_simple_commerce_emails WHERE email_id IN ('".implode("','", $email_ids)."')");

			foreach ($query->result_array() as $key => $row)
			{
				$vars['email_template'][$row['email_id']]['email_id'] = $row['email_id'];
				$vars['email_template'][$row['email_id']]['email_name'] = $row['email_name'];
				$vars['email_template'][$row['email_id']]['email_subject'] = $row['email_subject'];
				$vars['email_template'][$row['email_id']]['email_body'] = $row['email_body'];
				$vars['email_template'][$row['email_id']]['possible_post'] = ($key == 0) ? TRUE : FALSE;
			}

			$vars['cp_page_title']  = lang($type);
			ee()->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce', lang('simple_commerce_module_name'));


			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=update_emails';
		}


		$vars['type'] = lang($type);
		ee()->javascript->compile();
		return ee()->load->view('email_template', $vars, TRUE);

	}

	// --------------------------------------------------------------------

	function _email_form_validation($email_id = array())
	{
		ee()->load->library('form_validation');

		foreach ($email_id as $id)
		{
			ee()->form_validation->set_rules("email_name[{$id}]", 'lang:email_name', 'required');
			ee()->form_validation->set_rules("email_subject[{$id}]", 'lang:email_subject', 'required');
			ee()->form_validation->set_rules("email_body[{$id}]", 'lang:email_body', 'required');
		}

		ee()->form_validation->set_error_delimiters('<br /><span class="notice">', '</span>');
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
			ee()->db->select('email_id');
			ee()->db->where_in('email_id', $_POST['email_id']);
			$query = ee()->db->get('simple_commerce_emails');

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
				ee()->session->set_flashdata('message_failure', lang('invalid_emails'));

				ee()->functions->redirect(
					BASE.AMP.'C=addons_modules'.AMP
					.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_emails');
			}
		}

		$this->_email_form_validation($email_ids);

		if (ee()->form_validation->run() === FALSE)
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

				ee()->db->insert('simple_commerce_emails', $data);
			}
			else
			{
				$cp_message = 'update';

				ee()->db->where('email_id', $id);
				ee()->db->update('simple_commerce_emails', $data);
			}
		}

		ee()->session->set_flashdata('message_success', lang($cp_message));

		ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'
		.AMP.'module=simple_commerce'.AMP.'method=edit_emails');
	}



	/** -------------------------------------------
	/**  Delete Email Templates
	/** -------------------------------------------*/
	function delete_emails()
	{
		if (ee()->input->post('email_ids') !== FALSE)
		{
			$email_ids = explode('|', ee()->input->post('email_ids'));

			ee()->db->where_in('email_id', $email_ids);
			ee()->db->delete('simple_commerce_emails');

			ee()->session->set_flashdata('message_success', lang('emails_deleted'));
		}

		ee()->functions->redirect(
			BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'
			.AMP.'module=simple_commerce'.AMP.'method=edit_emails');
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Email Templates
	 *
	 * @access public
	*/
	function edit_emails()
	{
		// Either Show Search Form or Process Entries

		if (ee()->input->post('toggle') !== FALSE OR ee()->input->get_post('email_id') !== FALSE)
		{
			$email_ids = array();

			if (ee()->input->get_post('email_id') !== FALSE)
			{
				$email_ids[] = ee()->input->get_post('email_id');
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


			if (ee()->input->get_post('action') == 'delete')
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

			return $this->_emails_form($email_ids, 'n');
		}

		ee()->load->library('table');

		ee()->table->set_columns(array(
			'email_name' => array('header' => lang('template_name')),
			'_check'	 => array(
				'sort' => FALSE,
				'header' => form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"')
			)
		));

		$params = array('perpage' => $this->perpage);
		$default = array('sort' => array('email_name' => 'asc'));

		$data = ee()->table->datasource('_edit_emails_filter', $default, $params);

		$data['form_hidden'] = NULL;
		$data['email_templates'] = array();
		$data['cp_page_title']  = lang('edit_email_templates');

		ee()->cp->set_breadcrumb(
			BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce',
			lang('simple_commerce_module_name')
		);

		// Add javascript
		ee()->javascript->output(array(
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

		ee()->javascript->compile();

		$data['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_emails';

		return ee()->load->view('edit_templates', $data, TRUE);
	}

	function _edit_emails_filter($state, $params)
	{
		if (count($state['sort']) > 0)
		{
			foreach ($state['sort'] as $k => $v)
			{
				ee()->db->order_by($k, $v);
			}
		}

		$emails = ee()->db->select('email_id, email_name')
			->limit($params['perpage'], $state['offset'])
			->get('simple_commerce_emails')
			->result_array();

		$rows = array();

		while ($email = array_shift($emails))
		{
			$rows[] = array(
				'email_name'	=> '<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_emails'.AMP.'email_id='.$email['email_id'].'">'.$email['email_name'].'</a>',
				'_check'		=> '<input class="toggle" id="edit_box_'.$email['email_id'].'" type="checkbox" name="toggle[]" value="'.$email['email_id'].'" />'
			);
		}

		return array(
			'rows' => $rows,
			'no_results' => lang('invalid_entries'),
			'pagination' => array(
				'per_page' => $params['perpage'],
				'total_rows' => ee()->db->count_all('simple_commerce_emails')
			)
		);
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
		ee()->load->model('member_model');
		ee()->load->library('table');
		ee()->load->helper(array('form', 'date'));

		$vars['items'] = array();
		$vars['form_hidden'] = array();
		$type = 'add_purchase';

		ee()->cp->add_js_script('ui', 'datepicker');

		// used in date field
		ee()->javascript->output('
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

		ee()->db->select('simple_commerce_items.item_id, channel_titles.title');
		ee()->db->from('simple_commerce_items');
		ee()->db->join('channel_titles', 'simple_commerce_items.entry_id = channel_titles.entry_id');

		$items_list = ee()->db->get();

		$vars['items_dropdown'] = array('' => lang('choose_item'));

		foreach($items_list->result() as $item)
		{
			$vars['items_dropdown'][$item->item_id] = $item->title;
		}

		if ($new == 'y')
		{
			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=adding_purchase';

			foreach($purchase_ids as $id)
			{
				$vars['purchases'][$id]['txn_id'] = '';
				$vars['purchases'][$id]['screen_name'] = '';
				$vars['purchases'][$id]['item_id'] = '';
				$vars['purchases'][$id]['purchase_date'] = ee()->localize->human_time();
				$vars['purchases'][$id]['subscription_end_date'] = 0;
				$vars['purchases'][$id]['item_cost'] =  '';
				$vars['purchases'][$id]['purchase_id'] =  0;

			ee()->javascript->output('
			$("#purchase_date_'.$id.'").datepicker({dateFormat: $.datepicker.W3C + date_obj_time, defaultDate: new Date('.ee()->localize->format_date('%D %M %d %Y').')});
			$("#subscription_end_date_'.$id.'").datepicker({dateFormat: $.datepicker.W3C + date_obj_time, defaultDate: new Date('.ee()->localize->format_date('%D %M %d %Y').')});
		');

			}
		}
		else
		{
			$type = (count($purchase_ids) == 1) ? 'update_purchase' : 'update_purchases';
			$vars['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=update_purchases';

			$query = ee()->db->query("SELECT sp.*, si.entry_id, si.recurring, m.screen_name AS purchaser FROM exp_simple_commerce_purchases sp, exp_simple_commerce_items si, exp_members m
								 WHERE sp.item_id = si.item_id
								 AND sp.member_id = m.member_id
								 AND sp.purchase_id IN ('".implode("','", $purchase_ids)."')");

			foreach($query->result_array() as $row)
			{
				$vars['purchases'][$row['purchase_id']]['txn_id'] = $row['txn_id'];
				$vars['purchases'][$row['purchase_id']]['member_id']  = $row['member_id'];
				$vars['purchases'][$row['purchase_id']]['item_id'] = $row['item_id'];
				$vars['purchases'][$row['purchase_id']]['purchase_date'] = ee()->localize->human_time($row['purchase_date']);

				$vars['purchases'][$row['purchase_id']]['item_cost'] =  $row['item_cost'];
				$vars['purchases'][$row['purchase_id']]['purchase_id'] = $row['purchase_id'];
				$vars['purchases'][$row['purchase_id']]['screen_name'] = $row['purchaser'];
				$vars['purchases'][$row['purchase_id']]['recurring'] = $row['recurring'];

				$now_p_date = ($row['purchase_date'] * 1000);


			ee()->javascript->output('
			$("#purchase_date_'.$row['purchase_id'].'").datepicker({dateFormat: $.datepicker.W3C + date_obj_time, defaultDate: new Date('.$now_p_date.')});
		');

			}
		}

		$vars['cp_page_title']  = lang($type);
		$vars['type'] = lang($type);
		ee()->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce', lang('simple_commerce_module_name'));

		ee()->javascript->compile();

		return ee()->load->view('edit_purchase', $vars, TRUE);
	}

	function _purchases_validate($purchase_id = array())
	{
		ee()->load->library('form_validation');

		foreach ($purchase_id as $id)
		{
			ee()->form_validation->set_rules("txn_id[{$id}]", 'lang:txn_id', 'trim|required');
			ee()->form_validation->set_rules("item_id[{$id}]", 'lang:item_id', 'trim|required|numeric');
			ee()->form_validation->set_rules("screen_name[{$id}]", 'lang:screen_name', "trim|required|callback__valid_member[{$id}]");
			ee()->form_validation->set_rules("purchase_date[{$id}]", 'lang:purchase_date', "trim|required|callback__valid_date[{$id}]");
			ee()->form_validation->set_rules("subscription_end_date[{$id}]", 'lang:subscription_end_date', "trim|callback__valid_sub_date[{$id}]");
			ee()->form_validation->set_rules("item_cost[{$id}]", 'lang:item_cost', "trim|required|callback__valid_price[{$id}]");
			ee()->form_validation->set_rules("purchase_id[{$id}]", '', '');

		}

		ee()->form_validation->set_error_delimiters('<br /><span class="notice">', '</span>');
	}

	function _valid_member($str, $key)
	{
		ee()->db->select('member_id');
		ee()->db->where('screen_name', $str);
		$query = ee()->db->get('members');

		if ($query->num_rows() == 0)
		{
			ee()->form_validation->set_message('_valid_member', $this->lang->line('member_not_found'));
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
		$str = ee()->localize->string_to_timestamp($str);

		if ( ! is_numeric($str))
		{
			ee()->form_validation->set_message('_valid_date', $this->lang->line('invalid_date_formatting'));
			return FALSE;
		}

		$_POST['clean_purchase_date'] = array($key => $str);
		return TRUE;
	}

	function _valid_sub_date($str, $key)
	{
		$str = ($str == '') ? 0 : ee()->localize->string_to_timestamp($str);

		if ( ! is_numeric($str) OR ($str < 0))
		{
			ee()->form_validation->set_message('_valid_date', $this->lang->line('invalid_date_formatting'));
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
			ee()->form_validation->set_message('_valid_price', $this->lang->line('invalid_amount'));
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
			ee()->db->select('purchase_id');
			ee()->db->where_in('purchase_id', $_POST['purchase_id']);
			$query = ee()->db->get('simple_commerce_purchases');

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
				return $this->add_purchase(lang('invalid_purchases'));
			}
		}

		$this->_purchases_validate($purchase_ids);

		if (ee()->form_validation->run() === FALSE)
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
				ee()->db->insert('simple_commerce_purchases', $data);

				ee()->db->query("UPDATE exp_simple_commerce_items SET item_purchases = item_purchases + 1 WHERE item_id = '".$data['item_id']."'");
				ee()->db->query("UPDATE exp_simple_commerce_items SET current_subscriptions = current_subscriptions + 1 WHERE item_id = '".$data['item_id']."'");

			}
			else
			{
				ee()->db->where('purchase_id', $id);
				ee()->db->update('simple_commerce_purchases', $data);
			}
		}

		ee()->session->set_flashdata('message_success', lang('updated'));

		ee()->functions->redirect(
			BASE.AMP.'C=addons_modules'.AMP
			.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_purchases');

	}



	/** -------------------------------------------
	/**  Delete Purchases
	/** -------------------------------------------*/
	function delete_purchases()
	{
		if (ee()->input->post('purchase_ids') !== FALSE)
		{
			$purchase_ids = explode('|', ee()->input->post('purchase_ids'));

			ee()->db->select('item_id, purchase_id');
			ee()->db->where_in('purchase_id', $purchase_ids);
			$query = ee()->db->get('simple_commerce_purchases');

			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					ee()->db->where('purchase_id', $row['purchase_id']);
					ee()->db->delete('simple_commerce_purchases');

					// Get current count of purchases for the item
					ee()->db->where('item_id', $row['item_id']);
					ee()->db->from('simple_commerce_purchases');
					$count = ee()->db->count_all_results();

					// Update purchases count
					ee()->db->where('item_id', $row['item_id']);
					ee()->db->update('simple_commerce_items', array('item_purchases' => $count));

					// Get current count of live subscriptions the item
					ee()->db->where('item_id', $row['item_id']);
					ee()->db->where('subscription_end_date', 0);
					ee()->db->from('simple_commerce_purchases');
					$count = ee()->db->count_all_results();

					// Update current subscription count
					ee()->db->where('item_id', $row['item_id']);
					ee()->db->update('simple_commerce_items', array('current_subscriptions' => $count));
				}
			}

			ee()->session->set_flashdata('message_success', lang('purchases_deleted'));
		}

		ee()->functions->redirect(
			BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'
			.AMP.'module=simple_commerce'.AMP.'method=edit_purchases');

	}



	/** -------------------------------------------
	/**  Edit Purchases
	/** -------------------------------------------*/
	function edit_purchases()
	{
		//  Either Show Search Form or Process Entries
		if (ee()->input->post('toggle') !== FALSE OR ee()->input->get_post('purchase_id') !== FALSE)
		{
			$purchase_ids = array();

			if (ee()->input->get_post('purchase_id') !== FALSE)
			{
				$purchase_ids[] = ee()->db->escape_str(ee()->input->get_post('purchase_id'));
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

			if (ee()->input->get_post('action') == 'delete')
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

			//  Finally!  We can do something!
			return $this->_purchases_form($purchase_ids, 'n');
		}

		ee()->load->library('table');

		ee()->table->set_base_url('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_purchases');
		ee()->table->set_columns(array(
			'title'					=> array('header' => lang('item_purchased')),
			'screen_name'			=> array('header' => lang('purchaser_screen_name')),
			'purchase_date'			=> array('header' => lang('date_purchased')),
			'subscription_end_date'	=> array('header' => lang('subscription_end_date')),
			'item_cost'				=> array('header' => lang('item_cost')),
			'_check'				=> array(
				'sort' => FALSE,
				'header' => form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"')
			)
		));


		$params = array('perpage' => $this->perpage);
		$initial_state = array('sort' => array('purchase_date' => 'desc'));

		$data = ee()->table->datasource('_edit_purchases_filter', $initial_state, $params);


		$data['cp_page_title']  = lang('edit_purchases');
		ee()->cp->set_breadcrumb(
			BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce',
			lang('simple_commerce_module_name')
		);

		// Add javascript
		ee()->javascript->output(array(
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


		ee()->javascript->compile();

		//  Check for pagination

		$data['form_hidden'] = NULL;
		$data['show_add_button'] = TRUE;
		$data['action_url'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=edit_purchases';

		return ee()->load->view('edit_purchases', $data, TRUE);
	}

	function _edit_purchases_filter($state, $params)
	{
		if (count($state['sort']))
		{
			foreach ($state['sort'] as $key => $val)
			{
				ee()->db->order_by($key, $val);
			}
		}

		$purchases = ee()->db->from('simple_commerce_purchases scp, simple_commerce_items sci, members m, channel_titles wt')
			->select('scp.*, m.screen_name, wt.title, recurring')
			->where('scp.item_id = sci.item_id', NULL, FALSE)
			->where('sci.entry_id = wt.entry_id', NULL, FALSE)
			->where('scp.member_id = m.member_id', NULL, FALSE)
			->limit($params['perpage'], $state['offset'])
			->get()->result_array();

		$rows = array();

		while ($purchase = array_shift($purchases))
		{
			$subscription_end_date =  ' -- ';

			if ($purchase['subscription_end_date'] != 0)
			{
				$subscription_end_date = ee()->localize->human_time($purchase['subscription_end_date']);
			}
			elseif ($purchase['recurring'] == 'y')
			{
				$subscription_end_date = lang('recurring');
			}

			$rows[] = array(
				'title'					=> '<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.
					AMP.'method=edit_purchases'.AMP.'purchase_id='.$purchase['purchase_id'].'">'.$purchase['title'].'</a>',
				'screen_name'			=> $purchase['screen_name'],
				'purchase_date'			=> ee()->localize->human_time($purchase['purchase_date']),
				'subscription_end_date'	=> $subscription_end_date,
				'item_cost'				=> $purchase['item_cost'],
				'_check'				=> '<input class="toggle" id="edit_box_'.$purchase['purchase_id'].'" type="checkbox" name="toggle[]" value="'.$purchase['purchase_id'].'" />',
			);
		}

		return array(
			'rows' => $rows,
			'no_results' => lang('invalid_entries'),
			'pagination' => array(
				'per_page' => $params['perpage'],
				'total_rows' => ee()->db->count_all('simple_commerce_purchases')
			)
		);
	}

	/** -------------------------------------------
	/**  Export Functions
	/** -------------------------------------------*/

	function export_purchases() { $this->export('purchases'); }
	function export_items() 	{ $this->export('items'); }

	function export($which='purchases')
	{

		ee()->load->helper('download');

		$tab  = ($this->export_type == 'csv') ? ',' : "\t";
		$cr	  = "\n";
		$data = '';

		$filename = $which.'_'.ee()->localize->format_date('%y%m%d').'.txt';

		if ($which == 'items')
		{
			$query = ee()->db->query("SELECT wt.title as item_name, sc.* FROM exp_simple_commerce_items sc, exp_channel_titles wt
								 WHERE sc.entry_id = wt.entry_id
								 ORDER BY item_name");
		}
		else
		{
			$query = ee()->db->query("SELECT wt.title AS item_purchased, m.screen_name AS purchaser, scp.*
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

		ee()->api->instantiate('channel_categories');

		$allowed_channels = ee()->functions->fetch_assigned_channels(TRUE);

		if (count($allowed_channels) > 0)
		{
			// Fetch channel titles
			ee()->db->select('channel_title, channel_id, cat_group, status_group, field_group');
			ee()->db->where_in('channel_id', $allowed_channels);
			ee()->db->where('site_id', ee()->config->item('site_id'));

			ee()->db->order_by('channel_title');
			$query = ee()->db->get('channels');

			foreach ($query->result_array() as $row)
			{
				$channel_array[$row['channel_id']] = array(str_replace('"','',$row['channel_title']), $row['cat_group'], $row['status_group'], $row['field_group']);
			}
		}

		/** -----------------------------
		/**  Category Tree
		/** -----------------------------*/

		$order = ($this->nest_categories == 'y') ? 'group_id, parent_id, cat_name' : 'cat_name';

		ee()->db->select('categories.group_id, categories.parent_id, categories.cat_id, categories.cat_name');
		ee()->db->from('categories');
		ee()->db->where('site_id', ee()->config->item('site_id'));
		ee()->db->order_by($order);

		$query = ee()->db->get();

		// Load the text helper
		ee()->load->helper('text');

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
						ee()->api_channel_categories->cat_array[] = array($val['0'], $val['1'], $val['2']);
						ee()->api_channel_categories->category_form_subtree($val['1'], $categories, $depth=1);
					}
				}
			}
			else
			{
				ee()->api_channel_categories->cat_array = $categories;
			}
		}

		/** -----------------------------
		/**  Entry Statuses
		/** -----------------------------*/

		ee()->db->select('group_id, status');
		ee()->db->where('site_id', ee()->config->item('site_id'));
		ee()->db->order_by('status_order');
		$query = ee()->db->get('statuses');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$status_array[]  = array($row['group_id'], $row['status']);
			}
		}

		$default_cats[] = array('', lang('filter_by_category'));
		$default_cats[] = array('all', lang('all'));
		$default_cats[] = array('none', lang('none'));

		$dstatuses[] = array('', lang('filter_by_status'));
		$dstatuses[] = array('open', lang('open'));
		$dstatuses[] = array('closed', lang('closed'));

		$channel_info['0']['categories'] = $default_cats;
		$channel_info['0']['statuses'] = $dstatuses;

		foreach ($channel_array as $key => $val)
		{
			$any = 0;
			$cats = $default_cats;

			if (count(ee()->api_channel_categories->cat_array) > 0)
			{
				$last_group = 0;

				foreach (ee()->api_channel_categories->cat_array as $k => $v)
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
			$statuses[] = array('', lang('filter_by_status'));

			if (count($status_array) > 0)
			{
				foreach ($status_array as $k => $v)
				{
					if ($v['0'] == $val['2'])
					{
						$status_name = ($v['1'] == 'closed' OR $v['1'] == 'open') ?  lang($v['1']) : $v['1'];
						$statuses[] = array($v['1'], $status_name);
					}
				}
			}
			else
			{
				$statuses[] = array($v['1'], lang('open'));
				$statuses[] = array($v['1'], lang('closed'));
			}

			$channel_info[$key]['statuses'] = $statuses;
		}

		$channel_info = json_encode($channel_info);

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
		ee()->javascript->output($javascript);
	}



	function add_items($channel_id = '', $message = '', $extra_sql = '', $search_url = '', $form_url = '', $action = '', $extra_fields_search='', $extra_fields_entries='', $heading='')
	{
		ee()->lang->loadfile('content');
		ee()->load->helper('url');

		$channel_id = '';
		$extra_sql = array();


		ee()->db->select('entry_id');

		$query = ee()->db->get('simple_commerce_items');

        if ($query->num_rows() > 0)
        {
        	$extra_sql['where'] = " AND exp_channel_titles.entry_id NOT IN ('";

        	foreach($query->result_array() as $row) $extra_sql['where'] .= $row['entry_id']."','";

        	$extra_sql['where'] = substr($extra_sql['where'], 0, -2).') ';
        }

		ee()->load->library('api');

		// $action, $extra_fields_*, and $heading are used by move_comments
		$vars['message'] = $message;
		$action = $action ? $action : ee()->input->get_post('action');

		// Security check
		if ( ! ee()->cp->allowed_group('can_access_edit'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->library('pagination');
		ee()->load->library('table');
		ee()->load->helper(array('form', 'text', 'url', 'snippets'));
		ee()->api->instantiate('channel_categories');

		ee()->load->model('channel_model');
		ee()->load->model('channel_entries_model');
		ee()->load->model('category_model');
		ee()->load->model('status_model');

		// Load the search helper so we can filter the keywords
		ee()->load->helper('search');

		ee()->view->cp_page_title = lang('edit');

		ee()->cp->add_js_script('ui', 'datepicker');

		ee()->javascript->output(array(
			ee()->javascript->hide(".paginationLinks .first"),
			ee()->javascript->hide(".paginationLinks .previous")
		));

		ee()->javascript->output('
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

		ee()->jquery->tablesorter('.mainTable', '{
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


		ee()->javascript->output('
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

		ee()->javascript->change("#date_range", "
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

		ee()->javascript->output('
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

		$cp_theme  = ( ! ee()->session->userdata('cp_theme')) ? ee()->config->item('cp_theme') : ee()->session->userdata('cp_theme');
		$turn_on_robot = FALSE;

		// Fetch channel ID numbers assigned to the current user
		$allowed_channels = ee()->functions->fetch_assigned_channels();

		if (empty($allowed_channels))
		{
			show_error(lang('no_channels'));
		}

		//  Fetch Color Library - We use this to assist with our status colors
		if (file_exists(APPPATH.'config/colors.php'))
		{
			include (APPPATH.'config/colors.php');
		}
		else
		{
			$colors = '';
		}

		// We need to determine which channel to show entries from
		// if the channel_id combined
		if ($channel_id == '')
		{
			$channel_id = ee()->input->get_post('channel_id');
		}

		if ($channel_id == 'null' OR $channel_id === FALSE OR ! is_numeric($channel_id))
		{
			$channel_id = '';
		}

		$cat_group = '';
		$cat_id = ee()->input->get_post('cat_id');

		$status = ee()->input->get_post('status');
		$order	= ee()->input->get_post('order');
		$date_range = ee()->input->get_post('date_range');
		$total_channels = count($allowed_channels);

		// If we have more than one channel we'll write the JavaScript menu switching code
		if ($total_channels > 1)
		{
			ee()->javascript->output($this->filtering_menus());
		}

		// Do we have a message to show?
		// Note: a message is displayed on this page after editing or submitting a new entry

		if (ee()->input->get_post("U") == 'mu')
		{
			$vars['message'] = lang('multi_entries_updated');
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

		if (ee()->session->userdata['group_id'] != 1)
		{
			$where[] = array('channel_id' => $allowed_channels);
		}

		$query = ee()->channel_model->get_channels(ee()->config->item('site_id'), $fields, $where);

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

		$vars['channel_selected'] = ee()->input->get_post('channel_id');

		$vars['channel_select_options'] = array('null' => lang('filter_by_channel'));

		if ($query->num_rows() > 1)
		{
			$vars['channel_select_options']['all'] = lang('all');
		}

		foreach ($query->result_array() as $row)
		{
			$vars['channel_select_options'][$row['channel_id']] = $row['channel_title'];
		}

		// Category pull-down menu
		$vars['category_selected'] = $cat_id;

		$vars['category_select_options'][''] = lang('filter_by_category');

		if ($total_channels > 1)
		{
			$vars['category_select_options']['all'] = lang('all');
		}

		$vars['category_select_options']['none'] = lang('none');

		if ($cat_group != '')
		{
			foreach(ee()->api_channel_categories->cat_array as $key => $val)
			{
				if ( ! in_array($val['0'], explode('|',$cat_group)))
				{
					unset(ee()->api_channel_categories->cat_array[$key]);
				}
			}

			$i=1;
			$new_array = array();

			foreach (ee()->api_channel_categories->cat_array as $ckey => $cat)
			{
		    	if ($ckey-1 < 0 OR ! isset(ee()->api_channel_categories->cat_array[$ckey-1]))
    		   	{
					$vars['category_select_options']['NULL_'.$i] = '-------';
            	}

				$vars['category_select_options'][$cat['1']] = (str_replace("!-!","&nbsp;", $cat['2']));

            	if (isset(ee()->api_channel_categories->cat_array[$ckey+1]) && ee()->api_channel_categories->cat_array[$ckey+1]['0'] != $cat['0'])
	        	{
					$vars['category_select_options']['NULL_'.$i] = '-------';
       			}

       			$i++;
			}
		}

		// Authors list
		$vars['author_selected'] = ee()->input->get_post('author_id');

		$query = ee()->member_model->get_authors();
		$vars['author_select_options'][''] = lang('filter_by_author');

		foreach ($query->result_array() as $row)
		{
			$vars['author_select_options'][$row['member_id']] = ($row['screen_name'] == '') ? $row['username'] : $row['screen_name'];
		}

		// Status pull-down menu
		$vars['status_selected'] = $status;

		$vars['status_select_options'][''] = lang('filter_by_status');
		$vars['status_select_options']['all'] = lang('all');

		$sel_1 = '';
		$sel_2 = '';

		if ($cat_group != '')
		{
			  $sel_1 = ($status == 'open')	? 1 : '';
			  $sel_2 = ($status == 'closed') ? 1 : '';
		}

		if ($cat_group != '')
		{
			$rez = ee()->db->query("SELECT status_group FROM exp_channels WHERE channel_id = '$channel_id'");

			$query = ee()->db->query("SELECT status FROM exp_statuses WHERE group_id = '".ee()->db->escape_str($rez->row('status_group') )."' ORDER BY status_order");

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$status_name = ($row['status'] == 'closed' OR $row['status'] == 'open') ?  lang($row['status']) : $row['status'];
					$vars['status_select_options'][$row['status']] = $status_name;
				}
			}
		}
		else
		{
			 $vars['status_select_options']['open'] = lang('open');
			 $vars['status_select_options']['closed'] = lang('closed');
		}

		// Date range pull-down menu
		$vars['date_selected'] = $date_range;

		$vars['date_select_options'][''] = lang('date_range');
		$vars['date_select_options']['1'] = lang('today');
		$vars['date_select_options']['7'] = lang('past_week');
		$vars['date_select_options']['31'] = lang('past_month');
		$vars['date_select_options']['182'] = lang('past_six_months');
		$vars['date_select_options']['365'] = lang('past_year');
		$vars['date_select_options']['custom_date'] = lang('any_date');

		// Display order pull-down menu
		$vars['order_selected'] = $order;

		$vars['order_select_options'][''] = lang('order');
		$vars['order_select_options']['asc'] = lang('ascending');
		$vars['order_select_options']['desc'] = lang('descending');
		$vars['order_select_options']['alpha'] = lang('alpha');

		// Results per page pull-down menu
		if ( ! ($perpage = ee()->input->get_post('perpage')))
		{
			$perpage = ee()->input->cookie('perpage');
		}

		if ($perpage == ''){
			$perpage = 50;
		}

		ee()->functions->set_cookie('perpage' , $perpage, 60*60*24*182);

		$vars['perpage_selected'] = $perpage;

		$vars['perpage_select_options']['10'] = '10 '.lang('results');
		$vars['perpage_select_options']['25'] = '25 '.lang('results');
		$vars['perpage_select_options']['50'] = '50 '.lang('results');
		$vars['perpage_select_options']['75'] = '75 '.lang('results');
		$vars['perpage_select_options']['100'] = '100 '.lang('results');
		$vars['perpage_select_options']['150'] = '150 '.lang('results');

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
		$search_keywords = (ee()->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities($keywords) : $keywords;

		$vars['exact_match'] = ee()->input->get_post('exact_match');

		$vars['keywords'] = array(
									'name' 		=> 'keywords',
									'value'		=> stripslashes($keywords),
									'id'		=> 'keywords',
									'maxlength'	=> 200
								);

		$search_in = (ee()->input->get_post('search_in') != '') ? ee()->input->get_post('search_in') : 'title';

		$vars['search_in_selected'] = $search_in;

		$vars['search_in_options']['title'] =  lang('title_only');
		$vars['search_in_options']['body'] =  lang('title_and_body');

		if (isset(ee()->installed_modules['comment']))
		{
			$vars['search_in_options']['everywhere'] =  lang('title_body_comments');
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

		$sql .= " WHERE exp_channels.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."' AND exp_channel_titles.channel_id IN (";

		foreach ($allowed_channels as $val)
		{
			$sql .= "'".$val."',";
		}

		$sql = substr($sql, 0, -1).')';

		if ( ! ee()->cp->allowed_group('can_edit_other_entries') AND ! ee()->cp->allowed_group('can_view_other_entries'))
		{
			$sql .= " AND exp_channel_titles.author_id = ".ee()->session->userdata('member_id');
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
					$sql .= " AND (exp_channel_titles.title LIKE '%".ee()->db->escape_like_str($search_keywords)."%' ";
				}
				else
				{
					$pageurl .= AMP.'exact_match=yes';

					$sql .= " AND (exp_channel_titles.title = '".ee()->db->escape_str($search_keywords)."' OR exp_channel_titles.title LIKE '".ee()->db->escape_like_str($search_keywords)." %' OR exp_channel_titles.title LIKE '% ".ee()->db->escape_like_str($search_keywords)." %' ";
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
					$xql .= " WHERE channel_id = '".ee()->db->escape_str($channel_id)."' ";
				}

				$query = ee()->db->query($xql);

				if ($query->num_rows() > 0)
				{
					$fql = "SELECT field_id FROM exp_channel_fields WHERE group_id IN (";

					foreach ($query->result_array() as $row)
					{
						$fql .= "'".$row['field_group']."',";
					}

					$fql = substr($fql, 0, -1).')';

					$query = ee()->db->query($fql);

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
						$sql .= " OR exp_channel_data.field_id_".$val." LIKE '%".ee()->db->escape_like_str($search_keywords)."%' ";
					}
					else
					{
						$sql .= "  OR (exp_channel_data.field_id_".$val." LIKE '".ee()->db->escape_like_str($search_keywords)." %' OR exp_channel_data.field_id_".$val." LIKE '% ".ee()->db->escape_like_str($search_keywords)." %' OR exp_channel_data.field_id_".$val." = '".ee()->db->escape_str($search_keywords)."') ";
					}
				}
			}

			if ($search_in == 'everywhere' OR $search_in == 'comments')
			{
				if ($search_in == 'comments' && (substr(strtolower($search_keywords), 0, 3) == 'ip:' OR substr(strtolower($search_keywords), 0, 4) == 'mid:'))
				{
					if (substr(strtolower($search_keywords), 0, 3) == 'ip:')
					{
						$sql .= " OR (exp_comments.ip_address = '".ee()->db->escape_str(str_replace('_','.',substr($search_keywords, 3)))."') ";
					}
					elseif(substr(strtolower($search_keywords), 0, 4) == 'mid:')
					{
						$sql .= " OR (exp_comments.author_id = '".ee()->db->escape_str(substr($search_keywords, 4))."') ";
					}
				}
				else
				{
					$sql .= " OR (exp_comments.comment LIKE '%".ee()->db->escape_like_str($keywords)."%') "; // No ASCII conversion here!
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

		$query = ee()->db->query($sql_a.$sql_b.$sql);

		// No result?  Show the "no results" message

		$vars['total_count'] = $query->num_rows();

		if ($vars['total_count'] == 0)
		{
			ee()->javascript->compile();
			$vars['heading'] = 'edit_channel_entries';
			$vars['search_form_hidden']  = array();
			ee()->load->view('edit_rip', $vars, TRUE);
			return;
		}

		// Get the current row number and add the LIMIT clause to the SQL query

		if ( ! $rownum = ee()->input->get_post('rownum'))
		{
			$rownum = 0;
		}


		// --------------------------------------------
		//	 Run the query again, fetching ID numbers
		// --------------------------------------------

		if ($search_in == 'comments')
		{
			$rownum = ee()->input->get('current_page') ? ee()->input->get('current_page') : 0;
		}
		else
		{
			$pageurl .= AMP.'perpage='.$perpage;
			$vars['form_hidden']['pageurl'] = base64_encode($pageurl); // for pagination
		}

		$query = ee()->db->query($sql_a.$sql_b.$sql.$end." LIMIT ".$rownum.", ".$perpage);


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

			return ee()->view_comments('', '', '',	 FALSE, array_unique($comment_array), $vars['total_count'], $pageurl);
		}

		// --------------------------------------------
		//	 Fetch the channel information we need later
		// --------------------------------------------
		$sql = "SELECT channel_id, channel_name FROM exp_channels ";

		$sql .= "WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."' ";

		$w_array = array();

		$result = ee()->db->query($sql);

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
				 AND	exp_status_groups.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."' ";


		// Limit to channels assigned to user

		$sql .= " AND exp_channels.channel_id IN (";

		foreach ($allowed_channels as $val)
		{
			$sql .= "'".$val."',";
		}

		$sql = substr($sql, 0, -1).')';

		$result = ee()->db->query($cql);

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
		if (isset(ee()->installed_modules['comment'])){
			$table_headings[] .= lang('comments');
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

		$query = ee()->db->query($sql);


		// load the site's templates
		$templates = array();

		$tquery = ee()->db->query("SELECT exp_template_groups.group_name, exp_templates.template_name, exp_templates.template_id
							FROM exp_template_groups, exp_templates
							WHERE exp_template_groups.group_id = exp_templates.group_id
							AND exp_templates.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'");

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
				$qm = (ee()->config->item('force_query_string') == 'y') ? '' : '?';

				$view_link = anchor(ee()->functions->fetch_site_index().$qm.'URL='.
									ee()->functions->create_url($templates[$row['live_look_template']].'/'.$row['entry_id']),
									lang('view'), '', TRUE);
			}
			else
			{
					$view_link = '--';
			}

			$vars['entries'][$row['entry_id']][] = $view_link;


			// Comment count
			$show_link = TRUE;

			if ($row['author_id'] == ee()->session->userdata('member_id'))
			{
				if ( ! ee()->cp->allowed_group('can_edit_own_comments') AND
					 ! ee()->cp->allowed_group('can_delete_own_comments') AND
					 ! ee()->cp->allowed_group('can_moderate_comments'))
				{
					$show_link = FALSE;
				}
			}
			else
			{
				if ( ! ee()->cp->allowed_group('can_edit_all_comments') AND
					 ! ee()->cp->allowed_group('can_delete_all_comments') AND
					 ! ee()->cp->allowed_group('can_moderate_comments'))
				{
					$show_link = FALSE;
				}
			}

			if ( isset(ee()->installed_modules['comment']))
			{
				//	Comment Link
				if ($show_link !== FALSE)
				{
					$res = ee()->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$row['entry_id']."'");ee()->db->query_count--;
					$view_url = BASE.AMP.'C=content_edit'.AMP.'M=view_comments'.AMP.'channel_id='.$row['channel_id'].AMP.'entry_id='.$row['entry_id'];
				}

				$view_link = ($show_link == FALSE) ? '<div class="lightLinks">--</div>' : '<div class="lightLinks">('.($res->row('count') ).')'.NBS.anchor($view_url, lang('view')).'</div>';

				$vars['entries'][$row['entry_id']][] = $view_link;
			}

			// Username
			$name = ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
			$vars['entries'][$row['entry_id']][] = mailto($row['email'], $name);

			// Date
			$date_fmt = (ee()->session->userdata('time_format') != '') ? ee()->session->userdata('time_format') : ee()->config->item('time_format');

			if ($date_fmt == 'us')
			{
				$datestr = '%m/%d/%y %h:%i %a';
			}
			else
			{
				$datestr = '%Y-%m-%d %H:%i';
			}

			$vars['entries'][$row['entry_id']][] = ee()->localize->format_date($datestr, $row['entry_date']);

			// Channel
			$vars['entries'][$row['entry_id']][] = (isset($w_array[$row['channel_id']])) ? '<div class="smallNoWrap">'. $w_array[$row['channel_id']].'</div>' : '';

			// Status
			$status_name = ($row['status'] == 'open' OR $row['status'] == 'closed') ? lang($row['status']) : $row['status'];

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
		$config['prev_link'] = '<img src="'.ee()->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />';
		$config['next_link'] = '<img src="'.ee()->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />';
		$config['first_link'] = '<img src="'.ee()->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />';
		$config['last_link'] = '<img src="'.ee()->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />';

		ee()->pagination->initialize($config);

		$vars['pagination'] = ee()->pagination->create_links();
		$vars['heading'] = $heading ? $heading : 'edit_channel_entries';

		$vars['action_options'] = '';

		if ($action == '')
		{
			$vars['action_options'] = array(
												'add'	=> lang('add_items')
											);
		}
		elseif (is_array($action))
		{
			$vars['action_options'] = $action;
		}

		ee()->javascript->compile();
		return ee()->load->view('edit_rip', $vars, TRUE);
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
