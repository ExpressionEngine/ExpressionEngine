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
 * ExpressionEngine Channel Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class Channel {

	var $limit	= '100';	// Default maximum query results if not specified.

	// These variable are all set dynamically

	var $query;
	var $TYPE;
	var $entry_id				= '';
	var	$uri					= '';
	var $uristr					= '';
	var $return_data			= '';	 	// Final data
	var $basepath				= '';
	var $hit_tracking_id		= FALSE;
	var	$sql					= FALSE;
	var $cfields				= array();
	var $dfields				= array();
	var $rfields				= array();
	var $mfields				= array();
	var $pfields				= array();
	var $categories				= array();
	var $catfields				= array();
	var $channel_name	 		= array();
	var $channels_array			= array();
	var $related_entries		= array();
	var $reverse_related_entries= array();
	var $reserved_cat_segment 	= '';
	var $use_category_names		= FALSE;
	var $dynamic_sql			= FALSE;
	var $cat_request			= FALSE;
	var $enable					= array();	// modified by various tags with disable= parameter
    var $absolute_results		= NULL;		// absolute total results returned by the tag, useful when paginating

	// These are used with the nested category trees

	var $category_list  		= array();
	var $cat_full_array			= array();
	var $cat_array				= array();
	var $temp_array				= array();
	var $category_count			= 0;

	// Pagination variables

	var $paginate				= FALSE;
	var $field_pagination		= FALSE;
	var $paginate_data			= '';
	var $pagination_links		= '';
	var $page_next				= '';
	var $page_previous			= '';
	var $current_page			= 1;
	var $total_pages			= 1;
	var $multi_fields			= array();
	var $display_by				= '';
	var $total_rows				=  0;
	var $pager_sql				= '';
	var $p_limit				= '';
	var $p_page					= '';


	// SQL Caching

	var $sql_cache_dir			= 'sql_cache/';

	// Misc. - Class variable usable by extensions
	var $misc					= FALSE;

	/**
	  * Constructor
	  */
	function Channel()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		$this->p_limit = $this->limit;

		$this->query_string = ($this->EE->uri->page_query_string != '') ? $this->EE->uri->page_query_string : $this->EE->uri->query_string;

		if ($this->EE->config->item("use_category_name") == 'y' && $this->EE->config->item("reserved_category_word") != '')
		{
			$this->use_category_names	= $this->EE->config->item("use_category_name");
			$this->reserved_cat_segment	= $this->EE->config->item("reserved_category_word");
		}

		// a number tags utilize the disable= parameter, set it here
		if (isset($this->EE->TMPL) && is_object($this->EE->TMPL))
		{
			$this->_fetch_disable_param();
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Initialize values
	  */
	function initialize()
	{
		$this->sql 			= '';
		$this->return_data	= '';
	}

	// ------------------------------------------------------------------------

	/**
	  *  Fetch Cache
	  */
	function fetch_cache($identifier = '')
	{
		$tag = ($identifier == '') ? $this->EE->TMPL->tagproper : $this->EE->TMPL->tagproper.$identifier;

		if ($this->EE->TMPL->fetch_param('dynamic_parameters') !== FALSE && isset($_POST) && count($_POST) > 0)
		{
			foreach (explode('|', $this->EE->TMPL->fetch_param('dynamic_parameters')) as $var)
			{
				if (isset($_POST[$var]) && in_array($var, array('channel', 'entry_id', 'category', 'orderby', 'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from', 'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month', 'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'cat_limit', 'month_limit', 'offset', 'author_id')))
				{
					$tag .= $var.'="'.$_POST[$var].'"';
				}

				if (isset($_POST[$var]) && strncmp($var, 'search:', 7) == 0)
				{
					$tag .= $var.'="'.substr($_POST[$var], 7).'"';
				}
			}
		}

		$cache_file = APPPATH.'cache/'.$this->sql_cache_dir.md5($tag.$this->uri);

		if ( ! $fp = @fopen($cache_file, FOPEN_READ))
		{
			return FALSE;
		}

		flock($fp, LOCK_SH);
		$sql = @fread($fp, filesize($cache_file));
		flock($fp, LOCK_UN);
		fclose($fp);

		return $sql;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Save Cache
	  */
	function save_cache($sql, $identifier = '')
	{
		$tag = ($identifier == '') ? $this->EE->TMPL->tagproper : $this->EE->TMPL->tagproper.$identifier;

		$cache_dir  = APPPATH.'cache/'.$this->sql_cache_dir;
		$cache_file = $cache_dir.md5($tag.$this->uri);

		if ( ! @is_dir($cache_dir))
		{
			if ( ! @mkdir($cache_dir, DIR_WRITE_MODE))
			{
				return FALSE;
			}

			if ($fp = @fopen($cache_dir.'/index.html', FOPEN_WRITE_CREATE_DESTRUCTIVE))
			{
				fclose($fp);				
			}

			@chmod($cache_dir, DIR_WRITE_MODE);
		}

		if ( ! $fp = @fopen($cache_file, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			return FALSE;
		}

		flock($fp, LOCK_EX);
		fwrite($fp, $sql);
		flock($fp, LOCK_UN);
		fclose($fp);
		@chmod($cache_file, FILE_WRITE_MODE);

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Channel entries
	  */
	function entries()
	{
		// If the "related_categories" mode is enabled
		// we'll call the "related_categories" function
		// and bail out.

		if ($this->EE->TMPL->fetch_param('related_categories_mode') == 'yes')
		{
			return $this->related_entries();
		}
		// Onward...

		$this->initialize();

		$this->uri = ($this->query_string != '') ? $this->query_string : 'index.php';

		if ($this->enable['custom_fields'] == TRUE)
		{
			$this->fetch_custom_channel_fields();
		}

		if ($this->enable['member_data'] == TRUE)
		{
			$this->fetch_custom_member_fields();
		}

		if ($this->enable['pagination'] == TRUE)
		{
			$this->fetch_pagination_data();
		}

		$save_cache = FALSE;

		if ($this->EE->config->item('enable_sql_caching') == 'y')
		{
			if (FALSE == ($this->sql = $this->fetch_cache()))
			{
				$save_cache = TRUE;
			}
			else
			{
				if ($this->EE->TMPL->fetch_param('dynamic') != 'no')
				{
					if (preg_match("#(^|\/)C(\d+)#", $this->query_string, $match) OR in_array($this->reserved_cat_segment, explode("/", $this->query_string)))
					{
						$this->cat_request = TRUE;
					}
				}
			}

			if (FALSE !== ($cache = $this->fetch_cache('pagination_count')))
			{
				if (FALSE !== ($this->fetch_cache('field_pagination')))
				{
					if (FALSE !== ($pg_query = $this->fetch_cache('pagination_query')))
					{
						$this->paginate = TRUE;
						$this->field_pagination = TRUE;
						$this->create_pagination(trim($cache), $this->EE->db->query(trim($pg_query)));
					}
				}
				else
				{
					$this->create_pagination(trim($cache));
				}
			}
		}

		if ($this->sql == '')
		{
			$this->build_sql_query();
		}

		if ($this->sql == '')
		{
			return $this->EE->TMPL->no_results();
		}

		if ($save_cache == TRUE)
		{
			$this->save_cache($this->sql);
		}

		$this->query = $this->EE->db->query($this->sql);

		if ($this->query->num_rows() == 0)
		{
			return $this->EE->TMPL->no_results();
		}

		// -------------------------------------
		//  "Relaxed" View Tracking
		//
		//  Some people have tags that are used to mimic a single-entry
		//  page without it being dynamic. This allows Entry View Tracking
		//  to work for ANY combination that results in only one entry
		//  being returned by the tag, including channel query caching.
		//
		//  Hidden Configuration Variable
		//  - relaxed_track_views => Allow view tracking on non-dynamic
		//  	single entries (y/n)
		// -------------------------------------
		if ($this->EE->config->item('relaxed_track_views') === 'y' && $this->query->num_rows() == 1)
		{
			$this->hit_tracking_id = $this->query->row('entry_id') ;
		}

		$this->track_views();

		$this->EE->load->library('typography');
		$this->EE->typography->initialize(array(
				'convert_curly'	=> FALSE)
				);

		if ($this->enable['categories'] == TRUE)
		{
			$this->fetch_categories();
		}

		$this->parse_channel_entries();

		if ($this->enable['pagination'] == TRUE)
		{
			$this->add_pagination_data();
		}

		// Does the tag contain "related entries" that we need to parse out?

		if (count($this->EE->TMPL->related_data) > 0 && count($this->related_entries) > 0)
		{
			$this->parse_related_entries();
		}

		if (count($this->EE->TMPL->reverse_related_data) > 0 && count($this->reverse_related_entries) > 0)
		{
			$this->parse_reverse_related_entries();
		}

		return $this->return_data;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Process related entries
	  */
	function parse_related_entries()
	{
		$sql = "SELECT rel_id, rel_parent_id, rel_child_id, rel_type, rel_data
				FROM exp_relationships
				WHERE rel_id IN (";

		$templates = array();
		foreach ($this->related_entries as $val)
		{
			$x = explode('_', $val);
			$sql .= "'".$x[0]."',";
			$templates[] = array($x[0], $x[1], $this->EE->TMPL->related_data[$x[1]]);
		}

		$sql = substr($sql, 0, -1).')';
		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
			return;

		// --------------------------------
		//  Without this the Related Entries were inheriting the parameters of
		//  the enclosing Channel Entries tag.  Sometime in the future we will
		//  likely allow Related Entries to have their own parameters
		// --------------------------------

		$return_data = $this->return_data;

		foreach ($templates as $temp)
		{
			foreach ($query->result_array() as $row)
			{
				if ($row['rel_id'] != $temp[0])
					continue;

				// --------------------------------------
				//  If the data is emptied (cache cleared), then we
				//  rebuild it with fresh data so processing can continue.
				// --------------------------------------

				if (trim($row['rel_data']) == '')
				{
					$rewrite = array(
									 'type'			=> $row['rel_type'],
									 'parent_id'	=> $row['rel_parent_id'],
									 'child_id'		=> $row['rel_child_id'],
									 'related_id'	=> $row['rel_id']
								);

					$this->EE->functions->compile_relationship($rewrite, FALSE);

					$results = $this->EE->db->query("SELECT rel_data FROM exp_relationships WHERE rel_id = '".$row['rel_id']."'");
					$row['rel_data'] = $results->row('rel_data') ;
				}

				//  Begin Processing

				$this->initialize();

				if ($reldata = @unserialize($row['rel_data']))
				{
					$this->EE->TMPL->var_single	= $temp[2]['var_single'];
					$this->EE->TMPL->var_pair		= $temp[2]['var_pair'];
					$this->EE->TMPL->var_cond		= $temp[2]['var_cond'];
					$this->EE->TMPL->tagdata		= $temp[2]['tagdata'];

					if ($row['rel_type'] == 'channel')
					{
						// Bug fix for when categories were not being inserted
						// correctly for related channel entries.  Bummer.

						if (count($reldata['categories'] == 0) && ! isset($reldata['cats_fixed']))
						{
							$fixdata = array(
											'type'			=> $row['rel_type'],
											'parent_id'		=> $row['rel_parent_id'],
											'child_id'		=> $row['rel_child_id'],
											'related_id'	=> $row['rel_id']
										);

							$this->EE->functions->compile_relationship($fixdata, FALSE);
							$reldata['categories'] = $this->EE->functions->cat_array;
							$reldata['category_fields'] = $this->EE->functions->catfields;
						}

						$this->query = $reldata['query'];
						
						if ($this->query->num_rows != 0)
						{
							$this->categories = array($this->query->row('entry_id')  => $reldata['categories']);

							if (isset($reldata['category_fields']))
							{
								$this->catfields = array($this->query->row('entry_id') => $reldata['category_fields']);
							}							
						}

						$this->parse_channel_entries();

						$marker = LD."REL[".$row['rel_id']."][".$temp[2]['field_name']."]".$temp[1]."REL".RD;
						$return_data = str_replace($marker, $this->return_data, $return_data);
					}
				}
			}
		}

		$this->return_data = $return_data;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Process reverse related entries
	  */
	function parse_reverse_related_entries()
	{
		$this->EE->db->select('rel_id, rel_parent_id, rel_child_id, rel_type, reverse_rel_data');
		$this->EE->db->where_in('rel_child_id', array_keys($this->reverse_related_entries));
		$this->EE->db->where('rel_type', 'channel');
		$query = $this->EE->db->get('relationships');
		
		if ($query->num_rows() == 0)
		{
			// remove Reverse Related tags for these entries

			foreach ($this->reverse_related_entries as $entry_id => $templates)
			{
				foreach($templates as $tkey => $template)
				{
					$this->return_data = str_replace(LD."REV_REL[".$this->EE->TMPL->reverse_related_data[$template]['marker']."][".$entry_id."]REV_REL".RD, $this->EE->TMPL->reverse_related_data[$template]['no_rev_content'], $this->return_data);
				}
			}

			return;
		}

		//  Data Processing Time
		$entry_data = array();

		for ($i = 0, $total = count($query->result_array()); $i < $total; $i++)
		{
    		$row = array_shift($query->result_array);

			//  If the data is emptied (cache cleared or first process), then we
			//  rebuild it with fresh data so processing can continue.

			if (trim($row['reverse_rel_data']) == '')
			{
				$rewrite = array(
								 'type'			=> $row['rel_type'],
								 'parent_id'	=> $row['rel_parent_id'],
								 'child_id'		=> $row['rel_child_id'],
								 'related_id'	=> $row['rel_id']
							);

				$this->EE->functions->compile_relationship($rewrite, FALSE, TRUE);

				$this->EE->db->select('reverse_rel_data');
				$this->EE->db->where('rel_parent_id', $row['rel_parent_id']);
				$results = $this->EE->db->get('relationships');
				$row['reverse_rel_data'] = $results->row('reverse_rel_data');
			}

			//  Unserialize the entries data, please

			if ($revreldata = @unserialize($row['reverse_rel_data']))
			{
				$entry_data[$row['rel_child_id']][$row['rel_parent_id']] = $revreldata;
			}
		}
		
		//  Without this the Reverse Related Entries were inheriting the parameters of
		//  the enclosing Channel Entries tag, which is not appropriate.

		$return_data = $this->return_data;

		foreach ($this->reverse_related_entries as $entry_id => $templates)
		{
			//  No Entries?  Remove Reverse Related Tags and Continue to Next Entry

			if ( ! isset($entry_data[$entry_id]))
			{
				foreach($templates as $tkey => $template)
				{
					$return_data = str_replace(LD."REV_REL[".$this->EE->TMPL->reverse_related_data[$template]['marker']."][".$entry_id."]REV_REL".RD, $this->EE->TMPL->reverse_related_data[$template]['no_rev_content'], $return_data);
				}

				continue;
			}

			//  Process Our Reverse Related Templates

			foreach($templates as $tkey => $template)
			{
				$i = 0;
				$cats = array();

				$params = $this->EE->TMPL->reverse_related_data[$template]['params'];

				if ( ! is_array($params))
				{
					$params = array('status' => 'open');
				}
				elseif ( ! isset($params['status']))
				{
					$params['status'] = 'open';
				}
				else
				{
					$params['status'] = trim($params['status'], " |\t\n\r");
				}

				//  Entries have to be ordered, sorted and other stuff

				$new	= array();
				$order	= ( ! isset($params['orderby'])) ? 'date' : $params['orderby'];
				$offset	= ( ! isset($params['offset']) OR ! is_numeric($params['offset'])) ? 0 : $params['offset'];
				$limit	= ( ! isset($params['limit']) OR ! is_numeric($params['limit'])) ? 100 : $params['limit'];
				$sort	= ( ! isset($params['sort']))	 ? 'asc' : $params['sort'];
				$random = ($order == 'random') ? TRUE : FALSE;

				$base_orders = array('random', 'date', 'title', 'url_title', 'edit_date', 'comment_total', 'username', 'screen_name', 'most_recent_comment', 'expiration_date', 'entry_id', 
									 'view_count_one', 'view_count_two', 'view_count_three', 'view_count_four');

				$str_sort = array('title', 'url_title', 'username', 'screen_name');
				
				if ( ! in_array($order, $base_orders))
				{
					$set = 'n';
					foreach($this->cfields as $site_id => $cfields)
					{
						if ( isset($cfields[$order]))
						{
							$multi_order[] = 'field_id_'.$cfields[$order]; 
							$set = 'y';
							$str_sort[] = 'field_id_'.$cfields[$order];
							//break;
						}
					}

					if ( $set == 'n' )
					{
						$order = 'date';
					}
				}

				if ($order == 'date' OR $order == 'random')
				{
					$order = 'entry_date';
				}

				if (isset($params['channel']) && trim($params['channel']) != '')
				{
					if (count($this->channels_array) == 0)
					{
						$this->EE->db->select('channel_id, channel_name');
						$this->EE->db->where_in('site_id', $this->EE->TMPL->site_ids);
						$results = $this->EE->db->get('channels');

						foreach($results->result_array() as $row)
						{
							$this->channels_array[$row['channel_id']] = $row['channel_name'];
						}
					}

					$channels = explode('|', trim($params['channel']));
					$allowed = array();

					if (strncmp($channels[0], 'not ', 4) == 0)
					{
						$channels[0] = trim(substr($channels[0], 3));
						$allowed	  = $this->channels_array;

						foreach($channels as $name)
						{
							if (in_array($name, $allowed))
							{
								foreach (array_keys($allowed, $name) AS $k)
								{
									unset($allowed[$k]);
								}
							}
						}
					}
					else
					{
						foreach($channels as $name)
						{
							if (in_array($name, $this->channels_array))
							{
								foreach (array_keys($this->channels_array, $name) AS $k)
								{
									$allowed[$k] = $name;
								}
							}
						}
					}
				}


				$stati			= explode('|', $params['status']);
				$stati			= array_map('strtolower', $stati);	// match MySQL's case-insensitivity
				$status_state	= 'positive';


				// Check for "not "
				if (substr($stati[0], 0, 4) == 'not ')
				{
					$status_state = 'negative';
					$stati[0] = trim(substr($stati[0], 3));
					$stati[] = 'closed';
				}

				$r = 1;  // Fixes a problem when a sorting key occurs twice

				foreach($entry_data[$entry_id] as $relating_data)
				{
					$post_fix = ' '.$r;
					$order_set = FALSE;
					
					if ( ! isset($params['channel']) OR ($relating_data['query']->row('channel_id') &&  array_key_exists($relating_data['query']->row('channel_id'), $allowed))) 
					{
						$query_row = $relating_data['query']->row_array();
						
						if (isset($multi_order))
						{
							foreach ($multi_order as $field_val)
							{
								if (isset($query_row[$field_val]))
								{
								 	$order_set = TRUE;
									$order_key = '';
									
									if ($query_row[$field_val] != '')
									{
										$order_key = $query_row[$field_val];
										$order = $field_val;
										break;
									}
								}
							}
						}
						elseif (isset($query_row[$order]))
						{
							$order_set = TRUE;
							$order_key = $query_row[$order];
						}					

						// Needs to have the field we're ordering by
						if ($order_set)
						{
							if ($status_state == 'negative' && ! in_array(strtolower($query_row['status']) , $stati))
							{
								$new[$order_key.$post_fix] = $relating_data;
							}
							elseif (in_array(strtolower($query_row['status']) , $stati))
							{
								$new[$order_key.$post_fix] = $relating_data;
							}
						}

						++$r;
					}
				}
				
				$sort_flags = SORT_REGULAR;
				
				// Check if the custom field to sort on is numeric, sort numericaly if it is
				if (strncmp($order, 'field_id_', 9) === 0)
				{
					$this->EE->load->library('api');
					$this->EE->api->instantiate('channel_fields');
					$field_settings = $this->EE->api_channel_fields->get_settings(substr($order, 9));
					if (isset($field_settings['field_content_type']) && in_array($field_settings['field_content_type'], array('numeric', 'integer', 'decimal')))
					{
						$sort_flags = SORT_NUMERIC;
					}
				}
				
				if ($random === TRUE)
				{
					shuffle($new);
				}
				elseif ($sort == 'asc') // 1 to 10, A to Z
				{
					if (in_array($order, $str_sort))
					{
						ksort($new, $sort_flags);
					}
					else
					{
						uksort($new, 'strnatcasecmp'); 
					}
				}
				else
				{
					if (in_array($order, $str_sort))
					{
						ksort($new, $sort_flags);
					}
					else
					{
						uksort($new, 'strnatcasecmp'); 
					}
					
					$new = array_reverse($new, TRUE);
				}
				
				$output_data[$entry_id] = array_slice($new, $offset, $limit);

				if (count($output_data[$entry_id]) == 0)
				{
					$return_data = str_replace(LD."REV_REL[".$this->EE->TMPL->reverse_related_data[$template]['marker']."][".$entry_id."]REV_REL".RD, $this->EE->TMPL->reverse_related_data[$template]['no_rev_content'], $return_data);
					continue;
				}

				//  Finally!  We get to process our parents

				foreach($output_data[$entry_id] as $relating_data)
				{
					if ($i == 0)
					{
						$query = clone $relating_data['query'];
					}
					else
					{
						$query->result_array[] = $relating_data['query']->row_array();
					}

					$cats[$relating_data['query']->row('entry_id') ] = $relating_data['categories'];

					++$i;
				}

				$query->num_rows = $i;

				$this->initialize();

				$this->EE->TMPL->var_single	= $this->EE->TMPL->reverse_related_data[$template]['var_single'];
				$this->EE->TMPL->var_pair		= $this->EE->TMPL->reverse_related_data[$template]['var_pair'];
				$this->EE->TMPL->var_cond		= $this->EE->TMPL->reverse_related_data[$template]['var_cond'];
				$this->EE->TMPL->tagdata		= $this->EE->TMPL->reverse_related_data[$template]['tagdata'];

				$this->query = $query;
				$this->categories = $cats;
				$this->parse_channel_entries();

				$return_data = str_replace(	LD."REV_REL[".$this->EE->TMPL->reverse_related_data[$template]['marker']."][".$entry_id."]REV_REL".RD,
											$this->return_data,
											$return_data);
			}
		}

		$this->return_data = $return_data;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Track Views
	  */
	function track_views()
	{
		if ($this->EE->config->item('enable_entry_view_tracking') == 'n')
		{
			return;
		}
		
		if ( ! $this->EE->TMPL->fetch_param('track_views') OR $this->hit_tracking_id === FALSE)
		{
			return;
		}

		if ($this->field_pagination == TRUE AND $this->p_page > 0)
		{
			return;
		}

		foreach (explode('|', $this->EE->TMPL->fetch_param('track_views')) as $view)
		{
			if ( ! in_array(strtolower($view), array("one", "two", "three", "four")))
			{
				continue;
			}

			$sql = "UPDATE exp_channel_titles SET view_count_{$view} = (view_count_{$view} + 1) WHERE ";
			$sql .= (is_numeric($this->hit_tracking_id)) ? "entry_id = {$this->hit_tracking_id}" : "url_title = '".$this->EE->db->escape_str($this->hit_tracking_id)."'";

			$this->EE->db->query($sql);
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Fetch pagination data
	  */
	function fetch_pagination_data()
	{
		if (strpos($this->EE->TMPL->tagdata, LD.'paginate'.RD) === FALSE) return;

		if (preg_match("/".LD."paginate".RD."(.+?)".LD.'\/'."paginate".RD."/s", $this->EE->TMPL->tagdata, $match))
		{
			if ($this->EE->TMPL->fetch_param('paginate_type') == 'field')
			{
				if (preg_match("/".LD."multi_field\=[\"'](.+?)[\"']".RD."/s", $this->EE->TMPL->tagdata, $mmatch))
				{
					$this->multi_fields = $this->EE->functions->fetch_simple_conditions($mmatch[1]);
					$this->field_pagination = TRUE;
				}
			}

			// -------------------------------------------
			// 'channel_module_fetch_pagination_data' hook.
			//  - Works with the 'channel_module_create_pagination' hook
			//  - Developers, if you want to modify the $this object remember
			//	to use a reference on function call.
			//
				if ($this->EE->extensions->active_hook('channel_module_fetch_pagination_data') === TRUE)
				{
					$edata = $this->EE->extensions->universal_call('channel_module_fetch_pagination_data', $this);
					if ($this->EE->extensions->end_script === TRUE) return;
				}
			//
			// -------------------------------------------
			
			$this->paginate	= TRUE;
			$this->paginate_data = $match[1];

			$this->EE->TMPL->tagdata = preg_replace("/".LD."paginate".RD.".+?".LD.'\/'."paginate".RD."/s", "", $this->EE->TMPL->tagdata);
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Add pagination data to result
	  */
	function add_pagination_data()
	{
		if ($this->pagination_links == '')
		{
			return;
		}

		if ($this->paginate == TRUE)
		{
			// Parse current_page and total_pages by default
			$parse_array = array(
				'current_page' => $this->current_page,
				'total_pages' => $this->total_pages,
			);

			// Check to see if pagination_links is being used as a single 
			// variable or as a variable pair
			if (preg_match_all("/".LD."pagination_links".RD."(.+?)".LD.'\/'."pagination_links".RD."/s", $this->paginate_data, $matches))
			{
				$parse_array['pagination_links'] = array($this->pagination_array);
			}
			else
			{
				$parse_array['pagination_links'] = $this->pagination_links;
			}
			
			// Parse current_page and total_pages
			$this->paginate_data = $this->EE->TMPL->parse_variables(
				$this->paginate_data,
				array($parse_array)
			);
			
			if (preg_match_all("/".LD."if previous_page".RD."(.+?)".LD.'\/'."if".RD."/s", $this->paginate_data, $matches))
			{
				if ($this->page_previous == '')
				{
					 $this->paginate_data = preg_replace("/".LD."if previous_page".RD.".+?".LD.'\/'."if".RD."/s", '', $this->paginate_data);
				}
				else
				{
					foreach($matches[1] as $count => $match)
					{					
						$match = preg_replace("/".LD.'path.*?'.RD."/", 	$this->page_previous, $match);
						$match = preg_replace("/".LD.'auto_path'.RD."/", $this->page_previous, $match);

						$this->paginate_data = str_replace($matches[0][$count], $match, $this->paginate_data);
					}
				}
			}

			if (preg_match_all("/".LD."if next_page".RD."(.+?)".LD.'\/'."if".RD."/s", $this->paginate_data, $matches))
			{
				if ($this->page_next == '')
				{
					 $this->paginate_data = preg_replace("/".LD."if next_page".RD.".+?".LD.'\/'."if".RD."/s", '', $this->paginate_data);
				}
				else
				{
					foreach ($matches[1] as $count => $match)
					{
						$match = preg_replace("/".LD.'path.*?'.RD."/", 	$this->page_next, $match);
						$match = preg_replace("/".LD.'auto_path'.RD."/", $this->page_next, $match);

						$this->paginate_data = str_replace($matches[0][$count],	$match, $this->paginate_data);
					}					
				}
			}
			
			$this->paginate_data = $this->EE->functions->prep_conditionals($this->paginate_data, array('total_pages' => $this->total_pages));

			$position = ( ! $this->EE->TMPL->fetch_param('paginate')) ? '' : $this->EE->TMPL->fetch_param('paginate');

			switch ($position)
			{
				case "top"	: $this->return_data  = $this->paginate_data.$this->return_data;
					break;
				case "both"	: $this->return_data  = $this->paginate_data.$this->return_data.$this->paginate_data;
					break;
				default		: $this->return_data .= $this->paginate_data;
					break;
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Fetch custom channel field IDs
	  */
	function fetch_custom_channel_fields()
	{
		if (isset($this->EE->session->cache['channel']['custom_channel_fields']) && isset($this->EE->session->cache['channel']['date_fields'])
			&& isset($this->EE->session->cache['channel']['relationship_fields'])  && isset($this->EE->session->cache['channel']['pair_custom_fields']))
		{
			$this->cfields = $this->EE->session->cache['channel']['custom_channel_fields'];
			$this->dfields = $this->EE->session->cache['channel']['date_fields'];
			$this->rfields = $this->EE->session->cache['channel']['relationship_fields'];
			$this->pfields = $this->EE->session->cache['channel']['pair_custom_fields'];
			return;
		}
		
		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_fields');

		$fields = $this->EE->api_channel_fields->fetch_custom_channel_fields();

		$this->cfields = $fields['custom_channel_fields'];
		$this->dfields = $fields['date_fields'];
		$this->rfields = $fields['relationship_fields'];
		$this->pfields = $fields['pair_custom_fields'];

  		$this->EE->session->cache['channel']['custom_channel_fields']	= $this->cfields;
		$this->EE->session->cache['channel']['date_fields']				= $this->dfields;
		$this->EE->session->cache['channel']['relationship_fields']		= $this->rfields;
		$this->EE->session->cache['channel']['pair_custom_fields']		= $this->pfields;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Fetch custom member field IDs
	  */
	function fetch_custom_member_fields()
	{
		$this->EE->db->select('m_field_id, m_field_name, m_field_fmt');
		$query = $this->EE->db->get('member_fields');

		$fields_present = FALSE;

		$t1 = microtime(TRUE);

		foreach ($query->result_array() as $row)
		{
			if (strpos($this->EE->TMPL->tagdata, $row['m_field_name']) !== FALSE)
			{
				$fields_present = TRUE;
			}

			$this->mfields[$row['m_field_name']] = array($row['m_field_id'], $row['m_field_fmt']);
		}

		// If we can find no instance of the variable, then let's not process them at all.

		if ($fields_present === FALSE)
		{
			$this->mfields = array();
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Fetch categories
	  */
	function fetch_categories()
	{
		if ($this->enable['category_fields'] === TRUE)
		{
			$query = $this->EE->db->query("SELECT field_id, field_name FROM exp_category_fields WHERE site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."')");

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
				}
			}

			$field_sqla = ", cg.field_html_formatting, fd.* ";
			$field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id
							LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id";
		}
		else
		{
			$field_sqla = '';
			$field_sqlb = '';
		}

		$sql = "SELECT c.cat_name, c.cat_url_title, c.cat_id, c.cat_image, c.cat_description, c.parent_id,
						p.cat_id, p.entry_id, c.group_id {$field_sqla}
				FROM	(exp_categories AS c, exp_category_posts AS p)
				{$field_sqlb}
				WHERE	c.cat_id = p.cat_id
				AND		p.entry_id IN (";

		$categories = array();

		foreach ($this->query->result_array() as $row)
		{
			$sql .= "'".$row['entry_id']."',";

			$categories[] = $row['entry_id'];
		}

		$sql = substr($sql, 0, -1).')';

		$sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return;
		}

		foreach ($categories as $val)
		{
			$this->temp_array = array();
			$this->cat_array  = array();
			$parents = array();

			foreach ($query->result_array() as $row)
			{
				if ($val == $row['entry_id'])
				{
					$this->temp_array[$row['cat_id']] = array($row['cat_id'], $row['parent_id'], $row['cat_name'], $row['cat_image'], $row['cat_description'], $row['group_id'], $row['cat_url_title']);

					foreach ($row as $k => $v)
					{
						if (strpos($k, 'field') !== FALSE)
						{
							$this->temp_array[$row['cat_id']][$k] = $v;
						}
					}

					if ($row['parent_id'] > 0 && ! isset($this->temp_array[$row['parent_id']])) $parents[$row['parent_id']] = '';
					unset($parents[$row['cat_id']]);
				}
			}

			if (count($this->temp_array) == 0)
			{
				$temp = FALSE;
			}
			else
			{
				foreach($this->temp_array as $k => $v)
				{
					if (isset($parents[$v[1]])) $v[1] = 0;

					if (0 == $v[1])
					{
						$this->cat_array[] = $v;
						$this->process_subcategories($k);
					}
				}
			}

			$this->categories[$val] = $this->cat_array;
		}

		unset($this->temp_array);
		unset($this->cat_array);
	}

	// ------------------------------------------------------------------------
	
	/**
	  *  Build SQL query
	  */
	function build_sql_query($qstring = '')
	{
		$entry_id		= '';
		$year			= '';
		$month			= '';
		$day			= '';
		$qtitle			= '';
		$cat_id			= '';
		$corder			= array();
		$offset			=  0;
		$page_marker	= FALSE;
		$dynamic		= TRUE;

		$this->dynamic_sql = TRUE;

		/**------
		/**  Is dynamic='off' set?
		/**------*/

		// If so, we'll override all dynamically set variables

		if ($this->EE->TMPL->fetch_param('dynamic') == 'no')
		{
			$dynamic = FALSE;
		}

		/**------
		/**  Do we allow dynamic POST variables to set parameters?
		/**------*/
		if ($this->EE->TMPL->fetch_param('dynamic_parameters') !== FALSE AND isset($_POST) AND count($_POST) > 0)
		{
			foreach (explode('|', $this->EE->TMPL->fetch_param('dynamic_parameters')) as $var)
			{
				if (isset($_POST[$var]) AND in_array($var, array('channel', 'entry_id', 'category', 'orderby', 'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from', 'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month', 'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'cat_limit', 'month_limit', 'offset', 'author_id')))
				{
					$this->EE->TMPL->tagparams[$var] = $_POST[$var];
				}

				if (isset($_POST[$var]) && strncmp($var, 'search:', 7) == 0)
				{
					$this->EE->TMPL->search_fields[substr($var, 7)] = $_POST[$var];
				}
			}
		}

		/**------
		/**  Parse the URL query string
		/**------*/

		$this->uristr = $this->EE->uri->uri_string;

		if ($qstring == '')
			$qstring = $this->query_string;

		$this->basepath = $this->EE->functions->create_url($this->uristr);

		if ($qstring == '')
		{
			if ($this->EE->TMPL->fetch_param('require_entry') == 'yes')
			{
				return '';
			}
		}
		else
		{
			/** --------------------------------------
			/**  Do we have a pure ID number?
			/** --------------------------------------*/

			if ($dynamic && is_numeric($qstring))
			{
				$entry_id = $qstring;
			}
			else
			{
				// Load the string helper
				$this->EE->load->helper('string');

				/** --------------------------------------
				/**  Parse day
				/** --------------------------------------*/

				if ($dynamic && preg_match("#(^|\/)(\d{4}/\d{2}/\d{2})#", $qstring, $match))
				{
					$ex = explode('/', $match[2]);

					$year  = $ex[0];
					$month = $ex[1];
					$day   = $ex[2];

					$qstring = trim_slashes(str_replace($match[0], '', $qstring));
				}

				/** --------------------------------------
				/**  Parse /year/month/
				/** --------------------------------------*/

				// added (^|\/) to make sure this doesn't trigger with url titles like big_party_2006
				if ($dynamic && preg_match("#(^|\/)(\d{4}/\d{2})(\/|$)#", $qstring, $match))
				{
					$ex = explode('/', $match[2]);

					$year	= $ex[0];
					$month	= $ex[1];

					$qstring = trim_slashes(str_replace($match[2], '', $qstring));

					// Removed this in order to allow archive pagination
					// $this->paginate = FALSE;
				}

				/** --------------------------------------
				/**  Parse ID indicator
				/** --------------------------------------*/
				if ($dynamic && preg_match("#^(\d+)(.*)#", $qstring, $match))
				{
					$seg = ( ! isset($match[2])) ? '' : $match[2];

					if (substr($seg, 0, 1) == "/" OR $seg == '')
					{
						$entry_id = $match[1];
						$qstring = trim_slashes(preg_replace("#^".$match[1]."#", '', $qstring));
					}
				}

				/** --------------------------------------
				/**  Parse page number
				/** --------------------------------------*/

				if (($dynamic OR $this->EE->TMPL->fetch_param('paginate')) && preg_match("#^P(\d+)|/P(\d+)#", $qstring, $match)) 
				{
					$this->p_page = (isset($match[2])) ? $match[2] : $match[1];

					$this->basepath = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $this->basepath));

					$this->uristr  = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $this->uristr));

					$qstring = trim_slashes(str_replace($match[0], '', $qstring));

					$page_marker = TRUE;
				}

				/** --------------------------------------
				/**  Parse category indicator
				/** --------------------------------------*/

				// Text version of the category

				if ($qstring != '' AND $this->reserved_cat_segment != '' AND in_array($this->reserved_cat_segment, explode("/", $qstring)) AND $dynamic AND $this->EE->TMPL->fetch_param('channel'))
				{
					$qstring = preg_replace("/(.*?)\/".preg_quote($this->reserved_cat_segment)."\//i", '', '/'.$qstring);

					$sql = "SELECT DISTINCT cat_group FROM exp_channels WHERE site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') AND ";

					$xsql = $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('channel'), 'channel_name');

					if (substr($xsql, 0, 3) == 'AND') $xsql = substr($xsql, 3);

					$sql .= ' '.$xsql;

					$query = $this->EE->db->query($sql);

					if ($query->num_rows() > 0)
					{
						$valid = 'y';
						$last  = explode('|', $query->row('cat_group') );
						$valid_cats = array();

						foreach($query->result_array() as $row)
						{
							if ($this->EE->TMPL->fetch_param('relaxed_categories') == 'yes')
							{
								$valid_cats = array_merge($valid_cats, explode('|', $row['cat_group']));
							}
							else
							{
								$valid_cats = array_intersect($last, explode('|', $row['cat_group']));
							}

							$valid_cats = array_unique($valid_cats);

							if (count($valid_cats) == 0)
							{
								$valid = 'n';
								break;
							}
						}
					}
					else
					{
						$valid = 'n';
					}

					if ($valid == 'y')
					{
						// the category URL title should be the first segment left at this point in $qstring,
						// but because prior to this feature being added, category names were used in URLs,
						// and '/' is a valid character for category names.  If they have not updated their
						// category url titles since updating to 1.6, their category URL title could still
						// contain a '/'.  So we'll try to get the category the correct way first, and if
						// it fails, we'll try the whole $qstring

						// do this as separate commands to work around a PHP 5.0.x bug
						$arr = explode('/', $qstring);
						$cut_qstring = array_shift($arr);
						unset($arr);

						$result = $this->EE->db->query("SELECT cat_id FROM exp_categories
											  WHERE cat_url_title='".$this->EE->db->escape_str($cut_qstring)."'
											  AND group_id IN ('".implode("','", $valid_cats)."')");

						if ($result->num_rows() == 1)
						{
							$qstring = str_replace($cut_qstring, 'C'.$result->row('cat_id') , $qstring);
						}
						else
						{
							// give it one more try using the whole $qstring
							$result = $this->EE->db->query("SELECT cat_id FROM exp_categories
												  WHERE cat_url_title='".$this->EE->db->escape_str($qstring)."'
												  AND group_id IN ('".implode("','", $valid_cats)."')");

							if ($result->num_rows() == 1)
							{
								$qstring = 'C'.$result->row('cat_id') ;
							}
						}
					}
				}

				// Numeric version of the category

				if ($dynamic && preg_match("#(^|\/)C(\d+)#", $qstring, $match))
				{
					$this->cat_request = TRUE;

					$cat_id = $match[2];

					$qstring = trim_slashes(str_replace($match[0], '', $qstring));
				}

				/** --------------------------------------
				/**  Remove "N"
				/** --------------------------------------*/

				// The recent comments feature uses "N" as the URL indicator
				// It needs to be removed if presenst

				if (preg_match("#^N(\d+)|/N(\d+)#", $qstring, $match))
				{
					$this->uristr  = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $this->uristr));

					$qstring = trim_slashes(str_replace($match[0], '', $qstring));
				}

				/** --------------------------------------
				/**  Parse URL title
				/** --------------------------------------*/
				if (($cat_id == '' AND $year == '') OR $this->EE->TMPL->fetch_param('require_entry') == 'yes')
				{
					if (strpos($qstring, '/') !== FALSE)
					{
						$xe = explode('/', $qstring);
						$qstring = current($xe);
					}

					if ($dynamic == TRUE)
					{
						$sql = "SELECT count(*) AS count
								FROM  exp_channel_titles, exp_channels
								WHERE exp_channel_titles.channel_id = exp_channels.channel_id";

						if ($entry_id != '')
						{
							$sql .= " AND exp_channel_titles.entry_id = '".$this->EE->db->escape_str($entry_id)."'";							
						}
						else
						{
							$sql .= " AND exp_channel_titles.url_title = '".$this->EE->db->escape_str($qstring)."'";
						}

						$sql .= " AND exp_channels.site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";

						$query = $this->EE->db->query($sql);

						if ($query->row('count')  == 0)
						{
							if ($this->EE->TMPL->fetch_param('require_entry') == 'yes')
							{
								return '';
							}

							$qtitle = '';
						}
						else
						{
							$qtitle = $qstring;
						}
					}
				}
			}
		}


		/**------
		/**  Entry ID number
		/**------*/

		// If the "entry ID" was hard-coded, use it instead of
		// using the dynamically set one above

		if ($this->EE->TMPL->fetch_param('entry_id'))
		{
			$entry_id = $this->EE->TMPL->fetch_param('entry_id');
		}

		/**------
		/**  Only Entries with Pages
		/**------*/

		if ($this->EE->TMPL->fetch_param('show_pages') !== FALSE && in_array($this->EE->TMPL->fetch_param('show_pages'), array('only', 'no')) && ($pages = $this->EE->config->item('site_pages')) !== FALSE)
		{
			$pages_uris = array();
			
			foreach ($pages as $data)
			{
				$pages_uris += $data['uris'];
			}
			
			if (count($pages_uris) > 0 OR $this->EE->TMPL->fetch_param('show_pages') == 'only')
			{
				// consider entry_id
				if ($this->EE->TMPL->fetch_param('entry_id') !== FALSE)
				{
					$not = FALSE;

					if (strncmp($entry_id, 'not', 3) == 0)
					{
						$not = TRUE;
						$entry_id = trim(substr($entry_id, 3));
					}

					$ids = explode('|', $entry_id);

					if ($this->EE->TMPL->fetch_param('show_pages') == 'only')
					{
						if ($not === TRUE)
						{
							$entry_id = implode('|', array_diff(array_flip($pages_uris), explode('|', $ids)));
						}
						else
						{
							$entry_id = implode('|',array_diff($ids, array_diff($ids, array_flip($pages_uris))));
						}
					}
					else
					{
						if ($not === TRUE)
						{
							$entry_id = "not {$entry_id}|".implode('|', array_flip($pages_uris));
						}
						else
						{
							$entry_id = implode('|',array_diff($ids, array_flip($pages_uris)));
						}
					}
				}
				else
				{
					$entry_id = (($this->EE->TMPL->fetch_param('show_pages') == 'no') ? 'not ' : '').implode('|', array_flip($pages_uris));
				}
			
				//  No pages and show_pages only
				if ($entry_id == '' && $this->EE->TMPL->fetch_param('show_pages') == 'only')
				{
					$this->sql = '';
					return;
				}
			}			
		}

		/**------
		/**  Assing the order variables
		/**------*/

		$order  = $this->EE->TMPL->fetch_param('orderby');
		$sort	= $this->EE->TMPL->fetch_param('sort');
		$sticky = $this->EE->TMPL->fetch_param('sticky');

		/** -------------------------------------
		/**  Multiple Orders and Sorts...
		/** -------------------------------------*/

		if ($order !== FALSE && stristr($order, '|'))
		{
			$order_array = explode('|', $order);

			if ($order_array[0] == 'random')
			{
				$order_array = array('random');
			}
		}
		else
		{
			$order_array = array($order);
		}		

		if ($sort !== FALSE && stristr($sort, '|'))
		{
			$sort_array = explode('|', $sort);
		}
		else
		{
			$sort_array = array($sort);
		}

		/** -------------------------------------
		/**  Validate Results for Later Processing
		/** -------------------------------------*/

		$base_orders = array('random', 'entry_id', 'date', 'entry_date', 'title', 'url_title', 'edit_date', 'comment_total', 'username', 'screen_name', 'most_recent_comment', 'expiration_date',
							 'view_count_one', 'view_count_two', 'view_count_three', 'view_count_four');

		foreach($order_array as $key => $order)
		{
			if ( ! in_array($order, $base_orders))
			{
				if (FALSE !== $order)
				{
					$set = 'n';

					/** -------------------------------------
					/**  Site Namespace is Being Used, Parse Out
					/** -------------------------------------*/

					if (strpos($order, ':') !== FALSE)
					{
						$order_parts = explode(':', $order, 2);

						if (isset($this->EE->TMPL->site_ids[$order_parts[0]]) && isset($this->cfields[$this->EE->TMPL->site_ids[$order_parts[0]]][$order_parts[1]]))
						{
							$corder[$key] = $this->cfields[$this->EE->TMPL->site_ids[$order_parts[0]]][$order_parts[1]];
							$order_array[$key] = 'custom_field';
							$set = 'y';
						}
					}

					/** -------------------------------------
					/**  Find the Custom Field, Cycle Through All Sites for Tag
					/**  - If multiple sites have the same short_name for a field, we do a CONCAT ORDERBY in query
					/** -------------------------------------*/

					if ($set == 'n')
					{
						foreach($this->cfields as $site_id => $cfields)
						{
							// Only those sites specified
							if ( ! in_array($site_id, $this->EE->TMPL->site_ids))
							{
								continue;
							}

							if (isset($cfields[$order]))
							{
								if ($set == 'y')
								{
									$corder[$key] .= '|'.$cfields[$order];
								}
								else
								{
									$corder[$key] = $cfields[$order];
									$order_array[$key] = 'custom_field';
									$set = 'y';
								}
							}
						}
					}

					if ($set == 'n')
					{
						$order_array[$key] = FALSE;
					}
				}
			}

			if ( ! isset($sort_array[$key]))
			{
				$sort_array[$key] = 'desc';
			}
		}

		foreach($sort_array as $key => $sort)
		{
			if ($sort == FALSE OR ($sort != 'asc' AND $sort != 'desc'))
			{
				$sort_array[$key] = "desc";
			}
		}

		// fixed entry id ordering
		if (($fixed_order = $this->EE->TMPL->fetch_param('fixed_order')) === FALSE OR preg_match('/[^0-9\|]/', $fixed_order))
		{
			$fixed_order = FALSE;
		}
		else
		{
			// MySQL will not order the entries correctly unless the results are constrained
			// to matching rows only, so we force the entry_id as well
			$entry_id = $fixed_order;
			$fixed_order = preg_split('/\|/', $fixed_order, -1, PREG_SPLIT_NO_EMPTY);

			// some peeps might want to be able to 'flip' it
			// the default sort order is 'desc' but in this context 'desc' has a stronger "reversing"
			// connotation, so we look not at the sort array, but the tag parameter itself, to see the user's intent
			if ($sort == 'desc')
			{
				$fixed_order = array_reverse($fixed_order);
			}
		}

		/**------
		/**  Build the master SQL query
		/**------*/

		$sql_a = "SELECT ";

		$sql_b = ($this->EE->TMPL->fetch_param('category') OR $this->EE->TMPL->fetch_param('category_group') OR $cat_id != '' OR $order_array[0] == 'random') ? "DISTINCT(t.entry_id) " : "t.entry_id ";

		if ($this->field_pagination == TRUE)
		{
			$sql_b .= ",wd.* ";
		}

		$sql_c = "COUNT(t.entry_id) AS count ";

		$sql = "FROM exp_channel_titles AS t
				LEFT JOIN exp_channels ON t.channel_id = exp_channels.channel_id ";

		if ($this->field_pagination == TRUE)
		{
			$sql .= "LEFT JOIN exp_channel_data AS wd ON t.entry_id = wd.entry_id ";
		}
		elseif (in_array('custom_field', $order_array))
		{
			$sql .= "LEFT JOIN exp_channel_data AS wd ON t.entry_id = wd.entry_id ";
		}
		elseif ( ! empty($this->EE->TMPL->search_fields))
		{
			$sql .= "LEFT JOIN exp_channel_data AS wd ON wd.entry_id = t.entry_id ";
		}

		$sql .= "LEFT JOIN exp_members AS m ON m.member_id = t.author_id ";


		if ($this->EE->TMPL->fetch_param('category') OR $this->EE->TMPL->fetch_param('category_group') OR $cat_id != '')
		{
			/* --------------------------------
			/*  We use LEFT JOIN when there is a 'not' so that we get
			/*  entries that are not assigned to a category.
			/* --------------------------------*/

			if ((substr($this->EE->TMPL->fetch_param('category_group'), 0, 3) == 'not' OR substr($this->EE->TMPL->fetch_param('category'), 0, 3) == 'not') && $this->EE->TMPL->fetch_param('uncategorized_entries') !== 'n')
			{
				$sql .= "LEFT JOIN exp_category_posts ON t.entry_id = exp_category_posts.entry_id
						 LEFT JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ";
			}
			else
			{
				$sql .= "INNER JOIN exp_category_posts ON t.entry_id = exp_category_posts.entry_id
						 INNER JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ";
			}
		}

		$sql .= "WHERE t.entry_id !='' AND t.site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";

		/**------
		/**  We only select entries that have not expired
		/**------*/
		
		$timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->TMPL->cache_timestamp : $this->EE->localize->now;

		if ($this->EE->TMPL->fetch_param('show_future_entries') != 'yes')
		{
			$sql .= " AND t.entry_date < ".$timestamp." ";
		}

		if ($this->EE->TMPL->fetch_param('show_expired') != 'yes')
		{
			$sql .= " AND (t.expiration_date = 0 OR t.expiration_date > ".$timestamp.") ";
		}

		/**------
		/**  Limit query by post ID for individual entries
		/**------*/

		if ($entry_id != '')
		{
			$sql .= $this->EE->functions->sql_andor_string($entry_id, 't.entry_id').' ';
		}

		/**------
		/**  Limit query by post url_title for individual entries
		/**------*/

		if ($url_title = $this->EE->TMPL->fetch_param('url_title'))
		{
			$sql .= $this->EE->functions->sql_andor_string($url_title, 't.url_title').' ';
		}

		/**------
		/**  Limit query by entry_id range
		/**------*/

		if ($entry_id_from = $this->EE->TMPL->fetch_param('entry_id_from'))
		{
			$sql .= "AND t.entry_id >= '$entry_id_from' ";
		}

		if ($entry_id_to = $this->EE->TMPL->fetch_param('entry_id_to'))
		{
			$sql .= "AND t.entry_id <= '$entry_id_to' ";
		}

		/**------
		/**  Exclude an individual entry
		/**------*/
		if ($not_entry_id = $this->EE->TMPL->fetch_param('not_entry_id'))
		{
			$sql .= ( ! is_numeric($not_entry_id))
					? "AND t.url_title != '{$not_entry_id}' "
					: "AND t.entry_id  != '{$not_entry_id}' ";
		}

		/**------
		/**  Limit to/exclude specific channels
		/**------*/

		if ($channel = $this->EE->TMPL->fetch_param('channel'))
		{
			$xql = "SELECT channel_id FROM exp_channels WHERE ";

			$str = $this->EE->functions->sql_andor_string($channel, 'channel_name');

			if (substr($str, 0, 3) == 'AND')
			{
				$str = substr($str, 3);				
			}

			$xql .= $str;

			$query = $this->EE->db->query($xql);

			if ($query->num_rows() == 0)
			{
				return '';
			}
			else
			{
				if ($query->num_rows() == 1)
				{
					$sql .= "AND t.channel_id = '".$query->row('channel_id') ."' ";
				}
				else
				{
					$sql .= "AND (";

					foreach ($query->result_array() as $row)
					{
						$sql .= "t.channel_id = '".$row['channel_id']."' OR ";
					}

					$sql = substr($sql, 0, - 3);

					$sql .= ") ";
				}
			}
		}

		/**------------
		/**  Limit query by date range given in tag parameters
		/**------------*/
		if ($this->EE->TMPL->fetch_param('start_on'))
		{
			$sql .= "AND t.entry_date >= '".$this->EE->localize->convert_human_date_to_gmt($this->EE->TMPL->fetch_param('start_on'))."' ";			
		}

		if ($this->EE->TMPL->fetch_param('stop_before'))
		{
			$sql .= "AND t.entry_date < '".$this->EE->localize->convert_human_date_to_gmt($this->EE->TMPL->fetch_param('stop_before'))."' ";	
		}

		/**-------------
		/**  Limit query by date contained in tag parameters
		/**-------------*/

		if ($this->EE->TMPL->fetch_param('year') OR $this->EE->TMPL->fetch_param('month') OR $this->EE->TMPL->fetch_param('day'))
		{
			$year	= ( ! is_numeric($this->EE->TMPL->fetch_param('year'))) 	? date('Y') : $this->EE->TMPL->fetch_param('year');
			$smonth	= ( ! is_numeric($this->EE->TMPL->fetch_param('month')))	? '01' : $this->EE->TMPL->fetch_param('month');
			$emonth	= ( ! is_numeric($this->EE->TMPL->fetch_param('month')))	? '12':  $this->EE->TMPL->fetch_param('month');
			$day	= ( ! is_numeric($this->EE->TMPL->fetch_param('day')))		? '' : $this->EE->TMPL->fetch_param('day');

			if ($day != '' AND ! is_numeric($this->EE->TMPL->fetch_param('month')))
			{
				$smonth = date('m');
				$emonth = date('m');
			}

			if (strlen($smonth) == 1) 
			{
				$smonth = '0'.$smonth;
			}
			
			if (strlen($emonth) == 1) 
			{
				$emonth = '0'.$emonth;
			}
			
			if ($day == '')
			{
				$sday = 1;
				$eday = $this->EE->localize->fetch_days_in_month($emonth, $year);
			}
			else
			{
				$sday = $day;
				$eday = $day;
			}

			$stime = gmmktime(0, 0, 0, $smonth, $sday, $year);
			$etime = gmmktime(23, 59, 59, $emonth, $eday, $year);

			$sql .= " AND t.entry_date >= ".$stime." AND t.entry_date <= ".$etime." ";
		}
		else
		{
			/**--------
			/**  Limit query by date in URI: /2003/12/14/
			/**---------*/

			if ($year != '' AND $month != '' AND $dynamic == TRUE)
			{
				if ($day == '')
				{
					$sday = 1;
					$eday = $this->EE->localize->fetch_days_in_month($month, $year);
				}
				else
				{
					$sday = $day;
					$eday = $day;
				}

				$stime = gmmktime(0, 0, 0, $month, $sday, $year);
				$etime = gmmktime(23, 59, 59, $month, $eday, $year);

				if (date("I", $this->EE->localize->now) AND ! date("I", $stime))
				{
					$stime -= 3600;
				}
				elseif ( ! date("I", $this->EE->localize->now) AND date("I", $stime))
				{
					$stime += 3600;
				}

				$stime += $this->EE->localize->set_localized_offset();

				if (date("I", $this->EE->localize->now) AND ! date("I", $etime))
				{
					$etime -= 3600;
				}
				elseif ( ! date("I", $this->EE->localize->now) AND date("I", $etime))
				{
					$etime += 3600;
				}

				$etime += $this->EE->localize->set_localized_offset();

				$sql .= " AND t.entry_date >= ".$stime." AND t.entry_date <= ".$etime." ";
			}
			else
			{
				$this->display_by = $this->EE->TMPL->fetch_param('display_by');

				$lim = ( ! is_numeric($this->EE->TMPL->fetch_param('limit'))) ? '1' : $this->EE->TMPL->fetch_param('limit');

				/**---
				/**  If display_by = "month"
				/**---*/

				if ($this->display_by == 'month')
				{
					// We need to run a query and fetch the distinct months in which there are entries

					$dql = "SELECT t.year, t.month ".$sql;

					/**------
					/**  Add status declaration
					/**------*/

					if ($status = $this->EE->TMPL->fetch_param('status'))
					{
						$status = str_replace('Open',	'open',	$status);
						$status = str_replace('Closed', 'closed', $status);

						$sstr = $this->EE->functions->sql_andor_string($status, 't.status');

						if (stristr($sstr, "'closed'") === FALSE)
						{
							$sstr .= " AND t.status != 'closed' ";
						}

						$dql .= $sstr;
					}
					else
					{
						$dql .= "AND t.status = 'open' ";
					}

					$query = $this->EE->db->query($dql);

					$distinct = array();

					if ($query->num_rows() > 0)
					{
						foreach ($query->result_array() as $row)
						{
							$distinct[] = $row['year'].$row['month'];
						}

						$distinct = array_unique($distinct);

						sort($distinct);

						if ($sort_array[0] == 'desc')
						{
							$distinct = array_reverse($distinct);
						}

						$this->total_rows = count($distinct);

						$cur = ($this->p_page == '') ? 0 : $this->p_page;

						$distinct = array_slice($distinct, $cur, $lim);

						if ($distinct != FALSE)
						{
							$sql .= "AND (";

							foreach ($distinct as $val)
							{
								$sql .= "(t.year  = '".substr($val, 0, 4)."' AND t.month = '".substr($val, 4, 2)."') OR";
							}

							$sql = substr($sql, 0, -2).')';
						}
					}
				}


				/**---
				/**  If display_by = "day"
				/**---*/

				elseif ($this->display_by == 'day')
				{
					// We need to run a query and fetch the distinct days in which there are entries

					$dql = "SELECT t.year, t.month, t.day ".$sql;

					/**------
					/**  Add status declaration
					/**------*/

					if ($status = $this->EE->TMPL->fetch_param('status'))
					{
						$status = str_replace('Open',	'open',	$status);
						$status = str_replace('Closed', 'closed', $status);

						$sstr = $this->EE->functions->sql_andor_string($status, 't.status');

						if (stristr($sstr, "'closed'") === FALSE)
						{
							$sstr .= " AND t.status != 'closed' ";
						}

						$dql .= $sstr;
					}
					else
					{
						$dql .= "AND t.status = 'open' ";
					}

					$query = $this->EE->db->query($dql);

					$distinct = array();

					if ($query->num_rows() > 0)
					{
						foreach ($query->result_array() as $row)
						{
							$distinct[] = $row['year'].$row['month'].$row['day'];
						}

						$distinct = array_unique($distinct);
						sort($distinct);

						if ($sort_array[0] == 'desc')
						{
							$distinct = array_reverse($distinct);
						}

						$this->total_rows = count($distinct);

						$cur = ($this->p_page == '') ? 0 : $this->p_page;

						$distinct = array_slice($distinct, $cur, $lim);

						if ($distinct != FALSE)
						{
							$sql .= "AND (";

							foreach ($distinct as $val)
							{
								$sql .= "(t.year  = '".substr($val, 0, 4)."' AND t.month = '".substr($val, 4, 2)."' AND t.day	= '".substr($val, 6)."' ) OR";
							}

							$sql = substr($sql, 0, -2).')';
						}
					}
				}

				/**---
				/**  If display_by = "week"
				/**---*/

				elseif ($this->display_by == 'week')
				{
					/** ---------------------------------
					/*	 Run a Query to get a combined Year and Week value.  There is a downside
					/*	 to this approach and that is the lack of localization and use of DST for
					/*	 dates.  Unfortunately, without making a complex and ultimately fubar'ed
					/*	PHP script this is the best approach possible.
					/*  ---------------------------------*/

					$loc_offset = $this->EE->localize->zones[$this->EE->config->item('server_timezone')] * 3600;

					if ($this->EE->TMPL->fetch_param('start_day') === 'Monday')
					{
						$yearweek = "DATE_FORMAT(FROM_UNIXTIME(entry_date + {$loc_offset}), '%x%v') AS yearweek ";
						$dql = 'SELECT '.$yearweek.$sql;
					}
					else
					{
						$yearweek = "DATE_FORMAT(FROM_UNIXTIME(entry_date + {$loc_offset}), '%X%V') AS yearweek ";
						$dql = 'SELECT '.$yearweek.$sql;
					}

					/**------
					/**  Add status declaration
					/**------*/

					if ($status = $this->EE->TMPL->fetch_param('status'))
					{
						$status = str_replace('Open',	'open',	$status);
						$status = str_replace('Closed', 'closed', $status);

						$sstr = $this->EE->functions->sql_andor_string($status, 't.status');

						if (stristr($sstr, "'closed'") === FALSE)
						{
							$sstr .= " AND t.status != 'closed' ";
						}

						$dql .= $sstr;
					}
					else
					{
						$dql .= "AND t.status = 'open' ";
					}

					$query = $this->EE->db->query($dql);

					$distinct = array();

					if ($query->num_rows() > 0)
					{
						/** ---------------------------------
						/*	 Sort Default is ASC for Display By Week so that entries are displayed
						/*	oldest to newest in the week, which is how you would expect.
						/*  ---------------------------------*/

						if ($this->EE->TMPL->fetch_param('sort') === FALSE)
						{
							$sort_array[0] = 'asc';
						}

						foreach ($query->result_array() as $row)
						{
							$distinct[] = $row['yearweek'];
						}

						$distinct = array_unique($distinct);
						rsort($distinct);

						/* Old code, did nothing
						*
						if ($this->EE->TMPL->fetch_param('week_sort') == 'desc')
						{
							$distinct = array_reverse($distinct);
						}
						*
						*/

						$this->total_rows = count($distinct);
						$cur = ($this->p_page == '') ? 0 : $this->p_page;

						/** ---------------------------------
						/*	 If no pagination, then the Current Week is shown by default with
						/*	 all pagination correctly set and ready to roll, if used.
						/*  ---------------------------------*/

						if ($this->EE->TMPL->fetch_param('show_current_week') === 'yes' && $this->p_page == '')
						{
							if ($this->EE->TMPL->fetch_param('start_day') === 'Monday')
							{
								$query = $this->EE->db->query("SELECT DATE_FORMAT(CURDATE(), '%x%v') AS thisWeek");
							}
							else
							{
								$query = $this->EE->db->query("SELECT DATE_FORMAT(CURDATE(), '%X%V') AS thisWeek");
							}

							foreach($distinct as $key => $week)
							{
								if ($week == $query->row('thisWeek') )
								{
									$cur = $key;
									$this->p_page = $key;
									break;
								}
							}
						}

						$distinct = array_slice($distinct, $cur, $lim);

						/** ---------------------------------
						/*	 Finally, we add the display by week SQL to the query
						/*  ---------------------------------*/

						if ($distinct != FALSE)
						{
							// A Rough Attempt to Get the Localized Offset Added On

							$offset = $this->EE->localize->set_localized_offset();
							$dst_on = (date("I", $this->EE->localize->now) === 1) ? TRUE : FALSE;

							$sql .= "AND (";

							foreach ($distinct as $val)
							{
								if ($dst_on === TRUE AND (substr($val, 4) < 13 OR substr($val, 4) >= 43))
								{
									$offset -= 3600;
								}
								elseif ($dst_on === FALSE AND (substr($val, 4) >= 13 AND substr($val, 4) < 43))
								{
									$offset += 3600;
								}

								$sql_offset = ($offset < 0) ? "- ".abs($offset) : "+ ".$offset;

								if ($this->EE->TMPL->fetch_param('start_day') === 'Monday')
								{
									$sql .= " DATE_FORMAT(FROM_UNIXTIME(entry_date {$sql_offset}), '%x%v') = '".$val."' OR";
								}
								else
								{
									$sql .= " DATE_FORMAT(FROM_UNIXTIME(entry_date {$sql_offset}), '%X%V') = '".$val."' OR";
								}
							}

							$sql = substr($sql, 0, -2).')';
						}
					}
				}
			}
		}


		/**------
		/**  Limit query "URL title"
		/**------*/

		if ($qtitle != '' AND $dynamic)
		{
			$sql .= "AND t.url_title = '".$this->EE->db->escape_str($qtitle)."' ";

			// We use this with hit tracking....

			$this->hit_tracking_id = $qtitle;
		}


		// We set a
		if ($entry_id != '' AND $this->entry_id !== FALSE)
		{
			$this->hit_tracking_id = $entry_id;
		}

		/**------
		/**  Limit query by category
		/**------*/

		if ($this->EE->TMPL->fetch_param('category'))
		{
			if (stristr($this->EE->TMPL->fetch_param('category'), '&'))
			{
				/** --------------------------------------
				/**  First, we find all entries with these categories
				/** --------------------------------------*/

				$for_sql = (substr($this->EE->TMPL->fetch_param('category'), 0, 3) == 'not') ? trim(substr($this->EE->TMPL->fetch_param('category'), 3)) : $this->EE->TMPL->fetch_param('category');

				$csql = "SELECT exp_category_posts.entry_id, exp_category_posts.cat_id ".
						$sql.
						$this->EE->functions->sql_andor_string(str_replace('&', '|', $for_sql), 'exp_categories.cat_id');

				//exit($csql);

				$results = $this->EE->db->query($csql);

				if ($results->num_rows() == 0)
				{
					return;
				}

				$type = 'IN';
				$categories	 = explode('&', $this->EE->TMPL->fetch_param('category'));
				$entry_array = array();

				if (substr($categories[0], 0, 3) == 'not')
				{
					$type = 'NOT IN';

					$categories[0] = trim(substr($categories[0], 3));
				}

				foreach($results->result_array() as $row)
				{
					$entry_array[$row['cat_id']][] = $row['entry_id'];
				}

				if (count($entry_array) < 2 OR count(array_diff($categories, array_keys($entry_array))) > 0)
				{
					return;
				}

				$chosen = call_user_func_array('array_intersect', $entry_array);

				if (count($chosen) == 0)
				{
					return;
				}

				$sql .= "AND t.entry_id ".$type." ('".implode("','", $chosen)."') ";
			}
			else
			{
				if (substr($this->EE->TMPL->fetch_param('category'), 0, 3) == 'not' && $this->EE->TMPL->fetch_param('uncategorized_entries') !== 'n')
				{
					$sql .= $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('category'), 'exp_categories.cat_id', '', TRUE)." ";
				}
				else
				{
					$sql .= $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('category'), 'exp_categories.cat_id')." ";
				}
			}
		}

		if ($this->EE->TMPL->fetch_param('category_group'))
		{
			if (substr($this->EE->TMPL->fetch_param('category_group'), 0, 3) == 'not' && $this->EE->TMPL->fetch_param('uncategorized_entries') !== 'n')
			{
				$sql .= $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('category_group'), 'exp_categories.group_id', '', TRUE)." ";
			}
			else
			{
				$sql .= $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('category_group'), 'exp_categories.group_id')." ";
			}
		}

		if ($this->EE->TMPL->fetch_param('category') === FALSE && $this->EE->TMPL->fetch_param('category_group') === FALSE)
		{
			if ($cat_id != '' AND $dynamic)
			{
				$sql .= " AND exp_categories.cat_id = '".$this->EE->db->escape_str($cat_id)."' ";
			}
		}

		/**------
		/**  Limit to (or exclude) specific users
		/**------*/

		if ($username = $this->EE->TMPL->fetch_param('username'))
		{
			// Shows entries ONLY for currently logged in user

			if ($username == 'CURRENT_USER')
			{
				$sql .=  "AND m.member_id = '".$this->EE->session->userdata('member_id')."' ";
			}
			elseif ($username == 'NOT_CURRENT_USER')
			{
				$sql .=  "AND m.member_id != '".$this->EE->session->userdata('member_id')."' ";
			}
			else
			{
				$sql .= $this->EE->functions->sql_andor_string($username, 'm.username');
			}
		}

		/**------
        /**  Limit to (or exclude) specific author id(s)
        /**------*/

        if ($author_id = $this->EE->TMPL->fetch_param('author_id'))
		{
			// Shows entries ONLY for currently logged in user

			if ($author_id == 'CURRENT_USER')
			{
				$sql .=  "AND m.member_id = '".$this->EE->session->userdata('member_id')."' ";
			}
			elseif ($author_id == 'NOT_CURRENT_USER')
			{
				$sql .=  "AND m.member_id != '".$this->EE->session->userdata('member_id')."' ";
			}
			else
			{
				$sql .= $this->EE->functions->sql_andor_string($author_id, 'm.member_id');
			}
		}

		/**------
		/**  Add status declaration
		/**------*/

		if ($status = $this->EE->TMPL->fetch_param('status'))
		{
			$status = str_replace('Open',	'open',	$status);
			$status = str_replace('Closed', 'closed', $status);

			$sstr = $this->EE->functions->sql_andor_string($status, 't.status');

			if (stristr($sstr, "'closed'") === FALSE)
			{
				$sstr .= " AND t.status != 'closed' ";
			}

			$sql .= $sstr;
		}
		else
		{
			$sql .= "AND t.status = 'open' ";
		}

		/**------
		/**  Add Group ID clause
		/**------*/

		if ($group_id = $this->EE->TMPL->fetch_param('group_id'))
		{
			$sql .= $this->EE->functions->sql_andor_string($group_id, 'm.group_id');
		}

    	/** ---------------------------------------
    	/**  Field searching
    	/** ---------------------------------------*/

		if ( ! empty($this->EE->TMPL->search_fields))
		{
			foreach ($this->EE->TMPL->search_fields as $field_name => $terms)
			{
				if (isset($this->cfields[$this->EE->config->item('site_id')][$field_name]))
				{
					if (strncmp($terms, '=', 1) ==  0)
					{
						/** ---------------------------------------
						/**  Exact Match e.g.: search:body="=pickle"
						/** ---------------------------------------*/

						$terms = substr($terms, 1);

						// special handling for IS_EMPTY
						if (strpos($terms, 'IS_EMPTY') !== FALSE)
						{
							$terms = str_replace('IS_EMPTY', '', $terms);

							$add_search = $this->EE->functions->sql_andor_string($terms, 'wd.field_id_'.$this->cfields[$this->EE->config->item('site_id')][$field_name]);

							// remove the first AND output by $this->EE->functions->sql_andor_string() so we can parenthesize this clause
							$add_search = substr($add_search, 3);

							$conj = ($add_search != '' && strncmp($terms, 'not ', 4) != 0) ? 'OR' : 'AND';

							if (strncmp($terms, 'not ', 4) == 0)
							{
								$sql .= 'AND ('.$add_search.' '.$conj.' wd.field_id_'.$this->cfields[$this->EE->config->item('site_id')][$field_name].' != "") ';
							}
							else
							{
								$sql .= 'AND ('.$add_search.' '.$conj.' wd.field_id_'.$this->cfields[$this->EE->config->item('site_id')][$field_name].' = "") ';
							}
						}
						else
						{
							$sql .= $this->EE->functions->sql_andor_string($terms, 'wd.field_id_'.$this->cfields[$this->EE->config->item('site_id')][$field_name]).' ';
						}
					}
					else
					{
						/** ---------------------------------------
						/**  "Contains" e.g.: search:body="pickle"
						/** ---------------------------------------*/

						if (strncmp($terms, 'not ', 4) == 0)
						{
							$terms = substr($terms, 4);
							$like = 'NOT LIKE';
						}
						else
						{
							$like = 'LIKE';
						}

						if (strpos($terms, '&&') !== FALSE)
						{
							$terms = explode('&&', $terms);
							$andor = (strncmp($like, 'NOT', 3) == 0) ? 'OR' : 'AND';
						}
						else
						{
							$terms = explode('|', $terms);
							$andor = (strncmp($like, 'NOT', 3) == 0) ? 'AND' : 'OR';
						}

						$sql .= ' AND (';

						foreach ($terms as $term)
						{
							if ($term == 'IS_EMPTY')
							{
								$sql .= ' wd.field_id_'.$this->cfields[$this->EE->config->item('site_id')][$field_name].' '.$like.' "" '.$andor;
							}
							elseif (strpos($term, '\W') !== FALSE) // full word only, no partial matches
							{
								$not = ($like == 'LIKE') ? ' ' : ' NOT ';

								// Note: MySQL's nutty POSIX regex word boundary is [[:>:]]
								$term = '([[:<:]]|^)'.preg_quote(str_replace('\W', '', $term)).'([[:>:]]|$)';

								$sql .= ' wd.field_id_'.$this->cfields[$this->EE->config->item('site_id')][$field_name].$not.'REGEXP "'.$this->EE->db->escape_str($term).'" '.$andor;
							}
							else
							{
								$sql .= ' wd.field_id_'.$this->cfields[$this->EE->config->item('site_id')][$field_name].' '.$like.' "%'.$this->EE->db->escape_like_str($term).'%" '.$andor;
							}
						}

						$sql = substr($sql, 0, -strlen($andor)).') ';
					}
				}
			}
		}

		/**----------
		/**  Build sorting clause
		/**----------*/

		// We'll assign this to a different variable since we
		// need to use this in two places

		$end = 'ORDER BY ';

		if ($fixed_order !== FALSE && ! empty($fixed_order))
		{
			$end .= 'FIELD(t.entry_id, '.implode(',', $fixed_order).') ';
		}
		else
		{
			// Used to eliminate sort issues with duplicated fields below
			$entry_id_sort = $sort_array[0];
			
			if (FALSE === $order_array[0])
			{
				if ($sticky == 'no')
				{
					$end .= "t.entry_date";
				}
				else
				{
					$end .= "t.sticky desc, t.entry_date";
				}

				if ($sort_array[0] == 'asc' OR $sort_array[0] == 'desc')
				{
					$end .= " ".$sort_array[0];
				}
			}
			else
			{
				if ($sticky != 'no')
				{
					$end .= "t.sticky desc, ";
				}

				foreach($order_array as $key => $order)
				{
					if (in_array($order, array('view_count_one', 'view_count_two', 'view_count_three', 'view_count_four')))
					{
						$view_ct = substr($order, 10);
						$order	 = "view_count";
					}

					if ($key > 0) $end .= ", ";

					switch ($order)
					{
						case 'entry_id' :
							$end .= "t.entry_id";
						break;

						case 'date' :
							$end .= "t.entry_date";
						break;

						case 'edit_date' :
							$end .= "t.edit_date";
						break;

						case 'expiration_date' :
							$end .= "t.expiration_date";
						break;

						case 'title' :
							$end .= "t.title";
						break;

						case 'url_title' :
							$end .= "t.url_title";
						break;

						case 'view_count' :
							$vc = $order.$view_ct;

							$end .= " t.{$vc} ".$sort_array[$key];

							if (count($order_array)-1 == $key)
							{
								$end .= ", t.entry_date ".$sort_array[$key];
							}

							$sort_array[$key] = FALSE;
						break;

						case 'comment_total' :
							$end .= "t.comment_total ".$sort_array[$key];

							if (count($order_array)-1 == $key)
							{
								$end .= ", t.entry_date ".$sort_array[$key];
							}

							$sort_array[$key] = FALSE;
						break;

						case 'most_recent_comment' :
							$end .= "t.recent_comment_date ".$sort_array[$key];

							if (count($order_array)-1 == $key)
							{
								$end .= ", t.entry_date ".$sort_array[$key];
							}

							$sort_array[$key] = FALSE;
						break;

						case 'username' :
							$end .= "m.username";
						break;

						case 'screen_name' :
							$end .= "m.screen_name";
						break;

						case 'custom_field' :
							if (strpos($corder[$key], '|') !== FALSE)
							{
								$end .= "CONCAT(wd.field_id_".implode(", wd.field_id_", explode('|', $corder[$key])).")";
							}
							else
							{
								$end .= "wd.field_id_".$corder[$key];
							}
						break;

						case 'random' :
								$end = "ORDER BY rand()";
								$sort_array[$key] = FALSE;
						break;

						default		:
							$end .= "t.entry_date";
						break;
					}

					if ($sort_array[$key] == 'asc' OR $sort_array[$key] == 'desc')
					{
						// keep entries with the same timestamp in the correct order
						$end .= " {$sort_array[$key]}";
					}
				}
			}

			// In the event of a sorted field containing identical information as another
			// entry (title, entry_date, etc), they will sort on the order they were entered
			// into ExpressionEngine, with the first "sort" parameter taking precedence.
			// If no sort parameter is set, entries will descend by entry id.
			if ( ! in_array('entry_id', $order_array))
			{
				$end .= ", t.entry_id ".$entry_id_sort;
			}
		}

		//  Determine the row limits
		// Even thouth we don't use the LIMIT clause until the end,
		// we need it to help create our pagination links so we'll
		// set it here

		if ($cat_id  != '' AND is_numeric($this->EE->TMPL->fetch_param('cat_limit')))
		{
			$this->p_limit = $this->EE->TMPL->fetch_param('cat_limit');
		}
		elseif ($month != '' AND is_numeric($this->EE->TMPL->fetch_param('month_limit')))
		{
			$this->p_limit = $this->EE->TMPL->fetch_param('month_limit');
		}
		else
		{
			$this->p_limit  = ( ! is_numeric($this->EE->TMPL->fetch_param('limit')))  ? $this->limit : $this->EE->TMPL->fetch_param('limit');
		}

		/**------
		/**  Is there an offset?
		/**------*/
		// We do this hear so we can use the offset into next, then later one as well
		$offset = ( ! $this->EE->TMPL->fetch_param('offset') OR ! is_numeric($this->EE->TMPL->fetch_param('offset'))) ? '0' : $this->EE->TMPL->fetch_param('offset');

		//  Do we need pagination?
		// We'll run the query to find out

		if ($this->paginate == TRUE)
		{
			if ($this->field_pagination == FALSE)
			{
				$this->pager_sql = $sql_a.$sql_b.$sql;
				$query = $this->EE->db->query($this->pager_sql);
				$total = $query->num_rows;
				$this->absolute_results = $total;

				// Adjust for offset
				if ($total >= $offset)
					$total = $total - $offset;

				$this->create_pagination($total);
			}
			else
			{
				$this->pager_sql = $sql_a.$sql_b.$sql;

				$query = $this->EE->db->query($this->pager_sql);

				$total = $query->num_rows;
				$this->absolute_results = $total;

				$this->create_pagination($total, $query);

				if ($this->EE->config->item('enable_sql_caching') == 'y')
				{
					$this->save_cache($this->pager_sql, 'pagination_query');
					$this->save_cache('1', 'field_pagination');
				}
			}

			if ($this->EE->config->item('enable_sql_caching') == 'y')
			{
				$this->save_cache($total, 'pagination_count');
			}
		}

		/**------
		/**  Add Limits to query
		/**------*/

		$sql .= $end;

		if ($this->paginate == FALSE)
			$this->p_page = 0;

		// Adjust for offset
		$this->p_page += $offset;

		if ($this->display_by == '')
		{
			if (($page_marker == FALSE AND $this->p_limit != '') OR ($page_marker == TRUE AND $this->field_pagination != TRUE))
			{
				$sql .= ($this->p_page == '') ? " LIMIT ".$offset.', '.$this->p_limit : " LIMIT ".$this->p_page.', '.$this->p_limit;
			}
			elseif ($entry_id == '' AND $qtitle == '')
			{
				$sql .= ($this->p_page == '') ? " LIMIT ".$this->limit : " LIMIT ".$this->p_page.', '.$this->limit;
			}
		}
		else
		{
			if ($offset != 0)
			{
				$sql .= ($this->p_page == '') ? " LIMIT ".$offset.', '.$this->p_limit : " LIMIT ".$this->p_page.', '.$this->p_limit;
			}
		}

		/**------
		/**  Fetch the entry_id numbers
		/**------*/

		$query = $this->EE->db->query($sql_a.$sql_b.$sql);

		//exit($sql_a.$sql_b.$sql);

		if ($query->num_rows() == 0)
		{
			$this->sql = '';
			return;
		}
		
		/**------
		/**  Build the full SQL query
		/**------*/

		$this->sql = "SELECT ";

		if ($this->EE->TMPL->fetch_param('category') OR $this->EE->TMPL->fetch_param('category_group') OR $cat_id != '')
		{
			// Using DISTINCT like this is bogus but since
			// FULL OUTER JOINs are not supported in older versions
			// of MySQL it's our only choice

			$this->sql .= " DISTINCT(t.entry_id), ";
		}

		if ($this->display_by == 'week' && isset($yearweek))
		{
			$this->sql .= $yearweek.', ';
		}

		// DO NOT CHANGE THE ORDER
		// The exp_member_data table needs to be called before the exp_members table.

		$this->sql .= " t.entry_id, t.channel_id, t.forum_topic_id, t.author_id, t.ip_address, t.title, t.url_title, t.status, t.dst_enabled, t.view_count_one, t.view_count_two, t.view_count_three, t.view_count_four, t.allow_comments, t.comment_expiration_date, t.sticky, t.entry_date, t.year, t.month, t.day, t.edit_date, t.expiration_date, t.recent_comment_date, t.comment_total, t.site_id as entry_site_id,
						w.channel_title, w.channel_name, w.channel_url, w.comment_url, w.comment_moderate, w.channel_html_formatting, w.channel_allow_img_urls, w.channel_auto_link_urls, w.comment_system_enabled, 
						m.username, m.email, m.url, m.screen_name, m.location, m.occupation, m.interests, m.aol_im, m.yahoo_im, m.msn_im, m.icq, m.signature, m.sig_img_filename, m.sig_img_width, m.sig_img_height, m.avatar_filename, m.avatar_width, m.avatar_height, m.photo_filename, m.photo_width, m.photo_height, m.group_id, m.member_id, m.bday_d, m.bday_m, m.bday_y, m.bio,
						md.*,
						wd.*
				FROM exp_channel_titles		AS t
				LEFT JOIN exp_channels 		AS w  ON t.channel_id = w.channel_id
				LEFT JOIN exp_channel_data	AS wd ON t.entry_id = wd.entry_id
				LEFT JOIN exp_members		AS m  ON m.member_id = t.author_id
				LEFT JOIN exp_member_data	AS md ON md.member_id = m.member_id ";

		$this->sql .= "WHERE t.entry_id IN (";

		$entries = array();

		// Build ID numbers (checking for duplicates)

		foreach ($query->result_array() as $row)
		{
			if ( ! isset($entries[$row['entry_id']]))
			{
				$entries[$row['entry_id']] = 'y';
			}
			else
			{
				continue;
			}

			$this->sql .= $row['entry_id'].',';
		}

		//cache the entry_id
		$this->EE->session->cache['channel']['entry_ids']	= array_keys($entries);
		
		unset($query);
		unset($entries);

		$this->sql = substr($this->sql, 0, -1).') ';

		// modify the ORDER BY if displaying by week
		if ($this->display_by == 'week' && isset($yearweek))
		{
			$weeksort = ($this->EE->TMPL->fetch_param('week_sort') == 'desc') ? 'DESC' : 'ASC';
			$end = str_replace('ORDER BY ', 'ORDER BY yearweek '.$weeksort.', ', $end);
		}

		$this->sql .= $end;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Create pagination
	  */
	function create_pagination($count = 0, $query = '')
	{
		if (is_object($query))
		{
			$row = $query->row_array();
		}
		else
		{
			$row = '';
		}

		// -------------------------------------------
		// 'channel_module_create_pagination' hook.
		//  - Rewrite the pagination function in the Channel module
		//  - Could be used to expand the kind of pagination available
		//  - Paginate via field length, for example
		//
			if ($this->EE->extensions->active_hook('channel_module_create_pagination') === TRUE)
			{
				$edata = $this->EE->extensions->universal_call('channel_module_create_pagination', $this);
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------
		
		if ($this->paginate == TRUE)
		{
			/* --------------------------------------
			/*  For subdomain's or domains using $template_group and $template
			/*  in path.php, the pagination for the main index page requires
			/*  that the template group and template are specified.
			/* --------------------------------------*/

			if (($this->EE->uri->uri_string == '' OR $this->EE->uri->uri_string == '/') && $this->EE->config->item('template_group') != '' && $this->EE->config->item('template') != '')
			{
				$this->basepath = $this->EE->functions->create_url($this->EE->config->slash_item('template_group').'/'.$this->EE->config->item('template'));
			}
			
			if ($this->basepath == '')
			{
				$this->basepath = $this->EE->functions->create_url($this->EE->uri->uri_string);

				if (preg_match("#^P(\d+)|/P(\d+)#", $this->query_string, $match))
				{
					$this->p_page = (isset($match[2])) ? $match[2] : $match[1];
					$this->basepath = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $this->basepath));
				}
			}

			//  Standard pagination - base values

			if ($this->field_pagination == FALSE)
			{	
				if ($this->display_by == '')
				{
					if ($count == 0)
					{
						$this->sql = '';
						return;
					}

					$this->total_rows = $count;
				}

				if ($this->dynamic_sql == FALSE)
				{
					$cat_limit = FALSE;
					if ((in_array($this->reserved_cat_segment, explode("/", $this->EE->uri->uri_string))
						AND $this->EE->TMPL->fetch_param('dynamic') != 'no'
						AND $this->EE->TMPL->fetch_param('channel'))
						OR (preg_match("#(^|\/)C(\d+)#", $this->EE->uri->uri_string, $match) AND $this->EE->TMPL->fetch_param('dynamic') != 'no'))
					{
						$cat_limit = TRUE;
					}

					if ($cat_limit AND is_numeric($this->EE->TMPL->fetch_param('cat_limit')))
					{
						$this->p_limit = $this->EE->TMPL->fetch_param('cat_limit');
					}
					else
					{
						$this->p_limit  = ( ! is_numeric($this->EE->TMPL->fetch_param('limit')))  ? $this->limit : $this->EE->TMPL->fetch_param('limit');
					}
				}

				$this->p_page = ($this->p_page == '' OR ($this->p_limit > 1 AND $this->p_page == 1)) ? 0 : $this->p_page;

				if ($this->p_page > $this->total_rows)
				{
					$this->p_page = 0;
				}
								
				$this->current_page = floor(($this->p_page / $this->p_limit) + 1);
				$this->total_pages = intval(floor($this->total_rows / $this->p_limit));				
			}
			else
			{
				//  Field pagination - base values

				if ($count == 0)
				{
					$this->sql = '';
					return;
				}

				$m_fields = array();

				foreach ($this->multi_fields as $val)
				{
					foreach($this->cfields as $site_id => $cfields)
					{
						if (isset($cfields[$val]))
						{
							if (isset($row['field_id_'.$cfields[$val]]) AND $row['field_id_'.$cfields[$val]] != '')
							{
								$m_fields[] = $val;
							}
						}
					}
				}

				$this->p_limit = 1;

				$this->total_rows = count($m_fields);

				$this->total_pages = $this->total_rows;

				if ($this->total_pages == 0)
					$this->total_pages = 1;

				$this->p_page = ($this->p_page == '') ? 0 : $this->p_page;

				if ($this->p_page > $this->total_rows)
				{
					$this->p_page = 0;
				}

				$this->current_page = floor(($this->p_page / $this->p_limit) + 1);

				if (isset($m_fields[$this->p_page]))
				{
					$this->EE->TMPL->tagdata = preg_replace("/".LD."multi_field\=[\"'].+?[\"']".RD."/s", LD.$m_fields[$this->p_page].RD, $this->EE->TMPL->tagdata);
					$this->EE->TMPL->var_single[$m_fields[$this->p_page]] = $m_fields[$this->p_page];
				}
			}

			//  Create the pagination

			if ($this->total_rows > 0 && $this->p_limit > 0)
			{
				if ($this->total_rows % $this->p_limit)
				{
					$this->total_pages++;
				}				
			}

			if ($this->total_rows > $this->p_limit)
			{
				$this->EE->load->library('pagination');

				if (strpos($this->basepath, SELF) === FALSE && $this->EE->config->item('site_index') != '')
				{
					$this->basepath .= SELF;
				}

				if ($this->EE->TMPL->fetch_param('paginate_base'))
				{
					// Load the string helper
					$this->EE->load->helper('string');

					$this->basepath = $this->EE->functions->create_url(trim_slashes($this->EE->TMPL->fetch_param('paginate_base')));
				}
				
				$config['first_url'] 	= rtrim($this->basepath, '/');
				$config['base_url']		= $this->basepath;
				$config['prefix']		= 'P';
				$config['total_rows'] 	= $this->total_rows;
				$config['per_page']		= $this->p_limit;
				$config['cur_page']		= $this->p_page;
				$config['first_link'] 	= $this->EE->lang->line('pag_first_link');
				$config['last_link'] 	= $this->EE->lang->line('pag_last_link');
				
				// Allows $config['cur_page'] to override
				$config['uri_segment'] = 0;

				$this->EE->pagination->initialize($config);
				$this->pagination_links = $this->EE->pagination->create_links();
				$this->EE->pagination->initialize($config); // Re-initialize to reset config
				$this->pagination_array = $this->EE->pagination->create_link_array();

				if ((($this->total_pages * $this->p_limit) - $this->p_limit) > $this->p_page)
				{
					$this->page_next = reduce_double_slashes($this->basepath.'/P'.($this->p_page + $this->p_limit));
				}

				if (($this->p_page - $this->p_limit ) >= 0)
				{
					$this->page_previous = reduce_double_slashes($this->basepath.'/P'.($this->p_page - $this->p_limit));
				}
			}
			else
			{
				$this->p_page = '';
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Parse channel entries - New Attempt
	  */
	function parse_channel_entries_new()
	{
		// Internal Tag Caching
		$processed_member_fields = array();
		$existing_variables		 = array();

		if (preg_match_all("/".LD."([a-z\_]+)/i", $this->EE->TMPL->tagdata, $matches))
		{
			$existing_variables = array_flip($matches[1]);
		}

		//  Set default date header variables
		$heading_date_hourly  = 0;
		$heading_flag_hourly  = 0;
		$heading_flag_weekly  = 1;
		$heading_date_daily	  = 0;
		$heading_flag_daily	  = 0;
		$heading_date_monthly = 0;
		$heading_flag_monthly = 0;
		$heading_date_yearly  = 0;
		$heading_flag_yearly  = 0;

		//  "Search by Member" link
		// We use this with the {member_search_path} variable

		if ( isset($existing_variables['member_search_path']))
		{
			$result_path = (preg_match("/".LD."member_search_path\s*=(.*?)".RD."/s", $this->EE->TMPL->tagdata, $match)) ? $match[1] : 'search/results';
			$result_path = str_replace(array('"',"'"), "", $result_path);

			$search_link = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Search', 'do_search').'&amp;result_path='.$result_path.'&amp;mbr=';
		}

		//  Start the main processing loop

		$total_results = count($this->query->result_array());

		$site_pages = $this->EE->config->item('site_pages');

		$parse_data = array();

		foreach ($this->query->result_array() as $count => $row)
		{
			//$row['count']			= $count+1;
			//$row['total_results']	= $total_results;
			$row['absolute_count']	= $this->p_page + $count + 1;

			$row['page_uri']		= '';
			$row['page_url']		= '';

			if ($site_pages !== FALSE && isset($site_pages[$row['site_id']]['uris'][$row['entry_id']]))
			{
				$row['page_uri'] = $site_pages[$row['site_id']]['uris'][$row['entry_id']];
				$row['page_url'] = $this->EE->functions->create_page_url($site_pages[$row['site_id']]['url'], $site_pages[$row['site_id']]['uris'][$row['entry_id']]);
			}

			//  Adjust dates if needed
			// If the "dst_enabled" item is set in any given entry
			// we need to offset to the timestamp by an hour

			if ( ! isset($row['dst_enabled']))
			{
				$row['dst_enabled'] = 'n';
			}

			//  More Variables, Mostly for Conditionals
			$row['logged_in'] = ($this->EE->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
			$row['logged_out'] = ($this->EE->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';

			if ((($row['comment_expiration_date'] > 0 && $this->EE->localize->now > $row['comment_expiration_date']) && $this->EE->config->item('comment_moderation_override') !== 'y') OR $row['allow_comments'] == 'n' OR $row['comment_system_enabled']  == 'n')
			{
				$row['allow_comments'] = 'FALSE';
			}
			else
			{
				$row['allow_comments'] = 'TRUE';
			}

			foreach (array('avatar_filename', 'photo_filename', 'sig_img_filename') as $pv)
			{
				if ( ! isset($row[$pv]))
				{
					$row[$pv] = '';					
				}
			}

			$row['signature_image']			= ($row['sig_img_filename'] == '' OR $this->EE->config->item('enable_signatures') == 'n' OR $this->EE->session->userdata('display_signatures') == 'n') ? 'FALSE' : 'TRUE';
			$row['avatar']					= ($row['avatar_filename'] == '' OR $this->EE->config->item('enable_avatars') == 'n' OR $this->EE->session->userdata('display_avatars') == 'n') ? 'FALSE' : 'TRUE';
			$row['photo']					= ($row['photo_filename'] == '' OR $this->EE->config->item('enable_photos') == 'n' OR $this->EE->session->userdata('display_photos') == 'n') ? 'FALSE' : 'TRUE';
			$row['forum_topic']				= (empty($row['forum_topic_id'])) ? 'FALSE' : 'TRUE';
			$row['not_forum_topic']			= ( ! empty($row['forum_topic_id'])) ? 'FALSE' : 'TRUE';
			$row['category_request']		= ($this->cat_request === FALSE) ? 'FALSE' : 'TRUE';
			$row['not_category_request']	= ($this->cat_request !== FALSE) ? 'FALSE' : 'TRUE';
			$row['channel']					= $row['channel_title'];
			$row['channel_short_name']		= $row['channel_name'];
			$row['author']					= ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
			$row['photo_url']				= $this->EE->config->slash_item('photo_url').$row['photo_filename'];
			$row['photo_image_width']		= $row['photo_width'];
			$row['photo_image_height']		= $row['photo_height'];
			$row['avatar_url']				= $this->EE->config->slash_item('avatar_url').$row['avatar_filename'];
			$row['avatar_image_width']		= $row['avatar_width'];
			$row['avatar_image_height']		= $row['avatar_height'];
			$row['signature_image_url']		= $this->EE->config->slash_item('sig_img_url').$row['sig_img_filename'];
			$row['signature_image_width']	= $row['sig_img_width'];
			$row['signature_image_height']	= $row['sig_img_height'];

			if ( isset($existing_variables['relative_date']))
			{
				$row['relative_date']			= $this->EE->localize->format_timespan($this->EE->localize->now - $row['entry_date']);
			}

			//  Date Variables

			if ($row['recent_comment_date'] == 0)	$row['recent_comment_date'] = '';
			if ($row['expiration_date'] == 0)		$row['expiration_date'] = '';

			//  "week_date"

			if ( isset($existing_variables['week_start_date']))
			{
				// Subtract the number of days the entry is "into" the week to get zero (Sunday)
				// If the entry date is for Sunday, and Monday is being used as the week's start day,
				// then we must back things up by six days

				$offset = 0;

				if (strtolower($this->EE->TMPL->fetch_param('start_day')) == 'monday')
				{
					$day_of_week = $this->EE->localize->convert_timestamp('%w', $row['entry_date'], TRUE);

					if ($day_of_week == '0')
					{
						$offset = -518400; // back six days
					}
					else
					{
						$offset = 86400; // plus one day
					}
				}

				$row['week_start_date'] = $row['entry_date'] - ($this->EE->localize->convert_timestamp('%w', $row['entry_date'], TRUE) * 60 * 60 * 24) + $offset;
			}

			//  PATH Variables

			$row['profile_path']				= array('path', array('suffix'	=> $row['member_id'], 'default_path' => ''));

			if ( isset($existing_variables['week_start_date']))
			{
				$row['week_start_date']	= array('path', array('suffix'	=> $row['member_id'], 'url' => $search_link));
			}

			$row['comment_path']				= array('path', array('suffix'	=> $row['entry_id']));
			$row['entry_id_path']				= array('path', array('suffix'	=> $row['entry_id']));
			$row['url_title_path']				= array('path', array('suffix'	=> $row['url_title']));
			$row['title_permalink']				= array('path', array('suffix'	=> $row['url_title']));
			$row['permalink']					= array('path', array('suffix'	=> $row['entry_id']));
			$row['comment_auto_path']			= array('path', array('url'		=> ($row['comment_url'] == '') ? $row['channel_url'] : $row['comment_url']));
			$row['comment_url_title_auto_path']	= array('path', array('url' 	=> ($row['comment_url'] == '') ? $row['channel_url'] : $row['comment_url'], 'suffix' => $row['url_title']));
			$row['comment_entry_id_auto_path']	= array('path', array('url'		=> ($row['comment_url'] == '') ? $row['channel_url'] : $row['comment_url'], 'suffix' => $row['entry_id']));

			//  Other Single Variables

			$row['author']				= ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
			$row['channel']				= $row['channel_title'];
			$row['channel_short_name']	= $row['channel_name'];

			if ( isset($existing_variables['relative_date']))
			{
				$row['relative_date'] = $this->EE->localize->format_timespan($this->EE->localize->now - $row['entry_date']);
			}

			// Trimmed URL
			$channel_url = str_replace(array('http://','www.'), '', (isset($row['channel_url']) AND $row['channel_url'] != '') ? $row['channel_url'] : '');
			$xe = explode("/", $channel_url);

			$row['trimmed_url']			= current($xe);

			// Relative URL
			if ($x = strpos($channel_url, "/"))
			{
				$channel_url = substr($channel_url, $x + 1);
			}

			$row['relative_url'] 			= rtrim($channel_url, '/');
			$row['url_or_email']			= ($row['url'] != '') ? $row['url'] : $row['email'];

			if ( isset($existing_variables['url_or_email_as_author']))
			{
				$row['url_or_email_as_author'] = ($row['url'] != '') ? "<a href=\"".$row['url']."\">".$row['author']."</a>" : $this->EE->typography->encode_email($row['email'], $row['author']);
			}

			if ( isset($existing_variables['url_or_email_as_link']))
			{
				$row['url_or_email_as_link'] = ($row['url'] != '') ? "<a href=\"".$row['url']."\">".$row['url']."</a>" : $this->EE->typography->encode_email($row['email']);
			}

			//  {signature}

			$row['signature'] = '';

			if ( isset($existing_variables['signature']) && $this->EE->session->userdata('display_signatures') != 'n' && $row['signature'] != '' && $this->EE->session->userdata('display_signatures') != 'n')
			{
				$row['signature'] = array($row['signature'], array(
																	'text_format'	=> 'xhtml',
																	'html_format'	=> 'safe',
																	'auto_links'	=> 'y',
																	'allow_img_url' => $this->EE->config->item('sig_allow_img_hotlink')
																));
			}


			//  Member Images and Whatnot

			$row['signature_image_url'] = '';
			$row['signature_image_width'] = '';
			$row['signature_image_height'] = '';
			$row['avatar_url'] = '';
			$row['avatar_image_width'] = '';
			$row['avatar_image_height'] = '';
			$row['photo_url'] = '';
			$row['photo_image_width'] = '';
			$row['photo_image_height'] = '';


			if ($this->EE->session->userdata('display_signatures') != 'n' && $row['sig_img_filename'] != ''  && $this->EE->session->userdata('display_signatures') != 'n')
			{
				$row['signature_image_url'] 	= $this->EE->config->slash_item('sig_img_url').$row['sig_img_filename'];
				$row['signature_image_width']	= $row['sig_img_width'];
				$row['signature_image_height']	= $row['sig_img_height'];
			}

			if ($this->EE->session->userdata('display_avatars') != 'n' && $row['avatar_filename'] != ''  && $this->EE->session->userdata('display_avatars') != 'n')
			{
				$row['avatar_url'] 			= $this->EE->config->slash_item('avatar_url').$row['avatar_filename'];
				$row['avatar_image_width']	= $row['avatar_width'];
				$row['avatar_image_height']	= $row['avatar_height'];
			}

			if ($this->EE->session->userdata('display_photos') != 'n' && $row['photo_filename'] != ''  && $this->EE->session->userdata('display_photos') != 'n')
			{
				$row['photo_url'] 			= $this->EE->config->slash_item('photo_url').$row['photo_filename'];
				$row['photo_image_width']	= $row['photo_width'];
				$row['photo_image_height']	= $row['photo_height'];
			}

			// Title
			$row['title'] = str_replace(array('{', '}'), array('&#123;', '&#125;'), $row['title']);

			//
			// Custom Date Fields
			//

			if (isset($this->dfields[$row['site_id']]))
			{
				foreach ($this->dfields[$row['site_id']] as $dkey => $dval)
				{
					// Empty, Null, Zero, Zilch, Nada...

					if ( ! isset($existing_variables[$dkey])) continue;

					if ($row['field_id_'.$dval] == 0 OR $row['field_id_'.$dval] == '')
					{
						$row[$dkey] = '';
						continue;
					}

					$row[$dkey] = $this->EE->localize->simpl_offset($row['field_id_'.$dval], $row['field_dt_'.$dval]);
				}
			}

			//  parse custom channel fields

			if (isset($this->cfields[$row['site_id']]))
			{
				foreach ($this->cfields[$row['site_id']] as $name => $field_id)
				{
					$row[$name] = '';

					if ( ! isset($existing_variables[$name])) continue;

					if (isset($row['field_id_'.$field_id]) && $row['field_id_'.$field_id] != '')
					{
						$row[$name] = array( $this->EE->functions->encode_ee_tags($row['field_id_'.$field_id]),
										array(
												'text_format'	=> $row['field_ft_'.$field_id],
												'html_format'	=> $row['channel_html_formatting'],
												'auto_links'	=> $row['channel_auto_link_urls'],
												'allow_img_url' => $row['channel_allow_img_urls'],
												'convert_curly' => 'n'
											  ));
					 }
				}
			}

			//  parse custom member fields

			foreach ($this->mfields as $field_name => $field_meta)
			{
				if ( ! isset($existing_variables[$field_name])) continue;

				if ( ! isset($processed_member_fields[$row['member_id']]['m_field_id_'.$field_meta[0]]))
				{
					$processed_member_fields[$row['member_id']]['m_field_id_'.$field_meta[0]] =

											$this->EE->typography->parse_type(
																			$row['m_field_id_'.$field_meta[0]],
																			array(
																					'text_format'	=> $field_meta[1],
																					'html_format'	=> 'safe',
																					'auto_links'	=> 'y',
																					'allow_img_url' => 'n'
																				  )
																		  );
				}

				$row[$field_name] = $processed_member_fields[$row['member_id']]['m_field_id_'.$field_meta[0]];
			}

			//  Load Row onto $parse_data array
			$parse_data[] = $row;
		}

		// Do we have backspacing?
		$this->EE->TMPL->tagparams['backspace'] = '';

		// Process all tags and tagdata!!!

		$this->return_data = $this->EE->TMPL->parse_variables( $this->EE->TMPL->tagdata, $parse_data);

		// Kill multi_field variable
		if (strpos($this->return_data, 'multi_field=') !== FALSE)
		{
			$this->return_data = preg_replace("/".LD."multi_field\=[\"'](.+?)[\"']".RD."/s", '', $this->return_data);
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Parse channel entries
	  */
	function parse_channel_entries()
	{
		$switch = array();
		$processed_member_fields = array();

		//  Set default date header variables
		$heading_date_hourly  = 0;
		$heading_flag_hourly  = 0;
		$heading_flag_weekly  = 1;
		$heading_date_daily	  = 0;
		$heading_flag_daily	  = 0;
		$heading_date_monthly = 0;
		$heading_flag_monthly = 0;
		$heading_date_yearly  = 0;
		$heading_flag_yearly  = 0;

		// Fetch the "category chunk"
		// We'll grab the category data now to avoid processing cycles in the foreach loop below

		$cat_chunk = array();

		if (strpos($this->EE->TMPL->tagdata, LD.'/categories'.RD) !== FALSE)
		{
			if (preg_match_all("/".LD."categories(.*?)".RD."(.*?)".LD.'\/'.'categories'.RD."/s", $this->EE->TMPL->tagdata, $matches))
			{
				for ($j = 0; $j < count($matches[0]); $j++)
				{
					$cat_chunk[] = array($matches[2][$j], $this->EE->functions->assign_parameters($matches[1][$j]), $matches[0][$j]);
				}
	  		}
		}

		//  Fetch all the date-related variables

		$entry_date 		= array();
		$gmt_date 			= array();
		$gmt_entry_date		= array();
		$edit_date 			= array();
		$gmt_edit_date		= array();
		$expiration_date	= array();
		$week_date			= array();

		// We do this here to avoid processing cycles in the foreach loop

		$date_vars = array('entry_date', 'gmt_date', 'gmt_entry_date', 'edit_date', 'gmt_edit_date', 'expiration_date', 'recent_comment_date', 'week_date');
		$date_variables_exist = FALSE;

		foreach ($date_vars as $val)
		{
			if (strpos($this->EE->TMPL->tagdata, LD.$val) === FALSE) continue;

			if (preg_match_all("/".LD.$val."\s+format=([\"'])([^\\1]*?)\\1".RD."/s", $this->EE->TMPL->tagdata, $matches))
			{
				$date_variables_exist = TRUE;

				for ($j = 0; $j < count($matches[0]); $j++)
				{
					$matches[0][$j] = str_replace(array(LD,RD), '', $matches[0][$j]);

					switch ($val)
					{
						case 'entry_date' 			: $entry_date[$matches[0][$j]] = $this->EE->localize->fetch_date_params($matches[2][$j]);
							break;
						case 'gmt_date'				: $gmt_date[$matches[0][$j]] = $this->EE->localize->fetch_date_params($matches[2][$j]);
							break;
						case 'gmt_entry_date'		: $gmt_entry_date[$matches[0][$j]] = $this->EE->localize->fetch_date_params($matches[2][$j]);
							break;
						case 'edit_date' 			: $edit_date[$matches[0][$j]] = $this->EE->localize->fetch_date_params($matches[2][$j]);
							break;
						case 'gmt_edit_date'		: $gmt_edit_date[$matches[0][$j]] = $this->EE->localize->fetch_date_params($matches[2][$j]);
							break;
						case 'expiration_date' 		: $expiration_date[$matches[0][$j]] = $this->EE->localize->fetch_date_params($matches[2][$j]);
							break;
						case 'recent_comment_date' 	: $recent_comment_date[$matches[0][$j]] = $this->EE->localize->fetch_date_params($matches[2][$j]);
							break;
						case 'week_date'			: $week_date[$matches[0][$j]] = $this->EE->localize->fetch_date_params($matches[2][$j]);
							break;
					}
				}
			}
		}

	  	// Are any of the custom fields dates?

		$custom_date_fields = array();

		if (count($this->dfields) > 0)
		{
			foreach ($this->dfields as $site_id => $dfields)
			{
	  			foreach($dfields as $key => $value)
	  			{
	  				if (strpos($this->EE->TMPL->tagdata, LD.$key) === FALSE) continue;

					if (preg_match_all("/".LD.$key."\s+format=[\"'](.*?)[\"']".RD."/s", $this->EE->TMPL->tagdata, $matches))
					{
						for ($j = 0; $j < count($matches[0]); $j++)
						{
							$matches[0][$j] = str_replace(array(LD,RD), '', $matches[0][$j]);

							$custom_date_fields[$matches[0][$j]] = $this->EE->localize->fetch_date_params($matches[1][$j]);
						}
					}
				}
			}
		}

		// And the same again for reverse related entries

		$reverse_markers = array();
		if (preg_match_all("/".LD."REV_REL\[([^\]]+)\]REV_REL".RD."/", $this->EE->TMPL->tagdata, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$reverse_markers[$matches['1'][$j]] = '';
			}
		}

		// Fetch Custom Field Chunks
		// If any of our custom fields are tag pair fields, we'll grab those chunks now

		$pfield_chunk = array();

		if (count($this->pfields) > 0)
		{
			foreach ($this->pfields as $site_id => $pfields)
			{
				$pfield_names = array_intersect($this->cfields[$site_id], array_keys($pfields));

				foreach($pfield_names as $field_name => $field_id)
				{
					$offset = 0;
					
					while (($end = strpos($this->EE->TMPL->tagdata, LD.'/'.$field_name.RD, $offset)) !== FALSE)
					{
						// This hurts soo much. Using custom fields as pair and single vars in the same
						// channel tags could lead to something like this: {field}...{field}inner{/field}
						// There's no efficient regex to match this case, so we'll find the last nested
						// opening tag and re-cut the chunk.

						if (preg_match("/".LD."{$field_name}(.*?)".RD."(.*?)".LD.'\/'.$field_name.RD."/s", $this->EE->TMPL->tagdata, $matches, 0, $offset))
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
							
							$pfield_chunk[$site_id][$field_name][] = array($inner, $this->EE->functions->assign_parameters($params), $chunk);
						}
						
						$offset = $end + 1;
					}
					
					
					
					/*
					if (($end = strpos($this->EE->TMPL->tagdata, LD.'/'.$field_name.RD)) !== FALSE)
					{
						// This hurts soo much. Using custom fields as pair and single vars in the same
						// channel tags could lead to something like this: {field}...{field}inner{/field}
						// There's no efficient regex to match this case, so we'll find the last nested
						// opening tag and re-cut the chunk.

						if (preg_match_all("/".LD."{$field_name}(.*?)".RD."(.*?)".LD.'\/'.$field_name.RD."/s", $this->EE->TMPL->tagdata, $matches))
						{
							for ($j = 0; $j < count($matches[0]); $j++)
							{
								$chunk = $matches[0][$j];
								$params = $matches[1][$j];
								$inner = $matches[2][$j];

								// We might've sandwiched a single tag - no good, check again (:sigh:)
								if ((strpos($chunk, LD.$field_name, 1) !== FALSE) && preg_match_all("/".LD."{$field_name}(.*?)".RD."/s", $chunk, $match))
								{
									// Let's start at the end
									$idx = count($match[0]) - 1;
									$tag = $match[0][$idx];

									// Cut the chunk at the last opening tag (PHP5 could do this with strrpos :-( )
									while (strpos($chunk, $tag, 1) !== FALSE)
									{
										$chunk = substr($chunk, 1);
										$chunk = strstr($chunk, LD.$field_name);
										$inner = substr($chunk, strlen($tag), -strlen(LD.'/'.$field_name.RD));
									}
								}

								$pfield_chunk[$site_id][$field_name][] = array($inner, $this->EE->functions->assign_parameters($params), $chunk);
							}
						}
					}
					*/
				}
			}
		}

		// One more preloop check - custom fields with modifiers in conditionals

		$all_field_names = array();
		
		foreach($this->cfields as $site_id => $fields)
		{
			$all_field_names = array_unique(array_merge($all_field_names, $fields));
		}
		
		$modified_field_options = implode('|', array_keys($all_field_names));
		$modified_conditionals = array();

		if (preg_match_all("/".preg_quote(LD)."((if:(else))*if)\s+(($modified_field_options):(\w+))(.*?)".preg_quote(RD)."/s", $this->EE->TMPL->tagdata, $matches))
		{
			foreach($matches[5] as $match_key => $field_name)
			{
				$modified_conditionals[$field_name][] = $matches[6][$match_key];
			}
		}
		
		$modified_conditionals = array_map('array_unique', $modified_conditionals);
		unset($all_field_names, $modified_field_options);
		
		

		// "Search by Member" link
		// We use this with the {member_search_path} variable

		$result_path = (preg_match("/".LD."member_search_path\s*=(.*?)".RD."/s", $this->EE->TMPL->tagdata, $match)) ? $match[1] : 'search/results';
		$result_path = str_replace(array('"',"'"), "", $result_path);

		$search_link = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Search', 'do_search').'&amp;result_path='.$result_path.'&amp;mbr=';

		// Start the main processing loop

		// For our hook to work, we need to grab the result array
		$query_result = $this->query->result_array();

		// Ditch everything else
		$this->query->free_result();
		unset($this->query);

		// -------------------------------------------
		// 'channel_entries_query_result' hook.
		//  - Take the whole query result array, do what you wish
		//  - added 1.6.7
		//
			if ($this->EE->extensions->active_hook('channel_entries_query_result') === TRUE)
			{
				$query_result = $this->EE->extensions->call('channel_entries_query_result', $this, $query_result);
				if ($this->EE->extensions->end_script === TRUE) return $this->EE->TMPL->tagdata;
			}
		//
		// -------------------------------------------

		$total_results = count($query_result);

		$site_pages = $this->EE->config->item('site_pages');

		foreach ($query_result as $count => $row)
		{
			// Fetch the tag block containing the variables that need to be parsed

			$tagdata = $this->EE->TMPL->tagdata;

			$row['count']				= $count+1;
			$row['page_uri']			= '';
			$row['page_url']			= '';
			$row['total_results']		= $total_results;
			$row['absolute_count']		= $this->p_page + $row['count'];
			$row['absolute_results']	= ($this->absolute_results === NULL) ? $total_results : $this->absolute_results;
			
			if ($site_pages !== FALSE && isset($site_pages[$row['site_id']]['uris'][$row['entry_id']]))
			{
				$row['page_uri'] = $site_pages[$row['site_id']]['uris'][$row['entry_id']];
				$row['page_url'] = $this->EE->functions->create_page_url($site_pages[$row['site_id']]['url'], $site_pages[$row['site_id']]['uris'][$row['entry_id']]);
			}

			// -------------------------------------------
			// 'channel_entries_tagdata' hook.
			//  - Take the entry data and tag data, do what you wish
			//
				if ($this->EE->extensions->active_hook('channel_entries_tagdata') === TRUE)
				{
					$tagdata = $this->EE->extensions->call('channel_entries_tagdata', $tagdata, $row, $this);
					if ($this->EE->extensions->end_script === TRUE) return $tagdata;
				}
			//
			// -------------------------------------------

			// -------------------------------------------
			// 'channel_entries_row' hook.
			//  - Take the entry data, do what you wish
			//  - added 1.6.7
			//
				if ($this->EE->extensions->active_hook('channel_entries_row') === TRUE)
				{
					$row = $this->EE->extensions->call('channel_entries_row', $this, $row);
					if ($this->EE->extensions->end_script === TRUE) return $tagdata;
				}
			//
			// -------------------------------------------

			//  Adjust dates if needed
			// If the "dst_enabled" item is set in any given entry
			// we need to offset to the timestamp by an hour

			if ( ! isset($row['dst_enabled']))
				$row['dst_enabled'] = 'n';

			/**--
			/**  Reset custom date fields
			/**--*/

			// Since custom date fields columns are integer types by default, if they
			// don't contain any data they return a zero.
			// This creates a problem if conditionals are used with those fields.
			// For example, if an admin has this in a template:  {if mydate == ''}
			// Since the field contains a zero it would never evaluate TRUE.
			// Therefore we'll reset any zero dates to nothing.

			if (isset($this->dfields[$row['site_id']]) && count($this->dfields[$row['site_id']]) > 0)
			{
				foreach ($this->dfields[$row['site_id']] as $dkey => $dval)
				{
					// While we're at it, kill any formatting
					$row['field_ft_'.$dval] = 'none';
					if (isset($row['field_id_'.$dval]) AND $row['field_id_'.$dval] == 0)
					{
						$row['field_id_'.$dval] = '';
					}
				}
			}
			// While we're at it, do the same for related entries.
			if (isset($this->rfields[$row['site_id']]) && count($this->rfields[$row['site_id']]) > 0)
			{
				foreach ($this->rfields[$row['site_id']] as $rkey => $rval)
				{
					$row['field_ft_'.$rval] = 'none';
				}
			}

			// Reverse related markers
			$j = 0;

			foreach ($reverse_markers as $k => $v)
			{
				$this->reverse_related_entries[$row['entry_id']][$j] = $k;
				$tagdata = str_replace(	LD."REV_REL[".$k."]REV_REL".RD, LD."REV_REL[".$k."][".$row['entry_id']."]REV_REL".RD, $tagdata);
				$j++;
			}

			// Conditionals

			$cond = $row;
			$cond['logged_in']			= ($this->EE->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
			$cond['logged_out']			= ($this->EE->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';

			if ((($row['comment_expiration_date'] > 0 && $this->EE->localize->now > $row['comment_expiration_date']) && $this->EE->config->item('comment_moderation_override') !== 'y') OR $row['allow_comments'] == 'n' OR (isset($row['comment_system_enabled']) && $row['comment_system_enabled']  == 'n'))
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

			$cond['signature_image']		= ($row['sig_img_filename'] == '' OR $this->EE->config->item('enable_signatures') == 'n' OR $this->EE->session->userdata('display_signatures') == 'n') ? 'FALSE' : 'TRUE';
			$cond['avatar']					= ($row['avatar_filename'] == '' OR $this->EE->config->item('enable_avatars') == 'n' OR $this->EE->session->userdata('display_avatars') == 'n') ? 'FALSE' : 'TRUE';
			$cond['photo']					= ($row['photo_filename'] == '' OR $this->EE->config->item('enable_photos') == 'n' OR $this->EE->session->userdata('display_photos') == 'n') ? 'FALSE' : 'TRUE';
			$cond['forum_topic']			= (empty($row['forum_topic_id'])) ? 'FALSE' : 'TRUE';
			$cond['not_forum_topic']		= ( ! empty($row['forum_topic_id'])) ? 'FALSE' : 'TRUE';
			$cond['category_request']		= ($this->cat_request === FALSE) ? 'FALSE' : 'TRUE';
			$cond['not_category_request']	= ($this->cat_request !== FALSE) ? 'FALSE' : 'TRUE';
			$cond['channel']				= $row['channel_title'];
			$cond['channel_short_name']		= $row['channel_name'];
			$cond['author']					= ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
			$cond['photo_url']				= $this->EE->config->slash_item('photo_url').$row['photo_filename'];
			$cond['photo_image_width']		= $row['photo_width'];
			$cond['photo_image_height']		= $row['photo_height'];
			$cond['avatar_url']				= $this->EE->config->slash_item('avatar_url').$row['avatar_filename'];
			$cond['avatar_image_width']		= $row['avatar_width'];
			$cond['avatar_image_height']	= $row['avatar_height'];
			$cond['signature_image_url']	= $this->EE->config->slash_item('sig_img_url').$row['sig_img_filename'];
			$cond['signature_image_width']	= $row['sig_img_width'];
			$cond['signature_image_height']	= $row['sig_img_height'];
			$cond['relative_date']			= $this->EE->localize->format_timespan($this->EE->localize->now - $row['entry_date']);

			if (isset($this->cfields[$row['site_id']]))
			{
				foreach($this->cfields[$row['site_id']] as $key => $value)
				{
					$cond[$key] = ( ! isset($row['field_id_'.$value])) ? '' : $row['field_id_'.$value];
					
					// Is this field used with a modifier anywhere?
					if (isset($modified_conditionals[$key]) && count($modified_conditionals[$key]))
					{
						$this->EE->load->library('api');
						$this->EE->api->instantiate('channel_fields');

						if ($this->EE->api_channel_fields->setup_handler($value))
						{
							foreach($modified_conditionals[$key] as $modifier)
							{
								$this->EE->api_channel_fields->apply('_init', array(array('row' => $row)));
								$data = $this->EE->api_channel_fields->apply('pre_process', array($cond[$key]));
								if ($this->EE->api_channel_fields->check_method_exists('replace_'.$modifier))
								{
									$cond[$key.':'.$modifier] = $this->EE->api_channel_fields->apply('replace_'.$modifier, array($data, array(), FALSE));
								}
								else
								{							
									$cond[$key.':'.$modifier] = FALSE;
									$this->EE->TMPL->log_item('Unable to find parse type for custom field conditional: '.$key.':'.$modifier);
								}
							}
						}
					}
				}
			}

			foreach($this->mfields as $key => $value)
			{
				$cond[$key] = ( ! array_key_exists('m_field_id_'.$value[0], $row)) ? '' : $row['m_field_id_'.$value[0]];
				//( ! isset($row['m_field_id_'.$value[0]])) ? '' : $row['m_field_id_'.$value[0]];
			}

			//$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);


			// Reset custom variable pair cache
			$parsed_custom_pairs = array();

			//  Parse Variable Pairs
			foreach ($this->EE->TMPL->var_pair as $key => $val)
			{
				//  parse categories
				if (strncmp($key, 'categories', 10) == 0)
				{
					if (isset($this->categories[$row['entry_id']]) AND is_array($this->categories[$row['entry_id']]) AND count($cat_chunk) > 0)
					{
						foreach ($cat_chunk as $catkey => $catval)
						{
							$cats = '';
							$i = 0;
							
							//  We do the pulling out of categories before the "prepping" of conditionals
							//  So, we have to do it here again too.  How annoying...
							$catval[0] = $this->EE->functions->prep_conditionals($catval[0], $cond);
							$catval[2] = $this->EE->functions->prep_conditionals($catval[2], $cond);

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

							foreach ($this->categories[$row['entry_id']] as $k => $v)
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
											$temp = preg_replace("#".LD."path=.+?".RD."#", $this->EE->functions->remove_double_slashes($this->EE->functions->create_url($match).'/'.$this->reserved_cat_segment.'/'.$v[6]), $temp, 1);
										}
										else
										{
											$temp = preg_replace("#".LD."path=.+?".RD."#", $this->EE->functions->remove_double_slashes($this->EE->functions->create_url($match).'/C'.$v[0]), $temp, 1);
										}
									}
								}
								else
								{
									$temp = preg_replace("#".LD."path=.+?".RD."#", $this->EE->functions->create_url("SITE_INDEX"), $temp);
								}

								$cat_vars = array('category_name'			=> $v[2],
												  'category_url_title'		=> $v[6],
												  'category_description'	=> (isset($v[4])) ? $v[4] : '',
												  'category_group'			=> (isset($v[5])) ? $v[5] : '',
												  'category_image'			=> $v[3],
												  'category_id'				=> $v[0],
												  'parent_id'				=> $v[1]);

								// add custom fields for conditionals prep
								foreach ($this->catfields as $cv)
								{
									$cat_vars[$cv['field_name']] = ( ! isset($v['field_id_'.$cv['field_id']])) ? '' : $v['field_id_'.$cv['field_id']];
								}

								$temp = $this->EE->functions->prep_conditionals($temp, $cat_vars);

								$temp = str_replace(array(LD."category_id".RD,
														  LD."category_name".RD,
														  LD."category_url_title".RD,
														  LD."category_image".RD,
														  LD."category_group".RD,
														  LD.'category_description'.RD,
														  LD.'parent_id'.RD),
													array($v[0],
														  $v[2],
														  $v[6],
														  $v[3],
														  (isset($v[5])) ? $v[5] : '',
														  (isset($v[4])) ? $v[4] : '',
														  $v[1]
														  ),
													$temp);

								foreach($this->catfields as $cv2)
								{
									if (isset($v['field_id_'.$cv2['field_id']]) AND $v['field_id_'.$cv2['field_id']] != '')
									{
										$field_content = $this->EE->typography->parse_type($v['field_id_'.$cv2['field_id']],
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

									$temp = $this->EE->functions->remove_double_slashes($temp);
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

							$tagdata = str_replace($catval[2], $cats, $tagdata);
						}
					}
					else
					{
						$tagdata = $this->EE->TMPL->delete_var_pairs($key, 'categories', $tagdata);
					}
				}
				// END CATEGORIES

				// parse custom field pairs (file, checkbox, multiselect)

				// First we need the key name out of the {name foo=bar|baz} mess
				$key_name = $key;
				$parse_fnc = 'replace_tag';

				if (($spc = strpos($key, ' ')) !== FALSE)
				{
					$key_name = substr($key, 0, $spc);
				}

				/* Currently does not work with pair fields
				if (($cln = strpos($key, ':')) !== FALSE)
				{
					$parse_fnc = 'replace_'.substr($key_name, $cln + 1);
					$key_name = substr($key_name, 0, $cln);
				}
				*/
				
				// Is it a custom field?
				if (isset($this->cfields[$row['site_id']][$key_name]) && ! in_array($key_name, $parsed_custom_pairs))
				{
					// We parse all chunks, but TMPL->var_pairs will still have the others
					// so we'll keep track of these and bail if we've parsed it
					
					$parsed_custom_pairs[] = $key_name;

					// Is this custom field part of the current channel row?
					if (isset($row['field_id_'.$this->cfields[$row['site_id']][$key_name]]) && isset($this->pfields[$row['site_id']][$this->cfields[$row['site_id']][$key_name]]))
					{
						$this->EE->load->library('api');
						$this->EE->api->instantiate('channel_fields');

						if ($this->EE->api_channel_fields->setup_handler($this->cfields[$row['site_id']][$key_name]))
						{
							$this->EE->api_channel_fields->apply('_init', array(array('row' => $row)));

							// Preprocess
							$data = $this->EE->api_channel_fields->apply('pre_process', array($row['field_id_'.$this->cfields[$row['site_id']][$key_name]]));

							// Blast through all the chunks
							foreach($pfield_chunk[$row['site_id']][$key_name] as $chk_data)
							{
								// $chk_data = array(chunk_contents, parameters, chunk_with_tag);
								$tpl_chunk = $this->EE->api_channel_fields->apply('replace_tag', array($data, $chk_data[1], $chk_data[0]));

								// Replace the chunk
								$tagdata = str_replace($chk_data[2], $tpl_chunk, $tagdata);
							}
						}
						else
						{
							$this->EE->TMPL->log_item('Unable to find field type for custom field: '.$key);

							$tagdata = $this->EE->TMPL->delete_var_pairs($key, $key_name, $tagdata);
						}
					}
					else
					{
						$tagdata = $this->EE->TMPL->delete_var_pairs($key, $key_name, $tagdata);
					}
				}
				// END CUSTOM FIELD PAIRS


				//  parse date heading
				if (strncmp($key, 'date_heading', 12) == 0)
				{
					// Set the display preference

					$display = (is_array($val) AND isset($val['display'])) ? $val['display'] : 'daily';

					//  Hourly header
					if ($display == 'hourly')
					{
						$heading_date_hourly = gmdate('YmdH', $this->EE->localize->set_localized_time($row['entry_date']));

						if ($heading_date_hourly == $heading_flag_hourly)
						{
							$tagdata = $this->EE->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
						}
						else
						{
							$tagdata = $this->EE->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

							$heading_flag_hourly = $heading_date_hourly;
						}
					}
					//  Weekly header
					elseif ($display == 'weekly')
					{
						$temp_date = $this->EE->localize->set_localized_time($row['entry_date']);

						// date()'s week variable 'W' starts weeks on Monday per ISO-8601.
						// By default we start weeks on Sunday, so we need to do a little dance for
						// entries made on Sundays to make sure they get placed in the right week heading
						if (strtolower($this->EE->TMPL->fetch_param('start_day')) != 'monday' && gmdate('w', $this->EE->localize->set_localized_time($row['entry_date'])) == 0)
						{
							// add 7 days to toss us into the next ISO-8601 week
							$heading_date_weekly = gmdate('YW', $temp_date + 604800);
						}
						else
						{
							$heading_date_weekly = gmdate('YW', $temp_date);
						}

						if ($heading_date_weekly == $heading_flag_weekly)
						{
							$tagdata = $this->EE->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
						}
						else
						{
							$tagdata = $this->EE->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

							$heading_flag_weekly = $heading_date_weekly;
						}
					}
					//  Monthly header
					elseif ($display == 'monthly')
					{
						$heading_date_monthly = gmdate('Ym', $this->EE->localize->set_localized_time($row['entry_date']));

						if ($heading_date_monthly == $heading_flag_monthly)
						{
							$tagdata = $this->EE->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
						}
						else
						{
							$tagdata = $this->EE->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

							$heading_flag_monthly = $heading_date_monthly;
						}
					}
					//  Yearly header
					elseif ($display == 'yearly')
					{
						$heading_date_yearly = gmdate('Y', $this->EE->localize->set_localized_time($row['entry_date']));

						if ($heading_date_yearly == $heading_flag_yearly)
						{
							$tagdata = $this->EE->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
						}
						else
						{
							$tagdata = $this->EE->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

							$heading_flag_yearly = $heading_date_yearly;
						}
					}
					//  Default (daily) header
					else
					{
			 			$heading_date_daily = gmdate('Ymd', $this->EE->localize->set_localized_time($row['entry_date']));
			
						if ($heading_date_daily == $heading_flag_daily)
						{
							$tagdata = $this->EE->TMPL->delete_var_pairs($key, 'date_heading', $tagdata);
						}
						else
						{
							$tagdata = $this->EE->TMPL->swap_var_pairs($key, 'date_heading', $tagdata);

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
						if ( ! isset($query_result[$row['count']]) OR
							gmdate('YmdH', $this->EE->localize->set_localized_time($row['entry_date'])) != gmdate('YmdH', $this->EE->localize->set_localized_time($query_result[$row['count']]['entry_date'])))
						{
							$tagdata = $this->EE->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
						}
						else
						{
							$tagdata = $this->EE->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
						}
					}
					//  Weekly footer
					elseif ($display == 'weekly')
					{
						if ( ! isset($query_result[$row['count']]) OR
							gmdate('YW', $this->EE->localize->set_localized_time($row['entry_date'])) != gmdate('YW', $this->EE->localize->set_localized_time($query_result[$row['count']]['entry_date'])))
						{
							$tagdata = $this->EE->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
						}
						else
						{
							$tagdata = $this->EE->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
						}
					}
					//  Monthly footer
					elseif ($display == 'monthly')
					{
						if ( ! isset($query_result[$row['count']]) OR
							gmdate('Ym', $this->EE->localize->set_localized_time($row['entry_date'])) != gmdate('Ym', $this->EE->localize->set_localized_time($query_result[$row['count']]['entry_date'])))
						{
							$tagdata = $this->EE->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
						}
						else
						{
							$tagdata = $this->EE->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
						}
					}
					//  Yearly footer
					elseif ($display == 'yearly')
					{
						if ( ! isset($query_result[$row['count']]) OR
							gmdate('Y', $this->EE->localize->set_localized_time($row['entry_date'])) != gmdate('Y', $this->EE->localize->set_localized_time($query_result[$row['count']]['entry_date'])))
						{
							$tagdata = $this->EE->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
						}
						else
						{
							$tagdata = $this->EE->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
						}
					}
					//  Default (daily) footer
					else
					{
						if ( ! isset($query_result[$row['count']]) OR
							gmdate('Ymd', $this->EE->localize->set_localized_time($row['entry_date'])) != gmdate('Ymd', $this->EE->localize->set_localized_time($query_result[$row['count']]['entry_date'])))
						{
							$tagdata = $this->EE->TMPL->swap_var_pairs($key, 'date_footer', $tagdata);
						}
						else
						{
							$tagdata = $this->EE->TMPL->delete_var_pairs($key, 'date_footer', $tagdata);
						}
					}
				}
				// END DATE FOOTER

			}
			// END VARIABLE PAIRS

			// We swap out the conditionals after pairs are parsed so they don't interfere
			// with the string replace
			$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);

			//  Parse "single" variables
			foreach ($this->EE->TMPL->var_single as $key => $val)
			{
				/**--------
				/**  parse simple conditionals: {body|more|summary}
				/**--------*/

				// Note:  This must happen first.

				if (strpos($key, '|') !== FALSE && is_array($val))
				{
					foreach($val as $item)
					{
						// Basic fields

						if (isset($row[$item]) AND $row[$item] != "")
						{
							$tagdata = $this->EE->TMPL->swap_var_single($key, $row[$item], $tagdata);

							continue;
						}

						// Custom channel fields

						if ( isset( $this->cfields[$row['site_id']][$item] ) AND isset( $row['field_id_'.$this->cfields[$row['site_id']][$item]] ) AND $row['field_id_'.$this->cfields[$row['site_id']][$item]] != "")
						{
							$entry = $this->EE->typography->parse_type(
																$row['field_id_'.$this->cfields[$row['site_id']][$item]],
																array(
																		'text_format'	=> $row['field_ft_'.$this->cfields[$row['site_id']][$item]],
																		'html_format'	=> $row['channel_html_formatting'],
																		'auto_links'	=> $row['channel_auto_link_urls'],
																		'allow_img_url' => $row['channel_allow_img_urls']
																	)
															 );

							$tagdata = $this->EE->TMPL->swap_var_single($key, $entry, $tagdata);

							continue;
						}
					}

					// Garbage collection
					$val = '';
					$tagdata = $this->EE->TMPL->swap_var_single($key, "", $tagdata);
				}


				//  parse {switch} variable
				if (preg_match("/^switch\s*=.+/i", $key))
				{
					$sparam = $this->EE->functions->assign_parameters($key);

					$sw = '';

					if (isset($sparam['switch']))
					{
						$sopt = explode("|", $sparam['switch']);

						$sw = $sopt[($count + count($sopt)) % count($sopt)];
					}

					$tagdata = $this->EE->TMPL->swap_var_single($key, $sw, $tagdata);
				}

				//  parse entry date
				if (isset($entry_date[$key]))
				{
					$val = str_replace($entry_date[$key], $this->EE->localize->convert_timestamp($entry_date[$key], $row['entry_date'], TRUE), $val);

					$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
				}

				//  Recent Comment Date
				if (isset($recent_comment_date[$key]))
				{
					if ($row['recent_comment_date'] != 0)
					{
						$val = str_replace($recent_comment_date[$key], $this->EE->localize->convert_timestamp($recent_comment_date[$key], $row['recent_comment_date'], TRUE), $val);

						$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
					}
					else
					{
						$tagdata = str_replace(LD.$key.RD, '', $tagdata);
					}
				}


				//  GMT date - entry date in GMT
				if (isset($gmt_entry_date[$key]))
				{
					$val = str_replace($gmt_entry_date[$key], $this->EE->localize->convert_timestamp($gmt_entry_date[$key], $row['entry_date'], FALSE), $val);

					$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
				}

				if (isset($gmt_date[$key]))
				{
					$val = str_replace($gmt_date[$key], $this->EE->localize->convert_timestamp($gmt_date[$key], $row['entry_date'], FALSE), $val);

					$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
				}

				//  parse "last edit" date
				if (isset($edit_date[$key]))
				{
					$val = str_replace($edit_date[$key], $this->EE->localize->convert_timestamp($edit_date[$key], $this->EE->localize->timestamp_to_gmt($row['edit_date']), TRUE), $val);

					$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
				}

				//  "last edit" date as GMT
				if (isset($gmt_edit_date[$key]))
				{
					$val = str_replace($gmt_edit_date[$key], $this->EE->localize->convert_timestamp($gmt_edit_date[$key], $this->EE->localize->timestamp_to_gmt($row['edit_date']), FALSE), $val);

					$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
				}


				//  parse expiration date
				if (isset($expiration_date[$key]))
				{
					if ($row['expiration_date'] != 0)
					{
						$val = str_replace($expiration_date[$key], $this->EE->localize->convert_timestamp($expiration_date[$key], $row['expiration_date'], TRUE), $val);

						$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
					}
					else
					{
						$tagdata = str_replace(LD.$key.RD, "", $tagdata);
					}
				}


				//  "week_date"
				if (isset($week_date[$key]))
				{
					// Subtract the number of days the entry is "into" the week to get zero (Sunday)
					// If the entry date is for Sunday, and Monday is being used as the week's start day,
					// then we must back things up by six days

					$offset = 0;

					if (strtolower($this->EE->TMPL->fetch_param('start_day')) == 'monday')
					{
						$day_of_week = $this->EE->localize->convert_timestamp('%w', $row['entry_date'], TRUE);

						if ($day_of_week == '0')
						{
							$offset = -518400; // back six days
						}
						else
						{
							$offset = 86400; // plus one day
						}
					}

					$week_start_date = $row['entry_date'] - ($this->EE->localize->convert_timestamp('%w', $row['entry_date'], TRUE) * 60 * 60 * 24) + $offset;

					$val = str_replace($week_date[$key], $this->EE->localize->convert_timestamp($week_date[$key], $week_start_date, TRUE), $val);

					$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
				}

				//  parse profile path
				if (strncmp($key, 'profile_path', 12) == 0)
				{
					$tagdata = $this->EE->TMPL->swap_var_single(
														$key,
														$this->EE->functions->create_url($this->EE->functions->extract_path($key).'/'.$row['member_id']),
														$tagdata
													 );
				}

				//  {member_search_path}
				if (strncmp($key, 'member_search_path', 18) == 0)
				{
					$tagdata = $this->EE->TMPL->swap_var_single(
														$key,
														$search_link.$row['member_id'],
														$tagdata
													 );
				}


				//  parse comment_path
				if (strncmp($key, 'comment_path', 12) == 0 OR strncmp($key, 'entry_id_path', 13) == 0)
				{
					$path = ($this->EE->functions->extract_path($key) != '' AND $this->EE->functions->extract_path($key) != 'SITE_INDEX') ? $this->EE->functions->extract_path($key).'/'.$row['entry_id'] : $row['entry_id'];

					$tagdata = $this->EE->TMPL->swap_var_single(
														$key,
														$this->EE->functions->create_url($path),
														$tagdata
													 );
				}

				//  parse URL title path
				if (strncmp($key, 'url_title_path', 14) == 0)
				{
					$path = ($this->EE->functions->extract_path($key) != '' AND $this->EE->functions->extract_path($key) != 'SITE_INDEX') ? $this->EE->functions->extract_path($key).'/'.$row['url_title'] : $row['url_title'];

					$tagdata = $this->EE->TMPL->swap_var_single(
														$key,
														$this->EE->functions->create_url($path),
														$tagdata
													 );
				}

				//  parse title permalink
				if (strncmp($key, 'title_permalink', 15) == 0)
				{
					$path = ($this->EE->functions->extract_path($key) != '' AND $this->EE->functions->extract_path($key) != 'SITE_INDEX') ? $this->EE->functions->extract_path($key).'/'.$row['url_title'] : $row['url_title'];

					$tagdata = $this->EE->TMPL->swap_var_single(
														$key,
														$this->EE->functions->create_url($path, FALSE),
														$tagdata
													 );
				}

				//  parse permalink
				if (strncmp($key, 'permalink', 9) == 0)
				{
					$path = ($this->EE->functions->extract_path($key) != '' AND $this->EE->functions->extract_path($key) != 'SITE_INDEX') ? $this->EE->functions->extract_path($key).'/'.$row['entry_id'] : $row['entry_id'];

					$tagdata = $this->EE->TMPL->swap_var_single(
														$key,
														$this->EE->functions->create_url($path, FALSE),
														$tagdata
													 );
				}


				//  {comment_auto_path}
				if ($key == "comment_auto_path")
				{
					$path = ($row['comment_url'] == '') ? $row['channel_url'] : $row['comment_url'];

					$tagdata = $this->EE->TMPL->swap_var_single($key, $path, $tagdata);
				}

				//  {comment_url_title_auto_path}
				if ($key == "comment_url_title_auto_path")
				{
					$path = ($row['comment_url'] == '') ? $row['channel_url'] : $row['comment_url'];

					$tagdata = $this->EE->TMPL->swap_var_single(
														$key,
														reduce_double_slashes($path.'/'.$row['url_title']),
														$tagdata
													 );
				}

				//  {comment_entry_id_auto_path}
				if ($key == "comment_entry_id_auto_path")
				{
					$path = ($row['comment_url'] == '') ? $row['channel_url'] : $row['comment_url'];

					$tagdata = $this->EE->TMPL->swap_var_single(
														$key,
														reduce_double_slashes($path.'/'.$row['entry_id']),
														$tagdata
													 );
				}

				//  {author}
				if ($key == "author")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'], $tagdata);
				}

				//  {channel}
				if ($key == "channel")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $row['channel_title'], $tagdata);
				}

				//  {channel_short_name}
				if ($key == "channel_short_name")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $row['channel_name'], $tagdata);
				}

				//  {relative_date}

				if ($key == "relative_date")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $this->EE->localize->format_timespan($this->EE->localize->now - $row['entry_date']), $tagdata);
				}

				//  {trimmed_url} - used by Atom feeds
				if ($key == "trimmed_url")
				{
					$channel_url = (isset($row['channel_url']) AND $row['channel_url'] != '') ? $row['channel_url'] : '';

					$channel_url = str_replace(array('http://','www.'), '', $channel_url);
					$xe = explode("/", $channel_url);
					$channel_url = current($xe);

					$tagdata = $this->EE->TMPL->swap_var_single($val, $channel_url, $tagdata);
				}

				//  {relative_url} - used by Atom feeds
				if ($key == "relative_url")
				{
					$channel_url = (isset($row['channel_url']) AND $row['channel_url'] != '') ? $row['channel_url'] : '';
					$channel_url = str_replace('http://', '', $channel_url);

					if ($x = strpos($channel_url, "/"))
					{
						$channel_url = substr($channel_url, $x + 1);
					}

					$channel_url = rtrim($channel_url, '/');

					$tagdata = $this->EE->TMPL->swap_var_single($val, $channel_url, $tagdata);
				}

				//  {url_or_email}
				if ($key == "url_or_email")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, ($row['url'] != '') ? $row['url'] : $row['email'], $tagdata);
				}

				//  {url_or_email_as_author}
				if ($key == "url_or_email_as_author")
				{
					$name = ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];

					if ($row['url'] != '')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$name."</a>", $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, $this->EE->typography->encode_email($row['email'], $name), $tagdata);
					}
				}


				//  {url_or_email_as_link}
				if ($key == "url_or_email_as_link")
				{
					if ($row['url'] != '')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$row['url']."</a>", $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, $this->EE->typography->encode_email($row['email']), $tagdata);
					}
				}

				//  {signature}
				if ($key == "signature")
				{
					if ($this->EE->session->userdata('display_signatures') == 'n' OR $row['signature'] == '' OR $this->EE->session->userdata('display_signatures') == 'n')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, '', $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key,
														$this->EE->typography->parse_type($row['signature'], array(
																					'text_format'	=> 'xhtml',
																					'html_format'	=> 'safe',
																					'auto_links'	=> 'y',
																					'allow_img_url' => $this->EE->config->item('sig_allow_img_hotlink')
																				)
																			), $tagdata);
					}
				}


				if ($key == "signature_image_url")
				{
					if ($this->EE->session->userdata('display_signatures') == 'n' OR $row['sig_img_filename'] == ''  OR $this->EE->session->userdata('display_signatures') == 'n')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, '', $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('signature_image_width', '', $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('signature_image_height', '', $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, $this->EE->config->slash_item('sig_img_url').$row['sig_img_filename'], $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('signature_image_width', $row['sig_img_width'], $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('signature_image_height', $row['sig_img_height'], $tagdata);
					}
				}

				if ($key == "avatar_url")
				{
					if ($this->EE->session->userdata('display_avatars') == 'n' OR $row['avatar_filename'] == ''  OR $this->EE->session->userdata('display_avatars') == 'n')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, '', $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('avatar_image_width', '', $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('avatar_image_height', '', $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, $this->EE->config->slash_item('avatar_url').$row['avatar_filename'], $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('avatar_image_width', $row['avatar_width'], $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('avatar_image_height', $row['avatar_height'], $tagdata);
					}
				}

				if ($key == "photo_url")
				{
					if ($this->EE->session->userdata('display_photos') == 'n' OR $row['photo_filename'] == ''  OR $this->EE->session->userdata('display_photos') == 'n')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, '', $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('photo_image_width', '', $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('photo_image_height', '', $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($key, $this->EE->config->slash_item('photo_url').$row['photo_filename'], $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('photo_image_width', $row['photo_width'], $tagdata);
						$tagdata = $this->EE->TMPL->swap_var_single('photo_image_height', $row['photo_height'], $tagdata);
					}
				}

				//  parse {title}
				if ($key == 'title')
				{
					$row['title'] = str_replace(array('{', '}'), array('&#123;', '&#125;'), $row['title']);
					$tagdata = $this->EE->TMPL->swap_var_single($val,  $this->EE->typography->format_characters($row['title']), $tagdata);
				}

				//  parse basic fields (username, screen_name, etc.)
				//  Use array_key_exists to handle null values

				if (array_key_exists($val, $row))
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $row[$val], $tagdata);
				}

				//  parse custom date fields
				if (isset($custom_date_fields[$key]) && isset($this->dfields[$row['site_id']]))
				{
					foreach ($this->dfields[$row['site_id']] as $dkey => $dval)
					{
						if (strncmp($key.' ', $dkey.' ', strlen($dkey.' ')) !== 0)
							continue;

						if ($row['field_id_'.$dval] == 0 OR $row['field_id_'.$dval] == '')
						{
							$tagdata = $this->EE->TMPL->swap_var_single($key, '', $tagdata);
							continue;
						}

						// use a temporary variable in case the custom date variable is used
						// multiple times with different formats; prevents localization from
						// occurring multiple times on the same value
						$temp_val = $row['field_id_'.$dval];

						$localize = TRUE;
						if (isset($row['field_dt_'.$dval]) AND $row['field_dt_'.$dval] != '')
						{
							$localize = TRUE;
							if ($row['field_dt_'.$dval] != '')
							{
								$temp_val = $this->EE->localize->offset_entry_dst($temp_val, $row['dst_enabled']);
								$temp_val = $this->EE->localize->simpl_offset($temp_val, $row['field_dt_'.$dval]);
								$localize = FALSE;
							}
						}

						$val = str_replace($custom_date_fields[$key], $this->EE->localize->convert_timestamp($custom_date_fields[$key], $temp_val, $localize), $val);

						$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);
					}
				}

				//  Assign Related Entry IDs

				// When an entry has related entries within it, since the related entry ID
				// is stored in the custom field itself we need to pull it out and set it
				// aside so that when the related stuff is parsed out we'll have it.
				// We also need to modify the marker in the template so that we can replace
				// it with the right entry

				if (isset($this->rfields[$row['site_id']][$val]))
				{
					// No relationship?  Ditch the marker
					if ( !  isset($row['field_id_'.$this->cfields[$row['site_id']][$val]]) OR
						 $row['field_id_'.$this->cfields[$row['site_id']][$val]] == 0 OR
						 ! preg_match_all("/".LD."REL\[".$val."\](.+?)REL".RD."/", $tagdata, $match)
						)
					{
						// replace the marker with the {if no_related_entries} content
						preg_match_all("/".LD."REL\[".$val."\](.+?)REL".RD."/", $tagdata, $matches);

						foreach ($matches[1] as $match)
						{
							$tagdata = preg_replace("/".LD."REL\[".$val."\](.+?)REL".RD."/", $this->EE->TMPL->related_data[$match]['no_rel_content'], $tagdata);
						}
					}
					else
					{
						for ($j = 0; $j < count($match[1]); $j++)
						{
							$this->related_entries[] = $row['field_id_'.$this->cfields[$row['site_id']][$val]].'_'.$match[1][$j];
							$tagdata = preg_replace("/".LD."REL\[".$val."\](.+?)REL".RD."/", LD."REL[".$row['field_id_'.$this->cfields[$row['site_id']][$val]]."][".$val."]\\1REL".RD, $tagdata);
						}

						$tagdata = $this->EE->TMPL->swap_var_single($val, '', $tagdata);
					}
				}

				// Clean up any unparsed relationship fields

				if (isset($this->rfields[$row['site_id']]) && count($this->rfields[$row['site_id']]) > 0)
				{
					$tagdata = preg_replace("/".LD."REL\[".preg_quote($val,'/')."\](.+?)REL".RD."/", "", $tagdata);
				}

				// parse custom channel fields

				$params = array();
				$parse_fnc = 'replace_tag';
				$replace = $key;

				if (($spc = strpos($key, ' ')) !== FALSE)
				{
					$params = $this->EE->functions->assign_parameters($key);
					$val = $key = substr($key, 0, $spc);
				}
				
				if (($cln = strpos($key, ':')) !== FALSE)
				{
					$parse_fnc = 'replace_'.substr($key, $cln + 1);
					$val = $key = substr($key, 0, $cln);
				}

				if (isset($this->cfields[$row['site_id']][$key]))
				{
					if ( ! isset($row['field_id_'.$this->cfields[$row['site_id']][$val]]) OR $row['field_id_'.$this->cfields[$row['site_id']][$val]] == '')
					{
						$entry = '';
					}
					else
					{
						$this->EE->load->library('api');
						$this->EE->api->instantiate('channel_fields');

						$field_id = $this->cfields[$row['site_id']][$key];

						if ($this->EE->api_channel_fields->setup_handler($field_id))
						{
							$this->EE->api_channel_fields->apply('_init', array(array('row' => $row)));
							$data = $this->EE->api_channel_fields->apply('pre_process', array($row['field_id_'.$field_id]));
							
							
							if ($this->EE->api_channel_fields->check_method_exists($parse_fnc))
							{
								$entry = $this->EE->api_channel_fields->apply($parse_fnc, array($data, $params, FALSE));
							}
							else
							{							
								$entry = '';
								$this->EE->TMPL->log_item('Unable to find parse type for custom field: '.$parse_fnc);
							}							
						}
						else
						{
							// Couldn't find a fieldtype
							$entry = $this->EE->typography->parse_type(
																$this->EE->functions->encode_ee_tags($row['field_id_'.$this->cfields[$row['site_id']][$val]]),
																array(
																		'text_format'	=> $row['field_ft_'.$this->cfields[$row['site_id']][$val]],
																		'html_format'	=> $row['channel_html_formatting'],
																		'auto_links'	=> $row['channel_auto_link_urls'],
																		'allow_img_url' => $row['channel_allow_img_urls']
																	  )
															  );
						}
					 }

					// prevent accidental parsing of other channel variables in custom field data
					if (strpos($entry, '{') !== FALSE)
					{
						$this->EE->load->helper('string');
	                    $tagdata = $this->EE->TMPL->swap_var_single($replace, str_replace(array('{', '}'), array(unique_marker('channel_bracket_open'), unique_marker('channel_bracket_close')), $entry), $tagdata);
					}
					else
					{
	                    $tagdata = $this->EE->TMPL->swap_var_single($replace, $entry, $tagdata);
					}
				}

				//  parse custom member fields
				if (isset($this->mfields[$val]) && array_key_exists('m_field_id_'.$value[0], $row))
				{
					if ( ! isset($processed_member_fields[$row['member_id']]['m_field_id_'.$this->mfields[$val][0]]))
					{
						$processed_member_fields[$row['member_id']]['m_field_id_'.$this->mfields[$val][0]] =

												$this->EE->typography->parse_type(
																				$row['m_field_id_'.$this->mfields[$val][0]],
																				array(
																						'text_format'	=> $this->mfields[$val][1],
																						'html_format'	=> 'safe',
																						'auto_links'	=> 'y',
																						'allow_img_url' => 'n'
																					  )
																			  );
					}

					$tagdata = $this->EE->TMPL->swap_var_single($val,
																$processed_member_fields[$row['member_id']]['m_field_id_'.$this->mfields[$val][0]],
																$tagdata);
				}


			}
			// END SINGLE VARIABLES

			// do we need to replace any curly braces that we protected in custom fields?
			if (strpos($tagdata, unique_marker('channel_bracket_open')) !== FALSE)
			{
				$tagdata = str_replace(array(unique_marker('channel_bracket_open'), unique_marker('channel_bracket_close')), array('{', '}'), $tagdata);
			}

			// -------------------------------------------
			// 'channel_entries_tagdata_end' hook.
			//  - Take the final results of an entry's parsing and do what you wish
			//
				if ($this->EE->extensions->active_hook('channel_entries_tagdata_end') === TRUE)
				{
					$tagdata = $this->EE->extensions->call('channel_entries_tagdata_end', $tagdata, $row, $this);
					if ($this->EE->extensions->end_script === TRUE) return $tagdata;
				}
			//
			// -------------------------------------------

			$this->return_data .= $tagdata;

		}
		// END FOREACH LOOP

		// Kill multi_field variable
		if (strpos($this->return_data, 'multi_field=') !== FALSE)
		{
			$this->return_data = preg_replace("/".LD."multi_field\=[\"'](.+?)[\"']".RD."/s", "", $this->return_data);
		}

		// Do we have backspacing?
		if ($back = $this->EE->TMPL->fetch_param('backspace'))
		{
			if (is_numeric($back))
			{
				$this->return_data = substr($this->return_data, 0, - $back);
			}
		}
	}

	// ------------------------------------------------------------------------
	
	/**
	 * Get File Field Contents
	 * 
	 * Creates a proper array from the file field data
	 *
	 * @access	private
	 * @param	string	field data
	 * @return	array
	 */
	function _parse_file_field($data)
	{
		$file_info['path'] = '';
		
		if (preg_match('/^{filedir_(\d+)}/', $data, $matches))
		{
			// only replace it once
			$path = substr($data, 0, 10 + strlen($matches[1]));

			$file_dirs = $this->EE->functions->fetch_file_paths();

			if (isset($file_dirs[$matches[1]]))
			{
				$file_info['path'] = str_replace($matches[0], 
												 $file_dirs[$matches[1]], $path);
				$data = str_replace($matches[0], '', $data);				
			}
		}

		$parts = explode('.', $data);
		$file_info['extension'] = array_pop($parts);
		$file_info['filename'] = implode('.', $parts);
		return $file_info;
	}

	// ------------------------------------------------------------------------
	
	/**
	  *  Channel Info Tag
	  */
	function info()
	{
		if ( ! $channel_name = $this->EE->TMPL->fetch_param('channel'))
		{
			return '';
		}

		if (count($this->EE->TMPL->var_single) == 0)
		{
			return '';
		}

		$params = array(
							'channel_title',
							'channel_url',
							'channel_description',
							'channel_lang'
							);

		$q = '';
		$tags = FALSE;
		$charset = $this->EE->config->item('charset');
		
		foreach ($this->EE->TMPL->var_single as $val)
		{			
			if (in_array($val, $params))
			{
				$tags = TRUE;
				$q .= $val.',';
			}
			elseif ($val == 'channel_encoding')
			{
				$tags = TRUE;
			}
		}

		$q = substr($q, 0, -1);

		if ($tags == FALSE)
		{
			return '';
		}

		$sql = "SELECT ".$q." FROM exp_channels ";

		$sql .= " WHERE site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";

		if ($channel_name != '')
		{
			$sql .= " AND channel_name = '".$this->EE->db->escape_str($channel_name)."'";
		}

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() != 1)
		{
			return '';
		}

		// We add in the channel_encoding
		$cond_vars = array_merge($query->row_array(), array('channel_encoding' => $charset));
		
		$this->EE->TMPL->tagdata = $this->EE->functions->prep_conditionals($this->EE->TMPL->tagdata, $cond_vars);

		foreach ($query->row_array() as $key => $val)
		{
			$this->EE->TMPL->tagdata = str_replace(LD.$key.RD, $val, $this->EE->TMPL->tagdata);
		}
		
		$this->EE->TMPL->tagdata = str_replace(LD.'channel_encoding'.RD, $charset, $this->EE->TMPL->tagdata);

		return $this->EE->TMPL->tagdata;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Channel Name
	  */
	function channel_name()
	{
		$channel_name = $this->EE->TMPL->fetch_param('channel');

		if (isset($this->channel_name[$channel_name]))
		{
			return $this->channel_name[$channel_name];
		}

		$sql = "SELECT channel_title FROM exp_channels ";

		$sql .= " WHERE site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";

		if ($channel_name != '')
		{
			$sql .= " AND channel_name = '".$this->EE->db->escape_str($channel_name)."'";
		}

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 1)
		{
			$this->channel_name[$channel_name] = $query->row('channel_title') ;

			return $query->row('channel_title') ;
		}
		else
		{
			return '';
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Channel Category Totals
	  *
	  * Need to finish this function.  It lets a simple list of categories
	  * appear along with the post total.
	  */
	function category_totals()
	{
		$sql = "SELECT count( exp_category_posts.entry_id ) AS count,
				exp_categories.cat_id,
				exp_categories.cat_name
				FROM exp_categories
				LEFT JOIN exp_category_posts ON exp_category_posts.cat_id = exp_categories.cat_id
				GROUP BY exp_categories.cat_id
				ORDER BY group_id, parent_id, cat_order";
	}

	// ------------------------------------------------------------------------

	/**
	  *  Channel Categories
	  */
	function categories()
	{
		// -------------------------------------------
		// 'channel_module_categories_start' hook.
		//  - Rewrite the displaying of categories, if you dare!
		//
			if ($this->EE->extensions->active_hook('channel_module_categories_start') === TRUE)
			{
				return $this->EE->extensions->call('channel_module_categories_start');
			}
		//
		// -------------------------------------------
		
		$sql = "SELECT DISTINCT cat_group, channel_id FROM exp_channels WHERE site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";

		if ($channel = $this->EE->TMPL->fetch_param('channel'))
		{
			$sql .= $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('channel'), 'channel_name');
		}

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() != 1)
		{
			return '';
		}

		$group_id = $query->row('cat_group');
		$channel_id = $query->row('channel_id');

		if ($category_group = $this->EE->TMPL->fetch_param('category_group'))
		{
			if (substr($category_group, 0, 4) == 'not ')
			{
				$x = explode('|', substr($category_group, 4));

				$groups = array_diff(explode('|', $group_id), $x);
			}
			else
			{
				$x = explode('|', $category_group);

				$groups = array_intersect(explode('|', $group_id), $x);
			}

			if (count($groups) == 0)
			{
				return '';
			}
			else
			{
				$group_id = implode('|', $groups);
			}
		}

		$parent_only = ($this->EE->TMPL->fetch_param('parent_only') == 'yes') ? TRUE : FALSE;

		$path = array();

		if (preg_match_all("#".LD."path(=.+?)".RD."#", $this->EE->TMPL->tagdata, $matches))
		{
			for ($i = 0; $i < count($matches[0]); $i++)
			{
				if ( ! isset($path[$matches[0][$i]]))
				{
					$path[$matches[0][$i]] = $this->EE->functions->create_url($this->EE->functions->extract_path($matches[1][$i]));
				}
			}
		}

		$str = '';
		$strict_empty = ($this->EE->TMPL->fetch_param('restrict_channel') == 'no') ? 'no' : 'yes';

		if ($this->EE->TMPL->fetch_param('style') == '' OR $this->EE->TMPL->fetch_param('style') == 'nested')
		{
			$this->category_tree(
									array(
											'group_id'		=> $group_id,
											'channel_id'		=> $channel_id,											
											'template'		=> $this->EE->TMPL->tagdata,
											'path'			=> $path,
											'channel_array' 	=> '',
											'parent_only'	=> $parent_only,
											'show_empty'	=> $this->EE->TMPL->fetch_param('show_empty'),
											'strict_empty'	=> $strict_empty
										  )
								);


			if (count($this->category_list) > 0)
			{
				$i = 0;

				$id_name = ( ! $this->EE->TMPL->fetch_param('id')) ? 'nav_categories' : $this->EE->TMPL->fetch_param('id');
				$class_name = ( ! $this->EE->TMPL->fetch_param('class')) ? 'nav_categories' : $this->EE->TMPL->fetch_param('class');

				$this->category_list[0] = '<ul id="'.$id_name.'" class="'.$class_name.'">'."\n";

				foreach ($this->category_list as $val)
				{
					$str .= $val;
				}
			}
		}
		else
		{
			// fetch category field names and id's

			if ($this->enable['category_fields'] === TRUE)
			{
				$query = $this->EE->db->query("SELECT field_id, field_name FROM exp_category_fields
									WHERE site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."')
									AND group_id IN ('".str_replace('|', "','", $this->EE->db->escape_str($group_id))."')");

				if ($query->num_rows() > 0)
				{
					foreach ($query->result_array() as $row)
					{
						$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
					}
				}

				$field_sqla = ", cg.field_html_formatting, fd.* ";
				$field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id
								LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id";
			}
			else
			{
				$field_sqla = '';
				$field_sqlb = '';
			}

			$show_empty = $this->EE->TMPL->fetch_param('show_empty');

			if ($show_empty == 'no')
			{
				// First we'll grab all category ID numbers

				$query = $this->EE->db->query("SELECT cat_id, parent_id
									 FROM exp_categories
									 WHERE group_id IN ('".str_replace('|', "','", $this->EE->db->escape_str($group_id))."')
									 ORDER BY group_id, parent_id, cat_order");

				$all = array();

				// No categories exist?  Let's go home..
				if ($query->num_rows() == 0)
				{
					return FALSE;
				}

				foreach($query->result_array() as $row)
				{
					$all[$row['cat_id']] = $row['parent_id'];
				}

				// Next we'l grab only the assigned categories

				$sql = "SELECT DISTINCT(exp_categories.cat_id), parent_id FROM exp_categories
						LEFT JOIN exp_category_posts ON exp_categories.cat_id = exp_category_posts.cat_id
						LEFT JOIN exp_channel_titles ON exp_category_posts.entry_id = exp_channel_titles.entry_id
						WHERE group_id IN ('".str_replace('|', "','", $this->EE->db->escape_str($group_id))."') ";
		        

				$sql .= "AND exp_category_posts.cat_id IS NOT NULL ";

				if ($strict_empty == 'yes')
				{
					$sql .= "AND exp_channel_titles.channel_id = '".$channel_id."' ";
				}
				else
				{
					$sql .= "AND exp_channel_titles.site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";
				}

		        if (($status = $this->EE->TMPL->fetch_param('status')) !== FALSE)
		        {
					$status = str_replace(array('Open', 'Closed'), array('open', 'closed'), $status);
		            $sql .= $this->EE->functions->sql_andor_string($status, 'exp_channel_titles.status');
		        }
		        else
		        {
		            $sql .= "AND exp_channel_titles.status != 'closed' ";
		        }

				/**------
				/**  We only select entries that have not expired
				/**------*/

				$timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->TMPL->cache_timestamp : $this->EE->localize->now;

				if ($this->EE->TMPL->fetch_param('show_future_entries') != 'yes')
				{
					$sql .= " AND exp_channel_titles.entry_date < ".$timestamp." ";
				}

				if ($this->EE->TMPL->fetch_param('show_expired') != 'yes')
				{
					$sql .= " AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > ".$timestamp.") ";
				}

				if ($parent_only === TRUE)
				{
					$sql .= " AND parent_id = 0";
				}

				$sql .= " ORDER BY group_id, parent_id, cat_order";

				$query = $this->EE->db->query($sql);

				if ($query->num_rows() == 0)
				{
					return FALSE;
				}

				// All the magic happens here, baby!!

				foreach($query->result_array() as $row)
				{
					if ($row['parent_id'] != 0)
					{
						$this->find_parent($row['parent_id'], $all);
					}

					$this->cat_full_array[] = $row['cat_id'];
				}

				$this->cat_full_array = array_unique($this->cat_full_array);

				$sql = "SELECT c.cat_id, c.parent_id, c.cat_name, c.cat_url_title, c.cat_image, c.cat_description {$field_sqla}
				FROM exp_categories AS c
				{$field_sqlb}
				WHERE c.cat_id IN (";

				foreach ($this->cat_full_array as $val)
				{
					$sql .= $val.',';
				}

				$sql = substr($sql, 0, -1).')';

				$sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";

				$query = $this->EE->db->query($sql);

				if ($query->num_rows() == 0)
				{
					return FALSE;
				}
			}
			else
			{
				$sql = "SELECT c.cat_name, c.cat_url_title, c.cat_image, c.cat_description, c.cat_id, c.parent_id {$field_sqla}
						FROM exp_categories AS c
						{$field_sqlb}
						WHERE c.group_id IN ('".str_replace('|', "','", $this->EE->db->escape_str($group_id))."') ";

				if ($parent_only === TRUE)
				{
					$sql .= " AND c.parent_id = 0";
				}

				$sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";

				$query = $this->EE->db->query($sql);

				if ($query->num_rows() == 0)
				{
					return '';
				}
			}

			// Here we check the show parameter to see if we have any
			// categories we should be ignoring or only a certain group of
			// categories that we should be showing.  By doing this here before
			// all of the nested processing we should keep out all but the
			// request categories while also not having a problem with having a
			// child but not a parent.  As we all know, categories are not asexual.

			if ($this->EE->TMPL->fetch_param('show') !== FALSE)
			{
				if (strncmp($this->EE->TMPL->fetch_param('show'), 'not ', 4) == 0)
				{
					$not_these = explode('|', trim(substr($this->EE->TMPL->fetch_param('show'), 3)));
				}
				else
				{
					$these = explode('|', trim($this->EE->TMPL->fetch_param('show')));
				}
			}

			foreach($query->result_array() as $row)
			{
				if (isset($not_these) && in_array($row['cat_id'], $not_these))
				{
					continue;
				}
				elseif(isset($these) && ! in_array($row['cat_id'], $these))
				{
					continue;
				}

				$this->temp_array[$row['cat_id']]  = array($row['cat_id'], $row['parent_id'], '1', $row['cat_name'], $row['cat_description'], $row['cat_image'], $row['cat_url_title']);

				foreach ($row as $key => $val)
				{
					if (strpos($key, 'field') !== FALSE)
					{
						$this->temp_array[$row['cat_id']][$key] = $val;
					}
				}
			}

			foreach($this->temp_array as $key => $val)
			{
				if (0 == $val[1])
				{
					$this->cat_array[] = $val;
					$this->process_subcategories($key);
				}
			}

			unset($this->temp_array);

			$this->EE->load->library('typography');
			$this->EE->typography->initialize(array(
						'convert_curly'	=> FALSE)
						);

			$this->category_count = 0;
			$total_results = count($this->cat_array);

			foreach ($this->cat_array as $key => $val)
			{
				$chunk = $this->EE->TMPL->tagdata;

				$cat_vars = array('category_name'			=> $val[3],
								  'category_url_title'		=> $val[6],
								  'category_description'	=> $val[4],
								  'category_image'			=> $val[5],
								  'category_id'				=> $val[0],
								  'parent_id'				=> $val[1]
								);

				// add custom fields for conditionals prep

				foreach ($this->catfields as $v)
				{
					$cat_vars[$v['field_name']] = ( ! isset($val['field_id_'.$v['field_id']])) ? '' : $val['field_id_'.$v['field_id']];
				}

				$cat_vars['count'] = ++$this->category_count;
				$cat_vars['total_results'] = $total_results;

				$chunk = $this->EE->functions->prep_conditionals($chunk, $cat_vars);

				$chunk = str_replace(array(LD.'category_name'.RD,
											LD.'category_url_title'.RD,
											LD.'category_description'.RD,
											LD.'category_image'.RD,
											LD.'category_id'.RD,
											LD.'parent_id'.RD),
									 array($val[3],
											$val[6],
									 		$val[4],
									 		$val[5],
									 		$val[0],
											$val[1]),
									$chunk);

				foreach($path as $k => $v)
				{
					if ($this->use_category_names == TRUE)
					{
						$chunk = str_replace($k, $this->EE->functions->remove_double_slashes($v.'/'.$this->reserved_cat_segment.'/'.$val[6]), $chunk);
					}
					else
					{
						$chunk = str_replace($k, $this->EE->functions->remove_double_slashes($v.'/C'.$val[0]), $chunk);
					}
				}

				// parse custom fields
				foreach($this->catfields as $cv)
				{
					if (isset($val['field_id_'.$cv['field_id']]) AND $val['field_id_'.$cv['field_id']] != '')
					{
						$field_content = $this->EE->typography->parse_type($val['field_id_'.$cv['field_id']],
																	array(
																		  'text_format'		=> $val['field_ft_'.$cv['field_id']],
																		  'html_format'		=> $val['field_html_formatting'],
																		  'auto_links'		=> 'n',
																		  'allow_img_url'	=> 'y'
																		)
																);
						$chunk = str_replace(LD.$cv['field_name'].RD, $field_content, $chunk);
					}
					else
					{
						// garbage collection
						$chunk = str_replace(LD.$cv['field_name'].RD, '', $chunk);
					}
				}

				/** --------------------------------
				/**  {count}
				/** --------------------------------*/

				if (strpos($chunk, LD.'count'.RD) !== FALSE)
				{
					$chunk = str_replace(LD.'count'.RD, $this->category_count, $chunk);
				}

				/** --------------------------------
				/**  {total_results}
				/** --------------------------------*/

				if (strpos($chunk, LD.'total_results'.RD) !== FALSE)
				{
					$chunk = str_replace(LD.'total_results'.RD, $total_results, $chunk);
				}

				$str .= $chunk;
			}

			if ($this->EE->TMPL->fetch_param('backspace'))
			{
				$str = substr($str, 0, - $this->EE->TMPL->fetch_param('backspace'));
			}
		}

		return $str;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Process Subcategories
	  */
	function process_subcategories($parent_id)
	{
		foreach($this->temp_array as $key => $val)
		{
			if ($parent_id == $val[1])
			{
				$this->cat_array[] = $val;
				$this->process_subcategories($key);
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Category archives
	  */
	function category_archive()
	{
		$sql = "SELECT DISTINCT cat_group, channel_id FROM exp_channels WHERE site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";

		if ($channel = $this->EE->TMPL->fetch_param('channel'))
		{
			$sql .= $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('channel'), 'channel_name');
		}

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() != 1)
		{
			return '';
		}

		$group_id = $query->row('cat_group') ;
		$channel_id = $query->row('channel_id') ;


		$sql = "SELECT exp_category_posts.cat_id, exp_channel_titles.entry_id, exp_channel_titles.title, exp_channel_titles.url_title, exp_channel_titles.entry_date
				FROM exp_channel_titles, exp_category_posts
				WHERE channel_id = '$channel_id'
				AND exp_channel_titles.entry_id = exp_category_posts.entry_id ";

		$timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->TMPL->cache_timestamp : $this->EE->localize->now;

		if ($this->EE->TMPL->fetch_param('show_future_entries') != 'yes')
		{
			$sql .= "AND exp_channel_titles.entry_date < ".$timestamp." ";
		}

		if ($this->EE->TMPL->fetch_param('show_expired') != 'yes')
		{
			$sql .= "AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > ".$timestamp.") ";
		}

		$sql .= "AND exp_channel_titles.status != 'closed' ";

		if ($status = $this->EE->TMPL->fetch_param('status'))
		{
			$status = str_replace('Open',	'open',	$status);
			$status = str_replace('Closed', 'closed', $status);

			$sql .= $this->EE->functions->sql_andor_string($status, 'exp_channel_titles.status');
		}
		else
		{
			$sql .= "AND exp_channel_titles.status = 'open' ";
		}

		if ($this->EE->TMPL->fetch_param('show') !== FALSE)
		{
			$sql .= $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('show'), 'exp_category_posts.cat_id').' ';
		}


		$orderby  = $this->EE->TMPL->fetch_param('orderby');

		switch ($orderby)
		{
			case 'date'					: $sql .= "ORDER BY exp_channel_titles.entry_date";
				break;
			case 'expiration_date'		: $sql .= "ORDER BY exp_channel_titles.expiration_date";
				break;
			case 'title'				: $sql .= "ORDER BY exp_channel_titles.title";
				break;
			case 'comment_total'		: $sql .= "ORDER BY exp_channel_titles.entry_date";
				break;
			case 'most_recent_comment'	: $sql .= "ORDER BY exp_channel_titles.recent_comment_date desc, exp_channel_titles.entry_date";
				break;
			default						: $sql .= "ORDER BY exp_channel_titles.title";
				break;
		}

		$sort = $this->EE->TMPL->fetch_param('sort');

		switch ($sort)
		{
			case 'asc'	: $sql .= " asc";
				break;
			case 'desc'	: $sql .= " desc";
				break;
			default		: $sql .= " asc";
				break;
		}

		$result = $this->EE->db->query($sql);
		$channel_array = array();

		$parent_only = ($this->EE->TMPL->fetch_param('parent_only') == 'yes') ? TRUE : FALSE;

		$cat_chunk  = (preg_match("/".LD."categories\s*".RD."(.*?)".LD.'\/'."categories\s*".RD."/s", $this->EE->TMPL->tagdata, $match)) ? $match[1] : '';

		$c_path = array();

		if (preg_match_all("#".LD."path(=.+?)".RD."#", $cat_chunk, $matches))
		{
			for ($i = 0; $i < count($matches[0]); $i++)
			{
				$c_path[$matches[0][$i]] = $this->EE->functions->create_url($this->EE->functions->extract_path($matches[1][$i]));
			}
		}

		$tit_chunk = (preg_match("/".LD."entry_titles\s*".RD."(.*?)".LD.'\/'."entry_titles\s*".RD."/s", $this->EE->TMPL->tagdata, $match)) ? $match[1] : '';

		$t_path = array();

		if (preg_match_all("#".LD."path(=.+?)".RD."#", $tit_chunk, $matches))
		{
			for ($i = 0; $i < count($matches[0]); $i++)
			{
				$t_path[$matches[0][$i]] = $this->EE->functions->create_url($this->EE->functions->extract_path($matches[1][$i]));
			}
		}

		$id_path = array();

		if (preg_match_all("#".LD."entry_id_path(=.+?)".RD."#", $tit_chunk, $matches))
		{
			for ($i = 0; $i < count($matches[0]); $i++)
			{
				$id_path[$matches[0][$i]] = $this->EE->functions->create_url($this->EE->functions->extract_path($matches[1][$i]));
			}
		}

		$entry_date = array();

		preg_match_all("/".LD."entry_date\s+format\s*=\s*(\042|\047)([^\\1]*?)\\1".RD."/s", $tit_chunk, $matches);
		{
			$j = count($matches[0]);
			for ($i = 0; $i < $j; $i++)
			{
				$matches[0][$i] = str_replace(array(LD,RD), '', $matches[0][$i]);

				$entry_date[$matches[0][$i]] = $this->EE->localize->fetch_date_params($matches[2][$i]);
			}
		}

		$str = '';

		if ($this->EE->TMPL->fetch_param('style') == '' OR $this->EE->TMPL->fetch_param('style') == 'nested')
		{
			if ($result->num_rows() > 0 && $tit_chunk != '')
			{
					$i = 0;
				foreach($result->result_array() as $row)
				{
					$chunk = "<li>".str_replace(LD.'category_name'.RD, '', $tit_chunk)."</li>";

					foreach($t_path as $tkey => $tval)
					{
						$chunk = str_replace($tkey, $this->EE->functions->remove_double_slashes($tval.'/'.$row['url_title']), $chunk);
					}

					foreach($id_path as $tkey => $tval)
					{
						$chunk = str_replace($tkey, $this->EE->functions->remove_double_slashes($tval.'/'.$row['entry_id']), $chunk);
					}

					foreach($this->EE->TMPL->var_single as $key => $val)
					{
						if (isset($entry_date[$key]))
						{
							$val = str_replace($entry_date[$key], $this->EE->localize->convert_timestamp($entry_date[$key], $row['entry_date'], TRUE), $val);
							$chunk = $this->EE->TMPL->swap_var_single($key, $val, $chunk);
						}

					}

					$channel_array[$i.'_'.$row['cat_id']] = str_replace(LD.'title'.RD, $row['title'], $chunk);
					$i++;
				}
			}

			$this->category_tree(
									array(
											'group_id'		=> $group_id,
											'channel_id'		=> $channel_id,
											'path'			=> $c_path,
											'template'		=> $cat_chunk,
											'channel_array' 	=> $channel_array,
											'parent_only'	=> $parent_only,
											'show_empty'	=> $this->EE->TMPL->fetch_param('show_empty'),
											'strict_empty'	=> 'yes'										
										  )
								);

			if (count($this->category_list) > 0)
			{
				$id_name = ($this->EE->TMPL->fetch_param('id') === FALSE) ? 'nav_cat_archive' : $this->EE->TMPL->fetch_param('id');
				$class_name = ($this->EE->TMPL->fetch_param('class') === FALSE) ? 'nav_cat_archive' : $this->EE->TMPL->fetch_param('class');

				$this->category_list[0] = '<ul id="'.$id_name.'" class="'.$class_name.'">'."\n";

				foreach ($this->category_list as $val)
				{
					$str .= $val;
				}
			}
		}
		else
		{
			// fetch category field names and id's

			if ($this->enable['category_fields'] === TRUE)
			{
				$query = $this->EE->db->query("SELECT field_id, field_name FROM exp_category_fields
									WHERE site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."')
									AND group_id IN ('".str_replace('|', "','", $this->EE->db->escape_str($group_id))."')");

				if ($query->num_rows() > 0)
				{
					foreach ($query->result_array() as $row)
					{
						$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
					}
				}

				$field_sqla = ", cg.field_html_formatting, fd.* ";
				$field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id
								LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id ";
			}
			else
			{
				$field_sqla = '';
				$field_sqlb = '';
			}

			$sql = "SELECT DISTINCT (c.cat_id), c.cat_name, c.cat_url_title, c.cat_description, c.cat_image, c.parent_id {$field_sqla}
					FROM (exp_categories AS c";

			if ($this->EE->TMPL->fetch_param('show_empty') != 'no' AND $channel_id != '')
			{
				$sql .= ", exp_category_posts ";
			}

			$sql .= ") {$field_sqlb}";

			if ($this->EE->TMPL->fetch_param('show_empty') == 'no')
			{
				$sql .= " LEFT JOIN exp_category_posts ON c.cat_id = exp_category_posts.cat_id ";

				if ($channel_id != '')
				{
					$sql .= " LEFT JOIN exp_channel_titles ON exp_category_posts.entry_id = exp_channel_titles.entry_id ";
				}
			}

			$sql .= " WHERE c.group_id IN ('".str_replace('|', "','", $this->EE->db->escape_str($group_id))."') ";

			if ($this->EE->TMPL->fetch_param('show_empty') == 'no')
			{
				if ($channel_id != '')
				{
					$sql .= "AND exp_channel_titles.channel_id = '".$channel_id."' ";
				}
				else
				{
					$sql .= " AND exp_channel_titles.site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";
				}

				if ($status = $this->EE->TMPL->fetch_param('status'))
				{
					$status = str_replace('Open',	'open',	$status);
					$status = str_replace('Closed', 'closed', $status);

					$sql .= $this->EE->functions->sql_andor_string($status, 'exp_channel_titles.status');
				}
				else
				{
					$sql .= "AND exp_channel_titles.status = 'open' ";
				}

				if ($this->EE->TMPL->fetch_param('show_empty') == 'no')
				{
					$sql .= "AND exp_category_posts.cat_id IS NOT NULL ";
				}
			}

			if ($this->EE->TMPL->fetch_param('show') !== FALSE)
			{
				$sql .= $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('show'), 'c.cat_id').' ';
			}

			if ($parent_only == TRUE)
			{
				$sql .= " AND c.parent_id = 0";
			}

			$sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";
		 	$query = $this->EE->db->query($sql);

			if ($query->num_rows() > 0)
			{
				$this->EE->load->library('typography');
				$this->EE->typography->initialize(array(
								'convert_curly'	=> FALSE)
								);

				$used = array();

				foreach($query->result_array() as $row)
				{
					if ( ! isset($used[$row['cat_name']]))
					{
						$chunk = $cat_chunk;

						$cat_vars = array('category_name'			=> $row['cat_name'],
										  'category_url_title'		=> $row['cat_url_title'],
										  'category_description'	=> $row['cat_description'],
										  'category_image'			=> $row['cat_image'],
										  'category_id'				=> $row['cat_id'],
										  'parent_id'				=> $row['parent_id']
										);

						foreach ($this->catfields as $v)
						{
							$cat_vars[$v['field_name']] = ( ! isset($row['field_id_'.$v['field_id']])) ? '' : $row['field_id_'.$v['field_id']];
						}

						$chunk = $this->EE->functions->prep_conditionals($chunk, $cat_vars);

						$chunk = str_replace( array(LD.'category_id'.RD,
													LD.'category_name'.RD,
													LD.'category_url_title'.RD,
													LD.'category_image'.RD,
													LD.'category_description'.RD,
													LD.'parent_id'.RD),
											  array($row['cat_id'],
											  		$row['cat_name'],
													$row['cat_url_title'],
											  		$row['cat_image'],
											  		$row['cat_description'],
													$row['parent_id']),
											  $chunk);

						foreach($c_path as $ckey => $cval)
						{
							$cat_seg = ($this->use_category_names == TRUE) ? $this->reserved_cat_segment.'/'.$row['cat_url_title'] : 'C'.$row['cat_id'];
							$chunk = str_replace($ckey, $this->EE->functions->remove_double_slashes($cval.'/'.$cat_seg), $chunk);
						}

						// parse custom fields

						foreach($this->catfields as $cfv)
						{
							if (isset($row['field_id_'.$cfv['field_id']]) AND $row['field_id_'.$cfv['field_id']] != '')
							{
								$field_content = $this->EE->typography->parse_type($row['field_id_'.$cfv['field_id']],
																			array(
																				  'text_format'		=> $row['field_ft_'.$cfv['field_id']],
																				  'html_format'		=> $row['field_html_formatting'],
																				  'auto_links'		=> 'n',
																				  'allow_img_url'	=> 'y'
																				)
																		);
								$chunk = str_replace(LD.$cfv['field_name'].RD, $field_content, $chunk);
							}
							else
							{
								// garbage collection
								$chunk = str_replace(LD.$cfv['field_name'].RD, '', $chunk);
							}
						}

						$str .= $chunk;
						$used[$row['cat_name']] = TRUE;
					}

					foreach($result->result_array() as $trow)
					{
						if ($trow['cat_id'] == $row['cat_id'])
						{
							$chunk = str_replace(array(LD.'title'.RD, LD.'category_name'.RD),
												 array($trow['title'],$row['cat_name']),
												 $tit_chunk);

							foreach($t_path as $tkey => $tval)
							{
								$chunk = str_replace($tkey, $this->EE->functions->remove_double_slashes($tval.'/'.$trow['url_title']), $chunk);
							}

							foreach($id_path as $tkey => $tval)
							{
								$chunk = str_replace($tkey, $this->EE->functions->remove_double_slashes($tval.'/'.$trow['entry_id']), $chunk);
							}

							foreach($this->EE->TMPL->var_single as $key => $val)
							{
								if (isset($entry_date[$key]))
								{
									$val = str_replace($entry_date[$key], $this->EE->localize->convert_timestamp($entry_date[$key], $trow['entry_date'], TRUE), $val);

									$chunk = $this->EE->TMPL->swap_var_single($key, $val, $chunk);
								}

							}

							$str .= $chunk;
						}
					}

					if ($this->EE->TMPL->fetch_param('backspace'))
					{
						$str = substr($str, 0, - $this->EE->TMPL->fetch_param('backspace'));
					}
				}
			}
		}

		return $str;
	}

	// ------------------------------------------------------------------------

	/** --------------------------------
	/**  Locate category parent
	/** --------------------------------*/
	// This little recursive gem will travel up the
	// category tree until it finds the category ID
	// number of any parents.  It's used by the function
	// below
	function find_parent($parent, $all)
	{
		foreach ($all as $cat_id => $parent_id)
		{
			if ($parent == $cat_id)
			{
				$this->cat_full_array[] = $cat_id;

				if ($parent_id != 0)
					$this->find_parent($parent_id, $all);
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Category Tree
	  *
	  * This function and the next create a nested, hierarchical category tree
	  */
	function category_tree($cdata = array())
	{
		$default = array('group_id', 'channel_id', 'path', 'template', 'depth', 'channel_array', 'parent_only', 'show_empty', 'strict_empty');

		foreach ($default as $val)
		{
			$$val = ( ! isset($cdata[$val])) ? '' : $cdata[$val];
		}

		if ($group_id == '')
		{
			return FALSE;
		}

		if ($this->enable['category_fields'] === TRUE)
		{
			$query = $this->EE->db->query("SELECT field_id, field_name
								FROM exp_category_fields
								WHERE site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."')
								AND group_id IN ('".str_replace('|', "','", $this->EE->db->escape_str($group_id))."')");

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
				}
			}

			$field_sqla = ", cg.field_html_formatting, fd.* ";
			$field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id
							LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id";
		}
		else
		{
			$field_sqla = '';
			$field_sqlb = '';
		}

		/** -----------------------------------
		/**  Are we showing empty categories
		/** -----------------------------------*/

		// If we are only showing categories that have been assigned to entries
		// we need to run a couple queries and run a recursive function that
		// figures out whether any given category has a parent.
		// If we don't do this we will run into a problem in which parent categories
		// that are not assigned to a channel will be supressed, and therefore, any of its
		// children will be supressed also - even if they are assigned to entries.
		// So... we will first fetch all the category IDs, then only the ones that are assigned
		// to entries, and lastly we'll recursively run up the tree and fetch all parents.
		// Follow that?  No?  Me neither...

		if ($show_empty == 'no')
		{
			// First we'll grab all category ID numbers

			$query = $this->EE->db->query("SELECT cat_id, parent_id FROM exp_categories
								 WHERE group_id IN ('".str_replace('|', "','", $this->EE->db->escape_str($group_id))."')
								 ORDER BY group_id, parent_id, cat_order");

			$all = array();

			// No categories exist?  Back to the barn for the night..
			if ($query->num_rows() == 0)
			{
				return FALSE;
			}

			foreach($query->result_array() as $row)
			{
				$all[$row['cat_id']] = $row['parent_id'];
			}

			// Next we'l grab only the assigned categories

			$sql = "SELECT DISTINCT(exp_categories.cat_id), parent_id
					FROM exp_categories
					LEFT JOIN exp_category_posts ON exp_categories.cat_id = exp_category_posts.cat_id
					LEFT JOIN exp_channel_titles ON exp_category_posts.entry_id = exp_channel_titles.entry_id ";

			$sql .= "WHERE group_id IN ('".str_replace('|', "','", $this->EE->db->escape_str($group_id))."') ";

			$sql .= "AND exp_category_posts.cat_id IS NOT NULL ";

			if ($channel_id != '' && $strict_empty == 'yes')
			{
				$sql .= "AND exp_channel_titles.channel_id = '".$channel_id."' ";
			}
			else
			{
				$sql .= "AND exp_channel_titles.site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";
			}

			if (($status = $this->EE->TMPL->fetch_param('status')) !== FALSE)
	        {
				$status = str_replace(array('Open', 'Closed'), array('open', 'closed'), $status);
	            $sql .= $this->EE->functions->sql_andor_string($status, 'exp_channel_titles.status');
	        }
	        else
	        {
	            $sql .= "AND exp_channel_titles.status != 'closed' ";
	        }

			/**------
			/**  We only select entries that have not expired
			/**------*/

			$timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->TMPL->cache_timestamp : $this->EE->localize->now;

			if ($this->EE->TMPL->fetch_param('show_future_entries') != 'yes')
			{
				$sql .= " AND exp_channel_titles.entry_date < ".$timestamp." ";
			}

			if ($this->EE->TMPL->fetch_param('show_expired') != 'yes')
			{
				$sql .= " AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > ".$timestamp.") ";
			}

			if ($parent_only === TRUE)
			{
				$sql .= " AND parent_id = 0";
			}

			$sql .= " ORDER BY group_id, parent_id, cat_order";

			$query = $this->EE->db->query($sql);

			if ($query->num_rows() == 0)
			{
				return FALSE;
			}

			// All the magic happens here, baby!!

			foreach($query->result_array() as $row)
			{
				if ($row['parent_id'] != 0)
				{
					$this->find_parent($row['parent_id'], $all);
				}

				$this->cat_full_array[] = $row['cat_id'];
			}

			$this->cat_full_array = array_unique($this->cat_full_array);

			$sql = "SELECT c.cat_id, c.parent_id, c.cat_name, c.cat_url_title, c.cat_image, c.cat_description {$field_sqla}
			FROM exp_categories AS c
			{$field_sqlb}
			WHERE c.cat_id IN (";

			foreach ($this->cat_full_array as $val)
			{
				$sql .= $val.',';
			}

			$sql = substr($sql, 0, -1).')';

			$sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";

			$query = $this->EE->db->query($sql);

			if ($query->num_rows() == 0)
			{
				return FALSE;
			}
		}
		else
		{
			$sql = "SELECT DISTINCT(c.cat_id), c.parent_id, c.cat_name, c.cat_url_title, c.cat_image, c.cat_description {$field_sqla}
					FROM exp_categories AS c
					{$field_sqlb}
					WHERE c.group_id IN ('".str_replace('|', "','", $this->EE->db->escape_str($group_id))."') ";

			if ($parent_only === TRUE)
			{
				$sql .= " AND c.parent_id = 0";
			}

			$sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";

			$query = $this->EE->db->query($sql);

			if ($query->num_rows() == 0)
			{
				return FALSE;
			}
		}

		// Here we check the show parameter to see if we have any
		// categories we should be ignoring or only a certain group of
		// categories that we should be showing.  By doing this here before
		// all of the nested processing we should keep out all but the
		// request categories while also not having a problem with having a
		// child but not a parent.  As we all know, categories are not asexual

		if ($this->EE->TMPL->fetch_param('show') !== FALSE)
		{
			if (strncmp($this->EE->TMPL->fetch_param('show'), 'not ', 4) == 0)
			{
				$not_these = explode('|', trim(substr($this->EE->TMPL->fetch_param('show'), 3)));
			}
			else
			{
				$these = explode('|', trim($this->EE->TMPL->fetch_param('show')));
			}
		}

		foreach($query->result_array() as $row)
		{
			if (isset($not_these) && in_array($row['cat_id'], $not_these))
			{
				continue;
			}
			elseif(isset($these) && ! in_array($row['cat_id'], $these))
			{
				continue;
			}

			$this->cat_array[$row['cat_id']]  = array($row['parent_id'], $row['cat_name'], $row['cat_image'], $row['cat_description'], $row['cat_url_title']);

			foreach ($row as $key => $val)
			{
				if (strpos($key, 'field') !== FALSE)
				{
					$this->cat_array[$row['cat_id']][$key] = $val;
				}
			}
		}

		$this->temp_array = $this->cat_array;

		$open = 0;

		$this->EE->load->library('typography');
		$this->EE->typography->initialize(array(
				'convert_curly'	=> FALSE)
				);

		$this->category_count = 0;
		$total_results = count($this->cat_array);

		foreach($this->cat_array as $key => $val)
		{
			if (0 == $val[0])
			{
				if ($open == 0)
				{
					$open = 1;

					$this->category_list[] = "<ul>\n";
				}

				$chunk = $template;

				$cat_vars = array('category_name'			=> $val[1],
								  'category_url_title'		=> $val[4],
								  'category_description'	=> $val[3],
								  'category_image'			=> $val[2],
								  'category_id'				=> $key,
								  'parent_id'				=> $val[0]
								);

				// add custom fields for conditionals prep

				foreach ($this->catfields as $v)
				{
					$cat_vars[$v['field_name']] = ( ! isset($val['field_id_'.$v['field_id']])) ? '' : $val['field_id_'.$v['field_id']];
				}

				$cat_vars['count'] = ++$this->category_count;
				$cat_vars['total_results'] = $total_results;

				$chunk = $this->EE->functions->prep_conditionals($chunk, $cat_vars);

				$chunk = str_replace( array(LD.'category_id'.RD,
											LD.'category_name'.RD,
											LD.'category_url_title'.RD,
											LD.'category_image'.RD,
											LD.'category_description'.RD,
											LD.'parent_id'.RD),
									  array($key,
									  		$val[1],
											$val[4],
									  		$val[2],
									  		$val[3],
											$val[0]),
									  $chunk);

				foreach($path as $pkey => $pval)
				{
					if ($this->use_category_names == TRUE)
					{
						$chunk = str_replace($pkey, $this->EE->functions->remove_double_slashes($pval.'/'.$this->reserved_cat_segment.'/'.$val[4]), $chunk);
					}
					else
					{
						$chunk = str_replace($pkey, $this->EE->functions->remove_double_slashes($pval.'/C'.$key), $chunk);
					}
				}

				// parse custom fields
				foreach($this->catfields as $cval)
				{
					if (isset($val['field_id_'.$cval['field_id']]) AND $val['field_id_'.$cval['field_id']] != '')
					{
						$field_content = $this->EE->typography->parse_type($val['field_id_'.$cval['field_id']],
																	array(
																		  'text_format'		=> $val['field_ft_'.$cval['field_id']],
																		  'html_format'		=> $val['field_html_formatting'],
																		  'auto_links'		=> 'n',
																		  'allow_img_url'	=> 'y'
																		)
																);
						$chunk = str_replace(LD.$cval['field_name'].RD, $field_content, $chunk);
					}
					else
					{
						// garbage collection
						$chunk = str_replace(LD.$cval['field_name'].RD, '', $chunk);
					}
				}

				/** --------------------------------
				/**  {count}
				/** --------------------------------*/

				if (strpos($chunk, LD.'count'.RD) !== FALSE)
				{
					$chunk = str_replace(LD.'count'.RD, $this->category_count, $chunk);
				}

				/** --------------------------------
				/**  {total_results}
				/** --------------------------------*/

				if (strpos($chunk, LD.'total_results'.RD) !== FALSE)
				{
					$chunk = str_replace(LD.'total_results'.RD, $total_results, $chunk);
				}

				$this->category_list[] = "\t<li>".$chunk;

				if (is_array($channel_array))
				{
					$fillable_entries = 'n';

					foreach($channel_array as $k => $v)
					{
						$k = substr($k, strpos($k, '_') + 1);

						if ($key == $k)
						{
							if ($fillable_entries == 'n')
							{
								$this->category_list[] = "\n\t\t<ul>\n";
								$fillable_entries = 'y';
							}

							$this->category_list[] = "\t\t\t$v\n";
						}
					}
				}

				if (isset($fillable_entries) && $fillable_entries == 'y')
				{
					$this->category_list[] = "\t\t</ul>\n";
				}

				$this->category_subtree(
											array(
													'parent_id'		=> $key,
													'path'			=> $path,
													'template'		=> $template,
													'channel_array' 	=> $channel_array
												  )
									);
				$t = '';

				if (isset($fillable_entries) && $fillable_entries == 'y')
				{
					$t .= "\t";
				}

				$this->category_list[] = $t."</li>\n";

				unset($this->temp_array[$key]);

				$this->close_ul(0);
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Category Sub-tree
	  */
	function category_subtree($cdata = array())
	{
		$default = array('parent_id', 'path', 'template', 'depth', 'channel_array', 'show_empty');

		foreach ($default as $val)
		{
				$$val = ( ! isset($cdata[$val])) ? '' : $cdata[$val];
		}

		$open = 0;

		if ($depth == '')
				$depth = 1;

		$tab = '';
		for ($i = 0; $i <= $depth; $i++)
			$tab .= "\t";

		$total_results = count($this->cat_array);

		foreach($this->cat_array as $key => $val)
		{
			if ($parent_id == $val[0])
			{
				if ($open == 0)
				{
					$open = 1;
					$this->category_list[] = "\n".$tab."<ul>\n";
				}

				$chunk = $template;

				$cat_vars = array('category_name'			=> $val[1],
								  'category_url_title'		=> $val[4],
								  'category_description'	=> $val[3],
								  'category_image'			=> $val[2],
								  'category_id'				=> $key,
								  'parent_id'				=> $val[0]);

				// add custom fields for conditionals prep
				foreach ($this->catfields as $v)
				{
					$cat_vars[$v['field_name']] = ( ! isset($val['field_id_'.$v['field_id']])) ? '' : $val['field_id_'.$v['field_id']];
				}

				$cat_vars['count'] = ++$this->category_count;
				$cat_vars['total_results'] = $total_results;

				$chunk = $this->EE->functions->prep_conditionals($chunk, $cat_vars);

				$chunk = str_replace( array(LD.'category_id'.RD,
											LD.'category_name'.RD,
											LD.'category_url_title'.RD,
											LD.'category_image'.RD,
											LD.'category_description'.RD,
											LD.'parent_id'.RD),
									  array($key,
									  		$val[1],
											$val[4],
									  		$val[2],
									  		$val[3],
											$val[0]),
									  $chunk);

				foreach($path as $pkey => $pval)
				{
					if ($this->use_category_names == TRUE)
					{
						$chunk = str_replace($pkey, $this->EE->functions->remove_double_slashes($pval.'/'.$this->reserved_cat_segment.'/'.$val[4]), $chunk);
					}
					else
					{
						$chunk = str_replace($pkey, $this->EE->functions->remove_double_slashes($pval.'/C'.$key), $chunk);
					}
				}

				// parse custom fields
				foreach($this->catfields as $ccv)
				{
					if (isset($val['field_id_'.$ccv['field_id']]) AND $val['field_id_'.$ccv['field_id']] != '')
					{
						$field_content = $this->EE->typography->parse_type($val['field_id_'.$ccv['field_id']],
																	array(
																		  'text_format'		=> $val['field_ft_'.$ccv['field_id']],
																		  'html_format'		=> $val['field_html_formatting'],
																		  'auto_links'		=> 'n',
																		  'allow_img_url'	=> 'y'
																		)
																);
						$chunk = str_replace(LD.$ccv['field_name'].RD, $field_content, $chunk);
					}
					else
					{
						// garbage collection
						$chunk = str_replace(LD.$ccv['field_name'].RD, '', $chunk);
					}
				}


				/** --------------------------------
				/**  {count}
				/** --------------------------------*/

				if (strpos($chunk, LD.'count'.RD) !== FALSE)
				{
					$chunk = str_replace(LD.'count'.RD, $this->category_count, $chunk);
				}

				/** --------------------------------
				/**  {total_results}
				/** --------------------------------*/

				if (strpos($chunk, LD.'total_results'.RD) !== FALSE)
				{
					$chunk = str_replace(LD.'total_results'.RD, $total_results, $chunk);
				}

				$this->category_list[] = $tab."\t<li>".$chunk;

				if (is_array($channel_array))
				{
					$fillable_entries = 'n';

					foreach($channel_array as $k => $v)
					{
						$k = substr($k, strpos($k, '_') + 1);

						if ($key == $k)
						{
							if ( ! isset($fillable_entries) OR $fillable_entries == 'n')
							{
								$this->category_list[] = "\n{$tab}\t\t<ul>\n";
								$fillable_entries = 'y';
							}

							$this->category_list[] = "{$tab}\t\t\t$v";
						}
					}
				}

				if (isset($fillable_entries) && $fillable_entries == 'y')
				{
					$this->category_list[] = "{$tab}\t\t</ul>\n";
				}

				$t = '';

				if ($this->category_subtree(
											array(
													'parent_id'		=> $key,
													'path'			=> $path,
													'template'		=> $template,
													'depth' 			=> $depth + 2,
													'channel_array' 	=> $channel_array
												  )
									) != 0 );

			if (isset($fillable_entries) && $fillable_entries == 'y')
			{
				$t .= "$tab\t";
			}

				$this->category_list[] = $t."</li>\n";

				unset($this->temp_array[$key]);

				$this->close_ul($parent_id, $depth + 1);
			}
		}
		return $open;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Close </ul> tags
	  *
	  * This is a helper function to the above
	  */
	function close_ul($parent_id, $depth = 0)
	{
		$count = 0;

		$tab = "";
		for ($i = 0; $i < $depth; $i++)
		{
			$tab .= "\t";
		}

		foreach ($this->temp_array as $val)
		{
		 	if ($parent_id == $val[0])

		 	$count++;
		}

		if ($count == 0)
			$this->category_list[] = $tab."</ul>\n";
	}

	// ------------------------------------------------------------------------

	/**
	  *  Channel "category_heading" tag
	  */
	function category_heading()
	{
		if ($this->query_string == '')
		{
			return;
		}

		// -------------------------------------------
		// 'channel_module_category_heading_start' hook.
		//  - Rewrite the displaying of category headings, if you dare!
		//
			if ($this->EE->extensions->active_hook('channel_module_category_heading_start') === TRUE)
			{
				$this->EE->TMPL->tagdata = $this->EE->extensions->call('channel_module_category_heading_start');
				if ($this->EE->extensions->end_script === TRUE) return $this->EE->TMPL->tagdata;
			}
		//
		// -------------------------------------------
		
		$qstring = $this->query_string;

		/** --------------------------------------
		/**  Remove page number
		/** --------------------------------------*/

		if (preg_match("#/P\d+#", $qstring, $match))
		{
			$qstring = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $qstring));
		}

		/** --------------------------------------
		/**  Remove "N"
		/** --------------------------------------*/
		if (preg_match("#/N(\d+)#", $qstring, $match))
		{
			$qstring = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $qstring));
		}

		// Is the category being specified by name?

		if ($qstring != '' AND $this->reserved_cat_segment != '' AND in_array($this->reserved_cat_segment, explode("/", $qstring)) AND $this->EE->TMPL->fetch_param('channel'))
		{
			$qstring = preg_replace("/(.*?)\/".preg_quote($this->reserved_cat_segment)."\//i", '', '/'.$qstring);
			
			$sql = "SELECT DISTINCT cat_group FROM exp_channels WHERE site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') AND ";

			$xsql = $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('channel'), 'channel_name');

			if (substr($xsql, 0, 3) == 'AND') $xsql = substr($xsql, 3);

			$sql .= ' '.$xsql;

			$query = $this->EE->db->query($sql);

			if ($query->num_rows() > 0)
			{
				$valid = 'y';
				$last  = explode('|', $query->row('cat_group') );
				$valid_cats = array();

				foreach($query->result_array() as $row)
				{
					if ($this->EE->TMPL->fetch_param('relaxed_categories') == 'yes')
					{
						$valid_cats = array_merge($valid_cats, explode('|', $row['cat_group']));
					}
					else
					{
						$valid_cats = array_intersect($last, explode('|', $row['cat_group']));
					}

					$valid_cats = array_unique($valid_cats);

					if (count($valid_cats) == 0)
					{
						$valid = 'n';
						break;
					}
				}
			}
			else
			{
				$valid = 'n';
			}

			if ($valid == 'y')
			{
				// the category URL title should be the first segment left at this point in $qstring,
				// but because prior to this feature being added, category names were used in URLs,
				// and '/' is a valid character for category names.  If they have not updated their
				// category url titles since updating to 1.6, their category URL title could still
				// contain a '/'.  So we'll try to get the category the correct way first, and if
				// it fails, we'll try the whole $qstring

				$cut_qstring = array_shift($temp = explode('/', $qstring));

				$result = $this->EE->db->query("SELECT cat_id FROM exp_categories
									  WHERE cat_url_title='".$this->EE->db->escape_str($cut_qstring)."'
									  AND group_id IN ('".implode("','", $valid_cats)."')");

				if ($result->num_rows() == 1)
				{
					$qstring = str_replace($cut_qstring, 'C'.$result->row('cat_id') , $qstring);
				}
				else
				{
					// give it one more try using the whole $qstring
					$result = $this->EE->db->query("SELECT cat_id FROM exp_categories
										  WHERE cat_url_title='".$this->EE->db->escape_str($qstring)."'
										  AND group_id IN ('".implode("','", $valid_cats)."')");

					if ($result->num_rows() == 1)
					{
						$qstring = 'C'.$result->row('cat_id') ;
					}
				}
			}
		}

		// Is the category being specified by ID?

		if ( ! preg_match("#(^|\/)C(\d+)#", $qstring, $match))
		{
			return $this->EE->TMPL->no_results();
		}

		// fetch category field names and id's

		if ($this->enable['category_fields'] === TRUE)
		{
			// limit to correct category group
			$gquery = $this->EE->db->query("SELECT group_id FROM exp_categories WHERE cat_id = '".$this->EE->db->escape_str($match[2])."'");

			if ($gquery->num_rows() == 0)
			{
				return $this->EE->TMPL->no_results();
			}

			$query = $this->EE->db->query("SELECT field_id, field_name
								FROM exp_category_fields
								WHERE site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."')
								AND group_id = '".$gquery->row('group_id')."'");

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
				}
			}

			$field_sqla = ", cg.field_html_formatting, fd.* ";
			$field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id
							LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id ";
		}
		else
		{
			$field_sqla = '';
			$field_sqlb = '';
		}

		$query = $this->EE->db->query("SELECT c.cat_name, c.parent_id, c.cat_url_title, c.cat_description, c.cat_image {$field_sqla}
							FROM exp_categories AS c
							{$field_sqlb}
							WHERE c.cat_id = '".$this->EE->db->escape_str($match[2])."'");

		if ($query->num_rows() == 0)
		{
			return $this->EE->TMPL->no_results();
		}

		$row = $query->row_array();

		$cat_vars = array('category_name'			=> $query->row('cat_name') ,
						  'category_description'	=> $query->row('cat_description') ,
						  'category_image'			=> $query->row('cat_image') ,
						  'category_id'				=> $match[2],
						  'parent_id'				=> $query->row('parent_id'));

		// add custom fields for conditionals prep
		foreach ($this->catfields as $v)
		{
			$cat_vars[$v['field_name']] = ($query->row('field_id_'.$v['field_id'])) ? $query->row('field_id_'.$v['field_id']) : '';
		}

		$this->EE->TMPL->tagdata = $this->EE->functions->prep_conditionals($this->EE->TMPL->tagdata, $cat_vars);

		$this->EE->TMPL->tagdata = str_replace( array(LD.'category_id'.RD,
											LD.'category_name'.RD,
											LD.'category_url_title'.RD,
											LD.'category_image'.RD,
											LD.'category_description'.RD,
											LD.'parent_id'.RD),
							 	 	  array($match[2],
											$query->row('cat_name'),
											$query->row('cat_url_title'),
											$query->row('cat_image'),
											$query->row('cat_description'),
											$query->row('parent_id')),
							  		  $this->EE->TMPL->tagdata);

		// parse custom fields

		$this->EE->load->library('typography');
		$this->EE->typography->initialize(array(
				'convert_curly'	=> FALSE)
				);

		// parse custom fields
		foreach($this->catfields as $ccv)
		{
			if ($query->row('field_id_'.$ccv['field_id']) AND $query->row('field_id_'.$ccv['field_id']) != '')
			{
				$field_content = $this->EE->typography->parse_type($query->row('field_id_'.$ccv['field_id']),
															array(
																  'text_format'		=> $query->row('field_ft_'.$ccv['field_id']),
																  'html_format'		=> $query->row('field_html_formatting'),
																  'auto_links'		=> 'n',
																  'allow_img_url'	=> 'y'
																)
														);
				$this->EE->TMPL->tagdata = str_replace(LD.$ccv['field_name'].RD, $field_content, $this->EE->TMPL->tagdata);
			}
			else
			{
				// garbage collection
				$this->EE->TMPL->tagdata = str_replace(LD.$ccv['field_name'].RD, '', $this->EE->TMPL->tagdata);
			}
		}

		return $this->EE->TMPL->tagdata;
	}

	// ------------------------------------------------------------------------

	/** ---------------------------------------
	/**  Next / Prev entry tags
	/** ---------------------------------------*/

	function next_entry()
	{
		return $this->next_prev_entry('next');
	}

	function prev_entry()
	{
		return $this->next_prev_entry('prev');
	}

	function next_prev_entry($which = 'next')
	{
		$which = ($which != 'next' AND $which != 'prev') ? 'next' : $which;
		$sort = ($which == 'next') ? 'ASC' : 'DESC';

		// Don't repeat our work if we already know the single entry page details
		if ( ! isset($this->EE->session->cache['channel']['single_entry_id']) OR ! isset($this->EE->session->cache['channel']['single_entry_date']))
		{
			// no query string?  Nothing to do...
			if (($qstring = $this->query_string) == '')
			{
				return;
			}

			/** --------------------------------------
			/**  Remove page number
			/** --------------------------------------*/

			if (preg_match("#/P\d+#", $qstring, $match))
			{
				$qstring = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $qstring));
			}

			/** --------------------------------------
			/**  Remove "N"
			/** --------------------------------------*/

			if (preg_match("#/N(\d+)#", $qstring, $match))
			{
				$qstring = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $qstring));
			}

			if (strpos($qstring, '/') !== FALSE)
			{
				$qstring = substr($qstring, 0, strpos($qstring, '/'));
			}

			/** ---------------------------------------
			/**  Query for the entry id and date
			/** ---------------------------------------*/

			$sql = 'SELECT t.entry_id, t.entry_date
					FROM (exp_channel_titles AS t)
					LEFT JOIN exp_channels AS w ON w.channel_id = t.channel_id ';

	        if (is_numeric($qstring))
	        {
				$sql .= " WHERE t.entry_id = '".$this->EE->db->escape_str($qstring)."' ";
	        }
	        else
	        {
				$sql .= " WHERE t.url_title = '".$this->EE->db->escape_str($qstring)."' ";
	        }

			$sql .= " AND w.site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";

			if ($channel_name = $this->EE->TMPL->fetch_param('channel'))
			{
				$sql .= $this->EE->functions->sql_andor_string($channel_name, 'channel_name', 'w');
			}

			$query = $this->EE->db->query($sql);


			// no results or more than one result?  Buh bye!
			if ($query->num_rows() != 1)
			{
				$this->EE->TMPL->log_item('Channel Next/Prev Entry tag error: Could not resolve single entry page id.');
				return;
			}

			$row = $query->row_array();

			$this->EE->session->cache['channel']['single_entry_id'] = $row['entry_id'];
			$this->EE->session->cache['channel']['single_entry_date'] = $row['entry_date'];
		}

		/** ---------------------------------------
		/**  Find the next / prev entry
		/** ---------------------------------------*/

		$ids = '';

		// Get included or excluded entry ids from entry_id parameter
		if (($entry_id = $this->EE->TMPL->fetch_param('entry_id')) != FALSE)
		{
			$ids = $this->EE->functions->sql_andor_string($entry_id, 't.entry_id').' ';
		}

		$sql = 'SELECT t.entry_id, t.title, t.url_title
				FROM (exp_channel_titles AS t)
				LEFT JOIN exp_channels AS w ON w.channel_id = t.channel_id ';

		/* --------------------------------
		/*  We use LEFT JOIN when there is a 'not' so that we get
		/*  entries that are not assigned to a category.
		/* --------------------------------*/

		if ((substr($this->EE->TMPL->fetch_param('category_group'), 0, 3) == 'not' OR substr($this->EE->TMPL->fetch_param('category'), 0, 3) == 'not') && $this->EE->TMPL->fetch_param('uncategorized_entries') !== 'n')
		{
			$sql .= 'LEFT JOIN exp_category_posts ON t.entry_id = exp_category_posts.entry_id
					 LEFT JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ';
		}
		elseif($this->EE->TMPL->fetch_param('category_group') OR $this->EE->TMPL->fetch_param('category'))
		{
			$sql .= 'INNER JOIN exp_category_posts ON t.entry_id = exp_category_posts.entry_id
					 INNER JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ';
		}

		$sql .= ' WHERE t.entry_id != '.$this->EE->session->cache['channel']['single_entry_id'].' '.$ids;

		$timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->TMPL->cache_timestamp : $this->EE->localize->now;

	    if ($this->EE->TMPL->fetch_param('show_future_entries') != 'yes')
	    {
	    	$sql .= " AND t.entry_date < {$timestamp} ";
	    }

		// constrain by date depending on whether this is a 'next' or 'prev' tag
		if ($which == 'next')
		{
			$sql .= ' AND t.entry_date >= '.$this->EE->session->cache['channel']['single_entry_date'].' ';
			$sql .= ' AND IF (t.entry_date = '.$this->EE->session->cache['channel']['single_entry_date'].', t.entry_id > '.$this->EE->session->cache['channel']['single_entry_id'].', 1) ';
		}
		else
		{
			$sql .= ' AND t.entry_date <= '.$this->EE->session->cache['channel']['single_entry_date'].' ';
			$sql .= ' AND IF (t.entry_date = '.$this->EE->session->cache['channel']['single_entry_date'].', t.entry_id < '.$this->EE->session->cache['channel']['single_entry_id'].', 1) ';
		}

	    if ($this->EE->TMPL->fetch_param('show_expired') != 'yes')
	    {
			$sql .= " AND (t.expiration_date = 0 OR t.expiration_date > {$timestamp}) ";
	    }

		$sql .= " AND w.site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";

		if ($channel_name = $this->EE->TMPL->fetch_param('channel'))
		{
			$sql .= $this->EE->functions->sql_andor_string($channel_name, 'channel_name', 'w')." ";
		}

		if ($status = $this->EE->TMPL->fetch_param('status'))
	    {
			$status = str_replace('Open',   'open',   $status);
			$status = str_replace('Closed', 'closed', $status);

			$sql .= $this->EE->functions->sql_andor_string($status, 't.status')." ";
		}
		else
		{
			$sql .= "AND t.status = 'open' ";
		}

		/**------
	    /**  Limit query by category
	    /**------*/

	    if ($this->EE->TMPL->fetch_param('category'))
	    {
	    	if (stristr($this->EE->TMPL->fetch_param('category'), '&'))
	    	{
	    		/** --------------------------------------
	    		/**  First, we find all entries with these categories
	    		/** --------------------------------------*/

	    		$for_sql = (substr($this->EE->TMPL->fetch_param('category'), 0, 3) == 'not') ? trim(substr($this->EE->TMPL->fetch_param('category'), 3)) : $this->EE->TMPL->fetch_param('category');

	    		$csql = "SELECT exp_category_posts.entry_id, exp_category_posts.cat_id, ".
						str_replace('SELECT', '', $sql).						
						$this->EE->functions->sql_andor_string(str_replace('&', '|', $for_sql), 'exp_categories.cat_id');

	    		//exit($csql);

	    		$results = $this->EE->db->query($csql);

	    		if ($results->num_rows() == 0)
	    		{
					return;
	    		}

	    		$type = 'IN';
	    		$categories	 = explode('&', $this->EE->TMPL->fetch_param('category'));
	    		$entry_array = array();

	    		if (substr($categories[0], 0, 3) == 'not')
	    		{
	    			$type = 'NOT IN';

	    			$categories[0] = trim(substr($categories[0], 3));
	    		}

				foreach($results->result_array() as $row)
	    		{
	    			$entry_array[$row['cat_id']][] = $row['entry_id'];
	    		}

	    		if (count($entry_array) < 2 OR count(array_diff($categories, array_keys($entry_array))) > 0)
	    		{
					return;
	    		}

	    		$chosen = call_user_func_array('array_intersect', $entry_array);

	    		if (count($chosen) == 0)
	    		{
					return;
	    		}

	    		$sql .= "AND t.entry_id ".$type." ('".implode("','", $chosen)."') ";
	    	}
	    	else
	    	{
	    		if (substr($this->EE->TMPL->fetch_param('category'), 0, 3) == 'not' && $this->EE->TMPL->fetch_param('uncategorized_entries') !== 'n')
	    		{
	    			$sql .= $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('category'), 'exp_categories.cat_id', '', TRUE)." ";
	    		}
	    		else
	    		{
	    			$sql .= $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('category'), 'exp_categories.cat_id')." ";
	    		}
	    	}
	    }

	    if ($this->EE->TMPL->fetch_param('category_group'))
	    {
	        if (substr($this->EE->TMPL->fetch_param('category_group'), 0, 3) == 'not' && $this->EE->TMPL->fetch_param('uncategorized_entries') !== 'n')
			{
				$sql .= $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('category_group'), 'exp_categories.group_id', '', TRUE)." ";
			}
			else
			{
				$sql .= $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('category_group'), 'exp_categories.group_id')." ";
			}
	    }

		$sql .= " ORDER BY t.entry_date {$sort}, t.entry_id {$sort} LIMIT 1";

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return;
		}

		/** ---------------------------------------
		/**  Replace variables
		/** ---------------------------------------*/

		$this->EE->load->library('typography');

		if (strpos($this->EE->TMPL->tagdata, LD.'path=') !== FALSE)
		{
			$path  = (preg_match("#".LD."path=(.+?)".RD."#", $this->EE->TMPL->tagdata, $match)) ? $this->EE->functions->create_url($match[1]) : $this->EE->functions->create_url("SITE_INDEX");
			$path .= '/'.$query->row('url_title');
			$this->EE->TMPL->tagdata = preg_replace("#".LD."path=.+?".RD."#", $path, $this->EE->TMPL->tagdata);
		}

		if (strpos($this->EE->TMPL->tagdata, LD.'id_path=') !== FALSE)
		{
			$id_path  = (preg_match("#".LD."id_path=(.+?)".RD."#", $this->EE->TMPL->tagdata, $match)) ? $this->EE->functions->create_url($match[1]) : $this->EE->functions->create_url("SITE_INDEX");
			$id_path .= '/'.$query->row('entry_id');

			$this->EE->TMPL->tagdata = preg_replace("#".LD."id_path=.+?".RD."#", $id_path, $this->EE->TMPL->tagdata);
		}

		if (strpos($this->EE->TMPL->tagdata, LD.'url_title') !== FALSE)
		{
			$this->EE->TMPL->tagdata = str_replace(LD.'url_title'.RD, $query->row('url_title'), $this->EE->TMPL->tagdata);
		}

		if (strpos($this->EE->TMPL->tagdata, LD.'entry_id') !== FALSE)
		{
			$this->EE->TMPL->tagdata = str_replace(LD.'entry_id'.RD, $query->row('entry_id'), $this->EE->TMPL->tagdata);
		}

		if (strpos($this->EE->TMPL->tagdata, LD.'title') !== FALSE)
		{
			$this->EE->TMPL->tagdata = str_replace(LD.'title'.RD, $this->EE->typography->format_characters($query->row('title')), $this->EE->TMPL->tagdata);
		}

		if (strpos($this->EE->TMPL->tagdata, '_entry->title') !== FALSE)
		{
			$this->EE->TMPL->tagdata = preg_replace('/'.LD.'(?:next|prev)_entry->title'.RD.'/', 
													$this->EE->typography->format_characters($query->row('title')),
													$this->EE->TMPL->tagdata);
		}

		return $this->EE->functions->remove_double_slashes(stripslashes($this->EE->TMPL->tagdata));
	}

	// ------------------------------------------------------------------------

	/**
	  *  Channel "month links"
	  */
	function month_links()
	{
		$return = '';

		//  Build query

		// Fetch the timezone array and calculate the offset so we can localize the month/year
		$zones = $this->EE->localize->zones();

		$offset = ( ! isset($zones[$this->EE->session->userdata['timezone']]) OR $zones[$this->EE->session->userdata['timezone']] == '') ? 0 : ($zones[$this->EE->session->userdata['timezone']]*60*60);

		if (substr($offset, 0, 1) == '-')
		{
			$calc = 'entry_date - '.substr($offset, 1);
		}
		elseif (substr($offset, 0, 1) == '+')
		{
			$calc = 'entry_date + '.substr($offset, 1);
		}
		else
		{
			$calc = 'entry_date + '.$offset;
		}

		$sql = "SELECT DISTINCT year(FROM_UNIXTIME(".$calc.")) AS year,
						MONTH(FROM_UNIXTIME(".$calc.")) AS month
						FROM exp_channel_titles
						WHERE entry_id != ''
						AND site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";


		$timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->TMPL->cache_timestamp : $this->EE->localize->now;

		if ($this->EE->TMPL->fetch_param('show_future_entries') != 'yes')
		{
			$sql .= " AND exp_channel_titles.entry_date < ".$timestamp." ";
		}

		if ($this->EE->TMPL->fetch_param('show_expired') != 'yes')
		{
			$sql .= " AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > ".$timestamp.") ";
		}

		/**------
		/**  Limit to/exclude specific channels
		/**------*/

		if ($channel = $this->EE->TMPL->fetch_param('channel'))
		{
			$wsql = "SELECT channel_id FROM exp_channels WHERE site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";

			$wsql .= $this->EE->functions->sql_andor_string($channel, 'channel_name');

			$query = $this->EE->db->query($wsql);

			if ($query->num_rows() > 0)
			{
				$sql .= " AND ";

				if ($query->num_rows() == 1)
				{
					$sql .= "channel_id = '".$query->row('channel_id') ."' ";
				}
				else
				{
					$sql .= "(";

					foreach ($query->result_array() as $row)
					{
						$sql .= "channel_id = '".$row['channel_id']."' OR ";
					}

					$sql = substr($sql, 0, - 3);

					$sql .= ") ";
				}
			}
		}

		/**------
		/**  Add status declaration
		/**------*/

		if ($status = $this->EE->TMPL->fetch_param('status'))
		{
			$status = str_replace('Open',	'open',	$status);
			$status = str_replace('Closed', 'closed', $status);

			$sstr = $this->EE->functions->sql_andor_string($status, 'status');

			if (stristr($sstr, "'closed'") === FALSE)
			{
				$sstr .= " AND status != 'closed' ";
			}

			$sql .= $sstr;
		}
		else
		{
			$sql .= "AND status = 'open' ";
		}

		$sql .= " ORDER BY entry_date";

		switch ($this->EE->TMPL->fetch_param('sort'))
		{
			case 'asc'	: $sql .= " asc";
				break;
			case 'desc'	: $sql .= " desc";
				break;
			default		: $sql .= " desc";
				break;
		}

		if (is_numeric($this->EE->TMPL->fetch_param('limit')))
		{
			$sql .= " LIMIT ".$this->EE->TMPL->fetch_param('limit');
		}

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return '';
		}

		$year_limit	= (is_numeric($this->EE->TMPL->fetch_param('year_limit'))) ? $this->EE->TMPL->fetch_param('year_limit') : 50;
		$total_years  = 0;
		$current_year = '';

		foreach ($query->result_array() as $row)
		{
			$tagdata = $this->EE->TMPL->tagdata;

			$month = (strlen($row['month']) == 1) ? '0'.$row['month'] : $row['month'];
			$year  = $row['year'];

			$month_name = $this->EE->localize->localize_month($month);

			//  Dealing with {year_heading}
			if (isset($this->EE->TMPL->var_pair['year_heading']))
			{
				if ($year == $current_year)
				{
					$tagdata = $this->EE->TMPL->delete_var_pairs('year_heading', 'year_heading', $tagdata);
				}
				else
				{
					$tagdata = $this->EE->TMPL->swap_var_pairs('year_heading', 'year_heading', $tagdata);

					$total_years++;

					if ($total_years > $year_limit)
					{
						break;
					}
				}

				$current_year = $year;
			}

			/** ---------------------------------------
			/**  prep conditionals
			/** ---------------------------------------*/

			$cond = array();

			$cond['month']			= $this->EE->lang->line($month_name[1]);
			$cond['month_short']	= $this->EE->lang->line($month_name[0]);
			$cond['month_num']		= $month;
			$cond['year']			= $year;
			$cond['year_short']		= substr($year, 2);

			$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);

			//  parse path
			foreach ($this->EE->TMPL->var_single as $key => $val)
			{
				if (strncmp($key, 'path', 4) == 0)
				{
					$tagdata = $this->EE->TMPL->swap_var_single(
														$val,
														$this->EE->functions->create_url($this->EE->functions->extract_path($key).'/'.$year.'/'.$month),
														$tagdata
													  );
				}

				//  parse month (long)
				if ($key == 'month')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($key, $this->EE->lang->line($month_name[1]), $tagdata);
				}

				//  parse month (short)
				if ($key == 'month_short')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($key, $this->EE->lang->line($month_name[0]), $tagdata);
				}

				//  parse month (numeric)
				if ($key == 'month_num')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($key, $month, $tagdata);
				}

				//  parse year
				if ($key == 'year')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($key, $year, $tagdata);
				}

				//  parse year (short)
				if ($key == 'year_short')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($key, substr($year, 2), $tagdata);
				}
			 }

			 $return .= trim($tagdata)."\n";
		 }

		return $return;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Related Categories Mode
	  *
	  * This function shows entries that are in the same category as
	  * the primary entry being shown.  It calls the main "channel entries"
	  * function after setting some variables to control the content.
	  *
	  * Note:  We have deprecated the calling of this tag directly via its own tag.
	  * Related entries are now shown using the standard {exp:channel:entries} tag.
	  * The reason we're deprecating it is to avoid confusion since the channel tag
	  * now supports relational capability via a pair of {related_entries} tags.
	  *
	  * To show "related entries" the following parameter is added to the {exp:channel:entries} tag:
	  *
	  * related_categories_mode="on"
	  */
	function related_entries()
	{
		if ($this->query_string == '')
		{
			return FALSE;
		}

		$qstring = $this->query_string;

		/** --------------------------------------
		/**  Remove page number
		/** --------------------------------------*/

		if (preg_match("#/P\d+#", $qstring, $match))
		{
			$qstring = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $qstring));
		}

		/** --------------------------------------
		/**  Remove "N"
		/** --------------------------------------*/
		if (preg_match("#/N(\d+)#", $qstring, $match))
		{
			$qstring = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $qstring));
		}

		/** --------------------------------------
		/**  Make sure to only get one segment
		/** --------------------------------------*/

		if (strpos($qstring, '/') !== FALSE)
		{
			$qstring = substr($qstring, 0, strpos($qstring, '/'));
		}

		/** ----------------------------------
		/**  Find Categories for Entry
		/** ----------------------------------*/

		$sql = "SELECT exp_categories.cat_id, exp_categories.cat_name
				FROM exp_channel_titles
				INNER JOIN exp_category_posts ON exp_channel_titles.entry_id = exp_category_posts.entry_id
				INNER JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id
				WHERE exp_categories.cat_id IS NOT NULL
				AND exp_channel_titles.site_id IN ('".implode("','", $this->EE->TMPL->site_ids)."') ";

		$sql .= ( ! is_numeric($qstring)) ? "AND exp_channel_titles.url_title = '".$this->EE->db->escape_str($qstring)."' " : "AND exp_channel_titles.entry_id = '".$this->EE->db->escape_str($qstring)."' ";

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return $this->EE->TMPL->no_results();
		}

		/** ----------------------------------
		/**  Build category array
		/** ----------------------------------*/

		$cat_array = array();

		// We allow the option of adding or subtracting cat_id's
		$categories = ( ! $this->EE->TMPL->fetch_param('category'))  ? '' : $this->EE->TMPL->fetch_param('category');

		if (strncmp($categories, 'not ', 4) == 0)
		{
			$categories = substr($categories, 4);
			$not_categories = explode('|',$categories);
		}
		else
		{
			$add_categories = explode('|',$categories);
		}

		foreach($query->result_array() as $row)
		{
			if ( ! isset($not_categories) OR array_search($row['cat_id'], $not_categories) === FALSE)
			{
				$cat_array[] = $row['cat_id'];
			}
		}

		// User wants some categories added, so we add these cat_id's

		if (isset($add_categories) && count($add_categories) > 0)
		{
			foreach($add_categories as $cat_id)
			{
				$cat_array[] = $cat_id;
			}
		}

		// Just in case
		$cat_array = array_unique($cat_array);

		if (count($cat_array) == 0)
		{
			return $this->EE->TMPL->no_results();
		}

		/** ----------------------------------
		/**  Build category string
		/** ----------------------------------*/

		$cats = '';

		foreach($cat_array as $cat_id)
		{
			if ($cat_id != '')
			{
				$cats .= $cat_id.'|';
			}
		}
		$cats = substr($cats, 0, -1);

		/** ----------------------------------
		/**  Manually set paramters
		/** ----------------------------------*/

		$this->EE->TMPL->tagparams['category']		= $cats;
		$this->EE->TMPL->tagparams['dynamic']			= 'off';
		$this->EE->TMPL->tagparams['not_entry_id']	= $qstring; // Exclude the current entry

		// Set user submitted paramters

		$params = array('channel', 'username', 'status', 'orderby', 'sort');

		foreach ($params as $val)
		{
			if ($this->EE->TMPL->fetch_param($val) != FALSE)
			{
				$this->EE->TMPL->tagparams[$val] = $this->EE->TMPL->fetch_param($val);
			}
		}

		if ( ! is_numeric($this->EE->TMPL->fetch_param('limit')))
		{
			$this->EE->TMPL->tagparams['limit'] = 10;
		}

		/** ----------------------------------
		/**  Run the channel parser
		/** ----------------------------------*/

		$this->initialize();
		$this->entry_id 	= '';
		$qstring 			= '';

		if ($this->enable['custom_fields'] == TRUE && $this->EE->TMPL->fetch_param('custom_fields') == 'yes')
		{
			$this->fetch_custom_channel_fields();
		}

		$this->build_sql_query();

		if ($this->sql == '')
		{
			return $this->EE->TMPL->no_results();
		}

		$this->query = $this->EE->db->query($this->sql);

		if ($this->query->num_rows() == 0)
		{
			return $this->EE->TMPL->no_results();
		}

		$this->EE->load->library('typography');
		$this->EE->typography->initialize(array(
				'convert_curly'	=> FALSE)
				);

		if ($this->EE->TMPL->fetch_param('member_data') !== FALSE && $this->EE->TMPL->fetch_param('member_data') == 'yes')
		{
			$this->fetch_custom_member_fields();
		}

		$this->parse_channel_entries();

		return $this->return_data;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Fetch Disable Parameter
	  */
	function _fetch_disable_param()
	{
		$this->enable = array(
							'categories' 		=> TRUE,
							'category_fields'	=> TRUE,
							'custom_fields'		=> TRUE,
							'member_data'		=> TRUE,
							'pagination' 		=> TRUE,
							);

		if ($disable = $this->EE->TMPL->fetch_param('disable'))
		{
			if (strpos($disable, '|') !== FALSE)
			{
				foreach (explode("|", $disable) as $val)
				{
					if (isset($this->enable[$val]))
					{
						$this->enable[$val] = FALSE;
					}
				}
			}
			elseif (isset($this->enable[$disable]))
			{
				$this->enable[$disable] = FALSE;
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Channel Calendar
	  */
	function calendar()
	{
		// -------------------------------------------
		// 'channel_module_calendar_start' hook.
		//  - Rewrite the displaying of the calendar tag
		//
			if ($this->EE->extensions->active_hook('channel_module_calendar_start') === TRUE)
			{
				$edata = $this->EE->extensions->call('channel_module_calendar_start');
				if ($this->EE->extensions->end_script === TRUE) return $edata;
			}
		//
		// -------------------------------------------
		
		if ( ! class_exists('Channel_calendar'))
		{
			require PATH_MOD.'channel/mod.channel_calendar.php';
		}

		$WC = new Channel_calendar();
		return $WC->calendar();
	}

	// ------------------------------------------------------------------------

	/**
	  *  Insert a new channel entry
	  *
	  * This function serves dual purpose:
	  * 1. It allows submitted data to be previewed
	  * 2. It allows submitted data to be inserted
	  */
	function insert_new_entry()
	{
		if ( ! class_exists('Channel_standalone'))
		{
			require PATH_MOD.'channel/mod.channel_standalone.php';
		}

		$WS = new Channel_standalone();
		$WS->insert_new_entry();
	}
	
	// ------------------------------------------------------------------------

	/**
	  *  Ajax Image Upload
	  *
	  * Used by the SAEF
	  */

	function filemanager_endpoint($function = '', $params = array())
	{
		$this->EE->load->library('filemanager');
		$this->EE->lang->loadfile('content');
		//$this->EE->load->library('cp');
		
		$config = array();
		
		if ($function)
		{
			$this->EE->filemanager->_initialize($config);
			
			return call_user_func_array(array($this->filemanager, $function), $params);
		}

		$this->EE->filemanager->process_request($config);		
	}	
	
	// ------------------------------------------------------------------------

	/**
	  *  Smiley pop up
	  *
	  * Used by the SAEF
	  */

	function smiley_pop()
	{	
		if ($this->EE->session->userdata('member_id') == 0)
		{
			return $this->EE->output->fatal_error($this->EE->lang->line('must_be_logged_in'));
		}
		
		$class_path = PATH_MOD.'emoticon/emoticons.php';
		
		if ( ! is_file($class_path) OR ! @include_once($class_path))
		{
			return $this->EE->output->fatal_error('Unable to locate the smiley images');
		}
		
		if ( ! is_array($smileys))
		{
			return;
		}
		
		$path = $this->EE->config->slash_item('emoticon_url');
				
		ob_start();
		?>			 
		<script type="text/javascript"> 
		<!--
		function add_smiley(smiley)
		{
			var el = opener.document.getElementById('submit_post').body;

			if ('selectionStart' in el) {
				newStart = el.selectionStart + smiley.length;

				el.value = el.value.substr(0, el.selectionStart) +
								smiley +
								el.value.substr(el.selectionEnd, el.value.length);
				el.setSelectionRange(newStart, newStart);
			}
			else if (opener.document.selection) {
				opener.document.selection.createRange().text = smiley;
			}
			else {
				el.value += " " + smiley + " ";
			}

			el.focus();
			window.close();
		}
		//-->
		</script>
		
		<?php

		$javascript = ob_get_contents();
		ob_end_clean();		
		$r = $javascript;
				
		
		$i = 1;
		
		$dups = array();
		
		foreach ($smileys as $key => $val)
		{
			if ($i == 1 AND substr($r, -5) != "<tr>\n")
			{
				$r .= "<tr>\n";				
			}
			
			if (in_array($smileys[$key]['0'], $dups))
				continue;
			
			$r .= "<td class='tableCellOne' align='center'><a href=\"#\" onclick=\"return add_smiley('".$key."');\"><img src=\"".$path.$smileys[$key]['0']."\" width=\"".$smileys[$key]['1']."\" height=\"".$smileys[$key]['2']."\" alt=\"".$smileys[$key]['3']."\" border=\"0\" /></a></td>\n";

			$dups[] = $smileys[$key]['0'];

			if ($i == 10)
			{
				$r .= "</tr>\n";				
				
				$i = 1;
			}
			else
			{
				$i++;
			}	  
		}
		
		$r = rtrim($r);
				
		if (substr($r, -5) != "</tr>")
		{
			$r .= "</tr>\n";
		}

		$out = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
			.'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
			.'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{lang}" lang="{lang}">'
			.'<head>'
			.'<meta http-equiv="content-type" content="text/html; charset={charset}" />'
			.'<title>Smileys</title>'
			.'</head><body>';
		
		$out .= '<div id="content">'
			.'<div  class="tableBorderTopLeft">'
			.'<table cellpadding="3" cellspacing="0" border="0" style="width:100%;" class="tableBG">';
		$out .= $r;
		$out .= '</table></div></div></body></html>';

		print_r($out);
		exit;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Stand-alone version of the entry form
	  */
	function entry_form($return_form = FALSE, $captcha = '')
	{
		if ( ! class_exists('Channel_standalone'))
		{
			require PATH_MOD.'channel/mod.channel_standalone.php';
		}

		$WS = new Channel_standalone();
		return $WS->entry_form($return_form, $captcha);
	}

	// ------------------------------------------------------------------------
	
	/**
	 * ACT method for Stand Alone Entry Form Javascript
	 */
	function saef_filebrowser()
	{
		if ( ! class_exists('Channel_standalone'))
		{
			require PATH_MOD.'channel/mod.channel_standalone.php';
		}
		
		$channel_js = new Channel_standalone();
		return $channel_js->saef_javascript();
	}

}
// END CLASS

/* End of file mod.channel.php */
/* Location: ./system/expressionengine/modules/channel/mod.channel.php */
