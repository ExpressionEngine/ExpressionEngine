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
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Tools_data extends CP_Controller {

	var $sub_breadcrumbs = array();

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->model('tools_model');
		$this->lang->loadfile('tools_data');

		$this->sub_breadcrumbs['sql_view_database'] = BASE.AMP.'C=tools_data'.AMP.'M=sql_view_database';
		
		// Only show Database Query Form link for Super Admins
		if ($this->session->userdata('group_id') == '1')
		{
			$this->sub_breadcrumbs['sql_query_form'] = BASE.AMP.'C=tools_data'.AMP.'M=sql_query_form';
		}
		
		$this->sub_breadcrumbs = array_merge($this->sub_breadcrumbs,
			array(
				'sql_status'			=> BASE.AMP.'C=tools_data'.AMP.'M=sql_status',
				'sql_system_vars'		=> BASE.AMP.'C=tools_data'.AMP.'M=sql_system_vars',
				'sql_processlist'		=> BASE.AMP.'C=tools_data'.AMP.'M=sql_processlist'
			)
		);
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
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->cp->set_breadcrumb(BASE.AMP.'C=tools', lang('tools'));

		$this->view->cp_page_title = lang('tools_data');
		$this->view->controller = 'tools/tools_data';

		$this->cp->render('_shared/overview');
	}

	// --------------------------------------------------------------------

	/**
	 * Clear Caching
	 *
	 * Processes the clear cache submitted data
	 *
	 * @access	public
	 * @return	mixed
	 */
	function clear_caching()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		$vars['cleared'] = FALSE;

		if (isset($_POST['type']))
		{
			$this->functions->clear_caching($_POST['type'], '');
			$this->session->set_flashdata('message_success', lang('cache_deleted'));
			$this->functions->redirect(BASE.AMP.'C=tools_data'.AMP.'M=clear_caching');
		}

		$this->view->cp_page_title = lang('clear_caching');

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_data'=> lang('tools_data')
		);

		$this->cp->render('tools/clear_caching', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * SQL Manager
	 *
	 * The default page when the SQL Manager is accessed without an action
	 *
	 * @access	public
	 * @return	mixed
	 */
	function sql_manager()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		$sql_info = $this->tools_model->get_sql_info();

		$this->view->cp_page_title = lang('sql_manager');
		
		$this->cp->set_right_nav($this->sub_breadcrumbs);

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_data'=> lang('tools_data')
		);

		$this->load->library('table');

		$this->jquery->tablesorter('.mainTable', '{
			widgets: ["zebra"]
		}');

		$this->cp->render('tools/sql_manager', array('sql_info' => $sql_info));
	}

	// --------------------------------------------------------------------

	/**
	 * SQL Query Form
	 *
	 * Allows one to run queries from the control panel
	 *
	 * @access	public
	 * @return	void
	 */
	function sql_query_form()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}
		
		// Super Admins only, please
		if ($this->session->userdata('group_id') != '1')
		{
			show_error(lang('unauthorized_access'));
		}

		$this->view->cp_page_title = lang('sql_query_form');

		$this->cp->set_right_nav($this->sub_breadcrumbs);

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_data'=> lang('tools_data'),
			BASE.AMP.'C=tools_data'.AMP.'M=sql_manager' => lang('sql_manager')
		);

		$this->cp->render('tools/sql_query_form');
	}

	// --------------------------------------------------------------------

	/**
	 * SQL View Database
	 *
	 * View and browse all database tables
	 *
	 * @access	public
	 * @return	void
	 */
	function sql_view_database()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');

		$this->jquery->tablesorter('.mainTable', '{
			headers: {
			0: {sorter: false},
			2: {sorter: false}
		},
			widgets: ["zebra"]
		}');

		$details = $this->tools_model->get_table_status();

		$this->view->cp_page_title = lang('sql_view_database');

		$this->cp->set_right_nav($this->sub_breadcrumbs);

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_data'=> lang('tools_data'),
			BASE.AMP.'C=tools_data'.AMP.'M=sql_manager' => lang('sql_manager')
		);

		$this->javascript->output('
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
			);'
		);

		$this->cp->render('tools/sql_view_database', $details);
	}

	// --------------------------------------------------------------------

	/**
	 * SQL Run Table Action
	 *
	 * Optimize / Repair Tables
	 *
	 * @access	public
	 * @return	void
	 */
	function sql_run_table_action()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		if (($action = $this->input->post('table_action')) === FALSE OR ! in_array($action, array('OPTIMIZE', 'REPAIR')))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! isset($_POST['table']))
		{
			show_error(lang('no_buttons_selected'));
		}

		$this->load->library('table');

		// generate table headings from a query for a known table
		$query = $this->db->query('ANALYZE TABLE `exp_members`');
		$vars['headings'] = array();

		foreach ($query->list_fields() as $column)
		{
			$vars['headings'][] = $column;
		}

		// run the actions
		foreach ($this->input->post('table') as $table)
		{
			$query = $this->db->query("{$action} TABLE ".$this->db->escape_str($table));

			foreach ($query->result_array() as $row)
			{
				foreach ($row as $k => $v)
				{
					$vars['results'][$table][] = $v;
				}
			}
		}

		$vars['action'] = strtolower($action);

		$this->view->cp_page_title = lang($vars['action']);

		$this->cp->set_right_nav($this->sub_breadcrumbs);

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_data'=> lang('tools_data'),
			BASE.AMP.'C=tools_data'.AMP.'M=sql_manager'=> lang('sql_manager')
		);

		$this->cp->render('tools/sql_run_table_action', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * SQL Run Query, Status, System Variables, and Process List
	 *
	 * These methods all use the _sql_handler()
	 *
	 * @access	public
	 * @return	void
	 */

	function sql_run_query ()	{ $this->_sql_handler('run_query'); }
	function sql_status ()	{ $this->_sql_handler('status'); }
	function sql_system_vars ()	{ $this->_sql_handler('system_vars'); }
	function sql_processlist ()	{ $this->_sql_handler('processlist'); }

	// --------------------------------------------------------------------

	/**
	 * SQL Handler
	 *
	 * A shared private function that is used for the SQL tools
	 *
	 * @access	private
	 * @param	string		// the SQL process requested
	 * @return	mixed
	 */
	function _sql_handler($process = '')
	{
		// this is for hosted demos to prevent users from using the SQL Manager
		if ($this->config->item('demo_date') != FALSE)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('pagination');
		$this->load->library('table');

		$this->jquery->tablesorter('.mainTable', '{
			widgets: ["zebra"]
		}');

		// defaults in the house!
		$run_query	= FALSE;
		$row_limit	= 100;
		$title		= lang('sql_manager');

		$this->cp->set_right_nav($this->sub_breadcrumbs);

		// Set the "fetch fields" flag to true so that
		// the Query function will return the field names

		switch($process)
		{
			case 'processlist' :
				$sql 	= "SHOW PROCESSLIST";
				$query  = $this->db->query($sql);
				$title  = lang('sql_processlist');
				break;
			case 'system_vars' :
				$sql 	= "SHOW VARIABLES";
				$query	= $this->db->query($sql);
				$title	= lang('sql_system_vars');
				break;
			case 'status' :
				$sql 	= "SHOW STATUS";
				$query 	= $this->db->query($sql);
				$title 	= lang('sql_status');
				break;
			case 'run_query' :
				$this->db->db_debug = ($this->input->post('debug') !== FALSE) ? TRUE : FALSE;;
				$run_query = TRUE;
				$title	= lang('query_result');
				break;
			default :
				return;
				break;
		}

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_data'=> lang('tools_data'),
			BASE.AMP.'C=tools_data'.AMP.'M=sql_manager' => lang('sql_manager')
		);

		$this->view->cp_page_title = $title;

		// some defaults for some view variables
		$vars['write'] = FALSE;
		$vars['no_results'] = FALSE;
		$vars['pagination'] = FALSE;

		// Fetch the query.  It can either come from a
		// POST request or a url encoded GET request

		if ($run_query == TRUE)
		{
			if ( ! $sql = $this->input->post('thequery'))
			{
				if ( ! $sql = $this->input->get('thequery'))
				{
					return $this->sql_query_form();
				}
				else
				{
					$sql = rawurldecode(base64_decode($sql));
				}
			}

			$sql = trim(str_replace(";", "", $sql));

			// Determine if the query is one of the non-allowed types
			$qtypes = array('FLUSH', 'REPLACE', 'GRANT', 'REVOKE', 'LOCK', 'UNLOCK');

			if (preg_match("/(^|\s)(".implode('|', $qtypes).")\s/si", $sql))
			{
				show_error(lang('sql_not_allowed'));
			}

			// If it's a DELETE query, require that a Super Admin be the one submitting it
			if ($this->session->userdata['group_id'] != '1')
			{
				if (strpos(strtoupper($sql), 'DELETE') !== FALSE OR strpos(strtoupper($sql), 'ALTER') !== FALSE OR strpos(strtoupper($sql), 'TRUNCATE') !== FALSE OR strpos(strtoupper($sql), 'DROP') !== FALSE)
				{
					show_error(lang('unauthorized_access'));
				}
			}

			// If it's a SELECT query we'll see if we need to limit
			// the result total and add pagination links
			if (strpos(strtoupper($sql), 'SELECT') !== FALSE)
			{
				if ( ! preg_match("/LIMIT\s+[0-9]/i", $sql))
				{
					// Modify the query so we get the total sans LIMIT
					$row  = ( ! $this->input->get_post('per_page')) ? 0 : $this->input->get_post('per_page');
					$new_sql = $sql." LIMIT ".$row.", ".$row_limit;
					
					if ( ! $query = $this->db->query($new_sql))
					{
						$vars['no_results'] = lang('sql_no_result');
						$this->cp->render('tools/sql_results', $vars);
						return;
					}
					
					// Get total results
					$total_results = $this->db->query($sql)->num_rows();
					
					if ($total_results > $row_limit)
					{
						$config['base_url'] = BASE.AMP.'C=tools_data'.AMP.'M=sql_run_query'.AMP.'thequery='.rawurlencode(base64_encode($sql));
						$config['total_rows'] = $total_results;
						$config['per_page'] = $row_limit;
						$config['page_query_string'] = TRUE;
						$config['first_link'] = lang('pag_first_link');
						$config['last_link'] = lang('pag_last_link');
						
						$this->pagination->initialize($config);
					}
				}
			}

			$vars['pagination'] = $this->pagination->create_links();

			if ( ! isset($new_sql))
			{
				if ( ! $query = $this->db->query($sql))
				{
					$vars['no_results'] = lang('sql_no_result');
					$this->cp->render('tools/sql_results', $vars);
					return;
				}
			}

			$qtypes = array('INSERT', 'UPDATE', 'DELETE', 'ALTER', 'CREATE', 'DROP', 'TRUNCATE');

			foreach ($qtypes as $type)
			{
				if (strncasecmp($sql, $type, strlen($type)) == 0)
				{
					$vars['affected'] = ($this->db->affected_rows() > 0) ? lang('total_affected_rows').NBS.$this->db->affected_rows() : lang('sql_good_query');
					$vars['thequery'] = $this->security->xss_clean($sql);
					$vars['write'] = TRUE;

					$this->cp->render('tools/sql_results', $vars);
					return;
				}
			}
		}

		// no results?  Wasted efforts!
		if ($query->num_rows() == 0)
		{
			$vars['no_results'] = lang('sql_no_result');
			$this->cp->render('tools/sql_results', $vars);
			return;
		}

		$vars['thequery'] = $this->security->xss_clean($sql);
		$vars['total_results'] = str_replace('%x', (isset($total_results)) ? $total_results : $query->num_rows(), lang('total_results'));
		$vars['query'] = $query;

		$this->cp->render('tools/sql_results', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Search and Replace
	 *
	 * Creates the Search and Replace form page
	 *
	 * @access	public
	 * @return	mixed
	 */
	function search_and_replace()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		// get the submitted details
		$search  = $this->input->get_post('search_term');
		$replace = $this->input->get_post('replace_term');
		$where   = $this->input->get_post('replace_where');
		$replaced = FALSE;

		if ($search !== FALSE && $replace !== FALSE && $where !== FALSE)
		{
			$replaced = $this->_do_search_and_replace($search, $replace, $where);
		}

		$this->view->cp_page_title = lang('search_and_replace');

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_data'=> lang('tools_data')
		);

		$vars['save_tmpl_files'] = ($this->config->item('save_tmpl_files') == 'y') ? TRUE : FALSE;
		$vars['replace_options'] = $this->tools_model->get_search_replace_options();
		$vars['replaced'] = ($replaced !== FALSE) ? lang('rows_replaced').' '.$replaced : FALSE;

		$this->cp->render('tools/search_and_replace', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Do Search and Replace
	 *
	 * Used by search_and_replace() to execute replacement
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	function _do_search_and_replace($search, $replace, $where)
	{
		// escape search and replace for use in queries
		$search = $this->db->escape_str($search);
		$replace = $this->db->escape_str($replace);
		$where = $this->db->escape_str($where);

		if ($where == 'title')
		{
			$sql = "UPDATE `exp_channel_titles` SET `{$where}` = REPLACE(`{$where}`, '{$search}', '{$replace}')";
		}
		elseif ($where == 'preferences' OR strncmp($where, 'site_preferences_', 17) == 0)
		{
			$rows = 0;

			if ($where == 'preferences')
			{
				$site_id = $this->config->item('site_id');
			}
			else
			{
				$site_id = substr($where, strlen('site_preferences_'));
			}
	
			/** -------------------------------------------
			/**  Site Preferences in Certain Tables/Fields
			/** -------------------------------------------*/

			$preferences = array(
				'exp_channels' => array(
					'channel_title',
					'channel_url',
					'comment_url',
					'channel_description',
					'comment_notify_emails',
					'channel_notify_emails',
					'search_results_url',
					'rss_url'
				),
				'exp_upload_prefs' => array(
					'server_path',
					'properties',
					'file_properties',
					'url'
				),
				'exp_member_groups' => array(
					'group_title',
					'group_description',
					'mbr_delete_notify_emails'
				),
				'exp_global_variables'	=> array('variable_data'),
				'exp_categories'		=> array('cat_image'),
				'exp_forums'			=> array(
					'forum_name',
					'forum_notify_emails',
					'forum_notify_emails_topics'),
				'exp_forum_boards'		=> array(
					'board_label',
					'board_forum_url',
					'board_upload_path',
					'board_notify_emails',
					'board_notify_emails_topics'
				)
			);

			foreach($preferences as $table => $fields)
			{
				if ( ! $this->db->table_exists($table) OR $table == 'exp_forums')
				{
					continue;
				}

				$site_field = ($table == 'exp_forum_boards') ? 'board_site_id' : 'site_id';

				foreach($fields as $field)
				{
					$this->db->query("UPDATE `{$table}`
								SET `{$field}` = REPLACE(`{$field}`, '{$search}', '{$replace}')
								WHERE `{$site_field}` = '".$this->db->escape_str($site_id)."'");

					$rows += $this->db->affected_rows();
				}
			}

			if ($this->db->table_exists('exp_forum_boards'))
			{
				$this->db->select('board_id');
				$this->db->where('board_site_id', $site_id);
				$query = $this->db->get('forum_boards');
				
				if ($query->num_rows() > 0)
				{
					foreach($query->result_array() as $row)
					{
						foreach($preferences['exp_forums'] as $field)
						{
							$this->db->query("UPDATE `exp_forums`
										SET `{$field}` = REPLACE(`{$field}`, '{$search}', '{$replace}')
										WHERE `board_id` = '".$this->db->escape_str($row['board_id'])."'");

							$rows += $this->db->affected_rows();
						}
					}
				}
			}

			/** -------------------------------------------
			/**  Site Preferences in Database
			/** -------------------------------------------*/

			$this->config->update_site_prefs(array(), $site_id, $search, $replace);

			$rows += 5;
		}
		elseif ($where == 'template_data')
		{
			$sql = "UPDATE `exp_templates` SET `$where` = REPLACE(`{$where}`, '{$search}', '{$replace}'), `edit_date` = '".$this->localize->now."'";
		}
		elseif (strncmp($where, 'template_', 9) == 0)
		{
			$sql = "UPDATE `exp_templates` SET `template_data` = REPLACE(`template_data`, '{$search}', '{$replace}'), edit_date = '".$this->localize->now."'
					WHERE group_id = '".substr($where,9)."'";
		}
		elseif (strncmp($where, 'field_id_', 9) == 0)
		{
			$sql = "UPDATE `exp_channel_data` SET `{$where}` = REPLACE(`{$where}`, '{$search}', '{$replace}')";
		}
		else
		{
			// no valid $where
			return FALSE;
		}

		if (isset($sql))
		{
			$this->db->query($sql);
			$rows = $this->db->affected_rows();
		}

		return $rows;
	}

	// --------------------------------------------------------------------

	/**
	 * Recount Statistics
	 *
	 * Creates the Recount Statistics form page
	 *
	 * @access	public
	 * @return	mixed
	 */
	function recount_stats()
	{
		if ( ! $this->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');

		$this->jquery->tablesorter('.mainTable', '{
			headers: {2: {sorter: false}},
			widgets: ["zebra"]
		}');
		
		$this->cp->set_right_nav(array(lang('recount_prefs') => BASE.AMP.'C=admin_system'.AMP.'M=recount_preferences'));

		// Do the forums exist?
		$forum_exists = FALSE;

		if ($this->config->item('forum_is_installed') == "y")
		{
			$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Forum'");

			if ($query->row('count')  > 0)
			{
				$forum_exists = TRUE;
			}
		}

		if ($forum_exists == FALSE)
		{
			$sources = array('members', 'channel_titles', 'sites');
		}
		else
		{
			$sources = array('members', 'channel_titles', 'forums', 'forum_topics', 'sites');
		}

		foreach ($sources as $source)
		{
			$vars['sources'][$source] = $this->db->count_all($source);
		}

		//$vars['sources']['site_statistics'] = 4;

		// are we recounting now?
		$which  = $this->input->get_post('TBL');
		$recount = FALSE;

		if ($which !== FALSE)
		{
			$recount = $this->_do_recount_stats($which, $forum_exists);
			$this->session->set_flashdata('message_success', lang('recount_completed') );
			$this->functions->redirect(BASE.AMP.'C=tools_data'.AMP.'M=recount_stats');			
		}

		$this->view->cp_page_title = lang('recount_stats');

		// a bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=tools' => lang('tools'),
			BASE.AMP.'C=tools_data'=> lang('tools_data')
		);

		$this->cp->render('tools/recount_stats', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Do Recount Stats
	 *
	 * used by recount_stats() to do the recount
	 *
	 * @access	private
	 * @param	string
	 * @param	bool
	 * @return	bool
	 */
	function _do_recount_stats($which, $forum_exists)
	{
		$sources = array('members', 'channel_titles', 'forums', 'forum_topics', 'sites');
		
		$this->cp->get_installed_modules();
		
		if ( ! in_array($which, $sources))
		{
			return FALSE;
		}

		$batch = 1; // group UPDATEs into $batch records per query


		if ($which == 'members')
		{
			$member_entries = array(); // arrays of statements to update

			$member_entries_count = $this->db->query('SELECT COUNT(*) AS count, author_id FROM exp_channel_titles GROUP BY author_id ORDER BY count DESC');

			if (isset($this->cp->installed_modules['comment']))
			{
				$member_comments_count = $this->db->query('SELECT COUNT(*) AS count, author_id FROM exp_comments GROUP BY author_id ORDER BY count DESC');					
			}				

			$member_message_count = $this->db->query('SELECT COUNT(*) AS count, recipient_id FROM exp_message_copies WHERE message_read = "n" GROUP BY recipient_id ORDER BY count DESC');

			$member_data = array();
			
			if ($member_entries_count->num_rows() > 0)
			{

				foreach ($member_entries_count->result() as $row)
				{
					$member_entries[$row->author_id]['member_id'] = $row->author_id;
					$member_entries[$row->author_id]['total_entries'] = $row->count;						
					$member_entries[$row->author_id]['total_comments'] = 0;
					$member_entries[$row->author_id]['private_messages'] = 0;
					$member_entries[$row->author_id]['total_forum_posts'] = 0;
					$member_entries[$row->author_id]['total_forum_topics'] = 0;
				}
			}

			if ($this->cp->installed_modules['comment'])
			{
				if ($member_comments_count->num_rows() > 0)
				{
					foreach ($member_comments_count->result() as $row)
					{
						if (isset($member_entries[$row->author_id]['member_id']))
						{
							$member_entries[$row->author_id]['total_comments'] = $row->count;							
						}
						else
						{
							$member_entries[$row->author_id]['member_id'] = $row->author_id;
							$member_entries[$row->author_id]['total_entries'] = 0;					
							$member_entries[$row->author_id]['total_comments'] = $row->count;
							$member_entries[$row->author_id]['private_messages'] = 0;
							$member_entries[$row->author_id]['total_forum_posts'] = 0;
							$member_entries[$row->author_id]['total_forum_topics'] = 0;
						}
					}	
				}					
			}

			if ($member_message_count->num_rows() > 0)
			{
				foreach ($member_message_count->result() as $row)
				{
					if (isset($member_entries[$row->recipient_id]['member_id']))
					{
						$member_entries[$row->recipient_id]['private_messages'] = $row->count;							
					}
					else
					{
						$member_entries[$row->recipient_id]['member_id'] = $row->recipient_id;
						$member_entries[$row->recipient_id]['total_entries'] = 0;					
						$member_entries[$row->recipient_id]['total_comments'] = 0;
						$member_entries[$row->recipient_id]['private_messages'] = $row->count;
						
						$member_entries[$row->recipient_id]['total_forum_posts'] = 0;
						$member_entries[$row->recipient_id]['total_forum_topics'] = 0;
					}
				}
			}

			if ($forum_exists === TRUE)
			{
				$forum_topics_count = $this->db->query('SELECT COUNT(*) AS count, author_id FROM exp_forum_topics GROUP BY author_id ORDER BY count DESC');
				$forum_posts_count = $this->db->query('SELECT COUNT(*) AS count, author_id FROM exp_forum_posts GROUP BY author_id ORDER BY count DESC');

				if ($forum_topics_count->num_rows() > 0)
				{
					foreach($forum_topics_count->result() as $row)
					{
						if (isset($member_entries[$row->author_id]['member_id']))
						{
							$member_entries[$row->author_id]['total_forum_topics'] = $row->count;							
						}
						else
						{
							$member_entries[$row->author_id]['member_id'] = $row->author_id;
							$member_entries[$row->author_id]['total_entries'] = 0;					
							$member_entries[$row->author_id]['total_comments'] = 0;
							$member_entries[$row->author_id]['private_messages'] = 0;
							$member_entries[$row->author_id]['total_forum_posts'] = 0;
							$member_entries[$row->author_id]['total_forum_topics'] = $row->count;
						}
					}
				}

				if ($forum_posts_count->num_rows() > 0)
				{
					foreach($forum_posts_count->result() as $row)
					{
						if (isset($member_entries[$row->author_id]['member_id']))
						{
							$member_entries[$row->author_id]['total_forum_posts'] = $row->count;							
						}
						else
						{
							$member_entries[$row->author_id]['member_id'] = $row->author_id;
							$member_entries[$row->author_id]['total_entries'] = 0;					
							$member_entries[$row->author_id]['total_comments'] = 0;
							$member_entries[$row->author_id]['private_messages'] = 0;
							$member_entries[$row->author_id]['total_forum_posts'] = $row->count;
							$member_entries[$row->author_id]['total_forum_topics'] = 0;
						}
					}
				}
			}

			if ( ! empty($member_entries))
			{
				$this->db->update_batch('exp_members', $member_entries, 'member_id');	

				// Set the rest to 0 for all of the above
				
				$data = array(
					'total_entries'			=> 0,
					'total_comments'		=> 0,
					'private_messages'		=> 0,
					'total_forum_posts'		=> 0,
					'total_forum_topics'	=> 0
				);

				$this->db->where_not_in('member_id', array_keys($member_entries));
				$this->db->update('members', $data); 
			}
		}
		elseif ($which == 'channel_titles')
		{
			$channel_titles = array(); // arrays of statements to update

			if (isset($this->cp->installed_modules['comment']))
			{
				$channel_comments_count = $this->db->query('SELECT COUNT(comment_id) AS count, entry_id FROM exp_comments WHERE status = "o" GROUP BY entry_id ORDER BY count DESC');
				$channel_comments_recent = $this->db->query('SELECT MAX(comment_date) AS recent, entry_id FROM exp_comments WHERE status = "o" GROUP BY entry_id ORDER BY recent DESC');

				if ($channel_comments_count->num_rows() > 0)
				{
					foreach ($channel_comments_count->result() as $row)
					{
						$channel_titles[$row->entry_id]['entry_id'] = $row->entry_id;
						$channel_titles[$row->entry_id]['comment_total'] = $row->count;
						$channel_titles[$row->entry_id]['recent_comment_date'] = 0;
					}

					// Now for the most recent date
					foreach ($channel_comments_recent->result() as $row)
					{
						$channel_titles[$row->entry_id]['recent_comment_date'] = $row->recent;
					}
				}					
			}

			// Set the rest to 0 for all of the above
			$data = array(
           		'comment_total'			=> 0,
           		'recent_comment_date'	=> 0
         	);

			if (count($channel_titles) > 0)
			{
				$this->db->update_batch('exp_channel_titles', $channel_titles, 'entry_id');

				$this->db->where_not_in('entry_id', array_keys($channel_titles));
				$this->db->update('channel_titles', $data); 
			}
			else
			{
				$this->db->update('channel_titles', $data); 				
			}
		}
		elseif ($which == 'forums')
		{
			$query = $this->db->query("SELECT forum_id FROM exp_forums WHERE forum_is_cat = 'n'");

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$forum_id = $row['forum_id'];

					$res1 = $this->db->query("SELECT COUNT(*) AS count FROM exp_forum_topics WHERE forum_id = '{$forum_id}'");
					$total1 = $res1->row('count');

					$res2 = $this->db->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE forum_id = '{$forum_id}'");
					$total2 = $res2->row('count');

					$this->db->query("UPDATE exp_forums SET forum_total_topics = '{$total1}', forum_total_posts = '{$total2}' WHERE forum_id = '{$forum_id}'");
				}
			}
		}
		elseif ($which == 'forum_topics')
		{
			$total_rows = $this->db->count_all('forum_topics');

			$query = $this->db->query("SELECT forum_id FROM exp_forums WHERE forum_is_cat = 'n' ORDER BY forum_id");

			foreach ($query->result_array() as $row)
			{
				$forum_id = $row['forum_id'];

				$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_forum_topics WHERE forum_id = '{$forum_id}'");
				$data['forum_total_topics'] = $query->row('count');

				$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE forum_id = '{$forum_id}'");
				$data['forum_total_posts'] = $query->row('count');

				$query = $this->db->query("SELECT topic_id, title, topic_date, last_post_date, last_post_author_id, screen_name
									FROM exp_forum_topics, exp_members
									WHERE member_id = last_post_author_id
									AND forum_id = '{$forum_id}'
									ORDER BY last_post_date DESC LIMIT 1");

				$data['forum_last_post_id'] 		= ($query->num_rows() == 0) ? 0 : $query->row('topic_id') ;
				$data['forum_last_post_title'] 		= ($query->num_rows() == 0) ? '' : $query->row('title') ;
				$data['forum_last_post_date'] 		= ($query->num_rows() == 0) ? 0 : $query->row('topic_date') ;
				$data['forum_last_post_author_id']	= ($query->num_rows() == 0) ? 0 : $query->row('last_post_author_id') ;
				$data['forum_last_post_author']		= ($query->num_rows() == 0) ? '' : $query->row('screen_name') ;

				$query = $this->db->query("SELECT post_date, author_id, screen_name
									FROM exp_forum_posts, exp_members
									WHERE  member_id = author_id
									AND forum_id = '{$forum_id}'
									ORDER BY post_date DESC LIMIT 1");

				if ($query->num_rows() > 0)
				{
					if ($query->row('post_date')  > $data['forum_last_post_date'])
					{
						$data['forum_last_post_date'] 		= $query->row('post_date');
						$data['forum_last_post_author_id']	= $query->row('author_id');
						$data['forum_last_post_author']		= $query->row('screen_name');
					}
				}

				$this->db->query($this->db->update_string('exp_forums', $data, "forum_id='{$forum_id}'"));
				unset($data);

				/** -------------------------------------
				/**  Update
				/** -------------------------------------*/

				$query = $this->db->query("SELECT forum_id FROM exp_forums");

				$total_topics = 0;
				$total_posts  = 0;

				foreach ($query->result_array() as $row)
				{
					$q = $this->db->query("SELECT COUNT(*) AS count FROM exp_forum_topics WHERE forum_id = '".$row['forum_id']."'");
					$total_topics = ($total_topics == 0) ? $q->row('count')  : $total_topics + $q->row('count') ;

					$q = $this->db->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE forum_id = '".$row['forum_id']."'");
					$total_posts = ($total_posts == 0) ? $q->row('count')  : $total_posts + $q->row('count') ;
				}

				$this->db->query("UPDATE exp_stats SET total_forum_topics = '{$total_topics}', total_forum_posts = '{$total_posts}'");
			}

			$query = $this->db->query("SELECT topic_id FROM exp_forum_topics WHERE thread_total <= 1");

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$res = $this->db->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE topic_id = '".$row['topic_id']."'");
					$count = ($res->row('count') == 0) ? 1 : $res->row('count')  + 1;

					$this->db->query("UPDATE exp_forum_topics SET thread_total = '{$count}' WHERE topic_id = '".$row['topic_id']."'");
				}
			}
		}
		elseif ($which == 'sites')
		{
			$original_site_id = $this->config->item('site_id');

			$query = $this->db->query("SELECT site_id FROM exp_sites");

			foreach($query->result_array() as $row)
			{
				$this->config->set_item('site_id', $row['site_id']);
				
				if (isset($this->cp->installed_modules['comment']))
				{
					$this->stats->update_comment_stats();
				}

				$this->stats->update_member_stats();
				$this->stats->update_channel_stats();
			}

			$this->config->set_item('site_id', $original_site_id);
		}
	}
}
// END CLASS

/* End of file tools_data.php */
/* Location: ./system/expressionengine/controllers/cp/tools_data.php */