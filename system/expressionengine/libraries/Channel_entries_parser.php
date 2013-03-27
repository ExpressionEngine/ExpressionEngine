<?php

class EE_Channel_entries_parser {

	public function create($tagdata, $prefix = '')
	{
		return new EE_Channel_parser($tagdata, $prefix);
	}
}

class EE_Channel_parser {

	protected $_prefix;
	protected $_tagdata;

	public function __construct($tagdata, $prefix)
	{
		$this->_prefix = $prefix;
		$this->_tagdata = $tagdata;
	}

	public function tagdata()
	{
		return $this->_tagdata;
	}

	public function prefix()
	{
		return $this->_prefix;
	}

	public function pre_parser(Channel $channel)
	{
		return new EE_Channel_preparser($channel, $this);
	}

	public function row_parser(EE_Channel_preparser $preparsed, array $data)
	{
		return new EE_Channel_row_parser($preparsed, $data);
	}


	public function data_parser(EE_Channel_preparser $pre, $relationship_parser = NULL)
	{
		return new EE_Channel_data_parser($pre, $relationship_parser);
	}


	/*

	// short way
	$p = new Parser($tagdata, $prefix);
	$p->parse($channel, $entries, array( ... ));

	// long way
	$pre = $p->pre_parser($channel);
	$parser = $p->data_parser($pre);

	$parser->parse($entries, array(
		'disable' => array('relationships', 'categories'),
		'callbacks' => array(
			'pre_loop' => array($this, 'method');
		)
	));

	*/
	public function parse(Channel $channel, array $entries, array $config = array())
	{
		$pre = $this->pre_parser($channel);
		$parser = $this->data_parser($pre);

		return $parser->parse($entries, $config);
	}
}


class EE_Channel_data_parser {

	protected $_preparsed;
	protected $_relationship_parser;

	public function __construct(EE_Channel_preparser $pre, $relationship_parser = NULL)
	{
		$this->_preparsed = $pre;
		$this->_relationship_parser = $relationship_parser;
	}

	public function parse($data, $config = array())
	{
		// data options
		$entries	= $data['entries'];

		$categories		  = isset($data['categories']) ? $data['categories'] : array();
		$absolute_offset  = isset($data['absolute_offset']) ? $data['absolute_offset'] : 0;
		$absolute_results = isset($data['absolute_results']) ? $data['absolute_results'] : NULL;

		// config options
		$disabled	= isset($config['disable']) ? $config['disable'] : array();
		$callbacks	= isset($config['callbacks']) ? $config['callbacks'] : array();

		$pairs	 = $this->_preparsed->pairs;
		$singles = $this->_preparsed->singles;

		$prefix	 = $this->_preparsed->prefix();
		$channel = $this->_preparsed->channel();
		
		$relationship_parser = $this->_relationship_parser;
		$subscriber_totals = $this->_preparsed->subscriber_totals;

		$total_results = count($entries);
		$site_pages = config_item('site_pages');

		$result = ''; // final template

		// If custom fields are enabled, notify them of the data we're about to send
		if ( ! empty($channel->cfields))
		{
			$this->_send_custom_field_data_to_fieldtypes($entries);
		}

		$count = 0;

		foreach ($entries as $row)
		{
			$tagdata = $this->_preparsed->tagdata();

			$row['count']				= $count + 1;
			$row['page_uri']			= '';
			$row['page_url']			= '';
			$row['total_results']		= $total_results;
			$row['absolute_count']		= $absolute_offset + $row['count'];
			$row['absolute_results'] = ($absolute_results === NULL) ? $total_results : $absolute_results;
			$row['comment_subscriber_total'] = (isset($subscriber_totals[$row['entry_id']])) ? $subscriber_totals[$row['entry_id']] : 0;

			if ($site_pages !== FALSE && isset($site_pages[$row['site_id']]['uris'][$row['entry_id']]))
			{
				$row['page_uri'] = $site_pages[$row['site_id']]['uris'][$row['entry_id']];
				$row['page_url'] = get_instance()->create_page_url($site_pages[$row['site_id']]['url'], $site_pages[$row['site_id']]['uris'][$row['entry_id']]);
			}

			$row_parser = new EE_Channel_row_parser($this->_preparsed, $row);


			// -------------------------------------------
			// @todo channel_entries_tagdata hook
			// -------------------------------------------

			// -------------------------------------------
			// @todo channel_entries_row hook
			// -------------------------------------------


			// Reset custom date fields

			// Since custom date fields columns are integer types by default, if they
			// don't contain any data they return a zero.
			// This creates a problem if conditionals are used with those fields.
			// For example, if an admin has this in a template:  {if mydate == ''}
			// Since the field contains a zero it would never evaluate TRUE.
			// Therefore we'll reset any zero dates to nothing.

			if (isset($channel->dfields[$row['site_id']]) && count($channel->dfields[$row['site_id']]) > 0)
			{
				foreach ($channel->dfields[$row['site_id']] as $dkey => $dval)
				{
					// While we're at it, kill any formatting
					$row['field_ft_'.$dval] = 'none';
					if (isset($row['field_id_'.$dval]) AND $row['field_id_'.$dval] == 0)
					{
						$row['field_id_'.$dval] = '';
					}
				}
			}


			// conditionals!
			$cond = $this->_get_conditional_data($row, $prefix, $channel);


			foreach ($channel->mfields as $key => $value)
			{
				$cond[$key] = ( ! array_key_exists('m_field_id_'.$value[0], $row)) ? '' : $row['m_field_id_'.$value[0]];
			}

			//  Parse Variable Pairs
			foreach ($pairs as $key => $val)
			{
				// parse {categories} pair
				$tagdata = $row_parser->parse_categories($key, $tagdata, $categories);

				// parse custom field pairs (file, checkbox, multiselect)
				$tagdata = $row_parser->parse_custom_field_pair($key, $tagdata);

				// parse {date_heading} and {date_footer}
				$tagdata = $row_parser->parse_date_header_and_footer($key, $val, $tagdata);
			}
			// END VARIABLE PAIRS


			if ( ! in_array('relationships', $disabled) && isset($relationship_parser))
			{
				$tagdata = $relationship_parser->parse_relationships($row['entry_id'], $tagdata, $channel);
			}


			// We swap out the conditionals after pairs are parsed so they don't interfere
			// with the string replace
			$tagdata = get_instance()->functions->prep_conditionals($tagdata, $cond);


			//  Parse "single" variables
			foreach ($singles as $key => $val)
			{
				// Note:  This must happen first.
				// Parse simple conditionals: {body|more|summary}
				$tagdata = $row_parser->parse_simple_conditionals($key, $val, $tagdata);

				// parse {switch} variable
				$tagdata = $row_parser->parse_switch_variable($key, $tagdata, $count);

				// parse non-custom dates ({entry_date}, {comment_date}, etc)
				$tagdata = $row_parser->parse_date_variables($key, $val, $tagdata);

				// parse simple variables that have parameters or special processing,
				// such as any of the paths, url_or_email, url_or_email_as_author, etc
				$tagdata = $row_parser->parse_simple_variables($key, $val, $tagdata);

				//  parse custom date fields
				$tagdata = $row_parser->parse_custom_date_fields($key, $tagdata);

				// parse custom channel fields
				$tagdata = $row_parser->parse_custom_field($key, $val, $tagdata);

				// parse custom member fields
				$tagdata = $row_parser->parse_custom_member_field($key, $val, $tagdata);
			}
			// END SINGLE VARIABLES

			// do we need to replace any curly braces that we protected in custom fields?
			if (strpos($tagdata, unique_marker('channel_bracket_open')) !== FALSE)
			{
				$tagdata = str_replace(
					array(unique_marker('channel_bracket_open'), unique_marker('channel_bracket_close')),
					array('{', '}'),
					$tagdata
				);
			}

			// -------------------------------------------------------
			// Loop end callback. Do what you want.
			// Used by relationships to parse children and by the
			// channel module for the channel_entries_tagdata_end hook
			// -------------------------------------------------------

			if (isset($callbacks['tagdata_loop_end']))
			{
				$tagdata = call_user_func($callbacks['tagdata_loop_end'], $tagdata, $row);
			}

			$result .= $tagdata;
			$count++;
		}

		return $result;
	}

