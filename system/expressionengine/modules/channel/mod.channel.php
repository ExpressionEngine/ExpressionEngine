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

// --------------------------------------------------------------------

/**
 * ExpressionEngine Channel Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Channel {

	public $limit	= '100';	// Default maximum query results if not specified.

	// These variable are all set dynamically

	public $query;
	public $TYPE;
	public $entry_id				= '';
	public $uri						= '';
	public $uristr					= '';
	public $return_data				= '';	 	// Final data
	public $hit_tracking_id			= FALSE;
	public $sql						= FALSE;
	public $cfields					= array();
	public $dfields					= array();
	public $rfields					= array();
	public $gfields					= array();
	public $mfields					= array();
	public $pfields					= array();
	public $categories				= array();
	public $catfields				= array();
	public $channel_name	 		= array();
	public $channels_array			= array();
	public $reserved_cat_segment 	= '';
	public $use_category_names		= FALSE;
	public $cat_request				= FALSE;
	public $enable					= array();	// modified by various tags with disable= parameter
	public $absolute_results		= NULL;		// absolute total results returned by the tag, useful when paginating
	public $display_by				= '';

	// These are used with the nested category trees

	public $category_list  			= array();
	public $cat_full_array			= array();
	public $cat_array				= array();
	public $temp_array				= array();
	public $category_count			= 0;

	public $pagination;
	public $pager_sql 				= '';

	// SQL Caching
	public $sql_cache_dir			= 'sql_cache/';

	// Misc. - Class variable usable by extensions
	public $misc					= FALSE;

	// Array of parameters allowed to be set dynamically
	private $_dynamic_parameters	= array();

	/**
	  * Constructor
	  */
	public function Channel()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		ee()->load->library('pagination');
		$this->pagination = ee()->pagination->create(__CLASS__);
		// $this->pagination->per_page = $this->limit;

		// Used by pagination to determine whether we're coming from the cache
		$this->pagination->dynamic_sql = FALSE;

		$this->query_string = (ee()->uri->page_query_string != '') ? ee()->uri->page_query_string : ee()->uri->query_string;

		if (ee()->config->item("use_category_name") == 'y' && ee()->config->item("reserved_category_word") != '')
		{
			$this->use_category_names	= ee()->config->item("use_category_name");
			$this->reserved_cat_segment	= ee()->config->item("reserved_category_word");
		}

		// a number of tags utilize the disable= parameter, set it here
		if (isset(ee()->TMPL) && is_object(ee()->TMPL))
		{
			$this->_fetch_disable_param();
		}

		$this->_dynamic_parameters = array('channel', 'entry_id', 'category', 'orderby',
			'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from',
			'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month',
			'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'cat_limit',
			'month_limit', 'offset', 'author_id', 'url_title');
	}

	// ------------------------------------------------------------------------

	/**
	  *  Initialize values
	  */
	public function initialize()
	{
		$this->sql 			= '';
		$this->return_data	= '';
	}

	// ------------------------------------------------------------------------

	/**
	  *  Fetch Cache
	  */
	public function fetch_cache($identifier = '')
	{
		$tag = ($identifier == '') ? ee()->TMPL->tagproper : ee()->TMPL->tagproper.$identifier;

		if (ee()->TMPL->fetch_param('dynamic_parameters') !== FALSE && (! empty($_POST) OR ! empty($_GET)))
		{
			foreach (explode('|', ee()->TMPL->fetch_param('dynamic_parameters')) as $var)
			{
				if ($this->EE->input->get_post($var) && in_array($var, $this->_dynamic_parameters))
				{
					$tag .= $var.'="'.$this->EE->input->get_post($var).'"';
				}

				if (strncmp($var, 'search:', 7) == 0 && $this->EE->input->get_post($var))
				{
					$tag .= $var.'="'.substr($this->EE->input->get_post($var), 7).'"';
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
	public function save_cache($sql, $identifier = '')
	{
		$tag = ($identifier == '') ? ee()->TMPL->tagproper : ee()->TMPL->tagproper.$identifier;

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
	public function entries()
	{
		// If the "related_categories" mode is enabled
		// we'll call the "related_categories" function
		// and bail out.

		if (ee()->TMPL->fetch_param('related_categories_mode') == 'yes')
		{
			return $this->related_category_entries();
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
			$this->pagination->get_template();
		}

		$save_cache = FALSE;

		if (ee()->config->item('enable_sql_caching') == 'y' && ee()->TMPL->fetch_param('author_id') != 'CURRENT_USER')
		{
			if (FALSE == ($this->sql = $this->fetch_cache()))
			{
				$save_cache = TRUE;
			}
			else
			{
				if (ee()->TMPL->fetch_param('dynamic') != 'no')
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
						$this->pagination->paginate = TRUE;
						$this->pagination->field_pagination = TRUE;
						$this->pagination->cfields = $this->cfields;
						$this->pagination->build(trim($cache), $this->sql, ee()->db->query(trim($pg_query)));
					}
				}
				else
				{
					$this->pagination->cfields = $this->cfields;
					$this->pagination->build(trim($cache), $this->sql);
				}
			}
		}

		if ($this->sql == '')
		{
			$this->build_sql_query();
		}

		if ($this->sql == '')
		{
			return ee()->TMPL->no_results();
		}

		if ($save_cache == TRUE)
		{
			$this->save_cache($this->sql);
		}

		$this->query = ee()->db->query($this->sql);

		if ($this->query->num_rows() == 0)
		{
			return ee()->TMPL->no_results();
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
		if (ee()->config->item('relaxed_track_views') === 'y' && $this->query->num_rows() == 1)
		{
			$this->hit_tracking_id = $this->query->row('entry_id') ;
		}

		$this->track_views();

		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'convert_curly'	=> FALSE
		));

		if ($this->enable['categories'] == TRUE)
		{
			$this->fetch_categories();
		}

		$this->parse_channel_entries();

		if ($this->enable['pagination'] == TRUE)
		{
			$this->return_data = $this->pagination->render($this->return_data);
		}

		return $this->return_data;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Track Views
	  */
	public function track_views()
	{
		if (ee()->config->item('enable_entry_view_tracking') == 'n')
		{
			return;
		}

		if ( ! ee()->TMPL->fetch_param('track_views') OR $this->hit_tracking_id === FALSE)
		{
			return;
		}

		if ($this->pagination->field_pagination == TRUE AND $this->pagination->offset > 0)
		{
			return;
		}

		foreach (explode('|', ee()->TMPL->fetch_param('track_views')) as $view)
		{
			if ( ! in_array(strtolower($view), array("one", "two", "three", "four")))
			{
				continue;
			}

			$sql = "UPDATE exp_channel_titles SET view_count_{$view} = (view_count_{$view} + 1) WHERE ";
			$sql .= (is_numeric($this->hit_tracking_id)) ? "entry_id = {$this->hit_tracking_id}" : "url_title = '".ee()->db->escape_str($this->hit_tracking_id)."'";

			ee()->db->query($sql);
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Fetch custom channel field IDs
	  */
	public function fetch_custom_channel_fields()
	{
		if (isset(ee()->session->cache['channel']['custom_channel_fields']) &&
			isset(ee()->session->cache['channel']['date_fields']) &&
			isset(ee()->session->cache['channel']['relationship_fields']) &&
			isset(ee()->session->cache['channel']['grid_fields']) &&
			isset(ee()->session->cache['channel']['pair_custom_fields']))
		{
			$this->cfields = ee()->session->cache['channel']['custom_channel_fields'];
			$this->dfields = ee()->session->cache['channel']['date_fields'];
			$this->rfields = ee()->session->cache['channel']['relationship_fields'];
			$this->gfields = ee()->session->cache['channel']['grid_fields'];
			$this->pfields = ee()->session->cache['channel']['pair_custom_fields'];
			return;
		}

		ee()->load->library('api');
		ee()->api->instantiate('channel_fields');

		$fields = ee()->api_channel_fields->fetch_custom_channel_fields();

		$this->cfields = $fields['custom_channel_fields'];
		$this->dfields = $fields['date_fields'];
		$this->rfields = $fields['relationship_fields'];
		$this->gfields = $fields['grid_fields'];
		$this->pfields = $fields['pair_custom_fields'];

  		ee()->session->cache['channel']['custom_channel_fields']	= $this->cfields;
		ee()->session->cache['channel']['date_fields']				= $this->dfields;
		ee()->session->cache['channel']['relationship_fields']		= $this->rfields;
		ee()->session->cache['channel']['grid_fields']				= $this->gfields;
		ee()->session->cache['channel']['pair_custom_fields']		= $this->pfields;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Fetch custom member field IDs
	  */
	public function fetch_custom_member_fields()
	{
		ee()->db->select('m_field_id, m_field_name, m_field_fmt');
		$query = ee()->db->get('member_fields');

		$fields_present = FALSE;

		$t1 = microtime(TRUE);

		foreach ($query->result_array() as $row)
		{
			if (strpos(ee()->TMPL->tagdata, $row['m_field_name']) !== FALSE)
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
	public function fetch_categories()
	{
		if ($this->enable['category_fields'] === TRUE)
		{
			$query = ee()->db->query("SELECT field_id, field_name FROM exp_category_fields WHERE site_id IN ('".implode("','", ee()->TMPL->site_ids)."')");

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

		$query = ee()->db->query($sql);

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
						$this->cat_array[] = $this->temp_array[$k];
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


    /****************************************************************
    * Field Searching
    *
    *   Generate the sql for the where clause to implement field
    *  searching.  Implements cross site field searching with a
    *  sloppy search, IE if there are any fields with the same name
    *  in any of the sites specified in the [ site="" ] parameter then
    *  all of those fields will be searched.
    *
    *****************************************************************/

	// ------------------------------------------------------------------------

	/**
	 * Generate the SQL where condition to handle the {exp:channel:entries}
	 * field search parameter -- search:field="".  There are two primary
	 * syntax possibilities:
	 * 	search:field="words|other words"
	 *
	 * and
	 * 	search:field="=words|other words"
	 * The first performs a LIKE "%words%" OR LIKE "%other words%".  The second
	 * one performs an ="words" OR ="other words".  Other possibilities are
	 * prepending "not" to negate the search:
	 *
	 * 	search:field="not words|other words"
	 * And using IS_EMPTY to indicate an empty field.
	 * 	search:field ="IS_EMPTY"
	 * 	search:field="not IS_EMPTY"
	 * 	search:field="=IS_EMPTY"
	 * 	search:field="=not IS_EMPTY"
	 * All of these may be combined:
	 *
	 * 	search:field="not IS_EMPTY|words"
	 */
	private function _generate_field_search_sql($search_fields, $site_ids)
	{
		$sql = '';

		ee()->load->model('channel_model');

		foreach ($search_fields as $field_name => $search_terms)
		{
			// Log empty terms to notify the user.
			if(empty($search_terms) || $search_terms === '=')
			{
				ee()->TMPL->log_item('WARNING: Field search parameter for field "' . $field_name . '" was empty.  If you wish to search for an empty field, use IS_EMPTY.');
				continue;
			}

			$fields_sql = '';
			$search_terms = trim($search_terms);

			// Note- if a 'contains' search goes through with an empty string
			// the resulting sql looks like: LIKE "%%"
			// While it doesn't throw an error, there's no point in adding the overhead.
			if ($search_terms == '' OR $search_terms == '=')
			{
				continue;
			}

			$sites = ($site_ids ? $site_ids : array(ee()->config->item('site_id')));
			foreach ($sites as $site_name => $site_id)
			{
				// If fields_sql isn't empty then this isn't a first
				// loop and we have terms that need to be ored together.
				if($fields_sql !== '') {
					$fields_sql .= ' OR ';
				}

				// We're goign to repeat the search on each site
				// so store the terms in a temp.  FIXME Necessary?
				$terms = $search_terms;
				if ( ! isset($this->cfields[$site_id][$field_name]))
				{
					continue;
				}

				$search_column_name = 'wd.field_id_'.$this->cfields[$site_id][$field_name];

				$fields_sql .= ee()->channel_model->field_search_sql($terms, $search_column_name, $site_id);

			} // foreach($sites as $site_id)
			if ( ! empty($fields_sql))
			{
				$sql .=  'AND (' . $fields_sql . ')';
			}
		}


		return $sql;
	}

	/**
	  *  Build SQL query
	  */
	public function build_sql_query($qstring = '')
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

		$this->pagination->dynamic_sql = TRUE;

		/**------
		/**  Is dynamic='off' set?
		/**------*/

		// If so, we'll override all dynamically set variables

		if (ee()->TMPL->fetch_param('dynamic') == 'no')
		{
			$dynamic = FALSE;
		}

		/**------
		/**  Do we allow dynamic POST variables to set parameters?
		/**------*/
		if (ee()->TMPL->fetch_param('dynamic_parameters') !== FALSE && (! empty($_POST) OR ! empty($_GET)))
		{
			foreach (explode('|', ee()->TMPL->fetch_param('dynamic_parameters')) as $var)
			{
				if ($this->EE->input->get_post($var) && in_array($var, $this->_dynamic_parameters))
				{
					ee()->TMPL->tagparams[$var] = $this->EE->input->get_post($var);
				}

				if (strncmp($var, 'search:', 7) == 0 && $this->EE->input->get_post($var))
				{
					ee()->TMPL->search_fields[substr($var, 7)] = $this->EE->input->get_post($var);
				}
			}
		}

		/**------
		/**  Parse the URL query string
		/**------*/

		$this->uristr = ee()->uri->uri_string;

		if ($qstring == '')
		{
			$qstring = $this->query_string;
		}

		$this->pagination->basepath = ee()->functions->create_url($this->uristr);

		if ($qstring == '')
		{
			if (ee()->TMPL->fetch_param('require_entry') == 'yes')
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

				if (($dynamic OR ee()->TMPL->fetch_param('paginate')) && preg_match("#^P(\d+)|/P(\d+)#", $qstring, $match))
				{
					$this->pagination->offset = (isset($match[2])) ? $match[2] : $match[1];

					$this->pagination->basepath = reduce_double_slashes(str_replace($match[0], '', $this->pagination->basepath));

					$this->uristr  = reduce_double_slashes(str_replace($match[0], '', $this->uristr));

					$qstring = trim_slashes(str_replace($match[0], '', $qstring));

					$page_marker = TRUE;
				}

				/** --------------------------------------
				/**  Parse category indicator
				/** --------------------------------------*/

				// Text version of the category

				if ($qstring != '' AND $this->reserved_cat_segment != '' AND in_array($this->reserved_cat_segment, explode("/", $qstring)) AND $dynamic AND ee()->TMPL->fetch_param('channel'))
				{
					$qstring = preg_replace("/(.*?)\/".preg_quote($this->reserved_cat_segment)."\//i", '', '/'.$qstring);

					$sql = "SELECT DISTINCT cat_group FROM exp_channels WHERE site_id IN ('".implode("','", ee()->TMPL->site_ids)."') AND ";

					$xsql = ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('channel'), 'channel_name');

					if (substr($xsql, 0, 3) == 'AND') $xsql = substr($xsql, 3);

					$sql .= ' '.$xsql;

					$query = ee()->db->query($sql);

					if ($query->num_rows() > 0)
					{
						$valid = 'y';
						$valid_cats = explode('|', $query->row('cat_group') );

						foreach($query->result_array() as $row)
						{
							if (ee()->TMPL->fetch_param('relaxed_categories') == 'yes')
							{
								$valid_cats = array_merge($valid_cats, explode('|', $row['cat_group']));
							}
							else
							{
								$valid_cats = array_intersect($valid_cats, explode('|', $row['cat_group']));
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

						$result = ee()->db->query("SELECT cat_id FROM exp_categories
							WHERE cat_url_title='".ee()->db->escape_str($cut_qstring)."'
							AND group_id IN ('".implode("','", $valid_cats)."')");

						if ($result->num_rows() == 1)
						{
							$qstring = str_replace($cut_qstring, 'C'.$result->row('cat_id') , $qstring);
							$cat_id = $result->row('cat_id');
						}
						else
						{
							// give it one more try using the whole $qstring
							$result = ee()->db->query("SELECT cat_id FROM exp_categories
								WHERE cat_url_title='".ee()->db->escape_str($qstring)."'
								AND group_id IN ('".implode("','", $valid_cats)."')");

							if ($result->num_rows() == 1)
							{
								$qstring = 'C'.$result->row('cat_id') ;
								$cat_id = $result->row('cat_id');
							}
						}
					}
				}

				// If we got here, category may be numeric
				if (empty($cat_id))
				{
					ee()->load->helper('segment');
					$cat_id = parse_category($this->query_string);
				}

				// If we were able to get a numeric category ID
				if (is_numeric($cat_id) AND $cat_id !== FALSE)
				{
					$this->cat_request = TRUE;
				}
				// parse_category did not return a numberic ID, blow away $cat_id
				else
				{
					$cat_id = FALSE;
				}


				/** --------------------------------------
				/**  Remove "N"
				/** --------------------------------------*/

				// The recent comments feature uses "N" as the URL indicator
				// It needs to be removed if presenst

				if (preg_match("#^N(\d+)|/N(\d+)#", $qstring, $match))
				{
					$this->uristr  = reduce_double_slashes(str_replace($match[0], '', $this->uristr));

					$qstring = trim_slashes(str_replace($match[0], '', $qstring));
				}

				/** --------------------------------------
				/**  Parse URL title
				/** --------------------------------------*/
				if (($cat_id == '' AND $year == '') OR ee()->TMPL->fetch_param('require_entry') == 'yes')
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
							$sql .= " AND exp_channel_titles.entry_id = '".ee()->db->escape_str($entry_id)."'";
						}
						else
						{
							$sql .= " AND exp_channel_titles.url_title = '".ee()->db->escape_str($qstring)."'";
						}

						$sql .= " AND exp_channels.site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";

						$query = ee()->db->query($sql);

						if ($query->row('count')  == 0)
						{
							if (ee()->TMPL->fetch_param('require_entry') == 'yes')
							{
								return '';
							}
						}
						elseif ($entry_id == '')
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

		if (ee()->TMPL->fetch_param('entry_id'))
		{
			$entry_id = ee()->TMPL->fetch_param('entry_id');
		}

		/**------
		/**  Only Entries with Pages
		/**------*/

		if (ee()->TMPL->fetch_param('show_pages') !== FALSE && in_array(ee()->TMPL->fetch_param('show_pages'), array('only', 'no')) && ($pages = ee()->config->item('site_pages')) !== FALSE)
		{
			$pages_uris = array();

			foreach ($pages as $data)
			{
				$pages_uris += $data['uris'];
			}

			if (count($pages_uris) > 0 OR ee()->TMPL->fetch_param('show_pages') == 'only')
			{
				// consider entry_id
				if (ee()->TMPL->fetch_param('entry_id') !== FALSE)
				{
					$not = FALSE;

					if (strncmp($entry_id, 'not', 3) == 0)
					{
						$not = TRUE;
						$entry_id = trim(substr($entry_id, 3));
					}

					$ids = explode('|', $entry_id);

					if (ee()->TMPL->fetch_param('show_pages') == 'only')
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
					$entry_id = ((ee()->TMPL->fetch_param('show_pages') == 'no') ? 'not ' : '').implode('|', array_flip($pages_uris));
				}

				//  No pages and show_pages only
				if ($entry_id == '' && ee()->TMPL->fetch_param('show_pages') == 'only')
				{
					$this->sql = '';
					return;
				}
			}
		}

		/**------
		/**  Assing the order variables
		/**------*/

		$order  = ee()->TMPL->fetch_param('orderby');
		$sort	= ee()->TMPL->fetch_param('sort');
		$sticky = ee()->TMPL->fetch_param('sticky');

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

		$base_orders = array('status', 'random', 'entry_id', 'date', 'entry_date', 'title', 'url_title', 'edit_date', 'comment_total', 'username', 'screen_name', 'most_recent_comment', 'expiration_date',
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

						if (isset(ee()->TMPL->site_ids[$order_parts[0]]) && isset($this->cfields[ee()->TMPL->site_ids[$order_parts[0]]][$order_parts[1]]))
						{
							$corder[$key] = $this->cfields[ee()->TMPL->site_ids[$order_parts[0]]][$order_parts[1]];
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
							if ( ! in_array($site_id, ee()->TMPL->site_ids))
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
		if (($fixed_order = ee()->TMPL->fetch_param('fixed_order')) === FALSE OR preg_match('/[^0-9\|]/', $fixed_order))
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

		$sql_b = (ee()->TMPL->fetch_param('category') OR ee()->TMPL->fetch_param('category_group') OR $cat_id != '' OR $order_array[0] == 'random') ? "DISTINCT(t.entry_id) " : "t.entry_id ";

		if ($this->pagination->field_pagination == TRUE)
		{
			$sql_b .= ",wd.* ";
		}

		$sql_c = "COUNT(t.entry_id) AS count ";

		$sql = "FROM exp_channel_titles AS t
				LEFT JOIN exp_channels ON t.channel_id = exp_channels.channel_id ";

		if ($this->pagination->field_pagination == TRUE)
		{
			$sql .= "LEFT JOIN exp_channel_data AS wd ON t.entry_id = wd.entry_id ";
		}
		elseif (in_array('custom_field', $order_array))
		{
			$sql .= "LEFT JOIN exp_channel_data AS wd ON t.entry_id = wd.entry_id ";
		}
		elseif ( ! empty(ee()->TMPL->search_fields))
		{
			$sql .= "LEFT JOIN exp_channel_data AS wd ON wd.entry_id = t.entry_id ";
		}

		$sql .= "LEFT JOIN exp_members AS m ON m.member_id = t.author_id ";


		if (ee()->TMPL->fetch_param('category') OR ee()->TMPL->fetch_param('category_group') OR ($cat_id != '' && $dynamic == TRUE))
		{
			/* --------------------------------
			/*  We use LEFT JOIN when there is a 'not' so that we get
			/*  entries that are not assigned to a category.
			/* --------------------------------*/

			if ((substr(ee()->TMPL->fetch_param('category_group'), 0, 3) == 'not' OR substr(ee()->TMPL->fetch_param('category'), 0, 3) == 'not') && ee()->TMPL->fetch_param('uncategorized_entries') !== 'no')
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

		$sql .= "WHERE t.entry_id !='' AND t.site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";

		/**------
		/**  We only select entries that have not expired
		/**------*/

		$timestamp = ee()->localize->now;

		if (ee()->TMPL->fetch_param('show_future_entries') != 'yes')
		{
			$sql .= " AND t.entry_date < ".$timestamp." ";
		}

		if (ee()->TMPL->fetch_param('show_expired') != 'yes')
		{
			$sql .= " AND (t.expiration_date = 0 OR t.expiration_date > ".$timestamp.") ";
		}

		/**------
		/**  Limit query by post ID for individual entries
		/**------*/

		if ($entry_id != '')
		{
			$sql .= ee()->functions->sql_andor_string($entry_id, 't.entry_id').' ';
		}

		/**------
		/**  Limit query by post url_title for individual entries
		/**------*/

		if ($url_title = ee()->TMPL->fetch_param('url_title'))
		{
			$sql .= ee()->functions->sql_andor_string($url_title, 't.url_title').' ';
		}

		/**------
		/**  Limit query by entry_id range
		/**------*/

		if ($entry_id_from = ee()->TMPL->fetch_param('entry_id_from'))
		{
			$sql .= "AND t.entry_id >= '$entry_id_from' ";
		}

		if ($entry_id_to = ee()->TMPL->fetch_param('entry_id_to'))
		{
			$sql .= "AND t.entry_id <= '$entry_id_to' ";
		}

		/**------
		/**  Exclude an individual entry
		/**------*/
		if ($not_entry_id = ee()->TMPL->fetch_param('not_entry_id'))
		{
			$sql .= ( ! is_numeric($not_entry_id))
					? "AND t.url_title != '{$not_entry_id}' "
					: "AND t.entry_id  != '{$not_entry_id}' ";
		}

		/**------
		/**  Limit to/exclude specific channels
		/**------*/

		if ($channel = ee()->TMPL->fetch_param('channel'))
		{
			$xql = "SELECT channel_id FROM exp_channels WHERE ";

			$str = ee()->functions->sql_andor_string($channel, 'channel_name');

			if (substr($str, 0, 3) == 'AND')
			{
				$str = substr($str, 3);
			}

			$xql .= $str;

			$query = ee()->db->query($xql);

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
		if (ee()->TMPL->fetch_param('start_on'))
		{
			$sql .= "AND t.entry_date >= '".ee()->localize->string_to_timestamp(ee()->TMPL->fetch_param('start_on'))."' ";
		}

		if (ee()->TMPL->fetch_param('stop_before'))
		{
			$sql .= "AND t.entry_date < '".ee()->localize->string_to_timestamp(ee()->TMPL->fetch_param('stop_before'))."' ";
		}

		/**-------------
		/**  Limit query by date contained in tag parameters
		/**-------------*/

		ee()->load->helper('date');

		if (ee()->TMPL->fetch_param('year') OR ee()->TMPL->fetch_param('month') OR ee()->TMPL->fetch_param('day'))
		{
			$year	= ( ! is_numeric(ee()->TMPL->fetch_param('year'))) 	? date('Y') : ee()->TMPL->fetch_param('year');
			$smonth	= ( ! is_numeric(ee()->TMPL->fetch_param('month')))	? '01' : ee()->TMPL->fetch_param('month');
			$emonth	= ( ! is_numeric(ee()->TMPL->fetch_param('month')))	? '12':  ee()->TMPL->fetch_param('month');
			$day	= ( ! is_numeric(ee()->TMPL->fetch_param('day')))		? '' : ee()->TMPL->fetch_param('day');

			if ($day != '' AND ! is_numeric(ee()->TMPL->fetch_param('month')))
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
				$eday = days_in_month($emonth, $year);
			}
			else
			{
				$sday = $day;
				$eday = $day;
			}

			$stime = ee()->localize->string_to_timestamp($year.'-'.$smonth.'-'.$sday.' 00:00');
			$etime = ee()->localize->string_to_timestamp($year.'-'.$emonth.'-'.$eday.' 23:59');

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
					$eday = days_in_month($month, $year);
				}
				else
				{
					$sday = $day;
					$eday = $day;
				}

				$stime = ee()->localize->string_to_timestamp($year.'-'.$month.'-'.$sday.' 00:00:00');
				$etime = ee()->localize->string_to_timestamp($year.'-'.$month.'-'.$eday.' 23:59:59');

				$sql .= " AND t.entry_date >= ".$stime." AND t.entry_date <= ".$etime." ";
			}
			else
			{
				$this->display_by = ee()->TMPL->fetch_param('display_by');

				$lim = ( ! is_numeric(ee()->TMPL->fetch_param('limit'))) ? '1' : ee()->TMPL->fetch_param('limit');

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

					if ($status = ee()->TMPL->fetch_param('status'))
					{
						$status = str_replace('Open',	'open',	$status);
						$status = str_replace('Closed', 'closed', $status);

						$sstr = ee()->functions->sql_andor_string($status, 't.status');

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

					$query = ee()->db->query($dql);

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

						$this->pagination->total_rows = count($distinct);

						$cur = ($this->pagination->offset == '') ? 0 : $this->pagination->offset;

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

					if ($status = ee()->TMPL->fetch_param('status'))
					{
						$status = str_replace('Open',	'open',	$status);
						$status = str_replace('Closed', 'closed', $status);

						$sstr = ee()->functions->sql_andor_string($status, 't.status');

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

					$query = ee()->db->query($dql);

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

						$this->pagination->total_rows = count($distinct);

						$cur = ($this->pagination->offset == '') ? 0 : $this->pagination->offset;

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

					$loc_offset = $this->_get_timezone_offset();

					if (ee()->TMPL->fetch_param('start_day') === 'Monday')
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

					if ($status = ee()->TMPL->fetch_param('status'))
					{
						$status = str_replace('Open',	'open',	$status);
						$status = str_replace('Closed', 'closed', $status);

						$sstr = ee()->functions->sql_andor_string($status, 't.status');

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

					$query = ee()->db->query($dql);

					$distinct = array();

					if ($query->num_rows() > 0)
					{
						/** ---------------------------------
						/*	 Sort Default is ASC for Display By Week so that entries are displayed
						/*	oldest to newest in the week, which is how you would expect.
						/*  ---------------------------------*/

						if (ee()->TMPL->fetch_param('sort') === FALSE)
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
						if (ee()->TMPL->fetch_param('week_sort') == 'desc')
						{
							$distinct = array_reverse($distinct);
						}
						*
						*/

						$this->pagination->total_rows = count($distinct);
						$cur = ($this->pagination->offset == '') ? 0 : $this->pagination->offset;

						/** ---------------------------------
						/*	 If no pagination, then the Current Week is shown by default with
						/*	 all pagination correctly set and ready to roll, if used.
						/*  ---------------------------------*/

						if (ee()->TMPL->fetch_param('show_current_week') === 'yes' && $this->pagination->offset == '')
						{
							if (ee()->TMPL->fetch_param('start_day') === 'Monday')
							{
								$query = ee()->db->query("SELECT DATE_FORMAT(CURDATE(), '%x%v') AS thisWeek");
							}
							else
							{
								$query = ee()->db->query("SELECT DATE_FORMAT(CURDATE(), '%X%V') AS thisWeek");
							}

							foreach($distinct as $key => $week)
							{
								if ($week == $query->row('thisWeek') )
								{
									$cur = $key;
									$this->pagination->offset = $key;
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
							$sql .= "AND (";

							foreach ($distinct as $val)
							{
								$sql_offset = $this->_get_timezone_offset();

								if (ee()->TMPL->fetch_param('start_day') === 'Monday')
								{
									$sql .= " DATE_FORMAT(FROM_UNIXTIME(entry_date + {$sql_offset}), '%x%v') = '".$val."' OR";
								}
								else
								{
									$sql .= " DATE_FORMAT(FROM_UNIXTIME(entry_date + {$sql_offset}), '%X%V') = '".$val."' OR";
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
			$sql .= "AND t.url_title = '".ee()->db->escape_str($qtitle)."' ";

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

		if (ee()->TMPL->fetch_param('category'))
		{
			if (stristr(ee()->TMPL->fetch_param('category'), '&'))
			{
				/** --------------------------------------
				/**  First, we find all entries with these categories
				/** --------------------------------------*/

				$for_sql = (substr(ee()->TMPL->fetch_param('category'), 0, 3) == 'not') ? trim(substr(ee()->TMPL->fetch_param('category'), 3)) : ee()->TMPL->fetch_param('category');

				$csql = "SELECT exp_category_posts.entry_id, exp_category_posts.cat_id ".
						$sql.
						ee()->functions->sql_andor_string(str_replace('&', '|', $for_sql), 'exp_categories.cat_id');

				//exit($csql);

				$results = ee()->db->query($csql);

				if ($results->num_rows() == 0)
				{
					return;
				}

				$type = 'IN';
				$categories	 = explode('&', ee()->TMPL->fetch_param('category'));
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
				if (substr(ee()->TMPL->fetch_param('category'), 0, 3) == 'not' && ee()->TMPL->fetch_param('uncategorized_entries') !== 'no')
				{
					$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category'), 'exp_categories.cat_id', '', TRUE)." ";
				}
				else
				{
					$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category'), 'exp_categories.cat_id')." ";
				}
			}
		}

		if (ee()->TMPL->fetch_param('category_group'))
		{
			if (substr(ee()->TMPL->fetch_param('category_group'), 0, 3) == 'not' && ee()->TMPL->fetch_param('uncategorized_entries') !== 'no')
			{
				$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category_group'), 'exp_categories.group_id', '', TRUE)." ";
			}
			else
			{
				$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category_group'), 'exp_categories.group_id')." ";
			}
		}

		if (ee()->TMPL->fetch_param('category') === FALSE && ee()->TMPL->fetch_param('category_group') === FALSE)
		{
			if ($cat_id != '' AND $dynamic)
			{
				$sql .= " AND exp_categories.cat_id = '".ee()->db->escape_str($cat_id)."' ";
			}
		}

		/**------
		/**  Limit to (or exclude) specific users
		/**------*/

		if ($username = ee()->TMPL->fetch_param('username'))
		{
			// Shows entries ONLY for currently logged in user

			if ($username == 'CURRENT_USER')
			{
				$sql .=  "AND m.member_id = '".ee()->session->userdata('member_id')."' ";
			}
			elseif ($username == 'NOT_CURRENT_USER')
			{
				$sql .=  "AND m.member_id != '".ee()->session->userdata('member_id')."' ";
			}
			else
			{
				$sql .= ee()->functions->sql_andor_string($username, 'm.username');
			}
		}

		/**------
        /**  Limit to (or exclude) specific author id(s)
        /**------*/

        if ($author_id = ee()->TMPL->fetch_param('author_id'))
		{
			// Shows entries ONLY for currently logged in user

			if ($author_id == 'CURRENT_USER')
			{
				$sql .=  "AND m.member_id = '".ee()->session->userdata('member_id')."' ";
			}
			elseif ($author_id == 'NOT_CURRENT_USER')
			{
				$sql .=  "AND m.member_id != '".ee()->session->userdata('member_id')."' ";
			}
			else
			{
				$sql .= ee()->functions->sql_andor_string($author_id, 'm.member_id');
			}
		}

		/**------
		/**  Add status declaration
		/**------*/

		if ($status = ee()->TMPL->fetch_param('status'))
		{
			$status = str_replace('Open',	'open',	$status);
			$status = str_replace('Closed', 'closed', $status);

			$sstr = ee()->functions->sql_andor_string($status, 't.status');

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

		if ($group_id = ee()->TMPL->fetch_param('group_id'))
		{
			$sql .= ee()->functions->sql_andor_string($group_id, 'm.group_id');
		}

    	/** ---------------------------------------
    	/**  Field searching
    	/** ---------------------------------------*/

		if ( ! empty(ee()->TMPL->search_fields))
		{
            $sql .= $this->_generate_field_search_sql(ee()->TMPL->search_fields, ee()->TMPL->site_ids);
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

						case 'status' :
							$end .= "t.status";
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

		if ($cat_id  != '' AND is_numeric(ee()->TMPL->fetch_param('cat_limit')))
		{
			$this->pagination->per_page = ee()->TMPL->fetch_param('cat_limit');
		}
		elseif ($month != '' AND is_numeric(ee()->TMPL->fetch_param('month_limit')))
		{
			$this->pagination->per_page = ee()->TMPL->fetch_param('month_limit');
		}
		else
		{
			$this->pagination->per_page  = ( ! is_numeric(ee()->TMPL->fetch_param('limit')))  ? $this->limit : ee()->TMPL->fetch_param('limit');
		}

		/**------
		/**  Is there an offset?
		/**------*/
		// We do this hear so we can use the offset into next, then later one as well
		$offset = ( ! ee()->TMPL->fetch_param('offset') OR ! is_numeric(ee()->TMPL->fetch_param('offset'))) ? '0' : ee()->TMPL->fetch_param('offset');

		//  Do we need pagination?
		// We'll run the query to find out

		if ($this->pagination->paginate == TRUE)
		{
			$this->pager_sql = '';

			if ($this->pagination->field_pagination == FALSE)
			{
				$this->pager_sql = $sql_a.$sql_b.$sql;
				$query = ee()->db->query($this->pager_sql);
				$total = $query->num_rows;
				$this->absolute_results = $total;

				// Adjust for offset
				if ($total >= $offset)
				{
					$total = $total - $offset;
				}

				$this->pagination->cfields = $this->cfields;

				$this->pagination->build($total, $this->sql);
			}
			else
			{
				$this->pager_sql = $sql_a.$sql_b.$sql;

				$query = ee()->db->query($this->pager_sql);

				$total = $query->num_rows;
				$this->absolute_results = $total;

				$this->pagination->cfields = $this->cfields;
				$this->pagination->build($total, $this->sql, $query);

				if (ee()->config->item('enable_sql_caching') == 'y')
				{
					$this->save_cache($this->pager_sql, 'pagination_query');
					$this->save_cache('1', 'field_pagination');
				}
			}

			if (ee()->config->item('enable_sql_caching') == 'y')
			{
				$this->save_cache($total, 'pagination_count');
			}
		}

		/**------
		/**  Add Limits to query
		/**------*/

		$sql .= $end;

		if ($this->pagination->paginate == FALSE)
		{
			$this->pagination->offset = 0;
		}

		// Adjust for offset
		$this->pagination->offset += $offset;

		if ($this->display_by == '')
		{
			if (($page_marker == FALSE AND $this->pagination->per_page != '') OR ($page_marker == TRUE AND $this->pagination->field_pagination != TRUE))
			{
				$sql .= ($this->pagination->offset == '') ? " LIMIT ".$offset.', '.$this->pagination->per_page : " LIMIT ".$this->pagination->offset.', '.$this->pagination->per_page;
			}
			elseif ($entry_id == '' AND $qtitle == '')
			{
				$sql .= ($this->pagination->offset == '') ? " LIMIT ".$this->limit : " LIMIT ".$this->pagination->offset.', '.$this->limit;
			}
		}
		else
		{
			if ($offset != 0)
			{
				$sql .= ($this->pagination->offset == '') ? " LIMIT ".$offset.', '.$this->pagination->per_page : " LIMIT ".$this->pagination->offset.', '.$this->pagination->per_page;
			}
		}

		/**------
		/**  Fetch the entry_id numbers
		/**------*/

		$query = ee()->db->query($sql_a.$sql_b.$sql);

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

		if (ee()->TMPL->fetch_param('category') OR ee()->TMPL->fetch_param('category_group') OR $cat_id != '')
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

		$this->sql .= " t.entry_id, t.channel_id, t.forum_topic_id, t.author_id, t.ip_address, t.title, t.url_title, t.status, t.view_count_one, t.view_count_two, t.view_count_three, t.view_count_four, t.allow_comments, t.comment_expiration_date, t.sticky, t.entry_date, t.year, t.month, t.day, t.edit_date, t.expiration_date, t.recent_comment_date, t.comment_total, t.site_id as entry_site_id,
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
		ee()->session->cache['channel']['entry_ids']	= array_keys($entries);

		unset($query);
		unset($entries);

		$this->sql = substr($this->sql, 0, -1).') ';

		// modify the ORDER BY if displaying by week
		if ($this->display_by == 'week' && isset($yearweek))
		{
			$weeksort = (ee()->TMPL->fetch_param('week_sort') == 'desc') ? 'DESC' : 'ASC';
			$end = str_replace('ORDER BY ', 'ORDER BY yearweek '.$weeksort.', ', $end);
		}

		$this->sql .= $end;
	}

	// ------------------------------------------------------------------------

	/**
	 * Gets timezone offset for use in SQL queries for the display_by parameter
	 *
	 * @return int
	 */
	private function _get_timezone_offset()
	{
		ee()->load->helper('date');

		$offset = 0;
		$timezones = timezones();
		$timezone = ee()->config->item('default_site_timezone');

		// Check legacy timezone formats
		if (isset($timezones[$timezone]))
		{
			$offset = $timezones[$timezone] * 3600;
		}
		// Otherwise, get the offset from DateTime
		else
		{
			$dt = new DateTime('now', new DateTimeZone($timezone));

			if ($dt)
			{
				$offset = $dt->getOffset();
			}
		}

		return $offset;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Parse channel entries
	  */
	public function parse_channel_entries()
	{
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
			if (ee()->extensions->active_hook('channel_entries_query_result') === TRUE)
			{
				$query_result = ee()->extensions->call('channel_entries_query_result', $this, $query_result);
				if (ee()->extensions->end_script === TRUE) return ee()->TMPL->tagdata;
			}
		//
		// -------------------------------------------

		ee()->load->library('channel_entries_parser');
		$parser = ee()->channel_entries_parser->create(ee()->TMPL->tagdata/*, $prefix=''*/);


		$disable = array();

		foreach ($this->enable as $k => $v)
		{
			if ($v === FALSE)
			{
				$disable[] = $k;
			}
		}

		// Relate entry_ids to their entries for quick lookup and then parse
		$entries = array();

		foreach ($query_result as $i => $row)
		{
			unset($query_result[$i]);
			$entries[$row['entry_id']] = $row;
		}

		$data = array(
			'entries'			=> $entries,
			'categories'		=> $this->categories,
			'absolute_results'	=> $this->absolute_results,
			'absolute_offset'	=> $this->pagination->offset
		);

		$config = array(
			'callbacks' => array(
				'entry_row_data'	 => array($this, 'callback_entry_row_data'),
				'tagdata_loop_start' => array($this, 'callback_tagdata_loop_start'),
				'tagdata_loop_end'	 => array($this, 'callback_tagdata_loop_end')
			),
			'disable' => $disable
		);

		ee()->session->set_cache('mod_channel', 'active', $this);
		$this->return_data = $parser->parse($this, $data, $config);


		unset($parser, $entries, $data);

		if (function_exists('gc_collect_cycles'))
		{
			gc_collect_cycles();
		}

		// Kill multi_field variable
		if (strpos($this->return_data, 'multi_field=') !== FALSE)
		{
			$this->return_data = preg_replace("/".LD."multi_field\=[\"'](.+?)[\"']".RD."/s", "", $this->return_data);
		}

		// Do we have backspacing?
		if ($back = ee()->TMPL->fetch_param('backspace'))
		{
			if (is_numeric($back))
			{
				$this->return_data = substr($this->return_data, 0, - $back);
			}
		}
	}

	// ------------------------------------------------------------------------

	public function callback_entry_row_data($tagdata, $row)
	{
		// -------------------------------------------
		// 'channel_entries_row' hook.
		//  - Take the entry data, do what you wish
		//  - added 1.6.7
		//
			if (ee()->extensions->active_hook('channel_entries_row') === TRUE)
			{
				$row = ee()->extensions->call('channel_entries_row', $this, $row);
				//if (ee()->extensions->end_script === TRUE) return $tagdata;
			}
		//
		// -------------------------------------------

		return $row;
	}

	// ------------------------------------------------------------------------

	public function callback_tagdata_loop_start($tagdata, $row)
	{
		// -------------------------------------------
		// 'channel_entries_tagdata' hook.
		//  - Take the entry data and tag data, do what you wish
		//
			if (ee()->extensions->active_hook('channel_entries_tagdata') === TRUE)
			{
				$tagdata = ee()->extensions->call('channel_entries_tagdata', $tagdata, $row, $this);
			//	if (ee()->extensions->end_script === TRUE) return $tagdata;
			}
		//
		// -------------------------------------------

		return $tagdata;
	}

	// ------------------------------------------------------------------------

	public function callback_tagdata_loop_end($tagdata, $row)
	{
		// -------------------------------------------
		// 'channel_entries_tagdata_end' hook.
		//  - Take the final results of an entry's parsing and do what you wish
		//
			if (ee()->extensions->active_hook('channel_entries_tagdata_end') === TRUE)
			{
				$tagdata = ee()->extensions->call('channel_entries_tagdata_end', $tagdata, $row, $this);
			//	if (ee()->extensions->end_script === TRUE) return $tagdata;
			}
		//
		// -------------------------------------------

		return $tagdata;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Channel Info Tag
	  */
	public function info()
	{
		if ( ! $channel_name = ee()->TMPL->fetch_param('channel'))
		{
			return '';
		}

		if (count(ee()->TMPL->var_single) == 0)
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
		$charset = ee()->config->item('charset');

		foreach (ee()->TMPL->var_single as $val)
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

		$sql .= " WHERE site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";

		if ($channel_name != '')
		{
			$sql .= " AND channel_name = '".ee()->db->escape_str($channel_name)."'";
		}

		$query = ee()->db->query($sql);

		if ($query->num_rows() != 1)
		{
			return '';
		}

		// We add in the channel_encoding
		$cond_vars = array_merge($query->row_array(), array('channel_encoding' => $charset));

		ee()->TMPL->tagdata = ee()->functions->prep_conditionals(ee()->TMPL->tagdata, $cond_vars);

		foreach ($query->row_array() as $key => $val)
		{
			ee()->TMPL->tagdata = str_replace(LD.$key.RD, $val, ee()->TMPL->tagdata);
		}

		ee()->TMPL->tagdata = str_replace(LD.'channel_encoding'.RD, $charset, ee()->TMPL->tagdata);

		return ee()->TMPL->tagdata;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Channel Name
	  */
	public function channel_name()
	{
		$channel_name = ee()->TMPL->fetch_param('channel');

		if (isset($this->channel_name[$channel_name]))
		{
			return $this->channel_name[$channel_name];
		}

		$sql = "SELECT channel_title FROM exp_channels ";

		$sql .= " WHERE site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";

		if ($channel_name != '')
		{
			$sql .= " AND channel_name = '".ee()->db->escape_str($channel_name)."'";
		}

		$query = ee()->db->query($sql);

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
	  *  Channel Categories
	  */
	public function categories()
	{
		// -------------------------------------------
		// 'channel_module_categories_start' hook.
		//  - Rewrite the displaying of categories, if you dare!
		//
			if (ee()->extensions->active_hook('channel_module_categories_start') === TRUE)
			{
				return ee()->extensions->call('channel_module_categories_start');
			}
		//
		// -------------------------------------------

		$sql = "SELECT DISTINCT cat_group, channel_id FROM exp_channels WHERE site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";

		if ($channel = ee()->TMPL->fetch_param('channel'))
		{
			$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('channel'), 'channel_name');
		}

		$cat_groups = ee()->db->query($sql);

		if ($cat_groups->num_rows() == 0)
		{
			return;
		}

		$channel_ids = array();
		$group_ids = array();
		foreach ($cat_groups->result_array() as $group)
		{
			$channel_ids[] = $group['channel_id'];
			$group_ids[] = $group['cat_group'];
		}

		// Combine the group IDs from multiple channels into a string
		$group_ids = implode('|', $group_ids);

		if ($category_group = ee()->TMPL->fetch_param('category_group'))
		{
			if (substr($category_group, 0, 4) == 'not ')
			{
				$x = explode('|', substr($category_group, 4));

				$groups = array_diff(explode('|', $group_ids), $x);
			}
			else
			{
				$x = explode('|', $category_group);

				$groups = array_intersect(explode('|', $group_ids), $x);
			}

			if (count($groups) == 0)
			{
				return '';
			}
			else
			{
				$group_ids = implode('|', $groups);
			}
		}

		$parent_only = (ee()->TMPL->fetch_param('parent_only') == 'yes') ? TRUE : FALSE;

		$path = array();

		if (preg_match_all("#".LD."path(=.+?)".RD."#", ee()->TMPL->tagdata, $matches))
		{
			for ($i = 0; $i < count($matches[0]); $i++)
			{
				if ( ! isset($path[$matches[0][$i]]))
				{
					$path[$matches[0][$i]] = ee()->functions->create_url(ee()->functions->extract_path($matches[1][$i]));
				}
			}
		}

		$str = '';
		$strict_empty = (ee()->TMPL->fetch_param('restrict_channel') == 'no') ? 'no' : 'yes';

		if (ee()->TMPL->fetch_param('style') == '' OR ee()->TMPL->fetch_param('style') == 'nested')
		{
			$this->category_tree(array(
				'group_id'		=> $group_ids,
				'channel_ids'	=> $channel_ids,
				'template'		=> ee()->TMPL->tagdata,
				'path'			=> $path,
				'channel_array'	=> '',
				'parent_only'	=> $parent_only,
				'show_empty'	=> ee()->TMPL->fetch_param('show_empty'),
				'strict_empty'	=> $strict_empty
			));


			if (count($this->category_list) > 0)
			{
				$i = 0;

				$id_name = ( ! ee()->TMPL->fetch_param('id')) ? 'nav_categories' : ee()->TMPL->fetch_param('id');
				$class_name = ( ! ee()->TMPL->fetch_param('class')) ? 'nav_categories' : ee()->TMPL->fetch_param('class');

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
				$query = ee()->db->query("SELECT field_id, field_name FROM exp_category_fields
									WHERE site_id IN ('".implode("','", ee()->TMPL->site_ids)."')
									AND group_id IN ('".str_replace('|', "','", ee()->db->escape_str($group_ids))."')");

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

			$show_empty = ee()->TMPL->fetch_param('show_empty');

			if ($show_empty == 'no')
			{
				// First we'll grab all category ID numbers

				$query = ee()->db->query("SELECT cat_id, parent_id
									 FROM exp_categories
									 WHERE group_id IN ('".str_replace('|', "','", ee()->db->escape_str($group_ids))."')
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
						WHERE group_id IN ('".str_replace('|', "','", ee()->db->escape_str($group_ids))."') ";


				$sql .= "AND exp_category_posts.cat_id IS NOT NULL ";

				if ($strict_empty == 'yes')
				{
					$sql .= "AND exp_channel_titles.channel_id IN ('".implode("','", $channel_ids)."') ";
				}
				else
				{
					$sql .= "AND exp_channel_titles.site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";
				}

		        if (($status = ee()->TMPL->fetch_param('status')) !== FALSE)
		        {
					$status = str_replace(array('Open', 'Closed'), array('open', 'closed'), $status);
		            $sql .= ee()->functions->sql_andor_string($status, 'exp_channel_titles.status');
		        }
		        else
		        {
		            $sql .= "AND exp_channel_titles.status != 'closed' ";
		        }

				/**------
				/**  We only select entries that have not expired
				/**------*/

				$timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

				if (ee()->TMPL->fetch_param('show_future_entries') != 'yes')
				{
					$sql .= " AND exp_channel_titles.entry_date < ".$timestamp." ";
				}

				if (ee()->TMPL->fetch_param('show_expired') != 'yes')
				{
					$sql .= " AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > ".$timestamp.") ";
				}

				if ($parent_only === TRUE)
				{
					$sql .= " AND parent_id = 0";
				}

				$sql .= " ORDER BY group_id, parent_id, cat_order";

				$query = ee()->db->query($sql);

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

				$query = ee()->db->query($sql);

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
						WHERE c.group_id IN ('".str_replace('|', "','", ee()->db->escape_str($group_ids))."') ";

				if ($parent_only === TRUE)
				{
					$sql .= " AND c.parent_id = 0";
				}

				$sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";

				$query = ee()->db->query($sql);

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

			if (ee()->TMPL->fetch_param('show') !== FALSE)
			{
				if (strncmp(ee()->TMPL->fetch_param('show'), 'not ', 4) == 0)
				{
					$not_these = explode('|', trim(substr(ee()->TMPL->fetch_param('show'), 3)));
				}
				else
				{
					$these = explode('|', trim(ee()->TMPL->fetch_param('show')));
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

			ee()->load->library('typography');
			ee()->typography->initialize(array(
				'convert_curly'	=> FALSE
			));

			$this->category_count = 0;
			$total_results = count($this->cat_array);

			// Get category ID from URL for {if active} conditional
			ee()->load->helper('segment');
			$active_cat = parse_category($this->query_string);

			foreach ($this->cat_array as $key => $val)
			{
				$chunk = ee()->TMPL->tagdata;

				ee()->load->library('file_field');
				$cat_image = ee()->file_field->parse_field($val[5]);

				$cat_vars = array(
					'category_name'			=> $val[3],
					'category_url_title'	=> $val[6],
					'category_description'	=> $val[4],
					'category_image'		=> $cat_image['url'],
					'category_id'			=> $val[0],
					'parent_id'				=> $val[1],
					'active'				=> ($active_cat == $val[0] || $active_cat == $val[6])
				);

				// add custom fields for conditionals prep

				foreach ($this->catfields as $v)
				{
					$cat_vars[$v['field_name']] = ( ! isset($val['field_id_'.$v['field_id']])) ? '' : $val['field_id_'.$v['field_id']];
				}

				$cat_vars['count'] = ++$this->category_count;
				$cat_vars['total_results'] = $total_results;

				$chunk = ee()->functions->prep_conditionals($chunk, $cat_vars);

				$chunk = str_replace(
					array(
						LD.'category_name'.RD,
						LD.'category_url_title'.RD,
						LD.'category_description'.RD,
						LD.'category_image'.RD,
						LD.'category_id'.RD,
						LD.'parent_id'.RD
					),
					array(
						ee()->functions->encode_ee_tags($val[3]),
						$val[6],
						ee()->functions->encode_ee_tags($val[4]),
						$cat_image['url'],
						$val[0],
						$val[1]
					),
					$chunk
				);

				foreach($path as $k => $v)
				{
					if ($this->use_category_names == TRUE)
					{
						$chunk = str_replace($k, reduce_double_slashes($v.'/'.$this->reserved_cat_segment.'/'.$val[6]), $chunk);
					}
					else
					{
						$chunk = str_replace($k, reduce_double_slashes($v.'/C'.$val[0]), $chunk);
					}
				}

				// parse custom fields
				foreach($this->catfields as $cv)
				{
					if (isset($val['field_id_'.$cv['field_id']]) AND $val['field_id_'.$cv['field_id']] != '')
					{
						$field_content = ee()->typography->parse_type(
							$val['field_id_'.$cv['field_id']],
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

			if (ee()->TMPL->fetch_param('backspace'))
			{
				$str = substr($str, 0, - ee()->TMPL->fetch_param('backspace'));
			}
		}

		if (strpos($str, '{filedir_') !== FALSE)
		{
			ee()->load->library('file_field');
			$str = ee()->file_field->parse_string($str);
		}

		return $str;
	}

	// ------------------------------------------------------------------------

	/**
	  *  Process Subcategories
	  */
	public function process_subcategories($parent_id)
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
	public function category_archive()
	{
		$sql = "SELECT DISTINCT cat_group, channel_id FROM exp_channels WHERE site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";

		if ($channel = ee()->TMPL->fetch_param('channel'))
		{
			$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('channel'), 'channel_name');
		}

		$cat_groups = ee()->db->query($sql);

		if ($cat_groups->num_rows() == 0)
		{
			return;
		}

		$group_ids = $cat_groups->row('cat_group');

		$channel_ids = array();
		$group_ids = array();
		foreach ($cat_groups->result_array() as $group)
		{
			$channel_ids[] = $group['channel_id'];
			$group_ids[] = $group['cat_group'];
		}

		// Combine the group IDs from multiple channels into a string
		$group_ids = implode('|', $group_ids);

		$sql = "SELECT exp_category_posts.cat_id, exp_channel_titles.entry_id, exp_channel_titles.title, exp_channel_titles.url_title, exp_channel_titles.entry_date
				FROM exp_channel_titles, exp_category_posts
				WHERE channel_id IN ('".implode("','", $channel_ids)."')
				AND exp_channel_titles.entry_id = exp_category_posts.entry_id ";

		$timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

		if (ee()->TMPL->fetch_param('show_future_entries') != 'yes')
		{
			$sql .= "AND exp_channel_titles.entry_date < ".$timestamp." ";
		}

		if (ee()->TMPL->fetch_param('show_expired') != 'yes')
		{
			$sql .= "AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > ".$timestamp.") ";
		}

		$sql .= "AND exp_channel_titles.status != 'closed' ";

		if ($status = ee()->TMPL->fetch_param('status'))
		{
			$status = str_replace('Open',	'open',	$status);
			$status = str_replace('Closed', 'closed', $status);

			$sql .= ee()->functions->sql_andor_string($status, 'exp_channel_titles.status');
		}
		else
		{
			$sql .= "AND exp_channel_titles.status = 'open' ";
		}

		if (ee()->TMPL->fetch_param('show') !== FALSE)
		{
			$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('show'), 'exp_category_posts.cat_id').' ';
		}


		$orderby  = ee()->TMPL->fetch_param('orderby');

		switch ($orderby)
		{
			case 'date':
				$sql .= "ORDER BY exp_channel_titles.entry_date";
				break;
			case 'expiration_date':
				$sql .= "ORDER BY exp_channel_titles.expiration_date";
				break;
			case 'title':
				$sql .= "ORDER BY exp_channel_titles.title";
				break;
			case 'comment_total':
				$sql .= "ORDER BY exp_channel_titles.entry_date";
				break;
			case 'most_recent_comment':
				$sql .= "ORDER BY exp_channel_titles.recent_comment_date desc, exp_channel_titles.entry_date";
				break;
			default:
				$sql .= "ORDER BY exp_channel_titles.title";
				break;
		}

		$sort = ee()->TMPL->fetch_param('sort');

		switch ($sort)
		{
			case 'asc':
				$sql .= " asc";
				break;
			case 'desc':
				$sql .= " desc";
				break;
			default:
				$sql .= " asc";
				break;
		}

		$result = ee()->db->query($sql);
		$channel_array = array();

		$parent_only = (ee()->TMPL->fetch_param('parent_only') == 'yes') ? TRUE : FALSE;

		// Gather patterns for parsing and replacement of variable pairs
		$categories_pattern = "/".LD."categories\s*".RD."(.*?)".LD.'\/'."categories\s*".RD."/s";
		$titles_pattern = "/".LD."entry_titles\s*".RD."(.*?)".LD.'\/'."entry_titles\s*".RD."/s";

		$cat_chunk  = (preg_match($categories_pattern, ee()->TMPL->tagdata, $match)) ? $match[1] : '';

		$c_path = array();

		if (preg_match_all("#".LD."path(=.+?)".RD."#", $cat_chunk, $matches))
		{
			for ($i = 0; $i < count($matches[0]); $i++)
			{
				$c_path[$matches[0][$i]] = ee()->functions->create_url(ee()->functions->extract_path($matches[1][$i]));
			}
		}

		$title_chunk = (preg_match($titles_pattern, ee()->TMPL->tagdata, $match)) ? $match[1] : '';

		$t_path = array();

		if (preg_match_all("#".LD."path(=.+?)".RD."#", $title_chunk, $matches))
		{
			for ($i = 0; $i < count($matches[0]); $i++)
			{
				$t_path[$matches[0][$i]] = ee()->functions->create_url(ee()->functions->extract_path($matches[1][$i]));
			}
		}

		$id_path = array();

		if (preg_match_all("#".LD."entry_id_path(=.+?)".RD."#", $title_chunk, $matches))
		{
			for ($i = 0; $i < count($matches[0]); $i++)
			{
				$id_path[$matches[0][$i]] = ee()->functions->create_url(ee()->functions->extract_path($matches[1][$i]));
			}
		}

		$entry_date = array();
		preg_match_all("/".LD."entry_date\s+format\s*=\s*(\042|\047)([^\\1]*?)\\1".RD."/s", $title_chunk, $matches);
		{
			$j = count($matches[0]);
			for ($i = 0; $i < $j; $i++)
			{
				$matches[0][$i] = str_replace(array(LD,RD), '', $matches[0][$i]);

				$entry_date[$matches[0][$i]] = $matches[2][$i];
			}
		}

		$return_data = '';

		if (ee()->TMPL->fetch_param('style') == '' OR ee()->TMPL->fetch_param('style') == 'nested')
		{
			if ($result->num_rows() > 0 && $title_chunk != '')
			{
				$i = 0;

				foreach($result->result_array() as $row)
				{
					$chunk = "<li>".str_replace(LD.'category_name'.RD, '', $title_chunk)."</li>";

					foreach($t_path as $tkey => $tval)
					{
						$chunk = str_replace($tkey, reduce_double_slashes($tval.'/'.$row['url_title']), $chunk);
					}

					foreach($id_path as $tkey => $tval)
					{
						$chunk = str_replace($tkey, reduce_double_slashes($tval.'/'.$row['entry_id']), $chunk);
					}

					foreach(ee()->TMPL->var_single as $key => $val)
					{
						if (isset($entry_date[$key]))
						{
							$val = str_replace($entry_date[$key], ee()->localize->format_date($entry_date[$key], $row['entry_date']), $val);
							$chunk = ee()->TMPL->swap_var_single($key, $val, $chunk);
						}

						if ($key == 'entry_id')
						{
							$chunk = ee()->TMPL->swap_var_single($key, $row['entry_id'], $chunk);
						}

						if ($key == 'url_title')
						{
							$chunk = ee()->TMPL->swap_var_single($key, $row['url_title'], $chunk);
						}
					}

					$channel_array[$i.'_'.$row['cat_id']] = str_replace(LD.'title'.RD, $row['title'], $chunk);
					$i++;
				}
			}

			$this->category_tree(array(
				'group_id'		=> $group_ids,
				'channel_ids'	=> $channel_ids,
				'path'			=> $c_path,
				'template'		=> $cat_chunk,
				'channel_array' => $channel_array,
				'parent_only'	=> $parent_only,
				'show_empty'	=> ee()->TMPL->fetch_param('show_empty'),
				'strict_empty'	=> 'yes'
			));

			if (count($this->category_list) > 0)
			{
				$id_name = (ee()->TMPL->fetch_param('id') === FALSE) ? 'nav_cat_archive' : ee()->TMPL->fetch_param('id');
				$class_name = (ee()->TMPL->fetch_param('class') === FALSE) ? 'nav_cat_archive' : ee()->TMPL->fetch_param('class');

				$this->category_list[0] = '<ul id="'.$id_name.'" class="'.$class_name.'">'."\n";

				foreach ($this->category_list as $val)
				{
					$return_data .= $val;
				}
			}
		}
		else
		{
			// fetch category field names and id's

			if ($this->enable['category_fields'] === TRUE)
			{
				$query = ee()->db->query("SELECT field_id, field_name FROM exp_category_fields
									WHERE site_id IN ('".implode("','", ee()->TMPL->site_ids)."')
									AND group_id IN ('".str_replace('|', "','", ee()->db->escape_str($group_ids))."')");

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

			if (ee()->TMPL->fetch_param('show_empty') != 'no' AND count($channel_ids))
			{
				$sql .= ", exp_category_posts ";
			}

			$sql .= ") {$field_sqlb}";

			if (ee()->TMPL->fetch_param('show_empty') == 'no')
			{
				$sql .= " LEFT JOIN exp_category_posts ON c.cat_id = exp_category_posts.cat_id ";

				if (count($channel_ids))
				{
					$sql .= " LEFT JOIN exp_channel_titles ON exp_category_posts.entry_id = exp_channel_titles.entry_id ";
				}
			}

			$sql .= " WHERE c.group_id IN ('".str_replace('|', "','", ee()->db->escape_str($group_ids))."') ";

			if (ee()->TMPL->fetch_param('show_empty') == 'no')
			{
				if (count($channel_ids))
				{
					$sql .= "AND exp_channel_titles.channel_id IN ('".implode("','", $channel_ids)."') ";
				}
				else
				{
					$sql .= " AND exp_channel_titles.site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";
				}

				if ($status = ee()->TMPL->fetch_param('status'))
				{
					$status = str_replace('Open',	'open',	$status);
					$status = str_replace('Closed', 'closed', $status);

					$sql .= ee()->functions->sql_andor_string($status, 'exp_channel_titles.status');
				}
				else
				{
					$sql .= "AND exp_channel_titles.status = 'open' ";
				}

				if (ee()->TMPL->fetch_param('show_empty') == 'no')
				{
					$sql .= "AND exp_category_posts.cat_id IS NOT NULL ";
				}
			}

			if (ee()->TMPL->fetch_param('show') !== FALSE)
			{
				$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('show'), 'c.cat_id').' ';
			}

			if ($parent_only == TRUE)
			{
				$sql .= " AND c.parent_id = 0";
			}

			$sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";
		 	$query = ee()->db->query($sql);

			if ($query->num_rows() > 0)
			{
				ee()->load->library('typography');
				ee()->typography->initialize(array(
								'convert_curly'	=> FALSE)
								);

				$used = array();

				// Get category ID from URL for {if active} conditional
				ee()->load->helper('segment');
				$active_cat = parse_category($this->query_string);

				foreach($query->result_array() as $row)
				{
					// We'll concatenate parsed category and title chunks here for
					// replacing in the tagdata later
					$categories_parsed = '';
					$titles_parsed = '';

					if ( ! isset($used[$row['cat_name']]))
					{
						$chunk = $cat_chunk;

						ee()->load->library('file_field');
						$cat_image = ee()->file_field->parse_field($row['cat_image']);

						$cat_vars = array('category_name'			=> $row['cat_name'],
										  'category_url_title'		=> $row['cat_url_title'],
										  'category_description'	=> $row['cat_description'],
										  'category_image'			=> $cat_image['url'],
										  'category_id'				=> $row['cat_id'],
										  'parent_id'				=> $row['parent_id'],
										  'active'					=> ($active_cat == $row['cat_id'] ||
																		$active_cat == $row['cat_url_title'])
										);

						foreach ($this->catfields as $v)
						{
							$cat_vars[$v['field_name']] = ( ! isset($row['field_id_'.$v['field_id']])) ? '' : $row['field_id_'.$v['field_id']];
						}

						$chunk = ee()->functions->prep_conditionals($chunk, $cat_vars);

						$chunk = str_replace( array(LD.'category_id'.RD,
													LD.'category_name'.RD,
													LD.'category_url_title'.RD,
													LD.'category_image'.RD,
													LD.'category_description'.RD,
													LD.'parent_id'.RD),
											  array($row['cat_id'],
											  		ee()->functions->encode_ee_tags($row['cat_name']),
													$row['cat_url_title'],
											  		$cat_image['url'],
											  		ee()->functions->encode_ee_tags($row['cat_description']),
													$row['parent_id']),
											  $chunk);

						foreach($c_path as $ckey => $cval)
						{
							$cat_seg = ($this->use_category_names == TRUE) ? $this->reserved_cat_segment.'/'.$row['cat_url_title'] : 'C'.$row['cat_id'];
							$chunk = str_replace($ckey, reduce_double_slashes($cval.'/'.$cat_seg), $chunk);
						}

						// parse custom fields

						foreach($this->catfields as $cfv)
						{
							if (isset($row['field_id_'.$cfv['field_id']]) AND $row['field_id_'.$cfv['field_id']] != '')
							{
								$field_content = ee()->typography->parse_type($row['field_id_'.$cfv['field_id']],
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

						// Check to see if we need to parse {filedir_n}
						if (strpos($chunk, '{filedir_') !== FALSE)
						{
							ee()->load->library('file_field');
							$chunk = ee()->file_field->parse_string($chunk);
						}

						$categories_parsed .= $chunk;
						$used[$row['cat_name']] = TRUE;
					}

					foreach($result->result_array() as $trow)
					{
						if ($trow['cat_id'] == $row['cat_id'])
						{
							$chunk = str_replace(array(LD.'title'.RD, LD.'category_name'.RD),
												 array($trow['title'],$row['cat_name']),
												 $title_chunk);

							foreach($t_path as $tkey => $tval)
							{
								$chunk = str_replace($tkey, reduce_double_slashes($tval.'/'.$trow['url_title']), $chunk);
							}

							foreach($id_path as $tkey => $tval)
							{
								$chunk = str_replace($tkey, reduce_double_slashes($tval.'/'.$trow['entry_id']), $chunk);
							}

							foreach(ee()->TMPL->var_single as $key => $val)
							{
								if (isset($entry_date[$key]))
								{
									$val = str_replace($entry_date[$key], ee()->localize->format_date($entry_date[$key], $trow['entry_date']), $val);

									$chunk = ee()->TMPL->swap_var_single($key, $val, $chunk);
								}

								if ($key == 'entry_id')
								{
									$chunk = ee()->TMPL->swap_var_single($key, $trow['entry_id'], $chunk);
								}

								if ($key == 'url_title')
								{
									$chunk = ee()->TMPL->swap_var_single($key, $trow['url_title'], $chunk);
								}
							}

							$titles_parsed .= $chunk;
						}
					}

					// Parse row then concatenate on $return_data
					$parsed_row = preg_replace($categories_pattern, $categories_parsed, ee()->TMPL->tagdata);
					$parsed_row = preg_replace($titles_pattern, $titles_parsed, $parsed_row);

					$return_data .= $parsed_row;
				}

				if (ee()->TMPL->fetch_param('backspace'))
				{
					$return_data = substr($return_data, 0, - ee()->TMPL->fetch_param('backspace'));
				}
			}
		}

		return $return_data;
	}

	// ------------------------------------------------------------------------

	/** --------------------------------
	/**  Locate category parent
	/** --------------------------------*/
	// This little recursive gem will travel up the
	// category tree until it finds the category ID
	// number of any parents.  It's used by the function
	// below
	public function find_parent($parent, $all)
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
	public function category_tree($cdata = array())
	{
		$default = array('group_id', 'channel_ids', 'path', 'template', 'depth', 'channel_array', 'parent_only', 'show_empty', 'strict_empty');

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
			$query = ee()->db->query("SELECT field_id, field_name
								FROM exp_category_fields
								WHERE site_id IN ('".implode("','", ee()->TMPL->site_ids)."')
								AND group_id IN ('".str_replace('|', "','", ee()->db->escape_str($group_id))."')");

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

			$query = ee()->db->query("SELECT cat_id, parent_id FROM exp_categories
								 WHERE group_id IN ('".str_replace('|', "','", ee()->db->escape_str($group_id))."')
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

			$sql .= "WHERE group_id IN ('".str_replace('|', "','", ee()->db->escape_str($group_id))."') ";

			$sql .= "AND exp_category_posts.cat_id IS NOT NULL ";

			if (count($channel_ids) && $strict_empty == 'yes')
			{
				$sql .= "AND exp_channel_titles.channel_id IN ('".implode("','", $channel_ids)."') ";
			}
			else
			{
				$sql .= "AND exp_channel_titles.site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";
			}

			if (($status = ee()->TMPL->fetch_param('status')) !== FALSE)
	        {
				$status = str_replace(array('Open', 'Closed'), array('open', 'closed'), $status);
	            $sql .= ee()->functions->sql_andor_string($status, 'exp_channel_titles.status');
	        }
	        else
	        {
	            $sql .= "AND exp_channel_titles.status != 'closed' ";
	        }

			/**------
			/**  We only select entries that have not expired
			/**------*/

			$timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

			if (ee()->TMPL->fetch_param('show_future_entries') != 'yes')
			{
				$sql .= " AND exp_channel_titles.entry_date < ".$timestamp." ";
			}

			if (ee()->TMPL->fetch_param('show_expired') != 'yes')
			{
				$sql .= " AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > ".$timestamp.") ";
			}

			if ($parent_only === TRUE)
			{
				$sql .= " AND parent_id = 0";
			}

			$sql .= " ORDER BY group_id, parent_id, cat_order";

			$query = ee()->db->query($sql);

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

			$query = ee()->db->query($sql);

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
					WHERE c.group_id IN ('".str_replace('|', "','", ee()->db->escape_str($group_id))."') ";

			if ($parent_only === TRUE)
			{
				$sql .= " AND c.parent_id = 0";
			}

			$sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";

			$query = ee()->db->query($sql);

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

		if (ee()->TMPL->fetch_param('show') !== FALSE)
		{
			if (strncmp(ee()->TMPL->fetch_param('show'), 'not ', 4) == 0)
			{
				$not_these = explode('|', trim(substr(ee()->TMPL->fetch_param('show'), 3)));
			}
			else
			{
				$these = explode('|', trim(ee()->TMPL->fetch_param('show')));
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

		ee()->load->library('typography');
		ee()->typography->initialize(array(
				'convert_curly'	=> FALSE)
				);

		$this->category_count = 0;
		$total_results = count($this->cat_array);

		// Get category ID from URL for {if active} conditional
		ee()->load->helper('segment');
		$active_cat = parse_category($this->query_string);

		$this->category_subtree(array(
			'parent_id'		=> '0',
			'path'			=> $path,
			'template'		=> $template,
			'channel_array' 	=> $channel_array
		));
	}

	// ------------------------------------------------------------------------

	/**
	  *  Category Sub-tree
	  */
	public function category_subtree($cdata = array())
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

		// Get category ID from URL for {if active} conditional
		ee()->load->helper('segment');
		$active_cat = parse_category($this->query_string);

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

				ee()->load->library('file_field');
				$cat_image = ee()->file_field->parse_field($val[2]);

				$cat_vars = array('category_name'			=> $val[1],
								  'category_url_title'		=> $val[4],
								  'category_description'	=> $val[3],
								  'category_image'			=> $cat_image['url'],
								  'category_id'				=> $key,
								  'parent_id'				=> $val[0],
								  'active'					=> ($active_cat == $key || $active_cat == $val[4]));

				// add custom fields for conditionals prep
				foreach ($this->catfields as $v)
				{
					$cat_vars[$v['field_name']] = ( ! isset($val['field_id_'.$v['field_id']])) ? '' : $val['field_id_'.$v['field_id']];
				}

				$cat_vars['count'] = ++$this->category_count;
				$cat_vars['total_results'] = $total_results;

				$chunk = ee()->functions->prep_conditionals($chunk, $cat_vars);

				$chunk = str_replace( array(LD.'category_id'.RD,
											LD.'category_name'.RD,
											LD.'category_url_title'.RD,
											LD.'category_image'.RD,
											LD.'category_description'.RD,
											LD.'parent_id'.RD),
									  array($key,
									  		ee()->functions->encode_ee_tags($val[1]),
											$val[4],
									  		$cat_image['url'],
									  		ee()->functions->encode_ee_tags($val[3]),
											$val[0]),
									  $chunk);

				foreach($path as $pkey => $pval)
				{
					if ($this->use_category_names == TRUE)
					{
						$chunk = str_replace($pkey, reduce_double_slashes($pval.'/'.$this->reserved_cat_segment.'/'.$val[4]), $chunk);
					}
					else
					{
						$chunk = str_replace($pkey, reduce_double_slashes($pval.'/C'.$key), $chunk);
					}
				}

				// parse custom fields
				foreach($this->catfields as $ccv)
				{
					if (isset($val['field_id_'.$ccv['field_id']]) AND $val['field_id_'.$ccv['field_id']] != '')
					{
						$field_content = ee()->typography->parse_type($val['field_id_'.$ccv['field_id']],
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
	public function close_ul($parent_id, $depth = 0)
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
	public function category_heading()
	{
		if ($this->query_string == '')
		{
			return;
		}

		// -------------------------------------------
		// 'channel_module_category_heading_start' hook.
		//  - Rewrite the displaying of category headings, if you dare!
		//
			if (ee()->extensions->active_hook('channel_module_category_heading_start') === TRUE)
			{
				ee()->TMPL->tagdata = ee()->extensions->call('channel_module_category_heading_start');
				if (ee()->extensions->end_script === TRUE) return ee()->TMPL->tagdata;
			}
		//
		// -------------------------------------------

		$qstring = $this->query_string;

		/** --------------------------------------
		/**  Remove page number
		/** --------------------------------------*/

		if (preg_match("#/P\d+#", $qstring, $match))
		{
			$qstring = reduce_double_slashes(str_replace($match[0], '', $qstring));
		}

		/** --------------------------------------
		/**  Remove "N"
		/** --------------------------------------*/
		if (preg_match("#/N(\d+)#", $qstring, $match))
		{
			$qstring = reduce_double_slashes(str_replace($match[0], '', $qstring));
		}

		// Is the category being specified by name?

		if ($qstring != '' AND $this->reserved_cat_segment != '' AND in_array($this->reserved_cat_segment, explode("/", $qstring)) AND ee()->TMPL->fetch_param('channel'))
		{
			$qstring = preg_replace("/(.*?)\/".preg_quote($this->reserved_cat_segment)."\//i", '', '/'.$qstring);

			$sql = "SELECT DISTINCT cat_group FROM exp_channels WHERE site_id IN ('".implode("','", ee()->TMPL->site_ids)."') AND ";

			$xsql = ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('channel'), 'channel_name');

			if (substr($xsql, 0, 3) == 'AND') $xsql = substr($xsql, 3);

			$sql .= ' '.$xsql;

			$query = ee()->db->query($sql);

			if ($query->num_rows() > 0)
			{
				$valid = 'y';
				$valid_cats  = explode('|', $query->row('cat_group') );

				foreach($query->result_array() as $row)
				{
					if (ee()->TMPL->fetch_param('relaxed_categories') == 'yes')
					{
						$valid_cats = array_merge($valid_cats, explode('|', $row['cat_group']));
					}
					else
					{
						$valid_cats = array_intersect($valid_cats, explode('|', $row['cat_group']));
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

				$result = ee()->db->query("SELECT cat_id FROM exp_categories
									  WHERE cat_url_title='".ee()->db->escape_str($cut_qstring)."'
									  AND group_id IN ('".implode("','", $valid_cats)."')");

				if ($result->num_rows() == 1)
				{
					$qstring = str_replace($cut_qstring, 'C'.$result->row('cat_id') , $qstring);
				}
				else
				{
					// give it one more try using the whole $qstring
					$result = ee()->db->query("SELECT cat_id FROM exp_categories
										  WHERE cat_url_title='".ee()->db->escape_str($qstring)."'
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
			return ee()->TMPL->no_results();
		}

		// fetch category field names and id's

		if ($this->enable['category_fields'] === TRUE)
		{
			// limit to correct category group
			$gquery = ee()->db->query("SELECT group_id FROM exp_categories WHERE cat_id = '".ee()->db->escape_str($match[2])."'");

			if ($gquery->num_rows() == 0)
			{
				return ee()->TMPL->no_results();
			}

			$query = ee()->db->query("SELECT field_id, field_name
								FROM exp_category_fields
								WHERE site_id IN ('".implode("','", ee()->TMPL->site_ids)."')
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

		$query = ee()->db->query("SELECT c.cat_name, c.parent_id, c.cat_url_title, c.cat_description, c.cat_image {$field_sqla}
							FROM exp_categories AS c
							{$field_sqlb}
							WHERE c.cat_id = '".ee()->db->escape_str($match[2])."'");

		if ($query->num_rows() == 0)
		{
			return ee()->TMPL->no_results();
		}

		$row = $query->row_array();

		ee()->load->library('file_field');
		$cat_image = ee()->file_field->parse_field($query->row('cat_image'));

		$cat_vars = array('category_name'			=> $query->row('cat_name'),
						  'category_description'	=> $query->row('cat_description'),
						  'category_image'			=> $cat_image['url'],
						  'category_id'				=> $match[2],
						  'parent_id'				=> $query->row('parent_id'));

		// add custom fields for conditionals prep
		foreach ($this->catfields as $v)
		{
			$cat_vars[$v['field_name']] = ($query->row('field_id_'.$v['field_id'])) ? $query->row('field_id_'.$v['field_id']) : '';
		}

		ee()->TMPL->tagdata = ee()->functions->prep_conditionals(ee()->TMPL->tagdata, $cat_vars);

		ee()->TMPL->tagdata = str_replace( array(LD.'category_id'.RD,
											LD.'category_name'.RD,
											LD.'category_url_title'.RD,
											LD.'category_image'.RD,
											LD.'category_description'.RD,
											LD.'parent_id'.RD),
							 	 	  array($match[2],
											ee()->functions->encode_ee_tags($query->row('cat_name')),
											$query->row('cat_url_title'),
											$cat_image['url'],
											ee()->functions->encode_ee_tags($query->row('cat_description')),
											$query->row('parent_id')),
							  		  ee()->TMPL->tagdata);

		// Check to see if we need to parse {filedir_n}
		if (strpos(ee()->TMPL->tagdata, '{filedir_') !== FALSE)
		{
			ee()->load->library('file_field');
			ee()->TMPL->tagdata = ee()->file_field->parse_string(ee()->TMPL->tagdata);
		}

		// parse custom fields
		ee()->load->library('typography');
		ee()->typography->initialize(array(
				'convert_curly'	=> FALSE)
				);

		// parse custom fields
		foreach($this->catfields as $ccv)
		{
			if ($query->row('field_id_'.$ccv['field_id']) AND $query->row('field_id_'.$ccv['field_id']) != '')
			{
				$field_content = ee()->typography->parse_type($query->row('field_id_'.$ccv['field_id']),
															array(
																  'text_format'		=> $query->row('field_ft_'.$ccv['field_id']),
																  'html_format'		=> $query->row('field_html_formatting'),
																  'auto_links'		=> 'n',
																  'allow_img_url'	=> 'y'
																)
														);
				ee()->TMPL->tagdata = str_replace(LD.$ccv['field_name'].RD, $field_content, ee()->TMPL->tagdata);
			}
			else
			{
				// garbage collection
				ee()->TMPL->tagdata = str_replace(LD.$ccv['field_name'].RD, '', ee()->TMPL->tagdata);
			}
		}

		return ee()->TMPL->tagdata;
	}

	// ------------------------------------------------------------------------

	/** ---------------------------------------
	/**  Next / Prev entry tags
	/** ---------------------------------------*/

	public function next_entry()
	{
		return $this->next_prev_entry('next');
	}

	public function prev_entry()
	{
		return $this->next_prev_entry('prev');
	}

	public function next_prev_entry($which = 'next')
	{
		$which = ($which != 'next' AND $which != 'prev') ? 'next' : $which;
		$sort = ($which == 'next') ? 'ASC' : 'DESC';

		// Don't repeat our work if we already know the single entry page details
		if ( ! isset(ee()->session->cache['channel']['single_entry_id']) OR ! isset(ee()->session->cache['channel']['single_entry_date']))
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
				$qstring = reduce_double_slashes(str_replace($match[0], '', $qstring));
			}

			/** --------------------------------------
			/**  Remove "N"
			/** --------------------------------------*/

			if (preg_match("#/N(\d+)#", $qstring, $match))
			{
				$qstring = reduce_double_slashes(str_replace($match[0], '', $qstring));
			}

			if (strpos($qstring, '/') !== FALSE)
			{
				$qstring = substr($qstring, 0, strpos($qstring, '/'));
			}

			/** ---------------------------------------
			/**  Query for the entry id and date
			/** ---------------------------------------*/

			ee()->db->select('t.entry_id, t.entry_date');
			ee()->db->from('channel_titles AS t');
			ee()->db->join('channels AS w', 'w.channel_id = t.channel_id', 'left');

			// url_title parameter
			if ($url_title = ee()->TMPL->fetch_param('url_title'))
			{
				ee()->db->where('t.url_title', $url_title);
			}
			else
			{
				// Found entry ID in query string
				if (is_numeric($qstring))
				{
					ee()->db->where('t.entry_id', $qstring);
				}
				// Found URL title in query string
				else
				{
					ee()->db->where('t.url_title', $qstring);
				}
			}

			ee()->db->where_in('w.site_id', ee()->TMPL->site_ids);

			// Channel paremter
			if ($channel_name = ee()->TMPL->fetch_param('channel'))
			{
				ee()->functions->ar_andor_string($channel_name, 'channel_name', 'w');
			}

			$query = ee()->db->get();

			// no results or more than one result?  Buh bye!
			if ($query->num_rows() != 1)
			{
				ee()->TMPL->log_item('Channel Next/Prev Entry tag error: Could not resolve single entry page id.');
				return;
			}

			$row = $query->row_array();

			ee()->session->cache['channel']['single_entry_id'] = $row['entry_id'];
			ee()->session->cache['channel']['single_entry_date'] = $row['entry_date'];
		}

		/** ---------------------------------------
		/**  Find the next / prev entry
		/** ---------------------------------------*/

		$ids = '';

		// Get included or excluded entry ids from entry_id parameter
		if (($entry_id = ee()->TMPL->fetch_param('entry_id')) != FALSE)
		{
			$ids = ee()->functions->sql_andor_string($entry_id, 't.entry_id').' ';
		}

		$sql = 'SELECT t.entry_id, t.title, t.url_title, w.channel_name, w.channel_title, w.comment_url, w.channel_url
				FROM (exp_channel_titles AS t)
				LEFT JOIN exp_channels AS w ON w.channel_id = t.channel_id ';

		/* --------------------------------
		/*  We use LEFT JOIN when there is a 'not' so that we get
		/*  entries that are not assigned to a category.
		/* --------------------------------*/

		if ((substr(ee()->TMPL->fetch_param('category_group'), 0, 3) == 'not' OR substr(ee()->TMPL->fetch_param('category'), 0, 3) == 'not') && ee()->TMPL->fetch_param('uncategorized_entries') !== 'no')
		{
			$sql .= 'LEFT JOIN exp_category_posts ON t.entry_id = exp_category_posts.entry_id
					 LEFT JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ';
		}
		elseif(ee()->TMPL->fetch_param('category_group') OR ee()->TMPL->fetch_param('category'))
		{
			$sql .= 'INNER JOIN exp_category_posts ON t.entry_id = exp_category_posts.entry_id
					 INNER JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ';
		}

		$sql .= ' WHERE t.entry_id != '.ee()->session->cache['channel']['single_entry_id'].' '.$ids;

		$timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

	    if (ee()->TMPL->fetch_param('show_future_entries') != 'yes')
	    {
	    	$sql .= " AND t.entry_date < {$timestamp} ";
	    }

		// constrain by date depending on whether this is a 'next' or 'prev' tag
		if ($which == 'next')
		{
			$sql .= ' AND t.entry_date >= '.ee()->session->cache['channel']['single_entry_date'].' ';
			$sql .= ' AND IF (t.entry_date = '.ee()->session->cache['channel']['single_entry_date'].', t.entry_id > '.ee()->session->cache['channel']['single_entry_id'].', 1) ';
		}
		else
		{
			$sql .= ' AND t.entry_date <= '.ee()->session->cache['channel']['single_entry_date'].' ';
			$sql .= ' AND IF (t.entry_date = '.ee()->session->cache['channel']['single_entry_date'].', t.entry_id < '.ee()->session->cache['channel']['single_entry_id'].', 1) ';
		}

	    if (ee()->TMPL->fetch_param('show_expired') != 'yes')
	    {
			$sql .= " AND (t.expiration_date = 0 OR t.expiration_date > {$timestamp}) ";
	    }

		$sql .= " AND w.site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";

		if ($channel_name = ee()->TMPL->fetch_param('channel'))
		{
			$sql .= ee()->functions->sql_andor_string($channel_name, 'channel_name', 'w')." ";
		}

		if ($status = ee()->TMPL->fetch_param('status'))
	    {
			$status = str_replace('Open',   'open',   $status);
			$status = str_replace('Closed', 'closed', $status);

			$sql .= ee()->functions->sql_andor_string($status, 't.status')." ";
		}
		else
		{
			$sql .= "AND t.status = 'open' ";
		}

		/**------
	    /**  Limit query by category
	    /**------*/

	    if (ee()->TMPL->fetch_param('category'))
	    {
	    	if (stristr(ee()->TMPL->fetch_param('category'), '&'))
	    	{
	    		/** --------------------------------------
	    		/**  First, we find all entries with these categories
	    		/** --------------------------------------*/

	    		$for_sql = (substr(ee()->TMPL->fetch_param('category'), 0, 3) == 'not') ? trim(substr(ee()->TMPL->fetch_param('category'), 3)) : ee()->TMPL->fetch_param('category');

	    		$csql = "SELECT exp_category_posts.entry_id, exp_category_posts.cat_id, ".
						str_replace('SELECT', '', $sql).
						ee()->functions->sql_andor_string(str_replace('&', '|', $for_sql), 'exp_categories.cat_id');

	    		//exit($csql);

	    		$results = ee()->db->query($csql);

	    		if ($results->num_rows() == 0)
	    		{
					return;
	    		}

	    		$type = 'IN';
	    		$categories	 = explode('&', ee()->TMPL->fetch_param('category'));
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
	    		if (substr(ee()->TMPL->fetch_param('category'), 0, 3) == 'not' && ee()->TMPL->fetch_param('uncategorized_entries') !== 'no')
	    		{
	    			$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category'), 'exp_categories.cat_id', '', TRUE)." ";
	    		}
	    		else
	    		{
	    			$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category'), 'exp_categories.cat_id')." ";
	    		}
	    	}
	    }

	    if (ee()->TMPL->fetch_param('category_group'))
	    {
	        if (substr(ee()->TMPL->fetch_param('category_group'), 0, 3) == 'not' && ee()->TMPL->fetch_param('uncategorized_entries') !== 'no')
			{
				$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category_group'), 'exp_categories.group_id', '', TRUE)." ";
			}
			else
			{
				$sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category_group'), 'exp_categories.group_id')." ";
			}
	    }

		$sql .= " ORDER BY t.entry_date {$sort}, t.entry_id {$sort} LIMIT 1";

		$query = ee()->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return;
		}

		/** ---------------------------------------
		/**  Replace variables
		/** ---------------------------------------*/

		ee()->load->library('typography');
		$comment_path = ($query->row('comment_url') != '') ? $query->row('comment_url') : $query->row('channel_url');
		$title = ee()->typography->format_characters($query->row('title'));

		$vars['0'] = array(
			'entry_id'						=> $query->row('entry_id'),
			'id_path'						=> array($query->row('entry_id'), array('path_variable' => TRUE)),
			'path'							=> array($query->row('url_title'), array('path_variable' => TRUE)),
			'title'							=> $title,
			'url_title'						=> $query->row('url_title'),
			'channel_short_name'			=> $query->row('channel_name'),
			'channel'						=> $query->row('channel_title'),
			'channel_url'					=> $query->row('channel_url'),
			'comment_entry_id_auto_path'	=> reduce_double_slashes($comment_path.'/'.$query->row('entry_id')),
			'comment_url_title_auto_path'	=> reduce_double_slashes($comment_path.'/'.$query->row('url_title'))
		);

		// Presumably this is legacy
		if ($which == 'next')
		{
			$vars['0']['next_entry->title'] = $title;
		}
		else
		{
			$vars['0']['prev_entry->title'] = $title;
		}

		return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);
	}

	// ------------------------------------------------------------------------

	/**
	  *  Channel "month links"
	  */
	public function month_links()
	{
		$return = '';

		//  Build query

		// Fetch the timezone array and calculate the offset so we can localize the month/year
		ee()->load->helper('date');
		$zones = timezones();

		$offset = ( ! isset($zones[ee()->session->userdata['timezone']]) OR $zones[ee()->session->userdata['timezone']] == '') ? 0 : ($zones[ee()->session->userdata['timezone']]*60*60);

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
						AND site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";


		$timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

		if (ee()->TMPL->fetch_param('show_future_entries') != 'yes')
		{
			$sql .= " AND exp_channel_titles.entry_date < ".$timestamp." ";
		}

		if (ee()->TMPL->fetch_param('show_expired') != 'yes')
		{
			$sql .= " AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > ".$timestamp.") ";
		}

		/**------
		/**  Limit to/exclude specific channels
		/**------*/

		if ($channel = ee()->TMPL->fetch_param('channel'))
		{
			$wsql = "SELECT channel_id FROM exp_channels WHERE site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";

			$wsql .= ee()->functions->sql_andor_string($channel, 'channel_name');

			$query = ee()->db->query($wsql);

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

		if ($status = ee()->TMPL->fetch_param('status'))
		{
			$status = str_replace('Open',	'open',	$status);
			$status = str_replace('Closed', 'closed', $status);

			$sstr = ee()->functions->sql_andor_string($status, 'status');

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

		switch (ee()->TMPL->fetch_param('sort'))
		{
			case 'asc'	: $sql .= " asc";
				break;
			case 'desc'	: $sql .= " desc";
				break;
			default		: $sql .= " desc";
				break;
		}

		if (is_numeric(ee()->TMPL->fetch_param('limit')))
		{
			$sql .= " LIMIT ".ee()->TMPL->fetch_param('limit');
		}

		$query = ee()->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return '';
		}

		$year_limit	= (is_numeric(ee()->TMPL->fetch_param('year_limit'))) ? ee()->TMPL->fetch_param('year_limit') : 50;
		$total_years  = 0;
		$current_year = '';

		foreach ($query->result_array() as $row)
		{
			$tagdata = ee()->TMPL->tagdata;

			$month = (strlen($row['month']) == 1) ? '0'.$row['month'] : $row['month'];
			$year  = $row['year'];

			$month_name = ee()->localize->localize_month($month);

			//  Dealing with {year_heading}
			if (isset(ee()->TMPL->var_pair['year_heading']))
			{
				if ($year == $current_year)
				{
					$tagdata = ee()->TMPL->delete_var_pairs('year_heading', 'year_heading', $tagdata);
				}
				else
				{
					$tagdata = ee()->TMPL->swap_var_pairs('year_heading', 'year_heading', $tagdata);

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

			$cond['month']			= ee()->lang->line($month_name[1]);
			$cond['month_short']	= ee()->lang->line($month_name[0]);
			$cond['month_num']		= $month;
			$cond['year']			= $year;
			$cond['year_short']		= substr($year, 2);

			$tagdata = ee()->functions->prep_conditionals($tagdata, $cond);

			//  parse path
			foreach (ee()->TMPL->var_single as $key => $val)
			{
				if (strncmp($key, 'path', 4) == 0)
				{
					$tagdata = ee()->TMPL->swap_var_single(
														$val,
														ee()->functions->create_url(ee()->functions->extract_path($key).'/'.$year.'/'.$month),
														$tagdata
													  );
				}

				//  parse month (long)
				if ($key == 'month')
				{
					$tagdata = ee()->TMPL->swap_var_single($key, ee()->lang->line($month_name[1]), $tagdata);
				}

				//  parse month (short)
				if ($key == 'month_short')
				{
					$tagdata = ee()->TMPL->swap_var_single($key, ee()->lang->line($month_name[0]), $tagdata);
				}

				//  parse month (numeric)
				if ($key == 'month_num')
				{
					$tagdata = ee()->TMPL->swap_var_single($key, $month, $tagdata);
				}

				//  parse year
				if ($key == 'year')
				{
					$tagdata = ee()->TMPL->swap_var_single($key, $year, $tagdata);
				}

				//  parse year (short)
				if ($key == 'year_short')
				{
					$tagdata = ee()->TMPL->swap_var_single($key, substr($year, 2), $tagdata);
				}
			 }

			 $return .= trim($tagdata)."\n";
		 }

		return $return;
	}

	// ------------------------------------------------------------------------

	// The old relationship functions. No longer needed, stop calling them.

	public function parse_reverse_related_entries()
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.6');
	}

	public function parse_related_entries()
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.6');
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
	public function related_entries()
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.6', 'Channel::related_category_entries()');

		return $this->related_category_entries();
	}

	public function related_category_entries()
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
			$qstring = reduce_double_slashes(str_replace($match[0], '', $qstring));
		}

		/** --------------------------------------
		/**  Remove "N"
		/** --------------------------------------*/
		if (preg_match("#/N(\d+)#", $qstring, $match))
		{
			$qstring = reduce_double_slashes(str_replace($match[0], '', $qstring));
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
				AND exp_channel_titles.site_id IN ('".implode("','", ee()->TMPL->site_ids)."') ";

		$sql .= ( ! is_numeric($qstring)) ? "AND exp_channel_titles.url_title = '".ee()->db->escape_str($qstring)."' " : "AND exp_channel_titles.entry_id = '".ee()->db->escape_str($qstring)."' ";

		$query = ee()->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return ee()->TMPL->no_results();
		}

		/** ----------------------------------
		/**  Build category array
		/** ----------------------------------*/

		$cat_array = array();

		// We allow the option of adding or subtracting cat_id's
		$categories = ( ! ee()->TMPL->fetch_param('category'))  ? '' : ee()->TMPL->fetch_param('category');

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
			return ee()->TMPL->no_results();
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

		ee()->TMPL->tagparams['category']		= $cats;
		ee()->TMPL->tagparams['dynamic']			= 'off';
		ee()->TMPL->tagparams['not_entry_id']	= $qstring; // Exclude the current entry

		// Set user submitted paramters

		$params = array('channel', 'username', 'status', 'orderby', 'sort');

		foreach ($params as $val)
		{
			if (ee()->TMPL->fetch_param($val) != FALSE)
			{
				ee()->TMPL->tagparams[$val] = ee()->TMPL->fetch_param($val);
			}
		}

		if ( ! is_numeric(ee()->TMPL->fetch_param('limit')))
		{
			ee()->TMPL->tagparams['limit'] = 10;
		}

		/** ----------------------------------
		/**  Run the channel parser
		/** ----------------------------------*/

		$this->initialize();
		$this->entry_id 	= '';
		$qstring 			= '';

		if (ee()->TMPL->fetch_param('custom_fields') != 'yes')
		{
			$this->enable['custom_fields'] = FALSE;
		}

		if ($this->enable['custom_fields'])
		{
			$this->fetch_custom_channel_fields();
		}

		$this->build_sql_query();

		if ($this->sql == '')
		{
			return ee()->TMPL->no_results();
		}

		$this->query = ee()->db->query($this->sql);

		if ($this->query->num_rows() == 0)
		{
			return ee()->TMPL->no_results();
		}

		ee()->load->library('typography');
		ee()->typography->initialize(array(
				'convert_curly'	=> FALSE)
				);

		if (ee()->TMPL->fetch_param('member_data') !== FALSE && ee()->TMPL->fetch_param('member_data') == 'yes')
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
			'relationships'		=> TRUE
		);

		if ($disable = ee()->TMPL->fetch_param('disable'))
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
	public function calendar()
	{
		// -------------------------------------------
		// 'channel_module_calendar_start' hook.
		//  - Rewrite the displaying of the calendar tag
		//
			if (ee()->extensions->active_hook('channel_module_calendar_start') === TRUE)
			{
				$edata = ee()->extensions->call('channel_module_calendar_start');
				if (ee()->extensions->end_script === TRUE) return $edata;
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
	  *  Ajax Image Upload
	  *
	  * Used by the SAEF
	  */

	public function filemanager_endpoint($function = '', $params = array())
	{
		ee()->load->library('filemanager');
		ee()->lang->loadfile('content');
		//ee()->load->library('cp');

		$config = array();

		if ($function)
		{
			ee()->filemanager->_initialize($config);

			return call_user_func_array(array($this->filemanager, $function), $params);
		}

		ee()->filemanager->process_request($config);
	}

	// ------------------------------------------------------------------------

	/**
	  *  Smiley pop up
	  *
	  * Used by the SAEF
	  */

	public function smiley_pop()
	{
		if (ee()->session->userdata('member_id') == 0)
		{
			return ee()->output->fatal_error(ee()->lang->line('must_be_logged_in'));
		}

		$class_path = PATH_MOD.'emoticon/emoticons.php';

		if ( ! is_file($class_path) OR ! @include_once($class_path))
		{
			return ee()->output->fatal_error('Unable to locate the smiley images');
		}

		if ( ! is_array($smileys))
		{
			return;
		}

		$path = ee()->config->slash_item('emoticon_url');

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

	public function form()
	{
		ee()->load->library('channel_form/channel_form_lib');

		if ( ! empty(ee()->TMPL))
		{
			try
			{
				return ee()->channel_form_lib->entry_form();
			}
			catch (Channel_form_exception $e)
			{
				return $e->show_user_error();
			}
		}

		return '';
	}

	// --------------------------------------------------------------------

	/**
	 * submit_entry
	 *
	 * @return	void
	 */
	public function submit_entry()
	{
		//exit if not called as an action
		if ( ! empty(ee()->TMPL) || ! ee()->input->get_post('ACT'))
		{
			return '';
		}

		ee()->load->library('channel_form/channel_form_lib');

		try
		{
			ee()->channel_form_lib->submit_entry();
		}
		catch (Channel_form_exception $e)
		{
			return $e->show_user_error();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * combo_loader
	 *
	 * @return	void
	 */
	public function combo_loader()
	{
		ee()->load->library('channel_form/channel_form_lib');
		ee()->load->library('channel_form/channel_form_javascript');
		return ee()->channel_form_javascript->combo_load();
	}
}
// END CLASS

/* End of file mod.channel.php */
/* Location: ./system/expressionengine/modules/channel/mod.channel.php */
