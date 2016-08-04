<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Members {


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
	public function upload_member_images($type = 'avatar', $id)
	{
		// validate for unallowed blank values
		if (empty($_POST))
		{
			if (REQ == 'CP')
			{
				show_error(lang('not_authorized'));
			}
			else
			{
				ee()->output->show_user_error('submission', lang('not_authorized'));
			}
		}

		// Load the member model!
		ee()->load->model('member_model');

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
			if (ee()->config->item($enable_pref) == 'n')
			{
				if (REQ == 'CP')
				{
					show_error(lang($not_enabled));
				}

				return array('error', array($not_enabled, $not_enabled));
			}
		}
		else
		{
			if ($type == 'avatar')
			{
				$query = ee()->member_model->get_member_data($id, array('avatar_filename'));

				if ($query->row('avatar_filename')	== '')
				{
					if (REQ == 'CP')
					{
						// Returning type, method to call.
						return array('page', 'edit_avatar');
					}

					return array('redirect', array($edit_image));
				}

				ee()->member_model->update_member($id, array('avatar_filename' => ''));

				if (strncmp($query->row('avatar_filename'), 'default/', 8) !== 0)
				{
					@unlink(ee()->config->slash_item('avatar_path').$query->row('avatar_filename') );
				}
			}
			elseif ($type == 'photo')
			{
				$query = ee()->member_model->get_member_data($id, array('photo_filename'));

				if ($query->row('photo_filename')  == '')
				{
					if (REQ == 'CP')
					{
						ee()->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_photo'.AMP.'id='.$id);
					}

					return array('redirect', array($edit_image));
				}

				ee()->member_model->update_member($id, array('photo_filename' => ''));

				@unlink(ee()->config->slash_item('photo_path').$query->row('photo_filename') );
			}
			else
			{
				$query = ee()->member_model->get_member_data($id, array('sig_img_filename'));

				if ($query->row('sig_img_filename')	 == '')
				{
					if (REQ == 'CP')
					{
						return ee()->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_signature'.AMP.'id='.$id);
					}

					return array('redirect', array($edit_image));
				}

				ee()->member_model->update_member($id, array('sig_img_filename' => ''));

				@unlink(ee()->config->slash_item('sig_img_path').$query->row('sig_img_filename') );
			}

			if (REQ == 'CP')
			{
				ee()->session->set_flashdata('message_success', lang($removed));
				ee()->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M='.$edit_image.AMP.'id='.$id);
			}
			else if (REQ == 'PAGE')
			{
				return array('var_swap',
							array('success',
								array(
									'lang:heading'	=>	lang($remove),
									'lang:message'	=>	lang($removed)
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

			return array('error', array($edit_image, 'gd_required'));
		}

		// Is there $_FILES data?
		if ( ! isset($_FILES['userfile']))
		{
			if (REQ == 'CP')
			{
				ee()->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M=edit_'.$type.AMP.'id='.$id);
			}

			return array('redirect', $edit_image);
		}

		// Check the image size
		$size = ceil(($_FILES['userfile']['size'] / 1024));

		if ($type == 'avatar')
		{
			$max_size = (ee()->config->item('avatar_max_kb') == '' OR ee()->config->item('avatar_max_kb') == 0) ? 50 : ee()->config->item('avatar_max_kb');
		}
		elseif ($type == 'photo')
		{
			$max_size = (ee()->config->item('photo_max_kb') == '' OR ee()->config->item('photo_max_kb') == 0) ? 50 : ee()->config->item('photo_max_kb');
		}
		else
		{
			$max_size = (ee()->config->item('sig_img_max_kb') == '' OR ee()->config->item('sig_img_max_kb') == 0) ? 50 : ee()->config->item('sig_img_max_kb');
		}

		$max_size = preg_replace("/(\D+)/", "", $max_size);

		if ($size > $max_size)
		{
			if (REQ == 'CP')
			{
				show_error(sprintf(lang('image_max_size_exceeded'), $max_size));
			}

			ee()->output->show_user_error('submission',
											sprintf(
												lang('image_max_size_exceeded'),
												$max_size)
									);
		}

		// Is the upload path valid and writable?

		if ($type == 'avatar')
		{
			$upload_path = ee()->config->slash_item('avatar_path');
		}
		elseif ($type == 'photo')
		{
			$upload_path = ee()->config->slash_item('photo_path');
		}
		else
		{
			$upload_path = ee()->config->slash_item('sig_img_path');
		}

		if ( ! @is_dir($upload_path) OR ! is_really_writable($upload_path))
		{
			if (REQ == 'CP')
			{
				show_error('image_assignment_error');
			}

			return array('error', array($edit_image, 'image_assignment_error'));
		}

		// Set some defaults
		$filename = $_FILES['userfile']['name'];

		if ($type == 'avatar')
		{
			$max_width	= (ee()->config->item('avatar_max_width') == '' OR ee()->config->item('avatar_max_width') == 0) ? 100 : ee()->config->item('avatar_max_width');
			$max_height = (ee()->config->item('avatar_max_height') == '' OR ee()->config->item('avatar_max_height') == 0) ? 100 : ee()->config->item('avatar_max_height');
			$max_kb		= (ee()->config->item('avatar_max_kb') == '' OR ee()->config->item('avatar_max_kb') == 0) ? 50 : ee()->config->item('avatar_max_kb');
		}
		elseif ($type == 'photo')
		{
			$max_width	= (ee()->config->item('photo_max_width') == '' OR ee()->config->item('photo_max_width') == 0) ? 100 : ee()->config->item('photo_max_width');
			$max_height = (ee()->config->item('photo_max_height') == '' OR ee()->config->item('photo_max_height') == 0) ? 100 : ee()->config->item('photo_max_height');
			$max_kb		= (ee()->config->item('photo_max_kb') == '' OR ee()->config->item('photo_max_kb') == 0) ? 50 : ee()->config->item('photo_max_kb');
		}
		else
		{
			$max_width	= (ee()->config->item('sig_img_max_width') == '' OR ee()->config->item('sig_img_max_width') == 0) ? 100 : ee()->config->item('sig_img_max_width');
			$max_height = (ee()->config->item('sig_img_max_height') == '' OR ee()->config->item('sig_img_max_height') == 0) ? 100 : ee()->config->item('sig_img_max_height');
			$max_kb		= (ee()->config->item('sig_img_max_kb') == '' OR ee()->config->item('sig_img_max_kb') == 0) ? 50 : ee()->config->item('sig_img_max_kb');
		}

		// Does the image have a file extension?
		if (strpos($filename, '.') === FALSE)
		{
			if (REQ == 'CP')
			{
				show_error(lang('invalid_image_type'));
			}

			ee()->output->show_user_error('submission', lang('invalid_image_type'));
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
				show_error(lang('invalid_image_type'));
			}

			return ee()->output->show_user_error('submission', lang('invalid_image_type'));
		}

		// Assign the name of the image
		$new_filename = $type.'_'.$id.strtolower($extension);

		// Do they currently have an avatar or photo?
		if ($type == 'avatar')
		{
			$query = ee()->member_model->get_member_data($id, array('avatar_filename'));
			$old_filename = ($query->row('avatar_filename')	 == '') ? '' : $query->row('avatar_filename') ;

			if (strpos($old_filename, '/') !== FALSE)
			{
				$x = explode('/', $old_filename);
				$old_filename =	 end($x);
			}
		}
		elseif ($type == 'photo')
		{
			$query = ee()->member_model->get_member_data($id, array('photo_filename'));
			$old_filename = ($query->row('photo_filename')	== '') ? '' : $query->row('photo_filename') ;
		}
		else
		{
			$query = ee()->member_model->get_member_data($id, array('sig_img_filename'));
			$old_filename = ($query->row('sig_img_filename')  == '') ? '' : $query->row('sig_img_filename') ;
		}

		// Upload the image
		$config['file_name'] = $new_filename;
		$config['upload_path'] = $upload_path;
		$config['is_image'] = TRUE;
		$config['max_size']	= $max_kb;
		$config['max_width']  = $max_width;
		$config['max_height']  = $max_height;
		$config['overwrite'] = TRUE;

		if (ee()->config->item('xss_clean_uploads') == 'n')
		{
			$config['xss_clean'] = FALSE;
		}
		else
		{
			$config['xss_clean'] = (ee()->session->userdata('group_id') == 1) ? FALSE : TRUE;
		}

		ee()->load->library('upload', $config);

		if (ee()->upload->do_upload() === FALSE)
		{
			if (REQ == 'CP')
			{
				ee()->session->set_flashdata('message_failure', ee()->upload->display_errors());
				ee()->functions->redirect(BASE.AMP.'C=myaccount'.AMP.'M='.$edit_image.AMP.'id='.$id);
			}

			return ee()->output->show_user_error(
											'submission',
				 							lang(ee()->upload->display_errors())
										);
		}

		$file_info = ee()->upload->data();

		@chmod($file_info['full_path'], FILE_WRITE_MODE);

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
			$avatar = $new_filename;
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

		ee()->member_model->update_member($id, $data);

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
	public function image_resize($filename, $type = 'avatar', $axis = 'width')
	{
		ee()->load->library('image_lib');

		if ($type == 'avatar')
		{
			$max_width	= ($this->config->item('avatar_max_width') == '' OR $this->config->item('avatar_max_width') == 0) ? 100 : $this->config->item('avatar_max_width');
			$max_height = ($this->config->item('avatar_max_height') == '' OR $this->config->item('avatar_max_height') == 0) ? 100 : $this->config->item('avatar_max_height');
			$image_path = $this->config->slash_item('avatar_path');
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

		ee()->image_lib->clear();

		ee()->image_lib->initialize($config);

		return ( ! ee()->image_lib->resize()) ? FALSE : TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 *	Get formatted list of member subscriptions
	 *
	 *
	 *	@param 	int
	 *	@param 	int
	 *	@param 	int
	 *	@return array
	 */
	public function get_member_subscriptions($member_id, $rownum = 0, $perpage = 50)
	{
		ee()->load->helper('url');

		// Set some base values
		$channel_subscriptions	= FALSE;
		$forum_subscriptions	= FALSE;
		$result_ids				= array();
		$total_count			= 0;
		$qm						= (ee()->config->item('force_query_string') == 'y') ? '' : '?';

		if (ee()->db->table_exists('exp_comment_subscriptions'))
		{
			// Fetch Comment Subscriptions
			ee()->db->distinct();
			ee()->db->select('comment_subscriptions.entry_id, recent_comment_date, subscription_date, hash');
			ee()->db->from('comment_subscriptions');
			ee()->db->join('channel_titles', 'comment_subscriptions.entry_id = channel_titles.entry_id', 'left');
			ee()->db->where('member_id', $member_id);
			ee()->db->order_by("recent_comment_date", "desc");
			$query = ee()->db->get();

			if ($query->num_rows() > 0)
			{
				$channel_subscriptions = TRUE;

				foreach ($query->result_array() as $row)
				{
					// Can have duplicate zeros for comment date- so combine with subscription date
					$date_key = str_pad($row['recent_comment_date'], 14, '0',  STR_PAD_LEFT).
						str_pad($row['subscription_date'], 14, '0',  STR_PAD_LEFT).
						'b';

					$result_ids[$date_key] = $row['entry_id'];
					$total_count++;
				}
			}
		}
		// Fetch Forum Topic Subscriptions
		// Since the forum module might not be installed we'll test for it first.

		if (ee()->db->table_exists('exp_forum_subscriptions'))
		{
			// Fetch Forum Subscriptions
			ee()->db->select('forum_subscriptions.topic_id, last_post_date, subscription_date, hash');
			ee()->db->from('forum_subscriptions');
			ee()->db->join('forum_topics', 'forum_subscriptions.topic_id = forum_topics.topic_id', 'left');
			ee()->db->where('member_id', $member_id);
			ee()->db->order_by("last_post_date", "desc");
			$query = ee()->db->get();

			if ($query->num_rows() > 0)
			{
				$forum_subscriptions = TRUE;

				foreach ($query->result_array() as $row)
				{
					$date_key = str_pad($row['last_post_date'], 14, '0',  STR_PAD_LEFT).
						str_pad($row['subscription_date'], 14, '0',  STR_PAD_LEFT).
						'f';

					$result_ids[$date_key] = $row['topic_id'];
					$total_count++;
				}
			}
		}


		krsort($result_ids); // Sort the array

		$result_ids = array_slice($result_ids, $rownum, $perpage);

		// Fetch Channel Titles
		if ($channel_subscriptions == TRUE)
		{
			$sql = "SELECT
					exp_channel_titles.title, exp_channel_titles.url_title, exp_channel_titles.channel_id, exp_channel_titles.entry_id, exp_channel_titles.recent_comment_date,
					exp_channels.comment_url, exp_channels.channel_url
					FROM exp_channel_titles
					LEFT JOIN exp_channels ON exp_channel_titles.channel_id = exp_channels.channel_id
					WHERE entry_id IN (";

			$idx = '';
			$channel_keys = array();

			foreach ($result_ids as $key => $val)
			{
				if (substr($key, strlen($key)-1) == 'b')
				{
					$idx .= $val.",";
					$channel_keys[$val] = $key;
				}
			}

			$idx = substr($idx, 0, -1);

			if ($idx != '')
			{
				$query = ee()->db->query($sql.$idx.') ');

				if ($query->num_rows() > 0)
				{
					foreach ($query->result_array() as $row)
					{
						$row['title'] = str_replace(array('<', '>', '{', '}', '\'', '"', '?'), array('&lt;', '&gt;', '&#123;', '&#125;', '&#146;', '&quot;', '&#63;'), $row['title']);

						$path = reduce_double_slashes(ee()->functions->prep_query_string(($row['comment_url'] != '') ? $row['comment_url'] : $row['channel_url']).'/'.$row['url_title'].'/');
						$path = parse_config_variables($path);

						$result_ids[$channel_keys[$row['entry_id']]] = array(
												'title' => $row['title'],
												'active_date' => $row['recent_comment_date'],
												'url_title' => url_title($row['title']),
												'path' => ee()->functions->fetch_site_index().$qm.'URL='.$path,
												'id'	=> 'b'.$row['entry_id'],
												'type'	=> lang('comment')
												);
					}
				}
			}
		}

		// Fetch Forum Topics
		if ($forum_subscriptions == TRUE)
		{
			$sql = "SELECT title, topic_id, board_forum_url, last_post_date FROM exp_forum_topics, exp_forum_boards
					WHERE exp_forum_topics.board_id = exp_forum_boards.board_id
					AND topic_id IN (";

			$idx = '';
			$forum_keys = array();

			foreach ($result_ids as $key => $val)
			{
				if (substr($key, strlen($key)-1) == 'f')
				{
					$idx .= $val.",";
					$forum_keys[$val] = $key;
				}
			}

			$idx = substr($idx, 0, -1);

			if ($idx != '')
			{
				$query = ee()->db->query($sql.$idx.') ');

				if ($query->num_rows() > 0)
				{
					foreach ($query->result_array() as $row)
					{
						$row['title'] = str_replace(array('<', '>', '{', '}', '\'', '"', '?'), array('&lt;', '&gt;', '&#123;', '&#125;', '&#146;', '&quot;', '&#63;'), $row['title']);

						$path = reduce_double_slashes(ee()->functions->prep_query_string(parse_config_variables($row['board_forum_url'])).'/viewthread/'.$row['topic_id'].'/');

						$result_ids[$forum_keys[$row['topic_id']]] = array(
												'title' => $row['title'],
												'active_date' => $row['last_post_date'],
												'url_title' => url_title($row['title']),
												'path' => ee()->functions->fetch_site_index().$qm.'URL='.$path,
												'id'	=> 'f'.$row['topic_id'],
												'type'	=> lang('forum_post')
												);
					}
				}
			}
		}

		return array('total_results' => $total_count, 'result_array' => $result_ids);
	}

}

// EOF