	// ------------------------------------------------------------------------

	/**
	 * Sends custom field data to fieldtypes before the entries loop runs.
	 * This is particularly helpful to fieldtypes that need to query the database
	 * based on what they're passed, like the File field. This allows them to run
	 * potentially a single query to gather needed data instead of a query for
	 * each row.
	 *
	 * @param string $entries_data 
	 * @return void
	 */
	protected function _send_custom_field_data_to_fieldtypes($entries_data)
	{
		$channel = $this->_preparsed->channel();

		// We'll stick custom field data into this array in the form of:
		// field_id => array('data1', 'data2', ...);
		$custom_field_data = array();
		
		// Loop through channel entry data
		foreach ($entries_data as $row)
		{
			// Get array of custom fields for the row's current site
			$custom_fields = (isset($channel->cfields[$row['site_id']])) ? $channel->cfields[$row['site_id']] : array();
			
			foreach ($custom_fields as $field_name => $field_id)
			{
				// If the field exists and isn't empty
				if (isset($row['field_id_'.$field_id]))
				{
					if ( ! empty($row['field_id_'.$field_id]))
					{
						// Add the data to our custom field data array
						$custom_field_data[$field_id][] = $row['field_id_'.$field_id];
					}
				}
			}
		}
		
		if ( ! empty($custom_field_data))
		{
			get_instance()->load->library('api');
			get_instance()->api->instantiate('channel_fields');
			
			// For each custom field, notify its fieldtype class of the data we collected
			foreach ($custom_field_data as $field_id => $data)
			{
				if (get_instance()->api_channel_fields->setup_handler($field_id))
				{
					if (get_instance()->api_channel_fields->check_method_exists('pre_loop'))
					{
						get_instance()->api_channel_fields->apply('pre_loop', array($data));
					}
				}
			}
		}
	}

