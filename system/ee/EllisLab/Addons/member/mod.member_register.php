<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

// --------------------------------------------------------------------

/**
 * Member Management Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Member_register extends Member {

	var $errors = array();

	/**
	 * Member Registration Form
	 */
	public function registration_form()
	{
		// Do we allow new member registrations?
		if (ee()->config->item('allow_member_registration') == 'n')
		{
			$data = array(
				'title' 	=> lang('member_registration'),
				'heading'	=> lang('notice'),
				'content'	=> lang('mbr_registration_not_allowed'),
				'link'		=> array(
					ee()->functions->fetch_site_index(),
					stripslashes(ee()->config->item('site_name'))
				)
			);

			ee()->output->show_message($data);
		}

		// Is the current user logged in?
		if (ee()->session->userdata('member_id') != 0)
		{
			return ee()->output->show_user_error(
				'general',
				array(lang('mbr_you_are_registered'))
			);
		}

		// Fetch the registration form
		$reg_form = $this->_load_element('registration_form');

		// Do we have custom fields to show?
		$query = ee()->db->where('m_field_reg', 'y')
			->order_by('m_field_order')
			->get('member_fields');

		// If not, we'll kill the custom field variables from the template
		if ($query->num_rows() == 0)
		{
			$reg_form = preg_replace("/{custom_fields}.*?{\/custom_fields}/s", "", $reg_form);
		}
		else
		{
			// Parse custom field data

			// First separate the chunk between the {custom_fields} variable pairs.
			$field_chunk = (preg_match("/{custom_fields}(.*?){\/custom_fields}/s",
							$reg_form, $match)) ? $match['1'] : '';

			// Next, separate the chunck between the {required} variable pairs
			$req_chunk	= (preg_match("/{required}(.*?){\/required}/s", $field_chunk, $match)) ? $match['1'] : '';

			// Loop through the query result
			$str = '';

			foreach ($query->result_array() as $row)
			{
				$field  = '';
				$temp	= $field_chunk;

				// Replace {field_name}
				$temp = str_replace("{field_name}", $row['m_field_label'], $temp);

				if ($row['m_field_description'] == '')
				{
					$temp = preg_replace("/{if field_description}.+?{\/if}/s", "", $temp);
				}
				else
				{
					$temp = preg_replace("/{if field_description}(.+?){\/if}/s", "\\1", $temp);
				}

				$temp = str_replace("{field_description}", $row['m_field_description'], $temp);

				// Replace {required} pair
				if ($row['m_field_required'] == 'y')
				{
					$temp = preg_replace("/".LD."required".RD.".*?".LD."\/required".RD."/s", $req_chunk, $temp);
				}
				else
				{
					$temp = preg_replace("/".LD."required".RD.".*?".LD."\/required".RD."/s", '', $temp);
				}

				// Parse input fields

				// Set field width
				if (strpos($row['m_field_width'], 'px') === FALSE &&
					strpos($row['m_field_width'], '%') === FALSE)
				{
					$width = $row['m_field_width'].'px';
				}
				else
				{
					$width = $row['m_field_width'];
				}

				//  Textarea fields
				if ($row['m_field_type'] == 'textarea')
				{
					$rows = ( ! isset($row['m_field_ta_rows'])) ? '10' : $row['m_field_ta_rows'];

					$field = "<textarea style=\"width:{$width};\" name=\"m_field_id_".$row['m_field_id']."\"  cols='50' rows='{$rows}' class=\"textarea\" ></textarea>";
				}
				else
				{
					//  Text fields
					if ($row['m_field_type'] == 'text')
					{
						$maxlength = ($row['m_field_maxl'] == 0) ? '100' : $row['m_field_maxl'];

						$field = "<input type=\"text\" name=\"m_field_id_".$row['m_field_id']."\" value=\"\" class=\"input\" maxlength=\"$maxlength\" size=\"40\" style=\"width:{$width};\" />";
					}
					elseif ($row['m_field_type'] == 'select')
					{
						//  Drop-down fields
						$select_list = trim($row['m_field_list_items']);

						if ($select_list != '')
						{
							$field = "<select name=\"m_field_id_".$row['m_field_id']."\" class=\"select\">";

							foreach (explode("\n", $select_list) as $v)
							{
								$v = trim($v);

								 $field .= "<option value=\"$v\">$v</option>";
							}

							 $field .= "</select>";
						}
					}
				}

				$temp = str_replace("{field}", $field, $temp);

				$str .= $temp;
			}

			// since $str may have sequences that look like PCRE backreferences,
			// the two choices are to escape them and use preg_replace() or to
			// match the pattern and use str_replace().  This way happens
			// to be faster in this case.
			if (preg_match("/".LD."custom_fields".RD.".*?".LD."\/custom_fields".RD."/s",
						   $reg_form, $match))
			{
				$reg_form = str_replace($match[0], $str, $reg_form);
			}
		}

		// {if captcha}
		if (preg_match("/{if captcha}(.+?){\/if}/s", $reg_form, $match))
		{
			if (ee('Captcha')->shouldRequireCaptcha())
			{
				$reg_form = preg_replace("/{if captcha}.+?{\/if}/s", $match['1'], $reg_form);

				// Bug fix.  Deprecate this later..
				$reg_form = str_replace('{captcha_word}', '', $reg_form);

				if ( ! class_exists('Template'))
				{
					$reg_form = preg_replace("/{captcha}/", ee('Captcha')->create(), $reg_form);
				}
			}
			else
			{
				$reg_form = preg_replace("/{if captcha}.+?{\/if}/s", "", $reg_form);
			}
		}

		$un_min_len = str_replace("%x", ee()->config->item('un_min_len'),
									lang('mbr_username_length'));
		$pw_min_len = str_replace("%x", ee()->config->item('pw_min_len'),
									lang('mbr_password_length'));

		// Fetch the admin config values in order to populate the form with
		// the same options
		ee()->load->model('admin_model');
		ee()->load->helper('form');

		$config_fields = ee()->config->prep_view_vars('localization_cfg');

		// Parse languge lines
		$reg_form = $this->_var_swap(
			$reg_form,
			array(
				'lang:username_length' => $un_min_len,
				'lang:password_length' => $pw_min_len,
				'form:localization'    => ee()->localize->timezone_menu(),
				'form:date_format'     => form_preference('date_format', $config_fields['fields']['date_format']),
				'form:time_format'     => form_preference('time_format', $config_fields['fields']['time_format']),
				'form:include_seconds' => form_preference('include_seconds', $config_fields['fields']['include_seconds']),
				'form:language'        => $this->get_language_listing()
			)
		);

		// Generate Form declaration
		$data['hidden_fields'] = array(
			'ACT'  => ee()->functions->fetch_action_id('Member', 'register_member'),
			'RET'  => ee()->functions->fetch_site_index(),
			'FROM' => ($this->in_forum == TRUE) ? 'forum' : '',
		);

		if ($this->in_forum === TRUE)
		{
			$data['hidden_fields']['board_id'] = $this->board_id;
		}

		$data['id'] = 'register_member_form';

		// Return the final rendered form
		return ee()->functions->form_declaration($data).$reg_form."\n"."</form>";
	}

	// --------------------------------------------------------------------

	/**
	 * Register Member
	 */
	public function register_member()
	{
		// Do we allow new member registrations?
		if (ee()->config->item('allow_member_registration') == 'n')
		{
			return FALSE;
		}

		// Is user banned?
		if (ee()->session->userdata('is_banned') === TRUE)
		{
			return ee()->output->show_user_error(
				'general',
				array(lang('not_authorized'))
			);
		}

		// Blacklist/Whitelist Check
		if (ee()->blacklist->blacklisted == 'y' &&
			ee()->blacklist->whitelisted == 'n')
		{
			return ee()->output->show_user_error(
				'general',
				array(lang('not_authorized'))
			);
		}

		ee()->load->helper('url');

		// -------------------------------------------
		// 'member_member_register_start' hook.
		//  - Take control of member registration routine
		//  - Added EE 1.4.2
		//
			ee()->extensions->call('member_member_register_start');
			if (ee()->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		// Set the default globals
		$default = array(
			'username', 'password', 'password_confirm', 'email',
			'screen_name', 'url', 'location'
		);

		foreach ($default as $val)
		{
			if ( ! isset($_POST[$val])) $_POST[$val] = '';
		}

		if ($_POST['screen_name'] == '')
		{
			$_POST['screen_name'] = $_POST['username'];
		}


		// Do we have any custom fields?
		$query = ee()->db->select('m_field_id, m_field_name, m_field_label, m_field_type, m_field_list_items, m_field_required')
							  ->where('m_field_reg', 'y')
							  ->get('member_fields');

		$cust_errors = array();
		$custom_data = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$field_name = 'm_field_id_'.$row['m_field_id'];

				// Assume we're going to save this data, unless it's empty to begin with
				$valid = isset($_POST[$field_name]) && $_POST[$field_name] != '';

				// Basic validations
				if ($row['m_field_type'] == 'select' && $valid)
				{
					// Ensure their selection is actually a valid choice
					$options = explode("\n", $row['m_field_list_items']);

					if (! in_array(htmlentities($_POST[$field_name]), $options))
					{
						$valid = FALSE;
						$cust_errors[] = lang('mbr_field_invalid').'&nbsp;'.$row['m_field_label'];
					}
				}

				if ($valid)
				{
					$custom_data[$field_name] = ee('Security/XSS')->clean($_POST[$field_name]);
				}
			}
		}

		if (isset($_POST['email_confirm']) && $_POST['email'] != $_POST['email_confirm'])
		{
			$cust_errors[] = lang('mbr_emails_not_match');
		}

		if (ee('Captcha')->shouldRequireCaptcha())
		{
			if ( ! isset($_POST['captcha']) OR $_POST['captcha'] == '')
			{
				$cust_errors[] = lang('captcha_required');
			}
		}

		if (ee()->config->item('require_terms_of_service') == 'y')
		{
			if ( ! isset($_POST['accept_terms']))
			{
				$cust_errors[] = lang('mbr_terms_of_service_required');
			}
		}


		// -------------------------------------------
		// 'member_member_register_errors' hook.
		//  - Additional error checking prior to submission
		//  - Added EE 2.5.0
		//
			ee()->extensions->call('member_member_register_errors', $this);
			if (ee()->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------


		ee()->load->helper('security');

		// Assign the data
		$data = array(
			'username'		=> trim_nbs(ee()->input->post('username')),
			'password'		=> sha1($_POST['password']),
			'ip_address'	=> ee()->input->ip_address(),
			'join_date'		=> ee()->localize->now,
			'email'			=> trim_nbs(ee()->input->post('email')),
			'screen_name'	=> trim_nbs(ee()->input->post('screen_name')),
			'url'			=> prep_url(ee()->input->post('url')),
			'location'		=> ee()->input->post('location'),

			// overridden below if used as optional fields
			'language'		=> (ee()->config->item('deft_lang')) ?: 'english',
		);

		$data = array_merge($data, $custom_data);

		// Set member group

		if (ee()->config->item('req_mbr_activation') == 'manual' OR
			ee()->config->item('req_mbr_activation') == 'email')
		{
			$data['group_id'] = 4;  // Pending
		}
		else
		{
			if (ee()->config->item('default_member_group') == '')
			{
				$data['group_id'] = 4;  // Pending
			}
			else
			{
				$data['group_id'] = ee()->config->item('default_member_group');
			}
		}

		// Optional Fields

		$optional = array(
			'bio'				=> 'bio',
			'language'			=> 'language',
			'timezone'			=> 'server_timezone',
			'date_format'		=> 'date_format',
			'time_format'		=> 'time_format',
			'include_seconds'	=> 'include_seconds'
		);

		foreach($optional as $key => $value)
		{
			if (isset($_POST[$value]))
			{
				$data[$key] = ee()->input->post($value, TRUE); //XSS clean this
			}
		}

		// We generate an authorization code if the member needs to self-activate
		if (ee()->config->item('req_mbr_activation') == 'email')
		{
			$data['authcode'] = ee()->functions->random('alnum', 10);
		}

		$member = ee('Model')->make('Member', $data);
		$result = $member->validate();

		$field_labels = array();

		foreach ($member->getDisplay()->getFields() as $field)
		{
			$field_labels[$field->getName()] = $field->getLabel();
		}


		if ($result->failed())
		{
			$field_errors = array();

			$e = $result->getAllErrors();
			$errors = array_map('current', $e);

			foreach ($errors as $field => $error)
			{
				$label = lang($field);

				if (isset($field_labels[$field]))
				{
					$label = $field_labels[$field];
				}

				$field_errors[] = "<b>{$label}: </b>{$error}";
			}
		}

		$errors = array_merge($field_errors, $cust_errors, $this->errors);

		// Display error if there are any
		if (count($errors) > 0)
		{
			return ee()->output->show_user_error('submission', $errors);
		}


		// Do we require captcha?
		if (ee('Captcha')->shouldRequireCaptcha())
		{
			$query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_captcha WHERE word='".ee()->db->escape_str($_POST['captcha'])."' AND ip_address = '".ee()->input->ip_address()."' AND date > UNIX_TIMESTAMP()-7200");

			if ($query->row('count')  == 0)
			{
				return ee()->output->show_user_error('submission', array(lang('captcha_incorrect')));
			}

			ee()->db->query("DELETE FROM exp_captcha WHERE (word='".ee()->db->escape_str($_POST['captcha'])."' AND ip_address = '".ee()->input->ip_address()."') OR date < UNIX_TIMESTAMP()-7200");
		}


		$member->save();

		// Update stats
		if (ee()->config->item('req_mbr_activation') == 'none')
		{
			ee()->stats->update_member_stats();
		}

		$member_id = $member->member_id;


		// Send admin notifications
		if (ee()->config->item('new_member_notification') == 'y' &&
			ee()->config->item('mbr_notification_emails') != '')
		{
			$name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

			$swap = array(
							'name'					=> $name,
							'site_name'				=> stripslashes(ee()->config->item('site_name')),
							'control_panel_url'		=> ee()->config->item('cp_url'),
							'username'				=> $data['username'],
							'email'					=> $data['email']
						 );

			$template = ee()->functions->fetch_email_template('admin_notify_reg');
			$email_tit = $this->_var_swap($template['title'], $swap);
			$email_msg = $this->_var_swap($template['data'], $swap);

			// Remove multiple commas
			$notify_address = reduce_multiples(ee()->config->item('mbr_notification_emails'), ',', TRUE);

			// Send email
			ee()->load->helper('text');

			ee()->load->library('email');
			ee()->email->wordwrap = true;
			ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
			ee()->email->to($notify_address);
			ee()->email->subject($email_tit);
			ee()->email->message(entities_to_ascii($email_msg));
			ee()->email->Send();
		}

		// -------------------------------------------
		// 'member_member_register' hook.
		//  - Additional processing when a member is created through the User Side
		//  - $member_id added in 2.0.1
		//
			ee()->extensions->call('member_member_register', $data, $member_id);
			if (ee()->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		// Send user notifications
		if (ee()->config->item('req_mbr_activation') == 'email')
		{
			$action_id  = ee()->functions->fetch_action_id('Member', 'activate_member');

			$name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

			$board_id = (ee()->input->get_post('board_id') !== FALSE && is_numeric(ee()->input->get_post('board_id'))) ? ee()->input->get_post('board_id') : 1;

			$forum_id = (ee()->input->get_post('FROM') == 'forum') ? '&r=f&board_id='.$board_id : '';

			$swap = array(
				'name'				=> $name,
				'activation_url'	=> ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$data['authcode'].$forum_id,
				'site_name'			=> stripslashes(ee()->config->item('site_name')),
				'site_url'			=> ee()->config->item('site_url'),
				'username'			=> $data['username'],
				'email'				=> $data['email']
			 );

			$template = ee()->functions->fetch_email_template('mbr_activation_instructions');
			$email_tit = $this->_var_swap($template['title'], $swap);
			$email_msg = $this->_var_swap($template['data'], $swap);

			// Send email
			ee()->load->helper('text');

			ee()->load->library('email');
			ee()->email->wordwrap = true;
			ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
			ee()->email->to($data['email']);
			ee()->email->subject($email_tit);
			ee()->email->message(entities_to_ascii($email_msg));
			ee()->email->Send();

			$message = lang('mbr_membership_instructions_email');
		}
		elseif (ee()->config->item('req_mbr_activation') == 'manual')
		{
			$message = lang('mbr_admin_will_activate');
		}
		else
		{
			// Log user in (the extra query is a little annoying)
			ee()->load->library('auth');
			$member_data_q = ee()->db->get_where('members', array('member_id' => $member_id));

			$incoming = new Auth_result($member_data_q->row());
			$incoming->remember_me();
			$incoming->start_session();

			$message = lang('mbr_your_are_logged_in');
		}

		// Build the message
		if (ee()->input->get_post('FROM') == 'forum')
		{
			$query = $this->_do_form_query();

			$site_name	= $query->row('board_label') ;
			$return		= parse_config_variables($query->row('board_forum_url'));
		}
		else
		{
			$site_name = (ee()->config->item('site_name') == '') ? lang('back') : stripslashes(ee()->config->item('site_name'));
			$return = ee()->config->item('site_url');
		}

		$data = array(
			'title' 	=> lang('mbr_registration_complete'),
			'heading'	=> lang('thank_you'),
			'content'	=> lang('mbr_registration_completed')."\n\n".$message,
			'redirect'	=> '',
			'link'		=> array($return, $site_name)
		);

		ee()->output->show_message($data);
	}

	// --------------------------------------------------------------------

	private function _do_form_query()
	{
		if (ee()->input->get_post('board_id') !== FALSE &&
			is_numeric(ee()->input->get_post('board_id')))
		{
			return ee()->db->select('board_forum_url, board_id, board_label')
								->where('board_id', (int) ee()->input->get_post('board_id'))
								->get('forum_boards');
		}

		return ee()->db->select('board_forum_url, board_id, board_label')
							->where('board_id', 1)
							->get('forum_boards');
	}

	// --------------------------------------------------------------------

	/**
	 * Member Self-Activation
	 */
	public function activate_member()
	{
		// Fetch the site name and URL
		if (ee()->input->get_post('r') == 'f')
		{
			$query = $this->_do_form_query();

			$site_name	= $query->row('board_label') ;
			$return		= parse_config_variables($query->row('board_forum_url'));
		}
		else
		{
			$return 	= ee()->functions->fetch_site_index();
			$site_name 	= (ee()->config->item('site_name') == '') ? lang('back') : stripslashes(ee()->config->item('site_name'));
		}

		// No ID?  Tisk tisk...
		$id  = ee()->input->get_post('id');

		if ($id == FALSE)
		{

			$data = array(	'title' 	=> lang('mbr_activation'),
							'heading'	=> lang('error'),
							'content'	=> lang('invalid_url'),
							'link'		=> array($return, $site_name)
						 );

			ee()->output->show_message($data);
		}

		// Set the member group
		$group_id = ee()->config->item('default_member_group');

		// Is there even a Pending (group 4) account for this particular user?
		$query = ee()->db->select('member_id, group_id, email')
							  ->where('group_id', 4)
							  ->where('authcode', $id)
							  ->get('members');

		if ($query->num_rows() == 0)
		{
			$data = array(	'title' 	=> lang('mbr_activation'),
							'heading'	=> lang('error'),
							'content'	=> lang('mbr_problem_activating'),
							'link'		=> array($return, $site_name)
						 );

			ee()->output->show_message($data);
		}

		$member_id = $query->row('member_id');

		// If the member group hasn't been switched we'll do it.

		if ($query->row('group_id')  != $group_id)
		{
			ee()->db->query("UPDATE exp_members SET group_id = '".ee()->db->escape_str($group_id)."' WHERE authcode = '".ee()->db->escape_str($id)."'");
		}

		ee()->db->query("UPDATE exp_members SET authcode = '' WHERE authcode = '$id'");

		// -------------------------------------------
		// 'member_register_validate_members' hook.
		//  - Additional processing when member(s) are self validated
		//  - Added 1.5.2, 2006-12-28
		//  - $member_id added 1.6.1
		//
			ee()->extensions->call('member_register_validate_members', $member_id);
			if (ee()->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		// Upate Stats

		ee()->stats->update_member_stats();

		// Show success message
		$data = array(	'title' 	=> lang('mbr_activation'),
						'heading'	=> lang('thank_you'),
						'content'	=> lang('mbr_activation_success')."\n\n".lang('mbr_may_now_log_in'),
						'link'		=> array($return, $site_name)
					 );

		ee()->output->show_message($data);
	}
}
// END CLASS

// EOF
