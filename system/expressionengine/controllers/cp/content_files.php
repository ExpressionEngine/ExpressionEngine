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

class Content_files extends CP_Controller {

	private $_upload_dirs	= array();
	private $_allowed_dirs	= array();
	private $_base_url		= '';
	private $remove_spaces	= TRUE;
	private $temp_prefix	= "temp_file_";

	private $nest_categories = 'y';
	private $per_page		 = 40;
	private $pipe_length     = 1;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		// Permissions
		if ( ! $this->cp->allowed_group('can_access_content', 'can_access_files'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('filemanager');
		$this->load->library('api');
		$this->load->library('filemanager');
		$this->load->model('file_model');
		$this->load->model('file_upload_preferences_model');
		$this->cp->add_to_head($this->view->head_link('css/file_browser.css'));

		// Get upload dirs
		$upload_dirs = $this->filemanager->fetch_upload_dirs(array('ignore_site_id' => FALSE));

		foreach ($upload_dirs as $row)
		{
			$this->_upload_dirs[$row['id']] = $row;
		}

		if (AJAX_REQUEST)
        {
            $this->output->enable_profiler(FALSE);
        }


		$nav['file_manager']	= BASE.AMP.'C=content_files'.AMP.'M=index';

		if ($this->cp->allowed_group('can_admin_upload_prefs'))
		{
			$nav['file_upload_preferences']	= BASE.AMP.'C=content_files'.AMP.'M=file_upload_preferences';
			$nav['watermark_prefs']	= BASE.AMP.'C=content_files'.AMP.'M=watermark_preferences';
			//$nav['batch_upload']	= BASE.AMP.'C=content_files'.AMP.'M=batch_upload';
		}		

		$this->cp->set_right_nav($nav);
		
		$this->_base_url = BASE.AMP.'C=content_files';
	}

	// ------------------------------------------------------------------------

	/**
	 * Index Page
	 */
	public function index()
	{
		$this->load->library('table');
		$this->load->helper('search');
		$this->api->instantiate('channel_categories');
		
		$this->table->set_base_url('C=content_files');
		$this->table->set_columns(array(
			'file_id'		=> array('header' => '#'),
			'title'			=> array('header' => lang('file_title')),
			'file_name'		=> array(),
			'mime_type'		=> array('header' => lang('kind')),
			'upload_location_name' => array('header' => lang('dir_name')),
			'upload_date'	=> array('header' => lang('date')),
			'_actions'		=> array(
				'header' => lang('actions'),
				'sort' => FALSE
			),
			'_delete'		=> array(
				'header' => lang('action_delete'),
				'sort' => FALSE
			),
			'_check'		=> array(
				'sort'	 => FALSE,
				'header' => form_checkbox(
					array(
						'id'		=>'toggle_all',
						'name'		=>'toggle_all',
						'value'		=>'toggle_all',
						'checked'	=> FALSE
					)
				)
			)
		));
		
		$initial_state = array(
			'sort'	=> array('upload_date' => 'desc')
		);
		
		$data = $this->table->datasource('_files_filter', $initial_state);
		
		// Grab our private return data
		$get_post = $data['get_post'];
		$allowed_dirs = $data['allowed_dirs'];
		unset(
			$data['get_post'],
			$data['allowed_dirs']
		);

		// Create our various filter data
		$upload_dirs_options = array();
		$upload_dirs_options['null'] = lang('filter_by_directory');
		
		if (count($this->_upload_dirs) > 2)
		{
			$upload_dirs_options['all'] = lang('all');
		}

		foreach ($this->_upload_dirs as $k => $dir)
		{
			$upload_dirs_options[$dir['id']] = $dir['name'];
			$allowed_dirs[] = $k;
		}
		
		$selected_dir = ($selected_dir = $this->input->get_post('dir_id')) ? $selected_dir : NULL;
		
		// We need this for the filter, so grab it now
		$cat_form_array = $this->api_channel_categories->category_form_tree($this->nest_categories);
		
		// If we have directories we'll write the JavaScript menu switching code
		if (count($allowed_dirs) > 0)
		{
			$this->filtering_menus($cat_form_array);
		}

		// Cat filter
		$cat_group = isset($get_post['cat_id']) ? $get_post['cat_id'] : NULL;
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
			'custom_date'	=> lang('any_date')
		);

		$type_select_options = array(
			'1'				=> lang('file_type'),
			'all'			=> lang('all'),
			'image'			=> lang('image'),
			'non-image'		=> lang('non_image')
		);

		$search_select_options = array(
			'all'				=> lang('search_in'),
			'file_name'		=> lang('file_name'),
			'file_title'	=> lang('file_title'),
			'custom_field'	=> lang('custom_fields'),
			'all'			=> lang('all')
		);
		
		$no_upload_dirs = FALSE;
		
		if (empty($this->_upload_dirs))
		{
			$no_upload_dirs = TRUE;
		}

		$action_options = array(
			'download'			=> lang('download_selected'),
			'delete'			=> lang('delete_selected_files')
		);
		
		
		// Page Title
		$this->view->cp_page_title = lang('content_files');
		
		// both filebrowser and fileuploader need to be loaded because 
		// fileuploader depends on filebrowser's methods
		$this->cp->add_js_script(array(
			'plugin'	=> array(
				'overlay', 'ee_filebrowser', 'ee_fileuploader'
			),
			'file'		=> 'cp/files/file_manager_home',
			'ui' 		=> array('datepicker', 'dialog')
		));
		
		if ($allowed_dirs != FALSE)
		{
			$this->cp->set_action_nav(array(
				'upload_file' => ''
			));
		}

		$this->javascript->set_global(array(
			'lang' => array(
				'upload_file'	=> lang('upload_file')
			),
			'filebrowser' => array(
				'endpoint_url'	=> 'C=content_publish&M=filemanager_actions',
				'window_title'	=> lang('file_manager')
			),
			'fileuploader' => array(
				'window_title'	=> lang('file_upload'),
				'delete_url'	=> 'C=content_files&M=delete_files',
				'actions' => array(
					'download' 	=> '<a href="'.BASE.AMP.'C=content_files'.AMP.'M=multi_edit_form'.AMP.'file_id=[file_id]'.AMP.'action=download" title="'.lang('file_download').'"><img src="'.$this->cp->cp_theme_url.'images/icon-download-file.png"></a>',
					'delete'	=> '<a href="'.BASE.AMP.'C=content_files'.AMP.'M=multi_edit_form'.AMP.'file_id=[file_id]'.AMP.'action=delete" title="'.lang('delete_selected_files').'"><img src="'.$this->cp->cp_theme_url.'images/icon-delete.png"></a>',
					'edit'		=> '<a href="'.BASE.AMP.'C=content_files'.AMP.'M=edit_file'.AMP.'upload_dir=[upload_dir]'.AMP.'file_id=[file_id]" title="'.lang('edit_file').'"><img src="'.$this->cp->cp_theme_url.'images/icon-edit.png" alt="'.lang('edit_file').'" /></a>',
					'image'		=> '<a href="'.BASE.AMP.'C=content_files'.AMP.'M=edit_image'.AMP.'upload_dir=[upload_dir]'.AMP.'file_id=[file_id]" title="'.lang('edit_file').'"><img src="'.$this->cp->cp_theme_url.'images/icon-image.png" alt="'.lang('edit_image').'" /></a>'
				)
			)
		));
		

		$data = array_merge($data, array(
			'action_options' 		=> (isset($action_options)) ? $action_options : NULL,
			'category_options' 		=> $category_options,
			'date_select_options'	=> $date_select_options,
			'dir_size'				=> (isset($dir_size)) ? $dir_size : NULL,
			'files'					=> (isset($file_list)) ? $file_list : array(),
			'keywords'				=> $get_post['keywords'],
			'no_upload_dirs'		=> $no_upload_dirs,
			'pagination_count_text'	=> (isset($pagination_count_text)) ? $pagination_count_text : NULL,
			'pagination_links'		=> '', //$this->pagination->create_links(),
			'search_in_options'		=> $search_select_options,
			'selected_cat_id'		=> $get_post['cat_id'],
			'selected_date'			=> $get_post['date_range'],
			'selected_dir'			=> $selected_dir,
			'selected_search'		=> $get_post['search_type'],
			'selected_type'			=> $get_post['file_type'],
			'type_select_options'	=> $type_select_options,
			'upload_dirs_options' 	=> $upload_dirs_options
		));

		$this->cp->render('content/files/index', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * File ajax filter
	 */
	public function _files_filter($state)
	{
		// Setup get/post vars in class vars
		$get_post = $this->_fetch_get_post_vars();
		$allowed_dirs = $this->_setup_allowed_dirs();

		$dirs = ($get_post['dir_id'] === FALSE) ? $this->_allowed_dirs : $get_post['dir_id'];
		
		$params = array(
			'cat_id' 		=> $get_post['cat_id'], 
			'type'			=> $get_post['file_type'], 
			'limit'			=> $get_post['per_page'], 
			'offset'		=> $state['offset'],
			'search_value'	=> $get_post['keywords'], 
			'order'			=> $state['sort'], 
			'no_clue'		=> TRUE,
			'search_in'		=> ($get_post['search_in'] != '') ? $get_post['search_in'] : 'file_name',
			'date_start'	=> $get_post['date_start'],
			'date_end'		=> $get_post['date_end'],
			'date_range'	=> (substr($get_post['date_range'], 0, strlen($get_post['date_start'])) == $get_post['date_start'])
								? FALSE : $get_post['date_range']
		);
		
		$filtered_entries = $this->file_model->get_files($dirs, $params);

		$files = $filtered_entries['results'];
		$total_filtered = $filtered_entries['filter_count'];

		return array(
			'rows' => $this->_fetch_file_list($files, $total_filtered),
			'no_results' => sprintf(
				lang('no_uploaded_files'), 
				$this->cp->masked_url('http://ellislab.com/expressionengine/user-guide/cp/content/files/sync_files.html'),
				BASE.AMP.'C=content_files'.AMP.'M=file_upload_preferences'
			),
			'pagination' => array(
				'per_page'	 => $params['limit'],
				'total_rows' => $total_filtered
			),
			
			// regular returns
			'get_post' => $get_post,
			'allowed_dirs' => $allowed_dirs
		);
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

		if ($total_filtered > 0 AND ! empty($this->_upload_dirs))
		{
			// Date
			$date_fmt = ($this->session->userdata('time_format') != '') ?
							$this->session->userdata('time_format') : $this->config->item('time_format');

			$datestr = ($date_fmt == 'us') ? '%m/%d/%y %h:%i %a' : '%Y-%m-%d %H:%i';
			
			$file_list = array();
			$files = $files->result_array();

			// Setup file list
			while ($file = array_shift($files))
			{
				$r = array(
					'file_id' => $file['file_id'],
					'title'	=> $file['title']
				);
				
				$is_image = FALSE;

				$file_location = rtrim($this->_upload_dirs[$file['upload_location_id']]['url'], '/').'/'.rawurlencode($file['file_name']);

				$file_path = reduce_double_slashes(
					$this->_upload_dirs[$file['upload_location_id']]['server_path'].'/'.$file['file_name']
				);

				// Lightbox links
				if (strncmp($file['mime_type'], 'image', 5) === 0)
				{
					$is_image = $this->filemanager->is_editable_image($file_path, $file['mime_type']);

					$r['file_name'] = anchor(
						$file_location,
						$file['file_name'],
						array(
							'class'	=> 'less_important_link overlay',
							'id'	=> 'img_'.str_replace(array(".", ' '), '', $file['file_name']),
							'rel'	=> '#overlay'
						)
					);
				}
				else
				{
					$r['file_name'] = anchor(
						$file_location,
						$file['file_name'],
						array(
							'class'		=> 'less_important_link',
							'target'	=> '_blank'
						)
					);
				}

				$r['mime_type'] = $file['mime_type'];
				$r['upload_location_name'] = $this->_upload_dirs[$file['upload_location_id']]['name'];
				$r['upload_date'] = $this->localize->human_time($file['upload_date'], TRUE);

				$action_base = BASE.AMP.'C=content_files'.AMP.'M=multi_edit_form'.AMP.'file_id='.$file['file_id'];
				
				$actions = '<a href="'.$action_base.AMP.'action=download" title="'.lang('file_download').'"><img src="'.$this->cp->cp_theme_url.'images/icon-download-file.png"></a>';
				$actions .= NBS.NBS;
				$actions .= '<a href="'.BASE.AMP.'C=content_files'.AMP.'M=edit_file'.AMP.'upload_dir='.$file['upload_location_id'].AMP.'file_id='.$file['file_id'].'" title="'.lang('edit_file').'"><img src="'.$this->cp->cp_theme_url.'images/icon-edit.png" alt="'.lang('edit_file').'" /></a>';
				
				$delete_action = '<a href="'.$action_base.AMP.'action=delete" title="'.lang('delete_selected_files').'"><img src="'.$this->cp->cp_theme_url.'images/icon-delete.png"></a>';				

				if ($is_image)
				{
					$actions .= '&nbsp;&nbsp;';
					$actions .= '<a href="'.BASE.AMP.'C=content_files'.AMP.'M=edit_image'.AMP.'upload_dir='.$file['upload_location_id'].AMP.'file_id='.$file['file_id'].'" title="'.lang('edit_image').'"><img src="'.$this->cp->cp_theme_url.'images/icon-image.png" alt="'.lang('edit_image').'" /></a>';
				}

				$r['_actions'] = $actions;
				$r['_delete'] = $delete_action;
				$r['_check'] = form_checkbox('toggle[]', $file['file_id'], '', ' class="toggle" id="toggle_box_'.$file['file_id'].'"');
				
				$file_list[] = $r;
			}
		}

		return $file_list;
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
			'dir_id'		=> ($this->input->get_post('dir_id') != 'all' && $this->input->get_post('dir_id') != 'null') ? $this->input->get_post('dir_id') : FALSE,
			'date_range'	=> $this->input->get_post('date_range'),
			'file_type'		=> $this->input->get_post('file_type'),
			'keywords'		=> NULL, // Process this in a bit
			'offset'		=> ($offset = $this->input->get('offset')) ? $offset : 0,
			'order'			=> ($order = $this->input->get('offset')) ? $order : 0,
			'per_page'		=> ($per_page = $this->input->get('per_page')) ? $per_page : $this->per_page,
			'status'		=> ($this->input->get_post('status') != 'all') ? $this->input->get_post('status') : '',
			'search_in'		=> ($this->input->get_post('search_in')),
			'search_type'	=> $this->input->get_post('search_type'),
			'type'			=> ($type = $this->input->get_post('type')) ? $type : 'all',
			'date_range'	=> $this->input->get_post('date_range'),
			'date_start'	=> (($date_start = $this->input->get_post('custom_date_start')) != 'yyyy-mm-dd'
									AND $date_start !== FALSE) ? $date_start : FALSE,
			'date_end'		=> (($date_end = $this->input->get_post('custom_date_end')) != 'yyyy-mm-dd'
									AND $date_end !== FALSE) ? $date_end : FALSE
		);
		
		if ($this->input->post('keywords'))
		{
			$ret['keywords'] = sanitize_search_terms($this->input->post('keywords'));
		}
		elseif ($this->input->get('keywords'))
		{
			$ret['keywords'] = sanitize_search_terms(base64_decode($this->input->get('keywords')));
		}

		return $ret;
	}

	// --------------------------------------------------------------------

	/**
	 * Setup Allowed Upload Directories
	 *
	 * Cycles through upload dirs, returns false if there aren't any upload dirs
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
			return FALSE;
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
	 * Controls the batch actions
	 *
	 * When submitted to, expects a GET/POST variable named action containing
	 * either download or delete
	 */
	public function multi_edit_form()
	{
		$files = $this->_get_file_ids();
		
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
	 * Get the list of files and the directory ID for batch file actions
	 *
	 * @return array Associative array containing ['file_dir'] as the file directory
	 *    and ['files'] an array of the files to act upon.
	 */
	private function _get_file_ids()
	{
		if ($toggle = $this->input->post('toggle'))
		{
			$files = $toggle;
		}

		if ( ! isset($files))
		{
			// No file, why are we here?
			if ( ! ($files = $this->input->get_post('file_id')))
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

		$this->view->cp_page_title = lang('delete_selected_files');

		$this->cp->render('content/files/confirm_file_delete', $data);
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete a list of files (and their thumbnails) from a particular directory
	 * Expects two GET/POST variables:
	 *  - file: an array of file ids to delete
	 */
	public function delete_files()
	{
		$files = $this->input->get_post('file');

		// If no files were found, error out
		if ( ! $files)
		{
			$message_type = 'message_failure';
			$message = lang('choose_file');
			
			if (AJAX_REQUEST)
			{
				$this->output->send_ajax_response(array(
					'type'    => $message_type,
					'message' => $message
				), TRUE);
				
				return;
			}
			
			$this->session->set_flashdata($message_type, $message);
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}
		
		$delete = $this->file_model->delete_files($files);

		$message_type = ($delete) ? 'message_success' : 'message_failure';
		$message = ($delete) ? lang('delete_success') : lang('message_failure');

		if (AJAX_REQUEST)
		{
			$this->output->send_ajax_response(array(
				'type'    => $message_type,
				'message' => $message
			));
		}
		
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
	 * Edit's a file's metadata
	 */
	public function edit_file()
	{
		// Check to see if POST data is present, if it is, send it to 
		// _save_file to update the data
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="notice">', '</div>');
		$this->form_validation->set_rules('file_title', 'lang:file_title', 'trim|required');

		if ($this->form_validation->run())
		{
			return $this->_save_file();
		}
		
		// Get the file data
		$data = $this->_edit_setup('edit_file');

		// List out the tabs we'll need
		$data['tabs'] = array(
			'file_metadata'
		);
		
		// Get the categories
		$this->load->library('publish');
		$this->load->model(array('file_upload_preferences_model'));
		$category_group_ids = $this->file_upload_preferences_model->get_category_groups($data['upload_location_id']);		
		
		if (count($category_group_ids) != 1 OR current($category_group_ids) != '')
		{
			$data['tabs'][] = 'categories';
			$categories = $this->publish->build_categories_block($category_group_ids, $data['file_id'], NULL, '', TRUE);
			$data['categories'] = $categories;
		}
		
		// Create fields for the view
		$data['fields'] = array(
			'file_title' => array(
				'field' => form_input(array(
					'name' 	=> 'file_title',
					'id' 	=> 'file_title',
					'value' => $data['title'],
					'size' 	=> 255
				)),
				'type' => 'text',
				'required' => TRUE
			),
			'file_name' => array(
				'field' => '<span class="fake_input">' . $data['file_name'] . '</span>',
				'type' => 'text'
			),
			'description' => array(
				'field' => form_textarea(array(
					'name'	=> 'description',
					'id'	=> 'description',
					'value'	=> $data['description']
				)),
				'type' => 'textarea'
			),
			'credit' => array(
				'field' => form_input(array(
					'name'	=> 'credit',
					'id'	=> 'credit',
					'value'	=> $data['credit'],
					'size' 	=> 255
				)),
				'type' => 'text'
			),
			'location' => array(
				'field' => form_input(array(
					'name'	=> 'location',
					'id'	=> 'location',
					'value'	=> $data['location'],
					'size' 	=> 255
				)),
				'type' => 'text'
			)
		);
		
		// Droppable is in here because of publish_tabs
		$this->cp->add_js_script(array(
			'ui'		=> array('droppable'),
			'file'		=> array('cp/publish_tabs')
		));
				
		$this->cp->render('content/files/edit_file', $data);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Save the file's data to the database
	 */
	private function _save_file()
	{
		if ( ! ($file_id = $this->input->post('file_id')))
		{
			show_error(lang('unauthorized_access'));
		}
		
		// Update the file
		$this->file_model->save_file(array(
			'file_id'		=> $file_id,
			'title'			=> $this->input->post('file_title'),
			'description'	=> $this->input->post('description'),
			'credit'		=> $this->input->post('credit'),
			'location'		=> $this->input->post('location')
		));
		
		$this->load->model('file_category_model');
		
		// Delete existing categories
		$this->file_category_model->delete($file_id);
		
		// Add new categories
		$categories = $this->input->post('category');
		if ($categories)
		{
			foreach ($categories as $category_id) 
			{
				$this->file_category_model->set($file_id, $category_id);
			}
		}
		
		// Move em on out
		$this->session->set_flashdata('message_success', lang('file_saved'));
		$this->functions->redirect(
			BASE.AMP.
			'C=content_files'
		);
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
		$accordion_position = 0;
		
		// Setup and run validation first
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="notice">', '</div>');
		
		if (isset($_POST['save_image_crop']))
		{
			$this->form_validation->set_rules('crop_width', 'lang:crop_width', 'trim|numeric|greater_than[0]|required');
			$this->form_validation->set_rules('crop_height', 'lang:crop_height', 'trim|numeric|greater_than[0]|required');
			$this->form_validation->set_rules('crop_x', 'lang:crop_x', 'trim|numeric|required');
			$this->form_validation->set_rules('crop_y', 'lang:crop_y', 'trim|numeric|required');
		}
		else if (isset($_POST['save_image_rotate']))
		{
			$this->form_validation->set_rules('rotate', 'lang:rotate', 'required');
			$accordion_position = 1;
		}
		else if (isset($_POST['save_image_resize']))
		{
			$this->form_validation->set_rules('resize_width', 'lang:resize_width', 'trim|numeric|greater_than[0]|required');
			$this->form_validation->set_rules('resize_height', 'lang:resize_height', 'trim|numeric|greater_than[0]|required');
			$accordion_position = 2;
		}

		if ($this->form_validation->run())
		{
			return $this->filemanager->_do_image_processing();
		}
		
		$data = $this->_edit_setup('edit_image');
		
		// Prep javascript with globals, libraries and accordion call
		$this->javascript->set_global(array(
			'filemanager'	=> array(
				'image_width'				=> $data['file_info']['width'],
				'image_height'				=> $data['file_info']['height'],
				'resize_over_confirmation' 	=> lang('resize_over_confirmation')
			),
		));
		
		$this->cp->add_js_script(array(
			'file'		=> 'cp/files/file_manager_edit',
			'plugin'	=> array('jcrop', 'ee_resize_scale'),
			'ui'		=> 'accordion'
		));
		
		$this->javascript->output('
	        $("#file_manager_toolbar").accordion({
				autoHeight: false, 
				header: "h3",
				active: ' . $accordion_position . '
			});
		');
		
		$this->cp->render('content/files/edit_image', $data);
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Setup for an edit page (edit file and edit image)
	 * Gets the data to pass to the views which includes:
	 * 	- file path
	 * 	- file info
	 * 	- file url
	 * 	- file name
	 * 
	 * @param string $page_title_lang_key The lang key for the page title
	 * @return array Array containing the file's info from the database, as
	 * 		well as some formatted information for the edit file and 
	 * 		edit image pages
	 */
	private function _edit_setup($page_title_lang_key)
	{
		// Page Title
		$this->view->cp_page_title = lang($page_title_lang_key);
		$this->cp->set_breadcrumb(BASE.AMP.'C=content_files', lang('file_manager'));

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

		// Set header to not cache anything
		$this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
		$this->output->set_header("Pragma: no-cache");

		// Figure out the file_id now
		$file_id = $this->input->get_post('file_id');

		// Get the information from the database
		$file_query = $this->db->get_where('files', array(
			'file_id' => $file_id
		));
			
		$file = $file_query->row_array();
		
		// Some vars for later
		$file_name	= $file['file_name'];
		$file_url	= $this->_upload_dirs[$file_dir]['url'].rawurldecode($file_name);
		$file_path	= $this->_upload_dirs[$file_dir]['server_path'].rawurldecode($file_name);

		// Does this file exist?
		if ( ! file_exists($file_path))
		{
			show_error(lang('unauthorized_access'));
		}

		$file_info = $this->filemanager->get_file_info($file_path);
		
		$data = array(
			'filemtime'		=> ($filemtime = @filemtime($file_path)) ? $filemtime : 0,
			'file_info'		=> $file_info,
			'file_name'		=> $file_name,
			'file_path'		=> $file_path,
			'file_url'		=> $file_url,
			'form_hiddens'	=> array(
				'upload_dir'	=> $file['upload_location_id'], 
				'file_name'		=> $file_name, 
				'file_id'		=> $file_id
			)
		);
		
		$data = array_merge($file, $data);
		
		return $data;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Checks for images with no record in the database and adds them
	 */
	public function sync_directory()
	{
		if ( ! $this->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$file_dir  = $this->input->get('id');
		$cid = $file_dir;
		$var['sizes'] = array();

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
								
				$vars['sizes'][] = array('short_name' => $row->short_name, 'title' => $row->title, 'resize_type' => lang($row->resize_type), 'width' => $row->width, 'height' => $row->height, 'id' => $row->id, 'wm_name' => ($row->wm_name) ? $row->wm_name : '--');
				
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
					$js_size[$row->upload_location_id][$row->id]['wm_hor_offset'] = $row->wm_hor_offset;
					$js_size[$row->upload_location_id][$row->id]['wm_vrt_offset'] = $row->wm_vrt_offset;
					$js_size[$row->upload_location_id][$row->id]['wm_x_transp'] = $row->wm_x_transp;
					$js_size[$row->upload_location_id][$row->id]['wm_y_transp'] = $row->wm_y_transp;
					$js_size[$row->upload_location_id][$row->id]['wm_font_color'] =	$row->wm_font_color;
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

		$this->view->cp_page_title = $this->_upload_dirs[$cid]['name'];

		$this->cp->set_breadcrumb(BASE.AMP.'C=content_files', lang('file_manager'));			
		$this->cp->set_breadcrumb(
			BASE.AMP.'C=content_files'.AMP.'M=file_upload_preferences',
			lang('file_upload_prefs')
		);
		
		$this->cp->render('content/files/sync', $vars);


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
		if ( ! $this->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$type = 'insert';
		$errors = array();
		$file_data = array();
		$replace_sizes = array();
		$db_sync = ($this->input->post('db_sync') == 'y') ? 'y' : 'n';
		
		// If file exists- make sure it exists in db - otherwise add it to db and generate all child sizes
		// If db record exists- make sure file exists -  otherwise delete from db - ?? check for child sizes??

		if (
			(($sizes = $this->input->post('sizes')) === FALSE OR
			($current_files = $this->input->post('files')) === FALSE) AND
			$db_sync != 'y'
		)
		{
			return FALSE;
		}

		$id = key($sizes);
		
		// Final run through, it syncs the db, removing stray records and thumbs
		if ($db_sync == 'y')
		{
			$this->filemanager->sync_database($id);
			$errors[] = 'synced!';
	
			if (AJAX_REQUEST)
			{
				return $this->output->send_ajax_response(array(
					'message_type'	=> 'success'
				));
			}
			
			return;
		}		
		
		$dir_data = $this->_upload_dirs[$id];
		
		$this->filemanager->xss_clean_off();
		$dir_data['dimensions'] = (is_array($sizes[$id])) ? $sizes[$id] : array();
		$this->filemanager->set_upload_dir_prefs($id, $dir_data);		
		
		// Now for everything NOT forcably replaced
		
		$missing_only_sizes = (is_array($sizes[$id])) ? $sizes[$id] : array();

		// Check for resize_ids
		$resize_ids = $this->input->post('resize_ids');

		if (is_array($resize_ids))
		{
			foreach ($resize_ids as $resize_id)
			{
				$replace_sizes[$resize_id] = $sizes[$id][$resize_id];
				unset($missing_only_sizes[$resize_id]);
			}
		}

		// @todo, bail if there are no files in the directory!  :D

		$files = $this->filemanager->fetch_files($id, $current_files, TRUE);

		// Setup data for batch insert
		foreach ($files->files[$id] as $file)
		{
			if ( ! $file['mime'])
			{
				$errors[$file['name']] = lang('invalid_mime');
				continue;
			}

			// Clean filename
			$clean_filename = basename($this->filemanager->clean_filename(
				$file['name'], 
				$id,
				array('convert_spaces' => FALSE)
			));	

			if ($file['name'] != $clean_filename)
			{
				// It is just remotely possible the new clean filename already exists 
				// So we check for that and increment if such is the case 
				if (file_exists($this->_upload_dirs[$id]['server_path'].$clean_filename)) 
				{ 
					$clean_filename = basename($this->filemanager->clean_filename(
						$clean_filename, 
						$id, 
						array(
							'convert_spaces' => FALSE,
							'ignore_dupes' => FALSE
						)
					)); 
				} 

				// Rename the file
        		if ( ! @copy($this->_upload_dirs[$id]['server_path'].$file['name'],
	 						$this->_upload_dirs[$id]['server_path'].$clean_filename))
				{
					$errors[$file['name']] = lang('invalid_filename');
					continue;
				}

				unlink($this->_upload_dirs[$id]['server_path'].$file['name']);	
				$file['name'] = $clean_filename;			
			}

			// Does it exist in DB?
			$query = $this->file_model->get_files_by_name($file['name'], $id);

			if ($query->num_rows() > 0)
			{
				// It exists, but do we need to change sizes or add a missing thumb?
				
				if ( ! $this->filemanager->is_editable_image($this->_upload_dirs[$id]['server_path'].$file['name'], $file['mime']))
				{
					continue;
				}	
				
				// Note 'Regular' batch needs to check if file exists- and then do something if so
				if ( ! empty($replace_sizes))
				{
					$thumb_created = $this->filemanager->create_thumb(
						$this->_upload_dirs[$id]['server_path'].$file['name'],
						array(
							'server_path'	=> $this->_upload_dirs[$id]['server_path'],
							'file_name'		=> $file['name'],
							'dimensions'	=> $replace_sizes,
							'mime_type'		=> $file['mime']
						),
						TRUE,	// Create thumb
						FALSE	// Overwrite existing thumbs
					);
					
					if ( ! $thumb_created)
					{
						$errors[$file['name']] = lang('thumb_not_created');
					}
				}

				// Now for anything that wasn't forcably replaced- we make sure an image exists
				$thumb_created = $this->filemanager->create_thumb(
					$this->_upload_dirs[$id]['server_path'].$file['name'],
					array(
						'server_path'	=> $this->_upload_dirs[$id]['server_path'],
						'file_name'		=> $file['name'],
						'dimensions'	=> $missing_only_sizes,
						'mime_type'		=> $file['mime']
					),
					TRUE, 	// Create thumb
					TRUE 	// Don't overwrite existing thumbs
				);
				
				$file_path_name = $this->_upload_dirs[$id]['server_path'].$file['name'];
				
				// Update dimensions
				$image_dimensions = $this->filemanager->get_image_dimensions($file_path_name);
				
				$file_data = array(
					'file_id'				=> $query->row('file_id'),
					'file_size'				=> filesize($file_path_name),
					'file_hw_original'		=> $image_dimensions['height'] . ' ' . $image_dimensions['width']
				);
				$this->file_model->save_file($file_data);
				
				continue;
			}
			
			$file_location = reduce_double_slashes(
				$dir_data['url'].'/'.$file['name']
			);

			$file_path = reduce_double_slashes(
				$dir_data['server_path'].'/'.$file['name']
			);

			$file_dim = (isset($file['dimensions']) && $file['dimensions'] != '') ? str_replace(array('width="', 'height="', '"'), '', $file['dimensions']) : '';
			
			$image_dimensions = $this->filemanager->get_image_dimensions($file_path);

			$file_data = array(
				'upload_location_id'	=> $id,
				'site_id'				=> $this->config->item('site_id'),
				'rel_path'				=> $file_path, // this will vary at some point
				'mime_type'				=> $file['mime'],
				'file_name'				=> $file['name'],
				'file_size'				=> $file['size'],
				'uploaded_by_member_id'	=> $this->session->userdata('member_id'),
				'modified_by_member_id' => $this->session->userdata('member_id'),
				'file_hw_original'		=> $image_dimensions['height'] . ' ' . $image_dimensions['width'],
				'upload_date'			=> $file['date'],
				'modified_date'			=> $file['date']
			);
			
			
			$saved = $this->filemanager->save_file($this->_upload_dirs[$id]['server_path'].$file['name'], $id, $file_data, FALSE);
			
			if ( ! $saved['status'])
			{
				$errors[$file['name']] = $saved['message'];
			}
		}

		if ($db_sync == 'y')
		{
			$this->filemanager->sync_database($id);
		}
		
		if (AJAX_REQUEST)
		{
			if (count($errors))
			{
				return $this->output->send_ajax_response(array(
					'message_type'	=> 'failure',
					'errors'		=> $errors
				));
			}

			return $this->output->send_ajax_response(array(
				'message_type'	=> 'success'
			));
		}
	}

	// ------------------------------------------------------------------------

	function sync_database()
	{
		if ( ! $this->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

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
		if ( ! $this->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');
		$this->load->model('file_model');

		$this->view->cp_page_title = lang('watermark_prefs');
		$this->cp->set_breadcrumb($this->_base_url, lang('file_manager'));		
		

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 2: {sorter: false}},
			widgets: ["zebra"]
		}');

		$vars['watermarks'] = $this->file_model->get_watermark_preferences();

		$this->cp->set_action_nav(array('create_new_wm_pref' => BASE.AMP.'C=content_files'.AMP.'M=edit_watermark_preferences'));

		$this->cp->render('content/files/watermark_preferences', $vars);


	}

	// ------------------------------------------------------------------------

	/**
	 * Checks for images with no record in the database and adds them
	 */
	function edit_watermark_preferences()
	{
		if ( ! $this->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library(array('table', 'filemanager'));
		$this->load->model('file_model');
		
		$this->cp->add_js_script(array(
			'plugin' => array('jscolor'),
			'file'   => array('cp/files/watermark_settings')
		));
		
		$id = $this->input->get_post('id');
		$type = ($id) ? 'edit' : 'new';	
		
		$this->view->cp_page_title = lang('wm_'.$type);
		$this->cp->set_breadcrumb($this->_base_url, lang('file_manager'));
		$this->cp->set_breadcrumb($this->_base_url.AMP.'M=watermark_preferences', lang('watermark_prefs'));

		// if (FALSE)
		// {
		// 	show_error(lang('unauthorized_access'));
		// }

		$default_fields = array(
			'wm_name'				=> '',
			'wm_image_path'			=>	'',
			'wm_test_image_path'	=>	'',
			'wm_type'				=> 'text',
			'type_image'			=>  0,
			'type_text'				=>	1,
			'wm_use_font'			=> 'y',
			'font_yes'				=> 1,
			'font_no'				=> 0,
			'wm_font'				=> 'texb.ttf',
			'wm_font_size'			=> 16,
			'wm_text'				=> 'Copyright '.date('Y', $this->localize->now),
			'wm_alignment'			=> '',
			'wm_vrt_alignment'		=> 'top',
			'wm_hor_alignment'		=> 'left',
			'wm_padding'			=> 10,
			'wm_hor_offset'			=> 0,
			'wm_vrt_offset'			=> 0,
			'wm_x_transp'			=> 2,
			'wm_y_transp'			=> 2,
			'wm_font_color'			=> '#ffff00',
			'wm_use_drop_shadow'	=> 'y',
			'use_drop_shadow_yes'	=> 1,
			'use_drop_shadow_no'	=> 0,
			'wm_shadow_color'		=> '#999999',
			'wm_shadow_distance'	=> 1,
			'wm_opacity'			=> 50,
			'wm_apply_to_thumb'		=> 'n',
			'wm_apply_to_medium'	=> 'n'
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
			$vars['type_text']           = ($vars['wm_type'] == 't' OR $vars['wm_type'] == 'text') ? TRUE : FALSE;
			$vars['type_image']          = ($vars['wm_type'] == 't' OR $vars['wm_type'] == 'text') ? FALSE : TRUE;
			$vars['font_yes']            = ($vars['wm_use_font'] == 'y') ? TRUE : FALSE;
			$vars['font_no']             = ($vars['wm_use_font'] == 'y') ? FALSE : TRUE;
			$vars['use_drop_shadow_yes'] = ($vars['wm_use_drop_shadow'] == 'y') ? TRUE : FALSE;
			$vars['use_drop_shadow_no']  = ($vars['wm_use_drop_shadow'] == 'y') ? FALSE : TRUE;
			$vars['hidden']              = array('id' => $id);
		}

		for ($i = 1; $i <= 100; $i++)
		{ 
			$vars['opacity_options'][$i] = $i;
		}

		$this->load->library('form_validation');

		$title = ($type == 'edit') ? 'wm_edit' : 'wm_create';

		$vars['font_options'] = $this->filemanager->fetch_fontlist();
		$vars['lang_line'] = ($type == 'edit') ? 'update' : 'submit';

		$config = array(
			array(
				'field' => 'name',
				'label' => 'lang:wm_name',
				'rules' => 'trim|required|callback__name_check'
			),
			array(
				'field' => 'wm_type',
				'label' => 'lang:wm_type',
				'rules' => 'required'
			),
			array(
				'field' => 'wm_image_path',
				'label' => 'lang:wm_image_path',
				'rules' => ''
			),
			array(
				'field' => 'wm_test_image_path',
				'label' => 'lang:wm_test_image_path',
				'rules' => ''
			),
			array(
				'field' => 'wm_font',
				'label' => 'lang:wm_font',
				'rules' => ''
			),
			array(
				'field' => 'wm_font_size',
				'label' => 'lang:wm_font_size',
				'rules' => 'integer'
			),
			array(
				'field' => 'wm_hor_offset',
				'label' => 'lang:wm_hor_offset',
				'rules' => 'integer'
			),
			array(
				'field' => 'wm_vrt_offset',
				'label' => 'lang:wm_vrt_offset',
				'rules' => 'integer'
			),
			array(
				'field' => 'wm_vrt_alignment',
				'label' => 'lang:wm_vrt_alignment',
				'rules' => ''
			),
			array(
				'field' => 'wm_hor_alignment',
				'label' => 'lang:wm_hor_alignment',
				'rules' => ''
			),
			array(
				'field' => 'wm_x_transp',
				'label' => 'lang:wm_x_transp',
				'rules' => 'integer'
			),
			array(
				'field' => 'wm_y_transp',
				'label' => 'lang:wm_y_transp',
				'rules' => 'integer'
			),
			array(
				'field' => 'wm_font_color',
				'label' => 'lang:wm_font_color',
				'rules' => ''
			),
			array(
				'field' => 'wm_shadow_color',
				'label' => 'lang:wm_shadow_color',
				'rules' => ''
			)
		);

		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<span class="notice">', '</span>')
							  ->set_rules($config);
		$this->form_validation->set_old_value('wm_id', $id);

		if ( ! $this->form_validation->run())
		{
			$this->cp->render('content/files/watermark_settings', $vars);
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
			$this->form_validation->set_message('_name_check', lang('wm_name_taken'));
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
						'wm_hor_offset'					=> 0,
						'wm_vrt_offset'					=> 0,
						'wm_x_transp'					=> 2,
						'wm_y_transp'					=> 2,
						'wm_font_color'					=> '#ffff00',
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
		if ( ! $this->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$id = $this->input->get_post('id');

		if ( ! $id)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->view->cp_page_title = lang('delete_wm_preference');


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

		$this->cp->render('content/files/pref_delete_confirm', $data);

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
		if ( ! $this->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$id = $this->input->get_post('id');

		if ( ! $id)
		{
			show_error(lang('unauthorized_access'));
		}


		$name = $this->file_model->delete_watermark_preferences($id);

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
		if ( ! $this->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');

		$this->view->cp_page_title = lang('file_upload_prefs');
		$this->cp->set_breadcrumb(BASE.AMP.'C=content_files', lang('file_manager'));

		$this->jquery->tablesorter('.mainTable', '{
			headers: {1: {sorter: false}, 2: {sorter: false}},
			widgets: ["zebra"]
		}');

		$vars['message'] = $message;
		$vars['upload_locations'] = $this->file_upload_preferences_model->get_file_upload_preferences($this->session->userdata('group_id'));


		$this->cp->set_action_nav(array('create_new_upload_pref' => BASE.AMP.'C=content_files'.AMP.'M=edit_upload_preferences'));

		$this->cp->render('content/files/file_upload_preferences', $vars);
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
		if ( ! $this->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('table');
		$id = $this->input->get_post('id');

		$type = ($id) ? 'edit' : 'new';		$type = ($id) ? 'edit' : 'new';

		$this->cp->add_js_script(array('file' => 'cp/files/upload_pref_settings'));


		$this->javascript->set_global(array('lang' => array(
											'size_deleted'	=> lang('size_deleted'),
											'size_not_deleted' => lang('size_not_deleted')
										)
									));




		$fields = array(
			'id', 'site_id', 'name', 'server_path',
			'url', 'allowed_types', 'max_size',
			'max_width', 'max_height', 'max_image_action', 'properties',
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
			
			$data['field_url'] = base_url();
			$data['field_server_path'] = str_replace(SYSDIR.'/', '', FCPATH);
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

		// Page Title
		$this->cp->set_breadcrumb(BASE.AMP.'C=content_files', lang('file_manager'));

		$title = ($type == 'edit') ? 'edit_file_upload_preferences' : 'new_file_upload_preferences';

		$this->view->cp_page_title = lang($title);
		$data['lang_line'] = ($type == 'edit') ? 'update' : 'submit';

		$this->cp->set_breadcrumb($this->_base_url.AMP.'M=file_upload_preferences',
								  lang('file_upload_preferences'));

		$data['upload_pref_fields1'] = array(
							'max_size', 'max_width', 'max_height');

		$data['upload_pref_fields2'] = array(
							'properties', 'pre_format', 'post_format', 'file_properties',
							// 'file_pre_format', 'file_post_format', 'batch_location');
							// TODO: Enable batch location again
							'file_pre_format', 'file_post_format');

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
							 'field'   => 'max_width',
							 'label'   => 'lang:max_width',
							 'rules'   => 'numeric'
						  ),
					   array(
							 'field'   => 'max_height',
							 'label'   => 'lang:max_height',
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
			$this->cp->render('content/files/file_upload_create', $data);
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
		if ( ! $this->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}
		
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
		if (
			$this->admin_model->unique_upload_name(
				strtolower($this->input->post('name')),
				strtolower($this->input->post('cur_name')), 
				$edit
			)
		)
		{
			show_error(lang('duplicate_dir_name'));
		}

		$id = $this->input->get_post('id');

		unset($_POST['id']);
		unset($_POST['cur_name']);
		unset($_POST['submit']); // submit button
		unset($_POST['add_image_size']);
		unset($_POST['add_size']);
		

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
				if (isset($_POST['size_short_name_'.$row['id']]))
				{
					if ((trim($_POST['size_short_name_'.$row['id']]) == '' OR
						in_array($_POST['size_short_name_'.$row['id']], $names)) && ! isset($_POST['remove_size_'.$row['id']]))
					{
						return $this->output->show_user_error('submission', array(lang('invalid_shortname')));
					}
					
					
					// Do we need to delete?
					if (isset($_POST['remove_size_'.$row['id']]))
					{
						unset($_POST['remove_size_'.$row['id']]);
						unset($_POST['size_short_name_'.$row['id']]);

						$this->db->where('id', $row['id']);
						$this->db->delete('file_dimensions');
						
						continue;
					}
					
					$updatedata = array(
						'site_id' => $this->config->item('site_id'),
						'short_name' => $_POST['size_short_name_'.$row['id']],
						'title'	=> $_POST['size_short_name_'.$row['id']],
						'resize_type' => $_POST['size_resize_type_'.$row['id']],
						'height' => ($_POST['size_height_'.$row['id']] == '') ? 0 : $_POST['size_height_'.$row['id']],
						'width' => ($_POST['size_width_'.$row['id']] == '') ? 0 : $_POST['size_width_'.$row['id']],
						'watermark_id' => $_POST['size_watermark_id_'.$row['id']]
					);

					$this->db->where('id', $row['id']);
					$this->db->update('file_dimensions', $updatedata);

					$names[]  = $_POST['size_short_name_'.$row['id']];
					unset($_POST['size_short_name_'.$row['id']]);
					
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
					
					$short_name = $this->input->post($name);
					
					if ($short_name === FALSE OR
						preg_match('/[^a-z0-9\_\-]/i', $short_name) OR
						in_array(strtolower($short_name), $names) OR
						strtolower($short_name) == 'thumbs')
					{
						return $this->output->show_user_error('submission', array(lang('invalid_short_name')));
					}
					
					$size_data = array(
						'site_id' => $this->config->item('site_id'),
						'upload_location_id' => $id,
						'short_name' => $short_name,
						'title' => $_POST['size_short_name_'.$number],
						'resize_type' => $_POST['size_resize_type_'.$number],
						'height' => ($_POST['size_height_'.$number] == '') ? 0 : $_POST['size_height_'.$number],
						'width' => ($_POST['size_width_'.$number] == '') ? 0 : $_POST['size_width_'.$number],
						'watermark_id' => $_POST['size_watermark_id_'.$number]
					);
					
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
		
		if (isset($size_data))
		{
			// Set upload location id in size data 
			$size_data['upload_location_id'] = $id;
			$this->db->insert('file_dimensions', $size_data);
		}

		if (count($no_access) > 0)
		{
			foreach($no_access as $member_group)
			{
				$this->db->insert(
					'upload_no_access',
					array(
						'upload_id'		=> $id,
						'upload_loc'	=> 'cp',
						'member_group'	=> $member_group
					)
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
		if ( ! $this->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}
		
		$id = $this->input->get_post('id');

		if ( ! $id)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->view->cp_page_title = lang('delete_upload_preference');
		$this->cp->set_breadcrumb(BASE.AMP.'C=content_files', lang('file_manager'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=content_files'.AMP.'M=file_upload_preferences',
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
		$items = $this->file_upload_preferences_model->get_file_upload_preferences(NULL, $id);
		$data['items'] = array();

		if (isset($items['name']))
		{
			$data['items'][] = $items['name'];
		}

		$this->cp->render('content/files/pref_delete_confirm', $data);
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
		if ( ! $this->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}
		
		$id = $this->input->get_post('id');

		if ( ! $id)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('admin_content');

		$name = $this->file_upload_preferences_model->delete_upload_preferences($id);

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

		$this->view->cp_page_title = lang('batch_upload');
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

		$this->cp->render('content/files/batch_upload_index', $data);
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

		$this->view->cp_page_title = lang('batch_upload');

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

		$this->cp->render('content/files/manual_batch', $data);
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
