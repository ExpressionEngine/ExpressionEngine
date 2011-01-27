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
	 * Controls the batch actions
	 *
	 * When submitted to, expects a GET/POST variable named action containing either download or delete
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
		$file_path = $this->_upload_dirs[$file_dir]['server_path'];
		
		if ( ! $files OR ! $file_path OR $file_path === "")
		{
			$this->session->set_flashdata('message_failure', lang('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		$delete_problem = FALSE;

		foreach($files as $file_name)
		{
			$file_name = urldecode($file_name);
			$file      = $file_path.$file_name;
			$thumb     = $file_path.'_thumbs'.DIRECTORY_SEPARATOR.'thumb_'.$file_name;
			
			if ( ! @unlink($file))
			{
				$delete_problem = TRUE;
			}
			
			// Delete thumb also
			if (file_exists($thumb))
			{
				@unlink($thumb);
			}
		}

		if ($delete_problem)
		{
			// something's up.
			$this->session->set_flashdata('message_failure', lang('delete_fail'));
		}
		else
		{
			$this->session->set_flashdata('message_success', lang('delete_success'));
		}

		$this->functions->redirect(BASE.AMP.'C=content_files');
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
		$file_path = $this->_upload_dirs[$file_dir]['server_path'];
		
		$files_count = count($files);
		
		if ( ! $files_count)
		{
			return; // move along
		}
		else if ($files_count === 1)
		{
			// no point in zipping for a single file... let's just send the file
			
			$this->load->helper('download');
			
			$data = file_get_contents($file_path.urldecode($files[0]));
			$name = urldecode($files[0]);
			force_download($name, $data);
		}
		else
		{
			// its an array of files, zip 'em all
			
			$this->load->library('zip');
			
			foreach ($files as $file)
			{
				$this->zip->read_file($file_path.urldecode($file));
			}
			
			$this->zip->download('downloaded_files.zip'); 
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
	
	public function edit_image() {}

	public function display_image() {}

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
				echo '<script type="text/javascript">parent.EE_uploads.'.$this->input->get('frame_id').' = '.$this->javascript->generate_json(array('error' => $this->upload->display_errors())).';</script>';
				exit;
			}
			
			$this->session->set_flashdata('message_failure', $fm->upload_errors);
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}
		
		// Make the thumbnail
		
		$thumb = $fm->create_thumb(
			array('server_path' => $fm->upload_data['file_path']), 
			array('name' => $fm->upload_data['file_name'])
		);
		
		if ( ! $thumb)
		{
			
		}
		
		if ($this->input->is_ajax_request())
		{
			$response = array(
				'success'		=> lang('upload_success'),
				'filename'		=> $fm->upload_data['file_name'],
				'filesize'		=> $fm->upload_data['file_size'],
				'filetype'		=> $fm->upload_data['file_type'],
				'date'			=> date('M d Y - H:ia')
			);

			exit('<script type="text/javascript">parent.EE_uploads.'.$this->input->get('frame_id').' = '.$this->javascript->generate_json($response).';</script>');
		}
		
		$this->session->set_flashdata('message_success', lang('upload_success'));
		$this->functions->redirect(BASE.AMP.'C=content_files');
				
	}
	
	
}