	protected function _get_conditional_data($row, $prefix, $channel)
	{
		$cond = $row;
		$cond['logged_in']			= (get_instance()->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
		$cond['logged_out']			= (get_instance()->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';

		if ((($row['comment_expiration_date'] > 0 && get_instance()->localize->now > $row['comment_expiration_date']) && get_instance()->config->item('comment_moderation_override') !== 'y') OR $row['allow_comments'] == 'n' OR (isset($row['comment_system_enabled']) && $row['comment_system_enabled']  == 'n'))
		{
			$cond['allow_comments'] = 'FALSE';
		}
		else
		{
			$cond['allow_comments'] = 'TRUE';
		}

		foreach (array('avatar_filename', 'photo_filename', 'sig_img_filename') as $pv)
		{
			if ( ! isset($row[$pv]))
			{
				$row[$pv] = '';
			}
		}

		$cond['signature_image']		= ($row['sig_img_filename'] == '' OR get_instance()->config->item('enable_signatures') == 'n' OR get_instance()->session->userdata('display_signatures') == 'n') ? 'FALSE' : 'TRUE';
		$cond['avatar']					= ($row['avatar_filename'] == '' OR get_instance()->config->item('enable_avatars') == 'n' OR get_instance()->session->userdata('display_avatars') == 'n') ? 'FALSE' : 'TRUE';
		$cond['photo']					= ($row['photo_filename'] == '' OR get_instance()->config->item('enable_photos') == 'n' OR get_instance()->session->userdata('display_photos') == 'n') ? 'FALSE' : 'TRUE';
		$cond['forum_topic']			= (empty($row['forum_topic_id'])) ? 'FALSE' : 'TRUE';
		$cond['not_forum_topic']		= ( ! empty($row['forum_topic_id'])) ? 'FALSE' : 'TRUE';
		$cond['category_request']		= ($channel->cat_request === FALSE) ? 'FALSE' : 'TRUE';
		$cond['not_category_request']	= ($channel->cat_request !== FALSE) ? 'FALSE' : 'TRUE';
		$cond['channel']				= $row['channel_title'];
		$cond['channel_short_name']		= $row['channel_name'];
		$cond['author']					= ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
		$cond['photo_url']				= get_instance()->config->slash_item('photo_url').$row['photo_filename'];
		$cond['photo_image_width']		= $row['photo_width'];
		$cond['photo_image_height']		= $row['photo_height'];
		$cond['avatar_url']				= get_instance()->config->slash_item('avatar_url').$row['avatar_filename'];
		$cond['avatar_image_width']		= $row['avatar_width'];
		$cond['avatar_image_height']	= $row['avatar_height'];
		$cond['signature_image_url']	= get_instance()->config->slash_item('sig_img_url').$row['sig_img_filename'];
		$cond['signature_image_width']	= $row['sig_img_width'];
		$cond['signature_image_height']	= $row['sig_img_height'];
		$cond['relative_date']			= timespan($row['entry_date']);

		$prefixed_cond = array();

		foreach ($cond as $k => $v)
		{
			$prefixed_cond[$prefix.$k] = $v;
		}

		return $prefixed_cond;
	}
}


class EE_Channel_row_parser {

	protected $_data;
	protected $_preparsed;
	protected $_channel;

	public function __construct(EE_Channel_preparser $preparsed, array $data)
	{
		$this->_data = $data;
		$this->_preparsed = $preparsed;

		$this->_prefix = $preparsed->prefix();
		$this->_channel = $preparsed->channel();
	}

	public function replace($search, $replace, $subject)
	{
		return str_replace(LD.$this->_prefix.$search.RD, $replace, $subject);
	}

	public function starts_with($str, $tagname)
	{
		$tagname = $this->_prefix.$tagname;
		return strncmp($str, $tagname, strlen($tagname)) == 0;
	}

	public function equals($str, $tagname)
	{
		return $str == $this->_prefix.$tagname;
	}

	public function parse_custom_field($tag, $val, $tagdata)
	{
		$data = $this->_data;
		$prefix = $this->_prefix;

		$site_id = $data['site_id'];
		$cfields = $this->_channel->cfields[$site_id];

		$ft_api = get_instance()->api_channel_fields;

		$unprefixed_tag = preg_replace('/^'.$prefix.'/', '', $tag);
		$field_name = substr($unprefixed_tag.' ', 0, strpos($unprefixed_tag.' ', ' '));
		$param_string = substr($unprefixed_tag.' ', strlen($field_name));

		if (isset($cfields[$field_name]))
		{
			$entry = '';
			$field_id = $cfields[$field_name];

			if (isset($data['field_id_'.$field_id]) && $data['field_id_'.$field_id] != '')
			{
				$params = array();
				$parse_fnc = 'replace_tag';
				$parse_fnc_catchall = 'replace_tag_catchall';

				if ($param_string)
				{
					$params = get_instance()->functions->assign_parameters($param_string);
				}

				if ($ft_api->setup_handler($field_id))
				{
					$ft_api->apply('_init', array(array('row' => $data)));
					$data = $ft_api->apply('pre_process', array($data['field_id_'.$field_id]));

					if ($ft_api->check_method_exists($parse_fnc))
					{
						$entry = $ft_api->apply($parse_fnc, array($data, $params, FALSE));
					}
					elseif ($ft_api->check_method_exists($parse_fnc_catchall))
					{
						$entry = $ft_api->apply($parse_fnc_catchall, array($data, $params, FALSE, $modifier));
					}
				}
				else
				{
					// Couldn't find a fieldtype
					$entry = get_instance()->typography->parse_type(
						get_instance()->functions->encode_ee_tags($data['field_id_'.$field_id]),
						array(
							'text_format'	=> $data['field_ft_'.$field_id],
							'html_format'	=> $data['channel_html_formatting'],
							'auto_links'	=> $data['channel_auto_link_urls'],
							'allow_img_url' => $data['channel_allow_img_urls']
						)
					);
				}

				// prevent accidental parsing of other channel variables in custom field data
				if (strpos($entry, '{') !== FALSE)
				{
					$entry = str_replace(
						array('{', '}'),
						array(unique_marker('channel_bracket_open'), unique_marker('channel_bracket_close')),
						$entry
					);
				}

				$tagdata = str_replace(LD.$tag.RD, $entry, $tagdata);
			}
		}

		return $tagdata;
	}

	//  parse {switch} variable
	public function parse_switch_variable($tag, $tagdata, $count)
	{
		$prefix = $this->_prefix;

		if (preg_match("/^".$prefix."switch\s*=.+/i", $tag))
		{
			$sparam = get_instance()->functions->assign_parameters($tag);

			$sw = '';

			if (isset($sparam[$prefix.'switch']))
			{
				$sopt = explode("|", $sparam[$prefix.'switch']);

				$sw = $sopt[($count + count($sopt)) % count($sopt)];
			}

			$tagdata = str_replace(LD.$tag.RD, $sw, $tagdata);
		}

		return $tagdata;
	}

	public function parse_custom_field_pair($tag, $tagdata)
	{
		$data = $this->_data;
		$site_id = $data['site_id'];

		$cfields = $this->_channel->cfields[$site_id];
		$pfields = $this->_channel->pfields[$site_id];

		$pfield_chunks = $this->_preparsed->pfield_chunks;

		if ( ! isset($pfield_chunks[$site_id]))
		{
			return $tagdata;
		}

		$prefix = $this->_prefix;

		$pfield_chunk = $pfield_chunks[$site_id];

		$field_name = preg_replace('/^'.$prefix.'/', '', $tag);
		$field_name = substr($field_name, strpos($field_name, ' '));

		$ft_api = get_instance()->api_channel_fields;

		if (isset($cfields[$field_name]) && isset($pfields[$cfields[$field_name]]))
		{
			$field_id = $cfields[$field_name];
			$key_name = $pfields[$field_id];

			if ($ft_api->setup_handler($field_id))
			{
				$ft_api->apply('_init', array(array('row' => $data)));
				$pre_processed = $ft_api->apply('pre_process', array($data['field_id_'.$field_id]));

				// Blast through all the chunks
				if (isset($pfield_chunk[$prefix.$field_name]))
				{
					foreach($pfield_chunk[$prefix.$field_name] as $chk_data)
					{
						$tpl_chunk = '';
						// Set up parse function name based on whether or not
						// we have a modifier
						$parse_fnc = (isset($chk_data[3]))
							? 'replace_'.$chk_data[3] : 'replace_tag';

						if ($ft_api->check_method_exists($parse_fnc))
						{
							$tpl_chunk = $ft_api->apply(
								$parse_fnc,
								array($pre_processed, $chk_data[1], $chk_data[0])
							);
						}
						// Go to catchall and include modifier
						elseif ($ft_api->check_method_exists($parse_fnc_catchall)
							AND isset($chk_data[3]))
						{
							$tpl_chunk = $ft_api->apply(
								$parse_fnc_catchall,
								array($pre_processed, $chk_data[1], $chk_data[0], $chk_data[3])
							);
						}

						$tagdata = str_replace($chk_data[2], $tpl_chunk, $tagdata);
					}
				}
			}
		}

		return $tagdata;
	}

	public function parse_date_header_and_footer($key, $val, $tagdata)
	{
		//  parse date heading
		if (strncmp($key, 'date_heading', 12) == 0)
		{
			// Set the display preference

			$display = (is_array($val) AND isset($val['display'])) ? $val['display'] : 'daily';

			//  Hourly header
			if ($display == 'hourly')
			{
				$heading_date_hourly = get_instance()->localize->format_date('%Y%m%d%H', $data['entry_date']);

				if ($heading_date_hourly == $heading_flag_hourly)
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

					$heading_flag_hourly = $heading_date_hourly;
				}
			}
			//  Weekly header
			elseif ($display == 'weekly')
			{
				$temp_date = $data['entry_date'];

				// date()'s week variable 'W' starts weeks on Monday per ISO-8601.
				// By default we start weeks on Sunday, so we need to do a little dance for
				// entries made on Sundays to make sure they get placed in the right week heading
				if (strtolower(get_instance()->TMPL->fetch_param('start_day')) != 'monday' && get_instance()->localize->format_date('%w', $data['entry_date']) == 0)
				{
					// add 7 days to toss us into the next ISO-8601 week
					$temp_date = strtotime('+1 week', $temp_date);
				}

				$heading_date_weekly = get_instance()->localize->format_date('%Y%W', $temp_date);

				if ($heading_date_weekly == $heading_flag_weekly)
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

					$heading_flag_weekly = $heading_date_weekly;
				}
			}
			//  Monthly header
			elseif ($display == 'monthly')
			{
				$heading_date_monthly = get_instance()->localize->format_date('%Y%m', $data['entry_date']);

				if ($heading_date_monthly == $heading_flag_monthly)
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

					$heading_flag_monthly = $heading_date_monthly;
				}
			}
			//  Yearly header
			elseif ($display == 'yearly')
			{
				$heading_date_yearly = get_instance()->localize->format_date('%Y', $data['entry_date']);

				if ($heading_date_yearly == $heading_flag_yearly)
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

					$heading_flag_yearly = $heading_date_yearly;
				}
			}
			//  Default (daily) header
			else
			{
	 			$heading_date_daily = get_instance()->localize->format_date('%Y%m%d', $data['entry_date']);
	
				if ($heading_date_daily == $heading_flag_daily)
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

					$heading_flag_daily = $heading_date_daily;
				}
			}
		}
		// END DATE HEADING

		//  parse date footer
		if (strncmp($key, 'date_footer', 11) == 0)
		{
			// Set the display preference

			$display = (is_array($val) AND isset($val['display'])) ? $val['display'] : 'daily';

			//  Hourly footer
			if ($display == 'hourly')
			{
				if ( ! isset($query_result[$data['count']]) OR
					get_instance()->localize->format_date('%Y%m%d%H', $data['entry_date']) != get_instance()->localize->format_date('%Y%m%d%H', $query_result[$data['count']]['entry_date']))
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
				}
			}
			//  Weekly footer
			elseif ($display == 'weekly')
			{
				if ( ! isset($query_result[$data['count']]) OR
					get_instance()->localize->format_date('%Y%W', $data['entry_date']) != get_instance()->localize->format_date('%Y%W', $query_result[$data['count']]['entry_date']))
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
				}
			}
			//  Monthly footer
			elseif ($display == 'monthly')
			{
				if ( ! isset($query_result[$data['count']]) OR
					get_instance()->localize->format_date('%Y%m', $data['entry_date']) != get_instance()->localize->format_date('%Y%m', $query_result[$data['count']]['entry_date']))
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
				}
			}
			//  Yearly footer
			elseif ($display == 'yearly')
			{
				if ( ! isset($query_result[$data['count']]) OR
					get_instance()->localize->format_date('%Y', $data['entry_date']) != get_instance()->localize->format_date('%Y', $query_result[$data['count']]['entry_date']))
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
				}
			}
			//  Default (daily) footer
			else
			{
				if ( ! isset($query_result[$data['count']]) OR
					get_instance()->localize->format_date('%Y%m%d', $data['entry_date']) != get_instance()->localize->format_date('%Y%m%d', $query_result[$data['count']]['entry_date']))
				{
					$tagdata = get_instance()->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
				}
				else
				{
					$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
				}
			}
		}
		// END DATE FOOTER

		return $tagdata;
	}

	public function parse_categories($key, $tagdata, $categories)
	{
		$data = $this->_data;

		//  parse categories
		if ($this->starts_with($key, 'categories'))
		{
			$cat_chunk = $this->_preparsed->cat_chunks;

			if (isset($categories[$data['entry_id']]) AND is_array($categories[$data['entry_id']]) AND count($cat_chunk) > 0)
			{
				// Get category ID from URL for {if active} conditional
				get_instance()->load->helper('segment');
				$active_cat = ($this->_channel->pagination->dynamic_sql && $this->_channel->cat_request) ? parse_category($this->query_string) : FALSE;
				
				foreach ($cat_chunk as $catkey => $catval)
				{
					$cats = '';
					$i = 0;
					
					//  We do the pulling out of categories before the "prepping" of conditionals
					//  So, we have to do it here again too.  How annoying...
	// @todo conditionals
	//				$catval[0] = get_instance()->functions->prep_conditionals($catval[0], $cond);
	//				$catval[2] = get_instance()->functions->prep_conditionals($catval[2], $cond);

					$not_these		  = array();
					$these			  = array();
					$not_these_groups = array();
					$these_groups	  = array();

					if (isset($catval[1]['show']))
					{
						if (strncmp($catval[1]['show'], 'not ', 4) == 0)
						{
							$not_these = explode('|', trim(substr($catval[1]['show'], 3)));
						}
						else
						{
							$these = explode('|', trim($catval[1]['show']));
						}
					}

					if (isset($catval[1]['show_group']))
					{
						if (strncmp($catval[1]['show_group'], 'not ', 4) == 0)
						{
							$not_these_groups = explode('|', trim(substr($catval[1]['show_group'], 3)));
						}
						else
						{
							$these_groups = explode('|', trim($catval[1]['show_group']));
						}
					}

					foreach ($categories[$data['entry_id']] as $k => $v)
					{
						if (in_array($v[0], $not_these) OR (isset($v[5]) && in_array($v[5], $not_these_groups)))
						{
							continue;
						}
						elseif( (count($these) > 0 && ! in_array($v[0], $these)) OR
						 		(count($these_groups) > 0 && isset($v[5]) && ! in_array($v[5], $these_groups)))
						{
							continue;
						}

						$temp = $catval[0];

						if (preg_match_all("#".LD."path=(.+?)".RD."#", $temp, $matches))
						{
							foreach ($matches[1] as $match)
							{
								if ($this->use_category_names == TRUE)
								{
									$temp = preg_replace("#".LD."path=.+?".RD."#", reduce_double_slashes(get_instance()->functions->create_url($match).'/'.$this->reserved_cat_segment.'/'.$v[6]), $temp, 1);
								}
								else
								{
									$temp = preg_replace("#".LD."path=.+?".RD."#", reduce_double_slashes(get_instance()->functions->create_url($match).'/C'.$v[0]), $temp, 1);
								}
							}
						}
						else
						{
							$temp = preg_replace("#".LD."path=.+?".RD."#", get_instance()->functions->create_url("SITE_INDEX"), $temp);
						}
						
						get_instance()->load->library('file_field');
						$cat_image = get_instance()->file_field->parse_field($v[3]);
						
						$cat_vars = array(
							'category_name'			=> $v[2],
							'category_url_title'	=> $v[6],
							'category_description'	=> (isset($v[4])) ? $v[4] : '',
							'category_group'		=> (isset($v[5])) ? $v[5] : '',
							'category_image'		=> $cat_image['url'],
							'category_id'			=> $v[0],
							'parent_id'				=> $v[1],
							'active'				=> ($active_cat == $v[0] || $active_cat == $v[6])
						);

						// add custom fields for conditionals prep
						foreach ($this->_channel->catfields as $cv)
						{
							$cat_vars[$cv['field_name']] = ( ! isset($v['field_id_'.$cv['field_id']])) ? '' : $v['field_id_'.$cv['field_id']];
						}

						$temp = get_instance()->functions->prep_conditionals($temp, $cat_vars);

						$temp = str_replace(
							array(
								LD."category_id".RD,
								LD."category_name".RD,
								LD."category_url_title".RD,
								LD."category_image".RD,
								LD."category_group".RD,
								LD.'category_description'.RD,
								LD.'parent_id'.RD
							),
							array($v[0],
								get_instance()->functions->encode_ee_tags($v[2]),
								$v[6],
								$cat_image['url'],
								(isset($v[5])) ? $v[5] : '',
								(isset($v[4])) ? get_instance()->functions->encode_ee_tags($v[4]) : '',
								$v[1]
							),
							$temp
						);

						foreach($this->_channel->catfields as $cv2)
						{
							if (isset($v['field_id_'.$cv2['field_id']]) AND $v['field_id_'.$cv2['field_id']] != '')
							{
								$field_content = get_instance()->typography->parse_type(
									$v['field_id_'.$cv2['field_id']],
									array(
										'text_format'		=> $v['field_ft_'.$cv2['field_id']],
										'html_format'		=> $v['field_html_formatting'],
										'auto_links'		=> 'n',
										'allow_img_url'	=> 'y'
									)
								);
								
								$temp = str_replace(LD.$cv2['field_name'].RD, $field_content, $temp);
							}
							else
							{
								// garbage collection
								$temp = str_replace(LD.$cv2['field_name'].RD, '', $temp);
							}

							$temp = reduce_double_slashes($temp);
						}

						$cats .= $temp;

						if (is_array($catval[1]) && isset($catval[1]['limit']) && $catval[1]['limit'] == ++$i)
						{
							break;
						}
					}

					if (is_array($catval[1]) AND isset($catval[1]['backspace']))
					{
						$cats = substr($cats, 0, - $catval[1]['backspace']);
					}

					// Check to see if we need to parse {filedir_n}
					if (strpos($cats, '{filedir_') !== FALSE)
					{
						get_instance()->load->library('file_field');
						$cats = get_instance()->file_field->parse_string($cats);
					}
					
					$tagdata = str_replace($catval[2], $cats, $tagdata);
				}
			}
			else
			{
				$tagdata = get_instance()->TMPL->delete_var_pairs($key, 'categories', $tagdata);
			}
		}

		return $tagdata;
	}

	public function parse_date_variables($key, $val, $tagdata)
	{
		$data = $this->_data;
		$prefix = $this->_prefix;

		extract($this->_preparsed->date_vars);

		//  parse entry date
		if (isset($entry_date[$key]))
		{
			$val = str_replace($entry_date[$key], get_instance()->localize->format_date($entry_date[$key], $data['entry_date']), $val);

			$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
		}

		//  Recent Comment Date
		elseif (isset($recent_comment_date[$key]))
		{
			if ($data['recent_comment_date'] != 0)
			{
				$val = str_replace($recent_comment_date[$key], get_instance()->localize->format_date($recent_comment_date[$key], $data['recent_comment_date']), $val);

				$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$key.RD, '', $tagdata);
			}
		}

		//  GMT date - entry date in GMT
		elseif (isset($gmt_entry_date[$key]))
		{
			$val = str_replace($gmt_entry_date[$key], get_instance()->localize->format_date($gmt_entry_date[$key], $data['entry_date'], FALSE), $val);

			$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
		}

		elseif (isset($gmt_date[$key]))
		{
			$val = str_replace($gmt_date[$key], get_instance()->localize->format_date($gmt_date[$key], $data['entry_date'], FALSE), $val);

			$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
		}

		//  parse "last edit" date
		elseif (isset($edit_date[$key]))
		{
			$val = str_replace($edit_date[$key], get_instance()->localize->format_date($edit_date[$key], mysql_to_unix($data['edit_date'])), $val);

			$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
		}

		//  "last edit" date as GMT
		elseif (isset($gmt_edit_date[$key]))
		{
			$val = str_replace($gmt_edit_date[$key], get_instance()->localize->format_date($gmt_edit_date[$key], mysql_to_unix($data['edit_date']), FALSE), $val);

			$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
		}


		//  parse expiration date
		elseif (isset($expiration_date[$key]))
		{
			if ($data['expiration_date'] != 0)
			{
				$val = str_replace($expiration_date[$key], get_instance()->localize->format_date($expiration_date[$key], $data['expiration_date']), $val);

				$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$key.RD, "", $tagdata);
			}
		}


		//  "week_date"
		elseif (isset($week_date[$key]))
		{
			// Subtract the number of days the entry is "into" the week to get zero (Sunday)
			// If the entry date is for Sunday, and Monday is being used as the week's start day,
			// then we must back things up by six days

			$offset = 0;

			if (strtolower(get_instance()->TMPL->fetch_param('start_day')) == 'monday')
			{
				$day_of_week = get_instance()->localize->format_date('%w', $data['entry_date']);

				if ($day_of_week == '0')
				{
					$offset = -518400; // back six days
				}
				else
				{
					$offset = 86400; // plus one day
				}
			}

			$week_start_date = $data['entry_date'] - (get_instance()->localize->format_date('%w', $data['entry_date'], TRUE) * 60 * 60 * 24) + $offset;

			$val = str_replace($week_date[$key], get_instance()->localize->format_date($week_date[$key], $week_start_date), $val);

			$tagdata = str_replace(LD.$key.RD, $val, $tagdata);
		}

		return $tagdata;
	}

	public function parse_custom_date_fields($tag, $tagdata)
	{
		$data = $this->_data;
		$dfields = $this->_channel->dfields;
		$custom_date_fields = $this->_preparsed->custom_date_fields;

		if (isset($custom_date_fields[$tag]) && isset($dfields[$data['site_id']]))
		{
			$prefix = $this->_prefix;

			foreach ($dfields[$data['site_id']] as $dtag => $dval)
			{
				if (strncmp($tag.' ', $dtag.' ', strlen($dtag.' ')) !== 0)
				{
					continue;
				}

				if ($data['field_id_'.$dval] == 0 OR $data['field_id_'.$dval] == '')
				{
					$tagdata = str_replace(LD.$prefix.$tag.RD, '', $tagdata);
					continue;
				}

				// If date is fixed, get timezone to convert timestamp to,
				// otherwise localize it normally
				$localize = (isset($data['field_dt_'.$dval]) AND $data['field_dt_'.$dval] != '')
					? $data['field_dt_'.$dval] : TRUE;

				$tagdata = str_replace(
					LD.$prefix.$tag.RD,
					get_instance()->localize->format_date(
						$custom_date_fields[$tag],
						$data['field_id_'.$dval], 
						$localize
					),
					$tagdata
				);
			}
		}

		return $tagdata;
	}

	public function parse_simple_conditionals($key, $val, $tagdata)
	{
		$data = $this->_data;
		$prefix = $this->_prefix;

		$cfields = $this->_channel->cfields;

		if (strpos($key, '|') !== FALSE && is_array($val))
		{
			foreach($val as $item)
			{
				// Basic fields

				if (isset($data[$item]) AND $data[$item] != "")
				{
					$tagdata = str_replace(LD.$prefix.$key.RD, $data[$item], $tagdata);
					continue;
				}

				// Custom channel fields

				if ( isset( $this->cfields[$data['site_id']][$item] ) AND isset( $data['field_id_'.$cfields[$data['site_id']][$item]] ) AND $data['field_id_'.$cfields[$data['site_id']][$item]] != "")
				{
					$entry = get_instance()->typography->parse_type(
						$data['field_id_'.$cfields[$data['site_id']][$item]],
						array(
								'text_format'	=> $data['field_ft_'.$cfields[$data['site_id']][$item]],
								'html_format'	=> $data['channel_html_formatting'],
								'auto_links'	=> $data['channel_auto_link_urls'],
								'allow_img_url' => $data['channel_allow_img_urls']
							)
					);

					$tagdata = str_replace(LD.$prefix.$key.RD, $entry, $tagdata);

					continue;
				}
			}

			// Garbage collection
			$val = '';
			$tagdata = str_replace(LD.$prefix.$key.RD, "", $tagdata);
		}

		return $tagdata;
	}

	public function parse_custom_member_field($key, $val, $tagdata)
	{
		$data = $this->_data;
		$prefix = $this->_prefix;

		$mfields = $this->_channel->mfields;

		//  parse custom member fields
		if (isset($mfields[$val]) && array_key_exists('m_field_id_'.$value[0], $data))
		{
			if ( ! isset($processed_member_fields[$data['member_id']]['m_field_id_'.$mfields[$val][0]]))
			{
				$processed_member_fields[$data['member_id']]['m_field_id_'.$mfields[$val][0]] =

				get_instance()->typography->parse_type(
					$data['m_field_id_'.$mfields[$val][0]],
					array(
						'text_format'	=> $mfields[$val][1],
						'html_format'	=> 'safe',
						'auto_links'	=> 'y',
						'allow_img_url' => 'n'
					)
				);
			}

			$tagdata = str_replace(
				LD.$prefix.$val.RD,
				$processed_member_fields[$data['member_id']]['m_field_id_'.$mfields[$val][0]],
				$tagdata
			);
		}


		return $tagdata;
	}

	public function parse_simple_variables($key, $val, $tagdata)
	{
		$data = $this->_data;
		$prefix = $this->_prefix;

		//  parse profile path
		if ($this->starts_with($key, 'profile_path'))
		{
			$tagdata = str_replace(
				LD.$key.RD,
				get_instance()->functions->create_url(get_instance()->functions->extract_path($key).'/'.$data['member_id']),
				$tagdata
			 );
		}

		//  {member_search_path}
		elseif ($this->starts_with($key, 'member_search_path'))
		{
			$tagdata = str_replace(
				LD.$key.RD,
				$this->_preparsed->search_link.$data['member_id'],
				$tagdata
			);
		}


		//  parse comment_path
		elseif ($this->starts_with($key, 'comment_path') OR $this->starts_with($key, 'entry_id_path'))
		{
			$path = (get_instance()->functions->extract_path($key) != '' AND get_instance()->functions->extract_path($key) != 'SITE_INDEX') ? get_instance()->functions->extract_path($key).'/'.$data['entry_id'] : $data['entry_id'];

			$tagdata = str_replace(
				LD.$key.RD,
				get_instance()->functions->create_url($path),
				$tagdata
			);
		}

		//  parse URL title path
		elseif ($this->starts_with($key, 'url_title_path'))
		{
			$path = (get_instance()->functions->extract_path($key) != '' AND get_instance()->functions->extract_path($key) != 'SITE_INDEX') ? get_instance()->functions->extract_path($key).'/'.$data['url_title'] : $data['url_title'];

			$tagdata = str_replace(
				LD.$key.RD,
				get_instance()->functions->create_url($path),
				$tagdata
			);
		}

		//  parse title permalink
		elseif ($this->starts_with($key, 'title_permalink'))
		{
			$path = (get_instance()->functions->extract_path($key) != '' AND get_instance()->functions->extract_path($key) != 'SITE_INDEX') ? get_instance()->functions->extract_path($key).'/'.$data['url_title'] : $data['url_title'];
			
			$tagdata = str_replace(
				LD.$key.RD,
				get_instance()->functions->create_url($path, FALSE),
				$tagdata
			);
		}

		//  parse permalink
		elseif ($this->starts_with($key, 'permalink'))
		{
			$path = (get_instance()->functions->extract_path($key) != '' AND get_instance()->functions->extract_path($key) != 'SITE_INDEX') ? get_instance()->functions->extract_path($key).'/'.$data['entry_id'] : $data['entry_id'];

			$tagdata = str_replace(
				LD.$key.RD,
				get_instance()->functions->create_url($path, FALSE),
				$tagdata
			);
		}

		//  {comment_auto_path}
		elseif ($this->equals($key, "comment_auto_path"))
		{
			$path = ($data['comment_url'] == '') ? $data['channel_url'] : $data['comment_url'];

			$tagdata = str_replace(LD.$key.RD, $path, $tagdata);
		}

		//  {comment_url_title_auto_path}
		elseif ($this->equals($key, "comment_url_title_auto_path"))
		{
			$path = ($data['comment_url'] == '') ? $data['channel_url'] : $data['comment_url'];

			$tagdata = str_replace(
				LD.$key.RD,
				reduce_double_slashes($path.'/'.$data['url_title']),
				$tagdata
			);
		}

		//  {comment_entry_id_auto_path}
		elseif ($this->equals($key, "comment_entry_id_auto_path"))
		{
			$path = ($data['comment_url'] == '') ? $data['channel_url'] : $data['comment_url'];

			$tagdata = str_replace(
				LD.$key.RD,
				reduce_double_slashes($path.'/'.$data['entry_id']),
				$tagdata
			);
		}

		//  {author}
		elseif ($this->equals($key, "author"))
		{
			$tagdata = str_replace(LD.$val.RD, ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'], $tagdata);
		}

		//  {channel}
		elseif ($this->equals($key, "channel"))
		{
			$tagdata = str_replace(LD.$val.RD, $data['channel_title'], $tagdata);
		}

		//  {channel_short_name}
		elseif ($this->equals($key, "channel_short_name"))
		{
			$tagdata = str_replace(LD.$val.RD, $data['channel_name'], $tagdata);
		}

		//  {relative_date}

		elseif ($this->equals($key,  "relative_date"))
		{
			$tagdata = str_replace(LD.$val.RD, timespan($data['entry_date']), $tagdata);
		}

		//  {trimmed_url} - used by Atom feeds
		elseif ($this->equals($key, "trimmed_url"))
		{
			$channel_url = (isset($data['channel_url']) AND $data['channel_url'] != '') ? $data['channel_url'] : '';

			$channel_url = str_replace(array('http://','www.'), '', $channel_url);
			$xe = explode("/", $channel_url);
			$channel_url = current($xe);

			$tagdata = str_replace(LD.$val.RD, $channel_url, $tagdata);
		}

		//  {relative_url} - used by Atom feeds
		elseif ($this->equals($key, "relative_url"))
		{
			$channel_url = (isset($data['channel_url']) AND $data['channel_url'] != '') ? $data['channel_url'] : '';
			$channel_url = str_replace('http://', '', $channel_url);

			if ($x = strpos($channel_url, "/"))
			{
				$channel_url = substr($channel_url, $x + 1);
			}

			$channel_url = rtrim($channel_url, '/');

			$tagdata = str_replace(LD.$val.RD, $channel_url, $tagdata);
		}

		//  {url_or_email}
		elseif ($this->equals($key, "url_or_email"))
		{
			$tagdata = str_replace(LD.$val.RD, ($data['url'] != '') ? $data['url'] : $data['email'], $tagdata);
		}

		//  {url_or_email_as_author}
		elseif ($this->equals($key, "url_or_email_as_author"))
		{
			$name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

			if ($data['url'] != '')
			{
				$tagdata = str_replace(LD.$val.RD, "<a href=\"".$data['url']."\">".$name."</a>", $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$val.RD, get_instance()->typography->encode_email($data['email'], $name), $tagdata);
			}
		}


		//  {url_or_email_as_link}
		elseif ($this->equals($key, "url_or_email_as_link"))
		{
			if ($data['url'] != '')
			{
				$tagdata = str_replace(LD.$val.RD, "<a href=\"".$data['url']."\">".$data['url']."</a>", $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$val.RD, get_instance()->typography->encode_email($data['email']), $tagdata);
			}
		}

		//  {signature}
		elseif ($this->equals($key, "signature"))
		{
			if (get_instance()->session->userdata('display_signatures') == 'n' OR $data['signature'] == '' OR get_instance()->session->userdata('display_signatures') == 'n')
			{
				$tagdata = str_replace(LD.$key.RD, '', $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$key.RD,
					get_instance()->typography->parse_type($data['signature'],
						array(
							'text_format'	=> 'xhtml',
							'html_format'	=> 'safe',
							'auto_links'	=> 'y',
							'allow_img_url' => get_instance()->config->item('sig_allow_img_hotlink')
						)
					),
					$tagdata
				);
			}
		}

		elseif ($this->equals($key, "signature_image_url"))
		{
			if (get_instance()->session->userdata('display_signatures') == 'n' OR $data['sig_img_filename'] == ''  OR get_instance()->session->userdata('display_signatures') == 'n')
			{
				$tagdata = str_replace(LD.$key.RD, '', $tagdata);
				$tagdata = $this->replace('signature_image_width', '', $tagdata);
				$tagdata = $this->replace('signature_image_height', '', $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$key.RD, get_instance()->config->slash_item('sig_img_url').$data['sig_img_filename'], $tagdata);
				$tagdata = $this->replace('signature_image_width', $data['sig_img_width'], $tagdata);
				$tagdata = $this->replace('signature_image_height', $data['sig_img_height'], $tagdata);
			}
		}

		elseif ($this->equals($key, "avatar_url"))
		{
			if (get_instance()->session->userdata('display_avatars') == 'n' OR $data['avatar_filename'] == ''  OR get_instance()->session->userdata('display_avatars') == 'n')
			{
				$tagdata = str_replace(LD.$key.RD, '', $tagdata);
				$tagdata = $this->replace('avatar_image_width', '', $tagdata);
				$tagdata = $this->replace('avatar_image_height', '', $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$key.RD, get_instance()->config->slash_item('avatar_url').$data['avatar_filename'], $tagdata);
				$tagdata = $this->replace('avatar_image_width', $data['avatar_width'], $tagdata);
				$tagdata = $this->replace('avatar_image_height', $data['avatar_height'], $tagdata);
			}
		}

		elseif ($this->equals($key, "photo_url"))
		{
			if (get_instance()->session->userdata('display_photos') == 'n' OR $data['photo_filename'] == ''  OR get_instance()->session->userdata('display_photos') == 'n')
			{
				$tagdata = str_replace(LD.$key.RD, '', $tagdata);
				$tagdata = $this->replace('photo_image_width', '', $tagdata);
				$tagdata = $this->replace('photo_image_height', '', $tagdata);
			}
			else
			{
				$tagdata = str_replace(LD.$key.RD, get_instance()->config->slash_item('photo_url').$data['photo_filename'], $tagdata);
				$tagdata = $this->replace('photo_image_width', $data['photo_width'], $tagdata);
				$tagdata = $this->replace('photo_image_height', $data['photo_height'], $tagdata);
			}
		}

		//  parse {title}
		elseif ($this->equals($key, 'title'))
		{
			$data['title'] = str_replace(array('{', '}'), array('&#123;', '&#125;'), $data['title']);

			$tagdata = str_replace(
				LD.$key.RD,
				get_instance()->typography->format_characters($data['title']),
				$tagdata
			);
		}

		//  parse basic fields (username, screen_name, etc.)
		//  Use array_key_exists to handle null values

		else
		{
			$raw_val = str_replace($prefix, '', $val);

			if ($raw_val AND array_key_exists($raw_val, $data))
			{
				$tagdata = str_replace(LD.$val.RD, $data[$raw_val], $tagdata);
			}
		}

		return $tagdata;
	}
}



