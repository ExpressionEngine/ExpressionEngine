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
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Content_files extends CI_Controller {

	private $_upload_dirs    = array();
	private $_base_url       = '';
	private $remove_spaces    = TRUE;
	private $temp_prefix      = "temp_file_";

	private $nest_categories = 'y';
	private $pipe_length     = 3;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		// Permissions
		if ( ! $this->cp->allowed_group('can_access_content')  OR
			 ! $this->cp->allowed_group('can_access_files'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('filemanager');
		$this->load->library(array('filemanager'));
		$this->load->helper(array('form'));
		$this->load->model('file_model');

		// Get upload dirs
		$upload_dirs = $this->filemanager->fetch_upload_dirs();

		foreach ($upload_dirs as $row)
		{
			$this->_upload_dirs[$row['id']] = $row;
		}

		if (AJAX_REQUEST)
        {
            $this->output->enable_profiler(FALSE);
        }

		$this->cp->set_right_nav(array(
			'directory_manager' => BASE.AMP.'C=content_files'.AMP.'M=file_upload_preferences',
			'watermark_prefs'	=> BASE.AMP.'C=content_files'.AMP.'M=watermark_preferences',
			'batch_upload'		=> BASE.AMP.'C=content_files'.AMP.'M=batch_upload'
		));

		$this->_base_url = BASE.AMP.'C=content_files';
	}

	// ------------------------------------------------------------------------

	/**
	 * Index Page
	 */
	public function index()
	{
		$this->load->library(array('pagination'));
		$this->load->helper(array('string', 'search'));
		$this->api->instantiate('channel_categories');

		// Page Title
		$this->cp->set_variable('cp_page_title', lang('content_files'));

		$this->cp->add_js_script(array(
			'plugin'	=> array('overlay', 'overlay.apple',
								'ee_upload', 'dataTables'),
			'file'		=> 'cp/files/file_manager_home',
			'ui' 		=> 'datepicker'));

		$upload_dirs_options = array();

		// This is temporary for just a bit.
		$comments_enabled = FALSE;
		$table_columns = ($comments_enabled) ? 9: 8;

		// Setup get/post vars in class vars
		$get_post = $this->_fetch_get_post_vars();

		// Get array of allowed upload dirs, or error out.
		$allowed_dirs = $this->_setup_allowed_dirs();
		$total_dirs = count($allowed_dirs);

		$this->javascript->set_global(array(
			'file.pipe' 		=> $this->pipe_length,
			'file.perPage'		=> $get_post['per_page'],
			'file.themeUrl'		=> $this->cp->cp_theme_url,
			'file.tableColumns'	=> $table_columns,
			'lang.noEntries'	=> lang('no_entries_matching_that_criteria'))
		);

		// Create our various filter data

		foreach ($this->_upload_dirs as $k => $dir)
		{
			$upload_dirs_options[$dir['id']] = $dir['name'];
			$allowed_dirs[] = $k;
		}

		$upload_dirs_options['null'] = lang('filter_by_directory');

		if (count($upload_dirs_options) > 2)
		{
			$upload_dirs_options['all'] = lang('all');
		}

		ksort($upload_dirs_options);

		$selected_dir = ($selected_dir = $this->input->get_post('directory')) ? $selected_dir : NULL;

		// We need this for the filter, so grab it now
		$cat_form_array = $this->api_channel_categories->category_form_tree($this->nest_categories);

		// If we have channels we'll write the JavaScript menu switching code
		if (count($allowed_dirs) > 0)
		{
			$this->filtering_menus($cat_form_array);
		}

		// Cat filter
		$cat_group = ($selected_dir !== NULL) ? $this->_upload_dirs[$selected_dir]['cat_group']: '';
		$category_options = $this->category_filter_options($cat_group, $cat_form_array, count($allowed_dirs));

		// Date range pull-down menu
		$date_selected = $get_post['date_range'];

		$date_select_options = array(
			''				=> lang('date_range'),
			'1'				=> lang('past_day'),
			'7'				=> lang('past_week'),
			'31'			=> lang('past_month'),
			'182'			=> lang('past_six_months'),
			'365'			=> lang('past_year'),
			'custom_date'	=> lang('any_date'));

		$type_select_options = array(
			'1'				=> lang('file_type'),
			'all'			=> lang('all'),
			'image'			=> lang('image'),
			'non-image'		=> lang('non-image'));

		$search_select_options = array(
			''				=> lang('search_in'),
			'file_name'		=> lang('file_name'),
			'file_title'	=> lang('file_title'),
			'custom_field'	=> lang('custom_fields'),
			'all'			=> lang('all'));

		$no_upload_dirs = FALSE;

		if (empty($this->_upload_dirs))
		{
			$no_upload_dirs = TRUE;
		}
		else
		{
			$dirs = ($get_post['dir_id'] === FALSE) ? $this->_allowed_dirs : $get_post['dir_id'];

			$filtered_entries = $this->file_model->get_files($dirs, $get_post['cat_id'], $get_post['type'], 
															$get_post['per_page'], $get_post['offset'], 
															$get_post['keywords'], $get_post['order'],
															TRUE, $get_post['search_in']);

			$files = $filtered_entries['results'];
			$total_filtered = $filtered_entries['filter_count'];

			// No result?  Show the "no results" message
			if ( ! $files)
			{
				// no results-- bail
			}

			$dir_size = 0;

			$total_rows = $this->file_model->count_files($allowed_dirs);

			$file_list = $this->_fetch_file_list($files, $total_filtered);

			$base_url = $this->_base_url.AMP.'directory='.$selected_dir.AMP.'per_page='.$get_post['per_page'];
			$qstr_seg = 'offset';

			$this->_setup_pagination($base_url, $total_rows, $get_post['per_page'], $qstr_seg);

			$action_options = array(
				'download'			=> lang('download_selected'),
				'delete'			=> lang('delete_selected_files')
			);

			// Figure out where the count is starting
			// and ending for the dialog at the bottom of the page
			$offset = ($this->input->get($qstr_seg)) ? $this->input->get($qstr_seg) : 0;
			$count_from = $offset + 1;
			$count_to = $offset + count($file_list);

			$pagination_count_text = sprintf(lang('pagination_count_text'),
											$count_from, $count_to, $total_rows);
		}

		$data = array(
			'action_options' 		=> (isset($action_options)) ? $action_options : NULL,
			'category_options' 		=> $category_options,
			'comments_enabled'		=> $comments_enabled,
			'date_select_options'	=> $date_select_options,
			'dir_size'				=> (isset($dir_size)) ? $dir_size : NULL,
			'files'					=> (isset($file_list)) ? $file_list : array(),
			'keywords'				=> $get_post['keywords'],
			'no_upload_dirs'		=> $no_upload_dirs,
			'pagination_count_text'	=> (isset($pagination_count_text)) ? $pagination_count_text : NULL,
			'pagination_links'		=> $this->pagination->create_links(),
			'search_in_options'		=> $search_select_options,
			'selected_cat_id'		=> $get_post['cat_id'],
			'selected_date'			=> $get_post['date_range'],
			'selected_dir'			=> $selected_dir,
			'selected_search'		=> $get_post['search_type'],
			'selected_type'			=> $get_post['file_type'],
			'type_select_options'	=> $type_select_options,
			'upload_dirs_options' 	=> $upload_dirs_options
		);

		$this->javascript->compile();
		$this->load->view('content/files/index', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * File ajax filter
	 */
	public function file_ajax_filter()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error(lang('unauthorized_access'));
		}

		// Setup get/post vars in class vars
		$this->_fetch_get_post_vars();

		// Setup get/post vars in class vars
		$get_post = $this->_fetch_get_post_vars();

		$allowed_dirs = $this->_setup_allowed_dirs();

		$dirs = ($get_post['dir_id'] === FALSE) ? $this->_allowed_dirs : $get_post['dir_id'];

		/* Ordering */
		$order = array();
		$col_map = array('file_id', 'title', 'file_name', 'mime_type',
						'upload_location_id', 'upload_date', '', '');

		if ($this->input->get('iSortCol_0'))
		{
			for ( $i=0; $i < $this->input->get('iSortingCols'); $i++ )
			{
				if (isset($col_map[$this->input->get('iSortCol_'.$i)]))
				{
					$order[$col_map[$this->input->get('iSortCol_'.$i)]] = ($this->input->get('sSortDir_'.$i) == 'asc') ? 'asc' : 'desc';
				}
			}
		}

		$filtered_entries = $this->file_model->get_files($dirs, $get_post['cat_id'], $get_post['type'], 
														$get_post['per_page'], $get_post['offset'],		
														$get_post['keywords'], $order, 
														TRUE, $get_post['search_in']);

		$files = $filtered_entries['results'];
		$total_filtered = $filtered_entries['filter_count'];

		// No result?  Show the "no results" message
		if ( ! $files)
		{
			// no results-- bail
		}

		$dir_size = 0;

		$response = array(
			'sEcho' 				=> $get_post['sEcho'],
			'iTotalRecords' 		=> $this->file_model->count_files($allowed_dirs),
			'iTotalDisplayRecords' 	=> $total_filtered,
			'aaData'				=> $this->_fetch_file_list($files, $total_filtered)
		);


		$this->output->send_ajax_response($response);
	}


	// --------------------------------------------------------------------

	/**
	 * Fetch File List
	 *
	 * This function grabs the list of files for the index() function files table
	 *
	 * @param	array		array of files
	 * @param	int			total number of filtered results
	 * @return	string		the raw HTML string
	 */
	private function _fetch_file_list($files, $total_filtered)
	{
		$file_list = array();

		if ($total_filtered > 0)
		{
			// Date
			$date_fmt = ($this->session->userdata('time_format') != '') ?
							$this->session->userdata('time_format') : $this->config->item('time_format');

			$datestr = ($date_fmt == 'us') ? '%m/%d/%y %h:%i %a' : '%Y-%m-%d %H:%i';

			$i = 0;

			// Setup file list
			foreach ($files->result_array() as $k => $file)
			{
				$is_image = FALSE;

				$file_location = $this->functions->remove_double_slashes(
					$this->_upload_dirs[$file['upload_location_id']]['url'].'/'.$file['file_name']);

				$file_path = $this->functions->remove_double_slashes(
					$this->_upload_dirs[$file['upload_location_id']]['server_path'].'/'.$file['file_name']);

				$r[] = $file['file_id'];
				$r[] = $file['title'];

				// Lightbox links
				if (strncmp($file['mime_type'], 'image', 5) === 0)
				{
					$is_image = TRUE;
					$r[] = '<a class="less_important_link overlay" id="img_'.str_replace(array(".", ' '), '', $file['file_name']).'" href="'.$file_location.'" title="'.$file['file_name'].'" rel="#overlay">'.$file['file_name'].'</a>';
				}
				else
				{
					$r[] = $file['file_name'];
				}

				$r[] = $file['mime_type'];
				$r[] = $this->_upload_dirs[$file['upload_location_id']]['name'];
				$r[] = $this->localize->set_human_time($file['upload_date'], TRUE);


				$action_base = BASE.AMP.'C=content_files'.AMP.'M=multi_edit_form'.AMP.'file='.$file['file_id'];
				$actions = '<a href="'.$action_base.AMP.'action=download" title="'.lang('file_download').'"><img src="'.$this->cp->cp_theme_url.'images/icon-download-file.png"></a>';
				$actions .= '&nbsp;&nbsp;';
				$actions .= '<a href="'.$action_base.AMP.'action=delete" title="'.lang('delete_selected_files').'"><img src="'.$this->cp->cp_theme_url.'images/icon-delete.png"></a>';

				if ($is_image)
				{
					$actions .= '&nbsp;&nbsp;';
					$actions .= '<a href="'.BASE.AMP.'C=content_files'.AMP.'M=edit_image'.AMP.'upload_dir='.$file['upload_location_id'].AMP.'file='.$file['file_id'].'" title="'.lang('edit_file').'"><img src="'.$this->cp->cp_theme_url.'images/icon-edit.png" alt="'.lang('delete').'" /></a>';
				}

				$r[] = $actions;
				$r[] = form_checkbox('toggle[]', $file['file_id'], '', ' class="toggle" id="toggle_box_'.$file['file_id'].'"');

				$file_list[$i] = $r;
				unset($r);
				$i++;
			}
		}

		return $file_list;
	}

	// --------------------------------------------------------------------

	/**
	 * Setup Pagination
	 *
	 * This function is used to setup pagination for the index() method and
	 * the datatables calls.
	 *
	 * @param 	int		base url to feed to the pagination class
	 * @param 	int		total results to paginate
	 * @param	int		total number of results to display per page
	 * @param 	str		uri segment to paginate on.
	 * @return 	void
	 */
	private function _setup_pagination($base_url, $total_rows, $per_page, $qstr_seg)
	{
		$link = "<img src=\"{$this->cp->cp_theme_url}images/pagination_%s_button.gif\" width=\"13\" height=\"13\" alt=\"%s\" />";

		$p_config = array(
			'base_url'				=> $base_url,
			'total_rows'			=> $total_rows,
			'per_page'				=> $per_page,
			'page_query_string'		=> TRUE,
			'query_string_segment'	=> $qstr_seg,
			'full_tag_open'			=> '<p id="paginationLinks">',
			'full_tag_close'		=> '</p>',
			'prev_link'				=> sprintf($link, 'prev', '&lt;'),
			'next_link'				=> sprintf($link, 'next', '&gt;'),
			'first_link'			=> sprintf($link, 'first', '&lt; &lt;'),
			'last_link'				=> sprintf($link, 'last', '&gt; &gt;'));

		$this->pagination->initialize($p_config);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Get/Post variables
	 *
	 * GET/POST variables are just a wee bit different when a jquery datatables
	 * request is made.  In order to keep stupid IE from caching the ajax request,
	 * we add a time= variable to the request.  So here, we can safely assume that
	 * a request from datatables will have $_['GET']['time'] in it.
	 * There are just a coupla differences, so we construct our array of get/post
	 * vars and return 'er/
	 *
	 * @return 	array
	 */
	private function _fetch_get_post_vars()
	{
		$this->load->helper('search');
		
		$ret = array(
			'author_id'		=> $this->input->get_post('author_id'),
			'cat_id'		=> $this->input->get_post('cat_id'),
			'dir_id'		=> ($this->input->get_post('dir_id') != 'all') ? $this->input->get_post('dir_id') : array(),
			'date_range'	=> $this->input->get_post('date_range'),
			'file_type'		=> $this->input->get_post('file_type'),
			'keywords'		=> NULL, // Process this in a bit
			'offset'		=> ($offset = $this->input->get('offset')) ? $offset : 0,
			'order'			=> ($order = $this->input->get('offset')) ? $order : 0,
			'per_page'		=> ($per_page = $this->input->get('per_page')) ? $per_page : 40,
			'status'		=> ($this->input->get_post('status') != 'all') ? $this->input->get_post('status') : '',
			'search_in'		=> ($this->input->get_post('search_in')),
			'search_type'	=> $this->input->get_post('search_type'),
			'type'			=> ($type = $this->input->get_post('type')) ? $type : 'all'
		);

		// If the request is coming from datatables, we add time= to the
		// query string.  So, it's safe to assume that we can test it that way
		if ( ! $this->input->get('time'))
		{
			if ($this->input->post('keywords'))
			{
				$ret['keywords'] = sanitize_search_terms($this->input->post('keywords'));
			}
			elseif ($this->input->get('keywords'))
			{
				$ret['keywords'] = sanitize_search_terms(base64_decode($this->input->get('keywords')));
			}
		}
		else
		{
			$ret['keywords'] = ($this->input->get_post('keywords')) ? sanitize_search_terms($this->input->get_post('keywords')) : '';
			$ret['perpage'] = $this->input->get_post('iDisplayLength');
			$ret['offset'] = ($this->input->get_post('iDisplayStart')) ? $this->input->get_post('iDisplayStart') : 0; // Display start point
			$ret['sEcho'] = $this->input->get_post('sEcho');
		}

		return $ret;
	}

	// --------------------------------------------------------------------

	/**
	 * Setup Allowed Upload Directories
	 *
	 * Cycles through upload dirs, errors out if there aren't any upload dirs
	 * available.
	 *
	 * @return array
	 */
	private function _setup_allowed_dirs()
	{
		if ( ! empty($this->_allowed_dirs))
		{
			return $this->_allowed_dirs;
		}

		foreach ($this->_upload_dirs as $k => $v)
		{
			$this->_allowed_dirs[] = $k;
		}

		if (empty($this->_allowed_dirs))
		{
			show_error(lang('unauthorized_access'));
		}

		return $this->_allowed_dirs;
	}

	// --------------------------------------------------------------------

	/**
	 * Category Filter Options
	 *
	 * @param
	 */
	function category_filter_options($cat_group, $cat_form_array, $total_dirs)
	{
		$category_select_options[''] = lang('filter_by_category');

		if ($total_dirs > 1)
		{
			$category_select_options['all'] = lang('all');
		}

		$category_select_options['none'] = lang('none');

		if ($cat_group != '')
		{
			foreach($cat_form_array as $key => $val)
			{
				if ( ! in_array($val[0], explode('|',$cat_group)))
				{
					unset($cat_form_array[$key]);
				}
			}

			$i = 1;
			$new_array = array();

			foreach ($cat_form_array as $ckey => $cat)
			{
		    	if ($ckey - 1 < 0 OR ! isset($cat_form_array[$ckey - 1]))
    		   	{
					$category_select_options['NULL_'.$i] = '-------';
            	}

				$category_select_options[$cat[1]] = (str_replace("!-!","&nbsp;", $cat[2]));

            	if (isset($cat_form_array[$ckey + 1]) && $cat_form_array[$ckey + 1][0] != $cat[0])
	        	{
					$category_select_options['NULL_'.$i] = '-------';
       			}

       			$i++;
			}
		}

		return $category_select_options;
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
			show_error(lang('unauthorized_access'));
		}

		// In order to build our filtering options we need to gather
		// all the channels and categories
		$dir_array	= array();

		foreach ($this->_upload_dirs as $id => $row)
		{
			$dir_array[$id] = array(str_replace('"','',$this->_upload_dirs[$id]['name']), $this->_upload_dirs[$id]['cat_group']);
		}

		$default_cats[] = array('', lang('filter_by_category'));
		$default_cats[] = array('all', lang('all'));
		$default_cats[] = array('none', lang('none'));


		$file_info[0]['categories'] = $default_cats;

		foreach ($dir_array as $key => $val)
		{
			$any = 0;
			$cats = $default_cats;

			if (count($cat_form_array) > 0)
			{
				$last_group = 0;

				foreach ($cat_form_array as $k => $v)
				{
					if (in_array($v[0], explode('|', $val[1])))
					{
						if ($last_group == 0 OR $last_group != $v[0])
						{
							$cats[] = array('', '-------');
							$last_group = $v[0];
						}

						$cats[] = array($v[1], $v[2]);
					}
				}
			}

			$file_info[$key]['categories'] = $cats;
		}

		$this->javascript->set_global('file.directoryInfo', $file_info);
	}

	// ------------------------------------------------------------------------

	/**
	 * Upload File
	 */
	public function upload_file()
	{
		if (empty($_POST))
		{
			show_error(lang('unauthorized_access'));
		}

		// Do some basic permissions checking
		if ( ! ($file_dir = $this->input->get_post('upload_dir')))
		{
			show_error(lang('unauthorized_access'));
		}

		// Bail if they dont' have access to this upload location.
		if ( ! array_key_exists($file_dir, $this->_upload_dirs))
		{
			show_error(lang('unauthorized_access'));
		}

		/*
		All the directory information we need for the upload
		destination.

		array
		  'id' => string '1' (length=1)
		  'site_id' => string '1' (length=1)
		  'name' => string 'Main Upload Directory' (length=21)
		  'server_path' => string '/Volumes/Development/ee/ee2/images/uploads/' (length=43)
		  'url' => string 'http://10.0.0.5/ee/ee2/images/uploads/' (length=38)
		  'allowed_types' => string 'all' (length=3)
		  'max_size' => string '' (length=0)
		  'max_height' => string '' (length=0)
		  'max_width' => string '' (length=0)
		  'properties' => string 'style="border: 0;" alt="image"' (length=30)
		  'pre_format' => string '' (length=0)
		  'post_format' => string '' (length=0)
		  'file_properties' => string '' (length=0)
		  'file_pre_format' => string '' (length=0)
		  'file_post_format' => string '' (length=0)
		*/

		$fm = $this->filemanager->save($this->_upload_dirs[$file_dir]);


		if ($fm->upload_errors)
		{
			// Upload Failed
			if ($this->input->is_ajax_request())
			{
				$errors = $this->javascript->generate_json(
							array('error' => $this->upload->display_errors()));

				echo sprintf("<script type=\"text/javascript\">
								parent.EE_uploads.%s = %s;</script>",
								$this->input->get('frame_id'),
								$errors);
				exit();
			}

			$this->session->set_flashdata('message_failure', $fm->upload_errors);
			$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'directory='.$file_dir);
		}

		if ($fm->upload_data['file_name'] != $fm->upload_data['orig_name'])
		{
			// Page Title
			$this->cp->set_variable('cp_page_title', lang('file_exists_warning'));
			$this->cp->set_breadcrumb(BASE.AMP.'C=content_files', lang('file_manager'));

			$vars = $fm->upload_data;
			$vars['duped_name'] = $fm->upload_data['orig_name'];

			$vars['hidden'] = array(
				'orig_name'		=> $fm->upload_data['orig_name'],
				'rename_attempt' => '',
				'is_image' 		=> $fm->upload_data['is_image'],
				'temp_file_name'=> $fm->upload_data['file_name'],
				'remove_spaces'	=> '1',
				 'id' 			=> $file_dir
				);

			return $this->load->view('content/files/rename', $vars);
		}

		// Make the thumbnail
		$thumb = $fm->create_thumb(
			array('server_path' => $fm->upload_data['file_path']),
			array('name' => $fm->upload_data['file_name'])
		);

		if ($this->input->is_ajax_request())
		{
			$resp = $this->javascript->generate_json(array(
				'success'		=> lang('upload_success'),
				'filename'		=> $fm->upload_data['file_name'],
				'filesize'		=> $fm->upload_data['file_size'],
				'filetype'		=> $fm->upload_data['file_type'],
				'date'			=> date('M d Y - H:ia')
			));

			echo sprintf('<script type="text/javascript">
							parent.EE_uploads.%s = %s;</script>',
						$this->input->get('frame_id'), $resp);
			exit();
		}

		$this->session->set_flashdata('message_success', lang('upload_success'));
		$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'directory='.$file_dir);
	}

	// ------------------------------------------------------------------------

	/**
	 * Allows renaming and over writing of files
	 */
	public function rename_file()
	{
		$required = array('file_name', 'rename_attempt', 'orig_name', 'temp_file_name',
							'is_image', 'temp_prefix', 'remove_spaces', 'id');

		foreach ($required as $val)
		{
			$data[$val] = $this->input->post($val);
		}

		// Sigh- did they rename it w/an existing name?  We give them the rename form again.
        if (($data['rename_attempt'] != '' && $data['rename_attempt'] != $data['file_name'])
			OR ($data['rename_attempt'] == '' && $data['orig_name'] != $data['file_name']))
        {
			if (file_exists($this->_upload_dirs[$data['id']]['server_path'].$data['file_name']))
			{

				// Page Title
				$this->cp->set_variable('cp_page_title', lang('file_exists_warning'));
				$this->cp->set_breadcrumb(BASE.AMP.'C=content_files', lang('file_manager'));

				$vars['file_name'] = $data['file_name'];
				$vars['duped_name'] = ($data['file_name'] != '') ? $data['file_name'] : $data['orig_name'];

				$vars['hidden'] = array(
					'orig_name'		=> $data['orig_name'],
					'rename_attempt' => $data['file_name'],
					'is_image' 		=> $data['is_image'],
					'temp_file_name'=> $data['temp_file_name'],
					'remove_spaces'	=> $this->remove_spaces,
				 	'id' 			=> $data['id']
					);

				return $this->load->view('content/files/rename', $vars);
			}
		}

		$fm = $this->filemanager->replace_file($data);

		// Errors?
		if ($fm->upload_errors)
		{
			$this->session->set_flashdata('message_failure', $fm->upload_errors);
			$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'directory='.$data['id']);
		}

		// Woot- Success!  Make a new thumb
		$thumb = $fm->create_thumb(
			array('server_path' => $this->_upload_dirs[$data['id']]['server_path']),
			array('name' => $data['file_name'])
		);

		$this->session->set_flashdata('message_success', lang('upload_success'));
		$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'directory='.$data['id']);
	}

	// ------------------------------------------------------------------------

	/**
	 * Controls the batch actions
	 *
	 * When submitted to, expects a GET/POST variable named action containing
	 * either download or delete
	 */
	public function multi_edit_form()
	{
		$files = $this->_get_file_settings();
		
		switch ($this->input->get_post('action'))
		{
			case 'download':
				$this->_download_files($files);
				break;

			case 'delete':
				$this->_delete_files_confirm($files);
				break;

			default:
				show_error(lang('unauthorized_access'));
				break;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Creates the confirmation page to delete a list of files
	 *
	 * @param array $files Array of file names to delete
	 * @param integer $file_dir ID of the directory to delete from
	 * @return void
	 */
	private function _delete_files_confirm($files)
	{
		$data = array(
			'files'			=> $files,
			'del_notice'	=> (count($files) == 1) ? 'confirm_del_file' : 'confirm_del_files'
		);

		$this->cp->set_variable('cp_page_title', lang('delete_selected_files'));

		$this->load->view('content/files/confirm_file_delete', $data);
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete a list of files (and their thumbnails) from a particular directory
	 * Expects two GET/POST variables:
	 *  - file: an array of file ids to delete
	 *  - file_dir: the ID of the file directory to delete from
	 */
	public function delete_files()
	{
		$files = $this->input->get_post('file');

		if ( ! $files)
		{
			$this->session->set_flashdata('message_failure', lang('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		$delete = $this->filemanager->delete($files, TRUE);

		$message_type = ($delete) ? 'message_success' : 'message_failure';
		$message = ($delete) ? lang('delete_success') : lang('message_failure');

		$this->session->set_flashdata($message_type, $message);
		$this->functions->redirect(BASE.AMP.'C=content_files');
	}

	// ------------------------------------------------------------------------

	/**
	 * Download Files
	 *
	 * @param array $files Array of file names to download
	 */
	private function _download_files($files)
	{
		if (empty($files))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $this->filemanager->download_files($files))
		{
			$message = (count($files) > 1) ? lang('problem_downloading_file') : lang('problem_downloading_file');
			
			$this->session->set_flashdata('message_failure', $message);
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Get the list of files and the directory ID for batch file actions
	 *
	 * @return array Associative array containing ['file_dir'] as the file directory
	 *    and ['files'] an array of the files to act upon.
	 */
	private function _get_file_settings()
	{
		if ($toggle = $this->input->post('toggle'))
		{
			$files = $toggle;
		}

		if ( ! isset($files))
		{
			// No file, why are we here?
			if ( ! ($files = $this->input->get_post('file')))
			{
				show_error(lang('unauthorized_access'));
			}			
		}
		
		if ( ! is_array($files))
		{
			$files = array($files);
		}

		return $files;
	}

	// ------------------------------------------------------------------------

	/**
	 * Edit Image
	 *
	 * Main method for the image edit page.
	 *
	 * @return void
	 */
	public function edit_image()
	{
		// The form posts to this method, so if POST data is present
		// send to _do_image_processing to, well, do the image processing
		if ( ! empty($_POST))
		{
			return $this->_do_image_processing();
		}

		// Page Title
		$this->cp->set_variable('cp_page_title', lang('edit_image'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=content_files', lang('file_manager'));

		// Do some basic permissions checking
		if ( ! ($file_dir = $this->input->get('upload_dir')))
		{
			show_error(lang('unauthorized_access'));
		}

		// Bail if they dont' have access to this upload location.
		if ( ! array_key_exists($file_dir, $this->_upload_dirs))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
		$this->output->set_header("Pragma: no-cache");

		$this->cp->add_js_script(array(
			'file'		=> 'cp/files/file_manager_edit',
			'plugin'	=> 'jcrop',
			)
		);

		$qry = $this->db->select('file_name')
						->where('file_id', $this->input->get('file'))
						->get('files');

		$file_name 	= $qry->row('file_name');

		// Some vars for later
		$file_url 	= $this->_upload_dirs[$file_dir]['url'].urldecode($file_name);
		$file_path 	= $this->_upload_dirs[$file_dir]['server_path'].urldecode($file_name);

		// Does this file exist?
		if ( ! file_exists($file_path))
		{
			show_error(lang('unauthorized_access'));
		}

		$file_info = $this->filemanager->get_file_info($file_path);

		$this->javascript->set_global(array(
			'filemanager'	=> array(
				'image_width'	=> $file_info['width'],
				'image_height'	=> $file_info['height'],
			),
		));

		$data = array(
			'file_url'		=> $file_url,
			'file_path'		=> $file_path,
			'file_info'		=> $file_info,
			'upload_dir'	=> $this->_upload_dirs[$file_dir]['id'],
			'file'			=> urlencode($file_name),
			'filemtime'		=> ($filemtime = @filemtime($file_path)) ? $filemtime : 0,
		);

		$this->cp->add_js_script('ui', 'accordion');

		$this->javascript->output('
		        $("#file_manager_toolbar").accordion({autoHeight: false, header: "h3"});
		');

		$this->javascript->compile();

		$this->load->view('content/files/edit_image', $data);
	}

	// ------------------------------------------------------------------------

	/**
	 * image processing
	 *
	 * Figures out the full path to the file, and sends it to the appropriate
	 * method to process the image.
	 */
	private function _do_image_processing()
	{
		$file = $this->input->post('file');

		if ( ! $file)
		{
			$this->session->set_flashdata('message_failure', lang('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		$upload_dir_id = $this->input->post('upload_dir');

		$file = $this->security->sanitize_filename(urldecode($file));
		$file = $this->functions->remove_double_slashes(
				$this->_upload_dirs[$upload_dir_id]['server_path'].DIRECTORY_SEPARATOR.$file);

		switch ($this->input->post('action'))
		{
			case 'rotate':
				$this->_do_rotate($file);
				break;
			case 'crop':
				$this->_do_crop($file);
				break;
			case 'resize':
				$this->_do_resize($file);
				break;
			default:
				return ''; // todo, error
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Image crop
	 */
	private function _do_crop($file)
	{
		$config = array(
			'width'				=> $this->input->post('crop_width'),
			'maintain_ratio'	=> FALSE,
			'x_axis'			=> $this->input->post('crop_x'),
			'y_axis'			=> $this->input->post('crop_y'),
			'height'			=> ($this->input->post('crop_height')) ? $this->input->post('crop_height') : NULL,
			'master_dim'		=> 'width',
			'library_path'		=> $this->config->item('image_library_path'),
			'image_library'		=> $this->config->item('image_resize_protocol'),
			'source_image'		=> $file,
			'new_image'			=> $file
		);

		$this->load->library('image_lib', $config);

		if ( ! $this->image_lib->crop())
		{
	    	$errors = $this->image_lib->display_errors();
		}

		if (isset($errors))
		{
			if (AJAX_REQUEST)
			{
				$this->output->send_ajax_response($errors, TRUE);
			}

			show_error($errors);
		}

		$this->image_lib->clear();

		if (AJAX_REQUEST)
		{
			$dimensions = $this->image_lib->get_image_properties('', TRUE);
			$this->image_lib->clear();

			$this->output->send_ajax_response(array(
				'width'		=> $dimensions['width'],
				'height'	=> $dimensions['height']
			));
		}

		$this->session->set_flashdata('message_success', lang('file_saved'));
		$url = BASE.AMP.'C=content_files'.AMP.'M=edit_image'.AMP.'upload_dir='.$this->input->post('upload_dir').AMP.'file='.$this->input->post('file');
		$this->functions->redirect($url);
	}

	// ------------------------------------------------------------------------

	/**
	 * Do image rotation.
	 */
	private function _do_rotate($file)
	{
		$config = array(
			'rotation_angle'	=> $this->input->post('rotate'),
			'library_path'		=> $this->config->item('image_library_path'),
			'image_library'		=> $this->config->item('image_resize_protocol'),
			'source_image'		=> $file,
			'new_image'			=> $file
		);

		$this->load->library('image_lib', $config);

		if ( ! $this->image_lib->rotate())
		{
	    	$errors = $this->image_lib->display_errors();
		}

		if (isset($errors))
		{
			if (AJAX_REQUEST)
			{
				$this->output->send_ajax_response($errors, TRUE);
			}

			show_error($errors);
		}

		$this->image_lib->clear();

		if (AJAX_REQUEST)
		{
			$dimensions = $this->image_lib->get_image_properties('', TRUE);
			$this->image_lib->clear();

			$this->output->send_ajax_response(array(
				'width'		=> $dimensions['width'],
				'height'	=> $dimensions['height']
			));
		}

		$this->session->set_flashdata('message_success', lang('file_saved'));
		$url = BASE.AMP.'C=content_files'.AMP.'M=edit_image'.AMP.'upload_dir='.$this->input->post('upload_dir').AMP.'file='.$this->input->post('file');
		$this->functions->redirect($url);
	}

	// ------------------------------------------------------------------------

	/**
	 * Do image rotation.
	 */
	private function _do_resize($file)
	{


		$config = array(
			'width'				=> $this->input->get_post('resize_width'),
			'maintain_ratio'	=> $this->input->get_post('constrain'),
			'library_path'		=> $this->config->item('image_library_path'),
			'image_library'		=> $this->config->item('image_resize_protocol'),
			'source_image'		=> $file,
			'new_image'			=> $file
		);

		if ($this->input->get_post('resize_height') != '')
		{
			$config['height'] = $this->input->get_post('resize_height');
		}
		else
		{
			$config['master_dim'] = 'width';
		}

		$this->load->library('image_lib', $config);

		if ( ! $this->image_lib->resize())
		{
	    	$errors = $this->image_lib->display_errors();
		}

		if (isset($errors))
		{
			if (AJAX_REQUEST)
			{
				$this->output->send_ajax_response($errors, TRUE);
			}

			show_error($errors);
		}

		$this->image_lib->clear();

		if (AJAX_REQUEST)
		{
			$dimensions = $this->image_lib->get_image_properties('', TRUE);
			$this->image_lib->clear();

			$this->output->send_ajax_response(array(
				'width'		=> $dimensions['width'],
				'height'	=> $dimensions['height']
			));
		}

		$this->session->set_flashdata('message_success', lang('file_saved'));
		$url = BASE.AMP.'C=content_files'.AMP.'M=edit_image'.AMP.'upload_dir='.$this->input->post('upload_dir').AMP.'file='.$this->input->post('file');
		$this->functions->redirect($url);
	}

	// ------------------------------------------------------------------------

	/**
	 * Checks for images with no record in the database and adds them
	 */
	public function sync_directory()
	{
		$file_dir  = $this->input->get('id');
		$cid = $file_dir;
		$var['sizes'] = array();
		$this->load->library('javascript');

		$resize_existing = FALSE;

		// No file directory- they want to sync them all
		if ($file_dir === FALSE)
		{
			// return false
		}
		else
		{
			if ( ! array_key_exists($file_dir, $this->_upload_dirs))
			{
				show_error(lang('unauthorized_access'));
			}

			$ids = array($file_dir);
		}

		// Get the resize info for the directory
		$this->db->select('*');
		$this->db->from('file_dimensions');
		$this->db->join('file_watermarks', 'wm_id = watermark_id', 'left');	
		$this->db->where_in('upload_location_id', $ids);
		$query = $this->db->get();		

		// Build skeleton of the size array with the upload directories loaded in
		$js_size = array();

		foreach ($ids as $upload_directory_id)
		{
			$js_size[$upload_directory_id] = '';
		}

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$js_size[$row->upload_location_id][$row->id] = array('short_name' => $row->short_name, 'resize_type' => $row->resize_type, 'width' => $row->width, 'height' => $row->height, 'watermark_id' => $row->watermark_id);
								
				$vars['sizes'][] = array('short_name' => $row->short_name, 'title' => $row->title, 'resize_type' => $row->resize_type, 'width' => $row->width, 'height' => $row->height, 'id' => $row->id);
				
				if ($row->watermark_id != 0)
				{
					$js_size[$row->upload_location_id][$row->id]['wm_type'] = $row->wm_type;
					$js_size[$row->upload_location_id][$row->id]['wm_image_path'] =	$row->wm_image_path;				
					$js_size[$row->upload_location_id][$row->id]['wm_use_font'] = $row->wm_use_font;	
					$js_size[$row->upload_location_id][$row->id]['wm_font'] = $row->wm_font;					
					$js_size[$row->upload_location_id][$row->id]['wm_font_size'] = $row->wm_font_size;	
					$js_size[$row->upload_location_id][$row->id]['wm_text'] = $row->wm_text;		
					$js_size[$row->upload_location_id][$row->id]['wm_vrt_alignment'] = $row->wm_vrt_alignment;
					$js_size[$row->upload_location_id][$row->id]['wm_hor_alignment'] = $row->wm_hor_alignment;	
					$js_size[$row->upload_location_id][$row->id]['wm_padding'] = $row->wm_padding;
					$js_size[$row->upload_location_id][$row->id]['wm_opacity'] = $row->wm_opacity;				
					$js_size[$row->upload_location_id][$row->id]['wm_x_offset'] = $row->wm_x_offset;
					$js_size[$row->upload_location_id][$row->id]['wm_y_offset'] = $row->wm_y_offset;
					$js_size[$row->upload_location_id][$row->id]['wm_x_transp'] = $row->wm_x_transp;	
					$js_size[$row->upload_location_id][$row->id]['wm_y_transp'] = $row->wm_y_transp;
					$js_size[$row->upload_location_id][$row->id]['wm_text_color'] =	$row->wm_text_color;		
					$js_size[$row->upload_location_id][$row->id]['wm_use_drop_shadow'] = $row->wm_use_drop_shadow;
					$js_size[$row->upload_location_id][$row->id]['wm_shadow_distance'] = $row->wm_shadow_distance;
					$js_size[$row->upload_location_id][$row->id]['wm_shadow_color'] = $row->wm_shadow_color;
				}
			}
		}

		// Let's do a quick check of db to see if ANY file records for this directory
		//$this->db->where('upload_location_id', $id);
		//$this->db->from('files');
		//$do_db_check = ($this->db->count_all_results() == 0) ? FALSE : TRUE;


		// If I move this will need to fetch upload dir data
		foreach ($ids as $id)
		{
			$dir_data[$id] = $this->_upload_dirs[$id];
			$vars['dirs'][$id] = $this->_upload_dirs[$id];

			$vars['dirs'][$id]['files'] = $this->filemanager->directory_files_map(
				$dir_data[$id]['server_path'],
				1,
				FALSE,
				$dir_data[$id]['allowed_types']
			);

			$vars['dirs'][$id]['count'] = count($vars['dirs'][$id]['files']);
		}

		$this->cp->add_js_script(array(
				'plugin' => array('tmpl'),
				'ui'     => array('progressbar'),
				'file'   => array('underscore', 'cp/files/synchronize')
			)
		);

		$this->javascript->set_global(array(
			'file_manager' => array(
				'sync_files'      => $vars['dirs'][$id]['files'],
				'sync_file_count' => $vars['dirs'][$id]['count'],
				'sync_sizes'      => $js_size
			)
		));

		// Sigh- this is stupid and will move to updater after initial testing
		// Testing the updater is just a pain

		if ( ! $this->db->table_exists('files'))
		{
			$Q[] = "CREATE TABLE exp_file_watermarks (
					wm_id int(4) unsigned NOT NULL auto_increment,
					wm_name varchar(80) NOT NULL,
					wm_type char(1) NOT NULL default 'n',
					wm_image_path varchar(100) NOT NULL,
					wm_test_image_path varchar(100) NOT NULL,
					wm_use_font char(1) NOT NULL default 'y',
					wm_font varchar(30) NOT NULL,
					wm_font_size int(3) unsigned NOT NULL,
					wm_text varchar(100) NOT NULL,
					wm_vrt_alignment char(1) NOT NULL default 'T',
					wm_hor_alignment char(1) NOT NULL default 'L',
					wm_padding int(3) unsigned NOT NULL,
					wm_opacity int(3) unsigned NOT NULL,
					wm_x_offset int(4) unsigned NOT NULL,
					wm_y_offset int(4) unsigned NOT NULL,
					wm_x_transp int(4) NOT NULL,
					wm_y_transp int(4) NOT NULL,
					wm_text_color varchar(7) NOT NULL,
					wm_use_drop_shadow char(1) NOT NULL default 'y',
					wm_shadow_distance int(3) unsigned NOT NULL,
					wm_shadow_color varchar(7) NOT NULL,
					PRIMARY KEY (wm_id)
				)";


			$Q[] = "CREATE TABLE exp_file_dimensions (
			 id int(6) unsigned NOT NULL auto_increment,
			 upload_location_id INT(4) UNSIGNED NOT NULL DEFAULT 0,
			 title varchar(255) NOT NULL DEFAULT '',
			 short_name varchar(255) NOT NULL DEFAULT '',
			 resize_type varchar(50) NOT NULL DEFAULT '',
			 width int(10) NOT NULL DEFAULT 0,
			 height int(10) NOT NULL DEFAULT 0,
			 PRIMARY KEY `id` (`id`),
			 KEY `upload_location_id` (`upload_location_id`)
			)";

			// Note- change to cat_id cause it's what we use in category_posts and I just like the consistency

			$Q[] = "CREATE TABLE exp_file_categories (
			 file_id int(10) unsigned NOT NULL,
			 cat_id int(10) unsigned NOT NULL,
			 sort int(10) unsigned NOT NULL DEFAULT 0,
			 is_cover char(1) NOT NULL default 'n',
			 KEY `file_id` (`file_id`),
			 KEY `cat_id` (`cat_id`)
			)";

			//field_2_fmt TINYTEXT NOT NULL DEFAULT 'xhtml',
			// errors BLOB/TEXT column 'field_1_fmt' can't have a default value


			$Q[] = "CREATE TABLE exp_files (
			 file_id int(6) unsigned NOT NULL auto_increment,
			 site_id INT(4) UNSIGNED NOT NULL DEFAULT 1,
			 title varchar(255) NOT NULL,
			 upload_location_id INT(4) UNSIGNED NOT NULL DEFAULT 0,
			 path varchar(255) NOT NULL,
		 	 status char(1) NOT NULL default 'o',
			 mime_type varchar(255) NOT NULL,
			 file_name varchar(255) NOT NULL,
			 file_size INT(4) NOT NULL DEFAULT 0,
			 field_1 text NULL,
			 field_1_fmt TINYTEXT NOT NULL,
			 field_2 text NULL,
			 field_2_fmt TINYTEXT NOT NULL,
			 field_3 text NULL,
			 field_3_fmt TINYTEXT NOT NULL,
			 field_4 text NULL,
			 field_4_fmt TINYTEXT NOT NULL,
			 field_5 text NULL,
			 field_5_fmt TINYTEXT NOT NULL,
			 field_6 text NULL,
			 field_6_fmt TINYTEXT NOT NULL,
			 metadata MEDIUMTEXT NULL,
			 uploaded_by_member_id int(10) unsigned NOT NULL default 0,
			 upload_date int(10) NOT NULL,
			 modified_by_member_id int(10) unsigned NOT NULL default 0,
			 modified_date int(10) NOT NULL,
			 PRIMARY KEY `file_id` (`file_id`),
			 KEY `upload_location_id` (`upload_location_id`),
			 KEY `site_id` (`site_id`)
			)";

			//KEY `file_dimension_id` (`file_dimension_id`),

			// doesn't look like anything changed in upload prefs

			// Pascal will have a cow- but TEST DATA!
			// Add a column to files to hold the size for the new name
			// Seperate cause my go poof and couldn't normally hard code it
			$Q[] = "ALTER TABLE `exp_files` ADD COLUMN `file_hw_original` VARCHAR(20) NOT NULL default ''";


			foreach ($Q as $sql)
			{
				$this->db->query($sql);
			}
		} // End stupid - I hope

		$this->cp->set_variable('cp_page_title', $this->_upload_dirs[$cid]['name']);
		$this->cp->set_breadcrumb(
			BASE.AMP.'C=content_files'.AMP.'M=file_upload_preferences',
			lang('file_upload_prefs')
		);

		$this->javascript->compile();
		$this->load->view('content/files/sync', $vars);


		// process file array - move to own method?
	}

	// ------------------------------------------------------------------------

	/**
	 * Do sync files
	 *
	 *
	 *
	 */
	public function do_sync_files()
	{
		$type = 'insert';
		$errors = array();
		$file_data = array();
		$replace_sizes = array();

		// If file exists- make sure it exists in db - otherwise add it to db and generate all child sizes
		// If db record exists- make sure file exists -  otherwise delete from db - ?? check for child sizes??

		if (($sizes = $this->input->post('sizes')) === FALSE OR
			($current_files = $this->input->post('files')) === FALSE)
		{
			return FALSE;
		}

		$id = key($sizes);

		$dir_data = $this->_upload_dirs[$id];

		
		if (isset($_POST['resize_ids']) && is_array($_POST['resize_ids']))
		{
			foreach ($_POST['resize_ids'] as $v)
			{
				$replace_sizes[$v] = $sizes[$id][$v];
			}
		}

		
		//$this->sync_database();


		// @todo, bail if there are no files in the directory!  :D

		$files = $this->filemanager->fetch_files($id, $current_files, TRUE);

		$this->load->library('localize');

		// Setup data for batch insert
		foreach ($files->files[$id] as $file)
		{
			if ( ! $file['mime'])
			{
				// set error
				$errors[$file['name']] = 'No mime type';
				continue;
			}

			// Does it exist in DB?
			$query = $this->db->get_where('files', array('file_name' => $file['name']));

			if ($query->num_rows() > 0)
			{
				// It exists, but do we need to change sizes?
				if ( ! empty($replace_sizes))
				{
					/*
					$this->filemanager->sync_resized(
						array('server_path' => $this->_upload_dirs[$id]['server_path']),
						array('name' => $file['name']),
						$replace_sizes
					);
					*/
					
					// Note- really no need to create system thumb in this case
				$this->filemanager->create_thumb(
					$this->_upload_dirs[$id]['server_path'].$file['name'],
					array('server_path' => $this->_upload_dirs[$id]['server_path'],
					'name' => $file['name'],
					'dimensions' => $sizes[$id])
				);
					
					
				}

				continue;
			}

			$file_location = $this->functions->remove_double_slashes(
					$dir_data['url'].'/'.$file['name']
				);

			$file_path = $this->functions->remove_double_slashes(
					$dir_data['server_path'].'/'.$file['name']
				);

			$file_dim = (isset($file['dimensions']) && $file['dimensions'] != '') ? str_replace(array('width="', 'height="', '"'), '', $file['dimensions']) : '';

			//$file_data[] 
			$file_data = array(
					'upload_location_id'	=> $id,
					'site_id'				=> $this->config->item('site_id'),
					'title'					=> $file['name'],
					'path'					=> $file_path,
					'status'				=> 'o',
					'mime_type'				=> $file['mime'],
					'file_name'				=> $file['name'],
					'file_size'				=> $file['size'],
					'metadata'				=> '',
					'uploaded_by_member_id'	=> $this->session->userdata('member_id'),
					'upload_date'			=> $this->localize->now,
					'modified_by_member_id' => 0,
					'modified_date' 		=> 0,
					'field_1_fmt'			=> 'xhtml',
					'field_2_fmt'			=> 'xhtml',
					'field_3_fmt'			=> 'xhtml',
					'field_4_fmt'			=> 'xhtml',
					'field_5_fmt'			=> 'xhtml',
					'field_6_fmt'			=> 'xhtml',
					'file_hw_original'		=> $file_dim
			);

			//print_r($file_data);
			
			$this->filemanager->insert_file($file_data);

			// Insert into categories???

			// Go ahead and create the thumb
			// For syncing- will need to tap into dir prefs and make all image variations- so batch needs to be small

			// Woot- Success!  Make a new thumb
			/*
			$thumb = $this->filemanager->create_thumb(
				array('server_path' => $this->_upload_dirs[$id]['server_path']),
				array('name' => $file['name'])
			);
			*/

			if (is_array($sizes[$id]))
			{
				$this->filemanager->create_thumb(
					$this->_upload_dirs[$id]['server_path'].$file['name'],
					array('server_path' => $this->_upload_dirs[$id]['server_path'],
					'name' => $file['name'],
					'dimensions' => $sizes[$id])
				);
			}
		}

		// var_dump($file_data);
		// exit($this->output->send_ajax_response('failure before batch'));

		// Alas my beloved batch
		//if ( ! empty($file_data))
		//{
		//	$this->db->insert_batch('files', $file_data);
		//}

		// exit($this->output->send_ajax_response('failure after batch'));


		if (AJAX_REQUEST)
		{
			if ( ! empty($errors))
			{
				$this->output->send_ajax_response($errors, TRUE);
			}

			$this->output->send_ajax_response('success');
		}
	}

	// ------------------------------------------------------------------------

	function sync_database()
	{
		$id = $this->input->post('dir_id');

		if ( ! array_key_exists($id, $this->_upload_dirs))
		{
			show_error(lang('unauthorized_access'));
		}


		$this->filemanager->sync_database($id);
	}

	// ------------------------------------------------------------------------

	/**
	 * Checks for images with no record in the database and adds them
	 */
	function watermark_preferences()
	{
		$this->load->library('table');
		$this->load->model('file_model');

		$this->cp->set_variable('cp_page_title', lang('watermark_prefs'));
		$this->cp->set_breadcrumb($this->_base_url, lang('file_manager'));		
		

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 2: {sorter: false}},
			widgets: ["zebra"]
		}');

		$vars['watermarks'] = $this->file_model->get_watermark_preferences();

		$this->javascript->compile();

		$this->cp->set_right_nav(array('create_new_wm_pref' => BASE.AMP.'C=content_files'.AMP.'M=edit_watermark_preferences'));

		$this->load->view('content/files/watermark_preferences', $vars);


	}

	// ------------------------------------------------------------------------

	/**
	 * Checks for images with no record in the database and adds them
	 */
	function edit_watermark_preferences()
	{
		$this->load->library(array('table', 'filemanager'));
		$this->load->model('file_model');
		
		$this->cp->add_js_script(array(
				'plugin' => array('colorpicker'),
				'file'   => array('cp/files/watermark_settings')
			)
		);
		
		// CSS link for colorpicker
		//$css_folder = $this->config->item('use_compressed_js') == 'n' ? 'src' : 'compressed';
		
		//$css_file = PATH_THEMES.'javascript/'.$css_folder.'/jquery/themes/default/colorpicker.css';

		//$this->cp->add_to_head('<link rel="stylesheet" href="'.BASE.AMP.'C=css'.AMP.'M=colorpicker'.'" type="text/css" media="screen" />');
		
		$style = $this->view->head_link('css/colorpicker.css');
		
		$this->cp->add_to_head($style);
		
	

		$id = $this->input->get_post('id');


		$type = ($id) ? 'edit' : 'new';	
		
		$this->cp->set_variable('cp_page_title', lang('wm_'.$type));
		$this->cp->set_breadcrumb($this->_base_url, lang('file_manager'));
		$this->cp->set_breadcrumb($this->_base_url.AMP.'M=watermark_preferences', lang('watermark_prefs'));		


		if (FALSE)
		{
			show_error(lang('unauthorized_access'));
		}

		$default_fields = array(
						'wm_name'						=> '',
						'wm_image_path'					=>	'',
						'wm_test_image_path'			=>	'',
						'wm_type'						=> 'text',
						'type_image'					=>  0,
						'type_text'						=>	1,
						'wm_use_font'					=> 'y',
						'font_yes'						=> 1,
						'font_no'						=> 0,
						'wm_font'						=> 'texb.ttf',
						'wm_font_size'					=> 16,
						'wm_text'						=> 'Copyright '.date('Y', $this->localize->now),
						'wm_alignment'					=> '',
						'wm_vrt_alignment'				=> 'T',
						'wm_hor_alignment'				=> 'L',
						'wm_padding'					=> 10,
						'wm_x_offset'					=> 0,
						'wm_y_offset'					=> 0,
						'wm_x_transp'					=> 2,
						'wm_y_transp'					=> 2,
						'wm_text_color'					=> '#ffff00',
						'wm_use_drop_shadow'			=> 'y',
						'use_drop_shadow_yes'			=> 1,
						'use_drop_shadow_no'			=> 0,
						'wm_shadow_color'				=> '#999999',
						'wm_shadow_distance'			=> 1,
						'wm_opacity'					=> 50,
						'wm_apply_to_thumb'				=> 'n',
						'wm_apply_to_medium'			=> 'n'
				);

		if ($type == 'new')
		{
			$vars = $default_fields;

			$vars['hidden'] = array('id' => $id);

		}
		else
		{
			$wm_query = $this->file_model->get_watermark_preferences(array($id));

   			$settings = $wm_query->row_array();

 			foreach ($settings as $k => $v)
			{
				$vars[$k] = ($this->input->post($k)) ? $this->input->post($k) : $settings[$k];
			}

			// Set our true/false radios
			$vars['type_text'] = ($vars['wm_type'] == 't' OR $vars['wm_type'] == 'text') ? TRUE : FALSE;
			$vars['type_image'] = ($vars['wm_type'] == 't' OR $vars['wm_type'] == 'text') ? FALSE : TRUE;
			$vars['font_yes'] = ($vars['wm_use_font'] == 'y') ? TRUE : FALSE;
			$vars['font_no'] = ($vars['wm_use_font'] == 'y') ? FALSE : TRUE;
			$vars['use_drop_shadow_yes'] = ($vars['wm_use_drop_shadow'] == 'y') ? TRUE : FALSE;
			$vars['use_drop_shadow_no'] = ($vars['wm_use_drop_shadow'] == 'y') ? FALSE : TRUE;
			$vars['hidden'] = array('id' => $id);
		}


		$i = 1;

		while ($i < 101)
		{
			$vars['opacity_options'][$i] = $i;
			$i++;
		}

		
		$vars['font_options'] = $this->filemanager->fetch_fontlist();


		$this->load->library('form_validation');

		$title = ($type == 'edit') ? 'wm_edit' : 'wm_create';

		$vars['lang_line'] = ($type == 'edit') ? 'update' : 'submit';



		$config = array(
					   array(
							 'field'   => 'name',
							 'label'   => 'lang:wm_name',
							 'rules'   => 'trim|required|callback__name_check'
						  ),
					   //array(
					//		 'field'   => 'wm_type',
					//		 'label'   => 'lang:wm_type',
					//		 'rules'   => 'required'
					//	  ),
					   array(
							 'field'   => 'wm_image_path',
							 'label'   => 'lang:wm_image_path',
							 'rules'   => ''
						  ),
					   array(
							 'field'   => 'wm_test_image_path',
							 'label'   => 'lang:wm_test_image_path',
							 'rules'   => ''
						  ),
					   array(
							 'field'   => 'wm_font',
							 'label'   => 'lang:wm_font',
							 'rules'   => ''
						  ),
					   array(
							 'field'   => 'wm_font_size',
							 'label'   => 'lang:wm_font_size',
							 'rules'   => 'integer'
						  ),						
					   array(
							 'field'   => 'wm_x_offset',
							 'label'   => 'lang:wm_x_offset',
							 'rules'   => 'integer'
						  ),
					   array(
							 'field'   => 'wm_y_offset',
							 'label'   => 'lang:wm_y_offset',
							 'rules'   => 'integer'
						  ),
					   array(
							 'field'   => 'wm_vrt_alignment',
							 'label'   => 'lang:wm_vrt_alignment',
							 'rules'   => ''
						  ),
					   array(
							 'field'   => 'wm_hor_alignment',
							 'label'   => 'lang:wm_hor_alignment',
							 'rules'   => ''
						  ),
					   array(
							 'field'   => 'wm_x_transp',
							 'label'   => 'lang:wm_x_transp',
							 'rules'   => 'integer'
						  ),
					   array(
							 'field'   => 'wm_y_transp',
							 'label'   => 'lang:wm_y_transp',
							 'rules'   => 'integer'
						  ),

					   array(
							 'field'   => 'wm_text_color',
							 'label'   => 'lang:wm_text_color',
							 'rules'   => ''
						  ),
					   array(
							 'field'   => 'wm_shadow_color',
							 'label'   => 'lang:wm_shadow_color',
							 'rules'   => ''
						  )	
					);

		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<span class="notice">', '</span>')
							  ->set_rules($config);
		$this->form_validation->set_old_value('wm_id', $id);

		$this->javascript->compile();

		if ( ! $this->form_validation->run())
		{
			$this->javascript->compile();
			$this->load->view('content/files/watermark_settings', $vars);
		}
		else
		{
			$this->_update_watermark_preferences();
		}		
	}
	
	function _name_check($str)
	{
		// Check for duplicates
		//$this->db->where('site_id', $this->config->item('site_id'));
		$this->db->where('wm_name', $str);
		
		if ($this->form_validation->old_value('wm_id'))
		{
			$this->db->where('wm_id != ', $this->form_validation->old_value('wm_id'));
		}

		if ($this->db->count_all_results('file_watermarks') > 0)
		{
			$this->form_validation->set_message('_name_check', $this->lang->line('wm_name_taken'));
			return FALSE;
		}
		
		return TRUE;
	}



	// ------------------------------------------------------------------------	

	function _update_watermark_preferences()
	{
		$id = $this->input->post('id');

		$type = ($id) ? 'edit' : 'new';
		$data['wm_name'] = $this->input->post('name');

		$defaults = array(
						'wm_image_path'					=>	'',
						'wm_test_image_path'			=>	'',
						'wm_type'						=> 'n',
						'wm_use_font'					=> 'y',
						'wm_font'						=> 'texb.ttf',
						'wm_font_size'					=> 16,
						'wm_text'						=> 'Copyright '.date('Y', $this->localize->now),
						'wm_vrt_alignment'				=> 'T',
						'wm_hor_alignment'				=> 'L',
						'wm_padding'					=> 10,
						'wm_x_offset'					=> 0,
						'wm_y_offset'					=> 0,
						'wm_x_transp'					=> 2,
						'wm_y_transp'					=> 2,
						'wm_text_color'					=> '#ffff00',
						'wm_use_drop_shadow'			=> 'y',
						'wm_shadow_color'				=> '#999999',
						'wm_shadow_distance'			=> 1,
						'wm_opacity'					=> 50,
				);

		foreach ($defaults as $k => $v)
		{
			if ($this->input->post($k) == '')
			{
				$data[$k] = $defaults[$k];
			}
			else
			{
				$data[$k] = $this->input->post($k);
			}
		}


		// Construct the query based on whether we are updating or inserting
		if ($type === 'edit')
		{
			$this->db->update('file_watermarks', $data, array('wm_id' => $id));
			$cp_message = lang('preferences_updated');
		}
		else
		{
			//$data['site_id'] = $this->config->item('site_id');

			$this->db->insert('file_watermarks', $data);
			$id = $this->db->insert_id();
			$cp_message = lang('new_watermark_created');
		}

		$this->session->set_flashdata('message_success', $cp_message);
		$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'M=watermark_preferences');

	}


	// ------------------------------------------------------------------------

	/**
	 * Checks for images with no record in the database and adds them
	 */
	function delete_watermark_preferences_conf()
	{
		$id = $this->input->get_post('id');

		if ( ! $id)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->helper('form');

		$this->cp->set_variable('cp_page_title', lang('delete_wm_preference'));


		$this->cp->set_breadcrumb($this->_base_url, lang('file_manager'));
		$this->cp->set_breadcrumb($this->_base_url.AMP.'M=watermark_preferences', 
								lang('watermark_prefs'));

		$data = array(
			'form_action'	=> 'C=content_files'.AMP.'M=delete_watermark_preferences'.AMP.'id='.$id,
			'form_extra'	=> '',
			'form_hidden'	=> array(
				'id'			=> $id
			),
			'message'		=> lang('delete_watermark_pref_confirmation')
		);

		// Grab all wm prefs with this id
		$this->db->where('wm_id', $id);
		$items = $this->db->get('file_watermarks');
		$data['items'] = array();

		foreach($items->result() as $item)
		{
			$data['items'][] = $item->wm_name;
		}

		$this->javascript->compile();
		$this->load->view('content/files/pref_delete_confirm', $data);

	}

	// --------------------------------------------------------------------

	/**
	 *  Delete Upload Preferences
	 *
	 * @access	public
	 * @return	null
	 */
	function delete_watermark_preferences()
	{
		$id = $this->input->get_post('id');

		if ( ! $id)
		{
			show_error($this->lang->line('unauthorized_access'));
		}


		$name = $this->filemanager->delete_watermark_prefs($id);

		$this->logger->log_action(lang('watermark_pref_deleted').NBS.NBS.$name);

		// Clear database cache
		$this->functions->clear_caching('db');

		$this->session->set_flashdata('message_success', lang('watermark_pref_deleted').NBS.NBS.$name);
		$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'M=watermark_preferences');
	}


	// --------------------------------------------------------------------

	/**
	 * File Upload Preferences
	 *
	 * Creates the File Upload Preferences main page
	 *
	 * @return	void
	 */
	function file_upload_preferences($message = '')
	{
		$this->load->library('table');

		$this->cp->set_variable('cp_page_title', lang('file_upload_prefs'));

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 2: {sorter: false}},
			widgets: ["zebra"]
		}');

		$vars['message'] = $message;
		$vars['upload_locations'] = $this->file_model->get_upload_preferences($this->session->userdata('member_group'));

		$this->javascript->compile();

		$this->cp->set_right_nav(array('create_new_upload_pref' => BASE.AMP.'C=content_files'.AMP.'M=edit_upload_preferences'));

		$this->load->view('content/files/file_upload_preferences', $vars);
	}


	function delete_dimension()
	{

	}


	// --------------------------------------------------------------------

	/**
	 * Creates or edits a file upload location
	 *
	 * @todo -- more refactoring of this mess
	 * @return void
	 */
	public function edit_upload_preferences()
	{
		$this->load->library('table');
		$id = $this->input->get_post('id');

		$this->cp->add_js_script(array('file' => 'cp/files/upload_pref_settings'));


		$this->javascript->set_global(array('lang' => array(
											'size_deleted'	=> $this->lang->line('size_deleted'),
											'size_not_deleted' => $this->lang->line('size_not_deleted')
										)
									));


		$type = ($id) ? 'edit' : 'new';

		$fields = array(
			'id', 'site_id', 'name', 'server_path',
			'url', 'allowed_types', 'max_size',
			'max_height', 'max_width', 'max_image_action', 'properties',
			'pre_format', 'post_format', 'file_properties',
			'file_pre_format', 'file_post_format', 'batch_location',
			'cat_group'
		);

		$data['image_sizes'] = array();

		if ($type == 'new')
		{
			$data['form_hidden'] = NULL;
			$data['allowed_types'] = NULL;
			$data['allowed_types'] = 'disallow';

			foreach ($fields as $field)
			{
				$data['field_'.$field] = $this->input->post($field);
			}
		}
		else
		{
			if (count($this->_upload_dirs) !== 0)
			{
				if ( ! isset($this->_upload_dirs[$id]))
				{
					show_error(lang('unauthorized_access'));
				}

				foreach ($this->_upload_dirs[$id] as $k => $v)
				{
					if ($k == 'allowed_types')
					{
						$data['allowed_types'] = $v;
					}
					elseif ($k == 'max_image_action')
					{
						$data['max_image_action'] = $v;
					}
					else
					{
						$data['field_'.$k] = $v;
					}

					if ($k == 'cat_group')
					{
						$data['selected_cat_groups'] = explode('|', $v);
					}
				}
			}
			else
			{
				if ($id)
				{
					show_error(lang('unauthorized_access'));
				}

				foreach ($fields as $f)
				{
					$data['field_'.$f] = '';
				}

				$data['field_url'] = base_url();
				$data['field_server_path'] = str_replace(SYSDIR.'/', '', FCPATH);
			}

			// Get Image Versions
			$sizes_query = $this->db->get_where('file_dimensions',
														array('upload_location_id' => $id));
			if ($sizes_query->num_rows() != 0)
			{
				foreach($sizes_query->result_array() as $row)
				{
					$data['image_sizes'][$row['id']] = $row;
				}
			}


			$data['form_hidden'] = array(
				'id'		=> $data['field_id'],
				'cur_name'	=> $data['field_name']
			);
		}

		$this->load->library('form_validation');

		$data['upload_groups'] = $this->member_model->get_upload_groups();
		$data['banned_groups'] = array();

		if ($data['upload_groups']->num_rows() > 0)
		{
			$this->db->select('member_group');

			if ($id)
			{
				$this->db->where('upload_id', $id);
			}

			$result = $this->db->get('upload_no_access');

			if ($result->num_rows() != 0)
			{
				foreach($result->result_array() as $row)
				{
					$data['banned_groups'][] = $row['member_group'];
				}
			}
		}

		$title = ($type == 'edit') ? 'edit_file_upload_preferences' : 'new_file_upload_preferences';

		$this->cp->set_variable('cp_page_title', lang($title));
		$data['lang_line'] = ($type == 'edit') ? 'update' : 'submit';

		$this->cp->set_breadcrumb($this->_base_url.AMP.'M=file_upload_preferences',
								  lang('file_upload_preferences'));

		$data['upload_pref_fields1'] = array(
							'max_size', 'max_height', 'max_width');

		$data['upload_pref_fields2'] = array(
							'properties', 'pre_format', 'post_format', 'file_properties',
							'file_pre_format', 'file_post_format', 'batch_location');

		// Category Select List
		$this->load->model('category_model');
		$query = $this->category_model->get_category_groups('', FALSE, 1);

		$data['cat_group_options'][] = lang('none');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$data['cat_group_options'][$row->group_id] = $row->group_name;
			}
		}

		$data['selected_cat_groups'] = (isset($data['selected_cat_groups'])) ? $data['selected_cat_groups'] : NULL;

		$config = array(
					   array(
							 'field'   => 'name',
							 'label'   => 'lang:upload_pref_name',
							 'rules'   => 'required'
						  ),
					   array(
							 'field'   => 'server_path',
							 'label'   => 'lang:server_path',
							 'rules'   => 'required'
						  ),
					   array(
							 'field'   => 'url',
							 'label'   => 'lang:url_to_upload_dir',
							 'rules'   => 'callback_not_http'
						  ),
					   array(
							 'field'   => 'allowed_types',
							 'label'   => 'lang:allowed_types',
							 'rules'   => 'required'
						  ),
					   array(
							 'field'   => 'max_size',
							 'label'   => 'lang:max_size',
							 'rules'   => 'numeric'
						  ),
					   array(
							 'field'   => 'max_height',
							 'label'   => 'lang:max_height',
							 'rules'   => 'numeric'
						  ),
					   array(
							 'field'   => 'max_width',
							 'label'   => 'lang:max_width',
							 'rules'   => 'numeric'
						  ),
					   array(
							 'field'   => 'max_image_action',
							 'label'   => 'lang:max_image_handling',
							 'rules'   => ''
						  ),

					);

		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<span class="notice">', '</span>')
							  ->set_rules($config);

		// Find next file size id
		$size_query = $this->file_model->select_max('id', '', 'file_dimensions');

		$data['next_size_id'] = ($size_query->row('id') >= 1) ? $size_query->row('id') + 1 : 1;

		// Get watermark options
		$wm_query = $this->file_model->get_watermark_preferences();
		$data['watermark_options']['0'] = lang('add_watermark');

		foreach ($wm_query->result() as $wm)
		{
			$data['watermark_options'][$wm->wm_id] = $wm->wm_name;
		}

		if ( ! $this->form_validation->run())
		{
			$this->javascript->compile();
			$this->load->view('content/files/file_upload_create', $data);
		}
		else
		{
			$this->_update_upload_preferences();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Update upload pref
	 *
	 * @todo -- more refactoring of this mess
	 * @return void
	 */
	private function _update_upload_preferences()
	{
		$this->load->model('admin_model');

		// If the $id variable is present we are editing an
		// existing field, otherwise we are creating a new one

		$edit = (isset($_POST['id']) AND $_POST['id'] != '' && is_numeric($_POST['id'])) ? TRUE : FALSE;

		$server_path = $this->input->post('server_path');
		$url = $this->input->post('url');

		if (substr($server_path, -1) != '/' AND substr($server_path, -1) != '\\')
		{
			$_POST['server_path'] .= '/';
		}

		if (substr($url, -1) != '/')
		{
			$_POST['url'] .= '/';
		}

		$error = array();

		// Is the name taken?
		if ($this->admin_model->unique_upload_name(
								strtolower($this->input->post('name')),
								strtolower($this->input->post('cur_name')), $edit))
		{
			show_error(lang('duplicate_dir_name'));
		}

		$id = $this->input->get_post('id');

		unset($_POST['id']);
		unset($_POST['cur_name']);
		unset($_POST['submit']); // submit button
		unset($_POST['add_image_size']);

		$data = array();
		$no_access = array();

		$this->db->delete('upload_no_access', array('upload_id' => $id));

		// Check for changes in image sizes
		$query = $this->db->get_where('file_dimensions', array('upload_location_id' => $id));

		$names  = array();

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				if (isset($_POST['short_name_'.$row['id']]))
				{
					if ((trim($_POST['short_name_'.$row['id']]) == '' OR
						in_array($_POST['short_name_'.$row['id']], $names)) && ! isset($_POST['remove_size_'.$row['id']]))
					{
						return $this->output->show_user_error('submission', array($this->lang->line('invalid_shortname')));
					}

					$updatedata = array(
						'short_name' => $_POST['size_short_name_'.$row['id']],
						'title'	=> $_POST['size_short_name_'.$row['id']],
						'resize_type' => $_POST['size_resize_type_'.$row['id']],
						'height' => $_POST['size_height_'.$row['id']],
						'width' => $_POST['size_width_'.$row['id']],
						'watermark_id' => $_POST['size_watermark_id_'.$row['id']]
						);

					$this->db->where('id', $row['id']);
					$this->db->update('file_dimensions', $updatedata);

					$names[]  = $_POST['short_name_'.$row['id']];

				}
				else
				{
					if (isset($_POST['remove_size_'.$row['id']]))
					{
						unset($_POST['remove_size_'.$row['id']]);
						unset($_POST['size_short_name_'.$row['id']]);
					}

					$this->db->where('id', $row['id']);
					$this->db->delete('file_dimensions');
				}
			}
		}

		if ( ! isset($_POST['cat_group']))
		{
			$_POST['cat_group'] = '';
		}

		foreach ($_POST as $key => $val)
		{
			if (substr($key, 0, 7) == 'access_')
			{
				if ($val == 'n')
				{
					$no_access[] = substr($key, 7);
				}
			}
			elseif ($key == 'cat_group')
			{
				if ((count($this->input->post('cat_group')) > 0) && $this->input->post('cat_group'))
				{
					if ($_POST['cat_group'][0] == 0)
					{
						unset($_POST['cat_group'][0]);
					}

					$data['cat_group'] = implode('|', $this->input->post('cat_group'));
				}
				else
				{
					$data['cat_group'] = '';
				}
			}
			elseif(substr($key, 0, strlen('size_')) == 'size_')
			{
				if (substr($key, 0, strlen('size_short_name_')) == 'size_short_name_')
				{
					$number = substr($key, strlen('size_short_name_'));
					$name = 'size_short_name_'.$number;

					if (trim($val) == '') continue;

					if ( ! isset($_POST[$name]) OR ! preg_match("/^\w+$/", $_POST[$name]) OR
						in_array($_POST[$name], $names))
					{
						return $this->output->show_user_error('submission', array($this->lang->line('invalid_short_name')));
					}

					$size_data = array(
						'upload_location_id'		=> $id,
						'short_name'		=> $_POST[$name],
						'title'	=> $_POST['size_short_name_'.$number],
						'resize_type' => $_POST['size_resize_type_'.$number],
						'height' => $_POST['size_height_'.$number],
						'width' => $_POST['size_width_'.$number],
						'watermark_id' => $_POST['size_watermark_id_'.$number]
						);

					$this->db->insert('file_dimensions', $size_data);

					$names[]  = $_POST[$name];
				}
			}
			else
			{
				$data[$key] = $val;
			}
		}


		// Construct the query based on whether we are updating or inserting
		if ($edit === TRUE)
		{
			$this->db->update('upload_prefs', $data, array('id' => $id));
			$cp_message = lang('preferences_updated');
		}
		else
		{
			$data['site_id'] = $this->config->item('site_id');

			$this->db->insert('upload_prefs', $data);
			$id = $this->db->insert_id();
			$cp_message = lang('new_file_upload_created');
		}

		if (count($no_access) > 0)
		{
			foreach($no_access as $member_group)
			{
				$this->db->insert('upload_no_access',
									array(
										'upload_id'		=> $id,
										'upload_loc'	=> 'cp',
										'member_group'	=> $member_group)
								);
			}
		}

		$this->functions->clear_caching('db'); // Clear database cache

		$this->session->set_flashdata('message_success', $cp_message);
		$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'M=edit_upload_preferences'.AMP.'id='.$id);
	}

	// --------------------------------------------------------------------

	/**
	 *  Delete Upload Preferences Confirm
	 *
	 * @access	public
	 * @return	mixed
	 */
	function delete_upload_preferences_conf()
	{
		$id = $this->input->get_post('id');

		if ( ! $id)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->helper('form');

		$this->cp->set_variable('cp_page_title', lang('delete_upload_preference'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=file_upload_preferences',
								lang('file_upload_preferences'));

		$data = array(
			'form_action'	=> 'C=content_files'.AMP.'M=delete_upload_preferences'.AMP.'id='.$id,
			'form_extra'	=> '',
			'form_hidden'	=> array(
				'id'			=> $id
			),
			'message'		=> lang('delete_upload_pref_confirmation')
		);

		// Grab all upload locations with this id
		$this->db->where('id', $id);
		$items = $this->db->get('upload_prefs');
		$data['items'] = array();

		foreach($items->result() as $item)
		{
			$data['items'][] = $item->name;
		}

		$this->javascript->compile();
		$this->load->view('content/files/pref_delete_confirm', $data);
	}

	// --------------------------------------------------------------------

	/**
	 *  Delete Upload Preferences
	 *
	 * @access	public
	 * @return	null
	 */
	function delete_upload_preferences()
	{
		$id = $this->input->get_post('id');

		if ( ! $id)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('admin_content');

		$name = $this->file_model->delete_upload_preferences($id);

		$this->logger->log_action(lang('upload_pref_deleted').NBS.NBS.$name);

		// Clear database cache
		$this->functions->clear_caching('db');

		$this->session->set_flashdata('message_success', lang('upload_pref_deleted').NBS.NBS.$name);
		$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'M=file_upload_preferences');
	}

	// --------------------------------------------------------------------

	/**
	 *
	 *
	 *
	 *
	 *
	 */
	public function batch_upload()
	{
		if ( ! empty($_POST))
		{
			$this->_process_batch_upload();
		}

		$this->cp->set_variable('cp_page_title', lang('batch_upload'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=admin_content'.AMP.'M=file_upload_preferences',
								lang('file_upload_preferences'));

		$this->cp->add_js_script(array('file' => 'cp/files/batch_upload'));

		// Get Upload dirs, cycle through and figure out how many files
		// are in each batch upload location.
		$upload_dirs = array(lang('please_select'));

		foreach ($this->_upload_dirs as $dir)
		{
			$files = ($dir['batch_location'] === NULL) ? array() : get_dir_file_info($dir['batch_location'], TRUE);

			$file_count = count($files);

			if ($file_count > 0)
			{
				$upload_dirs[$dir['id']] = sprintf(lang('upload_dir_dropdown'),
													$dir['name'], count($files));
			}
		}

		$data = array(
			'no_sync_needed'=> (count($upload_dirs) === 1) ? TRUE : FALSE,
			'upload_dirs'	=> $upload_dirs,
			'stati'			=> array(
								''		=> lang('please_select'),
								'o'		=> lang('open'),
								'c'		=> lang('closed')
			)
		);

		$this->load->view('content/files/batch_upload_index', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Get category tree for a specific upload directory
	 *
	 *
	 */
	public function get_dir_cats()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->output->enable_profiler(FALSE);

		$id = $this->input->post('upload_directory_id');

		if (($id === 0) OR ! isset($this->_upload_dirs[$id]))
		{
			$this->output->send_ajax_response(array('error' => TRUE));
		}

		$group_id = $this->_upload_dirs[$id]['cat_group'];

		// Load the channel API
		$this->load->library('api');
		$this->api->instantiate('channel_categories');

		$this->load->model('category_model');
		$this->load->helper('form');

		$qry = $this->db->select('group_id, group_name, sort_order')
						->where('group_id', $group_id)
						->get('category_groups');

		if ($qry->num_rows() === 0)
		{
			$this->output->send_ajax_response(array('error' => TRUE));
		}

		$this->api_channel_categories->category_tree($group_id, '', $qry->row('sort_order'));

		$data = array(
			'edit_links' => TRUE,
			'categories' => array('' => $this->api_channel_categories->categories)
		);

		$ret = $this->load->view('content/_assets/categories', $data, TRUE);

		echo trim($ret); exit();
	}

	// --------------------------------------------------------------------

	/**
	 * Process Batch Upload
	 *
	 *
	 *
	 */
	private function _process_batch_upload()
	{
		if ( ! ($upload_dir_id = $this->input->post('upload_dirs')))
		{
			show_error(lang('unauthorized_access'));
		}

		$batch_location = (isset($this->_upload_dirs[$upload_dir_id]['batch_location'])) ?
							$this->_upload_dirs[$upload_dir_id]['batch_location'] : FALSE;

		if ( ! $batch_location)
		{
			// Batch Location isn't set, in the upload_dirs prop.  oops.
			show_error(lang('unauthorized_access'));
		}

		$allow_comments = ($this->input->post('allow_comments')) ? TRUE : FALSE;
		$status = ( ! $this->input->post('status')) ? 'c' : $this->input->post('status');
		$categories = implode(',', $this->input->post('category'));


		if ($this->input->post('manual_batch'))
		{
			$batch_type = 'manual';
		}
		elseif ($this->input->post('auto_batch'))
		{
			$batch_type = 'auto';
		}

		$url = BASE.AMP.'C=content_files'.AMP.'M=do_batch'.AMP."allow_comments={$allow_comments}".AMP."categories={$categories}".AMP."status={$status}".AMP.'loc='.base64_encode($batch_location).AMP."type={$batch_type}".AMP."upload_dir={$upload_dir_id}";
		$this->functions->redirect($url);

		show_error(lang('unauthorized_access'));
	}

	// --------------------------------------------------------------------

	/**
	 * Do manual batch processing
	 *
	 */
	public function do_batch()
	{
		$allow_comments = $this->input->get('allow_comments');
		$batch_dir_loc = base64_decode($this->input->get('loc'));
		$batch_type = $this->input->get('type');
		$categories = str_replace(',', '|', $this->input->get('categories'));
		$status = $this->input->get('status');
		$upload_dir = $this->input->get('upload_dir');

		// Sanitize the upload dir location since it's coming from _GET
		$batch_dir_loc = $this->security->sanitize_filename($batch_dir_loc, TRUE);

		// If anyone is offended by this, feel free to make it one line
		// My OCD with long lines of code got the best of me.
		// Before you move it, ask yourself if this is more readable than this:
		// 'C=content_files'.AMP.'M=do_batch'.AMP."allow_comments={$allow_comments}".AMP."categories={$categories}".AMP."status={$status}".AMP."status={$status}".AMP.'loc='.base64_encode($batch_location).AMP."type={$batch_type}".AMP."upload_dir={$upload_dir_id}";
		// I'll make you cookies if you disagree with me  :) -ga
		$form_action = 'C=content_files'.AMP.'M=do_batch'.AMP;
		$form_action .= "allow_comments={$allow_comments}".AMP;
		$form_action .= "categories=".$this->input->get('categories').AMP;
		$form_action .= "status={$status}".AMP;
		$form_action .= 'loc='.base64_encode($batch_dir_loc).AMP;
		$form_action .= "type={$batch_type}".AMP."upload_dir={$upload_dir}";


		if ( ! is_dir($batch_dir_loc))
		{
			show_error(lang('unauthorized_access'));
		}

		if ($batch_type == 'auto')
		{
			$this->_do_auto_batch($batch_dir_loc, $categories, $status,
								$allow_comments, $upload_dir, $form_action);
		}

		$this->_do_manual_batch($batch_dir_loc, $categories, $status,
								$allow_comments, $upload_dir, $form_action);
	}

	// --------------------------------------------------------------------

	/**
	 * Do manual batch upload
	 *
	 * @param 	string		base64_encoded string of the batch location
	 * @param 	string		categories in format of 1,3,5,78
	 * @param 	string		status 'o' or 'c'
	 * @param 	int 		allow comments -- 1 / 0
	 * @param 	int			Upload directory id
	 */
	private function _do_manual_batch($batch_dir_loc, $categories, $status,
									$allow_comments, $upload_dir, $form_action)
	{
		$files = get_dir_file_info($batch_dir_loc, TRUE);

		if (empty($files))
		{
			// Show the success page
			return;
		}

		$total_files_count = count($files);
		$files = array_slice($files, 0, 5);
		$current_processing_count = count($files);

		foreach ($files as $k => $file)
		{
			$mime = get_mime_by_extension($file['name']);

			if ($this->filemanager->is_image($mime))
			{
				$files[$k]['image'] = BASE.AMP.'C=content_files'.AMP.'M=batch_thumbs'.AMP."file=".base64_encode($file['server_path']);
			}
			else
			{
				$files[$k]['image'] = $this->config->item('theme_folder_url').'/cp_global_images/default.png"';
			}
		}

		$this->cp->set_variable('cp_page_title', lang('batch_upload'));

		$data = array(
			'count_lang'			=> sprintf(lang('files_count_lang'),
											   $current_processing_count,
											   $total_files_count),
			'current_num_files'		=> $current_processing_count,
			'files'					=> $files,
			'files_count' 			=> $total_files_count,
			'form_action'			=> $form_action,
			'form_hidden'			=> array()

		);

		$this->load->view('content/files/manual_batch', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Automatic batch upload.
	 *
	 * @param 	string		base64_encoded string of the batch location
	 * @param 	string		categories in format of 1,3,5,78
	 * @param 	string		status 'o' or 'c'
	 * @param 	int 		allow comments -- 1 / 0
	 * @param 	int			Upload directory id
	 */
	private function _do_auto_batch($batch_dir_loc, $categories, $status,
									$allow_comments, $upload_dir)
	{

	}

	// --------------------------------------------------------------------

	/**
	 * This function provides for dynamic thumbnail creation for display
	 * in the file manager
	 */
	public function batch_thumbs()
	{
		$this->output->enable_profiler(FALSE);

		$width = ($this->input->get('width')) ? $this->input->get('width') : 64;
		$height = ($this->input->get('height')) ? $this->input->get('height') : 64;

		$file_path = base64_decode($this->input->get('file'));
		$file_path = $this->security->sanitize_filename($file_path, TRUE);

		$config = array(
			'master_dim'		=> 'width',
			'library_path'		=> $this->config->item('image_library_path'),
			'image_library'		=> $this->config->item('image_resize_protocol'),
			'source_image'		=> $file_path,
			'dynamic_output'	=> TRUE,
			'height'			=> $height,
			'width'				=> $width,
			'create_thumb'		=> TRUE,
			'maintain_ratio'	=> TRUE
		);

		$this->load->library('image_lib', $config);
		$this->image_lib->resize();
	}
}
/* End File: content_files.php */
/* File Location: system/expressionengine/controllers/cp/content_files.php */

