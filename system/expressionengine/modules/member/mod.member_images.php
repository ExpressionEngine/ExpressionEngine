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

// --------------------------------------------------------------------

/**
 * Member Management Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Member_images extends Member {

	/**
	 * Signature Edit Form
	 */
	public function edit_signature()
	{
		// Are signatures allowed?
		if ($this->EE->config->item('allow_signatures') == 'n')
		{
			return $this->_trigger_error('edit_signature', 'signatures_not_allowed');
		}

		// Create the HTML formatting buttons
		$buttons = '';

		$this->EE->load->library('html_buttons');

		$this->EE->html_buttons->allow_img = ($this->EE->config->item('sig_allow_img_hotlink') == 'y') ? TRUE : FALSE;
		$buttons = $this->EE->html_buttons->create_buttons();

		$query = $this->EE->db->select("signature, sig_img_filename, sig_img_width, sig_img_height")
							  ->where('member_id', (int) $this->EE->session->userdata('member_id'))
							  ->get('members');

		$template = $this->_load_element('signature_form');

		if ($this->EE->config->item('sig_allow_img_upload') == 'y')
		{
			$template = $this->_allow_if('upload_allowed', $template);
			$template = $this->_deny_if('upload_not_allowed', $template);
		}
		else
		{
			$template = $this->_allow_if('upload_not_allowed', $template);
			$template = $this->_deny_if('upload_allowed', $template);
		}
		if ($query->row('sig_img_filename')  == '' OR $this->EE->config->item('sig_allow_img_upload') == 'n')
		{
			$template = $this->_deny_if('image', $template);
			$template = $this->_allow_if('no_image', $template);
		}
		else
		{
			$template = $this->_allow_if('image', $template);
			$template = $this->_deny_if('no_image', $template);
		}

		$max_kb = ($this->EE->config->item('sig_img_max_kb') == '' OR $this->EE->config->item('sig_img_max_kb') == 0) ? 50 : $this->EE->config->item('sig_img_max_kb');
		$max_w  = ($this->EE->config->item('sig_img_max_width') == '' OR $this->EE->config->item('sig_img_max_width') == 0) ? 100 : $this->EE->config->item('sig_img_max_width');
		$max_h  = ($this->EE->config->item('sig_img_max_height') == '' OR $this->EE->config->item('sig_img_max_height') == 0) ? 100 : $this->EE->config->item('sig_img_max_height');
		$max_size = str_replace('%x', $max_w, lang('max_image_size'));
		$max_size = str_replace('%y', $max_h, $max_size);
		$max_size .= ' - '.$max_kb.'KB';

		$data = array(
						'action' 		=> $this->_member_path('update_signature'),
						'enctype'		=> 'multi',
						'id'			=> 'submit_post'
					);

		return $this->_var_swap($template,
			array(
					'form_declaration'			=> $this->EE->functions->form_declaration($data),
					'path:signature_image'		=> 	$this->EE->config->slash_item('sig_img_url').$query->row('sig_img_filename') ,
					'signature_image_width'		=> 	$query->row('sig_img_width') ,
					'signature_image_height'	=> 	$query->row('sig_img_height') ,
					'signature'					=>	$query->row('signature') ,
					'lang:max_image_size'		=>  $max_size,
					'maxchars'					=> ($this->EE->config->item('sig_maxlength') == 0) ? 10000 : $this->EE->config->item('sig_maxlength'),
					'include:html_formatting_buttons' => $buttons,
				 )
			);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Signature
	 */
	public function update_signature()
	{
		// Are signatures allowed?
		if ($this->EE->config->item('allow_signatures') == 'n')
		{
			return $this->_trigger_error('edit_signature', 'signatures_not_allowed');
		}

		$_POST['body'] = $this->EE->db->escape_str($this->EE->security->xss_clean($_POST['body']));

		$maxlength = ($this->EE->config->item('sig_maxlength') == 0) ? 10000 : $this->EE->config->item('sig_maxlength');

		if (strlen($_POST['body']) > $maxlength)
		{
			return $this->EE->output->show_user_error('submission', str_replace('%x', $maxlength, lang('sig_too_big')));
		}

		$this->EE->db->query("UPDATE exp_members SET signature = '".$_POST['body']."' WHERE member_id ='".$this->EE->session->userdata('member_id')."'");

		// Is there an image to upload or remove?
		if ((isset($_FILES['userfile']) AND 
			$_FILES['userfile']['name'] != '') OR 
			isset($_POST['remove']))
		{
			return $this->upload_signature_image();
		}

		// Success message
		return $this->_var_swap($this->_load_element('success'),
								array(
										'lang:heading'	=>	lang('signature'),
										'lang:message'	=>	lang('signature_updated')
									 )
								);
	}

	// --------------------------------------------------------------------

	/**
	 * Avatar Edit Form
	 */
	public function edit_avatar()
	{
		// Are avatars enabled?
		if ($this->EE->config->item('enable_avatars') == 'n')
		{
			return $this->_trigger_error('edit_avatar', 'avatars_not_enabled');
		}

		// Fetch the avatar template
		$template = $this->_load_element('edit_avatar');

		// Does the current user have an avatar?
		$query = $this->EE->db->select("avatar_filename, avatar_width, avatar_height")
							  ->where('member_id', (int) $this->EE->session->userdata('member_id'))
							  ->get('members');

		if ($query->row('avatar_filename')  == '')
		{
			$template = $this->_deny_if('avatar', $template);
			$template = $this->_allow_if('no_avatar', $template);

			$cur_avatar_url = '';
			$avatar_width 	= '';
			$avatar_height 	= '';
		}
		else
		{
			$template = $this->_allow_if('avatar', $template);
			$template = $this->_deny_if('no_avatar', $template);

			$cur_avatar_url = $this->EE->config->slash_item('avatar_url').$query->row('avatar_filename') ;
			$avatar_width 	= $query->row('avatar_width') ;
			$avatar_height 	= $query->row('avatar_height') ;
		}

		// Can users upload their own images?
		if ($this->EE->config->item('allow_avatar_uploads') == 'y')
		{
			$template = $this->_allow_if('can_upload_avatar', $template);
		}
		else
		{
			$template = $this->_deny_if('can_upload_avatar', $template);
		}

		// Are there pre-installed avatars?

		// We'll make a list of all folders in the "avatar" folder,
		// then check each one to see if they contain images.  If so
		// we will add it to the list

		$avatar_path = $this->EE->config->slash_item('avatar_path');

		$extensions = array('.gif', '.jpg', '.jpeg', '.png');

		if ( ! @is_dir($avatar_path) OR ! $fp = @opendir($avatar_path))
		{
			$template = $this->_deny_if('installed_avatars', $template);
		}
		else
		{
			$tmpl = $this->_load_element('avatar_folder_list');

		 	$folders = '';
		 
			while (FALSE !== ($file = readdir($fp)))
			{
				if (is_dir($avatar_path.$file) AND $file != 'uploads' AND $file != '.' AND $file != '..')
				{
					if ($np = @opendir($avatar_path.$file))
					{
						while (FALSE !== ($innerfile = readdir($np)))
						{
							if (FALSE !== ($pos = strpos($innerfile, '.')))
							{
								if (in_array(substr($innerfile, $pos), $extensions))
								{
									$name = ucwords(str_replace("_", " ", $file));

									$temp = $tmpl;

									$temp = str_replace('{path:folder_path}', $this->_member_path('browse_avatars/'.$file.'/'), $temp);
									$temp = str_replace('{folder_name}', $name, $temp);

									$folders .= $temp;

									break;
								}
							}
						}

						closedir($np);
					}
				}
			}

			closedir($fp);

			if ($folders == '')
			{
				$template = $this->_deny_if('installed_avatars', $template);
			}
			else
			{
				$template = $this->_allow_if('installed_avatars', $template);
			}

			$template = str_replace('{include:avatar_folder_list}', $folders, $template);
		}

		// Set the default image meta values
		$max_kb = ($this->EE->config->item('avatar_max_kb') == '' OR $this->EE->config->item('avatar_max_kb') == 0) ? 50 : $this->EE->config->item('avatar_max_kb');
		$max_w  = ($this->EE->config->item('avatar_max_width') == '' OR $this->EE->config->item('avatar_max_width') == 0) ? 100 : $this->EE->config->item('avatar_max_width');
		$max_h  = ($this->EE->config->item('avatar_max_height') == '' OR $this->EE->config->item('avatar_max_height') == 0) ? 100 : $this->EE->config->item('avatar_max_height');
		$max_size = str_replace('%x', $max_w, lang('max_image_size'));
		$max_size = str_replace('%y', $max_h, $max_size);
		$max_size .= ' - '.$max_kb.'KB';

		// Finalize the template
		return $this->_var_swap($template,
			array(
				'form_declaration'		=> $this->EE->functions->form_declaration(
					array(
						'action' 		=> $this->_member_path('upload_avatar'),
						'enctype'		=> 'multi'
					)
				),
				'lang:max_image_size'	=>  $max_size,
				'path:avatar_image'		=> 	$cur_avatar_url,
				'avatar_width'			=> 	$avatar_width,
				'avatar_height'			=>	$avatar_height
				)
			);
	}

	// --------------------------------------------------------------------

	/**
	 * Browse Avatars
	 */
	public function browse_avatars()
	{
		// Are avatars enabled?
		if ($this->EE->config->item('enable_avatars') == 'n')
		{
			return $this->_trigger_error('edit_avatar', 'avatars_not_enabled');
		}

		// Define the paths and get the images
		$avatar_path = $this->EE->config->slash_item('avatar_path').$this->EE->security->sanitize_filename($this->cur_id).'/';
		$avatar_url  = $this->EE->config->slash_item('avatar_url').$this->EE->security->sanitize_filename($this->cur_id).'/';

		$avatars = $this->_get_avatars($avatar_path);

		// Did we succeed?
		if (count($avatars) == 0)
		{
			return $this->_trigger_error('edit_avatar', 'avatars_not_found');
		}

		// Pagination anyone?
		$pagination = '';
		$max_rows	= 8;
		$max_cols	= 3;
		$col_ct		= 0;
		$perpage 	= $max_rows * $max_cols;
		$total_rows = count($avatars);
		$rownum 	= ($this->uri_extra == '') ? 0 : $this->uri_extra;
		$base_url	= $this->_member_path('browse_avatars/'.$this->cur_id.'/');

		if ($rownum > count($avatars))
		{
			$rownum = 0;			
		}

		if ($total_rows > $perpage)
		{
			$avatars = array_slice($avatars, $rownum, $perpage);

			$this->EE->load->library('pagination');

			$config['base_url']		= $base_url;
			$config['total_rows'] 	= $total_rows;
			$config['per_page']		= $perpage;
			$config['cur_page']		= $rownum;
			$config['first_link'] 	= lang('pag_first_link');
			$config['last_link'] 	= lang('pag_last_link');
				
			$this->EE->pagination->initialize($config);
			$pagination = $this->EE->pagination->create_links();			

			// We add this for use later

			if ($rownum != '')
			{
				$base_url .= $rownum.'/';
			}
		}

		// Build the table rows
		$avstr = '';
		foreach ($avatars as $image)
		{
			if ($col_ct == 0)
			{
				$avstr .= "<tr>\n";
			}

			$avstr .= "<td align='center'><img src='".$avatar_url.$image."' border='0' alt='".$image."'/><br /><input type='radio' name='avatar' value='".$image."' /></td>\n";
			$col_ct++;

			if ($col_ct == $max_cols)
			{
				$avstr .= "</tr>";
				$col_ct = 0;
			}
		}

		if ($col_ct < $max_cols AND count($avatars) >= $max_cols)
		{
			for ($i = $col_ct; $i < $max_cols; $i++)
			{
				$avstr .= "<td>&nbsp;</td>\n";
			}

			$avstr .= "</tr>";
		}

		if (substr($avstr, -5) != '</tr>')
		{
			$avstr .= "</tr>";
		}

		// Finalize the output
		$template = $this->_load_element('browse_avatars');

		if ($pagination == '')
		{
			$template = $this->_deny_if('pagination', $template);
		}
		else
		{
			$template = $this->_allow_if('pagination', $template);
		}


		return $this->_var_swap($template,
			array(
			'form_declaration'		=> $this->EE->functions->form_declaration(
				array(
					'action' 		=> $this->_member_path('select_avatar'),
					'hidden_fields'	=> array('referrer' => $base_url, 'folder' => $this->cur_id)
					)
				),
			'avatar_set'			=> ucwords(str_replace("_", " ", $this->cur_id)),
			'avatar_table_rows'		=> $avstr,
			'pagination'			=> $pagination
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Select Avatar From  Library
	 */
	public function select_avatar()
	{
		// Are avatars enabled?
		if ($this->EE->config->item('enable_avatars') == 'n')
		{
			return $this->_trigger_error('edit_avatar', 'avatars_not_enabled');
		}

		if ($this->EE->input->get_post('avatar') === FALSE OR 
			$this->EE->input->get_post('folder') === FALSE)
		{
			return $this->EE->functions->redirect($this->EE->input->get_post('referrer'));
		}

		$folder	= $this->EE->security->sanitize_filename($this->EE->input->get_post('folder'));
		$file	= $this->EE->security->sanitize_filename($this->EE->input->get_post('avatar'));
		
		$basepath 	= $this->EE->config->slash_item('avatar_path');
		$avatar		= $avatar	= $folder.'/'.$file;

		$allowed = $this->_get_avatars($basepath.$folder);

		if ( ! in_array($file, $allowed) OR $folder == 'upload')
		{
			return $this->_trigger_error('edit_avatar', 'avatars_not_found');
		}
		
		// Fetch the avatar meta-data		
		if ( ! function_exists('getimagesize'))
		{
			return $this->_trigger_error('edit_avatar', 'image_assignment_error');
		}

		$vals = @getimagesize($basepath.$avatar);
		$width	= $vals['0'];
		$height	= $vals['1'];

		// Update DB
		$this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
		$this->EE->db->update('members', array('avatar_filename' => $avatar, 'avatar_width' => $width, 'avatar_height' => $height));


		return $this->_var_swap($this->_load_element('success'),
								array(
										'lang:heading'	=>	lang('edit_avatar'),
										'lang:message'	=>	lang('avatar_updated')
									 )
								);
	}

	// --------------------------------------------------------------------

	/**
	 * List all Images in a Folder
	 */
	protected function _get_avatars($avatar_path)
	{
		// Is this a valid avatar folder?
	    $extensions = array('.gif', '.jpg', '.jpeg', '.png');

	    if ( ! @is_dir($avatar_path) OR ! $fp = @opendir($avatar_path))
	    {
	        return array();
	    }

	    // Grab the image names

	    $avatars = array();

	    while (FALSE !== ($file = readdir($fp))) 
	    { 
	        if (FALSE !== ($pos = strpos($file, '.')))
	        {
	            if (in_array(substr($file, $pos), $extensions))
	            {
	                $avatars[] = $file;
	            }
	        }                            
	    }

	    closedir($fp);

	    return $avatars;
	}

	// --------------------------------------------------------------------

	/**
	 * Upload Avatar or Profile Photo
	 */
	public function upload_avatar()
	{
		return $this->_upload_image('avatar');
	}

	// --------------------------------------------------------------------	

	/**
	 * Upload Photo
	 */
	function upload_photo()
	{
		return $this->_upload_image('photo');
	}

	// --------------------------------------------------------------------

	/**
	 * Upload Signature
	 */
	function upload_signature_image()
	{
		return $this->_upload_image('sig_img');
	}

	// --------------------------------------------------------------------

	/**
	 * Upload Image
	 */
	function _upload_image($type = 'avatar')
	{
		$this->EE->load->library('members');
		
		$upload = $this->EE->members->upload_member_images($type, $this->EE->session->userdata('member_id'));

		if (is_array($upload))
		{
			switch ($upload[0])
			{
				case 'success':
					$edit_image = $upload[1];
					$updated = $upload[2];
					break;
				case 'redirect':
					return $this->EE->functions->redirect($this->_member_path($upload[1][0]));
					break;
				case 'var_swap':
					return $this->_var_swap($this->_load_element($upload[1][0]), $upload[1][1]);
					break;
				case 'error':
					return call_user_func_array(array($this, '_trigger_error'), $upload[1]);
					break;
			}
		}

		// Success message
		return $this->_var_swap($this->_load_element('success'),
								array(
										'lang:heading'	=>	lang($edit_image),
										'lang:message'	=>	lang($updated)
									 )
								);
	}

	// --------------------------------------------------------------------

	/**
	 * Photo Edit Form
	 */
	public function edit_photo()
	{
		// Are photos enabled?
		if ($this->EE->config->item('enable_photos') == 'n')
		{
			return $this->_trigger_error('edit_photo', 'photos_not_enabled');
		}

		// Fetch the photo template
		$template = $this->_load_element('edit_photo');

		// Does the current user have a photo?
		$query = $this->EE->db->select('photo_filename, photo_width, photo_height')
							  ->where('member_id', (int) $this->EE->session->userdata('member_id'))
							  ->get('members');

		if ($query->row('photo_filename')  == '')
		{
			$template = $this->_deny_if('photo', $template);
			$template = $this->_allow_if('no_photo', $template);

			$cur_photo_url = '';
			$photo_width 	= '';
			$photo_height 	= '';
		}
		else
		{
			$template = $this->_allow_if('photo', $template);
			$template = $this->_deny_if('no_photo', $template);

			$cur_photo_url = $this->EE->config->slash_item('photo_url').$query->row('photo_filename') ;
			$photo_width 	= $query->row('photo_width') ;
			$photo_height 	= $query->row('photo_height') ;
		}

		// Set the default image meta values
		$max_kb = ($this->EE->config->item('photo_max_kb') == '' OR $this->EE->config->item('photo_max_kb') == 0) ? 50 : $this->EE->config->item('photo_max_kb');
		$max_w  = ($this->EE->config->item('photo_max_width') == '' OR $this->EE->config->item('photo_max_width') == 0) ? 100 : $this->EE->config->item('photo_max_width');
		$max_h  = ($this->EE->config->item('photo_max_height') == '' OR $this->EE->config->item('photo_max_height') == 0) ? 100 : $this->EE->config->item('photo_max_height');
		$max_size = str_replace('%x', $max_w, lang('max_image_size'));
		$max_size = str_replace('%y', $max_h, $max_size);
		$max_size .= ' - '.$max_kb.'KB';

		// Finalize the template
		return $this->_var_swap($template,
				array(
					'form_declaration'		=> $this->EE->functions->form_declaration(
						array(
							'action' 		=> $this->_member_path('upload_photo'),
							'enctype'		=> 'multi'
						)
				),
				'lang:max_image_size'	=>  $max_size,
				'path:member_photo'		=> 	$cur_photo_url,
				'photo_width'			=> 	$photo_width,
				'photo_height'			=>	$photo_height,
				'name'					=>  $query->row('photo_filename')
			)
		);
	}
}
// END CLASS

/* End of file mod.member_images.php */
/* Location: ./system/expressionengine/modules/member/mod.member_images.php */