class EE_Channel_preparser {

	public $pairs = array();
	public $singles = array();

	public $cat_chunks = array();
	public $pfield_chunks = array();

	public $search_link = '';
	public $custom_date_fields = array();
	public $modified_conditionals = array();

	public $date_vars = array(
		'entry_date' 		=> array(),
		'gmt_date' 			=> array(),
		'gmt_entry_date'	=> array(),
		'edit_date' 		=> array(),
		'gmt_edit_date'		=> array(),
		'expiration_date'	=> array(),
		'week_date'			=> array()
	);

	public $subscriber_totals = array();

	protected $_prefix;
	protected $_tagdata;

	protected $_parser;
	protected $_channel;

	public function __construct(Channel $channel, EE_Channel_parser $parser)
	{
		$this->_parser = $parser;
		$this->_channel = $channel;

		$this->_prefix = $parser->prefix();
		$this->_tagdata = $parser->tagdata();

		$this->date_vars			 = $this->_find_date_variables();
		$this->cat_chunks			 = $this->_find_category_pairs();
		$this->custom_date_fields	 = $this->_find_custom_date_fields();
		$this->pfield_chunks		 = $this->_find_custom_field_pairs();
		$this->modified_conditionals = $this->_find_modified_conditionals();
		$this->search_link			 = $this->_member_search_link();

		$this->pairs	= $this->_extract_prefixed(get_instance()->TMPL->var_pair);
		$this->singles	= $this->_extract_prefixed(get_instance()->TMPL->var_single);

		$this->subscriber_totals	= $this->_subscriber_totals();
	}

