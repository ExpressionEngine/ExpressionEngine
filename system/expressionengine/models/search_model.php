<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Admin Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Search_model extends CI_Model {

	// --------------------------------------------------------------------

	/**
	 * Get Filtered Entries
	 *
	 * Main query for editable entries based on search parameters
	 *
	 * @access	public
	 * @param	array	data array
	 * @param	mixed	optional order array
	 * @param	bool	whether this is a control panel request
	 * @return	array
	 */

	function get_filtered_entries($data, $order = array(), $cp = TRUE)
	{
		$return_data = array('pageurl' => '', 'total_count' => 0, 'results' => array());
		$ids = array();

		$base_results = $this->build_main_query($data, $order);
		$base_qry_obj = $base_results['result_obj'];

		$return_data['results'] = array();
		$return_data['pageurl'] = $base_results['pageurl'];
		$return_data['total_count'] = $base_qry_obj->num_rows();

		if ($return_data['total_count'] == 0 OR
			$return_data['total_count'] <= $data['rownum'])
		{
			return $return_data;
		}

		// This code will return every row in the selected channels if there is
		// no filter. Potentially hundreds of thousands of rows. That's no good.
		// We need the total rows, but a complicated search can be quite slow and
		// we don't want to double up on a slow query. So getting around it with
		// some private db methods for now. -pk

		// $base_results = array_slice($base_results['result_obj']->result_array(), $data['rownum'], $data['perpage']);

		$base_results = array();
		$perpage = $data['perpage'];

		$base_qry_obj->_data_seek($data['rownum']);

		while ($perpage && ($row = $base_qry_obj->_fetch_assoc()))
		{
			$perpage--;
			$base_results[] = $row;
		}

		$base_qry_obj->free_result();

		if ($data['search_in'] == 'comments')
		{
			foreach ($base_results as $id)
			{
				$ids[] = $id['comment_id'];
			}

			$return_data['ids'] = $ids;
			return $return_data;
		}

		foreach ($base_results as $id)
		{
			$ids[] = $id['entry_id'];
		}

		$results = $this->get_full_cp_query($data, $ids, $order);

		$return_data['results'] = $results->result_array();
		$results->free_result();

		return $return_data;
	}


	// --------------------------------------------------------------------

	/**
	 * Build Main Query
	 *
	 * Creates the main query for search filter
	 *
	 * @access	public
	 * @param	array	data array
	 * @param	mixed	optional order array
	 * @param	bool	whether to count results
	 * @param	bool	whether this is a control panel request
	 * @return	array
	 */

	function build_main_query($data, $order = array(), $cp = TRUE)
	{
		$where_clause = '';
		$pageurl = '';

		if ($cp)
		{
			// Fetch channel ID numbers assigned to the current user
			$allowed_channels = $this->functions->fetch_assigned_channels();
		}

		$data['search_channels'] = $data['channel_id'];

		if ($data['search_channels'] == '')
		{
			if ($cp && $this->session->userdata['group_id'] != 1)
			{
				$data['search_channels'] = $allowed_channels;
			}
		}

		if ( ! is_array($data['search_channels']) && $data['search_channels'] != '')
		{
			$data['search_channels'] = array($data['search_channels']);
		}

		if ($cp && is_array($data['search_channels']))
		{
			$data['search_channels'] = array_intersect($data['search_channels'], $allowed_channels);
		}

		$searchable_fields = array();

		// Joins with channel and member tables slow us down at this point
		// So- to order by channel name or screen name- we'll manually specify sort order if
		// we need to order!

		if (isset($order['channel_name']))
		{
			$this->db->select('channel_id, channel_name');

			if ($data['search_channels'] != '')
			{
				$this->db->where_in('channel_id', $data['search_channels']);
			}

			$this->db->where('site_id', $this->config->item('site_id'));
			$channel_names = $this->db->get('channels');

			foreach($channel_names->result_array() as $row)
			{
				$channels[$row['channel_id']] = $row['channel_name'];
			}

			if ($order['channel_name'] == 'asc')
			{
				asort($channels);
			}
			else
			{
				arsort($channels);
			}

			$channels = array_flip($channels);

			$channel_name_order = implode(', ', $channels);
		}

		if (isset($order['screen_name']))
		{
			// OK- if they can't view entries by others, nothing to sort
			if ( ! $this->cp->allowed_group('can_view_other_entries'))
			{
				$screen_name_order = $this->session->userdata('member_id');
			}
			else
			{
				$this->db->select('member_id, screen_name');

				$this->db->where('total_entries >', 0);
				$screen_names = $this->db->get('members');

				foreach($screen_names->result_array() as $row)
				{
					$names[$row['member_id']] = $row['screen_name'];
				}

				if ($order['screen_name'] == 'asc')
				{
					asort($names);
				}
				else
				{
					arsort($names);
				}

				$names = array_flip($names);

				$screen_name_order = implode(', ', $names);
			}
		}

		if ($data['search_in'] == 'comments')
		{
			$this->db->select('comments.comment_id', FALSE);

			$this->db->from('comments');
			$this->db->join('channel_titles', 'exp_channel_titles.entry_id = exp_comments.entry_id', 'left');

		}
		else
		{
			$searchable_fields = $this->get_searchable_fields($data['search_channels']);

			if ($data['cat_id'] == 'none' OR $data['cat_id'] != "")
			{
				$this->db->select('channel_titles.entry_id', FALSE);
			}
			else
			{
				$this->db->select('channel_titles.entry_id', FALSE);
			}

			$this->db->from('channel_titles');
		}

		if ($data['keywords'] != '')
		{
			if ($data['search_in'] != 'title')
			{
				$this->db->join('channel_data', 'exp_channel_titles.entry_id = exp_channel_data.entry_id ', 'left');
			}

			if ($data['search_in'] == 'everywhere')
			{
				$this->db->join('comments', 'exp_channel_titles.entry_id = exp_comments.entry_id', 'left');
			}
		}


		if ($data['cat_id'] == 'none' OR $data['cat_id'] != "")
		{
			$this->db->join('category_posts', 'exp_channel_titles.entry_id = exp_category_posts.entry_id', 'left');
			$this->db->join('categories', 'exp_category_posts.cat_id = exp_categories.cat_id', 'left');
		}

		// Construct our where clause - this is annoying
		// Limit to channels assigned to user

		$where_clause .= "exp_channel_titles.site_id = '".$this->db->escape_str($this->config->item('site_id'))."'";

		if ( ! $this->cp->allowed_group('can_edit_other_entries') AND ! $this->cp->allowed_group('can_view_other_entries'))
		{
			$where_clause .= " AND exp_channel_titles.author_id = ".$this->session->userdata('member_id');
		}

		if ($data['keywords'] != '')
		{
			$pageurl .= AMP.'keywords='.base64_encode($data['keywords']);

			if ($data['search_in'] == 'comments')
			{
				// When searching in comments we do not want to search the entry title.
				// However, by removing this we would have to make the rest of the query creation code
				// below really messy so we simply check for an empty title, which should never happen.
				// That makes this check pointless and allows us some cleaner code. -Paul

				$where_clause .= " AND (exp_channel_titles.title = '' ";
			}
			else
			{
				if ($data['exact_match'] != 'yes')
				{
					// We are splitting the keywords and doing individual
					// LIKE clauses to account for titles that have punctuation
					// in them that sanitize_search_terms() stripped out
					$keyword_clauses = array();
					preg_match_all('/(?<!")\b\w+\b|(?<=")\b[^"]+/', $data['search_keywords'], $keywords, PREG_PATTERN_ORDER);
					for ($i = 0; $i < count($keywords[0]); $i++)
					{
						$keyword_clauses[] = " exp_channel_titles.title LIKE '%".$this->db->escape_like_str($keywords[0][$i])."%' ";
					}
					$where_clause .= " AND (" . join($keyword_clauses, ' AND ');
					unset($keywords, $keyword_clauses);
				}
				else
				{
					$pageurl .= AMP.'exact_match=yes';

					$where_clause .= " AND (exp_channel_titles.title = '".$this->db->escape_str($data['search_keywords'])."' OR exp_channel_titles.title LIKE '".$this->db->escape_like_str($data['search_keywords'])." %' OR exp_channel_titles.title LIKE '% ".$this->db->escape_like_str($data['search_keywords'])." %' ";
				}
			}

			$pageurl .= AMP.'search_in='.$data['search_in'];

			if ($data['search_in'] == 'body' OR $data['search_in'] == 'everywhere')
			{
				foreach ($searchable_fields as $val)
				{
					if ($data['exact_match'] != 'yes')
					{
						$where_clause .= " OR exp_channel_data.field_id_".$val." LIKE '%".$this->db->escape_like_str($data['search_keywords'])."%' ";
					}
					else
					{
						$where_clause .= "  OR (exp_channel_data.field_id_".$val." LIKE '".$this->db->escape_like_str($data['search_keywords'])." %' OR exp_channel_data.field_id_".$val." LIKE '% ".$this->db->escape_like_str($data['search_keywords'])." %' OR exp_channel_data.field_id_".$val." = '".$this->db->escape_str($data['search_keywords'])."') ";
					}
				}
			}

			if ($data['search_in'] == 'everywhere' OR $data['search_in'] == 'comments')
			{
				if ($data['search_in'] == 'comments' && (substr(strtolower($data['search_keywords']), 0, 3) == 'ip:' OR substr(strtolower($data['search_keywords']), 0, 4) == 'mid:'))
				{
					if (substr(strtolower($data['search_keywords']), 0, 3) == 'ip:')
					{
						$where_clause .= " OR (exp_comments.ip_address = '".$this->db->escape_str(str_replace('_','.',substr($data['search_keywords'], 3)))."') ";
					}
					elseif(substr(strtolower($data['search_keywords']), 0, 4) == 'mid:')
					{
						$where_clause .= " OR (exp_comments.author_id = '".$this->db->escape_str(substr($data['search_keywords'], 4))."') ";
					}
				}
				else
				{
					$where_clause .= " OR (exp_comments.comment LIKE '%".$this->db->escape_like_str($data['keywords'])."%') "; // No ASCII conversion here!
				}
			}

			$where_clause .= ")";
		}

		if ($data['channel_id'] != '')
		{
			$pageurl .= AMP.'channel_id='.$data['channel_id'];
		}

		if (is_array($data['search_channels']) && count($data['search_channels']) > 0)
		{
/////////			$where_clause .= " AND exp_channel_titles.channel_id = ".$data['channel_id'];
					$where_clause .= " AND exp_channel_titles.channel_id IN (".implode(',' , $data['search_channels']).")";
		}

		if ($data['date_range'])
		{
			//  Is a single number
			if (ctype_digit($data['date_range']))
			{
				$date_range = time() - ($data['date_range'] * 60 * 60 * 24);
				$where_clause .= " AND exp_channel_titles.entry_date > $date_range";

				$pageurl .= AMP.'date_range='.$data['date_range'];
			}
			elseif (strpos($data['date_range'], 'to') !== FALSE)
			{
				// Custom range
				$ranges = explode('to', $data['date_range']);

				$start = $this->localize->string_to_timestamp(trim($ranges[0]).' 00:00');
				$end = $this->localize->string_to_timestamp(trim($ranges[1]).' 23:59');

				if (ctype_digit($start) && ctype_digit($end))
				{
					$where_clause .= "AND exp_channel_titles.entry_date >= '".$start."' ";
					$where_clause .= "AND exp_channel_titles.entry_date <=  '".$end."' ";
					$pageurl .= AMP.'date_range='.$data['date_range'];
				}
			}
		}

		if (is_numeric($data['cat_id']))
		{
			$pageurl .= AMP.'cat_id='.$data['cat_id'];
			$where_clause .= " AND exp_category_posts.cat_id = '".$data['cat_id']."'
					  AND exp_category_posts.entry_id = exp_channel_titles.entry_id ";
		}

		if ($data['cat_id'] == 'none')
		{
			$pageurl .= AMP.'cat_id='.$data['cat_id'];
			$where_clause .= " AND exp_category_posts.entry_id IS NULL ";
		}

		if ($data['status'] && $data['status'] != 'all')
		{
			$pageurl .= AMP.'status='.$data['status'];

			$where_clause .= " AND exp_channel_titles.status = '".$data['status']."'";
		}

		$this->db->where($where_clause, NULL, FALSE);


		// Process hook data
		if (isset($data['_hook_wheres']) && is_array($data['_hook_wheres']))
		{
			foreach($data['_hook_wheres'] as $field => $value)
			{
				$func = 'where';

				if (is_array($value))
				{
					if ( ! count($value))
					{
						continue;
					}

					$func = 'where_in';

					// allow for where_not_in
					if (strpos($field, '!=') !== FALSE)
					{
						$field = str_replace('!=', '', $field);
						$func = 'where_not_in';
					}
				}

				$this->db->$func(trim($field), $value);
			}
		}


		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
				if ($key == 'channel_name')
				{
					$this->db->order_by('FIELD(channel_id, '.$channel_name_order.')', NULL, FALSE);
				}
				elseif ($key == 'screen_name')
				{
					$this->db->order_by('FIELD(author_id, '.$screen_name_order.')', NULL, FALSE);
				}
				else
				{
					$this->db->order_by($key, $val);
				}
			}
		}
		else
		{
			$this->db->order_by('entry_date', 'desc');
		}

		//$this->db->limit($data['perpage'], $data['rownum']);

		// ------------------------------
		//	 Are there results?
		// ------------------------------

		$this->db->distinct();

		return array('pageurl' => $pageurl, 'result_obj' => $this->db->get());
	}

	// --------------------------------------------------------------------

	/**
	 * Build Full CP Query
	 *
	 * Creates the full query for search filter
	 *
	 * @access	public
	 * @param	array	data array
	 * @param	array	array of entry ids
	 * @param	mixed	order
	 * @return	object
	 */
	function get_full_cp_query($data, $ids = array(), $order = array())
	{
		$select = ($data['cat_id'] == 'none' OR $data['cat_id'] != "") ? "DISTINCT(exp_channel_titles.entry_id), " : "exp_channel_titles.entry_id, ";

		$select .= "exp_channel_titles.channel_id,
				exp_channel_titles.title,
				exp_channel_titles.author_id,
				exp_channel_titles.status,
				exp_channel_titles.entry_date,
				exp_channel_titles.comment_total,
				exp_channels.live_look_template,
				exp_members.username,
				exp_members.email,
				exp_members.screen_name";


		$this->db->select($select, FALSE);
		$this->db->from('channel_titles');
		$this->db->join('channels', 'exp_channel_titles.channel_id = exp_channels.channel_id', 'left');
		$this->db->join('members', 'exp_members.member_id = exp_channel_titles.author_id', 'left');


		// left instead of inner
		// inner would mean we exclude entries that don't belong to a category, which
		// seems undesirable.
		if ($data['cat_id'] != 'none' AND $data['cat_id'] != "")
		{
			$this->db->join('category_posts', 'exp_channel_titles.entry_id = exp_category_posts.entry_id', 'left');
			$this->db->join('categories', 'exp_category_posts.cat_id = exp_categories.cat_id', 'left');
		}

		$where_clause = "exp_channel_titles.entry_id IN (";

		foreach ($ids as $id)
		{
			$where_clause .= $id.',';
		}

		$where_clause = substr($where_clause, 0, -1).")";

		$this->db->where($where_clause, NULL, FALSE);

		if (is_array($order) && count($order) > 0)
		{
			foreach ($order as $key => $val)
			{
				$this->db->order_by($key, $val);
			}
		}
		else
		{
			$this->db->order_by('entry_date', 'desc');
		}

		//$this->db->limit($data['perpage'], $data['rownum']);

		return $this->db->get();
	}

	// --------------------------------------------------------------------

	/**
	 * Get Searchable Fields
	 *
	 * CFetch the searchable field names
	 *
	 * @access	public
	 * @param	array	data array
	 * @return	array
	 */

	function get_searchable_fields($channel_id = array())
	{
		$fields = array();

		$this->db->distinct();
		$this->db->select('field_group');

		if (is_array($channel_id) && count($channel_id) > 0)
		{
			$this->db->where_in('channel_id', $channel_id);
		}

		$query = $this->db->get('channels');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$fql[] = $row['field_group'];
			}

			$this->db->select('field_id');
			$this->db->where_in('group_id', $fql);

			$query =  $this->db->get('channel_fields');

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$fields[] = $row['field_id'];
				}
			}
		}

		return $fields;
	}

	function comment_search($channel_id = '', $entry_id = '', $id_array = '', $total_rows = '', $validate = '', $order = array())
	{
		$return_data['error'] = FALSE;
		$return_data['results'] = array();
		$return_data['total_comments'] = $total_rows;
		$return_data['total_count'] = $total_rows;

		$ids = array();

		if ($validate OR (is_array($id_array) && count($id_array) > 0))
		{
			if ( ! $this->cp->allowed_group('can_moderate_comments'))
			{
				$return_data['error'] = $this->lang->line('unauthorized_access');
				return $return_data;
			}

			if (is_array($id_array))
			{
				$validate = TRUE;

				$this->db->select('comments.*, channels.channel_name, channel_titles.title AS entry_title');
				$this->db->from(array('comments', 'channels', 'channel_titles'));
				$this->db->where_in('comments.comment_id', $id_array);
				$this->db->where('comments.entry_id = '.$this->db->dbprefix('channel_titles.entry_id'));
				$this->db->where('comments.channel_id = '.$this->db->dbprefix('channels.channel_id'));
			}
			else
			{
				$this->db->select('comments.*, channels.channel_name, channel_titles.title AS entry_title');
				$this->db->from(array('comments', 'channels', 'channel_titles'));
				$this->db->where('comments.status', 'c');
				$this->db->where('comments.entry_id = '.$this->db->dbprefix('channel_titles.entry_id'));
				$this->db->where('comments.channel_id = '.$this->db->dbprefix('channels.channel_id'));
			}


			if (is_array($order) && count($order) > 0)
			{
				foreach ($order as $key => $val)
				{
					$this->db->order_by($key, $val);
				}
			}
			else
			{
				$this->db->order_by('comment_date', 'desc');
			}

			$query = $this->db->get();

			foreach ($query->result_array() as $row)
			{
				$ids[] = $row['comment_id'];
			}

			$return_data['ids'] = $ids;

			$return_data['total_count'] = $query->num_rows();
			$return_data['total_comments'] = $query->num_rows();

			$return_data['results'] = $query->result_array();

			return $return_data;
		}
		else
		{
			if ($entry_id == '')
			{
				if ( ! $entry_id = $this->input->get('entry_id'))
				{
					$return_data['error'] = $this->lang->line('unauthorized_access');
					return $return_data;
				}
			}

			if ($channel_id == '')
			{
				if ( ! $channel_id = $this->input->get('channel_id'))
				{
					$return_data['error'] = $this->lang->line('unauthorized_access');
					return $return_data;
				}
			}

			if ( ! is_numeric($entry_id) OR ! is_numeric($channel_id))
			{
				$return_data['error'] = $this->lang->line('unauthorized_access');
				return $return_data;
			}


			/** ---------------------------------------
			/**	 Fetch Author ID and verify privs
			/** ---------------------------------------*/

			$this->db->select('author_id, title');
			$query = $this->db->get_where('channel_titles', array('entry_id' => $entry_id));

			if ($query->num_rows() == 0)
			{
				$return_data['error'] = $this->lang->line('no_channel_exists');
				return $return_data;
			}

			if ($query->row('author_id') != $this->session->userdata('member_id'))
			{
				if ( ! $this->cp->allowed_group('can_view_other_comments'))
				{
					$return_data['error'] = $this->lang->line('unauthorized_access');
					return $return_data;
				}
			}

			//$et = $query->row('title') ;

			// Fetch comment ID numbers

			$this->db->select('comment_date, comment_id');

			if (is_array($order) && count($order) > 0)
			{
				foreach ($order as $key => $val)
				{
					$this->db->order_by($key, $val);
				}
			}
			else
			{
				$this->db->order_by('comment_date', 'desc');
			}

			$id_query = $this->db->get_where('comments', array('entry_id' => $entry_id));

			// No results?  No reason to continue...

			$return_data['total_comments'] = $id_query->num_rows();
			$return_data['total_count'] = $return_data['total_comments'];

			if ($id_query->num_rows() == 0)
			{
				//$message = $this->lang->line('no_comments');
				$return_data['results'] = $id_query->result_array();
				return $return_data;
			}

			foreach($id_query->result_array() as $row)
			{
				$c_ids[] = $row['comment_id'];
			}


			// Fetch Comments if necessary
			if (is_array($order) && count($order) > 0)
			{
				foreach ($order as $key => $val)
				{
					$this->db->order_by($key, $val);
				}
			}
			else
			{
				$this->db->order_by('comment_date', 'desc');
			}

			$this->db->select('comment_id, entry_id, status, channel_id, author_id, name, email, url, location, ip_address, comment_date, comment');
			$this->db->where_in('comment_id', $c_ids);
			$query = $this->db->get('comments');

			$return_data['results'] = $query->result_array();

			return $return_data;
		}
	}
}

/* End of file search_model.php */
/* Location: ./system/expressionengine/models/search_model.php */
