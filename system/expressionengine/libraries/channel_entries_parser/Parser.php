<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.6
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Parser
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_data_parser {

	protected $_parser;
	protected $_preparser;

	protected $_data;
	protected $_count;
	protected $_tag; // var_* key
	protected $_tag_options; // var_* value
	protected $_row;

	protected $_prefix;
	protected $_channel;

	public function __construct(EE_Channel_preparser $pre, EE_Channel_parser $parser)
	{
		$this->_preparser = $pre;
		$this->_parser = $parser;

		$this->_prefix = $pre->prefix();
		$this->_channel = $pre->channel();
	}

	// --------------------------------------------------------------------

	/**
	 * Preparser accessor
	 *
	 * @return	object	The preparser object
	 */
	public function preparsed()
	{
		return $this->_preparser;
	}

	// --------------------------------------------------------------------

	/**
	 * Parser channel accessor
	 *
	 * @return	object	The bound channel object
	 */
	public function channel()
	{
		return $this->_channel;
	}

	// --------------------------------------------------------------------

	/**
	 * Iterator row accessor
	 *
	 * @return	array	The row of the current iteration
	 */
	public function row()
	{
		return $this->_row;
	}

	// --------------------------------------------------------------------

	/**
	 * Data object accessor
	 *
	 * Several data items can be provided to the parser. This includes
	 * the mandatory 'entries', but also optional values such as 'categories',
	 * 'absolute_count', or 'absolute_results'.
	 *
	 * @param	string	The data element to retrieve
	 * @param	string	The value to return if the key does not exist
	 * @return	mixed	The requested data element
	 */
	public function data($key, $default = NULL)
	{
		$data = $this->_data;
		return isset($data[$key]) ? $data[$key] : $default;
	}

	// --------------------------------------------------------------------

	/**
	 * Iterator count accessor
	 *
	 * @return	string	The step of the current iteration
	 */
	public function count()
	{
		return $this->_count;
	}

	// --------------------------------------------------------------------

	/**
	 * TMPL->var_(pair|single) key accessor
	 *
	 * @return	string	The key of the current var_* key/value pair
	 */
	public function tag()
	{
		return $this->_tag;
	}

	// --------------------------------------------------------------------

	/**
	 * TMPL->var_(pair|single) value accessor
	 *
	 * @return	mixed	The value of the current var_* key/value pair
	 */
	public function tag_options()
	{
		return $this->_tag_options;
	}

	// --------------------------------------------------------------------

	/**
	 * Prefix accessor
	 *
	 * @return	string	The prefix
	 */
	public function prefix()
	{
		return $this->_prefix;
	}

	// --------------------------------------------------------------------

	/**
	 * Run the main parsing loop.
	 *
	 * Takes the data row, the preparsed tagdata, and any additonal
	 * options and delegates to the proper parsing components.
	 *
	 * @param	array	The data row.
 	 * @param	array	Config items
 	 *
 	 *		disable:   array of components to turn off
 	 *		callbacks: array of callbacks to register
 	 *
	 * @return	string	Parsed tagdata
	 */
	public function parse($data, $config = array())
	{
		$this->_data = $data;
		$pre = $this->_preparser;

		// data options
		$entries = $this->data('entries', array());
		$absolute_offset  = $this->data('absolute_offset', 0);
		$absolute_results = $this->data('absolute_results');

		// config options
		$disabled	= isset($config['disable']) ? $config['disable'] : array();
		$callbacks	= isset($config['callbacks']) ? $config['callbacks'] : array();

		$pairs	 = $pre->pairs;
		$singles = $pre->singles;

		$prefix	 = $this->_prefix;
		$channel = $this->_channel;

		$subscriber_totals = $pre->subscriber_totals;

		$total_results = count($entries);
		$site_pages = config_item('site_pages');

		$result = ''; // final template

		// If custom fields are enabled, notify them of the data we're about to send
		if ( ! empty($channel->cfields))
		{
			$this->_send_custom_field_data_to_fieldtypes($entries);
		}

		$count = 0;

		$orig_tagdata = $this->_parser->tagdata();
		$parser_components = $this->_parser->components();

		$dt = 0;

		foreach ($entries as $row)
		{
			$tagdata = $orig_tagdata;

			$this->_count = $count;

			$row['count']				= $count + 1;
			$row['page_uri']			= '';
			$row['page_url']			= '';
			$row['total_results']		= $total_results;
			$row['absolute_count']		= $absolute_offset + $row['count'];
			$row['absolute_results']	= ($absolute_results === NULL) ? $total_results : $absolute_results;
			$row['comment_subscriber_total'] = (isset($subscriber_totals[$row['entry_id']])) ? $subscriber_totals[$row['entry_id']] : 0;

			if ($site_pages !== FALSE && isset($site_pages[$row['site_id']]['uris'][$row['entry_id']]))
			{
				$row['page_uri'] = $site_pages[$row['site_id']]['uris'][$row['entry_id']];
				$row['page_url'] = ee()->functions->create_page_url($site_pages[$row['site_id']]['url'], $site_pages[$row['site_id']]['uris'][$row['entry_id']]);
			}

			// -------------------------------------------------------
			// Loop start callback. Do what you want.
			// Currently in use in the channel module for the
			// channel_entries_tagdata hook.
			// -------------------------------------------------------

			if (isset($callbacks['tagdata_loop_start']))
			{
				$tagdata = call_user_func($callbacks['tagdata_loop_start'], $tagdata, $row);
			}

			// -------------------------------------------------------
			// Row data callback. Do what you want.
			// Currently in use in the channel module for the
			// channel_entries_row hook.
			// -------------------------------------------------------

			if (isset($callbacks['entry_row_data']))
			{
				$row = call_user_func($callbacks['entry_row_data'], $tagdata, $row);
			}


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

			$this->_row = $row;

			// conditionals!
			$cond = $this->_get_conditional_data($row, $prefix, $channel);

			//  Parse Variable Pairs
			foreach ($pairs as $key => $val)
			{
				$this->_tag = $key;
				$this->_tag_options = $val;

				foreach ($parser_components->pair() as $k => $component)
				{
					if ( ! $pre->is_disabled($component))
					{
						$tagdata = $component->replace(
							$tagdata,
							$this,
							$pre->pair_data($component)
						);
					}
				}
			}


			// Run parsers that just process tagdata once (relationships, for example)
			foreach ($parser_components->once() as $k => $component)
			{
				if ( ! $pre->is_disabled($component))
				{
					$tagdata = $component->replace(
						$tagdata,
						$this,
						$pre->once_data($component)
					);
				}
			}


			// We swap out the conditionals after pairs are parsed so they don't interfere
			// with the string replace
			$tagdata = ee()->functions->prep_conditionals($tagdata, $cond);


			//  Parse individual variable tags
			foreach ($singles as $key => $val)
			{
				$this->_tag = $key;
				$this->_tag_options = $val;

				foreach ($parser_components->single() as $k => $component)
				{
					if ( ! $pre->is_disabled($component))
					{
						$tagdata = $component->replace(
							$tagdata,
							$this,
							$pre->single_data($component)
						);
					}
				}
			}


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
		$channel = $this->_preparser->channel();

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
			ee()->load->library('api');
			ee()->api->instantiate('channel_fields');
			$ft_api = ee()->api_channel_fields;

			// For each custom field, notify its fieldtype class of the data we collected
			foreach ($custom_field_data as $field_id => $data)
			{
				if ($ft_api->setup_handler($field_id))
				{
					if ($ft_api->check_method_exists('pre_loop'))
					{
						$ft_api->apply('pre_loop', array($data));
					}
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Prepare the row for conditionals
	 *
	 * Retrieves a prefixed set of all the row data that can be passed
	 * to prep_conditionals to allow for proper conditionals.
	 *
	 * @param	array	The data row.
	 * @param	string	The prefix.
 	 * @param	object	A channel object to operate on
	 * @return	array	Prefixed, prep-able data
	 */
	protected function _get_conditional_data($row, $prefix, $channel)
	{
		$pre = $this->_preparser;

		$cond = $row;
		$cond['logged_in']			= (ee()->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
		$cond['logged_out']			= (ee()->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';

		foreach (array('avatar_filename', 'photo_filename', 'sig_img_filename') as $pv)
		{
			if ( ! isset($row[$pv]))
			{
				$row[$pv] = '';
			}
		}

		$cond['allow_comments']			= $this->_commenting_allowed($row) ? 'TRUE' : 'FALSE';
		$cond['signature_image']		= ($row['sig_img_filename'] == '' OR ee()->config->item('enable_signatures') == 'n' OR ee()->session->userdata('display_signatures') == 'n') ? 'FALSE' : 'TRUE';
		$cond['avatar']					= ($row['avatar_filename'] == '' OR ee()->config->item('enable_avatars') == 'n' OR ee()->session->userdata('display_avatars') == 'n') ? 'FALSE' : 'TRUE';
		$cond['photo']					= ($row['photo_filename'] == '' OR ee()->config->item('enable_photos') == 'n' OR ee()->session->userdata('display_photos') == 'n') ? 'FALSE' : 'TRUE';
		$cond['forum_topic']			= (empty($row['forum_topic_id'])) ? 'FALSE' : 'TRUE';
		$cond['not_forum_topic']		= ( ! empty($row['forum_topic_id'])) ? 'FALSE' : 'TRUE';
		$cond['category_request']		= ($channel->cat_request === FALSE) ? 'FALSE' : 'TRUE';
		$cond['not_category_request']	= ($channel->cat_request !== FALSE) ? 'FALSE' : 'TRUE';
		$cond['channel']				= $row['channel_title'];
		$cond['channel_short_name']		= $row['channel_name'];
		$cond['author']					= ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
		$cond['photo_url']				= ee()->config->slash_item('photo_url').$row['photo_filename'];
		$cond['photo_image_width']		= $row['photo_width'];
		$cond['photo_image_height']		= $row['photo_height'];
		$cond['avatar_url']				= ee()->config->slash_item('avatar_url').$row['avatar_filename'];
		$cond['avatar_image_width']		= $row['avatar_width'];
		$cond['avatar_image_height']	= $row['avatar_height'];
		$cond['signature_image_url']	= ee()->config->slash_item('sig_img_url').$row['sig_img_filename'];
		$cond['signature_image_width']	= $row['sig_img_width'];
		$cond['signature_image_height']	= $row['sig_img_height'];
		$cond['relative_date']			= timespan($row['entry_date']);

		foreach($channel->mfields as $key => $value)
		{
			$cond[$key] = ( ! array_key_exists('m_field_id_'.$value[0], $row)) ? '' : $row['m_field_id_'.$value[0]];
		}

		// custom field conditionals
		if (isset($channel->cfields[$row['site_id']]))
		{
			foreach($channel->cfields[$row['site_id']] as $key => $value)
			{
				$cond[$key] = ( ! isset($row['field_id_'.$value])) ? '' : $row['field_id_'.$value];

				// Is this field used with a modifier anywhere?
				if (isset($pre->modified_conditionals[$key]) && count($pre->modified_conditionals[$key]))
				{
					ee()->load->library('api');
					ee()->api->instantiate('channel_fields');

					if (ee()->api_channel_fields->setup_handler($value))
					{
						foreach($pre->modified_conditionals[$key] as $modifier)
						{
							ee()->api_channel_fields->apply('_init', array(array(
								'row' => $row,
								'content_id' => $row['entry_id']
							)));
							$data = ee()->api_channel_fields->apply('pre_process', array($cond[$key]));
							if (ee()->api_channel_fields->check_method_exists('replace_'.$modifier))
							{
								$result = ee()->api_channel_fields->apply('replace_'.$modifier, array($data, array(), FALSE));
							}
							else
							{
								$result = FALSE;
								ee()->TMPL->log_item('Unable to find parse type for custom field conditional: '.$key.':'.$modifier);
							}

							$cond[$key.':'.$modifier] = $result;

							// If this key also happens to be a Grid field with the modifier
							// "total_rows", make it the default value for evaluating
							// conditionals
							if (isset($channel->gfields[$row['site_id']][$key]) &&
								$modifier == 'total_rows')
							{
								$cond[$key] = $result;
							}
						}
					}
				}
			}
		}




		foreach ($channel->mfields as $key => $value)
		{
			$cond[$key] = ( ! array_key_exists('m_field_id_'.$value[0], $row)) ? '' : $row['m_field_id_'.$value[0]];
		}

		if ( ! $prefix)
		{
			return $cond;
		}

		$prefixed_cond = array();

		foreach ($cond as $k => $v)
		{
			$prefixed_cond[$prefix.$k] = $v;
		}

		return $prefixed_cond;
	}

	// --------------------------------------------------------------------

	/**
	 * Is commenting on this row allowed?
	 *
	 * A convenience function so that we don't have to deal with this as
	 * a carzy conditional
	 *
	 * @param	array	The data row.
	 * @return	bool	Can comment on this row?
	 */
	protected function _commenting_allowed($row)
	{
		if ($row['allow_comments'] == 'n')
		{
			return FALSE;
		}

		if (isset($row['comment_system_enabled']) && $row['comment_system_enabled'] == 'n')
		{
			return FALSE;
		}

		if (config_item('comment_moderation_override') === 'y')
		{
			return TRUE;
		}
		elseif ($row['comment_expiration_date'] > 0 && $row['comment_expiration_date'] < ee()->localize->now)
		{
			return FALSE;
		}

		return TRUE;
	}
}