	public function tagdata()
	{
		return $this->_tagdata;
	}

	public function prefix()
	{
		return $this->_prefix;
	}

	public function channel()
	{
		return $this->_channel;
	}

	public function parser()
	{
		return $this->_parser;
	}

	public function has_tag($tagname)
	{
		return strpos($this->_tagdata, LD.$this->_prefix.$tagname) !== FALSE;
	}

	public function has_tag_pair($tagname)
	{
		$start = strpos($this->_tagdata, LD.$this->_prefix.$tagname);

		if ($start === FALSE)
		{
			return FALSE;
		}

		$end = strpos($this->_tagdata, LD.'/'.$this->_prefix.$tagname, $start);

		return $end !== FALSE;
	}

	protected function _subscriber_totals()
	{
		$subscribers = array();
		
		if (strpos($this->_tagdata, LD.'comment_subscriber_total'.RD) !== FALSE
			&& isset(get_instance()->session->cache['channel']['entry_ids'])
			)
		{
			get_instance()->load->library('subscription');
			get_instance()->subscription->init('comment');
			$subscribers = get_instance()->subscription->get_subscription_totals('entry_id', get_instance()->session->cache['channel']['entry_ids']);
		}

		return $subscribers;
	}

	protected function _extract_prefixed(array $data)
	{
		if ( ! $this->_prefix)
		{
			return $data;
		}

		$filtered = array();

		$regex_prefix = '/^'.preg_quote($this->_prefix, '/').'[^:]+( |$)/';

		foreach (preg_grep($regex_prefix, array_keys($data)) as $key)
		{
			$filtered[$key] = $data[$key];
		}

		return $filtered;
	}

