<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

class Member_settings extends Member {


	/** ----------------------------------
	/**  Member_settings Profile Constructor
	/** ----------------------------------*/
	function Member_settings()
	{
	}


	/** ----------------------------------------
	/**  Member Profile - Menu
	/** ----------------------------------------*/
	function profile_menu()
	{
		$menu = $this->_load_element('menu');

		if ($this->EE->config->item('allow_member_localization') == 'n' AND $this->EE->session->userdata('group_id') != 1)
		{
			$menu = $this->_deny_if('allow_localization', $menu);
		}
		else
		{
			$menu = $this->_allow_if('allow_localization', $menu);
		}

		return $this->_var_swap($menu,
								array(
										'path:profile'			=> $this->_member_path('edit_profile'),
										'path:email'			=> $this->_member_path('edit_email'),
										'path:username'			=> $this->_member_path('edit_userpass'),
										'path:localization'		=> $this->_member_path('edit_localization'),
										'path:subscriptions'	=> $this->_member_path('edit_subscriptions'),
										'path:ignore_list'		=> $this->_member_path('edit_ignore_list'),
										'path:notepad'			=> $this->_member_path('edit_notepad'),
										'include:messages_menu' => $this->pm_menu()
									 )
								 );
	}


	/** ----------------------------------------
	/**  Member Profile Main Page
	/** ----------------------------------------*/
	function profile_main()
	{
		$query = $this->EE->db->query("SELECT email, join_date, last_visit, last_activity, last_entry_date, last_comment_date, total_forum_topics, total_forum_posts, total_entries, total_comments, last_forum_post_date FROM exp_members WHERE member_id = '".$this->EE->session->userdata('member_id')."'");

		$time_fmt = ($this->EE->session->userdata['time_format'] != '') ? $this->EE->session->userdata['time_format'] : $this->EE->config->item('time_format');
		$datecodes = ($time_fmt == 'us') ? $this->us_datecodes : $this->eu_datecodes;

		return  $this->_var_swap($this->_load_element('home_page'),
								array(
										'email'						=> $query->row('email') ,
										'join_date'					=> $this->EE->localize->decode_date($datecodes['long'], $query->row('join_date') ),
										'last_visit_date'			=> ($query->row('last_activity')  == 0) ? '--' : $this->EE->localize->decode_date($datecodes['long'], $query->row('last_activity') ),
										'recent_entry_date'			=> ($query->row('last_entry_date')  == 0) ? '--' : $this->EE->localize->decode_date($datecodes['long'], $query->row('last_entry_date') ),
										'recent_comment_date'		=> ($query->row('last_comment_date')  == 0) ? '--' : $this->EE->localize->decode_date($datecodes['long'], $query->row('last_comment_date') ),
										'recent_forum_post_date'	=> ($query->row('last_forum_post_date')  == 0) ? '--' : $this->EE->localize->decode_date($datecodes['long'], $query->row('last_forum_post_date') ),
										'total_topics'				=> $query->row('total_forum_topics') ,
										'total_posts'				=> $query->row('total_forum_posts')  + $query->row('total_forum_topics') ,
										'total_replies'				=> $query->row('total_forum_posts') ,
										'total_entries'				=> $query->row('total_entries') ,
										'total_comments'			=> $query->row('total_comments')
									)
								);
	}




