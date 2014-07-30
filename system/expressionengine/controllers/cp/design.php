<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Design extends CP_Controller {

	var $sub_breadcrumbs = array();

	// Reserved Template names
	var $reserved_names = array('act', 'css');

	// Reserved Global Variable names
	var $reserved_vars = array(
		'lang',
		'charset',
		'homepage',
		'debug_mode',
		'gzip_mode',
		'version',
		'elapsed_time',
		'hits',
		'total_queries',
		'XID_HASH',
		'csrf_token'
	);

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('template_model');
		$this->lang->loadfile('design');

		$this->javascript->compile();

		$this->sub_breadcrumbs = array();
		if ($this->cp->allowed_group('can_admin_templates'))
		{
			$this->sub_breadcrumbs = array_merge($this->sub_breadcrumbs, array(
				'global_variables'	=> cp_url('design/global_variables'),
				'snippets'			=> cp_url('design/snippets'),
				'sync_templates'	=> cp_url('design/sync_templates')
			));

			if ($this->config->item('enable_template_routes') == 'y' && ! IS_CORE)
			{
				$this->sub_breadcrumbs['url_manager'] = cp_url('design/url_manager');
			}
		}

		// This is worded as "Can administrate design preferences" in member group management.
		if ($this->cp->allowed_group('can_admin_design'))
		{
			$this->sub_breadcrumbs = array_merge($this->sub_breadcrumbs, array(
				'global_template_preferences'	=> cp_url('design/global_template_preferences'),
				'template_preferences_manager'	=> cp_url('design/template_preferences_manager')
			));
		}

		$this->view->wiki_installed = (bool) $this->db->table_exists('wikis');
		$this->view->forum_installed = (bool) $this->db->table_exists('forums');
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */
	function index()
	{
		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->javascript->output(
			$this->javascript->slidedown("#adminTemplatesSubmenu")
		);

		$this->view->cp_page_title = lang('design');
		$this->view->controller = 'design';

		$this->cp->render('_shared/overview');
	}

	// --------------------------------------------------------------------

	/**
	 * New Template
	 *
	 * Create a new template
	 *
	 * @access	public
	 * @return	type
	 */
	function template_group_pick($edit = FALSE)
	{
		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$group_id = $this->input->get_post('id');

		if ($group_id != '')
		{
			$this->new_template('', $group_id);
		}

		$this->load->model('template_model');
		$this->lang->loadfile('admin_content');

		$this->view->cp_page_title = lang('new_template_form');

		$template_groups_query = $this->template_model->get_template_groups();
		$vars['template_groups'] = $template_groups_query->result_array();
		$vars['link_to_method'] = ($edit) ? 'edit_template_group' : 'new_template';

		// if this isn't an admin, then unset any template
		// groups they aren't allowed to admin
		if ($this->session->userdata['group_id'] != 1)
		{
			foreach($vars['template_groups'] as $index=>$group)
			{
				if ( ! array_key_exists($group['group_id'], $this->session->userdata['assigned_template_groups']))
				{
					unset($vars['template_groups'][$index]);
				}
			}
		}

		$this->cp->render('design/new_template_group_pick', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Template Group
	 *
	 * Create a new template
	 *
	 * @access	public
	 * @return	type
	 */
	function delete_template_group_pick()
	{
		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$group_id = $this->input->get_post('id');

		if ($group_id != '')
		{
			$this->manager();
		}

		$this->load->model('template_model');
		$this->lang->loadfile('admin_content');

		$this->view->cp_page_title = lang('new_template_form');

		$template_groups_query = $this->template_model->get_template_groups();
		$vars['template_groups'] = $template_groups_query->result_array();

		// if this isn't an admin, then unset any template
		// groups they aren't allowed to admin
		if ($this->session->userdata['group_id'] != 1)
		{
			foreach($vars['template_groups'] as $index=>$group)
			{
				if ( ! array_key_exists($group['group_id'], $this->session->userdata['assigned_template_groups']))
				{
					unset($vars['template_groups'][$index]);
				}
			}
		}

		$this->cp->render('design/delete_template_group', $vars);
	}

	// --------------------------------------------------------------------

	/**
	  *  Template Delete Confirm
	  */
	function template_group_delete_confirm()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '')
		{
			$this->manager();
		}

		if ( ! is_numeric($group_id))
		{
			show_error('id_not_found');
		}

		$this->load->model('template_model');

		$query = $this->template_model->get_group_info($group_id);

		$group_id	= $query->row('group_id') ;
		$vars['template_group_name'] = $query->row('group_name') ;

		if ( ! $this->cp->allowed_group('can_admin_templates'))
		{
			if ( ! $this->_template_access_privs(array('group_id' => $group_id)))
			{
				show_error(lang('unauthorized_access'));
			}
		}

		$vars['file_folder'] = FALSE;

		// Check for associated group folder
		if ($this->config->item('save_tmpl_files') == 'y' AND $this->config->item('tmpl_file_basepath') != '')
		{
			$basepath = $this->config->slash_item('tmpl_file_basepath');
			$basepath .= $this->config->item('site_short_name').'/'.$vars['template_group_name'].'.group/';

			$vars['file_folder'] = is_dir($basepath);
		}

		$vars['damned'] = array($group_id);

		$vars['cp_page_title'] = lang('delete_template_group');
		$this->cp->set_breadcrumb(
			cp_url('design/manager', "tgpref={$group_id}"),
			lang('template_manager')
		);

		$vars['form_hidden']['group_id'] = $group_id;

		$this->cp->render('design/template_group_delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/** -------------------------------
	/**  Delete Template Group
	/** -------------------------------*/
	function template_group_delete()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		// if the hidden group_id field is not set, they might be here by accident.
		if ( ! $this->input->post('group_id'))
		{
			show_error(lang('unauthorized_access'));
		}

		$group_id = $this->input->get_post('group_id');

		if ($group_id == '' OR  ! is_numeric($group_id))
		{
			show_error(lang('unauthorized_access'));
		}

		// Delete the group folder if it exists
		if ($this->config->item('save_tmpl_files') == 'y' AND $this->config->item('tmpl_file_basepath') != '')
		{
			$this->db->select('group_name');
			$result = $this->db->get_where('template_groups', array('group_id' => $group_id));

			$basepath = $this->config->slash_item('tmpl_file_basepath');
			$basepath .= $this->config->item('site_short_name').'/'.$result->row('group_name').'.group/';

			$this->load->helper('file');
			delete_files($basepath, TRUE);
			@rmdir($basepath);
		}

		// We need to delete all the saved template data in the versioning table
		$this->db->select('template_id');
		$this->db->where('group_id', $group_id);
		$query = $this->db->get('templates');

		if ($query->num_rows() > 0)
		{
			$sql = "DELETE FROM exp_revision_tracker WHERE ";
			$sqlb = '';

			foreach ($query->result_array() as $row)
			{
				$sqlb .= " item_id = '".$row['template_id']."' OR";
			}
			$sqlb = substr($sqlb, 0, -2);
			$this->db->query($sql.$sqlb);

			$this->db->query("DELETE FROM exp_template_no_access WHERE ".str_replace('item_id', 'template_id', $sqlb));

			$this->db->delete('exp_templates', array('group_id' => $group_id));
		}

		$this->db->delete('exp_template_groups', array('group_id' => $group_id));
		$this->db->delete('exp_template_member_groups', array('template_group_id' => $group_id));

		$this->session->set_flashdata('message_success', lang('template_group_deleted'));
		$this->functions->redirect(cp_url('design/manager'));
	}

	// --------------------------------------------------------------------

	/**
	 * New Template
	 *
	 * Create a new template
	 *
	 * @access	public
	 * @return	type
	 */
	function new_template($message = '', $group_id = '')
	{
		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ($group_id == '')
		{
			$group_id = $this->input->get_post('group_id');
		}

		// if its still blank, make them choose a template
		if ($group_id == '')
		{
			return $this->template_group_pick();
		}

		if ( ! $this->_template_access_privs(array('group_id' => $group_id)))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $this->_template_access_privs(array('group_id' => $group_id)))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('template_model');
		$this->load->library('table');

		$templates = $this->template_model->get_templates($this->config->item('site_id'));

		$vars['templates'][0] = lang('do_not_duplicate_template');

		foreach($templates->result() as $template)
		{
			$vars['templates'][$template->group_name][$template->template_id] = $template->template_name;
		}

		$vars['form_hidden']['group_id'] = $group_id;

		$vars['template_types'] = $this->_get_template_types();

		//create_new_template

		$this->view->cp_page_title = lang('create_new_template');
		$this->cp->set_breadcrumb(
			cp_url('design/manager', "tgpref={$group_id}"),
			lang('template_manager')
		);

		$this->cp->render('design/new_template', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * New Template Group
	 *
	 * Create a new template group
	 *
	 * @access	public
	 * @return	type
	 */
	function new_template_group()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->view->cp_page_title = lang('create_new_template_group');
		$this->cp->set_breadcrumb(cp_url('design/manager'), lang('template_manager'));

		$this->load->model('template_model');
		$this->lang->loadfile('admin_content');
		$this->load->library('form_validation');
		$this->load->library('table');

		$this->form_validation->set_rules('group_name',	'lang:group_name', 'required|callback__group_name_checks');
		$this->form_validation->set_rules('duplicate_group', 'lang:duplicate_group', '');
		$this->form_validation->set_rules('is_site_default', 'lang:is_site_default', '');
		$this->form_validation->set_error_delimiters('<br /><span class="notice">', '</span>');


		$template_groups_query = $this->template_model->get_template_groups();
		$template_groups = $template_groups_query->result_array();

		// if this isn't an admin, then unset any template
		// groups they aren't allowed to admin
		if ($this->session->userdata['group_id'] != 1)
		{
			foreach($template_groups as $index=>$group)
			{
				if ( ! array_key_exists($group['group_id'], $this->session->userdata['assigned_template_groups']))
				{
					unset($template_groups[$index]);
				}
			}
		}

		// now that the groups are filtered, built the group output

		$vars['template_groups'] = array('false'=>lang('do_not_duplicate_group'));
		foreach($template_groups as $group)
		{
			$vars['template_groups'][$group['group_id']] = $group['group_name'];
		}

		if ($this->form_validation->run() === TRUE)
		{
			$this->update_template_group();
		}
		else
		{
			$this->cp->render('design/new_template_group', $vars);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Global Template Preferences
	 *
	 * Page to allow users to edit the global template preferences in the
	 * cp's design section.
	 *
	 * @access	public
	 * @return	type
	 */
	function global_template_preferences()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('template_model');
		$this->load->model('admin_model');
		$this->load->library('table');

		$this->jquery->tablesorter('.mainTable', '{
			headers: {2: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->view->cp_page_title = lang('global_template_preferences');
		$this->cp->set_breadcrumb(cp_url('design/manager'), lang('template_manager'));

		$vars['template_data'] = array('' => lang('none'));

		$templates = $this->template_model->get_templates();

		foreach ($templates->result() as $template)
		{
			$group_name = $template->group_name.'/'.$template->template_name;
			$vars['template_data'][$group_name] = $group_name;
		}

		$f_data = ee()->config->get_config_fields('template_cfg');

		foreach ($f_data as $conf => $val)
		{
			$vars[$conf] = $this->config->item($conf);
		}

		$options_array = array(
			'n' => lang('no'),
			'y' => lang('yes')
		);

		$vars['save_tmpl_revisions_options'] = $options_array;
		$vars['route_options'] = $options_array;
		$vars['save_tmpl_files_options'] = $options_array;
		$vars['strict_urls_options'] = $options_array;

		$vars['save_tmpl_files_n'] = TRUE;
		$vars['save_tmpl_files_y'] = FALSE;
		$vars['save_tmpl_revisions_n'] = TRUE;
		$vars['save_tmpl_revisions_y'] = FALSE;

		if ($vars['save_tmpl_files'] && $vars['save_tmpl_files'] == 'y')
		{
			$vars['save_tmpl_files_n'] = FALSE;
			$vars['save_tmpl_files_y'] = TRUE;
		}

		if ($vars['save_tmpl_revisions'] && $vars['save_tmpl_revisions'] == 'y')
		{
			$vars['save_tmpl_revisions_n'] = FALSE;
			$vars['save_tmpl_revisions_y'] = TRUE;
		}

		$this->cp->render('design/global_template_preferences', $vars);
	}

	/**
	 * Update Global Template Preferences
	 *
	 * The form presented in global_template_preferences() redirects to
	 * here for processing.
	 *
	 */
	function update_global_template_prefs()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		//Just to be careful, let's strip out everything not a template conf
		$this->load->model('admin_model');
		$template_vars = array_keys(ee()->config->get_config_fields('template_cfg'));

		foreach ($_POST as $key => $val)
		{
			if ( ! in_array($key, $template_vars))
			{
				unset($_POST[$key]);
			}
		}

		$this->config->update_site_prefs($_POST);

		$this->session->set_flashdata('message_success', lang('preferences_updated'));
		$this->functions->redirect(cp_url('design/global_template_preferences'));
	}

	// --------------------------------------------------------------------

	/**
	 * Snippets
	 *
	 * Early-parsed variables for dynamic content
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function snippets()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('template_model');
		$this->load->library('table');

		$this->jquery->tablesorter('.mainTable', '{
			headers: {2: {sorter: false}},
			widgets: ["zebra"]
		}');

		$this->cp->set_breadcrumb(cp_url('design/manager'), lang('template_manager'));
		$this->view->cp_page_title = lang('snippets');

		$vars['snippets'] = $this->template_model->get_snippets();
		$vars['snippets_count'] = $vars['snippets']->num_rows();
		$vars['message'] = ($this->input->get_post('delete') !== FALSE) ? lang('variable_deleted') : FALSE;
		$vars['message'] = ($this->input->get_post('update') !== FALSE) ? lang('snippet_updated') : FALSE;

		$this->cp->set_right_nav(array(
			'create_new_snippet' => cp_url('design/snippets_edit')
		));

		$this->cp->render('design/snippets', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Edit/Create Snippets
	 *
	 * Displays the form for the creation/editing of Snippets
	 *
	 * @access	public
	 * @return	void
	 */
	function snippets_edit()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('template_model');

		// form defaults
		$vars = array(
						'msm'					=> FALSE,
						'update'				=> ($this->input->get_post('update') == 1),
						'site_id'				=> $this->config->item('site_id'),
						'all_sites'				=> FALSE,
						'snippet_id'			=> NULL,
						'snippet_name'			=> '',
						'snippet_contents'		=> '',
						'create_edit'			=> lang('snippet_create')
					);

		if ($this->config->item('multiple_sites_enabled') == 'y')
		{
			$vars['msm'] = TRUE;
		}

		if ($this->input->get_post('snippet') !== FALSE)
		{
			if (($snippet = $this->template_model->get_snippet($this->input->get_post('snippet'), TRUE)) !== FALSE)
			{
				$snippet['snippet_site_id'] = $snippet['site_id'];
				unset($snippet['site_id']);

				$vars = array_merge($vars, $snippet);
				$vars['orig_name'] = $vars['snippet_name'];
				$vars['create_edit'] = sprintf(lang('snippet_edit'), $vars['snippet_name']);
				$vars['all_sites'] = ($snippet['snippet_site_id'] == 0) ? TRUE : FALSE;
			}
		}

		$this->view->cp_page_title = $vars['create_edit'];
		$this->cp->set_breadcrumb(cp_url('design/manager'), lang('template_manager'));
		$this->cp->set_breadcrumb(cp_url('design/snippets'), lang('snippets'));

		$this->cp->add_to_head($this->view->head_link('css/codemirror.css'));
		$this->cp->add_to_head($this->view->head_link('css/codemirror-additions.css'));

		$this->cp->add_js_script(array(
				'plugin'	=> 'ee_codemirror',
				'file'		=> array(
					'codemirror/codemirror',
					'codemirror/closebrackets',
					'codemirror/overlay',
					'codemirror/xml',
					'codemirror/css',
					'codemirror/javascript',
					'codemirror/htmlmixed',
					'codemirror/ee-mode',
					'codemirror/dialog',
					'codemirror/searchcursor',
					'codemirror/search',

					'cp/snippet_editor',
				)
			)
		);

		$this->cp->set_action_nav(array(
			'toggle_editor' => 'javascript:$(\'#snippet_contents\').toggleCodeMirror();'
		));

		$this->cp->render('design/snippets_edit', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Snippets Update
	 *
	 * Handles creating/updating of Snippets
	 *
	 * @access	public
	 * @return	void
	 */
	function snippets_update()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('template_model');
		$this->load->library('api');

		foreach (array('snippet_id', 'site_id', 'snippet_name', 'snippet_contents') as $var)
		{
			${$var} = $this->input->get_post($var);
		}

		$update = FALSE;

		// is this an update?
		if ($snippet_id !== FALSE && ($snippet = $this->template_model->get_snippet($snippet_id)) !== FALSE)
		{
			$update = TRUE;
		}

		// validate name and contents
		if ($snippet_name == '' OR $snippet_contents == '' OR $site_id === FALSE)
		{
			show_error(lang('all_fields_required'));
		}
		elseif ($this->api->is_url_safe($snippet_name) === FALSE)
		{
			show_error(lang('illegal_characters'));
		}
		elseif (in_array($snippet_name, $this->cp->invalid_custom_field_names()))
		{
			show_error(lang('reserved_name'));
		}


		// validate site_id
		if ($site_id != $this->config->item('site_id') AND $site_id != 0)
		{
			$site_id = $this->config->item('site_id');
		}

		// looks okay!
		$data = array(
						'snippet_name'		=> $snippet_name,
						'snippet_contents'	=> $snippet_contents,
						'site_id'				=> $site_id
					);

		if ($update === TRUE)
		{
			// if the var name is changing, make sure it's unique
			if ($snippet['snippet_name'] != $data['snippet_name'] && $this->template_model->unique_snippet_name($data['snippet_name']) !== TRUE)
			{
				show_error(lang('duplicate_snippet_name'));
			}

			$this->db->update('snippets', $data, array('snippet_id' => $snippet_id));
			$cp_message = lang('snippet_updated');
		}
		else
		{
			// double check for uniqueness please.  Note that since a variable might change from being for
			// one site to all sites at any time, we have to have strict uniqueness for all variables at all times.
			if ($this->template_model->unique_snippet_name($data['snippet_name']) !== TRUE)
			{
				show_error(lang('duplicate_snippet_name'));
			}

			$this->db->insert('snippets', $data);
			$cp_message = lang('snippet_created');
		}

		// Clear caches- db and template cache my result in update not being reflected
		$this->functions->clear_caching('all');

		$this->session->set_flashdata('message_success', $cp_message);

		if ($this->input->get_post('update_and_return') !== FALSE)
		{
			$this->functions->redirect(cp_url('design/snippets', 'update=1'));
		}
		else
		{
			$this->functions->redirect(cp_url('design/snippets_edit', 'snippet='.$snippet_name.AMP.'update=1'));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Snippets
	 *
	 * Delete Delete I Eat Meat
	 *
	 * @access	public
	 * @return	void
	 */
	function snippets_delete()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('template_model');

		if (($snippet_id = $this->input->get_post('snippet_id')) === FALSE)
		{
			show_error(lang('unauthorized_access'));
		}

		if (($snippet = $this->template_model->get_snippet($snippet_id)) === FALSE)
		{
			show_error(lang('unauthorized_access'));
		}

		// offer up confirmation first
		if ($this->input->get_post('delete_confirm') == TRUE)
		{
			$this->template_model->delete_snippet($snippet_id);
			$this->session->set_flashdata('message_success', lang('snippet_deleted'));
			$this->functions->redirect(cp_url('design/snippets', 'delete=1'));
		}
		else
		{
			$this->cp->set_breadcrumb(cp_url('design/manager'), lang('template_manager'));
			$this->cp->set_breadcrumb(cp_url('design/snippets'), lang('snippets'));

			$this->view->cp_page_title = lang('delete_snippet');
			$this->cp->render('design/snippets_delete', $snippet);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Global Variables
	 *
	 * @access	public
	 * @param	string
	 * @return	type
	 */
	function global_variables()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('template_model');
		$this->load->library('table');

		$this->view->cp_page_title = lang('global_variables');
		$this->cp->set_breadcrumb(cp_url('design/manager'), lang('template_manager'));

		$this->jquery->tablesorter('.mainTable', '{
			headers: {2: {sorter: false}},
			widgets: ["zebra"]
		}');

		$vars['global_variables']		= $this->template_model->get_global_variables();
		$vars['global_variables_count']	= $vars['global_variables']->num_rows();

		$this->cp->set_right_nav(array(
			'create_new_global_variable'  => cp_url('design/global_variables_create')
		));

		$this->cp->render('design/global_variables', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Global Variables
	 *
	 * Processes the updating of Global Variables
	 *
	 * @access	public
	 * @return	type
	 */
	function global_variables_update()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('template_model');
		$this->load->library('table');

		$variable_id = $this->input->get_post('variable_id');
		$variable_name = $this->input->get_post('variable_name');
		$variable_data = $this->input->get_post('variable_data');
		$this->cp->set_breadcrumb(cp_url('design/manager'), lang('template_manager'));
		$this->cp->set_breadcrumb(cp_url('design/global_variables'), lang('global_variables'));

		if ($variable_name != '')
		{
			if ($variable_name == '' OR $variable_data == '')
			{
				show_error(lang('all_fields_required'));
			}

			if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i",$variable_name))
			{
				show_error(lang('illegal_characters'));
			}

			if (in_array($_POST['variable_name'], $this->reserved_vars))
			{
				show_error(lang('reserved_name'));
			}

			$this->template_model->update_global_variable($variable_id, $variable_name, $variable_data);

			// Clear caches- db and template cache my result in update not being reflected
			$this->functions->clear_caching('all');

			// Send success message and move user back to global vars page
			$this->session->set_flashdata('message_success', lang('global_var_updated'));
			$this->functions->redirect(cp_url('design/global_variables'));
		}
		else
		{
			$global_variable = $this->template_model->get_global_variable($variable_id);

			if ($global_variable->num_rows() < 1)
			{
				// They shouldn't be this far
				show_error('variable_does_not_exist');
			}

			$global_variable_info = $global_variable->row(); // PHP 5 can do this in one step...

			$vars['variable_id'] = $global_variable_info->variable_id;
			$vars['variable_name'] = $global_variable_info->variable_name;
			$vars['variable_data'] = $global_variable_info->variable_data;

			$this->view->cp_page_title = lang('global_var_update');

			$this->cp->render('design/global_variables_update', $vars);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Create Global Variables
	 *
	 * Processes the creation of Global Variables
	 *
	 * @access	public
	 * @return	type
	 */
	function global_variables_create()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');

		$variable_name = $this->input->get_post('variable_name');
		$variable_data = $this->input->get_post('variable_data');

		$this->cp->set_breadcrumb(cp_url('design/manager'), lang('template_manager'));
		$this->cp->set_breadcrumb(cp_url('design/global_variables'), lang('global_variables'));

		// Existing variables, will have an id
		if ($variable_name != '')
		{
			if ($variable_name == '' OR $variable_data == '')
			{
				show_error(lang('all_fields_required'));
			}

			if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i",$variable_name))
			{
				show_error(lang('illegal_characters'));
			}

			if (in_array($variable_name, $this->reserved_vars))
			{
				show_error(lang('reserved_name'));
			}

			if ($this->template_model->check_duplicate_global_variable_name($variable_name) === FALSE)
			{
				show_error(lang('duplicate_var_name'));
			}

			$this->template_model->create_global_variable($variable_name, $variable_data);

			// Clear caches- db and template cache my result in update not being reflected
			$this->functions->clear_caching('all');

			// Send success message and move user back to global vars page
			$this->global_variables(lang('global_var_created'));
		}
		else
		{
			$this->view->cp_page_title = lang('create_new_global_variable');
			$this->cp->render('design/global_variables_create');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Global Variables
	 *
	 * @access	public
	 * @return	type
	 */
	function global_variables_delete()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->cp->set_breadcrumb(cp_url('design/manager'), lang('template_manager'));
		$this->cp->set_breadcrumb(cp_url('design/global_variables'), lang('global_variables'));

		$variable_id = $this->input->get_post('variable_id');

		if ($variable_id == '')
		{
			// They shouldn't be this far
			show_error(lang('variable_does_not_exist'));
		}

		$global_variable = $this->template_model->get_global_variable($variable_id);

		if ($global_variable->num_rows() < 1)
		{
			// They shouldn't be this far
			show_error('variable_does_not_exist');
		}

		// offer up confirmation first
		// This is a hidden form value, and === isn't an appropriate check
		if ($this->input->get_post('delete_confirm') == TRUE)
		{
			$this->template_model->delete_global_variable($variable_id);

			// Send success message and move user back to global vars page
			$this->global_variables(lang('variable_deleted'));
		}
		else
		{
			$this->view->cp_page_title = lang('delete_global_variable');

			$global_variable_info = $global_variable->row(); // PHP 5 can do this in one step...

			$vars['variable_id'] = $global_variable_info->variable_id;
			$vars['variable_name'] = $global_variable_info->variable_name;

			$this->cp->render('design/global_variables_delete', $vars);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Template Preferences Manager
	 *
	 * @access	public
	 * @return	type
	 */
	function template_preferences_manager($message = '')
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ($this->input->get_post('id') !== '')
		{
			$group_id = $this->input->get_post('id');
		}

		$vars['message'] = $message;
		$vars['show_template_manager'] = TRUE; // in an error condition, this will go false

		if ($this->session->userdata['group_id'] != 1 && (count($this->session->userdata['assigned_template_groups']) == 0 OR $this->cp->allowed_group('can_admin_design') == FALSE))
		{
			$vars['message'] = lang('no_templates_assigned');
			$vars['show_template_manager'] = FALSE;
			return $this->cp->render('design/template_preferences_manager', $vars);
		}

		$this->load->library('table');

		$this->javascript->output('
			// select all options for template access restrictions
			$("input.select_all").click(function(){
				$("input[class="+$(this).val()+"]").each(function() {
					this.checked = true;
				});
			});

			var the_templates = $(\'div[id^="template_group_div_"]\');

			$("#template_groups").change(function() {
				the_templates.hide();
				var openDivs = $(this).val().toString()
				var ids = new Array();
				ids = openDivs.split(",");

				for(i=0;i<ids.length;i++)
				{
					$("#template_group_div_"+ids[i]).show();
				}

				return false;
			});
		');


		// Retrieve Valid Template Groups and Templates

		$this->db->from('template_groups tg, templates t');
		$this->db->select('tg.group_id, tg.group_name, t.template_id, t.template_name');
		$this->db->where('tg.group_id = t.group_id');
		$this->db->where('tg.site_id', $this->config->item('site_id'));

		if ($this->session->userdata['group_id'] != 1)
		{
			$this->db->where_in('t.group_id', array_keys($this->session->userdata['assigned_template_groups']));
		}

		$this->db->order_by('tg.group_order, t.group_id, t.template_name');

		$query = $this->db->get();

		if ($query->num_rows() == 0)
		{
			$vars['message'] = lang('no_templates_available');
			$vars['show_template_manager'] = FALSE;
			return $this->cp->render('design/template_preferences_manager', $vars);
		}

		// Create MultiSelect Lists

		$current_group = 0;

		$groups = array();
		$tmpl = array();

		$vars['templates'] = array();

		foreach ($query->result_array() as $i => $row)
		{
			if ($row['group_id'] != $current_group)
			{
				$groups[$row['group_id']] = form_prep($row['group_name']);

				if ($current_group != 0)
				{
					$vars['templates']['template_group_div_'.$current_group]['select'] = form_multiselect('template_group_'.$row['group_id'].'[]', $tmpl, '', "size='8' style='width:45%'");
					$vars['templates']['template_group_div_'.$current_group]['active'] = ($current_group == $group_id) ? TRUE : FALSE;
					$tmpl = array();
				}
			}

			$tmpl[$row['template_id']] = form_prep($row['template_name']);
			$current_group = $row['group_id'];
		}

		$groups = form_multiselect('template_groups', $groups, $group_id, "id='template_groups' size='10' style='width:160px'");

		$vars['templates']['template_group_div_'.$current_group]['select'] = form_multiselect('template_group_'.$row['group_id'].'[]', $tmpl, '', "size='8' style='width:45%'");
		$vars['templates']['template_group_div_'.$current_group]['active'] = ($current_group == $group_id) ? TRUE : FALSE;

		$vars['groups'] = $groups;

		if ($this->input->get_post('U'))
		{
			$vars['message'] = lang('preferences_updated');
		}

		// Template Preference Headings
		$headings = array(
						array('template_type', lang('type')),
						array('cache', lang('cache_enable')),
						array('refresh', lang('refresh_interval').' <small>('.lang('refresh_in_minutes').')</small>')
		);

		if ($this->session->userdata['group_id'] == 1)
		{
			$headings[] = array('allow_php', lang('enable_php').' <span class="notice">*</span>');
			$headings[] = array('php_parse_location', lang('parse_stage'));
		}


		if ($this->config->item('save_tmpl_files') == 'y' AND $this->config->item('tmpl_file_basepath') != '')
		{
			$headings[] = array('save_template_file', lang('save_template_file'));
		}

		$headings[] = array('hits', lang('hit_counter'));
		$headings[] = array('protect_javascript', lang('protect_javascript'));

		$vars['headings'] = $headings;

		// Template Preference Options

		$vars['template_prefs'] = array();

		$template_type_options = array(
			'null'		=> lang('do_not_change')
		);

		// Append standard template types to the end of the Do Not Change item
		$template_type_options = array_merge($template_type_options, $this->_get_template_types());

		$vars['template_prefs']['template_type'] = form_dropdown('template_type', $template_type_options, 'null', 'id="template_type"');

		$yes_no_options = array(
			'null'	=> lang('do_not_change'),
			'y'		=> lang('yes'),
			'n'		=> lang('no')
		);

		$vars['template_prefs']['cache'] = form_dropdown('cache', $yes_no_options, 'null', 'id="cache"');
		$vars['template_prefs']['refresh'] = form_input(array('name'=>'refresh', 'value'=>'0', 'size'=>5));

		if ($this->session->userdata['group_id'] == 1)
		{
			$php_i_o_options = array(
				'null'	=> lang('do_not_change'),
				'i'		=> lang('input'),
				'o'		=> lang('output')
			);

			$vars['template_prefs']['allow_php'] = form_dropdown('allow_php', $yes_no_options, 'null', 'id="allow_php"');
			$vars['template_prefs']['php_parse_location'] = form_dropdown('php_parse_location', $php_i_o_options, 'null', 'id="php_parse_location"');
		}

		if ($this->config->item('save_tmpl_files') == 'y' AND $this->config->item('tmpl_file_basepath') != '')
		{
			$vars['template_prefs']['save_template_file'] = form_dropdown('save_template_file', $yes_no_options, 'null', 'id="save_template_file"');
		}

		$vars['template_prefs']['hits'] = form_input(array('name'=>'hits', 'value'=>'', 'size'=>5));
		$vars['template_prefs']['protect_javascript'] = form_dropdown('protect_javascript', $yes_no_options, 'null', 'id="protect_javascript"');

		// Template Access Restrictions
		$this->db->select('group_id, group_title');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('group_id !=', '1');
		$this->db->order_by('group_title');
		$query = $this->db->get('member_groups');

		$vars['template_access'] = array();

		foreach ($query->result() as $row)
		{
			$vars['template_access'][$row->group_id][] = $row->group_title;

			$radio_options = '';
			foreach ($yes_no_options as $key => $lang)
			{
				$checked = ($key === 'null') ? TRUE : FALSE;
				$radio_options .= '<label>'.form_radio('access_'.$row->group_id, $key, $checked, 'class="access_'.$key.'"').NBS.$lang.'</label>'.NBS.NBS.NBS.NBS.NBS.NBS.NBS;
			}

			$vars['template_access'][$row->group_id][] = $radio_options;
		}

		$vars['template_access']['select_all'][] = lang('select_all');

		$select_all_radios = '<label>'.form_radio('select_all', 'access_null', TRUE, 'class="select_all"').NBS.lang('do_not_change').'</label>'.NBS.NBS.NBS.NBS.NBS.NBS.NBS;
		$select_all_radios .= '<label>'.form_radio('select_all', 'access_y', FALSE, 'class="select_all"').NBS.lang('yes').'</label>'.NBS.NBS.NBS.NBS.NBS.NBS.NBS;
		$select_all_radios .= '<label>'.form_radio('select_all', 'access_n', FALSE, 'class="select_all"').NBS.lang('no').'</label>';

		$vars['template_access']['select_all'][] = $select_all_radios;

		$this->db->select('template_groups.group_name, templates.template_name, templates.template_id');
		$this->db->where('template_groups.group_id = '.$this->db->dbprefix('templates.group_id'));
		$this->db->where('template_groups.site_id', $this->config->item('site_id'));
		$this->db->order_by('template_groups.group_name, templates.template_name');

		$query = $this->db->get(array('template_groups', 'templates'));

		$vars['no_auth_bounce_options']['null'] = lang('do_not_change');

		foreach ($query->result() as $row)
		{
			$vars['no_auth_bounce_options'][$row->template_id] = $row->group_name.'/'.$row->template_name;
		}

		$vars['enable_http_auth_options'] = $yes_no_options;

		$this->view->cp_page_title = lang('template_preferences_manager');
		$this->cp->set_breadcrumb(cp_url('design/manager'), lang('template_manager'));

		$this->cp->render('design/template_preferences_manager', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Preferences Manager
	 *
	 * @access	public
	 * @return	type
	 */
	function update_manager_prefs()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		// Determine Valid Template Groups and Templates

		if ($this->session->userdata['group_id'] != 1 && (count($this->session->userdata['assigned_template_groups']) == 0 OR $this->cp->allowed_group('can_admin_design') == FALSE))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->db->from('template_groups tg, templates t');
		$this->db->select('t.template_id, t.group_id, tg.group_name');
		$this->db->where('tg.group_id = t.group_id');
		$this->db->where('tg.site_id', $this->config->item('site_id'));

		if ($this->session->userdata['group_id'] != 1)
		{
			$this->db->where_in('t.group_id', array_keys($this->session->userdata['assigned_template_groups']));
		}

		$query = $this->db->get();

		if ($query->num_rows() == 0)
		{
			show_error(lang('unauthorized_access'));
		}

		$delete = array();

		foreach ($query->result_array() as $row)
		{
			$delete[$row['template_id']] = $row['group_name'];
		}


		$templates = array();

		foreach($_POST as $key => $value)
		{
			if (substr($key, 0, strlen('template_group_')) == 'template_group_' && is_array($value))
			{
				foreach($value as $template)
				{
					$templates[] = $this->db->escape_str($template);
				}
			}
		}

		if (count($templates) == 0)
		{
			show_error(lang('no_templates_selected'));
		}

		// Template Preferences

		$data = array();

		// Assigning the output to local vars so we're not calling the
		// input class over and over
		$template_type = $this->input->post('template_type');
		$cache = $this->input->post('cache');
		$hits = $this->input->post('hits');
		$enable_http_auth = $this->input->post('enable_http_auth');
		$template_route = $this->input->post('template_route');
		$route_required = $this->input->post('route_required');
		$no_auth_bounce = $this->input->post('no_auth_bounce');
		$protect_javascript = $this->input->post('protect_javascript');

		if ($template_type !== FALSE && $template_type != 'null')
		{
			$data['template_type'] = $template_type;
		}

		if (in_array($protect_javascript, array('y', 'n')))
		{
			$data['protect_javascript'] = $protect_javascript;
		}

		if (in_array($cache, array('y', 'n')))
		{
			$data['cache'] = $cache;

			$refresh = $this->input->post('refresh');

			if ($refresh != '' && is_numeric($refresh))
			{
				$data['refresh'] = $refresh;
			}
		}

		if ($this->session->userdata['group_id'] == 1)
		{
			$allow_php = $this->input->post('allow_php');

			if (in_array($allow_php, array('y', 'n')))
			{
				$data['allow_php'] = $allow_php;

				$php_parse_location = $this->input->post('php_parse_location');

				if (in_array($php_parse_location, array('i', 'o')))
				{
					$data['php_parse_location'] = $php_parse_location;
				}
			}
		}

		if ($hits != '' && is_numeric($hits))
		{
			$data['hits'] = $hits;
		}

		if (in_array($enable_http_auth, array('y', 'n')))
		{
			$data['enable_http_auth'] = $enable_http_auth;
		}

		if ($no_auth_bounce != 'null')
		{
			$data['no_auth_bounce'] = $no_auth_bounce;
		}

		if ($this->config->item('save_tmpl_files') == 'y' AND $this->config->item('tmpl_file_basepath') != '')
		{
			$save_template_file = $this->input->post('save_template_file');

			if ($save_template_file != FALSE && $save_template_file != 'null')
			{
				$data['save_template_file'] = $save_template_file;
			}
		}

		if (count($data) > 0)
		{
			// If we switched 'save' to no, we need to delete files.
			$short_name = $this->config->item('site_short_name');

			if ($this->input->post('save_template_file') == 'n')
			{
				$this->db->from('templates');
				$this->db->select('template_name, template_type, template_id');
				$this->db->where('save_template_file', 'y');
				$this->db->where_in('template_id', $templates);

				$query = $this->db->get();


				if ($query->num_rows() > 0)
				{
					foreach ($query->result_array() as $row)
					{
						$tdata = array(
								'template_id'		=> $row['template_id'],
								'site_short_name'	=> $short_name,
								'template_group'	=> $delete[$row['template_id']],
								'template_name'		=> $row['template_name'],
								'template_type'		=> $row['template_type']
								);

						$this->_delete_template_file($tdata);
					}
				}
			}

			$this->db->query($this->db->update_string('exp_templates', $data, "template_id IN ('".implode("','", $templates)."')"));
		}

		// Template Access

		$yes = array();
		$no  = array();

		$this->db->select('group_id');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('group_id !=', '1');
		$this->db->order_by('group_title');

		$query = $this->db->get('member_groups');

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				if ( isset($_POST['access_'.$row['group_id']]))
				{
					if ($_POST['access_'.$row['group_id']] == 'y')
					{
						$yes[] = $row['group_id'];
					}
					elseif($_POST['access_'.$row['group_id']] == 'n')
					{
						$no[] = $row['group_id'];
					}
				}
			}
		}

		if ( ! empty($yes) OR ! empty($no))
		{
			$access = array();

			if (count($no) > 0)
			{
				foreach($templates as $template)
				{
					$access[$template] = $no;
				}
			}

			$this->db->where_in('template_id', $templates);
			$query = $this->db->get('template_no_access');

			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					if ( ! in_array($row['member_group'], $yes) && ! in_array($row['member_group'], $no))
					{
						$access[$row['template_id']][] = $row['member_group'];
					}
				}
			}

			$this->db->where_in('template_id', $templates);
			$this->db->delete('template_no_access');

			foreach($access as $template => $groups)
			{
				if ( empty($groups)) continue;

				foreach($groups as $group)
				{
					$this->db->query($this->db->insert_string('exp_template_no_access', array('template_id' => $template, 'member_group' => $group)));
				}
			}
		}

		$this->functions->redirect(cp_url('design/template_preferences_manager', 'U=1'));
	}

	// --------------------------------------------------------------------

	/**
	 * Create New Template
	 *
	 * @access	public
	 * @return	type
	 */
	function create_new_template()
	{
		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$template_name = $this->input->post('template_name');
		$group_id = $this->input->post('group_id');

		if ($group_id == '')
		{
			show_error(lang('unauthorized_access'));
		}

		if ($template_name == '')
		{
			show_error(lang('you_must_submit_a_name'));
		}

		if ( ! $this->_template_access_privs(array('group_id' => $group_id)))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! preg_match("#^[a-zA-Z0-9_\.-]+$#i", $template_name))
		{
			show_error(lang('illegal_characters'));
		}

		if (in_array($template_name, $this->reserved_names))
		{
			show_error(lang('reserved_name'));
		}

		$this->db->where('group_id', $group_id)
				 ->where('template_name', $template_name);

		if ($this->db->count_all_results('templates'))
		{
			show_error(lang('template_name_taken'));
		}

		$template_data = '';
		$tmp_tmpl_data = NULL;
		$template_type = $this->input->post('template_type');

		if ($this->input->post('existing_template') != 0)
		{
			$qry = $this->db->select('tg.group_name, template_name,
									template_data, template_type,
									template_notes, cache, refresh,
									no_auth_bounce, allow_php, protect_javascript,
									php_parse_location, save_template_file')
							->from('templates t, template_groups tg')
							->where('t.template_id',
									$this->input->post('existing_template'))
							->where('tg.group_id = t.group_id')
							->get();

			if ($this->config->item('save_tmpl_files') == 'y' &&
				$this->config->item('tmpl_file_basepath') != '' &&
				$qry->row('save_template_file')  == 'y')
			{
				$basepath = $this->config->item('tmpl_file_basepath');
				$basepath .= (substr($basepath, -1) != '/') ? '/' : '';

				$this->load->library('api');
				$this->api->instantiate('template_structure');
				$ext = $this->api_template_structure->file_extensions($qry->row('template_type'));

				$basepath .= $this->config->item('site_short_name').'/';
				$basepath .= $qry->row('group_name').'.group/'.$qry->row('template_name').$ext;

				$this->load->helper('file');

				$tmp_tmpl_data = read_file($basepath);

			}

			$template_data = ($tmp_tmpl_data) ? $tmp_tmpl_data : $qry->row('template_data');

			if ($template_type != $qry->row('template_type'))
			{
				$template_type = $qry->row('template_type');
			}

			$data = array(
							'group_id'				=> $this->input->post('group_id'),
							'template_name'			=> $this->input->post('template_name'),
							'template_notes'		=> $qry->row('template_notes') ,
							'cache'					=> $qry->row('cache') ,
							'refresh'				=> $qry->row('refresh') ,
							'no_auth_bounce'		=> $qry->row('no_auth_bounce') ,
							'php_parse_location'	=> $qry->row('php_parse_location') ,
							'protect_javascript'	=> $qry->row('protect_javascript') ,
							'allow_php'				=> ($this->session->userdata('group_id') === 1) ? $qry->row('allow_php')  : 'n',
							'template_type'			=> $template_type,
							'template_data'			=> $template_data,
							'edit_date'				=> $this->localize->now,
							'site_id'				=> $this->config->item('site_id'),
							'last_author_id'		=> 0
						 );

				$template_id = $this->template_model->create_template($data);
		}
		else
		{
			$data = array(
							'group_id'			=> $this->input->post('group_id'),
							'template_name'		=> $this->input->post('template_name'),
							'template_type'		=> $template_type,
							'template_data'		=> '',
							'edit_date'			=> $this->localize->now,
							'site_id'			=> $this->config->item('site_id'),
							'last_author_id'	=> $this->session->userdata['member_id']
						 );

			$template_id = $this->template_model->create_template($data);
		}

		if (isset($_POST['create']))
		{
			$this->manager(lang('template_created'));
		}
		else
		{
			$this->edit_template($template_id, lang('template_created'));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Template
	 *
	 * The main template editor
	 *
	 * @access	public
	 * @return	void
	 */
	function edit_template($template_id = '', $message = '', $warnings = array())
	{
		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ($template_id == '')
		{
			$template_id = $this->input->get_post('id');
			if ($template_id == '')
			{
				show_error(lang('id_not_found'));
			}
		}

		if ( ! is_numeric($template_id))
		{
			show_error(lang('id_not_found'));
		}

		// Supress browser XSS check that could cause obscure bug after saving
		$this->output->set_header("X-XSS-Protection: 0");

		$this->load->library('api');
		$this->api->instantiate('template_structure');
		$this->load->model('design_model');

		$this->load->helper('file');

		$vars['can_admin_design'] = $this->cp->allowed_group('can_admin_design');

		$query = $this->template_model->get_template_info($template_id);

		if ($query->num_rows() == 0)
		{
			show_error(lang('id_not_found'));
		}

		$group_id = $query->row('group_id');
		$vars['template_type'] = $query->row('template_type') ;

		$this->db->select('group_name');
		$result = $this->db->get_where('template_groups', array('group_id' => $group_id));

		$vars['template_group']	 = $result->row('group_name') ;

		if ( ! $this->_template_access_privs(array('group_id' => $group_id)))
		{
			show_error(lang('unauthorized_access'));
		}

		$vars['last_file_edit'] 	= '';
		$vars['file_synced'] 		= TRUE;
		$vars['template_id']		= $template_id;
		$vars['group_id']			= $group_id;
		$vars['template_data']		= $query->row('template_data') ;
		$vars['template_name']		= $query->row('template_name') ;
		$vars['template_notes']		= $query->row('template_notes') ;
		$vars['save_template_file'] = ($query->row('save_template_file') != 'y') ? FALSE : TRUE ;
		$vars['no_auth_bounce']		= $query->row('no_auth_bounce');
		$vars['enable_http_auth']	= $query->row('enable_http_auth');
		$vars['protect_javascript']	= $query->row('protect_javascript');
		$vars['template_route'] 	= $query->row('route');
		$vars['route_required'] 	= $query->row('route_required');

		foreach(array('template_type', 'cache', 'refresh', 'allow_php', 'php_parse_location', 'hits', 'protect_javascript') as $pref)
		{
			$vars['prefs'][$pref] = $query->row($pref);
		}

		$vars['prefs']['template_size'] = $this->session->userdata('template_size');



		// now that we have the info, we can set the breadcrumb and page titles
		$this->view->cp_page_title = lang('edit_template').' ('.$vars['template_group'].' / '.$vars['template_name'].')';
		$this->cp->set_breadcrumb(cp_url('design/manager', 'tgpref='.$group_id), lang('template_manager'));

		$vars['edit_date'] = $this->localize->human_time($query->row('edit_date'));

		$mquery = $this->db->query("SELECT screen_name FROM exp_members WHERE member_id = ".$query->row('last_author_id'));

		$vars['last_author'] = ($mquery->num_rows() == 0) ? '' : $mquery->row('screen_name');

		/* -------------------------------------
		/*  'edit_template_start' hook.
		/*  - Allows complete takeover of the template editor
		/*  - Added 1.6.0
		*/
			$this->extensions->call('edit_template_start', $query, $template_id, $message);
			if ($this->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------*/

		// Clear old revisions

		if ($this->config->item('save_tmpl_revisions') == 'y')
		{
			$maxrev = $this->config->item('max_tmpl_revisions');

			if ($maxrev != '' AND is_numeric($maxrev) AND $maxrev > 0)
			{
				$res = $this->db->query("SELECT tracker_id FROM exp_revision_tracker WHERE item_id = '$template_id' AND item_table = 'exp_templates' AND item_field ='template_data' ORDER BY tracker_id DESC");

				if ($res->num_rows() > 0  AND $res->num_rows() > $maxrev)
				{
					$flag = '';

					$ct = 1;
					foreach ($res->result_array() as $row)
					{
						if ($ct >= $maxrev)
						{
							$flag = $row['tracker_id'];
							break;
						}

						$ct++;
					}

					if ($flag != '')
					{
						$this->db->query("DELETE FROM exp_revision_tracker WHERE tracker_id < $flag AND item_id = '".$this->db->escape_str($template_id)."' AND item_table = 'exp_templates' AND item_field ='template_data'");
					}
				}
			}
		}

		if ($this->config->item('save_tmpl_files') == 'y' AND $this->config->item('tmpl_file_basepath') != '' AND $vars['save_template_file'] == TRUE)
		{
			$this->load->helper('file');
			$basepath = $this->config->slash_item('tmpl_file_basepath');
			$basepath .= $this->config->item('site_short_name').'/'.$vars['template_group'].'.group/'.$query->row('template_name').$this->api_template_structure->file_extensions($query->row('template_type'));

			if (($file = read_file($basepath)) !== FALSE)
			{
				// Get the file edit date
				$file_date = get_file_info($basepath, 'date');
				if ($file_date !== FALSE)
				{
					$vars['last_file_edit'] = $this->localize->human_time($file_date['date']);
					if ($query->row('edit_date') < $file_date['date'])
					{
							$vars['file_synced'] = FALSE;
							$vars['template_data'] = $file;
					}
					else
					{
						$vars['file_synced'] = TRUE;
					}
				}
			}
		}

		$vars['view_path'] = $this->functions->fetch_site_index(0, 0).QUERY_MARKER.'URL='.$this->functions->fetch_site_index();
		$vars['view_path'] = rtrim($vars['view_path'], '/').'/';

		if ($vars['template_type'] == 'css')
		{
			$vars['view_path'] .= QUERY_MARKER.'css='.$vars['template_group'].'/'.$vars['template_name'];
		}
		else
		{
			$vars['view_path'] .= $vars['template_group'].(($vars['template_name'] == 'index') ? '' : '/'.$vars['template_name']);
		}

		$vars['revisions_js'] = ''; //"class='select' onchange='flipButtonText(this.options[this.selectedIndex].value);'>";

		$vars['revision_options'][] = lang('revision_history');

		$query = $this->db->query("SELECT tracker_id, item_date, screen_name FROM exp_revision_tracker LEFT JOIN exp_members ON exp_members.member_id = exp_revision_tracker.item_author_id WHERE item_table = 'exp_templates' AND item_field = 'template_data' AND item_id = '".$this->db->escape_str($template_id)."' ORDER BY tracker_id DESC");

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$vars['revision_options'][$row['tracker_id']] = $this->localize->human_time($row['item_date']).' ('.$row['screen_name'].')';
			}

			$vars['revision_options']['clear'] = lang('clear_revision_history');
		}

		$vars['message'] = $message;

		$vars['save_template_revision'] = ($this->config->item('save_tmpl_revisions') == 'y') ? 1 : '';

		$vars['can_save_file'] = ($this->config->item('save_tmpl_files') == 'y' && $this->config->item('tmpl_file_basepath') != '') ? TRUE : FALSE;

		$this->cp->add_to_head($this->view->head_link('css/codemirror.css'));
		$this->cp->add_to_head($this->view->head_link('css/codemirror-additions.css'));

		$this->javascript->set_global(
			'editor.lint', $this->_get_installed_plugins_and_modules()
		);

		$this->cp->add_js_script(array(
				'plugin'	=> 'ee_codemirror',
				'file'		=> array(
					'codemirror/codemirror',
					'codemirror/closebrackets',
					'codemirror/lint',
					'codemirror/overlay',
					'codemirror/xml',
					'codemirror/css',
					'codemirror/javascript',
					'codemirror/htmlmixed',
					'codemirror/ee-mode',
					'codemirror/dialog',
					'codemirror/searchcursor',
					'codemirror/search',

					'cp/template_editor',
					'cp/manager'
				)
			)
		);

		$this->cp->set_action_nav(array(
			'toggle_editor' => 'javascript:$(\'#template_data\').toggleCodeMirror();'
		));

		$vars['table_template'] = array(
					'table_open'			=> '<table class="templateTable templateEditorTable" border="0" cellspacing="0" cellpadding="0">'
		);

		// member group query
		$this->db->select('group_id, group_title');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('group_id !=', '1');
		$this->db->order_by('group_title');
		$m_groups = $this->db->get('member_groups');

		$vars['member_groups'] = array();
		foreach($m_groups->result() as $m_group)
		{
			$vars['member_groups'][$m_group->group_id] = $m_group;
		}

		// template access restrictions query
		$denied_groups = $this->design_model->template_access_restrictions();

		$vars['access'] = array();

		foreach($vars['member_groups'] as $mgroup_id => $group)
		{
			$vars['access'][$mgroup_id] = isset($denied_groups[$template_id][$mgroup_id]) ? FALSE : TRUE;
		}

		$vars['no_auth_bounce_options'] = array();
		if ($this->cp->allowed_group('can_admin_design'))
		{
			$query = $this->template_model->get_templates();

			foreach ($query->result_array() as $row)
			{
				$vars['no_auth_bounce_options'][$row['template_id']] = $row['group_name'].'/'.$row['template_name'];
			}
		}

		$vars['warnings'] = $warnings;
		$vars['template_types'] = $this->_get_template_types();

		$this->javascript->set_global('manager.warnings', $warnings);
		$this->cp->set_right_nav(array(
			'view_rendered_template' => $vars['view_path']
			));

		$this->cp->render('design/edit_template', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Update template
	 *
	 * @access	public
	 * @return	void
	 */
	function update_template()
	{
		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $template_id = $this->input->post('template_id'))
		{
			return false;
		}

		if ( ! is_numeric($template_id))
		{
			return false;
		}

		if ( ! $this->_template_access_privs(array('template_id' => $template_id)))
		{
			show_error(lang('unauthorized_access'));
		}

		$save_result = FALSE;
		$delete_template_file = FALSE;
		$save_template_file = ($this->input->post('save_template_file') == 'y') ? 'y' : 'n';

		/** -------------------------------
		/**	 Save template as file
		/** -------------------------------*/

		// Depending on how things are set up we might save the template data in a text file

		if ($this->config->item('tmpl_file_basepath') != '' && $this->config->item('save_tmpl_files') == 'y')
		{
			$query = $this->db->query("SELECT exp_templates.template_name, exp_templates.template_type, exp_templates.save_template_file, exp_template_groups.group_name
								FROM exp_templates
								LEFT JOIN exp_template_groups ON exp_templates.group_id = exp_template_groups.group_id
								WHERE template_id = '".$this->db->escape_str($template_id)."'");

			if ($save_template_file == 'y')
			{
				$tdata = array(
								'site_short_name'	=> $this->config->item('site_short_name'),
								'template_id'		=> $template_id,
								'template_group'	=> $query->row('group_name') ,
								'template_name'		=> $query->row('template_name'),
								'template_type'		=> $query->row('template_type'),
								'template_data'		=> $_POST['template_data'],
								'edit_date'			=> $this->localize->now,
								'last_author_id'	=> $this->session->userdata['member_id']
								);

				$save_result = $this->update_template_file($tdata);
			}
			else
			{
				// If the template was previously saved as a text file,
				// but the checkbox was not selected this time we'll
				// delete the file

				if ($query->row('save_template_file')  == 'y')
				{
					$delete_template_file = TRUE;

					$tdata = array(
								'template_id'		=> $template_id,
								'site_short_name'	=> $this->config->item('site_short_name'),
								'template_group'	=> $query->row('group_name') ,
								'template_name'		=> $query->row('template_name'),
								'template_type'		=> $query->row('template_type')
								);

					$template_file_result = $this->_delete_template_file($tdata);
				}
			}
		}

		/** -------------------------------
		/**	 Save revision cache
		/** -------------------------------*/

		if ($this->input->post('save_template_revision') == 'y')
		{
			$data = array(
							'item_id'			=> $template_id,
							'item_table'		=> 'exp_templates',
							'item_field'		=> 'template_data',
							'item_data'			=> $_POST['template_data'],
							'item_date'			=> $this->localize->now,
							'item_author_id'	=> $this->session->userdata['member_id']
						 );

			$this->db->query($this->db->insert_string('exp_revision_tracker', $data));
		}

		/** -------------------------------
		/**	 Save Template
		/** -------------------------------*/

		$this->db->query($this->db->update_string('exp_templates', array('template_data' => $_POST['template_data'], 'edit_date' => $this->localize->now, 'last_author_id' => $this->session->userdata['member_id'], 'save_template_file' => $save_template_file, 'template_notes' => $_POST['template_notes']), "template_id = '$template_id'"));

		// Clear cache files
		$this->functions->clear_caching('all');

		$message = lang('template_updated');
		$cp_message['message_success'] = lang('template_updated');

		if ($save_template_file == 'y' AND $save_result == FALSE)
		{
			$cp_message['message_failure'] = lang('template_not_saved');
			$message .= BR.lang('template_not_saved');
		}
		elseif ($delete_template_file == TRUE && $template_file_result == FALSE)
		{
			$cp_message['message_failure'] = lang('template_file_not_deleted');
			$message .= BR.lang('template_file_not_deleted');
		}

		/* -------------------------------------
		/*  'update_template_end' hook.
		/*  - Add more things to do for template
		/*  - Added 1.6.0
		*/
			$this->extensions->call('update_template_end', $template_id, $message);
			if ($this->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------*/

		if (isset($_POST['update_and_return']))
		{
			$this->session->set_flashdata($cp_message);
			$this->db->select('group_id');
			$this->db->where('template_id', $template_id);
			$query = $this->db->get('templates');

			$this->functions->redirect(cp_url('design/manager', 'tgpref='.$query->row('group_id')));
		}
		else
		{
			$this->session->set_flashdata($cp_message);
			$this->functions->redirect(cp_url('design/edit_template', 'id='.$template_id));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Validate tags
	 *
	 * @access	private
	 * @return	void
	 */
	function _get_installed_plugins_and_modules()
	{
		$this->load->library('template');
		$this->load->model('addons_model');
		$this->template->fetch_addons();

		$modules = $this->template->modules;
		$plugins = $this->template->plugins;
		unset($this->template);

		$this->db->select('module_name');
		$this->db->order_by('module_name');
		$query = $this->db->get('modules');

		$installed = array_map('array_pop', $query->result_array());
		$installed = array_map('strtolower', $installed);

		return array(
			'available' => array_merge($modules, $plugins),
			'not_installed' => array_values(array_diff($modules, $installed))
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Template File
	 *
	 * Saves / updates template saved as a file
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */
	function update_template_file($data)
	{
		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! isset($data['template_id']) OR ! $this->_template_access_privs(array('template_id' => $data['template_id'])))
		{
			return FALSE;
		}

		if ($this->config->item('save_tmpl_files') == 'n' OR $this->config->item('tmpl_file_basepath') == '')
		{
			return FALSE;
		}

		// check the main template path
		$basepath = $this->config->slash_item('tmpl_file_basepath');

		if ( ! @is_dir($basepath) OR ! is_really_writable($basepath))
		{
			return FALSE;
		}

		$this->load->library('api');
		$this->api->instantiate('template_structure');

		// add a site short name folder, in case MSM uses the same template path, and repeat
		$basepath .= $this->config->item('site_short_name');

		if ( ! @is_dir($basepath))
		{
			if ( ! @mkdir($basepath, DIR_WRITE_MODE))
			{
				return FALSE;
			}
			@chmod($basepath, DIR_WRITE_MODE);
		}

		// and finally with our template group
		$basepath .= '/'.$data['template_group'].'.group';

		if ( ! is_dir($basepath))
		{
			if ( ! @mkdir($basepath, DIR_WRITE_MODE))
			{
				return FALSE;
			}
			@chmod($basepath, DIR_WRITE_MODE);
		}

		$filename = $data['template_name'].$this->api_template_structure->file_extensions($data['template_type']);

		if ( ! $fp = @fopen($basepath.'/'.$filename, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			return FALSE;
		}
		else
		{
			flock($fp, LOCK_EX);
			fwrite($fp, $data['template_data']);
			flock($fp, LOCK_UN);
			fclose($fp);

			@chmod($basepath.'/'.$filename, FILE_WRITE_MODE);
		}

		return TRUE;
	}


	function _delete_template_file($data)
	{
		if ( ! isset($data['template_id']) OR ! $this->_template_access_privs(array('template_id' => $data['template_id'])))
		{
			return FALSE;
		}

		$this->load->library('api');
		$this->api->instantiate('template_structure');

		$basepath = $this->config->slash_item('tmpl_file_basepath');

		$basepath .= $data['site_short_name'].'/'.$data['template_group'].'.group/'.$data['template_name'].$this->api_template_structure->file_extensions($data['template_type']);

		if ( ! @unlink($basepath))
		{
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	  *  View Template Revision
	  */
	function template_revision_history()
	{
		if ($this->config->item('save_tmpl_revisions') == 'n')
		{
			// Revisions are off, but they are here anyway
			// It's confusing to simply show a white screen, so
			// give some feedback.
			show_error(lang('tmpl_revisions_not_enabled'));
		}

		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $id = $this->input->get_post('revision_id'))
		{
			show_error(lang('unauthorized_access'));
		}



		$item_id = $this->input->get_post('template');

		$this->load->library('api');
		$this->api->instantiate('template_structure');

		$vars = array();

		$this->javascript->output('$(window).focus();');

		if ($id != 'clear')
		{
			$vars['cp_page_title'] = lang('revision_history');
			$this->cp->set_breadcrumb(cp_url('design/manager'), lang('template_manager'));

			$this->db->select('item_id, item_data, item_date');
			$this->db->where('tracker_id', $id);
			$this->db->where('item_table', 'exp_templates');
			$this->db->where('item_field', 'template_data');
			$query = $this->db->get('revision_tracker');

			$item_id = $query->row('item_id');
			$vars['revision_data'] = $query->row('item_data');
			$vars['type'] = 'revision';

			if ($query->num_rows() == 0)
			{
				return false;
			}

			$vars['revision_date'] = $this->localize->human_time($query->row('item_date'));
		}
		else
		{
			$vars['cp_page_title'] = lang('clear_revision_history');
			$vars['revision_data'] = '';
			$vars['type'] = 'clear';
			$vars['form_hidden'] = array('template_id' => $item_id);
			$vars['template_name'] = '';
			$vars['revision_date'] = '';
		}

		if ( ! $this->_template_access_privs(array('template_id' => $item_id)))
		{
			show_error(lang('unauthorized_access'));
		}

		$query = $this->template_model->get_template_info($item_id);

		if ($query->num_rows() == 0)
		{
			show_error(lang('id_not_found'));
		}

		$group_id = $query->row('group_id');

		$this->db->select('group_name');
		$result = $this->db->get_where('template_groups', array('group_id' => $group_id));

		$vars['template_group']	 = $result->row('group_name') ;
		$vars['template_name']		= $query->row('template_name') ;

		$this->cp->render('design/revision_history', $vars);
	}


	// --------------------------------------------------------------------

	/**
	  *  Clear Revision History
	  */

	function clear_revision_history()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $id = $this->input->post('template_id'))
		{
			return false;
		}

		$this->db->where('item_id', $id);
		$this->db->where('item_table', 'exp_templates');
		$this->db->where('item_field', 'template_data');
		$this->db->delete('revision_tracker');

		$vars['cp_page_title'] = lang('history_cleared');
		$vars['revision_data'] = '';
		$vars['type'] = 'cleared';
		$vars['form_hidden'] = array();
		$vars['template_name'] = '';
		$vars['revision_date'] = '';

		$this->cp->render('design/revision_history', $vars);
	}


	// --------------------------------------------------------------------

	/**
	  *  Template Delete Confirm
	  */
	function template_delete_confirm()
	{
		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$template_id = $this->input->get_post('template_id');

		if ($template_id == '')
		{
			return $this->manager();
		}

		if ( ! is_numeric($template_id))
		{
			show_error(lang('template_id_not_found'));
		}

		$this->load->library('api');
		$this->api->instantiate('template_structure');

		$query = $this->template_model->get_template_info($template_id, array('group_id', 'template_name', 'template_type'));

		// You can't delete the index template
		if ($query->row('template_name') == 'index')
		{
			show_error(lang('index_delete_disallowed'));
		}

		$group_id	= $query->row('group_id') ;
		$vars['template_name'] = $query->row('template_name') ;

		if ( ! $this->cp->allowed_group('can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));

			if ( ! $this->_template_access_privs(array('group_id' => $group_id)))
			{
				show_error(lang('unauthorized_access'));
			}
		}

		$this->db->select('group_name');
		$result = $this->db->get_where('template_groups', array('group_id' => $group_id));

		$vars['template_group']	 = $result->row('group_name');
		$file = FALSE;

		if ($this->config->item('save_tmpl_files') == 'y' && $this->config->item('tmpl_file_basepath') != '')
		{
			$basepath = $this->config->slash_item('tmpl_file_basepath');
			$basepath .= $this->config->item('site_short_name').'/'.$vars['template_group'].'.group/'.$query->row('template_name').$this->api_template_structure->file_extensions($query->row('template_type'));

			$this->load->helper('file_helper');
			if (($file = read_file($basepath)) !== FALSE)
			{
				$file = $basepath;
			}
		}

		$vars['file'] = $file;
		$vars['damned'] = array($template_id);
		$vars['group_id'] = $group_id;

		$vars['cp_page_title'] = lang('template_del_conf');
		$this->cp->set_breadcrumb(cp_url('design/manager', 'tgpref='.$group_id), lang('template_manager'));

		$vars['form_hidden']['template_id'] = $template_id;

		$this->cp->render('design/template_delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 *  Delete Template
	 */
	function template_delete()
	{
		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$template_id = $this->input->get_post('template_id');

		if ($template_id == '')
		{
			$this->manager();
		}

		if ( ! is_numeric($template_id))
		{
			show_error('id_not_found');
		}

		$path = FALSE;

		$query = $this->template_model->get_template_info($template_id, array('group_id', 'template_type', 'template_name'));

		$group_id = $query->row('group_id');

		if ( ! $this->cp->allowed_group('can_admin_templates'))
		{
			if ( ! $this->_template_access_privs(array('group_id' => $group_id)))
			{
				show_error(lang('unauthorized_access'));
			}
		}

		if ($this->config->item('save_tmpl_files') == 'y' && $this->config->item('tmpl_file_basepath') != '')
		{
			$this->load->library('api');
			$this->api->instantiate('template_structure');

			$this->db->select('group_name');
			$result = $this->db->get_where('template_groups', array('group_id' => $group_id));

			$this->load->helper('file');
			$basepath = $this->config->slash_item('tmpl_file_basepath');
			$basepath .= $this->config->item('site_short_name').'/'.$result->row('group_name').'.group/'.$query->row('template_name').$this->api_template_structure->file_extensions($query->row('template_type'));

			if (($file = read_file($basepath)) !== FALSE)
			{
					$path = $basepath;
			}
		}

		$out = $this->template_model->delete_template($template_id, $path);
		$message = ($out === TRUE) ? lang('template_deleted') : lang('error_deleting_template');

		$this->manager($message);
	}

	// --------------------------------------------------------------------

	/**
	 * Template Access Privs
	 *
	 * Verifies access privileges to edit a template
	 *
	 * @access	private
	 * @param	mixed
	 * @return	bool
	 */
	function _template_access_privs($data = '')
	{
		// If the user is a Super Admin, return true

		if ($this->session->userdata['group_id'] == 1)
		{
			return TRUE;
		}

		$template_id = '';
		$group_id	 = '';

		if (is_array($data))
		{
			if (isset($data['template_id']))
			{
				$template_id = $data['template_id'];
			}

			if (isset($data['group_id']))
			{
				$group_id = $data['group_id'];
			}
		}


		if ($group_id == '')
		{
			if ($template_id == '')
			{
				return FALSE;
			}
			else
			{
					$query = $this->db->query("SELECT group_id, template_name FROM exp_templates WHERE template_id = '".$this->db->escape_str($template_id)."'");

					$group_id = $query->row('group_id') ;
			}
		}


		$access = FALSE;

		foreach ($this->session->userdata['assigned_template_groups'] as $key => $val)
		{
			if ($group_id == $key)
			{
				$access = TRUE;
				break;
			}
		}

		if ($access == FALSE)
		{
			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * User Message
	 *
	 * Processes new template
	 *
	 * @access	public
	 * @param	string
	 * @return	type
	 */
	function user_message($message = '')
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$template_id = $this->input->get_post('template_id');
		$template_data = $this->input->get_post('template_data');

		$this->cp->add_js_script('plugin', 'markitup');

		$markItUp = array(
			'nameSpace'		=> "html",
			'onShiftEnter'	=> array('keepDefault' => FALSE, 'replaceWith' => "<br />\n"),
			'onCtrlEnter'	=> array('keepDefault' => FALSE, 'openWith' => "\n<p>", 'closeWith' => "</p>\n")
		);

		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- allow_textarea_tabs => Preserve tabs in all textareas or disable completely
		/* -------------------------------------------*/

		if($this->config->item('allow_textarea_tabs') != 'n') {
			$markItUp['onTab'] = array('keepDefault' => FALSE, 'replaceWith' => "\t");
		}

		$this->javascript->output('
			$("#template_data").markItUp('.json_encode($markItUp).');
		');

		// check what the message is also, as this method could throw itself
		// into an infinite loop if we aren't careful here.
		if ($template_id)
		{
			$this->template_model->update_specialty_template($template_id, $template_data);

			$this->session->set_flashdata('message_success', lang('template_updated'));
			$this->functions->redirect(cp_url('design/user_message'));
		}
		else
		{
			$this->lang->loadfile('specialty_tmp');

			$this->view->cp_page_title = lang('user_message');

			$template = $this->template_model->get_specialty_template('message_template');
			$template_data = $template->row();

			$vars = array(
				'template_data'	=> $template_data->template_data,
				'template_id'	=> $template_data->template_id,
				'message'		=> $message
			);

			$this->cp->render('design/user_message', $vars);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * System Offline
	 *
	 * The system offline template
	 *
	 * @access	public
	 * @param	string
	 * @return	type
	 */
	function system_offline()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$template_id = $this->input->get_post('template_id');
		$template_data = $this->input->get_post('template_data');

		$this->cp->add_js_script('plugin', 'markitup');

		$markItUp = array(
			'nameSpace'	=> "html",
			'onShiftEnter'	=> array('keepDefault' => FALSE, 'replaceWith' => "<br />\n"),
			'onCtrlEnter'	=> array('keepDefault' => FALSE, 'openWith' => "\n<p>", 'closeWith' => "</p>\n")
		);

		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- allow_textarea_tabs => Preserve tabs in all textareas or disable completely
		/* -------------------------------------------*/

		if($this->config->item('allow_textarea_tabs') != 'n') {
			$markItUp['onTab'] = array('keepDefault' => FALSE, 'replaceWith' => "\t");
		}

		$this->javascript->output('
			$("#template_data").markItUp('.json_encode($markItUp).');
		');

		if ($template_id)
		{
			$this->template_model->update_specialty_template($template_id, $template_data);

			$this->session->set_flashdata('message_success', lang('template_updated'));
			$this->functions->redirect(cp_url('design/system_offline'));
		}
		else
		{
			$this->lang->loadfile('specialty_tmp');

			$this->view->cp_page_title = lang('offline_template');

			$template = $this->template_model->get_specialty_template('offline_template');
			$template_data = $template->row();

			$vars = array(
				'template_data'	=> $template_data->template_data,
				'template_id'	=> $template_data->template_id,
			);

			$this->cp->render('design/system_offline', $vars);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Email Notification
	 *
	 * @access	public
	 * @return	void
	 */
	function email_notification()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('specialty_tmp');

		$this->view->cp_page_title = lang('email_notification_template');

		$vars['specialty_email_templates_summary'] = $this->template_model->get_specialty_email_templates_summary();

		$this->cp->render('design/email_notification', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Email Template
	 *
	 * @access	public
	 * @return	void
	 */
	function edit_email_notification()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('specialty_tmp');

		$template = $this->input->get_post('template');

		$template_query = $this->template_model->get_specialty_template($template);

		if ($template_query->num_rows() == 0)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->view->cp_page_title = lang('edit_template');
		$this->cp->set_breadcrumb(cp_url('design/email_notification'), lang('email_notification_template'));

		$this->cp->add_js_script(array('plugin' => 'markitup'));

		$markItUp = array(
			'nameSpace'	=> "html",
			'onShiftEnter'	=> array('keepDefault' => FALSE, 'replaceWith' => "<br />\n"),
			'onCtrlEnter'	=> array('keepDefault' => FALSE, 'openWith' => "\n<p>", 'closeWith' => "</p>\n")
		);

		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- allow_textarea_tabs => Preserve tabs in all textareas or disable completely
		/* -------------------------------------------*/

		if($this->config->item('allow_textarea_tabs') != 'n')
		{
			$markItUp['onTab'] = array('keepDefault' => FALSE, 'replaceWith' => "\t");
		}

		$this->javascript->output('
			$("#template_data").markItUp('.json_encode($markItUp).');
		');

		$vars = array(
			'vars'				=> $this->template_model->get_specialty_template_vars($template),
			'template'			=> $template,
			'template_data'		=> $template_query->row('template_data'),
			'template_title'	=> $template_query->row('data_title'),
			'template_id'		=> $template_query->row('template_id'),
			'template_name'		=> (lang($template) == FALSE) ? $template : lang($template),
			'enable_template'	=> $template_query->row('enable_template')
		);

		$this->cp->render('design/edit_email_notification', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Email Notification
	 *
	 * @access	public
	 * @return	void
	 */
	function update_email_notification()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$template_name = $this->input->post('template');
		$template_id = $this->input->post('template_id');
		$template_data = $this->input->post('template_data');
		$enable_template = ($this->input->post('enable_template')) ? 'y' : 'n';
		$template_title = $this->input->post('template_title');

		$query = $this->template_model->get_specialty_template($template_name);

		if ($query->num_rows() != 1 OR $query->row('template_id') != $template_id)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->template_model->update_specialty_template($template_id, $template_data,
															$enable_template, $template_title);

		// Clear cache files

		$this->functions->clear_caching('all');

		$this->session->set_flashdata('message_success', lang('template_updated'));

		if ($this->input->get_post('update_and_return') !== FALSE)
		{
			$this->functions->redirect(cp_url('design/email_notification'));
		}

		// go back to the edit page for this template
		$this->functions->redirect(cp_url('design/edit_email_notification','template='.$template_name));
	}

	// --------------------------------------------------------------------

	/**
	  *	 Member Profile Templates
	  */
	function member_profile_templates()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_mbr_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('member_model');

		$vars['profiles'] = $this->member_model->get_profile_templates();

		$this->view->cp_page_title = lang('member_profile_templates');

		$this->cp->render('design/member_profile_templates', $vars);
	}

	// --------------------------------------------------------------------

	/** -----------------------------
	/**	 List Templates within a set
	/** -----------------------------*/

	function list_profile_templates()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_mbr_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$path = PATH_MBR_THEMES.$this->security->sanitize_filename($this->input->get_post('name'));

		if ( ! is_dir($path))
		{
			show_error(lang('unable_to_find_templates'));
		}

		$this->load->helper('directory');
		$files = directory_map($path, TRUE);

		$vars = array();
		$vars['theme_name'] = $this->input->get_post('name');
		$vars['theme_display_name'] = ucfirst(str_replace("_", " ", $vars['theme_name']));
		$vars['templates'] = array();

		foreach ($files as $val)
		{
			if (strpos($val, '.') === FALSE)
			{
				continue;
			}

			$human = substr($val, 0, -strlen(strrchr($val, '.')));
			$vars['templates'][$val] = (lang($human) == FALSE) ? $human : lang($human);
		}

		asort($vars['templates']);

		$this->view->cp_page_title = lang('member_profile_templates');

		$this->cp->render('design/member_profile_templates_list', $vars);
	}

	// --------------------------------------------------------------------


	/** -----------------------------
	/**	 Edit Profile Template
	/** -----------------------------*/

	function edit_profile_template($theme = '', $name = '', $template_data = '')
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_mbr_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$update = ($theme != '' AND $name != '') ? TRUE : FALSE;

		if ($theme == '')
		{
			$theme = $this->input->get_post('theme');
		}

		if ($name == '')
		{
			$name = $this->input->get_post('name');
		}

		$path = PATH_MBR_THEMES.$this->security->sanitize_filename($theme).'/'.$name;

		if ( ! file_exists($path))
		{
			show_error(lang('unable_to_find_template_file'));
		}

		$human = substr($name, 0, -strlen(strrchr($name, '.')));

		$vars['template_name']		= (lang($human) == FALSE) ? $human : lang($human);
		$vars['theme']				= $theme;
		$vars['theme_display_name']	= ucfirst(str_replace("_", " ", $vars['theme']));
		$vars['template_data']		= ($update === FALSE) ? file_get_contents($path) : $template_data;
		$vars['name']				= $name;
		$vars['not_writable']		= ! is_really_writable($path);
		$vars['message']			= ($update === TRUE) ? lang('template_updated') : '';
		$vars['type']				= 'profile';

		$this->view->cp_page_title = lang('member_profile_templates');
		$this->cp->add_js_script('plugin', 'markitup');

		$markItUp = array(
			'nameSpace'		=> "html",
			'onShiftEnter'	=> array('keepDefault' => FALSE, 'replaceWith' => "<br />\n"),
			'onCtrlEnter'	=> array('keepDefault' => FALSE, 'openWith' => "\n<p>", 'closeWith' => "</p>\n")
		);

		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- allow_textarea_tabs => Preserve tabs in all textareas or disable completely
		/* -------------------------------------------*/

		if($this->config->item('allow_textarea_tabs') != 'n') {
			$markItUp['onTab'] = array('keepDefault' => FALSE, 'replaceWith' => "\t");
		}

		$this->javascript->output('
			$("#template_data").markItUp('.json_encode($markItUp).');
		');

		$this->cp->render('design/edit_theme_template', $vars);
	}



	/** -----------------------------
	/**	 Save Profile Template
	/** -----------------------------*/

	function update_theme_template()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_mbr_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$theme = $this->input->get_post('theme');
		$name = $this->input->get_post('name');
		$template_data	= $this->input->get_post('template_data');

		switch ($type = $this->input->get_post('type'))
		{
			case 'profile':
			default:
				$path = PATH_MBR_THEMES.$this->security->sanitize_filename($theme).'/'.$this->security->sanitize_filename($name);
		}

		if ( ! file_exists($path))
		{
			show_error(lang('unable_to_find_template_file'));
		}

		$this->load->helper('file');

		if ( ! write_file($path, $template_data))
		{
			show_error(lang('error_opening_template'));
		}

		// Clear cache files

		$this->functions->clear_caching('all');

		$this->session->set_flashdata('message_success',lang('template_updated'));
		if ($this->input->get_post('update_and_return') !== FALSE)
		{
			$this->functions->redirect(cp_url('design/list_'.$type.'_templates', 'name='.$theme));
		}
		// go back to the edit page for the appropriate area
		$function = "edit_{$type}_template";
		$this->functions->redirect(cp_url('design/'.$function, 'theme='.$theme.AMP.'name='.$name));
	}

	// --------------------------------------------------------------------

	/**
	 * Update Template Routes
	 *
	 * Set routes for the route manager page
	 *
	 * @access	public
	 * @return	type
	 */
	function update_template_routes()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ($this->config->item('enable_template_routes') == 'n')
		{
			$this->functions->redirect(cp_url('design/manager'));
		}

		if (empty($_POST))
		{
			$this->functions->redirect(cp_url('design/url_manager'));
		}

		ee()->load->library('template_router');
		ee()->load->model('template_model');
		$errors = array();
		$error_ids = array();
		$updated_routes = array();
		$query = $this->template_model->get_templates();

		foreach ($query->result() as $template)
		{
			$error = FALSE;
			$id = $template->template_id;
			$route_required = $this->input->post('required_' . $id);
			$route = $this->input->post('route_' . $id);
			$ee_route = NULL;

			if ($route_required !== FALSE)
			{
				$required = $route_required;				}
			else
			{
				$required = 'n';
			}

			if ( ! empty($route))
			{

				try
				{
					$ee_route = new EE_Route($route, $required == 'y');
					$compiled = $ee_route->compile();
				}
				catch (Exception $error)
				{
					$error = $error->getMessage();
					$error_ids[] = $id;
					$errors[$id] = $error;
				}
			}
			else
			{
				$compiled = NULL;
				$route = NULL;
				$required = 'n';
			}

			// Check if we have a duplicate route
			if ( ! empty($ee_route))
			{
				foreach ($updated_routes as $existing_route)
				{
					if ($ee_route->equals($existing_route))
					{
						$error_ids[] = $id;
						$errors[$id] = lang('duplicate_route');
						$error = TRUE;
					}
				}

				if ($error === FALSE)
				{
					$updated_routes[] = $ee_route;
				}
			}

			if ($error === FALSE)
			{
				$data = array(
					'route' => $route,
					'route_parsed' => $compiled,
					'route_required' => $required
				);
				$this->template_model->update_template_route($id, $data);
			}
		}

		// Update Template Route order
		$route_order = json_decode($this->input->post('route_order'));
		$update = array();

		if ( ! empty($route_order))
		{
			foreach ($route_order as $index => $id)
			{
				$update[] = array('template_id' => $id, 'order' => $index);
			}


			$this->db->update_batch('template_routes', $update, 'template_id');
		}

		if (empty($errors))
		{
			$this->session->set_flashdata('message_success', lang('template_routes_saved'));
			$this->functions->redirect(cp_url('design/url_manager'));
		}
		else
		{
			$this->url_manager($_POST, $error_ids, $errors);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * url_manager
	 *
	 * @param array $input  The POST input, only used if we need to show errors
	 * @param array $error_ids  An array of template IDs for errors
	 * @param string $error_messages  An array of error messages
	 * @access public
	 * @return void
	 */
	function url_manager($input = array(), $error_ids = array(), $error_messages = "")
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ($this->config->item('enable_template_routes') == 'n')
		{
			$this->functions->redirect(cp_url('design/manager'));
		}

		$vars = array();
		$this->view->cp_page_title = lang('url_manager');
		$this->cp->set_breadcrumb(cp_url('design/manager'), lang('template_manager'));
		$this->load->library('table');

		$vars['input'] = $input;
		$vars['error_ids'] = $error_ids;
		$vars['errors'] = $error_messages;
		$vars['options'] = array(
			'n' => lang('no'),
			'y' => lang('yes')
		);

		$this->db->select(array('t.template_name', 'tg.group_name', 't.template_id', 'tr.route', 'tr.route_parsed', 'tr.route_required'));
		$this->db->from('templates AS t');
		$this->db->join('template_routes AS tr', 'tr.template_id = t.template_id', 'left');
		$this->db->join('template_groups AS tg', 'tg.group_id = t.group_id');
		$this->db->where('t.site_id', $this->config->item('site_id'));
		$this->db->order_by('tr.order, tg.group_name, t.template_name', 'ASC');
		$vars['templates'] = $this->db->get();

		$outputjs = <<<EOT
			$("#url_manager tbody td").each(function(){
        		$(this).css("width", $(this).width() +"px");
			});
			$("#url_manager tbody").sortable({
				update: function(event, ui) {
					$("#url_manager tbody > tr:odd").addClass("odd").removeClass("even");
					$("#url_manager tbody > tr:even").addClass("even").removeClass("odd");

					var order = Array();
					$("#url_manager input[type='text']").each(function(){
						order.push($(this).attr("name").replace("route_", ""));
					});

					$("#route_order").val(JSON.stringify(order));
				}
			});
EOT;

		$this->javascript->output(str_replace(array("\n", "\t"), '', $outputjs));

		$this->cp->render('design/url_manager', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Manager
	 *
	 * Template Manager
	 *
	 * @access	public
	 * @return	type
	 */
	function manager($message = '')
	{
		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('design_model');

		$this->view->cp_page_title = lang('template_manager');

		$this->load->library('table');
		$vars['can_admin_templates'] = $this->cp->allowed_group('can_admin_templates');
		$vars['can_admin_design'] = $this->cp->allowed_group('can_admin_design');

		$this->jquery->tablesorter('.templateTable', '{
			headers: {
				0: {sorter: false},
				1: {sorter: false},
				2: {sorter: false},
				3: {sorter: false},
				4: {sorter: false},
				5: {sorter: false}
			},
			widgets: ["zebra"]
		}');

		$this->cp->add_js_script('file', 'cp/manager');

		// auto scan for new templates
		if ($this->config->item('save_tmpl_files') == 'y' && $this->config->item('tmpl_file_basepath') != '')
		{
			$this->_sync_from_files();
		}

		// template group query
		// This query runs before the javascript so that we can determine
		// the first template group that will be listed
		$template_groups_query = $this->template_model->get_template_groups();
		$vars['template_groups'] = $template_groups_query->result_array();

		// if this isn't an admin, then unset any template
		// groups they aren't allowed to admin
		if ($this->session->userdata['group_id'] != 1)
		{
			foreach($vars['template_groups'] as $index => $group)
			{
				if ( ! array_key_exists($group['group_id'], $this->session->userdata['assigned_template_groups']))
				{
					unset($vars['template_groups'][$index]);
				}
			}
		}

		$this->javascript->set_global(array(
			'lang' => array('search_template' => lang('search_template')))
		);

		$vars['message'] = $message;
		$vars['default_group'] = '';

		// I'm using htmlentities here and not sanitize_search_terms from the
		// search helper on purpose.  We want <script>alert('boo');</script> to
		// show up on the page so there is feedback to *exactly* what was searched for
		// the search helper is a bit too overzealous for what is needed here.
		$vars['search_terms'] = htmlentities($this->input->post('template_keywords'));
		$vars['result_count'] = NULL;

		$this->javascript->output('
			// messages are hidden because they push the table out of the way with empty paragraphs
			// but if there is a message, we need to show it.
			if ("'.$message.'" != "")
			{
				$(".notice").show();
			}

			$("table").trigger("applyWidgets");


			EE.template_prefs_url = EE.BASE + "&C=design&M=template_prefs_ajax";

			$(".groupList ul li a").each(function(){
				var id = $(this).parent("li").attr("id");

				// enable group switching
				$(this).click(function() {

					// Populate the prefs for just that group
					EE.manager.refreshPrefs(id);


					// change appearance in side bar
					$(this).parent("li").addClass("selected").siblings("li").removeClass("selected");
					$("#" + id + "_templates").show().siblings(":not(.linkBar)").hide();

					$("table").show().trigger("applyWidgets");

					// Update the export group link
					$("div.exportTemplateGroup a#export_group").attr("href", EE.BASE+"&C=design&M=export_templates&group_id="+id);

					// do not follow any links
					return false;
				});
			});

			EE.template_edit_url = EE.BASE + "&C=design&M=template_edit_ajax";
			EE.access_edit_url = EE.BASE + "&C=design&M=access_edit_ajax";

			$(".show_prefs_link").click(function() {
				id = $(this).attr("id").replace("show_prefs_link_","");
				EE.manager.showPrefsRow(EE.pref_json[id], this);
				return false;
			});

			$(".show_access_link").click(function() {
				id = $(this).attr("id").replace("show_access_link_","");
				EE.manager.showAccessRow(id, EE.pref_json[id], this);
				return false;
			});
		');

		// reordering of template groups
		$this->javascript->output('
			$("#sortable_template_groups").sortable({
				axis: "y",
				update: function() {
					$.ajax({
						type: "POST",
						url: EE.BASE + "&C=design&M=reorder_template_groups",
						data: "is_ajax=true&XID="+ EE.XID + "&" + $("#sortable_template_groups").sortable("serialize")
					});
				}
			});
		');

		// load up the names too
		foreach ($vars['template_groups'] as $groups)
		{
			$vars['groups'][$groups['group_id']] = $groups['group_name'];

			// default group name
			if ($groups['is_site_default'] == 'y')
			{
				$vars['default_group'] = $groups['group_name'];
			}
		}

		$vars['member_groups'] = $this->_get_member_array();

		$hidden_indicator = ($this->config->item('hidden_template_indicator') != '') ? $this->config->item('hidden_template_indicator') : '_';
		$hidden_indicator_length = strlen($hidden_indicator);

		$query = $this->design_model->fetch_templates();

		if ($query->num_rows() == 0)
		{
			$vars['no_results'] = lang('no_templates_available');
			$vars['result_count'] = 0;
		}
		else
		{
			$vars['result_count'] = $query->num_rows();
		}

		// template access restrictions query
		$denied_groups = $this->design_model->template_access_restrictions();

		$vars['templates'] = array();
		$displayed_groups = array();
		$vars['no_auth_bounce_options'] = array();
		$prefs_json = array();

		$first_template = reset($vars['template_groups']);
		$vars['first_template'] = $first_template['group_id'];

		// Get template group ID so we can load the right preferences
		if ($this->input->get('tgpref', TRUE))
		{
			$vars['first_template'] = $this->input->get('tgpref', TRUE);
		}

		foreach ($query->result_array() as $row)
		{
			if ($vars['first_template'] == 0 OR $vars['first_template'] == $row['group_id'])
			{
				//  The very first group populates the default json prefs array
				foreach($vars['member_groups'] as $group_id => $group)
				{
					$access[$group_id] = array(
						'id' => $group->group_id,
						'group_name' => $group->group_title,
						'access' => isset($denied_groups[$row['template_id']][$group_id]) ? FALSE : TRUE
						);
				}

				$prefs_json[$row['template_id']] = array(
					'id' => $row['template_id'],
					'group_id' => $row['group_id'],
					'name' => $row['template_name'],
					'type' => $row['template_type'],
					'cache' => $row['cache'],
					'refresh' => $row['refresh'],
					'allow_php' => $row['allow_php'],
					'protect_javascript' => $row['protect_javascript'],
					'php_parsing' => $row['php_parse_location'],
					'hits' => $row['hits'],
					'access' => $access,
					'no_auth_bounce' => $row['no_auth_bounce'],
					'enable_http_auth' => $row['enable_http_auth'],
					'template_route' => $row['route'],
					'route_required' => $row['route_required']
				);

				$first = $row['group_id'];
			}

			$displayed_groups[$row['group_id']] = $row['group_id'];

			$vars['templates'][$row['group_id']][$row['template_id']]['hits'] = $row['hits'];
			$vars['templates'][$row['group_id']][$row['template_id']]['template_id'] = $row['template_id'];
			$vars['templates'][$row['group_id']][$row['template_id']]['group_id'] = $row['group_id'];
			$vars['templates'][$row['group_id']][$row['template_id']]['template_name'] = $row['template_name'];
			$vars['templates'][$row['group_id']][$row['template_id']]['template_type'] = $row['template_type'];
			$vars['templates'][$row['group_id']][$row['template_id']]['template_route'] = $row['route'];
			$vars['templates'][$row['group_id']][$row['template_id']]['route_required'] = $row['route_required'];
			$vars['templates'][$row['group_id']][$row['template_id']]['enable_http_auth'] = $row['enable_http_auth'];  // needed for display
			$vars['templates'][$row['group_id']][$row['template_id']]['protect_javascript'] = $row['protect_javascript'];

			$vars['templates'][$row['group_id']][$row['template_id']]['hidden'] = (strncmp($row['template_name'], $hidden_indicator, $hidden_indicator_length) == 0) ? TRUE : FALSE;

			if ($row['template_name'] == 'index')
			{
				$vars['templates'][$row['group_id']][$row['template_id']]['class'] = 'index';
			}
			elseif ($vars['templates'][$row['group_id']][$row['template_id']]['hidden'])
			{
				$vars['templates'][$row['group_id']][$row['template_id']]['class'] = 'hiddenTemplate '.$row['template_type'];
			}
			else
			{
				$vars['templates'][$row['group_id']][$row['template_id']]['class'] = $row['template_type'];
			}

			$vars['templates'][$row['group_id']][$row['template_id']]['view_path'] = $this->functions->fetch_site_index(0, 0).QUERY_MARKER.'URL='.$this->functions->fetch_site_index();
			$vars['templates'][$row['group_id']][$row['template_id']]['view_path'] = rtrim($vars['templates'][$row['group_id']][$row['template_id']]['view_path'], '/').'/';

			if (isset($vars['groups'][$row['group_id']]))
			{
				if ($vars['templates'][$row['group_id']][$row['template_id']]['template_type'] == 'css')
				{
					$vars['templates'][$row['group_id']][$row['template_id']]['view_path'] .= QUERY_MARKER.'css='.$vars['groups'][$row['group_id']].'/'.$vars['templates'][$row['group_id']][$row['template_id']]['template_name'];
				}
				else
				{
					$vars['templates'][$row['group_id']][$row['template_id']]['view_path'] .= $vars['groups'][$row['group_id']].(($vars['templates'][$row['group_id']][$row['template_id']]['template_name'] == 'index') ? '' : '/'.$vars['templates'][$row['group_id']][$row['template_id']]['template_name']);
				}
			}
		}

		$this->javascript->set_global('pref_json', $prefs_json);

		// remove any template groups that aren't being displayed, as may be the case when a search was performed
		foreach ($vars['template_groups'] as $index => $group)
		{
			if ( ! array_key_exists($group['group_id'], $displayed_groups))
			{
				unset($vars['template_groups'][$index]);
			}
		}

		$vars['no_auth_bounce_options'] = array();
		if ($this->cp->allowed_group('can_admin_design'))
		{
			$query = $this->template_model->get_templates();

			foreach ($query->result_array() as $row)
			{
				$vars['no_auth_bounce_options'][$row['template_id']] = $row['group_name'].'/'.$row['template_name'];
			}
		}

		$prefs_json = json_encode($prefs_json);

		$this->javascript->output("EE.pref_json = $prefs_json");
		$this->javascript->output('$("#template_group_'.$vars['first_template'].'").addClass("selected");');
		$this->javascript->output('$("#template_group_'.$vars['first_template'].'_templates").show();');
		$this->javascript->output(
			'$("div.exportTemplateGroup a#export_group").attr("href", EE.BASE+"&C=design&M=export_templates&group_id=template_group_'.$vars['first_template'].'");
		');

		$vars['table_template'] = array(
					'table_open'			=> '<table class="templateTable" border="0" cellspacing="0" cellpadding="0">'
		);

		if ($vars['result_count'] !== NULL)
		{
			$vars['result_count_lang'] = sprintf(
											lang('tmpl_search_result'),
											$vars['result_count'],
											count($displayed_groups)
									);

		}

		$vars['template_types'] = $this->_get_template_types();

		$this->cp->set_right_nav($this->sub_breadcrumbs);

		$this->cp->render('design/manager', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Export Template Group
	 *
	 * Export Template Group as a ZIP file
	 *
	 * @access	public
	 * @return	void
	 */
	function export_templates()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		// Load the design model
		$this->load->model('design_model');

		$this->load->library('api');
		$this->api->instantiate('template_structure');

		$templates = $this->design_model->export_tmpl_group($this->input->get_post('group_id'));

		$this->load->library('zip');
		$site_name = $this->config->item('site_short_name');

		foreach ($templates as $template)
		{
			// Get file extension for template type to construct template file name
			$tmpl_ext = $this->api_template_structure->file_extensions($template['template_type']);

			$template_name = $site_name.'/'.$template['group_name'].'.group'.'/'.$template['template_name'].$tmpl_ext;

			$this->zip->add_data($template_name, $template['template_data']);
		}

		if ($this->input->get_post('group_id'))
		{
			$this->zip->download($site_name.'_'.$template['group_name'].'.zip');
		}
		else
		{
			$this->zip->download($site_name.'.zip');
		}

		$this->zip->clear_data();

		exit();
	}


	// --------------------------------------------------------------------

	/**
	 * Template Prefs Ajax
	 *
	 * Used for inline editing of template prefs
	 *
	 * @access	public
	 * @return	type
	 */
	function template_prefs_ajax()
	{
		$template_group = $this->input->get_post("group_id");
		$template_group = str_replace('template_group_', '', $template_group);

		$this->load->model('design_model');

		$query = $this->design_model->fetch_templates($template_group);

		if ($query->num_rows() == 0)
		{
			return array();
		}

		$member_groups = $this->_get_member_array();

		// template access restrictions query
		$denied_groups = $this->design_model->template_access_restrictions();

		$vars['templates'] = array();
		$displayed_groups = array();
		$template_prefs = array();

		foreach ($query->result_array() as $row)
		{
			// Access

			foreach($member_groups as $group_id => $group)
			{
				$access[$group_id] = array(
					'id' => $group->group_id,
					'group_name' => $group->group_title,
					'access' => isset($denied_groups[$row['template_id']][$group_id]) ? FALSE : TRUE
				);
			}

			$prefs_json[$row['template_id']] = array(
				'id' => $row['template_id'],
				'group_id' => $row['group_id'],
				'name' => $row['template_name'],
				'type' => $row['template_type'],
				'cache' => $row['cache'],
				'refresh' => $row['refresh'],
				'allow_php' => $row['allow_php'],
				'php_parsing' => $row['php_parse_location'],
				'protect_javascript' => $row['protect_javascript'],
				'hits' => $row['hits'],
				'access' => $access,
				'no_auth_bounce' => $row['no_auth_bounce'],
				'enable_http_auth' => $row['enable_http_auth'],
				'template_route' => $row['route'],
				'route_required' => $row['route_required']
			);
		}

		$this->output->send_ajax_response($prefs_json);
	}

	private function _get_member_array()
	{
		$member_groups = array();

		// member group query
		$this->db->select('group_id, group_title');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('group_id !=', '1');
		$this->db->order_by('group_title');
		$m_groups = $this->db->get('member_groups');

		$member_groups = array();

		foreach($m_groups->result() as $m_group)
		{
			$member_groups[$m_group->group_id] = $m_group;
		}

		return $member_groups;
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Template Ajax
	 *
	 * Used for inline editing of template prefs
	 *
	 * @access	public
	 * @return	type
	 */
	function template_edit_ajax()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			$this->output->send_ajax_response(lang('unauthorized_access'), TRUE);
		}

		$template_id = $this->input->get_post('template_id');

		// check access privs
		if ( ! $this->_template_access_privs(array('template_id' => $template_id)))
		{
			$this->output->send_ajax_response(lang('unauthorized_access'), TRUE);
		}

		// Do we just have alpha num + . ?
		if ($this->input->get_post('template_name'))
		{
			if ( ! preg_match("#^[a-zA-Z0-9_\.-]+$#i", $this->input->get_post('template_name')))
			{
				$this->output->send_ajax_response(lang('illegal_characters'), TRUE);
			}
		}

		$this->output->enable_profiler(FALSE);

		$data = array(
						'template_name' 		=> $this->input->get_post('template_name'),
						'template_type' 		=> ($this->input->get_post('template_type') == '') ? 'webpage' : $this->input->get_post('template_type'),
						'cache' 				=> ($this->input->get_post('cache') == 'y') ? 'y' : 'n',
						'refresh' 				=> ($this->input->get_post('refresh') == '') ? 0 : $this->input->get_post('refresh'),
						'allow_php' 			=> ($this->input->get_post('allow_php') == 'y') ? 'y' : 'n',
						'protect_javascript'	=> ($this->input->get_post('protect_javascript') == 'y') ? 'y' : 'n',
						'php_parse_location' 	=> ($this->input->get_post('php_parse_location') == 'i') ? 'i' : 'o',
						'hits'					=> $this->input->get_post('hits')
		);

		$this->db->select('template_name, template_type, save_template_file, group_name, templates.group_id');
		$this->db->join('template_groups', 'template_groups.group_id = templates.group_id');
		$this->db->where('template_id', $template_id);
		$this->db->where('templates.site_id', $this->config->item('site_id'));
		$query = $this->db->get('templates');

		$template_info = $query->row_array();

		// safety
		if (count($template_info) == 0)
		{
			$this->output->send_ajax_response(lang('unauthorized_access'), TRUE);
		}

		$rename_file = FALSE;

		if ($data['template_name'] != $template_info['template_name'])
		{
			if ($template_info['template_name'] == 'index')
			{
				$this->output->send_ajax_response(lang('index_delete_disallowed'), TRUE);
			}

			$this->db->where('group_id', $template_info['group_id']);
			$this->db->where('template_name', $data['template_name']);

			// unique?
			if ($this->db->count_all_results('templates'))
			{
				$this->output->send_ajax_response(lang('template_name_taken'), TRUE);
			}

			// reserved?
			if (in_array($data['template_name'], $this->reserved_names))
			{
				$this->output->send_ajax_response(lang('reserved_name'), TRUE);
			}

			if ($template_info['save_template_file'] == 'y')
			{
				$rename_file = TRUE;
			}
		}

		$trigger_preference_notice = FALSE;

		// Update the template size?
		if (is_numeric($this->input->get_post('template_size')))
		{
			if ($this->session->userdata['template_size'] != $this->input->get_post('template_size'))
			{
				$this->load->model('member_model');
				$this->member_model->update_member($this->session->userdata('member_id'), array('template_size'=>$this->input->get_post('template_size')));
				$this->session->userdata['template_size'] = $this->input->get_post('template_size');
				$trigger_preference_notice = TRUE;
			}
		}

		if ($this->template_model->update_template_ajax($template_id, $data) OR $trigger_preference_notice)
		{
			if ($rename_file === TRUE)
			{
				if ($this->template_model->rename_template_file($template_info['group_name'], $template_info['template_type'], $template_info['template_name'], $data['template_name']) == FALSE)
				{
					$this->output->send_ajax_response(lang('template_file_not_renamed'), TRUE);
				}
			}

			$this->output->send_ajax_response(lang('preferences_updated'));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Access Template Ajax
	 *
	 * Used for inline editing of template access prefs
	 *
	 * @access	public
	 * @return	type
	 */
	function access_edit_ajax()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			$this->output->send_ajax_response(lang('unauthorized_access'), TRUE);
		}

		ee()->output->enable_profiler(FALSE);
		ee()->load->helper('array_helper');

		if ( ! IS_CORE)
		{
			ee()->load->library('template_router');
		}

		$payload = ee()->input->post('payload');

		// We may be changing permissions for multiple member groups at a time
		// if they selected a Select All option
		foreach ($payload as $group)
		{
			$template_id = $group['template_id'];
			$query = $this->template_model->get_template_info($template_id);

			if ( ! $this->_template_access_privs(array('template_id' => $template_id)))
			{
				$this->output->send_ajax_response(lang('unauthorized_access'), TRUE);
			}

			if ($member_group = element('member_group_id', $group))
			{
				$new_status = element('new_status', $group);
				$no_auth_bounce = element('no_auth_bounce', $group);

				if (($new_status != 'y' && $new_status != 'n') OR ! ctype_digit($no_auth_bounce))
				{
					$this->output->send_ajax_response(lang('unauthorized_access'), TRUE);
				}

				$this->template_model->update_access_ajax($template_id, $member_group, $new_status);
				$this->template_model->update_template_ajax($template_id, array('no_auth_bounce' => $no_auth_bounce));
			}
			elseif ($enable_http_auth = element('enable_http_auth', $group))
			{
				if ($enable_http_auth != 'y' && $enable_http_auth != 'n')
				{
					$this->output->send_ajax_response(lang('unauthorized_access'), TRUE);
				}

				$this->template_model->update_template_ajax($template_id, array('enable_http_auth' => $enable_http_auth));
			}
			elseif ($no_auth_bounce = element('no_auth_bounce', $group))
			{
				if ( ! ctype_digit($no_auth_bounce))
				{
					$this->output->send_ajax_response(lang('unauthorized_access'), TRUE);
				}

				$this->template_model->update_template_ajax($template_id, array('no_auth_bounce' => $no_auth_bounce));
			}
			elseif ($required = element('route_required', $group))
			{
				if ($required != 'y' && $required != 'n')
				{
					$this->output->send_ajax_response(lang('unauthorized_access'), TRUE);
				}

				$this->template_model->update_template_route($template_id, array('route_required' => $required));

				// We have to recompile the route when route_required changes
				$route = $query->row('route');

				try
				{
					$template_route = ee()->template_router->create_route($route, $required == 'y');
				}
				catch (Exception $error)
				{
					$this->output->send_ajax_response($error->getMessage(), TRUE);
				}

				$this->template_model->update_template_route($template_id, array('route_parsed' => $template_route->compile()));
			}
			elseif (isset($group['template_route']))
			{
				$route = $group['template_route'];

				// Must check whether route segments are required before compiling route
				if ($required = element('route_required', $group))
				{
					$required = ($required == 'y');
				}
				else
				{
					$required = ($query->row('route_required') == 'y');
				}

				if($route !== "")
				{
					try
					{
						$template_route = ee()->template_router->create_route($route, $required);
						$route_parsed = $template_route->compile();
					}
					catch (Exception $error)
					{
						$this->output->send_ajax_response($error->getMessage(), TRUE);
					}

					$this->load->model('design_model');
					$templates = $this->design_model->fetch_templates();

					foreach ($templates->result() as $row)
					{
						if( ! empty($row->route) && $row->template_id != $template_id)
						{
							$existing_route = new EE_Route($row->route);

							if($template_route->equals($existing_route))
							{
								$this->output->send_ajax_response(lang('duplicate_route'), TRUE);
							}
						}
					}
				}
				else
				{
					$route = NULL;
					$route_parsed = NULL;
				}

				$this->template_model->update_template_route($template_id, array('route_parsed' => $route_parsed));
				$this->template_model->update_template_route($template_id, array('route' => $route));
			}
			else
			{
				$this->output->send_ajax_response(lang('unauthorized_access'), TRUE);
			}
		}

		$this->output->send_ajax_response(lang('preferences_updated'));
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Template Group
	 *
	 * Edit a template group
	 *
	 * @access	public
	 * @return	type
	 */
	function edit_template_group()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$group_id = $this->input->get_post("group_id");

		if ($group_id == '')
		{
			return $this->template_group_pick(TRUE);
	//		$this->manager();
		}

		$this->load->library('form_validation');
		$this->load->library('table');

		$group_info = $this->template_model->get_group_info($group_id);
		$vars['group_name'] = $group_info->row('group_name');
		$vars['is_default'] = ($group_info->row('is_site_default') == 'y') ? TRUE : FALSE;

		$this->form_validation->set_rules('group_name',	'lang:group_name',	'required|callback__group_name_checks');
		$this->form_validation->set_rules('is_site_default', 'lang:is_site_default', '');
		$this->form_validation->set_error_delimiters('<br /><span class="notice">', '</span>');

		if ($this->form_validation->run() === FALSE)
		{
			$this->view->cp_page_title = lang('edit_template_group_form');
			$this->cp->set_breadcrumb(cp_url('design/manager', 'tgpref='.$group_id), lang('template_manager'));

			$vars['form_hidden'] = array(
				'group_id'		=> $group_id,
				'old_name'		=> $vars['group_name']
			);

			$this->cp->render('design/edit_template_group', $vars);
		}
		else
		{
			$this->update_template_group();
		}

	}

	// --------------------------------------------------------------------

	/**
	  *	 Check Template Group Name
	  */
	function _group_name_checks($str)
	{
		if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i", $str))
		{
			$this->lang->loadfile('admin');
			$this->form_validation->set_message('_group_name_checks', lang('illegal_characters'));
			return FALSE;
		}

		if (in_array($str, $this->reserved_names))
		{
			$this->form_validation->set_message('_group_name_checks', lang('reserved_name'));
			return FALSE;
		}

		$this->db->select('count(*) as count');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('group_name', $str);
		$query = $this->db->get('template_groups');

		if ((strtolower($this->input->post('old_name')) != strtolower($str)) AND $query->row('count')  > 0)
		{
			$this->form_validation->set_message('_group_name_checks', lang('template_group_taken'));
			return FALSE;
		}
		elseif ($query->row('count')  > 1)
		{
			$this->form_validation->set_message('_group_name_checks', lang('template_group_taken'));
			return FALSE;
		}

		return TRUE;
	}

	/**
	  *	 Create/Update Template Group
	  */
	function update_template_group()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('template_model');

		$group_id = $this->input->get_post('group_id');
		$group_name = $this->input->post('group_name');

		$is_site_default = ($this->input->post('is_site_default') == 'y' ) ? 'y' : 'n';

		if ($is_site_default == 'y')
		{
			$this->db->where('group_id !=', $group_id);
			$this->db->where('site_id', $this->config->item('site_id'));
			$this->db->set('is_site_default', 'n');
			$this->db->update('exp_template_groups');
		}

		if ( ! $group_id)
		{
			$data = array(
					 'group_name'	  => $group_name,
					 'is_site_default' => $is_site_default,
					 'site_id'			=> $this->config->item('site_id')
						);

			$group_id = $this->template_model->create_group($data);

			$duplicate = FALSE;

			if (is_numeric($_POST['duplicate_group']))
			{
				$query = $this->db->query("SELECT template_name, save_template_file, template_data, template_type, template_notes, cache, refresh, no_auth_bounce, allow_php, php_parse_location, protect_javascript FROM exp_templates WHERE group_id = '".$this->db->escape_str($_POST['duplicate_group'])."'");

				if ($query->num_rows() > 0)
				{
					$duplicate = TRUE;
				}
			}

			if ( ! $duplicate)
			{
				$data = array(
								'group_id'	  => $group_id,
								'template_name' => 'index',
								'template_data' => '',
								'last_author_id' => 0,
								'edit_date'		=> $this->localize->now,
								'site_id'		=> $this->config->item('site_id')
								);

				$this->template_model->create_template($data);
			}
			else
			{
				foreach ($query->result_array() as $row)
				{
					$data = array(
									'group_id'				=> $group_id,
									'template_name'			=> $row['template_name'],
									'save_template_file'	=> $row['save_template_file'],
									'template_notes'		=> $row['template_notes'],
									'cache'					=> $row['cache'],
									'refresh'				=> $row['refresh'],
									'no_auth_bounce'		=> $row['no_auth_bounce'],
									'php_parse_location'	=> $row['php_parse_location'],
									'protect_javascript'	=> $row['protect_javascript'],
									'allow_php'				=> ($this->session->userdata['group_id'] == 1) ? $row['allow_php'] : 'n',
									'template_type'			=> $row['template_type'],
									'template_data'			=> $row['template_data'],
									'edit_date'				=> $this->localize->now,
									'last_author_id' 		=> 0,
									'site_id'				=> $this->config->item('site_id')
								 );

					$this->template_model->create_template($data);
				}
			}

			$this->session->set_flashdata('message_success', lang('template_group_created'));
		}
		else
		{
			// If the group name changed, check for templates saved as files
			$old_name = $this->input->post('old_name');

			if ($old_name != FALSE && $old_name != $group_name && $this->config->item('save_tmpl_files') == 'y')
			{
				$basepath = $this->config->slash_item('tmpl_file_basepath').'/'.$this->config->item('site_short_name').'/';
				$old_dir = $basepath.$old_name.'.group/';
				$new_dir = $basepath.$group_name.'.group/';

				if (is_dir($old_dir) === TRUE && is_dir($new_dir) === FALSE)
				{
					rename($old_dir, $new_dir);
				}
			}

			$fields = array(
						'group_name' => $group_name,
						'is_site_default' => $is_site_default,
						'group_id'	=> $group_id,
						'site_id'		=> $this->config->item('site_id')
					  );

			$this->template_model->update_template_group($group_id, $fields);

			$this->session->set_flashdata('message_success', lang('template_group_updated'));

		}

		$this->functions->redirect(cp_url('design/manager', 'tgpref='.$group_id));
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Template Group Order
	 *
	 * Create a new template gropu
	 *
	 * @access	public
	 * @return	type
	 */
	function edit_template_group_order($message = '')
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->view->cp_page_title = lang('edit_template_group_order');

		$this->javascript->output('
			$("form label").css("cursor", "move");
			$("form").sortable({
				tolerance: "intersect",
				items: "p",
				axis: "y",
				stop: function(event, ui) {
					$("form p input[type=text]").each(function(i) {
						$(this).val(i+1);
					});
				}
			});
		');


		$vars['form_hidden'] = array();

		$vars['template_groups'] = $this->template_model->get_template_groups();

		$this->cp->render('design/edit_template_group_order', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Reorder Template Groups
	 *
	 * Called over Ajax, where it re-orders the template groups
	 * behind the scenes
	 *
	 * @access	public
	 * @return	void
	 */
	function reorder_template_groups()
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$template_groups = $this->input->post('template_group');

		if ($this->input->get_post('is_ajax') == 'true')
		{
			// Ajax request, no need to send them anywhere

			foreach ($template_groups as $order=>$group)
			{
				$this->template_model->update_template_group($group, array('group_order' => $order));
			}

			return TRUE;
		}
		else
		{
			// "old fashioned" request, show them the template order page

			$auto_order = 1;

			foreach ($template_groups as $group=>$order)
			{
				$order = ( ! is_numeric($order)) ? $auto_order++ : $order;

				$this->template_model->update_template_group($group, array('group_order' => $order));
			}

			$this->functions->redirect(cp_url('design/edit_template_group_order'));
		}
	}

	/**
	 * Sync from files confirmation
	 *
	 * Confirm updating the database to match template files
	 *
	 * @access	public
	 * @return	void
	 */
	function sync_templates($message = '')
	{
		if ( ! $this->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');

		if ($this->config->item('save_tmpl_files') != 'y' OR $this->config->item('tmpl_file_basepath') == '')
		{
			$message = lang('sync_not_allowed_1');
			$message .= '<a href="'.str_replace('&amp;', '&', BASE).'&C=design&M=global_template_preferences">'.lang('sync_not_allowed_2').'</a>';
		}

		$vars['table_template'] = array('table_open' => '<table id="entries" class="templateTable" border="0" cellspacing="0" cellpadding="0">',
					'row_start'           => '<tr class="odd">',
					'row_end'             => '</tr>',
					'cell_start'          => '<td>',
					'cell_end'            => '</td>',

					'row_alt_start'       => '<tr>',
					'row_alt_end'         => '</tr>',
					'cell_alt_start'      => '<td>',
					'cell_alt_end'        => '</td>',


		);

		if ( ! $this->cp->allowed_group('can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		// Add in new files
		$this->_sync_from_files();

		$vars['cp_page_title'] = lang('sync_templates');
		$this->cp->set_breadcrumb(cp_url('design/manager'), lang('template_manager'));

		$this->javascript->output(array(
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
				);')
		);

		$vars['templates'] = array();
		$vars['form_hidden']['confirm'] = 'confirm';

		$this->load->library('api');
		$this->api->instantiate('template_structure');
		$this->load->helper('file');

		$this->db->select(array('group_name', 'templates.group_id', 'template_name', 'template_type', 'template_id', 'edit_date'));
		$this->db->join('template_groups', 'template_groups.group_id = templates.group_id');
		$this->db->where('templates.site_id', $this->config->item('site_id'));
		$this->db->where('save_template_file', 'y');
		$this->db->order_by('group_name, template_name', 'ASC');
		$query = $this->db->get('templates');

		$existing = array();
		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				// Skip groups they do not have access to
				if ( ! $this->_template_access_privs(array('group_id' => $row->group_id)))
				{
					continue;
				}

				$edit_date = $this->localize->human_time($row->edit_date);

				$existing[$row->group_name][$row->template_name.$this->api_template_structure->file_extensions($row->template_type)] =
				 array('template_id' => $row->template_id,
				'edit_date' => $edit_date,
				'raw_edit_date' => $row->edit_date,
				'template_name' => $row->template_name,
				'file_name' => $row->template_name.$this->api_template_structure->file_extensions($row->template_type),
				'type' => $row->template_type,
				'file_edit' => '',
				'file_synced' => FALSE,
				'file_exists' => FALSE,
				'toggle' => form_checkbox('toggle[]', 'cf-'.$row->template_id, '', ' class="toggle" id="sync_box_'.$row->template_id.'"'));
			}
		}

		$basepath = $this->config->slash_item('tmpl_file_basepath');
		$basepath .= '/'.$this->config->item('site_short_name');
		$this->load->helper('directory');
		$files = directory_map($basepath, 0, 1);

		if ($files !== FALSE)
		{
			foreach ($files as $group => $templates)
			{
				if (substr($group, -6) != '.group')
				{
					continue;
				}

				$group_name = substr($group, 0, -6); // remove .group

				// DB column limits template and group name to 50 characters
				if (strlen($group_name) > 50)
				{
					continue;
				}

				foreach ($templates as $template)
				{
					if (is_array($template))
					{
						continue;
					}

					if (strlen($template) > 50)
					{
						continue;
					}

					$file_date = get_file_info($basepath.'/'.$group.'/'.$template);
					$file_date = ($file_date === FALSE) ? $file_date : $file_date['date'];

					if (isset($existing[$group_name][$template]))
					{
						$existing[$group_name][$template]['file_exists'] = TRUE;
						if ($existing[$group_name][$template]['raw_edit_date'] >= $file_date)
						{
							$existing[$group_name][$template]['file_synced'] = TRUE;
							$existing[$group_name][$template]['toggle'] = '';
						}
						$existing[$group_name][$template]['file_edit'] = $this->localize->human_time($file_date);
						$existing[$group_name][$template]['file_name'] = $template;
						$existing[$group_name][$template]['toggle'] = form_checkbox('toggle[]', $existing[$group_name][$template]['template_id'], '', ' class="toggle" id="sync_box_'.$existing[$group_name][$template]['template_id'].'"');
					}
				}
			}
		}

		if ($message == '' && count($existing) == 0)
		{
			$message = lang('no_valid_templates_sync');
		}

		$vars['message'] = $message;
		$vars['templates'] = $existing;

		$this->cp->render('design/sync_confirm', $vars);
	}

	/**
	 * Sync data from files
	 *
	 * Update database to match current template files
	 *
	 * @access	public
	 * @return	void
	 */
	function sync_run()
	{
		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$message = '';

		if ($this->config->item('save_tmpl_files') != 'y' OR $this->config->item('tmpl_file_basepath') == '')
		{
			$this->functions->redirect(cp_url('design/sync_templates'));
		}

		if ( ! $this->cp->allowed_group('can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $confirmed = $this->input->get_post('confirm') OR $confirmed != 'confirm')
		{
			$this->functions->redirect(cp_url('design/sync_templates'));
		}

		if ( ! $this->input->post('toggle') OR ! is_array($this->input->post('toggle')))
		{
			$this->functions->redirect(cp_url('design/sync_templates'));
		}

		$damned = array();
		$create_files = array();

		foreach ($_POST['toggle'] as $key => $val)
		{
			if (strncmp($val, 'cf-', 3) == 0)
			{
				$create_files[] = substr($val, 3);
				$damned[] = substr($val, 3);
			}
			else
			{
				$damned[] = $val;
			}
		}

		$save_result = FALSE;

		// If we need to create files, we do it now.
		if (count($create_files) > 0)
		{
			$this->db->select(array('group_name', 'template_name', 'template_type', 'template_id', 'edit_date', 'template_data'));
			$this->db->join('template_groups', 'template_groups.group_id = templates.group_id');
			$this->db->where('templates.site_id', $this->config->item('site_id'));
			$this->db->where('save_template_file', 'y');
			$this->db->where_in('template_id', $create_files);
			$this->db->order_by('group_name, template_name', 'ASC');
			$query = $this->db->get('templates');

			if ($query->num_rows() > 0)
			{
				foreach ($query->result() as $row)
				{
					$tdata = array(
								'site_short_name'	=> $this->config->item('site_short_name'),
								'template_id'		=> $row->template_id,
								'template_group'	=> $row->group_name,
								'template_name'		=> $row->template_name,
								'template_type'		=> $row->template_type,
								'template_data'		=> $row->template_data,
								'edit_date'			=> $this->localize->now,
								'last_author_id'	=> $this->session->userdata['member_id']
							);

					$save_result = $this->update_template_file($tdata);

					if ($save_result == FALSE)
					{
						show_error(lang('template_not_saved'));
					}
				}
			}

			//  Annoying.  This would cut down on overhead and eliminate need to include these in the following processing.
			//  UPDATE exp_templates SET edit_date = $this->localize->now WHERE template_id IN ($create_files)
		}

		$this->load->library('api');
		$this->api->instantiate('template_structure');
		$this->load->helper('file');

		$this->db->select(array('group_name', 'templates.group_id', 'template_name', 'template_type', 'template_id', 'edit_date'));
		$this->db->join('template_groups', 'template_groups.group_id = templates.group_id');
		$this->db->where('templates.site_id', $this->config->item('site_id'));
		$this->db->where('save_template_file', 'y');
		$this->db->where_in('template_id', $damned);
		$this->db->order_by('group_name, template_name', 'ASC');
		$query = $this->db->get('templates');

		$existing = array();
		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				// Skip groups they do not have access to
				if ( ! $this->_template_access_privs(array('group_id' => $row->group_id)))
				{
					continue;
				}

				$existing[$row->group_name.'.group'][$row->template_name.$this->api_template_structure->file_extensions($row->template_type)] =
					array($row->group_id,
						$row->template_id,
						$row->edit_date,
						$row->template_name,
						$row->template_type
					);
			}
		}

		$query->free_result();

		$basepath = $this->config->slash_item('tmpl_file_basepath');
		$basepath .= '/'.$this->config->item('site_short_name');
		$this->load->helper('directory');
		$files = directory_map($basepath, 0, 1);

		$save_revisions = $this->config->item('save_tmpl_revisions');
		$maxrev = $this->config->item('max_tmpl_revisions');

		if ($files !== FALSE)
		{
			foreach ($files as $group => $templates)
			{
				if (substr($group, -6) != '.group')
				{
					continue;
				}

				$group_name = substr($group, 0, -6); // remove .group

				// update existing templates
				foreach ($templates as $template)
				{
					if (is_array($template))
					{
						continue;
					}

					if ( isset($existing[$group][$template]))
					{
						$edit_date = $existing[$group][$template]['2'];
						$file_date = get_file_info($basepath.'/'.$group.'/'.$template);

						if (($file_date !== FALSE) && ($file_date['date'] < $edit_date))
						{
							continue;
						}

						$contents = file_get_contents($basepath.'/'.$group.'/'.$template);

						if ($contents !== FALSE)
						{
							$data = array(
								'group_id'				=> $existing[$group][$template]['0'],
								'template_name'			=> $existing[$group][$template]['3'],
								'template_type'			=> $existing[$group][$template]['4'],
								'template_data'			=> $contents,
								'edit_date'				=> $this->localize->now,
								'save_template_file'	=> 'y',
								'last_author_id'		=> $this->session->userdata['member_id'],
								'site_id'				=> $this->config->item('site_id')
								);

							$this->db->where('template_id', $existing[$group][$template]['1']);
							$this->db->update('templates', $data);

							// Revision tracking
							if ($save_revisions == 'y')
							{
								$data = array(
									'item_id'			=> $existing[$group][$template]['1'],
									'item_table'		=> 'exp_templates',
									'item_field'		=> 'template_data',
									'item_data'			=> $contents,
									'item_date'			=> $this->localize->now,
									'item_author_id'	=> $this->session->userdata['member_id']
									);

								$this->db->insert('revision_tracker', $data);

								// Cull revisions
								if ($maxrev != '' AND is_numeric($maxrev) AND $maxrev > 0)
								{
									$this->db->select('tracker_id');
									$this->db->where('item_id', $existing[$group][$template]['1']);
									$this->db->where('item_table', 'exp_templates');
									$this->db->where('item_field', 'template_data');
									$this->db->order_by("tracker_id", "desc");
									$res = $this->db->get('revision_tracker');


									if ($res->num_rows() > 0  AND $res->num_rows() > $maxrev)
									{
										$flag = '';
										$ct = 1;

										foreach ($res->result_array() as $row)
										{
											if ($ct >= $maxrev)
											{
												$flag = $row['tracker_id'];
												break;
											}

											$ct++;
										}

										if ($flag != '')
										{
											$this->db->where('tracker_id <', $flag);
											$this->db->where('item_id', $existing[$group][$template]['1']);
											$this->db->where('item_table', 'exp_templates');
											$this->db->where('item_field', 'template_data');
											$this->db->delete('revision_tracker');
										}
									}
								}
							}
						}

						unset($existing[$group][$template]);
					}
				}
			}
		}

		$this->functions->clear_caching('all');
		$message = lang('sync_completed');

		$this->session->set_flashdata('message_success', $message);
		$this->functions->redirect(cp_url('design/sync_templates'));
	}

	/**
	 * Sync from files
	 *
	 * Reads the template file directory and
	 * automatically creates new groups and templates as necessary
	 *
	 * @access	public
	 * @return	void
	 */
	function _sync_from_files()
	{
		if ($this->config->item('save_tmpl_files') != 'y' OR $this->config->item('tmpl_file_basepath') == '')
		{
			return FALSE;
		}

		$this->load->library('api');
		$this->api->instantiate('template_structure');

		$this->db->select(array('group_name', 'template_name', 'template_type', 'save_template_file'));
		$this->db->join('template_groups', 'template_groups.group_id = templates.group_id');
		$this->db->where('templates.site_id', $this->config->item('site_id'));
		$this->db->order_by('group_name, template_name', 'ASC');
		$query = $this->db->get('templates');

		$existing = array();
		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$existing[$row->group_name.'.group'][$row->template_name] = $row->save_template_file;
			}
		}

		$basepath = $this->config->slash_item('tmpl_file_basepath');
		$basepath .= '/'.$this->config->item('site_short_name');
		$this->load->helper('directory');
		$files = directory_map($basepath, 0, 1);

		if ($files !== FALSE)
		{
			foreach ($files as $group => $templates)
			{
				if (substr($group, -6) != '.group')
				{
					continue;
				}

				$group_name = substr($group, 0, -6); // remove .group

				// DB column limits template and group name to 50 characters
				if (strlen($group_name) > 50)
				{
					continue;
				}

				$group_id = '';

				if ( ! preg_match("#^[a-zA-Z0-9_\-]+$#i", $group_name))
				{
					continue;
				}

				// if the template group doesn't exist, make it!
				if ( ! isset($existing[$group]))
				{
					if ( ! $this->api->is_url_safe($group_name))
					{
						continue;
					}

					if (in_array($group_name, $this->reserved_names))
					{
						continue;
					}

					$data = array(
									'group_name'		=> $group_name,
									'is_site_default'	=> 'n',
									'site_id'			=> $this->config->item('site_id')
								);

					$group_id = $this->template_model->create_group($data);

				}

				// Grab group_id if we still don't have it.
				if ($group_id == '')
				{
					$this->db->select('group_id');
					$this->db->where('group_name', $group_name);
					$this->db->where('site_id', $this->config->item('site_id'));
					$query = $this->db->get('template_groups');

					$group_id = $query->row('group_id');
				}

				// if the templates don't exist, make 'em!
				foreach ($templates as $template)
				{
					// Skip subdirectories (such as those created by svn)
					if (is_array($template))
					{
						continue;
					}

					// Skip hidden ._ files
					if (substr($template, 0, 2) == '._')
					{
						continue;
					}

					// If the last occurance is the first position?  We skip that too.
					if (strrpos($template, '.') == FALSE)
					{
						continue;
					}

					$ext = strtolower(ltrim(strrchr($template, '.'), '.'));

					if ( ! in_array('.'.$ext, $this->api_template_structure->file_extensions))
					{
						continue;
					}

					$ext_length = strlen($ext)+1;
					$template_name = substr($template, 0, -$ext_length);

					$template_type = array_search('.'.$ext, $this->api_template_structure->file_extensions);

					if (isset($existing[$group][$template_name]))
					{
						continue;
					}

					if ( ! $this->api->is_url_safe($template_name))
					{
						continue;
					}

					if (strlen($template_name) > 50)
					{
						continue;
					}


					$data = array(
									'group_id'				=> $group_id,
									'template_name'			=> $template_name,
									'template_type'			=> $template_type,
									'template_data'			=> file_get_contents($basepath.'/'.$group.'/'.$template),
									'edit_date'				=> $this->localize->now,
									'save_template_file'	=> 'y',
									'last_author_id'		=> $this->session->userdata['member_id'],
									'site_id'				=> $this->config->item('site_id')
								 );

					// do it!
					$this->template_model->create_template($data);

					// add to existing array so we don't try to create this template again
					$existing[$group][$template_name] = 'y';
				}

				// An index template is required- so we create it if necessary

				if ( ! isset($existing[$group]['index']))
				{
					$data = array(
									'group_id'				=> $group_id,
									'template_name'			=> 'index',
									'template_data'			=> '',
									'edit_date'				=> $this->localize->now,
									'save_template_file'	=> 'y',
									'last_author_id'		=> $this->session->userdata['member_id'],
									'site_id'				=> $this->config->item('site_id')
								 );

					$this->template_model->create_template($data);
				}

				unset($existing[$group]);
			}
		}
	}

	/**
	 * Get template types
	 *
	 * Returns a list of the standard EE template types to be used in
	 * template type selection dropdowns, optionally merged with
	 * user-defined template types via the template_types hook.
	 *
	 * @access private
	 * @return array Array of available template types
	 */
	function _get_template_types()
	{
		$template_types = array(
			'webpage'	=> lang('webpage'),
			'feed'		=> lang('rss'),
			'css'		=> lang('css_stylesheet'),
			'js'		=> lang('js'),
			'static'	=> lang('static'),
			'xml'		=> lang('xml')
		);

		// -------------------------------------------
		// 'template_types' hook.
		//  - Provide information for custom template types.
		//
		$custom_templates = $this->extensions->call('template_types', array());
		//
		// -------------------------------------------

		if ($custom_templates != NULL)
		{
			// Instead of just merging the arrays, we need to get the
			// template_name value out of the associative array for
			// easy use of the form_dropdown helper
			foreach ($custom_templates as $key => $value)
			{
				$template_types[$key] = $value['template_name'];
			}
		}

		return $template_types;
	}
}

/* End of file design.php */
/* Location: ./system/expressionengine/controllers/cp/design.php */
