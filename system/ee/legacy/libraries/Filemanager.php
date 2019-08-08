<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Filemanager
 */
class Filemanager {

	var $config;
	var $theme_url;

	public $upload_errors		= FALSE;
	public $upload_data			= NULL;
	public $upload_warnings		= FALSE;

	private $_errors			= array();
	private $_upload_dirs		= array();
	private $_upload_dir_prefs	= array();

	private $_xss_on			= TRUE;
	private $_memory_tweak_factor = 1.8;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		ee()->load->library('javascript');
		ee()->lang->loadfile('filemanager');

		ee()->router->set_class('cp');
		ee()->load->library('cp');
		ee()->router->set_class('ee');
		$this->theme_url = ee()->cp->cp_theme_url;
	}

	function _set_error($error)
	{
		return;
	}

	/**
	 * Cleans the filename to prep it for the system, mostly removing spaces
	 * sanitizing the file name and checking for duplicates.
	 *
	 * @param string $filename The filename to clean the name of
	 * @param integer $dir_id The ID of the directory in which we'll check for duplicates
	 * @param array $parameters Associative array containing optional parameters
	 * 		'convert_spaces' (Default: TRUE) Setting this to FALSE will not remove spaces
	 * 		'ignore_dupes' (Default: TRUE) Setting this to FALSE will check for duplicates
	 *
	 * @return string Full path and filename of the file, use basepath() to just
	 * 		get the filename
	 */
	function clean_filename($filename, $dir_id, $parameters = array())
	{
		// at one time the third parameter was (bool) $dupe_check
		if ( ! is_array($parameters))
		{
			$parameters = array('ignore_dupes' => ! $parameters);
		}

		// Establish the default parameters
		$default_parameters = array(
			'convert_spaces' => TRUE,
			'ignore_dupes' => TRUE
		);

		// Get the actual set of parameters and go
		$parameters = array_merge($default_parameters, $parameters);

		$prefs = $this->fetch_upload_dir_prefs($dir_id);

		$i = 1;
		$ext = '';
		$path = $prefs['server_path'];

		// clean up the filename
		if ($parameters['convert_spaces'] === TRUE)
		{
			$filename = preg_replace("/\s+/", "_", $filename);
		}

		$filename = ee()->security->sanitize_filename($filename);

		if (strpos($filename, '.') !== FALSE)
		{
			$parts		= explode('.', $filename);
			$ext		= array_pop($parts);

			// @todo prevent security issues with multiple extensions
			// http://httpd.apache.org/docs/1.3/mod/mod_mime.html#multipleext
			$filename	= implode('.', $parts);
		}

		$ext = '.'.$ext;

		// Figure out a unique filename
		if ($parameters['ignore_dupes'] === FALSE)
		{
			$basename = $filename;

			while (file_exists($path.$filename.$ext))
			{
				$filename = $basename.'_'.$i++;
			}
		}

		return $path.$filename.$ext;
	}

	function set_upload_dir_prefs($dir_id, array $prefs)
	{
		$required = array_flip(
			array('name', 'server_path', 'url', 'allowed_types', 'max_height', 'max_width')
		);

		$defaults = array(
			'dimensions' => array()
		);

		// make sure all required keys are in there
		if (count(array_diff_key($required, $prefs)))
		{
			return FALSE;
		}

		// add defaults for optional fields
		foreach ($defaults as $key => $val)
		{
			if ( ! isset($prefs[$key]))
			{
				$prefs[$key] = $val;
			}
		}

		$prefs['max_height'] = ($prefs['max_height'] == '') ? 0 : $prefs['max_height'];
		$prefs['max_width'] = ($prefs['max_width'] == '') ? 0 : $prefs['max_width'];

		$this->_upload_dir_prefs[$dir_id] = $prefs;
		return $prefs;
	}

	/**
	 * Get the upload directory preferences for an individual directory
	 *
	 * @param integer $dir_id ID of the directory to get preferences for
	 * @param	bool $ignore_site_id If TRUE, returns upload destinations for all sites
	 */
	function fetch_upload_dir_prefs($dir_id, $ignore_site_id = FALSE)
	{
		if (isset($this->_upload_dir_prefs[$dir_id]))
		{
			return $this->_upload_dir_prefs[$dir_id];
		}

		$dir = ee('Model')->get('UploadDestination', $dir_id);

		if ( ! $ignore_site_id)
		{
			$dir->filter('site_id', ee()->config->item('site_id'));
		}

		if ($dir->count() < 1)
		{
			return FALSE;
		}

		$dir = $dir->first();
		$prefs = $dir->getValues();

		// Add dimensions to prefs
		$prefs['dimensions'] = array();

		foreach ($dir->FileDimensions as $dimension)
		{
			$data = array(
				'short_name'   => $dimension->short_name,
				'width'        => $dimension->width,
				'height'       => $dimension->height,
				'watermark_id' => $dimension->watermark_id,
				'resize_type'  => $dimension->resize_type,
				'quality'      => $dimension->quality
			);

			// Add watermarking prefs
			if ($dimension->Watermark)
			{
				$data = array_merge($data, $dimension->Watermark->getValues());
			}

			$prefs['dimensions'][$dimension->getId()] = $data;
		}

		// check keys and cache
		return $this->set_upload_dir_prefs($dir_id, $prefs);
	}

	/**
	 * Checks the uploaded file to make sure it's both allowed and passes
	 *	XSS filtering
	 *
	 * @param	string	$file_path	The path to the file
	 * @param	array	$prefs		File preferences containing allowed_types
	 * @return	mixed	Returns the mime type if everything passes, FALSE otherwise
	 */
	function security_check($file_path, $prefs)
	{
		ee()->load->helper(array('file', 'xss'));
		ee()->load->library('mime_type');

		$is_image = FALSE;
		$allowed = $prefs['allowed_types'];
		$mime = ee()->mime_type->ofFile($file_path);

		if ($allowed == 'all' OR $allowed == '*')
		{
			if (ee()->mime_type->isSafeForUpload($mime))
			{
				return $mime;
			}
			else
			{
				return FALSE;
			}
		}

		if ($allowed == 'img')
		{
			if ( ! ee()->mime_type->isImage($mime))
			{
				return FALSE;
			}

			$is_image = TRUE;
		}

		// We need to be able to turn this off!

		//Apply XSS Filtering to uploaded files?
		if ($this->_xss_on AND
			xss_check() AND
			! ee('Security/XSS')->clean($file_path, $is_image))
		{
			return FALSE;
		}

		return $mime;
	}

	/**
	 * Turn XSS cleaning on
	 */
	public function xss_clean_on()
	{
		$this->_xss_on = TRUE;
	}

	public function xss_clean_off()
	{
		$this->_xss_on = FALSE;
	}

	/**
	 * Checks to see if the image is an editable/resizble image
	 *
	 * @param	string	$file_path	The full path to the file to check
	 * @param	string	$mime		The file's mimetype
	 * @return	boolean	TRUE if the image is editable, FALSE otherwise
	 */
	function is_editable_image($file_path, $mime)
	{
		if ( ! $this->is_image($mime))
		{
			return FALSE;
		}

		if (function_exists('getimagesize'))
		{
			if (FALSE === @getimagesize($file_path))
			{
				return FALSE;
			}
		}

		return TRUE;
	}


	/**
	 * Gets Image Height and Width
	 *
	 * @param	string	$file_path	The full path to the file to check
	 * @return	mixed	False if function not available, associative array otherwise
	 */
	function get_image_dimensions($file_path)
	{
		if (function_exists('getimagesize'))
		{
			$D = @getimagesize($file_path);

			$image_size = array(
				'height'	=> $D['1'],
				'width'	=> $D['0']
				);

			return $image_size;
		}

		return FALSE;
	}


	/**
	 * Save File
	 *
	 * @access	public
	 * @param	boolean	$check_permissions	Whether to check permissions or not
	 */
	function save_file($file_path, $dir_id, $prefs = array(), $check_permissions = TRUE)
	{
		if ( ! $file_path OR ! $dir_id)
		{
			return $this->_save_file_response(FALSE, lang('no_path_or_dir'));
		}

		if ($check_permissions === TRUE AND ! $this->_check_permissions($dir_id))
		{
			// This person does not have access, error?
			return $this->_save_file_response(FALSE, lang('no_permission'));
		}

		// fetch preferences & merge with passed in prefs
		$dir_prefs = $this->fetch_upload_dir_prefs($dir_id, TRUE);

		if ( ! $dir_prefs)
		{
			// something went way wrong!
			return $this->_save_file_response(FALSE, lang('invalid_directory'));
		}

		$prefs['upload_location_id'] = $dir_id;

		$prefs = array_merge($prefs, $dir_prefs);

		if ( ! isset($prefs['dimensions']))
		{
			$prefs['dimensions'] = array();
		}

		// Figure out the mime type
		$mime = $this->security_check($file_path, $prefs);

		if ($mime === FALSE)
		{
			// security check failed
			return $this->_save_file_response(FALSE, lang('security_failure'));
		}

		$prefs['mime_type'] = $mime;

		// Check to see if its an editable image, if it is, try and create the thumbnail
		if ($this->is_editable_image($file_path, $mime))
		{
			// Check to see if we have GD and can resize images
			if ( ! (extension_loaded('gd') && function_exists('gd_info')))
			{
				return $this->_save_file_response(FALSE, lang('gd_not_installed'));
			}

			// Check and fix orientation
			$orientation = $this->orientation_check($file_path, $prefs);

			if ( ! empty($orientation))
			{
				$prefs = $orientation;
			}

			$prefs = $this->max_hw_check($file_path, $prefs);

			if ( ! $prefs)
			{
				return $this->_save_file_response(FALSE, lang('image_exceeds_max_size'));
			}

			if ( ! $this->create_thumb($file_path, $prefs))
			{
				return $this->_save_file_response(FALSE, lang('thumb_not_created'));
			}
		}

		// Insert the file metadata into the database
		ee()->load->model('file_model');

		if ($file_id = ee()->file_model->save_file($prefs))
		{
			$response = $this->_save_file_response(TRUE, $file_id);
		}
		else
		{
			$response = $this->_save_file_response(FALSE, lang('file_not_added_to_db'));
		}

		$this->_xss_on = TRUE;

		return $response;
	}

	/**
	 * Reorient main image if exif info indicates we should
	 *
	 * @access	public
	 * @return	void
	 */
	function orientation_check($file_path, $prefs)
	{
		if ( ! function_exists('exif_read_data'))
		{
			return;
		}

		// Not all images are supported
		$exif = @exif_read_data($file_path);

		if ( ! $exif OR ! isset($exif['Orientation']))
		{
			return;
		}

		$orientation = $exif['Orientation'];

		if ($orientation == 1)
		{
			return;
		}

		// Image is rotated, let's see by how much
		$deg = 0;

		switch ($orientation) {
			case 3:
				$deg = 180;
				break;
			case 6:
				$deg = 270;
				break;
			case 8:
				$deg = 90;
				break;
		}

		if ($deg)
		{
			ee()->load->library('image_lib');

			ee()->image_lib->clear();

			// Set required memory
			try
			{
				ee('Memory')->setMemoryForImageManipulation($file_path);
			}
			catch (\Exception $e)
			{
				log_message('error', $e->getMessage().': '.$file_path);
				return;
			}

			$config = array(
				'rotation_angle'	=> $deg,
				'library_path'		=> ee()->config->item('image_library_path'),
				'image_library'		=> ee()->config->item('image_resize_protocol'),
				'source_image'		=> $file_path
			);

			ee()->image_lib->initialize($config);

			if ( ! ee()->image_lib->rotate())
			{
				return;
			}

			$new_image = ee()->image_lib->get_image_properties('', TRUE);
			ee()->image_lib->clear();

			// We need to reset some prefs
			if ($new_image)
			{
				ee()->load->helper('number');
				$f_size =  get_file_info($file_path);
				$prefs['file_height'] = $new_image['height'];
				$prefs['file_width'] = $new_image['width'];
				$prefs['file_hw_original'] = $new_image['height'].' '.$new_image['width'];
				$prefs['height'] = $new_image['height'];
				$prefs['width'] = $new_image['width'];
			}

			return $prefs;
		}
	}

	/**
	 * Resizes main image if it exceeds max heightxwidth- adds metadata to file_data array
	 *
	 * @access	public
	 * @return	void
	 */
	function max_hw_check($file_path, $prefs)
	{
		$force_master_dim = FALSE;

		// Make sure height and width are set
		if ( ! isset($prefs['height']) OR ! isset($prefs['width']))
		{
			$dim = $this->get_image_dimensions($file_path);

			if ($dim == FALSE)
			{
				return FALSE;
			}

			$prefs['height'] = $dim['height'];
			$prefs['width'] = $dim['width'];
			$prefs['file_height'] = $prefs['height'];
			$prefs['file_width'] = $prefs['width'];
		}

		if ($prefs['max_width'] == 0 && $prefs['max_height'] == 0)
		{
			return $prefs;
		}


		$config['width']			= $prefs['max_width'];
		$config['height']			= $prefs['max_height'];

		ee()->load->library('image_lib');

		ee()->image_lib->clear();

		// If either h/w unspecified, calculate the other here
		if ($prefs['max_width'] ==  0)
		{
			$config['width'] = ($prefs['width']/$prefs['height'])*$prefs['max_height'];
			$force_master_dim = 'height';
		}
		elseif ($prefs['max_height'] ==  0)
		{
			// Old h/old w * new width
			$config['height'] = ($prefs['height']/$prefs['width'])*$prefs['max_width'];
			$force_master_dim = 'width';
		}

		// If the original is smaller than the thumb hxw, we'll make a copy rather than upsize
		if (($force_master_dim == 'height' && $prefs['height'] <= $prefs['max_height']) OR
				($force_master_dim == 'width' && $prefs['width'] <= $prefs['max_width']) OR
				($force_master_dim == FALSE && $prefs['width'] <= $prefs['max_width']) OR
				($force_master_dim == FALSE && $prefs['height'] <= $prefs['max_height']))
		{
			return $prefs;
		}


		unset($prefs['width']);
		unset($prefs['height']);

		// Set required memory
		try
		{
			ee('Memory')->setMemoryForImageManipulation($file_path);
		}
		catch (\Exception $e)
		{
			log_message('error', $e->getMessage().': '.$file_path);
			return FALSE;
		}

		// Resize

		$config['source_image']		= $file_path;
		$config['maintain_ratio']	= TRUE;
		$config['image_library']	= ee()->config->item('image_resize_protocol');
		$config['library_path']		= ee()->config->item('image_library_path');

		ee()->image_lib->initialize($config);

		if ( ! ee()->image_lib->resize())
		{
			return FALSE;
		}

		$new_image = ee()->image_lib->get_image_properties('', TRUE);

		// We need to reset some prefs
		if ($new_image)
		{
			ee()->load->helper('number');
			$f_size =  get_file_info($file_path);

			$prefs['file_size'] = ($f_size) ? $f_size['size'] : 0;

			$prefs['file_height'] = $new_image['height'];
			$prefs['file_width'] = $new_image['width'];
			$prefs['file_hw_original'] = $new_image['height'].' '.$new_image['width'];
			$prefs['height'] = $new_image['height'];
			$prefs['width'] = $new_image['width'];
		}

		return $prefs;
	}


	/**
	 * Checks the permissions of the current user and directory
	 * Returns TRUE if they have access FALSE otherwise
	 *
	 * @access	private
	 * @param	int|string	$dir_id		Directory to check permissions on
	 * @return	boolean		TRUE if current user has access, FALSE otherwise
	 */
	private function _check_permissions($dir_id)
	{
		$group_id = ee()->session->userdata('group_id');

		// Non admins need to have their permissions checked
		if ($group_id != 1)
		{
			// non admins need to first be checked for restrictions
			// we'll add these into a where_not_in() check below
			ee()->db->select('upload_id');
			ee()->db->where(array(
				'member_group' => $group_id,
				'upload_id'    => $dir_id
			));

			// If any record shows up, then they do not have access
			if (ee()->db->count_all_results('upload_no_access') > 0)
			{

				return FALSE;
			}
		}

		return TRUE;
	}


	/**
	 * Send save_file response
	 *
	 * @param	boolean		$status		TRUE if save_file passed, FALSE otherwise
	 * @param	string		$message	Message to send
	 * @return	array		Associative array containing the status and message/file_id
	 */
	private function _save_file_response($status, $message = '')
	{
		$key = '';

		if ($status === TRUE)
		{
			$key = 'file_id';
		}
		else
		{
			$key = 'message';
		}

		return array(
			'status'	=> $status,
			$key		=> $message
		);
	}

	/**
	 * Process Request
	 *
	 * Main Backend Handler
	 *
	 * @access	public
	 * @param	mixed	configuration options
	 * @return	void
	 */
	function process_request($config = array())
	{
		$this->_initialize($config);

		$type = ee()->input->get('action');

		switch($type)
		{
			case 'setup':
				$this->setup();
				break;
			case 'setup_upload':
				$this->setup_upload();
				break;
			case 'directory':
				$this->directory(ee()->input->get('directory'), TRUE);
				break;
			case 'directories':
				$this->directories(TRUE);
				break;
			case 'directory_contents':
				$this->directory_contents();
				break;
			case 'directory_info':
				$this->directory_info();
				break;
			case 'file_info':
				$this->file_info();
				break;
			case 'upload':
				$this->upload_file(ee()->input->get_post('upload_dir'), FALSE, TRUE);
				break;
			case 'edit_image':
				$this->edit_image();
				break;
			case 'ajax_create_thumb':
				$this->ajax_create_thumb();
				break;
			default:
				exit('Invalid Request');
		}
	}

	/**
	 * Initialize
	 *
	 * @access	private
	 * @param	mixed	configuration options
	 * @return	void
	 */
	function _initialize($config)
	{
		// Callbacks!
		foreach(array('directories', 'directory_contents', 'directory_info', 'file_info', 'upload_file') as $key)
		{
			$this->config[$key.'_callback'] = isset($config[$key.'_callback']) ? $config[$key.'_callback'] : array($this, '_'.$key);
		}

		unset($config);
	}

	/**
	 * Setup
	 *
	 * The real filebrowser bootstrapping function. Generates the required html.
	 *
	 * @access	private
	 * @param	mixed	configuration options
	 * @return	void
	 */
	function setup()
	{
		// Make sure there are directories
		$dirs = $this->directories(FALSE, TRUE);
		if (empty($dirs))
		{
			return ee()->output->send_ajax_response(array(
				'error' => lang('no_upload_dirs')
			));
		}

		if (REQ != 'CP')
		{
			ee()->load->helper('form');
			$action_id = '';

			ee()->db->select('action_id');
			ee()->db->where('class', 'Channel');
			ee()->db->where('method', 'filemanager_endpoint');
			$query = ee()->db->get('actions');

			if ($query->num_rows() > 0)
			{
				$row = $query->row();
				$action_id = $row->action_id;
			}

			$vars['filemanager_backend_url'] = str_replace('&amp;', '&', ee()->functions->fetch_site_index(0, 0).QUERY_MARKER).'ACT='.$action_id;
		}
		else
		{
			$vars['filemanager_backend_url'] = ee()->cp->get_safe_refresh();
		}

		unset($_GET['action']);	// current url == get_safe_refresh()

		$vars['filemanager_directories'] = $this->directories(FALSE);

		// Generate the filters
		// $vars['selected_filters'] = form_dropdown('selected', array('all' => lang('all'), 'selected' => lang('selected'), 'unselected' => lang('unselected')), 'all');
		// $vars['category_filters'] = form_dropdown('category', array());
		$vars['view_filters'] = form_dropdown(
			'view_type',
			array(
				'list' => lang('list'),
				'thumb' => lang('thumbnails')
			),
			'list', 'id="view_type"'
		);

		$data = $this->datatables(key($vars['filemanager_directories']));
		$vars = array_merge($vars, $data);

		$filebrowser_html = ee()->load->ee_view('_shared/file/browser', $vars, TRUE);

		ee()->output->send_ajax_response(array(
			'manager'		=> str_replace(array("\n", "\t"), '', $filebrowser_html),	// reduces transfer size
			'directories'	=> $vars['filemanager_directories']
		));
	}

	public function datatables($first_dir = NULL)
	{
		ee()->load->model('file_model');

		// Argh
		ee()->set('_mcp_reference', $this);

		ee()->load->library('table');
		// @todo put .AMP. back ...
		ee()->table->set_base_url('C=content_publish&M=filemanager_actions&action=directory_contents');
		ee()->table->set_columns(array(
			'file_name' => array('header' => lang('name')),
			'file_size'	=> array('header' => lang('size')),
			'mime_type'	=> array('header' => lang('kind')),
			'date'		=> array('header' => lang('date'))
		));

		$per_page	= ee()->input->get_post('per_page');
		$dir_id 	= ee()->input->get_post('dir_choice');
		$keywords 	= ee()->input->get_post('keywords');
		$tbl_sort	= ee()->input->get_post('tbl_sort');

		// Default to file_name sorting if tbl_sort isn't set
		$state = (is_array($tbl_sort)) ? $tbl_sort : array('sort' => array('file_name' => 'asc'));

		$params = array(
			'per_page'	=> $per_page ? $per_page : 15,
			'dir_id'	=> $dir_id,
			'keywords'	=> $keywords
		);

		if ($first_dir)
		{
			// @todo rename
			ee()->table->force_initial_load();

			$params['dir_id'] = $first_dir;
		}

		$data = ee()->table->datasource('_file_datasource', $state, $params);

		// End Argh
		ee()->remove('_mcp_reference');

		return $data;
	}

	public function _file_datasource($state, $params)
	{
		$per_page = $params['per_page'];

		$dirs = $this->directories(FALSE, TRUE);
		$dir = $dirs[$params['dir_id']];

		// Check to see if we're sorting on date, if so, change the key to sort on
		if (isset($state['sort']['date']))
		{
			$state['sort']['modified_date'] = $state['sort']['date'];
			unset($state['sort']['date']);
		}

		$file_params = array(
			'type'		=> $dir['allowed_types'],
			'order'		=> $state['sort'],
			'limit'		=> $per_page,
			'offset'	=> $state['offset']
		);

		if (isset($params['keywords']))
		{
			$file_params['search_value']	= $params['keywords'];
			$file_params['search_in']		= 'all';
		}

		// Mask the URL if we're coming from the CP
		$sync_files_url = (REQ == "CP") ?
			ee()->cp->masked_url(DOC_URL.'cp/files/uploads/sync.html') :
			DOC_URL.'cp/files/uploads/sync.html';

		return array(
			'rows'			=> $this->_browser_get_files($dir, $file_params),
			'no_results'	=> sprintf(
				lang('no_uploaded_files'),
				$sync_files_url,
				BASE.AMP.'C=content_files'.AMP.'M=file_upload_preferences'
			),
			'pagination' 	=> array(
				'per_page' 		=> $per_page,
				'total_rows'	=> ee()->file_model->count_files($params['dir_id'])
			)
		);
	}

	public function setup_upload()
	{
		$base = (defined('BASE')) ? BASE : ee()->functions->fetch_site_index(0,0).QUERY_MARKER;

		$vars = array(
			'base_url'	=> $base.AMP.'C=content_files_modal'
		);

		ee()->output->send_ajax_response(array(
			'uploader'	=> ee()->load->ee_view('_shared/file_upload/upload_modal', $vars, TRUE)
		));
	}

	/**
	 * Directory
	 *
	 * Get information for a single directory
	 *
	 * @access	public
	 * @param	int		directory id
	 * @param	bool	ajax request (optional)
	 * @param	bool	return all info (optional)
	 * @return	mixed	directory information
	 */
	function directory($dir_id, $ajax = FALSE, $return_all = FALSE, $ignore_site_id = FALSE)
	{
		$return_all = ($ajax) ? FALSE : $return_all;		// safety - ajax calls can never get all info!

		$dirs = $this->directories(FALSE, $return_all, $ignore_site_id);

		$return = isset($dirs[$dir_id]) ? $dirs[$dir_id] : FALSE;

		if ($ajax)
		{
			die(json_encode($return));
		}

		return $return;
	}

	/**
	 * Directories
	 *
	 * Get all directory information
	 *
	 * @access	public
	 * @param	bool	ajax request (optional)
	 * @param	bool	return all info (optional)
	 * @return	mixed	directory information
	 */
	function directories($ajax = FALSE, $return_all = FALSE, $ignore_site_id = FALSE)
	{
		static $dirs;
		$return = array();

		if ($ajax === FALSE)
		{
			$this->_initialize($this->config);
		}

		if ( ! is_array($dirs))
		{
			$dirs = call_user_func($this->config['directories_callback'], array('ignore_site_id' => $ignore_site_id));
		}

		if ($return_all AND ! $ajax)	// safety - ajax calls can never get all info!
		{
			$return = $dirs;
		}
		else
		{
			foreach($dirs as $dir_id => $info)
			{
				$return[$dir_id] = $info['name'];
			}
		}

		if ($ajax)
		{
			ee()->output->send_ajax_response($return);
		}

		return $return;
	}

	/**
	 * Directory Contents
	 *
	 * Get all files in a directory
	 *
	 * @access	public
	 * @return	mixed	directory information
	 */
	function directory_contents()
	{
		$this->datatables();

		$dir_id	= ee()->input->get('directory_id');
		$dir	= $this->directory($dir_id, FALSE, TRUE);

		$offset	= ee()->input->get('offset');
		$limit	= ee()->input->get('limit');

		$data = $dir ? call_user_func($this->config['directory_contents_callback'], $dir, $limit, $offset) : array();

		if (count($data) == 0)
		{
			echo '{}';
		}
		else
		{
			$data['files'] = $this->find_thumbs($dir, $data['files']);

			foreach ($data['files'] as &$file)
			{
				unset($file['encrypted_path']);
			}

			$data['id'] = $dir_id;
			echo json_encode($data);
		}
		exit;
	}

	/**
	 * Get the quantities for both files and images within a directory
	 */
	function directory_info()
	{
		$dir_id = ee()->input->get('directory_id');
		$dir = $this->directory($dir_id, FALSE, TRUE);

		$data = $dir ? call_user_func($this->config['directory_info_callback'], $dir) : array();

		if (count($data) == 0)
		{
			echo '{}';
		}
		else
		{
			$data['id'] = $dir_id;
			echo json_encode($data);
		}
		exit;
	}

	/**
	 * Get the file information for an individual file (by ID)
	 */
	function file_info()
	{
		$file_id = ee()->input->get('file_id');

		$data = $file_id ? call_user_func($this->config['file_info_callback'], $file_id) : array();

		if (count($data) == 0)
		{
			echo '{}';
		}
		else
		{
			echo json_encode($data);
		}
		exit;
	}

	/**
	 * Upload File
	 *
	 * Upload a files
	 *
	 * @access	public
	 * @param	int		$dir_id		Upload Directory ID
	 * @param	string	$field		Upload Field Name (optional - defaults to first upload field)
	 * @param 	boolean $image_only	Override to restrict uploads to images
	 * @return	mixed	uploaded file info
	 */
	function upload_file($dir_id = '', $field = FALSE, $image_only = FALSE)
	{
		// Fetches all info and is site_id independent
		$dir = $this->directory($dir_id, FALSE, TRUE, TRUE);

		// TODO: Check $image_only value to verify it's correct and then clarify
		// with Kevin

		// Override the allowed types of the dir if we're restricting to images
		if ($image_only)
		{
			$dir['allowed_types'] = 'img';
		}

		$data = array('error' => 'No File');

		if ( ! $dir)
		{
			$data = array('error' => "You do not have access to this upload directory.");
		}
		else if (count($_FILES) > 0)
		{
			// If the field isn't set, default to first upload field
			if ( ! $field && is_array(current($_FILES)))
			{
				$field = key($_FILES);
			}

			// If we actually found the image, go ahead and send it to the
			// callback, most likely _upload_file
			if (isset($_FILES[$field]))
			{
				$data = call_user_func($this->config['upload_file_callback'], $dir, $field);
			}
		}

		return $data;
	}

	/**
	 * Set Image Memory for Image Resizing
	 *
	 * Deprecated in 4.1.0
	 *
	 * @see EllisLab\ExpressionEngine\Service\Memory\Memory::setMemoryForImageManipulation()
	 */
	function set_image_memory($filename)
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('4.1.0', "ee('Memory')->setMemoryForImageManipulation()");

		try
		{
			ee('Memory')->setMemoryForImageManipulation($filename);
			return TRUE;
		}
		catch (\Exception $e)
		{
			// legacy behavior, error display and logging is handled by the caller
			return FALSE;
		}
	}

	/**
	 * Create Thumbnails
	 *
	 * Create Thumbnails for a file
	 *
	 * @access	public
	 * @param	string	file path
	 * @param	array	file and directory information
	 * @param	bool	Whether or not to create a thumbnail; will do so
	 *		regardless of missing_only setting because directory syncing
	 *		needs to update thumbnails even if no image manipulations are
	 *		updated.
	 * @param	bool	Whether or not to replace missing image
	 *		manipulations only (TRUE) or replace them all (FALSE).
	 * @return	bool	success / failure
	 */
	function create_thumb($file_path, $prefs, $thumb = TRUE, $missing_only = TRUE)
	{
		ee()->load->library('image_lib');
		ee()->load->library('mime_type');
		ee()->load->helper('file');

		$img_path = rtrim($prefs['server_path'], '/').'/';
		$source = $file_path;

		if ( ! isset($prefs['mime_type']))
		{
			// Figure out the mime type
			$prefs['mime_type'] = ee()->mime_type->ofFile($file_path);
		}

		if ( ! $this->is_editable_image($file_path, $prefs['mime_type']))
		{
			return FALSE;
		}

		// Make sure we have enough memory to process
		try
		{
			ee('Memory')->setMemoryForImageManipulation($file_path);
		}
		catch (\Exception $e)
		{
			log_message('error', $e->getMessage().': '.$file_path);
			return FALSE;
		}

		$dimensions = $prefs['dimensions'];

		if ($thumb)
		{
			$dimensions[] = array(
				'short_name'	=> 'thumbs',
				'width'			=> 73,
				'height'		=> 60,
				'quality'       => 90,
				'watermark_id'	=> 0,
				'resize_type'	=> 'crop'
			);
		}

		$protocol = ee()->config->item('image_resize_protocol');
		$lib_path = ee()->config->item('image_library_path');

		// Make sure height and width are set
		if ( ! isset($prefs['height']) OR ! isset($prefs['width']))
		{
			$dim = $this->get_image_dimensions($file_path);

			if ($dim == FALSE)
			{
				return FALSE;
			}

			$prefs['height'] = $dim['height'];
			$prefs['width'] = $dim['width'];
		}

		foreach ($dimensions as $size_id => $size)
		{
			// May be FileDimension object
			if ( ! is_array($size))
			{
				$size = $size->toArray();
			}

			ee()->image_lib->clear();
			$force_master_dim = FALSE;

			$resized_path = $img_path.'_'.$size['short_name'].'/';

			if ( ! is_dir($resized_path))
			{
				mkdir($resized_path);

				if ( ! file_exists($resized_path.'index.html'))
				{
					$f = fopen($resized_path.'index.html', FOPEN_READ_WRITE_CREATE_DESTRUCTIVE);
					fwrite($f, 'Directory access is forbidden.');
					fclose($f);
				}
			}
			elseif ( ! is_really_writable($resized_path))
			{
				return FALSE;
			}

			$resized_dir = rtrim(realpath($resized_path), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

			// Does the thumb image exist
			if (file_exists($resized_path.$prefs['file_name']))
			{
				// Only skip images that are custom image manipulations and when missing_only
				// has been set to TRUE, but always make sure we update normal thumbnails
				if (($missing_only AND $size['short_name'] != 'thumbs') OR
					($size['short_name'] == 'thumbs' AND $thumb == FALSE))
				{
					continue;
				}

				// Delete the image to make way for a new one
				@unlink($resized_path.$prefs['file_name']);
			}

			// If the size doesn't have a valid height and width, skip resize
			if ($size['width'] <= 0 && $size['height'] <= 0)
			{
				$size['resize_type'] = 'none';
			}

			// If either h/w unspecified, calculate the other here
			if ($size['width'] == '' OR $size['width'] == 0)
			{
				$size['width'] = ($prefs['width']/$prefs['height'])*$size['height'];
				$force_master_dim = 'height';
			}
			elseif ($size['height'] == '' OR $size['height'] == 0)
			{
				// Old h/old w * new width
				$size['height'] = ($prefs['height']/$prefs['width'])*$size['width'];
				$force_master_dim = 'width';
			}

			// Resize
			$config['source_image']		= $source;
			$config['new_image']		= $resized_path.$prefs['file_name'];
			$config['maintain_ratio']	= TRUE;
			$config['image_library']	= $protocol;
			$config['library_path']		= $lib_path;
			$config['width']			= $size['width'];
			$config['height']			= $size['height'];
			$config['quality']          = $size['quality'];

			// If the original is smaller than the thumb hxw, we'll make a copy rather than upsize
			if (($force_master_dim == 'height' && $prefs['height'] < $size['height']) OR
				($force_master_dim == 'width' && $prefs['width'] < $size['width']) OR
				($force_master_dim == FALSE &&
					($prefs['width'] < $size['width'] && $prefs['height'] < $size['height'])
				) OR
				$size['resize_type'] == 'none')
			{
				copy($config['source_image'],$config['new_image']);
			}
			elseif (isset($size['resize_type']) AND $size['resize_type'] == 'crop')
			{
				// Scale the larger dimension up so only one dimension of our
				// image fits within the desired dimension
				if ($prefs['width'] > $prefs['height'])
				{
					$config['width'] = round($prefs['width'] * $size['height'] / $prefs['height']);

					// If the new width ends up being smaller than the
					// resized width
					if ($config['width'] < $size['width'])
					{
						$config['width'] = $size['width'];
						$config['master_dim'] = 'width';
					}
				}
				elseif ($prefs['height'] > $prefs['width'])
				{
					$config['height'] = round($prefs['height'] * $size['width'] / $prefs['width']);

					// If the new height ends up being smaller than the
					// desired resized height
					if ($config['height'] < $size['height'])
					{
						$config['height'] = $size['height'];
						$config['master_dim'] = 'height';
					}
				}
				// If we're dealing with a perfect square image
				elseif ($prefs['height'] == $prefs['width'])
				{
					// And the desired image is landscape, edit the
					// square image's width to fit
					if ($size['width'] > $size['height'] ||
						$size['width'] == $size['height'])
					{
						$config['width'] = $size['width'];
						$config['master_dim'] = 'width';
					}
					// If the desired image is portrait, edit the
					// square image's height to fit
					elseif ($size['width'] < $size['height'])
					{
						$config['height'] = $size['height'];
						$config['master_dim'] = 'height';
					}
				}

				// First resize down to smallest possible size (greater of height and width)
				ee()->image_lib->initialize($config);

				if ( ! ee()->image_lib->resize())
				{
					return FALSE;
				}

				// Next set crop accordingly
				$resized_image_dimensions = $this->get_image_dimensions($resized_path.$prefs['file_name']);
				$config['source_image'] = $resized_path.$prefs['file_name'];
				$config['x_axis'] = (($resized_image_dimensions['width'] / 2) - ($size['width'] / 2));
				$config['y_axis'] = (($resized_image_dimensions['height'] / 2) - ($size['height'] / 2));
				$config['maintain_ratio'] = FALSE;

				// Change height and width back to the desired size
				$config['width'] = $size['width'];
				$config['height'] = $size['height'];

				ee()->image_lib->initialize($config);

				if ( ! @ee()->image_lib->crop())
				{
					return FALSE;
				}
			}
			else
			{
				$config['master_dim'] = $force_master_dim;

				ee()->image_lib->initialize($config);

				if ( ! ee()->image_lib->resize())
				{
					return FALSE;
				}
			}

			@chmod($config['new_image'], FILE_WRITE_MODE);

			// Does the thumb require watermark?
			if ($size['watermark_id'] != 0)
			{
				if ( ! $this->create_watermark($resized_path.$prefs['file_name'], $size))
				{
					log_message('error', 'Image Watermarking Failed: '.$prefs['file_name']);
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	/**
	 * Create Watermark
	 *
	 * Create a Watermarked Image
	 *
	 * @access	public
	 * @param	string	full path to image
	 * @param	array	file information
	 * @return	bool	success / failure
	 */
	function create_watermark($image_path, $data)
	{
		ee()->image_lib->clear();

		$config = $this->set_image_config($data, 'watermark');
		$config['source_image'] = $image_path;

		ee()->image_lib->initialize($config);

		// watermark it!

		if ( ! ee()->image_lib->watermark())
		{
			return FALSE;
		}

		ee()->image_lib->clear();

		return TRUE;
	}


	/**
	 * Ajax Create Thumbnail
	 *
	 * Create a Thumbnail for a file
	 *
	 * @access	public
	 * @param	mixed	directory information
	 * @param	mixed	file information
	 * @return	bool	success / failure
	 */
	function ajax_create_thumb()
	{
		$data = array('name' => ee()->input->get_post('image'));
		$dir = $this->directory(ee()->input->get_post('dir'), FALSE, TRUE);

		if ( ! $this->create_thumb($dir, $data))
		{
			header('HTTP', true, 500); // Force ajax error
			exit;
		}
		else
		{
			// Worked, let's return the thumb path
			echo rtrim($dir['server_path'], '/').'/'.'_thumbs/'.'thumb_'.$data['name'];
		}
	}

	/**
	 * Get's the thumbnail for a particular image in a directory
	 * This assumes the thumbnail has already been created
	 *
	 * @param array $file Response from save_file, should be an associative array
	 * 	and minimally needs to contain the file_name and the mime_type/file_type
	 * 	Optionally, you can use the file name in the event you don't have the
	 * 	full response from save_file
	 * @param integer $directory_id The ID of the upload directory the file is in
	 * @param	bool $ignore_site_id If TRUE, returns upload destinations for all sites
	 * @return string URL to the thumbnail
	 */
	public function get_thumb($file, $directory_id, $ignore_site_id = FALSE)
	{
		$thumb_info = array(
			'thumb' => PATH_CP_GBL_IMG.'missing.jpg',
			'thumb_path' => '',
			'thumb_class' => 'no_image',
		);

		if (empty($file))
		{
			return $thumb_info;
		}

		$directory = $this->fetch_upload_dir_prefs($directory_id, $ignore_site_id);

		// If the raw file name was passed in, figure out the mime_type
		if ( ! is_array($file) OR ! isset($file['mime_type']))
		{
			ee()->load->helper('file');
			ee()->load->library('mime_type');

			$file = array(
				'file_name' => $file,
				'mime_type' => ee()->mime_type->ofFile($directory['server_path'] . $file)
			);
		}

		// If it's an image, use it's thumbnail, otherwise use the default
		if ($this->is_image($file['mime_type']))
		{
			$site_url = str_replace('index.php', '', ee()->config->site_url());

			$thumb_info['thumb'] = $directory['url'].'_thumbs/'.$file['file_name'];
			$thumb_info['thumb_path'] = $directory['server_path'] . '_thumbs/' . $file['file_name'];
			$thumb_info['thumb_class'] = 'image';
		}

		return $thumb_info;
	}

	/**
	 * Finds Thumbnails
	 *
	 * Creates a list of available thumbnails based on the supplied information
	 *
	 * @access	public
	 * @param	mixed	directory information
	 * @param	mixed	list of files
	 * @return	mixed	list of files with added 'has_thumb' boolean key
	 */
	function find_thumbs($dir, $files)
	{
		$thumb_path = rtrim($dir['server_path'], '/').'/_thumbs';

		if ( ! is_dir($thumb_path))
		{
			return $files;
		}

		ee()->load->helper('directory');
		$map = directory_map($thumb_path, TRUE);

		foreach($files as $key => &$file)
		{
			// Hide the thumbs directory
			if ($file['file_name'] == '_thumbs' OR ! $file['mime_type'] /* skips folders */)
			{
				unset($files[$key]);
				continue;
			}

			$file['date'] = ee()->localize->human_time($file['modified_date'], TRUE);
			//$file['size'] = number_format($file['file_size']/1000, 1).' '.lang('file_size_unit');
			$file['has_thumb'] = (in_array('thumb_'.$file['file_name'], $map));
		}

		// if we unset a directory in the loop above our
		// keys are no longer sequential and json won't turn
		// into an array (which is what we need)
		return array_values($files);
	}

	/**
	 * This used to only delete files. We decided we do not like that behavior
	 * so now it does nothing.
	 *
	 * @param mixed $dir_id
	 * @access public
	 * @return void
	 */
	function sync_database($dir_id)
	{
		return;
	}

	/**
	 * set_image_config
	 *
	 * @param  mixed  $data Image configuration array
	 * @param  string $type Setting type (e.g. watermark)
	 * @access public
	 * @return array  Final configuration array
	 */
	function set_image_config($data, $type = 'watermark')
	{
		$config = array();

		if ($type == 'watermark')
		{
			// Verify the watermark settings actually exist
			if ( ! isset($data['wm_type']) AND isset($data['watermark_id']))
			{
				ee()->load->model('file_model');
				$qry = ee()->file_model->get_watermark_preferences($data['watermark_id']);
				$qry = $qry->row_array();
				$data = array_merge($data, $qry);
			}

			$wm_prefs = array('source_image', 'wm_padding', 'wm_vrt_alignment', 'wm_hor_alignment',
				'wm_hor_offset', 'wm_vrt_offset');

			$i_type_prefs = array('wm_overlay_path', 'wm_opacity', 'wm_x_transp', 'wm_y_transp');

			$t_type_prefs = array('wm_text', 'wm_font_path', 'wm_font_size', 'wm_font_color',
				'wm_shadow_color', 'wm_shadow_distance');

			$config['wm_type'] =  ($data['wm_type'] == 't' OR $data['wm_type'] == 'text') ? 'text' : 'overlay';

			if ($config['wm_type'] == 'text')
			{
				// If dropshadow not enabled, let's blank the related values
				if (isset($data['wm_use_drop_shadow']) && $data['wm_use_drop_shadow'] == 'n')
				{
					$data['wm_shadow_color'] = '';
					$data['wm_shadow_distance'] = '';
				}

				foreach ($t_type_prefs as $name)
				{
					if (isset($data[$name]) && $data[$name] != '')
					{
						$config[$name] = $data[$name];
					}
				}

				if (isset($data['wm_use_font']) && isset($data['wm_font']) && $data['wm_use_font'] == 'y')
				{
					$path = APPPATH.'/fonts/';
					$config['wm_font_path'] = $path.$data['wm_font'];
				}
			}
			else
			{
				foreach ($i_type_prefs as $name)
				{
					if (isset($data[$name]) && $data[$name] != '')
					{
						$config[$name] = $data[$name];
					}
				}

				$config['wm_overlay_path'] = $data['wm_image_path'];
			}

			foreach ($wm_prefs as $name)
			{
				if (isset($data[$name]) && $data[$name] != '')
				{
					$config[$name] = $data[$name];
				}
			}
		}

		return $config;
	}
	//	Default Callbacks
	/**
	 * Directories Callback
	 *
	 * The function that retrieves the actual directory information
	 *
	 * @access	private
	 * @return	mixed	directory list
	 */
	function _directories($params = array())
	{
		$dirs = array();
		$ignore_site_id = (isset($params['ignore_site_id']) && $params['ignore_site_id'] == FALSE) ? FALSE : TRUE;

		ee()->load->model('file_upload_preferences_model');

		$directories = ee()->file_upload_preferences_model->get_file_upload_preferences(
			ee()->session->userdata('group_id'),
			NULL,
			$ignore_site_id
		);

		foreach($directories as $dir)
		{
			$dirs[$dir['id']] = $dir;
		}

		return $dirs;
	}

	/**
	 * Directory Contents Callback
	 *
	 * The function that retrieves the actual files from a directory
	 *
	 * @access	private
	 * @return	mixed	directory list
	 */
	function _directory_contents($dir, $limit, $offset)
	{
		return array(
			'files' => $this->_browser_get_files($dir, $limit, $offset)
		);
	}


	/**
	 * Gets the files for a particular directory
	 * Also, adds short name and file size
	 *
	 * @param array $dir Associative array containg directory information
	 * @param integer $limit Number of files to retrieve
	 * @param integer $offset Where to start
	 *
	 * @access private
	 * @return array	List of files
	 */
	private function _browser_get_files($dir, $limit = 15, $offset = 0)
	{
		ee()->load->model('file_model');
		ee()->load->helper(array('text', 'number'));

		if (is_array($limit))
		{
			$params = $limit;
		}
		else
		{
			$params = array(
				'type'		=> $dir['allowed_types'],
				'order'		=> array(
					'file_name' => 'asc'
				),
				'limit'		=> $limit,
				'offset'	=> $offset
			);
		}

		$files = ee()->file_model->get_files(
			$dir['id'],
			$params
		);

		if ($files['results'] === FALSE)
		{
			return array();
		}

		$files = $files['results']->result_array();

		foreach ($files as &$file)
		{
			$file['file_name'] = rawurlencode($file['file_name']);

			// Get thumb information
			$thumb_info = $this->get_thumb($file, $dir['id']);

			// Copying file_name to name for addons
			$file['name'] = $file['file_name'];

			// Setup the link
			$file['file_name'] = '
				<a href="#"
					title="'.$file['file_name'].'"
					onclick="$.ee_filebrowser.placeImage('.$file['file_id'].'); return false;"
				>
					'.urldecode($file['file_name']).'
				</a>';

			$file['short_name']		= ellipsize($file['title'], 13, 0.5);
			$file['file_size']		= byte_format($file['file_size']);
			$file['date']			= ee()->localize->format_date('%F %j, %Y %g:%i %a', $file['modified_date']);
			$file['thumb'] 			= $thumb_info['thumb'];
			$file['thumb_class']	= $thumb_info['thumb_class'];
		}

		return $files;
	}

	/**
	 * Build a dropdown list of categories
	 *
	 * @access private
	 * @param $dir Directory array, containing at least the id
	 * @return array Array with the category group name as the key and the
	 *		categories as the values (see above)
	 */
	private function _get_category_dropdown($dir)
	{
		ee()->load->helper('form');

		$raw_categories = $this->_get_categories($dir);
		$category_dropdown_array = array('all' => lang('all_categories'));

		// Build the array of categories
		foreach ($raw_categories as $category_group)
		{
			$categories = array();

			foreach($category_group['categories'] as $category)
			{
				$categories[$category['cat_id']] = $category['cat_name'];
			}

			$category_dropdown_array[$category_group['group_name']] = $categories;
		}

		return form_dropdown('category', $category_dropdown_array);
	}


	/**
	 * Validate Post Data
	 *
	 * Validates that the POST data did not get dropped, this happens when
	 * the content-length of the request is larger than PHP's post_max_size
	 *
	 *
	 * @return	bool
	 */
	public function validate_post_data()
	{
		ee()->load->helper('number_helper');
		$post_limit = get_bytes(ini_get('post_max_size'));
		return $_SERVER['CONTENT_LENGTH'] <= $post_limit;
	}


	/**
	 * Get the categories for the directory
	 *
	 * This function retrieves the categories for a particular directory
	 *
	 * @access private
	 * @return array category list
	 */
	private function _get_categories($dir)
	{
		$categories = array();

		ee()->load->model(array('file_upload_preferences_model', 'category_model'));

		$category_group_ids = ee()->file_upload_preferences_model->get_file_upload_preferences(NULL, $dir['id']);
		$category_group_ids = explode('|', $category_group_ids['cat_group']);

		if (count($category_group_ids) > 0 AND $category_group_ids[0] != '')
		{
			foreach ($category_group_ids as $category_group_id)
			{
				$category_group_info = ee()->category_model->get_category_groups($category_group_id);
				$categories[$category_group_id] = $category_group_info->row_array();
				$categories_for_group = ee()->category_model->get_channel_categories($category_group_id);
				$categories[$category_group_id]['categories'] = $categories_for_group->result_array();
			}
		}

		return $categories;
	}

	/**
	 * Directory Info Callback
	 *
	 * Returns the file count, image count and url of the directory
	 *
	 * @param array $dir Directory info associative array
	 */
	private function _directory_info($dir)
	{
		ee()->load->model('file_model');

		return array(
			'url' 			=> $dir['url'],
			'file_count'	=> ee()->file_model->count_files($dir['id']),
			'image_count'	=> ee()->file_model->count_images($dir['id'])
		);
	}

	/**
	 * File Info Callback
	 *
	 * Returns the file information for use when placing a file
	 *
	 * @param integer $file_id The File's ID
	 */
	private function _file_info($file_id)
	{
		ee()->load->model('file_model');

		$file_info = ee()->file_model->get_files_by_id($file_id);
		$file_info = $file_info->row_array();

		$file_info['is_image'] = (strncmp('image', $file_info['mime_type'], '5') == 0) ? TRUE : FALSE;

		$thumb_info = $this->get_thumb($file_info['file_name'], $file_info['upload_location_id']);
		$file_info['thumb'] = $thumb_info['thumb'];

		return $file_info;
	}

	/**
	 * Upload File Callback
	 *
	 * The function that handles the file upload logic (allowed upload? etc.)
	 *
	 *	1. Establish the allowed types for the directory
	 *		- If the field is a custom field, make sure it's permissions aren't stricter
	 *	2. Upload the file
	 *		- Checks to see if XSS cleaning needs to be on
	 *		- Returns errors
	 *	3. Send file to save_file, which does more security, creates thumbs
	 *		and adds it to the database.
	 *
	 * @access	private
	 * @param	array 	$dir 		Directory information from the database in array form
	 * @param	string	$field_name	Provide the field name in case it's a custom field
	 * @return 	array 	Array of file_data sent to Filemanager->save_file
	 */
	private function _upload_file($dir, $field_name)
	{
		// --------------------------------------------------------------------
		// Make sure the file is allowed

		// Is this a custom field?
		if (strpos($field_name, 'field_id_') === 0)
		{
			$field_id = str_replace('field_id_', '', $field_name);

			ee()->db->select('field_type, field_settings');
			$type_query = ee()->db->get_where('channel_fields', array('field_id' => $field_id));

			if ($type_query->num_rows())
			{
				$settings = unserialize(base64_decode($type_query->row('field_settings')));

				// Permissions can only get more strict!
				if (isset($settings['field_content_type']) && $settings['field_content_type'] == 'image')
				{
					$allowed_types = 'gif|jpg|jpeg|png|jpe';
				}
			}

			$type_query->free_result();
		}

		// --------------------------------------------------------------------
		// Upload the file

		$field = ($field_name) ? $field_name : 'userfile';
		$original_filename = $_FILES[$field]['name'];
		$clean_filename = basename($this->clean_filename(
			$_FILES[$field]['name'],
			$dir['id'],
			array('ignore_dupes' => TRUE)
		));

		$config = array(
			'file_name'		=> $clean_filename,
			'upload_path'	=> $dir['server_path'],
			'max_size'		=> round((int)$dir['max_size'], 3)
		);

		// Restricted upload directory?
		if ($dir['allowed_types'] == 'img')
		{
			$config['is_image'] = TRUE;
		}

		ee()->load->helper('xss');

		// Check to see if the file needs to be XSS Cleaned
		if (xss_check())
		{
			$config['xss_clean'] = TRUE;
		}
		else
		{
			$config['xss_clean'] = FALSE;
			$this->xss_clean_off();
		}

		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- channel_form_overwrite => Allow authors to overwrite their own files via Channel Form
		/* -------------------------------------------*/

		if (bool_config_item('channel_form_overwrite'))
		{
			$original = ee('Model')->get('File')
				->filter('file_name', $clean_filename)
				->filter('upload_location_id', $dir['id'])
				->first();

			if ($original && $original->uploaded_by_member_id == ee()->session->userdata('member_id'))
			{
				$config['overwrite'] = TRUE;
			}
		}

		// Upload the file
		ee()->load->library('upload');
		ee()->upload->initialize($config);

		if ( ! ee()->upload->do_upload($field_name))
		{
			return $this->_upload_error(
				ee()->upload->display_errors()
			);
		}

		$file = ee()->upload->data();

		// (try to) Set proper permissions
		@chmod($file['full_path'], FILE_WRITE_MODE);


		// --------------------------------------------------------------------
		// Add file the database

		// Make sure the file has a valid MIME Type
		if ( ! $file['file_type'])
		{
			return $this->_upload_error(
				lang('invalid_mime'),
				array(
					'file_name'		=> $file['file_name'],
					'directory_id'	=> $dir['id']
				)
			);
		}

		$thumb_info = $this->get_thumb($file['file_name'], $dir['id']);

		// Build list of information to save and return
		$file_data = array(
			'upload_location_id'	=> $dir['id'],
			'site_id'				=> ee()->config->item('site_id'),

			'file_name'				=> $file['file_name'],
			'orig_name'				=> $original_filename, // name before any upload library processing
			'file_data_orig_name'	=> $file['orig_name'], // name after upload lib but before duplicate checks

			'is_image'				=> $file['is_image'],
			'mime_type'				=> $file['file_type'],

			'file_thumb'			=> $thumb_info['thumb'],
			'thumb_class' 			=> $thumb_info['thumb_class'],

			'modified_by_member_id' => ee()->session->userdata('member_id'),
			'uploaded_by_member_id'	=> ee()->session->userdata('member_id'),

			'file_size'				=> $file['file_size'] * 1024, // Bring it back to Bytes from KB
			'file_height'			=> $file['image_height'],
			'file_width'			=> $file['image_width'],
			'file_hw_original'		=> $file['image_height'].' '.$file['image_width'],
			'max_width'				=> $dir['max_width'],
			'max_height'			=> $dir['max_height']
		);

		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- channel_form_overwrite => Allow authors to overwrite their own files via Channel Form
		/* -------------------------------------------*/

		if (isset($config['overwrite']) && $config['overwrite'] === TRUE)
		{
			$file_data['file_id'] = $original->file_id;
		}

		// Check to see if its an editable image, if it is, check max h/w
		if ($this->is_editable_image($file['full_path'], $file['file_type']))
		{
			// Check and fix orientation
			$orientation = $this->orientation_check($file['full_path'], $file_data);

			if ( ! empty($orientation))
			{
				$file_data = $orientation;
			}

			$file_data = $this->max_hw_check($file['full_path'], $file_data);

			if ( ! $file_data)
			{
				return $this->_upload_error(
					lang('exceeds_max_dimensions'),
					array(
						'file_name'		=> $file['file_name'],
						'directory_id'	=> $dir['id']
					)
				);
			}
		}

		// Save file to database
		$saved = $this->save_file($file['full_path'], $dir['id'], $file_data);

		// Return errors from the filemanager
		if ( ! $saved['status'])
		{
			return $this->_upload_error(
				$saved['message'],
				array(
					'file_name'		=> $file['file_name'],
					'directory_id'	=> $dir['id']
				)
			);
		}

		// Merge in information from database
		$file_data = array_merge($file_data, $this->_file_info($saved['file_id']));

		// Stash upload directory prefs in case
		$file_data['upload_directory_prefs'] = $dir;
		$file_data['directory'] = $dir['id'];

		// Change file size to human readable
		ee()->load->helper('number');
		$file_data['file_size'] = byte_format($file_data['file_size']);

		return $file_data;
	}

	/**
	 * Sends an upload error and delete's the file based upon
	 * available information
	 *
	 * @param string $error_message The error message to send
	 * @param array $file_info Array containing file_id or file_name and directory_id
	 * @return array Associative array with error message in it
	 */
	function _upload_error($error_message, $file_info = array())
	{
		if (isset($file_info['file_id']))
		{
			ee()->load->model('file_model');
			ee()->file_model->delete_files($file_info['file_id']);
		}
		else if (isset($file_info['file_name']) AND isset($file_info['directory_id']))
		{
			ee()->load->model('file_model');
			ee()->file_model->delete_raw_file($file_info['file_name'], $file_info['directory_id']);
		}

		return array('error' => $error_message);
	}

	/**
	 * Overwrite OR Rename Files Manually
	 *
	 * @access	public
	 * @param integer $file_id The ID of the file in exp_files
	 * @param string $new_file_name The new file name for the file
	 * @param string $replace_file_name The temporary replacement name for the file
	 * @return mixed TRUE if successful, otherwise it returns the error
	 */
	function rename_file($file_id, $new_file_name, $replace_file_name = '')
	{
		ee()->load->model(array('file_upload_preferences_model', 'file_model'));

		$replace = FALSE;

		// Get the file data form the database
		$previous_data = ee()->file_model->get_files_by_id($file_id);
		$previous_data = $previous_data->row();

		// If the new name is the same as the previous, get out of here
		if ($new_file_name == $previous_data->file_name)
		{
			return array(
				'success'	=> TRUE,
				'replace'	=> $replace,
				'file_id'	=> $file_id
			);
		}

		$directory_id		= $previous_data->upload_location_id;
		$old_file_name		= $previous_data->file_name;
		$upload_directory	= $this->fetch_upload_dir_prefs($directory_id);


		// If they renamed, we need to be sure the NEW name doesn't conflict
		if ($replace_file_name != '' && $new_file_name != $replace_file_name)
        {
			if (file_exists($upload_directory['server_path'].$new_file_name))
			{
				$replace_data = ee()->file_model->get_files_by_name($new_file_name, $directory_id);

				if ($replace_data->num_rows() > 0)
				{
					$replace_data = $replace_data->row();

					return array(
						'success'	=> FALSE,
						'error'	=> 'retry',
						'replace_filename' => $replace_data->file_name,
						'file_id'	=> $file_id
						);
				}

				return array(
					'success'	=> FALSE,
					'error'	=> lang('file_exists_replacement_error'),
					'file_id'	=> $file_id
					);
        	}
        }

		// Check to see if a file with that name already exists
		if (file_exists($upload_directory['server_path'] . $new_file_name))
		{
			// If it does, delete the old files and remove the new file
			// record in the database

			$replace = TRUE;
			$previous_data = $this->_replace_file($previous_data, $new_file_name, $directory_id);
			$file_id = $previous_data->file_id;
		}

		// Delete the thumbnails
		ee()->file_model->delete_raw_file($old_file_name, $directory_id, TRUE);

		// Rename the actual file
		$file_path = $this->_rename_raw_file(
			$old_file_name,
			$new_file_name,
			$directory_id
		);

		$new_file_name = str_replace($upload_directory['server_path'], '', $file_path);

		// If renaming the file sparked an error return it
		if (is_array($file_path))
		{
			return array(
				'success'	=> FALSE,
				'error'		=> $file_path['error']
			);
		}

		// Update the file record
		$updated_data = array(
			'file_id'	=> $file_id,
			'file_name'	=> $new_file_name,
		);

		// Change title if it was automatically set
		if ($previous_data->title == $previous_data->file_name)
		{
			$updated_data['title'] = $new_file_name;
		}

		$file = $this->save_file(
			$file_path,
			$previous_data->upload_location_id,
			$updated_data
		);

		return array(
			'success'	=> TRUE,
			'replace'	=> $replace,
			'file_id'	=> ($replace) ? $file['file_id'] : $file_id
		);
    }

	/**
	 * Deletes the old raw files, and the new file's database records
	 *
	 * @param object $new_file The data coming from the database for the deleted file
	 * @param string $file_name The file name, the existing files are deleted
	 * 	and the new files are renamed within Filemanager::rename_file
	 * @param integer $directory_id The directory ID where the file is located
	 * @return object Object from database representing the data of the old item
	 */
	public function _replace_file($new_file, $file_name, $directory_id)
	{
		// Get the ID of the existing file
		$existing_file = ee()->file_model->get_files_by_name($file_name, $directory_id);
		$existing_file = $existing_file->row();

		// Delete the existing file's raw files, but leave the database record
		ee()->file_model->delete_raw_file($file_name, $directory_id);

		// It is possible the file exists but is NOT in the DB yet
		if (empty($existing_file))
		{
			$new_file->modified_by_member_id = ee()->session->userdata('member_id');
			return $new_file;
		}

		// Delete the new file's database record, but leave the files
		ee()->file_model->delete_files($new_file->file_id, FALSE);

		// Update file_hw_original, filesize, modified date and modified user
		ee()->file_model->save_file(array(
			'file_id'				=> $existing_file->file_id, // Use the old file_id
			'file_size'				=> $new_file->file_size,
			'file_hw_original'		=> $new_file->file_hw_original,
			'modified_date'			=> $new_file->modified_date,
			'modified_by_member_id'	=> ee()->session->userdata('member_id')
		));

		$existing_file->file_size				= $new_file->file_size;
		$existing_file->file_hw_original		= $new_file->file_hw_original;
		$existing_file->modified_date			= $new_file->modified_date;
		$existing_file->modified_by_member_id 	= ee()->session->userdata('member_id');

		return $existing_file;
	}

	/**
	 * Renames a raw file, doesn't touch the database
	 *
	 * @param string $old_file_name The old file name
	 * @param string $new_file_name The new file name
	 * @param integer $directory_id The ID of the directory the file is in
	 * @return string The path of the newly renamed file
	 */
    public function _rename_raw_file($old_file_name, $new_file_name, $directory_id)
    {
		// Make sure the filename is clean
		$new_file_name = basename($this->clean_filename($new_file_name, $directory_id));

		// Check they have permission for this directory and get directory info
		$upload_directory = $this->fetch_upload_dir_prefs($directory_id);

		// If this directory doesn't exist then we can't do anything
		if ( ! $upload_directory)
		{
			return array('error' => lang('no_known_file'));
		}

		// Rename the file
		$config = array(
			'upload_path'	=> $upload_directory['server_path'],
			'allowed_types'	=> (ee()->session->userdata('group_id') == 1) ? 'all' : $upload_directory['allowed_types'],
			'max_size'		=> round((int) $upload_directory['max_size']*1024, 3),
			'max_width'		=> $upload_directory['max_width'],
			'max_height'	=> $upload_directory['max_height']
		);

		ee()->load->library('upload', $config);

		if ( ! ee()->upload->file_overwrite($old_file_name, $new_file_name))
		{
			return array('error' => ee()->upload->display_errors());
		}

		return $upload_directory['server_path'] . $new_file_name;
    }

	/**
	 * Handle the edit actions
	 *
	 * @access	public
	 * @return	mixed
	 */
	function edit_image()
	{
		ee()->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
		ee()->output->set_header("Pragma: no-cache");

		$file = str_replace(DIRECTORY_SEPARATOR, '/', ee('Encrypt')->decode(rawurldecode(ee()->input->get_post('file')), ee()->config->item('session_crypt_key')));

		if ($file == '')
		{
			// nothing for you here
			ee()->session->set_flashdata('message_failure', ee()->lang->line('choose_file'));
			ee()->functions->redirect(BASE.AMP.'C=content_files');
		}

		// crop takes precendence over resize
		// we need at least a width
		if (ee()->input->get_post('crop_width') != '' AND ee()->input->get_post('crop_width') != 0)
		{

			$config['width'] = ee()->input->get_post('crop_width');
			$config['maintain_ratio'] = FALSE;
			$config['x_axis'] = ee()->input->get_post('crop_x');
			$config['y_axis'] = ee()->input->get_post('crop_y');
			$action = 'crop';

			if (ee()->input->get_post('crop_height') != '')
			{
				$config['height'] = ee()->input->get_post('crop_height');
			}
			else
			{
				$config['master_dim'] = 'width';
			}
		}
		elseif (ee()->input->get_post('resize_width') != '' AND ee()->input->get_post('resize_width') != 0)
		{
			$config['width'] = ee()->input->get_post('resize_width');
			$config['maintain_ratio'] = ee()->input->get_post("constrain");
			$action = 'resize';

			if (ee()->input->get_post('resize_height') != '')
			{
				$config['height'] = ee()->input->get_post('resize_height');
			}
			else
			{
				$config['master_dim'] = 'width';
			}
		}
		elseif (ee()->input->get_post('rotate') != '' AND ee()->input->get_post('rotate') != 'none')
		{
			$action = 'rotate';
			$config['rotation_angle'] = ee()->input->get_post('rotate');
		}
		else
		{
			if (ee()->input->get_post('is_ajax'))
			{
				header('HTTP', true, 500);
				exit(ee()->lang->line('width_needed'));
			}
			else
			{
				show_error(ee()->lang->line('width_needed'));
			}
		}

		$config['image_library'] = ee()->config->item('image_resize_protocol');
		$config['library_path'] = ee()->config->item('image_library_path');
		$config['source_image'] = $file;

		$path = substr($file, 0, strrpos($file, '/')+1);
		$filename = substr($file, strrpos($file, '/')+1, -4); // All editable images have 3 character file extensions
		$file_ext = substr($file, -4); // All editable images have 3 character file extensions

		$image_name_reference = $filename.$file_ext;

		if (ee()->input->get_post('source') == 'resize_orig')
		{
			$config['new_image'] = $file;
		}
		else
		{
			// Add to db using save- becomes a new entry
			$thumb_suffix = ee()->config->item('thumbnail_prefix');

			$new_filename = ee('Filesystem')->getUniqueFilename($path.$filename.'_'.$thumb_suffix.$file_ext);
			$new_filename = str_replace($path, '', $new_filename);

			$image_name_reference = $new_filename;
			$config['new_image'] = $new_filename;
		}

//		$config['dynamic_output'] = TRUE;

		ee()->load->library('image_lib', $config);

		$errors = '';

		// Cropping and Resizing
		if ($action == 'resize')
		{
			if ( ! ee()->image_lib->resize())
			{
		    	$errors = ee()->image_lib->display_errors();
			}
		}
		elseif ($action == 'rotate')
		{

			if ( ! ee()->image_lib->rotate())
			{
			    $errors = ee()->image_lib->display_errors();
			}
		}
		else
		{
			if ( ! ee()->image_lib->crop())
			{
			    $errors = ee()->image_lib->display_errors();
			}
		}

		// Any reportable errors? If this is coming from ajax, just the error messages will suffice
		if ($errors != '')
		{
			if (ee()->input->get_post('is_ajax'))
			{
				header('HTTP', true, 500);
				exit($errors);
			}
			else
			{
				show_error($errors);
			}
		}

		$dimensions = ee()->image_lib->get_image_properties('', TRUE);
		ee()->image_lib->clear();

		// Rebuild thumb
		$this->create_thumb(
						array('server_path'	=> $path),
						array('name'		=> $image_name_reference)
					);


		exit($image_name_reference);
	}

	/**
	 * Fetch Upload Directories
	 *
	 * self::_directories() caches upload dirs in _upload_dirs, so we don't
	 * query twice if we don't need to.
	 *
	 * @return array
	 */
	public function fetch_upload_dirs($params = array())
	{
		if ( ! empty($this->_upload_dirs))
		{
			return $this->_upload_dirs;
		}

		return $this->_directories($params);
	}

	/**
	 *
	 *
	 */
	public function fetch_files($file_dir_id = NULL, $files = array(), $get_dimensions = FALSE)
	{
		ee()->load->model('file_upload_preferences_model');

		$upload_dirs = ee()->file_upload_preferences_model->get_file_upload_preferences(
										ee()->session->userdata('group_id'),
										$file_dir_id);

		$dirs = new stdclass();
		$dirs->files = array();

		// Nest the array one level deep if single row is
		// returned so the loop can do the same work
		if ($file_dir_id != NULL)
		{
			$upload_dirs = array($upload_dirs);
		}

		foreach ($upload_dirs as $dir)
		{
			$dirs->files[$dir['id']] = array();

			$files = ee()->file_model->get_raw_files($dir['server_path'], $dir['allowed_types'], '', false, $get_dimensions, $files);

			foreach ($files as $file)
			{
				$dirs->files[$dir['id']] = $files;
			}
		}

		return $dirs;
	}

	/**
	 * Create a Directory Map
	 *
	 * Reads the specified directory and builds an array
	 * representation of it.  Sub-folders contained with the
	 * directory will be mapped as well.
	 *
	 * @param  string $source_dir path to source
	 * @param  int    $directory_depth depth of directories to traverse
	 *   (0 = fully recursive, 1 = current dir, etc)
	 * @param  bool   $hidden Include hidden files (default: FALSE)
	 * @param  string $allowed_tpyes Either "img" for images or "all" for
	 *   everything
	 * @return array|bool FALSE if we cannot open the directory, an array of
	 *   files otherwise.
	 */
	function directory_files_map($source_dir, $directory_depth = 0, $hidden = false, $allowed_types = 'all')
	{
		ee()->load->helper(array('file', 'directory'));
		ee()->load->library('mime_type');

		if ($fp = @opendir($source_dir))
		{
			$filedata	= array();
			$new_depth	= $directory_depth - 1;
			$source_dir	= rtrim($source_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

			while (FALSE !== ($file = readdir($fp)))
			{
				$allowed = TRUE;
				// Remove '.', '..', and hidden files [optional]
				if ( ! trim($file, '.') OR ($hidden == FALSE && $file[0] == '.'))
				{
					continue;
				}

				$index = array('index.html', 'index.htm', 'index.php');
				if ( ! is_dir($source_dir.$file) && ! in_array($file, $index))
				{
					$mime = ee()->mime_type->ofFile($source_dir.$file);

					if ($allowed_types == 'img')
					{
						$allowed = ee()->mime_type->isImage($mime);
					}

					if ( ! $allowed)
					{
						continue;
					}

					$filedata[] = $file;
				}
				elseif (($directory_depth < 1 OR $new_depth > 0) && @is_dir($source_dir.$file))
				{
					$filedata[$file] = directory_map($source_dir.$file.DIRECTORY_SEPARATOR, $new_depth, $hidden);
				}
			}

			closedir($fp);

			sort($filedata);
			return $filedata;
		}

		return FALSE;
	}

	/**
	 * Download Files.
	 *
	 * This is a helper wrapper around the zip lib and download helper
	 *
	 * @param 	mixed   string or array of urlencoded file names
	 * @param 	string	file directory the files are located in.
	 * @param 	string	optional name of zip file to download
	 * @return 	mixed 	nuttin' or boolean false if everything goes wrong.
	 */
	public function download_files($files, $zip_name='downloaded_files.zip')
	{
		ee()->load->model('file_upload_preferences_model');

		$upload_prefs = ee()->file_upload_preferences_model->get_file_upload_preferences(1);

		if (count($files) === 1)
		{
			// Get the file Location:
			$file_data = ee()->db->select('upload_location_id, file_name')
				->from('files')
				->where('file_id', $files[0])
				->get()
				->row();

			$file_path = reduce_double_slashes(
				$upload_prefs[$file_data->upload_location_id]['server_path'].'/'.$file_data->file_name
			);

			if ( ! file_exists($file_path))
			{
				return FALSE;
			}

			$file = file_get_contents($file_path);
			$file_name = $file_data->file_name;

			ee()->load->helper('download');
			force_download($file_name, $file);

			return TRUE;
		}

		// Zip up a bunch of files for download
		ee()->load->library('zip');

		$files_data = ee()->db->select('upload_location_id, file_name')
			->from('files')
			->where_in('file_id', $files)
			->get();

		if ($files_data->num_rows() === 0)
		{
			return FALSE;
		}

		foreach ($files_data->result() as $row)
		{
			$file_path = reduce_double_slashes(
				$upload_prefs[$row->upload_location_id]['server_path'].'/'.$row->file_name
			);
			ee()->zip->read_file($file_path);
		}

		ee()->zip->download($zip_name);

		return TRUE;
	}

	/**
	 * Get file info
	 *
	 * At this time, this is a basic wrapper around the CI image lib
	 * It's here to make things forward compatible for if/when image uploads
	 * could be tossed in the database.
	 *
	 * @param 	string		full system path to the image to examine
	 * @return 	array
	 */
	public function get_file_info($file)
	{
		ee()->load->library('image_lib');

		return ee()->image_lib->get_image_properties($file, TRUE);
	}

	/**
	 * Is Image
	 *
	 * This function has been lifted from the CI file upload class, and tweaked
	 * just a bit.
	 *
	 * @param 	string 		path to file
	 * @return 	boolean		TRUE if image, FALSE if not
	 */
	public function is_image($mime)
	{
		ee()->load->library('mime_type');
		return ee()->mime_type->isImage($mime);
	}

	/**
	 * Fetch Fontlist
	 *
	 * Retrieves available font file names, returns associative array
	 *
	 * @return 	array
	 */

	function fetch_fontlist()
	{
		$path = APPPATH.'/fonts/';

		$font_files = array();

		if ($fp = @opendir($path))
		{
			while (false !== ($file = readdir($fp)))
			{
				if (stripos(substr($file, -4), '.ttf') !== FALSE)
				{
					$name = substr($file, 0, -4);
					$name = ucwords(str_replace("_", " ", $name));

					$font_files[$file] = $name;
				}
			}

			closedir($fp);
		}

		return $font_files;
	}

	/**
	 * image processing
	 *
	 * Figures out the full path to the file, and sends it to the appropriate
	 * method to process the image.
	 *
	 * Needs a few POST variables:
	 * 	- file_id: ID of the file
	 * 	- file_name: name of the file without full path
	 * 	- upload_dir: Directory ID
	 */
	public function _do_image_processing($redirect = TRUE)
	{
		$file_id = ee()->input->post('file_id');

		$actions = ee()->input->post('action');
		if ( ! is_array($actions))
		{
			$actions = array($actions);
		}

		// Check to see if a file was actually sent...
		if ( ! ($file_name = ee()->input->post('file_name')))
		{
			ee()->session->set_flashdata('message_failure', lang('choose_file'));
			ee()->functions->redirect(BASE.AMP.'C=content_files');
		}

		// Get the upload directory preferences
		$upload_dir_id = ee()->input->post('upload_dir');
		$upload_prefs = $this->fetch_upload_dir_prefs($upload_dir_id);

		// Clean up the filename and add the full path
		$file_name = ee()->security->sanitize_filename(urldecode($file_name));
		$file_path = reduce_double_slashes(
			$upload_prefs['server_path'].DIRECTORY_SEPARATOR.$file_name
		);

		// Loop over the actions
		foreach ($actions as $action)
		{
			// Where are we going with this?
			switch ($action)
			{
				case 'rotate':
					$response = $this->_do_rotate($file_path);
					break;
				case 'crop':
					$response = $this->_do_crop($file_path);
					break;
				case 'resize':
					$response = $this->_do_resize($file_path);
					break;
				default:
					return ''; // todo, error
			}

			// Did we break anything?
			if (isset($response['errors']))
			{
				if (AJAX_REQUEST)
				{
					ee()->output->send_ajax_response($response['errors'], TRUE);
				}

				show_error($response['errors']);
			}
		}

		ee()->load->model('file_model');

		// Update database
		ee()->file_model->save_file(array(
			'file_id' => $file_id,
			'file_hw_original' => $response['dimensions']['height'] . ' ' . $response['dimensions']['width'],
			'file_size' => $response['file_info']['size']
		));

		// Get dimensions for thumbnail
		$dimensions = ee()->file_model->get_dimensions_by_dir_id($upload_dir_id);
		$dimensions = $dimensions->result_array();

		// Regenerate thumbnails
		$this->create_thumb(
			$file_path,
			array(
				'server_path'	=> $upload_prefs['server_path'],
				'file_name'		=> basename($file_name),
				'dimensions'	=> $dimensions
			),
			TRUE, // Regenerate thumbnails
			FALSE // Regenerate all images
		);

		// If we're redirecting send em on
		if ($redirect === TRUE)
		{
			// Send the dimensions back for Ajax requests
			if (AJAX_REQUEST)
			{
				ee()->output->send_ajax_response(array(
					'width'		=> $response['dimensions']['width'],
					'height'	=> $response['dimensions']['height']
				));
			}

			// Otherwise redirect
			ee()->session->set_flashdata('message_success', lang('file_saved'));
			ee()->functions->redirect(
				BASE.AMP.
				'C=content_files'.AMP.
				'M=edit_image'.AMP.
				'upload_dir='.ee()->input->post('upload_dir').AMP.
				'file_id='.ee()->input->post('file_id')
			);
		}
		// Otherwise return the response from the called method
		else
		{
			return $response;
		}
	}

	/**
	 * Image crop
	 */
	public function _do_crop($file_path)
	{
		$config = array(
			'width'				=> ee()->input->post('crop_width'),
			'maintain_ratio'	=> FALSE,
			'x_axis'			=> ee()->input->post('crop_x'),
			'y_axis'			=> ee()->input->post('crop_y'),
			'height'			=> (ee()->input->post('crop_height')) ? ee()->input->post('crop_height') : NULL,
			'master_dim'		=> 'width',
			'library_path'		=> ee()->config->item('image_library_path'),
			'image_library'		=> ee()->config->item('image_resize_protocol'),
			'source_image'		=> $file_path,
			'new_image'			=> $file_path
		);

		// Must initialize seperately in case image_lib was loaded previously
		ee()->load->library('image_lib');
		$return = ee()->image_lib->initialize($config);

		if ($return === FALSE)
		{
			$errors = ee()->image_lib->display_errors();
		}
		else
		{
			if ( ! ee()->image_lib->crop())
			{
		    	$errors = ee()->image_lib->display_errors();
			}
		}

		$reponse = array();

		if (isset($errors))
		{
			$response['errors'] = $errors;
		}
		else
		{
			ee()->load->helper('file');
			$response = array(
				'dimensions' => ee()->image_lib->get_image_properties('', TRUE),
				'file_info'  => get_file_info($file_path)
			);
		}

		ee()->image_lib->clear();

		return $response;
	}

	/**
	 * Do image rotation.
	 */
	public function _do_rotate($file_path)
	{
		$config = array(
			'rotation_angle'	=> ee()->input->post('rotate'),
			'library_path'		=> ee()->config->item('image_library_path'),
			'image_library'		=> ee()->config->item('image_resize_protocol'),
			'source_image'		=> $file_path,
			'new_image'			=> $file_path
		);

		// Must initialize seperately in case image_lib was loaded previously
		ee()->load->library('image_lib');
		$return = ee()->image_lib->initialize($config);

		if ($return === FALSE)
		{
			$errors = ee()->image_lib->display_errors();
		}
		else
		{
			if ( ! ee()->image_lib->rotate())
			{
		    	$errors = ee()->image_lib->display_errors();
			}
		}

		$reponse = array();

		if (isset($errors))
		{
			$response['errors'] = $errors;
		}
		else
		{
			ee()->load->helper('file');
			$response = array(
				'dimensions' => ee()->image_lib->get_image_properties('', TRUE),
				'file_info'  => get_file_info($file_path)
			);
		}

		ee()->image_lib->clear();

		return $response;
	}

	/**
	 * Do image resizing.
	 */
	public function _do_resize($file_path)
	{
		$config = array(
			'width'				=> ee()->input->get_post('resize_width'),
			'maintain_ratio'	=> ee()->input->get_post('constrain'),
			'library_path'		=> ee()->config->item('image_library_path'),
			'image_library'		=> ee()->config->item('image_resize_protocol'),
			'source_image'		=> $file_path,
			'new_image'			=> $file_path
		);

		if (ee()->input->get_post('resize_height') != '')
		{
			$config['height'] = ee()->input->get_post('resize_height');
		}
		else
		{
			$config['master_dim'] = 'width';
		}

		// Must initialize seperately in case image_lib was loaded previously
		ee()->load->library('image_lib');
		$return = ee()->image_lib->initialize($config);

		if ($return === FALSE)
		{
			$errors = ee()->image_lib->display_errors();
		}
		else
		{
			if ( ! ee()->image_lib->resize())
			{
		    	$errors = ee()->image_lib->display_errors();
			}
		}

		$reponse = array();

		if (isset($errors))
		{
			$response['errors'] = $errors;
		}
		else
		{
			ee()->load->helper('file');
			$response = array(
				'dimensions' => ee()->image_lib->get_image_properties('', TRUE),
				'file_info'  => get_file_info($file_path)
			);
		}

		ee()->image_lib->clear();

		return $response;
	}
}

// END Filemanager class

// EOF
