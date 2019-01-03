<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Core Private Messaging
 */
class EE_Messages_send extends EE_Messages {


	/** -------------------------------------
	/**  Uploading Attachments
	/** -------------------------------------*/
	function _attach_file()
	{
		/** -------------------------------------
		/**  Check the paths
		/** -------------------------------------*/

		if ($this->upload_path == '')
		{
			return ee()->lang->line('unable_to_recieve_attach');
		}

		if ( ! @is_dir($this->upload_path) OR ! is_really_writable($this->upload_path))
		{
			return ee()->lang->line('unable_to_recieve_attach');
		}

		/** -------------------------------------
		/**  Are there previous attachments?
		/** -------------------------------------*/

		$this->attachments = array();
		$attachments_size  = 0;

		if (ee()->input->get_post('attach') !== FALSE && ee()->input->get_post('attach') != '')
		{
			ee()->db->select('attachment_id, attachment_size, attachment_location');
			ee()->db->where_in('attachment_id', str_replace('|',"','", ee()->input->get_post('attach')));
			$query = ee()->db->get('message_attachments');

 			if ($query->num_rows() + 1 > $this->max_attachments)
 			{
 				return ee()->lang->line('no_more_attachments');
 			}
 			elseif ($query->num_rows() > 0)
 			{
 				foreach($query->result_array() as $row)
 				{
 					if ( ! file_exists($row['attachment_location']))
 					{
 						continue;
 					}

 					$this->attachments[] = $row['attachment_id'];
 					$attachments_size += $row['attachment_size'];
				}
			}
		}

		/** -------------------------------------
		/**  Attachment too hefty?
		/** -------------------------------------*/

		if ($this->attach_maxsize != 0 && ($attachments_size + ($_FILES['userfile']['size'] /1024)) > $this->attach_maxsize)
		{
			return ee()->lang->line('attach_too_large');
		}

		/** -------------------------------------
		/**  Fetch the size of all attachments
		/** -------------------------------------*/
		if ($this->attach_total != '0')
		{
			ee()->db->select('SUM(attachment_size) as total');
			ee()->db->where('is_temp', 'y');
			$query = ee()->db->get('message_attachments');

			if ($query->row('total') != NULL)
			{
				// Is the size of the new file (along with the previous ones) too large?
				if (ceil($query->row('total')  + ($_FILES['userfile']['size']/1024)) > ($this->attach_total * 1000))
				{
					return ee()->lang->line('too_many_attachments');
				}
			}
		}

		$filehash = ee()->functions->random('alnum', 20);

		/** -------------------------------------
		/**  Upload the image
		/** -------------------------------------*/

		// Upload the image
		$config = array(
				'upload_path'	=> $this->upload_path,
				'max_size'		=> $this->attach_maxsize
		);

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
			return ee()->upload->display_errors();
		}

		$upload_data = ee()->upload->data();

		@chmod($upload_data['full_path'], DIR_WRITE_MODE);

		$this->temp_message_id = ee()->functions->random('nozero', 9);

		$data = array(
					'sender_id'				=> $this->member_id,
					'message_id'			=> $this->temp_message_id,
					'attachment_name'		=> $upload_data['file_name'],
					'attachment_hash'		=> $filehash,
					'attachment_extension'  => $upload_data['file_ext'],
					'attachment_location'	=> $upload_data['file_name'],
					'attachment_date'		=> ee()->localize->now,
					'attachment_size'		=> $upload_data['file_size']
				);

		ee()->db->insert('message_attachments', $data);
		$attach_id = ee()->db->insert_id();

		/** -------------------------------------
		/**  Load Attachment into array
		/** -------------------------------------*/

		$this->attachments[] = $attach_id;

		/* -------------------------------------
		/*  Delete Temp Attachments Over 48 Hours Old
		/*
		/*  The temp attachments are kept so long because
		/*  of draft messages that may contain attachments
		/*  but will not be sent until later.  I think 48
		/*  hours is enough time.  Any longer and the attachment
		/*  is gone but the message remains.
		/* -------------------------------------*/

		$expire = ee()->localize->now - 24*60*60;

		ee()->db->select('attachment_location');
		ee()->db->where('attachment_date < ', $expire);
		ee()->db->where('is_temp', 'y');
		$result = ee()->db->get('message_attachments');

