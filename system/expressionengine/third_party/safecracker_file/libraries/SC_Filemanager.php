<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'libraries/Filemanager'.EXT;

class SC_Filemanager extends Filemanager
{
	public $overwrite = FALSE;
	public $field_id;
	
	public function __construct()
	{
		if (method_exists('Filemanager', 'Filemanager'))
		{
			parent::Filemanager();
		}
		else
		{
			parent::__construct();
		}
		
		$this->EE = get_instance();
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
	public function directory($dir_id, $ajax = FALSE, $return_all = FALSE)
	{
		$return_all = ($ajax) ? FALSE : $return_all;		// safety - ajax calls can never get all info!
		
		$dirs = $this->directories(FALSE, $return_all);

		$return = isset($dirs[$dir_id]) ? $dirs[$dir_id] : FALSE;
		
		//if not absolute path OR windows-style path
		if ( ! preg_match('#^(/|\w+:[/\\\])#', $return['server_path']))
		{
			$return['server_path'] = realpath(APPPATH.'../'.$return['server_path']).'/';
		}
		
		if ($ajax)
		{
			die($this->EE->javascript->generate_json($return));
		}
		
		return $return;
	}
	
	public function _initialize($config)
	{
		if ( ! empty($config['overwrite']))
		{
			$this->overwrite = $config['overwrite'];
		}
		
		if ( ! empty($config['field_id']))
		{
			$this->field_id = $config['field_id'];
		}
		
		parent::_initialize($config);
	}
	
	public function _upload_file($dir, $field_name)
	{
		$this->EE->load->helper('url');
		$this->EE->load->library('upload');

		$this->EE->db->select('field_type, field_content_type')
				->from('channel_fields')
				->where('field_id', $this->field_id);
		
		if ($this->EE->db->get()->row('field_content_type') == 'image' || $dir['allowed_types'] == 'img')
		{
			$dir['allowed_types'] = 'gif|jpg|jpeg|png|jpe';
		}
		else if ($dir['allowed_types'] == 'all')
		{
			$dir['allowed_types'] = '*';
		}
		
		$dir['upload_path'] = $dir['server_path'];
		
		$dir['overwrite'] = $this->overwrite;
		
		$dir['temp_prefix'] = '';
		
		$this->EE->upload->initialize($dir);

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
}

/* End of file SC_Filemanager.php */
/* Location: ./system/expressionengine/third_party/safecracker_file/libraries/SC_Filemanager.php */