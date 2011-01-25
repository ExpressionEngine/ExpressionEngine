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
class Content_files_old extends CI_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->lang->loadfile('filemanager');
		
		if (AJAX_REQUEST)
        {
            $this->output->enable_profiler(FALSE);
        }
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 * 
	 * @access	public
	 * @return	void
	 */
	function index()
	{
		$this->load->library('table');
		$this->load->helper(array('form', 'string', 'url', 'file'));

		$this->cp->set_variable('cp_page_title', lang('content_files'));
		
		$this->cp->add_js_script(array(
		            'plugin'    => array('overlay', 'overlay.apple', 'tablesorter', 'ee_upload'),
					'file'		=> 'cp/file_manager_home'
		    )
		);

		if (AJAX_REQUEST)
		{
			$id = $this->input->get_post('directory');
			$path = $this->input->get_post('enc_path');
			$data = $this->_map($id, $path);
			
			$data['EE_view_disable'] = TRUE;
		}
		else
		{
			$data = $this->_map();
		}
		
		$data['can_edit_upload_prefs'] = TRUE;
		
		// Can they access file preferences?		
		if ( ! $this->cp->allowed_group('can_access_admin') OR
			 ! $this->cp->allowed_group('can_access_content_prefs') OR
			 ! $this->cp->allowed_group('can_admin_channels'))
		{
			$data['can_edit_upload_prefs'] = FALSE;
		}
		
		$this->javascript->set_global('lang', array(
					'loading'			=> lang('loading'),
					'uploading_file'	=> lang('uploading_file').'&hellip;',
					'show_toolbar'		=> lang('show_toolbar'),
					'hide_toolbar'		=> lang('hide_toolbar')
			)
		);

		$this->javascript->compile();
		$this->load->view('content/file_browse', $data);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get a directory map
	 *
	 * Creates an array of directories and their content
	 * 
	 * @access	public
	 * @param	int		optional directory id (defaults to all)
	 * @return	mixed
	 */
	function _map($dir_id = FALSE, $enc_path = FALSE)
	{
		$this->load->model('tools_model');
		
		$upload_directories = $this->tools_model->get_upload_preferences($this->session->userdata('group_id'));
		
		// if a user has no directories available to them, then they have no right to be here
		if ($this->session->userdata['group_id'] != 1 && $upload_directories->num_rows() == 0)
		{
			show_error(lang('unauthorized_access'));
		}

		$vars['file_list'] = array(); // will hold the list of directories and files
		$vars['upload_directories'] = array();

		$this->load->helper('directory');
		
		if ($enc_path)
		{
			$enc_path = $this->_decode($enc_path);
		}

		foreach ($upload_directories->result() as $dir)
		{
			if ($dir_id && $dir->id != $dir_id)
			{
				continue;
			}
			
			// we need to know the dirs for the purposes of uploads, so grab them here
			$vars['upload_directories'][$dir->id] = $dir->name;

			$vars['file_list'][$dir->id] = array(
				'id'		=> $dir->id,
				'name'		=> $dir->name,
				'url'		=> $dir->url,
				'display'	=> ($this->input->cookie('hide_upload_dir_id_'.$dir->id) == 'true') ? 'none' : 'block',
				
				'files'		=> array()	// initialize so empty dirs don't throw errors
			);


			$file_count = 0;
			$files = $this->tools_model->get_files($dir->server_path, $dir->allowed_types);

			// construct table row arrays
			foreach($files as $file)
			{
				if ($enc_path && $enc_path != $file['relative_path'].$file['name'])
				{
					continue;
				}

				if ($file['name'] == '_thumbs' OR is_dir($file['server_path']))
				{
					continue;
				}

				$enc_url_path = $this->_encode($dir->url.$file['name']); //needed for displaying image in edit mode

				// the extension check is not needed, as allowed_types takes care of that.
				// if (strncmp($file['mime'], 'image', 5) == 0 AND (in_array(strtolower(substr($file['name'], -3)), array('jpg', 'gif', 'png'))))

				if (strncmp($file['mime'], 'image', 5) == 0)
				{
					$vars['file_list'][$dir->id]['files'][$file_count] = array(
						array(
							'class'	=> 'overlay',
							'id'	=> $file['encrypted_path'],
							'data'	=> '<a class="overlay" id="img_'.str_replace(".", '', $file['name']).'" href="'.$dir->url.$file['name'].'" title="'.$file['name'].'" rel="#overlay">'.$file['name'].'</a>',
						),
						array(
							'class'	=>'align_right', 
							'data'	=> number_format($file['size']/1000, 1).NBS.lang('file_size_unit'),
						),
						array(
							'class'	=>'', 
							'data'	=> $file['mime']
						),
						array(
							'class'	=>'',
							'data'	=> date('M d Y - H:ia', $file['date']),
							'data-rawdate' => $file['date']
						),
						array(
							'id'	=> 'edit_img_'.str_replace(".", '', $file['name']), 
							'data'	=> '<a href="'.BASE.AMP.'C=content_files'.AMP.'M=prep_edit_image'.AMP.'url_path='.$enc_url_path.AMP.'file='.$file['encrypted_path'].'" title="'.$file['name'].'">'.lang('edit').'</a>'
						),
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=download_files'.AMP.'file='.$file['encrypted_path'].'" title="'.lang('file_download').':'.NBS.$file['name'].'"><img src="'.$this->cp->cp_theme_url.'images/icon-download-file.png" alt="'.lang('file_download').'" /></a> '.NBS.NBS.NBS.
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=delete_files_confirm'.AMP.'file='.$file['encrypted_path'].'" title="'.lang('delete').':'.NBS.$file['name'].'"><img src="'.$this->cp->cp_theme_url.'images/icon-delete.png" alt="'.lang('delete').'" /></a>',
						array(
							'class'	=> 'file_select', 
							'data'	=> form_checkbox('file[]', $file['encrypted_path'], FALSE, 'class="toggle"')
						)
					);
				}
				else
				{
					$vars['file_list'][$dir->id]['files'][$file_count] = array(
						$file['name'],
						array(
							'class'	=>'align_right', 
							'data'	=> number_format($file['size']/1000, 1).NBS.lang('file_size_unit'),
						),
						$file['mime'],
						date('M d Y - H:ia', $file['date']),
						'--',
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=download_files'.AMP.'file='.$file['encrypted_path'].'" title="'.lang('file_download').':'.NBS.$file['name'].'"><img src="'.$this->cp->cp_theme_url.'images/icon-download-file.png" alt="'.lang('file_download').'" /></a> '.NBS.NBS.NBS.
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=delete_files_confirm'.AMP.'file='.$file['encrypted_path'].'" title="'.lang('delete').':'.NBS.$file['name'].'"><img src="'.$this->cp->cp_theme_url.'images/icon-delete.png" alt="'.lang('delete').'" /></a>',
						array(
							'class'	=> 'file_select', 
							'data'	=> form_checkbox('file[]', $file['encrypted_path'], FALSE, 'class="toggle"')
						)
					);//iimages/icon-download-file.png") no-repeat scroll 0 0 transparent
				}

				$file_count++;
			}

		}
		
		return $vars;
	}

	// --------------------------------------------------------------------

	/**
	 * File Info
	 *
	 * Used in the file previews ajax call
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function file_info()
	{
		if ( ! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->output->enable_profiler(FALSE);
		$this->load->helper(array('file', 'html'));

		$file = $this->_decode($this->input->get_post('file'));
		
		$file_info = get_file_info($file, array('name', 'size', 'fileperms'));

		$data = array();

		if ($file_info)
		{
			$file_type = get_mime_by_extension($file);
			
			// Remove any system path information
			$theme_path = trim(PATH_THEMES, '/');
			$theme_path = substr($theme_path, 0, strrpos($theme_path, '/'));
			
			$remove	= array(SYSDIR, BASEPATH, APPPATH, $theme_path);
			$where	= str_replace($remove, '', dirname($file));
			
			$this->load->helper('text');
			
			$data['file'] = array(
				'name'			=> $file_info['name'],
				'size'			=> number_format($file_info['size']/1000, 1),
				'type'			=> $file_type,
				'permissions'	=> symbolic_permissions($file_info['fileperms']),
				'location'		=> trim($where, '/'),
				'src'			=> '' // how was this meant to be used?
			);

		}
		
		exit($this->load->view('content/_assets/file_sidebar_info', $data, TRUE));
	}

	// --------------------------------------------------------------------

	/**
	 * Upload File
	 *
	 * @access	public
	 * @return	mixed
	 */
	function upload_file()
	{
		$this->load->library('filemanager');
		$this->load->model('tools_model');
		
		// get upload dir info
		$upload_id = $this->input->get_post('upload_dir');

		$upload_dir_result = $this->tools_model->get_upload_preferences(
											$this->session->userdata('member_group'), 
											$upload_id
								);
								
		$upload_dir_prefs = $upload_dir_result->row();

		// Convert the file size to kilobytes
		$max_file_size	= ($upload_dir_prefs->max_size == '') ? 0 : round($upload_dir_prefs->max_size/1024, 2);
		$max_width		= ($upload_dir_prefs->max_width == '') ? 0 : $upload_dir_prefs->max_width;
		$max_height		= ($upload_dir_prefs->max_height == '') ? 0 : $upload_dir_prefs->max_height;

		$config = array(
			'upload_path'	=> $upload_dir_prefs->server_path,
			'max_size'		=> $max_file_size,
			'max_width'		=> $max_width,
			'max_height'	=> $max_height
		);

		if ($this->config->item('xss_clean_uploads') == 'n')
		{
			$config['xss_clean'] = FALSE;
		}
		else
		{
			$config['xss_clean'] = ($this->session->userdata('group_id') === 1) ? FALSE : TRUE;
		}

		switch($upload_dir_prefs->allowed_types)
		{
			case 'all' : $config['allowed_types'] = '*';
				break;
			case 'img' : $config['allowed_types'] = 'jpg|png|gif';
				break;
			default :
				$config['allowed_types'] = $upload_dir_prefs->allowed_types;
		}

		$this->load->library('upload', $config);
		
		// We use an iframe to simulate asynchronous uploading.  Files submitted
		// in this way will have the "is_ajax" field, otherwise they where normal
		// file upload submissions.

		if ( ! $this->upload->do_upload())
		{
			if ($this->input->get_post('is_ajax') == 'true')
			{
				echo '<script type="text/javascript">parent.EE_uploads.'.$this->input->get('frame_id').' = '.$this->javascript->generate_json(array('error' => $this->upload->display_errors())).';</script>';
				exit;
			}
			
			$this->session->set_flashdata('message_failure', $this->upload->display_errors());
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}
		else
		{
			$file_info = $this->upload->data();
			$encrypted_path = $this->_encode($file_info['full_path']);

			$this->filemanager->create_thumb(
				array('server_path' => $file_info['file_path']), 
				array('name' => $file_info['file_name'])
			);
			
			/* 	It makes me kind of cry a bit to do this, but some hosts have
				stupid permissions, so unless you chmod the file like so, the
				user won't be able to delete it with their ftp client.  :( */
			
			@chmod($file_info['full_path'], DIR_WRITE_MODE);

			if ($this->input->get_post('is_ajax') == 'true')
			{
				$response = array(
					'success'		=> lang('upload_success'),
					'enc_path'		=> $encrypted_path,
					'filename'		=> $file_info['file_name'],
					'filesize'		=> $file_info['file_size'],
					'filetype'		=> $file_info['file_type'],
					'date'			=> date('M d Y - H:ia')
				);

				exit('<script type="text/javascript">parent.EE_uploads.'.$this->input->get('frame_id').' = '.$this->javascript->generate_json($response).';</script>');
			}
			
			$this->session->set_flashdata('message_success', lang('upload_success'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Download Files
	 *
	 * If a single file is passed, it is offered as a download, but if an array
	 * is passed, they are zipped up and offered as download
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function download_files($files = array())
	{
		if ($this->input->get_post('file') == '')
		{
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		if (is_array($this->input->get_post('file')))
		{
			// extract each filename, add to files array
			foreach ($this->input->get_post('file') as $scrambled_file)
			{
				$files[] = $this->_decode($scrambled_file);
			}
		}
		else
		{
			$file_offered = $this->_decode($this->input->get_post('file'));

			if ($file_offered != '')
			{
				$files = array($file_offered);
			}
		}

		$files_count = count($files);

		if ( ! $files_count)
		{
			return; // move along
		}
		
		if ($files_count == 1)
		{
			// no point in zipping for a single file... let's just send the file

			$this->load->helper('download');

			$data = file_get_contents($files[0]);
			$name = substr(strrchr($files[0], '/'), 1);
			force_download($name, $data);
		}
		else
		{
			// its an array of files, zip 'em all

			$this->load->library('zip');

			foreach ($files as $file)
			{
				$this->zip->read_file($file);
			}

			$this->zip->download('downloaded_files.zip'); 
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Files Confirm
	 *
	 * Used to confirm deleting files
	 *
	 * @access	public
	 * @return	mixed
	 */
	function delete_files_confirm()
	{
		if ( ! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->helper('form');

		$files = $this->input->get_post('file');
		
		if ( ! is_array($files))
		{
			$files = array($files);
		}

		$vars['files'] = $files;

		if ($vars['files'] == 1)
		{
			$vars['del_notice'] =  'confirm_del_file';
		}
		else
		{
			$vars['del_notice'] = 'confirm_del_files';
		}

		$this->cp->set_variable('cp_page_title', lang('delete_selected_files'));

		$this->javascript->compile();

		$this->load->view('content/file_delete_confirm', $vars);

	}

	// --------------------------------------------------------------------

	/**
	 * Delete Files
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function delete_files()
	{
		$files = $this->input->get_post('file');

		if ( ! $files)
		{
			$this->session->set_flashdata('message_failure', lang('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		$delete_problem = FALSE;

		foreach($files as $file)
		{
			$file	= rtrim($this->_decode($file), DIRECTORY_SEPARATOR);
			$thumb	= substr($file, strrpos($file, DIRECTORY_SEPARATOR) + 1);
			
			$path		= substr($file, 0, strrpos($file, DIRECTORY_SEPARATOR) + 1);
			$thumb_path	= $path.'_thumbs'.DIRECTORY_SEPARATOR.'thumb_'.$thumb;

			if ( ! @unlink($file))
			{
				$delete_problem = TRUE;
				// @confirm continue;
			}

			// Delete thumb also
			if (file_exists($thumb_path))
			{
				@unlink($thumb_path);
			}
		}

		if ($delete_problem)
		{
			// something's up.
			$this->session->set_flashdata('message_failure', lang('delete_fail'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		$this->session->set_flashdata('message_success', lang('delete_success'));
		$this->functions->redirect(BASE.AMP.'C=content_files');
	}

	// --------------------------------------------------------------------

	/**
	 * Show an Image
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function display_image()
	{
		$this->load->helper('file');

		$this->output->set_header('Content-Type: image/png');
		$file = $this->_decode($this->input->get_post('file'));
		echo read_file($file);
	}

	// --------------------------------------------------------------------

	/**
	 * Show Image Editing Screen
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function prep_edit_image()
	{
		$this->load->helper(array('form', 'string', 'url', 'file'));

		$file	  = $this->_decode($this->input->get_post('file'));
		$url_path = $this->_decode($this->input->get_post('url_path')).'?f='.time();

		if ($file == '')
		{
			$this->session->set_flashdata('message_failure', lang('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}
		
		$this->javascript->set_global(array(
			'filemanager.url_path'	=> $url_path,
			
			'lang'					=> array(
				'no'					=> lang('no'),
				'hide_toolbar'			=> lang('hide_toolbar'),
				'show_toolbar'			=> lang('show_toolbar'),
				'apply_changes'			=> lang('apply_changes'),
				'exit_apply_changes'	=> lang('exit_apply_changes')
			)
		));
		
		$this->cp->add_js_script(array(
			'ui'		=> array('resizable', 'dialog'),
			'plugin'	=> 'jcrop',
			'file'		=> 'cp/file_manager_edit'
		));

		$this->cp->set_variable('cp_page_title', lang('edit').' '.substr(strrchr($file, '/'), 1));
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=content_files' => lang('content_files')
		));
		
		$data = array(
			'file'				=> $file,
			'url_path'			=> $url_path,
			'rotate_selected'	=> 'none',
			
			'form_hidden'		=> array(
				'file'				=> $this->input->get_post('file'),
				'url_path'			=> $this->input->get_post('url_path')
			),
			'rotate_options'	=> array(
				"none"				=> lang('none'),
				"90"				=> lang('rotate_90r'),
				"270"				=> lang('rotate_90l'),
				"180"				=> lang('rotate_180'),
				"vrt"				=> lang('rotate_flip_vert'),
				"hor"				=> lang('rotate_flip_hor')
			)
		);

		$params = array(
			'resize_width', 'resize_height',
			'crop_width', 'crop_height', 'crop_x', 'crop_y'
		);
		
		foreach ($params as $k => $param)
		{
			$data[$param] = array(
				'autocomplete' => 'off',
				'name'	=> $param,
				'id'	=> $param,
				'size'	=> 5,
				'value'	=> 0
			);
			
			if ($k > 1)
			{
				$data[$param]['class'] = 'crop_dim';
			}
		}

		$this->javascript->compile();

		$this->load->view('content/prep_edit_image', $data);
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
		if ($this->input->get_post('edit_done'))
		{
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}
		
		$this->load->library('filemanager');

		$this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
		$this->output->set_header("Pragma: no-cache");

		$file = str_replace(DIRECTORY_SEPARATOR, '/', $this->_decode($this->input->get_post('file')));

		if ($file == '')
		{
			// nothing for you here
			$this->session->set_flashdata('message_failure', lang('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		// crop takes precendence over resize
		// we need at least a width
		if ($this->input->get_post('crop_width') != '' AND $this->input->get_post('crop_width') != 0)
		{

			$config['width'] = $this->input->get_post('crop_width');
			$config['maintain_ratio'] = FALSE;
			$config['x_axis'] = $this->input->get_post('crop_x');
			$config['y_axis'] = $this->input->get_post('crop_y');
			$action = 'crop';

			if ($this->input->get_post('crop_height') != '')
			{
				$config['height'] = $this->input->get_post('crop_height');
			}
			else
			{
				$config['master_dim'] = 'width';
			}
		}
		elseif ($this->input->get_post('resize_width') != '' AND $this->input->get_post('resize_width') != 0)
		{
			$config['width'] = $this->input->get_post('resize_width');
			$config['maintain_ratio'] = $this->input->get_post("constrain");
			$action = 'resize';

			if ($this->input->get_post('resize_height') != '')
			{
				$config['height'] = $this->input->get_post('resize_height');
			}
			else
			{
				$config['master_dim'] = 'width';
			}
		}
		elseif ($this->input->get_post('rotate') != '' AND $this->input->get_post('rotate') != 'none')
		{
			$action = 'rotate';
			$config['rotation_angle'] = $this->input->get_post('rotate');
		}
		else
		{
			if ($this->input->get_post('is_ajax'))
			{
				header('HTTP', true, 500);
				exit(lang('width_needed'));
			}

			show_error(lang('width_needed'));
		}

		$config['library_path']	 = $this->config->item('image_library_path'); 
		$config['image_library'] = $this->config->item('image_resize_protocol');
		$config['source_image']	 = $file;
		$config['new_image']	 = $file;

		$this->load->library('image_lib', $config);

		$errors = '';

		// $action is one of: resize, rotate, crop

		if ( ! $this->image_lib->$action())
		{
	    	$errors = $this->image_lib->display_errors();
		}

		// Any reportable errors? If this is coming from ajax, just the error messages will suffice
		if ($errors != '')
		{
			if (AJAX_REQUEST)
			{
				header('HTTP', true, 500);
				exit($errors);
			}
			
			show_error($errors);
		}

		$dimensions = $this->image_lib->get_image_properties('', TRUE);
		$this->image_lib->clear();

		// Rebuild thumb
		$this->filemanager->create_thumb(array(
					'server_path'	=> substr($file, 0, strrpos($file, '/'))),
					array('name'	=> basename($file)
			)
		);
		
		if (AJAX_REQUEST)
		{
			exit('width="'.$dimensions['width'].'" height="'.$dimensions['height'].'" ');
		}
		
		$url = BASE.AMP.'C=content_files'.AMP.'M=prep_edit_image'.AMP.'file='.rawurlencode($this->input->get_post('file')).AMP.'url_path='.rawurlencode($this->input->get_post('url_path'));
		
		$this->functions->redirect($url);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Encode for url
	 * 
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	private function _encode($data)
	{
		$this->load->library('encrypt');
		
		return rawurlencode($this->encrypt->encode($data, $this->session->sess_crypt_key));
	}
	
	// --------------------------------------------------------------------

	/**
	 * Decode from url
	 * 
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	private function _decode($data)
	{
		$this->load->library('encrypt');
		
		return $this->encrypt->decode(rawurldecode($data), $this->session->sess_crypt_key);
	}
}
// END CLASS

/* End of file content_files.php */
/* Location: ./system/expressionengine/controllers/cp/content_files.php */