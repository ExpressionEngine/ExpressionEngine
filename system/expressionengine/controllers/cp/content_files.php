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
		
		// Get upload dirs
		$this->_upload_dirs = $this->filemanager->fetch_upload_dirs();
		$per_page = ($per_page = $this->input->get('per_page')) ? $per_page : 10;
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
		// var_dump($this->_upload_dirs[$selected_dir]);
		// Setup file list
		foreach ($files->files[$selected_dir] as $file)
		{
			if ( ! $file['mime'])
			{
				continue;
			}
			
			$file_list[] = array(
				'name'		=> $file['name'],
				'link'		=> '',
				'mime'		=> $file['mime'],
				'size'		=> $file['size'],
				'date'		=> $file['date'],
			);
			
			$dir_size = $dir_size + $file['size'];
		}
		
		$total_rows = count($file_list);
		
		$file_list = array_slice($file_list, $offset, $per_page);
		
		$base_url = BASE.AMP.'C=content_files'.AMP.'per_page='.$per_page;
		
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
			'download'			=> $this->lang->line('download_selected'),
			'delete'			=> $this->lang->line('delete_selected_files')
		);
		
		// Figure out where the count is starting and ending for the dialog at the bottom of the page
		$offset = ($this->input->get($p_config['query_string_segment'])) ? $this->input->get($p_config['query_string_segment']) : 0;
		$count_from = $offset + 1;
		$count_to = $offset + count($file_list);
		
		$data = array(
			'upload_dirs_options' 	=> $upload_dirs_options,
			'selected_dir'			=> $selected_dir,
			'files'					=> $file_list,
			'dir_size'				=> $dir_size,
			'pagination_links'		=> $this->pagination->create_links(),
			'action_options' 		=> $action_options, 
			'total_files' 			=> $total_rows,
			'count_from'			=> $count_from,
			'count_to'				=> $count_to
		);
		
		$this->load->view('content/files/index', $data);
	}

	// ------------------------------------------------------------------------
		
	public function delete_files() {}
	
	public function download_files() {}
	
	public function edit_image() {}

	public function display_image() {}
	
	public function upload_file() {}
	
	
}