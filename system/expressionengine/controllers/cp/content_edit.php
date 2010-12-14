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
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Content_edit extends CI_Controller {

	private $nest_categories	= 'y';
	private $installed_modules	= FALSE;
	
	private $pipe_length			= 3;
	private $comment_chars			= 25;
	private $comment_leave_breaks	= 'n';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->installed_modules = $this->cp->get_installed_modules();
		
		$this->load->library('api');

		$this->load->model('channel_model');
		$this->load->model('channel_entries_model');
		$this->load->model('category_model');
		$this->load->model('status_model');
		$this->load->model('search_model');
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @return	void
	 */	
	public function index($channel_id = '', $message = '', $extra_sql = '', $search_url = '', 
						  $form_url = '', $action = '', $extra_fields_search='', $extra_fields_entries='', $heading='')
	{		
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$channel_id = '';
		$extra_sql = '';
		
		// $action, $extra_fields_*, and $heading are used by move_comments
		$vars['message'] = $message;
		$action = ($action != '') ? $action : $this->input->get_post('action');

		$this->load->library('pagination');
		$this->load->library('table');
		$this->load->helper(array('form', 'text', 'url', 'snippets'));
		$this->api->instantiate('channel_categories');

		// Load the search helper so we can filter the keywords
		$this->load->helper('search');

		$this->cp->set_variable('cp_page_title', $this->lang->line('edit'));

		$this->cp->add_js_script(array('plugin' => 'dataTables',
										'ui' => 'datepicker'));
		
		// Need perpage for js
		
		// Results per page pull-down menu
		if ( ! ($perpage = $this->input->get_post('perpage')))
		{
			$perpage = $this->input->cookie('perpage');
		}

		if ($perpage == '')
		{
			$perpage = 50;
		}

		$this->cp->add_js_script(array('file' => 'cp/content_edit'));

		$this->javascript->set_global('lang.selection_required', $this->lang->line('selection_required'));

		$cp_theme  = ( ! $this->session->userdata('cp_theme')) ? $this->config->item('cp_theme') : $this->session->userdata('cp_theme');

		if ($this->config->item('kill_all_humans') !== 'disable' && ((mt_rand(0, 5000) == 42 && $this->session->userdata['group_id'] == 1) OR $this->config->item('kill_all_humans')))
		{
			$this->load->helper('html');
			$image_properties = array(
				'src'		=> base_url()."themes/cp_themes/default/images/".strrev('tobor_rellik').".png",
				'alt'		=> '',
				'id'		=> 'extra',
				'width'		=> '228',
				'height'	=> '157',
				'style'		=> 'z-index: 1000; position: absolute; top: 49px; left: 790px'
			);

			$this->javascript->output(array(
				'$("#mainMenu").append(\''.img($image_properties).'\')',
				$this->javascript->animate("#extra", array("left"=>0), 4000, 'function(){$(\'#extra\').fadeOut(3000)}')
			));
		}

		// Fetch channel ID numbers assigned to the current user
		$allowed_channels = $this->functions->fetch_assigned_channels();

		if (empty($allowed_channels))
		{
			show_error($this->lang->line('no_channels'));
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
			$channel_id = $this->input->get_post('channel_id');
		}

		if ($channel_id == 'null' OR $channel_id === FALSE OR ! is_numeric($channel_id))
		{
			$channel_id = '';
		}

		$cat_group = '';
		
		// We want the filter to work based on both get and post

		$filter_data['channel_id'] = $channel_id;
		$filter_data['cat_id'] = $this->input->get_post('cat_id');

		$filter_data['status'] = $this->input->get_post('status');
		$filter_data['order']	= $this->input->get_post('order');
		$filter_data['date_range'] = $this->input->get_post('date_range');
		$total_channels = count($allowed_channels);

		$vars['status'] = $filter_data['status'];
		
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
		
		$filter_data['keywords'] = $keywords;

		// We need this for the filter, so grab it now
		$cat_form_array = $this->api_channel_categories->category_form_tree($this->nest_categories);
		
		// If we have channels we'll write the JavaScript menu switching code
		if ($total_channels > 0)
		{
			$this->filtering_menus($cat_form_array);
		}

		// If we're filtering using ajax, we redirect comment only searches
		// So- pass along the filter in the url
		if (isset($this->installed_modules['comment']))
		{
			$comment_url = '&ajax=true';

			$comment_url .= ($filter_data['channel_id'] != '') ? '&channel_id='.$filter_data['channel_id'] : '';
			$comment_url .= ($filter_data['keywords'] != '') ? '&keywords='.base64_encode($filter_data['keywords']) : '';
		}

		if (isset($this->installed_modules['comment']))
		{
			$table_columns = 9;
		}
		else
		{
			$table_columns = 8;
		}

		$this->javascript->set_global(array(
						'edit.pipe' 		=> $this->pipe_length,
						'edit.perPage'		=> $perpage,
						'edit.themeUrl'		=> $this->cp->cp_theme_url,
						'edit.tableColumns'	=> $table_columns,
						'lang.noEntries'	=> $this->lang->line('no_entries_matching_that_criteria')
					)
		);
	
		// Do we have a message to show?
		// Note: a message is displayed on this page after editing or submitting a new entry

		if ($this->input->get_post("U") == 'mu')
		{
			$vars['message'] = $this->lang->line('multi_entries_updated');
		}

		// Declare the "filtering" form

		$vars['search_form'] = ($search_url != '') ? $search_url : 'C=content_edit';

		// Channel selection pull-down menu
		// Fetch the names of all channels and write each one in an <option> field

		$fields = array('channel_title', 'channel_id', 'cat_group');
		$where = array();
		
		// If the user is restricted to specific channels, add that to the query
		
		if ($this->session->userdata['group_id'] != 1)
		{
			$where[] = array('channel_id' => $allowed_channels);
		}

		$query = $this->channel_model->get_channels($this->config->item('site_id'), $fields, $where);

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

		$vars['channel_selected'] = $this->input->get_post('channel_id');

		$vars['channel_select_options'] = array('null' => $this->lang->line('filter_by_channel'));

		if ($query->num_rows() > 1)
		{
			$vars['channel_select_options']['all'] = $this->lang->line('all');
		}

		foreach ($query->result_array() as $row)
		{
			$vars['channel_select_options'][$row['channel_id']] = $row['channel_title'];
		}

		// Category pull-down menu
		$vars['category_selected'] = $filter_data['cat_id'];

		$vars['category_select_options'][''] = $this->lang->line('filter_by_category');

		if ($total_channels > 1)
		{				
			$vars['category_select_options']['all'] = $this->lang->line('all');
		}

		$vars['category_select_options']['none'] = $this->lang->line('none');

		if ($cat_group != '')
		{
			foreach($cat_form_array as $key => $val)
			{
				if ( ! in_array($val['0'], explode('|',$cat_group)))
				{
					unset($cat_form_array[$key]);
				}
			}

			$i=1;
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
		$vars['status_selected'] = $filter_data['status'];

		$vars['status_select_options'][''] = $this->lang->line('filter_by_status');
		$vars['status_select_options']['all'] = $this->lang->line('all');
		
		$sel_1 = '';
		$sel_2 = '';

		if ($cat_group != '')
		{				
			  $sel_1 = ($filter_data['status'] == 'open')	? 1 : '';
			  $sel_2 = ($filter_data['status'] == 'closed') ? 1 : '';
		}

		if ($cat_group != '')
		{	 
			$rez = $this->db->query("SELECT status_group FROM exp_channels WHERE channel_id = '$channel_id'");									

			$query = $this->db->query("SELECT status FROM exp_statuses WHERE group_id = '".$this->db->escape_str($rez->row('status_group') )."' ORDER BY status_order");							

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$status_name = ($row['status'] == 'closed' OR $row['status'] == 'open') ?  $this->lang->line($row['status']) : $row['status'];
					$vars['status_select_options'][$row['status']] = $status_name;
				}
			}
		} 
		else
		{
			 $vars['status_select_options']['open'] = $this->lang->line('open');
			 $vars['status_select_options']['closed'] = $this->lang->line('closed');
		}

		// Date range pull-down menu
		$vars['date_selected'] = $filter_data['date_range'];

		$vars['date_select_options'][''] = $this->lang->line('date_range');
		$vars['date_select_options']['1'] = $this->lang->line('past_day');
		$vars['date_select_options']['7'] = $this->lang->line('past_week');
		$vars['date_select_options']['31'] = $this->lang->line('past_month');
		$vars['date_select_options']['182'] = $this->lang->line('past_six_months');
		$vars['date_select_options']['365'] = $this->lang->line('past_year');
		$vars['date_select_options']['custom_date'] = $this->lang->line('any_date');

		// Display order pull-down menu
		$vars['order_selected'] = $filter_data['order'];

		$vars['order_select_options'][''] = $this->lang->line('order');
		$vars['order_select_options']['asc'] = $this->lang->line('ascending');
		$vars['order_select_options']['desc'] = $this->lang->line('descending');
		$vars['order_select_options']['alpha'] = $this->lang->line('alpha');

		
		$filter_data['perpage'] = $perpage;

		$this->functions->set_cookie('perpage' , $perpage, 60*60*24*182);

		$vars['perpage_selected'] = $perpage;

		$vars['perpage_select_options']['10'] = '10 '.$this->lang->line('results');
		$vars['perpage_select_options']['25'] = '25 '.$this->lang->line('results');
		$vars['perpage_select_options']['50'] = '50 '.$this->lang->line('results');
		$vars['perpage_select_options']['75'] = '75 '.$this->lang->line('results');
		$vars['perpage_select_options']['100'] = '100 '.$this->lang->line('results');
		$vars['perpage_select_options']['150'] = '150 '.$this->lang->line('results');


		// Because of the auto convert we prepare a specific variable with the converted ascii
		// characters while leaving the $keywords variable intact for display and URL purposes
		$search_keywords = ($this->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities($keywords) : $keywords;
		
		$filter_data['search_keywords'] = $search_keywords;

		$vars['exact_match'] = $this->input->get_post('exact_match');
		$filter_data['exact_match'] = $vars['exact_match'];

		$vars['keywords'] = array(
									'name' 		=> 'keywords',
									'value'		=> stripslashes($keywords),
									'id'		=> 'keywords',
									'maxlength'	=> 200
								);

		$filter_data['search_in'] = ($this->input->get_post('search_in') != '') ? $this->input->get_post('search_in') : 'title';

		$vars['search_in_selected'] = $filter_data['search_in'];

		$vars['search_in_options']['title'] =  $this->lang->line('title_only');
		$vars['search_in_options']['body'] =  $this->lang->line('title_and_body');

		if (isset($this->installed_modules['comment']))
		{
			$vars['search_in_options']['everywhere'] =  $this->lang->line('title_body_comments');
		}

		
		$filter = $this->create_return_filter($filter_data);

		if ($search_url != '')
		{
			$pageurl = BASE.AMP.$search_url;
		}
		else
		{
			$pageurl = BASE.AMP.'C=content_edit';
		}

		// Get the current row number and add the LIMIT clause to the SQL query

		if ( ! $rownum = $this->input->get_post('rownum'))
		{
			$rownum = 0;
		}
		
		$filter_data['rownum'] = $rownum;
		$filter_data['perpage'] = $perpage;

		//	 Are there results?
		$filtered_entries = $this->search_model->get_filtered_entries($filter_data);

		// No result?  Show the "no results" message
		
		$vars['autosave_show'] = FALSE;

		$vars['total_count'] = $filtered_entries['total_count'];
		$pageurl .= $filtered_entries['pageurl'];

		if ($vars['total_count'] == 0)
		{
			$this->javascript->compile();
			$vars['heading'] = 'edit_channel_entries';
			$vars['search_form_hidden']  = array();
			$this->load->view('content/edit', $vars);
			return; 
		}

		$pageurl .= AMP.'perpage='.$perpage;
		$vars['form_hidden']['pageurl'] = base64_encode($pageurl); // for pagination

		// Full SQL query results

		$query_results =  $filtered_entries['results'];

		// --------------------------------------------
		//	 Fetch the channel information we need later
		// --------------------------------------------
		$sql = "SELECT channel_id, channel_name FROM exp_channels ";

		$sql .= "WHERE site_id = '".$this->db->escape_str($this->config->item('site_id'))."' ";

		$w_array = array();

		$result = $this->db->query($sql);

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
				 AND	exp_status_groups.site_id = '".$this->db->escape_str($this->config->item('site_id'))."' ";


		// Limit to channels assigned to user

		$sql .= " AND exp_channels.channel_id IN (";

		foreach ($allowed_channels as $val)
		{
			$sql .= "'".$val."',"; 
		}

		$sql = substr($sql, 0, -1).')';

		$result = $this->db->query($cql);

		$c_array = array();

		if ($result->num_rows() > 0)
		{			
			foreach ($result->result_array() as $rez)
			{			
				$c_array[$rez['channel_id'].'_'.$rez['status']] = str_replace('#', '', $rez['highlight']);
			}
		}

		// information for entries table
		
		$vars['entries_form'] = ($form_url != '') ? $form_url : 'C=content_edit'.AMP.'M=multi_edit_form';
		
		$vars['form_hidden'] = $extra_fields_entries;
		$vars['search_form_hidden'] = $extra_fields_search ? $extra_fields_search : array();

		// table headings
		$table_headings = array('#', lang('title'), lang('view'));

		// comments module installed?  If so, add it to the list of headings.
		if (isset($this->installed_modules['comment'])){
			$table_headings[] .= $this->lang->line('comments');
		}

		$table_headings = array_merge($table_headings, array(lang('author'), lang('date'), lang('channel'), lang('status'), form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"')));

		$vars['table_headings'] = $table_headings;

		// load the site's templates
		$templates = array();
		
		$tquery = $this->db->query("SELECT exp_template_groups.group_name, exp_templates.template_name, exp_templates.template_id
							FROM exp_template_groups, exp_templates
							WHERE exp_template_groups.group_id = exp_templates.group_id
							AND exp_templates.site_id = '".$this->db->escape_str($this->config->item('site_id'))."'");

		if ($tquery->num_rows() > 0)
		{
			foreach ($tquery->result_array() as $row)
			{
				$templates[$row['template_id']] = $row['group_name'].'/'.$row['template_name'];
			}
		}

		// Grab all autosaved entries
		$this->prune_autosave();
		
		$this->db->select('entry_id, original_entry_id, channel_id, title, author_id, status, entry_date, dst_enabled, comment_total');
		$autosave = $this->db->get('channel_entries_autosave');
		
		$autosave_array = array();
		
		foreach ($autosave->result() as $entry)
		{
			if ($entry->original_entry_id)
			{
				$autosave_array[] = $entry->original_entry_id;
			}
		}

		$vars['autosave_show'] = ($autosave->num_rows() > 0) ? TRUE : FALSE;

		// Loop through the main query result and set up data structure for table

		$vars['entries'] = array();
		
		$comment_totals = array();

		$i = 0;

		foreach($query_results as $row)
		{
			// Entry ID number
			$id_column = $i++;
			
			if ( ! isset($row['original_entry_id']))
			{
				$vars['entries'][$id_column][] = $row['entry_id'];
			}
			elseif ($row['original_entry_id'] == 0)
			{
				$row['entry_id'] = 0;
				$vars['entries'][$id_column][] = $row['original_entry_id'];
			}

			// Channel entry title (view entry)			
			$output = anchor(BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$row['channel_id'].AMP.'entry_id='.$id_column.$filter, $row['title']);
			
			$output .= isset($autosave_array[$row['entry_id']]) ? NBS.required() : '';
			$vars['entries'][$id_column][] = $output;

			// "View"
			if ($row['live_look_template'] != 0 && isset($templates[$row['live_look_template']]))
			{
				$qm = ($this->config->item('force_query_string') == 'y') ? '' : '?';

				$url = $this->functions->create_url($templates[$row['live_look_template']].'/'.$id_column);

				$view_link = anchor($this->functions->fetch_site_index().QUERY_MARKER.'URL='.$url,
									$this->lang->line('view'));
			}
			else
			{
					$view_link = '--';
			}

			$vars['entries'][$id_column][] = $view_link;


			// Comment count
			$show_link = TRUE;

			if ($row['author_id'] == $this->session->userdata('member_id'))
			{
				if ( ! $this->cp->allowed_group('can_edit_own_comments') AND 
					 ! $this->cp->allowed_group('can_delete_own_comments') AND 
					 ! $this->cp->allowed_group('can_moderate_comments'))
				{
					$show_link = FALSE;
				}
			}
			else
			{
				if ( ! $this->cp->allowed_group('can_edit_all_comments') AND 
					 ! $this->cp->allowed_group('can_delete_all_comments') AND 
					 ! $this->cp->allowed_group('can_moderate_comments'))
				{
					$show_link = FALSE;
				}
			}
			
			if (isset($this->cp->installed_modules['comment']))
			{
				$view_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment	'.AMP.'method=index'.AMP.'entry_id='.$id_column;
				
				$view_link = ($show_link === FALSE) ? '<div class="lightLinks">--</div>' : 
					'<div class="lightLinks">(0)'.NBS.anchor($view_url, $this->lang->line('view')).'</div>';
				
				$vars['entries'][$id_column][] = $view_link;

				// Setup an array of entry IDs here so we can do an aggregate query to
				// get an accurate count of total comments for each entry.
				$comment_totals[] = $id_column;

			}			
			
			
			// Username
			$name = ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
			$vars['entries'][$id_column][] = mailto($row['email'], $name);

			// Date
			$date_fmt = ($this->session->userdata('time_format') != '') ? $this->session->userdata('time_format') : $this->config->item('time_format');

			if ($date_fmt == 'us')
			{
				$datestr = '%m/%d/%y %h:%i %a';
			}
			else
			{
				$datestr = '%Y-%m-%d %H:%i';
			}

			$vars['entries'][$id_column][] = $this->localize->decode_date($datestr, $row['entry_date'], TRUE);

			// Channel
			$vars['entries'][$id_column][] = (isset($w_array[$row['channel_id']])) ? '<div class="smallNoWrap">'. $w_array[$row['channel_id']].'</div>' : '';

			// Status
			$status_name = ($row['status'] == 'open' OR $row['status'] == 'closed') ? $this->lang->line($row['status']) : $row['status'];

			$color_info = '';

			if (isset($c_array[$row['channel_id'].'_'.$row['status']]) AND $c_array[$row['channel_id'].'_'.$row['status']] != '')
			{			
				$color = $c_array[$row['channel_id'].'_'.$row['status']];
				$prefix = (is_array($colors) AND ! array_key_exists(strtolower($color), $colors)) ? '#' : '';

				// There are custom colours, override the class above
				$color_info = 'style="color:'.$prefix.$color.';"';
			}

			$vars['entries'][$id_column][] = '<span class="status_'.$row['status'].'"'.$color_info.'>'.$status_name.'</span>';

			// Delete checkbox
			$vars['entries'][$id_column][] = form_checkbox('toggle[]', $id_column, '', ' class="toggle" id="delete_box_'.$id_column.'"');
		
		}

		if (isset($this->cp->installed_modules['comment']))
		{
			// Get the total number of comments for each entry
			$this->db->select('comment_id, entry_id, channel_id, COUNT(*) as count');
			$this->db->where_in('entry_id', $comment_totals);
			$this->db->group_by('entry_id');
			$comment_query = $this->db->get('comments');

			foreach ($comment_query->result() as $row)
 			{
				if ($show_link !== FALSE)
				{
					$view_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=index'.AMP.'entry_id='.$row->entry_id;
				}
					
				$view_link = ($show_link === FALSE) ? '<div class="lightLinks">--</div>' : 
				'<div class="lightLinks">('.$row->count.')'.NBS.anchor($view_url, $this->lang->line('view')).'</div>';
				
				$vars['entries'][$row->entry_id][3] = $view_link;
 			}
		}

		// Pass the relevant data to the paginate class
		$config['base_url'] = $pageurl;
		$config['total_rows'] = $vars['total_count'];
		$config['per_page'] = $perpage;
		$config['page_query_string'] = TRUE;
		$config['query_string_segment'] = 'rownum';
		$config['full_tag_open'] = '<p id="paginationLinks">';
		$config['full_tag_close'] = '</p>';
		$config['prev_link'] = '<img src="'.$this->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />';
		$config['next_link'] = '<img src="'.$this->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />';
		$config['first_link'] = '<img src="'.$this->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />';
		$config['last_link'] = '<img src="'.$this->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />';

		$this->pagination->initialize($config);

		$vars['pagination'] = $this->pagination->create_links();
		$vars['heading'] = $heading ? $heading : 'edit_channel_entries';
		
		$vars['action_options'] = array();
	
		if (is_array($action))
		{
			$vars['action_options'] = $action;
		}
		elseif ($action == '' OR  ! $this->input->post('toggle'))
		{
			$vars['action_options'] = array(
												'edit'				=> $this->lang->line('edit_selected'),
												'delete'			=> $this->lang->line('delete_selected'),
												'------'			=> '------',
												'add_categories'	=> $this->lang->line('add_categories'),
												'remove_categories'	=> $this->lang->line('remove_categories')
											);
		}

		$this->javascript->set_global('autosave_map', $autosave_array);

		$this->javascript->compile();
		$this->load->view('content/edit', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Edit ajax filter
	 */
	public function edit_ajax_filter()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->output->enable_profiler(FALSE);
		$this->load->helper(array('form', 'text', 'url', 'snippets'));
		
		$filter_data['channel_id'] = ($this->input->get_post('channel_id') != 'null' && $this->input->get_post('channel_id') != 'all') ? $this->input->get_post('channel_id') : '';
		$filter_data['cat_id'] = ($this->input->get_post('cat_id') != 'all') ? $this->input->get_post('cat_id') : '';

		$filter_data['status'] = ($this->input->get_post('status') != 'all') ? $this->input->get_post('status') : '';
		$filter_data['date_range'] = $this->input->get_post('date_range');	
		$filter_data['author_id'] = $this->input->get_post('author_id');	
	
		$filter_data['keywords'] = ($this->input->get_post('keywords')) ? $this->input->get_post('keywords') : '';
		$filter_data['search_in'] = ($this->input->get_post('search_in') != '') ? $this->input->get_post('search_in') : 'title';
		$filter_data['exact_match'] = $this->input->get_post('exact_match');

		// Because of the auto convert we prepare a specific variable with the converted ascii
		// characters while leaving the $keywords variable intact for display and URL purposes
		$search_keywords = ($this->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities($filter_data['keywords']) : $filter_data['keywords'];

		$filter_data['search_keywords'] = $search_keywords;

		$filter = $this->create_return_filter($filter_data);
		
		// Apply only to comments- not part of edit page filter
		$filter_data['entry_id'] = $this->input->get_post('entry_id');
		$filter_data['comment_id'] = $this->input->get_post('comment_id');
		$filter_data['id_array'] = ($this->input->get_post('id_array')) ? explode($this->input->get_post('id_array')) : array();		
	
		$filter_data['validate'] = ($this->input->get_post('validate') == 'true') ? TRUE : FALSE;
		$validate = $filter_data['validate'];


		$perpage = $this->input->get_post('iDisplayLength');
		$offset = ($this->input->get_post('iDisplayStart')) ? $this->input->get_post('iDisplayStart') : 0; // Display start point		
		
		$filter_data['perpage'] = $perpage;
		$filter_data['rownum'] = $offset;

		$sEcho = $this->input->get_post('sEcho');	
		
		if (isset($this->installed_modules['comment']))
		{
			$col_map = array('exp_channel_titles.entry_id', 'title', 'view', 'comment_total', 'screen_name', 'entry_date', 'channel_name', 'status', '');
		}
		else
		{
			$col_map = array('exp_channel_titles.entry_id', 'title', 'view', 'screen_name', 'entry_date', 'channel_name', 'status', '');
		}

		/* Ordering */
		$order = array();
		
		if ($this->input->get('iSortCol_0') !== FALSE)
		{
			for ( $i=0; $i < $this->input->get('iSortingCols'); $i++ )
			{
				if (isset($col_map[$this->input->get('iSortCol_'.$i)]))
				{
					$order[$col_map[$this->input->get('iSortCol_'.$i)]] = ($this->input->get('sSortDir_'.$i) == 'asc') ? 'asc' : 'desc';
				}
			}
		}
		
		$filtered_entries = $this->search_model->get_filtered_entries($filter_data, $order);
		
		// No result?  Show the "no results" message
		$total = $filtered_entries['total_count'];
		$query_results = $filtered_entries['results'];

		$j_response['sEcho'] = $sEcho;
		$j_response['iTotalRecords'] = $this->db->count_all('channel_titles');  
		$j_response['iTotalDisplayRecords'] = $total;		
		
		
		// --------------------------------------------
		//	 Fetch the channel information we need later
		// --------------------------------------------
		
		// Fetch channel ID numbers assigned to the current user
		$allowed_channels = $this->functions->fetch_assigned_channels();

		if (empty($allowed_channels))
		{
			show_error($this->lang->line('no_channels'));
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

		$sql = "SELECT channel_id, channel_name FROM exp_channels ";

		$sql .= "WHERE site_id = '".$this->db->escape_str($this->config->item('site_id'))."' ";

		$w_array = array();

		$result = $this->db->query($sql);

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
				 AND	exp_status_groups.site_id = '".$this->db->escape_str($this->config->item('site_id'))."' ";


		// Limit to channels assigned to user

		$sql .= " AND exp_channels.channel_id IN (";

		foreach ($allowed_channels as $val)
		{
			$sql .= "'".$val."',"; 
		}

		$sql = substr($sql, 0, -1).')';

		$result = $this->db->query($cql);

		$c_array = array();

		if ($result->num_rows() > 0)
		{			
			foreach ($result->result_array() as $rez)
			{			
				$c_array[$rez['channel_id'].'_'.$rez['status']] = str_replace('#', '', $rez['highlight']);
			}
		}


		// load the site's templates
		$templates = array();

		$tquery = $this->db->query("SELECT exp_template_groups.group_name, exp_templates.template_name, exp_templates.template_id
							FROM exp_template_groups, exp_templates
							WHERE exp_template_groups.group_id = exp_templates.group_id
							AND exp_templates.site_id = '".$this->db->escape_str($this->config->item('site_id'))."'");

		if ($tquery->num_rows() > 0)
		{
			foreach ($tquery->result_array() as $row)
			{
				$templates[$row['template_id']] = $row['group_name'].'/'.$row['template_name'];
			}
		}

		$tdata = array();
		$i = 0;
		
		// Grab all autosaved entries
		$this->db->select('original_entry_id');
		$autosave = $this->db->get('channel_entries_autosave');
		$autosave_array = array();

		foreach ($autosave->result() as $entry)
		{
			$autosave_array[] = $entry->original_entry_id;
		}

		foreach($query_results as $row)
		{
			$m[] = $row['entry_id'];
			$title_output = anchor(BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$row['channel_id'].AMP.'entry_id='.$row['entry_id'].$filter, $row['title']);
			$title_output .= (in_array($row['entry_id'], $autosave_array)) ? NBS.required() : '';

			$m[] = $title_output;

			// "View"
			if ($row['live_look_template'] != 0 && isset($templates[$row['live_look_template']]))
			{
				$qm = ($this->config->item('force_query_string') == 'y') ? '' : '?';

				$url = $this->functions->create_url($templates[$row['live_look_template']].'/'.$row['entry_id']);
				$view_link = anchor($this->functions->fetch_site_index().$qm.'URL='.$url,
									$this->lang->line('view'));
			}
			else
			{
					$view_link = '--';
			}
			
			$m[] = $view_link; // Add live look template
			
			// Comment count
			$show_link = TRUE;

			if ($row['author_id'] == $this->session->userdata('member_id'))
			{
				if ( ! $this->cp->allowed_group('can_edit_own_comments') AND 
					 ! $this->cp->allowed_group('can_delete_own_comments') AND 
					 ! $this->cp->allowed_group('can_moderate_comments'))
				{
					$show_link = FALSE;
				}
			}
			else
			{
				if ( ! $this->cp->allowed_group('can_edit_all_comments') AND 
					 ! $this->cp->allowed_group('can_delete_all_comments') AND 
					 ! $this->cp->allowed_group('can_moderate_comments'))
				{
					$show_link = FALSE;
				}
			}

			if ( isset($this->installed_modules['comment']))
			{
				//	Comment Link
				if ($show_link !== FALSE)
				{
					$res = $this->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$row['entry_id']."'");$this->db->query_count--;
					$view_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=index'.AMP.'entry_id='.$row['entry_id'];
				}

				$view_link = ($show_link == FALSE) ? '<div class="lightLinks">--</div>' : '<div class="lightLinks">('.$res->row('count').')'.NBS.anchor($view_url, $this->lang->line('view')).'</div>';

				$m[] = $view_link;
			}

			// Username
			$name = ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
			$m[] = mailto($row['email'], $name);

			// Date
			$date_fmt = ($this->session->userdata('time_format') != '') ? $this->session->userdata('time_format') : $this->config->item('time_format');

			if ($date_fmt == 'us')
			{
				$datestr = '%m/%d/%y %h:%i %a';
			}
			else
			{
				$datestr = '%Y-%m-%d %H:%i';
			}
			
			$m[] = $this->localize->decode_date($datestr, $row['entry_date'], TRUE);

			// Channel
			$m[] = (isset($w_array[$row['channel_id']])) ? '<div class="smallNoWrap">'.$w_array[$row['channel_id']].'</div>' : '';

			// Status
			$status_name = ($row['status'] == 'open' OR $row['status'] == 'closed') ? $this->lang->line($row['status']) : $row['status'];

			$color_info = '';

			if (isset($c_array[$row['channel_id'].'_'.$row['status']]) AND $c_array[$row['channel_id'].'_'.$row['status']] != '')
			{			
				$color = $c_array[$row['channel_id'].'_'.$row['status']];
				$prefix = (is_array($colors) AND ! array_key_exists(strtolower($color), $colors)) ? '#' : '';

				// There are custom colours, override the class above
				$color_info = 'style="color:'.$prefix.$color.';"';
			}

			$m[] = '<span class="status_'.$row['status'].'"'.$color_info.'>'.$status_name.'</span>';

			// Delete checkbox
			$m[] = form_checkbox('toggle[]', $row['entry_id'], '', ' class="toggle" id="delete_box_'.$row['entry_id'].'"');

			$tdata[$i] = $m;
			$i++;
			unset($m);

		} // End foreach
		

		$j_response['aaData'] = $tdata;	

		$this->output->send_ajax_response($j_response);
	}
	

	// --------------------------------------------------------------------
	
	/**
	 * Create Return Filter
	 *
	 * Creates a properly format variable to pass in the url indicating the filter state
	 *
	 * @param	array
	 * @return	string
	 */
	public function create_return_filter($filter_data)
	{
		
		$filters = array();
		$filter = '';
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
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! in_array($this->input->post('action'), array('edit', 'delete', 'add_categories', 'remove_categories')))
		{
			show_error($this->lang->line('unauthorized_access'));
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

		$this->load->helper('form');
		
		$this->cp->add_js_script(array('ui' => 'datepicker'));

		// -----------------------------
		// Fetch the entry IDs 
		// -----------------------------
		$entry_ids = $this->input->post('toggle');

		// Are there still any entry IDs at this point?
		// If not, we'll show an unauthorized message.

		if (count($entry_ids) == 0)
		{
			show_error($this->lang->line('unauthorized_to_edit'));
		}

		// -----------------------------
		// Build and run the query
		// -----------------------------

		$this->db->select('entry_id, exp_channel_titles.channel_id, author_id, title, url_title, entry_date, dst_enabled, status, allow_comments, sticky, comment_system_enabled');
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
		$assigned_channels = $this->functions->fetch_assigned_channels();

		foreach ($query->result_array() as $row)
		{
			if ( ! in_array($row['channel_id'], $assigned_channels))
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
				show_error($this->lang->line('unauthorized_to_edit'));
			}

			unset($query);

			// Run the query one more time with the proper IDs.
			$this->db->select('entry_id, exp_channel_titles.channel_id, author_id, title, url_title, entry_date, dst_enabled, status, allow_comments, sticky, comment_system_enabled');
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
		$this->db->from('exp_channels');
		$this->db->where_in('channel_id', $channel_ids);

		$channel_query = $this->db->get();

		// Fetch disallowed statuses
		$no_status_access = array();

		if ($this->session->userdata['group_id'] != 1)
		{
			$this->db->select('status_id');
			$this->db->from('exp_status_no_access');
			$this->db->where('member_group', $this->session->userdata('group_id'));			

			$result = $this->db->get();
			
			if ($result->num_rows() > 0)
			{
				foreach ($result->result_array() as $row)
				{
					$no_status_access[] = $row['status_id'];
				}
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
						$vars['entries_status'][$entry_id]['open'] = $this->lang->line('open');
					}
					
					$vars['entries_status'][$entry_id]['closed'] = $this->lang->line('closed');
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
						$status_name = ($status_row['status'] == 'open' OR $status_row['status'] == 'closed') ? $this->lang->line($status_row['status']) : form_prep($status_row['status']);
						$vars['entries_status'][$entry_id][form_prep($status_row['status'])] = $status_name;
					}

					// Were there no statuses? If the current user is not allowed 
					// to submit any statuses we'll set the default to closed
					if ($no_status_flag == TRUE)
					{
						$vars['entries_status'][$entry_id]['closed'] = $this->lang->line('closed');
					}
				}
			}

			// Set up date js
			$this->javascript->output('
				$(".entry_date_'.$entry_id.'").datepicker({dateFormat: $.datepicker.W3C + date_obj_time, defaultDate: new Date('.($this->localize->set_localized_time($row['entry_date']) * 1000).')});
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

		$this->javascript->compile();

		$this->cp->set_variable('cp_page_title', $this->lang->line('multi_entry_editor'));
		// A bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=content' => $this->lang->line('content'),
			BASE.AMP.'C=content_edit'=> $this->lang->line('edit')
		));

		$this->load->view('content/multi_edit', $vars);
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
		
		$data['cp_page_title'] = lang('autosaved_entries');
		
		$data['table_headings'] = array(
			lang('autosaved'),
			lang('original'),
			lang('channel'),
			lang('discard_autosave')
		);
		
		$this->cp->set_breadcrumb(BASE.AMP.'C=content_edit', lang('edit'));
		
		$allowed_channels = $this->functions->fetch_assigned_channels();
		
		$data['entries'] = array();
		
		$qry = $this->db->select('channel_id, entry_id, original_entry_id, title')
						->order_by('original_entry_id', 'ASC')
						->where_in('channel_id', $allowed_channels)
						->get('channel_entries_autosave');
				
		foreach($qry->result() as $row)
		{
			$channel = $row->channel_id;
			$save_id = $row->entry_id;
			$orig_id = $row->original_entry_id;
			
			$data['entries'][] = array(
				anchor(BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel.AMP.'entry_id='.$save_id.AMP.'use_autosave=y', $row->title),
				$orig_id ? anchor(BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel.AMP.'entry_id='.$orig_id, $row->title) : '--',
				'Blog',
				anchor(BASE.AMP.'C=content_edit'.AMP.'M=autosaved_discard'.AMP.'id='.$save_id, lang('delete'))
			);
		}
		
		$this->load->view('content/autosave', $data);
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
		$allowed_channels = $this->functions->fetch_assigned_channels();
		
		if ($this->session->userdata('group_id') != 1)
		{
			if ( ! in_array($row['channel_id'], $allowed_channels))
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
		$this->functions->redirect(BASE.AMP.'C=content_edit'.AMP.'M=autosaved');
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
			show_error($this->lang->line('unauthorized_access'));
		}
	
		if ( ! is_array($_POST['entry_id']))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		/* -------------------------------------------
		/* 'update_multi_entries_start' hook.
		/*  - Perform additional actions before entries are updated
		*/
			$edata = $this->extensions->call('update_multi_entries_start');
			if ($this->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		$channel_ids = array();

		foreach ($_POST['entry_id'] as $id)
		{
			$channel_id = $_POST['channel_id'][$id];
			
			// Remember channels we've touched so we can update stats at the end
			$channel_ids[] = intval($channel_id);
		
			$data = array(
							'title'				=> strip_tags($_POST['title'][$id]),
							'url_title'			=> $_POST['url_title'][$id],
							'entry_date'		=> $_POST['entry_date'][$id],
							'status'			=> $_POST['status'][$id],
							'sticky'			=> (isset($_POST['sticky'][$id]) AND $_POST['sticky'][$id] == 'y') ? 'y' : 'n',
							'allow_comments'	=> (isset($_POST['allow_comments'][$id]) AND $_POST['allow_comments'][$id] == 'y') ? 'y' : 'n'
							);

			$error = array();

			// No entry title? Assign error.
			if ($data['title'] == "")
			{
				$error[] = $this->lang->line('missing_title');
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
					$error[] = $this->lang->line('url_title_is_numeric');
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
						$error[] = $this->lang->line('url_title_not_unique');
						break;
					 }
				}

				$data['url_title'] = $temp;
			}

			// No date? Assign error.
			if ($data['entry_date'] == '')
			{
				$error[] = $this->lang->line('missing_date');
			}

			// Convert the date to a Unix timestamp
			$data['entry_date'] = $this->localize->convert_human_date_to_gmt($data['entry_date']);

			if ( ! is_numeric($data['entry_date'])) 
			{ 
				// Localize::convert_human_date_to_gmt() returns verbose errors
				if ($data['entry_date'] !== FALSE)
				{
					$error[] = $data['entry_date'];
				}
				else
				{
					$error[] = $this->lang->line('invalid_date_formatting');
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
			$data['year']	= date('Y', $data['entry_date']);
			$data['month']	= date('m', $data['entry_date']);
			$data['day']	= date('d', $data['entry_date']);

			// Update the entry
			$this->db->query($this->db->update_string('exp_channel_titles', $data, "entry_id = '$id'"));
			
			/* -------------------------------------------
			/* 'update_multi_entries_loop' hook.
			/*  - Perform additional actions after each entry is updated
			*/
				$edata = $this->extensions->call('update_multi_entries_loop', $id, $data);
				if ($this->extensions->end_script === TRUE) return;
			/*
			/* -------------------------------------------*/
		}

		// Clear caches if needed

		$entry_ids = "'";

		foreach($_POST['entry_id'] as $id)
		{
			$entry_ids .= $this->db->escape_str($id)."', '";
		}

		$entry_ids = substr($entry_ids, 0, -3);

		$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_relationships
							WHERE rel_parent_id IN ({$entry_ids})
							OR rel_child_id IN ({$entry_ids})");

		$clear_rel = ($query->row('count')	> 0) ? TRUE : FALSE;

		if ($this->config->item('new_posts_clear_caches') == 'y')
		{
			$this->functions->clear_caching('all', '', $clear_rel);
		}
		else
		{
			$this->functions->clear_caching('sql', '', $clear_rel);
		}


		// Update each modified channel's stats. Might want to get update_channel_stats()
		// to accept an array so we can avoid looping here.
		foreach(array_unique($channel_ids) as $id)
		{
			$this->stats->update_channel_stats($id);			
		}


		$this->session->set_flashdata('message_success', $this->lang->line('multi_entries_updated'));

		if (isset($_POST['redirect']) && ($redirect = base64_decode($this->security->xss_clean($_POST['redirect']))) !== FALSE)
		{
			$this->functions->redirect($this->security->xss_clean($redirect));
		}
		else
		{
			$this->functions->redirect(BASE.AMP.'C=content_edit');
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
			show_error($this->lang->line('unauthorized_access'));
		}
        
       	if ($query->num_rows() == 0)
        {
            show_error($this->lang->line('unauthorized_to_edit'));
        }

		$this->load->helper('form');
        
		/** -----------------------------
		/**  Fetch the cat_group
		/** -----------------------------*/
		
		/* Available from $query:	entry_id, channel_id, author_id, title, url_title, 
									entry_date, dst_enabled, status, allow_comments, 
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
			show_error($this->lang->line('no_category_group_match'));
		}
		
		$this->api->instantiate('channel_categories');
		$this->api_channel_categories->category_tree(($cat_group = implode('|', $valid_cats)));
		//print_r($this->api_channel_categories->categories);
		$vars['cats'] = array();
		$vars['message']  = '';
		
		if (count($this->api_channel_categories->categories) == 0)
		{  
			$vars['message'] = $this->lang->line('no_categories');
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

		$this->cp->set_breadcrumb(BASE.AMP.'C=content_edit', $this->lang->line('edit'));

		$vars['form_hidden'] = array();
		$vars['form_hidden']['entry_ids'] = implode('|', $entry_ids);
		$vars['form_hidden']['type'] = $type;

		$vars['type'] = $type;
	
		$this->cp->set_variable('cp_page_title', $this->lang->line('multi_entry_category_editor'));

		$this->javascript->compile();
		$this->load->view('content/multi_cat_edit', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 *  Update Multiple Entries with Categories
	 */
	public function multi_entry_category_update()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ($this->input->get_post('entry_ids') === FALSE OR $this->input->get_post('type') === FALSE)
		{
			show_error($this->lang->line('unauthorized_to_edit'));
		}

		if ($this->input->get_post('category') === FALSE OR ! is_array($_POST['category']) OR count($_POST['category']) == 0)
		{
			return $this->output->show_user_error('submission', $this->lang->line('no_categories_selected'));
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
			return $this->output->show_user_error('submission', $this->lang->line('no_category_group_match'));
		}

		/** -----------------------------
		/**	 Remove Valid Cats, Then Add...
		/** -----------------------------*/

		$valid_cat_ids = array();
		$query = $this->db->query("SELECT cat_id FROM exp_categories
							 WHERE group_id IN ('".implode("','", $valid_cats)."')
							 AND cat_id IN ('".implode("','", $this->api_channel_categories->cat_parents)."')");

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$this->db->query("DELETE FROM exp_category_posts WHERE cat_id = ".$row['cat_id']." AND entry_id IN ('".$entries_string."')");
				$valid_cat_ids[] = $row['cat_id'];
			}
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
		
		$this->session->set_flashdata('message_success', $this->lang->line('multi_entries_updated'));
		$this->functions->redirect(BASE.AMP.'C=content_edit');
	}

	// --------------------------------------------------------------------

	/**
	 * Delete entries confirm
	 */
	public function delete_entries_confirm()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_delete_self_entries') AND
			 ! $this->cp->allowed_group('can_delete_all_entries'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
				
		if ( ! $this->input->post('toggle'))
		{
			redirect(BASE.'content_edit');
		}

		$this->load->helper('form');

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
			$vars['message'] = $this->lang->line('delete_entry_confirm');
		}
		else
		{
			$vars['message'] = $this->lang->line('delete_entries_confirm');
		}

		$vars['title_deleted_entry'] = '';

		// if it's just one entry, let's be kind and show a title
		if (count($_POST['toggle']) == 1)
		{
			$query = $this->db->query('SELECT title FROM exp_channel_titles WHERE entry_id = "'.$this->db->escape_str($_POST['toggle'][0]).'"');

			if ($query->num_rows() == 1)
			{
				$vars['title_deleted_entry'] = str_replace('%title', $query->row('title') , $this->lang->line('entry_title_with_title'));
			}
		}

		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_confirm'));

		$this->javascript->compile();
		$this->load->view('content/delete_confirm', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete entries
	 */
	public function delete_entries()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_delete_self_entries') AND
			 ! $this->cp->allowed_group('can_delete_all_entries'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
				
		if ( ! $this->input->post('delete'))
		{
			$this->session->set_flashdata('message_failure', $this->lang->line('no_valid_selections'));
			$this->functions->redirect(BASE.AMP.'C=content_edit'.AMP.'M=index');
		}

		/* -------------------------------------------
		/* 'delete_entries_start' hook.
		/*  - Perform actions prior to entry deletion / take over deletion
		*/
			$edata = $this->extensions->call('delete_entries_start');
			if ($this->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		$this->api->instantiate('channel_entries');
		$res = $this->api_channel_entries->delete_entry($this->input->post('delete'));
		
		if ($res === FALSE)
		{
			$this->session->set_flashdata('message_failure', $this->lang->line('no_valid_selections'));
			$this->functions->redirect(BASE.AMP.'C=content_edit'.AMP.'M=index');
		}
		
		// Return success message
		$this->session->set_flashdata('message_success', $this->lang->line('entries_deleted'));
		$this->functions->redirect(BASE.AMP.'C=content_edit');
	}

	// --------------------------------------------------------------------

	/**
	 * JavaScript filtering code
	 *
	 * This function writes some JavaScript functions that
	 * are used to switch the various pull-down menus in the
	 * EDIT page
	 */
	public function filtering_menus($cat_form_array)
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// In order to build our filtering options we need to gather 
		// all the channels, categories and custom statuses

		$channel_array	= array();
		$status_array = array();
		
		$this->api->instantiate('channel_categories');

		$allowed_channels = $this->functions->fetch_assigned_channels(TRUE);

		if (count($allowed_channels) > 0)
		{
			// Fetch channel titles
			$this->db->select('channel_title, channel_id, cat_group, status_group, field_group');
			$this->db->where_in('channel_id', $allowed_channels);
			$this->db->where('site_id', $this->config->item('site_id'));
			
			$this->db->order_by('channel_title');
			$query = $this->db->get('channels');

			foreach ($query->result_array() as $row)
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
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$status_array[]  = array($row['group_id'], $row['status']);
			}
		}

		$default_cats[] = array('', $this->lang->line('filter_by_category'));
		$default_cats[] = array('all', $this->lang->line('all'));
		$default_cats[] = array('none', $this->lang->line('none'));		
		
		$dstatuses[] = array('', $this->lang->line('filter_by_status'));
		$dstatuses[] = array('open', $this->lang->line('open'));
		$dstatuses[] = array('closed', $this->lang->line('closed'));

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
			$statuses[] = array('', $this->lang->line('filter_by_status'));

			if (count($status_array) > 0)
			{
				foreach ($status_array as $k => $v)
				{
					if ($v['0'] == $val['2'])
					{
						$status_name = ($v['1'] == 'closed' OR $v['1'] == 'open') ?  $this->lang->line($v['1']) : $v['1'];
						$statuses[] = array($v['1'], $status_name);
					}
				}
			}
			else
			{
				$statuses[] = array('open', $this->lang->line('open'));
				$statuses[] = array('closed', $this->lang->line('closed'));
			}

			$channel_info[$key]['statuses'] = $statuses;
		}

		$this->javascript->set_global('edit.channelInfo', $channel_info);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Custom dates
	 */
	public function custom_dates()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->output->enable_profiler(FALSE);

		// load the javascript view, as its just a variable, no html template needed
		$this->load->view('_shared/javascript');
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
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->library('table');
		$this->load->model('channel_entries_model');
		$this->lang->loadfile('homepage');
		
		$this->cp->set_variable('cp_page_title', $this->lang->line('most_recent_entries'));
		
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
		
		$vars['no_result'] = $this->lang->line('no_entries');
		$vars['left_column'] = $this->lang->line('most_recent_entries');
		$vars['right_column'] = $this->lang->line('comments');
		
		$this->javascript->compile();
		$this->load->view('content/recent_list', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Show a Single Comment - Deprecated
	 *
	 * Used by quicklinks to link to most recent comment
	 *
	 * @return	void
	 */
	public function view_comment()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$url =  BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=index'.AMP.'comment_id='.$this->input->get('comment_id');
			
		$this->functions->redirect($url);
	}


	// --------------------------------------------------------------------

	/**
	 * View Comments - Deprecated
	 *
	 * @return	void
	 */
	public function view_comments($channel_id = '', $entry_id = '', $message = '', $id_array = '', $total_rows = '', $pag_base_url = '')
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$url =  BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=index';

		if ($this->input->get_post('entry_id'))
		{
			$url =  BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=index'.AMP.'entry_id='.$this->input->get_post('entry_id');
		}
		elseif ($this->input->get_post('channel_id'))
		{
			$url =  BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=index'.AMP.'channel_id='.$this->input->get_post('channel_id');
		}
		
		$this->functions->redirect($url);		
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Comments Confirmation - Deprecated
	 *
	 * @return	void
	 */
	public function delete_comment_confirm()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// delete can come in pure get from email notification templates so redirect it
		
		if ($this->input->get_post('comment_id'))
		{
			$url =  BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=delete_comment_confirm'.AMP.'comment_id='.$this->input->get_post('comment_id');
			
			$this->functions->redirect($url);
		}

		show_error($this->lang->line('unauthorized_access'));

	}

	// --------------------------------------------------------------------

	/**
	 * Change Comment Status - Deprecated
	 *
	 * @param	string	new status
	 * @return	void
	 */
	public function change_comment_status($status='')
	{
		//  Flipped back and forth between two statuses.
		//  COULD be accessed via get for email notification approve
		//  so we redirect

		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$comment_id	= $this->input->get_post('comment_id');
		$status = $this->input->get_post('status');

		if ($status && $comment_id)
		{
			$url =  BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=change_comment_status'.AMP.'comment_id='.$comment_id.AMP.'status='.$status;
			
			$this->functions->redirect($url);
		}

		show_error($this->lang->line('unauthorized_access'));
	}
}
// END CLASS

/* End of file edit.php */
/* Location: ./system/expressionengine/controllers/cp/edit.php */