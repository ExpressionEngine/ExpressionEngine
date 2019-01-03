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
 * Member Management Memberlist
 */
class Member_memberlist extends Member {

	var $is_search			= FALSE;
	var $search_keywords	= '';
	var $search_fields		= '';
	var $search_total		= 0;

	/** ----------------------------------
	/**  Member Email Form
	/** ----------------------------------*/
	function email_console()
	{
		/** ---------------------------------
		/**  Is the user logged in?
		/** ---------------------------------*/

		if (ee()->session->userdata('member_id') == 0)
		{
			return $this->profile_login_form($this->_member_path('self'));
		}

		/** ---------------------------------
		/**  Is user allowed to send email?
		/** ---------------------------------*/

		if (ee()->session->userdata['can_email_from_profile'] == 'n')
		{
			return ee()->output->show_user_error('general', array(ee()->lang->line('mbr_not_allowed_to_use_email_console')));
		}


		$query = ee()->db->query("SELECT screen_name, accept_user_email FROM exp_members WHERE member_id = '{$this->cur_id}'");

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		if ($query->row('accept_user_email')  != 'y')
		{
			return $this->_var_swap(
				$this->_load_element('email_user_message'),
				array(
					'lang:message'	=>	ee()->lang->line('mbr_email_not_accepted'),
					'css_class'		=>	'highlight'
				)
			);
		}

		$data = array(
			'hidden_fields' => array('MID' => $this->cur_id),
			'action' 		=> $this->_member_path('send_email')
		);

		$data['id']		= 'email_console_form';

		$this->_set_page_title(ee()->lang->line('email_console'));

		return $this->_var_swap(
			$this->_load_element('email_form'),
			 array(
				'form_declaration'	=>	ee()->functions->form_declaration($data),
				'name'				=>	$query->row('screen_name')
			)
		);
	}





	/** ----------------------------------
	/**  Send Member Email
	/** ----------------------------------*/
	function send_email()
	{
		if ( ! $member_id = ee()->input->post('MID'))
		{
			return FALSE;
		}

		/** ----------------------------------------
		/**  Is the user banned?
		/** ----------------------------------------*/

		if (ee()->session->userdata['is_banned'] == TRUE)
		{
			return FALSE;
		}

		/** ---------------------------------
		/**  Is the user logged in?
		/** ---------------------------------*/

		if (ee()->session->userdata('member_id') == 0)
		{
			return $this->profile_login_form($this->_member_path('email_console/'.$member_id));
		}

		/** ---------------------------------
		/**  Are we missing data?
		/** ---------------------------------*/

		if ( ! $member_id = ee()->input->post('MID'))
		{
			return FALSE;
		}

		if ( ! isset($_POST['subject']) OR ! isset($_POST['message']))
		{
			return FALSE;
		}

		if ($_POST['subject'] == '' OR $_POST['message'] == '')
		{
			return ee()->output->show_user_error('submission', array(ee()->lang->line('mbr_missing_fields')));
		}

		/** ----------------------------------------
		/**  Check Email Timelock
		/** ----------------------------------------*/

		if (ee()->session->userdata['group_id'] != 1)
		{
			$lock = ee()->config->item('email_console_timelock');

			if (is_numeric($lock) AND $lock != 0)
			{
				if ((ee()->session->userdata['last_email_date'] + ($lock*60)) > ee()->localize->now)
				{
					return $this->_var_swap(
						$this->_load_element('email_user_message'),
						array(
							'lang:message'			=>	str_replace("%x", $lock, ee()->lang->line('mbr_email_timelock_not_expired')),
							'css_class'				=>	'highlight',
							'lang:close_window'		=>	ee()->lang->line('mbr_close_window')
						)
					);
				}
			}
		}

		/** ---------------------------------
		/**  Does the recipient accept email?
		/** ---------------------------------*/

		$query = ee()->db->query("SELECT email, screen_name, accept_user_email FROM exp_members WHERE member_id = '{$member_id}'");

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		if ($query->row('accept_user_email')  != 'y')
		{
			return $this->_var_swap(
				$this->_load_element('email_user_message'),
				array(
					'lang:message'	=>	ee()->lang->line('mbr_email_not_accepted'),
					'css_class'		=>	'highlight'
				)
			);
		}

		$message  = stripslashes($_POST['message'])."\n\n";
		$message .= ee()->lang->line('mbr_email_forwarding')."\n";
		$message .= ee()->config->item('site_url')."\n";
		$message .= ee()->lang->line('mbr_email_forwarding_cont');

		/** ----------------------------
		/**  Send email
		/** ----------------------------*/

		ee()->load->library('email');
		ee()->email->wordwrap = true;
		ee()->email->from(ee()->session->userdata['email']);
		ee()->email->subject(stripslashes($_POST['subject']));
		ee()->email->message($message);

		if (isset($_POST['self_copy']))
		{
			/*	If CC'ing the send, they get the email and the recipient is BCC'ed
				Because Rick says his filter blocks emails without a To: field
			*/

			ee()->email->to(ee()->session->userdata['email']);
			ee()->email->bcc($query->row('email') );
		}
		else
		{
			ee()->email->to($query->row('email') );
		}

		$swap['lang:close_window'] = ee()->lang->line('mbr_close_window');

		if ( ! ee()->email->send())
		{
			$swap['lang:message']	= ee()->lang->line('mbr_email_error');
			$swap['css_class'] 		= 'alert';
		}
		else
		{
			$this->log_email($query->row('email') , $query->row('screen_name') , $_POST['subject'], $_POST['message']);

			$swap['lang:message']	= ee()->lang->line('mbr_good_email');
			$swap['css_class'] 		= 'success';

			ee()->db->query("UPDATE exp_members SET last_email_date = '".ee()->localize->now."' WHERE member_id = '".ee()->session->userdata('member_id')."'");

		}

		$this->_set_page_title(ee()->lang->line('email_console'));

		return $this->_var_swap($this->_load_element('email_user_message'), $swap);
	}




