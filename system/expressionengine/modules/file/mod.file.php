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
 * ExpressionEngine File Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class File {

	var $limit	= '100';	// Default maximum query results if not specified.
	var $reserved_cat_segment 	= '';
	var $use_category_names		= FALSE;
	var $categories				= array();
	var $catfields				= array();
	var $valid_thumbs			= array();

	var $sql					= '';
	var $return_data			= '';	 	// Final data	

	// Pagination variables
	var $paginate				= FALSE;
	var $paginate_data			= '';
	var $pagination_links		= '';
	var $page_next				= '';
	var $page_previous			= '';
	var $current_page			= 1;
	var $total_pages			= 1;
	var $display_by				= '';
	var $total_rows				=  0;
	var $pager_sql				= '';
	var $p_page					= '';



	/**
	  * Constructor
	  */
	public function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		$this->limit = $this->limit;

		$this->query_string = (ee()->uri->page_query_string != '') ? ee()->uri->page_query_string : ee()->uri->query_string;

		if (ee()->config->item("use_category_name") == 'y' && ee()->config->item("reserved_category_word") != '')
		{
			$this->use_category_names	= ee()->config->item("use_category_name");
			$this->reserved_cat_segment	= ee()->config->item("reserved_category_word");
		}

	}

	// ------------------------------------------------------------------------

	/**
	  *  Files tag
	  */
	function entries()
	{
		$this->uri = ($this->query_string != '') ? $this->query_string : 'index.php';
		
		$this->_fetch_disable_param();

		$this->fetch_pagination_data();
		//$this->parse_gallery_tag();

		$this->build_sql_query();
		
		if (empty($this->sql))
		{
			return ee()->TMPL->no_results();
		}

		$this->query = $this->sql;

		if ($this->query->num_rows() == 0)
		{
			return ee()->TMPL->no_results();
		}

		ee()->load->library('typography');
		ee()->typography->initialize(array(
				'convert_curly'	=> FALSE)
				);

		$this->fetch_categories();

		$this->fetch_valid_thumbs();

		$this->parse_file_entries();

		if ($this->enable['pagination'] == TRUE)
		{
			$this->add_pagination_data();
		}


		return $this->return_data;

	}
	
	// ------------------------------------------------------------------------

	/**
	  *  Build SQL Query
	  */
	function build_sql_query($qstring = '')
	{
		$file_id		= '';
		$cat_id			= '';
		$dynamic		= TRUE;

		// If dynamic is off, we'll override all dynamically set variables
		if (ee()->TMPL->fetch_param('dynamic') == 'no')
		{
			$dynamic = FALSE;
		}

		$this->limit = ( ! is_numeric(ee()->TMPL->fetch_param('limit')))  ? $this->limit : ee()->TMPL->fetch_param('limit');

		// Parse the URL query string
		$this->uristr = ee()->uri->uri_string;

		if ($qstring == '')
		{
			$qstring = $this->query_string;
		}

		$this->basepath = ee()->functions->create_url($this->uristr);

		if ($qstring != '')
		{
			if ($dynamic && is_numeric($qstring))
			{
				$file_id = $qstring;
			}
			else
			{

// Man- this is way redundant.  Maybe move some to url helper or some such??

				ee()->load->helper('segment');
			
				// Parse ID
				if ($dynamic)
				{
					$seg = parse_id($qstring);
					$qstring = $seg['qstring'];
					$file_id = $seg['entry_id'];
				}
				
				// Parse page number
				if ($dynamic OR ee()->TMPL->fetch_param('paginate'))
				{
					$seg = parse_page_number($qstring, $this->basepath, $this->uristr);
					$this->p_page = $seg['p_page'];
					$this->basepath = $seg['basepath'];
					$this->uristr = $seg['uristr'];
					$qstring = $seg['qstring'];
					$page_marker = ($this->p_page) ? TRUE : FALSE;
				}			
				


				/** --------------------------------------
				/**  Parse category indicator
				/** --------------------------------------*/

				// Text version of the category

				if ($qstring != '' AND $this->reserved_cat_segment != '' AND in_array($this->reserved_cat_segment, explode("/", $qstring)) AND $dynamic)
				{
					$qstring = preg_replace("/(.*?)\/".preg_quote($this->reserved_cat_segment)."\//i", '', '/'.$qstring);

					ee()->db->distinct();
					ee()->db->select('cat_group');
					ee()->db->where_in('site_id', ee()->TMPL->site_ids);
					ee()->functions->ar_andor_string(ee()->TMPL->fetch_param('directory_id'), 'id');
					$query = ee()->db->get('upload_prefs');

					if ($query->num_rows() > 0)
					{
						$valid = 'y';
						$last  = explode('|', $query->row('cat_group') );
						$valid_cats = array();

						foreach($query->result_array() as $row)
						{
							if (ee()->TMPL->fetch_param('relaxed_categories') == 'yes')
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

						$result = ee()->db->select('cat_id')
								->where('cat_url_title', $cut_qstring)
								->where_in('group_id', $valid_cats)
								->get('categories');

						if ($result->num_rows() == 1)
						{
							$qstring = str_replace($cut_qstring, 'C'.$result->row('cat_id') , $qstring);
						}
						else
						{
							$result = ee()->db->select('cat_id')
								->where('cat_url_title', $qstring)
								->where_in('group_id', $valid_cats)
								->get('categories');


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


				//  Remove "N"
				// The recent comments feature uses "N" as the URL indicator
				// It needs to be removed if present
				if ($dynamic)
				{
					$seg = parse_n($qstring, $this->uristr);
					$qstring = $seg['qstring'];
					$this->uristr = $seg['uristr'];
				}				
			}	
		}


		// If the "File ID" was hard-coded, use it instead of
		// using the dynamically set one above

		if (ee()->TMPL->fetch_param('file_id'))
		{
			$file_id = ee()->TMPL->fetch_param('file_id');
		}

		// Setup Orderby
		$allowed_sorts = array('date', 'upload_date', 'random');
		$order_by = ee()->TMPL->fetch_param('orderby', 'upload_date');
		$sort = ee()->TMPL->fetch_param('sort', 'desc');
		
		$random = ($order_by == 'random') ? TRUE : FALSE;
		$order_by  = ($order_by == 'date' OR ! in_array($order_by, $allowed_sorts))  ? 'upload_date' : $order_by;
		
		// Need to add a short_name to the file upload prefs to be consistent with gallery

		//$dir_ids = array();

		// Start the cache so we can use for pagination
		ee()->db->start_cache();

		//  Fetch the file ids

		if (ee()->TMPL->fetch_param('category') OR ee()->TMPL->fetch_param('category_group') OR $cat_id != '')
		{
			ee()->db->distinct();

			//  We use 'LEFT' JOIN when there is a 'not' so that we get
			//  entries that are not assigned to a category.

			if ((substr(ee()->TMPL->fetch_param('category_group'), 0, 3) == 'not' OR substr(ee()->TMPL->fetch_param('category'), 0, 3) == 'not') && ee()->TMPL->fetch_param('uncategorized_entries') !== 'n')
			{
				ee()->db->join('file_categories', 'exp_files.file_id = exp_file_categories.file_id', 'LEFT');
				ee()->db->join('categories', 'exp_file_categories.cat_id = exp_categories.cat_id', 'LEFT');
			}
			else
			{
				ee()->db->join('file_categories', 'exp_files.file_id = exp_file_categories.file_id', 'INNER');
				ee()->db->join('categories', 'exp_file_categories.cat_id = exp_categories.cat_id', 'INNER');
			}
		}


		ee()->db->select('exp_files.file_id');
		ee()->db->from('files');

		if ($file_id != '')
		{
			ee()->functions->ar_andor_string($file_id, 'exp_files.file_id').' ';
		}
		
		// If directory_id is set in template
		if (($directory_ids = ee()->TMPL->fetch_param('directory_id')) != FALSE)
		{
			ee()->functions->ar_andor_string($directory_ids, 'upload_location_id');
		}
		// If no directory_id is set, restrict files to current site
		else
		{
			ee()->db->where_in('exp_files.site_id', ee()->TMPL->site_ids);
		}
		
		//  Limit query by category
		if (ee()->TMPL->fetch_param('category'))
		{
			// Doing a simplified version for now- no & allowed ??
			if (stristr(ee()->TMPL->fetch_param('category'), '&'))
			{
			}
			else
			{
				if (substr(ee()->TMPL->fetch_param('category'), 0, 3) == 'not' && ee()->TMPL->fetch_param('uncategorized_entries') !== 'n')
				{
					// $str, $field, $prefix = '', $null=FALSE
					ee()->functions->ar_andor_string(ee()->TMPL->fetch_param('category'), 'exp_categories.cat_id', '', TRUE);
				}
				else
				{
					ee()->functions->ar_andor_string(ee()->TMPL->fetch_param('category'), 'exp_categories.cat_id');
				}
			}
		}


		if (ee()->TMPL->fetch_param('category_group'))
		{
			if (substr(ee()->TMPL->fetch_param('category_group'), 0, 3) == 'not' && ee()->TMPL->fetch_param('uncategorized_entries') !== 'n')
			{
				ee()->functions->ar_andor_string(ee()->TMPL->fetch_param('category_group'), 'exp_categories.group_id', '', TRUE);
			}
			else
			{
				ee()->functions->ar_andor_string(ee()->TMPL->fetch_param('category_group'), 'exp_categories.group_id');
			}
		}

		if (ee()->TMPL->fetch_param('category') === FALSE && ee()->TMPL->fetch_param('category_group') === FALSE)
		{
			if ($cat_id != '' AND $dynamic)
			{
				ee()->db->where('exp_categories.cat_id', $cat_id);
			}
		}

		ee()->db->stop_cache();

		if ($this->paginate == TRUE)
		{
			//ee()->db->select('exp_files.file_id');
			$total = ee()->db->count_all_results();
			$this->absolute_results = $total;

			$this->create_pagination($total);
		}
		
		// We do the select down here as it could have been lost from cache anyway
		if ($this->paginate == TRUE)
		{
			ee()->db->select('exp_files.file_id');
		}

		// Add sorting
		$this_sort = ($random) ? 'random' : strtolower($sort);

		ee()->db->order_by($order_by, $this_sort);		

		// Add the limit
		$this_page = ($this->p_page == '' OR ($this->limit > 1 AND $this->p_page == 1)) ? 0 : $this->p_page;
		ee()->db->limit($this->limit, $this_page);
		
		
		//Fetch the file_id numbers
		$query = ee()->db->get();
		
		ee()->db->flush_cache();
		
		if ($query->num_rows() == 0)
		{
			$this->sql = '';
			return;
		}
		
		foreach ($query->result() as $row)
		{
			$file_ids[] = $row->file_id;
		}
			
		//  Build the full SQL query
		ee()->db->select('*');
		ee()->db->from('files');
		ee()->db->join('upload_prefs', 'upload_prefs.id = files.upload_location_id', 'LEFT');
		ee()->db->where_in('file_id', $file_ids);
		ee()->db->order_by($order_by, $this_sort);	
		
		$this->sql = ee()->db->get();
		
	}






	// ------------------------------------------------------------------------

	/**
	  *  Fetch pagination data
	  */
	function fetch_pagination_data()
	{
		if (strpos(ee()->TMPL->tagdata, LD.'paginate'.RD) === FALSE) 
		{
			return;
		}

		if (preg_match("/".LD."paginate".RD."(.+?)".LD.'\/'."paginate".RD."/s", ee()->TMPL->tagdata, $match))
		{
			$this->paginate	= TRUE;
			$this->paginate_data = $match[1];

			ee()->TMPL->tagdata = preg_replace("/".LD."paginate".RD.".+?".LD.'\/'."paginate".RD."/s", "", ee()->TMPL->tagdata);
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Create Pagination
	  */
	function create_pagination($count = 0)
	{

		if ($this->paginate == TRUE)
		{
			/* --------------------------------------
			/*  For subdomain's or domains using $template_group and $template
			/*  in path.php, the pagination for the main index page requires
			/*  that the template group and template are specified.
			/* --------------------------------------*/

			if ((ee()->uri->uri_string == '' OR ee()->uri->uri_string == '/') && ee()->config->item('template_group') != '' && ee()->config->item('template') != '')
			{
				$this->basepath = ee()->functions->create_url(ee()->config->slash_item('template_group').'/'.ee()->config->item('template'));
			}
			
			if ($this->basepath == '')
			{
				$this->basepath = ee()->functions->create_url(ee()->uri->uri_string);

				if (preg_match("#^P(\d+)|/P(\d+)#", $this->query_string, $match))
				{
					$this->p_page = (isset($match[2])) ? $match[2] : $match[1];
					$this->basepath = reduce_double_slashes(str_replace($match[0], '', $this->basepath));
				}
			}

			//  Standard pagination - base values

			if ($count == 0)
			{
				$this->sql = '';
				return;
			}

			$this->total_rows = $count;


			$this->p_page = ($this->p_page == '' OR ($this->limit > 1 AND $this->p_page == 1)) ? 0 : $this->p_page;

			if ($this->p_page > $this->total_rows)
			{
				$this->p_page = 0;
			}
								
			$this->current_page = floor(($this->p_page / $this->limit) + 1);
			$this->total_pages = intval(floor($this->total_rows / $this->limit));				

			//  Create the pagination

			if ($this->total_rows > 0 && $this->limit > 0)
			{
				if ($this->total_rows % $this->limit)
				{
					$this->total_pages++;
				}				
			}

			if ($this->total_rows > $this->limit)
			{
				ee()->load->library('pagination');

				if (strpos($this->basepath, SELF) === FALSE && ee()->config->item('site_index') != '')
				{
					$this->basepath .= SELF;
				}

				if (ee()->TMPL->fetch_param('paginate_base'))
				{
					$this->basepath = ee()->functions->create_url(trim_slashes(ee()->TMPL->fetch_param('paginate_base')));
				}
				
				$config['base_url']		= $this->basepath;
				$config['prefix']		= 'P';
				$config['total_rows'] 	= $this->total_rows;
				$config['per_page']		= $this->limit;
				$config['cur_page']		= $this->p_page;
				$config['first_link'] 	= ee()->lang->line('pag_first_link');
				$config['last_link'] 	= ee()->lang->line('pag_last_link');
				
				// Allows $config['cur_page'] to override
				$config['uri_segment'] = 0;

				ee()->pagination->initialize($config);
				$this->pagination_links = ee()->pagination->create_links();				


				if ((($this->total_pages * $this->limit) - $this->limit) > $this->p_page)
				{
					$this->page_next = reduce_double_slashes($this->basepath.'/P'.($this->p_page + $this->limit));
				}

				if (($this->p_page - $this->limit ) >= 0)
				{
					$this->page_previous = reduce_double_slashes($this->basepath.'/P'.($this->p_page - $this->limit));
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
			$this->paginate_data = str_replace(LD.'current_page'.RD, 		$this->current_page, 		$this->paginate_data);
			$this->paginate_data = str_replace(LD.'total_pages'.RD,			$this->total_pages,  		$this->paginate_data);
			$this->paginate_data = str_replace(LD.'pagination_links'.RD,	$this->pagination_links,	$this->paginate_data);

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
			
			$this->paginate_data = ee()->functions->prep_conditionals($this->paginate_data, array('total_pages' => $this->total_pages));

			$position = ( ! ee()->TMPL->fetch_param('paginate')) ? '' : ee()->TMPL->fetch_param('paginate');

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
	  *  Fetch categories
	  */
	function fetch_categories()
	{

			 ee()->db->select('field_id, field_name')
				->from('category_fields')
				->where_in('site_id', ee()->TMPL->site_ids);
				
			$query = ee()->db->get();

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
				}
			}


		$categories = array();

		foreach ($this->query->result_array() as $row)
		{
			$categories[] = $row['file_id'];
		}


		$sql = ee()->db->select('c.cat_name, c.cat_url_title, c.cat_id, c.cat_image, c.cat_description,
		 							c.parent_id, p.cat_id, p.file_id, c.group_id, cg.field_html_formatting, fd.*')
					->from('exp_categories AS c, exp_file_categories AS p')
					->join('category_field_data AS fd', 'fd.cat_id = c.cat_id', 'LEFT')
					->join('category_groups AS cg', 'cg.group_id = c.group_id', 'LEFT')
					->where('c.cat_id = p.cat_id')
					->where_in('file_id', $categories)
					->order_by('c.group_id, c.parent_id, c.cat_order');
		

		$query = ee()->db->get();

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
				if ($val == $row['file_id'])
				{
					$this->temp_array[$row['cat_id']] = array('category_id' => $row['cat_id'], 'parent_id' => $row['parent_id'], 'category_name' => $row['cat_name'], 'category_image' => $row['cat_image'], 'category_description' => $row['cat_description'], 'category_group_id' => $row['group_id'], 'category_url_title' => $row['cat_url_title']);
					
				
					// Add in the path variable
					$this->temp_array[$row['cat_id']]['path'] = ($this->use_category_names == TRUE)
							? array($this->reserved_cat_segment.'/'.$row['cat_url_title'], array('path_variable' => TRUE)) :
								array('/C'.$row['cat_id'], array('path_variable' => TRUE));

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
					if (isset($parents[$v['parent_id']])) $v['parent_id'] = 0;

					if (0 == $v['parent_id'])
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
	  *  Process Subcategories
	  */
	function process_subcategories($parent_id)
	{
		foreach($this->temp_array as $key => $val)
		{
			if ($parent_id == $val['parent_id'])
			{
				$this->cat_array[] = $val;
				$this->process_subcategories($key);
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	  *  Fetch Valid Thumbs
	  */
	function fetch_valid_thumbs()
	{
		ee()->db->select('upload_location_id, short_name');
		ee()->db->from('upload_prefs');

		ee()->db->join('file_dimensions', 'upload_prefs.id = file_dimensions.upload_location_id');
		
		ee()->db->where_in('upload_prefs.site_id', ee()->TMPL->site_ids);
		
		if (($directory_ids = ee()->TMPL->fetch_param('directory_id')) != FALSE)
		{		
			ee()->functions->ar_andor_string($directory_ids, 'upload_location_id');
		}
		
		$sql = ee()->db->get();
		
		if ($sql->num_rows() == 0)
		{
			return;
		}		

		foreach ($sql->result_array() as $row)
		{
			$this->valid_thumbs[] = array('dir' => $row['upload_location_id'], 'name' => $row['short_name']);
		}		
		
	}

	// ------------------------------------------------------------------------

	/**
	  *  Parse file entries
	  */
	function parse_file_entries()
	{
		
		// Fetch the "category chunk"
		// We'll grab the category data now to avoid processing cycles in the foreach loop below

		$cat_chunk = array();

		if (strpos(ee()->TMPL->tagdata, LD.'/categories'.RD) !== FALSE)
		{
			if (preg_match_all("/".LD."categories(.*?)".RD."(.*?)".LD.'\/'.'categories'.RD."/s", ee()->TMPL->tagdata, $matches))
			{
				for ($j = 0; $j < count($matches[0]); $j++)
				{
					$cat_chunk[] = array($matches[2][$j], ee()->functions->assign_parameters($matches[1][$j]), $matches[0][$j]);
				}
	  		}
		}


// dates still need localizing!

		$parse_data = array();
		
		$default_variables = array('description', 'caption', 'title');

		ee()->load->model('file_upload_preferences_model');
		$upload_prefs = ee()->file_upload_preferences_model->get_file_upload_preferences(1, NULL, TRUE);

		foreach ($this->query->result_array() as $count => $row)
		{
			$row_prefs = $upload_prefs[$row['upload_location_id']];
			
			$row['absolute_count']	= $this->p_page + $count + 1;

			//  More Variables, Mostly for Conditionals
			$row['logged_in'] 		= (ee()->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
			$row['logged_out'] 		= (ee()->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
			$row['entry_date']		= $row['upload_date'];
			$row['edit_date']		= $row['modified_date'];
			$row['directory_id']	= $row['id'];
			$row['directory_title']	= $row['name'];
			$row['entry_id']		= $row['file_id'];
			$row['file_url']		= rtrim($row_prefs['url'], '/').'/'.$row['file_name'];
			$row['filename'] 		= $row['file_name'];
			$row['viewable_image'] = $this->is_viewable_image($row['file_name']);

			// Add in the path variable
			$row['id_path'] = array('/'.$row['file_id'], array('path_variable' => TRUE));

			// typography on title?
			$row['title']			= ee()->typography->format_characters($row['title']);
			
			// typography on caption
			ee()->typography->parse_type($row['description'],
				array(
					'text_format'	=> 'xhtml',
					'html_format'	=> 'safe',
					'auto_links'	=> 'y',
					'allow_img_url' => 'y'
				)
			);
			
			// Caption is now called Description, but keep supporting
			// caption so it doesn't break on upgrade
			$row['caption'] = $row['description'];
			
			// Get File Size/H/W data
			$size_data = $this->get_file_sizes(reduce_double_slashes($row_prefs['server_path'].'/'.$row['filename']));
			
			foreach($size_data as $k => $v)
			{
				$row[$k] = $v;
			}
			
			// Thumbnail data
			foreach ($this->valid_thumbs as $data)
			{
				
				if ($row['viewable_image'] && $row['id'] == $data['dir'])
				{
					$size_data = array();
					
					$row[$data['name'].'_file_url'] = rtrim($row_prefs['url'], '/').'/_'.$data['name'].'/'.$row['file_name'];
					
					$size_data = $this->get_file_sizes(reduce_double_slashes($row_prefs['server_path'].'/_'.$data['name'].'/'.$row['file_name']));
						
					foreach($size_data as $k => $v)
					{
						$row[$data['name'].'_'.$k] = $v;
					}
				}
				else
				{
					$row[$data['name'].'_height'] = '';
					$row[$data['name'].'_width'] = '';
					$row[$data['name'].'_size'] = '';
					$row[$data['name'].'_file_url'] = '';
				}
			}
			
			// Category variables
			$row['categories'] = (isset($this->categories[$row['file_id']])) ? $this->categories[$row['file_id']] : array();
			
			$parse_data[] = $row;
		}
		
		$this->return_data = ee()->TMPL->parse_variables( ee()->TMPL->tagdata, $parse_data);		
		
	}
	

	function is_viewable_image($file)
	{
		$viewable_image = array('bmp','gif','jpeg','jpg','jpe','png');
		
		$ext = strtolower(substr(strrchr($file, '.'), 1));
		

		$viewable = (in_array($ext, $viewable_image)) ? TRUE : FALSE;
		return 	$viewable;	
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Gets File Metadata- may move to db
	 *
	 * @param	string	$file_path	The full path to the file to check
	 * @return	array
	 */
	function get_file_sizes($file_path)
	{	
		ee()->load->helper('file');
		$filedata = array('height' => '', 'width' => '');
		
		$filedata['is_image'] = $this->is_viewable_image($file_path);
		
		if ($filedata['is_image'] && function_exists('getimagesize'))
		{
			$D = @getimagesize($file_path);

			$filedata['height']	= $D['1'];
			$filedata['width']	= $D['0'];
		}
		
	 	$s = get_file_info($file_path, array('size'));

		$filedata['size'] = ($s) ? $s['size'] : FALSE;

		return $filedata;
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
							'member_data'		=> TRUE,
							'pagination' 		=> TRUE,
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
	


}
// END CLASS

/* End of file mod.file.php */
/* Location: ./system/expressionengine/modules/file/mod.file.php */