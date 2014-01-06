<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Wiki Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Control Panel Page
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Wiki_mcp {

	var $base_url = '';

	/**
	  *  Constructor
	  */
	function Wiki_mcp()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

        $this->base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki';

        ee()->cp->set_right_nav(array(
				'wiki_homepage'	=> $this->base_url,
                'create_wiki'   => $this->base_url.AMP.'method=create',
                'wiki_themes'   => $this->base_url.AMP.'method=list_themes'
            ));
	}

	// --------------------------------------------------------------------

	/**
	  *  A Wiki Config
	  */
	function index()
	{
		ee()->load->library('table');
		ee()->load->library('javascript');
		ee()->load->helper('form');
		ee()->load->model('wiki_model');

		ee()->cp->add_js_script('fp_module', 'wiki');

		$vars['cp_page_title'] = ee()->lang->line('wiki_module_name');

		$select = array('wiki_id', 'wiki_label_name', 'wiki_short_name');
		$sort_order = array('wiki_label_name', 'asc');

		$query = ee()->wiki_model->get_wikis('', $select, $sort_order);

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

		ee()->javascript->compile();
		return ee()->load->view('index', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Wiki Config
	  */
	function update()
	{
		ee()->load->library('form_validation');
		ee()->load->library('table');
		ee()->load->helper('form');
		ee()->load->model('wiki_model');

		ee()->cp->add_js_script(array('fp_module' => 'wiki'));

		ee()->javascript->set_global(array('lang' => array(
											'namespace_deleted'	=> ee()->lang->line('namespace_deleted'),
											'namespace_not_deleted' => ee()->lang->line('namespace_not_deleted')
										)
									));


		$wiki_id = (ee()->input->get_post('wiki_id') !== '' && is_numeric(ee()->input->get_post('wiki_id'))) ? ee()->input->get_post('wiki_id') : 1;

		if (ee()->input->get_post('create') == 'new')
		{
			$message = ee()->lang->line('wiki_created');
			$vars['message'] = $message;
			$vars['message_type'] = 'success';
		}

		ee()->cp->set_breadcrumb($this->base_url, ee()->lang->line('wiki_module_name'));

		$vars['cp_page_title'] = ee()->lang->line('wiki_preferences');
		$vars['form_hidden']['wiki_id'] = $wiki_id;
		$vars['form_hidden']['action'] = 'edit';

		ee()->form_validation->set_rules('wiki_label_name',		'lang:wiki_label_name',			'required');
		ee()->form_validation->set_rules('wiki_short_name',		'lang:wiki_short_name',			'required|callback__check_duplicate');
		ee()->form_validation->set_rules('wiki_upload_dir',		'lang:wiki_upload_dir',			'required');
		ee()->form_validation->set_rules('wiki_users',				'lang:wiki_users',				'required');
		ee()->form_validation->set_rules('wiki_admins',			'lang:wiki_admins',				'required');
		ee()->form_validation->set_rules('wiki_html_format',		'lang:wiki_html_format',		'required');
		ee()->form_validation->set_rules('wiki_text_format',		'lang:wiki_text_format',		'required');
		ee()->form_validation->set_rules('wiki_revision_limit',	'lang:wiki_revision_limit',		'is_natural');
		ee()->form_validation->set_rules('wiki_author_limit',		'lang:wiki_author_limit',		'is_natural');
		ee()->form_validation->set_rules('wiki_namespaces_list',	'lang:wiki_namespaces_list',	'');
		ee()->form_validation->set_rules('wiki_moderation_emails',	'lang:wiki_moderation_emails',	'prep_list[,]|valid_emails');

		ee()->form_validation->set_old_value('id', $wiki_id);
		ee()->form_validation->set_error_delimiters('<br /><span class="notice">', '</span>');

		if (ee()->form_validation->run() === FALSE)
		{
			$query = ee()->wiki_model->get_wikis($wiki_id);

			if ($query->num_rows() == 0)
			{
				show_error(ee()->lang->line('unauthorized_access'));
			}

			// Preferences
			// generate field values
			foreach ($query->row() as $field_name => $value)
			{
				$vars[$field_name.'_value'] = $value;
			}

			// a few extra options for the view that aren't set above
			ee()->load->model('addons_model');

			$vars['wiki_text_format_options'] 	= ee()->addons_model->get_plugin_formatting();
			$vars['wiki_html_format_options'] 	= array(
												'none'	=> ee()->lang->line('convert_to_entities'),
												'safe'	=> ee()->lang->line('allow_safe_html'),
												'all'	=> ee()->lang->line('allow_all_html')
											);

			/**
			 * We were calling this method three times prior to moving it over to the model
			 * I decided to just run it once, but it'll be easy enough to tweak things if needed
			 * in the future --ga
			 */
			$member_group_options = ee()->wiki_model->member_group_options();

			$vars['wiki_upload_dir_options'] 	= ee()->wiki_model->fetch_upload_options();
			$vars['wiki_admins_options'] 		= $member_group_options;
			$vars['wiki_users_options'] 		= $member_group_options;

			// Namespaces
			$namespaces_query = ee()->db->get_where('wiki_namespaces',
														array('wiki_id' => $wiki_id));

			$vars['namespaces'] = array();
			$vars['member_group_options'] = $member_group_options;

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

			$result = ee()->wiki_model->select_max('namespace_id', '', 'wiki_namespaces');

			$vars['next_namespace_id'] = ($result->row('namespace_id') >= 1) ? $result->row('namespace_id') + 1 : 1;
			$vars['wiki_users_value'] = explode('|', $vars['wiki_users_value']);
			$vars['wiki_admins_value'] = explode('|', $vars['wiki_admins_value']);

			return ee()->load->view('update', $vars, TRUE);
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
					$query = ee()->db->get_where('wiki_namespaces', array('wiki_id' => $wiki_id));

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
									return ee()->output->show_user_error('submission', array(ee()->lang->line('invalid_namespace')));
								}

								$updatedata = array(
									'namespace_name'	=> $_POST['namespace_name_'.$row['namespace_id']],
									'namespace_label'	=> $_POST['namespace_label_'.$row['namespace_id']],
									'namespace_admins'	=> (ee()->input->get_post('namespace_admins_'.$row['namespace_id']) == FALSE) ? '' : implode('|', ee()->input->get_post('namespace_admins_'.$row['namespace_id'])),
									'namespace_users'	=> (ee()->input->get_post('namespace_users_'.$row['namespace_id']) == FALSE) ? '' : implode('|', ee()->input->get_post('namespace_users_'.$row['namespace_id']))
									);

								ee()->db->where('namespace_id', $row['namespace_id']);
								ee()->db->update('wiki_namespaces', $updatedata);

								$labels[] = $_POST['namespace_label_'.$row['namespace_id']];
								$names[]  = $_POST['namespace_name_'.$row['namespace_id']];

								unset($_POST['namespace_label_'.$row['namespace_id']]);

								//  If Short Name changes update article pages

								if ($row['namespace_name'] != $_POST['namespace_name_'.$row['namespace_id']])
								{
									ee()->db->set('page_namespace', ee()->input->post('namespace_name_').$row['namespace_id']);
									ee()->db->where('page_namespace', $row['namespace_name']);
									ee()->db->update('wiki_page');
								}
							}
							else
							{
								ee()->db->where('namespace_id', $row['namespace_id']);
								ee()->db->delete('wiki_namespaces');
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
								return ee()->output->show_user_error('submission', array(ee()->lang->line('invalid_namespace')));
							}

							$namespace_data = array(
								'namespace_name'	=> $_POST[$name],
								'namespace_label'	=> $value,
								'wiki_id'			=> $wiki_id,
								'namespace_users'	=> (ee()->input->get_post('namespace_users_'.$number) === FALSE) ? '' : implode('|', ee()->input->get_post('namespace_users_'.$number)),
								'namespace_admins'	=> (ee()->input->get_post('namespace_admins_'.$number) === FALSE) ? '' : implode('|', ee()->input->get_post('namespace_admins_'.$number))
								);

							ee()->db->insert('wiki_namespaces', $namespace_data);

							$labels[] = $value;
							$names[]  = $_POST[$name];
						}
					}
				}

				if ($val == 'wiki_users' OR $val == 'wiki_admins')
				{
					$data[$val] = implode('|', ee()->input->get_post($val));
				}
				elseif($val != 'wiki_namespaces_list')
				{
					$data[$val] = ee()->input->get_post($val);
				}
			}

			if (count($data) > 0)
			{
				ee()->wiki_model->update_wiki($wiki_id, $data);
				ee()->session->set_flashdata('message_success', ee()->lang->line('update_successful'));
			}

			ee()->functions->redirect($this->base_url.AMP.'method=update'.AMP.'wiki_id='.$wiki_id);
		}
	}

	// --------------------------------------------------------------------

	/**
	  *  Check duplicate short name (callback)
	  */
	function _check_duplicate($str)
	{
		ee()->load->model('wiki_model');

		if (ee()->wiki_model->check_duplicate(ee()->form_validation->old_value('id'), $str) === FALSE)
		{
			ee()->form_validation->set_message('_check_duplicate', ee()->lang->line('duplicate_short_name'));
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
 	  *  Delete Wikis Confirmation
 	  */
	function delete_confirm()
	{
		if ( ! ee()->input->post('toggle'))
		{
			ee()->functions->redirect($this->base_url);
		}

		ee()->cp->set_breadcrumb($this->base_url, ee()->lang->line('wiki_module_name'));

		$vars['cp_page_title'] = ee()->lang->line('wiki_delete_confirm');
		$vars['question_key'] = 'wiki_delete_question';
		$vars['form_action'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=delete';

		ee()->load->helper('form');

		foreach ($_POST['toggle'] as $key => $val)
		{
			$vars['damned'][] = $val;
		}

		ee()->javascript->compile();

		return ee()->load->view('delete_confirm', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	  *  Delete Wikis
	  */
	function delete()
	{
		if ( ! ee()->input->post('delete'))
		{
			ee()->functions->redirect($this->base_url);
		}

		ee()->load->model('wiki_model');
		$message = ee()->wiki_model->delete_wiki($_POST['delete']);

		ee()->session->set_flashdata('message_success', $message);
		ee()->functions->redirect($this->base_url);
	}

	// --------------------------------------------------------------------

	/**
	 * Create New Wiki
	 *
	 */
	function create()
	{
		ee()->load->model('wiki_model');
		$query = ee()->wiki_model->select_max('wiki_id', 'max', 'wikis');

		$prefix = ($query->num_rows() > 0 && $query->row('max')  != 0) ? '_'.((int)$query->row('max') +1) : '';

		$id = ee()->wiki_model->create_new_wiki($prefix);

		ee()->functions->redirect($this->base_url.AMP.'method=update'.AMP.'wiki_id='.$id.AMP.'create=new');
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Namespace
	 */
	function delete_namespace()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error(ee()->lang->line('unauthorized_access'));
		}

		ee()->load->model('wiki_model');

		if (ee()->wiki_model->delete_namespace(ee()->input->get_post('namespace_id')) === TRUE)
		{
			ee()->output->send_ajax_response(array('response' => 'success'));
		}

		ee()->output->send_ajax_response(array('response' => 'failure'));
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
		ee()->cp->set_breadcrumb($this->base_url,
										ee()->lang->line('wiki_module_name'));

		$vars = array();
		$vars['cp_page_title'] = ee()->lang->line('wiki_themes');
		$vars['themes'] = array();

		ee()->load->helper('directory');

		foreach (directory_map(PATH_THEMES.'wiki_themes', TRUE) as $file)
		{
			if (is_dir(PATH_THEMES.'wiki_themes/'.$file) AND $file != '.' AND $file != '..' AND $file != '.svn' AND $file != '.cvs')
			{
				$vars['themes'][$file] = ucfirst(str_replace("_", " ", $file));
			}
		}

		return ee()->load->view('list_themes', $vars, TRUE);
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
		ee()->cp->set_breadcrumb($this->base_url,
										ee()->lang->line('wiki_module_name'));
		ee()->cp->set_breadcrumb($this->base_url.AMP.'method=list_themes',
										ee()->lang->line('wiki_themes'));

		$vars = array();
		$vars['cp_page_title'] = ee()->lang->line('wiki_theme_templates');
		$vars['templates'] = array();

		// no theme?
		if (($vars['theme'] = ee()->input->get_post('theme')) === FALSE)
		{
			return ee()->load->view('theme_templates', $vars, TRUE);
		}

		$vars['theme'] = strtolower(ee()->security->sanitize_filename($vars['theme']));
		$vars['theme_name'] = strtolower(str_replace('_', ' ', $vars['theme']));
		$vars['cp_page_title'] .= ' - '.htmlentities($vars['theme_name']);

		$path = PATH_THEMES.'/wiki_themes/'.$vars['theme'];

		ee()->load->helper('directory');

		foreach (directory_map($path, TRUE) as $file)
		{
			if (strpos($file, '.') === FALSE)
			{
				continue;
			}

			$vars['templates'][$file] = ucwords(str_replace('_', ' ', substr($file, 0, -strlen(strrchr($file, '.')))));
		}

		asort($vars['templates']);

		ee()->javascript->compile();

		return ee()->load->view('theme_templates', $vars, TRUE);
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
		ee()->cp->set_breadcrumb($this->base_url,
										ee()->lang->line('wiki_module_name'));
		ee()->cp->set_breadcrumb($this->base_url.AMP.'method=list_themes',
										ee()->lang->line('wiki_themes'));

		$vars = array();
		$vars['cp_page_title'] = ee()->lang->line('edit_template');

		$vars['templates'] = array(); // no templates, needed if the theme_templates view gets called

		// no theme?
		if (($vars['theme'] = ee()->input->get_post('theme')) === FALSE)
		{
			return ee()->load->view('theme_templates', $vars, TRUE);
		}

		// no template?
		if (($vars['template'] = ee()->input->get_post('template')) === FALSE)
		{
			return ee()->load->view('theme_templates', $vars, TRUE);
		}

		$vars['theme'] = ee()->security->sanitize_filename($vars['theme']);
		$vars['template'] = ee()->security->sanitize_filename($vars['template']);
		$vars['theme_name'] = strtolower(str_replace('_', ' ', $vars['theme']));
		$vars['template_name'] = ucwords(str_replace('_', ' ', substr($vars['template'], 0, -strlen(strrchr($vars['template'], '.')))));
		$vars['cp_page_title'] .= ' - '.htmlentities($vars['theme_name']).' / '.htmlentities($vars['template_name']);

		ee()->cp->set_breadcrumb($this->base_url.AMP.'method=theme_templates'.AMP.'theme='.$vars['theme'],
		 								htmlentities($vars['theme_name']));

		$path = PATH_THEMES.'wiki_themes/'.$vars['theme'].'/';

		ee()->load->helper('form');
		ee()->load->helper('file');

		// can't read file?
		if (($vars['template_data'] = read_file($path.$vars['template'])) === FALSE)
		{
			return ee()->load->view('theme_templates', $vars, TRUE);
		}

		ee()->jquery->plugin(BASE.AMP.'C=javascript'.AMP.'M=load'.AMP.'plugin=markitup', TRUE);

		$markItUp = array(
			'nameSpace'		=> "html",
			'onShiftEnter'	=> array('keepDefault' => FALSE, 'replaceWith' => "<br />\n"),
			'onCtrlEnter'	=> array('keepDefault' => FALSE, 'openWith' => "\n<p>", 'closeWith' => "</p>\n")
		);

		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- allow_textarea_tabs => Preserve tabs in all textareas or disable completely
		/* -------------------------------------------*/

		if (ee()->config->item('allow_textarea_tabs') != 'n')
		{
			$markItUp['onTab'] = array('keepDefault' => FALSE, 'replaceWith' => "\t");
		}

		ee()->javascript->output('
			$("#template_data").markItUp('.json_encode($markItUp).');
		');

		ee()->javascript->compile();

		$vars['not_writable'] = ! is_really_writable($path.$vars['template']);

		return ee()->load->view('edit_template', $vars, TRUE);
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
		ee()->cp->set_breadcrumb($this->base_url,
										ee()->lang->line('wiki_module_name'));

		// no theme?
		if (($theme = ee()->input->get_post('theme')) === FALSE)
		{
			show_error(ee()->lang->line('invalid_wiki_theme'));
		}

		// no template?
		if (($template = ee()->input->get_post('template')) === FALSE)
		{
			show_error(ee()->lang->line('invalid_template'));
		}

		$theme = ee()->security->sanitize_filename($theme);
		$template = ee()->security->sanitize_filename($template);

		$path = PATH_THEMES.'/wiki_themes/'.$theme.'/'.$template;

		if ( ! file_exists($path))
		{
			show_error(ee()->lang->line('unable_to_find_template_file'));
		}

		ee()->load->helper('file');

		if ( ! write_file($path, ee()->input->get_post('template_data')))
		{
			show_error(ee()->lang->line('error_opening_template'));
		}

		// Clear cache files
		ee()->functions->clear_caching('all');
		ee()->session->set_flashdata('message_success', ee()->lang->line('template_updated'));

		if (ee()->input->get_post('update_and_return') !== FALSE)
		{
			ee()->functions->redirect($this->base_url.AMP.'method=theme_templates'.AMP.'theme='.$theme);
		}

		ee()->functions->redirect($this->base_url.AMP.'method=edit_template'.AMP.'theme='.$theme.AMP.'template='.$template);
	}
}
/* END Class */

/* End of file mcp.wiki.php */
/* Location: ./system/expressionengine/modules/wiki/mcp.wiki.php */