	/** ---------------------------------
	/**  Log Email Message
	/** ---------------------------------*/
	function log_email($recipient, $recipient_name, $subject, $message)
	{
		if (ee()->config->item('log_email_console_msgs') == 'y')
		{
			$data = array(
				'cache_date'		=> ee()->localize->now,
				'member_id'			=> ee()->session->userdata('member_id'),
				'member_name'		=> ee()->session->userdata['screen_name'],
				'ip_address'		=> ee()->input->ip_address(),
				'recipient'			=> $recipient,
				'recipient_name'	=> $recipient_name,
				'subject'			=> $subject,
				'message'			=> ee('Security/XSS')->clean($message)
			);

			ee()->db->query(ee()->db->insert_string('exp_email_console_cache', $data));
		}
	}


	/** ----------------------------------------
	/**  Member List
	/** ----------------------------------------*/
	function memberlist()
	{
		/** ----------------------------------------
		/**  Can the user view profiles?
		/** ----------------------------------------*/

		if (ee()->session->userdata['can_view_profiles'] == 'n')
		{
			return ee()->output->show_user_error('general', array(ee()->lang->line('mbr_not_allowed_to_view_profiles')));
		}

		/** ----------------------------------------
		/**  Grab the templates
		/** ----------------------------------------*/

		$template = $this->_load_element('memberlist');
		$vars = ee('Variables/Parser')->extractVariables($template);
		$var_cond = ee()->functions->assign_conditional_variables($template, '/');

		$memberlist_rows = $this->_load_element('memberlist_rows');
		$mvars = ee('Variables/Parser')->extractVariables($memberlist_rows);
		$mvar_cond = ee()->functions->assign_conditional_variables($memberlist_rows, '/');

		$this->var_cond   = array_merge($var_cond, $mvar_cond);
		$this->var_single = array_merge($vars['var_single'], $mvars['var_single']);
		$this->var_pair   = array_merge($vars['var_pair'], $mvars['var_pair']);

		/** ----------------------------------------
		/**  Fetch the custom member field definitions
		/** ----------------------------------------*/

		$fields = array();

		$query = ee()->db->query("SELECT m_field_id, m_field_name FROM exp_member_fields");

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$fields[$row['m_field_name']] = $row['m_field_id'];
			}
		}

		/** ----------------------------------------
		/**  Assign default variables
		/** ----------------------------------------*/

		// CP allows member_id, username, dates, member_group
		// We'll convert username to screen_name and dates to join_date below
		$valid_order_bys = array('screen_name', 'total_comments', 'total_entries', 'total_posts', 'join_date', 'member_id', 'member_group');

		$sort_orders = array('asc', 'desc');

		if (($group_id = (int) ee()->input->post('group_id')) === 0)
		{
			$group_id = 0;
		}

		$sort_order = ( ! in_array(ee()->input->post('sort_order'), $sort_orders)) ? ee()->config->item('memberlist_sort_order') : ee()->input->post('sort_order');

		if (($row_limit = (int) ee()->input->post('row_limit')) === 0)
		{
			$row_limit = ee()->config->item('memberlist_row_limit');
		}

		if ( ! ($order_by = ee()->input->post('order_by')))
		{
			$order_by = ee()->config->item('memberlist_order_by');

			// Normalizing cp available sorts
			$order_by = ($order_by == 'username') ? 'screen_name' : $order_by;
			$order_by = ($order_by == 'dates') ? 'join_date' : $order_by;
		}

		if (($row_count = (int) ee()->input->post('row_count')) === 0)
		{
			$row_count = 0;
		}

		/* ----------------------------------------
		/*  Check for Search URL
		/*		- In an attempt to be clever, I decided to first check for
				the Search ID and if found, use an explode to set it and
				find a new $this->cur_id.  This solves the problem easily
				and saves me from using substr() and strpos() far too many times
				for a sane man to consider reasonable. -Paul
		/* ----------------------------------------*/

		$search_path = '';

		if (preg_match("|\/([a-z0-9]{32})\/|i", '/'.ee()->uri->query_string.'/', $match))
		{
			foreach(explode('/', '/'.ee()->uri->query_string.'/') as $val)
			{
				if (isset($search_id))
				{
					$this->cur_id = $val;
					break;
				}
				elseif($match['1'] == $val)
				{
					$search_id = $val;
					$search_path .= '/'.$search_id.'/';
				}
			}
		}

		/** ----------------------------------------
		/**  Parse the request URI
		/** ----------------------------------------*/

		// Redirect for old URI styles
		if (preg_match('/^([0-9]{1,})\-([0-9a-z_]{1,})\-([0-9a-z]{1,})\-([0-9]{1,})\-([0-9]{1,})/i', $this->cur_id, $matches))
		{
			$group_id   = $matches[1];
			$order_by   = $matches[2];
			$sort_order = $matches[3];
			$row_limit  = $matches[4];
			$row_count  = $matches[5];

			return ee()->functions->redirect($this->_member_path('memberlist').'/G'.$group_id.'/'.$order_by.'/'.$sort_order.'/L'.$row_limit.'/P'.$row_count, FALSE, 301);
		}

		$path = '';
		if (preg_match('#/G([0-9]+)/(.*?)/(.*?)/L([0-9]+)(?:/|\Z)#', ee()->uri->query_string, $matches))
		{
			$group_id   = $matches[1];
			$order_by   = $matches[2];
			$sort_order = $matches[3];
			$row_limit  = $matches[4];
		}

		// Ensure $order_by is valid
		if ( ! in_array($order_by, $valid_order_bys))
		{
			$order_by = ee()->config->item('memberlist_order_by');

			// Still not valid?
			if ( ! in_array($order_by, $valid_order_bys))
			{
				$order_by = 'member_id';
			}
		}

		$path = '/G'.$group_id.'/'.$order_by.'/'.$sort_order.'/L'.$row_limit;

		/** ----------------------------------------
		/**  Build the query
		/** ----------------------------------------*/

		if (array_intersect_key($fields, $this->var_single))
		{
			$mcf_select = ', md.*';
			$mcf_sql = ' LEFT JOIN exp_member_data md ON md.member_id = m.member_id ';
		}
		else
		{
			$mcf_select = '';
			$mcf_sql = '';
		}

		$f_sql	= "SELECT m.member_id, m.username, m.screen_name, m.email, m.join_date, m.last_visit, m.last_activity, m.last_entry_date, m.last_comment_date, m.last_forum_post_date, m.total_entries, m.total_comments, m.total_forum_topics, m.total_forum_posts, m.language, m.timezone, m.accept_user_email, m.avatar_filename, m.avatar_width, m.avatar_height, (m.total_forum_topics + m.total_forum_posts) AS total_posts, g.group_title as member_group {$mcf_select} ";
		$p_sql	= "SELECT COUNT(m.member_id) AS count ";
		$sql	= "FROM exp_members m
					LEFT JOIN exp_member_groups g ON g.group_id = m.group_id
					WHERE g.group_id != '3'
					AND g.group_id != '4'
					AND g.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'
					AND g.include_in_memberlist = 'y' ";

		if ($this->is_admin == FALSE OR ee()->session->userdata('group_id') != 1)
		{
			$sql .= "AND g.group_id != '2' ";
		}

		// 2 = Banned 3 = Guests 4 = Pending

		if ($group_id != 0)
		{
			$sql .= " AND g.group_id = '$group_id'";
		}

		/** ----------------------------------------
		/**  Load the Search's Member IDs
		/** ----------------------------------------*/

		if (isset($search_id))
		{
			$sql .= $this->fetch_search($search_id);
		}

		/** -------------------------------------
		/**  First Letter of Screen Name, Secret Addition
		/** -------------------------------------*/

		$first_letter = '';

		// No pagination
		// Pagination or No Pagination & Forum
		// Pagination & Forum

		for ($i=3; $i <= 5; ++ $i)
		{
			if (isset(ee()->uri->segments[$i]) && strlen(ee()->uri->segments[$i]) == 1 && preg_match("/[A-Z]{1}/", ee()->uri->segments[$i]))
			{
				$first_letter = ee()->uri->segments[$i];
				$sql .= " AND m.screen_name LIKE '{$first_letter}%' ";
				break;
			}
		}

		/** ----------------------------------------
		/**  Run "count" query for pagination
		/** ----------------------------------------*/

		$query = ee()->db->query($p_sql.$sql);

		if ( ! in_array($sort_order, array('asc', 'desc')))
		{
			$sort_order = 'desc';
		}

		if ($order_by == 'total_posts' OR $order_by == 'member_group')
		{
			$sql .= " ORDER BY ".$order_by." ".$sort_order;
		}
		else
		{
			$sql .= " ORDER BY m.".$order_by." ".$sort_order;
		}

		/** -----------------------------
		/**  Build Pagination
		/** -----------------------------*/

		// Check to see if the old style pagination exists
		// @deprecated 2.8
		if (stripos($template, LD.'if paginate'.RD) !== FALSE)
		{
			$template = preg_replace("/{if paginate}(.*?){\/if}/uis", "{paginate}$1{/paginate}", $template);
			ee()->load->library('logger');
			ee()->logger->developer('{if paginate} has been deprecated, use normal {paginate} tags in your Member List template.', TRUE, 604800);
			$config['last_link'] = ee()->lang->line('last');
			$config['suffix'] = ($first_letter != '') ? $first_letter.'/' : '';
			$config['first_url'] = $this->_member_path('memberlist'.$search_path.$path.'-0');
			$config['cur_page']	= ($row_count == '') ? '0' : $row_count;

			// Allows $config['cur_page'] to override
			$config['uri_segment'] = 0;

			if (preg_match("/".LD.'pagination_links'.RD."/", $template))
			{
				$config['first_tag_open'] = '<td><div class="paginate">';
				$config['first_tag_close'] = '</div></td>';
				$config['last_tag_open'] = '<td><div class="paginate">';
				$config['last_tag_close'] = '</div></td>';
				$config['next_tag_open'] = '<td><div class="paginate">';
				$config['next_tag_close'] = '</div></td>';
				$config['prev_tag_open'] = '<td><div class="paginate">';
				$config['prev_tag_close'] = '</div></td>';
				$config['cur_tag_open'] = '<td><div class="paginateCur">';
				$config['cur_tag_close'] = '</div></td>';
				$config['num_tag_close'] = '</div></td>';
			}

			ee()->pagination->initialize($config);
		}

		// Start running pagination
		ee()->load->library('pagination');
		$pagination = ee()->pagination->create();
		$pagination->position = 'inline';
		$pagination->basepath = $this->_member_path('memberlist').$path;
		$template = $pagination->prepare($template);

		if ($query->row('count') > $row_limit && $pagination->paginate === TRUE)
		{
			$pagination->build($query->row('count'), $row_limit);
			$sql .= " LIMIT ".$pagination->offset.", ".$row_limit;
		}

		/** ----------------------------------------
		/**  Run the full query and process result
		/** ----------------------------------------*/

		$sql = str_replace('WHERE', $mcf_sql.' WHERE', $sql);
		$query = ee()->db->query($f_sql.$sql);

		$str = '';
		$i = 0;

		if ($query->num_rows() > 0)
		{
			$member_ids = [];
			foreach ($query->result_array() as $row)
			{
				$member_ids[] = $row['member_id'];
			}

			$members = ee('Model')->get('Member', $member_ids)
				->all()
				->indexBy('member_id');

			foreach ($query->result_array() as $row)
			{
				$member = $members[$row['member_id']];
				foreach ($member->getCustomFieldNames() as $name)
				{
					$row[$name] = $member->$name;
				}

				$temp = $memberlist_rows;

				$style = ($i++ % 2) ? 'memberlistRowOne' : 'memberlistRowTwo';

				$temp = str_replace("{member_css}", $style, $temp);
				$temp = str_replace("{path:profile}", $this->_member_path($row['member_id']), $temp);

				$temp = $this->_var_swap(
					$temp,
					array(
						'email_console'	=> "onclick=\"window.open('".$this->_member_path('email_console/'.$row['member_id'])."', '_blank', 'width=650,height=600,scrollbars=yes,resizable=yes,status=yes,screenx=5,screeny=5');\"",
					)
				);

				$avatar_path	= '';
				$avatar_width	= '';
				$avatar_height	= '';

				/** ----------------------------------------
				/**  Parse conditional pairs
				/** ----------------------------------------*/

				foreach ($this->var_cond as $val)
				{
					/** ----------------------------------------
					/**  Conditional statements
					/** ----------------------------------------*/

					$cond = ee()->functions->prep_conditional($val['0']);

					$lcond	= substr($cond, 0, strpos($cond, ' '));
					$rcond	= substr($cond, strpos($cond, ' '));

					/** ----------------------------------------
					/**  Parse conditions in standard fields
					/** ----------------------------------------*/

					// array_key_exists instead of isset since columns can be NULL
					if (array_key_exists($val['3'], $row))
					{
						$lcond = str_replace($val['3'], "\$row['".$val['3']."']", $lcond);
						$cond = $lcond.' '.$rcond;
						$cond = str_replace("\|", "|", $cond);

						eval("\$result = ".$cond.";");

						if ($result)
						{
							$temp = preg_replace("/".LD.$val['0'].RD."(.*?)".LD.'\/if'.RD."/s", "\\1", $temp);
						}
						else
						{
							$temp = preg_replace("/".LD.$val['0'].RD."(.*?)".LD.'\/if'.RD."/s", "", $temp);
						}
					}
					/** ------------------------------------------
					/**  Parse conditions in custom member fields
					/** ------------------------------------------*/
					elseif (isset($fields[$val['3']]))
					{
						if (array_key_exists('m_field_id_'.$fields[$val['3']], $row))
						{
							$v = $row['m_field_id_'.$fields[$val['3']]];

							$lcond = str_replace($val['3'], "\$v", $lcond);
							$cond = $lcond.' '.$rcond;
							$cond = str_replace("\|", "|", $cond);

							eval("\$result = ".$cond.";");

							if ($result)
							{
								$temp = preg_replace("/".LD.$val['0'].RD."(.*?)".LD.'\/if'.RD."/s", "\\1", $temp);
							}
							else
							{
								$temp = preg_replace("/".LD.$val['0'].RD."(.*?)".LD.'\/if'.RD."/s", "", $temp);
							}
						}
					}

					/** ----------------------------------------
					/**  {if accept_email}
					/** ----------------------------------------*/
					if (preg_match("/^if\s+accept_email.*/i", $val['0']))
					{
						if ($row['accept_user_email'] == 'n')
						{
							$temp = $this->_deny_if('accept_email', $temp);
						}
						else
						{
							$temp = $this->_allow_if('accept_email', $temp);
						}
					}

					/** ----------------------------------------
					/**  {if avatar}
					/** ----------------------------------------*/
					if (preg_match("/^if\s+avatar.*/i", $val['0']))
					{
						if (ee()->config->item('enable_avatars') == 'y' AND ee()->session->userdata('display_avatars') == 'y' )
						{
							$avatar_path	= $member->getAvatarUrl();
							$avatar_width	= $row['avatar_width'];
							$avatar_height	= $row['avatar_height'];

							$temp = $this->_allow_if('avatar', $temp);
						}
						else
						{
							$temp = $this->_deny_if('avatar', $temp);
						}
					}

				}
				// END PAIRS


				/** ----------------------------------------
				/**  Manual replacements
				/** ----------------------------------------*/

				$name_replacement = ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
				$temp = $this->_var_swap_single('name', $name_replacement, $temp);

				/** ----------------------------------------
				/**  1:1 variables
				/** ----------------------------------------*/

				foreach ($this->var_single as $key => $val)
				{
					/** ----------------------------------------
					/**  parse profile path
					/** ----------------------------------------*/

					if (strncmp($key, 'profile_path', 12) == 0)
					{
						$temp = $this->_var_swap_single($key, ee()->functions->create_url(ee()->functions->extract_path($key).'/'.$row['member_id']), $temp);
					}

					/** ----------------------------------------
					/**  parse avatar path
					/** ----------------------------------------*/

					if ($key == 'path:avatar')
					{
						$temp = $this->_var_swap_single($key, $avatar_path, $temp);
					}

					/** ----------------------------------------
					/**  parse "last_visit"
					/** ----------------------------------------*/

					if (strncmp($key, 'last_visit', 10) == 0)
					{
						$temp = $this->_var_swap_single($key, ($row['last_activity'] > 0) ? ee()->localize->format_date($val, $row['last_activity']) : '--', $temp);
					}

					/** ----------------------------------------
					/**  parse "join_date"
					/** ----------------------------------------*/

					if (strncmp($key, 'join_date', 9) == 0)
					{
						$temp = $this->_var_swap_single($key, ($row['join_date'] > 0) ? ee()->localize->format_date($val, $row['join_date']) : '--', $temp);
					}

					/** ----------------------------------------
					/**  parse "last_entry_date"
					/** ----------------------------------------*/

					if (strncmp($key, 'last_entry_date', 15) == 0)
					{
						$temp = $this->_var_swap_single($key, ($row['last_entry_date'] > 0) ? ee()->localize->format_date($val, $row['last_entry_date']) : '--', $temp);
					}

					/** ----------------------------------------
					/**  parse "last_comment_date"
					/** ----------------------------------------*/

					if (strncmp($key, 'last_comment_date', 17) == 0)
					{
						$temp = $this->_var_swap_single($key, ($row['last_comment_date'] > 0) ? ee()->localize->format_date($val, $row['last_comment_date']) : '--', $temp);
					}

					/** ----------------------------------------
					/**  parse "last_forum_post_date"
					/** ----------------------------------------*/

					if (strncmp($key, 'last_forum_post_date', 20) == 0)
					{
						$temp = $this->_var_swap_single($key, ($row['last_forum_post_date'] > 0) ? ee()->localize->format_date($val, $row['last_forum_post_date']) : '--', $temp);
					}

					/** ----------------------------------------
					/**  {total_forum_posts}
					/** ----------------------------------------*/

					if ($key == 'total_forum_posts')
					{
						$temp = $this->_var_swap_single($val, $row['total_forum_topics']+$row['total_forum_posts'], $temp);
					}

					/** ----------------------------------------
					/**  {total_combined_posts}
					/** ----------------------------------------*/

					if ($key == 'total_combined_posts')
					{
						$temp = $this->_var_swap_single($val, $row['total_forum_topics']+$row['total_forum_posts']+$row['total_entries']+$row['total_comments'], $temp);
					}

					/** ----------------------------------------
					/**  {total_entries}
					/** ----------------------------------------*/

					if ($key == 'total_entries')
					{
						$temp = $this->_var_swap_single($val, $row['total_entries'], $temp);
					}

					/** ----------------------------------------
					/**  {total_comments}
					/** ----------------------------------------*/

					if ($key == 'total_comments')
					{
						$temp = $this->_var_swap_single($val, $row['total_comments'], $temp);
					}

					/** ----------------------------------------
					/**  parse literal variables
					/** ----------------------------------------*/

					if (isset($row[$val]))
					{
						$temp = $this->_var_swap_single($val, $row[$val], $temp);
					}

					/** ----------------------------------------
					/**  parse custom member fields
					/** ----------------------------------------*/

					if ( isset($fields[$val]) AND isset($row['m_field_id_'.$fields[$val]]))
					{
						$temp = $this->_var_swap_single($val, $row['m_field_id_'.$fields[$val]], $temp);
					}
				}

				$str .= $temp;
			}
		}

		/** ----------------------------------------
		/**  Render the member group list
		/** ----------------------------------------*/

		$english = array('Guests', 'Banned', 'Members', 'Pending', 'Super Admins');

		$sql = "SELECT group_id, group_title FROM exp_member_groups
				WHERE include_in_memberlist = 'y' AND site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."' AND group_id != '3' AND group_id != '4' ";

		if ($this->is_admin == FALSE OR ee()->session->userdata('group_id') != 1)
		{
			$sql .= "AND group_id != '2' ";
		}

		$sql .= " order by group_title";

		$query = ee()->db->query($sql);

		$selected = ($group_id == 0) ? " selected='selected' " : '';

		$menu = "<option value='0'".$selected.">".ee()->lang->line('mbr_all_member_groups')."</option>\n";

		foreach ($query->result_array() as $row)
		{
			$group_title = $row['group_title'];

			if (in_array($group_title, $english))
			{
				$group_title = ee()->lang->line(strtolower(str_replace(" ", "_", $group_title)));
			}

			$selected = ($group_id == $row['group_id']) ? " selected='selected' " : '';

			$menu .= "<option value='".$row['group_id']."'".$selected.">".$group_title."</option>\n";
		}

		$template = str_replace(LD.'group_id_options'.RD, $menu, $template);


		/** ----------------------------------------
		/**  Create the "Order By" menu
		/** ----------------------------------------*/

		$selected = ($order_by == 'screen_name') ? " selected='selected' " : '';
		$menu = "<option value='screen_name'".$selected.">".ee()->lang->line('mbr_member_name')."</option>\n";

		if ($this->in_forum == TRUE)
		{
			$selected = ($order_by == 'total_posts') ? " selected='selected' " : '';
			$menu .= "<option value='total_posts'".$selected.">".ee()->lang->line('total_posts')."</option>\n";
		}
		else
		{
			$selected = ($order_by == 'total_comments') ? " selected='selected' " : '';
			$menu .= "<option value='total_comments'".$selected.">".ee()->lang->line('mbr_total_comments')."</option>\n";

			$selected = ($order_by == 'total_entries') ? " selected='selected' " : '';
			$menu .= "<option value='total_entries'".$selected.">".ee()->lang->line('mbr_total_entries')."</option>\n";
		}

		$selected = ($order_by == 'join_date') ? " selected='selected' " : '';
		$menu .= "<option value='join_date'".$selected.">".ee()->lang->line('join_date')."</option>\n";

		$template = str_replace(LD.'order_by_options'.RD, $menu, $template);

		/** ----------------------------------------
		/**  Create the "Sort By" menu
		/** ----------------------------------------*/

		$selected = ($sort_order == 'asc') ? " selected='selected' " : '';
		$menu = "<option value='asc'".$selected.">".ee()->lang->line('mbr_ascending')."</option>\n";

		$selected = ($sort_order == 'desc') ? " selected='selected' " : '';
		$menu .= "<option value='desc'".$selected.">".ee()->lang->line('mbr_descending')."</option>\n";

		$template = str_replace(LD.'sort_order_options'.RD, $menu, $template);

		/** ----------------------------------------
		/**  Create the "Row Limit" menu
		/** ----------------------------------------*/

		$selected = ($row_limit == '10') ? " selected='selected' " : '';
		$menu  = "<option value='10'".$selected.">10</option>\n";
		$selected = ($row_limit == '20') ? " selected='selected' " : '';
		$menu .= "<option value='20'".$selected.">20</option>\n";
		$selected = ($row_limit == '30') ? " selected='selected' " : '';
		$menu .= "<option value='30'".$selected.">30</option>\n";
		$selected = ($row_limit == '40') ? " selected='selected' " : '';
		$menu .= "<option value='40'".$selected.">40</option>\n";
		$selected = ($row_limit == '50') ? " selected='selected' " : '';
		$menu .= "<option value='50'".$selected.">50</option>\n";

		if ($row_limit > 50)
		{
			$menu .= "<option value='".$row_limit."' selected='selected'>".$row_limit."</option>\n";
		}

		$template = str_replace(LD.'row_limit_options'.RD, $menu, $template);


		/** ----------------------------------------
		/**  Custom Member Fields for Member Search
		/** ----------------------------------------*/

		$sql = "SELECT m_field_id, m_field_label FROM exp_member_fields WHERE m_field_public = 'y' ORDER BY m_field_order ";

		$query = ee()->db->query($sql);

		$profile_options = '';

		foreach ($query->result_array() as $row)
		{
			$profile_options .= "<option value='m_field_id_".$row['m_field_id']."'>".$row['m_field_label']."</option>\n";
		}

		$template = str_replace(LD.'custom_profile_field_options'.RD, $profile_options, $template);

		/** ----------------------------------------
		/**  Put rendered chunk into template
		/** ----------------------------------------*/
		if ($pagination->paginate === TRUE)
		{
			$template = $pagination->render($template);
		}

		if ($this->is_search === TRUE)
		{
			$form_open = ee()->functions->form_declaration(array(
				'method' => 'post',
				'action' => $this->_member_path('member_search'.$search_path)
			));
		}
		else
		{
			$form_open = ee()->functions->form_declaration(array(
				'method' => 'post',
				'action' => $this->_member_path('memberlist'.(($first_letter != '') ? $first_letter.'/' : $search_path))
			));
		}

		$template = str_replace(LD."form_declaration".RD, $form_open, $template);
		$form_open_member_search = ee()->functions->form_declaration(array(
			'method' => 'post',
			'action' => $this->_member_path('do_member_search')
		));
		$template = str_replace(LD."form:form_declaration:do_member_search".RD, $form_open_member_search, $template);
		$template = str_replace(LD."member_rows".RD, $str, $template);

		return	$template;
	}


	/** ------------------------------------------
	/**  Take Search ID and Fetch Member IDs
	/** ------------------------------------------*/

	function fetch_search($search_id)
	{
		$query = ee()->db->query("SELECT * FROM exp_member_search WHERE search_id = '".ee()->db->escape_str($search_id)."'");

		if ($query->num_rows() == 0)
		{
			return '';
		}

		$this->is_search = TRUE;
		$this->search_keywords	= str_replace('|', ", ", $query->row('keywords') );
		$this->search_fields	= str_replace('|', ", ", $query->row('fields') );
		$this->search_total		= $query->row('total_results') ;

		$query = ee()->db->query($query->row('query') );

		$return = '';

		if ($query->num_rows() > 0)
		{
			$return = 'AND m.member_id IN (';

			foreach($query->result_array() as $row)
			{
				$return .= "'".$row['member_id']."',";
			}

			$return = substr($return, 0, -1).")";
		}

		return $return;
	}




	/** ------------------------------------------
	/**  Perform a Search
	/** ------------------------------------------*/

	function do_member_search()
	{
		/** ----------------------------------------
		/**  Fetch the search language file
		/** ----------------------------------------*/

		ee()->lang->loadfile('search');

		/** ----------------------------------------
		/**  Is the current user allowed to search?
		/** ----------------------------------------*/
		if (ee()->session->userdata['can_search'] == 'n' AND ee()->session->userdata['group_id'] != 1)
		{
			return ee()->output->show_user_error('general', array(ee()->lang->line('search_not_allowed')));
		}

		/** ----------------------------------------
		/**  Flood control
		/** ----------------------------------------*/

		if (ee()->session->userdata['search_flood_control'] > 0 AND ee()->session->userdata['group_id'] != 1)
		{
			$cutoff = time() - ee()->session->userdata['search_flood_control'];

			$sql = "SELECT search_id FROM exp_search WHERE site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."' AND search_date > '{$cutoff}' AND ";

			if (ee()->session->userdata['member_id'] != 0)
			{
				$sql .= "(member_id='".ee()->db->escape_str(ee()->session->userdata('member_id'))."' OR ip_address='".ee()->db->escape_str(ee()->input->ip_address())."')";
			}
			else
			{
				$sql .= "ip_address='".ee()->db->escape_str(ee()->input->ip_address())."'";
			}

			$query = ee()->db->query($sql);

			$text = str_replace("%x", ee()->session->userdata['search_flood_control'], ee()->lang->line('search_time_not_expired'));

			if ($query->num_rows() > 0)
			{
				return ee()->output->show_user_error('general', array($text));
			}
		}

		/** ----------------------------------------
		/**  Valid Fields for Searching
		/** ----------------------------------------*/

		$valid = array('screen_name', 'email', 'signature');

		$custom_fields = FALSE;
		$query = ee()->db->query("SELECT m_field_id, m_field_label FROM exp_member_fields WHERE m_field_public = 'y' ORDER BY m_field_order");

		if ($query->num_rows() > 0)
		{
			$custom_fields = array();

			foreach($query->result_array() as $row)
			{
				$custom_fields[$row['m_field_id']] = $row['m_field_label'];

				$valid[] = 'm_field_id_'.$row['m_field_id'];
			}
		}

		/** ----------------------------------------
		/**  Compile the Search
		/** ----------------------------------------*/

		$search_array = array();

		foreach($_POST as $key => $value)
		{
			if (substr($key, 0, 13) == 'search_field_' && isset($_POST['search_keywords_'.substr($key, 13)]))
			{
				if (in_array($value, $valid) && trim($_POST['search_keywords_'.substr($key, 13)]) != '')
				{
					$search_array[] = array($value, trim($_POST['search_keywords_'.substr($key, 13)]));
				}
			}
		}

		/** ----------------------------------------
		/**  Stuff that is tediously boring to explain
		/** ----------------------------------------*/

		if (isset($_POST['search_group_id']))
		{
			$_POST['group_id'] = $_POST['search_group_id'];
		}

		if (count($search_array) == 0)
		{
			return $this->memberlist();
		}

		/** ----------------------------------------
		/**  Create Query
		/** ----------------------------------------*/

		$keywords = array();
		$fields	= array();

		$xsql = ($this->is_admin == FALSE OR ee()->session->userdata('group_id') != 1) ? ",'2'" : "";

		if ($custom_fields === FALSE)
		{
			$sql = "SELECT m.member_id FROM exp_members m
					WHERE m.group_id NOT IN ('3', '4'{$xsql}) ";
		}
		else
		{
			$sql = "SELECT m.member_id FROM exp_members m, exp_member_data md
					WHERE m.member_id = md.member_id
					AND m.group_id NOT IN ('3', '4'{$xsql}) ";
		}

		if (isset($_POST['search_group_id']) && $_POST['search_group_id'] != '0')
		{
			$sql .= "AND m.group_id = '".ee()->db->escape_str($_POST['search_group_id'])."'";
		}

		foreach($search_array as $search)
		{
			if (substr($search['0'], 0, 11) == 'm_field_id_' && is_numeric(substr($search['0'], 11)))
			{
				$fields[] = $custom_fields[substr($search['0'], 11)];

				$sql .= "AND md.".$search['0']." LIKE '%".ee()->db->escape_like_str($search['1'])."%' ";
			}
			else
			{
				$fields[] = ee()->lang->line($search['0']);

				$sql .= "AND m.".$search['0']." LIKE '%".ee()->db->escape_like_str($search['1'])."%' ";
			}

			$keywords[] = $search['1'];
		}

		$query = ee()->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return ee()->output->show_user_error('off', array(ee()->lang->line('search_no_result')), ee()->lang->line('search_result_heading'));
		}

		/** ----------------------------------------
		/**  If we have a result, cache it
		/** ----------------------------------------*/

		$hash = ee()->functions->random('md5');

		$data = array(
			'search_id'		=> $hash,
			'search_date'	=> ee()->localize->now,
			'member_id'		=> ee()->session->userdata('member_id'),
			'keywords'		=> implode('|', $keywords),
			'fields'		=> implode('|', $fields),
			'ip_address'	=> ee()->input->ip_address(),
			'total_results'	=> $query->num_rows,
			'query'			=> $sql,
			'site_id'		=> ee()->config->item('site_id')
		);

		ee()->db->query(ee()->db->insert_string('exp_member_search', $data));

		/** ----------------------------------------
		/**  Redirect to search results page
		/** ----------------------------------------*/

		return ee()->functions->redirect(reduce_double_slashes($this->_member_path('member_search/'.$hash)));
	}
}
// END CLASS

// EOF
