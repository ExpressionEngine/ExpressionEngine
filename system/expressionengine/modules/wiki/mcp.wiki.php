<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2010, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: mcp.wiki.php
-----------------------------------------------------
 Purpose: Wiki class - CP
=====================================================
*/
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}


class Wiki_mcp {

	/**
	  *  Constructor
	  */
	function Wiki_mcp( $switch = TRUE )
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

        $base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP;

        $this->EE->cp->set_right_nav(array(
				'wiki_homepage'	=> BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki',
                'create_wiki'   => $base_url.'method=create',
                'wiki_themes'   => $base_url.'method=list_themes'
            ));
	}

	// --------------------------------------------------------------------

	/**
	  *  A Wiki Config
	  */
	function index()
	{
		$this->EE->load->library('table');
		$this->EE->load->library('javascript');
		$this->EE->load->helper('form');

		$this->EE->jquery->tablesorter('.mainTable', '{
			headers: {2: {sorter: false}},
			widgets: ["zebra"]
		}');

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

		$vars['cp_page_title'] = $this->EE->lang->line('wiki_module_name');

		$this->EE->db->select('wiki_id, wiki_label_name, wiki_short_name');
		$this->EE->db->order_by('wiki_label_name', 'asc');
		$query = $this->EE->db->get('wikis');

		$vars['wikis'] = array();

		foreach ($query->result() as $row)
		{
			$vars['wikis'][$row->wiki_id]['id'] = $row->wiki_id;
			$vars['wikis'][$row->wiki_id]['label_name'] = $row->wiki_label_name;
			$vars['wikis'][$row->wiki_id]['shortname'] = $row->wiki_short_name;
			$vars['wikis'][$row->wiki_id]['toggle'] = array(
																			'name'		=> 'toggle[]',
																			'id'		=> 'delete_box_'.$row->wiki_id,
																			'value'		=> $row->wiki_id,
																			'class'		=>'toggle'
			    														);
		}

		$this->EE->javascript->compile();
		return $this->EE->load->view('index', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Wiki Config
	  */
	function update()
	{
		$this->EE->load->library('form_validation');
		$this->EE->load->library('table');
		$this->EE->load->helper('form');

		$wiki_id = ($this->EE->input->get_post('wiki_id') !== '' && is_numeric($this->EE->input->get_post('wiki_id'))) ? $this->EE->input->get_post('wiki_id') : 1;

		if ($this->EE->input->get_post('create') == 'new')
		{
			$message = $this->EE->lang->line('wiki_created');
			$vars['message'] = $message;
			$vars['message_type'] = 'success';
		}

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki', $this->EE->lang->line('wiki_module_name'));

		$vars['cp_page_title'] = $this->EE->lang->line('wiki_preferences');
		$vars['form_hidden']['wiki_id'] = $wiki_id;
		$vars['form_hidden']['action'] = 'edit';
		
		$this->EE->form_validation->set_rules('wiki_label_name',		'lang:wiki_label_name',			'required');
		$this->EE->form_validation->set_rules('wiki_short_name',		'lang:wiki_short_name',			'required|callback__check_duplicate');
		$this->EE->form_validation->set_rules('wiki_upload_dir',		'lang:wiki_upload_dir',			'required');
		$this->EE->form_validation->set_rules('wiki_users',				'lang:wiki_users',				'required');
		$this->EE->form_validation->set_rules('wiki_admins',			'lang:wiki_admins',				'required');
		$this->EE->form_validation->set_rules('wiki_html_format',		'lang:wiki_html_format',		'required');
		$this->EE->form_validation->set_rules('wiki_text_format',		'lang:wiki_text_format',		'required');
		$this->EE->form_validation->set_rules('wiki_revision_limit',	'lang:wiki_revision_limit',		'is_natural');
		$this->EE->form_validation->set_rules('wiki_author_limit',		'lang:wiki_author_limit',		'is_natural');
		$this->EE->form_validation->set_rules('wiki_namespaces_list',	'lang:wiki_namespaces_list',	'');
		$this->EE->form_validation->set_rules('wiki_moderation_emails',	'lang:wiki_moderation_emails',	'prep_list[,]|valid_emails');		

		$this->EE->form_validation->set_old_value('id', $wiki_id);
		$this->EE->form_validation->set_error_delimiters('<br /><span class="notice">', '</span>');

		if ($this->EE->form_validation->run() === FALSE)
		{
			$query = $this->EE->db->get_where('wikis', array('wiki_id' => $wiki_id));

			if ($query->num_rows() == 0)
			{
				show_error($this->EE->lang->line('unauthorized_access'));
			}

			// Preferences

			// generate field values
			foreach ($query->row() as $field_name => $value)
			{
				$vars[$field_name.'_value'] = $value;
			}

			// a few extra options for the view that aren't set above
			$this->EE->load->model('addons_model');

			$vars['wiki_text_format_options'] 	= $this->EE->addons_model->get_plugin_formatting();
			$vars['wiki_html_format_options'] 	= array(
															'none'	=> $this->EE->lang->line('convert_to_entities'),
															'safe'	=> $this->EE->lang->line('allow_safe_html'),
															'all'	=> $this->EE->lang->line('allow_all_html')
														);
			$vars['wiki_upload_dir_options'] 	= $this->upload_directory_options();
			$vars['wiki_admins_options'] 		= $this->member_group_options();
			$vars['wiki_users_options'] 		= $this->member_group_options();

			// Namespaces

			$namespaces_query = $this->EE->db->get_where('wiki_namespaces', array('wiki_id' => $wiki_id));

			$vars['namespaces'] = array();
			$vars['member_group_options'] = $this->member_group_options();

			if ($namespaces_query->num_rows() > 0)
			{
				foreach($namespaces_query->result() as $namespace)
				{
					$vars['namespaces'][$namespace->namespace_id]['namespace_id'] = $namespace->namespace_id;
					$vars['namespaces'][$namespace->namespace_id]['wiki_id'] = $namespace->wiki_id;
					$vars['namespaces'][$namespace->namespace_id]['namespace_name'] = $namespace->namespace_name;
					$vars['namespaces'][$namespace->namespace_id]['namespace_label'] = $namespace->namespace_label;
					$vars['namespaces'][$namespace->namespace_id]['namespace_users'] = explode('|', $namespace->namespace_users);
					$vars['namespaces'][$namespace->namespace_id]['namespace_admins'] = explode('|', $namespace->namespace_admins);
				}
			}

			$this->EE->db->select_max('namespace_id');
			$result = $this->EE->db->get('wiki_namespaces');

			$vars['next_namespace_id'] = ($result->row('namespace_id') >= 1) ? $result->row('namespace_id') + 1 : 1;

			return $this->EE->load->view('update', $vars, TRUE);
		}
		else
		{
			$fields = array('wiki_label_name',
							'wiki_short_name',
							'wiki_upload_dir',
							'wiki_users',
							'wiki_admins',
							'wiki_html_format',
							'wiki_text_format',
							'wiki_revision_limit',
							'wiki_author_limit',
							'wiki_namespaces_list',
							'wiki_moderation_emails');

			foreach($fields AS $val)
			{
				if ($val == 'wiki_namespaces_list')
				{
					//  Namespaces Requiring an Update

					$query = $this->EE->db->get_where('wiki_namespaces', array('wiki_id' => $wiki_id));

					$labels = array();
					$names  = array();

					if ($query->num_rows() > 0)
					{
						foreach($query->result_array() as $row)
						{
							if (isset($_POST['namespace_label_'.$row['namespace_id']]) && isset($_POST['namespace_name_'.$row['namespace_id']]))
							{
								if (trim($_POST['namespace_label_'.$row['namespace_id']]) == '' OR
									! preg_match("/^\w+$/",$_POST['namespace_name_'.$row['namespace_id']]) OR
									$_POST['namespace_name_'.$row['namespace_id']] == 'category' OR
									in_array($_POST['namespace_name_'.$row['namespace_id']], $names) OR
									in_array($_POST['namespace_label_'.$row['namespace_id']], $labels))
								{
									return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('invalid_namespace')));
								}

								$updatedata = array(
									'namespace_name'	=> $_POST['namespace_name_'.$row['namespace_id']],
									'namespace_label'	=> $_POST['namespace_label_'.$row['namespace_id']],
									'namespace_admins'	=> ($this->EE->input->get_post('namespace_admins_'.$row['namespace_id']) == FALSE) ? '' : implode('|', $this->EE->input->get_post('namespace_admins_'.$row['namespace_id'])),
									'namespace_users'	=> ($this->EE->input->get_post('namespace_users_'.$row['namespace_id']) == FALSE) ? '' : implode('|', $this->EE->input->get_post('namespace_users_'.$row['namespace_id']))
									);

								$this->EE->db->where('namespace_id', $row['namespace_id']);
								$this->EE->db->update('wiki_namespaces', $updatedata);

								$labels[] = $_POST['namespace_label_'.$row['namespace_id']];
								$names[]  = $_POST['namespace_name_'.$row['namespace_id']];

								unset($_POST['namespace_label_'.$row['namespace_id']]);

								//  If Short Name changes update article pages

								if ($row['namespace_name'] != $_POST['namespace_name_'.$row['namespace_id']])
								{
									$this->EE->db->set('page_namespace', $this->EE->input->post('namespace_name_').$row['namespace_id']);
									$this->EE->db->where('page_namespace', $row['namespace_name']);
									$this->EE->db->update('wiki_page');
								}
							}
							else
							{
								$this->EE->db->where('namespace_id', $row['namespace_id']);
								$this->EE->db->delete('wiki_namespaces');
							}
						}
					}

					foreach($_POST as $key => $value)
					{
						if (substr($key, 0, strlen('namespace_label_')) == 'namespace_label_')
						{
							$number = substr($key, strlen('namespace_label_'));
							$name = 'namespace_name_'.$number;

							if (trim($value) == '') continue;

							if ( ! isset($_POST[$name]) OR ! preg_match("/^\w+$/", $_POST[$name]) OR $_POST[$name] == 'category' OR
								in_array($_POST[$name], $names) OR in_array($value, $labels))
							{
								return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('invalid_namespace')));
							}

							$namespace_data = array(
								'namespace_name'	=> $_POST[$name],
								'namespace_label'	=> $value,
								'wiki_id'			=> $wiki_id,
								'namespace_users'	=> ($this->EE->input->get_post('namespace_users_'.$number) === FALSE) ? '' : implode('|', $this->EE->input->get_post('namespace_users_'.$number)),
								'namespace_admins'	=> ($this->EE->input->get_post('namespace_admins_'.$number) === FALSE) ? '' : implode('|', $this->EE->input->get_post('namespace_admins_'.$number))
								);

							$this->EE->db->insert('wiki_namespaces', $namespace_data);

							$labels[] = $value;
							$names[]  = $_POST[$name];
						}
					}
				}

				if ($val == 'wiki_users' OR $val == 'wiki_admins')
				{
					$data[$val] = implode('|', ($this->EE->input->get_post($val) == '') ? array() : $this->EE->input->get_post($val));
				}
				elseif($val != 'wiki_namespaces_list')
				{
					$data[$val] = $this->EE->input->get_post($val);
				}
			}

			if (count($data) > 0)
			{
				$this->EE->db->where('wiki_id', $wiki_id);
				$this->EE->db->update('wikis', $data);
				$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('update_successful'));
			}
			
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=update'.AMP.'wiki_id='.$wiki_id);
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  Check duplicate short name (callback)
	  */
	function _check_duplicate($str)
	{
		if ($id = $this->EE->form_validation->old_value('id'))
		{
			$this->EE->db->where('wiki_id != ', $id);
		}
		
		$this->EE->db->where('wiki_short_name', $str);
		
		if ($this->EE->db->count_all_results('wikis') > 0)
		{
			$this->EE->form_validation->set_message('_check_duplicate', $this->EE->lang->line('duplicate_short_name'));
			return FALSE;
		}
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	  *  Add Namespace JS for Configuration
	  */
	function add_namespace_js($i, $first)
	{
		// @confirm: since we need the CP to be functional without JS, I've replaced this "dynamicly add a row"
		// javascript with simple submit buttons labelled "+" and "-"
		// This function remains here for reference.  Remove or implement for 2.0 release
		$this->EE->lang->loadfile('admin');

		$action_can_not_be_undone = $this->EE->lang->line('action_can_not_be_undone')."\\n\\n".$this->EE->lang->line('toggle_extension_confirmation')."\\n";

			return <<<DOH

<script type="text/javascript">
//<![CDATA[

var namespaceCount	= {$i};
var modelNamespace	= {$first};

function add_namespace_fields()
{

	if (document.getElementById('namespace_container_' + modelNamespace))
	{
		// Find last namespace field
		var originalNamespaceField = document.getElementById('namespace_container_' + modelNamespace);
		namespaceCount++;

		// Clone it, change the id
		var newNamespaceField = originalNamespaceField.cloneNode(true);
		newNamespaceField.id = 'namespace_container_' + namespaceCount;

		// Zero the input and change the names of fields
		var newFieldInputs = newNamespaceField.getElementsByTagName('input');
		newFieldInputs[0].value = '';
		newFieldInputs[0].name = 'namespace_label_' + namespaceCount;
		newFieldInputs[1].value = '';
		newFieldInputs[1].name = 'namespace_name_' + namespaceCount;

		// Append it and we're done
		originalNamespaceField.parentNode.appendChild(newNamespaceField);
	}
}

function delete_namespace_field(obj)
{
	if (obj.parentNode && obj.parentNode.parentNode)
	{
		if( ! confirm("{$action_can_not_be_undone}")) return false;

		siblings = obj.parentNode.parentNode.parentNode.getElementsByTagName('tr');

		if (siblings.length == 2)
		{
			add_namespace_fields();
		}

		if (obj.parentNode.parentNode.id = siblings[1].id)
		{
			modelNamespace = siblings[2].id.substr(20);
		}
		else
		{
			modelNamespace = siblings[1].id.substr(20);
		}

		obj.parentNode.parentNode.parentNode.removeChild(obj.parentNode.parentNode);
	}
}

//-->
//]]>
</script>

DOH;

	}

	// --------------------------------------------------------------------

	/**
	  *  List of Member Groups
	  */
	function member_group_options()
	{
		// currently all member group functions are irrespective of specific contexts
		// so it may make sense to load these once into an array available to the whole
		// module, however that would limit future flexibility, and the select calls
		// are not very intensive, so we'll continue running a db call each time.
		// Then again, query caching and this is pretty much a non-issue...

		// @todo: is there a model that would be better for this?  I'll hold off
		// AR'ing this sucker until I investimigate (sic) it 
		$query = $this->EE->db->query("SELECT group_title, group_id
							 FROM exp_member_groups
							 WHERE group_id NOT IN (2,3,4)
							 AND site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'");

		$options = array();

		foreach($query->result() as $row)
		{
			$options[$row->group_id] = $row->group_title;
		}

		return $options;
	}

	// --------------------------------------------------------------------

	/**
	  *  Upload Some Files with This Directory
	  */
	function upload_directory_options($value='')
	{
		$this->EE->db->select('id, name');
		$this->EE->db->order_by('name');
		$query = $this->EE->db->get('upload_prefs');

		$options[0] = $this->EE->lang->line('none');

		foreach($query->result() as $row)
		{
			$selected = ($value == $row->id) ? 1 : '';

			$options[$row->id] = $row->name;
		}

		return $options;
	}

	// --------------------------------------------------------------------

	/**
 	  *  Delete Wikis Confirmation
 	  */
	function delete_confirm()
	{
		if ( ! $this->EE->input->post('toggle'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki');
		}

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki', $this->EE->lang->line('wiki_module_name'));

		$vars['cp_page_title'] = $this->EE->lang->line('wiki_delete_confirm');
		$vars['question_key'] = 'wiki_delete_question';
		$vars['form_action'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=delete';

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
	  *  Delete Wikis
	  */
	function delete()
	{
		if ( ! $this->EE->input->post('delete'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki');
		}

		$this->EE->db->where_in('wiki_id', $_POST['delete']);
		$this->EE->db->delete(array('wikis', 'wiki_page', 'wiki_revisions', 'wiki_categories'));

		$message = (count($_POST['delete']) == 1) ? $this->EE->lang->line('wiki_deleted') : $this->EE->lang->line('wikis_deleted');

		$this->EE->session->set_flashdata('message_success', $message);
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki');
	}

	// --------------------------------------------------------------------

	/**
	  * Create New Wiki
	  */
	function create()
	{
		$query = $this->EE->db->query("SELECT MAX(wiki_id) AS max FROM exp_wikis");

		$prefix = ($query->num_rows() > 0 && $query->row('max')  != 0) ? '_'.($query->row('max') +1) : '';

		$id = $this->create_new_wiki($prefix);

		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=update'.AMP.'wiki_id='.$id.AMP.'create=new');
	}

	// --------------------------------------------------------------------

	/**
	  *  Create New Wiki
	  */
	function create_new_wiki($prefix='')
	{
		//  Default Index Page

		$data  = array(	'wiki_label_name'			=> "EE Wiki".str_replace('_', ' ', $prefix),
						'wiki_short_name'			=> 'default_wiki'.$prefix,
						'wiki_text_format'			=> 'xhtml',
						'wiki_html_format'			=> 'safe',
						'wiki_admins'				=> '1',
						'wiki_users'				=> '1|5',
						'wiki_upload_dir'			=> '0',
						'wiki_revision_limit'		=> 200,
						'wiki_author_limit'			=> 75,
						'wiki_moderation_emails'	=> '');

		$this->EE->db->query($this->EE->db->insert_string('exp_wikis', $data));
		$wiki_id = $this->EE->db->insert_id();

		//  Default Index Page

		$data = array(	'wiki_id'		=> $wiki_id,
						'page_name'		=> 'index',
						'last_updated'	=> $this->EE->localize->now);

		$this->EE->db->query($this->EE->db->insert_string('exp_wiki_page', $data));

		$this->EE->lang->loadfile('wiki');

		$page_id = $this->EE->db->insert_id();

		$data = array(	'page_id'			=> $page_id,
						'wiki_id'			=> $wiki_id,
						'revision_date'		=> $this->EE->localize->now,
						'revision_author'	=> $this->EE->session->userdata['member_id'],
						'revision_notes'	=> $this->EE->lang->line('default_index_note'),
						'page_content'		=> $this->EE->lang->line('default_index_content')
					 );

		$this->EE->db->query($this->EE->db->insert_string('exp_wiki_revisions', $data));

		$last_revision_id = $this->EE->db->insert_id();

		$this->EE->db->query($this->EE->db->update_string('exp_wiki_page', array('last_revision_id' => $last_revision_id), array('page_id' => $page_id)));

		return $wiki_id;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * List Themes
	 *
	 * Lists available wiki themes to edit
	 *
	 * @access	public
	 * @return	string
	 */
	function list_themes()
	{
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki', $this->EE->lang->line('wiki_module_name'));

		$vars = array();
		$vars['cp_page_title'] = $this->EE->lang->line('wiki_themes');
		$vars['themes'] = array();
		
		$this->EE->load->helper('directory');

		foreach (directory_map(PATH_THEMES.'wiki_themes', TRUE) as $file)
		{
			if (is_dir(PATH_THEMES.'wiki_themes/'.$file) AND $file != '.' AND $file != '..' AND $file != '.svn' AND $file != '.cvs') 
			{
				$vars['themes'][$file] = ucfirst(str_replace("_", " ", $file));
			}
		}

		return $this->EE->load->view('list_themes', $vars, TRUE);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Theme Templates
	 *
	 * Lists available templates from a Theme
	 *
	 * @access	public
	 * @return	string
	 */
	function theme_templates()
	{
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki', $this->EE->lang->line('wiki_module_name'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=list_themes', $this->EE->lang->line('wiki_themes'));

		$vars = array();
		$vars['cp_page_title'] = $this->EE->lang->line('wiki_theme_templates');
		$vars['templates'] = array();
		
		// no theme?
		if (($vars['theme'] = $this->EE->input->get_post('theme')) === FALSE)
		{
			return $this->EE->load->view('theme_templates', $vars, TRUE);
		}
		
		$vars['theme'] = strtolower($this->EE->functions->sanitize_filename($vars['theme']));
		$vars['theme_name'] = strtolower(str_replace('_', ' ', $vars['theme']));
		$vars['cp_page_title'] .= ' - '.htmlentities($vars['theme_name']);
		
		$path = PATH_THEMES.'/wiki_themes/'.$vars['theme'];
		
		$this->EE->load->helper('directory');

		foreach (directory_map($path, TRUE) as $file)
		{
			if (strpos($file, '.') === FALSE)
			{
				continue;
			}

			$vars['templates'][$file] = ucwords(str_replace('_', ' ', substr($file, 0, -strlen(strrchr($file, '.')))));
		}

		asort($vars['templates']);

		$this->EE->javascript->compile();
		
		$this->EE->load->helper('string');
		return $this->EE->load->view('theme_templates', $vars, TRUE);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Edit Template
	 *
	 * Edit wiki theme template
	 *
	 * @access	public
	 * @return	string
	 */
	function edit_template()
	{
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki', $this->EE->lang->line('wiki_module_name'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=list_themes', $this->EE->lang->line('wiki_themes'));
		
		$vars = array();
		$vars['cp_page_title'] = $this->EE->lang->line('edit_template');

		$vars['templates'] = array(); // no templates, needed if the theme_templates view gets called

		// no theme?
		if (($vars['theme'] = $this->EE->input->get_post('theme')) === FALSE)
		{
			return $this->EE->load->view('theme_templates', $vars, TRUE);
		}

		// no template?
		if (($vars['template'] = $this->EE->input->get_post('template')) === FALSE)
		{
			return $this->EE->load->view('theme_templates', $vars, TRUE);
		}

		$vars['theme'] = $this->EE->functions->sanitize_filename($vars['theme']);
		$vars['template'] = $this->EE->functions->sanitize_filename($vars['template']);
		$vars['theme_name'] = strtolower(str_replace('_', ' ', $vars['theme']));
		$vars['template_name'] = ucwords(str_replace('_', ' ', substr($vars['template'], 0, -strlen(strrchr($vars['template'], '.')))));
		$vars['cp_page_title'] .= ' - '.htmlentities($vars['theme_name']).' / '.htmlentities($vars['template_name']);

		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=theme_templates'.AMP.'theme='.$vars['theme'], htmlentities($vars['theme_name']));

		$path = PATH_THEMES.'wiki_themes/'.$vars['theme'].'/';

		$this->EE->load->helper('form');
		$this->EE->load->helper('file');

		// can't read file?
		if (($vars['template_data'] = read_file($path.$vars['template'])) === FALSE)
		{
			return $this->EE->load->view('theme_templates', $vars, TRUE);
		}
		
		$this->EE->jquery->plugin(BASE.AMP.'C=javascript'.AMP.'M=load'.AMP.'plugin=markitup', TRUE);

		$markItUp = array(
			'nameSpace'	=> "html",
			'onShiftEnter'	=> array('keepDefault' => FALSE, 'replaceWith' => "<br />\n"),
			'onCtrlEnter'	=> array('keepDefault' => FALSE, 'openWith' => "\n<p>", 'closeWith' => "</p>\n")
		);
		
		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- allow_textarea_tabs => Preserve tabs in all textareas or disable completely
		/* -------------------------------------------*/
		
		if($this->EE->config->item('allow_textarea_tabs') != 'n') {
			$markItUp['onTab'] = array('keepDefault' => FALSE, 'replaceWith' => "\t");
		}

		$this->EE->javascript->output('
			$("#template_data").markItUp('.$this->EE->javascript->generate_json($markItUp).');
		');
		
		$this->EE->javascript->compile();

		$vars['not_writable'] = ! is_really_writable($path.$vars['template']);

		return $this->EE->load->view('edit_template', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Template
	 *
	 * Updates a wiki theme template
	 *
	 * @access	public
	 * @return	string
	 */
	function update_template()
	{
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki', $this->EE->lang->line('wiki_module_name'));

		// no theme?
		if (($theme = $this->EE->input->get_post('theme')) === FALSE)
		{
			show_error($this->EE->lang->line('invalid_wiki_theme'));
		}

		// no template?
		if (($template = $this->EE->input->get_post('template')) === FALSE)
		{
			show_error($this->EE->lang->line('invalid_template'));
		}

		$theme = $this->EE->functions->sanitize_filename($theme);
		$template = $this->EE->functions->sanitize_filename($template);

		$path = PATH_THEMES.'/wiki_themes/'.$theme.'/'.$template;

		if ( ! file_exists($path))
		{
			show_error($this->EE->lang->line('unable_to_find_template_file'));
		}

		$this->EE->load->helper('file');

		if ( ! write_file($path, $this->EE->input->get_post('template_data')))
		{
			show_error($this->EE->lang->line('error_opening_template'));
		}

		// Clear cache files
		$this->EE->functions->clear_caching('all');
		
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('template_updated'));

		if ($this->EE->input->get_post('update_and_return') != FALSE)
		{
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=theme_templates'.AMP.'theme='.$theme);
		}

$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=edit_template'.AMP.'theme='.$theme.AMP.'template='.$template);		
	}
}
/* END Class */

/* End of file mcp.wiki.php */
/* Location: ./system/expressionengine/modules/wiki/mcp.wiki.php */