<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * ExpressionEngine Filemanager Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Filemanager
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Filemanager {

	var $config;
	var $theme_url;
	
	public $upload_errors		= FALSE;
	public $upload_data			= NULL;
	public $upload_warnings		= FALSE;

	private $EE;
	
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
		$this->EE =& get_instance();
		$this->EE->load->library('javascript');
		$this->EE->load->library('security');
		$this->EE->lang->loadfile('filemanager');
		
		$this->theme_url = $this->EE->config->item('theme_folder_url').'cp_themes/'.$this->EE->config->item('cp_theme').'/';
	}

	// ---------------------------------------------------------------------
	
	function _set_error($error)
	{
		return;
	}

	// ---------------------------------------------------------------------

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

		$filename = $this->EE->security->sanitize_filename($filename);
		
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

	// ---------------------------------------------------------------------
	
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
	
	// --------------------------------------------------------------------
	
	/**
	 * Get the upload directory preferences for an individual directory
	 * 
	 * @param integer $dir_id ID of the directory to get preferences for
	 */
	function fetch_upload_dir_prefs($dir_id)
	{
		if (isset($this->_upload_dir_prefs[$dir_id]))
		{
			return $this->_upload_dir_prefs[$dir_id];
		}
		
		$this->EE->load->model(array('file_model', 'file_upload_preferences_model'));

		// Figure out if the directory actually exists
		$qry = $this->EE->file_upload_preferences_model->get_upload_preferences(
			'1', // Overriding the group ID to get all IDs
			$dir_id
		);
		
		if ( ! $qry->num_rows())
		{
			return FALSE;
		}
		
		$prefs = $qry->row_array();
		$qry->free_result();
		
		// Add dimensions to prefs
		$prefs['dimensions'] = array();
		
		$qry = $this->EE->file_model->get_dimensions_by_dir_id($dir_id, TRUE);
		
		foreach ($qry->result_array() as $row)
		{
			$prefs['dimensions'][$row['id']] = array(
				'short_name'	=> $row['short_name'],
				'width'			=> $row['width'],
				'height'		=> $row['height'],
				'watermark_id'	=> $row['watermark_id'], 
				'resize_type'	=> $row['resize_type']
			);
			
			// Add watermarking prefs
			foreach ($row as $key => $val)
			{
				if (substr($key, 0, 3) == 'wm_')
				{
					$prefs['dimensions'][$row['id']][$key] = $val;
				}
			}
		}
		
		$qry->free_result();
		
		// check keys and cache
		//return $this->set_upload_dir_prefs($dir_id, $qry->row_array());
		return $this->set_upload_dir_prefs($dir_id, $prefs);
	}

	// --------------------------------------------------------------------

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
		$this->EE->load->helper(array('file', 'xss'));
		
		$is_image = FALSE;
		$allowed = $prefs['allowed_types'];
		$mime = get_mime_by_extension($file_path);
		
		if (is_array($mime))
		{
			$mime = $mime[0];
		}
		
		if ($allowed == 'all' OR $allowed == '*')
		{
			return $mime;
		}
		
		if ($allowed == 'img')
		{
			$allowed = 'gif|jpg|jpeg|png|jpe';
		}
		
		$extension = strtolower(substr(strrchr($file_path, '.'), 1));

		if (strpos($allowed, $extension) === FALSE)
		{
			return FALSE;
		}
		
		// Double check mime type for images so we can
		// be sure that our xss check is run correctly
		if (substr($mime, 0, 5) == 'image')
		{
			$is_image = TRUE;
		}
		
		// We need to be able to turn this off!
		
		//Apply XSS Filtering to uploaded files?
		if ($this->_xss_on AND 
			xss_check() AND 
			! $this->EE->security->xss_clean($file_path, $is_image))
		{
			return FALSE;
		}
		
		return $mime;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Turn XSS cleaning on
	 */
	public function xss_clean_on()
	{
		$this->_xss_on = TRUE;
	}

	// --------------------------------------------------------------------
	
	public function xss_clean_off()
	{
		$this->_xss_on = FALSE;
	}

	// --------------------------------------------------------------------
	
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
	
	
	// --------------------------------------------------------------------
	
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
	
	
	// --------------------------------------------------------------------
	
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
		$dir_prefs = $this->fetch_upload_dir_prefs($dir_id);
		
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
		$this->EE->load->model('file_model');

		if ($file_id = $this->EE->file_model->save_file($prefs))
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
	
	// --------------------------------------------------------------------

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

		$this->EE->load->library('image_lib');

		$this->EE->image_lib->clear();

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
		if (($force_master_dim == 'height' && $prefs['height'] < $prefs['max_height']) OR 
				($force_master_dim == 'width' && $prefs['width'] < $prefs['max_width']) OR
				($force_master_dim == FALSE && $prefs['width'] < $prefs['max_width']) OR 
				($force_master_dim == FALSE && $prefs['height'] < $prefs['max_height']))
		{
			return $prefs;
		}


		unset($prefs['width']);
		unset($prefs['height']);

		// Set required memory
		if ( ! $this->set_image_memory($file_path))
		{
			log_message('error', 'Insufficient Memory for Thumbnail Creation: '.$file_path);
			return FALSE;
		}
			
		// Resize
		
		$config['source_image']		= $file_path;
		$config['maintain_ratio']	= TRUE;
		$config['image_library']	= $this->EE->config->item('image_resize_protocol');
		$config['library_path']		= $this->EE->config->item('image_library_path');

		$this->EE->image_lib->initialize($config);
				
		if ( ! $this->EE->image_lib->resize())
		{
			return FALSE;
		}
		
		$new_image = $this->EE->image_lib->get_image_properties('', TRUE);

		// We need to reset some prefs
		if ($new_image)
		{
			$this->EE->load->helper('number');
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


	// ---------------------------------------------------------------------

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
		$group_id = $this->EE->session->userdata('group_id');

		// Non admins need to have their permissions checked
		if ($group_id != 1)
		{
			// non admins need to first be checked for restrictions
			// we'll add these into a where_not_in() check below
			$this->EE->db->select('upload_id');
			$this->EE->db->where(array(
				'member_group' => $group_id,
				'upload_id'    => $dir_id
			));

			// If any record shows up, then they do not have access
			if ($this->EE->db->count_all_results('upload_no_access') > 0)
			{
				
				return FALSE;
			}
		}

		return TRUE;
	}
	
	
	// ---------------------------------------------------------------------

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

	// --------------------------------------------------------------------
	
	/**
	 * Filebrowser
	 *
	 * Includes the javascript that is needed to dynamically bootstrap the filebrowser
	 *
	 * @access	public
	 * @param	string	the endpoint url
	 * @return	void
	 */	
	function filebrowser($endpoint_url)
	{
		// Include dependencies
		$this->EE->cp->add_js_script(array(
			'plugin'    => array('scrollable', 'scrollable.navigator', 'ee_filebrowser', 'ee_fileuploader', 'tmpl')
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
	
	// --------------------------------------------------------------------
	
	/**
	 * Filebrowser (Frontend)
	 *
	 * Same as filebrowser(), but with additional considerations for the frontend
	 *
	 * @access	public
	 * @param	string	the endpoint url
	 * @param	bool	include jquery core?
	 * @return	void
	 */
	function frontend_filebrowser($endpoint_url, $include_jquery_base = TRUE)
	{
		$this->EE->lang->loadfile('filebrowser');

		$ret = array();

		$ret['str'] = '';

		$ret['json'] = array(
			'BASE'			=> $this->EE->functions->fetch_site_index(0,0).QUERY_MARKER,
			'THEME_URL'		=> $this->theme_url,
			'PATH_CP_GBL_IMG'	=> $this->EE->config->item('theme_folder_url').'cp_global_images/',
			'filebrowser' => array(
				'endpoint_url'	=> $endpoint_url,
				'window_title'	=> lang('file_manager'),
				'theme_url'		=> $this->theme_url
			),
			'fileuploader' => array(
				'window_title'		=> lang('file_upload'),
				'delete_url'		=> 'C=content_files&M=delete_files'
			),
			'lang' => array(
				'or'				=> $this->EE->lang->line('or'), 
				'resize_image' 		=> $this->EE->lang->line('resize_image'), 
				'return_to_publish' => $this->EE->lang->line('return_to_publish')
			)
		);

		$script_base = $this->EE->functions->fetch_site_index(0,0).QUERY_MARKER.'ACT=jquery';
		
		if ($include_jquery_base)
		{
			$ret['str'] .= '<script type="text/javascript" charset="utf-8" src="'.$script_base.'"></script>';
		}

		$live_url =  ($this->EE->TMPL->fetch_param('use_live_url') != 'no') ? AMP.'use_live_url=y' : '';

		$ret['str'] .= '<script type="text/javascript" charset="utf-8" src="'.$this->EE->functions->fetch_site_index(0,0).QUERY_MARKER.'ACT=saef'.$live_url.'"></script>';

		return $ret;
	}
	
	// --------------------------------------------------------------------
	
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
		
		$type = $this->EE->input->get('action');
		
		switch($type)
		{
			case 'setup':
				$this->setup();
				break;
			case 'setup_upload':
				$this->setup_upload();
				break;
			case 'directory':
				$this->directory($this->EE->input->get('directory'), TRUE);
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
				$this->upload_file($this->EE->input->get_post('upload_dir'), FALSE, TRUE);
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
	
	// --------------------------------------------------------------------
	
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
	
	// --------------------------------------------------------------------
	
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
		$vars = array();
		
		if (REQ != 'CP')
		{
			$this->EE->load->helper('form');
			$action_id = '';
			
			$this->EE->db->select('action_id');
			$this->EE->db->where('class', 'Channel'); 
			$this->EE->db->where('method', 'filemanager_endpoint'); 
			$query = $this->EE->db->get('actions');
			
			if ($query->num_rows() > 0)
			{
				$row = $query->row();
				$action_id = $row->action_id;
			}

			$vars['filemanager_backend_url'] = str_replace('&amp;', '&', $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER).'ACT='.$action_id;
		}
		else
		{
			$vars['filemanager_backend_url'] = $this->EE->cp->get_safe_refresh();
		}

		unset($_GET['action']);	// current url == get_safe_refresh()
		
		$vars['filemanager_directories'] = $this->directories(FALSE);

		// Generate the filters
		// $vars['selected_filters'] = form_dropdown('selected', array('all' => lang('all'), 'selected' => lang('selected'), 'unselected' => lang('unselected')), 'all');
		// $vars['category_filters'] = form_dropdown('category', array());
		$vars['view_filters']     = form_dropdown('view_type', array('list' => lang('list'), 'thumb' => lang('thumbnails')), 'list', 'id="view_type"');

		$filebrowser_html = $this->EE->load->ee_view('_shared/file/browser', $vars, TRUE);
		
		die($this->EE->javascript->generate_json(array(
			'manager'		=> str_replace(array("\n", "\t"), '', $filebrowser_html),	// reduces transfer size
			'directories'	=> $vars['filemanager_directories']
		)));
	}

	// --------------------------------------------------------------------
	
	public function setup_upload()
	{
		$base = (defined('BASE')) ? BASE : $this->EE->functions->fetch_site_index(0,0).QUERY_MARKER; 
		
		$vars = array(
			'base_url'	=> $base.AMP.'C=content_files_modal'
		);
		
		$this->EE->output->send_ajax_response(array(
			'uploader'	=> $this->EE->load->ee_view('_shared/file_upload/upload_modal', $vars, TRUE)
		));
	}
	
	// --------------------------------------------------------------------
	
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
	function directory($dir_id, $ajax = FALSE, $return_all = FALSE)
	{
		$return_all = ($ajax) ? FALSE : $return_all;		// safety - ajax calls can never get all info!
		
		$dirs = $this->directories(FALSE, $return_all);

		$return = isset($dirs[$dir_id]) ? $dirs[$dir_id] : FALSE;
		
		if ($ajax)
		{
			die($this->EE->javascript->generate_json($return));
		}
		
		return $return;
	}
	
	// --------------------------------------------------------------------
	
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
	function directories($ajax = FALSE, $return_all = FALSE)
	{
		static $dirs;
		$return = array();
		
		if ($ajax === FALSE)
		{
			$this->_initialize($this->config);
		}
		
		if ( ! is_array($dirs))
		{
			$dirs = call_user_func($this->config['directories_callback']);
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
			$this->EE->output->send_ajax_response($return);
		}
		
		return $return;
	}
	
	// --------------------------------------------------------------------
	
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
		$dir_id = $this->EE->input->get('directory_id');
		$dir = $this->directory($dir_id, FALSE, TRUE);
		
		$offset	= $this->EE->input->get('offset');
		$limit	= $this->EE->input->get('limit');

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
			echo $this->EE->javascript->generate_json($data, TRUE);
		}
		exit;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get the quantities for both files and images within a directory
	 */	
	function directory_info()
	{
		$dir_id = $this->EE->input->get('directory_id');
		$dir = $this->directory($dir_id, FALSE, TRUE);

		$data = $dir ? call_user_func($this->config['directory_info_callback'], $dir) : array();
		
		if (count($data) == 0)
		{
			echo '{}';
		}
		else
		{
			$data['id'] = $dir_id;
			echo $this->EE->javascript->generate_json($data, TRUE);
		}
		exit;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get the file information for an individual file (by ID)
	 */
	function file_info()
	{
		$file_id = $this->EE->input->get('file_id');
		
		$data = $file_id ? call_user_func($this->config['file_info_callback'], $file_id) : array();
		
		if (count($data) == 0)
		{
			echo '{}';
		}
		else
		{
			echo $this->EE->javascript->generate_json($data, TRUE);
		}
		exit;
	}
	
	// --------------------------------------------------------------------
	
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
		$dir = $this->directory($dir_id, FALSE, TRUE);
		
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
	
	// --------------------------------------------------------------------
	
	/**
	 * Set Image Memory for Image Resizing
	 *
	 * Sets memory limit for image manipulation
	 *  See // http://php.net/manual/en/function.imagecreatefromjpeg.php#64155
	 *
	 * @access	public
	 * @param	string	file path
	 * @return	bool	success / failure
	 */
	function set_image_memory($filename)
	{
		$k64 = 65536;    // number of bytes in 64K

  		$image_info = getimagesize($filename);

		// Channel may not be set for pngs - so we default to highest
		$image_info['channels'] = ( ! isset($image_info['channels'])) ? 4 : $image_info['channels'];

		$memory_needed = round(($image_info[0] * $image_info[1]
											* $image_info['bits']
											* $image_info['channels'] / 8
											+ $k64
								) * $this->_memory_tweak_factor
                         );

		$memory_setting = (ini_get('memory_limit') != '') ? intval(ini_get('memory_limit')) : 8;
		$current = $memory_setting*1024*1024;

		if (function_exists('memory_get_usage'))
		{
			if ((memory_get_usage() + $memory_needed) > $current)
			{
				// There was a bug/behavioural change in PHP 5.2, where numbers over one million get output
				// into scientific notation.  number_format() ensures this number is an integer
				// http://bugs.php.net/bug.php?id=43053
			
				$new_memory = number_format(ceil(memory_get_usage() + $memory_needed + $current), 0, '.', '');
			
				if ( ! ini_set('memory_limit', $new_memory))
				{
					return FALSE;
				}
			
				return TRUE;
			}
			
			return TRUE;
		}
		elseif ($memory_needed < $current)
		{
			// Note- this is not tremendously accurate
			return TRUE;
		}

		return FALSE;
	}



	// --------------------------------------------------------------------
	
	/**
	 * Create Thumbnails
	 *
	 * Create Thumbnails for a file
	 *
	 * @access	public
	 * @param	string	file path
	 * @param	array	file and directory information
	 * @return	bool	success / failure
	 */
	function create_thumb($file_path, $prefs, $thumb = TRUE, $missing_only = FALSE)
	{
		$this->EE->load->library('image_lib');
		$this->EE->load->helper('file');
		
		$img_path = rtrim($prefs['server_path'], '/').'/';
		$source = $file_path;
		
		if ( ! isset($prefs['mime_type']))
		{
			// Figure out the mime type
			$prefs['mime_type'] = get_mime_by_extension($file_path);
		}
		
		if ( ! $this->is_editable_image($file_path, $prefs['mime_type']))
		{
			return FALSE;
		}
		
		// Make sure we have enough memory to process
		if ( ! $this->set_image_memory($file_path))
		{
			log_message('error', 'Insufficient Memory for Thumbnail Creation: '.$file_path);
			return FALSE;
		}

		$dimensions = $prefs['dimensions'];
		
		if ($thumb)
		{
			$dimensions[] = array(
				'short_name'	=> 'thumbs',
				'width'			=> 73,
				'height'		=> 60,
				'watermark_id'	=> 0
			);
		}
			
		$protocol = $this->EE->config->item('image_resize_protocol');
		$lib_path = $this->EE->config->item('image_library_path');
		
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
			$this->EE->image_lib->clear();
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
			
			// Does the thumb image exist - nuke it!
			if (file_exists($resized_path.$prefs['file_name']))
			{
				if ($missing_only)
				{
					continue;
				}
				
				@unlink($resized_path.$prefs['file_name']);
			}		

			// In the event that the size doesn't have a valid height and width, move on
			if ($size['width'] <= 0 && $size['height'] <= 0)
			{
				continue;
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
			
			// If the original is smaller than the thumb hxw, we'll make a copy rather than upsize
			if (($force_master_dim == 'height' && $prefs['height'] < $size['height']) OR 
				($force_master_dim == 'width' && $prefs['width'] < $size['width']) OR
				($force_master_dim == FALSE && $prefs['width'] < $size['width']) OR 
				($force_master_dim == FALSE && $prefs['height'] < $size['height']))
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
				}
				elseif ($prefs['height'] > $prefs['width'])
				{
					$config['height'] = round($pref['height'] * $size['width'] / $prefs['width']);
				}
				
				// First resize down to smallest possible size (greater of height and width)
				$this->EE->image_lib->initialize($config);
				
				if ( ! $this->EE->image_lib->resize())
				{
					return FALSE;
				}
				
				// Next set crop accordingly
				$resized_image_dimensions = $this->get_image_dimensions($resized_path.$prefs['file_name']);
				$config['source_image'] = $resized_path.$prefs['file_name'];
				$config['x_axis'] = (($resized_image_dimensions['width'] / 2) - ($config['width'] / 2));
				$config['y_axis'] = (($resized_image_dimensions['height'] / 2) - ($config['height'] / 2));
				$config['maintain_ratio'] = FALSE;
				
				// Change height and width back to the desired size
				$config['width'] = $size['width'];
				$config['height'] = $size['height'];
				
				$this->EE->image_lib->initialize($config);

				if ( ! @$this->EE->image_lib->crop())
				{
					return FALSE;
				}
			}
			else
			{
				$this->EE->image_lib->initialize($config);
				
				if ( ! $this->EE->image_lib->resize())
				{
					return FALSE;
				}
			}

			@chmod($config['new_image'], DIR_WRITE_MODE);
			
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
	
	// --------------------------------------------------------------------

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
		$this->EE->image_lib->clear();
		
		$config = $this->set_image_config($data, 'watermark');
		$config['source_image'] = $image_path;
		
		$this->EE->image_lib->initialize($config);
			
		// watermark it!
			
		if ( ! $this->EE->image_lib->watermark())
		{
			return FALSE;
		}

		$this->EE->image_lib->clear();
		
		return TRUE;
	}


	// --------------------------------------------------------------------

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
		$data = array('name' => $this->EE->input->get_post('image'));
		$dir = $this->directory($this->EE->input->get_post('dir'), FALSE, TRUE);

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

	// --------------------------------------------------------------------

	/**
	 * Get's the thumbnail for a particular image in a directory
	 * This assumes the thumbnail has already been created
	 * 
	 * @param array $file Response from save_file, should be an associative array
	 * 	and minimally needs to contain the file_name and the mime_type/file_type
	 * 	Optionally, you can use the file name in the event you don't have the
	 * 	full response from save_file
	 * @param integer $directory_id The ID of the upload directory the file is in
	 * @return string URL to the thumbnail
	 */
	public function get_thumb($file, $directory_id)
	{
		$directory = $this->fetch_upload_dir_prefs($directory_id);
		$thumb_info = array();
		
		// If the raw file name was passed in, figure out the mime_type
		if ( ! is_array($file) OR ! isset($file['mime_type']))
		{
			$this->EE->load->helper('file');
			
			$file = array(
				'file_name' => $file,
				'mime_type' => get_mime_by_extension($file)
			);
		}
		
		// If it's an image, use it's thumbnail, otherwise use the default
		if ($this->is_image($file['mime_type']))
		{
			$site_url = str_replace('index.php', '', $this->EE->config->site_url());

			$thumb_info['thumb'] = $directory['url'].'_thumbs/'.$file['file_name'];
			$thumb_info['thumb_path'] = $directory['server_path'] . '_thumbs/' . $file['file_name'];
			$thumb_info['thumb_class'] = 'image';
		}
		else
		{
			$thumb_info['thumb'] = PATH_CP_GBL_IMG.'default.png';
			$thumb_info['thumb_path'] = '';
			$thumb_info['thumb_class'] = 'no_image';
		}
		
		return $thumb_info;
	}

	// --------------------------------------------------------------------

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
		
		$this->EE->load->helper('directory');
		$map = directory_map($thumb_path, TRUE);

		foreach($files as $key => &$file)
		{
			// Hide the thumbs directory
			if ($file['file_name'] == '_thumbs' OR ! $file['mime_type'] /* skips folders */)
			{
				unset($files[$key]);
				continue;
			}
			
			$file['date'] = $this->EE->localize->set_human_time($file['modified_date'], TRUE);
			//$file['size'] = number_format($file['file_size']/1000, 1).' '.lang('file_size_unit');
			$file['has_thumb'] = (in_array('thumb_'.$file['file_name'], $map));
		}

		// if we unset a directory in the loop above our
		// keys are no longer sequential and json won't turn
		// into an array (which is what we need)
		return array_values($files);
	}
	


	function sync_database($dir_id)
	{
		$db_files = array();
		$server_files = array();
		
		$query = $this->EE->file_model->get_files_by_dir($dir_id);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		foreach ($query->result_array() as $row)
		{
			$db_files[$row['file_id']] = $row['file_name'];
		}
		
		$query = $this->EE->file_upload_preferences_model->get_upload_preferences(1, $dir_id);
		
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
		
   		$d_row = $query->row();
		
		$server_files = $this->directory_files_map($d_row->server_path, 0, FALSE, $d_row->allowed_types);
		
		// get file names in db that are not on server
		$delete = array_diff($db_files, $server_files);
		
		if (count($delete))
		{
			$this->EE->file_model->delete_files(array_keys($delete));
		}
	}
	

	function set_image_config($data, $type = 'watermark')
	{
		$config = array();
		
		if ($type == 'watermark')
		{
			// Verify the watermark settings actually exist
			if ( ! isset($data['wm_type']) AND isset($data['watermark_id']))
			{
				$this->EE->load->model('file_model');
				$qry = $this->EE->file_model->get_watermark_preferences($data['watermark_id']);
				$qry = $qry->row_array();
				$data = array_merge($data, $qry);
			}
			
			$wm_prefs = array('source_image', 'padding', 'wm_vrt_alignment', 'wm_hor_alignment', 
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


	// --------------------------------------------------------------------
	
	// --------------------------------------------------------------------
	//	Default Callbacks
	// --------------------------------------------------------------------
	
	/**
	 * Directories Callback
	 *
	 * The function that retrieves the actual directory information
	 *
	 * @access	private
	 * @return	mixed	directory list
	 */
	function _directories()
	{
		$dirs = array();
		$this->EE->load->model('file_upload_preferences_model');
		
		$query = $this->EE->file_upload_preferences_model->get_upload_preferences($this->EE->session->userdata('group_id'));
		
		foreach($query->result_array() as $dir)
		{
			$dirs[$dir['id']] = $dir;
		}
		
		return $dirs;
	}
	
	// --------------------------------------------------------------------
	
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
			'files' => $this->_get_files($dir, $limit, $offset)
		);
	}
	

	// --------------------------------------------------------------------

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
	private function _get_files($dir, $limit = 15, $offset = 0)
	{
		$this->EE->load->model('file_model');
		$this->EE->load->helper(array('text', 'number'));
		
		$files = $this->EE->file_model->get_files(
			$dir['id'], 
			array(
				'type' => $dir['allowed_types'],
				'order' => array(
					'file_name' => 'asc'
				),
				'limit' => $limit,
				'offset' => $offset
			)
		);

		if ($files['results'] === FALSE)
		{
			return array();
		}

		$files = $files['results']->result_array();

		foreach ($files as &$file)
		{
			$file['short_name'] = ellipsize($file['title'], 13, 0.5);
			$file['file_size'] = byte_format($file['file_size']);
			$file['date'] = date('F j, Y g:i a', $file['modified_date']);
			
			// Copying file_name to name for addons
			$file['name'] = $file['file_name'];
			
			$thumb_info = $this->get_thumb($file, $dir['id']);
			$file['thumb'] = $thumb_info['thumb'];
			$file['thumb_class'] = $thumb_info['thumb_class'];
		}

		return $files;
	}

	// --------------------------------------------------------------------
	
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
		$this->EE->load->helper('form');

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

	
	// --------------------------------------------------------------------
	
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

		$this->EE->load->model(array('file_upload_preferences_model', 'category_model'));

		$category_group_ids = $this->EE->file_upload_preferences_model->get_upload_preferences($dir['id']);
		$category_group_ids = explode('|', $category_group_ids->row('cat_group'));

		if (count($category_group_ids) > 0 AND $category_group_ids[0] != '')
		{
			foreach ($category_group_ids as $category_group_id)
			{
				$category_group_info = $this->EE->category_model->get_category_groups($category_group_id);
				$categories[$category_group_id] = $category_group_info->row_array();
				$categories_for_group = $this->EE->category_model->get_channel_categories($category_group_id);
				$categories[$category_group_id]['categories'] = $categories_for_group->result_array();
			}
		}

		return $categories;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Directory Info Callback
	 * 
	 * Returns the file count, image count and url of the directory
	 * 
	 * @param array $dir Directory info associative array
	 */
	private function _directory_info($dir)
	{
		$this->EE->load->model('file_model');
		
		return array(
			'url' 			=> $dir['url'],
			'file_count'	=> $this->EE->file_model->count_files($dir['id']),
			'image_count'	=> $this->EE->file_model->count_images($dir['id'])
		);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * File Info Callback
	 * 
	 * Returns the file information for use when placing a file
	 * 
	 * @param integer $file_id The File's ID
	 */
	private function _file_info($file_id)
	{
		$this->EE->load->model('file_model');
		
		$file_info = $this->EE->file_model->get_files_by_id($file_id);
		$file_info = $file_info->row_array();
		
		$file_info['is_image'] = (strncmp('image', $file_info['mime_type'], '5') == 0) ? TRUE : FALSE;
		
		$thumb_info = $this->get_thumb($file_info['file_name'], $file_info['upload_location_id']);
		$file_info['thumb'] = $thumb_info['thumb'];
		
		return $file_info;
	}
	
	// --------------------------------------------------------------------
	
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
		
		// Restricted upload directory?
		switch($dir['allowed_types'])
		{
			case 'all': 
				$allowed_types = '*';
				break;
				
			case 'img': 
				$allowed_types = 'gif|jpg|jpeg|png|jpe';
				break;
				
			default: 
				$allowed_types = '';
		}
		
		// Is this a custom field?
		if (strpos($field_name, 'field_id_') === 0)
		{
			$field_id = str_replace('field_id_', '', $field_name);
		
			$this->EE->db->select('field_type, field_settings');
			$type_query = $this->EE->db->get_where('channel_fields', array('field_id' => $field_id));
		
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
			array('ignore_dupes' => FALSE)
		));
		
		$config = array(
			'file_name'		=> $clean_filename,
			'upload_path'	=> $dir['server_path'],
			'allowed_types'	=> $allowed_types,
			'max_size'		=> round($dir['max_size']/1024, 2)
		);
		
		$this->EE->load->helper('xss');
		
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
		
		// Upload the file
		$this->EE->load->library('upload');
		$this->EE->upload->initialize($config);
		
		if ( ! $this->EE->upload->do_upload($field_name))
		{
			return $this->_upload_error(
				$this->EE->upload->display_errors()
			);
		}

		$file = $this->EE->upload->data();
		
		// (try to) Set proper permissions
		@chmod($file['full_path'], DIR_WRITE_MODE);
		

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
			'site_id'				=> $this->EE->config->item('site_id'),
			
			'file_name'				=> $file['file_name'],
			'orig_name'				=> $original_filename,
			
			'is_image'				=> $file['is_image'],
			'mime_type'				=> $file['file_type'],
			
			'rel_path'				=> $file['full_path'],
			'file_thumb'			=> $thumb_info['thumb'],
			'thumb_class' 			=> $thumb_info['thumb_class'],
		
			'modified_by_member_id' => $this->EE->session->userdata('member_id'),
			'uploaded_by_member_id'	=> $this->EE->session->userdata('member_id'),
			
			'file_size'				=> $file['file_size'] * 1024, // Bring it back to Bytes from KB
			'file_height'			=> $file['image_height'],
			'file_width'			=> $file['image_width'],
			'file_hw_original'		=> $file['image_height'].' '.$file['image_width'],
			'max_width'				=> $dir['max_width'],
			'max_height'			=> $dir['max_height']
		);
		
		
		// Check to see if its an editable image, if it is, check max h/w
		if ($this->is_editable_image($file['full_path'], $file['file_type']))
		{
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
		
		// Set file id in return data
		$file_data['file_id'] = $saved['file_id'];
		
		// Stash upload directory prefs in case
		$file_data['upload_directory_prefs'] = $dir;
		$file_data['directory'] = $dir['id'];
		
		// Manually create a modified date
		$file_data['modified_date'] = $this->EE->localize->set_human_time();
		
		// Change file size to human readable
		$this->EE->load->helper('number');
		$file_data['file_size'] = byte_format($file_data['file_size']);
		
		return $file_data;
	}

	// --------------------------------------------------------------------
	
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
			$this->EE->load->model('file_model');
			$this->EE->file_model->delete_files($file_info['file_id']);
		}
		else if (isset($file_info['file_name']) AND isset($file_info['directory_id']))
		{
			$this->EE->load->model('file_model');
			$this->EE->file_model->delete_raw_file($file_info['file_name'], $file_info['directory_id']);
		}
		
		return array('error' => $error_message);
	}
	
	// --------------------------------------------------------------------

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
		$this->EE->load->model(array('file_upload_preferences_model', 'file_model'));
		
		$replace = FALSE;
		
		// Get the file data form the database
		$previous_data = $this->EE->file_model->get_files_by_id($file_id);
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
				$replace_data = $this->EE->file_model->get_files_by_name($new_file_name, $directory_id);
				
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
		$this->EE->file_model->delete_raw_file($old_file_name, $directory_id, TRUE);
		
		// Rename the actual file
		$file_path = $this->_rename_raw_file(
			$old_file_name,
			$new_file_name,
			$directory_id
		);

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
			'rel_path'	=> str_replace($old_file_name, $new_file_name, $previous_data->rel_path)
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

	// --------------------------------------------------------------------

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
		$existing_file = $this->EE->file_model->get_files_by_name($file_name, $directory_id);
		$existing_file = $existing_file->row();
		
		// Delete the existing file's raw files, but leave the database record
		$this->EE->file_model->delete_raw_file($file_name, $directory_id);
		
		// Delete the new file's database record, but leave the files
		$this->EE->file_model->delete_files($new_file->file_id, FALSE);
				
		// Update file_hw_original, filesize, modified date and modified user
		$this->EE->file_model->save_file(array(
			'file_id'				=> $existing_file->file_id, // Use the old file_id
			'file_size'				=> $new_file->file_size,
			'file_hw_original'		=> $new_file->file_hw_original,
			'modified_date'			=> $new_file->modified_date,
			'modified_by_member_id'	=> $this->EE->session->userdata('member_id')
		));
		$existing_file->file_size				= $new_file->file_size;
		$existing_file->file_hw_original		= $new_file->file_hw_original;
		$existing_file->modified_date			= $new_file->modified_date;
		$existing_file->modified_by_member_id 	= $this->EE->session->userdata('member_id');
		
		return $existing_file;
	}

	// --------------------------------------------------------------------

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
			'allowed_types'	=> ($this->EE->session->userdata('group_id') == 1) ? 'all' : $upload_directory['allowed_types'],
			'max_size'		=> round($upload_directory['max_size']/1024, 2),
			'max_width'		=> $upload_directory['max_width'],
			'max_height'	=> $upload_directory['max_height']
		);
		
		$this->EE->load->library('upload', $config);
		
		if ( ! $this->EE->upload->file_overwrite($old_file_name, $new_file_name))
		{
			return array('error' => $this->EE->upload->display_errors());
		}
		
		return $upload_directory['server_path'] . $new_file_name;
    }

	// --------------------------------------------------------------------

	/**
	 * Handle the edit actions
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function edit_image()
	{
		$this->EE->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
		$this->EE->output->set_header("Pragma: no-cache");

		$this->EE->load->library('encrypt');

		$file = str_replace(DIRECTORY_SEPARATOR, '/', $this->EE->encrypt->decode(rawurldecode($this->EE->input->get_post('file')), $this->EE->session->sess_crypt_key));

		if ($file == '')
		{
			// nothing for you here
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('choose_file'));
			$this->EE->functions->redirect(BASE.AMP.'C=content_files');
		}

		// crop takes precendence over resize
		// we need at least a width
		if ($this->EE->input->get_post('crop_width') != '' AND $this->EE->input->get_post('crop_width') != 0)
		{

			$config['width'] = $this->EE->input->get_post('crop_width');
			$config['maintain_ratio'] = FALSE;
			$config['x_axis'] = $this->EE->input->get_post('crop_x');
			$config['y_axis'] = $this->EE->input->get_post('crop_y');
			$action = 'crop';

			if ($this->EE->input->get_post('crop_height') != '')
			{
				$config['height'] = $this->EE->input->get_post('crop_height');
			}
			else
			{
				$config['master_dim'] = 'width';
			}
		}
		elseif ($this->EE->input->get_post('resize_width') != '' AND $this->EE->input->get_post('resize_width') != 0)
		{
			$config['width'] = $this->EE->input->get_post('resize_width');
			$config['maintain_ratio'] = $this->EE->input->get_post("constrain");
			$action = 'resize';

			if ($this->EE->input->get_post('resize_height') != '')
			{
				$config['height'] = $this->EE->input->get_post('resize_height');
			}
			else
			{
				$config['master_dim'] = 'width';
			}
		}
		elseif ($this->EE->input->get_post('rotate') != '' AND $this->EE->input->get_post('rotate') != 'none')
		{
			$action = 'rotate';
			$config['rotation_angle'] = $this->EE->input->get_post('rotate');
		}
		else
		{
			if ($this->EE->input->get_post('is_ajax'))
			{
				header('HTTP', true, 500);
				exit($this->EE->lang->line('width_needed'));
			}
			else
			{
				show_error($this->EE->lang->line('width_needed'));
			}
		}

		$config['image_library'] = $this->EE->config->item('image_resize_protocol');
		$config['library_path'] = $this->EE->config->item('image_library_path');
		$config['source_image'] = $file;

		$path = substr($file, 0, strrpos($file, '/')+1);
		$filename = substr($file, strrpos($file, '/')+1, -4); // All editable images have 3 character file extensions
		$file_ext = substr($file, -4); // All editable images have 3 character file extensions

		$image_name_reference = $filename.$file_ext;

		if ($this->EE->input->get_post('source') == 'resize_orig')
		{
			$config['new_image'] = $file;
		}
		else
		{
			// Add to db using save- becomes a new entry
			
			$new_filename = '';
			
			$thumb_suffix = $this->EE->config->item('thumbnail_prefix');
			
			if ( ! file_exists($path.$filename.'_'.$thumb_suffix.$file_ext))
			{
				$new_filename = $filename.'_'.$thumb_suffix.$file_ext;
			}
			else
			{
				for ($i = 1; $i < 100; $i++)
				{			
					if ( ! file_exists($path.$filename.'_'.$thumb_suffix.'_'.$i.$file_ext))
					{
						$new_filename = $filename.'_'.$thumb_suffix.'_'.$i.$file_ext;
						break;
					}
				}				
			}

			$image_name_reference = $new_filename;
			$config['new_image'] = $new_filename;
		}

//		$config['dynamic_output'] = TRUE;

		$this->EE->load->library('image_lib', $config);

		$errors = '';

		// Cropping and Resizing
		if ($action == 'resize')
		{
			if ( ! $this->EE->image_lib->resize())
			{
		    	$errors = $this->EE->image_lib->display_errors();
			}
		}
		elseif ($action == 'rotate')
		{

			if ( ! $this->EE->image_lib->rotate())
			{
			    $errors = $this->EE->image_lib->display_errors();
			}
		}
		else
		{
			if ( ! $this->EE->image_lib->crop())
			{
			    $errors = $this->EE->image_lib->display_errors();
			}
		}

		// Any reportable errors? If this is coming from ajax, just the error messages will suffice
		if ($errors != '')
		{
			if ($this->EE->input->get_post('is_ajax'))
			{
				header('HTTP', true, 500);
				exit($errors);
			}
			else
			{
				show_error($errors);
			}
		}

		$dimensions = $this->EE->image_lib->get_image_properties('', TRUE);
		$this->EE->image_lib->clear();

		// Rebuild thumb
		$this->create_thumb(
						array('server_path'	=> $path), 
						array('name'		=> $image_name_reference)
					);


		exit($image_name_reference);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Fetch Upload Directories
	 *
	 * self::_directories() caches upload dirs in _upload_dirs, so we don't
	 * query twice if we don't need to.
	 *
	 * @return array
	 */
	public function fetch_upload_dirs()
	{
		if ( ! empty($this->_upload_dirs))
		{
			return $this->_upload_dirs;
		}
		
		return $this->_directories();
	}

	// --------------------------------------------------------------------	
	
	/**
	 *
	 *
	 */
	public function fetch_files($file_dir_id = NULL, $files = array(), $get_dimensions = FALSE)
	{
		$this->EE->load->model('file_upload_preferences_model');

		$upload_dirs = $this->EE->file_upload_preferences_model->get_upload_preferences(
										$this->EE->session->userdata('group_id'),
										$file_dir_id);
		
		$dirs = new stdclass();
		$dirs->files = array();
		
		foreach ($upload_dirs->result() as $dir)
		{
			$dirs->files[$dir->id] = array();
			
			$files = $this->EE->file_model->get_raw_files($dir->server_path, $dir->allowed_types, '', false, $get_dimensions, $files);
			
			foreach ($files as $file)
			{
				$dirs->files[$dir->id] = $files;
			}
		}
	
		return $dirs;
	}
	
	// --------------------------------------------------------------------	

	function directory_files_map($source_dir, $directory_depth = 0, $hidden = false, $allowed_types = 'all')
	{
		$this->EE->load->helper('file');

		if ($allowed_types == 'img')
		{
			$allowed_type = array('image/gif','image/jpeg','image/png');
		}
		elseif ($allowed_types == 'all')
		{
			$allowed_type = array();
		}

		if ($fp = @opendir($source_dir))
		{
			$filedata	= array();
			$new_depth	= $directory_depth - 1;
			$source_dir	= rtrim($source_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
			
			while (FALSE !== ($file = readdir($fp)))
			{
				// Remove '.', '..', and hidden files [optional]
				if ( ! trim($file, '.') OR ($hidden == FALSE && $file[0] == '.'))
				{
					continue;
				}
				
				if ( ! is_dir($source_dir.$file))
				{
					if ( ! empty($allowed_type))
					{
						$mime = get_mime_by_extension($file);
						
						//echo $mime;
						
						if ( ! in_array($mime, $allowed_type))
						{
							continue;
						}
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
	
	// --------------------------------------------------------------------
	
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
		
		if (count($files) === 1)
		{
			// Get the file Location:
			$qry = $this->EE->db->select('rel_path, file_name, 	server_path')
								->from('files')
								->join('upload_prefs', 'upload_prefs.id = files.upload_location_id')
								->where('file_id', $files[0])
								->get();
			
			$file_path = $this->EE->functions->remove_double_slashes($qry->row('server_path').DIRECTORY_SEPARATOR.$qry->row('file_name'));
			
			if ( ! file_exists($file_path))
			{
				return FALSE;
			}

			$file = file_get_contents($file_path);
			$file_name = $qry->row('file_name');

			$this->EE->load->helper('download');
			force_download($file_name, $file);

			return TRUE;
		}
		
		// Zip up a bunch of files for download
		$this->EE->load->library('zip');

		$qry = $this->EE->db->select('rel_path, file_name, 	server_path')
								->from('files')
								->join('upload_prefs', 'upload_prefs.id = files.upload_location_id')
								->where_in('file_id', $files)
								->get();
		
		
		if ($qry->num_rows() === 0)
		{
			return FALSE;
		}

		
		foreach ($qry->result() as $row)
		{
			$file_path = $this->EE->functions->remove_double_slashes($row->server_path.DIRECTORY_SEPARATOR.$row->file_name);
			$this->EE->zip->read_file($file_path);
		}

		$this->EE->zip->download($zip_name);
		
		return TRUE;
	}

	// --------------------------------------------------------------------		

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
		$this->EE->load->library('image_lib');

		return $this->EE->image_lib->get_image_properties($file, TRUE);
	}

	// --------------------------------------------------------------------		
	
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
		// IE will sometimes return odd mime-types during upload, 
		// so here we just standardize all jpegs or pngs to the same file type.

		$png_mimes  = array('image/x-png');
		$jpeg_mimes = array('image/jpg', 'image/jpe', 'image/jpeg', 'image/pjpeg');

		if (in_array($mime, $png_mimes))
		{
			$mime = 'image/png';
		}

		if (in_array($mime, $jpeg_mimes))
		{
			$mime = 'image/jpeg';
		}

		$img_mimes = array(
							'image/gif',
							'image/jpeg',
							'image/png',
						);

		return (in_array($mime, $img_mimes, TRUE)) ? TRUE : FALSE;
	}
	
	// --------------------------------------------------------------------		
	
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
	
	// ------------------------------------------------------------------------
	
	/**
	 * image processing
	 *
	 * Figures out the full path to the file, and sends it to the appropriate
	 * method to process the image.
	 */
	public function _do_image_processing($redirect = TRUE)
	{
		$file_id = $this->EE->input->post('file_id');
		
		// Check to see if a file was actually sent...
		if ( ! ($file_name = $this->EE->input->post('file_name')))
		{
			$this->EE->session->set_flashdata('message_failure', lang('choose_file'));
			$this->EE->functions->redirect(BASE.AMP.'C=content_files');
		}
		
		// Get the upload directory preferences
		$upload_dir_id = $this->EE->input->post('upload_dir');
		$upload_prefs = $this->fetch_upload_dir_prefs($upload_dir_id);

		// Clean up the filename and add the full path
		$file_name = $this->EE->security->sanitize_filename(urldecode($file_name));
		$file_path = $this->EE->functions->remove_double_slashes(
			$upload_prefs['server_path'].DIRECTORY_SEPARATOR.$file_name
		);
		
		// Where are we going with this?
		switch ($this->EE->input->post('action'))
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
		
		// Alright, what did we break?
		if (isset($response['errors']))
		{
			if (AJAX_REQUEST)
			{
				$this->EE->output->send_ajax_response($response['errors'], TRUE);
			}

			show_error($response['errors']);
		}
		
		$this->EE->load->model('file_model');
		
		// die(var_dump($response));
		// Update database
		$this->EE->file_model->save_file(array(
			'file_id' => $file_id,
			'file_hw_original' => $response['dimensions']['height'] . ' ' . $response['dimensions']['width'],
			'file_size' => $response['file_info']['size']
		));
		
		// Get dimensions for thumbnail
		$dimensions = $this->EE->file_model->get_dimensions_by_dir_id($upload_dir_id);
		$dimensions = $dimensions->result_array();
		
		// Regenerate thumbnails
		$this->create_thumb(
			$file_path,
			array(
				'server_path' => $upload_prefs['server_path'],
				'file_name'  => basename($file_name),
				'dimensions' => $dimensions
			)
		);
		
		// If we're redirecting send em on
		if ($redirect === TRUE)
		{
			// Send the dimensions back for Ajax requests
			if (AJAX_REQUEST)
			{
				$this->EE->output->send_ajax_response(array(
					'width'		=> $response['dimensions']['width'],
					'height'	=> $response['dimensions']['height']
				));
			}

			// Otherwise redirect
			$this->EE->session->set_flashdata('message_success', lang('file_saved'));
			$this->EE->functions->redirect(
				BASE.AMP.
				'C=content_files'.AMP.
				'M=edit_image'.AMP.
				'upload_dir='.$this->EE->input->post('upload_dir').AMP.
				'file_id='.$this->EE->input->post('file_id')
			);
		}
		// Otherwise return the response from the called method
		else
		{
			return $response;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Image crop
	 */
	private function _do_crop($file_path)
	{
		$config = array(
			'width'				=> $this->EE->input->post('crop_width'),
			'maintain_ratio'	=> FALSE,
			'x_axis'			=> $this->EE->input->post('crop_x'),
			'y_axis'			=> $this->EE->input->post('crop_y'),
			'height'			=> ($this->EE->input->post('crop_height')) ? $this->EE->input->post('crop_height') : NULL,
			'master_dim'		=> 'width',
			'library_path'		=> $this->EE->config->item('image_library_path'),
			'image_library'		=> $this->EE->config->item('image_resize_protocol'),
			'source_image'		=> $file_path,
			'new_image'			=> $file_path
		);

		$this->EE->load->library('image_lib', $config);

		if ( ! $this->EE->image_lib->crop())
		{
	    	$errors = $this->EE->image_lib->display_errors();
		}
		
		$reponse = array();
		
		if (isset($errors))
		{
			$response['errors'] = $errors;
		}
		else
		{
			$this->EE->load->helper('file');
			$response = array(
				'dimensions' => $this->EE->image_lib->get_image_properties('', TRUE),
				'file_info'  => get_file_info($file_path)
			);
		}
		
		$this->EE->image_lib->clear();
		
		return $response;
	}

	// ------------------------------------------------------------------------

	/**
	 * Do image rotation.
	 */
	private function _do_rotate($file_path)
	{
		$config = array(
			'rotation_angle'	=> $this->EE->input->post('rotate'),
			'library_path'		=> $this->EE->config->item('image_library_path'),
			'image_library'		=> $this->EE->config->item('image_resize_protocol'),
			'source_image'		=> $file_path,
			'new_image'			=> $file_path
		);

		$this->EE->load->library('image_lib', $config);

		if ( ! $this->EE->image_lib->rotate())
		{
	    	$errors = $this->EE->image_lib->display_errors();
		}

		$reponse = array();
		
		if (isset($errors))
		{
			$response['errors'] = $errors;
		}
		else
		{
			$this->EE->load->helper('file');
			$response = array(
				'dimensions' => $this->EE->image_lib->get_image_properties('', TRUE),
				'file_info'  => get_file_info($file_path)
			);
		}
		
		$this->EE->image_lib->clear();
		
		return $response;
	}

	// ------------------------------------------------------------------------

	/**
	 * Do image rotation.
	 */
	private function _do_resize($file_path)
	{
		$config = array(
			'width'				=> $this->EE->input->get_post('resize_width'),
			'maintain_ratio'	=> $this->EE->input->get_post('constrain'),
			'library_path'		=> $this->EE->config->item('image_library_path'),
			'image_library'		=> $this->EE->config->item('image_resize_protocol'),
			'source_image'		=> $file_path,
			'new_image'			=> $file_path
		);

		if ($this->EE->input->get_post('resize_height') != '')
		{
			$config['height'] = $this->EE->input->get_post('resize_height');
		}
		else
		{
			$config['master_dim'] = 'width';
		}

		$this->EE->load->library('image_lib', $config);

		if ( ! $this->EE->image_lib->resize())
		{
	    	$errors = $this->EE->image_lib->display_errors();
		}

		$reponse = array();
		
		if (isset($errors))
		{
			$response['errors'] = $errors;
		}
		else
		{
			$this->EE->load->helper('file');
			$response = array(
				'dimensions' => $this->EE->image_lib->get_image_properties('', TRUE),
				'file_info'  => get_file_info($file_path)
			);
		}
		
		$this->EE->image_lib->clear();
		
		return $response;
	}

	// ------------------------------------------------------------------------
}

// END Filemanager class

/* End of file Filemanager.php */
/* Location: ./system/expressionengine/libraries/Filemanager.php */