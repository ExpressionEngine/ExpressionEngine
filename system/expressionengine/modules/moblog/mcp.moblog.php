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
 * ExpressionEngine Moblog Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Moblog_mcp {

	var $channel_array		= array();
	var $status_array 		= array();
	var $field_array  		= array();
	var $author_array 		= array();
	var $image_dim_array	= array();
	var $upload_loc_array	= array();
	
	var $default_template 	= '';
	var $default_channel_cat	= '';


	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Moblog_mcp( $switch = TRUE )
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();


		$this->default_template = <<<EOT
{text}

{images}
<img src="{file}" width="{width}" height="{height}" alt="pic" />
{/images}

{files match="audio|files|movie"}
<a href="{file}">Download File</a>
{/files}
EOT;

	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Moblog Homepage
	 *
	 * @access	public
	 * @return	string
	 */
	function index()
	{
		$vars['cp_page_title'] = $this->EE->lang->line('moblog');
		
		$this->EE->load->helper('form');
		$this->EE->load->library('table');
		$this->EE->load->library('javascript');
		
		$this->EE->javascript->output(array(
				'// Za Toggles, zey do nuffink!

				$(".toggle_all").toggle(
					function(){
						$("input[class=toggle_moblog]").each(function() {
							this.checked = true;
						});
					}, function (){
						$("input[class=toggle_moblog]").each(function() {
							this.checked = false;
						});
					}
				);

				var loading = $("<span><img src=\''.$this->EE->config->slash_item('theme_folder_url').'cp_global_images/loader.gif\' />&nbsp;</span>");
				
				$(".notification_link").click(function() {
					var that = $(this).before(loading);

					$.getJSON($(this).attr("href"), function(data) {	
						loading.remove();
						
						if (data.error === undefined) {
							$.ee_notice(data.message);
						}
						else {
							type = (data.error == true) ? "error" : "success";
							$.ee_notice(data.message, {"type": type});
						}
					});
					return false;
				});
			
				'
			)
		);
		
		$this->EE->javascript->compile();
		
		if ( ! $rownum = $this->EE->input->get_post('rownum'))
		{		
			$rownum = 0;
		}
		
		$perpage = 100;
		
		$total = $this->EE->db->count_all('moblogs');

		if ($total > 0)
		{
			$this->EE->db->select('moblog_full_name, moblog_id, moblog_enabled');
			$this->EE->db->order_by('moblog_full_name', 'ASC');
			
			$query = $this->EE->db->get('moblogs', $perpage, $rownum);
			
			if ($query->num_rows() > 0)
			{
				$vars['moblogs'] = $query->result();
			}
		}

		$this->EE->cp->set_right_nav(array(
											'create_moblog' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog'.AMP.'method=create_modify'
											)
									);

		return $this->EE->load->view('index', $vars, TRUE);
	}


	// --------------------------------------------------------------------
	
	/**
	 * Create Moblog
	 *
	 * @access	public
	 * @return	string
	 */
	function create_modify()
	{
		$this->EE->load->helper('form');
		$this->EE->load->library('form_validation');
		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_categories');
		
		$id		= ( ! $this->EE->input->get_post('id')) ? '' : $this->EE->input->get_post('id');
		$basis	= ( ! $this->EE->input->post('basis'))  ? '' : $this->EE->input->post('basis');
		
		$count = $this->EE->db->count_all('moblogs');

		$vars['hidden_fields'] = array('id' => $id, 'basis' => $basis);
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog', $this->EE->lang->line('moblog'));

		if ($id != '')
		{
			$vars['cp_page_title'] = $this->EE->lang->line('edit_moblog');
		}
		else
		{
			$vars['cp_page_title'] = $this->EE->lang->line('create_moblog');
		}

		// Base new moblog on existing one?

		if ($basis == '' && $count > 0 && $id == '')
		{
			$this->EE->db->select('moblog_id, moblog_full_name');
			$query = $this->EE->db->get('moblogs');
			
			$options['none'] = $this->EE->lang->line('none');
			
			foreach($query->result_array() as $row)
			{
				$options[$row['moblog_id']] = $row['moblog_full_name'];
			}
			
			return $this->EE->load->view('choose', array('options' => $options), TRUE);
		}
		

		// Fetch Channels
		
		$channel_array = array();
		
		$this->EE->db->select('channel_id, channel_title, site_label');
		$this->EE->db->from(array('channels', 'sites'));
		$this->EE->db->where('channels.site_id = '.$this->EE->db->dbprefix('sites.site_id'));

		if ($this->EE->config->item('multiple_sites_enabled') !== 'y')
		{
			$this->EE->db->where('channels.site_id', '1');
		}
		
		$channel_array['null'] = $this->EE->lang->line('channel_id');
		
		$result = $this->EE->db->get();

		if ($result->num_rows() > 0)
		{
			foreach ($result->result_array() as $rez)
			{
				$channel_array[$rez['channel_id']] = ($this->EE->config->item('multiple_sites_enabled') === 'y') ? $rez['site_label'].NBS.'-'.NBS.$rez['channel_title'] : $rez['channel_title'];
			}
		}

		// Fetch Upload Directories
		// @pk marker - remove
		
		$this->upload_loc_array = array('0' => $this->EE->lang->line('none'));
		$this->image_dim_array = array('0' => $this->EE->lang->line('none'));
		
		$upload_array = array('0' => $this->EE->lang->line('none'));
		
		$this->EE->load->model('tools_model');
		$query = $this->EE->tools_model->get_upload_preferences($this->EE->session->userdata['group_id']);
		
		
		$this->EE->load->model('file_model');
		$sizes_q = $this->EE->file_model->get_dimensions_by_dir_id(1);
		$sizes = array();
		
		foreach ($query->result_array() as $row)
		{
			$sizes[$row['id']] = array('0' => '----');
			$upload_array[$row['id']] = $row['name'];
		}
		
		foreach ($sizes_q->result() as $size)
		{
			$sizes[$size->upload_location_id][$size->id] = $size->title;
		}

		// Options Matrix - Whoa.

		$form_data = array(
						'moblog_full_name'			=> '',
						'moblog_short_name'			=> '',
						'moblog_time_interval'		=> '15',
						'moblog_enabled'			=> 'y',
						'moblog_file_archive'		=> 'n',
						
						// moblog_entry_settings
						'channel_id'				=> array($channel_array, 0),
						'cat_id[]'					=> array(array('none'=> $this->EE->lang->line('none')), array('none' => $this->EE->lang->line('none'), '' => '-----')),
						'field_id'					=> array(array('none'=> $this->EE->lang->line('none')), 'none'),
						'status'					=> array(array('none'=> $this->EE->lang->line('none'), 'open' => $this->EE->lang->line('open'), 'closed' => $this->EE->lang->line('closed')), 'none'),
						'author_id'					=> array(array('none'=> $this->EE->lang->line('none'),
																$this->EE->session->userdata['member_id'] => ($this->EE->session->userdata['screen_name'] == '') ? $this->EE->session->userdata['username'] : $this->EE->session->userdata['screen_name']),
																'none'),
						'moblog_sticky_entry'		=> 'n',
						'moblog_allow_overrides'	=> 'y',
						'moblog_template'			=> $this->default_template,	// textarea

						// moblog_email_settings
						'moblog_email_type'			=> array(array('pop3' => $this->EE->lang->line('pop3')),'pop3'),
						'moblog_email_address'		=> '',
						'moblog_email_server'		=> '',
						'moblog_email_login'		=> '',
						'moblog_email_password'		=> '',
						'moblog_subject_prefix'		=> 'moblog:',
						'moblog_auth_required'		=> 'n',
						'moblog_auth_delete'		=> 'n',
						'moblog_valid_from'			=> '',	// textarea
						'moblog_ignore_text'		=> '',	// textarea
						
						// moblog_image_settings
						'moblog_upload_directory'	=> array(array('none'=> $this->EE->lang->line('none')), '0'),
						'moblog_image_size'			=> array(array('none'=> $this->EE->lang->line('none')), '0'),
						'moblog_thumb_size'			=> array(array('none'=> $this->EE->lang->line('none')), '0')
						);


		// Filtering Javascript
		
		$this->_filtering_menus('moblog_create');
		$this->EE->javascript->compile();


		// Validation Rules

		$this->EE->form_validation->set_rules('moblog_full_name',			'lang:moblog_full_name',		'required|callback__check_duplicate[moblog_full_name]');
		$this->EE->form_validation->set_rules('moblog_short_name',			'lang:moblog_short_name',		'required|alpha_dash|callback__check_duplicate');
		$this->EE->form_validation->set_rules('moblog_auth_required',		'lang:moblog_auth_required',	'required|enum[y,n]');
		$this->EE->form_validation->set_rules('moblog_auth_delete',			'lang:moblog_auth_delete',		'required|enum[y,n]');
		$this->EE->form_validation->set_rules('moblog_email_type',			'lang:moblog_email_type',		'required');
		$this->EE->form_validation->set_rules('moblog_email_address',		'lang:moblog_email_address',	'required');
		$this->EE->form_validation->set_rules('moblog_email_server',		'lang:moblog_email_server',		'required');
		$this->EE->form_validation->set_rules('moblog_email_login',			'lang:moblog_email_login',		'required');
		$this->EE->form_validation->set_rules('moblog_email_password',		'lang:moblog_email_password',	'required');
		$this->EE->form_validation->set_rules('moblog_time_interval',		'lang:moblog_time_interval',	'required');
		$this->EE->form_validation->set_rules('moblog_enabled',				'lang:moblog_enabled',			'required|enum[y,n]');
		
		// All the non-required fields...sighs
		$this->EE->form_validation->set_rules('moblog_valid_from',			'lang:moblog_valid_from',		'prep_list[,]|valid_emails');
		
		$this->EE->form_validation->set_rules('channel_id',					'lang:channel_id',				'');
		$this->EE->form_validation->set_rules('cat_id[]',					'lang:cat_id',					'');
		$this->EE->form_validation->set_rules('field_id',					'lang:field_id',				'');
		$this->EE->form_validation->set_rules('status',						'lang:status',					'');
		$this->EE->form_validation->set_rules('author_id',					'lang:author_id',				'');

		$this->EE->form_validation->set_rules('moblog_subject_prefix',		'lang:moblog_subject_prefix',	'');
		$this->EE->form_validation->set_rules('moblog_ignore_text',			'lang:moblog_ignore_text',		'');
		$this->EE->form_validation->set_rules('moblog_template',			'lang:moblog_template',			'');
		$this->EE->form_validation->set_rules('ping[]',						'lang:ping',					'');
		$this->EE->form_validation->set_rules('moblog_allow_overrides',		'lang:moblog_allow_overrides',	'enum[y,n]');
		$this->EE->form_validation->set_rules('moblog_sticky_entry',		'lang:moblog_sticky_entry',		'enum[y,n]');
		
		$this->EE->form_validation->set_rules('moblog_upload_directory',	'lang:moblog_upload_directory',	'required');
		$this->EE->form_validation->set_rules('moblog_image_size',			'lang:moblog_image_size',		'is_natural');
		$this->EE->form_validation->set_rules('moblog_thumb_size',			'lang:moblog_thumb_size',		'is_natural');
		
		$this->EE->form_validation->set_error_delimiters('<p class="notice">', '</p>');

		if ($edit_id = $this->EE->input->post('id'))
		{
			$this->EE->form_validation->set_old_value('id', $edit_id);
		}

		// Data
		
		$data = array('author_id' => $this->EE->session->userdata['member_id']);
		
		$form_data['moblog_upload_directory'] = array($upload_array, '');
		
		if (($basis != '' && $basis != 'none') OR ($id != '' && is_numeric($id)))
		{
			$moblog_id = ($basis != '') ? $basis : $id;  
			
			$query = $this->EE->db->get_where('moblogs', array('moblog_id' => $moblog_id));
			
			// Fetch a single row
			
			$row = $query->row_array();
			
			// Upload Directory Double-Check
		
			if ( ! isset($upload_array[$row['moblog_upload_directory']]))
			{
				$this->EE->db->select('name');
				$this->EE->db->where('id', $row['moblog_upload_directory']);
				$results = $this->EE->db->get('upload_prefs');
				
				if ($results->num_rows() > 0)
				{
					$upload_array[$row['moblog_upload_directory']] = $results->row('name') ;
					$form_data['moblog_upload_directory'] = array($upload_array, $row['moblog_upload_directory']);
				}
			}
			else
			{
				$form_data['moblog_upload_directory'] = array($upload_array, $row['moblog_upload_directory']);
			}

			$data = array(
						'moblog_short_name'			=> ($basis != '') ? $row['moblog_short_name'] .'_copy' : $row['moblog_short_name'] ,
						'moblog_full_name'			=> ($basis != '') ? $row['moblog_full_name'] .' - copy' : $row['moblog_full_name'] ,
						'channel_id'				=> $row['moblog_channel_id'] ,
						'cat_id[]'					=> explode('|',$row['moblog_categories'] ),
						'field_id'					=> $row['moblog_field_id'] ,
						'status'					=> $row['moblog_status'] ,
						'author_id'					=> $row['moblog_author_id'] ,
						'moblog_auth_required'		=> $row['moblog_auth_required'] ,
						'moblog_auth_delete'		=> $row['moblog_auth_delete'] ,
						'moblog_upload_directory'	=> $row['moblog_upload_directory'] ,

						'moblog_image_size'			=> $row['moblog_image_size'],
						'moblog_thumb_size'			=> $row['moblog_thumb_size'],
						
						'moblog_email_type'			=> $row['moblog_email_type'] ,
						'moblog_email_address'		=> base64_decode($row['moblog_email_address'] ),
						'moblog_email_server'		=> $row['moblog_email_server'] ,
						'moblog_email_login'		=> base64_decode($row['moblog_email_login'] ),
						'moblog_email_password'		=> base64_decode($row['moblog_email_password'] ),
						'moblog_subject_prefix'		=> $row['moblog_subject_prefix'] ,
						'moblog_valid_from'			=> str_replace('|',"\n",$row['moblog_valid_from'] ),
						'moblog_ignore_text'		=> $row['moblog_ignore_text'] ,
						'moblog_template'			=> $row['moblog_template'] ,
						'moblog_time_interval'		=> $row['moblog_time_interval'] ,
						'moblog_enabled'			=> $row['moblog_enabled'] ,
						'moblog_file_archive'		=> $row['moblog_file_archive'] ,

						'moblog_allow_overrides'	=> ( ! isset($row['moblog_allow_overrides'] ) OR $row['moblog_allow_overrides']  == '') ? 'y' : $row['moblog_allow_overrides'] ,
						'moblog_sticky_entry'		=> ( ! isset($row['moblog_sticky_entry'] ) OR $row['moblog_sticky_entry']  == '') ? 'n' : $row['moblog_sticky_entry'] 
						);

			/** ------------------------------
			/**  Modify Form Creation Data
			/** ------------------------------*/
			
			if ($row['moblog_channel_id'] != 0 && array_key_exists($row['moblog_channel_id'], $this->channel_array))
			{
				// Upload Locations
				if ( ! isset($this->upload_loc_array[$data['moblog_upload_directory']]))
				{
					$data['moblog_upload_directory'] = '0';
				}

				$form_data['moblog_upload_directory'] = array($this->upload_loc_array, $data['moblog_upload_directory']);

				// Image Dimensions
				$size_options = $this->image_dim_array[$data['moblog_upload_directory']];
				
				if ( ! isset($size_options[$data['moblog_image_size']]))
				{
					$data['moblog_image_size'] = 0;
				}
				if ( ! isset($size_options[$data['moblog_thumb_size']]))
				{
					$data['moblog_thumb_size'] = 0;
				}
				
				$form_data['moblog_image_size'] = array(
					$size_options,
					$data['moblog_image_size']
				);
				
				$form_data['moblog_thumb_size'] = array(
					$size_options,
					$data['moblog_thumb_size']
				);
				
				
				$form_data['channel_id'][1] = $row['moblog_channel_id'];
				
				$new_array = array('none'=> $this->EE->lang->line('none'));
				
				foreach($this->EE->api_channel_categories->cat_array as $key => $val)
				{
					if (is_array($val) && ! in_array($val['0'], explode('|', $this->channel_array[$query->row('moblog_channel_id')]['1'])))
					{
						unset($this->EE->api_channel_categories->cat_array[$key]);
					}
				}

				if (count($this->EE->api_channel_categories->cat_array > 0))
				{
					$new_array = array('all'=> $this->EE->lang->line('all'));
				}
				
				$new_array = array('none'=> $this->EE->lang->line('none'));
				$i=0;

				foreach ($this->EE->api_channel_categories->cat_array as $ckey => $cat)
				{
					if ($ckey-1 < 0 OR ! isset($this->EE->api_channel_categories->cat_array[$ckey-1]))
					{
						$new_array['NULL_'.$i] = '-------';
					}

					$new_array[$cat['1']] = (str_replace("!-!","&nbsp;",$cat['2']));

					if (isset($this->EE->api_channel_categories->cat_array[$ckey+1]) && $this->EE->api_channel_categories->cat_array[$ckey+1]['0'] != $cat['0'])
					{
						$new_array['NULL_'.$i] = '-------';
					}

					$i++;
				}

				$form_data['cat_id[]'] = array($new_array, $data['cat_id[]']);

				$new_array = array('none'=> $this->EE->lang->line('none'), 'open' => $this->EE->lang->line('open'), 'closed' => $this->EE->lang->line('closed'));

				foreach($this->status_array as $val)
				{
					if (is_array($val) && $val['0'] == $this->channel_array[$row['moblog_channel_id']]['2'])
					{
						if ($val['1'] == 'open' OR $val['1'] == 'closed')
						{
							$new_array[$val['1']] = $this->EE->lang->line($val['1']);
						}
						else
						{
							$new_array[$val['1']] = $val['1'];
						}
					}
				}
				
				if ( ! in_array($row['moblog_status'], $new_array))
				{
					$new_array[$row['moblog_status'] ] = $row['moblog_status'] ;
				}
					
				$form_data['status'] = array($new_array, $data['status']);
				$new_array = array('none'=> $this->EE->lang->line('none'));

				foreach($this->field_array as $val)
				{
					if (is_array($val) && $val['0'] == $this->channel_array[$row['moblog_channel_id']]['3'])
					{
						$new_array[$val['1']] = $val['2'];
					}
				}
				
				$form_data['field_id'] = array($new_array, $data['field_id']);
				$new_array = array('none'=> $this->EE->lang->line('none'));
				
				foreach($this->author_array as $val)
				{
					if (is_array($val) && $val['0'] == $row['moblog_channel_id'] )
					{
						$new_array[$val['1']] = $val['2'];
					}
				}
				
				$form_data['author_id'] = array($new_array, $data['author_id']);	
			}
		}
			
		/** -----------------------------
		/**  Create the form
		/** -----------------------------*/

		$vars['submit_text'] = ($id != '' && is_numeric($id)) ? 'update' : 'submit';
		
		$ping_servers = $query->row_array();
		$ping_servers = ( ! isset($ping_servers['moblog_ping_servers'])) ? '' : $ping_servers['moblog_ping_servers'];

		if ($ping_servers = $this->fetch_ping_servers($ping_servers))
		{
			$this->EE->lang->line('ping_servers');
			$vars['ping_servers'] = $ping_servers;
		}
		

		// Set the default types
		foreach($form_data as $key => $var)
		{
			if (isset($data[$key]) && ! is_array($var))
			{
				$form_data[$key] = $data[$key];
			}
		}

		$vars['values'] = $form_data;
	
		if ($this->EE->form_validation->run() === FALSE)
		{
			// If the "basis_flag" $_POST is set, it means they have come from the form 
			// that asks if they want to build this moblog based on another one. We need
			// to unset the form validation error messages.
			if ($this->EE->input->post('basis_flag'))
			{
				unset($this->EE->form_validation->_field_data);
			}

			return $this->EE->load->view('update', $vars, TRUE);
		}
		
		$this->update_moblog();
	}

	// --------------------------------------------------------------------
	
	/**
	 * JavaScript filtering code
	 *
	 * Creates some javascript functions that are used to switch
	 * various pull-down menus
	 *
	 * @access	public
	 * @return	void
	 */
	function _filtering_menus($form_name)
	{
		// In order to build our filtering options we need to gather 
		// all the channels, categories and custom statuses
		
		/** ----------------------------- 
		/**  Allowed Channels
		/** -----------------------------*/
		
		$allowed_channels = $this->EE->functions->fetch_assigned_channels(TRUE);

		if (count($allowed_channels) > 0)
		{
			// Fetch channel titles
			$this->EE->db->select('channel_title, channel_id, cat_group, status_group, field_group');
					
			if ( ! $this->EE->cp->allowed_group('can_edit_other_entries'))
			{
				$this->EE->db->where_in('channel_id', $allowed_channels);
			}
			
			$this->EE->db->order_by('channel_title');
			$query = $this->EE->db->get('channels');

			foreach ($query->result_array() as $row)
			{
				$this->channel_array[$row['channel_id']] = array(str_replace('"','',$row['channel_title']), $row['cat_group'], $row['status_group'], $row['field_group']);
			}		
		}
		

		//  Category Tree
		$cat_array = $this->EE->api_channel_categories->category_form_tree('y', FALSE, 'all');
		  
		/** ----------------------------- 
		/**  Entry Statuses
		/** -----------------------------*/
		
		$this->EE->db->select('group_id, status');
		$this->EE->db->order_by('status_order');
		$query = $this->EE->db->get('statuses');
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->status_array[]  = array($row['group_id'], $row['status']);
			}
		}

		/** ----------------------------- 
		/**  Custom Channel Fields
		/** -----------------------------*/
		
		/* -------------------------------------
		/*  Hidden Configuration Variable
		/*  - moblog_allow_nontextareas => Removes the textarea only restriction
		/*	for custom fields in the moblog module (y/n)
		/* -------------------------------------*/

		$this->EE->db->select('group_id, field_label, field_id');
		$this->EE->db->order_by('field_label');
		
		if ($this->EE->config->item('moblog_allow_nontextareas') != 'y')
		{
			$this->EE->db->where('channel_fields.field_type', 'textarea');
		}
		
		$query = $this->EE->db->get('channel_fields');
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->field_array[]  = array($row['group_id'], $row['field_id'], str_replace('"','',$row['field_label']));
			}
		}

		/** ----------------------------- 
		/**  SuperAdmins
		/** -----------------------------*/
		
		$this->EE->db->select('member_id, username, screen_name');
		$this->EE->db->where('group_id', '1');
		$query = $this->EE->db->get('members');
		
		foreach ($query->result_array() as $row)
			{
				$author = ($row['screen_name'] == '') ? $row['username'] : $row['screen_name'];
				
				foreach($this->channel_array as $key => $value)
				{
					$this->author_array[]  = array($key, $row['member_id'], str_replace('"','',$author));
				}
			}
		
		/** ----------------------------- 
		/**  Assignable Channel Authors
		/** -----------------------------*/
		$dbp = $this->EE->db->dbprefix;
		
		$this->EE->db->select('channels.channel_id, members.member_id, members.group_id, members.username, members.screen_name');
		$this->EE->db->from(array('channels', 'members', 'channel_member_groups'));
		$this->EE->db->where("({$dbp}channel_member_groups.channel_id = {$dbp}channels.channel_id OR {$dbp}channel_member_groups.channel_id IS NULL)");
		$this->EE->db->where("{$dbp}members.group_id", "{$dbp}channel_member_groups.group_id", FALSE);

		$query = $this->EE->db->get();
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$author = ($row['screen_name'] == '') ? $row['username'] : $row['screen_name'];
				
				$this->author_array[]  = array($row['channel_id'], $row['member_id'], str_replace('"','',$author));
			}
		}
			
		// Create JSON Reference

		// Mixing php with output buffering was ugly, so we'll build out a js objects with
		// all the information we need and then manipulate that in javascript

		$channel_info = array();

		foreach ($this->channel_array as $key => $val)
		{
			$any = 0;
			$cats = array();
	
			if (count($cat_array) > 0)
			{
				$last_group = 0;
		
				foreach ($cat_array as $k => $v)
				{
					if (in_array($v['0'], explode('|', $val['1'])))
					{
						if ( ! isset($set))
						{
							$cats[] = array('', $this->EE->lang->line('all'));
							$cats[] = array('none', $this->EE->lang->line('none'));
							
							$set = 'y';
						}
				
						if ($last_group == 0 OR $last_group != $v['0'])
						{
							$cats[] = array('', '-------');
							$last_group = $v['0'];
						}

						$cats[] = array($v['1'], $v['2']);
					}
				}
		
				if ( ! isset($set))
		        {
					$cats[] = array('none', $this->EE->lang->line('none'));
		        }
				unset($set);
			}

			$channel_info[$key]['categories'] = $cats;

			$statuses = array();

			$statuses[] = array('none', $this->EE->lang->line('none'));

			if (count($this->status_array) > 0)
			{
				foreach ($this->status_array as $k => $v)
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

			$fields = array();
	
			$fields[] = array('none', $this->EE->lang->line('none'));
			
 
			if (count($this->field_array) > 0)
			{
				foreach ($this->field_array as $k => $v)
				{
					if ($v['0'] == $val['3'])
					{
						$fields[] = array($v['1'], $v['2']);
					}
				}
			}
	
			$channel_info[$key]['fields'] = $fields;

			$authors = array();
	
			$authors[] = array('none', $this->EE->lang->line('none'));

			if (count($this->author_array) > 0)
			{
				$inserted_authors = array();
		
				foreach ($this->author_array as $k => $v)
				{
					if ($v['0'] == $key && ! in_array($v['1'],$inserted_authors))
					{
						$inserted_authors[] = $v['1'];
						$authors[] = array($v['1'], $v['2']);
					}
				}
			}
	
			$channel_info[$key]['authors'] = $authors;
		}

		$channel_info = $this->EE->javascript->generate_json($channel_info, TRUE);
		$none_text = $this->EE->lang->line('none');

		$javascript = <<<MAGIC

// An object to represent our channels
var channel_map = $channel_info;

var empty_select =  '<option value="none">$none_text</option>';
var spaceString = new RegExp('!-!', "g");

// We prep the magic array as soon as we can, basically
// converting everything into option elements
(function() {
	jQuery.each(channel_map, function(key, details) {
		
		// Go through each of the individual settings and build a proper dom element
		jQuery.each(details, function(group, values) {
			var html = new String();
			
			// Add the new option fields
			jQuery.each(values, function(a, b) {
				html += '<option value="' + b[0] + '">' + b[1].replace(spaceString, String.fromCharCode(160)) + "</option>";
			});

			channel_map[key][group] = html;
		});
	});
})();

// Change the submenus
// Gets passed the channel id
function changemenu(index)
{
	var channels = 'null';
	
	if (channel_map[index] === undefined) {
		$('select[name=field_id], select[name="cat_id[]"], select[name=status], select[name=author_id]').empty().append(empty_select);
	}
	else {
		jQuery.each(channel_map[index], function(key, val) {
			switch(key) {
				case 'fields':		$('select[name=field_id]').empty().append(val);
					break;
				case 'categories':	$('select[name="cat_id[]"]').empty().append(val);
					break;
				case 'statuses':	$('select[name=status]').empty().append(val);
					break;
				case 'authors':		$('select[name=author_id]').empty().append(val);
					break;
			}
		});
	}
}

$('select[name=channel_id]').change(function() {
	changemenu(this.value);
});

MAGIC;
		
		// And same idea for file upload dirs and dimensions
		$this->upload_loc_array = array('0' => $this->EE->lang->line('none'));
		$this->image_dim_array = array('0' => $this->upload_loc_array);
		
		// Fetch Upload Directories
		
		$this->EE->load->model('tools_model');
		$this->EE->load->model('file_model');
		
		$sizes_q = $this->EE->file_model->get_dimensions_by_dir_id();
		$sizes_array = array();
		
		foreach ($sizes_q->result_array() as $row)
		{
			$sizes_array[$row['upload_location_id']][$row['id']] = $row['title'];
		}
		
		$upload_q = $this->EE->tools_model->get_upload_preferences($this->EE->session->userdata['group_id']);
		
		foreach ($upload_q->result_array() as $row)
		{
			$this->image_dim_array[$row['id']] = array('0' => $this->lang->line('none'));
			$this->upload_loc_array[$row['id']] = $row['name'];
			
			// Get sizes
			if (isset($sizes_array[$row['id']]))
			{
				foreach ($sizes_array[$row['id']] as $id => $title)
				{
					$this->image_dim_array[$row['id']][$id] = $title;
				}
			}
		}
		
		$upload_info = $this->EE->javascript->generate_json($this->image_dim_array, TRUE);
		
		$javascript .= <<<MAGIC

// An object to represent our channels
var upload_info = $upload_info;

var empty_select =  '<option value="0">$none_text</option>';
var spaceString = new RegExp('!-!', "g");

// We prep the magic array as soon as we can, basically
// converting everything into option elements
(function(undefined) {
	jQuery.each(upload_info, function(key, options) {

		var html = '';

		// add option fields
		jQuery.each(options, function(k, v) {
			
			html += '<option value="' + k + '">' + v.replace(spaceString, String.fromCharCode(160)) + "</option>";
		});
		
		if (html) {
			upload_info[key] = html;
		}
	});	
})();

// Change the submenus
// Gets passed the channel id
function upload_changemenu(index)
{
	$('select[name=moblog_image_size]').empty().append(upload_info[index]);
	$('select[name=moblog_thumb_size]').empty().append(upload_info[index]);
}

$('select[name=moblog_upload_directory]').change(function() {
	upload_changemenu(this.value);
});

MAGIC;

		
		
		$this->EE->javascript->output($javascript);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Form validation duplicate name callback
	 *
	 * @access	public
	 * @return	bool
	 */
	function _check_duplicate($str, $which = 'moblog_short_name')
	{
		if ($edit_id = $this->EE->form_validation->old_value('id'))
		{
			$this->EE->db->start_cache();
			$this->EE->db->where('moblog_id !=', $edit_id);
			$this->EE->db->stop_cache();
		}
		
		if ($which == 'moblog_short_name')
		{
			// Short Name Check - Zzzzz...
			
			$this->EE->db->where('moblog_short_name', $str);
			$count = $this->EE->db->count_all_results('moblogs');

			if ($count > 0)
			{
				$this->EE->form_validation->set_message('_check_duplicate', $this->EE->lang->line('moblog_taken_short_name'));
				return FALSE;
			}
		}
		elseif ($which = 'moblog_full_name')
		{
			// Full Name Check

			$this->EE->db->where('moblog_full_name', $str);
			$count = $this->EE->db->count_all_results('moblogs');

			$this->EE->db->flush_cache();

			if ($count > 0)
			{
				$this->EE->form_validation->set_message('_check_duplicate', $this->EE->lang->line('moblog_taken_name'));
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Update Moblog
	 *
	 * @access	public
	 * @return	void
	 */
	function update_moblog()
	{
		// In case the select none/all and any others.
		if (isset($_POST['cat_id']) && count($_POST['cat_id']) > 1 && (in_array('all',$_POST['cat_id']) OR in_array('none',$_POST['cat_id'])))
		{
			if (in_array('all', $_POST['cat_id']))
			{
				$_POST['cat_id'] = array('all');
			}
			else
			{
				$_POST['cat_id'] = array('none');
			}
		}
		
		
		// Format from emails
		
		$from_values = '';
		
		if (isset($_POST['moblog_valid_from']))
		{
			$from_emails = explode(",", $this->EE->input->post('moblog_valid_from'));
			
			if (count($from_emails) > 0)
			{	
				$from_values = implode('|', $from_emails);
			}
		}

		$post_data = array(
						'moblog_full_name'			=> $_POST['moblog_full_name'],
						'moblog_short_name'			=> $_POST['moblog_short_name'],
						'moblog_channel_id'			=> ( ! isset($_POST['channel_id']) OR $_POST['channel_id'] == 'null') ? 'none' : $_POST['channel_id'],
						'moblog_categories'			=> ( ! isset($_POST['cat_id'])) ? 'none' : implode('|',$_POST['cat_id']),
						'moblog_field_id'			=> ( ! isset($_POST['field_id'])) ? 'none' : $_POST['field_id'],
						'moblog_status'				=> ( ! isset($_POST['status'])) ? 'none' : $_POST['status'],
						'moblog_author_id'			=> ( ! isset($_POST['author_id'])) ? 'none' : $_POST['author_id'],
						'moblog_auth_required'		=> $_POST['moblog_auth_required'],
						'moblog_auth_delete'		=> $_POST['moblog_auth_delete'],
						'moblog_upload_directory'	=> $_POST['moblog_upload_directory'],
						
						'moblog_image_size'			=> $_POST['moblog_image_size'],
						'moblog_thumb_size'			=> $_POST['moblog_thumb_size'],
						
						'moblog_email_type'			=> $_POST['moblog_email_type'],
						'moblog_email_address'		=> base64_encode($_POST['moblog_email_address']),
						'moblog_email_server'		=> $_POST['moblog_email_server'],
						'moblog_email_login'		=> base64_encode($_POST['moblog_email_login']),
						'moblog_email_password'		=> base64_encode($_POST['moblog_email_password']),
						'moblog_subject_prefix'		=> ( ! isset($_POST['moblog_subject_prefix'])) ? '' : $_POST['moblog_subject_prefix'],
						'moblog_valid_from'			=> $from_values,
						'moblog_ignore_text'		=> ( ! isset($_POST['moblog_ignore_text'])) ? '' : $_POST['moblog_ignore_text'],
						'moblog_template'			=> ( ! isset($_POST['moblog_template'])) ? '' : $_POST['moblog_template'],
						'moblog_time_interval'		=> $_POST['moblog_time_interval'],
						'moblog_enabled'			=> $_POST['moblog_enabled'],
						'moblog_file_archive'		=> $_POST['moblog_file_archive'],
						
						'moblog_ping_servers'		=> ( ! isset($_POST['ping'])) ? '' : implode('|',$_POST['ping']),
						
						'moblog_allow_overrides'	=> ( ! isset($_POST['moblog_allow_overrides'])) ? 'y' : $_POST['moblog_allow_overrides'],
						'moblog_sticky_entry'		=> ( ! isset($_POST['moblog_sticky_entry'])) ? 'n' : $_POST['moblog_sticky_entry']
						);						
		
		// In 1.6 this module wasn't strict mode compatible and just inserted 'none'
		// into integer fields. This is a quick hack to simply unset those. As well
		// as a check to make sure that we have a usable id
		
		if (isset($_POST['id']) && ! is_numeric($_POST['id']))
		{
			unset($_POST['id']);
		}
		
		$int_fields = array('moblog_id', 'moblog_channel_id', 'moblog_time_interval', 'moblog_author_id', 'moblog_upload_directory', 'moblog_image_width',
							'moblog_image_height', 'moblog_resize_width', 'moblog_resize_height', 'moblog_thumbnail_width', 'moblog_thumbnail_height'
							);

		foreach($int_fields as $field)
		{
			if (isset($post_data[$field]) && ( ! is_numeric($post_data[$field])))
			{
				unset($post_data[$field]);
			}
		}

		if ( ! isset($_POST['id']))
		{
			$sql = $this->EE->db->insert_string('exp_moblogs', $post_data);
			$this->EE->db->query($sql);
			$message = $this->EE->lang->line('moblog_created');
		}
		else
		{
			$sql = $this->EE->db->update_string('exp_moblogs', $post_data, "moblog_id = '".$this->EE->db->escape_str($_POST['id'])."'");
			$this->EE->db->query($sql);
			$message = $this->EE->lang->line('moblog_updated');
		}
		
		$this->EE->session->set_flashdata('message_success', $message);
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog');
	}
	
	
	/** -------------------------------------------
	/**  Delete Confirm
	/** -------------------------------------------*/
	function delete_confirm()
	{
		if ( ! $this->EE->input->post('toggle'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog');
		}

		$this->EE->load->helper('form');

		$vars['cp_page_title'] = $this->EE->lang->line('moblog_delete_confirm_title');
		
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog', $this->EE->lang->line('moblog'));
		$vars['form_action'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog'.AMP.'method=delete_moblogs';
		
		foreach ($_POST['toggle'] as $val)
		{
			$vars['damned'][] = $val;
		}
		
		return $this->EE->load->view('delete_confirm', $vars, TRUE);
	}
	
	/** -------------------------------------------
	/**  Delete Moblogs
	/** -------------------------------------------*/
	function delete_moblogs()
	{
		if ( ! $this->EE->input->post('delete'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog');
		}

		foreach ($_POST['delete'] as $key => $val)
		{
			$this->EE->db->or_where('moblog_id', $val);
		}

		$this->EE->db->delete('moblogs');
	
		$message = (count($_POST['delete']) == 1) ? $this->EE->lang->line('moblog_deleted') : $this->EE->lang->line('moblogs_deleted');

		$this->EE->session->set_flashdata('message_success', $message);
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog');
	}

	
	/** -------------------------
	/**  Check Moblog
	/** -------------------------*/
	
	function check_moblog()
	{
		if ( ! $id = $this->EE->input->get('moblog_id'))
		{			
			return FALSE;
		}
		
		$where = array(
			'moblog_enabled'	=> 'y',
			'moblog_id'			=> $id
		);
		
		$query = $this->EE->db->get_where('moblogs', $where);

		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('invalid_moblog')));
		}
		
		if ( ! class_exists('Moblog'))
		{
			require PATH_MOD.'moblog/mod.moblog.php';
		}
		
		$MP = new Moblog();
		$MP->moblog_array = $query->row_array();
		
		$error = FALSE;
		if ($MP->moblog_array['moblog_email_type'] == 'imap')
		{
			if ( ! $MP->check_imap_moblog())
			{
				$display = $MP->message_array;
				$cp_message = '';
				
				foreach ($MP->message_array as $val)
				{
					$cp_message .= $this->EE->lang->line($val).'<br>';
				}

				$this->EE->session->set_flashdata('message_failure', $cp_message);
			}
			else
			{
				$message = $this->EE->lang->line('moblog_successful_check').'<br />';
				$message .= $this->EE->lang->line('emails_done').NBS.NBS.$MP->emails_done.'<br />';
				$message .= $this->EE->lang->line('entries_added').NBS.NBS.$MP->entries_added.'<br />';
				$message .= $this->EE->lang->line('attachments_uploaded').NBS.NBS.$MP->uploads.'<br />';
				$message .= $this->EE->lang->line('pings_sent').NBS.NBS.$MP->pings_sent.'<br />';
				
				if (count($MP->message_array) > 0)
				{
					$message .= $MP->errors();
					$error = TRUE;
				}
				
				$this->EE->session->set_flashdata(array('message' => $message, 'error' => $error));
				$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog');
			}
		}
		else
		{
			if ( ! $MP->check_pop_moblog())
			{
				$display = $MP->message_array;
				$cp_message = '';
				
				foreach ($MP->message_array as $val)
				{
					$cp_message .= $this->EE->lang->line($val).'<br>';
				}

				$this->EE->session->set_flashdata('message_failure', $cp_message);				
			}
			else
			{
				$message = $this->EE->lang->line('moblog_successful_check').'<br />';
				$message .= $this->EE->lang->line('emails_done').NBS.NBS.$MP->emails_done.'<br />';
				$message .= $this->EE->lang->line('entries_added').NBS.NBS.$MP->entries_added.'<br />';
				$message .= $this->EE->lang->line('attachments_uploaded').NBS.NBS.$MP->uploads.'<br />';
				$message .= $this->EE->lang->line('pings_sent').NBS.NBS.$MP->pings_sent.'<br />';

				if (count($MP->message_array) > 0)
				{
					$message .= $MP->errors();
					$error = TRUE;
				}

				$this->EE->session->set_flashdata(array('message' => $message, 'error' => $error));
				$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog');
			}			
		}

		$this->EE->session->set_flashdata(array('message' => $MP->errors(), 'error' => TRUE));
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog');
	}

	
	/** ---------------------------------------------------------------
	/**  Fetch ping servers
	/** ---------------------------------------------------------------*/
	// This function displays the ping server checkboxes
	//---------------------------------------------------------------
		
	function fetch_ping_servers($selected = '', $type = 'update')
	{
		$sent_pings = array();

		if ($selected != '')
		{
			$sent_pings = explode('|', $selected);
		}
		
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
		$count = $this->EE->db->count_all_results('ping_servers');

		$member_id = ($count  == 0) ? 0 : $this->EE->session->userdata('member_id');
		
		$this->EE->db->select('id, server_name, is_default, server_url');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->where('member_id', $member_id);
		$this->EE->db->order_by('server_order');
		
		$query = $this->EE->db->get('ping_servers');

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$r = array();
		$done = array();
		
		foreach($query->result_array() as $row)
		{
			// Because of multiple sites a member might have multiple Ping Servers with the same
			// URL.  The moblog is a module and does not recognize Sites like that, so we simply
			// show all Ping Servers from all Sites, but remove duplicate ones based on the Server URL
			if (in_array($row['server_url'], $done))
			{
				continue;
			}
			
			$done[] = $row['server_url'];
		
			if (count($sent_pings) > 0)
			{
				$selected = (in_array($row['id'], $sent_pings)) ? 1 : '';
			}
			elseif($type == 'submit')
			{
				$selected = ($row['is_default'] == 'y') ? TRUE : FALSE;
			}
			
			$r[$row['id']] = array($row['server_name'], $selected);
		}

		return $r;
	}
}
// END CLASS

/* End of file mcp.moblog.php */
/* Location: ./system/expressionengine/modules/moblog/mcp.moblog.php */