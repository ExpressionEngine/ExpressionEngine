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
class Content_edit extends Controller {

	var $nest_categories	= 'y';
	var $installed_modules	= FALSE;
	
	var $pipe_length			= 3;
	var $comment_chars			= 25;
	var $comment_leave_breaks	= 'n';

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Content_edit()
	{
		parent::Controller();

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
	 * @access	public
	 * @return	void
	 */	
	function index($channel_id = '', $message = '', $extra_sql = '', $search_url = '', $form_url = '', $action = '', $extra_fields_search='', $extra_fields_entries='', $heading='')
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

		if ((mt_rand(0, 5000) == 42 && $this->session->userdata['group_id'] == 1) OR $this->config->item('kill_all_humans'))
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

		// Authors list
		$vars['author_selected'] = $this->input->get_post('author_id');
		
		$filter_data['author_id'] = $this->input->get_post('author_id');

		$query = $this->member_model->get_authors();
		$vars['author_select_options'][''] = $this->lang->line('filter_by_author');

		foreach ($query->result_array() as $row)
		{
			$vars['author_select_options'][$row['member_id']] = ($row['screen_name'] == '') ? $row['username'] : $row['screen_name'];
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
			$vars['search_in_options']['comments'] =  $this->lang->line('comments');
		}

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

		if ($filter_data['search_in'] == 'comments')
		{
			$rownum = $this->input->get('current_page') ? $this->input->get('current_page') : 0;
		}
		
		
		$filter_data['rownum'] = $rownum;
		$filter_data['perpage'] = $perpage;

		//	 Are there results?
		$filtered_entries = $this->search_model->get_filtered_entries($filter_data);

		// No result?  Show the "no results" message

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

	
		if ($filter_data['search_in'] != 'comments')
		{
			$pageurl .= AMP.'perpage='.$perpage;
			$vars['form_hidden']['pageurl'] = base64_encode($pageurl); // for pagination
		}
		
		if ($filter_data['search_in'] == 'comments')
		{
			if ($keywords == '')
			{
				$pageurl .= AMP.'keywords='.base64_encode($keywords).AMP.'search_in=comments';
			}

			return $this->view_comments('', '', '',	array_unique($filtered_entries['ids']), $vars['total_count'], $pageurl);
		}

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
		$this->db->select('original_entry_id');
		$autosave = $this->db->get('channel_entries_autosave');
		$autosave_array = array();
		foreach ($autosave->result() as $entry)
		{
			$autosave_array[] = $entry->original_entry_id;
		}

		$vars['autosave_show'] = (count($autosave_array) > 0) ? TRUE : FALSE;

		// Loop through the main query result and set up data structure for table

		$vars['entries'] = array();
		
		$comment_totals = array();

		foreach($query_results as $row)
		{
			// Entry ID number
			$vars['entries'][$row['entry_id']][] = $row['entry_id'];

			// Channel entry title (view entry)			
			$output = anchor(BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$row['channel_id'].AMP.'entry_id='.$row['entry_id'], $row['title']);
			
			$output .= (in_array($row['entry_id'], $autosave_array)) ? NBS.required() : '';
			$vars['entries'][$row['entry_id']][] = $output;

			// "View"
			if ($row['live_look_template'] != 0 && isset($templates[$row['live_look_template']]))
			{
				$qm = ($this->config->item('force_query_string') == 'y') ? '' : '?';

				$url = $this->functions->create_url($templates[$row['live_look_template']].'/'.$row['entry_id']);

				$view_link = anchor($this->functions->fetch_site_index().QUERY_MARKER.'URL='.$url,
									$this->lang->line('view'));
			}
			else
			{
					$view_link = '--';
			}

			$vars['entries'][$row['entry_id']][] = $view_link;


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

			// Setup an array of entry IDs here so we can do an aggregate query to
			// get an accurate count of total comments for each entry.
			if (isset($this->installed_modules['comment']))
			{
				$comment_totals[] = $row['entry_id'];
			}
			
			if ( isset($this->installed_modules['comment']))
			{						
				$vars['entries'][$row['entry_id']][] = $view_link;
			}

			// Username
			$name = ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
			$vars['entries'][$row['entry_id']][] = mailto($row['email'], $name);

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

			if ($this->config->item('honor_entry_dst') == 'y') 
			{					
				if ($row['dst_enabled'] == 'n' AND $this->session->userdata('daylight_savings') == 'y')
				{
					if ($row['entry_date'] != '')
					{
						$row['entry_date'] -= 3600;
					}
				}
				elseif ($row['dst_enabled'] == 'y' AND $this->session->userdata('daylight_savings') == 'n')
				{		
					if ($row['entry_date'] != '')
					{
						$row['entry_date'] += 3600;
					}
				}
			}

			$vars['entries'][$row['entry_id']][] = $this->localize->decode_date($datestr, $row['entry_date'], TRUE);

			// Channel
			$vars['entries'][$row['entry_id']][] = (isset($w_array[$row['channel_id']])) ? '<div class="smallNoWrap">'. $w_array[$row['channel_id']].'</div>' : '';

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

			$vars['entries'][$row['entry_id']][] = '<span class="status_'.$row['status'].'"'.$color_info.'>'.$status_name.'</span>';

			// Delete checkbox
			$vars['entries'][$row['entry_id']][] = form_checkbox('toggle[]', $row['entry_id'], '', ' class="toggle" id="delete_box_'.$row['entry_id'].'"');
		} // End foreach
		
		if (isset($this->cp->installed_modules['comment']))
		{
			// Get the total number of comments for each entry
			$this->db->select('comment_id, entry_id, channel_id, COUNT(*) as count');
			$this->db->where_in('entry_id', $comment_totals);
			$this->db->group_by('entry_id');
			$comment_query = $this->db->get('comments');

			if (isset($this->installed_modules['comment']))
			{
				foreach ($comment_query->result() as $row)
				{
					if ($show_link !== FALSE)
					{
						$view_url = BASE.AMP.'C=content_edit'.AMP.'M=view_comments'.AMP.'channel_id='.$row->channel_id.AMP.'entry_id='.$row->entry_id;
					}
					
					$view_link = ($show_link === FALSE) ? '<div class="lightLinks">--</div>' : 
					'<div class="lightLinks">('.$row->count.')'.NBS.anchor($view_url, $this->lang->line('view')).'</div>';
				
					$vars['entries'][$row->entry_id][3] = $view_link;
				
				}
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

		$this->javascript->compile();
		$this->load->view('content/edit', $vars);
	}


	function edit_ajax_filter()
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
		
		if ($filter_data['search_in'] == 'comments')
		{
			$col_map[] = 'comment';
			
			if ($filter_data['validate'])
			{
				$col_map[] = 'channel_name';
				$col_map[] = 'view';
			}
			
			$col_map[] = 'name';
			$col_map[] = 'exp_comments.email';
			$col_map[] = 'comment_date';
			$col_map[] = 'exp_comments.ip_address';
			$col_map[] = 'exp_comments.status';
		}
		else
		{
			if (isset($this->installed_modules['comment']))
			{
				$col_map = array('exp_channel_titles.entry_id', 'title', 'view', 'comment_total', 'screen_name', 'entry_date', 'channel_name', 'status', '');
			}
			else
			{
				$col_map = array('exp_channel_titles.entry_id', 'title', 'view', 'screen_name', 'entry_date', 'channel_name', 'status', '');
			}
		}

		/* Ordering */
		$order = array();

		if ( isset($_GET['iSortCol_0']))
		{
			for ( $i=0; $i < $_GET['iSortingCols']; $i++ )
			{
				$order[$col_map[$_GET['iSortCol_'.$i]]] = $_GET['sSortDir_'.$i];
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
			$title_output = anchor(BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$row['channel_id'].AMP.'entry_id='.$row['entry_id'], $row['title']);
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
					$view_url = BASE.AMP.'C=content_edit'.AMP.'M=view_comments'.AMP.'channel_id='.$row['channel_id'].AMP.'entry_id='.$row['entry_id'];
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

			if ($this->config->item('honor_entry_dst') == 'y') 
			{					
				if ($row['dst_enabled'] == 'n' AND $this->session->userdata('daylight_savings') == 'y')
				{
					if ($row['entry_date'] != '')
					{
						$row['entry_date'] -= 3600;
					}
				}
				elseif ($row['dst_enabled'] == 'y' AND $this->session->userdata('daylight_savings') == 'n')
				{		
					if ($row['entry_date'] != '')
					{
						$row['entry_date'] += 3600;
					}
				}
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
	

	// --------------------------------------------
	//	 Multi Edit Form
	// --------------------------------------------
 
	function multi_edit_form()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! in_array($this->input->post('action'), array('edit', 'delete', 'add_categories', 'remove_categories')))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $this->input->post('toggle'))
		{
			return $this->index();
		}

		if ($this->input->post('action') == 'delete')
		{
			return $this->delete_entries_confirm();
		}

		$this->load->helper('form');

		$this->jquery->ui(BASE.AMP.'C=javascript'.AMP.'M=load'.AMP.'ui=datepicker', TRUE);

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

			if ($this->config->item('honor_entry_dst') == 'y') 
			{
				$vars['form_hidden']["dst_enabled[$entry_id]"] = $row['dst_enabled'];
			}

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
					$vars['entries_status'][$entry_id]['open'] = $this->lang->line('open');
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


	/** -----------------------------------------
	/**	 Update Multi Entries
	/** -----------------------------------------*/
	function update_multi_entries()
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
		
		foreach ($_POST['entry_id'] as $id)
		{
			$channel_id = $_POST['channel_id'][$id];
		
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

	/** --------------------------------------------
    /**  Multi Categories Edit Form
    /** --------------------------------------------*/
 
    function multi_categories_edit($type, $query)
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

	/**
	  *  Update Multiple Entries with Categories
	  */
	function multi_entry_category_update()
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

 	/* END */


	// --------------------------------------------
	// Delete Entries (confirm)
	//
	// Warning message if you try to delete an entry
	//--------------------------------------------

	function delete_entries_confirm()
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

	
	// --------------------------------------------
	// Delete Entries
	// Kill the specified entries
	//--------------------------------------------
	function delete_entries()
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

	// --------------------------------------------
	//	 JavaScript filtering code
	//
	// This function writes some JavaScript functions that
	// are used to switch the various pull-down menus in the
	// EDIT page
	//--------------------------------------------
	function filtering_menus($cat_form_array)
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
	
	function custom_dates()
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
	 * Used by "recent comments" homepage link
	 *
	 * @access	public
	 * @return	void
	 */
	function show_recent_comments()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->library('table');
		$this->load->model('channel_entries_model');
		$this->lang->loadfile('homepage');
		
		$this->cp->set_variable('cp_page_title', $this->lang->line('most_recent_comments'));
		
		$count = $this->input->get('count');
		$vars = array('entries' => array());
		
		$query = $this->channel_entries_model->get_recent_commented($count);
		
		if ($query && $query->num_rows() > 0)
		{
			$result = $query->result();

			foreach($result as $row)
			{
				$date = $row->recent_comment_date;
				
				$link = BASE.AMP.'C=content_publish'.AMP.'M=view_entry'.AMP.'channel_id='.$row->channel_id.AMP.'entry_id='.$row->entry_id;
				$link = '<a href="'.$link.'">'.$row->title.'</a>';
				
				$vars['entries'][$link] = $this->localize->set_human_time($date);
			}
		}
		
		$vars['no_result'] = $this->lang->line('no_comments');
		$vars['left_column'] = $this->lang->line('most_recent_comments');
		$vars['right_column'] = $this->lang->line('date');
		
		$this->javascript->compile();
		$this->load->view('content/recent_list', $vars);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Show entries with most recent comments
	 *
	 * Used by "recent entries" homepage link
	 *
	 * @access	public
	 * @return	void
	 */
	function show_recent_entries()
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
				$c_link = BASE.AMP.'C=content_edit'.AMP.'M=view_comments'.AMP.'channel_id='.$row->channel_id.AMP.'entry_id='.$row->entry_id;
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
	 * Show a Single Comment
	 *
	 * Used by quicklinks to link to most recent comment
	 *
	 * @access	public
	 * @return	void
	 */
	function view_comment()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$comment_id = $this->input->get('comment_id');
		$this->view_comments('', '', '', array($comment_id));
	}


	function comment_ajax_filter()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->output->enable_profiler(FALSE);
		$this->load->helper(array('form', 'text', 'url', 'snippets'));
		
		$val = '';
		$validate = FALSE;
		$filter_data['validate'] = 0;


		if ($this->input->get_post('search_in') == 'comments' OR $this->input->get_post('validate') != FALSE)
		{
			$validate = TRUE;
			$val = 'validate=1';
			$filter_data['validate'] = 1;
		}
		
		
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
		
		// Apply only to comments- not part of edit page filter
		$filter_data['entry_id'] = $this->input->get_post('entry_id');
		$filter_data['comment_id'] = ($this->input->get_post('comment_id')) ? array($this->input->get_post('comment_id')) : '';
		//$filter_data['validate'] = ($this->input->get_post('validate') == 'true') ? TRUE : FALSE;
		//$validate = $filter_data['validate'];
		
		//$validate = FALSE;


		$perpage = $this->input->get_post('iDisplayLength');
		$offset = ($this->input->get_post('iDisplayStart')) ? $this->input->get_post('iDisplayStart') : 0; // Display start point		
		
		$filter_data['perpage'] = $perpage;
		$filter_data['rownum'] = $offset;
		
		
		// Note- we pipeline the js, so pull more data than are displayed on the page		
		$perpage = $this->input->get_post('iDisplayLength');
		$offset = ($this->input->get_post('iDisplayStart')) ? $this->input->get_post('iDisplayStart') : 0; // Display start point
		$sEcho = $this->input->get_post('sEcho');	
		

		$col_map[] = 'comment';
			
		if ($validate)
		{
			$col_map[] = 'channel_name';
			$col_map[] = 'channel_titles.title';
		}
			
		$col_map[] = 'name';
		$col_map[] = 'exp_comments.email';
		$col_map[] = 'comment_date';
		$col_map[] = 'exp_comments.ip_address';
		$col_map[] = 'exp_comments.status';

		/* Ordering */
		$order = array();

		if ( isset($_GET['iSortCol_0']))
		{
			for ( $i=0; $i < $_GET['iSortingCols']; $i++ )
			{
				$order[$col_map[$_GET['iSortCol_'.$i]]] = $_GET['sSortDir_'.$i];
			}
		}


		if ($filter_data['entry_id'] != FALSE OR $filter_data['comment_id'] != FALSE)
		{
			//  we are looking at a specific comment or comments for a specific entry
			
			$filtered_entries = $this->search_model->comment_search('', $filter_data['entry_id'], $filter_data['comment_id'], '', $validate, $order);
			
			$filter_data['search_in'] == 'comments';
			
			$data_array = $filtered_entries['results'];

			$f_total = $filtered_entries['total_count'];
			$query_results = $filtered_entries['results'];

			$j_response['sEcho'] = $sEcho;
			$j_response['iTotalRecords'] = $this->db->count_all('comments');
			$j_response['iTotalDisplayRecords'] = $f_total;	
			
			$j_response['aaData']  = $this->format_comments($validate, $data_array);
			
			$this->output->send_ajax_response($j_response);			
		}


		//  We're searching in comments only
		$filtered_entries = $this->search_model->get_filtered_entries($filter_data, $order);

		// No result?  Show the "no results" message
		$total = $this->db->count_all('comments');
		$f_total = $filtered_entries['total_count'];
		$query_results = $filtered_entries['results'];

		$j_response['sEcho'] = $sEcho;
		$j_response['iTotalRecords'] = $this->db->count_all('comments');
		$j_response['iTotalDisplayRecords'] = $f_total;		
		
		if (isset($filtered_entries['ids']))
		{
			$j_response['iTotalDisplayRecords'] = count($filtered_entries['ids']);
			
			$data_array = $this->search_model->comment_search('', '', $filtered_entries['ids'], count($filtered_entries['ids']), $validate, $order);
		}
		else
		{
			$data_array = $filtered_entries['results'];
		}
		
			
		$j_response['aaData']  = $this->format_comments($validate, $data_array['results']);
			
		$this->output->send_ajax_response($j_response);

	}

	function format_comments($validate = FALSE, $data_array = '')
	{
		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- view_comment_chars => Number of characters to display (#)
		/*	- view_comment_leave_breaks => Create <br />'s based on line breaks? (y/n)
		/* -------------------------------------------*/
		
		$this->comment_chars	= ($this->config->item('view_comment_chars') !== FALSE) ? $this->config->item('view_comment_chars') : $this->comment_chars;
		$this->comment_leave_breaks = ($this->config->item('view_comment_leave_breaks') !== FALSE) ? $this->config->item('view_comment_leave_breaks') : $this->comment_leave_breaks;
		
		$current_page = 'cp';
		$val = 'val';
		$pag_config['per_page'] = 50;
		$rownum = '';
		$channel_id = '';
		$entry_title = FALSE;

		if ($validate OR $channel_id == '')
		{

			$comment_text_formatting = 'xhtml';
			$comment_html_formatting = 'safe';
			$comment_allow_img_urls	 = 'n';
			$comment_auto_link_urls	 = 'y';

		}
		else
		{
			// Fetch comment display preferences

			$this->db->select('comment_text_formatting, comment_html_formatting, comment_allow_img_urls, comment_auto_link_urls');
			$query = $this->db->get_where('channels', array('channel_id' => $channel_id));

			if ($query->num_rows() == 0)
			{
				show_error($this->lang->line('no_channel_exists'));
			}

			foreach ($query->row_array() as $key => $val)
			{
				$$key = $val;
			}
		}


		$this->load->library('typography');
		$this->typography->initialize();
		$tdata = array();
		
		$i = 0;

		foreach ($data_array as $row)
		{
			$data = array();

			if ($this->comment_leave_breaks == 'y')
			{
				$row['comment'] = str_replace(array("\n","\r"),
												  '<br />',
												  strip_tags($row['comment'])
												  );
			}
			else
			{
				$row['comment'] = strip_tags(str_replace(array("\t","\n","\r"), '', $row['comment']));
			}

			if ($this->comment_chars != 0)
			{
				$row['comment'] = $this->functions->char_limiter(trim($row['comment']), $this->comment_chars);
			}

			if (is_array($data_array))
			{
				$edit_url =	 BASE.AMP.'C=content_edit'.AMP.'M=edit_comment_form'.
														AMP.'channel_id='.$row['channel_id'].
														AMP.'keywords='.$this->input->get_post('keywords').
														AMP.'entry_id='.$row['entry_id'].
														AMP.'comment_id='.$row['comment_id'].
														AMP.'current_page='.$rownum.$val;
			 }
			 else
			 {
				$edit_url =	 BASE.AMP.'C=content_edit'.AMP.'M=edit_comment_form'.
														AMP.'channel_id='.$row['channel_id'].
														AMP.'entry_id='.$row['entry_id'].
														AMP.'comment_id='.$row['comment_id'].
														AMP.'current_page='.$current_page.$val;
			 }

			if ($validate === TRUE)
			{
				// Channel entry title (view entry)
				$show_link = TRUE;

				if (($row['author_id'] != $this->session->userdata('member_id')) && ! $this->cp->allowed_group('can_edit_other_entries'))
				{
					$show_link = FALSE;
				}

				$entry_url	= BASE.AMP.'C=content_publish'.AMP.'M=view_entry'.AMP.'channel_id='.$row['channel_id'].AMP.'entry_id='.$row['entry_id'];
				if (isset($row['entry_title']))
				{
					$entry_title = $this->functions->char_limiter(trim(strip_tags($row['entry_title'])), 26); 
				}
			}

			$mid_search_url = BASE.AMP.'C=content_edit'.AMP.'M=index'.
												AMP.'search_in=comments'.
												AMP.'order=desc'.
												AMP.'perpage='.$pag_config['per_page'].
												AMP.'keywords='.base64_encode('mid:'.$row['author_id']);

			$email = ($row['email'] != '') ? mailto($row['email']) : NBS.'--'.NBS;

			if ($row['status'] == 'o')
			{
				$status = 'close';
				$status_label = $this->lang->line('open');
			}
			else
			{
				$status = 'open';
				$status_label = $this->lang->line('closed');
			 }

			if (is_array($data_array))
			{
				$status_change_url = BASE.AMP.'C=content_edit'.AMP.'M=change_comment_status'.
														AMP.'search_in=comments'.
														AMP.'channel_id='.$row['channel_id'].
														AMP.'keywords='.$this->input->get_post('keywords').
														AMP.'comment_id='.$row['comment_id'].
														AMP.'current_page='.$rownum.
														AMP.'status='.$status.$val;
			}
			else
			{
				$status_change_url = BASE.AMP.'C=content_edit'.AMP.'M=change_comment_status'.
														AMP.'channel_id='.$channel_id.
														AMP.'entry_id='.$entry_id.
														AMP.'comment_id='.$row['comment_id'].
														AMP.'current_page='.$current_page.
														AMP.'status='.$status.$val;
			}

			$ip_search_url = BASE.AMP.'C=content_edit'.AMP.'M=index'.
												AMP.'search_in=comments'.
												AMP.'order=desc'.
												AMP.'perpage='.$pag_config['per_page'].
												AMP.'keywords='.base64_encode('ip:'.str_replace('.','_',$row['ip_address']));


			$date = $this->localize->set_human_time($row['comment_date']);

			$channel_name = isset($row['channel_name']) ? $row['channel_name'] : '';
			$data['entry_url'] = '';
			$data['entry_title'] = '';
			$data['show_link'] = '';

			if ($validate === TRUE)
			{
				$data['entry_url'] = $entry_url;
				$data['entry_title'] = $entry_title;
				$data['show_link'] = $show_link;
			}
			
			
			$out[] = "<a class='less_important_link' href='{$edit_url}'>{$row['comment']}</a>";
			
			if ($validate === TRUE)
			{
				$out[] = $channel_name;
				$out[] = ($show_link) ? "<a class='less_important_link' href='{$entry_url}'>{$entry_title}</a>" : $entry_title;
			}
			
			$out[] = ($row['author_id'] == '0') ? $row['name'] : "<a class='less_important_link'  href='{$mid_search_url}'>{$row['name']}</a>";
			$out[] = $email;
			$out[] = $date;
			$out[] = "<a class='less_important_link' href='{$ip_search_url}'>{$row['ip_address']}</a>";
			$out[] = "<a class='less_important_link' href='{$status_change_url}'>{$status_label}</a>";												
			$out[] = form_checkbox('toggle[]', 'c'.$row['comment_id'], FALSE, 'class="comment_toggle"');
			
			
			$tdata[$i] = $out;
			
			unset($out);
			$i++;
		}
		
		$j_response = $tdata;	
		
		return $j_response;
	}

	// --------------------------------------------------------------------

	/**
	 * View Comments
	 *
	 * @access	public
	 * @return	void
	 */
	function view_comments($channel_id = '', $entry_id = '', $message = '', $id_array = '', $total_rows = '', $pag_base_url = '')
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper('text');

		$val = '';
		$search = '';
		$validate = FALSE;
		$filter_data['validate'] = 0;
		$perpage = '75';
		$cols = '[null, null, null, null, null, null, { "bSortable" : false } ]';
		$this->cp->add_js_script(array('plugin' => 'dataTables'));

		// This function is accessible through the url, but also callable
		// so we'll override with _GET if it's called directly

		if ($this->input->get('C') == 'content_edit' && $this->input->get('M') == 'view_comments')
		{
			foreach(array('channel_id', 'entry_id', 'message', 'id_array', 'rownum') as $param)
			{
				if ( ! isset($_GET[$param]))
				{
					continue;
				}

				switch($param)
				{
					case 'id_array':	$$param = explode('|', $this->input->get($param));
						break;
					default:			$$param = $this->input->get($param);
				}
			}
		}
		
		$pagination_links = '';
		$results = array();

		
		$this->load->library('pagination');
		
		$pag_config['per_page'] = '75';
		$pag_config['page_query_string'] = TRUE;
		$pag_config['query_string_segment'] = 'current_page';
		
		//  validate or keyword indicate $validate = TRUE
		
		if ($this->input->get_post('keywords') !== FALSE OR $this->input->get('validate') != FALSE)
		{
			$validate = TRUE;
			$search = '&search_in=comments';
			$val = 'validate=1';
			$filter_data['validate'] = 1;
			$cols = '[null, null, null, null, null, null, null, null, { "bSortable" : false } ]';
		}

		$filter_data['channel_id'] = ($this->input->get_post('channel_id') && $this->input->get_post('channel_id') != 'null') ? $this->input->get_post('channel_id') : '';
		$filter_data['cat_id'] = $this->input->get_post('cat_id');

		$filter_data['status'] = $this->input->get_post('status');
		$filter_data['order']	= $this->input->get_post('order');
		$filter_data['date_range'] = $this->input->get_post('date_range');	
		$filter_data['author_id'] = $this->input->get_post('author_id');	
		$filter_data['keywords'] = ($this->input->get_post('keywords')) ? $this->input->get_post('keywords') : '';
		$filter_data['search_in'] = 'comments';
		$filter_data['exact_match'] = $this->input->get_post('exact_match');
		$search_keywords = ($this->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities($filter_data['keywords']) : $filter_data['keywords'];

		$filter_data['search_keywords'] = $search_keywords;
		$filter_data['perpage'] = $perpage;
		$filter_data['rownum'] = 0;	
			
		$filter_data['entry_id'] = $this->input->get_post('entry_id');
		$filter_data['comment_id'] = $this->input->get_post('comment_id');

		if ($this->input->get('ajax'))
		{			
			$filtered = $this->search_model->get_filtered_entries($filter_data);
			$id_array = (isset($filtered['ids'])) ? $filtered['ids'] : array();
			
			if (count($id_array) == 0)
			{
				$message = $this->lang->line('no_comments');
				$vars['message'] = $message;

				$this->javascript->compile();
				return	$this->load->view('content/comments', $vars);
			}			
		}
		
		// Require at least one comment checked to submit
		$this->javascript->output('
		$("#target").submit(function() {
			if ( ! $("input:checkbox", this).is(":checked")) {
			$.ee_notice("'.$this->lang->line('selection_required').'", {"type" : "error"});
			return false;
			}
		});');
		
	
	$this->javascript->output('
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

	var keywords   = "'.$filter_data['keywords'].'";
    var status     = "'.$filter_data['status'].'";
	var channel_id  = "'.$filter_data['channel_id'].'";
    var cat_id      = "'.$filter_data['cat_id'].'";
    var date_range = "'.$filter_data['date_range'].'";
    var comment_id = "'.$filter_data['comment_id'].'";
    var entry_id = "'.$filter_data['entry_id'].'";
    var validate = "'.$filter_data['validate'].'";

	aoData.push( 
		 { "name": "keywords", "value": keywords },
         { "name": "status", "value": status },
		 { "name": "channel_id", "value": channel_id },
         { "name": "cat_id", "value": cat_id },
         { "name": "date_range", "value": date_range },
         { "name": "comment_id", "value": comment_id },
         { "name": "entry_id", "value": entry_id },
         { "name": "validate", "value": validate }
	 );
	

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
		
			aoData.push( 
		 		{ "name": "keywords", "value": keywords },
         		{ "name": "status", "value": status },
		 		{ "name": "channel_id", "value": channel_id },
         		{ "name": "cat_id", "value": cat_id },
         		{ "name": "date_range", "value": date_range },
         		{ "name": "comment_id", "value": comment_id },
         		{ "name": "entry_id", "value": entry_id },
         		{ "name": "validate", "value": validate }
	 		);

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

	oTable = $(".mainTable").dataTable( {	
			"sPaginationType": "full_numbers",
			"bLengthChange": false,
			"bFilter": false,
			"sWrapper": false,
			"sInfo": false,
			"bAutoWidth": false,
			"iDisplayLength": '.$perpage.', 
			
			
		"aoColumns": '.$cols.', 
			
			
		"oLanguage": {
			"sZeroRecords": "'.$this->lang->line('no_comments').'",
			"sInfoFiltered": "(filtered from _MAX_ total comments)",
			
			"oPaginate": {
				"sFirst": "<img src=\"'.$this->cp->cp_theme_url.'images/pagination_first_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sPrevious": "<img src=\"'.$this->cp->cp_theme_url.'images/pagination_prev_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />",
				"sNext": "<img src=\"'.$this->cp->cp_theme_url.'images/pagination_next_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />", 
				"sLast": "<img src=\"'.$this->cp->cp_theme_url.'images/pagination_last_button.gif\" width=\"13\" height=\"13\" alt=\"&lt; &lt;\" />"
			}
		},
		
			"bProcessing": true,
			"bServerSide": true,
			"sAjaxSource": EE.BASE+"&C=content_edit&M=comment_ajax_filter'.$search.'",
			"fnServerData": fnDataTablesPipeline
	} );


		');
	
		
		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- view_comment_chars => Number of characters to display (#)
		/*	- view_comment_leave_breaks => Create <br />'s based on line breaks? (y/n)
		/* -------------------------------------------*/

		$this->comment_chars		= ($this->config->item('view_comment_chars') !== FALSE) ? $this->config->item('view_comment_chars') : $this->comment_chars;
		$this->comment_leave_breaks = ($this->config->item('view_comment_leave_breaks') !== FALSE) ? $this->config->item('view_comment_leave_breaks') : $this->comment_leave_breaks;
	

		/** ---------------------------------------
		/**	 Assign page header and breadcrumb
		/** ---------------------------------------*/

		$page = 'comments';
		$this->cp->set_variable('cp_page_title', $this->lang->line($page));
		
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=content_edit' => $this->lang->line('edit')
		));

		$comment_results = $this->search_model->comment_search($channel_id, $entry_id, $id_array, $total_rows, $validate);

		if ($comment_results['error'] !== FALSE)
		{
			show_error($comment_results['error']);
		}
		
		if ($validate OR $channel_id == '')
		{
			// Paginate the results - this only happens when the function is called
			// directly and the base url and total comment count are passed

			if ($pag_base_url != '' && $total_rows !== '')
			{
				if ($this->input->get_post('perpage'))
				{
					$pag_config['per_page'] = $this->input->get_post('perpage');
				}
				
				$pag_config['total_rows'] = $total_rows;
				$pag_config['base_url'] = $pag_base_url.AMP.'perpage='.$pag_config['per_page'];

				$this->pagination->initialize($pag_config);
				$pagination_links = $this->pagination->create_links();
			}

			if (is_array($id_array))
			{
				//$validate = TRUE;
			}


			if (count($comment_results['results']) == 0)
			{
				if ($this->input->get('U') == 1)
				{
					$message = $this->lang->line('status_changed');
				}
				else
				{
					$message = $this->lang->line('no_entries_matching_that_criteria');
				}
			}

			$comment_text_formatting = 'xhtml';
			$comment_html_formatting = 'safe';
			$comment_allow_img_urls	 = 'n';
			$comment_auto_link_urls	 = 'y';
		}
		else
		{
			// No results?  No reason to continue...

			if (count($comment_results['total_comments']) == 0)
			{
				$message = $this->lang->line('no_comments');
			}

			// Fetch comment display preferences

			$this->db->select('comment_text_formatting, comment_html_formatting, comment_allow_img_urls, comment_auto_link_urls');
			$query = $this->db->get_where('channels', array('channel_id' => $channel_id));

			if ($query->num_rows() == 0)
			{
				show_error($this->lang->line('no_channel_exist'));
			}

			foreach ($query->row_array() as $key => $val)
			{
				$$key = $val;
			}

			// Do we need pagination?

			$pag_config['base_url'] = BASE.AMP.'C=content_edit'.AMP.'M=view_comments'.AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id;
			$pag_config['total_rows'] = $comment_results['total_comments'];

			$this->pagination->initialize($pag_config);
			$pagination_links = $this->pagination->create_links();
			
		}

		// Prep for output
		$this->load->library('typography');
		$this->typography->initialize();
		$this->load->library('table');
		$this->load->helper('form');

		$val = ($validate) ? AMP.'validate=1' : '';

		$vars = array();
		$hidden = array();
		
		$current_page = ($this->input->get('current_page') != FALSE) ? $this->input->get('current_page') : '';

		$hidden['current_page'] = $rownum = $current_page;

		if ($this->input->get_post('keywords') !== FALSE)
		{
			$hidden['keywords'] = $this->input->get_post('keywords');
		}

		// Show comments
		
		foreach ($comment_results['results'] as $row)
		{
			$data = array();

			if ($this->comment_leave_breaks == 'y')
			{
				$row['comment'] = str_replace(array("\n","\r"),
												  '<br />',
												  strip_tags($row['comment'])
												  );
			}
			else
			{
				$row['comment'] = strip_tags(str_replace(array("\t","\n","\r"), '', $row['comment']));
			}

			if ($this->comment_chars != 0)
			{
				$row['comment'] = $this->functions->char_limiter(trim($row['comment']), $this->comment_chars);
			}

			if (is_array($id_array))
			{
				$edit_url =	 BASE.AMP.'C=content_edit'.AMP.'M=edit_comment_form'.
														AMP.'channel_id='.$row['channel_id'].
														AMP.'keywords='.$this->input->get_post('keywords').
														AMP.'entry_id='.$row['entry_id'].
														AMP.'comment_id='.$row['comment_id'].
														AMP.'current_page='.$rownum.$val;
			 }
			 else
			 {
				$edit_url =	 BASE.AMP.'C=content_edit'.AMP.'M=edit_comment_form'.
														AMP.'channel_id='.$row['channel_id'].
														AMP.'entry_id='.$row['entry_id'].
														AMP.'comment_id='.$row['comment_id'].
														AMP.'current_page='.$current_page.$val;
			 }

			if ($validate === TRUE)
			{
				// Channel entry title (view entry)
				$show_link = TRUE;

				if (($row['author_id'] != $this->session->userdata('member_id')) && ! $this->cp->allowed_group('can_edit_other_entries'))
				{
					$show_link = FALSE;
				}

				$entry_url	= BASE.AMP.'C=content_publish'.AMP.'M=view_entry'.AMP.'channel_id='.$row['channel_id'].AMP.'entry_id='.$row['entry_id'];
				$entry_title = $this->functions->char_limiter(trim(strip_tags($row['entry_title'])), 26); 
			}

			$mid_search_url = BASE.AMP.'C=content_edit'.AMP.'M=index'.
												AMP.'search_in=comments'.
												AMP.'order=desc'.
												AMP.'perpage='.$pag_config['per_page'].
												AMP.'keywords='.base64_encode('mid:'.$row['author_id']);

			$email = ($row['email'] != '') ? mailto($row['email']) : NBS.'--'.NBS;

			if ($row['status'] == 'o')
			{
				$status = 'close';
				$status_label = $this->lang->line('open');
			}
			else
			{
				$status = 'open';
				$status_label = $this->lang->line('closed');
			 }

			if (is_array($id_array))
			{
				$status_change_url = BASE.AMP.'C=content_edit'.AMP.'M=change_comment_status'.
														AMP.'search_in=comments'.
														AMP.'channel_id='.$row['channel_id'].
														AMP.'keywords='.$this->input->get_post('keywords').
														AMP.'comment_id='.$row['comment_id'].
														AMP.'current_page='.$rownum.
														AMP.'status='.$status.$val;
			}
			else
			{
				$status_change_url = BASE.AMP.'C=content_edit'.AMP.'M=change_comment_status'.
														AMP.'channel_id='.$channel_id.
														AMP.'entry_id='.$entry_id.
														AMP.'comment_id='.$row['comment_id'].
														AMP.'current_page='.$current_page.
														AMP.'status='.$status.$val;
			}

			$ip_search_url = BASE.AMP.'C=content_edit'.AMP.'M=index'.
												AMP.'search_in=comments'.
												AMP.'order=desc'.
												AMP.'perpage='.$pag_config['per_page'].
												AMP.'keywords='.base64_encode('ip:'.str_replace('.','_',$row['ip_address']));

			$data = $row;
			$data['edit_url'] = $edit_url;
			$data['status_label'] = $status_label;
			$data['status_change_url'] = $status_change_url;
			$data['ip_search_url'] = $ip_search_url;
			$data['email'] = $email;
			$data['mid_search'] = $mid_search_url;
			$data['date'] = $this->localize->set_human_time($row['comment_date']);

			$data['channel_name'] = isset($row['channel_name']) ? $row['channel_name'] : '';
			$data['entry_url'] = '';
			$data['entry_title'] = '';
			$data['show_link'] = '';

			if ($validate === TRUE)
			{
				$data['entry_url'] = $entry_url;
				$data['entry_title'] = $entry_title;
				$data['show_link'] = $show_link;
			}

			$vars['comments'][] = $data;
		}
		// END FOREACH

		$vars['form_options'] = array(
									'close' => $this->lang->line('close_selected'),
									'open' => $this->lang->line('open_selected'),
									'delete' => $this->lang->line('delete_selected'),
									);

		if ($this->cp->allowed_group('can_edit_all_comments') OR $this->cp->allowed_group('can_moderate_comments'))
		{
			$vars['form_options']['null'] = '------';
			$vars['form_options']['move'] = $this->lang->line('move_selected');
		}
		
		$this->javascript->output('
		$(".toggle_comments").toggle(
			function(){
				$("input[class=comment_toggle]").each(function() {
					this.checked = true;
				});
			}, function (){
				$("input[class=comment_toggle]").each(function() {
					this.checked = false;
				});
			}
		);');

		$vars['pagination'] = $pagination_links;


		$vars['validate'] = $validate;
		$vars['hidden'] = $hidden;
		$vars['message'] = $message;

		$this->javascript->compile();
		$this->load->view('content/comments', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Move Comments Form
	 *
	 * @access	public
	 * @return	void
	 */
	function move_comments_form()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$channel_id		= $this->input->get_post('channel_id');
		$entry_id		= $this->input->get_post('entry_id');

		if($this->input->get_post('comment_ids') !== FALSE)
		{
			$comments = explode('|', $this->input->get_post('comment_ids'));
		}
		else
		{
			$comments	= array();

			foreach ($_POST['toggle'] as $key => $val)
			{
				if (substr($val, 0, 1) == 'c')
				{
					$comments[] = substr($val, 1);
				}
			}

			if($this->input->get_post('comment_id') !== FALSE && is_numeric($this->input->get_post('comment_id')))
			{
				$comments[] = $this->input->get_post('comment_id');
			}
		}

		if (count($comments) == 0)
		{
			$this->session->set_flashdata('message_failure', $this->lang->line('no_valid_selections'));
			$this->functions->redirect(BASE.AMP.'C=content_edit'.AMP.'M=index');
		}

		if ( ! $this->cp->allowed_group('can_moderate_comments') && ! $this->cp->allowed_group('can_edit_all_comments'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->db->select('comment_id');
		$this->db->where_in('comment_id', $comments);
		$query = $this->db->get('comments');

		/** -------------------------------
		/**	 Retrieve Our Results
		/** -------------------------------*/

		if ($query->num_rows() == 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$comment_ids  = array();

		foreach($query->result_array() as $row)
		{
			$comment_ids[]	= $row['comment_id'];
		}

		/** -------------------------------
		/**	 Create Our Form
		/** -------------------------------*/

		$vars = array();
		$hidden = array();

		$this->cp->set_variable('cp_page_title', 'choose_entry_for_comment_move');

		$hidden['comment_ids'] = implode('|', $comments);

		if ($this->input->get_post('keywords') !== FALSE)
		{
			$hidden['keywords'] = $this->input->get_post('keywords');
		}

		if ($this->input->get_post('current_page') !== FALSE)
		{
			$hidden['current_page'] = $this->input->get_post('current_page');
		}

		$actions = array('move' => $this->lang->line('move_comments_to_entry'));
		$this->index('', '', '', 'C=content_edit'.AMP.'M=move_comments_form', 'C=content_edit'.AMP.'M=move_comments', $actions, $hidden, $hidden, 'choose_entry_for_comment_move');
	}

	// --------------------------------------------------------------------

	/**
	 * Move Comments
	 *
	 * @access	public
	 * @return	void
	 */
	function move_comments()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$channel_id		= $this->input->get_post('channel_id');
		$entry_id		= $this->input->get_post('entry_id');

		if ( ! $this->cp->allowed_group('can_moderate_comments') && ! $this->cp->allowed_group('can_edit_all_comments'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if($this->input->get_post('comment_ids') !== FALSE)
		{
			$comments = explode('|', $this->input->get_post('comment_ids'));

			foreach($comments as $key => $val)
			{
				$comments[$key] = $this->db->escape_str($val);
			}
		}
		else
		{
			$this->session->set_flashdata('message_failure', $this->lang->line('no_valid_selections'));
			$this->functions->redirect(BASE.AMP.'C=content_edit'.AMP.'M=index');
		}

		$new_entries = array();

		foreach ($_POST['toggle'] as $key => $val)
		{
			$new_entries[] = $val;
		}

		if (count($new_entries) == 0)
		{
			return $this->move_comments_form();
		}
		elseif(count($new_entries) > 1)
		{
			return show_error($this->lang->line('choose_only_one_entry'));
		}

		$this->db->select('channel_id, entry_id');
		$query = $this->db->get_where('channel_titles', array('entry_id' => $new_entries['0']));

		$new_entry_id = $query->row('entry_id') ;
		$new_channel_id = $query->row('channel_id') ;

		$this->db->select('comment_id, channel_id, entry_id');
		$this->db->where_in('comment_id', $comments);
		$query = $this->db->get('comments');

		/** -------------------------------
		/**	 Retrieve Our Results
		/** -------------------------------*/

		if ($query->num_rows() == 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$comment_ids  = array();
		$entry_ids	= array($new_entry_id);
		$channel_ids = array($new_channel_id);

		/** -------------------------------
		/**	 Move Comments
		/** -------------------------------*/

		foreach($query->result_array() as $row)
		{
			$this->db->query($this->db->update_string('exp_comments', array('channel_id' => $new_channel_id, 'entry_id' => $new_entry_id), "comment_id = '".$row['comment_id']."'"));

			$comment_ids[]	= $row['comment_id'];
			$entry_ids[]  = $row['entry_id'];
			$channel_ids[] = $row['channel_id'];
		}

		/** -------------------------------
		/**	 Recounts
		/** -------------------------------*/

		foreach(array_unique($entry_ids) as $entry_id)
		{
			$query = $this->db->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '".$this->db->escape_str($entry_id)."'");

			$comment_date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;

			$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$this->db->escape_str($entry_id)."' AND status = 'o'");

			$this->db->query("UPDATE exp_channel_titles SET comment_total = '".($query->row('count') )."', recent_comment_date = '$comment_date' WHERE entry_id = '".$this->db->escape_str($entry_id)."'");
		}

		// Quicker and updates just the channels
		foreach(array_unique($channel_ids) as $channel_id) { $this->stats->update_comment_stats($channel_id, '', FALSE); }

		// Updates the total stats
		$this->stats->update_comment_stats();

		$this->functions->clear_caching('all');
		
		$cp_message = (count($comments) > 1) ? 'comments_moved' : 'comment_moved';
		
		$this->session->set_flashdata('message_success', $this->lang->line($cp_message));
		$this->functions->redirect(BASE.AMP.'C=content_edit'.AMP.'M=view_comments'.AMP.'channel_id='.$new_channel_id.AMP.'entry_id='.$new_entry_id.AMP.'U=1'.$val);
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Comment Form
	 *
	 * @access	public
	 * @return	void
	 */
	function edit_comment_form()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->load->library('table');

		$comment_id		= $this->input->get_post('comment_id');
		$channel_id		= $this->input->get_post('channel_id');
		$entry_id		= $this->input->get_post('entry_id');
		$current_page	= $this->input->get_post('current_page');

		if ($comment_id == FALSE OR ! is_numeric($comment_id) OR ! is_numeric($channel_id) OR ! is_numeric($entry_id))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper(array('form', 'snippets'));

		$validate = 0;

		if ($this->input->get_post('validate') == 1)
		{
			if ( ! $this->cp->allowed_group('can_moderate_comments'))
			{
				show_error($this->lang->line('unauthorized_access'));
			}

			$query = $this->db->get_where('comments', array('comment_id' => $comment_id));

			$validate = 1;
		}
		else
		{
			if ( ! $this->cp->allowed_group('can_edit_all_comments'))
			{
				if ( ! $this->cp->allowed_group('can_edit_own_comments'))
				{
					show_error($this->lang->line('unauthorized_access'));
				}
				else
				{
					$this->db->select('channel_titles.author_id');
					$this->db->from(array('channel_titles', 'comments'));
					$this->db->where('channel_titles.entry_id = '.$this->db->dbprefix('comments.entry_id'));
					$this->db->where('comments.comment_id', $comment_id);

					$query = $this->db->get();

					if ($query->row('author_id') != $this->session->userdata('member_id'))
					{
						show_error($this->lang->line('unauthorized_access'));
					}
				}
			}

			$query = $this->db->get_where('comments', array('comment_id' => $comment_id));
		}

		if ($query->num_rows() == 0)
		{
			return false;
		}

		$vars = $query->row_array();

		$hidden = array(
						'comment_id'	=> $comment_id,
						'author_id'		=> $vars['author_id'],
						'channel_id'	=> $channel_id,
						'current_page'	=> $current_page,
						'entry_id'		=> $entry_id,
						'validate'		=> $validate
						);

		if ($this->input->get_post('keywords') !== FALSE)
		{
			$hidden['keywords'] = $this->input->get_post('keywords');
		}

		$this->cp->set_variable('cp_page_title', $this->lang->line('edit_comment'));

		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=content_edit' => $this->lang->line('edit'),
			BASE.AMP.'C=content_edit'.AMP.'M=view_comments'.AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id	=> $this->lang->line('comments')
		));

		$vars['hidden'] = $hidden;

		$this->javascript->compile();
		$this->load->view('content/edit_comment', $vars);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Update Comment
	 *
	 * @access	public
	 * @return	void
	 */
	function update_comment()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$comment_id = $this->input->get_post('comment_id');
		$author_id	= $this->input->get_post('author_id');
		$channel_id	 = $this->input->get_post('channel_id');
		$entry_id	= $this->input->get_post('entry_id');

		if ($comment_id == FALSE OR ! is_numeric($comment_id) OR ! is_numeric($channel_id) OR ! is_numeric($entry_id))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ($author_id === FALSE)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ($this->input->get_post('validate') == 1)
		{
			if ( ! $this->cp->allowed_group('can_moderate_comments'))
			{
				show_error($this->lang->line('unauthorized_access'));
			}

			$this->db->where('comment_id', $comment_id);
			$count = $this->db->count_all_results('comments');

			if ($count == 0)
			{
				show_error($this->lang->line('unauthorized_access'));
			}
		}
		else
		{
			if ( ! $this->cp->allowed_group('can_edit_all_comments'))
			{
				if ( ! $this->cp->allowed_group('can_edit_own_comments'))
				{
					show_error($this->lang->line('unauthorized_access'));
				}
				else
				{
					$this->db->select('channel_titles.author_id');
					$this->db->from(array('channel_titles', 'comments'));
					$this->db->where('channel_titles.entry_id = '.$this->db->dbprefix('comments.entry_id'));
					$this->db->where('comments.comment_id', $comment_id);
					$query = $this->db->get();

					if ($query->row('author_id') != $this->session->userdata('member_id'))
					{
						show_error($this->lang->line('unauthorized_access'));
					}
				}
			}
		}

		/** ---------------------------------------
		/**	 Fetch comment display preferences
		/** ---------------------------------------*/

		$this->db->select('channels.comment_require_email');
		$this->db->from(array('channels', 'comments'));
		$this->db->where('comments.channel_id = '.$this->db->dbprefix('channels.channel_id'));
		$this->db->where('comments.comment_id', $comment_id);
		$query = $this->db->get();

		if ($query->num_rows() == 0)
		{
			return show_error($this->lang->line('no_channel_exists'));
		}

		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}


		// Error checks

		$error = array();

		if ($author_id == 0)
		{
			// Fetch language file

			$this->lang->loadfile('myaccount');

			if ($comment_require_email == 'y')
			{
				// Is email missing?

				if ($_POST['email'] == '')
				{
					$error[] = $this->lang->line('missing_email');
				}

				// Is email valid?

				$this->load->helper('email');
				if ( ! valid_email($_POST['email']))
				{
					$error[] = $this->lang->line('invalid_email_address');
				}


				// Is email banned?

				if ($this->session->ban_check('email', $_POST['email']))
				{
					$error[] = $this->lang->line('banned_email');
				}
			}
		}

		/** -------------------------------------
		/**	 Is comment missing?
		/** -------------------------------------*/

		if ($_POST['comment'] == '')
		{
			$error[] = $this->lang->line('missing_comment');
		}


		/** -------------------------------------
		/**	 Display error is there are any
		/** -------------------------------------*/
		 if (count($error) > 0)
		 {
			$msg = '';

			foreach($error as $val)
			{
				$msg .= $val.'<br />';
			}

			return show_error($msg);
		 }

		// Build query

		if ($author_id == 0)
		{
			$data = array(
							'name'		=> $_POST['name'],
							'email'		=> $_POST['email'],
							'url'		=> $_POST['url'],
							'location'	=> $_POST['location'],
							'comment'	=> $_POST['comment']
						 );
		}
		else
		{
			$data = array(
							'comment'	=> $_POST['comment']
						 );
		}


		$this->db->query($this->db->update_string('exp_comments', $data, "comment_id = '$comment_id'"));

		/* -------------------------------------------
		/* 'update_comment_additional' hook.
		/*  - Add additional processing on comment update.
		*/
			$edata = $this->extensions->call('update_comment_additional', $comment_id, $data);
			if ($this->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		$this->functions->clear_caching('all');

		$current_page = ( ! isset($_POST['current_page'])) ? 0 : $_POST['current_page'];

		if ($this->input->get_post('keywords') !== FALSE)
		{
			$url = BASE.AMP.'C=content_edit'.
						AMP.'M=index'.
						AMP.'search_in=comments'.
						AMP.'rownum='.$current_page.
						AMP.'order=desc'.
						AMP.'keywords='.$this->input->get_post('keywords');
		}
		elseif ($this->input->post('validate') == 1)
		{
			$url = BASE.AMP.'C=publish'.AMP.'M=view_comments'.AMP.'validate=1';
		}
		else
		{
			$url = BASE.AMP.'C=content_edit'.
						AMP.'M=view_comments'.
						AMP.'channel_id='.$channel_id.
						AMP.'entry_id='.$entry_id.
						AMP.'current_page='.$current_page;
		}

		$this->session->set_flashdata('message_success',  $this->lang->line('comment_updated'));
		$this->functions->redirect($url);
	}

	// --------------------------------------------------------------------

	/**
	 * Modify Comments
	 *
	 * @access	public
	 * @return	void
	 */
	function modify_comments()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// This only happens if they submit with no comments checked, so we send
		// them home.
		if ( ! $this->input->post('toggle') && ! $this->input->get_post('comment_id'))
		{
			$this->session->set_flashdata('message_failure', $this->lang->line('no_valid_selections'));
			$this->functions->redirect(BASE.AMP.'C=content_edit'.AMP.'M=index');
		}
		
		switch($this->input->post('action'))
		{
			case 'open':
				$this->change_comment_status('open');
			break;
			case 'close':
				$this->change_comment_status('close');
			break;
			case 'move':
				$this->move_comments_form();
			break;
			default:
				$this->delete_comment_confirm();
			break;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Comments Confirmation
	 *
	 * @access	public
	 * @return	void
	 */
	function delete_comment_confirm()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$channel_id = $this->input->get_post('channel_id');
		$entry_id	 = $this->input->get_post('entry_id');

		if ( ! $this->input->post('toggle') && ! $this->input->get_post('comment_id'))
		{
			$this->session->set_flashdata('message_failure', $this->lang->line('no_valid_selections'));
			$this->functions->redirect(BASE.AMP.'C=content_edit'.AMP.'M=index');
		}

		$comments	= array();

		foreach ($_POST['toggle'] as $key => $val)
		{
			if (substr($val, 0, 1) == 'c')
			{
				$comments[] = $this->db->escape_str(substr($val, 1));
			}
		}

		if($this->input->get_post('comment_id') !== FALSE && is_numeric($this->input->get_post('comment_id')))
		{
			$comments[] = $this->db->escape_str($this->input->get_post('comment_id'));
		}

		if ($this->input->get_post('validate') == 1)
		{
			if (count($comments) == 0)
			{
				show_error($this->lang->line('unauthorized_access'));
			}

			if ( ! $this->cp->allowed_group('can_moderate_comments'))
			{
				show_error($this->lang->line('unauthorized_access'));
			}

			$this->db->where_in('comment_id', $comments);
			$count = $this->db->count_all_results('comments');

			if ($count == 0)
			{
				show_error($this->lang->line('unauthorized_access'));
			}
		}
		else
		{
			if ( ! $this->cp->allowed_group('can_delete_all_comments'))
			{
				if ( ! $this->cp->allowed_group('can_delete_own_comments'))
				{
					show_error($this->lang->line('unauthorized_access'));
				}
				else
				{
					if (count($comments) > 0)
					{
						$this->db->select('channel_titles.author_id, comments.comment_id');
						$this->db->from(array('channel_titles', 'comments'));
						$this->db->where('channel_titles.entry_id = '.$this->db->dbprefix('comments.entry_id'));
						$this->db->where_in('comments.comment_id', $comments);
					}

					$comments	= array();

					$query = $this->db->get();

					if ($query->num_rows() > 0)
					{
						foreach($query->result_array() as $row)
						{
							if ($row['author_id'] == $this->session->userdata('member_id'))
							{
								$comments[] = $row['comment_id'];
							}
						}
					}
				}
			}
			}

		if (count($comments) == 0)
		{
			$this->session->set_flashdata('message_failure', $this->lang->line('no_valid_selections'));
			$this->functions->redirect(BASE.AMP.'C=content_edit'.AMP.'M=index');
		}

		$this->load->helper('form');
		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_confirm'));

		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=content_edit' => $this->lang->line('edit'),

		));
		
		$vars = array();

		$vars['hidden'] = array(
								'validate'		=> ($this->input->get_post('validate') == 1) ? 1 : 0,
								'comment_ids'	=> implode('|', $comments)
								);

		if ($this->input->get_post('keywords') !== FALSE)
		{
			$vars['hidden']['keywords'] = $this->input->post('keywords');
			$vars['hidden']['current_page'] = $this->input->post('current_page');
		}

		$message = (count($comments) > 1) ? 'delete_comments_confirm' : 'delete_comment_confirm';

		$vars['message'] = $message;
		$this->load->view('content/delete_comments', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Change Comment Status
	 *
	 * @access	public
	 * @param	string	new status
	 * @return	void
	 */
	function change_comment_status($status='')
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$channel_id		= $this->input->get_post('channel_id');
		$entry_id		= $this->input->get_post('entry_id');
		$current_page	= $this->input->get_post('current_page');

		$comments	= array();

		if (isset($_POST['toggle']) && is_array($_POST['toggle']))
		{
			foreach ($_POST['toggle'] as $key => $val)
			{
				if (substr($val, 0, 1) == 'c')
				{
					$comments[] = $this->db->escape_str(substr($val, 1));
				}
			}
		}

		if($this->input->get_post('comment_id') !== FALSE && is_numeric($this->input->get_post('comment_id')))
		{
			$comments[] = $this->input->get_post('comment_id');
		}

		if (count($comments) == 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_moderate_comments') && ! $this->cp->allowed_group('can_edit_all_comments'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->db->select('entry_id, channel_id, author_id');
		$this->db->where_in('comment_id', $comments);
		$query = $this->db->get('comments');

		// Retrieve Our Results

		if ($query->num_rows() == 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$entry_ids	= array();
		$author_ids = array();
		$channel_ids = array();

		foreach($query->result_array() as $row)
		{
			$entry_ids[]  = $row['entry_id'];
			$author_ids[] = $row['author_id'];
			$channel_ids[] = $row['channel_id'];
		}

		$entry_ids	= array_unique($entry_ids);
		$author_ids = array_unique($author_ids);
		$channel_ids = array_unique($channel_ids);

		/** -------------------------------
		/**	 Change Status
		/** -------------------------------*/

		$status = ($status == 'close' OR (isset($_GET['status']) AND $_GET['status'] == 'close')) ? 'c' : 'o';

		$this->db->set('status', $status);
		$this->db->where_in('comment_id', $comments);
		$this->db->update('comments');

		foreach(array_unique($entry_ids) as $entry_id)
		{
			$query = $this->db->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '".$this->db->escape_str($entry_id)."'");

			$comment_date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;

			$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$this->db->escape_str($entry_id)."' AND status = 'o'");

			$this->db->set('comment_total', $query->row('count'));
			$this->db->set('recent_comment_date', $comment_date);
			$this->db->where('entry_id', $entry_id);
			$this->db->update('channel_titles');
		}

		// Quicker and updates just the channels
		foreach(array_unique($channel_ids) as $channel_id)
		{
			$this->stats->update_comment_stats($channel_id, '', FALSE);
		}

		// Updates the total stats
		$this->stats->update_comment_stats();

		foreach(array_unique($author_ids) as $author_id)
		{
			$res = $this->db->query("SELECT COUNT(comment_id) AS comment_total, MAX(comment_date) AS comment_date FROM exp_comments WHERE author_id = '$author_id'");
			$resrow = $res->row_array();

			$comment_total = $resrow['comment_total'];
			$comment_date  = ( ! empty($resrow['comment_date'])) ? $resrow['comment_date'] : 0;

			$this->db->query($this->db->update_string('exp_members', array('total_comments' => $comment_total, 'last_comment_date' => $comment_date), "member_id = '$author_id'"));
		}

		/** ----------------------------------------
		/**	 Send email notification
		/** ----------------------------------------*/
		if ($status == 'o')
		{
			/** ----------------------------------------
			/**	 Instantiate Typography class
			/** ----------------------------------------*/

			$this->load->library('typography');
			$this->typography->initialize();
			$this->typography->parse_images = FALSE;

			/** ----------------------------------------
			/**	 Go Through Array of Entries
			/** ----------------------------------------*/

			foreach ($comments as $comment_id)
			{
				$this->db->select('comment, name, email, comment_date, entry_id');
				$query = $this->db->get_where('comments', array('comment_id' => $comment_id));

				/*
				Find all of the unique commenters for this entry that have
				notification turned on, posted at/before this comment
				and do not have the same email address as this comment.
				*/

				$results = $this->db->query("SELECT DISTINCT(email), name, comment_id
										FROM exp_comments
										WHERE status = 'o'
										AND entry_id = '".$this->db->escape_str($query->row('entry_id') )."'
										AND notify = 'y'
										AND email != '".$this->db->escape_str($query->row('email') )."'
										AND comment_date <= '".$this->db->escape_str($query->row('comment_date') )."'");

				$recipients = array();

				if ($results->num_rows() > 0)
				{
					foreach ($results->result_array() as $row)
					{
						$recipients[] = array($row['email'], $row['comment_id'], $row['name']);
					}
				}

				$email_msg = '';

				if (count($recipients) > 0)
				{
					$comment = $this->typography->parse_type( $query->row('comment') ,
													array(
															'text_format'	=> 'none',
															'html_format'	=> 'none',
															'auto_links'	=> 'n',
															'allow_img_url' => 'n'
														)
												);

					$qs = ($this->config->item('force_query_string') == 'y') ? '' : '?';

					$action_id	= $this->functions->fetch_action_id('Comment_mcp', 'delete_comment_notification');

					$results = $this->db->query("SELECT wt.title, wt.url_title, w.channel_title, w.comment_url, w.channel_url
											FROM exp_channel_titles wt, exp_channels w
											WHERE wt.entry_id = '".$this->db->escape_str($query->row('entry_id') )."'
											AND wt.channel_id = w.channel_id");

					$com_url = ($results->row('comment_url')  == '') ? $results->row('channel_url')	 : $results->row('comment_url') ;

					$swap = array(
									'name_of_commenter'			=> $query->row('name') ,
									'name'						=> $query->row('name') ,
									'channel_name'				=> $results->row('channel_title') ,
									'entry_title'				=> $results->row('title') ,
									'site_name'					=> stripslashes($this->config->item('site_name')),
									'site_url'					=> $this->config->item('site_url'),
									'comment'					=> $comment,
									'comment_id'				=> $comment_id,
									'comment_url'				=> $this->functions->remove_double_slashes($com_url.'/'.$results->row('url_title') .'/')
								 );

					$template = $this->functions->fetch_email_template('comment_notification');
					$email_tit = $this->functions->var_swap($template['title'], $swap);
					$email_msg = $this->functions->var_swap($template['data'], $swap);

					/** ----------------------------
					/**	 Send email
					/** ----------------------------*/

					$this->load->library('email');

					$this->email->wordwrap = true;


					// Load the text helper
					$this->load->helper('text');

					$sent = array();

					foreach ($recipients as $val)
					{
						if ( ! in_array($val['0'], $sent))
						{
							$title	 = $email_tit;
							$message = $email_msg;

							// Deprecate the {name} variable at some point
							$title	 = str_replace('{name}', $val['2'], $title);
							$message = str_replace('{name}', $val['2'], $message);

							$title	 = str_replace('{name_of_recipient}', $val['2'], $title);
							$message = str_replace('{name_of_recipient}', $val['2'], $message);


							$title	 = str_replace('{notification_removal_url}', $this->functions->fetch_site_index(0, 0).$qs.'ACT='.$action_id.'&id='.$val['1'], $title);
							$message = str_replace('{notification_removal_url}', $this->functions->fetch_site_index(0, 0).$qs.'ACT='.$action_id.'&id='.$val['1'], $message);

							$this->email->EE_initialize();
							$this->email->from($this->config->item('webmaster_email'), $this->config->item('webmaster_name'));
							$this->email->to($val['0']);
							$this->email->subject($title);
							$this->email->message(entities_to_ascii($message));
							$this->email->send();

							$sent[] = $val['0'];
						}
					}
				}
			}
		}

		$this->functions->clear_caching('all');

		$val = ($this->input->get_post('validate') == 1) ? AMP.'validate=1' : '';

		if ($this->input->get_post('search_in') !== FALSE)
		{
			$url = BASE.AMP.'C=content_edit'.
						AMP.'M=index'.
						AMP.'search_in=comments'.
						AMP.'rownum='.$this->input->get_post('current_page').
						AMP.'order=desc'.
						AMP.'keywords='.$this->input->get_post('keywords');
		}
		else
		{
			$url = BASE.AMP.'C=content_edit'.AMP.'M=view_comments'.AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id.AMP.'current_page='.$current_page.AMP.'U=1'.$val;
		}

		$this->session->set_flashdata('message_success', $this->lang->line('status_changed'));
		$this->functions->redirect($url);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Comment
	 *
	 * @access	public
	 * @return	void
	 */
	function delete_comment()
	{
		if ( ! $this->cp->allowed_group('can_access_content'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$comment_id = $this->input->post('comment_ids');

		if ($comment_id == FALSE)
		{
			show_error($this->lang->line('unauthorized_access'));
		}


		if ( ! preg_match("/^[0-9]+$/", str_replace('|', '', $comment_id)))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->db->select('channel_titles.author_id, channel_titles.entry_id, channel_titles.channel_id, channel_titles.comment_total');
		$this->db->from(array('channel_titles', 'comments'));
		$this->db->where('channel_titles.entry_id = '.$this->db->dbprefix('comments.entry_id'));
		$this->db->where_in('comments.comment_id', explode('|', $comment_id));

		$query = $this->db->get();

		if ($query->num_rows() == 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$entry_ids	= array();
		$author_ids = array();
		$channel_ids = array();

		foreach($query->result_array() as $row)
		{
			$entry_ids[]  = $row['entry_id'];
			$author_ids[] = $row['author_id'];
			$channel_ids[] = $row['channel_id'];
		}

		$entry_ids	= array_unique($entry_ids);
		$author_ids = array_unique($author_ids);
		$channel_ids = array_unique($channel_ids);

		/** -------------------------------
		/**	 Validation Checks
		/** -------------------------------*/

		if ($this->input->get_post('validate') == 1)
		{
			if ( ! $this->cp->allowed_group('can_moderate_comments'))
			{
				show_error($this->lang->line('unauthorized_access'));
			}

			$this->db->where_in('comment_id', explode('|', $comment_id));
			$count = $this->db->count_all_results('comments');

			if ($count == 0)
			{
				show_error($this->lang->line('unauthorized_access'));
			}
		}
		else
		{
			if ( ! $this->cp->allowed_group('can_delete_all_comments'))
			{
				if ( ! $this->cp->allowed_group('can_delete_own_comments'))
				{
					show_error($this->lang->line('unauthorized_access'));
				}
				else
				{
					foreach($query->result_array() as $row)
					{
						if ($row['author_id'] != $this->session->userdata('member_id'))
						{
							show_error($this->lang->line('unauthorized_access'));
						}
					}
				}
			}
		}

		/** --------------------------------
		/**	 Update Entry and Channel Stats
		/** --------------------------------*/

		$this->db->where_in('comment_id', explode('|', $comment_id));
		$this->db->delete('comments');

		foreach($entry_ids as $entry_id)
		{
			$query = $this->db->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '".$this->db->escape_str($entry_id)."'");

			$comment_date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;

			$query = $this->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$this->db->escape_str($entry_id)."' AND status = 'o'");

			$this->db->set('comment_total', $query->row('count'));
			$this->db->set('recent_comment_date', $comment_date);
			$this->db->where('entry_id', $entry_id);
			$this->db->update('channel_titles');
		}

		// Quicker and updates just the channels
		foreach($channel_ids as $channel_id) { $this->stats->update_comment_stats($channel_id, '', FALSE); }

		// Updates the total stats
		$this->stats->update_comment_stats();

		foreach($author_ids as $author_id)
		{
			$res = $this->db->query("SELECT COUNT(comment_id) AS comment_total, MAX(comment_date) AS comment_date FROM exp_comments WHERE author_id = '$author_id'");
			$resrow = $res->row_array();

			$comment_total = $resrow['comment_total'] ;
			$comment_date  = ( ! empty($resrow['comment_date'])) ? $resrow['comment_date'] : 0;

			$this->db->query($this->db->update_string('exp_members', array('total_comments' => $comment_total, 'last_comment_date' => $comment_date), "member_id = '$author_id'"));
		}

		$msg = $this->lang->line('comment_deleted');

		/* -------------------------------------------
		/* 'delete_comment_additional' hook.
		/*  - Add additional processing on comment delete
		*/
			$edata = $this->extensions->call('delete_comment_additional');
			if ($this->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		$this->functions->clear_caching('all');

		$this->session->set_flashdata('message_success', $msg);
		
		if ($this->input->post('validate') == 1)
		{
			$this->functions->redirect(BASE.AMP.'C=content_edit'.AMP.'M=view_comments'.AMP.'validate=1');
		}

		if ($this->input->get_post('keywords') != '')
		{
			$url = BASE.AMP.'C=content_publish'.
						AMP.'M=edit_entries'.
						AMP.'search_in=comments'.
						AMP.'rownum='.$this->input->get_post('current_page').
						AMP.'order=desc'.
						AMP.'keywords='.$this->input->get_post('keywords');

			$this->functions->redirect($url);
		}
		else
		{
			$this->functions->redirect(BASE.AMP.'C=content_edit'.AMP.'M=view_comments'.
			AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id);
		}

	}
}
// END CLASS

/* End of file edit.php */
/* Location: ./system/expressionengine/controllers/cp/edit.php */