	protected function _find_category_pairs()
	{
		$cat_chunk = array();
		$prefix = preg_quote($this->_prefix, '/');

		if ($this->has_tag_pair('categories'))
		{
			if (preg_match_all("/".LD.$prefix."categories(.*?)".RD."(.*?)".LD.'\/'.$prefix.'categories'.RD."/s", $this->_tagdata, $matches))
			{
				for ($j = 0; $j < count($matches[0]); $j++)
				{
					$cat_chunk[] = array(
						$matches[2][$j],
						get_instance()->functions->assign_parameters($matches[1][$j]),
						$matches[0][$j]
					);
				}
	  		}
		}

		return $cat_chunk;
	}


	protected function _find_date_variables()
	{
		$prefix = $this->_prefix;

		$entry_date 		= array();
		$gmt_date 			= array();
		$gmt_entry_date		= array();
		$edit_date 			= array();
		$gmt_edit_date		= array();
		$expiration_date	= array();
		$week_date			= array();

		$date_vars = array('entry_date', 'gmt_date', 'gmt_entry_date', 'edit_date', 'gmt_edit_date', 'expiration_date', 'recent_comment_date', 'week_date');

		get_instance()->load->helper('date');

		foreach ($date_vars as $val)
		{
			if ( ! $this->has_tag($val))
			{
				continue;
			}

			$full_val = $prefix.$val;

			if (preg_match_all("/".LD.$full_val."\s+format=([\"'])([^\\1]*?)\\1".RD."/s", $this->_tagdata, $matches))
			{
				for ($j = 0; $j < count($matches[0]); $j++)
				{
					$matches[0][$j] = str_replace(array(LD,RD), '', $matches[0][$j]);

					switch ($val)
					{
						case 'entry_date': 
							$entry_date[$matches[0][$j]] = $matches[2][$j];
							break;
						case 'gmt_date':
							$gmt_date[$matches[0][$j]] = $matches[2][$j];
							break;
						case 'gmt_entry_date':
							$gmt_entry_date[$matches[0][$j]] = $matches[2][$j];
							break;
						case 'edit_date':
							$edit_date[$matches[0][$j]] = $matches[2][$j];
							break;
						case 'gmt_edit_date':
							$gmt_edit_date[$matches[0][$j]] = $matches[2][$j];
							break;
						case 'expiration_date':
							$expiration_date[$matches[0][$j]] = $matches[2][$j];
							break;
						case 'recent_comment_date':
							$recent_comment_date[$matches[0][$j]] = $matches[2][$j];
							break;
						case 'week_date':
							$week_date[$matches[0][$j]] = $matches[2][$j];
							break;
					}
				}
			}
		}

		return call_user_func_array('compact', $date_vars);
	}



