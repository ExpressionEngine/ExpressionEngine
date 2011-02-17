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
	
	private $_upload_dirs = array();
	private $_base_url = '';
	public $remove_spaces = TRUE;
	public $temp_prefix = "temp_file_";
	
	private $nest_categories	= 'y';
	private $pipe_length		= 3;
	
	
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
			'directory_manager' => BASE.AMP.'C=content_files'.AMP.'M=file_upload_preferences'
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
		$this->load->helper('string');
		$this->api->instantiate('channel_categories');
		
		// Page Title
		$this->cp->set_variable('cp_page_title', lang('content_files'));
		
		$this->cp->add_js_script(array(
			'plugin'	=> array('overlay', 'overlay.apple', 'ee_upload', 'dataTables'),
			'file'		=> 'cp/file_manager_home',
			'ui' 		=> 'datepicker'
			)
		);

		$per_page = ($per_page = $this->input->get('per_page')) ? $per_page : 40;
		$offset = ($offset = $this->input->get('offset')) ? $offset : 0;
		$upload_dirs_options = array();
		$allowed_dirs = array();
		$comments_enabled = FALSE;
		
		$table_columns = ($comments_enabled) ? 9: 8;
		


		// We want the filter to work based on both get and post

		$dir_id = $this->input->get_post('dir_id');
		$cat_id = $this->input->get_post('cat_id');

		$status = $this->input->get_post('status');
		$order	= $this->input->get_post('order');
		$date_range = $this->input->get_post('date_range');
		$file_type = $this->input->get_post('file_type');
		$search_type = $this->input->get_post('search_type');
		$total_dirs = count($allowed_dirs);
		
		
		$this->javascript->set_global(array(
						'file.pipe' 		=> $this->pipe_length,
						'file.perPage'		=> $per_page,
						'file.themeUrl'		=> $this->cp->cp_theme_url,
						'file.tableColumns'	=> $table_columns,
						'lang.noEntries'	=> $this->lang->line('no_entries_matching_that_criteria')
					)
		);		

		
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
		


		// Create our various filter data
		
		foreach ($this->_upload_dirs as $k => $dir)
		{
			$upload_dirs_options[$dir['id']] = $dir['name'];
			$allowed_dirs[] = $k;
		}
		
		$upload_dirs_options['null'] = $this->lang->line('filter_by_directory');

		if (count($upload_dirs_options) > 2)
		{
			$upload_dirs_options['all'] = $this->lang->line('all');
		}

		ksort($upload_dirs_options);

		$selected_dir = ($selected_dir = $this->input->get_post('directory')) ? $selected_dir : 'null';
		
		// We need this for the filter, so grab it now
		$cat_form_array = $this->api_channel_categories->category_form_tree($this->nest_categories);
		
		// If we have channels we'll write the JavaScript menu switching code
		if (count($allowed_dirs) > 0)
		{
			$this->filtering_menus($cat_form_array);
		}

		
		// Cat filter
		$cat_group = ($selected_dir != 'null') ? $this->_upload_dirs[$selected_dir]['cat_group']: '';
		$category_options = $this->category_filter_options($cat_group, $cat_form_array, count($allowed_dirs));

		// Date range pull-down menu
		$date_selected = $date_range;

		$date_select_options[''] = $this->lang->line('date_range');
		$date_select_options['1'] = $this->lang->line('past_day');
		$date_select_options['7'] = $this->lang->line('past_week');
		$date_select_options['31'] = $this->lang->line('past_month');
		$date_select_options['182'] = $this->lang->line('past_six_months');
		$date_select_options['365'] = $this->lang->line('past_year');
		$date_select_options['custom_date'] = $this->lang->line('any_date');


		$type_select_options[''] = $this->lang->line('file_type');
		$type_select_options['all'] = $this->lang->line('all');
		$type_select_options['image'] = $this->lang->line('image');
		$type_select_options['non-image'] = $this->lang->line('non-image');

		$search_select_options[''] = $this->lang->line('search_in');
		$searcj_select_options['file_name'] = $this->lang->line('file_name');
		$search_select_options['title'] = $this->lang->line('file_title');
		$search_select_options['custom_field'] = $this->lang->line('custom_fields');
		$search_select_options['all'] = $this->lang->line('all');











		$no_upload_dirs = FALSE;
		
		if (empty($this->_upload_dirs))
		{
			$no_upload_dirs = TRUE;
		}
		else
		{
			
			$type = 'all';
			$dirs = ($dir_id == FALSE) ? $allowed_dirs : $dir_id;
		
			$filtered_entries= $this->file_model->get_files($dirs, $cat_id, $type, $per_page, $offset, $keywords, $order);
			$files = $filtered_entries['results'];
		
			// No result?  Show the "no results" message
			if ( ! $files)
			{
				// no results-- bail
			}
			
			$total = $this->file_model->count_files($allowed_dirs);
		

			$file_list = array();
			$dir_size = 0;

			$total_rows = $this->file_model->count_files($allowed_dirs);


			
			// Setup file list
			foreach ($files->result_array() as $k => $file)
			{


				$file_location = $this->functions->remove_double_slashes(
						$this->_upload_dirs[$file['upload_location_id']]['url'].'/'.$file['file_name']
					);

				$file_path = $this->functions->remove_double_slashes(
						$this->_upload_dirs[$file['upload_location_id']]['server_path'].'/'.$file['file_name']
					);

				$list = array(
					'file_id'	=> $file['file_id'],
					'title'		=> $file['title'],
					'name'		=> $file['file_name'],
					'link'		=> $file_location,
					'mime'		=> $file['mime_type'],
					'size'		=> $file['file_id'],
					'date'		=> $file['upload_date'],
					'path'		=> $file_path,
					'is_image'	=> FALSE,
					'dir_name'	=> $this->_upload_dirs[$file['upload_location_id']]['name']
				);				

				// Lightbox links
				if (strncmp($file['mime_type'], 'image', 5) === 0)
				{
					$list['is_image'] = TRUE;
					$list['link'] = '<a class="less_important_link overlay" id="img_'.str_replace(array(".", ' '), '', $file['file_name']).'" href="'.$file_location.'" title="'.$file['file_name'].'" rel="#overlay">'.$file['file_name'].'</a>';
				}

				$file_list[] = $list;
			}

			$base_url = BASE.AMP.'C=content_files'.AMP.'directory='.$selected_dir.AMP.'per_page='.$per_page;

			$link = "<img src=\"{$this->cp->cp_theme_url}images/pagination_%s_button.gif\" width=\"13\" height=\"13\" alt=\"%s\" />";

			$p_config = array(
				'base_url'				=> $base_url,
				'total_rows'			=> $total_rows,
	 			'per_page'				=> $per_page,
				'page_query_string'		=> TRUE,
				'query_string_segment'	=> 'offset',
				'full_tag_open'			=> '<p id="paginationLinks">',
				'full_tag_close'		=> '</p>',
				'prev_link'				=> sprintf($link, 'prev', '&lt;'),
				'next_link'				=> sprintf($link, 'next', '&gt;'),
				'first_link'			=> sprintf($link, 'first', '&lt; &lt;'),
				'last_link'				=> sprintf($link, 'last', '&gt; &gt;')
			);

			$this->pagination->initialize($p_config);

			$action_options = array(
				'download'			=> lang('download_selected'),
				'delete'			=> lang('delete_selected_files')
			);

			// Figure out where the count is starting 
			// and ending for the dialog at the bottom of the page
			$offset = ($this->input->get($p_config['query_string_segment'])) ? $this->input->get($p_config['query_string_segment']) : 0;
			$count_from = $offset + 1;
			$count_to = $offset + count($file_list);


			$pagination_count_text = sprintf(
										lang('pagination_count_text'),
										$count_from, $count_to, $total_rows);			
		}
		
		$data = array(
			'comments_enabled'		=> $comments_enabled,
			'no_upload_dirs'		=> $no_upload_dirs,
			'upload_dirs_options' 	=> $upload_dirs_options,
			'selected_dir'			=> $selected_dir,
			
			'category_options' 		=> $category_options,
			'selected_cat_id'		=> $cat_id,

			'date_select_options'	=> $date_select_options,
			'selected_date'			=> $date_range,

			'type_select_options'	=> $type_select_options,
			'selected_type'			=> $file_type,

			'search_in_options'		=> $search_select_options,
			'selected_search'		=> $search_type,
			'keywords'				=> $keywords,
			
			'files'					=> (isset($file_list)) ? $file_list : array(),
			'dir_size'				=> (isset($dir_size)) ? $dir_size : NULL,
			'pagination_links'		=> $this->pagination->create_links(),
			'action_options' 		=> (isset($action_options)) ? $action_options : NULL, 
			'pagination_count_text'	=> (isset($pagination_count_text)) ? $pagination_count_text : NULL,
		);
		
	
		$this->javascript->compile();
		$this->load->view('content/files/index', $data);
	}


	function category_filter_options($cat_group, $cat_form_array, $total_dirs)
	{
		$category_select_options[''] = $this->lang->line('filter_by_category');

		if ($total_dirs > 1)
		{				
			$category_select_options['all'] = $this->lang->line('all');
		}

		$category_select_options['none'] = $this->lang->line('none');

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
					$category_select_options['NULL_'.$i] = '-------';
            	}
            	
				$category_select_options[$cat['1']] = (str_replace("!-!","&nbsp;", $cat['2']));

            	if (isset($cat_form_array[$ckey+1]) && $cat_form_array[$ckey+1]['0'] != $cat['0'])
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
			show_error($this->lang->line('unauthorized_access'));
		}

		// In order to build our filtering options we need to gather 
		// all the channels and categories
		
		$dir_array	= array();
		

		foreach ($this->_upload_dirs as $id => $row)
		{
			$dir_array[$id] = array(str_replace('"','',$this->_upload_dirs[$id]['name']), $this->_upload_dirs[$id]['cat_group']);
		}		
		

		$default_cats[] = array('', $this->lang->line('filter_by_category'));
		$default_cats[] = array('all', $this->lang->line('all'));
		$default_cats[] = array('none', $this->lang->line('none'));		
		

		$file_info['0']['categories'] = $default_cats;		
	

		foreach ($dir_array as $key => $val)
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

			$file_info[$key]['categories'] = $cats;
			
		}

		$this->javascript->set_global('file.directoryInfo', $file_info);
	}



	// --------------------------------------------------------------------

	/**
	 * File ajax filter
	 */
	public function file_ajax_filter()
	{
		if ( ! AJAX_REQUEST)
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$allowed_directories = array();
		
		foreach ($this->_upload_dirs as $k =>$v)
		{
			$allowed_dirs[] = $k;
		}
		
		if (empty($allowed_dirs))
		{
			show_error($this->lang->line('unauthorized_access'));
		}		
		
		$this->output->enable_profiler(FALSE);
		$this->load->helper(array('form', 'text', 'url', 'snippets'));
		
		$dir_id = ($this->input->get_post('dir_id') != 'null' && $this->input->get_post('dir_id') != 'all') ? $this->input->get_post('dir_id') : '';
		$cat_id = ($this->input->get_post('cat_id') != 'all') ? $this->input->get_post('cat_id') : '';

		$status = ($this->input->get_post('status') != 'all') ? $this->input->get_post('status') : '';
		$date_range = $this->input->get_post('date_range');	
		$author_id = $this->input->get_post('author_id');	
	
		$keywords = ($this->input->get_post('keywords')) ? $this->input->get_post('keywords') : '';
		$search_in = ($this->input->get_post('search_in') != '') ? $this->input->get_post('search_in') : 'title';

		//$filter = $this->create_return_filter($filter_data);


		$perpage = $this->input->get_post('iDisplayLength');
		$offset = ($this->input->get_post('iDisplayStart')) ? $this->input->get_post('iDisplayStart') : 0; // Display start point		

		$sEcho = $this->input->get_post('sEcho');	
		
		// name, size, kind, date *** we don't have size in db

		$col_map = array('file_id', 'title', 'file_name', 'mime_type', 'upload_location_id', 'upload_date', '', '');


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
		
		$type = 'all';
		$dirs = ($dir_id == FALSE) ? $allowed_dirs : $dir_id;
		
		$filtered_entries = $this->file_model->get_files($dirs, $cat_id, $type, $perpage, $offset, $keywords, $order);





		
		// No result?  Show the "no results" message
		$total = $filtered_entries['filter_count'];
		$query_results = $filtered_entries['results'];
		

		$j_response['sEcho'] = $sEcho;
		$j_response['iTotalRecords'] = $this->file_model->count_files($allowed_dirs);  
		$j_response['iTotalDisplayRecords'] = $total;		
		
		$edit_link_base = BASE.AMP.'C=content_files'.AMP.'M=multi_edit_form'.AMP.'upload_dir=';
		$i = 0;
		
		foreach($query_results->result_array() as $file)
		{
			$file_location = $this->functions->remove_double_slashes(
						$this->_upload_dirs[$file['upload_location_id']]['url'].'/'.$file['file_name']
					);

			$file_path = $this->functions->remove_double_slashes(
						$this->_upload_dirs[$file['upload_location_id']]['server_path'].'/'.$file['file_name']
					);
					
			$is_image = FALSE;

			$m[] = $file['file_id'];
			$m[] = $file['title'];

			// Lightbox links
			if (strncmp($file['mime_type'], 'image', 5) === 0)
			{
				$is_image = TRUE;
				$m[] = '<a class="less_important_link overlay" id="img_'.str_replace(array(".", ' '), '', $file['file_name']).'" href="'.$file_location.'" title="'.$file['file_name'].'" rel="#overlay">'.$file['file_name'].'</a>';
			}
			else
			{
				$m[] = $file['file_name'];
			}
			
			$m[] = $file['mime_type'];
			$m[] = $this->_upload_dirs[$file['upload_location_id']]['name'];
			
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
			
			$m[] = $this->localize->decode_date($datestr, $file['upload_date'], TRUE);

			// Actions
			$actions = '<a href="'.$edit_link_base.$file['upload_location_id'].AMP.'file='.$file['file_name'].AMP.'action=download" title="'.$this->lang->line('file_download').'"><img src="'.$this->cp->cp_theme_url.'images/icon-download-file.png"></a>
						&nbsp;&nbsp;<a href="'.$edit_link_base.$file['upload_location_id'].AMP.'file='.$file['file_name'].AMP.'action=delete" title="'.$this->lang->line('delete_selected_files').'"><img src="'.$this->cp->cp_theme_url.'>images/icon-delete.png"></a>';

			if (strncmp($file['mime_type'], 'image', 5) === 0)
			{
				$actions .= '&nbsp;&nbsp;<a href="'.$edit_link_base.$file['upload_location_id'].AMP.'file='.urlencode($file['file_name']).'" title="'.$this->lang->line('edit_file').'"><img src="'.$this->cp->cp_theme_url.'images/icon-edit.png" alt="'.$this->lang->line('delete').'" /></a>';
			}
			
			$m[] = $actions;
			


			// Checkbox
			$m[] = form_checkbox('toggle[]', $file['file_id'], '', ' class="toggle" id="toggle_box_'.$file['file_id'].'"');

			$tdata[$i] = $m;
			$i++;
			unset($m);

		} // End foreach
		

		$j_response['aaData'] = $tdata;	

		$this->output->send_ajax_response($j_response);
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
		
		// All the directory information we need for the upload
		// destination.
		
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
	 *
	 * 
	 */
	public function rename_file()
	{
		$required = array('file_name', 'rename_attempt', 'orig_name', 'temp_file_name', 'is_image', 'temp_prefix', 'remove_spaces', 'id');
		
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
		$file_settings = $this->_get_file_settings();
		
		$files    = $file_settings['files'];
		$file_dir = $file_settings['file_dir'];
		
		switch ($this->input->get_post('action'))
		{
			case 'download':
				$this->_download_files($files, $file_dir);
				break;
			
			case 'delete':
				$this->_delete_files_confirm($files, $file_dir);
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
	 */
	private function _delete_files_confirm($files, $file_dir)
	{
		$data = array(
			'files'			=> $files,
			'file_dir'		=> $file_dir,
			'del_notice'	=> (count($files) == 1) ? 'confirm_del_file' : 'confirm_del_files'
		);

		$this->cp->set_variable('cp_page_title', lang('delete_selected_files'));

		$this->load->view('content/file_delete_confirm', $data);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Delete a list of files (and their thumbnails) from a particular directory
	 * Expects two GET/POST variables:
	 *  - file: an array of urlencoded file names to delete
	 *  - file_dir: the ID of the file directory to delete from
	 */
	public function delete_files()
	{
		$files     = $this->input->get_post('file');
		$file_dir  = $this->input->get_post('file_dir');
		
		// Bail if they dont' have access to this upload location.
		if ( ! array_key_exists($file_dir, $this->_upload_dirs))
		{
			show_error(lang('unauthorized_access'));
		}
		
		$file_path = $this->_upload_dirs[$file_dir]['server_path'];
				
		if ( ! $files OR ! $file_path OR $file_path === "")
		{
			$this->session->set_flashdata('message_failure', lang('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'directory='.$file_dir);
		}

		$delete = $this->filemanager->delete($files, $file_path, TRUE);		
		
		$message_type = ($delete) ? 'message_success' : 'message_failure';
		$message = ($delete) ? lang('delete_success') : lang('message_failure');
		
		$this->session->set_flashdata($message_type, $message);
		$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'directory='.$file_dir);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Download Files
	 *
	 * @param array $files Array of file names to download
	 * @param integer $file_dir ID of the directory to download from
	 */
	private function _download_files($files, $file_dir)
	{
		$files_count = count($files);
		
		if ( ! $files_count OR 
			 ! isset($this->_upload_dirs[$file_dir]['server_path']))
		{
			show_error(lang('unauthorized_access'));
		}
		
		$file_path = $this->_upload_dirs[$file_dir]['server_path'];
		
		if ( ! $this->filemanager->download_files($files, $file_path))
		{
			show_error(lang('unauthorized_access'));
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
		
		// No file, why are we here?
		if ( ! ($files = $this->input->get_post('file')))
		{
			show_error(lang('unauthorized_access'));
		}
		
		if ( ! is_array($files))
		{
			$files = array($files);
		}
		
		return array(
			'file_dir' => $file_dir,
			'files' => $files
		);
	}

	// ------------------------------------------------------------------------	
	
	/**
	 *
	 *
	 *
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
			// 'plugin'	=> array(''),
			'file'		=> 'cp/file_manager_edit',
			'plugin'	=> 'jcrop',
			)
		);

		// It cleans itself
		$file_name 	= $this->security->sanitize_filename($this->input->get('file'));
		
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

		}
		else
		{
			if ( ! array_key_exists($file_dir, $this->_upload_dirs))
			{
				show_error(lang('unauthorized_access'));
			}

			$ids = array($file_dir);
		}
		
		// Get the resize info for the directories
		$this->db->where_in('upload_location_id', $ids);
		$query = $this->db->get('file_dimensions');
		
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
				$js_size[$row->upload_location_id][$row->short_name] = array('resize_type' => $row->resize_type, 'width' => $row->width, 'height' => $row->height);
				$vars['sizes'][] = array('short_name' => $row->short_name, 'title' => $row->title, 'resize_type' => $row->resize_type, 'width' => $row->width, 'height' => $row->height);
			}
		}
		
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
	
	function do_sync_files()
	{
		$type = 'insert';
		$errors = array();
		$file_data = array();
		
		// If file exists- make sure it exists in db - otherwise add it to db and generate all child sizes
		// If db record exists- make sure file exists -  otherwise delete from db - ?? check for child sizes??
		
		if (($sizes = $this->input->post('sizes')) === FALSE OR ($current_files = $this->input->post('files')) === FALSE)
		{
			return FALSE;
		}
		
		$id = key($sizes);
		
		$dir_data = $this->_upload_dirs[$id];
		
		//$this->sync_database();


		// @todo, bail if there are no files in the directory!  :D

		$files = $this->filemanager->fetch_files($id, $current_files, TRUE);
		
		// Let's do a quick check of db to see if ANY file records for this directory
		//$this->db->where('upload_location_id', $id);
		//$this->db->from('files');
		//$do_db_check = ($this->db->count_all_results() == 0) ? FALSE : TRUE;			
			
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
				// It exists, but we need to check sizes 
				if (is_array($sizes[$id]))
				{
					$this->filemanager->sync_resized(
						array('server_path' => $this->_upload_dirs[$id]['server_path']), 
						array('name' => $file['name']),
						$sizes[$id]
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

			$file_data[] = array(
					'upload_location_id'	=> $id,
					'site_id'				=> $this->config->item('site_id'),
					'title'					=> $file['name'],
					'path'					=> $file_path,
					'status'				=> 'o',
					'mime_type'				=> $file['mime'],
					'file_name'				=> $file['name'],
					'file_size'				=> $file['size'],
					'metadata'				=> '',
					'uploaded_by_member_id'	=> 0,
					'upload_date'			=> 0,
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

		// Insert into categories???


			// Go ahead and create the thumb
			// For syncing- will need to tap into dir prefs and make all image variations- so batch needs to be small
			
			// Woot- Success!  Make a new thumb
			$thumb = $this->filemanager->create_thumb(
				array('server_path' => $this->_upload_dirs[$id]['server_path']), 
				array('name' => $file['name'])
			);	
			
			if (is_array($sizes[$id]))
			{
				$this->filemanager->sync_resized(
					array('server_path' => $this->_upload_dirs[$id]['server_path']), 
					array('name' => $file['name']),
					$sizes[$id]
				);
			}
		}
		
		// var_dump($file_data);
		// exit($this->output->send_ajax_response('failure before batch'));
		
		
		if ( ! empty($file_data))
		{
			$this->db->insert_batch('files', $file_data);
		}
		
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

	function sync_database()
	{
		$id = $this->input->post('dir_id');

		if ( ! array_key_exists($id, $this->_upload_dirs))
		{
			show_error(lang('unauthorized_access'));
		}
		
		
		$this->filemanager->sync_database($id);		
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
		$this->load->model('tools_model');

		$this->cp->set_variable('cp_page_title', lang('file_upload_prefs'));

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 2: {sorter: false}},
			widgets: ["zebra"]
		}');

		$vars['message'] = $message;
		$vars['upload_locations'] = $this->tools_model->get_upload_preferences($this->session->userdata('member_group'));

		$this->javascript->compile();

		$this->cp->set_right_nav(array('create_new_upload_pref' => BASE.AMP.'C=content_files'.AMP.'M=edit_upload_preferences'));
		
		$this->load->view('content/files/file_upload_preferences', $vars);
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
		
		$type = ($id) ? 'edit' : 'new';

		$fields = array(
			'id', 'site_id', 'name', 'server_path',
			'url', 'allowed_types', 'max_size',
			'max_height', 'max_width', 'properties',
			'pre_format', 'post_format', 'file_properties',
			'file_pre_format', 'file_post_format', 'batch_location', 
			'cat_group'
		);

		if ($type == 'new')
		{
			$data['form_hidden'] = NULL;
			$data['allowed_types'] = NULL;
			
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

		$data['upload_pref_fields'] = array(
							'max_size', 'max_height', 'max_width', 'properties', 
							'pre_format', 'post_format', 'file_properties', 
							'file_pre_format', 'file_post_format', 'batch_location');

		// Category Select List
		$this->load->model('category_model');
		$query = $this->category_model->get_category_groups('', FALSE, 1);

		$data['cat_group_options'][] = $this->lang->line('none');

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
						  )
					);

		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<span class="notice">', '</span>')
							  ->set_rules($config);
		
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
			show_error($this->lang->line('duplicate_dir_name'));
		}

		$id = $this->input->get_post('id');

		unset($_POST['id']);
		unset($_POST['cur_name']);
		unset($_POST['submit']); // submit button

		$data = array();
		$no_access = array();

		$this->db->delete('upload_no_access', array('upload_id' => $id));

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
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->model('tools_model');
		$this->lang->loadfile('admin_content');

		$name = $this->tools_model->delete_upload_preferences($id);

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
			if ($this->input->post('manual_batch'))
			{
				$this->_process_batch_upload();
			}
			elseif ($this->input->post('auto_batch'))
			{
				$this->_do_auto_batch();
			}
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
		
		
		
		
		
	}

	// --------------------------------------------------------------------
	
	/**
	 * Do manual batch processing
	 *
	 */
	private function _do_manual_batch()
	{
		var_dump($_POST);
	}
			
}
