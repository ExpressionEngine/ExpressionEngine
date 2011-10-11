<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

// --------------------------------------------------------------------

/**
 * ExpressionEngine Channel Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Channel_standalone extends Channel {

	var $categories 		= array();
	var $cat_parents 		= array();
	var $assign_cat_parent 	= FALSE;
	var $upload_div 		= '';
	var $output_js 			= NULL;
	var $default_entry_title = '';
	var $url_title_prefix 	= '';
	var $_installed_mods	= array('smileys' => FALSE, 'spellcheck' => FALSE);
	var $theme_url;

	// --------------------------------------------------------------------	

	/**
	 * Run Filemanager
	 *
	 * @param 	string	
	 * @param	array
	 * @return 	mixed
	 */
	function run_filemanager($function = '', $params = array())
	{
		$this->EE->load->library('filemanager');
		$this->EE->lang->loadfile('content');
		
		$config = array();
		
		$this->EE->filemanager->_initialize($config);
		
		return call_user_func_array(array($this->EE->filemanager, $function), $params);
	}

	// --------------------------------------------------------------------	

	/**
	 * Insert New Channel entry
	 *
	 * This function serves dual purpose:
	 * 1. It allows submitted data to be previewed
	 * 2. It allows submitted data to be inserted
	 */
	function insert_new_entry()
	{
		$this->EE->lang->loadfile('channel');
		$this->EE->lang->loadfile('content');
		$this->EE->load->model('field_model');
		$this->EE->load->model('channel_model');

		// Ya gotta be logged-in billy bob...
		if ($this->EE->session->userdata('member_id') == 0)
		{
			return $this->EE->output->show_user_error('general', $this->EE->lang->line('channel_must_be_logged_in'));
		}

		if ( ! $channel_id = $this->EE->input->post('channel_id') OR ! is_numeric($channel_id))
		{
			return false;
		}

		// Prep file fields
		$file_fields = array();
		
		$this->EE->db->select('field_group');
		$this->EE->db->where('channel_id', $channel_id);
		$query = $this->EE->db->get('channels');
		
		if ($query->num_rows() > 0)
		{
			$row = $query->row();
			$field_group =  $row->field_group;
		
			$this->EE->db->select('field_id');
			$this->EE->db->where('group_id', $field_group);
			$this->EE->db->where('field_type', 'file');
			
			$f_query = $this->EE->db->get('channel_fields');
			
			if ($f_query->num_rows() > 0)
			{
				foreach ($f_query->result() as $row)
				{
   					$file_fields[] = $row->field_id;
				}
			} 
		} 

		foreach ($file_fields as $v)
		{
			if (isset($_POST['field_id_'.$v.'_hidden']))
			{
				$_POST['field_id_'.$v] = $_POST['field_id_'.$v.'_hidden'];
				if ( ! $this->EE->input->post('preview'))
				{
					unset($_POST['field_id_'.$v.'_hidden']);
				}
			}

			// Upload or maybe just a path in the hidden field?
			if (isset($_FILES['field_id_'.$v]) && $_FILES['field_id_'.$v]['size'] > 0 && isset($_POST['field_id_'.$v.'_directory']))
			{
				$data = $this->run_filemanager('upload_file', array($_POST['field_id_'.$v.'_directory'], 'field_id_'.$v));
					
				if (array_key_exists('error', $data))
				{
					die('error '.$data['error']);
				}
				else
				{
					$_POST['field_id_'.$v] = $data['name'];
					
					if ($this->EE->input->post('preview') !== FALSE)
					{
						$_POST['field_id_'.$v.'_hidden'] = $data['name'];
					}
				}
			}
		}

		/** ----------------------------------------
		/**  Prep data for insertion
		/** ----------------------------------------*/
		if ( ! $this->EE->input->post('preview'))
		{
			$this->EE->load->library('api');
			$this->EE->api->instantiate(array('channel_entries', 'channel_categories', 'channel_fields'));
			
			unset($_POST['hidden_pings']);
			unset($_POST['status_id']);
			unset($_POST['allow_cmts']);
			unset($_POST['sticky_entry']);
			
			$return_url	= ( ! $this->EE->input->post('return_url')) ? '' : $this->EE->input->get_post('return_url');
			unset($_POST['return_url']);

			
			if ( ! $this->EE->input->post('entry_date'))
			{
				$_POST['entry_date'] = $this->EE->localize->set_human_time($this->EE->localize->now);
			}

			$data = $_POST;
			
			
			
			// Rudimentary handling of custom fields
			
			$field_query = $this->EE->channel_model->get_channel_fields($field_group);
			
			$dst_enabled = $this->EE->session->userdata('daylight_savings');
			$dst_enabled = ( ! isset($_POST['dst_enabled'])) ? 'n' :  $dst_enabled;
			
			foreach ($field_query->result_array() as $row)
			{
				$field_data = '';
				$field_dt = '';
				$field_fmt	= $row['field_fmt'];


				// Settings that need to be prepped			
				$settings = array(
					'field_instructions'	=> trim($row['field_instructions']),
					'field_text_direction'	=> ($row['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr',
					'field_fmt'				=> $field_fmt,
					'field_dt'				=> $field_dt,
					'field_data'			=> $field_data,
					'field_name'			=> 'field_id_'.$row['field_id'],
					'dst_enabled'			=> $dst_enabled
				);

				$ft_settings = array();

				if (isset($row['field_settings']) && strlen($row['field_settings']))
				{
					$ft_settings = unserialize(base64_decode($row['field_settings']));
				}

				$settings = array_merge($row, $settings, $ft_settings);

				$this->EE->api_channel_fields->set_settings($row['field_id'], $settings);
			}
			
			
			$extra = array(
				'url_title'		=> '',
				'ping_errors'	=> FALSE,
				'revision_post'	=> $_POST,
				);
		
			// Fetch xml-rpc ping server IDs
			$data['ping_servers'] = array();
		
			if (isset($_POST['ping']) && is_array($_POST['ping']))
			{
				$data['ping_servers'] = $_POST['ping'];	
				unset($_POST['ping']);		
			}
		
			$data = array_merge($extra, $data);
	
			$success = $this->EE->api_channel_entries->submit_new_entry($channel_id, $data);

			if ( ! $success)
			{
				$errors = $this->EE->api_channel_entries->errors;
				return $this->EE->output->show_user_error('general', $errors);
			}
		
			if ($this->EE->api_channel_entries->get_errors('pings'))
			{
				return FALSE;
			}

			$loc = ($return_url == '') ? $this->EE->functions->fetch_site_index() : $this->EE->functions->create_url($return_url, 1, 0);

			$loc = $this->EE->api_channel_entries->trigger_hook('entry_submission_redirect', $loc);

			$this->EE->functions->redirect($loc);
		} // END Insert


		/** ----------------------------------------
		/**  Preview Entry
		/** ----------------------------------------*/

		if ($this->EE->input->post('PRV') == '')
		{
			$this->EE->lang->loadfile('channel');

			return $this->EE->output->show_user_error('general', $this->EE->lang->line('channel_no_preview_template'));
		}

		$this->EE->functions->clear_caching('all', $_POST['PRV']);

		require APPPATH.'libraries/Template.php';

		$this->EE->TMPL = new EE_Template();

		$preview = ( ! $this->EE->input->post('PRV')) ? '' : $this->EE->input->get_post('PRV');

		if (strpos($preview, '/') === FALSE)
		{
			return FALSE;
		}

		$ex = explode("/", $preview);

		if (count($ex) != 2)
		{
			return FALSE;
		}

		$this->EE->TMPL->run_template_engine($ex['0'], $ex['1']);
	}

	// --------------------------------------------------------------------	

	/**
	 * Stand-alone version of the entry form
	 *
	 * @param 	mixed
	 * @param 	string
	 * @return 	mixed
	 */
	function entry_form($return_form = FALSE, $captcha = '')
	{
		$field_data		= '';
		$cat_list		= '';
		$status			= '';
		$title 			= '';
		$url_title 		= '';
		
		// No loggy? No looky...
		if ($this->EE->session->userdata('member_id') == 0) { 
			return; 
		}
	  
		if ( ! $channel = $this->EE->TMPL->fetch_param('channel'))
		{
			return $this->EE->output->show_user_error('general', $this->EE->lang->line('channel_not_specified'));
	  	}
	  
	  	// Fetch the action ID number.  Even though we don't need it until later
	  	// we'll grab it here.  If not found it means the action table doesn't
	  	// contain the ID, which means the user has not updated properly.  Ya know?
	  	if ( ! $insert_action = $this->EE->functions->fetch_action_id('Channel', 'insert_new_entry'))
	  	{
			return $this->EE->output->show_user_error('general', $this->EE->lang->line('channel_no_action_found'));
	  	}
	
		$this->theme_url = $this->EE->config->item('theme_folder_url').'cp_themes/'.$this->EE->config->item('cp_theme').'/';
	
		// Load some helpers, language files & libraries.
		// Doing this after error checking since it makes no sense 
		// To load a bunch of things up if we're just going to error
		$this->EE->lang->loadfile('channel');
		$this->EE->load->helper('form');
		$this->EE->load->library('spellcheck');
		
		$assigned_channels = $this->EE->functions->fetch_assigned_channels();
		$channel_id = ( ! $this->EE->TMPL->fetch_param('channel')) ? '' : $this->EE->TMPL->fetch_param('channel');
		
		if ($channel_id != '')
		{
			$this->EE->db->select('channel_id');
			$this->EE->db->where_in('site_id', $this->EE->TMPL->site_ids);
			$this->EE->db->where('channel_name', $channel);
			$query = $this->EE->db->get('channels');
			
			if ($query->num_rows() == 1)
			{
				$channel_id = $query->row('channel_id') ;
			}
		}
		
		// Security Check
		if ( ! in_array($channel_id, $assigned_channels))
		{
			return $this->EE->TMPL->no_results();
		}

		// Fetch Channel Preferences
		$this->EE->db->where('channel_id', $channel_id);
		$channel_q = $this->EE->db->get('channels');
	
		if ( ! isset($_POST['channel_id']))
		{
			$title		= $channel_q->row('default_entry_title');
			$url_title	= $channel_q->row('url_title_prefix');
			
			$this->default_entry_title 	= $channel_q->row('default_entry_title');
			$this->url_title_prefix		= $channel_q->row('url_title_prefix');
		}
		
		// Return 'no cache' version of form
		if ($return_form === FALSE)
		{
			$nc = '{{NOCACHE_CHANNEL_FORM ';

			if (count($this->EE->TMPL->tagparams) > 0)
			{
				foreach ($this->EE->TMPL->tagparams as $key => $val)
				{
					$nc .= ' '.$key.'="'.$val.'" ';
				}
			}
			
			$nc .= '}}'.$this->EE->TMPL->tagdata.'{{/NOCACHE_FORM}}';

			return $nc;
		}
		
		$default_entry_title = form_prep($channel_q->row('default_entry_title'));
		
		$action_id = $this->EE->functions->fetch_action_id('Channel', 'filemanager_endpoint');
		$endpoint = 'ACT='.$action_id;
		
		$this->EE->load->library('filemanager');
		$this->EE->load->library('javascript');
		$this->EE->load->model('admin_model');
		$this->EE->load->model('tools_model');
		$this->EE->load->model('channel_model');
		
		// Onward...
		$which = ($this->EE->input->post('preview')) ? 'preview' : 'new';
		
		$upload_directories = $this->EE->tools_model->get_upload_preferences($this->EE->session->userdata('group_id'));

		$file_list = array();
		$directories = array();

		foreach($upload_directories->result() as $row)
		{
			$directories[$row->id] = $row->name;

			$file_list[$row->id]['id'] = $row->id;
			$file_list[$row->id]['name'] = $row->name;
			$file_list[$row->id]['url'] = $row->url;
		}

		// Fetch Custom Fields
		// $field_query = $this->EE->channel_model->get_channel_fields($field_group);
		
		$html_buttons = $this->EE->admin_model->get_html_buttons($this->EE->session->userdata('member_id'));
		$button_js = array();

		foreach ($html_buttons->result() as $button)
		{
			if (strpos($button->classname, 'btn_img') !== FALSE)
			{
				// images are handled differently because of the file browser
				// at least one image must be available for this to work
				if (count($file_list) > 0)
				{
					$button_js[] = array(
										'name' 			=> $button->tag_name, 
										'key' 			=> $button->accesskey, 
										'replaceWith' 	=> '', 
										'className' 	=> $button->classname
								);
				}
			}
			elseif(strpos($button->classname, 'markItUpSeparator') !== FALSE)
			{
				// separators are purely presentational
				$button_js[] = array('separator' => '---');
			}
			else
			{
				$button_js[] = array(
									'name' 		=> $button->tag_name, 
									'key' 		=> strtoupper($button->accesskey), 
									'openWith' 	=> $button->tag_open, 
									'closeWith' => $button->tag_close, 
									'className' => $button->classname
								);
			}
		}
		
		$markItUp = $markItUp_writemode = array(
			'nameSpace'		=> "html",
			'onShiftEnter'	=> array('keepDefault' => FALSE, 'replaceWith' => "<br />\n"),
			'onCtrlEnter'	=> array('keepDefault' => FALSE, 'openWith' => "\n<p>", 'closeWith' => "</p>\n"),
			'markupSet'		=> $button_js,
		);

		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- allow_textarea_tabs => Add tab preservation to all textareas or disable completely
		/* -------------------------------------------*/
		
		if ($this->EE->config->item('allow_textarea_tabs') == 'y')
		{
			$markItUp['onTab'] = array('keepDefault' => FALSE, 'replaceWith' => "\t");
			$markItUp_writemode['onTab'] = array('keepDefault' => FALSE, 'replaceWith' => "\t");
		}
		elseif ($this->EE->config->item('allow_textarea_tabs') != 'n')
		{
			$markItUp_writemode['onTab'] = array('keepDefault' => FALSE, 'replaceWith' => "\t");
		}
		
		// Are smiley's & spellcheck Enabled?
		$this->EE->db->select('COUNT(*) as count');
		$this->EE->db->where('module_name', 'Emoticon');
		$smiley = $this->EE->db->get('modules');
		
		$this->_installed_mods['smileys'] = ((int) $smiley->row('count') == 1) ? TRUE : FALSE;

		// -------------------------------------------
		//	Publish Page Title Focus - makes the title field gain focus when the page is loaded
		//
		//	Hidden Configuration Variable - publish_page_title_focus => Set focus to the tile? (y/n)		
		$addt_js = array(
				'publish'		=> array(
							'show_write_mode' 	=> (($channel_q->row('show_button_cluster') == 'y') ? TRUE : FALSE),
							'title_focus'		=> (($which != 'edit' && $this->EE->config->item('publish_page_title_focus') !== 'n') ? TRUE : FALSE),
							'smileys'			=> ($this->_installed_mods['smileys']) ? TRUE : FALSE),
							
				'user_id'		=> $this->EE->session->userdata('member_id'),
				'lang'			=> array(
							'confirm_exit'			=> $this->EE->lang->line('confirm_exit'),
							'add_new_html_button'	=> $this->EE->lang->line('add_new_html_button')
					)		
			);
		
		$this->EE->lang->loadfile('content');
		$this->_setup_js($endpoint, $markItUp, $markItUp_writemode, $addt_js);
		
		/** ----------------------------------------
		/**  Compile form declaration and hidden fields
		/** ----------------------------------------*/

		$RET = (isset($_POST['RET'])) ? $_POST['RET'] : $this->EE->functions->fetch_current_uri();
		$XID = ( ! isset($_POST['XID'])) ? '' : $_POST['XID'];
		$PRV = (isset($_POST['PRV'])) ? $_POST['PRV'] : '{PREVIEW_TEMPLATE}';

		$hidden_fields = array(
								'ACT'	  				=> $insert_action,
								'RET'	  				=> $RET,
								'PRV'	  				=> $PRV,
								'URI'	  				=> ($this->EE->uri->uri_string == '') ? 'index' : $this->EE->uri->uri_string,
								'XID'	  				=> $XID,
								'return_url'			=> (isset($_POST['return_url'])) ? $_POST['return_url'] : $this->EE->TMPL->fetch_param('return'),
								'author_id'				=> $this->EE->session->userdata('member_id'),
								'channel_id'			=> $channel_id,
								'entry_id'				=> 0
							  );
		
		$status_id = ( ! isset($_POST['status_id'])) ? $this->EE->TMPL->fetch_param('status') : $_POST['status_id'];

		if ($status_id == 'Open' OR $status_id == 'Closed')
		{
			$status_id = strtolower($status_id);			
		}

		$this->EE->db->where('group_id', $channel_q->row('status_group'));
		$this->EE->db->order_by('status_order');
		$status_query = $this->EE->db->get('statuses');

		if ($status_id != '')
		{
			$closed_flag = TRUE;

			if ($status_query->num_rows() > 0)
			{  
				foreach ($status_query->result_array() as $row)
				{
					if ($row['status'] == $status_id)
						$closed_flag = FALSE;
				}
			}

			$hidden_fields['status'] = ($closed_flag == TRUE) ? 'closed' : $status_id;
		}

		/** ----------------------------------------
		/**  Add "allow" options
		/** ----------------------------------------*/

		$allow_cmts = ( ! isset($_POST['allow_cmts'])) ? $this->EE->TMPL->fetch_param('allow_comments') : $_POST['allow_cmts'];

		if ($allow_cmts != '' && $channel_q->row('comment_system_enabled') == 'y')
		{
			$hidden_fields['allow_comments'] = ($allow_cmts == 'yes') ? 'y' : 'n';
		}

		$sticky_entry = ( ! isset($_POST['sticky_entry'])) ? $this->EE->TMPL->fetch_param('sticky_entry') : $_POST['sticky_entry'];

		if ($sticky_entry != '')
		{
			$hidden_fields['sticky'] = ($sticky_entry == 'yes') ? 'y' : 'n';
		}

		/** ----------------------------------------
		/**  Add categories to hidden fields
		/** ----------------------------------------*/
		if ($category_id = $this->EE->TMPL->fetch_param('category'))
		{
			if (isset($_POST['category']))
			{
				foreach ($_POST as $key => $val)
				{
					if (strpos($key, 'category') !== FALSE && is_array($val))
					{
						$i = 0;
						foreach ($val as $v)
						{
							$hidden_fields['category['.($i++).']'] = $v;
						}
					}
				}
			}
			else
			{
				if (strpos($category_id, '|') === FALSE)
				{
					$hidden_fields['category[]'] = $category_id;
				}
				else
				{
					$i = 0;

					foreach(explode("|", trim($category_id, '|')) as $val)
					{
						$hidden_fields['category['.($i++).']'] = $val;
					}
				}
			}
		}

		/** ----------------------------------------
		/**  Add pings to hidden fields
		/** ----------------------------------------*/

		$hidden_pings = ( ! isset($_POST['hidden_pings'])) ? $this->EE->TMPL->fetch_param('hidden_pings') : $_POST['hidden_pings'];

		if ($hidden_pings == 'yes')
		{
			$hidden_fields['hidden_pings'] = 'yes';

			$ping_servers = $this->fetch_ping_servers('new');

			if (is_array($ping_servers) AND count($ping_servers) > 0)
			{
				$i = 0;
				foreach ($ping_servers as $val)
				{
					if ($val['1'] != '')
					{
						$hidden_fields['ping['.($i++).']'] = $val['0'];						
					}
				}
			}
		}
		
		// Parse out the tag
		$tagdata = $this->EE->TMPL->tagdata;	

		// Fetch Custom Fields
		if ($this->EE->TMPL->fetch_param('show_fields') !== FALSE)
		{
			if (strncmp($this->EE->TMPL->fetch_param('show_fields'), 'not ', 4) == 0)
			{
				$this->EE->db->where_not_in('field_name', explode('|', trim(substr($this->EE->TMPL->fetch_param('show_fields'), 3))));			
			}
			else
			{
				$this->EE->db->where_in('field_name', explode('|', trim($this->EE->TMPL->fetch_param('show_fields'))));
			}
		}

		$this->EE->db->where('group_id', $channel_q->row('field_group'));
		$this->EE->db->order_by('field_order');
		$cf_query = $this->EE->db->get('channel_fields');

		$fields = array();
		$date_fields = array();
		$pair_fields = array();
		$pfield_chunk = array();
		$cond = array();
		
		if ($which == 'preview')
		{ 
			foreach ($cf_query->result_array() as $row)
			{
				$fields['field_id_'.$row['field_id']] = array($row['field_name'], 	
															$row['field_type']);
															$cond[$row['field_name']] = '';
				if ($row['field_type'] == 'date')
				{
					$date_fields[$row['field_name']] = $row['field_id'];
				}
				elseif (in_array($row['field_type'], array('file', 'multi_select', 'checkboxes')))
				{
					$pair_fields[$row['field_name']] = array($row['field_type'], $row['field_id']);
				}
			}
		}

		if (preg_match("#".LD."preview".RD."(.+?)".LD.'/'."preview".RD."#s", $tagdata, $match))
		{
			$tagdata = $this->_parse_preview($which, $match, $tagdata, 
											 $pair_fields, $date_fields, $fields, $channel_q);
			// This ends preview parsing- 
			// it's the only spot we need to parse custom fields that are funky.
		}
		
		// Fetch {custom_fields} chunk
		
		$custom_fields = '';
		$file_allowed = (count($directories) > 0) ? TRUE : FALSE;
		
		if (preg_match("#".LD."custom_fields".RD."(.+?)".LD.'/'."custom_fields".RD."#s", $tagdata, $match))
		{
			$custom_fields = trim($match['1']);

			$tagdata = str_replace($match['0'], LD.'temp_custom_fields'.RD, $tagdata);
			
			if ($custom_fields != '')
			{
				$tagdata = $this->_parse_custom_fields($custom_fields, $cf_query, 
														$which, $tagdata, $directories);
			}
		}
		
		// Categories
		if (preg_match("#".LD."category_menu".RD."(.+?)".LD.'/'."category_menu".RD."#s", $tagdata, $match))
		{
			$this->_category_tree_form($channel_q->row('cat_group'), $which, 
										$channel_q->row('deft_category'), $cat_list);

			if (count($this->categories) == 0)
			{
				$tagdata = str_replace ($match['0'], '', $tagdata);
			}
			else
			{
				$c = '';
				foreach ($this->categories as $val)
				{
					$c .= $val;
				}

				$match['1'] = str_replace(LD.'select_options'.RD, $c, $match['1']);
				$tagdata = str_replace ($match['0'], $match['1'], $tagdata);
			}
		}

		// Ping Servers
		if (preg_match("#".LD."ping_servers".RD."(.+?)".LD.'/'."ping_servers".RD."#s", $tagdata, $match))
		{
			$field = (preg_match("#".LD."ping_row".RD."(.+?)".LD.'/'."ping_row".RD."#s", $tagdata, $match1)) ? $match1['1'] : '';

			if ( ! isset($match1['0']))
			{
				$tagdata = str_replace ($match['0'], '', $tagdata);
			}

			$ping_servers = $this->fetch_ping_servers($which);

			if ( ! is_array($ping_servers) OR count($ping_servers) == 0)
			{
				$tagdata = str_replace ($match['0'], '', $tagdata);
			}
			else
			{
				$ping_build = '';

				foreach ($ping_servers as $val)
				{
					$temp = $field;

					$temp = str_replace(LD.'ping_value'.RD, $val['0'], $temp);
					$temp = str_replace(LD.'ping_checked'.RD, $val['1'], $temp);
					$temp = str_replace(LD.'ping_server_name'.RD, $val['2'], $temp);

					$ping_build .= $temp;
				}

				$match['1'] = str_replace ($match1['0'], $ping_build, $match['1']);
				$tagdata = str_replace ($match['0'], $match['1'], $tagdata);
			}
		}
		
		// Status
		if (preg_match("#".LD."status_menu".RD."(.+?)".LD.'/'."status_menu".RD."#s", $tagdata, $match))
		{
			if (isset($_POST['status']))
			{
				$deft_status = $_POST['status'];				
			}

			if ($channel_q->row('deft_status') == '')
			{
				$deft_status = 'open';				
			}

			if ($status == '')
			{
				$status = $channel_q->row('deft_status');
			}

			/** --------------------------------
			/**  Fetch disallowed statuses
			/** --------------------------------*/

			$no_status_access = array();

			if ($this->EE->session->userdata['group_id'] != 1)
			{
				$this->EE->db->select('status_id');
				$this->EE->db->where('member_group', $this->EE->session->userdata('group_id'));
				$status_na_q = $this->EE->db->get('status_no_access');
				
				if ($status_na_q->num_rows() > 0)
				{
					foreach ($status_na_q->result_array() as $row)
					{
						$no_status_access[] = $row['status_id'];
					}
				}
			}

			/** --------------------------------
			/**  Create status menu
			/** --------------------------------*/

			$r = '';

			if ($status_query->num_rows() == 0)
			{
				// if there is no status group assigned, only Super Admins can create 'open' entries
				if ($this->EE->session->userdata['group_id'] == 1)
				{
					$selected = ($status == 'open') ? " selected='selected'" : '';
					$r .= "<option value='open'".$selected.">".$this->EE->lang->line('open')."</option>";
				}

				$selected = ($status == 'closed') ? " selected='selected'" : '';
				$r .= "<option value='closed'".$selected.">".$this->EE->lang->line('closed')."</option>";
			}
			else
			{
				$no_status_flag = TRUE;

				foreach ($status_query->result_array() as $row)
				{
					$selected = ($status == $row['status']) ? " selected='selected'" : '';

					if ($selected != 1)
					{
						if (in_array($row['status_id'], $no_status_access))
						{
							continue;
						}
					}

					$no_status_flag = FALSE;

					$status_name = ($row['status'] == 'open' OR $row['status'] == 'closed') ? $this->EE->lang->line($row['status']) : $row['status'];

					$r .= "<option value='".form_prep($row['status'])."'".$selected.">". form_prep($status_name)."</option>\n";
				}

				if ($no_status_flag == TRUE)
				{
					$tagdata = str_replace ($match['0'], '', $tagdata);
				}
			}


			$match['1'] = str_replace(LD.'select_options'.RD, $r, $match['1']);
			$tagdata = str_replace ($match['0'], $match['1'], $tagdata);
		}
		
		foreach ($this->EE->TMPL->var_single as $key => $val)
		{
			/** ----------------------------------------
			/**  {title}
			/** ----------------------------------------*/

			if ($key == 'title')
			{
				$title = ( ! isset($_POST['title'])) ? $title : $_POST['title'];

				$tagdata = $this->EE->TMPL->swap_var_single($key, form_prep($title), $tagdata);
				
				if ($this->EE->TMPL->fetch_param('use_live_url') == 'no')
				{
					$tagdata = str_replace('liveUrlTitle();', '', $tagdata);
				}
				
			}

			/** ----------------------------------------
			/**  {allow_comments}
			/** ----------------------------------------*/

			if ($key == 'allow_comments')
			{
				if ($which == 'preview')
				{
					$checked = ( ! isset($_POST['allow_comments']) OR $channel_q->row('comment_system_enabled') != 'y') ? '' : "checked='checked'";
				}
				else
				{
					$checked = ($channel_q->row('deft_comments') == 'n' OR
					 		$channel_q->row('comment_system_enabled') != 'y') ? '' : "checked='checked'";
				}

				$tagdata = $this->EE->TMPL->swap_var_single($key, $checked, $tagdata);
			}

			/** ----------------------------------------
			/**  {dst_enabled}
			/** ----------------------------------------*/

			if ($key == 'dst_enabled')
			{
				$checked = ($this->EE->session->userdata('daylight_savings') == 'y') ? "checked='checked'" : '';
				$tagdata = $this->EE->TMPL->swap_var_single($key, $checked, $tagdata);
			}

			/** ----------------------------------------
			/**  {sticky}
			/** ----------------------------------------*/

			if ($key == 'sticky')
			{
				$checked = '';

				if ($which == 'preview')
				{
					$checked = ( ! isset($_POST['sticky'])) ? '' : "checked='checked'";
				}

				$tagdata = $this->EE->TMPL->swap_var_single($key, $checked, $tagdata);
			}

			/** ----------------------------------------
			/**  {url_title}
			/** ----------------------------------------*/
			if ($key == 'url_title')
			{
				$url_title = ( ! isset($_POST['url_title'])) ? $url_title : $_POST['url_title'];

				$tagdata = $this->EE->TMPL->swap_var_single($key, $url_title, $tagdata);
			}

			/** ----------------------------------------
			/**  {entry_date}
			/** ----------------------------------------*/
			if ($key == 'entry_date')
			{
				$entry_date = ( ! isset($_POST['entry_date'])) ? $this->EE->localize->set_human_time($this->EE->localize->now) : $_POST['entry_date'];

				$tagdata = $this->EE->TMPL->swap_var_single($key, $entry_date, $tagdata);
			}

			/** ----------------------------------------
			/**  {expiration_date}
			/** ----------------------------------------*/
			if ($key == 'expiration_date')
			{
				$expiration_date = ( ! isset($_POST['expiration_date'])) ? '': $_POST['expiration_date'];

				$tagdata = $this->EE->TMPL->swap_var_single($key, $expiration_date, $tagdata);
			}

			/** ----------------------------------------
			/**  {comment_expiration_date}
			/** ----------------------------------------*/
			if ($key == 'comment_expiration_date')
			{
				$comment_expiration_date = '';

				if ($which == 'preview')
				{
						$comment_expiration_date = ( ! isset($_POST['comment_expiration_date'])) ? '' : $_POST['comment_expiration_date'];
				}
				else
				{
					if ($channel_q->row('comment_expiration') > 0)
					{
						$comment_expiration_date = $channel_q->row('comment_expiration') * 86400;
						$comment_expiration_date = $comment_expiration_date + $this->EE->localize->now;
						$comment_expiration_date = $this->EE->localize->set_human_time($comment_expiration_date);
					}
				}

				$tagdata = $this->EE->TMPL->swap_var_single($key, $comment_expiration_date, $tagdata);

			}
			
			/** ----------------------------------------
			/**  {saef_javascript}
			/** ----------------------------------------*/
			
			if ($key == 'saef_javascript')
			{
				$js = '<script type="text/javascript" charset="utf-8">// <![CDATA[ '."\n"; 

				foreach ($this->output_js['json'] as $key => $val)
				{
					if ($js == 'EE')
					{
						$js .= 'if (typeof EE == "undefined" || ! EE) {'."\n".
							'var EE = '.$this->EE->javascript->generate_json($val, TRUE)."\n".
							"}\n";
					}
					else 
					{
						$js .= $this->EE->javascript->generate_json($val, TRUE);
					}
				}

				$js .= "\n".' // ]]>'."\n".'</script>';
				$js .= $this->output_js['str'];
				
				$tagdata = $this->EE->TMPL->swap_var_single($key, $js, $tagdata);
				unset($this->output_js);
			}
			
		}
		
		$data = array(
						'hidden_fields' => $hidden_fields,
						'action'		=> $RET,
						'id'			=> 'publishForm',
						'class'			=> $this->EE->TMPL->form_class,
						'enctype' 		=> 'multi'
						);

		$res  = $this->EE->functions->form_declaration($data);
		
		// our Json string will go here if it hasn't been put in by {saef_javascript}
		if (isset($this->output_js))
		{
			$res .= '<script type="text/javascript" charset="utf-8">// <![CDATA[ '."\n"; 
			
			foreach ($this->output_js['json'] as $key => $val)
			{
				if ($key == 'EE')
				{
					$res .= 'if (typeof EE == "undefined" || ! EE) { var EE = '.$this->EE->javascript->generate_json($val).";}\n";
				}
				else 
				{
					$res .= $key.' = ' . $this->EE->javascript->generate_json($val, TRUE) . ";\n";
				}
			}
			
			$res .= "\n".' // ]]>'."\n".'</script>';
			$res .= $this->output_js['str'];
		}

		$res .= stripslashes($tagdata);
		$res .= "</form>";
		
		$res .= $this->_writemode_markup();

		return $res;

	}
	
	// --------------------------------------------------------------------	

	/**
	 * Category Tree
	 *
	 * This function (and the next) create a hierarchy tree
	 * of categories.
	 *
	 * @param 	integer
	 * @param 	integer
	 * @param	mixed
	 * @param	mixed
	 */
	function _category_tree_form($group_id = '', $action = '', $default = '', $selected = '')
	{
		// Fetch category group ID number
		if ($group_id == '')
		{
			if ( ! $group_id = $this->EE->input->get_post('group_id'))
			{
				return FALSE;
			}
		}

		// If we are using the category list on the "new entry" page
		// we need to gather the selected categories so we can highlight
		// them in the form.
		if ($action == 'preview')
		{
			$catarray = array();

			foreach ($_POST as $key => $val)
			{
				if (strpos($key, 'category') !== FALSE && is_array($val))
				{
						foreach ($val as $k => $v)
						{
							$catarray[$v] = $v;
						}
				}
			}
		}

		if ($action == 'edit')
		{
			$catarray = array();

			if (is_array($selected))
			{
				foreach ($selected as $key => $val)
				{
					$catarray[$val] = $val;
				}
			}
		}

		// Fetch category groups
		$group_ids = explode('|', $group_id);
		
		$this->EE->db->select('cat_name, cat_id, parent_id');
		$this->EE->db->where_in('group_id', $group_ids);
		$this->EE->db->order_by('group_id, parent_id, cat_order');
		$kitty_query = $this->EE->db->get('categories');

		if ($kitty_query->num_rows() == 0)
		{
			return FALSE;
		}

		// Assign the query result to a multi-dimensional array

		foreach($kitty_query->result_array() as $row)
		{
			$cat_array[$row['cat_id']]  = array($row['parent_id'], $row['cat_name']);
		}

		$size = count($cat_array) + 1;

		// Build our output...

		$sel = '';

		foreach($cat_array as $key => $val)
		{
			if (0 == $val['0'])
			{
				if ($action == 'new')
				{
					$sel = ($default == $key) ? '1' : '';
				}
				else
				{
					$sel = (isset($catarray[$key])) ? '1' : '';
				}

				$s = ($sel != '') ? " selected='selected'" : '';

				$this->categories[] = "<option value='".$key."'".$s.">".$val['1']."</option>\n";

				$this->_category_subtree_form($key, $cat_array, $depth=1, $action, $default, $selected);
			}
		}
	}


	// --------------------------------------------------------------------	

	/**
	 * Category sub-tree
	 *
	 * This function works with the preceeding one to show a
	 * hierarchical display of categories
	 *
	 * @param 	integer
	 * @param	array
	 * @param	integer
	 * @param	mixed
	 * @param 	mixed
	 * @param	mixed
	 */
	function _category_subtree_form($cat_id, $cat_array, $depth, $action, $default = '', $selected = '')
	{
		$spcr = "&nbsp;";

		// Just as in the function above, we'll figure out which items are selected.
		if ($action == 'preview')
		{
			$catarray = array();

			foreach ($_POST as $key => $val)
			{
				if (strpos($key, 'category') !== FALSE && is_array($val))
				{
					foreach ($val as $k => $v)
					{
						$catarray[$v] = $v;
					}
				}
			}
		}

		if ($action == 'edit')
		{
			$catarray = array();

			if (is_array($selected))
			{
				foreach ($selected as $key => $val)
				{
					$catarray[$val] = $val;
				}
			}
		}

		$indent = $spcr.$spcr.$spcr.$spcr;

		if ($depth == 1)
		{
			$depth = 4;
		}
		else
		{
			$indent = str_repeat($spcr, $depth).$indent;

			$depth = $depth + 4;
		}

		$sel = '';

		foreach ($cat_array as $key => $val)
		{
			if ($cat_id == $val['0'])
			{
				$pre = ($depth > 2) ? "&nbsp;" : '';

				if ($action == 'new')
				{
					$sel = ($default == $key) ? '1' : '';
				}
				else
				{
					$sel = (isset($catarray[$key])) ? '1' : '';
				}

				$s = ($sel != '') ? " selected='selected'" : '';

				$this->categories[] = "<option value='".$key."'".$s.">".$pre.$indent.$spcr.$val['1']."</option>\n";

				$this->_category_subtree_form($key, $cat_array, $depth, $action, $default, $selected);
			}
		}
	}

	// --------------------------------------------------------------------	

	/**
	 * Fetch ping servers
	 *
	 * This function displays the ping server checkboxes
	 *
	 * @param 	string
	 * @return 	array
	 */
	function fetch_ping_servers($which = 'new')
	{
		$this->EE->db->select('COUNT(*) as count');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
		$pingq = $this->EE->db->get('ping_servers');
		
		$member_id = ($pingq->row('count')  == 0) ? 0 : $this->EE->session->userdata('member_id');

		$this->EE->db->select('id, server_name, is_default');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$this->EE->db->where('member_id', $member_id);
		$this->EE->db->order_by('server_order');
		$pingq = $this->EE->db->get('ping_servers');

		if ($pingq->num_rows() == 0)
		{
			return FALSE;
		}

		$ping_array = array();

		foreach($pingq->result_array() as $row)
		{
			if (isset($_POST['preview']))
			{
				$selected = '';
				foreach ($_POST as $key => $val)
				{
					if (strpos($key, 'ping') !== FALSE && $val == $row['id'])
					{
						$selected = " checked='checked' ";
						break;
					}
				}
			}
			else
			{
				$selected = ($row['is_default'] == 'y') ? " checked='checked' " : '';
			}


			$ping_array[] = array($row['id'], $selected, $row['server_name']);
		}


		return $ping_array;
	}

	// --------------------------------------------------------------------

	/**
	 * Combo Loaded Javascript for the Stand-Alone Entry Form
	 *
	 * Given the heafty amount of javascript needed for this form, we don't
	 * want to kill page speeds, so we're going to combo load what is needed
	 *
	 * @return void
	 */
	function saef_javascript()
	{
		$scripts = array(
				'ui'		=> array('core', 'widget', 'button', 'dialog'),
				'plugins'	=> array('scrollable', 'scrollable.navigator', 
										'ee_filebrowser', 'markitup',
										'thickbox')
			);

		$type = ($this->EE->config->item('use_compressed_js') == 'n') ? 'src' : 'compressed';

		if ( ! defined('PATH_JQUERY'))
		{			
			define('PATH_JQUERY', PATH_THEMES.'javascript/'.$type.'/jquery/');
		}
		
		$output = '';
		
		foreach ($scripts as $key => $val)
		{
			foreach ($val as $script)
			{
				$filename = ($key == 'ui') ? 'jquery.ui.'.$script.'.js' : $script.'.js';
				
				$output .= file_get_contents(PATH_JQUERY.$key.'/'.$filename)."\n";
			}
		}
		
		if ($this->EE->input->get('use_live_url') == 'y')
		{
			$output .= $this->_url_title_js();
		}
		
		$this->EE->load->helper('smiley');
		
		$output .= ($this->EE->config->item('use_compressed_js') != 'n') ? str_replace(array("\n", "\t"), '', smiley_js('', '', FALSE)) : smiley_js('', '', FALSE);

		$output .= file_get_contents(PATH_THEMES.'javascript/'.$type.'/saef.js');

		$this->EE->output->out_type = 'cp_asset';
		$this->EE->output->set_header("Content-Type: text/javascript");
		
		$this->EE->output->set_header('Content-Length: '.strlen($output));
		$this->EE->output->set_output($output);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Setup SAEF Javascript
	 */
	function _setup_js($endpoint, $markItUp, $markItUp_writemode, $addt_js)
	{
		$include_jquery = ($this->EE->TMPL->fetch_param('include_jquery') == 'no') ? FALSE : TRUE;

		$this->EE->load->library('filemanager');
		
		$js = $this->EE->filemanager->frontend_filebrowser($endpoint, $include_jquery);

		$json = array_merge_recursive($js['json'], $addt_js);

		$this->output_js['json'] = array(
					'EE'					=> $json,
					'mySettings'			=> $markItUp,
					'myWritemodeSettings'	=> $markItUp_writemode
			);

		$this->output_js['str'] = $js['str'];
	}
	
	// --------------------------------------------------------------------	
	
	/**
	 * Parse Preview
	 *
	 */
	function _parse_preview($which, $match, $tagdata, $pair_fields, $date_fields, $fields, $channel_q)
	{
		if ($which != 'preview')
		{
			$tagdata = str_replace($match['0'], '', $tagdata);
			return $tagdata;
		}

		// Snag out the possible pair chunks (file, multiselect and checkboxes)
		foreach ($pair_fields as $field_name => $field_info)
		{
			if (($end = strpos($match['1'], LD.'/'.$field_name.RD)) !== FALSE)
			{
				if (preg_match_all("/".LD."{$field_name}(.*?)".RD."(.*?)".LD.'\/'.$field_name.RD."/s", $match['1'], $pmatches))
				{
					for ($j = 0; $j < count($pmatches[0]); $j++)
					{
						$chunk = $pmatches[0][$j];
						$params = $pmatches[1][$j];
						$inner = $pmatches[2][$j];
						
						// We might've sandwiched a single tag - no good, check again (:sigh:)
						if ((strpos($chunk, LD.$field_name, 1) !== FALSE) && preg_match_all("/".LD."{$field_name}(.*?)".RD."/s", $chunk, $pmatch))
						{
							// Let's start at the end
							$idx = count($pmatch[0]) - 1;
							$tag = $pmatch[0][$idx];
							
							// Cut the chunk at the last opening tag (PHP5 could do this with strrpos :-( )
							while (strpos($chunk, $tag, 1) !== FALSE)
							{
								$chunk = substr($chunk, 1);
								$chunk = strstr($chunk, LD.$field_name);
								$inner = substr($chunk, strlen($tag), -strlen(LD.'/'.$field_name.RD));
							}
						}

						$pfield_chunk['field_id_'.$field_info['1']][] = array($inner,
							 										$this->EE->functions->assign_parameters($params), $chunk);
					}
				}
			}
		}

		/** ----------------------------------------
		/**  Instantiate Typography class
		/** ----------------------------------------*/

		$this->EE->load->library('typography');
		$this->EE->typography->initialize(array(
				'convert_curly'	=> FALSE)
				);

		$file_dirs = $this->EE->functions->fetch_file_paths();

		$match['1'] = str_replace(LD.'title'.RD, stripslashes($this->EE->input->post('title')), $match['1']);

		// We need to grab each
		$str = '';

		foreach($_POST as $key => $val)
		{
			if (strncmp($key, 'field_id', 8) == 0)
			{
				// do pair variables
				if (isset($pfield_chunk[$key]))
				{
					
					$expl = explode('field_id_', $key);
					$txt_fmt = ( ! isset($_POST['field_ft_'.$expl['1']])) ? 'xhtml' : $_POST['field_ft_'.$expl['1']];
											
					// Blast through all the chunks
					foreach($pfield_chunk[$key] as $chk_data)
					{
						$tpl_chunk = '';
						$limit = FALSE;
						
						// Limit Parameter
						if (is_array($chk_data[1]) AND isset($chk_data[1]['limit']))
						{
							$limit = $chk_data[1]['limit'];
						}

						foreach($val as $k => $item)
						{
							if ( ! $limit OR $k < $limit)
							{
								$vars['item'] = $item;
								$vars['count'] = $k + 1;	// {count} parameter

								$tmp = $this->EE->functions->prep_conditionals($chk_data[0], $vars);
								$tpl_chunk .= $this->EE->functions->var_swap($tmp, $vars);
							}
							else
							{
								break;
							}
						}

						// Everybody loves backspace
						if (is_array($chk_data[1]) AND isset($chk_data[1]['backspace']))
						{
							$tpl_chunk = substr($tpl_chunk, 0, - $chk_data[1]['backspace']);
						}

					}
					
					// Typography!
					$tpl_chunk = $this->EE->typography->parse_type(
										$this->EE->functions->encode_ee_tags($tpl_chunk),
								 		array(
											'text_format'   => $txt_fmt,
											'html_format'   => $channel_q->row('channel_html_formatting'),
											'auto_links'    => $channel_q->row('channel_allow_img_urls'),
											'allow_img_url' => $channel_q->row('channel_auto_link_urls')
									   		)
						);

					// Replace the chunk
					if (isset($fields[$key]['0']))
					{
						$match['1'] = str_replace($chk_data[2], $tpl_chunk, $match['1']);
					}
				}

				// end pair variables						
				
				$expl = explode('field_id_', $key);
				$temp = '';
				if (! is_numeric($expl['1'])) continue;

				if (in_array($expl['1'], $date_fields))
				{
					$temp_date = $this->EE->localize->convert_human_date_to_gmt($_POST['field_id_'.$expl['1']]);
					$temp = $_POST['field_id_'.$expl['1']];
					$cond[$fields['field_id_'.$expl['1']]['0']] =  $temp_date;
				}
				elseif ($fields['field_id_'.$expl['1']]['1'] == 'file')
				{
					$file_info['path'] = '';
					$file_info['extension'] = '';
					$file_info['filename'] = '';
					$full_path = '';
					$entry = '';

					if ($val != '')
					{
						$parts = explode('.', $val);
						$file_info['extension'] = array_pop($parts);
						$file_info['filename'] = implode('.', $parts);

						if (isset($_POST['field_id_'.$expl['1'].'_directory']) && isset($_POST['field_id_'.$expl['1']]) && $_POST['field_id_'.$expl['1']] != '')
						{
							$file_info['path'] = $file_dirs[$_POST['field_id_'.$expl['1'].'_directory']];
						}

						$full_path = $file_info['path'].$file_info['filename'].'.'.$file_info['extension'];
					}
					
					if (preg_match_all("/".LD.$fields['field_id_'.$expl['1']]['0']."(.*?)".RD."/s", $match['1'], $pmatches))
					{
						foreach ($pmatches['0'] as $id => $tag)
						{
							if ($pmatches['1'][$id] == '')
							{
								$entry = $full_path;
							}
							else
							{
								$params = $this->EE->functions->assign_parameters($pmatches['1'][$id]);
								
								if (isset($params['wrap']) && $params['wrap'] == 'link')
								{
									$entry = '<a href="'.$full_path.'">'.$file_info['filename'].'</a>';
								}
								elseif (isset($params['wrap']) && $params['wrap'] == 'image')
								{
									$entry = '<img src="'.$full_path.'" alt="'.$file_info['filename'].'" />';
								}
								else
								{
									$entry = $full_path;
								}
							}
								
							$match['1'] = str_replace($pmatches['0'][$id], $entry, $match['1']);
						}
					}
					
					$str .= '<p>'.$full_path.'</p>';
				}
				elseif (in_array($fields['field_id_'.$expl['1']]['1'], array('multi_select', 'checkboxes')))
				{
					$entry = implode(', ', $val);
						
					$cond[$fields['field_id_'.$expl['1']]['0']] =  ( ! isset($_POST['field_id_'.$expl['1']])) ? '' : $entry;

					$txt_fmt = ( ! isset($_POST['field_ft_'.$expl['1']])) ? 'xhtml' : $_POST['field_ft_'.$expl['1']];
						
					if (preg_match_all("/".LD.$fields['field_id_'.$expl['1']]['0']."(.*?)".RD."/s", $match['1'], $pmatches))
					{
						foreach ($pmatches['0'] as $id => $tag)
						{
							if ($pmatches['1'][$id] == '')
							{
								
							}
							else
							{
								$params = $this->EE->functions->assign_parameters($pmatches['1'][$id]);

								if (isset($params['limit']))
								{
									$limit = intval($params['limit']);
					
									if (count($val) > $limit)
									{
										$val = array_slice($val, 0, $limit);
									}
								}

								if (isset($params['markup']) && ($params['markup'] == 'ol' OR $params['markup'] == 'ul'))
								{
									$entry = '<'.$params['markup'].'>';
						
									foreach($val as $dv)
									{
										$entry .= '<li>';
										$entry .= $dv;
										$entry .= '</li>';
									}

									$entry .= '</'.$params['markup'].'>';
								}
							}

							$entry = $this->EE->typography->parse_type(
									$this->EE->functions->encode_ee_tags($entry),
								 		array(
											'text_format'   => $txt_fmt,
											'html_format'   => $channel_q->row('channel_html_formatting'),
											'auto_links'    => $channel_q->row('channel_allow_img_urls'),
											'allow_img_url' => $channel_q->row('channel_auto_link_urls')
									   		)
								);

							$match['1'] = str_replace($pmatches['0'][$id], $entry, $match['1']);
						}
					}

					$str .= '<p>'.$entry.'</p>';
				}
				elseif (! is_array($val))
				{
					if (isset($fields['field_id_'.$expl['1']]))
					{
					
						$cond[$fields['field_id_'.$expl['1']]['0']] =  ( ! isset($_POST['field_id_'.$expl['1']])) ? '' : $_POST['field_id_'.$expl['1']];

						$txt_fmt = ( ! isset($_POST['field_ft_'.$expl['1']])) ? 'xhtml' : $_POST['field_ft_'.$expl['1']];

						$temp = $this->EE->typography->parse_type( stripslashes($val),
									 		array(
												'text_format'   => $txt_fmt,
												'html_format'   => $channel_q->row('channel_html_formatting'),
												'auto_links'    => $channel_q->row('channel_allow_img_urls'),
												'allow_img_url' => $channel_q->row('channel_auto_link_urls')
										   		)
										);
					}
				}

				if (isset($fields[$key]['0']))
				{
					$match['1'] = str_replace(LD.$fields[$key]['0'].RD, $temp, $match['1']);
				}

				$str .= $temp;
			//}
			
			
			// end non pair fields
			}
		}

		$match['1'] = str_replace(LD.'display_custom_fields'.RD, $str, $match['1']);
		$match['1'] = $this->EE->functions->prep_conditionals($match['1'], $cond);
		$tagdata = str_replace ($match['0'], $match['1'], $tagdata);

		
		return $tagdata;
	}

	// --------------------------------------------------------------------	
	
	/**
	 * Parse Custom Fields
	 *
	 *
	 *
	 *
	 */
	function _parse_custom_fields($custom_fields, $query, $which, $tagdata, $directories)
	{
		$field_array = array(
							'textarea', 'textinput', 'pulldown', 'multiselect', 
							'checkbox', 'radio', 'file', 'date', 'relationship', 
							'file');

		$formatting_toolbar 	= '';
		$textarea 				= '';
		$textinput 				= '';
		$pulldown				= '';
		$multiselect			= '';
		$checkbox				= '';
		$radio					= '';
		$file					= '';
		$file_options			= '';
		$file_pulldown			= '';			
		$date					= '';
		$relationship 			= '';
		$rel_options 			= '';
		$pd_options				= '';
		$multi_options 			= '';
		$check_options 			= '';
		$radio_options 			= '';
		$required				= '';

		foreach ($field_array as $val)
		{
			if (preg_match("#".LD."\s*if\s+".$val.RD."(.+?)".LD.'/'."if".RD."#s", $custom_fields, $match))
			{
				$$val = $match['1'];

				if ($val == 'pulldown')
				{
					if (preg_match("#".LD."options".RD."(.+?)".LD.'/'."options".RD."#s", $pulldown, $pmatch))
					{
						$pd_options = $pmatch['1']; 
						$pulldown = str_replace ($pmatch['0'], LD.'temp_pd_options'.RD, $pulldown);
					}
				}

				if ($val == 'file')
				{
					if (preg_match("#".LD."options".RD."(.+?)".LD.'/'."options".RD."#s", $file, $pmatch))
					{
						$file_options = $pmatch['1']; 
						$file = str_replace ($pmatch['0'], LD.'temp_file_options'.RD, $file);
					}
				}


				if ($val == 'multiselect')
				{
					if (preg_match("#".LD."options".RD."(.+?)".LD.'/'."options".RD."#s", $multiselect, $pmatch))
					{
						$multi_options = $pmatch['1'];
						$multiselect = str_replace ($pmatch['0'], LD.'temp_multi_options'.RD, $multiselect);
					}
				}

				if ($val == 'checkbox')
				{
					if (preg_match("#".LD."options".RD."(.+?)".LD.'/'."options".RD."#s", $checkbox, $pmatch))
					{
						$check_options = $pmatch['1'];
						$checkbox = str_replace ($pmatch['0'], LD.'temp_check_options'.RD, $checkbox);
					}
				}

				if ($val == 'radio')
				{
					if (preg_match("#".LD."options".RD."(.+?)".LD.'/'."options".RD."#s", $radio, $pmatch))
					{
						$radio_options = $pmatch['1'];
						$radio = str_replace ($pmatch['0'], LD.'temp_radio_options'.RD, $radio);
					}
				}

				if ($val == 'relationship')
				{
					if (preg_match("#".LD."options".RD."(.+?)".LD.'/'."options".RD."#s", $relationship, $pmatch))
					{
						$rel_options = $pmatch['1'];
						$relationship = str_replace ($pmatch['0'], LD.'temp_rel_options'.RD, $relationship);
					}
				}

				$custom_fields = str_replace($match['0'], LD.'temp_'.$val.RD, $custom_fields);
			}
		}

		if (preg_match("#".LD."if\s+required".RD."(.+?)".LD.'/'."if".RD."#s", $custom_fields, $match))
		{
			$required = $match['1'];

			$custom_fields = str_replace($match['0'], LD.'temp_required'.RD, $custom_fields);
		}
		
		/** --------------------------------
		/**  Parse Custom Fields
		/** --------------------------------*/

		$build = '';

		foreach ($query->result_array() as $row)
		{
			$settings = unserialize(base64_decode($row['field_settings']));
			$temp_chunk = $custom_fields;
			$temp_field = '';

			switch ($which)
			{
				case 'preview' :
						$field_data = ( ! isset( $_POST['field_id_'.$row['field_id']] )) ?  '' : $_POST['field_id_'.$row['field_id']];
						$field_fmt  = ( ! isset( $_POST['field_ft_'.$row['field_id']] )) ? $row['field_fmt'] : $_POST['field_ft_'.$row['field_id']];
					break;
				/* no edits or $result in the SAEF - leftover from old CP Publish class
				case 'edit'	:
						$field_data = ($result->row('field_id_'.$row['field_id']) !== FALSE) ? '' : $result->row('field_id_'.$row['field_id']);
						$field_fmt  = ($result->row('field_ft_'.$row['field_id']) !== FALSE) ? $row['field_fmt'] : $result->row('field_ft_'.$row['field_id']);
					break;
				*/
				default		:
						$field_data = '';
						$field_fmt  = $row['field_fmt'];
					break;
			}

			$temp_chunk = $this->_build_format_buttons($temp_chunk, $row, $settings);
			
			if (isset($settings['field_show_spellcheck']) && $settings['field_show_spellcheck'] == 'n')
			{
				$temp_chunk = $this->_build_spellcheck($temp_chunk, $row, $settings);
			}

			if ($row['field_type'] == 'textarea' AND $textarea != '')
			{
				$temp_chunk = str_replace(LD.'temp_textarea'.RD, $textarea, $temp_chunk);
			}

			if ($row['field_type'] == 'text' AND $textinput != '')
			{
				$temp_chunk = str_replace(LD.'temp_textinput'.RD, $textinput, $temp_chunk);
			}

			if ($row['field_type'] == 'file' AND $file != '')
			{
					$pdo = '';

					$file_dir = ( ! isset( $_POST['field_id_'.$row['field_id'].'_directory'] )) ?  '' : $_POST['field_id_'.$row['field_id'].'_directory'];
					$filename = ( ! isset( $_POST['field_id_'.$row['field_id'].'_hidden'] )) ?  '' : $_POST['field_id_'.$row['field_id'].'_hidden'];

					$file_div = 'hold_field_'.$row['field_id'];
					$file_set = 'file_set';

					if ($filename == '')
					{
						$file_set .= ' js_hide';
					}

					$options = '';

					foreach ($directories as $k => $v)
					{
						$temp_options = $file_options;
						$selected = ($k == $file_dir) ? ' selected="selected"' : '';
						$options .= '<option value="'.$k.'"'.$selected.'>'.trim($v).'</option>';

						$pdo .= $temp_options;
					}

					$file = '<div class="publish_field">';
					$file .= '<div class="file_set js_hide">';
					$file .= '<p class="filename">';
					$file .= '<img src="'.$this->EE->config->item('theme_folder_url').'/cp_global_images/default.png" alt="default thumbnail" />';
					$file .= '</p>';
					$file .= '<p class="sub_filename"><a href="#" class="remove_file">'.$this->EE->lang->line('remove_file').'</a></p>';
					$file .= '<p><input type="hidden" name="field_id_'.$row['field_id'].'_hidden" value="'.$field_data.'" /></p>';
					$file .= '</div>'; 
					$file .= '<div class="no_file js_hide">';
					$file .= '<p><input type="file" name="field_id_'.$row['field_id'].'" value="'.$field_data.'" /></p>';
					$file .= '<p><select name="field_id_'.$row['field_id'].'_directory">'.$options.'</select></p>' ;
					
					$file .= '</div>';
					$file .= '<div class="modifiers js_show">';
					$file .= '<p class="sub_filename"><a href="#" class="choose_file">'.$this->EE->lang->line('add_file').'</a></p>';
					$file .= '</div></div>';

					$temp_chunk = str_replace(LD.'temp_file'.RD, $file, $temp_chunk);
					
					// $temp_file = str_replace(LD.'temp_file_options'.RD, $pdo, $file);
					// $temp_file = str_replace(LD.'file_name'.RD, $filename, $temp_file);
					// $temp_file = str_replace(LD.'file_set'.RD, $file_set, $temp_file);
					// $temp_file = str_replace(LD.'file_div'.RD, $file_div, $temp_file);			
					// $temp_chunk = str_replace(LD.'temp_file'.RD, $temp_file, $temp_chunk); 
			}	

			if ($row['field_type'] == 'rel')
			{
				if ($row['field_related_orderby'] == 'date')
				{
					$row['field_related_orderby'] = 'entry_date';						
				}

				$this->EE->db->select('entry_id, title');
				$this->EE->db->where('channel_id', $row['field_related_id']);
				$this->EE->db->order_by($row['field_related_orderby'], $row['field_related_sort']);

				if ($row['field_related_max'] > 0)
				{
					$this->EE->db->limit($row['field_related_max']);
				}

				$relquery = $this->EE->db->get('channel_titles');

				if ($relquery->num_rows() > 0)
				{
					$relentry_id = '';
					if ( ! isset($_POST['field_id_'.$row['field_id']]))
					{
						$this->EE->db->select('rel_child_id');
						$this->EE->db->where('rel_id', $field_data);
						$relentry = $this->EE->db->get('relationships');

						if ($relentry->num_rows() == 1)
						{
							$relentry_id = $relentry->row('rel_child_id') ;
						}
					}
					else
					{
						$relentry_id = $_POST['field_id_'.$row['field_id']];
					}

					$temp_options = $rel_options;
					$temp_options = str_replace(LD.'option_name'.RD, '--', $temp_options);
					$temp_options = str_replace(LD.'option_value'.RD, '', $temp_options);
					$temp_options = str_replace(LD.'selected'.RD, '', $temp_options);
					$pdo = $temp_options;

					foreach ($relquery->result_array() as $relrow)
					{
						$temp_options = $rel_options;
						$temp_options = str_replace(LD.'option_name'.RD, $relrow['title'], $temp_options);
						$temp_options = str_replace(LD.'option_value'.RD, $relrow['entry_id'], $temp_options);
						$temp_options = str_replace(LD.'selected'.RD, ($relentry_id == $relrow['entry_id']) ? ' selected="selected"' : '', $temp_options);

						$pdo .= $temp_options;
					}

					$temp_relationship = str_replace(LD.'temp_rel_options'.RD, $pdo, $relationship);
					$temp_chunk = str_replace(LD.'temp_relationship'.RD, $temp_relationship, $temp_chunk);
				}
			}
			
			// Date Fields
			if ($row['field_type'] == 'date' AND $date != '')
			{
				$temp_chunk = $custom_fields;

				$date_field = 'field_id_'.$row['field_id'];
				$date_local = 'field_dt_'.$row['field_id'];

				$dtwhich = $which;
				if (isset($_POST[$date_field]))
				{
					$field_data = $_POST[$date_field];
					$dtwhich = 'preview';
				}

				$custom_date = '';
				$localize = FALSE;
				
				if ($dtwhich != 'preview')
				{
					$localize = TRUE;

					if ($field_data != '')
					{
						$custom_date = $this->EE->localize->set_human_time($field_data, $localize);						
					}

					$cal_date = ($this->EE->localize->set_localized_time($custom_date) * 1000);
				}
				else
				{
					$custom_date = $_POST[$date_field];
					$cal_date = ($custom_date != '') ? ($this->EE->localize->set_localized_time($this->EE->localize->convert_human_date_to_gmt($custom_date)) * 1000) : ($this->EE->localize->set_localized_time() * 1000);
				}
				
				$temp_chunk = str_replace(LD.'temp_date'.RD, $date, $temp_chunk);
				$temp_chunk = str_replace(LD.'date'.RD, $custom_date, $temp_chunk);
			}
			elseif ($row['field_type'] == 'select' AND $pulldown != '')
			{
				if ($row['field_pre_populate'] == 'n')
				{
					$pdo = '';

					if ($row['field_required'] == 'n')
					{
						$temp_options = $pd_options;
												
						$temp_options = str_replace(LD.'option_name'.RD, '--', $temp_options);
						$temp_options = str_replace(LD.'option_value'.RD, '', $temp_options);
						$temp_options = str_replace(LD.'selected'.RD, '', $temp_options);
						$pdo = $temp_options;
					}

					foreach (explode("\n", trim($row['field_list_items'])) as $v)
					{
						$temp_options = $pd_options;

						$v = trim($v);
						$temp_options = str_replace(LD.'option_name'.RD, $v, $temp_options);
						$temp_options = str_replace(LD.'option_value'.RD, $v, $temp_options);
						$temp_options = str_replace(LD.'selected'.RD, ($v == $field_data) ? ' selected="selected"' : '', $temp_options);

						$pdo .= $temp_options;
					}

					$temp_pulldown = str_replace(LD.'temp_pd_options'.RD, $pdo, $pulldown);
					$temp_chunk = str_replace(LD.'temp_pulldown'.RD, $temp_pulldown, $temp_chunk); 
				}
				else
				{
					// We need to pre-populate this menu from an another channel custom field
					$this->EE->db->select('field_id_'.$row['field_pre_field_id']);
					$this->EE->db->where('channel_id', $row['field_pre_channel_id']);
					$this->EE->db->where('field_id_'.$row['field_pre_field_id'].' != ""');
					$pop_query = $this->EE->db->get('channel_data');

					if ($pop_query->num_rows() > 0)
					{
						$temp_options = $rel_options;
						$temp_options = str_replace(LD.'option_name'.RD, '--', $temp_options);
						$temp_options = str_replace(LD.'option_value'.RD, '', $temp_options);
						$temp_options = str_replace(LD.'selected'.RD, '', $temp_options);
						$pdo = $temp_options;

						foreach ($pop_query->result_array() as $prow)
						{
							$pretitle = substr($prow['field_id_'.$row['field_pre_field_id']], 0, 110);
							$pretitle = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $pretitle);
							$pretitle = form_prep($pretitle);

							$temp_options = $pd_options;
							$temp_options = str_replace(LD.'option_name'.RD, $pretitle, $temp_options);
							$temp_options = str_replace(LD.'option_value'.RD, form_prep($prow['field_id_'.$row['field_pre_field_id']]), $temp_options);
							$temp_options = str_replace(LD.'selected'.RD, ($prow['field_id_'.$row['field_pre_field_id']] == $field_data) ? ' selected="selected"' : '', $temp_options);
							$pdo .= $temp_options;
						}

						$temp_pulldown = str_replace(LD.'temp_pd_options'.RD, $pdo, $pulldown);
						$temp_chunk = str_replace(LD.'temp_pulldown'.RD, $temp_pulldown, $temp_chunk);
					}
				}
			}
			elseif ($row['field_type'] == 'multi_select' AND $multiselect != '')
			{
				if ($row['field_pre_populate'] == 'n')
				{
					$pdo = '';

					if ($row['field_required'] == 'n')
					{
						$temp_options = $multi_options;
						$temp_options = str_replace(LD.'option_name'.RD, '--', $temp_options);
						$temp_options = str_replace(LD.'option_value'.RD, '', $temp_options);
						$temp_options = str_replace(LD.'selected'.RD, '', $temp_options);
						$pdo = $temp_options;
					}

					foreach (explode("\n", trim($row['field_list_items'])) as $v)
					{
						$temp_options = $multi_options;

						$v = trim($v);
						$temp_options = str_replace(LD.'option_name'.RD, $v, $temp_options);
						$temp_options = str_replace(LD.'option_value'.RD, $v, $temp_options);
						$temp_options = str_replace(LD.'selected'.RD, (is_array($field_data) && in_array($v, $field_data)) ? ' selected="selected"' : '', $temp_options);
						$pdo .= $temp_options;
					}

					$temp_multiselect = str_replace(LD.'temp_multi_options'.RD, $pdo, $multiselect);
					$temp_chunk = str_replace(LD.'temp_multiselect'.RD, $temp_multiselect, $temp_chunk);
				}

				else
				{
					// We need to pre-populate this menu from an another channel custom field
					$this->EE->db->select('field_id_'.$row['field_pre_field_id']);
					$this->EE->db->where('channel_id', $row['field_pre_channel_id']);
					$this->EE->db->where('field_id_'.$row['field_pre_field_id'].' != ""');
					$pop_query = $this->EE->db->get('channel_data');

					if ($pop_query->num_rows() > 0)
					{
						$temp_options = $multi_options;
						$temp_options = str_replace(LD.'option_name'.RD, '--', $temp_options);
						$temp_options = str_replace(LD.'option_value'.RD, '', $temp_options);
						$temp_options = str_replace(LD.'selected'.RD, '', $temp_options);
						$pdo = $temp_options;

						foreach ($pop_query->result_array() as $prow)
						{
							if (trim($prow['field_id_'.$row['field_pre_field_id']]) != '')
							{
								$pretitle = substr(trim($prow['field_id_'.$row['field_pre_field_id']]), 0, 110);
								$pretitle = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $pretitle);
								$pretitle = form_prep($pretitle);

								$temp_options = $multi_options;
								$temp_options = str_replace(LD.'option_name'.RD, $pretitle, $temp_options);
								$temp_options = str_replace(LD.'option_value'.RD, form_prep($prow['field_id_'.$row['field_pre_field_id']]), $temp_options);
								$temp_options = str_replace(LD.'selected'.RD, (is_array($field_data) && in_array($prow['field_id_'.$row['field_pre_field_id']], $field_data)) ? ' selected="selected"' : '', $temp_options);
								$pdo .= $temp_options;
							}
						}

						$temp_multiselect = str_replace(LD.'temp_multi_options'.RD, $pdo, $multiselect);
						$temp_chunk = str_replace(LD.'temp_multiselect'.RD, $temp_multiselect, $temp_chunk);
					}
				}
			}

			elseif ($row['field_type'] == 'checkboxes' AND $checkbox != '')
			{
				if ($row['field_pre_populate'] == 'n')
				{
					$pdo = '';

					foreach (explode("\n", trim($row['field_list_items'])) as $v)
					{
						$temp_options = $check_options;

						$v = trim($v);
						$temp_options = str_replace(LD.'option_name'.RD, $v, $temp_options);
						$temp_options = str_replace(LD.'option_value'.RD, $v, $temp_options);
						$temp_options = str_replace(LD.'checked'.RD, (is_array($field_data) && in_array($v, $field_data)) ? ' checked ' : '', $temp_options);

						$pdo .= $temp_options;
					}

					$temp_checkbox = str_replace(LD.'temp_check_options'.RD, $pdo, $checkbox);
					$temp_chunk = str_replace(LD.'temp_checkbox'.RD, $temp_checkbox, $temp_chunk);
				}

				else
				{
					// We need to pre-populate this menu from an another channel custom field
					$this->EE->db->select('field_id_'.$row['field_pre_field_id']);
					$this->EE->db->where('channel_id', $row['field_pre_channel_id']);
					$this->EE->db->where('field_id_'.$row['field_pre_field_id'].' != ""');
					$pop_query = $this->EE->db->get('channel_data');

					if ($pop_query->num_rows() > 0)
					{
						$pdo = '';

						foreach ($pop_query->result_array() as $prow)
						{
							$pretitle = substr(trim($prow['field_id_'.$row['field_pre_field_id']]), 0, 110);
							$pretitle = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $pretitle);
							$pretitle = form_prep($pretitle);

							$temp_options = $check_options;
							$temp_options = str_replace(LD.'option_name'.RD, $pretitle, $temp_options);
							$temp_options = str_replace(LD.'option_value'.RD, form_prep($prow['field_id_'.$row['field_pre_field_id']]), $temp_options);
							$temp_options = str_replace(LD.'checked'.RD, (is_array($field_data) && in_array($prow['field_id_'.$row['field_pre_field_id']], $field_data)) ? ' checked ' : '', $temp_options);
							$pdo .= $temp_options;
						}

						$temp_checkbox = str_replace(LD.'temp_check_options'.RD, $pdo, $checkbox);
						$temp_chunk = str_replace(LD.'temp_checkbox'.RD, $temp_checkbox, $temp_chunk);
					}
				}
			}
			elseif ($row['field_type'] == 'radio' AND $radio != '')
			{
				if ($row['field_pre_populate'] == 'n')
				{
					$pdo = '';

					foreach (explode("\n", trim($row['field_list_items'])) as $v)
					{
						$temp_options = $radio_options;

						$v = trim($v);
						$temp_options = str_replace(LD.'option_name'.RD, $v, $temp_options);
						$temp_options = str_replace(LD.'option_value'.RD, $v, $temp_options);
						$temp_options = str_replace(LD.'checked'.RD, ($v == $field_data) ? ' checked ' : '', $temp_options);

						$pdo .= $temp_options;
					}

					$temp_radio = str_replace(LD.'temp_radio_options'.RD, $pdo, $radio);
					$temp_chunk = str_replace(LD.'temp_radio'.RD, $temp_radio, $temp_chunk);
				}
				else
				{
					// We need to pre-populate this menu from an another channel custom field
					$this->EE->db->select('field_id_'.$row['field_pre_field_id']);
					$this->EE->db->where('channel_id', $row['field_pre_channel_id']);
					$this->EE->db->where('field_id_'.$row['field_pre_field_id'].' != ""');
					$pop_query = $this->EE->db->get('channel_data');

					if ($pop_query->num_rows() > 0)
					{
						$pdo = '';

						foreach ($pop_query->result_array() as $prow)
						{
							$pretitle = substr($prow['field_id_'.$row['field_pre_field_id']], 0, 110);
							$pretitle = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $pretitle);
							$pretitle = form_prep($pretitle);

							$temp_options = $radio_options;
							$temp_options = str_replace(LD.'option_name'.RD, $pretitle, $temp_options);
							$temp_options = str_replace(LD.'option_value'.RD, form_prep($prow['field_id_'.$row['field_pre_field_id']]), $temp_options);
							$temp_options = str_replace(LD.'checked'.RD, ($prow['field_id_'.$row['field_pre_field_id']] == $field_data) ? ' checked ' : '', $temp_options);
							$pdo .= $temp_options;
						}

						$temp_radio = str_replace(LD.'temp_radio_options'.RD, $pdo, $radio);
						$temp_chunk = str_replace(LD.'temp_radio'.RD, $temp_radio, $temp_chunk);
					}
				}
			}


			if ($row['field_required'] == 'y')
			{
				$temp_chunk = str_replace(LD.'temp_required'.RD, $required, $temp_chunk);
			}
			else
			{
				$temp_chunk = str_replace(LD.'temp_required'.RD, '', $temp_chunk);
			}

			if (is_array($field_data))
			{

			}
			else
			{
				$temp_chunk = str_replace(LD.'field_data'.RD, form_prep($field_data), $temp_chunk);					
			}

			$temp_chunk = str_replace(LD.'path:cp_global_img'.RD,
						$this->EE->config->item('theme_folder_url').'/cp_global_images/', $temp_chunk);
			$temp_chunk = str_replace(LD.'formatting_buttons'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'spellcheck'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'temp_date'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'temp_textarea'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'temp_relationship'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'temp_textinput'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'temp_file'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'temp_file_options'.RD, '', $temp_chunk);

			$temp_chunk = str_replace(LD.'temp_pulldown'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'temp_pd_options'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'temp_multiselect'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'temp_multi_options'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'temp_checkbox'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'temp_check_options'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'temp_radio'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'temp_radio_options'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'calendar_link'.RD, '', $temp_chunk);
			$temp_chunk = str_replace(LD.'calendar_id'.RD, '', $temp_chunk);

			$temp_chunk = str_replace(LD.'rows'.RD, ( ! isset($row['field_ta_rows'])) ? '10' : $row['field_ta_rows'], $temp_chunk);
			$temp_chunk = str_replace(LD.'field_label'.RD, $row['field_label'], $temp_chunk);
			$temp_chunk = str_replace(LD.'field_instructions'.RD, $row['field_instructions'], $temp_chunk);
			$temp_chunk = str_replace(LD.'text_direction'.RD, $row['field_text_direction'], $temp_chunk);
			$temp_chunk = str_replace(LD.'maxlength'.RD, $row['field_maxl'], $temp_chunk);
			$temp_chunk = str_replace(LD.'field_name'.RD, 'field_id_'.$row['field_id'], $temp_chunk);
			$temp_chunk = str_replace(LD.'field_name_directory'.RD, 'field_id_'.$row['field_id'].'_directory', $temp_chunk);				

			$hidden_fields['field_ft_'.$row['field_id']] = $field_fmt;
			// $temp_chunk .= "\n<input type='hidden' name='field_ft_".$row['field_id']."' value='".$field_fmt."' />\n";

			$build .= $temp_chunk;
		}

		$tagdata = str_replace(LD.'temp_custom_fields'.RD, $build, $tagdata);

		return $tagdata;
	}

	// --------------------------------------------------------------------	
	
	/**
	 * Build Formatting Buttons
	 *
	 * This function replaces the {formatting_buttons} variables and
	 * adds the field to the global json array if formatting btns is set to yes & if the var is present
	 *
	 * @param string
	 * @param array
	 * @param array
	 * @return string
	 */
	function _build_format_buttons($chunk, $row, $settings)
	{
		if (strpos($chunk, LD.'formatting_buttons'.RD) !== FALSE)
		{
			if (isset($settings['field_show_formatting_btns']) && $settings['field_show_formatting_btns'] == 'y')
			{
				$this->output_js['json']['EE']['publish']['markitup']['fields']['field_id_'.$row['field_id']] = $row['field_id'];
			}
		}

		return str_replace(LD.'formatting_buttons'.RD, '', $chunk);
	}

	// --------------------------------------------------------------------	

	/**
	 * Build Spellcheck 
	 *
	 * @param string
	 * @param array
	 * @param array
	 */
	function _build_spellcheck($chunk, $row, $settings)
	{
		/*
		array
		  'field_show_smileys' => string 'y' (length=1)
		  'field_show_glossary' => string 'y' (length=1)
		  'field_show_spellcheck' => string 'y' (length=1)
		  'field_show_formatting_btns' => string 'y' (length=1)
		  'field_show_file_selector' => string 'y' (length=1)
		  'field_show_writemode' => string 'y' (length=1)
		*/
		// Unset formatting buttons choice, we've already dealt with it.
		unset($settings['field_show_formatting_btns']);
				
		foreach ($settings as $key => $val)
		{
			if ($val == 'n')
			{
				unset($settings[$key]);
			}
		}
		
		if (empty($settings))
		{
			return $chunk;
		}
		
		$output = '<div class="spellcheck markitup">';

		// Commented out for the time being while we decide on what to do regarding
		// Thickbox.  @see _writemode_markup();
		// if (isset($settings['field_show_writemode']))
		// {
		// 	$output .= '<a href="#TB_inline?height=100%'.AMP.'width=100%'.AMP.'modal=true'.AMP.'inlineId=write_mode_container" class="write_mode_trigger thickbox" id="id_'.$row['field_id'].'"><img src="'.$this->theme_url.'images/publish_write_mode.png" /></a>';
		// }

		if (isset($settings['field_show_file_selector']))
		{
			$output .= '<a href="#" class="markItUpButton">
			<img class="file_manipulate js_show" src="'.$this->theme_url.'images/publish_format_picture.gif" alt="'.lang('file').'" /></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}

		if ($this->_installed_mods['spellcheck'] && isset($settings['field_show_spellcheck']))
		{
			$id = (ctype_digit($row['field_id'])) ? 'field_id_' : '';
			
			$output .= '<a href="#" class="spellcheck_link" id="'.$id.$row['field_id'].'" title="'.lang('check_spelling').'"><img src="'.$this->theme_url.'images/spell_check_icon.png" alt="'.lang('check_spelling').'" /></a>';
			
			// $output .= '<a href="#" class="spellcheck_link" id="spelltrigger_'.(ctype_digit($row['field_id']))?'field_id_':''.$row['field_id'].'" title="'.lang('check_spelling').'"><img src="images/spell_check_icon.png" style="margin-bottom: -8px;" alt="'.lang('check_spelling').'" /></a>';
		}
		
		if (isset($settings['field_show_glossary']))
		{
			$output .= '<a href="#" class="glossary_link" title="'.lang('glossary').'"><img src="'.$this->theme_url.'images/spell_check_glossary.png" alt="'.lang('glossary').'" /></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		
		if ($this->_installed_mods['smileys'])
		{
			$this->EE->load->helper('smiley');
			$this->EE->load->library('table');
			
			$output .= '<a href="#" id="smiley_link_'.$row['field_id'].'" class="smiley_link" title="'.lang('emotions').'">'.lang('emotions').'</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			$image_array = get_clickable_smileys($path = $this->EE->config->slash_item('emoticon_url'), 'field_id_'.$row['field_id']);
			$col_array = $this->EE->table->make_columns($image_array, 8);
			$output .= '<div id="smiley_table_'.$row['field_id'].'" class="smileyContent" style="display: none;">'.$this->EE->table->generate($col_array).'</div>';
			$this->EE->table->clear(); // clear out tables for the next smiley
		}
		
		if (isset($settings['field_show_glossary']))
		{
			$output .= $this->EE->load->ee_view('content/_assets/glossary_items', '', TRUE);
		}
		
		$output .= '</div>';
		
		$chunk = str_replace(LD.'spellcheck'.RD, $output, $chunk);

		return $chunk;
	}

	// --------------------------------------------------------------------	

	/**
	 * SAEF URL Title Javascript
	 * 
	 * This function adds url_title javascript to the js script compiled in saef_javascript()
	 *
	 * @return string
	 */
	function _url_title_js()
	{
		// js for URL Title
		$convert_ascii = ($this->EE->config->item('auto_convert_high_ascii') == 'y') ? TRUE : FALSE;
		$word_separator = $this->EE->config->item('word_separator') != "dash" ? '_' : '-';

		// Foreign Character Conversion Javascript
		include(APPPATH.'config/foreign_chars.php');

		/* -------------------------------------
		/*  'foreign_character_conversion_array' hook.
		/*  - Allows you to use your own foreign character conversion array
		/*  - Added 1.6.0
		/* 	- Note: in 2.0, you can edit the foreign_chars.php config file as well
		*/  
			if (isset($this->extensions->extensions['foreign_character_conversion_array']))
			{
				$foreign_characters = $this->extensions->call('foreign_character_conversion_array');
			}
		/*
		/* -------------------------------------*/

		$foreign_replace = '';

		foreach($foreign_characters as $old => $new)
		{
			$foreign_replace .= "if (c == '$old') {NewTextTemp += '$new'; continue;}\n\t\t\t\t";
		}

		$url_title_js = <<<YOYOYO

function liveUrlTitle()
{
	var defaultTitle = '{$this->default_entry_title}';
	var NewText = document.getElementById("title").value;

	if (defaultTitle != '')
	{
		if (NewText.substr(0, defaultTitle.length) == defaultTitle)
		{
			NewText = NewText.substr(defaultTitle.length);
		}
	}

	NewText = NewText.toLowerCase();
	var separator = "{$word_separator}";

	/* Foreign Character Attempt */

	var NewTextTemp = '';
	for(var pos=0; pos<NewText.length; pos++)
	{
		var c = NewText.charCodeAt(pos);

		if (c >= 32 && c < 128)
		{
			NewTextTemp += NewText.charAt(pos);
		}
		else
		{
			{$foreign_replace}
		}
	}

	var multiReg = new RegExp(separator + '{2,}', 'g');

	NewText = NewTextTemp;

	NewText = NewText.replace('/<(.*?)>/g', '');
	NewText = NewText.replace(/\s+/g, separator);
	NewText = NewText.replace(/\//g, separator);
	NewText = NewText.replace(/[^a-z0-9\-\._]/g,'');
	NewText = NewText.replace(/\+/g, separator);
	NewText = NewText.replace(multiReg, separator);
	NewText = NewText.replace(/-$/g,'');
	NewText = NewText.replace(/_$/g,'');
	NewText = NewText.replace(/^_/g,'');
	NewText = NewText.replace(/^-/g,'');

	if (document.getElementById("url_title"))
	{
		document.getElementById("url_title").value = "{$this->url_title_prefix}" + NewText;
	}
	else
	{
		document.forms['entryform'].elements['url_title'].value = "{$this->url_title_prefix}" + NewText;
	}
}

YOYOYO;

		$ret = $url_title_js;

		if ($this->EE->config->item('use_compressed_js') != 'n')
		{
			return str_replace(array("\n", "\t"), '', $ret);			
		}

		return $ret;
	}	
	
	// --------------------------------------------------------------------	

	/**
	 * Writemode markup
	 *
	 * This function is just returning nothing at the moment while we decide on
	 * what to do about thickbox, given that it is no longer supported.
	 */
	function _writemode_markup()
	{
		return '';
		
		$output = '<div id="write_mode_container" style="display:none">';
		$output .= '<div id="write_mode_close_container"><a href="#" class="TB_closeWindowButton"><img alt="'.lang('close').'" width="13" height="13" src="images/write_mode_close.png" /></a><a href="#" class="publish_to_field"><img alt="Publish to Field" width="103" height="18" src="images/write_mode_publish_to_field.png" /></a>&nbsp;</div>';
		$output .= '<div id="write_mode_writer"><div id="write_mode_header"><a href="#" class="reveal_formatting_buttons"><img class="show_tools" alt="'.lang('show_tools').'" width="109" height="18" src="<images/write_mode_show_tools.png" /></a></div><textarea id="write_mode_textarea"></textarea></div>';
		$output .= '<div id="write_mode_footer"><a href="#" class="publish_to_field"><img alt="'.lang('publish_to_field').'" width="103" height="18" src="images/write_mode_publish_to_field.png" /></a></div></div>';
	
		return $output;
	}
}
// END CLASS

/* End of file mod.channel_standalone.php */
/* Location: ./system/expressionengine/modules/channel/mod.channel_standalone.php */