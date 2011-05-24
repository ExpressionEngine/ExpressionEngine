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

class Content_files_modal extends CI_Controller {

	private $_upload_dirs    = array();
	private $_base_url       = '';

	public function __construct()
	{
		parent::__construct();

		// Permissions
		if ( ! $this->cp->allowed_group('can_access_content', 'can_access_files'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library(array('filemanager'));
		$this->load->helper(array('form'));
		$this->load->model(array('file_model', 'file_upload_preferences_model'));

		// Get upload dirs
		$upload_dirs = $this->filemanager->fetch_upload_dirs();

		foreach ($upload_dirs as $row)
		{
			$this->_upload_dirs[$row['id']] = $row;
		}

		// Turn off the profiler, everything is in a modal
		$this->output->enable_profiler(FALSE);

		$this->_base_url = BASE.AMP.'C=content_files_modal';
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Shows the inner upload iframe, handles getting that view the data it needs
	 */
	public function index()
	{
		$selected_directory_id = ($this->input->get('directory_id')) ? $this->input->get('directory_id') : '';
		
		$vars = array(
			'upload_directories' => $this->file_upload_preferences_model->get_dropdown_array($this->session->userdata('group_id')),
			'selected_directory_id' => $selected_directory_id
		);
		
		$this->load->view('_shared/file_upload/index', $vars);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Upload file
	 * 
	 * This method does a few things, but it's main goal is to facilitate working
	 * with Filemanager to both upload and add the file to exp_files.
	 *
	 * 	1. Verifies that you have access to upload
	 *		- Is this being accessed through a form?
	 *		- Was a upload directory specified?
	 *		- Does the user have access to the directory?
	 *	2. Next, it calls Filemanager's upload_file
	 *		- That uploads the file and adds it to the database
	 *	3. Then it generates a response based upon Filemanager's response:
	 *		- If there's an error, that's shown
	 *		- If there's an existing file with the same name, they have the option to rename
	 *		- If everything went according to plan, a success message is shown
	 *
	 * @return mixed View file based upon Filemanager's response: success, failure or rename
	 */
	public function upload_file()
	{
		$this->output->enable_profiler(FALSE);

		// Make sure this is a valid form submit
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

		// Both uploads the file and adds it to the database
		$upload_response = $this->filemanager->upload_file($file_dir);
		
		// Any errors from the Filemanager?
		if (isset($upload_response['error']))
		{
			$vars = array(
				'error'	=> $upload_response['error']
			);
			
			return $this->load->view('_shared/file_upload/failure', $vars);
		}
		
		// Copying file_name to name and file_thumb to thumb for addons
		$upload_response['name'] = $upload_response['file_name'];
		$upload_response['thumb'] = $upload_response['file_thumb'];
		
		// Check to see if they want to increment or replace
		$file_name_source =  ($this->config->item('filename_increment') == 'y') ? 'file_name' : 'orig_name';
		
		$orig_name = explode('.' , $upload_response[$file_name_source]);
		$vars = array(
			'file'		=> $upload_response,
			'file_json'	=> $this->javascript->generate_json($upload_response, TRUE),
			'file_ext'	=> array_pop($orig_name),
			'orig_name'	=> implode('.', $orig_name),
			'date'		=> date('M d Y - H:ia')
		);
		
		// Check to see if the file needs to be renamed
		if ($upload_response['file_name'] != $upload_response['orig_name'])
		{
			return $this->load->view('_shared/file_upload/rename', $vars);
 		}
		
		return $this->load->view('_shared/file_upload/success', $vars);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Attempts to rename the file, goes back to rename if it couldn't, sends
	 * the user to succes if everything went to plan.
	 * 
	 * Called via content_files::upload_file if the file already exists
	 */
	public function update_file()
	{
		$new_file_name = basename($this->filemanager->clean_filename(
			$this->input->post('new_file_name') . '.' . $this->input->post('file_ext'),
			$this->input->post('directory_id')
		));
		
		// Attempt to replace the file
		$rename_file = $this->filemanager->rename_file(
			$this->input->post('file_id'),
			$new_file_name
		);
		
		// Get file data from JSON
		$file_json = $this->input->post('file_json');
		$vars = $this->_get_file_from_json($file_json, $new_file_name, $rename_file);
		
		// If the file was successfully replaced send them to the success page
		if ($rename_file['success'] === TRUE)
		{
			return $this->load->view('_shared/file_upload/success', $vars);
		}
		// If it's a different type of error, show it
		else
		{
			return $this->load->view('_shared/file_upload/failure', $rename_file['error']);
		}
	}
	
	// ------------------------------------------------------------------------
	
	public function edit_image()
	{
		$vars = $this->_get_file_from_json($this->input->post('file_json'));
		
		// Clear out the preloaded core javacript files
		$this->cp->requests = array();
		$this->cp->loaded = array();
		
		// Load a few back in
		
		// 'effect'	=> 'core',
		// 'ui'		=> array('core', 'widget', 'mouse', 'position', 'sortable', 'dialog'),
		// 'plugin'	=> array('ee_focus', 'ee_notice', 'ee_txtarea', 'tablesorter'),
		// 'file'		=> 'cp/global'
		
		$this->cp->add_js_script(array(
			'file'		=> 'cp/files/file_manager_edit',
			'ui'		=> array('core', 'widget', 'accordion')
		));
		
		$this->javascript->set_global(array(
			'filemanager'	=> array(
				'image_width'				=> $vars['file']['file_width'],
				'image_height'				=> $vars['file']['file_height'],
				'resize_over_confirmation' 	=> lang('resize_over_confirmation')
			),
		));

		$this->javascript->output('$(".edit_controls").accordion({autoHeight: false, header: "h3"});');
		
		$this->javascript->compile();
		
		$this->load->view('_shared/file_upload/edit', $vars);
	}
	
	// ------------------------------------------------------------------------
	
	private function _get_file_from_json($file_json, $new_file_name = '', $rename_file_response = array())
	{
		if ( ! function_exists('json_decode'))
		{
			$this->load->library('Services_json');
		}
		
		// Get the JSON and decode it
		$file = get_object_vars(json_decode($file_json));
		$file['upload_directory_prefs'] = get_object_vars($file['upload_directory_prefs']);
		
		// If the file is being replaced, use the new file name and responze
		// from rename_file to update the data
		if ($new_file_name != '' AND count($rename_file_response) > 0)
		{
			// Replace the filename and thumb (everything else should have stayed the same)
			$thumb_info = $this->filemanager->get_thumb($new_file_name, $file['upload_location_id']);

			$file['file_id']	= $rename_file_response['file_id'];
			$file['file_name'] 	= $new_file_name;
			$file['name'] 		= $new_file_name;
			$file['thumb'] 		= $thumb_info['thumb'];
			$file['replace']	= $rename_file_response['replace'];
		}
		
		// Prep the vars for the success and failure pages
		return array(
			'file'		=> $file,
			'file_json'	=> $this->javascript->generate_json($file, TRUE),
			'date'		=> date('M d Y - H:ia')
		);
	}	
}
/* End File: content_files_modal.php */
/* File Location: system/expressionengine/controllers/cp/content_files_modal.php */

