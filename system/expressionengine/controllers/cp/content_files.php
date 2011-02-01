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

class Content_files extends CI_Controller {
	
	private $_upload_dirs = array();
	public $remove_spaces = TRUE;
	public $temp_prefix = "temp_file_";
	
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		// Permissions
		if ( ! $this->cp->allowed_group('can_access_content')  OR 
			 ! $this->cp->allowed_group('can_access_files'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('filemanager');
		$this->load->library(array('filemanager'));
		$this->load->helper(array('form'));
		
		// Get upload dirs
		$upload_dirs = $this->filemanager->fetch_upload_dirs();
		
		foreach ($upload_dirs as $row)
		{
			$this->_upload_dirs[$row['id']] = $row;
		}		
		
		if (AJAX_REQUEST)
        {
            $this->output->enable_profiler(FALSE);
        }
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Index Page
	 */
	public function index()
	{
		$this->load->library(array('pagination'));
		$this->load->helper('string');
		
		// Page Title
		$this->cp->set_variable('cp_page_title', lang('content_files'));
		
		$this->cp->add_js_script(array(
			'plugin'	=> array('overlay', 'overlay.apple', 'ee_upload'),
			'file'		=> 'cp/file_manager_home'
			)
		);
		
		
		$per_page = ($per_page = $this->input->get('per_page')) ? $per_page : 40;
		$offset = ($offset = $this->input->get('offset')) ? $offset : 0;
		$upload_dirs_options = array();
	
		foreach ($this->_upload_dirs as $dir)
		{
			$upload_dirs_options[$dir['id']] = $dir['name'];
		}

		ksort($upload_dirs_options);

		$selected_dir = ($selected_dir = $this->input->get('directory')) ? $selected_dir : NULL;
		
		if ( ! $selected_dir)
		{		
			$selected_dir = array_search(current($upload_dirs_options), $upload_dirs_options);
		}
		
		$no_upload_dirs = FALSE;
		
		if (empty($this->_upload_dirs))
		{
			$no_upload_dirs = TRUE;
		}
		else
		{
			$all_files = $this->filemanager->directory_files_map($this->_upload_dirs[$selected_dir]['server_path'], 1, FALSE, $this->_upload_dirs[$selected_dir]['allowed_types']);

			$file_list = array();
			$dir_size = 0;

			$total_rows = count($all_files);

			$current_files = array_slice($all_files, $offset, $per_page);

			$files = $this->filemanager->fetch_files($selected_dir, $current_files);

			// Setup file list
			foreach ($files->files[$selected_dir] as $file)
			{
				if ( ! $file['mime'])
				{
					continue;
				}

				$file_location = $this->functions->remove_double_slashes(
						$this->_upload_dirs[$selected_dir]['url'].'/'.$file['name']
					);

				$file_path = $this->functions->remove_double_slashes(
						$this->_upload_dirs[$selected_dir]['server_path'].'/'.$file['name']
					);

				$list = array(
					'name'		=> $file['name'],
					'link'		=> $file_location,
					'mime'		=> $file['mime'],
					'size'		=> $file['size'],
					'date'		=> $file['date'],
					'path'		=> $file_path,
					'is_image'	=> FALSE,
				);				

				// Lightbox links
				if (strncmp($file['mime'], 'image', 5) === 0)
				{
					$list['is_image'] = TRUE;
					$list['link'] = '<a class="less_important_link overlay" id="img_'.str_replace(array(".", ' '), '', $file['name']).'" href="'.$file_location.'" title="'.$file['name'].'" rel="#overlay">'.$file['name'].'</a>';
				}

				$file_list[] = $list;

				$dir_size = $dir_size + $file['size'];
			}

			$base_url = BASE.AMP.'C=content_files'.AMP.'directory='.$selected_dir.AMP.'per_page='.$per_page;

			$link = "<img src=\"{$this->cp->cp_theme_url}images/pagination_%s_button.gif\" width=\"13\" height=\"13\" alt=\"%s\" />";

			$p_config = array(
				'base_url'				=> $base_url,
				'total_rows'			=> $total_rows,
	 			'per_page'				=> $per_page,
				'page_query_string'		=> TRUE,
				'query_string_segment'	=> 'offset',
				'full_tag_open'			=> '<p id="paginationLinks">',
				'full_tag_close'		=> '</p>',
				'prev_link'				=> sprintf($link, 'prev', '&lt;'),
				'next_link'				=> sprintf($link, 'next', '&gt;'),
				'first_link'			=> sprintf($link, 'first', '&lt; &lt;'),
				'last_link'				=> sprintf($link, 'last', '&gt; &gt;')
			);

			$this->pagination->initialize($p_config);

			$action_options = array(
				'download'			=> lang('download_selected'),
				'delete'			=> lang('delete_selected_files')
			);

			// Figure out where the count is starting 
			// and ending for the dialog at the bottom of the page
			$offset = ($this->input->get($p_config['query_string_segment'])) ? $this->input->get($p_config['query_string_segment']) : 0;
			$count_from = $offset + 1;
			$count_to = $offset + count($file_list);


			$pagination_count_text = sprintf(
										lang('pagination_count_text'),
										$count_from, $count_to, $total_rows);			
		}
		
		$data = array(
			'no_upload_dirs'		=> $no_upload_dirs,
			'upload_dirs_options' 	=> $upload_dirs_options,
			'selected_dir'			=> $selected_dir,
			'files'					=> (isset($file_list)) ? $file_list : array(),
			'dir_size'				=> (isset($dir_size)) ? $dir_size : NULL,
			'pagination_links'		=> $this->pagination->create_links(),
			'action_options' 		=> (isset($action_options)) ? $action_options : NULL, 
			'pagination_count_text'	=> (isset($pagination_count_text)) ? $pagination_count_text : NULL,
		);
		
		$this->load->view('content/files/index', $data);
	}

	// ------------------------------------------------------------------------	

	/**
	 * Upload File
	 */
	public function upload_file()
	{
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
		
	
		/*
		
		// All the directory information we need for the upload
		// destination.
		
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
		
		$fm = $this->filemanager->save($this->_upload_dirs[$file_dir]);
		
		
		if ($fm->upload_errors)
		{
			// Upload Failed
			if ($this->input->is_ajax_request())
			{
				$errors = $this->javascript->generate_json(
							array('error' => $this->upload->display_errors()));
				
				echo sprintf("<script type=\"text/javascript\">
								parent.EE_uploads.%s = %s;</script>",
								$this->input->get('frame_id'),
								$errors);
				exit();
			}
			
			$this->session->set_flashdata('message_failure', $fm->upload_errors);
			$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'directory='.$file_dir);
		}
		

		if ($fm->upload_data['file_name'] != $fm->upload_data['orig_name'])
		{
			// Page Title
			$this->cp->set_variable('cp_page_title', lang('file_exists_warning'));
			$this->cp->set_breadcrumb(BASE.AMP.'C=content_files', lang('file_manager'));

			$vars = $fm->upload_data;
			$vars['duped_name'] = $fm->upload_data['orig_name'];
			
			$vars['hidden'] = array(
				'orig_name'		=> $fm->upload_data['orig_name'],
				'rename_attempt' => '',
				'is_image' 		=> $fm->upload_data['is_image'],
				'temp_file_name'=> $fm->upload_data['file_name'],
				'remove_spaces'	=> '1',
				 'id' 			=> $file_dir
				);
				
			return $this->load->view('content/files/rename', $vars);
		}

		// Make the thumbnail
		
		$thumb = $fm->create_thumb(
			array('server_path' => $fm->upload_data['file_path']), 
			array('name' => $fm->upload_data['file_name'])
		);
		
		if ($this->input->is_ajax_request())
		{
			$resp = $this->javascript->generate_json(array(
				'success'		=> lang('upload_success'),
				'filename'		=> $fm->upload_data['file_name'],
				'filesize'		=> $fm->upload_data['file_size'],
				'filetype'		=> $fm->upload_data['file_type'],
				'date'			=> date('M d Y - H:ia')
			));

			echo sprintf('<script type="text/javascript">
							parent.EE_uploads.%s = %s;</script>', 
						$this->input->get('frame_id'), $resp);
			exit();
		}
		
		$this->session->set_flashdata('message_success', lang('upload_success'));
		$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'directory='.$file_dir);			
	}


	// ------------------------------------------------------------------------

	/**
	 * Allows renaming and over writing of files
	 *
	 * 
	 */
	public function rename_file()
	{
		$required = array('file_name', 'rename_attempt', 'orig_name', 'temp_file_name', 'is_image', 'temp_prefix', 'remove_spaces', 'id');
		
		foreach ($required as $val)
		{
			$data[$val] = $this->input->post($val);
		}

		// Sigh- did they rename it w/an existing name?  We give them the rename form again.
        if (($data['rename_attempt'] != '' && $data['rename_attempt'] != $data['file_name']) 
			OR ($data['rename_attempt'] == '' && $data['orig_name'] != $data['file_name']))
        {
			if (file_exists($this->_upload_dirs[$data['id']]['server_path'].$data['file_name']))
			{

				// Page Title
				$this->cp->set_variable('cp_page_title', lang('file_exists_warning'));
				$this->cp->set_breadcrumb(BASE.AMP.'C=content_files', lang('file_manager'));

				$vars['file_name'] = $data['file_name'];
				$vars['duped_name'] = ($data['file_name'] != '') ? $data['file_name'] : $data['orig_name'];

				$vars['hidden'] = array(
					'orig_name'		=> $data['orig_name'],
					'rename_attempt' => $data['file_name'],
					'is_image' 		=> $data['is_image'],
					'temp_file_name'=> $data['temp_file_name'],
					'remove_spaces'	=> $this->remove_spaces,
				 	'id' 			=> $data['id']
					);
				
				return $this->load->view('content/files/rename', $vars);			
			}
		}

		$fm = $this->filemanager->replace_file($data);
		
		// Errors?
		if ($fm->upload_errors)
		{
			$this->session->set_flashdata('message_failure', $fm->upload_errors);
			$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'directory='.$data['id']);
		}		

		// Woot- Success!  Make a new thumb
		$thumb = $fm->create_thumb(
			array('server_path' => $this->_upload_dirs[$data['id']]['server_path']), 
			array('name' => $data['file_name'])
		);
		
		$this->session->set_flashdata('message_success', lang('upload_success'));
		$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'directory='.$data['id']);		
	}

	// ------------------------------------------------------------------------

	/**
	 * Controls the batch actions
	 *
	 * When submitted to, expects a GET/POST variable named action containing 
	 * either download or delete
	 */
	public function multi_edit_form()
	{
		$file_settings = $this->_get_file_settings();
		
		$files    = $file_settings['files'];
		$file_dir = $file_settings['file_dir'];
		
		switch ($this->input->get_post('action'))
		{
			case 'download':
				$this->_download_files($files, $file_dir);
				break;
			
			case 'delete':
				$this->_delete_files_confirm($files, $file_dir);
				break;
			
			default:
				show_error(lang('unauthorized_access'));
				break;
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Creates the confirmation page to delete a list of files
	 *
	 * @param array $files Array of file names to delete
	 * @param integer $file_dir ID of the directory to delete from
	 */
	private function _delete_files_confirm($files, $file_dir)
	{
		$data = array(
			'files'			=> $files,
			'file_dir'		=> $file_dir,
			'del_notice'	=> (count($files) == 1) ? 'confirm_del_file' : 'confirm_del_files'
		);

		$this->cp->set_variable('cp_page_title', lang('delete_selected_files'));

		$this->load->view('content/file_delete_confirm', $data);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Delete a list of files (and their thumbnails) from a particular directory
	 * Expects two GET/POST variables:
	 *  - file: an array of urlencoded file names to delete
	 *  - file_dir: the ID of the file directory to delete from
	 */
	public function delete_files()
	{
		$files     = $this->input->get_post('file');
		$file_dir  = $this->input->get_post('file_dir');
		
		// Bail if they dont' have access to this upload location.
		if ( ! array_key_exists($file_dir, $this->_upload_dirs))
		{
			show_error(lang('unauthorized_access'));
		}
		
		$file_path = $this->_upload_dirs[$file_dir]['server_path'];
				
		if ( ! $files OR ! $file_path OR $file_path === "")
		{
			$this->session->set_flashdata('message_failure', lang('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'directory='.$file_dir);
		}

		$delete = $this->filemanager->delete($files, $file_path, TRUE);		
		
		$message_type = ($delete) ? 'message_success' : 'message_failure';
		$message = ($delete) ? lang('delete_success') : lang('message_failure');
		
		$this->session->set_flashdata($message_type, $message);
		$this->functions->redirect(BASE.AMP.'C=content_files'.AMP.'directory='.$file_dir);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Download Files
	 *
	 * @param array $files Array of file names to download
	 * @param integer $file_dir ID of the directory to download from
	 */
	private function _download_files($files, $file_dir)
	{
		$files_count = count($files);
		
		if ( ! $files_count OR 
			 ! isset($this->_upload_dirs[$file_dir]['server_path']))
		{
			show_error(lang('unauthorized_access'));
		}
		
		$file_path = $this->_upload_dirs[$file_dir]['server_path'];
		
		if ( ! $this->filemanager->download_files($files, $file_path))
		{
			show_error(lang('unauthorized_access'));
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Get the list of files and the directory ID for batch file actions
	 *
	 * @return array Associative array containing ['file_dir'] as the file directory 
	 *    and ['files'] an array of the files to act upon.
	 */
	private function _get_file_settings()
	{
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
		
		// No file, why are we here?
		if ( ! ($files = $this->input->get_post('file')))
		{
			show_error(lang('unauthorized_access'));
		}
		
		if ( ! is_array($files))
		{
			$files = array($files);
		}
		
		return array(
			'file_dir' => $file_dir,
			'files' => $files
		);
	}

	// ------------------------------------------------------------------------	
	
	/**
	 *
	 *
	 *
	 */
	public function edit_image()
	{
		// The form posts to this method, so if POST data is present
		// send to _do_image_processing to, well, do the image processing
		if ( ! empty($_POST))
		{
			return $this->_do_image_processing();
		}
		
		// Page Title
		$this->cp->set_variable('cp_page_title', lang('edit_image'));
		$this->cp->set_breadcrumb(BASE.AMP.'C=content_files', lang('file_manager'));
		
		// Do some basic permissions checking
		if ( ! ($file_dir = $this->input->get('upload_dir')))
		{
			show_error(lang('unauthorized_access'));
		}
		
		// Bail if they dont' have access to this upload location.
		if ( ! array_key_exists($file_dir, $this->_upload_dirs))
		{
			show_error(lang('unauthorized_access'));
		}
		
		$this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
		$this->output->set_header("Pragma: no-cache");

		$this->cp->add_js_script(array(
			// 'plugin'	=> array(''),
			'file'		=> 'cp/file_manager_edit',
			'plugin'	=> 'jcrop',
			)
		);

		// It cleans itself
		$file_name 	= $this->security->sanitize_filename($this->input->get('file'));
		
		// Some vars for later
		$file_url 	= $this->_upload_dirs[$file_dir]['url'].urldecode($file_name);
		$file_path 	= $this->_upload_dirs[$file_dir]['server_path'].urldecode($file_name); 
		
		// Does this file exist?
		if ( ! file_exists($file_path))
		{
			show_error(lang('unauthorized_access'));
		}
		
		$file_info = $this->filemanager->get_file_info($file_path);
		
		$this->javascript->set_global(array(
			'filemanager'	=> array(
				'image_width'	=> $file_info['width'],
				'image_height'	=> $file_info['height'],
			),
		));

		$hidden_fields = form_hidden('directory_id', $file_dir).form_hidden('file', urlencode($file_name));
		
		$data = array(
			'file_url'		=> $file_url,
			'file_path'		=> $file_path,
			'file_info'		=> $file_info,
			'hidden_fields' => $hidden_fields,
		);
		
		$this->cp->add_js_script('ui', 'accordion');
		
		$this->javascript->output('
		        $("#file_manager_toolbar").accordion({autoHeight: false, header: "h3"});
		');
		
		$this->javascript->compile();
		
		
		$this->load->view('content/files/edit_image', $data);
	}

	// ------------------------------------------------------------------------		
	
	/**
	 * image processing
	 *
	 * Figures out the full path to the file, and sends it to the appropriate 
	 * method to process the image.
	 */
	private function _do_image_processing()
	{
		$file = $this->input->post('file');

		if ( ! $file)
		{
			$this->session->set_flashdata('message_failure', lang('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files');		
		}
		
		$upload_dir_id = $this->input->post('upload_dir');
		
		$file = $this->security->sanitize_filename(urldecode($file));
		$file = $this->functions->remove_double_slashes(
				$this->_upload_dirs[$upload_dir_id]['server_path'].DIRECTORY_SEPARATOR.$file);		
		
		switch ($this->input->post('action'))
		{
			case 'rotate':
				$this->_do_rotate($file);
				break;
			case 'crop':
				$this->_do_crop($file);
				break;
			case 'resize':
				break;
			default:
				return ''; // todo, error
		}
	}
	
	// ------------------------------------------------------------------------		
	
	/**
	 * Image crop
	 */
	private function _do_crop($file)
	{
		$config = array(
			'width'				=> $this->input->post('width'),
			'maintain_ratio'	=> FALSE,
			'x_axis'			=> $this->input->post('crop_x'),
			'y_axis'			=> $this->input->post('crop_y'),
			'height'			=> ($this->input->post('height')) ? $this->input->post('height') : NULL,
			'master_dim'		=> 'width',
			'library_path'		=> $this->config->item('image_library_path'),
			'image_library'		=> $this->config->item('image_resize_protocol'),
			'source_image'		=> $file,
			'new_image'			=> $file
		);

		$this->load->library('image_lib', $config);
		
		if ( ! $this->image_lib->crop())
		{
	    	$errors = $this->image_lib->display_errors();
		}
		
		if (isset($errors))
		{
			if (AJAX_REQUEST)
			{
				$this->output->send_ajax_response($errors, TRUE);
			}
			
			show_error($errors);
		}
		
		$this->image_lib->clear();
		
		if (AJAX_REQUEST)
		{
			$dimensions = $this->image_lib->get_image_properties('', TRUE);
			$this->image_lib->clear();
			
			$this->output->send_ajax_response(array(
				'width'		=> $dimensions['width'],
				'height'	=> $dimensions['height']
			));
		}
		
		$url = BASE.AMP.'C=content_files'.AMP.'M=edit_image'.AMP.'upload_dir='.$this->input->post('upload_dir').AMP.'file='.$this->input->post('file');
		$this->functions->redirect($url);
	}

	// ------------------------------------------------------------------------		
	
	/**
	 * Do image rotation.
	 */
	private function _do_rotate($file)
	{
		
	}

	// ------------------------------------------------------------------------		
}