	protected function _find_custom_date_fields()
	{
		$prefix = $this->_prefix;
		$custom_date_fields = array();

		if (count($this->_channel->dfields) > 0)
		{
			foreach ($this->_channel->dfields as $site_id => $dfields)
			{
	  			foreach($dfields as $key => $value)
	  			{
	  				if ( ! $this->has_tag($key))
	  				{
	  					continue;
	  				}

	  				$key = $prefix.$key;

					if (preg_match_all("/".LD.$key."\s+format=[\"'](.*?)[\"']".RD."/s", $this->_tagdata, $matches))
					{
						for ($j = 0; $j < count($matches[0]); $j++)
						{
							$matches[0][$j] = str_replace(array(LD,RD), '', $matches[0][$j]);

							$custom_date_fields[$matches[0][$j]] = $matches[1][$j];
						}
					}
				}
			}
		}

		return $custom_date_fields;
	}

	protected function _find_custom_field_pairs()
	{
		if (count($this->_channel->pfields) == 0)
		{
			return array();
		}

		$prefix = $this->_prefix;
		$pfield_chunk = array();

		foreach ($this->_channel->pfields as $site_id => $pfields)
		{
			$pfield_names = array_intersect($this->_channel->cfields[$site_id], array_keys($pfields));

			foreach($pfield_names as $field_name => $field_id)
			{
				if ( ! $this->has_tag_pair($field_name))
				{
					continue;
				}

				$offset = 0;
				$field_name = $prefix.$field_name;
				
				while (($end = strpos($this->_tagdata, LD.'/'.$field_name.RD, $offset)) !== FALSE)
				{
					// This hurts soo much. Using custom fields as pair and single vars in the same
					// channel tags could lead to something like this: {field}...{field}inner{/field}
					// There's no efficient regex to match this case, so we'll find the last nested
					// opening tag and re-cut the chunk.

					if (preg_match("/".LD."{$field_name}(.*?)".RD."(.*?)".LD.'\/'."{$field_name}(.*?)".RD."/s", $this->_tagdata, $matches, 0, $offset))
					{
						$chunk = $matches[0];
						$params = $matches[1];
						$inner = $matches[2];

						// We might've sandwiched a single tag - no good, check again (:sigh:)
						if ((strpos($chunk, LD.$field_name, 1) !== FALSE) && preg_match_all("/".LD."{$field_name}(.*?)".RD."/s", $chunk, $match))
						{
							// Let's start at the end
							$idx = count($match[0]) - 1;
							$tag = $match[0][$idx];
							
							// Reassign the parameter
							$params = $match[1][$idx];

							// Cut the chunk at the last opening tag (PHP5 could do this with strrpos :-( )
							while (strpos($chunk, $tag, 1) !== FALSE)
							{
								$chunk = substr($chunk, 1);
								$chunk = strstr($chunk, LD.$field_name);
								$inner = substr($chunk, strlen($tag), -strlen(LD.'/'.$field_name.RD));
							}
						}
						
						$chunk_array = array($inner, get_instance()->functions->assign_parameters($params), $chunk);
						
						// Grab modifier if it exists and add it to the chunk array
						if (substr($params, 0, 1) == ':')
						{
							$chunk_array[] = str_replace(':', '', $params);
						}
						
						$pfield_chunk[$site_id][$field_name][] = $chunk_array;
					}
					
					$offset = $end + 1;
				}
			}
		}

		return $pfield_chunk;
	}

