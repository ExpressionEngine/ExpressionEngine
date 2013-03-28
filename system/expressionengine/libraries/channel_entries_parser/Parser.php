<?php


class EE_Channel_data_parser {

	protected $_preparsed;
	protected $_relationship_parser;

	protected $_data;
	protected $_count;
	protected $_tag; // var_* key
	protected $_tag_options; // var_* value
	protected $_row;

	public function __construct(EE_Channel_preparser $pre, $relationship_parser = NULL)
	{
		$this->_preparsed = $pre;
		$this->_relationship_parser = $relationship_parser;
	}

	public function preparsed()
	{
		return $this->_preparsed;
	}

	public function channel()
	{
		return $this->_preparsed->channel();
	}

	public function row()
	{
		return $this->_row;
	}

	public function data($key, $default = NULL)
	{
		$data = $this->_data;
		return isset($data[$key]) ? $data[$key] : $default;
	}

	public function count()
	{
		return $this->_count;
	}

	public function tag()
	{
		return $this->_tag;
	}

	public function tag_options()
	{
		return $this->_tag_options;
	}

	public function prefix()
	{
		return $this->_preparsed->prefix();
	}


	public function parse($data, $config = array())
	{
		$this->_data = $data;

		// data options
		$entries = $this->data('entries', array());

		$absolute_offset  = $this->data('absolute_offset', 0);
		$absolute_results = $this->data('absolute_results');

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

		$parser_plugins = get_instance()->channel_entries_parser->plugins();

		foreach ($entries as $row)
		{
			$tagdata = $this->_preparsed->tagdata();

			$this->_count = $count;

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

			$this->_row = $row;

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
				$this->_tag = $key;
				$this->_tag_options = $val;

				foreach ($parser_plugins->pair() as $plugin)
				{
					$tagdata = $plugin->replace($tagdata, $this);
				}
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
				$this->_tag = $key;
				$this->_tag_options = $val;

				foreach ($parser_plugins->single() as $plugin)
				{
					$tagdata = $plugin->replace($tagdata, $this);
				}
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