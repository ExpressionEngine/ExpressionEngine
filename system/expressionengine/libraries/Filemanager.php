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


	function clean_filename($filename, $dir_id)
	{
		$prefs = $this->fetch_upload_dir_prefs($dir_id);
		
		$i = 1;
		$ext = '';
		$path = $prefs['server_path'];
		
		// clean up the filename
		$filename = preg_replace("/\s+/", "_", $filename);
		$filename = $this->EE->security->sanitize_filename($filename);
		
		if (strpos($filename, '.') !== FALSE)
		{
			$parts		= explode('.', $filename);
			$ext		= array_pop($parts);
			
			// @todo prevent security issues with multiple extensions
			// http://httpd.apache.org/docs/1.3/mod/mod_mime.html#multipleext
			$filename	= implode('.', $parts);
		}
		
		// Figure out a unique filename
		$ext = '.'.$ext;
		$basename = $filename;
		
		while (file_exists($path.$filename.$ext))
		{
			$filename = $basename.'_'.$i++;
		}
		
		return $path.$filename.$ext;
	}

	// ---------------------------------------------------------------------
	
	function set_upload_dir_prefs($dir_id, array $prefs)
	{
		$required = array_flip(
			array('name', 'server_path', 'url', 'allowed_types')
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
		
		$this->_upload_dir_prefs[$dir_id] = $prefs;
		return $prefs;
	}
	
	// --------------------------------------------------------------------
	
	function fetch_upload_dir_prefs($dir_id)
	{
		if (isset($this->_upload_dir_prefs[$dir_id]))
		{
			return $this->_upload_dir_prefs[$dir_id];
		}
		
		$qry = $this->EE->db->where('id', $dir_id)
							->where('site_id', $this->EE->config->item('site_id'))
							->get('upload_prefs');
		
		if ( ! $qry->num_rows())
		{
			return FALSE;
		}
		
		$prefs = $qry->row_array();
		$qry->free_result();
		
		
		// Add dimensions
		$prefs['dimensions'] = array();
		
		/*
		$qry = $this->EE->db->from('file_dimensions')
							->where('upload_location_id', $dir_id)
							->join('file_watermarks', 'wm_id = watermark_id', 'left')
							->get_where('file_dimensions');
		*/

		$qry = $this->EE->db->select('*')
						->from('file_dimensions')
						->join('file_watermarks', 'wm_id = watermark_id', 'left')
						->where_in('upload_location_id', $dir_id)
						->get();							
							
		
		foreach ($qry->result_array() as $row)
		{
			$prefs['dimensions'][$row['id']] = array(
				'short_name'	=> $row['short_name'],
				'width'			=> $row['width'],
				'height'		=> $row['height'],
				'watermark_id'	=> $row['watermark_id']
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
		
		// check keys and cache
		//return $this->set_upload_dir_prefs($dir_id, $qry->row_array());
		return $this->set_upload_dir_prefs($dir_id, $prefs);
	}

	// --------------------------------------------------------------------

	/**
	 * Checks the uploaded file to make sure it's both allowed and passes 
	 *	XSS filtering
	 *
	 * TODO: Is this method used redundantly? Save_file calls this, but files
	 *	need to have the same checks before being uploaded anyways. So the
	 *	work gets done at least twice.
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
			$D = @getimagesize($this->file_temp);

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

		$prefs = array_merge($dir_prefs, $prefs);
		
		// Figure out the mime type
		$mime = $this->security_check($file_path, $prefs);

		if ($mime === FALSE)
		{
			// security check failed
			return $this->_save_file_response(FALSE, lang('security_failure'));
		}
		
		$prefs['mime_type'] = $mime;
		// Check to see if its an editable image, if it is, try and create the thumbnail
		if ($this->is_editable_image($file_path, $mime) && 
			! $this->create_thumb($file_path, $prefs))
		{
				return $this->_save_file_response(FALSE, lang('thumb_not_created'));
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
			'plugin'    => array('scrollable', 'scrollable.navigator', 'ee_filebrowser', 'ee_fileuploader')
		));

		$this->EE->cp->add_to_head('<link rel="stylesheet" href="'.BASE.AMP.'C=css'.AMP.'M=file_browser" type="text/css" media="screen" />');
		
		$this->EE->javascript->set_global('lang', array(
			'resize_image'		=> $this->EE->lang->line('resize_image'),
			'or'				=> $this->EE->lang->line('or'),
			'return_to_publish'	=> $this->EE->lang->line('return_to_publish')
		));
		
		$this->EE->javascript->set_global('filebrowser', array(
			'endpoint_url'	=> $endpoint_url,
			'window_title'	=> $this->EE->lang->line('file_manager')
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
				'window_title'	=> $this->EE->lang->line('file_manager'),
				'theme_url'		=> $this->theme_url),
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
			case 'setup':				$this->setup();
				break;
			case 'setup_upload':		$this->setup_upload();
				break;
			case 'directory':			$this->directory($this->EE->input->get('directory'), TRUE);
				break;
			case 'directories':			$this->directories(TRUE);
				break;
			case 'directory_contents':	$this->directory_contents();
				break;
			case 'upload':				$this->upload_file($this->EE->input->get_post('upload_dir'), FALSE, TRUE);
				break;
			case 'edit_image':			$this->edit_image();
				break;
			case 'ajax_create_thumb':	$this->ajax_create_thumb();
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
		foreach(array('directories', 'directory_contents', 'upload_file') as $key)
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
		if (REQ != 'CP')
		{
			$this->EE->load->_ci_view_path =  PATH_THEMES.'cp_themes/default/';
			$vars['cp_theme_url'] = $this->EE->config->slash_item('theme_folder_url').'cp_themes/default/';
			
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
		$vars['selected_filters'] = form_dropdown('selected', array('all' => 'all', 'selected' => 'selected', 'unselected' => 'unselected'), 'all');
		$vars['category_filters'] = form_dropdown('category', array());
		$vars['view_filters']     = form_dropdown('view_type', array('list' => 'a list', 'thumb' => 'thumbnails'), 'list', 'id="view_type"');

		$filebrowser_html = $this->EE->load->view('_shared/file/browser', $vars, TRUE);

		die($this->EE->javascript->generate_json(array(
			'manager'		=> str_replace(array("\n", "\t"), '', $filebrowser_html),	// reduces transfer size
			'directories'	=> $vars['filemanager_directories']
		)));
	}

	// --------------------------------------------------------------------
	
	public function setup_upload()
	{
		$vars = array(
			'base_url'	=> BASE.AMP.'C=content_files'.AMP.'M=upload_inner'
		);
		
		$this->EE->output->send_ajax_response(array(
			'uploader'	=> $this->EE->load->view('_shared/file/upload_modal', $vars, TRUE)
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
		$dir_id = $this->EE->input->get('directory');
		$dir = $this->directory($dir_id, FALSE, TRUE);

		$data = $dir ? call_user_func($this->config['directory_contents_callback'], $dir) : array();

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
	 * Upload File
	 *
	 * Upload a files
	 *
	 * @access	public
	 * @param	int		upload directory id
	 * @param	string	upload field name (optional - defaults to first upload field)
	 * @param	bool	ajax request? (optional)
	 * @return	mixed	uploaded file info
	 */
	function upload_file($dir_id = '', $field = FALSE, $ajax = FALSE)
	{
		$dir = $this->directory($dir_id, FALSE, TRUE);

		$data = array('error' => 'No File');
		
		if ( ! $dir)
		{
			$data = array('error' => "You do not have access to this upload directory.");
		}
		else if (count($_FILES) > 0)
		{
			if ( ! $field && is_array(current($_FILES)))
			{
				$field = key($_FILES);
			}
			
			if (isset($_FILES[$field]))
			{
				$data = call_user_func($this->config['upload_file_callback'], $dir, $field);
			}
		}

		if ( ! $ajax)
		{
			return $data;
		}
		
		if (array_key_exists('error', $data))
		{
			exit('<script>parent.jQuery.ee_filebrowser.upload_error('.$this->EE->javascript->generate_json($data).');</script>');
		}

		exit('<script>parent.jQuery.ee_filebrowser.upload_success('.$this->EE->javascript->generate_json($data).');</script>');
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
	function create_thumb($file_path, $prefs, $thumb = TRUE)
	{
		$this->EE->load->library('image_lib');
		
		$img_path = rtrim($prefs['server_path'], '/').'/';
		$source = $file_path;
		
		$dimensions = $prefs['dimensions'];
		
		$dimensions[0] = array(
			'short_name'	=> 'thumb',
			'width'			=> 73,
			'height'		=> 60,
			'watermark_id'	=> 0
			);
			
		$protocol = $this->EE->config->item('image_resize_protocol');
		$lib_path = $this->EE->config->item('image_library_path');
		
		foreach ($dimensions as $size_id => $size)
		{
			// In the event that the size doesn't have a valid height or width, move on
			if ($size['width'] <= 0 OR $size['height'] <= 0)
			{
				continue;
			}
			
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
				@unlink($resized_path.$prefs['file_name']);
			}		

			// Resize
		
			$config['source_image']		= $source;
			$config['new_image']		= $resized_path.$prefs['file_name'];
			$config['maintain_ratio']	= TRUE;
			$config['image_library']	= $protocol;
			$config['library_path']		= $lib_path;
			$config['width']			= $size['width'];
			$config['height']			= $size['height'];

			$this->EE->image_lib->initialize($config);

			// crop based on resize type - does anyone really crop sight unseen????
			if (isset($size['resize_type']) AND $size['resize_type'] == 'crop')
			{
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
				
				// This may need to change if we let them manuall set crop
				// For now, let's crop from center for Wes

				$config['x_axis'] = (($prefs['width'] / 2) + ($config['width'] / 2));
				$config['y_axis'] = (($prefs['height'] / 2) + ($config['height'] / 2));

				
				$this->EE->image_lib->initialize($config);

				if ( ! @$this->EE->image_lib->crop())
				{
					return FALSE;
				}
			}
			else
			{
				$this->EE->image_lib->initialize($config);
				
				if ( ! @$this->EE->image_lib->resize())
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
			$this->delete(array_keys($delete));
		}
	}
	

	function set_image_config($data, $type = 'watermark')
	{
		$config = array();
		
		if ($type == 'watermark')
		{
			$wm_prefs = array('source_image', 'padding', 'wm_vrt_alignment', 'wm_hor_alignment', 
			'wm_hor_offset', 'wm_vrt_offset');

			$i_type_prefs = array('wm_overlay_path', 'wm_opacity', 'wm_x_transp', 'wm_y_transp');

			$t_type_prefs = array('wm_text', 'wm_font_path', 'wm_font_size', 'wm_font_color', 
			'wm_shadow_color', 'wm_shadow_distance');			
			
			$config['wm_type'] =  ($data['wm_type'] == 't' OR $data['wm_type'] == 'text') ? 'text' : 'overlay';
			
			if ($config['wm_type'] == 'text')
			{
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
			
			if (isset($config['wm_vrt_alignment']))
			{
				if ($config['wm_vrt_alignment'] == 't')
				{
					$config['wm_vrt_alignment'] = 'top';
				}
				elseif ($config['wm_vrt_alignment'] == 'm')
				{
					$config['wm_vrt_alignment'] = 'middle';
				}
				else
				{
					$config['wm_vrt_alignment'] = 'bottom';
				}
			}
			
			if (isset($config['wm_hor_alignment']))
			{
				if ($config['wm_hor_alignment'] == 'l')
				{
					$config['wm_hor_alignment'] = 'left';
				}
				elseif ($config['wm_hor_alignment'] == 'c')
				{
					$config['wm_hor_alignment'] = 'center';
				}
				else
				{
					$config['wm_hor_alignment'] = 'right';
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
	function _directory_contents($dir)
	{
		return array(
			'url' => $dir['url'], 
			'files' => $this->_get_files($dir), 
			'categories' => $this->_get_category_dropdown($dir)
		);
	}
	

	// --------------------------------------------------------------------

	/**
	 * Gets the files for a particular directory
	 * Also, adds short name and file size
	 *
	 * @access private
	 * @return array	List of files
	 */
	private function _get_files($dir)
	{
		$this->EE->load->model('file_model');
		$this->EE->load->helper(array('text', 'number'));
		
		$files = $this->EE->file_model->get_files($dir['id'], array('type' => $dir['allowed_types']));
		$files = $files['results']->result_array();

		foreach ($files as &$file)
		{
			$file['short_name'] = ellipsize($file['title'], 10, 0.5);
			$file['file_size'] = byte_format($file['file_size']);
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
	function _upload_file($dir, $field_name)
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

			$this->EE->db->select('field_type, settings');
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
		}
		
		// --------------------------------------------------------------------
		// Upload the file
		
		// TODO: Figure out why relative paths don't work
		
		$field = ($field_name) ? $field_name : 'userfile';
		$clean_filename = basename($this->clean_filename($_FILES[$field]['name'], $dir['id']));
		
		$config = array(
			'file_name'		=> $clean_filename,
			'upload_path'	=> $dir['server_path'],
			'allowed_types'	=> $allowed_types,
			'max_size'		=> round($dir['max_size']/1024, 2),
			'max_width'		=> $dir['max_width'],
			'max_height'	=> $dir['max_height']
		);
		
		$this->EE->load->helper('xss');
		
		// Check to see if the file needs to be XSS Cleaned
		if (xss_check())
		{
			$config['xss_clean'] = FALSE;
			$this->xss_clean_off();
		}
		else
		{
			$config['xss_clean'] = TRUE;
		}


		// Upload the file
		$this->EE->load->library('upload', $config);
		if ( ! $this->EE->upload->do_upload($field_name))
		{
			return array('error' => $this->EE->upload->display_errors());
		}

		$file = $this->EE->upload->data();
		
		// --------------------------------------------------------------------
		// Add file the database
		
		// Make sure the file has a valid MIME Type
		if ( ! $file['file_type'])
		{
			return array('error' => lang('invalid_mime'));
		}
		
		// Figure out what dimensions we need to worry about
		$this->EE->load->model('file_model');
		$file_dimensions = $this->EE->file_model->get_dimensions_by_dir_id($dir['id']);
		
		// Build list of information to save and return
		$file_data = array(
			'upload_location_id'	=> $dir['id'],
			'site_id'				=> $this->EE->config->item('site_id'),
			
			'file_name'				=> $file['file_name'],
			'orig_name'				=> $file['orig_name'],
			
			'is_image'				=> $file['is_image'],
			'mime_type'				=> $file['file_type'],
			
			'rel_path'				=> $file['full_path'],
			'file_thumb'			=> $dir['url'].'_thumbs/thumb_'.$file['file_name'],
		
			'modified_by_member_id' => $this->EE->session->userdata('member_id'),
			'uploaded_by_member_id'	=> $this->EE->session->userdata('member_id'),
			
			'file_size'				=> $file['file_size'],
			'file_height'			=> $file['image_height'],
			'file_width'			=> $file['image_width'],
			'file_hw_original'		=> $file['image_height'].' '.$file['image_width'],
			
			'dimensions'			=> $file_dimensions->result_array()
		);
		
		// Save file to database
		$saved = $this->save_file($file['full_path'], $dir['id'], $file_data);
		
		// Set file id in return data
		$file_data['file_id'] = $saved['file_id'];
		
		// Return errors from the filemanager
		if ( ! $saved['status'])
		{
			return array('error' => $saved['message']);
		}
		
		return $file_data;
	}


	// --------------------------------------------------------------------

	/**
	 * Overwrite OR Rename Files Manually
	 *
	 * @access	public
	 * @return	void
	 */	 
	function replace_file($data)
	{
		$directory_id  = $data['upload_location_id'];
		$old_file_name = str_replace('temp_file_', '', $data['file_name']);
		$new_file_name = basename($this->clean_filename($old_file_name, $directory_id));
		
		$this->EE->load->model('file_upload_preferences_model');
		
		// TODO: Remove permissions checks?
		// Permissions checks were left in here (although redundant with
		// Filemanager::upload_file) because this will later change to allow
		// people to choose the name of the file
		
		// Check they have permission for this directory and get directory info
		$query = $this->EE->file_upload_preferences_model->get_upload_preferences(
			$this->EE->session->userdata('group_id'), 
			$directory_id
		);
		
		if ($query->num_rows() <= 0)
		{
			return;
		}
		
		$dir_row = $query->row();
		
		$config = array(
			'upload_path'	=> $dir_row->server_path,
			'allowed_types'	=> ($this->EE->session->userdata('group_id') == 1) ? 'all' : $dir_row->allowed_types,
			'max_size'		=> round($dir_row->max_size/1024, 2),
			'max_width'		=> $dir_row->max_width,
			'max_height'	=> $dir_row->max_height
		);
		
		$this->EE->load->library('upload', $config);
		
		if ( ! $this->EE->upload->file_overwrite($old_file_name, $new_file_name))
		{
			return array('error' => $this->EE->upload->display_errors());
		} 
		
		// Update the file record
		$this->EE->load->model('file_model');
		$previous_data = $this->EE->db->get_where('files', array('file_id' => $data['file_id']));
		$previous_data = $previous_data->row();
		
		$updated_data = array(
			'file_id'	=> $data['file_id'],
			'file_name'	=> $new_file_name,
			'rel_path'	=> str_replace($old_file_name, $new_file_name, $previous_data->rel_path)
		);
		
		// Change title in the event if it's automatic
		if ($previous_data->title == $previous_data->file_name)
		{
			$updated_data['title'] = $new_file_name;
		}
		
		$this->EE->file_model->save_file($updated_data);
		
		// TODO: Rebuild thumbnails
		
		return TRUE;
    }

    /* END */
 

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
	 * Delete files by filename.
	 *
	 * Delete files in a single upload location.  This file accepts filenames to delete.
	 * If the user does not belong to the upload group, an error will be thrown.
	 *
	 * @param 	array 		array of files to delete
	 * @param 	boolean		whether or not to delete thumbnails
	 * @return 	boolean 	TRUE on success/FALSE on failure
	 */
	public function delete_file_names($dir_id, $files = array())
	{
		$this->EE->load->model('file_model');
		$file_ids = array();
		$file_data = $this->EE->file_model->get_files_by_name($files, array($dir_id));

		foreach ($file_data->result() as $file)
		{
			$file_ids[] = $file->file_id;
		}

		return $this->delete($file_ids);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Delete files.
	 *
	 * Delete files in the upload locations.  This file accepts FileIDs to delete.
	 * If the user does not belong to the upload group, an error will be thrown.
	 *
	 * @param 	array 		array of files to delete
	 * @param 	boolean		whether or not to delete thumbnails
	 * @return 	boolean 	TRUE on success/FALSE on failure
	 */
	public function delete($files = array(), $find_thumbs = True)
	{
		if (empty($files))
		{
			return FALSE;
		}
		
		$this->EE->load->model('file_model');		
		$file_data = $this->EE->file_model->get_files_by_id($files);
		
		if ($file_data->num_rows() === 0)
		{
			return FALSE;
		}
				
		// store and set free
		$files = $file_data->result();
		$file_data->free_result();

		$dir_paths = array();
		$thumb_sizes = array();
		$file_dirs = $this->fetch_upload_dirs();
		
		// We need to loop twice - first one is for permissions
		foreach ($files as $file)
		{
			$id = $file->upload_location_id;
			
			if ( ! isset($dir_paths[$id]))
			{
				if ( ! isset($file_dirs[$id]) OR ! $file_dirs[$id])
				{
					return FALSE;
				}
				
				$thumb_sizes[$id] = array('thumb');
				$dir_paths[$id] = $file_dirs[$id]['server_path'];
			}
		}
		
		// Figure out custom thumb sizes
		$thumb_query = $this->EE->file_model->get_dimensions_by_dir_id(array_keys($dir_paths));
		
		foreach ($thumb_query->result() as $thumbs)
		{
			$thumb_sizes[$thumbs->upload_location_id][] = $thumbs->short_name;
		}
		
		
		// will contain only those that we could actually remove
		$deleted = array();
		$delete_problem	= FALSE;	
		
		// Second round, remove files
		foreach ($files as $file)
		{
			$server_path = $dir_paths[$file->upload_location_id];
			
			// Kill the file
			if ( ! @unlink($server_path.$file->rel_path))
			{
				$delete_problem = TRUE;
			}
			
			// And now the thumbs
			foreach ($thumb_sizes[$file->upload_location_id] as $name)
			{
				$thumb = $server_path.'_'.$name.'/'.$file->rel_path;
				if (file_exists($thumb))
				{
					@unlink($thumb);
				}				
			}
			
			// Store for the hook
			$deleted[] = $file;
			
			// Remove 'er from the database
			$this->EE->db->where('file_id', $file->file_id)->delete('files');
		}
		
		/* -------------------------------------------
		/* 'files_after_delete' hook.
		/*  - Add additional processing after file deletion
		*/
			$edata = $this->EE->extensions->call('files_after_delete', $deleted);
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		return ($delete_problem) ? FALSE : TRUE;	
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
			$qry = $this->EE->db->select('path, file_name')
								->where('file_id', $files[0])
								->get('files');
			
			if ( ! file_exists($qry->row('path')))
			{
				return FALSE;
			}

			$file = file_get_contents($qry->row('path'));
			$file_name = $qry->row('file_name');

			$this->EE->load->helper('download');
			force_download($file_name, $file);

			return TRUE;
		}
		
		// Zip up a bunch of files for download
		$this->EE->load->library('zip');

		$qry = $this->EE->db->select('path')
							->where_in('file_id', $files)
							->get('files');
		
		
		if ($qry->num_rows() === 0)
		{
			return FALSE;
		}

		foreach ($qry->result() as $row)
		{
			$this->EE->zip->read_file($row->path);
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
	
	function delete_watermark_prefs($id)
	{
		$name = $this->EE->file_model->delete_watermark_preferences($id);
		
		// And reset any dimensions using this watermark to 0
		$this->EE->file_model->update_dimensions(array('watermark_id' => 0), array('watermark_id' => array($id)));
		
		return $name;
	}
	
	

}

// END Filemanager class

/* End of file Filemanager.php */
/* Location: ./system/expressionengine/libraries/Filemanager.php */
