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
class Content_edit extends CP_Controller {

	private $publish_base_uri;
	private $publish_base_url;
	private $edit_base_uri;
	private $edit_base_url;
	private $nest_categories	= 'y';
	private $installed_modules	= FALSE;
	private $allowed_channels	= array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->installed_modules = $this->cp->get_installed_modules();
		$this->allowed_channels = $this->functions->fetch_assigned_channels();

		$this->publish_base_uri = 'C=content_publish';
		$this->publish_base_url = BASE.AMP.$this->publish_base_uri;
		$this->edit_base_uri	= 'C=content_edit';
		$this->edit_base_url	= BASE.AMP.$this->edit_base_uri;

		$this->load->library('api');
		$this->load->model('channel_model');
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @return	void
	 */
	public function index()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}

		// Fetch channel ID numbers assigned to the current user
		if (empty($this->allowed_channels))
		{
			show_error(lang('no_channels'));
		}


		// Fetch channels
		// ----------------------------------------------------------------

		$this->api->instantiate('channel_structure');
		$channel_q = $this->api_channel_structure->get_channels();

		$channels = array();

		foreach($channel_q->result() as $c_row)
		{
			$channels[$c_row->channel_id] = $c_row;
		}

		// Set up Per page data
		// ----------------------------------------------------------------

		// Results per page pull-down menu
		if ( ! ($perpage = $this->input->get_post('perpage')))
		{
			$perpage = ($this->input->cookie('perpage') == FALSE) ? 50 : $this->input->cookie('perpage');
		}

		$this->functions->set_cookie('perpage' , $perpage, 60*60*24*182);


		// Table
		// ----------------------------------------------------------------

		$this->load->library('table');

		$columns = array(
			'entry_id'		=> array('header' => '#', 'html' => FALSE),
			'title'			=> array('header' => lang('title')),
			'view'			=> array('header' => lang('view'), 'sort' => FALSE),
			'comment_total'	=> array('header' => lang('comments')),
			'screen_name'	=> array('header' => lang('author')),
			'entry_date'	=> array('header' => lang('date')),
			'channel_name'	=> array('header' => lang('channel')),
			'status'		=> array('header' => lang('status')),
			'_check'		=> array(
				'header' => form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"'),
				'sort' => FALSE
			)
		);

		if ( ! isset($this->installed_modules['comment']))
		{
			unset($columns['comment_total']);
		}


		$this->table->set_base_url($this->edit_base_uri);
		$this->table->set_columns($columns);

		$initial_state = array(
			'sort'	=> array('entry_date' => 'desc')
		);

		$params = array(
			'perpage'	=> $perpage,
			'channels'	=> $channels,
		);

		$vars = $this->table->datasource('_table_datasource', $initial_state, $params);

		$filter_data = $vars['filter_data'];
		unset($vars['filter_data']);

		// Setup the form!
		// ----------------------------------------------------------------

		$form_fields = $this->_edit_form($filter_data, $channels);
		$vars = array_merge($vars, $form_fields);


		// Action Options!
		// ----------------------------------------------------------------

		$vars['action_options'] = array();

		if ( ! $this->input->post('toggle'))
		{
			$vars['action_options'] = array(
				'edit'				=> lang('edit_selected'),
				'delete'			=> lang('delete_selected'),
				'------'			=> '------',
				'add_categories'	=> lang('add_categories'),
				'remove_categories'	=> lang('remove_categories')
			);
		}


		// Assemble!
		// ----------------------------------------------------------------

		// Do we have a message to show?
		// Note: a message is displayed on this page after editing or submitting a new entry

		if ($this->input->get_post("U") == 'mu')
		{
			$vars['message'] = lang('multi_entries_updated');
		}

		// Declare the "filtering" form

		$vars['heading'] = 'edit_channel_entries';

		$vars['form_hidden']	= array();
		$vars['search_form']	= 'C=content_edit';
		$vars['entries_form']	= 'C=content_edit'.AMP.'M=multi_edit_form';

		$this->view->cp_page_title = lang('edit');

		$this->cp->add_js_script(array(
			'ui'		=> 'datepicker',
			'file'		=> 'cp/content_edit'
		));

		$this->javascript->set_global('autosave_map', $vars['autosave_array']);
		$this->cp->render('content/edit', $vars);
	}


	// --------------------------------------------------------------------

	/**
	 * Edit table datasource
	 *
	 * Must remain public so that it can be called from the
	 * table library!
	 *
	 * @access	public
	 */
	public function _table_datasource($tbl_settings, $defaults)
	{
		// Get filter information
		// ----------------------------------------------------------------

		$keywords = (string) $this->input->post('keywords');
		$channel_id = (string) $this->input->get_post('channel_id');

		if ($channel_id == 'null')
		{
			$channel_id = NULL;
		}

		if ( ! $keywords)
		{
			$keywords = (string) $this->input->get('keywords');

			if ($keywords)
			{
				$keywords = base64_decode($keywords);
			}
		}

		if ($keywords)
		{
			$this->load->helper('search');
			$keywords = xss_clean($keywords);

			if (substr(strtolower($keywords), 0, 3) == 'ip:')
			{
				$keywords = str_replace('_','.',$keywords);
			}
		}


		// Because of the auto convert we prepare a specific variable with the converted ascii
		// characters while leaving the $keywords variable intact for display and URL purposes
		$this->load->helper('text');
		$search_keywords = ($this->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities($keywords) : $keywords;

		$perpage = $this->input->get_post('perpage');
		$perpage = $perpage ? $perpage : $defaults['perpage'];

		$rownum = $tbl_settings['offset'];

		// We want the filter to work based on both get and post
		$filter_data = array(
			'channel_id'	=> $channel_id,
			'keywords'		=> $keywords,
			'cat_id'		=> $this->input->get_post('cat_id'),
			'status'		=> $this->input->get_post('status'),
			'order'			=> $this->input->get_post('order'),
			'date_range'	=> $this->input->get_post('date_range'),

			'author_id'		=> $this->input->get_post('author_id'),
			'exact_match'	=> $this->input->get_post('exact_match'),
			'cat_id'		=> ($this->input->get_post('cat_id') != 'all') ? $this->input->get_post('cat_id') : '',
			'search_in'		=> $this->input->get_post('search_in') ? $this->input->get_post('search_in') : 'title',

			'rownum'		=> $rownum,
			'perpage'		=> $perpage,

			'search_keywords'	=> $search_keywords
		);

		$channels = $defaults['channels'];

		$order = $tbl_settings['sort'];
		$columns = $tbl_settings['columns'];

		// -------------------------------------------
		// 'edit_entries_additional_where' hook.
		//  - Add additional where, where_in, where_not_in
		//
			$_hook_wheres = $this->extensions->call('edit_entries_additional_where', $filter_data);
			if ($this->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		$filter_data['_hook_wheres'] = is_array($_hook_wheres) ? $_hook_wheres : array();


		$this->load->model('search_model');
		$filter_result = $this->search_model->get_filtered_entries($filter_data, $order);

		$rows = $filter_result['results'];
		$total = $filter_result['total_count'];

		unset($filter_result);

		$filter_url = $this->_create_return_filter($filter_data);

		// Gather up ids for a single quick query down the line
		$entry_ids = array();
		foreach ($rows as $row)
		{
			$entry_ids[] = $row['entry_id'];
		}


		// Load the site's templates
		// ----------------------------------------------------------------

		$templates = array();

		$tquery = $this->db->query("SELECT exp_template_groups.group_name, exp_templates.template_name, exp_templates.template_id
							FROM exp_template_groups, exp_templates
							WHERE exp_template_groups.group_id = exp_templates.group_id
							AND exp_templates.site_id = '".$this->db->escape_str($this->config->item('site_id'))."'");

		foreach ($tquery->result_array() as $row)
		{
			$templates[$row['template_id']] = $row['group_name'].'/'.$row['template_name'];
		}


		// Comment count
		// ----------------------------------------------------------------

		$show_link = TRUE;
		$comment_counts = array();

		if (count($entry_ids) AND $this->db->table_exists('comments'))
		{
			$comment_qry = $this->db->select('entry_id, COUNT(*) as count')
				->where_in('entry_id', $entry_ids)
				->group_by('entry_id')
				->get('comments');

			foreach ($comment_qry->result() as $row)
			{
				$comment_counts[$row->entry_id] = $row->count;
			}
		}



		// Date formatting
		$date_fmt = ($this->session->userdata('time_format') != '') ? $this->session->userdata('time_format') : $this->config->item('time_format');

		$datestr = '%m/%d/%y %h:%i %a';

		if ($date_fmt != 'us')
		{
			$datestr = '%Y-%m-%d %H:%i';
		}

		// Autosave - Grab all autosaved entries
		// ----------------------------------------------------------------

		$this->prune_autosave();
		$this->db->select('entry_id, original_entry_id, channel_id, title, author_id, status, entry_date,  comment_total');
		$autosave = $this->db->get('channel_entries_autosave');

		$autosave_array = array();
		$autosave_show = FALSE;

		if ($autosave->num_rows())
		{
			$this->load->helper('snippets');
			$autosave_show = TRUE;
		}

		foreach ($autosave->result() as $entry)
		{
			if ($entry->original_entry_id)
			{
				$autosave_array[] = $entry->original_entry_id;
			}
		}


		// Status Highlight Colors
		// ----------------------------------------------------------------

		$status_color_q = $this->db->from('channels AS c, statuses AS s, status_groups AS sg')
			->select('c.channel_id, c.channel_name, s.status, s.highlight')
			->where('sg.group_id = c.status_group', NULL, FALSE)
			->where('sg.group_id = s.group_id', NULL, FALSE)
			->where('sg.site_id', $this->config->item('site_id'))
			->where('s.highlight !=', '')
			->where_in('c.channel_id', array_keys($channels))
			->get();

		$c_array = array();

		foreach ($status_color_q->result_array() as $rez)
		{
			$c_array[$rez['channel_id'].'_'.$rez['status']] = str_replace('#', '', $rez['highlight']);
		}

		$colors = array();

		//  Fetch Color Library
		if (file_exists(APPPATH.'config/colors.php'))
		{
			include (APPPATH.'config/colors.php');
		}

		// Generate row data
		// ----------------------------------------------------------------

		foreach ($rows as &$row)
		{
			$url = $this->publish_base_uri.AMP."M=entry_form".AMP."channel_id={$row['channel_id']}".AMP."entry_id={$row['entry_id']}".AMP.$filter_url;

			$row['title'] = anchor(BASE.AMP.$url, $row['title']);
			$row['view'] = '---';
			$row['channel_name'] = $channels[$row['channel_id']]->channel_title;
			$row['entry_date'] = $this->localize->format_date($datestr, $row['entry_date']);
			$row['_check'] = form_checkbox('toggle[]', $row['entry_id'], '', ' class="toggle" id="delete_box_'.$row['entry_id'].'"');

			// autosave indicator
			if (in_array($row['entry_id'], $autosave_array))
			{
				$row['title'] .= NBS.required();
			}

			// screen name email link
			if ( ! $row['screen_name'])
			{
				$row['screen_name'] = $row['username'];
			}

			$row['screen_name'] = mailto($row['email'], $row['screen_name']);


			// live look template
			$llt = $row['live_look_template'];
			if ($llt && isset($templates[$llt]))
			{
				$url = $this->functions->create_url($templates[$row['live_look_template']].'/'.$row['entry_id']);
				$row['view'] = anchor($this->cp->masked_url($url), lang('view'));
			}


			// Status
			$color_info = '';
			$color_key = $row['channel_id'].'_'.$row['status'];
			$status_name = ($row['status'] == 'open' OR $row['status'] == 'closed') ? lang($row['status']) : $row['status'];

			if (isset($c_array[$color_key]) AND $c_array[$color_key] != '')
			{
				$color = strtolower($c_array[$color_key]);
				$prefix = isset($colors[$color]) ? '' : '#';

				// There are custom colours, override the class above
				$color_info = 'style="color:'.$prefix.$color.';"';
			}

			$row['status'] = '<span class="status_'.$row['status'].'"'.$color_info.'>'.$status_name.'</span>';


			// comment_total link
			if (isset($this->installed_modules['comment']))
			{
				$all_or_own = 'all';

				if ($row['author_id'] == $this->session->userdata('member_id'))
				{
					$all_or_own = 'own';
				}

				// do not move these to the new allowed_group style - they are ANDs not ORs
				if ( ! $this->cp->allowed_group('can_edit_'.$all_or_own.'_comments') AND
					 ! $this->cp->allowed_group('can_delete_'.$all_or_own.'_comments') AND
					 ! $this->cp->allowed_group('can_moderate_comments'))
				{
					$row['comment_total'] = '<div class="lightLinks">--</div>';
				}
				else
				{
					$comment_count = isset($comment_counts[$row['entry_id']]) ? $comment_counts[$row['entry_id']] : 0;
					$view_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=index'.AMP.'entry_id='.$row['entry_id'];

					$row['comment_total'] = '<div class="lightLinks">('.$comment_count.')'.NBS.anchor($view_url, lang('view')).'</div>';
				}
			}

			$row = array_intersect_key($row, $columns);
		}

		// comes out with an added:
		// table_html
		// pagination_html

		return array(
			'rows'				=> $rows,
			'no_results'		=> lang('no_entries_matching_that_criteria'),
			'pagination'		=> array(
				'per_page' => $filter_data['perpage'],
				'total_rows' => $total
			),

			// used by index on non-ajax requests
			'filter_data'		=> $filter_data,
			'autosave_show'		=> $autosave_show,
			'autosave_array'	=> $autosave_array
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Create Return Filter
	 *
	 * Creates a properly format variable to pass in the url indicating the filter state
	 *
	 * @access	protected
	 * @param	array
	 * @return	string
	 */
	protected function _create_return_filter($filter_data)
	{
		$filter = '';
		$filters = array();
		$filter_keys = array('channel_id', 'cat_id', 'status', 'date_range', 'keywords', 'exact_match', 'search_in');

		foreach($filter_keys as $k)
		{
			if ( isset($filter_data[$k]) && $filter_data[$k] != '')
			{
				$filters[$k] = $filter_data[$k];
			}
		}

		if ( ! isset($filters['keywords']))
		{
			unset($filters['exact_match']);
			unset($filters['search_in']);
		}

		if ( ! empty($filters))
		{
			$filter = AMP.'filter='.base64_encode(serialize($filters));
		}

		return $filter;
	}

	// --------------------------------------------------------------------

	/**
	 * Multi edit form
	 */
	public function multi_edit_form()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! in_array($this->input->post('action'), array('edit', 'delete', 'add_categories', 'remove_categories')))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');

		if ( ! $this->input->post('toggle'))
		{
			return $this->index();
		}

		if ($this->input->post('action') == 'delete')
		{
			return $this->delete_entries_confirm();
		}

		$this->cp->add_js_script('ui', 'datepicker');

		// -----------------------------
		// Fetch the entry IDs
		// -----------------------------
		$entry_ids = $this->input->post('toggle');

		// Are there still any entry IDs at this point?
		// If not, we'll show an unauthorized message.

		if (count($entry_ids) == 0)
		{
			show_error(lang('unauthorized_to_edit'));
		}

		// -----------------------------
		// Build and run the query
		// -----------------------------

		$this->db->select('entry_id, exp_channel_titles.channel_id, author_id, title, url_title, entry_date, status, allow_comments, sticky, comment_system_enabled');
		$this->db->from('exp_channel_titles');
		$this->db->join('exp_channels', 'exp_channels.channel_id = exp_channel_titles.channel_id');
		$this->db->where_in('exp_channel_titles.entry_id', $entry_ids);
		$this->db->order_by("entry_date", "desc");

		$query = $this->db->get();

		// -----------------------------
		// Security check...
		//
		// Before we show anything we have to make sure that the user is allowed to
		// access the channel the entry is in, and if the user is trying
		// to edit an entry authored by someone else they are allowed to
		// -----------------------------
		$disallowed_ids = array();

		foreach ($query->result_array() as $row)
		{
			if ( ! in_array($row['channel_id'], $this->allowed_channels))
			{
				$disallowed_ids = $row['entry_id'];
			}

			if ($row['author_id'] != $this->session->userdata('member_id'))
			{
				if ( ! $this->cp->allowed_group('can_edit_other_entries'))
				{
					$disallowed_ids = $row['entry_id'];
				}
			}

			if (count($disallowed_ids) > 0)
			{
				$disallowed_ids = array_unique($disallowed_ids);
			}
		}

		// Are there disallowed posts?  If so, we have to remove them....
		if (count($disallowed_ids) > 0)
		{
			$new_ids = array_diff($entry_ids, $disallowed_ids);

			// After removing the disallowed entry IDs are there any left?
			if (count($new_ids) == 0)
			{
				show_error(lang('unauthorized_to_edit'));
			}

			unset($query);

			// Run the query one more time with the proper IDs.
			$this->db->select('entry_id, exp_channel_titles.channel_id, author_id, title, url_title, entry_date, status, allow_comments, sticky, comment_system_enabled');
			$this->db->from('exp_channel_titles');
			$this->db->join('exp_channels', 'exp_channels.channel_id = exp_channel_titles.channel_id');
			$this->db->where_in('exp_channel_titles.entry_id', $new_ids);
			$this->db->order_by("entry_date", "desc");

			$query = $this->db->get();
		}

		// -----------------------------
		// Adding/Removing of Categories Breaks Off to Their Own Function
		// -----------------------------

		if ($this->input->post('action') == 'add_categories')
		{
			return $this->multi_categories_edit('add', $query);
		}
		elseif ($this->input->post('action') == 'remove_categories')
		{
			return $this->multi_categories_edit('remove', $query);
		}

		// Fetch the channel preferences
		// We need these in order to fetch the status groups and options.

		$channel_ids = array();
		foreach ($query->result_array() as $row)
		{
			$channel_ids[] = $row['channel_id'];
		}

		$this->db->select('channel_id, status_group, deft_status');
		$this->db->where_in('channel_id', $channel_ids);

		$channel_query = $this->db->get('channels');

		// Fetch disallowed statuses
		$no_status_access = array();

		if ($this->session->userdata['group_id'] != 1)
		{
			$this->db->select('status_id');
			$this->db->from('exp_status_no_access');
			$this->db->where('member_group', $this->session->userdata('group_id'));

			$result = $this->db->get();

			foreach ($result->result_array() as $row)
			{
				$no_status_access[] = $row['status_id'];
			}
		}

		$vars['form_hidden'] = array();

		if (isset($_POST['pageurl']))
		{
			$vars['form_hidden']['redirect'] = $this->security->xss_clean($_POST['pageurl']);
		}

		// used in date field
		$this->javascript->output('
			date_obj = new Date();
			date_obj_hours = date_obj.getHours();
			date_obj_mins = date_obj.getMinutes();

			if (date_obj_mins < 10) { date_obj_mins = "0" + date_obj_mins; }

			if (date_obj_hours > 11) {
				date_obj_hours = date_obj_hours - 12;
				date_obj_am_pm = " PM";
			} else {

				// getHours reports midnight as 0
				if (date_obj_hours == 0)
				{
					date_obj_hours = 12;
				}

				date_obj_am_pm = " AM";
			}

			date_obj_time = " \'"+date_obj_hours+":"+date_obj_mins+date_obj_am_pm+"\'";
		');

		$vars['entries'] = $query;

		foreach ($query->result_array() as $row)
		{
			$entry_id = $row['entry_id'];
			$vars['form_hidden']["entry_id[$entry_id]"] = $entry_id;
			$vars['form_hidden']["channel_id[$entry_id]"] = $row['channel_id'];

			// Status pull-down menu
			$vars['entries_status'][$entry_id] = array();
			$vars['entries_selected'][$entry_id] = $row['status'];

			foreach ($channel_query->result_array() as $channel_row)
			{
				if ($channel_row['channel_id'] != $row['channel_id'])
				{
					continue;
				}

				$this->db->where('group_id', $channel_row['status_group']);
				$this->db->order_by('status_order');
				$status_query = $this->db->get('statuses');

				if ($status_query->num_rows() == 0)
				{
					// if there is no status group assigned, only Super Admins can create 'open' entries
					if ($this->session->userdata['group_id'] == 1)
					{
						$vars['entries_status'][$entry_id]['open'] = lang('open');
					}

					$vars['entries_status'][$entry_id]['closed'] = lang('closed');
				}
				else
				{
					$no_status_flag = TRUE;

					foreach ($status_query->result_array() as $status_row)
					{
						if (in_array($status_row['status_id'], $no_status_access))
						{
							continue;
						}

						$no_status_flag = FALSE;
						$status_name = ($status_row['status'] == 'open' OR $status_row['status'] == 'closed') ? lang($status_row['status']) : form_prep($status_row['status']);
						$vars['entries_status'][$entry_id][form_prep($status_row['status'])] = $status_name;
					}

					// Were there no statuses? If the current user is not allowed
					// to submit any statuses we'll set the default to closed
					if ($no_status_flag == TRUE)
					{
						$vars['entries_status'][$entry_id]['closed'] = lang('closed');
					}
				}
			}

			// Set up date js
			$this->javascript->output('
				$(".entry_date_'.$entry_id.'").datepicker({constrainInput: false, dateFormat: $.datepicker.W3C + date_obj_time, defaultDate: new Date("'.$this->localize->format_date('%D %M %d %Y', $row['entry_date']).'")});
			');

			// Sticky
			$vars['options'][$entry_id]['sticky'] = array();

			$vars['options'][$entry_id]['sticky']['name'] = 'sticky['.$row['entry_id'].']';
			$vars['options'][$entry_id]['sticky']['value'] = 'y';
			$vars['options'][$entry_id]['sticky']['checked'] = ($row['sticky'] == 'y') ? TRUE : FALSE;
			$vars['options'][$entry_id]['sticky']['style'] = 'width: auto!important;';

			// Allow Comments
			$vars['options'][$entry_id]['allow_comments'] = array();

			if ( ! isset($this->installed_modules['comment']) OR $row['comment_system_enabled'] == 'n')
			{
				$vars['form_hidden']["allow_comments[$entry_id]"] = $row['allow_comments'];
			}
			else
			{
				$vars['options'][$entry_id]['allow_comments']['name'] = 'allow_comments['.$row['entry_id'].']';
				$vars['options'][$entry_id]['allow_comments']['value'] = 'y';
				$vars['options'][$entry_id]['allow_comments']['checked'] = ($row['allow_comments'] == 'y') ? TRUE : FALSE;

				$vars['options'][$entry_id]['allow_comments']['style'] = 'width: auto!important;';
			}
		}

		// A bit of a breadcrumb override is needed
		$this->view->cp_breadcrumbs = array(
			BASE.AMP.'C=content' => lang('content'),
			BASE.AMP.'C=content_edit'=> lang('edit')
		);

		$this->view->cp_page_title = lang('multi_entry_editor');
		$this->cp->render('content/multi_edit', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * List Autosaved Entries
	 *
	 * @access public
	 */
	public function autosaved()
	{
		$this->prune_autosave();

		$this->load->library('table');

		$data['entries'] = array();
		$data['cp_page_title'] = lang('autosaved_entries');
		$data['table_headings'] = array(
			lang('autosaved'),
			lang('original'),
			lang('channel'),
			lang('discard_autosave')
		);

		$autosave_q = $this->db->select('cea.channel_id, cea.entry_id, cea.original_entry_id, cea.title, c.channel_title')
			->from('channel_entries_autosave as cea')
			->order_by('cea.original_entry_id', 'ASC')
			->where_in('cea.channel_id', $this->allowed_channels)
			->join('channels c', 'cea.channel_id = c.channel_id')
			->get();

		foreach($autosave_q->result() as $row)
		{
			$channel = $row->channel_id;
			$save_id = $row->entry_id;
			$orig_id = $row->original_entry_id;

			$data['entries'][] = array(
				anchor(
					BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel.AMP.'entry_id='.$save_id.AMP.'use_autosave=y',
					$row->title
				),
				$orig_id ? anchor(BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel.AMP.'entry_id='.$orig_id, $row->title) : '--',
				$row->channel_title,
				anchor(BASE.AMP.'C=content_edit'.AMP.'M=autosaved_discard'.AMP.'id='.$save_id, lang('delete'))
			);
		}

		$this->cp->set_breadcrumb($this->edit_base_url, lang('edit'));
		$this->cp->render('content/autosave', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Autosave Data
	 *
	 * @access public
	 */
	function autosaved_discard()
	{
		$id = $this->input->get_post('id');
		$qry = $this->db->select('author_id, channel_id')
			->order_by('original_entry_id', 'ASC')
			->get_where('channel_entries_autosave', array('entry_id' => $id));


		if ($qry->num_rows() != 1)
		{
			show_error(lang('unauthorized_access'));
		}

		$row = $qry->row();
		$can_delete = TRUE;

		// Check permissions

		if ($this->session->userdata('group_id') != 1)
		{
			if ( ! in_array($row['channel_id'], $this->allowed_channels))
			{
				$can_delete = FALSE;
			}
		}

		if ($row->author_id == $this->session->userdata('member_id'))
		{
			if ($this->session->userdata('can_delete_self_entries') != 'y')
			{
				$can_delete = FALSE;
			}
		}
		else
		{
			if ($this->session->userdata('can_delete_all_entries') != 'y')
			{
				$can_delete = FALSE;
			}
		}

		if ( ! $can_delete)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->db->where('entry_id', $id)->delete('channel_entries_autosave');
		$this->functions->redirect($this->edit_base_url.AMP.'M=autosaved');
	}

	// --------------------------------------------------------------------

	/**
	 * Prune Autosaved Data
	 *
	 * @access public
	 */
	function prune_autosave()
	{
		// default to pruning every 6 hours
		$autosave_prune = ($this->config->item('autosave_prune_hours') === FALSE) ?
										6 : $this->config->item('autosave_prune_hours');


		// Convert to seconds
		$autosave_prune = $autosave_prune * 60 * 60;

		$cutoff_date = time();
		$cutoff_date -= $autosave_prune;
		$cutoff_date = date("YmdHis", $cutoff_date);

		$this->db->where('edit_date <', $cutoff_date)->delete('channel_entries_autosave');
	}

	// --------------------------------------------------------------------

	/**
	 * Update multi entries
	 */
	public function update_multi_entries()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! is_array($_POST['entry_id']))
		{
			show_error(lang('unauthorized_access'));
		}

		/* -------------------------------------------
		/* 'update_multi_entries_start' hook.
		/*  - Perform additional actions before entries are updated
		*/
			$this->extensions->call('update_multi_entries_start');
			if ($this->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		$channel_ids = array();

		// Outside the for loop so seconds are consistent
		$edit_date = gmdate("YmdHis");

		foreach ($_POST['entry_id'] as $id)
		{
			$channel_id = $_POST['channel_id'][$id];

			// Remember channels we've touched so we can update stats at the end
			$channel_ids[] = intval($channel_id);

			$data = array(
				'title'				=> strip_tags($_POST['title'][$id]),
				'url_title'			=> $_POST['url_title'][$id],
				'entry_date'		=> $_POST['entry_date'][$id],
				'edit_date'			=> $edit_date,
				'status'			=> $_POST['status'][$id],
				'sticky'			=> (isset($_POST['sticky'][$id]) AND $_POST['sticky'][$id] == 'y') ? 'y' : 'n',
				'allow_comments'	=> (isset($_POST['allow_comments'][$id]) AND $_POST['allow_comments'][$id] == 'y') ? 'y' : 'n'
			);

			$error = array();

			// No entry title? Assign error.
			if ($data['title'] == "")
			{
				$error[] = lang('missing_title');
			}

			// Is the title unique?
			if ($data['title'] != '')
			{
				// Do we have a URL title?
				// If not, create one from the title

				$word_separator = $this->config->item('word_separator');

				if ($data['url_title'] == '')
				{
					$data['url_title'] = url_title($data['title'], $word_separator, TRUE);
				}
				else
				{
					$data['url_title'] = url_title($data['url_title'], $word_separator);
				}

				// Is the url_title a pure number?	If so we show an error.
				if (is_numeric($data['url_title']))
				{
					$error[] = lang('url_title_is_numeric');
				}

				// Is URL title unique?
				$unique = FALSE;
				$i = 0;

				while ($unique == FALSE)
				{
					$temp = ($i == 0) ? $data['url_title'] : $data['url_title'].$i;
					$i++;

					$sql = "SELECT count(*) AS count FROM exp_channel_titles WHERE url_title = '".$this->db->escape_str($temp)."' AND channel_id = '".$this->db->escape_str($channel_id)."'";

					if ($id != '')
					{
						$sql .= " AND entry_id != '".$this->db->escape_str($id)."'";
					}

					 $query = $this->db->query($sql);

					 if ($query->row('count')  == 0)
					 {
						$unique = TRUE;
					 }

					 // Safety
					 if ($i >= 50)
					 {
						$error[] = lang('url_title_not_unique');
						break;
					 }
				}

				$data['url_title'] = $temp;
			}

			// No date? Assign error.
			if ($data['entry_date'] == '')
			{
				$error[] = lang('missing_date');
			}

			// Convert the date to a Unix timestamp
			$data['entry_date'] = $this->localize->string_to_timestamp($data['entry_date']);

			if ( ! is_numeric($data['entry_date']))
			{
				if ($data['entry_date'] !== FALSE)
				{
					$error[] = $data['entry_date'];
				}
				else
				{
					$error[] = lang('invalid_date_formatting');
				}
			}

			// Do we have an error to display?
			 if (count($error) > 0)
			 {
				$msg = '';

				foreach($error as $val)
				{
					$msg .= '<div class="itemWrapper">'.$val.'</div>';
				}

				return show_error($msg);
			 }

			// Day, Month, and Year Fields
			$data['year']	= $this->localize->format_date('%Y', $data['entry_date']);
			$data['month']	= $this->localize->format_date('%m', $data['entry_date']);
			$data['day']	= $this->localize->format_date('%d', $data['entry_date']);

			// Update the entry
			$this->db->query($this->db->update_string('exp_channel_titles', $data, "entry_id = '$id'"));

			/* -------------------------------------------
			/* 'update_multi_entries_loop' hook.
			/*  - Perform additional actions after each entry is updated
			*/
				$this->extensions->call('update_multi_entries_loop', $id, $data);
				if ($this->extensions->end_script === TRUE) return;
			/*
			/* -------------------------------------------*/
		}

		// Clear caches if needed

		if ($this->config->item('new_posts_clear_caches') == 'y')
		{
			$this->functions->clear_caching('all', '');
		}
		else
		{
			$this->functions->clear_caching('sql', '');
		}


		// Update each modified channel's stats. Might want to get update_channel_stats()
		// to accept an array so we can avoid looping here.
		foreach(array_unique($channel_ids) as $id)
		{
			$this->stats->update_channel_stats($id);
		}


		$this->session->set_flashdata('message_success', lang('multi_entries_updated'));

		if (isset($_POST['redirect']) && ($redirect = base64_decode($this->security->xss_clean($_POST['redirect']))) !== FALSE)
		{
			$this->functions->redirect($this->security->xss_clean($redirect));
		}
		else
		{
			$this->functions->redirect($this->edit_base_url);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * multi categories edit form
	 */
	public function multi_categories_edit($type, $query)
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}

       	if ($query->num_rows() == 0)
        {
            show_error(lang('unauthorized_to_edit'));
        }

		/** -----------------------------
		/**  Fetch the cat_group
		/** -----------------------------*/

		/* Available from $query:	entry_id, channel_id, author_id, title, url_title,
									entry_date, status, allow_comments,
									sticky
		*/

		$sql = "SELECT DISTINCT cat_group FROM exp_channels WHERE channel_id IN(";

		$channel_ids = array();
		$entry_ids  = array();

		foreach ($query->result_array() as $row)
		{
			$channel_ids[] = $row['channel_id'];
			$entry_ids[] = $row['entry_id'];

			$sql .= $row['channel_id'].',';
		}

		$group_query = $this->db->query(substr($sql, 0, -1).')');

		$valid = 'n';

		if ($group_query->num_rows() > 0)
		{
			$valid = 'y';
			$last  = explode('|', $group_query->row('cat_group'));

			foreach($group_query->result_array() as $row)
			{
				$valid_cats = array_intersect($last, explode('|', $row['cat_group']));

				if (count($valid_cats) == 0)
				{
					$valid = 'n';
					break;
				}
			}
		}

		if ($valid == 'n')
		{
			show_error(lang('no_category_group_match'));
		}

		$this->api->instantiate('channel_categories');
		$this->api_channel_categories->category_tree(($cat_group = implode('|', $valid_cats)));
		//print_r($this->api_channel_categories->categories);
		$vars['cats'] = array();
		$vars['message']  = '';

		if (count($this->api_channel_categories->categories) == 0)
		{
			$vars['message'] = lang('no_categories');
		}
		else
		{
			foreach ($this->api_channel_categories->categories as $val)
			{
					$vars['cats'][$val['3']][] = $val;
			}
		}

		$vars['edit_categories_link'] = FALSE; //start off as false, meaning user does not have privs

		$link_info = $this->api_channel_categories->fetch_allowed_category_groups($cat_group);

		$links = FALSE;

		if ($link_info !== FALSE)
		{
			foreach ($link_info as $val)
			{
				$links[] = array('url' => BASE.AMP.'C=admin_content'.AMP.'M=category_editor'.AMP.'group_id='.$val['group_id'],
					'group_name' => $val['group_name']);
			}
		}

		$vars['edit_categories_link'] = $links;

		$this->cp->set_breadcrumb($this->edit_base_url, lang('edit'));

		$vars['form_hidden'] = array();
		$vars['form_hidden']['entry_ids'] = implode('|', $entry_ids);
		$vars['form_hidden']['type'] = $type;

		$vars['type'] = $type;

		$this->view->cp_page_title = lang('multi_entry_category_editor');

		$this->cp->render('content/multi_cat_edit', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 *  Update Multiple Entries with Categories
	 */
	public function multi_entry_category_update()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ($this->input->get_post('entry_ids') === FALSE OR $this->input->get_post('type') === FALSE)
		{
			show_error(lang('unauthorized_to_edit'));
		}

		if ($this->input->get_post('category') === FALSE OR ! is_array($_POST['category']) OR count($_POST['category']) == 0)
		{
			return $this->output->show_user_error('submission', lang('no_categories_selected'));
		}

		/** ---------------------------------
		/**	 Fetch categories
		/** ---------------------------------*/

		// We do this first so we can destroy the category index from
		// the $_POST array since we use a separate table to store categories in

		$this->api->instantiate('channel_categories');

		foreach ($_POST['category'] as $cat_id)
		{
			$this->api_channel_categories->cat_parents[] = $cat_id;
		}

		if ($this->api_channel_categories->assign_cat_parent == TRUE)
		{
			$this->api_channel_categories->fetch_category_parents($_POST['category']);
		}

		$this->api_channel_categories->cat_parents = array_unique($this->api_channel_categories->cat_parents);

		sort($this->api_channel_categories->cat_parents);

		unset($_POST['category']);

		$ids = array();

		foreach (explode('|', $_POST['entry_ids']) as $entry_id)
		{
			$ids[] = $this->db->escape_str($entry_id);
		}

		unset($_POST['entry_ids']);

		$entries_string = implode("','", $ids);

		/** -----------------------------
		/**	 Get Category Group IDs
		/** -----------------------------*/
		$query = $this->db->query("SELECT DISTINCT exp_channels.cat_group FROM exp_channels, exp_channel_titles
							 WHERE exp_channel_titles.channel_id = exp_channels.channel_id
							 AND exp_channel_titles.entry_id IN ('".$entries_string."')");

		$valid = 'n';

		if ($query->num_rows() > 0)
		{
			$valid = 'y';
			$last  = explode('|', $query->row('cat_group') );

			foreach($query->result_array() as $row)
			{
				$valid_cats = array_intersect($last, explode('|', $row['cat_group']));

				if (count($valid_cats) == 0)
				{
					$valid = 'n';
					break;
				}
			}
		}

		if ($valid == 'n')
		{
			return $this->output->show_user_error('submission', lang('no_category_group_match'));
		}

		/** -----------------------------
		/**	 Remove Valid Cats, Then Add...
		/** -----------------------------*/

		$valid_cat_ids = array();
		$query = $this->db->query("SELECT cat_id FROM exp_categories
							 WHERE group_id IN ('".implode("','", $valid_cats)."')
							 AND cat_id IN ('".implode("','", $this->api_channel_categories->cat_parents)."')");

		foreach($query->result_array() as $row)
		{
			$this->db->query("DELETE FROM exp_category_posts WHERE cat_id = ".$row['cat_id']." AND entry_id IN ('".$entries_string."')");
			$valid_cat_ids[] = $row['cat_id'];
		}

		if ($this->input->get_post('type') == 'add')
		{
			$insert_cats = array_intersect($this->api_channel_categories->cat_parents, $valid_cat_ids);
			// How brutish...
			foreach($ids as $id)
			{
				foreach($insert_cats as $val)
				{
					$this->db->query($this->db->insert_string('exp_category_posts', array('entry_id' => $id, 'cat_id' => $val)));
				}
			}
		}


		/** ---------------------------------
		/**	 Clear caches if needed
		/** ---------------------------------*/

		if ($this->config->item('new_posts_clear_caches') == 'y')
		{
			$this->functions->clear_caching('all');
		}
		else
		{
			$this->functions->clear_caching('sql');
		}

		$this->session->set_flashdata('message_success', lang('multi_entries_updated'));
		$this->functions->redirect($this->edit_base_url);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete entries confirm
	 */
	public function delete_entries_confirm()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_delete_self_entries') AND
			 ! $this->cp->allowed_group('can_delete_all_entries'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $this->input->post('toggle'))
		{
			redirect(BASE.'content_edit');
		}

		$damned = array();

		foreach ($_POST['toggle'] as $key => $val)
		{
			if ($val != '')
			{
				$damned[] = $val;
			}
		}

		// Pass the damned on for judgement
		$vars['damned'] = $damned;

		if (count($damned) == 1)
		{
			$vars['message'] = lang('delete_entry_confirm');
		}
		else
		{
			$vars['message'] = lang('delete_entries_confirm');
		}

		$vars['title_deleted_entry'] = '';

		// if it's just one entry, let's be kind and show a title
		if (count($_POST['toggle']) == 1)
		{
			$query = $this->db->query('SELECT title FROM exp_channel_titles WHERE entry_id = "'.$this->db->escape_str($_POST['toggle'][0]).'"');

			if ($query->num_rows() == 1)
			{
				$vars['title_deleted_entry'] = str_replace('%title', $query->row('title') , lang('entry_title_with_title'));
			}
		}

		$this->view->cp_page_title = lang('delete_confirm');

		$this->cp->render('content/delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete entries
	 */
	public function delete_entries()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_delete_self_entries') AND
			 ! $this->cp->allowed_group('can_delete_all_entries'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $this->input->post('delete'))
		{
			$this->session->set_flashdata('message_failure', lang('no_valid_selections'));
			$this->functions->redirect($this->edit_base_url);
		}

		/* -------------------------------------------
		/* 'delete_entries_start' hook.
		/*  - Perform actions prior to entry deletion / take over deletion
		*/
			$this->extensions->call('delete_entries_start');
			if ($this->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		$this->api->instantiate('channel_entries');
		$res = $this->api_channel_entries->delete_entry($this->input->post('delete'));

		if ($res === FALSE)
		{
			$this->session->set_flashdata('message_failure', lang('no_valid_selections'));
			$this->functions->redirect($this->edit_base_url);
		}

		// Return success message
		$this->session->set_flashdata('message_success', lang('entries_deleted'));
		$this->functions->redirect($this->edit_base_url);
	}

	// --------------------------------------------------------------------

	/**
	 * JavaScript filtering code
	 *
	 * This function writes some JavaScript functions that
	 * are used to switch the various pull-down menus in the
	 * EDIT page
	 *
	 * @access	protected
	 */
	protected function _filtering_menus($cat_form_array)
	{
		// In order to build our filtering options we need to gather
		// all the channels, categories and custom statuses

		$channel_array	= array();
		$status_array = array();

		$this->api->instantiate('channel_categories');

		if (count($this->allowed_channels) > 0)
		{
			// Fetch channel titles
			$this->api->instantiate('channel_structure');
			$channel_q = $this->api_channel_structure->get_channels();

			foreach ($channel_q->result_array() as $row)
			{
				$channel_array[$row['channel_id']] = array(str_replace('"','',$row['channel_title']), $row['cat_group'], $row['status_group'], $row['field_group']);
			}
		}

		/** -----------------------------
		/**  Entry Statuses
		/** -----------------------------*/

		$this->db->select('group_id, status');
		$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->order_by('status_order');
		$query = $this->db->get('statuses');

		foreach ($query->result_array() as $row)
		{
			$status_array[]  = array($row['group_id'], $row['status']);
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

			if (count($cat_form_array) > 0)
			{
				$last_group = 0;

				foreach ($cat_form_array as $k => $v)
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
				$statuses[] = array('open', lang('open'));
				$statuses[] = array('closed', lang('closed'));
			}

			$channel_info[$key]['statuses'] = $statuses;
		}

		$this->javascript->set_global('edit.channelInfo', $channel_info);
	}

	// --------------------------------------------------------------------

	/**
	 * Show entries with most recent comments
	 *
	 * Used by "recent entries" homepage link
	 *
	 * @return	void
	 */
	public function show_recent_entries()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');
		$this->load->model('channel_entries_model');
		$this->lang->loadfile('homepage');

		$this->view->cp_page_title = lang('most_recent_entries');

		$count = $this->input->get('count');
		$vars = array('entries' => array());

		$query = $this->channel_entries_model->get_recent_entries($count);

		if ($query && $query->num_rows() > 0)
		{
			$result = $query->result();
			foreach($result as $row)
			{
				$c_link = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=index'.AMP.'entry_id='.$row->entry_id;

				$link = BASE.AMP.'C=content_publish'.AMP.'M=view_entry'.AMP.'channel_id='.$row->channel_id.AMP.'entry_id='.$row->entry_id;

				if (($row->author_id == $this->session->userdata('member_id')) OR $this->cp->allowed_group('can_edit_other_entries'))
				{
					$link = BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$row->channel_id.AMP.'entry_id='.$row->entry_id;
				}

				$c_link = '<a href="'.$c_link.'">'.$row->comment_total.'</a>';
				$link = '<a href="'.$link.'">'.$row->title.'</a>';

				$vars['entries'][$link] = $c_link;
			}
		}

		$vars['no_result'] = lang('no_entries');
		$vars['left_column'] = lang('most_recent_entries');
		$vars['right_column'] = lang('comments');

		$this->cp->render('content/recent_list', $vars);
	}

	/**
	 * Edit Form Elements
	 *
	 * @access	protected
	 */
	protected function _edit_form($filter_data, $channels)
	{
		// Category Filtering Menus
		// ----------------------------------------------------------------

		// We need this for the filter, so grab it now
		$this->api->instantiate('channel_categories');
		$cat_form_array = $this->api_channel_categories->category_form_tree($this->nest_categories);

		$total_channels = count($this->allowed_channels);

		// If we have channels we'll write the JavaScript menu switching code
		if ($total_channels > 0)
		{
			$this->_filtering_menus($cat_form_array);
		}

		// Channel selection pull-down menu
		// ----------------------------------------------------------------

		$c_row = FALSE;
		$cat_group = '';
		$status_group = '';
		$channel_id = $this->input->get_post('channel_id');

		if (count($channels) == 1)
		{
			$c_row = current($channels);
		}
		elseif (isset($channels[$filter_data['channel_id']]))
		{
			$c_row = $channels[$filter_data['channel_id']];
		}

		if ($c_row)
		{
			$channel_id = $c_row->channel_id;
			$cat_group = $c_row->cat_group;
			$status_group = $c_row->status_group;
		}

		$vars['channel_selected'] = $this->input->get_post('channel_id');
		$vars['channel_select_options'] = array('null' => lang('filter_by_channel'));

		if (count($channels) > 1)
		{
			$vars['channel_select_options']['all'] = lang('all');
		}

		foreach ($channels as $id => $row)
		{
			$vars['channel_select_options'][$id] = $row->channel_title;
		}

		// Category pull-down menu
		// ----------------------------------------------------------------

		$vars['category_selected'] = $filter_data['cat_id'];
		$vars['category_select_options'][''] = lang('filter_by_category');

		if ($total_channels > 1)
		{
			$vars['category_select_options']['all'] = lang('all');
		}

		$vars['category_select_options']['none'] = lang('none');

		if ($cat_group != '')
		{
			foreach($cat_form_array as $key => $val)
			{
				if ( ! in_array($val['0'], explode('|',$cat_group)))
				{
					unset($cat_form_array[$key]);
				}
			}

			$i = 1;
			$new_array = array();

			foreach ($cat_form_array as $ckey => $cat)
			{
		    	if ($ckey-1 < 0 OR ! isset($cat_form_array[$ckey-1]))
			   	{
					$vars['category_select_options']['NULL_'.$i] = '-------';
		    	}

				$vars['category_select_options'][$cat['1']] = (str_replace("!-!","&nbsp;", $cat['2']));

		    	if (isset($cat_form_array[$ckey+1]) && $cat_form_array[$ckey+1]['0'] != $cat['0'])
		    	{
					$vars['category_select_options']['NULL_'.$i] = '-------';
				}

				$i++;
			}
		}


		// Status pull-down menu
		// ----------------------------------------------------------------

		$vars['status'] = $filter_data['status'];
		$vars['status_selected'] = $filter_data['status'];

		$vars['status_select_options'][''] = lang('filter_by_status');
		$vars['status_select_options']['all'] = lang('all');

		if ($status_group != '')
		{
			$status_q = $this->db->select('status')
				->where('group_id', $status_group)
				->order_by('status_order')
				->get('statuses');

			foreach ($status_q->result_array() as $row)
			{
				$status_name = ($row['status'] == 'closed' OR $row['status'] == 'open') ?  lang($row['status']) : $row['status'];
				$vars['status_select_options'][$row['status']] = $status_name;
			}
		}
		else
		{
			 $vars['status_select_options']['open'] = lang('open');
			 $vars['status_select_options']['closed'] = lang('closed');
		}


		// Date range pull-down menu
		$vars['date_selected'] = $filter_data['date_range'];
		$vars['date_select_options'] = array(
			''		=> lang('date_range'),
			'1'		=> lang('past_day'),
			'7'		=> lang('past_week'),
			'31'	=> lang('past_month'),
			'182'	=> lang('past_six_months'),
			'365'	=> lang('past_year'),
			'custom_date'	=> lang('any_date')
		);

		// Display order pull-down menu
		$vars['order_selected']	= $filter_data['order'];
		$vars['order_select_options'] = array(
			''		=> lang('order'),
			'asc'	=> lang('ascending'),
			'desc'	=> lang('descending'),
			'alpha'	=> lang('alpha')
		);

		// Per page pull-down menu
		$vars['perpage_selected'] = $filter_data['perpage'];
		$vars['perpage_select_options'] = array(
			'10'	=> '10 '.lang('results'),
			'25'	=> '25 '.lang('results'),
			'50'	=> '50 '.lang('results'),
			'75'	=> '75 '.lang('results'),
			'100'	=> '100 '.lang('results'),
			'150'	=> '150 '.lang('results')
		);

		// Search-in pull-down menu
		$vars['search_in_selected'] = $filter_data['search_in'];
		$vars['search_in_options'] = array(
			'title'			=> lang('title_only'),
			'body'			=> lang('title_and_body'),
			'everywhere'	=> lang('title_body_comments')
		);

		if ( ! isset($this->installed_modules['comment']))
		{
			unset($vars['search_in_options']['everywhere']);
		}

		// Keywords and exact match
		$vars['exact_match'] = $filter_data['exact_match'];
		$vars['keywords'] = array(
			'name' 		=> 'keywords',
			'value'		=> stripslashes($filter_data['keywords']),
			'id'		=> 'keywords',
			'maxlength'	=> 200
		);

		return $vars;
	}
}
// END CLASS

/* End of file edit.php */
/* Location: ./system/expressionengine/controllers/cp/edit.php */