	public function _find_modified_conditionals()
	{
		$prefix = $this->_prefix;
		$all_field_names = array();

		if (strpos($this->_tagdata, LD.'if') === FALSE)
		{
			return array();
		}

		foreach($this->_channel->cfields as $site_id => $fields)
		{
			$all_field_names = array_unique(array_merge($all_field_names, $fields));
		}

		$modified_field_options = $prefix.implode('|'.$prefix, array_keys($all_field_names));
		$modified_conditionals = array();

		if (preg_match_all("/".preg_quote(LD)."((if:(else))*if)\s+(($modified_field_options):(\w+))(.*?)".preg_quote(RD)."/s", $this->_tagdata, $matches))
		{
			foreach($matches[5] as $match_key => $field_name)
			{
				$modified_conditionals[$field_name][] = $matches[6][$match_key];
			}
		}
		
		return array_map('array_unique', $modified_conditionals);
	}

	// We use this with the {member_search_path} variable
	protected function _member_search_link()
	{
		$prefix = $this->_prefix;

		$result_path = (preg_match("/".LD.$prefix."member_search_path\s*=(.*?)".RD."/s", $this->_tagdata, $match)) ? $match[1] : 'search/results';
		$result_path = str_replace(array('"',"'"), "", $result_path);

		return get_instance()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.get_instance()->functions->fetch_action_id('Search', 'do_search').'&amp;result_path='.$result_path.'&amp;mbr=';
	}
}