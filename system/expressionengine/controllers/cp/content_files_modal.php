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
		if ( ! $this->cp->allowed_group('can_access_content'))
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

		// Clear out the preloaded core javacript files
		$this->cp->requests = array();
		$this->cp->loaded = array();
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Shows the inner upload iframe, handles getting that view the data it needs
	 */
	public function index()
	{
		$vars = $this->_get_index_vars();
		$this->load->view('_shared/file_upload/index', $vars);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Retrieves variables used on the index page of the modal since they are
	 * used on failure as well as initial load
	 * 
	 * @return array Associative array containing the upload directory dropdown
	 * 		array, hidden variables for the form, and the ID of the selected
	 * 		directory
	 */
	private function _get_index_vars()
	{
		$selected_directory_id = ($this->input->get_post('directory_id')) ? $this->input->get_post('directory_id') : '';
		$directory_override = ($this->input->get_post('restrict_directory') == 'true') ? $selected_directory_id : '';
		$restrict_image = ($this->input->get_post('restrict_image') == 'true') ? TRUE : FALSE;
		
		return array(
			'upload_directories' => $this->file_upload_preferences_model->get_dropdown_array(
				$this->session->userdata('group_id'), 
				$directory_override
			),
			'hidden_vars' => array(
				'restrict_image' => $restrict_image,
				'directory_id' => $this->input->get_post('directory_id'),
				'restrict_directory' => $this->input->get_post('restrict_directory')
			),
			'selected_directory_id' => $selected_directory_id
		);
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

		$restrict_image = ($this->input->post('restrict_image')) ? TRUE : FALSE;

		// Both uploads the file and adds it to the database
		$upload_response = $this->filemanager->upload_file($file_dir, FALSE, $restrict_image);
		
		// Any errors from the Filemanager?
		if (isset($upload_response['error']))
		{
			$vars = $this->_get_index_vars();
			$vars['error'] = $upload_response['error'];
			
			return $this->load->view('_shared/file_upload/index', $vars);
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
		// It needs to be renamed if the file names are different and the 
		// length of the names are different too because clean_filename
		// replaces spaces with underscores
		// For example "file name.jpg" changes to "file_name.jpg" they're 
		// different strings, but the same length
		
		if (
			strlen($upload_response['file_name']) != strlen($upload_response['orig_name']) AND
			$upload_response['file_name'] != $upload_response['orig_name']
		)
		{
			$vars['temp_filename'] = $upload_response['orig_name'];
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
			$this->input->post('new_file_name').'.'.$this->input->post('file_ext'),
			$this->input->post('directory_id')
		));
		
		$new_file_base = substr($new_file_name, 0, -strlen('.'.$this->input->post('file_ext')));
		
		$temp_filename = basename($this->filemanager->clean_filename(
			$this->input->post('temp_filename'),
			$this->input->post('directory_id')
		));
		
		// Attempt to replace the file
		$rename_file = $this->filemanager->rename_file(
			$this->input->post('file_id'),
			$new_file_name,
			$temp_filename
		);
		
		if ($rename_file['success'] === FALSE && $rename_file['error'] == 'retry')
		{
			$file['file_id'] = $rename_file['file_id'];
			$file['upload_location_id'] = $this->input->post('directory_id');
				
			$vars = array('file_json' => $this->input->post('file_json'),
						'file_ext' => $this->input->post('file_ext'),
						'temp_filename' => $rename_file['replace_filename'],
						'orig_name' => $new_file_base,
						'file' => array('file_id' => $rename_file['file_id'], 'upload_location_id' => $this->input->post('directory_id'))
						);

			return $this->load->view('_shared/file_upload/rename', $vars);
		}

		// Get file data from JSON
		$vars = $this->_get_file_from_json(array(
			'new_file_name' => $new_file_name,
			'rename_file_response' => $rename_file
		));
		
		// If the file was successfully replaced send them to the success page
		if ($rename_file['success'] === TRUE)
		{
			return $this->load->view('_shared/file_upload/success', $vars);
		}
		// If it's a different type of error, show it
		else
		{
			
			return $this->load->view('_shared/file_upload/rename', $rename_file['error']);
		}
	}
	
	// ------------------------------------------------------------------------
	
	public function edit_image()
	{
		$parameters = array();
		
		// The form posts to this method, so if POST data is present
		// send to _do_image_processing to, well, do the image processing
		if ( ! empty($_POST['action']))
		{
			$response = $this->filemanager->_do_image_processing(FALSE);
			$parameters['dimensions'] = $response['dimensions'];
		}
		
		$vars = $this->_get_file_from_json($parameters);
		
		$vars['file_data'] = array(
			'upload_dir'	=> $vars['file']['upload_location_id'], 
			'file'			=> $vars['file']['file_name'],
			'file_name'		=> $vars['file']['file_name'],
			'file_id'		=> $vars['file']['file_id']
		);
		
		// This isn't in the file_data variable because of a bug that
		// wouldn't properly encode the smae json object paseed twice
		// to form_open()
		
		$vars['file_json_input'] = form_hidden('file_json', $vars['file_json']);
		
		$this->javascript->set_global(array(
			'filemanager'	=> array(
				'image_width'				=> $vars['file']['file_width'],
				'image_height'				=> $vars['file']['file_height'],
				'resize_over_confirmation' 	=> lang('resize_over_confirmation')
			),
		));
		
		// Load javascript libraries
		$this->cp->add_js_script(array(
			'file'		=> 'cp/files/file_manager_edit',
			'ui'		=> array('core', 'widget', 'accordion')
		));	

		// Yup, more accordions
		$this->javascript->output('$(".edit_controls").accordion({autoHeight: false, header: "h3"});');
		
		$this->javascript->compile();
		$this->load->view('_shared/file_upload/edit', $vars);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Get's file information based on the file json object
	 * 
	 * @param array $parameters Associative array containing three optional parameters:
	 * 	- new_file_name: if the file is being renamed, we need to get the
	 * 		thumbnail, so we need the new file name
	 * 	- rename_file_response: if the file is being renamed, we also need the
	 * 		response from the file_rename method
	 * 	- dimensions: an associative array from Filemanager::_do_image_processing
	 * 		for when you are resizing an image
	 */
	private function _get_file_from_json($parameters = array())
	{
		if ( ! function_exists('json_decode'))
		{
			$this->load->library('Services_json');
		}
		
		// Get the JSON and decode it
		$file_json = $this->input->post('file_json');
		$file = (array) json_decode($file_json);
		$file['upload_directory_prefs'] = (array) $file['upload_directory_prefs'];
		
		// If the file is being renamed, use the new file name and responze
		// from rename_file to update the data
		if (isset($parameters['new_file_name']) AND isset($parameters['rename_file_response']))
		{
			// Replace the filename and thumb (everything else should have stayed the same)
			$thumb_info = $this->filemanager->get_thumb(
				$parameters['new_file_name'], 
				$file['upload_location_id']
			);

			$file['file_id']	= $parameters['rename_file_response']['file_id'];
			$file['file_name'] 	= $parameters['new_file_name'];
			$file['name'] 		= $parameters['new_file_name'];
			$file['thumb'] 		= $thumb_info['thumb'];
			$file['replace']	= $parameters['rename_file_response']['replace'];
		}
		
		// If dimensions are passed in, update the height and width
		if (isset($parameters['dimensions']))
		{
			$file['file_height'] = $parameters['dimensions']['height'];
			$file['file_width'] = $parameters['dimensions']['width'];
			$file['file_hw_original'] = $parameters['dimensions']['height'] . ' ' . $parameters['dimensions']['width'];
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

