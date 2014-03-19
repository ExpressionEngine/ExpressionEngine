<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
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

class Content_files_modal extends CP_Controller {

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

		$this->load->library('filemanager');
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
		$this->load->view('_shared/file_upload/index', $this->_vars_index());
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

		// Handles situation where the file attempted to upload exceeds the max upload size so much
		// that it removes all headers in $_POST and $_FILES; we need to handle this before anything
		// else because everything below depends on $_POST
		if (empty($_POST)
			AND empty($_FILES)
			AND $this->input->server('REQUEST_METHOD') == 'POST'
			AND $this->input->server('CONTENT_LENGTH') > 0)
		{
			$this->lang->loadfile('upload');
			$vars = $this->_vars_index();
			$vars['error'] = lang('upload_file_exceeds_limit');

			return $this->load->view('_shared/file_upload/index', $vars);
		}

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
			$vars = $this->_vars_index();
			$vars['error'] = $upload_response['error'];

			return $this->load->view('_shared/file_upload/index', $vars);
		}

		// Check to see if the file needs to be renamed
		// It needs to be renamed if the current name differs from
		// the original AFTER clean_filename and upload library's prep done
		// but before duplicate checking

		$file					= $this->_get_file($upload_response['file_id']);
		$file['modified_date']	= $this->localize->human_time($file['modified_date']);
		$original_name			= $upload_response['orig_name'];
		$cleaned_name			= basename($this->filemanager->clean_filename(
			$original_name,
			$file_dir
		));

		if ($file['file_name'] != $original_name
			AND $file['file_name'] != $cleaned_name)
		{
			// At this point, orig_name contains the extension
			$vars = $this->_vars_rename($file, $original_name);
			return $this->load->view('_shared/file_upload/rename', $vars);
 		}

		$vars = $this->_vars_success($file);
		return $this->load->view('_shared/file_upload/success', $vars);
	}

	// ------------------------------------------------------------------------

	/**
	 * Attempts to rename the file, goes back to rename if it couldn't, sends
	 * the user to success if everything went to plan.
	 *
	 * Called via content_files::upload_file if the file already exists
	 */
	public function update_file()
	{
		$new_file_name = basename($this->filemanager->clean_filename(
			$this->input->post('new_file_name').'.'.$this->input->post('file_extension'),
			$this->input->post('directory_id')
		));

		$new_file_base = substr($new_file_name, 0, -strlen('.'.$this->input->post('file_extension')));

		$temp_filename = basename($this->filemanager->clean_filename(
			$this->input->post('original_name'),
			$this->input->post('directory_id')
		));

		// Attempt to replace the file
		$rename_file = $this->filemanager->rename_file(
			$this->input->post('file_id'),
			$new_file_name,
			$temp_filename
		);

		// Get the file data of the renamed file
		$file = $this->_get_file($rename_file['file_id']);

		// Humanize Unix timestamp
		$file['modified_date']	= $this->localize->human_time($file['modified_date']);

		// Views need to know if the file was replaced
		$file['replace'] = $rename_file['replace'];

		// If renaming the file was unsuccessful try again
		if ($rename_file['success'] === FALSE && $rename_file['error'] == 'retry')
		{
			// At this point, original_name no longer contains the file extension
			// so we need to add it for build_rename_vars
			$vars = $this->_vars_rename(
				$file,
				$this->input->post('original_name')
			);
			return $this->load->view('_shared/file_upload/rename', $vars);
		}

		// If the file was successfully replaced send them to the success page
		if ($rename_file['success'] === TRUE)
		{
			$vars = $this->_vars_success($file);
			return $this->load->view('_shared/file_upload/success', $vars);
		}
		// If it's a different type of error, show it
		else
		{
			return $this->load->view('_shared/file_upload/rename', $rename_file['error']);
		}
	}

	// ------------------------------------------------------------------------

	public function edit_file()
	{
		// Retrieve the file ID
		$file_id = $this->input->get_post('file_id');

		// Attempt to save the file
		$this->_save_file();

		// Retrieve the (possibly updated) file data
		$vars['file'] = $this->_get_file($file_id);
		$vars['file_json'] = json_encode($vars['file']);

		// Create array of hidden inputs
		$vars['hidden'] = array(
			'file_id'		=> $file_id,
			'file_name'		=> $vars['file']['file_name'],
			'upload_dir'	=> $vars['file']['upload_location_id']
		);

		// List out the tabs
		$vars['tabs'] = array('file_metadata');

		// Add image tools if we're dealing with an image
		if ($vars['file']['is_image'])
		{
			$this->javascript->set_global(array(
				'filemanager'	=> array(
					'image_height'				=> $vars['file']['dimensions'][0],
					'image_width'				=> $vars['file']['dimensions'][1],
					'resize_over_confirmation' 	=> lang('resize_over_confirmation')
				),
			));

			array_push($vars['tabs'], 'image_tools');
		}

		// Create a list of metadata fields
		$vars['metadata_fields'] = array(
			'title' 		=> form_input('title', $vars['file']['title']),
			'description' 	=> form_textarea(array(
				'name'	=> 'description',
				'value'	=> $vars['file']['description'],
				'rows'	=> 3
			)),
			'credit'		=> form_input('credit', $vars['file']['credit']),
			'location'		=> form_input('location', $vars['file']['location'])
		);

		// Load javascript libraries
		$this->cp->add_js_script(array(
			'plugin'	=> 'ee_resize_scale',
			'file'		=> 'files/edit_file'
		));

		$this->javascript->compile();
		$this->cp->render('_shared/file_upload/edit', $vars);
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
	private function _vars_index()
	{
		$selected_directory_id = ($this->input->get_post('directory_id')) ? $this->input->get_post('directory_id') : '';
		$directory_override = (in_array($this->input->get_post('restrict_directory'), array('true', 1))) ? $selected_directory_id : '';
		$restrict_image = (in_array($this->input->get_post('restrict_image'), array('true', 1))) ? TRUE : FALSE;

		return array(
			'upload_directories' => $this->file_upload_preferences_model->get_dropdown_array(
				$this->session->userdata('group_id'),
				$directory_override
			),
			'hidden_vars' => array(
				'restrict_image'		=> $restrict_image,
				'directory_id'			=> $this->input->get_post('directory_id'),
				'restrict_directory'	=> $this->input->get_post('restrict_directory')
			),
			'selected_directory_id' => $selected_directory_id
		);
	}


	// ------------------------------------------------------------------------

	/**
	 * Creates an associative array the be passed as the variables for the
	 * rename view
	 *
	 * @param array $file The associative array of the file, comes from _get_file
	 * @param string $original_name The original name of the file that was uploaded
	 * @return array Associative array containing file_json, file_extension,
	 * 		original_name and an array of hidden variables
	 */
	private function _vars_rename($file, $original_name)
	{
		// Check to see if they want to increment or replace
		$original_name = ($this->config->item('filename_increment') == 'y') ? $file['file_name'] : $original_name;

		// Explode the original name so we have something to work with if they
		// need to rename the file later
		$original_name	= explode('.' , $original_name);
		$file_extension	= array_pop($original_name);
		$original_name	= implode('.', $original_name);

		return array(
			'file_json'			=> json_encode($file),
			'file_extension'	=> $file_extension,
			'original_name'		=> $original_name,
			'hidden' => array(
				'file_id'			=> $file['file_id'],
				'directory_id'		=> $file['upload_location_id'],
				'file_extension'	=> $file_extension,
				'original_name'		=> $original_name.'.'.$file_extension
			)
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Creates an associative array for the success view
	 *
	 * @param array $file The associative array of the file, comes from _get_file
	 * @return array Associative array containing file, file_id and file_json
	 */
	private function _vars_success($file)
	{
		// Success only needs file, file_id, and file_json
		return array(
			'file'		=> $file,
			'file_id'	=> $file['file_id'],
			'file_json'	=> json_encode($file)
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Retrieves the file and sets up various data we need for the file uploader
	 *
	 * @param integer $file_id The ID of the file
	 * @return array Associative array of the file
	 */
	private function _get_file($file_id)
	{
		$file = $this->file_model->get_files_by_id($file_id)->row_array();

		// Set is_image
		$file['is_image'] = $this->filemanager->is_image($file['mime_type']);

		// Get thumbnail
		$thumb_info = $this->filemanager->get_thumb(
			$file['file_name'],
			$file['upload_location_id']
		);
		$file['thumb'] = $thumb_info['thumb'];

		// Copying file_name to name for addons
		$file['name'] = $file['file_name'];

		// Add dimensions if we're dealing with an image
		if ($file['is_image'])
		{
			$file['dimensions']	= explode(' ', $file['file_hw_original']);
		}

		// Change file size to human readable
		$this->load->helper('number');
		$file['file_size'] = byte_format($file['file_size']);

		// Blend in the upload directory preferences
		$file['upload_directory_prefs'] = $this->file_upload_preferences_model->get_file_upload_preferences(
			$this->session->userdata('group_id'),
			$file['upload_location_id']
		);

		return $file;
	}

	// ------------------------------------------------------------------------

	/**
	 * Saves the file if we've submitted the edit form and validation passes
	 */
	private function _save_file()
	{
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div class="notice">', '</div>');
		$this->form_validation->set_rules('title', 'lang:title', 'trim|required');

		// Save the file if title has been posted (success form doesn't have
		// title, so it wouldn't even bother saving data)
		if ($this->input->post('title') !== FALSE AND $this->form_validation->run())
		{
			$updated_data = array(
				'file_id'		=> $this->input->post('file_id'),
				'description' 	=> $this->input->post('description', ''),
				'credit'		=> $this->input->post('credit', ''),
				'location'		=> $this->input->post('location', '')
			);

			// Only add title if it's not blank
			if (($title = $this->input->post('title', '')) != '')
			{
				$updated_data['title'] = $this->input->post('title');
			}

			$this->file_model->save_file($updated_data);


			// Check and see if we actually need to do image processing. Height
			// or width needs to be different from the default or rotate needs
			// to be set.
			$actions = array();

			if ($this->input->post('resize_height_default') !== $this->input->post('resize_height')
				OR $this->input->post('resize_width_default') !== $this->input->post('resize_width'))
			{
				// Resize MUST come first, the original values only make sense
				// in the context of resize first
				$actions[] = 'resize';
			}

			if ($this->input->post('rotate') !== FALSE)
			{
				$actions[] = 'rotate';
			}

			if (count($actions))
			{
				$_POST['action'] = $actions;
				$this->filemanager->_do_image_processing(FALSE);
			}
		}
	}
}
/* End File: content_files_modal.php */
/* File Location: system/expressionengine/controllers/cp/content_files_modal.php */

