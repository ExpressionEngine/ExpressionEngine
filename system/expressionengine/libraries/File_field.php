<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
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
 * @category	File_field
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class File_field {
	
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Creates a file field
	 * 
	 * @param string $field_name The name of the field
	 * @param string $data The data stored in the file field 
	 * 		e.g. {filedir_x}filename.ext
	 * @param string $allowed_file_dirs The allowed file directory 
	 * 		Either 'all' or ONE directory ID
	 * @param string $content_type The content type allowed. 
	 * 		Either 'all' or 'image'
	 * @return string Fully rendered file field
	 */
	public function field($field_name, $data = '', $allowed_file_dirs = 'all', $content_type = 'all')
	{
		// Load necessary library, helper, model and langfile
		$this->EE->load->library('filemanager');
		$this->EE->load->helper(array('html', 'form'));
		$this->EE->load->model(array('file_model', 'file_upload_preferences_model'));
		$this->EE->lang->loadfile('fieldtypes');
		
		$vars = array(
			'filedir'				=> '',
			'filename'				=> '',
			'upload_location_id'	=> ''
		);
		$allowed_file_dirs = ($allowed_file_dirs == 'all') ? '' : $allowed_file_dirs;
		$specified_directory = ($allowed_file_dirs == '') ? 'all' : $allowed_file_dirs;
		
		// Parse field data
		if ( ! empty($data) AND ($parsed_field = $this->parse_field($data)) !== FALSE)
		{
			$vars = $parsed_field;
			$vars['filename'] = $vars['filename'].'.'.$vars['extension'];
		}
		
		// Retrieve all directories that are both allowed for this user and
		// for this field
		$upload_dirs[''] = lang('directory');
		$upload_dirs = $this->EE->file_upload_preferences_model->get_dropdown_array(
			$this->EE->session->userdata('group_id'),
			$allowed_file_dirs,
			$upload_dirs
		);
		
		// Get the thumbnail
		$thumb_info = $this->EE->filemanager->get_thumb($vars['filename'], $vars['upload_location_id']);
		$vars['thumb'] = img(array(
			'src' => $thumb_info['thumb'],
			'alt' => $vars['filename']
		));
		
		// Create the hidden fields for the file and directory
		$vars['hidden']	  = form_hidden($field_name.'_hidden', $vars['filename']);
		$vars['hidden']	 .= form_hidden($field_name.'_hidden_dir', $vars['upload_location_id']);
		
		// Create a standard file upload field and dropdown for folks 
		// without javascript
		$vars['upload'] = form_upload(array(
			'name'				=> $field_name,
			'value'				=> $vars['filename'],
			'data-content-type'	=> $content_type,
			'data-directory'	=> $specified_directory
		));
		$vars['dropdown'] = form_dropdown($field_name.'_directory', $upload_dirs, $vars['upload_location_id']);

		// Check to see if they have access to any directories to create an upload link
		$vars['upload_link'] = (count($upload_dirs) > 0) ? '<a href="#" class="choose_file" data-directory="'.$specified_directory.'">'.lang('add_file').'</a>' : lang('directory_no_access');

		// If we have a file, show the thumbnail, filename and remove link
		$vars['set_class'] = $vars['filename'] ? '' : 'js_hide';

		return $this->EE->load->ee_view('_shared/file/field', $vars, TRUE);
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
	public function browser($config = array(), $endpoint_url = 'C=content_publish&M=filemanager_actions')
	{
		$this->EE->lang->loadfile('content');

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

		$this->_browser_css();
		$this->_browser_javascript($endpoint_url);
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Validate's the data by checking to see if they used the normal file 
	 * field or the file browser
	 * 
	 * USE THIS BEFORE format_data()
	 * 
	 * @param string $data The data in the field we're validating
	 * @param string $field_name The name of the field we're validating
	 * @param string $required Set to 'y' if the field is required
	 * @return array Associative array containing ONLY the name of the 
	 * 		file uploaded
	 */
	public function validate($data, $field_name, $required = 'n')
	{
		$this->EE->load->model('file_upload_preferences_model');
		
		$dir_field		= $field_name.'_directory';
		$hidden_field	= $field_name.'_hidden';
		$hidden_dir		= ($this->EE->input->post($field_name.'_hidden_dir')) ? $this->EE->input->post($field_name.'_hidden_dir') : '';
		$allowed_dirs	= array();
		
		// Default to blank - allows us to remove files
		$_POST[$field_name] = '';
		
		// Default directory
		$upload_directories = $this->EE->file_upload_preferences_model->get_file_upload_preferences($this->EE->session->userdata('group_id'));
		
		// Directory selected - switch
		$filedir = ($this->EE->input->post($dir_field)) ? $this->EE->input->post($dir_field) : '';
		
		foreach($upload_directories as $row)
		{
			$allowed_dirs[] = $row['id'];
		}
		
		// Upload or maybe just a path in the hidden field?
		if (isset($_FILES[$field_name]) && $_FILES[$field_name]['size'] > 0 AND in_array($filedir, $allowed_dirs))
		{
			$this->EE->load->library('filemanager');
			$data = $this->EE->filemanager->upload_file($filedir, $field_name);
			
			if (array_key_exists('error', $data))
			{
				return $data['error'];
			}
			else
			{
				$_POST[$field_name] = $data['file_name'];
			}
		}
		elseif ($this->EE->input->post($hidden_field))
		{
			$_POST[$field_name] = $_POST[$hidden_field];
		}
		
		$_POST[$dir_field] = $filedir;
		
		unset($_POST[$hidden_field]);
		
		// If the current file directory is not one the user has access to
		// make sure it is an edit and value hasn't changed
		
		if ($_POST[$field_name] && ! in_array($filedir, $allowed_dirs))
		{
			if ($filedir != '' OR ( ! $this->EE->input->post('entry_id') OR $this->EE->input->post('entry_id') == ''))
			{
				return lang('directory_no_access');
			}
			
			// The existing directory couldn't be selected because they didn't have permission to upload
			// Let's make sure that the existing file in that directory is the one that's going back in
			
			$eid = (int) $this->EE->input->post('entry_id');
			
			$this->EE->db->select($field_name);
			$query = $this->EE->db->get_where('channel_data', array('entry_id'=>$eid));	

			if ($query->num_rows() == 0)
			{
				return lang('directory_no_access');
			}
			
			if ('{filedir_'.$hidden_dir.'}'.$_POST[$field_name] != $query->row($field_name))
			{
				return lang('directory_no_access');
			}
			
			// Replace the empty directory with the existing directory
			$_POST[$field_name.'_directory'] = $hidden_dir;
		}
		
		if ($required == 'y' && ! $_POST[$field_name])
		{
			return lang('required');
		}
		
		unset($_POST[$field_name.'_hidden_dir']);
		return array('value' => $_POST[$field_name]);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Format's the data of a file field given the name of the file and 
	 * the directory_id
	 * 
	 * @param string $data The name of the file
	 * @param integer $directory_id The directory ID
	 * @return string The formatted field data e.g. {filedir_1}file.ext
	 */
	public function format_data($file_name, $directory_id = 0)
	{
		if ($file_name != '')
		{
			if ( ! empty($directory_id))
			{
			     return '{filedir_'.$directory_id.'}'.$file_name;
			}

			return $file_name;
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Parse field contents, which may be in the {filedir_n} format for may be
	 * a file ID.
	 *
	 * @access	public
	 * @param	string $data Field contents
	 * @return	array|boolean Information about file and upload directory, false 
	 * 		if there is no file
	 */
	public function parse_field($data)
	{
		$this->EE->load->model('file_model');

		$file_dirs = $this->_file_dirs();
		
		// If the file field is in the "{filedir_n}image.jpg" format
		if (preg_match('/^{filedir_(\d+)}/', $data, $matches))
		{
			// Only replace it once
			$path = substr($data, 0, 10 + strlen($matches[1]));
			
			// Set upload directory ID and file name
			$dir_id = $matches[1];
			$file_name = str_replace($matches[0], '', $data);
			
			$file = $this->EE->file_model->get_files_by_name($file_name, $dir_id)->row_array();
		}
		// If file field is just a file ID
		else if (! empty($data) && is_numeric($data))
		{
			// Query file model on file ID
			$file = $this->EE->file_model->get_files_by_id($data)->row_array();
		}

		// If there is no file, get out of here
		if (empty($file))
		{
			return FALSE;
		}

		// Set additional data based on what we've gathered
		$file['path'] 		= (isset($file_dirs[$file['upload_location_id']])) ? $file_dirs[$file['upload_location_id']] : '';
		$file['extension'] 	= substr(strrchr($file['file_name'], '.'), 1);
		$file['filename'] 	= basename($file['file_name'], '.'.$file['extension']); // backwards compatibility
		$file['url'] 		= $file['path'].$file['file_name'];

		$dimensions = explode(" ", $file['file_hw_original']);

		$file['width'] 	= isset($dimensions[1]) ? $dimensions[1] : '';
		$file['height'] = isset($dimensions[0]) ? $dimensions[0] : '';

		// Make the URLs of any manipulated versions available via e.g. {url:small}
		$manipulations = $this->EE->file_model->get_dimensions_by_dir_id($file['upload_location_id'])->result_array();

		foreach($manipulations as $m)
		{
			$file['url:'.$m['short_name']] = $file['path'].'_'.$m['short_name'].'/'.$file['file_name'];

		}

		return $file;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Unlike parse(), this parses all occurances of {filedir_n} from a given
	 * string to their actual values and returns the processed string.
	 *
	 * @access	public
	 * @param	string $data The string to parse {filedir_n} in
	 * @return	string The original string with all {filedir_n}'s parsed
	 */	
	public function parse_string($data)
	{
		// Find each instance of {filedir_n}
		if (preg_match_all('/{filedir_(\d+)}/', $data, $matches, PREG_SET_ORDER))
		{
			$file_dirs = $this->_file_dirs();
			
			// Replace each match
			foreach ($matches as $match)
			{
				if (isset($file_dirs[$match[1]]))
				{
					$data = str_replace('{filedir_'.$match[1].'}', $file_dirs[$match[1]], $data);
				}
			}
		}
		
		return $data;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Get the file directory data and keep it stored in the cache
	 * 
	 * @return array Array of file directories
	 */
	private function _file_dirs()
	{
		if ( ! $this->EE->session->cache(__CLASS__, 'file_dirs'))
		{
			$this->EE->session->set_cache(
				__CLASS__,
				'file_dirs',
				$this->EE->functions->fetch_file_paths()
			);
		}
		
		return $this->EE->session->cache(__CLASS__, 'file_dirs');
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Add the file browser CSS to the head
	 */
	private function _browser_css()
	{
		$this->EE->cp->add_to_head($this->EE->view->head_link('css/file_browser.css'));
	}

	// ------------------------------------------------------------------------

	/**
	 * Loads up javascript dependencies and global variables for the file 
	 * browser and file uploader
	 */
	private function _browser_javascript($endpoint_url)
	{
		$this->EE->cp->add_js_script('plugin', array('tmpl', 'ee_table'));
		
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
				'resize_image'		=> lang('resize_image'),
				'or'				=> lang('or'),
				'return_to_publish'	=> lang('return_to_publish')
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

// END File_field class

/* End of file File_field.php */
/* Location: ./system/expressionengine/libraries/File_field.php */