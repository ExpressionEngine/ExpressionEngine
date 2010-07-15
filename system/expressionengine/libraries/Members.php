<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Members library
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Libraries
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Members {
	
	
	var $EE;
	
	function Members()
	{
		$this->EE =& get_instance();
	}
	
	// ------------------------------------------------------------------------	


	/**
	 *	Upload Member Images
	 *
	 *	This method is used by both the member module (mod.member_images.php) and the myaccount controller
	 *	Return values are dependent on if this is a CP or PAGE request.
	 *
	 *	@param 	string	avatar/photo/sig_img
	 *	@param 	int		member id being updated
	 *	@return mixed
	 */
	
	function upload_member_images($type = 'avatar', $id)
	{
		// validate for unallowed blank values
		if (empty($_POST)) 
		{
			if (REQ == 'CP')
			{
				show_error($this->EE->lang->line('not_authorized'));				
			}
			else
			{
				$this->EE->output->show_user_error('submission', $this->EE->lang->line('not_authorized'));
			}
		}
		
		// Load the member model!
		$this->EE->load->model('member_model');

		switch ($type)
		{
			case 'avatar'	:
								$edit_image		= 'edit_avatar';
								$enable_pref	= 'allow_avatar_uploads';
								$not_enabled	= 'avatars_not_enabled';
								$remove			= 'remove_avatar';
								$removed		= 'avatar_removed';
								$updated		= 'avatar_updated';
				break;
			case 'photo'	:
								$edit_image		= 'edit_photo';
								$enable_pref	= 'enable_photos';
								$not_enabled	= 'photos_not_enabled';
								$remove			= 'remove_photo';
								$removed		= 'photo_removed';
								$updated		= 'photo_updated';

				break;
			case 'sig_img'		:
								$edit_image		= 'edit_signature';
								$enable_pref	= 'sig_allow_img_upload';
								$not_enabled	= 'sig_img_not_enabled';
								$remove			= 'remove_sig_image';
								$removed		= 'sig_img_removed';
								$updated		= 'signature_updated';
				break;
		}

		//Is this a remove request?

		if ( ! isset($_POST['remove']))
		{
			if ($this->EE->config->item($enable_pref) == 'n')
			{
				if (REQ == 'CP')
				{
					show_error($this->EE->lang->line($not_enabled));					
				}
				else
				{
					return array('error', array($not_enabled, $not_enabled));
				}
			}
		}
		else
		{
			if ($type == 'avatar')
			{
				$this->EE->db->select('avatar_filename');
				$this->EE->db->where('member_id', $id);
				$query = $this->EE->db->get('members');

				if ($query->row('avatar_filename')	== '')
				{
					if (REQ == 'CP')
					{
						// Returning type, method to call.
						return array('page', 'edit_avatar');
					}
					else
					{
						return array('redirect', array($edit_image));
					}
				}

				$this->EE->db->where('member_id', $id);
				$this->EE->db->set('avatar_filename', '');
				$this->EE->db->update('members');

				if (strncmp($query->row('avatar_filename'), 'uploads/', 8) == 0)
				{
					@unlink($this->EE->config->slash_item('avatar_path').$query->row('avatar_filename') );
				}

				if (REQ == 'CP')
				{
					$this->EE->session->set_flashdata('message_success', $this->EE->lang->line($removed));
					$this->EE->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_avatar'.AMP.'id='.$id);					
				}
			}
			elseif ($type == 'photo')
			{
				$this->EE->db->select('photo_filename');
				$this->EE->db->where('member_id', $id);
				$query = $this->EE->db->get('members');

				if ($query->row('photo_filename')  == '')
				{
					if (REQ == 'CP')
					{
						$this->EE->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_photo'.AMP.'id='.$id);						
					}
					else
					{
						return array('redirect', array($edit_image));
					}
				}
				
				$this->EE->db->set('photo_filename', '');
				$this->EE->db->where('member_id', $id);
				$this->EE->db->update('members');

				@unlink($this->EE->config->slash_item('photo_path').$query->row('photo_filename') );

				if (REQ == 'CP')
				{
					// Returning type, method to call + args.
					return array('page', 'edit_avatar', array($this->EE->lang->line($removed)));
				}
			}
			else
			{
				$this->EE->db->select('sig_img_filename');
				$this->EE->db->where('member_id', $id);
				$query = $this->EE->db->get('members');
				
				if ($query->row('sig_img_filename')	 == '')
				{
					if (REQ == 'CP')
					{
						return $this->EE->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_signature'.AMP.'id='.$id);						
					}
					else
					{
						return array('redirect', array($edit_image));
					}
				}
				
				$this->EE->db->set('sig_img_filename', '');
				$this->EE->db->where('member_id', $id);
				$this->EE->db->update('members');

				@unlink($this->EE->config->slash_item('sig_img_path').$query->row('sig_img_filename') );

				if (REQ == 'CP')
				{
					$this->EE->session->set_flashdata('message_success', $this->EE->lang->line($removed));
					$this->EE->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_signature'.AMP.'id='.$id);					
				}
			}
			
			if (REQ == 'PAGE')
			{
				return array('var_swap',
							array('success',
								array(
									'lang:heading'	=>	$this->EE->lang->line($remove),
									'lang:message'	=>	$this->EE->lang->line($removed)								
								)
							)
						);				
			}
		}

		// Do the have the GD library?
		if ( ! function_exists('getimagesize'))
		{
			if (REQ == 'CP')
			{
				show_error('gd_required');				
			}
			else
			{
				return array('error', array($edit_image, 'gd_required'));
			}
		}

		// Is there $_FILES data?

		if ( ! isset($_FILES['userfile']))
		{
			if (REQ == 'CP')
			{
				$this->EE->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_'.$type.AMP.'id='.$id);				
			}
			else
			{
				return array('redirect', $edit_image);
			}
		}

		// Check the image size
		$size = ceil(($_FILES['userfile']['size'] / 1024));

		if ($type == 'avatar')
		{
			$max_size = ($this->EE->config->item('avatar_max_kb') == '' OR $this->EE->config->item('avatar_max_kb') == 0) ? 50 : $this->EE->config->item('avatar_max_kb');
		}
		elseif ($type == 'photo')
		{
			$max_size = ($this->EE->config->item('photo_max_kb') == '' OR $this->EE->config->item('photo_max_kb') == 0) ? 50 : $this->EE->config->item('photo_max_kb');
		}
		else
		{
			$max_size = ($this->EE->config->item('sig_img_max_kb') == '' OR $this->EE->config->item('sig_img_max_kb') == 0) ? 50 : $this->EE->config->item('sig_img_max_kb');
		}

		$max_size = preg_replace("/(\D+)/", "", $max_size);

		if ($size > $max_size)
		{
			if (REQ == 'CP')
			{
				show_error(sprintf($this->EE->lang->line('image_max_size_exceeded'), $max_size));				
			}
			else
			{
				$this->EE->output->show_user_error('submission',
												sprintf(
													$this->EE->lang->line('image_max_size_exceeded'), 
													$max_size)
										);
			}
		}

		// Is the upload path valid and writable?

		if ($type == 'avatar')
		{
			$upload_path = $this->EE->config->slash_item('avatar_path').'uploads/';
		}
		elseif ($type == 'photo')
		{
			$upload_path = $this->EE->config->slash_item('photo_path');
		}
		else
		{
			$upload_path = $this->EE->config->slash_item('sig_img_path');
		}

		if ( ! @is_dir($upload_path) OR ! is_really_writable($upload_path))
		{
			if (REQ == 'CP')
			{
				show_error('image_assignment_error');				
			}
			else
			{
				return array('error', array($edit_image, 'image_assignment_error'));
			}
		}

		// Set some defaults
		$filename = $_FILES['userfile']['name'];

		if ($type == 'avatar')
		{
			$max_width	= ($this->EE->config->item('avatar_max_width') == '' OR $this->EE->config->item('avatar_max_width') == 0) ? 100 : $this->EE->config->item('avatar_max_width');
			$max_height = ($this->EE->config->item('avatar_max_height') == '' OR $this->EE->config->item('avatar_max_height') == 0) ? 100 : $this->EE->config->item('avatar_max_height');
			$max_kb		= ($this->EE->config->item('avatar_max_kb') == '' OR $this->EE->config->item('avatar_max_kb') == 0) ? 50 : $this->EE->config->item('avatar_max_kb');
		}
		elseif ($type == 'photo')
		{
			$max_width	= ($this->EE->config->item('photo_max_width') == '' OR $this->EE->config->item('photo_max_width') == 0) ? 100 : $this->EE->config->item('photo_max_width');
			$max_height = ($this->EE->config->item('photo_max_height') == '' OR $this->EE->config->item('photo_max_height') == 0) ? 100 : $this->EE->config->item('photo_max_height');
			$max_kb		= ($this->EE->config->item('photo_max_kb') == '' OR $this->EE->config->item('photo_max_kb') == 0) ? 50 : $this->EE->config->item('photo_max_kb');
		}
		else
		{
			$max_width	= ($this->EE->config->item('sig_img_max_width') == '' OR $this->EE->config->item('sig_img_max_width') == 0) ? 100 : $this->EE->config->item('sig_img_max_width');
			$max_height = ($this->EE->config->item('sig_img_max_height') == '' OR $this->EE->config->item('sig_img_max_height') == 0) ? 100 : $this->EE->config->item('sig_img_max_height');
			$max_kb		= ($this->EE->config->item('sig_img_max_kb') == '' OR $this->EE->config->item('sig_img_max_kb') == 0) ? 50 : $this->EE->config->item('sig_img_max_kb');
		}

		// Does the image have a file extension?
		if (strpos($filename, '.') === FALSE)
		{
			if (REQ == 'CP')
			{
				show_error($this->EE->lang->line('invalid_image_type'));				
			}
			else
			{
				$this->EE->output->show_user_error('submission', $this->EE->lang->line('invalid_image_type'));
			}
		}

		// Is it an allowed image type?

		$x = explode('.', $filename);
		$extension = '.'.end($x);

		// We'll do a simple extension check now.
		// The file upload class will do a more thorough check later

		$types = array('.jpg', '.jpeg', '.gif', '.png');

		if ( ! in_array(strtolower($extension), $types))
		{
			if (REQ == 'CP')
			{
				show_error($this->EE->lang->line('invalid_image_type'));				
			}
			else
			{
				return $this->EE->output->show_user_error('submission', $this->EE->lang->line('invalid_image_type'));
			}
		}

		// Assign the name of the image
		$new_filename = $type.'_'.$id.strtolower($extension);

		// Do they currently have an avatar or photo?
		if ($type == 'avatar')
		{
			$query = $this->EE->member_model->get_member_data($id, array('avatar_filename'));
			$old_filename = ($query->row('avatar_filename')	 == '') ? '' : $query->row('avatar_filename') ;

			if (strpos($old_filename, '/') !== FALSE)
			{
				$x = explode('/', $old_filename);
				$old_filename =	 end($x);
			}
		}
		elseif ($type == 'photo')
		{
			$query = $this->EE->member_model->get_member_data($id, array('photo_filename'));
			$old_filename = ($query->row('photo_filename')	== '') ? '' : $query->row('photo_filename') ;
		}
		else
		{
			$query = $this->EE->member_model->get_member_data($id, array('sig_img_filename'));
			$old_filename = ($query->row('sig_img_filename')  == '') ? '' : $query->row('sig_img_filename') ;
		}

		// Upload the image
		$config['file_name'] = $new_filename;
		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = 'gif|jpg|jpeg|png';
		$config['max_size']	= $max_kb;
		$config['max_width']  = $max_width;
		$config['max_height']  = $max_height;
		$config['overwrite'] = TRUE;

		if ($this->EE->config->item('xss_clean_uploads') == 'n')
		{
			$config['xss_clean'] = FALSE;
		}
		else
		{
			$config['xss_clean'] = ($this->EE->session->userdata('group_id') == 1) ? FALSE : TRUE;
		}

		$this->EE->load->library('upload', $config);
		
		if ($this->EE->upload->do_upload() === FALSE)
		{
			if (REQ == 'CP')
			{
				$this->EE->session->set_flashdata('message_failure', $this->EE->upload->display_errors());
				$this->EE->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M='.$edit_image.AMP.'id='.$id);				
			}
			else
			{
				return $this->EE->output->show_user_error(
											'submission',
				 							$this->EE->lang->line($this->EE->upload->display_errors())
										);
			}
		}

		$file_info = $this->EE->upload->data();
		
		// Do we need to resize?
		$width	= $file_info['image_width'];
		$height = $file_info['image_height'];

		// Delete the old file if necessary
		if ($old_filename != $new_filename)
		{
			@unlink($upload_path.$old_filename);
		}

		// Update DB
		if ($type == 'avatar')
		{
			$avatar = 'uploads/'.$new_filename;
			$data = array(
							'avatar_filename' 	=> $avatar,
							'avatar_width' 		=> $width,
							'avatar_height' 	=> $height
			);
		}
		elseif ($type == 'photo')
		{
			$data = array(
							'photo_filename' 	=> $new_filename,
							'photo_width' 		=> $width,
							'photo_height'		=> $height
			);
		}
		else
		{
			$data = array(
							'sig_img_filename' 	=> $new_filename,
							'sig_img_width' 	=> $width,
							'sig_img_height' 	=> $height
			);
		}

		$this->EE->member_model->update_member($id, $data);

		return array('success', $edit_image, $updated);	
	}
	
	// ------------------------------------------------------------------------

	/**
	 *	Resize Member Images
	 *
	 *
	 *	@param 	string
	 *	@param 	string	avatar/photo/sig_img
	 *	@param 	string
	 *	@return bool
	 */
	
	function image_resize($filename, $type = 'avatar', $axis = 'width')
	{
		$this->EE->load->library('image_lib');

		if ($type == 'avatar')
		{
			$max_width	= ($this->config->item('avatar_max_width') == '' OR $this->config->item('avatar_max_width') == 0) ? 100 : $this->config->item('avatar_max_width');
			$max_height = ($this->config->item('avatar_max_height') == '' OR $this->config->item('avatar_max_height') == 0) ? 100 : $this->config->item('avatar_max_height');
			$image_path = $this->config->slash_item('avatar_path').'uploads/';
		}
		else
		{
			$max_width	= ($this->config->item('photo_max_width') == '' OR $this->config->item('photo_max_width') == 0) ? 100 : $this->config->item('photo_max_width');
			$max_height = ($this->config->item('photo_max_height') == '' OR $this->config->item('photo_max_height') == 0) ? 100 : $this->config->item('photo_max_height');
			$image_path = $this->config->slash_item('photo_path');
		}

		$config = array(
				'image_library'		=> $this->config->item('image_resize_protocol'),
				'libpath'			=> $this->config->item('image_library_path'),
				'maintain_ratio'	=> TRUE,
				'master_dim'		=> $axis,
				'source_image'		=> $image_path.$filename,
				'quality'			=> 75,
				'width'				=> $max_width,
				'height'			=> $max_height				
			);
			
		$this->EE->image_lib->clear();
		
		$this->EE->image_lib->initialize($config);

		if ($this->EE->image_lib->resize() === FALSE)
		{
			return FALSE;
		}

		return TRUE;
	}

	
}
/* End of file members.php */
/* Location: ./system/expressionengine/libraries/members.php */