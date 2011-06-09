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

class File {

	var $limit	= '100';	// Default maximum query results if not specified.
	var $reserved_cat_segment 	= '';
	var $use_category_names		= FALSE;
	var $categories				= array();
	var $catfields				= array();

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
	var $p_limit				= '';
	var $p_page					= '';



	/**
	  * Constructor
	  */
	public function __construct()
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

	}

	// ------------------------------------------------------------------------

/*
1x params

    * category="2"
    * columns="4" rows="2"
    * dynamic="off"
    * file_id="147"
    * file_id_from="20"
    * file_id_to="40"
    * gallery="vacations"
    * limit="10"
    * log_views="off"
    * orderby="date" - caption, date, edit_date, file_id, most_comments, most_recent_comment, most_views, random, screen_name, title, username
    * paginate="top"
    * show_future_entries="yes"
    * sort="asc"
    * status="open"

*/

/*
1x sing vars
    * {caption}
    * {category}
    * {category_id}
    * {category_path='gallery/category'}
    * {count}
    * {custom_field_one}... {custom_field_six}
    * {entry_date format="%Y %m %d"}
    * {file_id}
    * {filename}
    * {height}
    * {id_path='gallery/comments'}
    * {image_url}
    * {medium_height}
    * {medium_url}
    * {medium_width}
    * {recent_comment_date format="%Y %m %d"}
    * {switch="option_one|option_two|option_three"}
    * {thumb_height}
    * {thumb_url}
    * {thumb_width}
    * {title}
    * {total_results}
    * {total_comments}
    * {views}
    * {width}

*/


	// ------------------------------------------------------------------------

	/**
	  *  Files tag
	  */
	function files()
	{
		$this->uri = ($this->query_string != '') ? $this->query_string : 'index.php';
		
		$this->_fetch_disable_param();

		$this->fetch_pagination_data();
		//$this->parse_gallery_tag();

		$this->build_sql_query();
		
		if ($this->sql == '')
		{
			return $this->EE->TMPL->no_results();
		}

		$this->query = $this->sql;

		if ($this->query->num_rows() == 0)
		{
			return $this->EE->TMPL->no_results();
		}

		$this->EE->load->library('typography');
		$this->EE->typography->initialize(array(
				'convert_curly'	=> FALSE)
				);

		$this->fetch_categories();

		$this->parse_file_entries();

		if ($this->enable['pagination'] == TRUE)
		{
			$this->add_pagination_data();
		}


		return $this->return_data;


// vs separate table for each file


	}
	
	function build_sql_query($qstring = '')
	{
		$file_id		= '';
		$cat_id			= '';
		$dynamic		= TRUE;

		// If dynamic is off, we'll override all dynamically set variables
		if ($this->EE->TMPL->fetch_param('dynamic') == 'no')
		{
			$dynamic = FALSE;
		}

		// Parse the URL query string
		$this->uristr = $this->EE->uri->uri_string;

		if ($qstring == '')
		{
			$qstring = $this->query_string;
		}

		$this->basepath = $this->EE->functions->create_url($this->uristr);

		if ($qstring != '')
		{
			if ($dynamic && is_numeric($qstring))
			{
				$file_id = $qstring;
			}
			else
			{

// Man- this is way redundant.  Maybe move some to url helper or some such??

				/** --------------------------------------
				/**  Parse ID indicator
				/** --------------------------------------*/
				if ($dynamic && preg_match("#^(\d+)(.*)#", $qstring, $match))
				{
					$seg = ( ! isset($match[2])) ? '' : $match[2];

					if (substr($seg, 0, 1) == "/" OR $seg == '')
					{
						$file_id = $match[1];
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
			}	
		}
				
				
				
// end of really horribly redundant


		// If the "File ID" was hard-coded, use it instead of
		// using the dynamically set one above

		if ($this->EE->TMPL->fetch_param('file_id'))
		{
			$file_id = $this->EE->TMPL->fetch_param('file_id');
		}


		
		// Need to add a short_name to the file upload prefs to be consistent with gallery
		// Are we limiting it to a specific upload directory?
		/*
		$dir_ids = array();
		
		if ($dir = $this->EE->TMPL->fetch_param('upload_directory') OR $this->EE->TMPL->fetch_param('site'))
		{
			$this->EE->db->select('id');
			$this->EE->db->where_in('site_id', $this->EE->TMPL->site_ids);

			if ($dir !== FALSE)
			{
				$this->EE->functions->ar_andor_string($dir, 'channel_name');
			}

			$dirs = $this->EE->db->get('channels');
				
			if ($dirs->num_rows() == 0)
			{
				if ( ! $dynamic)
				{
					return $this->EE->TMPL->no_results();
				}

				return false;
			}
			else
			{
				foreach($dirs->result_array() as $row)
				{
					$dir_ids[] = $row['channel_id'];
				}
			}
		}
		
		*/
		



		// Start the cache so we can use for pagination
		$this->EE->db->start_cache();

		//  Fetch the file ids

		if ($this->EE->TMPL->fetch_param('category') OR $this->EE->TMPL->fetch_param('category_group') OR $cat_id != '')
		{
			$this->EE->db->distinct();

			//  We use 'LEFT' JOIN when there is a 'not' so that we get
			//  entries that are not assigned to a category.

			if ((substr($this->EE->TMPL->fetch_param('category_group'), 0, 3) == 'not' OR substr($this->EE->TMPL->fetch_param('category'), 0, 3) == 'not') && $this->EE->TMPL->fetch_param('uncategorized_entries') !== 'n')
			{
				$this->EE->join('file_categories', 't.file_id = exp_file_categories.file_id', 'LEFT');
				$this->EE->join('categories', 'exp_file_categories.cat_id = exp_categories.cat_id', 'LEFT');
			}
			else
			{
				$this->EE->join('file_categories', 't.file_id = exp_file_categories.file_id', INNER);
				$this->EE->join('categories', 'exp_file_categories.cat_id = exp_categories.cat_id', INNER);
			}
		}


		$this->EE->db->select('file_id');
		$this->EE->db->from('files');

		$this->EE->db->where_in('site_id', $this->EE->TMPL->site_ids);

		if ($file_id != '')
		{
			$this->EE->functions->ar_andor_string($file_id, 't.file_id').' ';
		}

		if (($directory = $this->EE->TMPL->fetch_param('directory_id')) != FALSE)
		{		
			$this->EE->functions->ar_andor_string($directory, 'id').' ';
		}


$this->EE->db->stop_cache();
		$this->paginate = FALSE;
		if ($this->paginate == TRUE)
		{

			$total = $this->db->count_all_results();
			$this->absolute_results = $total;

			$this->create_pagination($total);
		}
		
		// Add the limit
		$this_page = ($this->current_page == '' OR ($this->limit > 1 AND $this->current_page == 1)) ? 0 : $this->current_page;
		$this->EE->db->limit($this->limit, $this_page);
		
		//Fetch the file_id numbers
		$query = $this->EE->db->get();
		
		
		
		$this->EE->db->flush_cache();
		
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
		
		$this->EE->db->select('*');
		$this->EE->db->from('files');
		$this->EE->db->join('upload_prefs', 'upload_prefs.id = files.upload_location_id', 'LEFT');
		$this->EE->db->where_in('file_id', $file_ids);
		
		$this->sql = $this->EE->db->get();
		

		/*
		$sqlc = "	FROM exp_gallery_entries			AS e
					LEFT JOIN exp_galleries				AS p ON p.gallery_id = e.gallery_id
					LEFT JOIN exp_gallery_categories	AS c ON c.cat_id = e.cat_id
					LEFT JOIN exp_members				AS m ON e.author_id = m.member_id 
					WHERE ";	
		*/
		
	}






	// ------------------------------------------------------------------------

	/**
	  *  Fetch pagination data
	  */
	function fetch_pagination_data()
	{
		if (strpos($this->EE->TMPL->tagdata, LD.'paginate'.RD) === FALSE) 
		{
			return;
		}

		if (preg_match("/".LD."paginate".RD."(.+?)".LD.'\/'."paginate".RD."/s", $this->EE->TMPL->tagdata, $match))
		{
			$this->paginate	= TRUE;
			$this->paginate_data = $match[1];

			$this->EE->TMPL->tagdata = preg_replace("/".LD."paginate".RD.".+?".LD.'\/'."paginate".RD."/s", "", $this->EE->TMPL->tagdata);
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

			if ($count == 0)
			{
				$this->sql = '';
				return;
			}

			$this->total_rows = $count;


			$this->p_page = ($this->p_page == '' OR ($this->p_limit > 1 AND $this->p_page == 1)) ? 0 : $this->p_page;

			if ($this->p_page > $this->total_rows)
			{
				$this->p_page = 0;
			}
								
			$this->current_page = floor(($this->p_page / $this->p_limit) + 1);
			$this->total_pages = intval(floor($this->total_rows / $this->p_limit));				

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
	  *  Fetch categories
	  */
	function fetch_categories()
	{

			 $this->EE->db->select('field_id, field_name')
				->from('category_fields')
				->where_in('site_id', $this->EE->TMPL->site_ids);
				
			$query = $this->EE->db->get();

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


		$sql = $this->EE->db->select('c.cat_name, c.cat_url_title, c.cat_id, c.cat_image, c.cat_description,
		 							c.parent_id, p.cat_id, p.file_id, c.group_id, cg.field_html_formatting, fd.*')
					->from('exp_categories AS c, exp_file_categories AS p')
					->join('category_field_data AS fd', 'fd.cat_id = c.cat_id', 'LEFT')
					->join('category_groups AS cg', 'cg.group_id = c.group_id', 'LEFT')
					->where('c.cat_id = p.cat_id')
					->where_in('file_id', $categories)
					->order_by('c.group_id, c.parent_id, c.cat_order');
		

		$query = $this->EE->db->get();

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
	  *  Parse file entries
	  */
	function parse_file_entries()
	{
		
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

// dates still need localizing!

		$parse_data = array();
		$default_variables = array('caption', 'title');
		$custom_fields = array('1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four', '5' => 'five', '6' => 'six');

		foreach ($this->query->result_array() as $count => $row)
		{
			$row['absolute_count']	= $this->p_page + $count + 1;

			//  More Variables, Mostly for Conditionals
			$row['logged_in'] = ($this->EE->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
			$row['logged_out'] = ($this->EE->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
			$row['entry_id_path']				= array('path', array('suffix'	=> $row['file_id']));
			$row['entry_date']				= $row['upload_date'];
			//$row['channel']				= $row['channel_title'];
			
			// Category variables
			$row['categories'] = $this->categories[$row['file_id']];
			
			//if (isset($this->categories[$row['file_id']]))
			//{
			//	foreach ($this->categories[$row['file_id']]
			//}
				
			
			

			// Default variables
			
			// 6 custom fields
			foreach ($custom_fields as $field_id => $tag)
			{
				$row['custom_field_'.$tag] = $this->EE->typography->parse_type(
					$row['field_'.$field_id],
						array(
							'text_format'	=> $row['field_'.$field_id.'_fmt'],
							'html_format'	=> 'safe',
							'auto_links'	=> 'y',
							'allow_img_url' => 'y'
							)
						);
			}
			
			$parse_data[] = $row;



		}
		
		$this->return_data = $this->EE->TMPL->parse_variables( $this->EE->TMPL->tagdata, $parse_data);		
		
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
	


}
// END CLASS

/* End of file mod.file.php */
/* Location: ./system/expressionengine/modules/file/mod.file.php */