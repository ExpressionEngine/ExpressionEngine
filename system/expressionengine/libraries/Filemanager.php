<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
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

	var $EE;
	var $config;
	var $theme_url;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->library('javascript');
		
		$this->theme_url = $this->EE->config->item('theme_folder_url').'cp_themes/'.$this->EE->config->item('cp_theme').'/';
		
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
											'plugin'    => array('scrollable', 'scrollable.navigator', 'ee_filebrowser')
										)
									);

		$this->EE->cp->add_to_head('<link rel="stylesheet" href="'.BASE.AMP.'C=css'.AMP.'M=file_browser" type="text/css" media="screen" />');
		
		$this->EE->javascript->set_global('lang', array(
									'resize_image'		=> $this->EE->lang->line('resize_image'),
									'or'				=> $this->EE->lang->line('or'),
									'return_to_publish'	=> $this->EE->lang->line('return_to_publish')
													)
										);
		
		$this->EE->javascript->set_global('filebrowser', array(
									'endpoint_url'	=> $endpoint_url,
									'window_title'	=> $this->EE->lang->line('file_manager')
														)
										);
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
		
		$filebrowser_html = $this->EE->load->view('_shared/filebrowser', $vars, TRUE);

		die($this->EE->javascript->generate_json(array(
			'manager'		=> str_replace(array("\n", "\t"), '', $filebrowser_html),	// reduces transfer size
			'directories'	=> $vars['filemanager_directories']
		)));
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
			
			$data['id'] = $dir_id;
			echo $this->EE->javascript->generate_json($data);
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
		
		if ( ! array_key_exists('error', $data))
		{
			$this->create_thumb($dir, $data);
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
	 * Create Thumbnail
	 *
	 * Create a Thumbnail for a file
	 *
	 * @access	public
	 * @param	mixed	directory information
	 * @param	mixed	file information
	 * @return	bool	success / failure
	 */
	function create_thumb($dir, $data)
	{
		$this->EE->load->library('image_lib');
		
		$img_path = rtrim($dir['server_path'], '/').'/';
		$thumb_path = $img_path.'_thumbs/';
				
		if ( ! is_dir($thumb_path))
		{
			mkdir($thumb_path);
			
			if ( ! file_exists($thumb_path.'index.html'))
			{
				$f = fopen($thumb_path.'index.html', FOPEN_READ_WRITE_CREATE_DESTRUCTIVE);
				fwrite($f, 'Directory access is forbidden.');
				fclose($f);
			}
		}
		elseif ( ! is_really_writable($thumb_path))
		{
			return FALSE;
		}
		
		$this->EE->image_lib->clear();

		$config['source_image']		= $img_path.$data['name'];
		$config['new_image']		= $thumb_path.'thumb_'.$data['name'];
		$config['maintain_ratio']	= TRUE;
		$config['image_library']	= $this->EE->config->item('image_resize_protocol');
		$config['library_path']		= $this->EE->config->item('image_library_path');
		$config['width']			= 73;
		$config['height']			= 60;

		$this->EE->image_lib->initialize($config);

		if ( ! $this->EE->image_lib->resize())
		{
			return FALSE;
			die($this->EE->image_lib->display_errors());
		}
	
		@chmod($config['new_image'], DIR_WRITE_MODE);
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

		foreach($files as $key => $file)
		{
			// Hide the thumbs directory
			if ($file['name'] == '_thumbs' OR $file['name'] == "folder")
			{
				unset($files[$key]);
			}
			else
			{
				$files[$key]['has_thumb'] = (in_array('thumb_'.$file['name'], $map));
			}
		}

		return $files;
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
		
		$this->EE->load->model('tools_model');
		$query = $this->EE->tools_model->get_upload_preferences($this->EE->session->userdata('group_id'));

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
		$this->EE->load->model('tools_model');
		$files = $this->EE->tools_model->get_files($dir['server_path'], $dir['allowed_types'], '', TRUE, TRUE);
		
		return array('url' => $dir['url'], 'files' => $files);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Upload File Callback
	 *
	 * The function that handles the file upload logic (allowed upload? etc.)
	 *
	 * @access	private
	 * @param	mixed	upload directory information
	 * @return	string	upload field name
	 */
	function _upload_file($dir, $field_name)
	{
		$this->EE->load->helper('url');
		
		// Restricted upload directory?
		switch($dir['allowed_types'])
		{
			case 'all'	: $allowed_types = '*';
				break;
			case 'img'	: $allowed_types = 'gif|jpg|jpeg|png|jpe';
				break;
			default		: $allowed_types = '';
		}

		// Is this a custom field?
		if (strpos($field_name, 'field_id_') === 0)
		{
			$field_id = str_replace('field_id_', '', $field_name);

			$this->EE->db->select('field_type, field_content_type');
			$type_query = $this->EE->db->get_where('channel_fields', array('field_id' => $field_id));

			if ($type_query->num_rows())
			{
				// Permissions can only get more strict!
				if ($type_query->row('field_content_type') == 'image')
				{
					$allowed_types = 'gif|jpg|jpeg|png|jpe'; 				
				}
			}
		}

		$config = array(
				'upload_path'	=> $dir['server_path'],
				'allowed_types'	=> $allowed_types,
				'max_size'		=> round($dir['max_size']/1024, 2),
				'max_width'		=> $dir['max_width'],
				'max_height'	=> $dir['max_height']
			);

		if ($this->EE->config->item('xss_clean_uploads') == 'n')
		{
			$config['xss_clean'] = FALSE;
		}
		else
		{
			$config['xss_clean'] = ($this->EE->session->userdata('group_id') === 1) ? FALSE : TRUE;
		}

		$this->EE->load->library('upload', $config);

		if ( ! $this->EE->upload->do_upload($field_name))
		{
			return array('error' => $this->EE->upload->display_errors());
		}
		else
		{
			$data = $this->EE->upload->data();

			$this->EE->load->library('encrypt');

			return array(
				'name'			=> $data['file_name'],
				'is_image'		=> $data['is_image'],
				'dimensions'	=> $data['image_size_str'],
				'directory'		=> $dir['id'],
				'width'			=> $data['image_width'],
				'height'		=> $data['image_height'],
				'thumb'			=> $dir['url'].'_thumbs/thumb_'.$data['file_name'],
				'url_path'		=> rawurlencode($this->EE->encrypt->encode($data['full_path'], $this->EE->session->sess_crypt_key)) //needed for displaying image in edit mode
			);
		}
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
}

// END Filemanager class

/* End of file Filemanager.php */
/* Location: ./system/expressionengine/libraries/Filemanager.php */