		if ($result->num_rows() > 0)
		{
			foreach ($result->result_array() as $row)
			{
				@unlink($row['attachment_location']);
			}

			ee()->db->where('attachment_date <', $expire);
			ee()->db->where('is_temp = "y"');
			ee()->db->delete('message_attachments');
		}

		return TRUE;
	}





	/** -------------------------------------
	/**  Duplicate Attachments for Forwards
	/** -------------------------------------*/
	function _duplicate_files()
	{
		if (count($this->attachments) == 0)
		{
			return TRUE;
		}

		/** -------------------------------------
		/**  Check the paths
		/** -------------------------------------*/

		if ($this->upload_path == '')
		{
			return ee()->lang->line('unable_to_recieve_attach');
		}

		if ( ! @is_dir($this->upload_path) OR ! is_really_writable($this->upload_path))
		{
			return ee()->lang->line('unable_to_recieve_attach');
		}

		/** -------------------------------------
		/**  Fetch the size of all attachments
		/** -------------------------------------*/
		if ($this->attach_total != '0')
		{
			$query = ee()->db->query("SELECT SUM(attachment_size) AS total FROM exp_message_attachments WHERE is_temp != 'y'");

			if ($query->row('total') != NULL)
			{
				$total = $query->row('total') ;
			}
			else
			{
				$total = 0;
			}
		}


		/** -------------------------------------
		/**  Get Attachment Data
		/** -------------------------------------*/

 		$results = ee()->db->query("SELECT attachment_name, attachment_size,
 								attachment_location, attachment_extension
 								FROM exp_message_attachments
 								WHERE attachment_id IN ('".implode("','", $this->attachments)."')");

 		if ($query->num_rows() == 0)
 		{
 			return TRUE;
 		}

 		$this->attachments = array();

 		foreach($results->result_array() as $row)
 		{
 			if ( ! file_exists($row['attachment_location']))
 			{
 				continue;
 			}

 			/** -------------------------------------
			/**  Check Against Max
			/** -------------------------------------*/
			if ($this->attach_total != '0')
			{
				if (ceil($total + $row['attachment_size']) > ($this->attach_total * 1000))
				{
					return ee()->lang->line('too_many_attachments');
				}
			}

			/** -------------------------------------
			/**  Duplicate File
			/** -------------------------------------*/

			$filehash = ee()->functions->random('alnum', 20);

			$new_name = $filehash.$row['attachment_extension'];

			$new_location = $this->upload_path.$new_name;

			if (@copy($row['attachment_location'], $new_location))
			{
				chmod($new_location, FILE_WRITE_MODE);
			}

			/** -------------------------------------
			/**  Insert into Database
			/** -------------------------------------*/

			$this->temp_message_id = ee()->functions->random('nozero', 10);

	  		$data = array(
	  						'sender_id'				=> $this->member_id,
	  						'message_id'			=> $this->temp_message_id,
	  						'attachment_name'		=> $row['attachment_name'],
	  						'attachment_hash'		=> $filehash,
	  						'attachment_extension'  => $row['attachment_extension'],
	  						'attachment_location'	=> $new_location,
	  						'attachment_date'		=> ee()->localize->now,
	  						'attachment_size'		=> $row['attachment_size']
	  					);

			ee()->db->query(ee()->db->insert_string('exp_message_attachments', $data));
			$attach_id = ee()->db->insert_id();


			/** -------------------------------------
			/**  Change file name with attach ID
			/** -------------------------------------*/

			// For convenience we use the attachment ID number as the prefix for all files.
			// That way they will be easier to manager.

			// OK, whatever you say, Rick.  -Paul

			if (file_exists($new_location))
			{
				$final_name = $attach_id.'_'.$filehash;
				$final_path = $this->upload_path.$final_name.$row['attachment_extension'];

				if (rename($new_location, $final_path))
				{
					chmod($final_path, FILE_WRITE_MODE);
				}

				ee()->db->query("UPDATE exp_message_attachments
							SET attachment_hash = '{$final_name}', attachment_location = '{$final_path}'
							WHERE attachment_id = '{$attach_id}'");
			}

			/** -------------------------------------
			/**  Load Attachment into array
			/** -------------------------------------*/

			$this->attachments[] = $attach_id;
		}

		return TRUE;
	}




	/** -------------------------------------
	/**  Submission Error Display
	/** -------------------------------------*/

	function _remove_attachment($id)
	{
		ee()->db->select('attachment_location');
		ee()->db->where(array('attachment_id' => $id, 'sender_id' => ee()->session->userdata['member_id']));
		$query = ee()->db->get('message_attachments');

		if ($query->num_rows() == 0)
		{
			return;
		}

		@unlink($query->row('attachment_location') );

		ee()->db->query("DELETE FROM exp_message_attachments WHERE attachment_id = '{$id}'");

		$this->attachments = array();

		$x = explode("|", ee()->input->get_post('attach'));

		foreach ($x as $val)
		{
			if ($val != $id)
			{
				$this->attachments[] = $val;
			}
		}
	}



 	/** -----------------------------------
	/**  Send Message
	/** -----------------------------------*/
	function send_message()
	{
		$submission_error = array();

		/** ----------------------------------------
		/**  Is the user banned?
		/** ----------------------------------------*/

		if (ee()->session->userdata['is_banned'] === TRUE)
		{
			return $this->_error_page();
		}

		/** ----------------------------------------
		/**  Is the IP or User Agent unavalable?
		/** ----------------------------------------*/
		if (ee()->config->item('require_ip_for_posting') == 'y')
		{
			if (ee()->input->ip_address() == '0.0.0.0' OR ee()->session->userdata['user_agent'] == '')
			{
				return $this->_error_page();
			}
		}

		/** -------------------------------------
		/**  Status Setting
		/** -------------------------------------*/

		if (ee()->input->get_post('preview') OR ee()->input->get_post('remove'))
		{
			$status = 'preview';
		}
		elseif(ee()->input->get_post('draft'))
		{
			$status = 'draft';
		}
		else
		{
			$status = 'sent';
		}

		/** -------------------------------------
		/**  Already Sent?
		/** -------------------------------------*/

		if (ee()->input->get_post('message_id') !== FALSE && is_numeric(ee()->input->get_post('message_id')))
		{
			$query = ee()->db->query("SELECT message_status FROM exp_message_data WHERE message_id = '".ee()->db->escape_str(ee()->input->get_post('message_id'))."'");

			if ($query->num_rows() > 0 && $query->row('message_status')  == 'sent')
			{
				return $this->_error_page(ee()->lang->line('messsage_already_sent'));
			}
		}

		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- prv_msg_waiting_period => How many hours after becoming a member until they can PM?
		/* -------------------------------------------*/

		$waiting_period = (ee()->config->item('prv_msg_waiting_period') !== FALSE) ? (int) ee()->config->item('prv_msg_waiting_period') : 1;

		if (ee()->session->userdata['group_id'] != 1 && ee()->session->userdata['join_date'] > (ee()->localize->now - $waiting_period * 60 * 60))
		{
			return $this->_error_page(str_replace(array('%time%', '%email%', '%site%'),
												  array($waiting_period, ee()->functions->encode_email(ee()->config->item('webmaster_email')), ee()->config->item('site_name')),
												  ee()->lang->line('waiting_period_not_reached')));
		}


		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- prv_msg_throttling_period => How many seconds between PMs?
		/* -------------------------------------------*/

		if ($status == 'sent' && ee()->session->userdata['group_id'] != 1)
		{
			$period = (ee()->config->item('prv_msg_throttling_period') !== FALSE) ? (int) ee()->config->item('prv_msg_throttling_period') : 30;

			$query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_message_data d
								 WHERE d.sender_id = '".ee()->db->escape_str($this->member_id)."'
								 AND d.message_status = 'sent'
								 AND d.message_date > ".ee()->db->escape_str(ee()->localize->now - $period));

			if ($query->row('count')  > 0)
			{
				return $this->_error_page(str_replace('%x', $period, ee()->lang->line('send_throttle')));
			}
		}


		/** ------------------------------------------
		/**  Is there a recipient, subject, and body?
		/** ------------------------------------------*/

		if (ee()->input->get_post('recipients') == '' && $status == 'sent')
		{
			$submission_error[] = ee()->lang->line('empty_recipients_field');
		}
		elseif (ee()->input->get_post('subject') == '')
		{
			$submission_error[] = ee()->lang->line('empty_subject_field');
		}
		elseif (ee()->input->get_post('body') == '')
		{
			$submission_error[] = ee()->lang->line('empty_body_field');
		}

		/** -------------------------------------------
		/**  Deny Duplicate Data
		/** -------------------------------------------*/

		if (ee()->config->item('deny_duplicate_data') == 'y')
		{
			$query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_message_data d
								 WHERE d.sender_id = '".ee()->db->escape_str($this->member_id)."'
								 AND d.message_status = 'sent'
								 AND d.message_body = '".ee()->db->escape_str(ee('Security/XSS')->clean(ee()->input->get_post('body')))."'");

			if ($query->row('count')  > 0)
			{
				return $this->_error_page(ee()->lang->line('duplicate_message_sent'));
			}
		}

		/** ------------------------------------------
		/**  Valid Recipients? - Only Checked on Sent
		/** ------------------------------------------*/

		$recipients = $this->convert_recipients(ee()->input->get_post('recipients'), 'array', 'member_id');

		$cc = (trim(ee()->input->get_post('cc')) == '') ? array() : $this->convert_recipients(ee()->input->get_post('cc'), 'array', 'member_id');

		$recip_orig	= count($recipients);
		$cc_orig	= count($cc);

		// Make sure CC does not contain members in Recipients
		$cc = array_diff($cc, $recipients);

		if(count($recipients) == 0 && $status == 'sent')
		{
			$submission_error[] = ee()->lang->line('empty_recipients_field');
		}

		if($this->invalid_name === TRUE)
		{
			$submission_error[] = ee()->lang->line('invalid_username');
		}

		/** ------------------------------------------
		/**  Too Big for Its Britches?
		/** ------------------------------------------*/

		if ($this->max_chars != 0 && strlen(ee()->input->get_post('body')) > $this->max_chars)
		{
			$submission_error[] = str_replace('%max%', $this->max_chars, ee()->lang->line('message_too_large'));
		}

		/** -------------------------------------
		/**  Super Admins get a free pass
		/** -------------------------------------*/

		if (ee()->session->userdata('group_id') != 1)
		{
			/** ------------------------------------------
			/**  Sender Allowed to Send More Messages?
			/** ------------------------------------------*/
			$query = ee()->db->query("SELECT COUNT(c.copy_id) AS count
								 FROM exp_message_copies c, exp_message_data d
								 WHERE c.message_id = d.message_id
								 AND c.sender_id = '".ee()->db->escape_str($this->member_id)."'
								 AND d.message_status = 'sent'
								 AND d.message_date > ".(ee()->localize->now - 24*60*60));

			if (($query->row('count')  + count($recipients) + count($cc)) > $this->send_limit)
			{
				$submission_error[] = ee()->lang->line('sending_limit_warning');
			}

			/** ------------------------------------------
			/**  Sender Allowed to Store More Messages?
			/** ------------------------------------------*/
			if ($this->storage_limit != '0' && (ee()->input->get_post('sent_copy') !== FALSE && ee()->input->get_post('sent_copy') == 'y'))
			{
				if ($this->total_messages == '')
				{
					$this->storage_usage();
				}

				if (($this->total_messages + 1) > $this->storage_limit)
				{
					$submission_error[] = ee()->lang->line('storage_limit_warning');
				}
			}
		}

		/** -------------------------------------
		/**  Upload Path Set?
		/** -------------------------------------*/

		if ($this->upload_path == '' && (isset($_POST['remove']) OR (isset($_FILES['userfile']['name']) && $_FILES['userfile']['name'] != '')))
		{
			$submission_error[] = ee()->lang->line('unable_to_recieve_attach');
		}

		/** -------------------------------------
		/**  Attachments?
		/** -------------------------------------*/
		if (ee()->input->get_post('attach') !== FALSE && ee()->input->get_post('attach') != '')
		{
			$this->attachments = explode('|', $_POST['attach']);
		}

		/* -------------------------------------
		/*  Create Forward Attachments
		/*
		/*  We have to copy the attachments for
		/*  forwarded messages.  We only do this
		/*  when the compose messaage page is first
		/*  submitted.  We have a special variable
		/*  called 'create_attach' to tell us when
		/*  that is.
		/* -------------------------------------*/

		if ($this->attach_allowed == 'y' && $this->upload_path != '' && count($this->attachments) > 0 && ee()->input->get_post('create_attach'))
		{
			if (($message = $this->_duplicate_files()) !== TRUE)
			{
				$submission_error[] = $message.BR;
			}
		}

		/** -------------------------------------
		/**  Is this a remove attachment request?
		/** -------------------------------------*/
		if (isset($_POST['remove']) && $this->upload_path != '')
		{
			$id = key($_POST['remove']);

			if (is_numeric($id))
			{
				$this->_remove_attachment($id);

				// Treat an attachment removal like a draft, where we do not
				// see the preview only the message.

				$this->hide_preview = TRUE;
			}
		}

		/** -------------------------------------
		/**  Do we have an attachment to deal with?
		/** -------------------------------------*/

		if ($this->attach_allowed == 'y')
		{
			if ($this->upload_path != '' AND isset($_FILES['userfile']['name']) AND $_FILES['userfile']['name'] != '')
			{
				$preview = (ee()->input->post('preview') !== FALSE) ? TRUE : FALSE;

				if (($message = $this->_attach_file()) !== TRUE)
				{
					$submission_error[] = $message.BR;
				}
			}
		}

		/** -----------------------------------
		/**  Check Overflow
		/** -----------------------------------*/

		$details  = array();
		$details['overflow_recipients'] = array();
		$details['overflow_cc'] = array();

		for($i=0, $size = count($recipients); $i < $size; $i++)
		{
			if ($this->_check_overflow($recipients[$i]) === FALSE)
			{
				$details['overflow_recipients'][] = $recipients[$i];
				unset($recipients[$i]);
			}
		}

		for($i=0, $size = count($cc); $i < $size; $i++)
		{
			if ($this->_check_overflow($cc[$i]) === FALSE)
			{
				$details['overflow_cc'][] = $cc[$i];
				unset($cc[$i]);
			}
		}

		/* -------------------------------------------------
		/*  If we have people unable to receive a message
		/*  because of an overflow we make the message a
		/*  preview and will send a message to the sender.
		/* -------------------------------------*/

		if (count($details['overflow_recipients']) > 0 OR count($details['overflow_cc']) > 0)
		{
			sort($recipients);
			sort($cc);
			$overflow_names = array();

			/* -------------------------------------
			/*  Send email alert regarding a full
			/*  inbox to these users, load names
			/*  for error message
			/* -------------------------------------*/

			$query = ee()->db->query("SELECT exp_members.screen_name, exp_members.email, exp_members.accept_messages, exp_member_groups.prv_msg_storage_limit
								 FROM exp_members
								 LEFT JOIN exp_member_groups ON exp_member_groups.group_id = exp_members.group_id
								 WHERE exp_members.member_id IN ('".implode("','",array_merge($details['overflow_recipients'], $details['overflow_cc']))."')
								 AND exp_member_groups.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'");

			if ($query->num_rows() > 0)
			{
				ee()->load->library('email');

				ee()->email->wordwrap = true;

				$swap = array(
							  'sender_name'			=> ee()->session->userdata('screen_name'),
							  'site_name'			=> stripslashes(ee()->config->item('site_name')),
							  'site_url'			=> ee()->config->item('site_url')
							  );

				$template = ee()->functions->fetch_email_template('pm_inbox_full');
				$email_tit = ee()->functions->var_swap($template['title'], $swap);
				$email_msg = ee()->functions->var_swap($template['data'], $swap);

				foreach($query->result_array() as $row)
				{
					$overflow_names[] = $row['screen_name'];

					if ($row['accept_messages'] != 'y')
					{
						continue;
					}

					ee()->email->EE_initialize();
					ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
					ee()->email->to($row['email']);
					ee()->email->subject($email_tit);
					ee()->email->message(ee()->functions->var_swap($email_msg, array('recipient_name' => $row['screen_name'], 'pm_storage_limit' => $row['prv_msg_storage_limit'])));
					ee()->email->send();
				}
			}

			$submission_error[] = str_replace('%overflow_names%', implode(', ', $overflow_names), ee()->lang->line('overflow_recipients'));
		}

		/** ----------------------------------------
		/**  Submission Errors Force a Preview
		/** ----------------------------------------*/

		if (count($submission_error) > 0)
		{
			$status = 'preview';
			$this->hide_preview = TRUE;
			$this->invalid_name = FALSE;
		}

		/* -------------------------------------
		/*  Check Blocked on Sent
		/*
		/*  If a message is blocked, we will not notify
		/*  the sender of this and simply proceed.
		/* -------------------------------------*/

		if ($status == 'sent')
		{
			$sql = "SELECT member_id FROM exp_message_listed
					WHERE listed_type = 'blocked'
					AND listed_member = '{$this->member_id}'
					AND
					(
					member_id IN ('".implode("','", $recipients)."')";

			if (count($cc) > 0)
			{
				$sql .= "OR
						 member_id IN ('".implode("','", $cc)."')";
			}

			$sql .= ")";

			$blocked = ee()->db->query($sql);

			if ($blocked->num_rows() > 0)
			{
				foreach($blocked->result_array() as $row)
				{
					$details['blocked'][] = $row['member_id'];
				}

				$recipients = array_diff($recipients, $details['blocked']);
				$cc = (count($cc) > 0) ? array_diff($cc, $details['blocked']) : array();

				sort($recipients);
				sort($cc);
			}
		}

		/** -------------------------------------
		/**  Store Data
		/** -------------------------------------*/

		$data = array(
			'sender_id'				=> $this->member_id,
			'message_date'			=> ee()->localize->now,
			'message_subject'		=> ee()->input->get_post('subject', TRUE),
			'message_body'			=> ee()->input->get_post('body', TRUE),
			'message_tracking'		=> ( ! ee()->input->get_post('tracking')) ? 'n' : 'y',
			'message_attachments'	=> (count($this->attachments) > 0) ? 'y' : 'n',
			'message_recipients'	=> implode('|', $recipients),
			'message_cc'			=> implode('|', $cc),
			'message_hide_cc'		=> ( ! ee()->input->get_post('hide_cc')) ? 'n' : 'y',
			'message_sent_copy'		=> ( ! ee()->input->get_post('sent_copy')) ? 'n' : 'y',
			'total_recipients'		=> (count($recipients) + count($cc)),
			'message_status'		=> $status
		);

		if (ee()->input->get_post('message_id') && is_numeric(ee()->input->get_post('message_id')))
		{
			/* -------------------------------------
			/*  Preview or Draft previously submitted.
			/*  So, we're updating an already existing message
			/* -------------------------------------*/

			$message_id = ee()->input->get_post('message_id');
			unset($data['message_id']);

			ee()->db->query(ee()->db->update_string('exp_message_data', $data, "message_id = '".ee()->db->escape_str($message_id)."'"));
		}
		else
		{
			ee()->db->query(ee()->db->insert_string('exp_message_data', $data));

			$message_id = ee()->db->insert_id();
		}

		/** -----------------------------------------
		/**  Send out Messages to Recipients and CC
		/** -----------------------------------------*/

		if ($status == 'sent')
		{
			$copy_data = array(	'message_id' => $message_id,
								'sender_id'	 => $this->member_id);

			/** -----------------------------------------
			/**  Send out Messages to Recipients and CC
			/** -----------------------------------------*/

			for($i=0, $size = count($recipients); $i < $size; $i++)
			{
				$copy_data['recipient_id'] 		= $recipients[$i];
				$copy_data['message_authcode']	= ee()->functions->random('alnum', 10);
				ee()->db->query(ee()->db->insert_string('exp_message_copies', $copy_data));
			}

			for($i=0, $size = count($cc); $i < $size; $i++)
			{
				$copy_data['recipient_id']		= $cc[$i];
				$copy_data['message_authcode']	= ee()->functions->random('alnum', 10);
				ee()->db->query(ee()->db->insert_string('exp_message_copies', $copy_data));
			}

			/** ----------------------------------
			/**  Increment exp_members.private_messages
			/** ----------------------------------*/

			ee()->db->query("UPDATE exp_members SET private_messages = private_messages + 1
						WHERE member_id IN ('".implode("','",array_merge($recipients, $cc))."')");

			/** ----------------------------------
			/**  Send Any and All Email Notifications
			/** ----------------------------------*/

			$query = ee()->db->query("SELECT screen_name, email FROM exp_members
								 WHERE member_id IN ('".implode("','",array_merge($recipients, $cc))."')
								 AND notify_of_pm = 'y'
								 AND member_id != {$this->member_id}");

			if ($query->num_rows() > 0)
			{
				ee()->load->library('typography');
				ee()->typography->initialize(array(
 				 				'parse_images'		=> FALSE,
 				 				'smileys'			=> FALSE,
 				 				'highlight_code'	=> TRUE)
 				 				);

				$subject = ee('Security/XSS')->clean(ee()->input->get_post('subject'));

				if (bool_config_item('enable_censoring'))
        		{
        			$subject = ee('Format')->make('Text', $subject)->censor();
				}

				$body = ee()->typography->parse_type(stripslashes(ee('Security/XSS')->clean(ee()->input->get_post('body'))),
														array('text_format'	=> 'none',
																 'html_format'	=> 'none',
																 'auto_links'	=> 'n',
																 'allow_img_url' => 'n'
																 ));

				ee()->load->library('email');

				ee()->email->wordwrap = true;

				$swap = array(
							  'sender_name'			=> ee()->session->userdata('screen_name'),
							  'message_subject'		=> $subject,
							  'message_content'		=> $body,
							  'site_name'			=> stripslashes(ee()->config->item('site_name')),
							  'site_url'			=> ee()->config->item('site_url')
							  );

				$template = ee()->functions->fetch_email_template('private_message_notification');
				$email_tit = ee()->functions->var_swap($template['title'], $swap);
				$email_msg = ee()->functions->var_swap($template['data'], $swap);

				// Load the text helper
				ee()->load->helper('text');

				foreach($query->result_array() as $row)
				{
					ee()->email->EE_initialize();
					ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
					ee()->email->to($row['email']);
					ee()->email->subject($email_tit);
					ee()->email->message(entities_to_ascii(ee()->functions->var_swap($email_msg, array('recipient_name' => $row['screen_name']))));
					ee()->email->send();
				}
			}
		}

		/** -------------------------------------
		/**  Sent Copy?
		/** -------------------------------------*/

		if ($status == 'sent' && $data['message_sent_copy'] == 'y')
		{
			$copy_data['recipient_id'] 		= $this->member_id;
			$copy_data['message_authcode']	= ee()->functions->random('alnum', 10);
			$copy_data['message_folder']	= '2';  // Sent Message Folder
			$copy_data['message_read']		= 'y';  // Already read automatically
			ee()->db->query(ee()->db->insert_string('exp_message_copies', $copy_data));
		}

		/** -------------------------------------
		/**  Replying or Forwarding?
		/** -------------------------------------*/

		if ($status == 'sent' && (ee()->input->get_post('replying') !== FALSE OR ee()->input->get_post('forwarding') !== FALSE))
		{
			$copy_id = (ee()->input->get_post('replying') !== FALSE) ? ee()->input->get_post('replying') : ee()->input->get_post('forwarding');
			$status  = (ee()->input->get_post('replying') !== FALSE) ? 'replied' : 'forwarded';

			ee()->db->query("UPDATE exp_message_copies SET message_status = '{$status}' WHERE copy_id = '{$copy_id}'");
		}

		/** -------------------------------------
		/**  Correct Member ID for Attachments
		/** -------------------------------------*/

		if (count($this->attachments) > 0)
		{
			ee()->db->query("UPDATE exp_message_attachments SET message_id = '{$message_id}'
						WHERE attachment_id IN ('".implode("','", $this->attachments)."')");
		}

		/** -------------------------------------
		/**  Remove Temp Status for Attachments
		/** -------------------------------------*/

		if ($status == 'sent')
		{
			ee()->db->query("UPDATE exp_message_attachments SET is_temp = 'n' WHERE message_id = '{$message_id}'");
		}

		/** -------------------------------------
		/**  Redirect Them
		/** -------------------------------------*/

		if ($status == 'preview')
		{
			return $this->compose($message_id, $submission_error);
		}
		elseif($status == 'draft')
		{
			$this->drafts();
		}
		else
		{
			ee()->functions->redirect($this->_create_path('inbox'));
		}
	}





}

// EOF