	/** ----------------------------------------
	/**  Member Public Profile
	/** ----------------------------------------*/
	function public_profile()
	{
		/** ----------------------------------------
		/**  Can the user view profiles?
		/** ----------------------------------------*/

		if ($this->EE->session->userdata('can_view_profiles') == 'n')
		{
			return $this->EE->output->show_user_error('general', 
					array($this->EE->lang->line('mbr_not_allowed_to_view_profiles')));
		}

		// No member id, no view
		if ($this->cur_id == '')
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('profile_not_available')));
		}

		/** ----------------------------------------
		/**  Fetch the member data
		/** ----------------------------------------*/

		$select = 'm.member_id, m.group_id, m.username, m.screen_name, m.email, m.signature, 
					m.avatar_filename, m.avatar_width, m.avatar_height, m.photo_filename, 
					m.photo_width, m.photo_height, m.url, m.location, m.occupation, m.interests,
					m.icq, m.aol_im, m.yahoo_im, m.msn_im, m.bio, m.join_date, m.last_visit, 
					m.last_activity, m.last_entry_date, m.last_comment_date, m.last_forum_post_date, 
					m.total_entries, m.total_comments, m.total_forum_topics,
					m.total_forum_posts, m.language, m.timezone, m.daylight_savings, 
					m.bday_d, m.bday_m, m.bday_y, m.accept_user_email, m.accept_messages,
					g.group_title, g.can_send_private_messages';

		$this->EE->db->select($select);
		$this->EE->db->from(array('members m', 'member_groups g'));
		$this->EE->db->where('m.member_id', (int)$this->cur_id);
		$this->EE->db->where('g.site_id', $this->EE->config->item('site_id'));
		$this->EE->db->where('m.group_id', 'g.group_id', FALSE);

		if ($this->is_admin == FALSE OR $this->EE->session->userdata('group_id') != 1)
		{
			$this->EE->db->where('m.group_id !=', 2);
		}

		$this->EE->db->where('m.group_id !=', 3);
		$this->EE->db->where('m.group_id !=', 4);

		$query = $this->EE->db->get();

		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('profile_not_available')));
		}

		// Fetch the row
		$row = $query->row_array();

		/** ----------------------------------------
		/**  Fetch the template
		/** ----------------------------------------*/

		$content = $this->_load_element('public_profile');

		/** ----------------------------------------
		/**  Is there an avatar?
		/** ----------------------------------------*/

		if ($this->EE->config->item('enable_avatars') == 'y' AND $row['avatar_filename']  != '')
		{
			$avatar_path	= $this->EE->config->slash_item('avatar_url').$row['avatar_filename'] ;
			$avatar_width	= $row['avatar_width'] ;
			$avatar_height	= $row['avatar_height'] ;

			$content = $this->_allow_if('avatar', $content);
		}
		else
		{
			$avatar_path	= '';
			$avatar_width	= '';
			$avatar_height	= '';

			$content = $this->_deny_if('avatar', $content);
		}

		/** ----------------------------------------
		/**  Is there a member photo?
		/** ----------------------------------------*/

		if ($this->EE->config->item('enable_photos') == 'y' AND $row['photo_filename'] != '')
		{
			$photo_path		= $this->EE->config->slash_item('photo_url').$row['photo_filename'] ;
			$photo_width	= $row['photo_width'] ;
			$photo_height	= $row['photo_height'] ;

			$content = $this->_allow_if('photo', $content);
			$content = $this->_deny_if('not_photo', $content);
		}
		else
		{
			$photo_path	= '';
			$photo_width	= '';
			$photo_height	= '';

			$content = $this->_deny_if('photo', $content);
			$content = $this->_allow_if('not_photo', $content);
		}


		/** ----------------------------------------
		/**  Forum specific stuff
		/** ----------------------------------------*/

		$rank_class = 'rankMember';
		$rank_title	= '';
		$rank_stars	= '';
		$stars		= '';

		if ($this->in_forum == TRUE)
		{
			$rank_query	 = $this->EE->db->query("SELECT rank_title, rank_min_posts, rank_stars FROM exp_forum_ranks ORDER BY rank_min_posts");
			$mod_query	 = $this->EE->db->query("SELECT mod_member_id, mod_group_id FROM exp_forum_moderators");

			$total_posts = ($row['total_forum_topics']  + $row['total_forum_posts'] );

			/** ----------------------------------------
			/**  Assign the rank stars
			/** ----------------------------------------*/

			if (preg_match("/{if\s+rank_stars\}(.+?){\/if\}/i", $content, $matches))
			{
				$rank_stars = $matches['1'];
				$content = str_replace($matches['0'], '{rank_stars}', $content);
			}

			if ($rank_stars != '' AND $rank_query->num_rows() > 0)
			{
				$num_stars = NULL;
				$rank_title = '';

				$i = 1;
				foreach ($rank_query->result_array() as $rank)
				{
					if ($num_stars == NULL)
					{
						$num_stars	= $rank['rank_stars'];
						$rank_title	= $rank['rank_title'];
					}

					if ($rank['rank_min_posts'] >= $total_posts)
					{
						$stars = str_repeat($rank_stars, $num_stars);
						break;
					}
					else
					{
						$num_stars	= $rank['rank_stars'];
						$rank_title = $rank['rank_title'];
					}

					if ($i++ == $rank_query->num_rows)
					{
						$stars = str_repeat($rank_stars,  $num_stars);
						break;
					}
				}
			}

			/** ----------------------------------------
			/**  Assign the member rank
			/** ----------------------------------------*/

			// Is the user an admin?

			$admin_query = $this->EE->db->query('SELECT admin_group_id, admin_member_id FROM exp_forum_administrators');

			$is_admin = FALSE;
			if ($admin_query->num_rows() > 0)
			{
				foreach ($admin_query->result_array() as $admin)
				{
					if ($admin['admin_member_id'] != 0)
					{
						if ($admin['admin_member_id'] == $this->cur_id)
						{
							$is_admin = TRUE;
							break;
						}
					}
					elseif ($admin['admin_group_id'] != 0)
					{
						if ($admin['admin_group_id'] == $row['group_id'] )
						{
							$is_admin = TRUE;
							break;
						}
					}
				}
			}


			if ($row['group_id']  == 1 OR $is_admin == TRUE)
			{
				$rankclass = 'rankAdmin';
				$rank_class = 'rankAdmin';
				$rank_title = $this->EE->lang->line('administrator');
			}
			else
			{
				if ($mod_query->num_rows() > 0)
				{
					foreach ($mod_query->result_array() as $mod)
					{
						if ($mod['mod_member_id'] == $this->cur_id OR $mod['mod_group_id'] == $row['group_id'] )
						{
							$rank_class = 'rankModerator';
							$rank_title = $this->EE->lang->line('moderator');
							break;
						}
					}
				}
			}
		}

		/** ----------------------------------------
		/**  Parse variables
		/** ----------------------------------------*/

		if ($this->in_forum == TRUE)
		{
			$search_path = $this->forum_path.'member_search/'.$this->cur_id.'/';
		}
		else
		{
			$search_path = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Search', 'do_search').'&amp;mbr='.urlencode($row['member_id'] );
		}

		$ignore_form = array('hidden_fields'	=> array('toggle[]' => '', 'name' => '', 'daction' => ''),
							  'action'			=> $this->_member_path('update_ignore_list'),
						 	  'id'				=> 'target'
						 	  );

		if ( ! in_array($row['member_id'] , $this->EE->session->userdata['ignore_list']))
		{
			$ignore_button = "<div><a href='".$this->_member_path('edit_ignore_list')."' ".
								"onclick='dynamic_action(\"add\");list_addition(\"".$row['screen_name'] ."\");return false;'>".
								"{lang:ignore_member}</a></div></form>";
		}
		else
		{
			$ignore_button = "<div><a href='".$this->_member_path('edit_ignore_list')."' ".
								"onclick='dynamic_action(\"delete\");list_addition(\"".$row['member_id'] ."\", \"toggle[]\");return false;'>".
								"{lang:unignore_member}</a></div></form>";
		}

		$content = $this->_var_swap($content,
										array(
												'aim_console'			=> "onclick=\"window.open('".$this->_member_path('aim_console/'.$this->cur_id)."', '_blank', 'width=240,height=360,scrollbars=yes,resizable=yes,status=yes,screenx=5,screeny=5');\"",
												'icq_console'			=> "onclick=\"window.open('".$this->_member_path('icq_console/'.$this->cur_id)."', '_blank', 'width=650,height=580,scrollbars=yes,resizable=yes,status=yes,screenx=5,screeny=5');\"",
												'yahoo_console'			=> "http://edit.yahoo.com/config/send_webmesg?.target=".$row['yahoo_im'] ."&amp;.src=pg",
												'email_console'			=> "onclick=\"window.open('".$this->_member_path('email_console/'.$this->cur_id)."', '_blank', 'width=650,height=600,scrollbars=yes,resizable=yes,status=yes,screenx=5,screeny=5');\"",
												'send_private_message'	=> $this->_member_path('messages/pm/'.$this->cur_id),
												'search_path'			=> $search_path,
												'path:avatar_url'		=> $avatar_path,
												'avatar_width'			=> $avatar_width,
												'avatar_height'			=> $avatar_height,
												'path:photo_url'		=> $photo_path,
												'photo_width'			=> $photo_width,
												'photo_height'			=> $photo_height,
												'rank_class'			=> $rank_class,
												'rank_stars'			=> $stars,
												'rank_title'			=> $rank_title,
												'ignore_link'			=> $this->list_js().
																			$this->EE->functions->form_declaration($ignore_form).
																			$ignore_button
											)
										);


		$vars = $this->EE->functions->assign_variables($content, '/');
		$this->var_single	= $vars['var_single'];
		$this->var_pair		= $vars['var_pair'];

		$this->var_cond = $this->EE->functions->assign_conditional_variables($content, '/');

		/** ----------------------------------------
		/**  Parse conditional pairs
		/** ----------------------------------------*/
		foreach ($this->var_cond as $val)
		{
			/** ----------------------------------------
			/**  Conditional statements
			/** ----------------------------------------*/

			$cond = $this->EE->functions->prep_conditional($val['0']);

			$lcond	= substr($cond, 0, strpos($cond, ' '));
			$rcond	= substr($cond, strpos($cond, ' '));

			if ( isset($row[$val['3']]))
			{
				$lcond = str_replace($val['3'], "\$row['".$val['3'] ."']", $lcond);
				$cond = $lcond.' '.$rcond;
				$cond = str_replace("\|", "|", $cond);

				eval("\$result = ".$cond.";");

				if ($result)
				{
					$content = preg_replace("/".LD.$val['0'].RD."(.*?)".LD.'\/if'.RD."/s", "\\1", $content);
				}
				else
				{
					$content = preg_replace("/".LD.$val['0'].RD."(.*?)".LD.'\/if'.RD."/s", "", $content);
				}
			}

			/** ----------------------------------------
			/**  {if accept_email}
			/** ----------------------------------------*/
			if (preg_match("/^if\s+accept_email.*/i", $val['0']))
			{
				if ($row['accept_user_email'] == 'n')
				{
					$content = preg_replace("/".LD.$val['0'].RD."(.+?)".LD.'\/if'.RD."/s", "", $content);
				}
				else
				{
					$content = preg_replace("/".LD.$val['0'].RD."(.+?)".LD.'\/if'.RD."/s", "\\1", $content);
				}
			}

			/** ----------------------------------------
			/**  {if can_private_message}
			/** ----------------------------------------*/
			if (stristr($val['0'], 'can_private_message'))
			{
				if ($row['can_send_private_messages'] == 'n' OR $row['accept_messages'] == 'n')
				{
					$content = preg_replace("/".LD.$val['0'].RD."(.+?)".LD.'\/if'.RD."/s", "", $content);
				}
				else
				{
					$content = preg_replace("/".LD.$val['0'].RD."(.+?)".LD.'\/if'.RD."/s", "\\1", $content);
				}
			}

			/** -------------------------------------
			/**  {if ignore}
			/** -------------------------------------*/

			if (stristr($val['0'], 'ignore'))
			{
				if ($row['member_id'] == $this->EE->session->userdata['member_id'])
				{
					$content = $this->_deny_if('ignore', $content);
				}
				else
				{
					$content = $this->_allow_if('ignore', $content);
				}
			}
		}
		// END CONDITIONAL PAIRS
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();

		/** ----------------------------------------
		/**  Parse "single" variables
		/** ----------------------------------------*/
		foreach ($this->var_single as $key => $val)
		{
			/** ----------------------------------------
			/**  Format URLs
			/** ----------------------------------------*/
			if ($key == 'url')
			{
				if (strncmp($row['url'], 'http', 4) != 0 && strpos($row['url'], '://') === FALSE)
				{
					$row['url'] = "http://".$row['url'] ;
				}
			}

			/** ----------------------------------------
			/**  "last_visit"
			/** ----------------------------------------*/

			if (strncmp($key, 'last_visit', 10) == 0)
			{
				$content = $this->_var_swap_single($key, ($row['last_activity'] > 0) ? $this->EE->localize->decode_date($val, $row['last_activity'] ) : '', $content);
			}

			/** ----------------------------------------
			/**  "join_date"
			/** ----------------------------------------*/

			if (strncmp($key, 'join_date', 9) == 0)
			{
				$content = $this->_var_swap_single($key, ($row['join_date'] > 0) ? $this->EE->localize->decode_date($val, $row['join_date'] ) : '', $content);
			}

			/** ----------------------------------------
			/**  "last_entry_date"
			/** ----------------------------------------*/

			if (strncmp($key, 'last_entry_date', 15) == 0)
			{
				$content = $this->_var_swap_single($key, ($row['last_entry_date']  > 0) ? $this->EE->localize->decode_date($val, $row['last_entry_date'] ) : '', $content);
			}

			/** ----------------------------------------
			/**  "last_forum_post_date"
			/** ----------------------------------------*/

			if (strncmp($key, 'last_forum_post_date', 20) == 0)
			{
				$content = $this->_var_swap_single($key, ($row['last_forum_post_date']  > 0) ? $this->EE->localize->decode_date($val, $row['last_forum_post_date'] ) : '', $content);
			}

			/** ----------------------------------------
			/**  parse "recent_comment"
			/** ----------------------------------------*/

			if (strncmp($key, 'last_comment_date', 17) == 0)
			{
				$content = $this->_var_swap_single($key, ($row['last_comment_date']  > 0) ? $this->EE->localize->decode_date($val, $row['last_comment_date'] ) : '', $content);
			}

			/** ----------------------
			/**  {name}
			/** ----------------------*/

			$name = ( ! $row['screen_name'] ) ? $row['username']  : $row['screen_name'] ;

			$name = $this->_convert_special_chars($name);

			if ($key == "name")
			{
				$content = $this->_var_swap_single($val, $name, $content);
			}

			/** ----------------------
			/**  {member_group}
			/** ----------------------*/

			if ($key == "member_group")
			{
				$content = $this->_var_swap_single($val, $row['group_title'] , $content);
			}

			/** ----------------------
			/**  {email}
			/** ----------------------*/

			if ($key == "email")
			{
				$content = $this->_var_swap_single($val, $this->EE->typography->encode_email($row['email'] ), $content);
			}

			/** ----------------------
			/**  {birthday}
			/** ----------------------*/

			if ($key == "birthday")
			{
				$birthday = '';

				if ($row['bday_m']  != '' AND $row['bday_m']  != 0)
				{
					$month = (strlen($row['bday_m'] ) == 1) ? '0'.$row['bday_m'] : $row['bday_m'];

					$m = $this->EE->localize->localize_month($month);

					$birthday .= $this->EE->lang->line($m['1']);

					if ($row['bday_d'] != '' AND $row['bday_d']  != 0)
					{
						$birthday .= ' '.$row['bday_d'] ;
					}
				}

				if ($row['bday_y']  != '' AND $row['bday_y']  != 0)
				{
					if ($birthday != '')
					{
						$birthday .= ', ';
					}

					$birthday .= $row['bday_y'] ;
				}

				if ($birthday == '')
				{
					$birthday = '';
				}

				$content = $this->_var_swap_single($val, $birthday, $content);
			}

			/** ----------------------
			/**  {timezone}
			/** ----------------------*/

			if ($key == "timezone")
			{
				$timezone = ($row['timezone']  != '') ? $this->EE->lang->line($row['timezone'] ) : '';

				$content = $this->_var_swap_single($val, $timezone, $content);
			}

			/** ----------------------
			/**  {local_time}
			/** ----------------------*/

			if (strncmp($key, 'local_time', 10) == 0)
			{
				$time = $this->EE->localize->now;

				if ($this->EE->session->userdata('member_id') != $this->cur_id)
				{  
					// Default is UTC?
					$zone = ($row['timezone']  == '') ? 'UTC' : $row['timezone'] ;
					$time = $this->EE->localize->set_localized_time($time, $zone, $row['daylight_savings'] );
				}

				$content = $this->_var_swap_single($key, $this->EE->localize->decode_date($val, $time), $content);
			}

			/** ----------------------
			/**  {bio}
			/** ----------------------*/

			if ($key == 'bio')
			{
				$bio = $this->EE->typography->parse_type($row[$val],
															 array(
																		'text_format'	=> 'xhtml',
																		'html_format'	=> 'safe',
																		'auto_links'	=> 'y',
																		'allow_img_url' => 'n'
																	)
															);

				$content = $this->_var_swap_single($key, $bio, $content);
			}

			// Special consideration for {total_forum_replies}, and
			// {total_forum_posts} whose meanings do not match the
			// database field names
			if ($key == 'total_forum_replies')
			{
				$content = $this->_var_swap_single($key, $row['total_forum_posts'] , $content);
			}

			if ($key == 'total_forum_posts')
			{
				$total_posts = $row['total_forum_topics'] + $row['total_forum_posts'];
				$content = $this->_var_swap_single($key, $total_posts, $content);
			}

			/** ----------------------------------------
			/**  parse basic fields (username, screen_name, etc.)
			/** ----------------------------------------*/

			// array_key_exists instead of isset since some columns may be NULL
			if (array_key_exists($val, $row))
			{				
				$content = $this->_var_swap_single($val, strip_tags($row[$val]), $content);
			}
		}


		/** -------------------------------------
		/**  Do we have custom fields to show?
		/** ------------------------------------*/
		// Grab the data for the particular member

		$sql = "SELECT m_field_id, m_field_name, m_field_label, m_field_description, m_field_fmt FROM  exp_member_fields ";

		if ($this->EE->session->userdata['group_id'] != 1)
		{
			$sql .= " WHERE m_field_public = 'y' ";
		}

		$sql .= " ORDER BY m_field_order";

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() > 0)
		{
			$fnames = array();

			foreach ($query->result_array() as $row)
			{
				$fnames[$row['m_field_name']] = $row['m_field_id'];
			}

			$result = $this->EE->db->query("SELECT * FROM exp_member_data WHERE member_id = '{$this->cur_id}'");

			/** ----------------------------------------
			/**  Parse conditionals for custom fields
			/** ----------------------------------------*/

			$result_row = $result->row_array();

			foreach ($this->var_cond as $val)
			{
				// Prep the conditional

				$cond = $this->EE->functions->prep_conditional($val['0']);

				$lcond	= substr($cond, 0, strpos($cond, ' '));
				$rcond	= substr($cond, strpos($cond, ' '));

				if (isset($fnames[$val['3']]))
				{
					$lcond = str_replace($val['3'], "\$result_row['m_field_id_".$fnames[$val['3']]."']", $lcond);

					$cond = $lcond.' '.$rcond;

					$cond = str_replace("\|", "|", $cond);

					eval("\$rez = ".$cond.";");

					if ($rez)
					{
						$content = preg_replace("/".LD.$val['0'].RD."(.*?)".LD.'\/if'.RD."/s", "\\1", $content);
					}
					else
					{
						$content = preg_replace("/".LD.$val['0'].RD."(.*?)".LD.'\/if'.RD."/s", "", $content);
					}
				}

			}
			// END CONDITIONALS

			/** ----------------------------------------
			/**  Parse single variables
			/** ----------------------------------------*/

			foreach ($this->var_single as $key => $val)
			{
				foreach ($query->result_array() as $row)
				{
					if ($row['m_field_name'] == $key)
					{
						$field_data = (isset($result_row['m_field_id_'.$row['m_field_id']])) ? $result_row['m_field_id_'.$row['m_field_id']] : '';

						if ($field_data != '')
						{
							$field_data = $this->EE->typography->parse_type($field_data,
																		 array(
																					'text_format'	=> $row['m_field_fmt'],
																					'html_format'	=> 'none',
																					'auto_links'	=> 'n',
																					'allow_img_url' => 'n'
																				)
																		);
						}

						$content = $this->_var_swap_single($val, $field_data, $content);
					}
				}
			}

			/** ----------------------------------------
			/**  Parse auto-generated "custom_fields"
			/** ----------------------------------------*/

			$field_chunk = $this->_load_element('public_custom_profile_fields');

			// Is there a chunk to parse?

			if ($query->num_rows() == 0)
			{
				$content = str_replace("/{custom_profile_fields}/s", '', $content);
			}
			else
			{
				$this->EE->load->library('typography');
				$this->EE->typography->initialize();

				$str = '';

				foreach ($query->result_array() as $row)
				{
					$temp = $field_chunk;

					$field_data = (isset($result_row['m_field_id_'.$row['m_field_id']])) ? $result_row['m_field_id_'.$row['m_field_id']] : '';

					if ($field_data != '')
					{
						$field_data = $this->EE->typography->parse_type($field_data,
																	 array(
																				'text_format'	=> $row['m_field_fmt'],
																				'html_format'	=> 'safe',
																				'auto_links'	=> 'y',
																				'allow_img_url' => 'n'
																			)
																	);



					}


					$temp = str_replace('{field_name}', $row['m_field_label'], $temp);
					$temp = str_replace('{field_description}', $row['m_field_description'], $temp);
					$temp = str_replace('{field_data}', $field_data, $temp);

					$str .= $temp;

				}

				$content = str_replace("{custom_profile_fields}", $str, $content);
			}

		}
		// END  if ($quey->num_rows() > 0)

		/** ----------------------------------------
		/**  Clean up left over variables
		/** ----------------------------------------*/

		$content = str_replace(LD.'custom_profile_fields'.RD, '', $content);

		return $content;
	}


	/** ----------------------------------------
	/**  Member Profile Edit Page
	/** ----------------------------------------*/
	function edit_profile()
	{
		// Load the form helper
		$this->EE->load->helper('form');

		/** ----------------------------------------
		/**  Build the custom profile fields
		/** ----------------------------------------*/

		$tmpl = $this->_load_element('custom_profile_fields');

		/** ----------------------------------------
		/**  Fetch the data
		/** ----------------------------------------*/

		$sql = "SELECT * FROM exp_member_data WHERE member_id = '".$this->EE->session->userdata('member_id')."'";

		$result = $this->EE->db->query($sql);

		if ($result->num_rows() > 0)
		{
			foreach ($result->row_array() as $key => $val)
			{
				$$key = $val;
			}
		}

		/** ----------------------------------------
		/**  Fetch the field definitions
		/** ----------------------------------------*/

		$r = '';

		$sql = "SELECT *  FROM exp_member_fields ";

		if ($this->EE->session->userdata['group_id'] != 1)
		{
			$sql .= " WHERE m_field_public = 'y' ";
		}

		$sql .= " ORDER BY m_field_order";

		$query = $this->EE->db->query($sql);

		$result_row = $result->row_array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$temp = $tmpl;

				/** ----------------------------------------
				/**  Assign the data to the field
				/** ----------------------------------------*/

				$temp = str_replace('{field_id}', $row['m_field_id'], $temp);

				$field_data = (isset($result_row['m_field_id_'.$row['m_field_id']])) ? $result_row['m_field_id_'.$row['m_field_id']] : '';

				$required  = ($row['m_field_required'] == 'n') ? '' : "<span class='alert'>*</span>&nbsp;";

				if ($row['m_field_width'] == '')
				{
					$row['m_field_width'] == '100%';
				}

				$width = ( ! stristr($row['m_field_width'], 'px')  AND ! stristr($row['m_field_width'], '%')) ? $row['m_field_width'].'px' : $row['m_field_width'];

				/** ----------------------------------------
				/**  Render textarea fields
				/** ----------------------------------------*/

				if ($row['m_field_type'] == 'textarea')
				{
					$rows = ( ! isset($row['m_field_ta_rows'])) ? '10' : $row['m_field_ta_rows'];

					$tarea = "<textarea name='".'m_field_id_'.$row['m_field_id']."' id='".'m_field_id_'.$row['m_field_id']."' style='width:".$width.";' class='textarea' cols='90' rows='{$rows}'>".form_prep($field_data)."</textarea>";

					$temp = str_replace('<td ', "<td valign='top' ", $temp);
					$temp = str_replace('{lang:profile_field}', $required.$row['m_field_label'], $temp);
					$temp = str_replace('{lang:profile_field_description}', $row['m_field_description'], $temp);
					$temp = str_replace('{form:custom_profile_field}', $tarea, $temp);
				}
				elseif ($row['m_field_type'] == 'text')
				{
					/** ----------------------------------------
					/**  Render text fields
					/** ----------------------------------------*/

					$input = "<input type='text' name='".'m_field_id_'.$row['m_field_id']."' id='".'m_field_id_'.$row['m_field_id']."' style='width:".$width.";' value='".form_prep($field_data)."' maxlength='".$row['m_field_maxl']."' class='input' />";

					$temp = str_replace('{lang:profile_field}', $required.$row['m_field_label'], $temp);
					$temp = str_replace('{lang:profile_field_description}', $row['m_field_description'], $temp);
					$temp = str_replace('{form:custom_profile_field}', $input, $temp);
				}
				elseif ($row['m_field_type'] == 'select')
				{
					/** ----------------------------------------
					/**  Render pull-down menues
					/** ----------------------------------------*/

					$menu = "<select name='m_field_id_".$row['m_field_id']."' id='m_field_id_".$row['m_field_id']."' class='select'>\n";

					foreach (explode("\n", trim($row['m_field_list_items'])) as $v)
					{
						$v = trim($v);

						$selected = ($field_data == $v) ? " selected='selected'" : '';

						$menu .= "<option value='{$v}'{$selected}>".$v."</option>\n";
					}

					$menu .= "</select>\n";

					$temp = str_replace('{lang:profile_field}', $required.$row['m_field_label'], $temp);
					$temp = str_replace('{lang:profile_field_description}', $row['m_field_description'], $temp);
					$temp = str_replace('{form:custom_profile_field}', $menu, $temp);
				}

				$r .= $temp;
			}
		}

		/** ----------------------------------------
		/**  Build the output data
		/** ----------------------------------------*/
		$query = $this->EE->db->query("SELECT bday_y, bday_m, bday_d, url, location, occupation, interests, aol_im, icq, yahoo_im, msn_im, bio FROM exp_members WHERE member_id = '".$this->EE->session->userdata('member_id')."'");

		return  $this->_var_swap($this->_load_element('edit_profile_form'),
								array(
										'path:update_profile'	=> $this->_member_path('update_profile'),
										'form:birthday_year'	=> $this->_birthday_year($query->row('bday_y') ),
										'form:birthday_month'	=> $this->_birthday_month($query->row('bday_m') ),
										'form:birthday_day'		=> $this->_birthday_day($query->row('bday_d') ),
										'url'					=> ($query->row('url')  == '') ? 'http://' : $query->row('url') ,
										'location'				=> form_prep($query->row('location') ),
										'occupation'			=> form_prep($query->row('occupation') ),
										'interests'				=> form_prep($query->row('interests') ),
										'aol_im'				=> form_prep($query->row('aol_im') ),
										'icq'					=> form_prep($query->row('icq') ),
										'icq_im'				=> form_prep($query->row('icq') ),
										'yahoo_im'				=> form_prep($query->row('yahoo_im') ),
										'msn_im'				=> form_prep($query->row('msn_im') ),
										'bio'					=> form_prep($query->row('bio') ),
										'custom_profile_fields'	=> $r
									)
								);
	}




	/** ----------------------------------------
	/**  Profile Update
	/** ----------------------------------------*/
	function update_profile()
	{
		$this->EE->load->model('member_model');
		
		/** -------------------------------------
		/**  Safety....
		/** -------------------------------------*/
		if (count($_POST) == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('invalid_action')));
		}

		// Are any required custom fields empty?
		$this->EE->db->select('m_field_id, m_field_label');
		$this->EE->db->where('m_field_required = "y"');
		$query = $this->EE->db->get('member_fields');

		 $errors = array();

		 if ($query->num_rows() > 0)
		 {
			foreach ($query->result_array() as $row)
			{
				if (isset($_POST['m_field_id_'.$row['m_field_id']]) AND $_POST['m_field_id_'.$row['m_field_id']] == '')
				{
					$errors[] = $this->EE->lang->line('mbr_custom_field_empty').'&nbsp;'.$row['m_field_label'];
				}
			}
		 }

		/** ----------------------------------------
		/**  Blacklist/Whitelist Check
		/** ----------------------------------------*/

		if ($this->EE->blacklist->blacklisted == 'y' && $this->EE->blacklist->whitelisted == 'n')
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		/** -------------------------------------
		/**  Show errors
		/** -------------------------------------*/
		 if (count($errors) > 0)
		 {
			return $this->EE->output->show_user_error('submission', $errors);
		 }

		/** -------------------------------------
		/**  Build query
		/** -------------------------------------*/

		if (isset($_POST['url']) AND $_POST['url'] == 'http://')
		{
			$_POST['url'] = '';
		}

		$fields = array(	'bday_y',
							'bday_m',
							'bday_d',
							'url',
							'location',
							'occupation',
							'interests',
							'aol_im',
							'icq',
							'yahoo_im',
							'msn_im',
							'bio'
						);

		$data = array();

		foreach ($fields as $val)
		{
			$data[$val] = (isset($_POST[$val])) ? $this->EE->security->xss_clean($_POST[$val]) : '';
			unset($_POST[$val]);
		}

		if (is_numeric($data['bday_d']) AND is_numeric($data['bday_m']))
		{
			$year = ($data['bday_y'] != '') ? $data['bday_y'] : date('Y');
			$mdays = $this->EE->localize->fetch_days_in_month($data['bday_m'], $year);

			if ($data['bday_d'] > $mdays)
			{
				$data['bday_d'] = $mdays;
			}
		}

		unset($_POST['HTTP_REFERER']);

		if (count($data) > 0)
		{
			$this->EE->member_model->update_member($this->EE->session->userdata('member_id'), $data);
		}

		/** -------------------------------------
		/**  Update the custom fields
		/** -------------------------------------*/

		$m_data = array();

		if (count($_POST) > 0)
		{
			foreach ($_POST as $key => $val)
			{
				if (strncmp($key, 'm_field_id_', 11) == 0)
				{
					$m_data[$key] = $this->EE->security->xss_clean($val);
				}
			}

			if (count($m_data) > 0)
			{
				$this->EE->member_model->update_member_data($this->EE->session->userdata('member_id'), $m_data);
			}
		}

		/** -------------------------------------
		/**  Update comments
		/** -------------------------------------*/

		if ($data['location'] != "" OR $data['url'] != "")
		{
			if ($this->EE->db->table_exists('comments'))
			{
				$d = array(
						'location'	=> $data['location'],
						'url'		=> $data['url']
					);
				
				$this->EE->db->where('author_id', $this->EE->session->userdata('member_id'));
				$this->EE->db->update('comments', $d);
			}
	  	}

		/** -------------------------------------
		/**  Success message
		/** -------------------------------------*/

		return $this->_var_swap($this->_load_element('success'),
								array(
										'lang:heading'	=>	$this->EE->lang->line('profile_updated'),
										'lang:message'	=>	$this->EE->lang->line('mbr_profile_has_been_updated')
									 )
								);
	}



	/** ----------------------------------------
	/**  Forum Preferences
	/** ----------------------------------------*/
	function edit_preferences()
	{
		$query = $this->EE->db->query("SELECT display_avatars, display_signatures, smart_notifications, accept_messages, parse_smileys FROM exp_members WHERE member_id = '".$this->EE->session->userdata('member_id')."'");
	 
	 	$element = $this->_load_element('edit_preferences');
	 
	 	// -------------------------------------------
		// 'member_edit_preferences' hook.
		//  - Allows adding of preferences to user side preferences form
		//
			if ($this->EE->extensions->active_hook('member_edit_preferences') === TRUE)
			{
				$element = $this->EE->extensions->call('member_edit_preferences', $element);
			}
		//
		// -------------------------------------------
	 
	 
		return $this->_var_swap($element,
								array(
										'path:update_edit_preferences'	=>	$this->_member_path('update_preferences'),
										'state:display_avatars'		=>	($query->row('display_avatars')  == 'y') ? " checked='checked'" : '',
										'state:accept_messages'		=>	($query->row('accept_messages')  == 'y') ? " checked='checked'" : '',
										'state:display_signatures'	=>	($query->row('display_signatures')  == 'y')  ? " checked='checked'" : '',
										'state:parse_smileys'		=>	($query->row('parse_smileys')  == 'y')  ? " checked='checked'" : ''
									 )
								);
	}





	/** ----------------------------------------
	/**  Update  Preferences
	/** ----------------------------------------*/
	function update_preferences()
	{
		/** -------------------------------------
		/**  Assign the query data
		/** -------------------------------------*/

		$data = array(
						'accept_messages'		=> (isset($_POST['accept_messages'])) ? 'y' : 'n',
						'display_avatars'		=> (isset($_POST['display_avatars'])) ? 'y' : 'n',
						'display_signatures'	=> (isset($_POST['display_signatures']))  ? 'y' : 'n',
						'parse_smileys'			=> (isset($_POST['parse_smileys']))  ? 'y' : 'n'
					  );

		$this->EE->db->query($this->EE->db->update_string('exp_members', $data, "member_id = '".$this->EE->session->userdata('member_id')."'"));

		// -------------------------------------------
		// 'member_update_preferences' hook.
		//  - Allows updating of added preferences via user side preferences form
		//
			$edata = $this->EE->extensions->call('member_update_preferences', $data);
			if ($this->EE->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		/** -------------------------------------
		/**  Success message
		/** -------------------------------------*/

		return $this->_var_swap($this->_load_element('success'),
								array(
										'lang:heading'	=>	$this->EE->lang->line('mbr_preferences_updated'),
										'lang:message'	=>	$this->EE->lang->line('mbr_prefereces_have_been_updated')
									 )
							);
	}



	/** ----------------------------------------
	/**  Email Settings
	/** ----------------------------------------*/
	function edit_email()
	{
		$query = $this->EE->db->query("SELECT email, accept_admin_email, accept_user_email, notify_by_default, notify_of_pm, smart_notifications FROM exp_members WHERE member_id = '".$this->EE->session->userdata('member_id')."'");
	 
		return $this->_var_swap($this->_load_element('email_prefs_form'),
								array(
										'path:update_email_settings'	=>	$this->_member_path('update_email'),
										'email'							=>	$query->row('email') ,
										'state:accept_admin_email'		=>	($query->row('accept_admin_email')  == 'y') ? " checked='checked'" : '',
										'state:accept_user_email'		=>	($query->row('accept_user_email')  == 'y')  ? " checked='checked'" : '',
										'state:notify_by_default'		=>	($query->row('notify_by_default')  == 'y')  ? " checked='checked'" : '',
										'state:notify_of_pm'			=>	($query->row('notify_of_pm')  == 'y')  ? " checked='checked'" : '',
										'state:smart_notifications'		=>	($query->row('smart_notifications')  == 'y')  ? " checked='checked'" : ''
									 )
								);
	}





	/** ----------------------------------------
	/**  Email Update
	/** ----------------------------------------*/
	function update_email()
	{
		// Safety.

		if ( ! isset($_POST['email']))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('invalid_action')));
		}

		/** ----------------------------------------
		/**  Blacklist/Whitelist Check
		/** ----------------------------------------*/

		if ($this->EE->blacklist->blacklisted == 'y' && $this->EE->blacklist->whitelisted == 'n')
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}

		/** -------------------------------------
		/**  Validate submitted data
		/** -------------------------------------*/
		if ( ! class_exists('EE_Validate'))
		{
			require APPPATH.'libraries/Validate'.EXT;
		}


		$query = $this->EE->db->query("SELECT email, password FROM exp_members WHERE member_id = '".$this->EE->session->userdata('member_id')."'");

		$VAL = new EE_Validate(
								array(
										'member_id'		=> $this->EE->session->userdata('member_id'),
										'val_type'		=> 'update', // new or update
										'fetch_lang' 	=> TRUE,
										'require_cpw' 	=> FALSE,
										'enable_log'	=> FALSE,
										'email'			=> $_POST['email'],
										'cur_email'		=> $query->row('email')
									 )
							);

		$VAL->validate_email();

		if ($_POST['email'] != $query->row('email') )
		{
			if ($this->EE->session->userdata['group_id'] != 1)
			{
				if ($_POST['password'] == '')
				{
					$VAL->errors[] = $this->EE->lang->line('missing_current_password');
				}
				elseif ($this->EE->functions->hash(stripslashes($_POST['password'])) != $query->row('password') )
				{
					$VAL->errors[] = $this->EE->lang->line('invalid_password');
				}
			}
		}

		if (count($VAL->errors) > 0)
		{
			return $this->EE->output->show_user_error('submission', $VAL->errors);
		}

		/** -------------------------------------
		/**  Assign the query data
		/** -------------------------------------*/

		$data = array(
						'email'					=>  $_POST['email'],
						'accept_admin_email'	=> (isset($_POST['accept_admin_email'])) ? 'y' : 'n',
						'accept_user_email'		=> (isset($_POST['accept_user_email']))  ? 'y' : 'n',
						'notify_by_default'		=> (isset($_POST['notify_by_default']))  ? 'y' : 'n',
						'notify_of_pm'			=> (isset($_POST['notify_of_pm']))  ? 'y' : 'n',
						'smart_notifications'	=> (isset($_POST['smart_notifications']))  ? 'y' : 'n'
					  );

		$this->EE->db->query($this->EE->db->update_string('exp_members', $data, "member_id = '".$this->EE->session->userdata('member_id')."'"));

		/** -------------------------------------
		/**  Update comments and log email change
		/** -------------------------------------*/

		if ($query->row('email')  != $_POST['email'])
		{
			$this->EE->db->query($this->EE->db->update_string('exp_comments', array('email' => $_POST['email']), "author_id = '".$this->EE->session->userdata('member_id')."'"));
		}

		/** -------------------------------------
		/**  Success message
		/** -------------------------------------*/

		return $this->_var_swap($this->_load_element('success'),
								array(
										'lang:heading'	=>	$this->EE->lang->line('mbr_email_updated'),
										'lang:message'	=>	$this->EE->lang->line('mbr_email_has_been_updated')
									 )
							);
	}




	/** ----------------------------------------
	/**  Username/Password Preferences
	/** ----------------------------------------*/
	function edit_userpass()
	{
		$query = $this->EE->db->query("SELECT username, screen_name FROM exp_members WHERE member_id = '".$this->EE->session->userdata('member_id')."'");

		return $this->_var_swap($this->_load_element('username_password_form'),
								array(
										'row:username_form'				=>	($this->EE->session->userdata['group_id'] == 1 OR $this->EE->config->item('allow_username_change') == 'y') ? $this->_load_element('username_row') : $this->_load_element('username_change_disallowed'),
										'path:update_username_password'	=>	$this->_member_path('update_userpass'),
										'username'						=>	$query->row('username') ,
										'screen_name'					=>	$this->_convert_special_chars($query->row('screen_name') )
									 )
								);
	}





	/** ----------------------------------------
	/**  Username/Password Update
	/** ----------------------------------------*/
	function update_userpass()
	{
	  	// Safety.  Prevents accessing this function unless
	  	// the requrest came from the form submission

		if ( ! isset($_POST['current_password']))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('invalid_action')));
		}

		$query = $this->EE->db->query("SELECT username, screen_name FROM exp_members WHERE member_id = '".$this->EE->db->escape_str($this->EE->session->userdata('member_id'))."'");

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		if ($this->EE->config->item('allow_username_change') != 'y')
		{
			$_POST['username'] = $query->row('username');
		}

		// If the screen name field is empty, we'll assign is
		// from the username field.

		if ($_POST['screen_name'] == '')
			$_POST['screen_name'] = $_POST['username'];

		if ( ! isset($_POST['username']))
			$_POST['username'] = '';

		/** -------------------------------------
		/**  Validate submitted data
		/** -------------------------------------*/
		if ( ! class_exists('EE_Validate'))
		{
			require APPPATH.'libraries/Validate'.EXT;
		}

		$VAL = new EE_Validate(
								array(
										'member_id'			=> $this->EE->session->userdata('member_id'),
										'val_type'			=> 'update', // new or update
										'fetch_lang' 		=> TRUE,
										'require_cpw' 		=> TRUE,
									 	'enable_log'		=> FALSE,
										'username'			=> $_POST['username'],
										'cur_username'		=> $query->row('username') ,
										'screen_name'		=> $_POST['screen_name'],
										'cur_screen_name'	=> $query->row('screen_name') ,
										'password'			=> $_POST['password'],
									 	'password_confirm'	=> $_POST['password_confirm'],
									 	'cur_password'		=> $_POST['current_password']
									 )
							);

		$VAL->validate_screen_name();

		if ($this->EE->config->item('allow_username_change') == 'y')
		{
			$VAL->validate_username();
		}

		if ($_POST['password'] != '')
		{
			$VAL->validate_password();
		}

		/** -------------------------------------
		/**  Display error is there are any
		/** -------------------------------------*/

		if (count($VAL->errors) > 0)
		{
			return $this->EE->output->show_user_error('submission', $VAL->errors);
		}

		/** -------------------------------------
		/**  Update "last post" forum info if needed
		/** -------------------------------------*/

		if ($query->row('screen_name')  != $_POST['screen_name'] AND $this->EE->config->item('forum_is_installed') == "y" )
		{
			$this->EE->db->query("UPDATE exp_forums SET forum_last_post_author = '".$this->EE->db->escape_str($_POST['screen_name'])."' WHERE forum_last_post_author_id = '".$this->EE->session->userdata('member_id')."'");
			$this->EE->db->query("UPDATE exp_forum_moderators SET mod_member_name = '".$this->EE->db->escape_str($_POST['screen_name'])."' WHERE mod_member_id = '".$this->EE->session->userdata('member_id')."'");
		}

		/** -------------------------------------
		/**  Assign the query data
		/** -------------------------------------*/
		$data['screen_name'] = $_POST['screen_name'];

		if ($this->EE->config->item('allow_username_change') == 'y')
		{
			$data['username'] = $_POST['username'];
		}

		// Was a password submitted?

		$pw_change = '';

		if ($_POST['password'] != '')
		{
			$data['password'] = $this->EE->functions->hash(stripslashes($_POST['password']));

			$pw_change = $this->_var_swap($this->_load_element('password_change_warning'),
											array('lang:password_change_warning' => $this->EE->lang->line('password_change_warning'))
										);
		}

		$this->EE->db->query($this->EE->db->update_string('exp_members', $data, "member_id = '".$this->EE->session->userdata('member_id')."'"));

		/** -------------------------------------
		/**  Update comments if screen name has changed
		/** -------------------------------------*/
		if ($query->row('screen_name')  != $_POST['screen_name'])
		{
			$this->EE->db->query($this->EE->db->update_string('exp_comments', array('name' => $_POST['screen_name']), "author_id = '".$this->EE->session->userdata('member_id')."'"));

			$this->EE->session->userdata['screen_name'] = stripslashes($_POST['screen_name']);
		}

		/** -------------------------------------
		/**  Success message
		/** -------------------------------------*/

		return $this->_var_swap($this->_load_element('success'),
								array(
										'lang:heading'	=>	$this->EE->lang->line('username_and_password'),
										'lang:message'	=>	$this->EE->lang->line('mbr_settings_updated').$pw_change
									 )
							);
	}




	/** ----------------------------------------
	/**  Localization Edit Form
	/** ----------------------------------------*/

	function edit_localization()
	{
		// Are localizations enabled?

		if ($this->EE->config->item('allow_member_localization') == 'n' AND $this->EE->session->userdata('group_id') != 1)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('localization_disallowed')));
		}

		// Time format selection menu

		$tf = "<select name='time_format' class='select'>\n";
		$selected = ($this->EE->session->userdata['time_format'] == 'us') ? " selected='selected'" : '';
		$tf .= "<option value='us'{$selected}>".$this->EE->lang->line('united_states')."</option>\n";
		$selected = ($this->EE->session->userdata['time_format'] == 'eu') ? " selected='selected'" : '';
		$tf .= "<option value='eu'{$selected}>".$this->EE->lang->line('european')."</option>\n";
		$tf .= "</select>\n";


		$query = $this->EE->db->query("SELECT language, timezone,daylight_savings FROM exp_members WHERE member_id = '".$this->EE->session->userdata('member_id')."'");

		return $this->_var_swap($this->_load_element('localization_form'),
								array(
										'path:update_localization'		=>	$this->_member_path('update_localization'),
										'form:localization'				=>	$this->EE->localize->timezone_menu(($query->row('timezone')  == '') ? 'UTC' : $query->row('timezone') ),
										'state:daylight_savings'		=>	($query->row('daylight_savings')  == 'y') ? " checked='checked'" : '',
										'form:time_format'				=>	$tf,
										'form:language'					=>	$this->EE->functions->language_pack_names(($query->row('language')  == '') ? 'english' : $query->row('language') )
									 )
								);
	}





	/** ----------------------------------------
	/**  Update Localization Prefs
	/** ----------------------------------------*/

	function update_localization()
	{
		// Are localizations enabled?

		if ($this->EE->config->item('allow_member_localization') == 'n' AND $this->EE->session->userdata('group_id') != 1)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('localization_disallowed')));
		}

		if ( ! isset($_POST['server_timezone']))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('invalid_action')));
		}

		$this->EE->load->library('security');
		
		$data['language']	= $this->EE->security->sanitize_filename($_POST['deft_lang']);
		$data['timezone']	= $_POST['server_timezone'];
		$data['time_format'] = $_POST['time_format'];

		if ( ! is_dir(APPPATH.'language/'.$data['language']))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('invalid_action')));
		}

		$data['daylight_savings'] = ($this->EE->input->post('daylight_savings') == 'y') ? 'y' : 'n';

		$this->EE->db->query($this->EE->db->update_string('exp_members', $data, "member_id = '".$this->EE->session->userdata('member_id')."'"));

		/** -------------------------------------
		/**  Success message
		/** -------------------------------------*/

		return $this->_var_swap($this->_load_element('success'),
								array(
										'lang:heading'	=>	$this->EE->lang->line('localization_settings'),
										'lang:message'	=>	$this->EE->lang->line('mbr_localization_settings_updated')
									 )
								);
	}



	/** -------------------------------------
	/**  Edit Ignore List
	/** -------------------------------------*/

	function edit_ignore_list($msg = '')
	{
		$query = $this->EE->db->query("SELECT ignore_list FROM exp_members WHERE member_id = '".$this->EE->session->userdata['member_id']."'");

		if ($query->num_rows() == 0)
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
		else
		{
			$ignored = ($query->row('ignore_list')  == '') ? array() : explode('|', $query->row('ignore_list') );
		}

		$query = $this->EE->db->query("SELECT screen_name, member_id FROM exp_members WHERE member_id IN ('".implode("', '", $ignored)."') ORDER BY screen_name");
		$out = '';

		if ($query->num_rows() == 0)
		{
			// not ignoring anyone right now
		}
		else
		{
			$template = $this->_load_element('edit_ignore_list_rows');
			$i = 0;

			foreach ($query->result_array() as $row)
			{
				$temp = $this->_var_swap($template,
										 array(
											'path:profile_link'		=> $this->_member_path($row['member_id']),
											'name'					=> $row['screen_name'],
											'member_id'				=> $row['member_id'],
											'class'					=> ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo'
											)
										);
				$out .= $temp;
			}
		}

		$form_details = array('hidden_fields'	=> array('name' => '', 'daction' => '', 'toggle[]' => ''),
							  'action'			=> $this->_member_path('update_ignore_list'),
						 	  'id'				=> 'target'
						 	  );

		$images_folder = $this->EE->config->slash_item('theme_folder_url').'cp_global_images/';

		$finalized = $this->_var_swap($this->_load_element('edit_ignore_list_form'),
								array(
										'form:form_declaration'			=> $this->EE->functions->form_declaration($form_details),
										'include:edit_ignore_list_rows'	=> $out,
										'include:member_search'			=> $this->member_search_js().
																			'<a href="#" title="{lang:member_search}" onclick="member_search(); return false;">'.
																			'<img src="'.$images_folder.'search_glass.gif" style="border: 0px" width="12" height="12" alt="'.$this->EE->lang->line('search_glass').'" />'.
																			'</a>',
										'include:toggle_js'				=> $this->toggle_js(),
										'form:add_button'				=> $this->list_js().
						 													"<button type='submit' id='add' name='add' value='add' ".
						 													"class='buttons' title='{lang:add_member}' ".
						 													"onclick='dynamic_action(\"add\");list_addition();return false;'>".
						 													"{lang:add_member}</button>".NBS.NBS,
										'form:delete_button'			=> "<button type='submit' id='delete' name='delete' value='delete' ".
					 														"class='buttons' title='{lang:delete_selected_members}' ".
					 														"onclick='dynamic_action(\"delete\");'>".
					 														"{lang:delete_member}</button> ",
										'path:update_ignore_list'		=> $this->_member_path('update_ignore_list'),
										'lang:message'					=> $this->EE->lang->line('ignore_list_updated')
									)
								);
		if ($msg == '')
		{
			$finalized = $this->_deny_if('success_message', $finalized);
		}
		else
		{
			$finalized = $this->_allow_if('success_message', $finalized);
		}

		return $finalized;
	}



	/** -------------------------------------
	/**  Update Ignore List
	/** -------------------------------------*/

	function update_ignore_list()
	{
		if ( ! ($action = $this->EE->input->post('daction')))
		{
			return $this->edit_ignore_list();
		}

		$ignored = array_flip($this->EE->session->userdata['ignore_list']);

		if ($action == 'delete')
		{
			if ( ! ($member_ids = $this->EE->input->post('toggle')))
			{
				return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
			}

			foreach ($member_ids as $member_id)
			{
				unset($ignored[$member_id]);
			}
		}
		else
		{
			if ( ! ($screen_name = $this->EE->input->post('name')))
			{
				return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
			}

			$query = $this->EE->db->query("SELECT member_id FROM exp_members WHERE screen_name = '".$this->EE->db->escape_str($screen_name)."'");

			if ($query->num_rows() == 0)
			{
				return $this->_trigger_error('invalid_screen_name', 'invalid_screen_name_message');
			}

			if ($query->row('member_id')  == $this->EE->session->userdata['member_id'])
			{
				return $this->_trigger_error('invalid_screen_name', 'can_not_ignore_self');
			}

			if ( ! isset($ignored[$query->row('member_id') ]))
			{
				$ignored[$query->row('member_id') ] = $query->row('member_id') ;
			}
		}

		$ignored_list = implode('|', array_keys($ignored));

		$this->EE->db->query($this->EE->db->update_string('exp_members', array('ignore_list' => $ignored_list), "member_id = '".$this->EE->session->userdata['member_id']."'"));

		return $this->edit_ignore_list('ignore_list_updated');
	}



	/** -------------------------------------
	/**  Member Mini Search (Ignore List)
	/** -------------------------------------*/

	function member_mini_search($msg = '')
	{
		$form_details = array('hidden_fields' => array(),
							  'action'	=> $this->_member_path('do_member_mini_search'),
						 	  );

		$group_opts = '';

		$query = $this->EE->db->query("SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."' ORDER BY group_title");

		foreach ($query->result_array() as $row)
		{
			$group_opts .= "<option value='{$row['group_id']}'>{$row['group_title']}</option>";
		}

		$template = $this->_var_swap($this->_load_element('search_members'),
										array(
												'form:form_declaration:do_member_search'	=> $this->EE->functions->form_declaration($form_details),
												'include:message'							=> $msg,
												'include:member_group_options'				=> $group_opts
											)
									);

		if ($msg == '')
		{
			$template = $this->_deny_if('message', $template);
		}
		else
		{
			$template = $this->_allow_if('message', $template);
		}

		return $template;
	}



	/** -------------------------------------
	/**  Do Member Mini Search (Ignore List)
	/** -------------------------------------*/

	function do_member_mini_search()
	{
			$redirect_url = $this->_member_path('member_mini_search');

		/** -------------------------------------
		/**  Parse the $_POST data
		/** -------------------------------------*/
		if ($_POST['screen_name'] 	== '' &&
			$_POST['email'] 		== ''
			)
			{
				$this->EE->functions->redirect($redirect_url);
				exit;
			}

		$search_query = array();

		foreach ($_POST as $key => $val)
		{
			if ($key == 'XID')
			{
				continue;
			}
			if ($key == 'group_id')
			{
				if ($val != 'any')
				{
					$search_query[] = " group_id ='".$this->EE->db->escape_str($_POST['group_id'])."'";
				}
			}
			else
			{
				if ($val != '')
				{
					$search_query[] = $key." LIKE '%".$this->EE->db->escape_like_str($val)."%'";
				}
			}
		}

		if (count($search_query) < 1)
		{
			$this->EE->functions->redirect($redirect_url);
			exit;
		}

  		$Q = implode(" AND ", $search_query);

		$sql = "SELECT DISTINCT exp_members.member_id, exp_members.screen_name FROM exp_members, exp_member_groups
				WHERE exp_members.group_id = exp_member_groups.group_id AND exp_member_groups.site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'
				AND ".$Q;

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return $this->member_mini_search($this->EE->lang->line('no_search_results'));
		}

		$r = '';

		foreach($query->result_array() as $row)
		{
			$item = '<a href="#" onclick="opener.dynamic_action(\'add\');opener.list_addition(\''.$row['screen_name'].'\', \'name\');return false;">'.$row['screen_name'].'</a>';
			$r .= $this->_var_swap($this->_load_element('member_results_row'),
									array(
											'item' => $item
										)
									);
		}

		return $this->_var_swap($this->_load_element('member_results'),
								array(
										'include:search_results'	=> $r,
										'path:new_search_url'		=> $redirect_url,
										'which_field'				=> 'name'		// not used in this instance; probably will log a minor js error
									)
								);
	}



	/** -------------------------------------
	/**  Toggle JS - used in Ignore List mgmt.
	/** -------------------------------------*/

	function toggle_js()
	{
	$str = <<<EOT

	<script type="text/javascript">
	//<![CDATA[

	function toggle(thebutton)
	{
		if (thebutton.checked)
		{
			val = true;
		}
		else
		{
			val = false;
		}

		if (document.target)
		{
			var theForm = document.target;
		}
		else if (document.getElementById('target'))
		{
			var theForm = document.getElementById('target');
		}
		else
		{
			return false;
		}

		var len = theForm.elements.length;

		for (var i = 0; i < len; i++)
		{
			var button = theForm.elements[i];

			var name_array = button.name.split("[");

			if (name_array[0] == "toggle")
			{
				button.checked = val;
			}
		}

		theForm.toggleflag.checked = val;
	}
	//]]>
	</script>

EOT;

		return trim($str);
	}



	/** -------------------------------------
	/**  Add member to Ignore List js
	/** -------------------------------------*/

	function list_js()
	{
		return <<<EWOK

	<script type="text/javascript">
	//<![CDATA[

	function list_addition(member, el)
	{
		var member_text = '{lang:member_usernames}';

		var Name = (member == null) ? prompt(member_text, '') : member;
		var el = (el == null) ? 'name' : el;

		 if ( ! Name || Name == null)
		 {
		 	return;
		 }

		var frm = document.getElementById('target');
		var x;

		for (i = 0; i < frm.length; i++)
		{
			if (frm.elements[i].name == el)
			{
				frm.elements[i].value = Name;
			}
		}

		 document.getElementById('target').submit();
	}

	function dynamic_action(which)
	{
		if (document.getElementById('target').daction)
		{
			document.getElementById('target').daction.value = which;
		}
	}
	//]]>
	</script>
EWOK;

	}



	/** -------------------------------------
	/**  Member Search JS for Ignore List
	/** -------------------------------------*/

	function member_search_js()
	{
			$url = $this->_member_path('member_mini_search');

		$str = <<<UNGA

<script type="text/javascript">
//<![CDATA[
function member_search()
{
	var popWin = window.open('{$url}', '_blank', 'width=450,height=480,scrollbars=yes,status=yes,screenx=0,screeny=0,resizable=yes');
}

//]]>
</script>

UNGA;

		return $str;
	}


	/** ----------------------------------------
	/**  Notepad Edit Form
	/** ----------------------------------------*/

	function edit_notepad()
	{
		$query = $this->EE->db->query("SELECT notepad, notepad_size FROM exp_members WHERE member_id = '".$this->EE->session->userdata('member_id')."'");
									 
		return $this->_var_swap($this->_load_element('notepad_form'),
								array(
										'path:update_notepad'	=>	$this->_member_path('update_notepad'),
										'notepad_data'			=>	$query->row('notepad') ,
										'notepad_size'			=>	$query->row('notepad_size')
									 )
								);
	}



	/** ----------------------------------------
	/**  Update Notepad
	/** ----------------------------------------*/

	function update_notepad()
	{
		if ( ! isset($_POST['notepad']))
		{
			return $this->EE->functions->redirect($this->_member_path('edit_notepad'));
		}

		$notepad_size = ( ! is_numeric($_POST['notepad_size'])) ? 18 : $_POST['notepad_size'];

		$this->EE->db->query("UPDATE exp_members SET notepad = '".$this->EE->db->escape_str($this->EE->security->xss_clean($_POST['notepad']))."', notepad_size = '".$notepad_size."' WHERE member_id ='".$this->EE->session->userdata('member_id')."'");

		/** -------------------------------------
		/**  Success message
		/** -------------------------------------*/

		return $this->_var_swap($this->_load_element('success'),
								array(
										'lang:heading'	=>	$this->EE->lang->line('mbr_notepad'),
										'lang:message'	=>	$this->EE->lang->line('mbr_notepad_updated')
									 )
								);
	}




	/** ----------------------------------
	/**  Username/password update
	/** ----------------------------------*/
	function unpw_update()
	{
		if ($this->cur_id == '' OR strpos($this->cur_id, '_') === FALSE)
		{
			return;
		}

		$x = explode('_', $this->cur_id);

		if (count($x) != 3)
		{
			return;
		}

		foreach ($x as $val)
		{
			if ( ! is_numeric($val))
			{
				return;
			}
		}

		$mid	= $x['0'];
		$ulen	= $x['1'];
		$plen	= $x['2'];

		$tmpl = $this->_load_element('update_un_pw_form');

		$uml = $this->EE->config->item('un_min_len');
		$pml = $this->EE->config->item('pw_min_len');


		if ($ulen < $uml)
		{
			$tmpl = $this->_allow_if('invalid_username', $tmpl);
		}

		if ($plen < $pml)
		{
			$tmpl = $this->_allow_if('invalid_password', $tmpl);
		}


		$tmpl = $this->_deny_if('invalid_username', $tmpl);
		$tmpl = $this->_deny_if('invalid_password', $tmpl);


		$data['hidden_fields']['ACT']	= $this->EE->functions->fetch_action_id('Member', 'update_un_pw');
		$data['hidden_fields']['FROM']	= ($this->in_forum == TRUE) ? 'forum' : '';

		if ($this->EE->uri->segment(5))
		{
			$data['action']	= $this->EE->functions->fetch_current_uri();
		}

		$this->_set_page_title($this->EE->lang->line('member_login'));

		return $this->_var_swap($tmpl,
								array(
										'form_declaration' 	=> $this->EE->functions->form_declaration($data),
										'lang:username_length'	=> str_replace('%x', $this->EE->config->item('un_min_len'), $this->EE->lang->line('un_len')),
										'lang:password_length'	=> sprintf($this->EE->lang->line('pw_len'),	
																		$this->EE->config->item('pw_min_len'))
									)
								);
	}



	/** ----------------------------------
	/**  Update the username/password
	/** ----------------------------------*/

	function update_un_pw()
	{
		$missing = FALSE;

		if ( ! isset($_POST['new_username']) AND  ! isset($_POST['new_password']))
		{
			$missing = TRUE;
		}

		if ((isset($_POST['new_username']) AND $_POST['new_username'] == '') OR (isset($_POST['new_password']) AND $_POST['new_password'] == ''))
		{
			$missing = TRUE;
		}

		if ($this->EE->input->post('username') == '' OR $this->EE->input->get_post('password') == '')
		{
			$missing = TRUE;
		}

		if ($missing == TRUE)
		{
			return $this->EE->output->show_user_error('submission', $this->EE->lang->line('all_fields_required'));
		}

		/** ----------------------------------------
		/**  Check password lockout status
		/** ----------------------------------------*/

		if ($this->EE->session->check_password_lockout($this->EE->input->post('username')) === TRUE)
		{
			$line = str_replace("%x", $this->EE->config->item('password_lockout_interval'), $this->EE->lang->line('password_lockout_in_effect'));
			return $this->EE->output->show_user_error('submission', $line);
		}

		/** ----------------------------------------
		/**  Fetch member data
		/** ----------------------------------------*/
		$sql = "SELECT member_id, group_id
				FROM	exp_members
				WHERE  username = '".$this->EE->db->escape_str($this->EE->input->post('username'))."'
				AND	password = '".$this->EE->functions->hash(stripslashes($this->EE->input->post('password')))."'";

		$query = $this->EE->db->query($sql);

		/** ----------------------------------------
		/**  Invalid Username or Password
		/** ----------------------------------------*/
		if ($query->num_rows() == 0)
		{
			$this->EE->session->save_password_lockout($this->EE->input->post('username'));
			return $this->EE->output->show_user_error('submission', $this->EE->lang->line('invalid_existing_un_pw'));
		}

		$member_id = $query->row('member_id') ;

		/** ----------------------------------------
		/**  Is the user banned?
		/** ----------------------------------------*/

		// Super Admins can't be banned

		if ($query->row('group_id')  != 1)
		{
			if ($this->EE->session->ban_check())
			{
				return $this->EE->output->fatal_error($this->EE->lang->line('not_authorized'));
			}
		}

		/** -------------------------------------
		/**  Instantiate validation class
		/** -------------------------------------*/
		if ( ! class_exists('EE_Validate'))
		{
			require APPPATH.'libraries/Validate'.EXT;
		}

		$new_un  = (isset($_POST['new_username'])) ? $_POST['new_username'] : '';
		$new_pw  = (isset($_POST['new_password'])) ? $_POST['new_password'] : '';
		$new_pwc = (isset($_POST['new_password_confirm'])) ? $_POST['new_password_confirm'] : '';

		$VAL = new EE_Validate(
								array(
										'val_type'			=> 'new',
										'fetch_lang' 		=> TRUE,
										'require_cpw' 		=> FALSE,
									 	'enable_log'		=> FALSE,
										'username'			=> $new_un,
										'password'			=> $new_pw,
									 	'password_confirm'	=> $new_pwc,
									 	'cur_password'		=> $_POST['password'],
									 )
							);

		$un_exists = (isset($_POST['new_username']) AND $_POST['new_username'] != '') ? TRUE : FALSE;
		$pw_exists = (isset($_POST['new_password']) AND $_POST['new_password'] != '') ? TRUE : FALSE;

		if ($un_exists)
			$VAL->validate_username();
		if ($pw_exists)
			$VAL->validate_password();

		/** -------------------------------------
		/**  Display error is there are any
		/** -------------------------------------*/

		if (count($VAL->errors) > 0)
		{		 
			return $this->EE->output->show_user_error('submission', $VAL->errors);
		}


		if ($un_exists)
		{
			$this->EE->db->query("UPDATE exp_members SET username = '".$this->EE->db->escape_str($_POST['new_username'])."' WHERE member_id = '{$member_id}'");
		}

		if ($pw_exists)
		{
			$this->EE->db->query("UPDATE exp_members SET password = '".$this->EE->functions->hash(stripslashes($_POST['new_password']))."' WHERE member_id = '{$member_id}'");
		}

		// Clear the tracker cookie since we're not sure where the redirect should go
		$this->EE->functions->set_cookie('tracker');

		$return = $this->EE->functions->form_backtrack();

		if ($this->EE->config->item('user_session_type') != 'c')
		{
			if ($this->EE->config->item('force_query_string') == 'y' && substr($return, 0, -3) == "php")
			{
				$return .= '?';
			}

			if ($this->EE->session->userdata['session_id'] != '')
			{
				$return .= "/S=".$this->EE->session->userdata['session_id']."/";
			}
		}

		if ($this->EE->uri->segment(5))
		{
			$link = $this->EE->functions->create_url($this->EE->uri->segment(5));
			$line = $this->EE->lang->line('return_to_forum');
		}
		else
		{
			$link = $this->_member_path('login');
			$line = $this->EE->lang->line('return_to_login');
		}

		// We're done.
		$data = array(	'title' 	=> $this->EE->lang->line('settings_update'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('unpw_updated'),
						'link'		=> array($link, $line)
						 );

		$this->EE->output->show_message($data);
	}


}
// END CLASS

/* End of file mod.member_settings.php */
/* Location: ./system/expressionengine/modules/member/mod.member_settings.php */