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
	
	public $upload_errors = FALSE;
	public $upload_warnings = FALSE;
	public $upload_data = NULL;
	public $dir_sizes = FALSE;
	private $_upload_dirs = array();

	private $EE;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->library('javascript');
		$this->EE->lang->loadfile('filemanager');
		
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

		// Generate the filters
		$vars['selected_filters'] = form_dropdown('selected', array('all' => 'all', 'selected' => 'selected', 'unselected' => 'unselected'), 'all');
		$vars['category_filters'] = form_dropdown('category', array());
		$vars['view_filters']     = form_dropdown('view_type', array('list' => 'a list', 'thumb' => 'thumbnails'), 'list', 'id="view_type"');

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
	
	// --------------------------------------------------------------------
	
	/**
	 * Synchronize Resized Images
	 *
	 * Checks and creates resized images per directory settings
	 *
	 * @access	public
	 * @param	mixed	directory information
	 * @param	array	file information
	 * @param	array	array of sizes
	 * @return	bool	success / failure
	 */
	function sync_resized($dir, $data, $dimensions)
	{
		$this->EE->load->library('image_lib');
		
		$img_path = rtrim($dir['server_path'], '/').'/';
		
		//$source_dir = rtrim(realpath($dir), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

		foreach ($dimensions as $name => $size)
		{
			$create_new = FALSE;
			$resized_path = $img_path.'_'.$name.'/';
			
				
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
			
			// Does the image exist and is it the proper size?
			if ( ! file_exists($resized_path.$data['name']))
			{
				$create_new = TRUE;
			}
			else  // probably best to have a param for this check
			{
				$file = get_file_info($resized_path.$data['name']);
			
				$file['relative_path'] = (isset($file['relative_path'])) ?
					 	reduce_double_slashes($file['relative_path']) :
						reduce_double_slashes($resized_dir);
						
				if (function_exists('getimagesize')) 
				{
					if ($D = @getimagesize($file['relative_path'].$file['name']))
					{
						$file['width'] = $D[0];
						$file['height'] = $D[1];
					}
				
					// Check against image setting
					if ($file['width'] > $size['width'])
					{
						$create_new = TRUE;
					}
					elseif ($file['height'] > $size['height'])
					{
						$create_new = TRUE;
					}

				}
				else
				{
					// We can't give dimensions, so ???
					$file['dimensions'] = FALSE;
					exit('no dim'.$file['name']);
				}
				
				// If size is wrong- nuke old resized image
				if ($create_new == TRUE)
				{
					@unlink($file['relative_path'].$file['name']);
				}	
			}		

			if ($create_new == FALSE)
			{
				continue;
			}
			
			$this->EE->image_lib->clear();

			$config['source_image']		= $img_path.$data['name'];
			$config['new_image']		= $resized_path.$data['name'];
			$config['maintain_ratio']	= TRUE;
			$config['image_library']	= $this->EE->config->item('image_resize_protocol');
			$config['library_path']		= $this->EE->config->item('image_library_path');
			$config['width']			= $size['width'];
			$config['height']			= $size['height'];

			$this->EE->image_lib->initialize($config);

			// crop based on resize type
			
			if ( ! $this->EE->image_lib->resize())
			{
				return FALSE;
				die($this->EE->image_lib->display_errors());
			}
	
			@chmod($config['new_image'], DIR_WRITE_MODE);

		}
		
		return TRUE;
	}

	function sync_database()
	{
		
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
			'category_groups' => $this->_get_category_dropdown_array($dir)
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
		
		$files = $this->EE->file_model->get_files($dir['id'], '', $dir['allowed_types']);
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
	 * Build an array formatted for a dropdown of the categories for a directory
	 *
	 * array(
	 *		'group_name' = array(
	 *			'cat_id' => 'cat_name',
	 *			...
	 *		)
	 *	)
	 *
	 * @access private
	 * @param $dir Directory array, containing at least the id
	 * @return array Array with the category group name as the key and the 
	 *		categories as the values (see above)
	 */
	private function _get_category_dropdown_array($dir)
	{
		$raw_categories = $this->_get_categories($dir);
		$category_dropdown_array = array();

		// Build the array of categories
		foreach ($raw_categories as $category_group) {
			$categories = array();

			foreach($category_group['categories'] as $category) {
				$categories[$category['cat_id']] = $category['cat_name'];		
			}
			
			$category_dropdown_array[$category_group['group_name']] = $categories;
		}

		return $category_dropdown_array;
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

		if (count($category_group_ids) > 0 AND $category_group_ids[0] != '') {
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
				'orig_name'		=> $this->EE->upload->orig_name,
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
	 * Overwrite OR Rename Files Manually
	 *
	 * @access	public
	 * @return	void
	 */	 
    function replace_file($data)
    {
        $id          	= $data['id']; 
        $file_name   	= $data['file_name'];
		$orig_name   	= $data['orig_name'];   
        $temp_file_name	= $data['temp_file_name'];  
        $is_image    	= $data['is_image'];
		$remove_spaces	= $data['remove_spaces'];
		$temp_prefix	= $data['temp_prefix'];
        //$field_group 	= $data['field_group'];
        

		if ($remove_spaces == TRUE)
        {
            $file_name = preg_replace("/\s+/", "_", $file_name);
            $temp_file_name = preg_replace("/\s+/", "_", $temp_file_name);
        }

		// Check they have permission for this directory and get directory info
		$this->EE->load->model('tools_model');
		$query = $this->EE->tools_model->get_upload_preferences($this->EE->session->userdata('group_id'), $id);
		
		if ($query->num_rows() == 0)
		{
			return;
		}
		
		$dir_row = $query->row();

		$config = array(
				'upload_path'	=> $dir_row->server_path,
				'allowed_types'	=> ($this->EE->session->userdata('group_id') == 1) ? 'all' : $dir_row->allowed_types,
				'max_size'		=> round($dir_row->max_size/1024, 2),
				'max_width'		=> $dir_row->max_width,
				'max_height'	=> $dir_row->max_height, 
				'temp_prefix'	=> $temp_prefix
			);

        //  This checks that the newly named file doesn't conflict with an existing file- 
		//  if they newly named it of course!
		/*
        if ($orig_name != $file_name)
        {
			if (file_exists($dir_row->server_path.$file_name))
			{
				$this->upload_warnings = array('file_exists');
				return $this;
        	}
        }
		*/
                
		$this->EE->load->library('upload', $config);
		

		if ( ! $this->EE->upload->file_overwrite($temp_file_name, $file_name))
		{
			$this->upload_errors = array('error' => $this->EE->upload->display_errors());
		} 
		
		return $this;
        
		// Not at all sure if I need this-
		
		/*
		$data = $this->EE->upload->data();

		$this->EE->load->library('encrypt');

			return array(
				'name'			=> $data['file_name'],
				'orig_name'		=> $this->EE->upload->orig_name,
				'is_image'		=> $data['is_image'],
				'dimensions'	=> $data['image_size_str'],
				'directory'		=> $dir['id'],
				'width'			=> $data['image_width'],
				'height'		=> $data['image_height'],
				'thumb'			=> $dir['url'].'_thumbs/thumb_'.$data['file_name'],
				'url_path'		=> rawurlencode($this->EE->encrypt->encode($data['full_path'], $this->EE->session->sess_crypt_key)) //needed for displaying image in edit mode
			);
			
		*/
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
		$this->EE->load->model('tools_model');
		
		$upload_dirs = $this->EE->tools_model->get_upload_preferences(
										$this->EE->session->userdata('group_id'),
										$file_dir_id);
		
		$dirs = new StdClass();
		$dirs->files = array();
		
		foreach ($upload_dirs->result() as $dir)
		{
			$dirs->files[$dir->id] = array();
			
			$files = $this->EE->tools_model->get_files($dir->server_path, $dir->allowed_types, '', FALSE, $get_dimensions, $files);
			
			foreach ($files as $file)
			{
				$dirs->files[$dir->id] = $files;
			}
		}
	
		return $dirs;
	}
	
	// --------------------------------------------------------------------	

	function directory_files_map($source_dir, $directory_depth = 0, $hidden = FALSE, $allowed_types = 'all')
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
				
				if ( ! @is_dir($source_dir.$file))
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
			}

			closedir($fp);
			
			sort($filedata);
			return $filedata;
		}

		return FALSE;
	}
	
	// --------------------------------------------------------------------	
	
	/**
	 * Save a file
	 *
	 * The purpose of this method is to make the upload process generally 
	 * transparent to us and third party developers.  Pass in information on
	 * the upload directory as per the array below, and you're off to the 
	 * races.  
	 *
	 */
	public function save($upload_dir_info)
	{
		/*
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
		
		// Allowed filetypes for this directory
		switch($upload_dir_info['allowed_types'])
		{
			case 'all' : $allowed_types = '*';
				break;
			case 'img' : $allowed_types = 'jpg|jpeg|png|gif';
				break;
			default :
				$allowed_types = $upload_dir_info['allowed_types'];
		}
		
		// Convert the file size to kilobytes
		$max_file_size	= ($upload_dir_info['max_size'] == '') ? 0 : round($upload_dir_info['max_size']/1024, 2);
		$max_width		= ($upload_dir_info['max_width'] == '') ? 0 : $upload_dir_info['max_width'];
		$max_height		= ($upload_dir_info['max_height'] == '') ? 0 : $upload_dir_info['max_height'];
		
		if ($this->EE->config->item('xss_clean_uploads') == 'n')
		{
			$xss_clean_upload = FALSE;
		}
		else
		{
			$xss_clean_upload = ($this->EE->session->userdata('group_id') === 1) ? FALSE : TRUE;
		}
		
		
		$config = array(
			'upload_path'		=> $upload_dir_info['server_path'],
			'allowed_types'		=> $allowed_types,
			'max_height'		=> $max_height,
			'max_width'			=> $max_width,
			'max_size'			=> $max_file_size,
			'xss_clean'			=> $xss_clean_upload,
		);
		
		
		$this->EE->load->library('upload', $config);
		
		if ( ! $this->EE->upload->do_upload())
		{
			$this->upload_errors = $this->EE->upload->display_errors();
			
			return $this;
		}
		
		$this->upload_data = $this->EE->upload->data();		
		
		/* 	It makes me kind of cry a bit to do this, but some hosts have
			stupid permissions, so unless you chmod the file like so, the
			user won't be able to delete it with their ftp client.  :( */
		
		@chmod($this->upload_data['full_path'], DIR_WRITE_MODE);
		
		
		return $this;
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
		
		$delete_problem = FALSE;	
		
		$file_data = $this->EE->file_model->get_files_by_id($files);
		
		if ($file_data->num_rows() === 0)
		{
			return FALSE;
		}
		
		$file_dirs = $this->fetch_upload_dirs();
		$dir_path = NULL;
		
		// I'm not sold on this approach, so it might need some refining.
		// We'll see as things come together
		foreach ($file_data->result() as $file)
		{
			// make sure the user has access to this upload dir
			foreach ($file_dirs as $dir)
			{
				if ($file->upload_location_id === $dir['id'])
				{
					$dir_path = $dir['server_path'];
					break;
				}
			}

			// We didn't get the directory path.
			if ( ! $dir_path)
			{
				return FALSE;
			}
			
			if ( ! @unlink($file->path))
			{
				$delete_problem = TRUE;
			}
			
			if ($find_thumbs)
			{
				$thumb = $dir_path.'_thumbs'.DIRECTORY_SEPARATOR.'thumb_'.$file->file_name;
				
				if (file_exists($thumb))
				{
					@unlink($thumb);
				}
			}
			
			// Remove 'er from the database
			$this->EE->db->where('file_id', $file->file_id)->delete('files');
		}
		
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

}

// END Filemanager class

/* End of file Filemanager.php */
/* Location: ./system/expressionengine/libraries/Filemanager.php */
