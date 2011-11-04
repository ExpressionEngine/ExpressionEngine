<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.4
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine File_browser Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	File_browser
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class File_browser {
	
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Initialize the file browser given a configuration array and an endpoint url
	 * @param array $config Associative array containing five different keys and values:
	 * 		- publish: set to TRUE if you're on the publish page, optionally
	 * 			just pass an empty array or none at all for the same behavior
	 * 		- trigger: the selector to pass to jQuery to create a trigger for
	 * 			the file browser
	 * 		- field_name: the field you're operating on. If undefined, it will
	 * 			assume the name is userfile
	 * 		- settings: JSON object defining the content type and directory
	 * 			e.g. {"content_type": "all/image", "directory": "all/<directory_id>"}
	 * 		- callback: Javascript function that will be called when an image
	 * 			is selected. e.g. function (file, field) { console.log(file, field); }
	 * 			file is an object of the selected file's data, and field is a
	 * 			jQuery object representing the field from the field_name given
	 * @param string $endpoint_url The URL the file browser will hit
	 */
	public function init($config = array(), $endpoint_url = 'C=content_publish&M=filemanager_actions')
	{
		// Are we on the publish page? If so, go ahead and load up the publish
		// page javascript files
		if (empty($config) OR (isset($config['publish']) AND $config['publish'] === TRUE))
		{
			$this->EE->javascript->set_global(array(
				'filebrowser' => array(
					'publish' => TRUE
				)
			));
		}
		// No? Make sure we at least have a trigger and a callback
		elseif (isset($config['trigger'], $config['callback']))
		{
			$field_name = (isset($config['field_name'])) ? $config['field_name'].', ' : '';
			$settings = (isset($config['settings'])) ? $config['settings'].', ' : '';
			
			$this->EE->javascript->ready("
				$.ee_filebrowser.add_trigger('{$config['trigger']}', {$field_name}{$settings}{$config['callback']});
			");
		}
		else
		{
			return;
		}

		$this->_css();
		$this->_javascript($endpoint_url);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Creates a file field
	 * 
	 * @param string $data The data stored in the file field 
	 * 		e.g. {filedir_x}filename.ext
	 * @param string $field_name The name of the field
	 * @param string $allowed_file_dirs The allowed file directory 
	 * 		Either 'all' or ONE directory ID
	 * @param string $content_type The content type allowed. 
	 * 		Either 'all' or 'image'
	 * @return string Fully rendered file field
	 */
	public function field($data, $field_name, $allowed_file_dirs = 'all', $content_type = 'all')
	{
		// Load necessary library, helper, model and langfile
		$this->EE->load->library('filemanager');
		$this->EE->load->helper('html');
		$this->EE->load->model('file_upload_preferences_model');
		$this->EE->lang->loadfile('fieldtypes');
		
		$vars = array(
			'filedir'	=> '',
			'filename'	=> ''
		);
		$allowed_file_dirs = ($allowed_file_dirs == 'all') ? '' : $allowed_file_dirs;
		$specified_directory = ($allowed_file_dirs == '') ? 'all' : $allowed_file_dirs;

		// Figure out the directory and name of the file from the data 
		// (e.g. {filedir_1}filename.jpg)
		if (preg_match('/{filedir_([0-9]+)}/', $data, $matches))
		{
			$vars['filedir'] = $matches[1];
			$vars['filename'] = str_replace($matches[0], '', $data);
		}
		
		// Retrieve all directories that are both allowed for this user and
		// for this field
		$upload_directories = $this->EE->file_upload_preferences_model->get_upload_preferences(
			$this->EE->session->userdata('group_id'),
			$allowed_file_dirs
		);

		// Create the list of directories
		$upload_dirs = array('' => lang('directory'));
		foreach($upload_directories->result() as $row)
		{
			$upload_dirs[$row->id] = $row->name;
		}
		
		// Get the thumbnail
		$thumb_info = $this->EE->filemanager->get_thumb($vars['filename'], $vars['filedir']);
		$vars['thumb'] = img(array(
			'src' => $thumb_info['thumb'],
			'alt' => $vars['filename']
		));
		
		// Create the hidden fields for the file and directory
		$vars['hidden']	  = form_hidden($field_name.'_hidden', $vars['filename']);
		$vars['hidden']	 .= form_hidden($field_name.'_hidden_dir', $vars['filedir']);
		
		// Create a standard file upload field and dropdown for folks 
		// without javascript
		$vars['upload'] = form_upload(array(
			'name'				=> $field_name,
			'value'				=> $vars['filename'],
			'data-content-type'	=> $content_type,
			'data-directory'	=> $specified_directory
		));
		$vars['dropdown'] = form_dropdown($field_name.'_directory', $upload_dirs, $vars['filedir']);

		// Check to see if they have access to any directories to create an upload link
		$vars['upload_link'] = (count($upload_dirs) > 1) ? '<a href="#" class="choose_file" data-directory="'.$specified_directory.'">'.lang('add_file').'</a>' : lang('directory_no_access');

		// If we have a file, show the thumbnail, filename and remove link
		$vars['set_class'] = $vars['filename'] ? '' : 'js_hide';

		return $this->EE->load->ee_view('_shared/file/field', $vars, TRUE);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Add the file browser CSS to the head
	 */
	private function _css()
	{
		$this->EE->cp->add_to_head($this->EE->view->head_link('css/file_browser.css'));
	}

	// ------------------------------------------------------------------------

	/**
	 * Loads up javascript dependencies and global variables for the file 
	 * browser and file uploader
	 */
	private function _javascript($endpoint_url)
	{
		// Include dependencies
		$this->EE->cp->add_js_script(array(
			'file'		=> array(
				'underscore',
				'files/publish_fields'
			),
			'plugin'	=> array(
				'scrollable',
				'scrollable.navigator',
				'ee_filebrowser',
				'ee_fileuploader',
				'tmpl'
			)
		));
		
		$this->EE->load->helper('html');
		
		$this->EE->javascript->set_global(array(
			'lang' => array(
				'resize_image'		=> $this->EE->lang->line('resize_image'),
				'or'				=> $this->EE->lang->line('or'),
				'return_to_publish'	=> $this->EE->lang->line('return_to_publish')
			),
			'filebrowser' => array(
				'endpoint_url'		=> $endpoint_url,
				'window_title'		=> lang('file_manager'),
				'next'				=> anchor(
					'#', 
					img(
						$this->EE->cp->cp_theme_url . 'images/pagination_next_button.gif',
						array(
							'alt' => lang('next'),
							'width' => 13,
							'height' => 13
						)
					),
					array(
						'class' => 'next'
					)
				),
				'previous'			=> anchor(
					'#', 
					img(
						$this->EE->cp->cp_theme_url . 'images/pagination_prev_button.gif',
						array(
							'alt' => lang('previous'),
							'width' => 13,
							'height' => 13
						)
					),
					array(
						'class' => 'previous'
					)
				)
			),
			'fileuploader' => array(
				'window_title'		=> lang('file_upload'),
				'delete_url'		=> 'C=content_files&M=delete_files'
			)
		));
	}

}

// END File_browser class

/* End of file File_browser.php */
/* Location: ./system/expressionengine/libraries/File_browser.php */