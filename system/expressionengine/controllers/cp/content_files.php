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
		
		// Get upload dirs
		$this->_upload_dirs = $this->filemanager->fetch_upload_dirs();
		
		if (AJAX_REQUEST)
        {
            $this->output->enable_profiler(FALSE);
        }
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Index Page
	 *
	 *
	 *
	 *
	 *
	 *
	 */
	public function index()
	{

		$this->load->library(array('pagination'));
		
		// Page Title
		$this->cp->set_variable('cp_page_title', lang('content_files'));
		
		$per_page = ($per_page = $this->input->get('per_page')) ? $per_page : 40;
		$offset = ($offset = $this->input->get('offset')) ? $offset : 0;
		$upload_dirs_options = array();
	
		foreach ($this->_upload_dirs as $dir)
		{
			$upload_dirs_options[$dir['id']] = $dir['name'];
		}

		ksort($upload_dirs_options);

		$selected_dir = ($selected_dir = $this->input->get('directory')) ? $selected_dir : NULL;
		
		if ( ! $selected_dir)
		{		
			$selected_dir = array_search(current($upload_dirs_options), $upload_dirs_options);
		}
		
		$files = $this->filemanager->fetch_files($selected_dir);
		
		$file_list = array();
		$dir_size = 0;

		// Setup file list
		foreach ($files->files[$selected_dir] as $file)
		{
			if ( ! $file['mime'])
			{
				continue;
			}
			
			$file_location = $this->functions->remove_double_slashes(
					$this->_upload_dirs[$selected_dir]['url'].'/'.$file['name']
				);
			
			$file_path = $this->functions->remove_double_slashes(
					$this->_upload_dirs[$selected_dir]['server_path'].'/'.$file['name']
				);
			
			$file_list[] = array(
				'name'		=> $file['name'],
				'link'		=> $file_location,
				'mime'		=> $file['mime'],
				'size'		=> $file['size'],
				'date'		=> $file['date'],
				'path'		=> $file_path,
			);
			
			$dir_size = $dir_size + $file['size'];
		}
		
		$total_rows = count($file_list);
		
		$file_list = array_slice($file_list, $offset, $per_page);
		
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
		
		$data = array(
			'upload_dirs_options' 	=> $upload_dirs_options,
			'selected_dir'			=> $selected_dir,
			'files'					=> $file_list,
			'dir_size'				=> $dir_size,
			'pagination_links'		=> $this->pagination->create_links(),
			'action_options' 		=> $action_options, 
			'pagination_count_text'	=> $pagination_count_text,
		);
		
		$this->load->view('content/files/index', $data);
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Download Files
	 *
	 *
	 */
	public function download_files()
	{
		// Do some basic permissions checking
		if ( ! ($file_dir = $this->input->get('dir')))
		{
			show_error(lang('unauthorized_access'));
		}
		
		// Bail if they dont' have access to this upload location.
		if ( ! array_key_exists($file_dir, $this->_upload_dirs))
		{
			show_error(lang('unauthorized_access'));			
		}
		
		// No file, why are we here?
		if ( ! ($file = $this->input->get('file')))
		{
			show_error(lang('unauthorized_access'));
		}
		
		// Base64 decode the filename from the URL.
		$filename = base64_decode($file);
		$file = $this->_upload_dirs[$file_dir]['server_path'] . $filename;
		
		$this->load->helper('download');
		
		$file_contents = file_get_contents($file);
		
		force_download($filename, $file_contents);
	}

	// ------------------------------------------------------------------------	
	
	/**
	 *
	 *
	 *
	 */
	public function delete_files()
	{		
		// Do some basic permissions checking
		if ( ! ($file_dir = $this->input->get('dir')))
		{
			show_error(lang('unauthorized_access'));
		}
		
		// Bail if they dont' have access to this upload location.
		if ( ! array_key_exists($file_dir, $this->_upload_dirs))
		{
			show_error(lang('unauthorized_access'));			
		}
		
		// No file, why are we here?
		if ( ! ($file = $this->input->get('file')))
		{
			show_error(lang('unauthorized_access'));
		}
		
		if ( ! isset($_POST['delete_confirm']))
		{
			$this->_delete_files_confirm($file, $file_dir);
		}
				
	}

	// ------------------------------------------------------------------------	
	
	/**
	 * Confirm File Deletion
	 *
	 * @param 	string		base64_encoded string of files to delete
	 * @param 	string		directory to delete
	 * @return 	void
	 */
	private function _delete_files_confirm($files, $file_dir)
	{
	
	}


	// ------------------------------------------------------------------------	
	
	public function edit_image() {}

	public function display_image() {}
	
	public function upload_file() {}
	
	
}