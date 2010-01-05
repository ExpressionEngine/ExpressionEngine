<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2010, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: mod.gallery.php
-----------------------------------------------------
 Purpose: Photo Gallery Module
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}


class Gallery {

	var	$return_data 		= ''; 
	var	$limit				= 500;
	
	var	$sql				= '';
	var	$query				= '';
	var	$TYPE				= '';
	var $dynamic			= TRUE;
	
	var	$entry_id			= '';
	var	$cat_id				= '';
	var	$categories			= '';
	
	var	$entry_date			= array();
	var	$recent_entry_date	= array();
	var $recent_comment_date = array();
	
	var	$entry_template		= '';
	var	$row_start			= '';
	var	$row_end			= '';
	var	$blank_row			= '';
	var	$row				= '';
	
	var	$max_columns		= 3;
	var	$max_rows			= 3;
	
	var	$cat_template		= '';	
	var	$cat_row			= '';
	var	$cat_row_start		= '';
	var	$cat_row_end		= '';

	var	$subcat_template	= '';
	var	$subcat_row			= '';
	var	$subcat_marker		= '';
	var	$subcat_row_start	= '';
	var	$subcat_row_end		= '';
	
	var	$paginate			= FALSE;
	var	$paginate_data		= '';
	var	$pagination_links	= '';
	var $page_next			= '';
	var $page_previous		= '';
	var $current_page		= 1;
	var $total_pages		= 1;
	
	var $one_entry			= FALSE;
	
	// Show anchor?
	// TRUE/FALSE
	// Determines whether to show the <a name> anchor above each comment
	
	var $show_anchor = FALSE; 
	
	
	
	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function Gallery()
	{ 
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		$fields = array('name', 'email', 'url', 'location', 'comment');
		
		foreach ($fields as $val)
		{
			if (isset($_POST[$val] ))
			{
				$_POST[$val] = $this->EE->functions->encode_ee_tags($_POST[$val]);
				$_POST[$val] = str_replace("{", "&#123;", $_POST[$val]);
				$_POST[$val] = str_replace("}", "&#125;", $_POST[$val]);
				
				if ($val == 'comment')
				{
					$_POST[$val] = $this->EE->security->xss_clean($_POST[$val]);
				}
			}
		}
	
	}



	/** -------------------------------------
	/**  Gallery Entries Tag
	/** -------------------------------------*/
	function entries()
	{
		if ($this->EE->TMPL->fetch_param('columns'))
		{
			$this->max_columns = $this->EE->TMPL->fetch_param('columns');			
		}
		
		if ($this->EE->TMPL->fetch_param('rows'))
		{
			$this->max_rows = $this->EE->TMPL->fetch_param('rows');
		}  
				
		if ($this->EE->TMPL->fetch_param('dynamic') == 'off')
		{		
			$this->dynamic = FALSE;
		}  
						
		$this->fetch_pagination_data();
		$this->parse_gallery_tag();

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
		  
		if ($this->entry_id != '' AND $this->EE->TMPL->fetch_param('log_views') != 'off')
		{
			$this->log_views();
		}
		
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		$this->parse_gallery_entries();
		$this->add_pagination_data();
		
		return $this->return_data;
	}



	/** ----------------------------------------
	/**  Fetch pagination data
	/** ----------------------------------------*/
	function fetch_pagination_data()
	{
		if (preg_match("/".LD."paginate".RD."(.+?)".LD.'\/'."paginate".RD."/s", $this->EE->TMPL->tagdata, $match))
		{
			$this->paginate	= TRUE;
			$this->paginate_data	= $match['1'];
						
			$this->EE->TMPL->tagdata = preg_replace("/".LD."paginate".RD.".+?".LD.'\/'."paginate".RD."/s", "", $this->EE->TMPL->tagdata);
		}
	}




	/** ----------------------------------------
	/**  Add pagination data to result
	/** ----------------------------------------*/
	
	function add_pagination_data()
	{
		if ($this->paginate == TRUE)
		{
			$this->paginate_data = str_replace(LD.'current_page'.RD, 		$this->current_page, 		$this->paginate_data);
			$this->paginate_data = str_replace(LD.'total_pages'.RD,			$this->total_pages,  		$this->paginate_data);
			$this->paginate_data = str_replace(LD.'pagination_links'.RD,	$this->pagination_links,	$this->paginate_data);
			
			if (preg_match("/".LD."if previous_page".RD."(.+?)".LD.'\/'."if".RD."/s", $this->paginate_data, $match))
			{
				if ($this->page_previous == '')
				{
					 $this->paginate_data = preg_replace("/".LD."if previous_page".RD.".+?".LD.'\/'."if".RD."/s", '', $this->paginate_data);
				}
				else
				{
				$match['1'] = preg_replace("/".LD.'path.*?'.RD."/", 	$this->page_previous, $match['1']);
				$match['1'] = preg_replace("/".LD.'auto_path'.RD."/",	$this->page_previous, $match['1']);
			
				$this->paginate_data = str_replace($match['0'],	$match['1'], $this->paginate_data);
			}
			}
			
			
			if (preg_match("/".LD."if next_page".RD."(.+?)".LD.'\/'."if".RD."/s", $this->paginate_data, $match))
			{
				if ($this->page_next == '')
				{
					 $this->paginate_data = preg_replace("/".LD."if next_page".RD.".+?".LD.'\/'."if".RD."/s", '', $this->paginate_data);
				}
				else
				{
				$match['1'] = preg_replace("/".LD.'path.*?'.RD."/", 	$this->page_next, $match['1']);
				$match['1'] = preg_replace("/".LD.'auto_path'.RD."/",	$this->page_next, $match['1']);
			
				$this->paginate_data = str_replace($match['0'],	$match['1'], $this->paginate_data);
			}
			}
				
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

	


	/** -------------------------------------
	/**  Category Name
	/** -------------------------------------*/
	function category_name()
	{
		if ( ! preg_match("#(^|\/)C(\d+)#", $this->EE->uri->query_string, $match))
		{		
			return '';
		}
				
		$sql = "SELECT exp_gallery_categories.cat_name
				FROM exp_gallery_categories
				WHERE exp_gallery_categories.cat_id = '".$this->EE->db->escape_str($match['2'])."'";

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return '';
		}

		return str_replace(LD.'category'.RD, $query->row('cat_name') , $this->EE->TMPL->tagdata);
	}



	/** -------------------------------------
	/**  Category List
	/** -------------------------------------*/
	function category_list()
	{
		if ( ! $gallery = $this->EE->TMPL->fetch_param('gallery'))
		{
			return '';
		}
	
		$sql = "SELECT c.cat_id, c.cat_name, c.cat_description
				FROM exp_gallery_categories AS c, exp_galleries
				WHERE c.gallery_id = exp_galleries.gallery_id";
				
		$sql .= "AND exp_galleries.gallery_short_name = '".$this->EE->db->escape_str($gallery)."'";

		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return '';
		}

		$cat_path  = '';
		$cat_chunk = '';
		
		if (preg_match("/".LD.'category_path=(.+?)'.RD."/", $this->EE->TMPL->tagdata, $match))
		{
			$cat_chunk = $match['0'];
			$cat_path  = $match['1'];
		}

		$return = '';		
		foreach ($query->result_array() as $row)
		{
			$tagdata = $this->EE->TMPL->tagdata;
		
			$tagdata = str_replace(LD.'category_name'.RD, $row['cat_name'], $tagdata);
			$tagdata = str_replace(LD.'category_description'.RD, $row['cat_description'], $tagdata);
			$tagdata = str_replace(LD.'category_id'.RD, $row['cat_id'], $tagdata);
			
			// deprecated, never documented, keeping it in just in case, though
			$tagdata = str_replace(LD.'cat_id'.RD, $row['cat_id'], $tagdata);
	
			if ($cat_path != '')
			{
				$path = $cat_path.'/C'.$row['cat_id'].'/';
				$tagdata = str_replace($cat_chunk, $this->EE->functions->create_url($path, 1, 0), $tagdata);			
			}
			$return .= $tagdata;
		}
		
		return $return;
	}



	/** -------------------------------------
	/**  Log Image Views
	/** -------------------------------------*/
	function log_views()
	{
		if ($this->entry_id == '')
			return;
			
			
		$query = $this->EE->db->query("SELECT cat_id, views FROM exp_gallery_entries WHERE entry_id = '{$this->entry_id}'");
		
		if ($query->num_rows() == 1)
		{
			$cat_id	= $query->row('cat_id') ;
			$views	= $query->row('views')  + 1;
			
			$this->EE->db->query("UPDATE exp_gallery_entries set views = '{$views}' WHERE entry_id = '{$this->entry_id}'");
			
			$query = $this->EE->db->query("SELECT total_views FROM exp_gallery_categories WHERE cat_id = '{$cat_id}'");
			
			if ($query->num_rows() == 1)
			{
				$views	= $query->row('total_views')  + 1;
				$this->EE->db->query("UPDATE exp_gallery_categories SET total_views = '{$views}' WHERE cat_id = '{$cat_id}'");
			}
		}
	}



	/** -------------------------------------
	/**  Build Gallery Query
	/** -------------------------------------*/
	function build_sql_query()
	{
		if ( ! $gallery = $this->EE->TMPL->fetch_param('gallery'))
		{
			return '';
		}
		
		/** ----------------------------------------------
		/**  Entry ID number
		/** ----------------------------------------------*/
		
		$this->entry_id	= '';
		$this->cat_id	= '';		
		$uristr			= '';
		$current_page	= '';
	
		$qstring		= $this->EE->uri->query_string;
		$uristr			= $this->EE->uri->uri_string;
	

		/** --------------------------------------
		/**  Check to see if we have an extra segment
		/** --------------------------------------*/
		
		// This basically fixes a bug that happens if there is an extra segment.
		
		if (preg_match("#^(\d+)(.*)#", $qstring, $match) AND $this->dynamic == TRUE)
		{ 
			$seg = ( ! isset($match['2'])) ? '' : $match['2'];
			if (substr($seg, 0, 1) == "/")
			{
				$qstring = $match['1'];
			}
		}
		
		// Onward...

		if (is_numeric($qstring) AND $qstring != '' AND $this->dynamic == TRUE)
		{
			$this->entry_id  = $qstring;
			$this->max_columns = 1;
		}
		else
		{
			// Load the string helper
			$this->EE->load->helper('string');

			/** --------------------------------------
			/**  Is the category being specified by ID?
			/** --------------------------------------*/
			if (preg_match("#(^|\/)C(\d+)#", $qstring, $match) AND $this->dynamic == TRUE)
			{	
				$this->cat_id = $match['2'];	
				$qstring = trim_slashes(str_replace($match['0'], '', $qstring));
			}
		
			/** --------------------------------------
			/**  Parse page number
			/** --------------------------------------*/
			if (preg_match("#^P(\d+)|/P(\d+)#", $qstring, $match))
			{					
				$current_page = (isset($match['2'])) ? $match['2'] : $match['1'];	
				$uristr  = $this->EE->functions->remove_double_slashes(str_replace($match['0'], '', $uristr));
				$qstring = trim_slashes(str_replace($match['0'], '', $qstring));
				$page_marker = TRUE;
			}

			/** --------------------------------------
			/**  Remove "N" 
			/** --------------------------------------*/
			
			// The recent comments feature uses "N" as the URL indicator
			// It needs to be removed if presenst

			if (preg_match("#^N(\d+)|/N(\d+)#", $qstring, $match))
			{					
				$uristr = $this->EE->functions->remove_double_slashes(str_replace($match['0'], '', $uristr));
				$qstring = trim_slashes(str_replace($match['0'], '', $qstring));
			}
		}
				
		// If the "entry ID" was hard-coded, use it instead
		
		if ($this->EE->TMPL->fetch_param('entry_id'))
		{
			$this->entry_id = $this->EE->TMPL->fetch_param('entry_id');
		}
		
		/** ------------------------------
		/**  Build the query
		/** ------------------------------*/
		
		$sql = '';
		
		$sqla = "	SELECT e.entry_id ";
				
		$sqlb = "	SELECT 
					e.*, 
					p.gallery_image_url, p.gallery_thumb_prefix, p.gallery_medium_prefix, p.gallery_text_formatting, p.gallery_auto_link_urls, p.gallery_cf_one_formatting, p.gallery_cf_one_auto_link, p.gallery_cf_two_formatting, p.gallery_cf_two_auto_link, p.gallery_cf_three_formatting, p.gallery_cf_three_auto_link, p.gallery_cf_four_formatting, p.gallery_cf_four_auto_link, p.gallery_cf_five_formatting, p.gallery_cf_five_auto_link, p.gallery_cf_six_formatting, p.gallery_cf_six_auto_link,					
					c.cat_folder, c.cat_name, c.cat_description,
					m.screen_name, m.username ";
			
		$sqlc = "	FROM exp_gallery_entries			AS e
					LEFT JOIN exp_galleries				AS p ON p.gallery_id = e.gallery_id
					LEFT JOIN exp_gallery_categories	AS c ON c.cat_id = e.cat_id
					LEFT JOIN exp_members				AS m ON e.author_id = m.member_id 
					WHERE ";			
					
								 
		$xql  = "SELECT gallery_id FROM exp_galleries WHERE gallery_id = gallery_id";  // need a where clause for sql_andor_string() until it gets modified
	
		$xql .= $this->EE->functions->sql_andor_string($gallery, 'gallery_short_name');
											
		$query = $this->EE->db->query($xql);
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->TMPL->no_results();
		}
		else
		{
			if ($query->num_rows() == 1)
			{
				$sql .= " e.gallery_id = '".$query->row('gallery_id') ."' ";
			}
			else
			{
				$sql .= " (";
				foreach ($query->result_array() as $row)
				{
					$sql .= "e.gallery_id = '".$row['gallery_id']."' OR ";
				}
				$sql = substr($sql, 0, - 3).") ";
			}
		}

		
		/** ----------------------------------------------
		/**  Limit query by entry ID for individual entries
		/** ----------------------------------------------*/
		 
		if ($this->entry_id != '')
		{			
			$sql .= $this->EE->functions->sql_andor_string($this->entry_id, 'e.entry_id ')." ";
		}
		
		/** ----------------------------------------------
		/**  Limit query by entry_id range
		/** ----------------------------------------------*/
				
		if ($entry_id_from = $this->EE->TMPL->fetch_param('entry_id_from'))
		{
			$sql .= "AND e.entry_id >= '$entry_id_from' ";
		}
		
		if ($entry_id_to = $this->EE->TMPL->fetch_param('entry_id_to'))
		{
			$sql .= "AND e.entry_id <= '$entry_id_to' ";
		}
		
		/** ---------------------------------
		/**  Status
		/** ---------------------------------*/
			
		$status = $this->EE->TMPL->fetch_param('status');
		
		switch ($status)
		{
			case 'open'		:	$status = 'o';
				break;
			case 'closed'	:	$status = 'c';
				break;
			default			:	$status = 'o';
				break;
		}
		
		$sql .= " AND e.status = '{$status}' ";
					
		/** ---------------------------------
		/**  Limit future entries
		/** ---------------------------------*/
		
		$timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->localize->set_gmt($this->EE->TMPL->cache_timestamp) : $this->EE->localize->now;
		
		if ($this->EE->TMPL->fetch_param('show_future_entries') != 'yes')
		{
			$sql .= " AND e.entry_date < ".$timestamp." ";
		}
		
		/** ---------------------------------
		/**  Limit query by category
		/** ---------------------------------*/
				
		if ($this->EE->TMPL->fetch_param('category'))
		{
			$sql .= $this->EE->functions->sql_andor_string($this->EE->TMPL->fetch_param('category'), 'c.cat_id')." ";
		}  
		else
		{
			if ($this->cat_id != '' AND $this->dynamic == TRUE)
			{
				$sql .= " AND e.cat_id = '{$this->cat_id}' ";				
			}
		}
		
		/* -------------------------------------------
		/* 'gallery_build_sql_query_add' hook.
		/*  - Add onto the SQL Query for displaying gallery entries
		/*  - Added 1.4.2
		*/
			if ($this->EE->extensions->active_hook('gallery_build_sql_query_add') === TRUE)
			{
				$sql = $this->EE->extensions->call('gallery_build_sql_query_add', $sql);
			}
		/*
		/* -------------------------------------------*/
					
		/** ---------------------------------
		/**  Set ORDER BY Clause
		/** ---------------------------------*/
		$orderby = $this->EE->TMPL->fetch_param('orderby');				
				
		switch ($orderby)
		{
			case		'entry_id'				:	$sql .= " ORDER BY e.entry_id";
				break;
			case		'title'					:	$sql .= " ORDER BY e.title";
				break;
			case		'caption'				:	$sql .= " ORDER BY e.caption";
				break;
			case		'date'					:	$sql .= " ORDER BY e.entry_date";
				break;
			case		'edit_date'				:	$sql .= " ORDER BY e.edit_date";
				break;
			case		'random'				:	$sql .= " ORDER BY rand()";
				break;
			case		'most_views'			:	$sql .= " ORDER BY e.views";
				break;
			case		'most_comments'			:	$sql .= " ORDER BY e.total_comments";
				break;
			case		'most_recent_comment'	:	$sql .= " ORDER BY e.recent_comment_date";
				break;
			case		'username'				:	$sql .= " ORDER BY m.username";
				break;
			case		'screen_name'			:	$sql .= " ORDER BY m.screen_name";
				break;
			default								:	$sql .= " ORDER BY e.entry_date";
				break;
		}
		
		
		$sort  = $this->EE->TMPL->fetch_param('sort');
				
		if ($sort === FALSE OR ($sort != 'asc' AND $sort != 'desc'))
		{
			$sort = 'desc';
		}
		
		$sql .= " ".$sort;
		
		if (stristr($sql, 'ORDER BY e.entry_date'))
		{
			$sql .= ", e.entry_id ".$sort;
		}
		
		/** ---------------------------------
		/**  Set LIMIT Clause
		/** ---------------------------------*/
		
		// The limit must be adjusted based on the number of
		// columns and rows
		
		// Note:  We don't add this until after pagination
		// but we need to calculate it here

		if ($limit = $this->EE->TMPL->fetch_param('limit'))
		{
			$this->limit = $this->EE->TMPL->fetch_param('limit');
		}
		else
		{		
			$this->limit = $this->max_columns * $this->max_rows;
		}
		
		/** ----------------------------------------
		/**  Do we need pagination?
		/** ----------------------------------------*/
		
		// We'll run the query to find out
				
		if ($this->paginate == TRUE)
		{
			$query = $this->EE->db->query($sqla.$sqlc.$sql);
			
			if ($query->num_rows() == 0)
			{
				$this->sql = '';
				return;
			}
		
			$total_rows = $query->num_rows;
			
			$current_page = ($current_page == '' OR ($this->limit > 1 AND $current_page == 1)) ? 0 : $current_page;
			
			if ($current_page > $total_rows)
			{
				$current_page = 0;
			}
							
			$this->current_page	= floor(($current_page / $this->limit) + 1);
			$this->total_pages	= intval(floor($total_rows / $this->limit));

			/** ----------------------------------------
			/**  Create the pagination
			/** ----------------------------------------*/
			
			if ($total_rows % $this->limit) 
			{
				$this->total_pages++;
			}	
			
			if ($total_rows > $this->limit)
			{
				if ( ! class_exists('Paginate'))
				{
					require APPPATH.'_to_be_replaced/lib.paginate'.EXT;
				}
				
				$PGR = new Paginate();
				
				$basepath = $this->EE->functions->create_url($uristr, 1);
				
				// Check for URL rewriting.  If so, we need to add the
				// SELF filename to the URL
				
				if (substr(SELF, -4) !== '.php' && strpos($basepath, SELF) === FALSE)
				{
					$basepath .= SELF.'/';
				}
				
				$first_url = (substr($basepath, -5) == '.php/') ? substr($basepath, 0, -1) : $basepath;
				
				$PGR->first_url 	= $first_url;
				$PGR->path			= $basepath;
				$PGR->prefix		= 'P';
				$PGR->total_count 	= $total_rows;
				$PGR->per_page		= $this->limit;
				$PGR->cur_page		= $current_page;

				$this->pagination_links = $PGR->show_links();
				
				if ((($this->total_pages * $this->limit) - $this->limit) > $current_page)
				{
					$this->page_next = $basepath.'P'.($current_page + $this->limit).'/';
				}
				
				if (($current_page - $this->limit ) >= 0) 
				{						
					$this->page_previous = $basepath.'P'.($current_page - $this->limit).'/';
				}
			}
			else
			{
				$current_page = '';
			}
		}
	
		if ($this->paginate == FALSE)
			$current_page = 0;
			
		$sql .= ($current_page == '') ? " LIMIT ".$this->limit : " LIMIT ".$current_page.', '.$this->limit;
		
		$this->sql = $sqlb.$sqlc.$sql;
	}





	/** -------------------------------------
	/**  Parse Gallery Entries Tag
	/** -------------------------------------*/
	function parse_gallery_tag()
	{
		/** ----------------------------------------
		/**  Fetch all the date-related variables
		/** ----------------------------------------*/
						
		if (preg_match_all("/".LD."entry_date format=[\"'](.*?)[\"']".RD."/s", $this->EE->TMPL->tagdata, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$matches['0'][$j] = str_replace(LD, '', $matches['0'][$j]);
				$matches['0'][$j] = str_replace(RD, '', $matches['0'][$j]);
																
				$this->entry_date[$matches['0'][$j]] = array($matches['1'][$j], $this->EE->localize->fetch_date_params($matches['1'][$j]));					
			}
		}
		
		if (preg_match_all("/".LD."recent_comment_date format=[\"'](.*?)[\"']".RD."/s", $this->EE->TMPL->tagdata, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$matches['0'][$j] = str_replace(LD, '', $matches['0'][$j]);
				$matches['0'][$j] = str_replace(RD, '', $matches['0'][$j]);
																
				$this->recent_comment_date[$matches['0'][$j]] = array($matches['1'][$j], $this->EE->localize->fetch_date_params($matches['1'][$j]));					
			}
		}
		
		/** -------------------------------------
		/**  Fetch the entries "chunk"
		/** -------------------------------------*/
		
		// This needs to be the first preg_match!
		// It extracts the chunk that will repeat with eacn result row
		
		// If this REGX returns FALSE we will set the "one_entry" flag, telling the parser
		// to render it in "single entry" mode.
		
		if ( ! preg_match("/".LD."entries".RD."(.+?)".LD.'\/'."entries".RD."/s", $this->EE->TMPL->tagdata, $match))
		{
			$this->one_entry = TRUE;
			$this->EE->TMPL->tagparams['limit'] = 1;
		}
		else
		{
			$this->one_entry = FALSE;
			$this->entry_template = trim($match['1']);
			$this->EE->TMPL->tagdata = str_replace ($match['0'], LD.'entry_template'.RD, $this->EE->TMPL->tagdata);		
		}
		
		
		/** -------------------------------------
		/**  Fetch the {row_start} data
		/** -------------------------------------*/
		
		// We'll replace it with a temporary marker that
		// we can itentify with a str_replace later
		
		if (preg_match("/".LD."row_start".RD."(.+?)".LD.'\/'."row_start".RD."/s", $this->entry_template, $match))
		{
			$this->row_start	 = trim($match['1']);
			$this->entry_template = str_replace ($match['0'], LD.'temp_row_start'.RD, $this->entry_template);
		}
		
		/** -------------------------------------
		/**  Fetch the {row_end} data
		/** -------------------------------------*/
		if (preg_match("/".LD."row_end".RD."(.+?)".LD.'\/'."row_end".RD."/s", $this->entry_template, $match))
		{
			$this->row_end = trim($match['1']);
		
			$this->entry_template = str_replace ($match['0'], LD.'temp_row_end'.RD, $this->entry_template);
		}
				
		
		/** -------------------------------------
		/**  Fetch the entry row
		/** -------------------------------------*/
		
		if (preg_match("/".LD."row".RD."(.+?)".LD.'\/'."row".RD."/s", $this->entry_template, $match))
		{
			$this->row = trim($match['1']);
		
			$this->entry_template = str_replace ($match['0'], LD.'temp_template'.RD, $this->entry_template);		
		}
		
		/** -------------------------------------
		/**  Fetch the blank row
		/** -------------------------------------*/
		
		if (preg_match("/".LD."row_blank".RD."(.+?)".LD.'\/'."row_blank".RD."/s", $this->entry_template, $match))
		{
			$this->blank_row = trim($match['1']);
		
			$this->entry_template = str_replace ($match['0'], LD.'temp_blank_row'.RD, $this->entry_template);		
		}
	}




	/** ---------------------------------
	/**  Parse Gallery Result
	/** ---------------------------------*/
	
	// NOTE:  The template is parsed in one of two ways
	// depending on whether the entry_id is present in the URL.
	// If it's not present, we assume

	function parse_gallery_entries()
	{
		$return 		= '';
		$template		= '';
		$current_row 	= 0;
		$total_rows		= 1;
		$switch			= array();
		
		$cat_name 		= '';
		$cat_desc 		= '';
		
		$this->max_columns -= 1;
		$total_results = count($this->query->result_array());
		
		foreach($this->query->result_array() as $key => $row)
		{
			$cat_name 				= $row['cat_name'];
			$cat_desc 				= $row['cat_description'];
			$row['count'] 			= $key+1;
			$row['total_results']	= $total_results;
			
			$row['thumb_width']	 = $row['t_width'];
			$row['thumb_height'] = $row['t_height'];
			
			$row['views'] = $row['views']+1;

			if ($this->one_entry === FALSE)
			{
				$tagdata	= $this->row;
				$template	= $this->entry_template;
			}
			else
			{
				$tagdata = $this->EE->TMPL->tagdata;
			}
			
			
			/* -------------------------------------------
			/* 'gallery_parse_entries_tagdata' hook.
			/*  - Allows parsing of contents in tagdata variable (full contents of tag)
			/*  - Added 1.4.2
			*/
				if ($this->EE->extensions->active_hook('gallery_parse_entries_tagdata') === TRUE)
				{
					$tagdata = $this->EE->extensions->call('gallery_parse_entries_tagdata', $row, $tagdata);
				}
			/*
			/* -------------------------------------------*/
			
			/* -------------------------------------------
			/* 'gallery_parse_entries_template' hook.
			/*  - Allows parsing of contents in template variable (if columns approach, each entry)
			/*  - Added 1.4.2
			*/
				if ($this->EE->extensions->active_hook('gallery_parse_entries_template') === TRUE)
				{
					$template = $this->EE->extensions->call('gallery_parse_entries_template', $row, $template);
				}
			/*
			/* -------------------------------------------*/
		
		

			/** ------------------------------------
			/**  Add {row_start} {/row_start} data
			/** ------------------------------------*/
			
			// On the very first cycle of each row we have to add the row_start data
			
			if ($this->one_entry === FALSE)
			{
				if ($current_row == 0)
				{
					$template = str_replace(LD.'temp_row_start'.RD,	$this->row_start, $template);
				}
				else
				{
					$template = str_replace(LD.'temp_row_start'.RD, '', $template);
				}
			}
			

			/** ----------------------------------------
			/**  Conditionals
			/** ----------------------------------------*/
			
			$cond = $row;
			$cond['logged_in']		= ($this->EE->session->userdata['member_id'] == 0) ? 'FALSE' : 'TRUE';
			$cond['logged_out']		= ($this->EE->session->userdata['member_id'] != 0) ? 'FALSE' : 'TRUE';
			$cond['allow_comments']	= ($row['allow_comments'] == 'n') ? 'FALSE' : 'TRUE';
			
			$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);
						
			/** ------------------------------------
			/**  Single Variables
			/** ------------------------------------*/
		
			foreach ($this->EE->TMPL->var_single as $key => $val)
			{
				/** ----------------------------------------
				/**  parse {switch} variable
				/** ----------------------------------------*/
				
				if (preg_match("/^switch\s*=.+/i", $key))
				{
					$sparam = $this->EE->functions->assign_parameters($key);
					
					$sw = '';
					
					if (isset($sparam['switch']))
					{
						$sopt = explode("|", $sparam['switch']);

						$sw = $sopt[($row['count']-1 + count($sopt)) % count($sopt)];
					}
					
					$tagdata = $this->EE->TMPL->swap_var_single($key, $sw, $tagdata);
				}

				/** ----------------------------
				/**  {cat_id}
				/** ----------------------------*/
				if ($key == "cat_id" OR $key == "category_id")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $row['cat_id'], $tagdata);
				}
			
				/** ----------------------------
				/**  {entry_id}
				/** ----------------------------*/
				
				if ($key == "entry_id")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $row['entry_id'], $tagdata);
				}
				
				
				/** ----------------------------
				/**  {id_path}
				/** ----------------------------*/
				
				if (strncmp($key, 'id_path', 7) == 0)
				{						  
					$tagdata = $this->EE->TMPL->swap_var_single(
														$key, 
														$this->EE->functions->create_url($this->EE->functions->extract_path($key).'/'.$row['entry_id']), 
														$tagdata
													 );
				}
				
				/** ----------------------------
				/**  {category_path}
				/** ----------------------------*/
				
				if (strncmp($key, 'category_path', 13) == 0)
				{						  
					$tagdata = $this->EE->TMPL->swap_var_single(
														$key, 
														$this->EE->functions->create_url($this->EE->functions->extract_path($key).'/C'.$row['cat_id']), 
														$tagdata
													 );
				}
				
				/** ----------------------------
				/**  {Views}
				/** ----------------------------*/
				
				if ($key == "views")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $row['views'], $tagdata);
				}
				
				/** ----------------------------
				/**  {filename}
				/** ----------------------------*/
				
				if ($key == "filename")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $row['filename'].$row['extension'], $tagdata);
				}
				
				/** ----------------------------
				/**  {image_url}
				/** ----------------------------*/
				
				if ($key == "image_url")
				{
					$url = $this->EE->functions->remove_double_slashes( $row['gallery_image_url'].'/'.$row['cat_folder'].'/'.$row['filename'].$row['extension']);
					$tagdata = $this->EE->TMPL->swap_var_single($val, $url, $tagdata);
				}
				
				/** ----------------------------
				/**  {thumb_url}
				/** ----------------------------*/
				
				if ($key == "thumb_url")
				{
					$url = $this->EE->functions->remove_double_slashes( $row['gallery_image_url'].'/'.$row['cat_folder'].'/'.$row['filename'].$row['gallery_thumb_prefix'].$row['extension']);
					$tagdata = $this->EE->TMPL->swap_var_single($val, $url, $tagdata);
				}
				
				/** ----------------------------
				/**  {medium_url}
				/** ----------------------------*/
				
				if ($key == "medium_url")
				{
					$url = $this->EE->functions->remove_double_slashes($row['gallery_image_url'].'/'.$row['cat_folder'].'/'.$row['filename'].$row['gallery_medium_prefix'].$row['extension']);
					$tagdata = $this->EE->TMPL->swap_var_single($val, $url, $tagdata);
				}
				
				/** ------------------------------
				/**  {thumb_width}  {thumb_height}
				/** ------------------------------*/
				
				if ($key == "thumb_width")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $row['t_width'], $tagdata);
				}
				if ($key == "thumb_height")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $row['t_height'], $tagdata);
				}
				/** ------------------------------
				/**  {medium_width}  {medium_height}
				/** ------------------------------*/
				
				if ($key == "medium_width")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $row['m_width'], $tagdata);
				}
				if ($key == "medium_height")
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, $row['m_height'], $tagdata);
				}
				
				/** ----------------------------
				/**  {caption}
				/** ----------------------------*/
				
				if ($key == "caption")
				{
					$caption = $this->EE->typography->parse_type( 
														$row['caption'], 
														array(
																'text_format'	=> $row['gallery_text_formatting'],
																'html_format'	=> 'safe',
																'auto_links'	=> $row['gallery_auto_link_urls'],
																'allow_img_url' => 'n'
															)
													 );
	
					$tagdata = $this->EE->TMPL->swap_var_single($key, $caption, $tagdata);  
				}

				/** ----------------------------
				/**  parse custom fields
				/** ----------------------------*/
				foreach (array('one', 'two', 'three', 'four', 'five', 'six') as $cfval)
				{
					if ($key == "custom_field_".$cfval)
					{
						$cf = $this->EE->typography->parse_type( 
														$row['custom_field_'.$cfval], 
														array(
																'text_format'	=> $row['gallery_cf_'.$cfval.'_formatting'],
																'html_format'	=> 'safe',
																'auto_links'	=> $row['gallery_cf_'.$cfval.'_auto_link'],
																'allow_img_url' => 'n'
															)
													 );
		
						$tagdata = $this->EE->TMPL->swap_var_single($key, $cf, $tagdata);  
					}
				}

					
				/** ----------------------------
				/**  parse basic fields
				/** ----------------------------*/
				 
				if (isset($row[$val]))
				{					
					$tagdata  = $this->EE->TMPL->swap_var_single($val, $row[$val], $tagdata);
					$template = $this->EE->TMPL->swap_var_single($val, $row[$val], $template);
				}
			}
			// END SINGLE VARS
			
			$tagdata = str_replace(LD.'category'.RD, $cat_name, $tagdata);
			$tagdata = str_replace(LD.'category_description'.RD, $cat_desc, $tagdata);
			
			
			/** ------------------------------------
			/**  Parse Date Variables
			/** ------------------------------------*/
			if (count($this->entry_date) > 0)
			{
				foreach($this->entry_date as $orig_tag => $date_array)
				{
					if ($row['entry_date'] == 0)
					{
						$tagdata = str_replace(LD.$orig_tag.RD, '', $tagdata);
						continue;
					}
										
					foreach($date_array['1'] as $el)
					{
						$date_array['0'] = str_replace($el, $this->EE->localize->convert_timestamp($el, $row['entry_date'], TRUE), $date_array['0']);
					}
					
					$tagdata = str_replace(LD.$orig_tag.RD, $date_array['0'], $tagdata);					
				}
			}
		
			if (count($this->recent_comment_date) > 0)
			{
				foreach($this->recent_comment_date as $orig_tag => $date_array)
				{
					if ($row['recent_comment_date'] == 0)
					{
						$tagdata = str_replace(LD.$orig_tag.RD, '', $tagdata);
						continue;
					}
										
					foreach($date_array['1'] as $el)
					{
						$date_array['0'] = str_replace($el, $this->EE->localize->convert_timestamp($el, $row['recent_comment_date'], TRUE), $date_array['0']);
					}
					
					$tagdata = str_replace(LD.$orig_tag.RD, $date_array['0'], $tagdata);					
				}
			}
			
			
			/** ------------------------------------
			/**  Add {row_end} {/row_end} data
			/** ------------------------------------*/
			if ($this->one_entry === FALSE)
			{
				if ($current_row == $this->max_columns)
				{
					$template = str_replace(LD.'temp_row_end'.RD, $this->row_end, $template);
					$template = str_replace(LD.'temp_blank_row'.RD, '', $template);
	
					$current_row = 0;
				}
				else
				{				
					if ($this->query->num_rows() != $total_rows)
					{
						$template = str_replace(LD.'temp_row_end'.RD, '', $template);
						$template = str_replace(LD.'temp_blank_row'.RD, '', $template);
					}
					
					$current_row++;
				}
				
				$return .= str_replace(LD.'temp_template'.RD, $tagdata, $template);
				$total_rows++;
			}
			else
			{
				$return .= $tagdata;
			}
			
		}
		// END FOREACH
		
		
		/** ------------------------------------
		/**  Add {row_end} {/row_end} data
		/** ------------------------------------*/
		
		// If the loop ended before we reached our max
		// we need to add the row_end
		
		// But... we might need to add some blank cells
		
		if ($this->one_entry === FALSE)
		{
			$blank = '';
			
			if (($current_row <= $this->max_columns) AND $this->max_rows > 1)
			{		
				for ($i=$current_row; $i<=$this->max_columns; $i++)
				{
					$blank .= $this->blank_row;
				}
			}
			
			$return = str_replace(LD.'temp_blank_row'.RD, $blank, $return);
			$return = str_replace(LD.'temp_row_end'.RD, $this->row_end, $return);
					
			$this->return_data  = str_replace (LD.'entry_template'.RD, $return, $this->EE->TMPL->tagdata);
		}
		else
		{
			$this->return_data = $return;
		}
		

		$this->return_data = str_replace(LD.'category'.RD, $cat_name, $this->return_data);
		$this->return_data = str_replace(LD.'category_description'.RD, $cat_desc, $this->return_data);
		
		return TRUE;
	}






	/** -------------------------------------
	/**  Categories
	/** -------------------------------------*/
	function categories()
	{
		if ( ! $gallery = $this->EE->TMPL->fetch_param('gallery'))
		{ 
			return '';
		}

		$sql = "SELECT gallery_id, gallery_sort_order FROM exp_galleries ";
	
		$sql .= "WHERE gallery_short_name = '".$this->EE->db->escape_str($gallery)."'";


		$query = $this->EE->db->query($sql);
		
		if ($query->num_rows() == "")
		{
			return '';
		}
		
		$gallery_id = $query->row('gallery_id') ;
			$sort_order = $query->row('gallery_sort_order') ;
	
		/** -------------------------------------
		/**  Pre-process all path variables
		/** -------------------------------------*/
		
		if (preg_match_all("#".LD."category_path=(.+?)".RD."#", $this->EE->TMPL->tagdata, $matches))
		{					
			foreach ($matches['1'] as $match)
			{				
				$this->EE->TMPL->tagdata = preg_replace("#".LD."category_path=.+?".RD."#", $this->EE->functions->create_url($match).'C{cat_id}/', $this->EE->TMPL->tagdata, 1);
			}
		}
		
		/** ----------------------------------------
		/**  Fetch all the date-related variables
		/** ----------------------------------------*/
				
		if (preg_match_all("/".LD."recent_entry_date\s+format=[\"'](.*?)[\"']".RD."/s", $this->EE->TMPL->tagdata, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{			
				$matches['0'][$j] = str_replace(LD, '', $matches['0'][$j]);
				$matches['0'][$j] = str_replace(RD, '', $matches['0'][$j]);
												
				$this->recent_entry_date[$matches['0'][$j]] = array($matches['1'][$j], $this->EE->localize->fetch_date_params($matches['1'][$j]));					
			}
		}
		
		/** -------------------------------------
		/**  Fetch the category "chunk"
		/** -------------------------------------*/
		
		// This needs to be the first preg_match!
		
		// It extracts the chunk that will repeat with eacn result row
		
		if (preg_match("/".LD."category_row".RD."(.+?)".LD.'\/'."category_row".RD."/s", $this->EE->TMPL->tagdata, $match))
		{
			$this->cat_template = trim($match['1']);
		
			$this->EE->TMPL->tagdata = str_replace ($match['0'], LD.'cat_template'.RD, $this->EE->TMPL->tagdata);		
		}
		
		/** -------------------------------------
		/**  Fetch the sub-cat "chunk"
		/** -------------------------------------*/
		
		if (preg_match("/".LD."subcategory_row".RD."(.+?)".LD.'\/'."subcategory_row".RD."/s", $this->EE->TMPL->tagdata, $match))
		{
			$this->subcat_template = trim($match['1']);
		
			$this->EE->TMPL->tagdata = str_replace ($match['0'], '', $this->EE->TMPL->tagdata);		
		}	
		
		/** -------------------------------------
		/**  Fetch the {row_start} data
		/** -------------------------------------*/
		
		// We'll replace it with a temporary marker that
		// we can itentify with a str_replace later
		
		if (preg_match("/".LD."row_start".RD."(.+?)".LD.'\/'."row_start".RD."/s", $this->cat_template, $match))
		{
			$this->cat_row_start	 = trim($match['1']);
		
			$this->cat_template = str_replace ($match['0'], LD.'temp_row_start'.RD, $this->cat_template);
		}
		
		/** -------------------------------------
		/**  Fetch the {row_end} data
		/** -------------------------------------*/
		if (preg_match("/".LD."row_end".RD."(.+?)".LD.'\/'."row_end".RD."/s", $this->cat_template, $match))
		{
			$this->cat_row_end = trim($match['1']);
		
			$this->cat_template = str_replace ($match['0'], LD.'temp_row_end'.RD, $this->cat_template);
		}
				
		/** -------------------------------------
		/**  Fetch the subcat {row_start} data
		/** -------------------------------------*/
		
		if (preg_match("/".LD."row_start".RD."(.+?)".LD.'\/'."row_start".RD."/s", $this->subcat_template, $match))
		{
			$this->subcat_row_start = trim($match['1']);
		
			$this->subcat_template = str_replace ($match['0'], LD.'temp_row_start'.RD, $this->subcat_template);
		}
		
		/** -------------------------------------
		/**  Fetch the subcat {row_end} data
		/** -------------------------------------*/
		if (preg_match("/".LD."row_end".RD."(.+?)".LD.'\/'."row_end".RD."/s", $this->subcat_template, $match))
		{
			$this->subcat_row_end = trim($match['1']);
		
			$this->subcat_template = str_replace ($match['0'], LD.'temp_row_end'.RD, $this->subcat_template);
		}
		
		/** -------------------------------------
		/**  Fetch the {subcat_marker} marker
		/** -------------------------------------*/
		
		if (preg_match("/".LD."subcat_marker".RD."(.+?)".LD.'\/'."subcat_marker".RD."/s", $this->subcat_template, $match))
		{
			$this->subcat_marker = trim($match['1']);
		
			$this->subcat_template = str_replace ($match['0'], LD.'subcat_marker'.RD, $this->subcat_template);
		}
		
		if (preg_match("/".LD."subcat_marker".RD."(.+?)".LD.'\/'."subcat_marker".RD."/s", $this->cat_template, $match))
		{
			$this->subcat_marker = trim($match['1']);
		
			$this->cat_template = str_replace ($match['0'], LD.'subcat_marker'.RD, $this->cat_template);		
		}
		
		/** -------------------------------------
		/**  Fetch the category row
		/** -------------------------------------*/
				
		if (preg_match("/".LD."row".RD."(.+?)".LD.'\/'."row".RD."/s", $this->cat_template, $match))
		{
			$this->cat_row = trim($match['1']);
		
			$this->cat_template = str_replace ($match['0'], LD.'cat_row'.RD, $this->cat_template);		
		}
		
		/** -------------------------------------
		/**  Fetch the sub-cat row
		/** -------------------------------------*/
		
		if (preg_match("/".LD."row".RD."(.+?)".LD.'\/'."row".RD."/s", $this->subcat_template, $match))
		{
			$this->subcat_row = trim($match['1']);
		
			$this->subcat_template = str_replace ($match['0'], LD.'cat_row'.RD, $this->subcat_template);		
		}		

		/** ------------------------------
		/**  Fetch the category data
		/** ------------------------------*/
				
		$sql = "SELECT cat_name, cat_description, cat_id, parent_id, total_files, total_views, total_comments, recent_entry_date FROM exp_gallery_categories WHERE gallery_id = '{$gallery_id}' ";
							 
		$sql .= ($sort_order == 'a') ? "ORDER BY parent_id, cat_name" : "ORDER BY parent_id, cat_order";
							 
		$query = $this->EE->db->query($sql);
			  
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}	 

		/** ------------------------------
		/**  Assign the result to an array
		/** ------------------------------*/
					
		foreach($query->result_array() as $row)
		{		
			$cat_array[$row['cat_id']]  = array($row['parent_id'], $row['cat_name'], $row['cat_description'], $row['total_files'], $row['recent_entry_date'], $row['total_views'], $row['total_comments']);
		}
		
		/** ------------------------------
		/**  Build the Category array
		/** ------------------------------*/
		
		$this->category_tree($cat_array);
		
		$out = '';
		
		foreach ($this->categories as $val)
		{
			$out .= $val;
		}		
		
		$out = str_replace(LD.'cp_image_dir'.RD,  PATH_CP_GBL_IMG, $out);		
		
		return str_replace(LD.'cat_template'.RD, $out, $this->EE->TMPL->tagdata);
			
	}



	
	/** ------------------------------
	/**  Category tree
	/** ------------------------------*/
	function category_tree($cat_array)
	{
		foreach($cat_array as $key => $val) 
		{		
			if (0 == $val['0']) 
			{
				$template = $this->cat_template;
					
				$template = str_replace(LD.'temp_row_start'.RD,	$this->cat_row_start,	$template);
				$template = str_replace(LD.'temp_row_end'.RD,		$this->cat_row_end,		$template);
										
				$swap = array(
									'cat_id'					=>	$key,
									'category_id'				=>	$key,
									'category'					=>	$val['1'],
									'category_description'		=>	$val['2'],
									'total_files'				=>	$val['3'],
									'total_comments'			=>	$val['6'],
									'subcat_marker'				=>	'',
									'total_views'				=>	$val['5']
								);
					
					
				$row = $this->cat_row;
					
				/** ----------------------------------------
				/**  Conditionals
				/** ----------------------------------------*/
			
				$row = $this->EE->functions->prep_conditionals($row, $swap);
					
				/** ----------------------------------------
				/**  Single Variables
				/** ----------------------------------------*/
				
				foreach ($swap as $k => $v)
				{
					$row = str_replace(LD.$k.RD,	$v, $row);
				}
					
				/** ------------------------------
				/**  Date variables
				/** ------------------------------*/
				
				if (count($this->recent_entry_date) > 0)
				{
					foreach($this->recent_entry_date as $orig_tag => $date_array)
					{
						if ($val['4'] == 0)
						{
							$row = str_replace(LD.$orig_tag.RD, '', $row);
							continue;
						}
											
						foreach($date_array['1'] as $el)
						{
							$date_array['0'] = str_replace($el, $this->EE->localize->convert_timestamp($el, $val['4'], TRUE), $date_array['0']);
						}
						
						$row = str_replace(LD.$orig_tag.RD, $date_array['0'], $row);					
						}
				}
									
				$this->categories[] = str_replace(LD.'cat_row'.RD, $row, $template);
				
				$this->category_subtree($key, $cat_array, $depth=0);
			}
		} 
	}

	
	
	
	/** --------------------------------------
	/**  Category sub-tree
	/** --------------------------------------*/
		
	function category_subtree($cat_id, $cat_array, $depth)
	{
		$spacer = '&nbsp;';
			
		if ($depth == 0)	
		{
			$depth = 1;
		}
		else 
		{	
			$depth = $depth + 1;
			$spacer = str_repeat($spacer, $depth + 4);
		}
				
		$indent = $spacer.$this->subcat_marker;

		foreach ($cat_array as $key => $val) 
		{				
			if ($cat_id == $val['0']) 
			{		 
					$template = ($this->subcat_template == '') ? $this->cat_template : $this->subcat_template;
					
				$template = str_replace(LD.'temp_row_start'.RD,	$this->cat_row_start,	$template);
				$template = str_replace(LD.'temp_row_end'.RD,	$this->cat_row_end,		$template);
										
				$swap = array(
									'cat_id'					=>	$key,
									'category_id'				=>	$key,
									'category'					=>	$val['1'],
									'category_description'		=>	$val['2'],
									'total_files'				=>	$val['3'],
									'subcat_marker'				=>	$indent,
									'total_views'				=>	$val['5'],
									'total_comments'			=>	$val['6']
								);
					
					
				$row = ($this->subcat_row == '') ? $this->cat_row : $this->subcat_row;
					
				/** ----------------------------------------
				/**  Conditionals
				/** ----------------------------------------*/
				
				$row = $this->EE->functions->prep_conditionals($row, $swap);
					
				/** ----------------------------------------
				/**  Single Variables
				/** ----------------------------------------*/
				
				foreach ($swap as $k => $v)
				{
					$row = str_replace(LD.$k.RD,	$v, $row);
				}
					
				/** ------------------------------
				/**  Date variables
				/** ------------------------------*/
				
				if (count($this->recent_entry_date) > 0)
				{
					foreach($this->recent_entry_date as $orig_tag => $date_array)
					{
						if ($val['4'] == 0)
						{
							$row = str_replace(LD.$orig_tag.RD, '', $row);
							continue;
						}
											
						foreach($date_array['1'] as $el)
						{
							$date_array['0'] = str_replace($el, $this->EE->localize->convert_timestamp($el, $val['4'], TRUE), $date_array['0']);
						}
						
						$row = str_replace(LD.$orig_tag.RD, $date_array['0'], $row);					
						}
				}
									
				$this->categories[] = str_replace(LD.'cat_row'.RD, $row, $template);
											
				$this->category_subtree($key, $cat_array, $depth);	
			}
		}
	}



	/** ----------------------------------------
	/**  Gallery "next entry" link
	/** ----------------------------------------*/
	function next_entry()
	{
		return $this->next_prev_parse('next');
	}



	/** ----------------------------------------
	/**  Channel "previous entry" link
	/** ----------------------------------------*/
	function prev_entry()
	{
		return $this->next_prev_parse('prev');
	}



	/** ----------------------------------------
	/**  Parse next/prev tags
	/** ----------------------------------------*/
	function next_prev_parse($type)
	{
		if (FALSE == ($sql = $this->next_prev_query($type)))
		{
			return '';
		}
						
		$query = $this->EE->db->query($sql);
		
		if ($query->num_rows() == 0)
		{
			$type  = ($type == 'next') ? 'prev' : 'next';
	
			$sql = $this->next_prev_query($type, 'cycle');
			
			$query = $this->EE->db->query($sql);
			
			if ($query->num_rows() == 0)
			{
				return '';
			}
		}
		
		$path = (preg_match("#".LD."path=(.+?)".RD."#", $this->EE->TMPL->tagdata, $match)) ? $this->EE->functions->create_url($match['1']) : $this->EE->functions->create_url("SITE_INDEX");
		
		$path .= '/'.$query->row('entry_id') .'/';
		
		$this->EE->TMPL->tagdata = preg_replace("#".LD."path=.+?".RD."#", $path, $this->EE->TMPL->tagdata);	
		$this->EE->TMPL->tagdata = preg_replace("#".LD."title".RD."#", preg_quote($query->row('title') ), $this->EE->TMPL->tagdata);	
		$this->EE->TMPL->tagdata = str_replace(LD.'entry_id'.RD, $query->row('entry_id') , $this->EE->TMPL->tagdata);

		return $this->EE->functions->remove_double_slashes(stripslashes($this->EE->TMPL->tagdata));
	}



	/** ----------------------------------------
	/**  Builds the query for the next/prev links
	/** ----------------------------------------*/
	function next_prev_query($type = 'next', $cycle = '')
	{
		if ($this->EE->uri->query_string == '')
		{
			return FALSE;
		}
		
		$qstring = $this->EE->uri->query_string;
		
		/** --------------------------------------
		/**  Remove page number 
		/** --------------------------------------*/
		
		if (preg_match("#/P\d+#", $qstring, $match))
		{			
			$qstring = $this->EE->functions->remove_double_slashes(str_replace($match['0'], '', $qstring));
		}
		
		/** --------------------------------------
		/**  Remove "N" 
		/** --------------------------------------*/
		if (preg_match("#/N(\d+)#", $qstring, $match))
		{			
			$qstring = $this->EE->functions->remove_double_slashes(str_replace($match['0'], '', $qstring));
		}
		
		$timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->localize->set_gmt($this->EE->TMPL->cache_timestamp) : $this->EE->localize->now;
		
		$xsql = ($this->EE->TMPL->fetch_param('show_all') == 'y') ? '' : "AND t1.cat_id = t2.cat_id";
			
		$sql = "SELECT t1.entry_id, t1.title, t1.entry_date, t2.entry_date AS test_date 
				FROM exp_gallery_entries t1, exp_gallery_entries t2
				{$xsql}
				AND t1.entry_id != '".$this->EE->db->escape_str($qstring)."' 
				AND t2.entry_id  = '".$this->EE->db->escape_str($qstring)."'
				AND t1.entry_date < ".$timestamp;
	
		if ($cycle == '')
		{
			if ($type == 'next')	
				$sql .= " AND t1.entry_date >= t2.entry_date ";
			else
				$sql .= " AND t1.entry_date <= t2.entry_date ";
		}
		
		if ( ! $gallery = $this->EE->TMPL->fetch_param('gallery'))
		{
			return FALSE;
		}
		
		$sql .= $this->EE->functions->sql_andor_string($gallery, 'gallery_short_name', 'exp_galleries');
		
		$sql .= " AND t1.status = 'o' ";
		
		
		if ($cycle == '')
			$order = ($type == 'next') ? 'asc' : 'desc';
		else
			$order = ($type == 'next') ? 'desc' : 'asc';

		$sql .= " ORDER BY t1.entry_date ".$order."";
		
		if ($cycle != '')
		{
			$sql .= ($type == 'next') ? ', t1.entry_id DESC' : ', t1.entry_id ASC';
		}
		else
		{
			$sql .= ($type == 'next') ? ', t1.entry_id ASC' : ', t1.entry_id DESC';
			
			$query = $this->EE->db->query($sql);
			
			$now_valid	= array();
			$later_valid = array();
			$sort = FALSE;
			
			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					if ($type == 'next')
					{
						if ($row['test_date'] == $row['entry_date'])
						{
							if ($qstring < $row['entry_id'])
							{
								$now_valid[] = $row['entry_id'];
								$sort = TRUE;
							}
						}
						else
						{
							$later_valid[] = $row['entry_id'];
						}
					}
					else
					{
						if ($row['test_date'] == $row['entry_date'])
						{
							if ($qstring > $row['entry_id'])
							{
								$now_valid[] = $row['entry_id'];
								$sort = TRUE;
							}
						}
						else
						{
							$later_valid[] = $row['entry_id'];
						}
					}
				}
				
				$valid = (count($now_valid) > 0) ? $now_valid : $later_valid;
				
				if (count($valid) > 0)
				{
					if ($sort === TRUE)
					{
						if ($type == 'next')
						{
							sort($valid); // lowest id
						}
						else
						{
							rsort($valid); // greatest id
						}
					}
					
					$sql = "SELECT t1.entry_id, t1.title, t1.entry_date 
							FROM exp_gallery_entries t1 
							WHERE t1.entry_id = '".$this->EE->db->escape_str(array_shift($valid))."'";
				}
				else
				{
					// Cycle of Time
					$type = ($type == 'next') ? 'prev' : 'next';
					return $this->next_prev_query($type, 'cycle');
				}
			}	
		}
		
	
		return $sql;
	}



	/** ----------------------------------------
	/**  Comments
	/** ----------------------------------------*/
	function comments()
	{
		// Base variables
		
		$return 			= '';
		$current_page		= '';
		$qstring			= $this->EE->uri->query_string;
		$uristr				= $this->EE->uri->uri_string;
		$switch 			= array();
		
		// Pagination variables
		
		$paginate			= FALSE;
		$paginate_data		= '';
		$pagination_links	= '';
		$page_next			= '';
		$page_previous		= '';
		$current_page		= 0;
		$t_current_page		= '';
		$total_pages		= 1;
		$search_link		= '';
		$total_rows 		= 0;

		$dynamic = ($this->EE->TMPL->fetch_param('dynamic') == 'off') ? FALSE : TRUE;

		/** --------------------------------------
		/**  Parse page number
		/** --------------------------------------*/
		
		// We need to strip the page number from the URL for two reasons:
		// 1. So we can create pagination links
		// 2. So it won't confuse the query with an improper proper ID
				
		if ( ! $dynamic)
		{
			if (preg_match("#N(\d+)#", $qstring, $match) OR preg_match("#/N(\d+)#", $qstring, $match))
			{
				$current_page = $match['1'];	
				$uristr  = $this->EE->functions->remove_double_slashes(str_replace($match['0'], '', $uristr));
			}		
		}
		else
		{
			if (preg_match("#/P(\d+)#", $qstring, $match))
			{
				$current_page = $match['1'];	
				
				$uristr  = $this->EE->functions->remove_double_slashes(str_replace($match['0'], '', $uristr));
				$qstring = $this->EE->functions->remove_double_slashes(str_replace($match['0'], '', $qstring));
			}
					
			$entry_id = trim($qstring);
			 
			// If there is a slash in the entry ID we'll kill everything after it.
			
			$entry_id = preg_replace("#/.+#", "", $entry_id);
			
			// No entry ID?  Bail..
			
			if ($entry_id == '' OR ! is_numeric($entry_id))
				return;
			
			/** ----------------------------------------
			/**  Do we have a vaild entry ID number?
			/** ----------------------------------------*/
			
			$timestamp = ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->localize->set_gmt($this->EE->TMPL->cache_timestamp) : $this->EE->localize->now;
					
			$sql = "SELECT exp_gallery_entries.entry_id, exp_gallery_entries.gallery_id 
					FROM exp_gallery_entries
					WHERE exp_gallery_entries.entry_id = '{$entry_id}' 
					AND exp_gallery_entries.status = 'o' ";
					
			if ($this->EE->TMPL->fetch_param('show_future_entries') != 'yes')
			{
				$sql .= " AND exp_gallery_entries.entry_date < ".$timestamp." ";
			}
							
			$query = $this->EE->db->query($sql);
			
			// Bad ID?  See ya!
			
			if ($query->num_rows() == 0)
			{
					return FALSE;
			}
			unset($sql);

		}


		
		// If the comment tag is being used in freeform mode
		// we need to fetch the channel ID numbers
		
		$w_sql = '';
		
		if ( ! $dynamic)
		{		
			if ($gallery = $this->EE->TMPL->fetch_param('gallery'))
			{
				$xql = "SELECT gallery_id FROM exp_galleries WHERE ";
			
				$str = $this->EE->functions->sql_andor_string($gallery, 'gallery_short_name');
				
				if (substr($str, 0, 3) == 'AND')
					$str = substr($str, 3);
				
				$xql .= $str;			
					
				$query = $this->EE->db->query($xql);
				
				if ($query->num_rows() == 0)
				{
					return $this->EE->TMPL->no_results();
				}
				else
				{
					if ($query->num_rows() == 1)
					{
						$w_sql .= "AND gallery_id = '".$query->row('gallery_id') ."' ";
					}
					else
					{
						$w_sql .= "AND (";
						
						foreach ($query->result_array() as $row)
						{
							$w_sql .= "gallery_id = '".$row['gallery_id']."' OR ";
						}
						
						$w_sql = substr($w_sql, 0, - 3);
						
						$w_sql .= ") ";
					}
				}
			}
		}




		/** ----------------------------------------
		/**  Set sorting and limiting
		/** ----------------------------------------*/
		
		if ( ! $dynamic)
		{
			$limit = ( ! $this->EE->TMPL->fetch_param('limit')) ? $this->limit : $this->EE->TMPL->fetch_param('limit');
			$sort  = ( ! $this->EE->TMPL->fetch_param('sort'))  ? 'desc' : $this->EE->TMPL->fetch_param('sort');
		}
		else
		{
			$limit = ( ! $this->EE->TMPL->fetch_param('limit')) ? 100 : $this->EE->TMPL->fetch_param('limit');
			$sort  = ( ! $this->EE->TMPL->fetch_param('sort'))  ? 'asc' : $this->EE->TMPL->fetch_param('sort');
		}
		

		/** ----------------------------------------
		/**  Fetch comment ID numbers
		/** ----------------------------------------*/
		$lsql  = " LIMIT ".$limit;
		
		if ( ! $dynamic)
		{
			// Left this here for backward compatibility
			// We need to deprecate the "order_by" parameter
		
			if ($this->EE->TMPL->fetch_param('orderby') != '')
			{
				$order_by = $this->EE->TMPL->fetch_param('orderby');
			}
			else
			{
				$order_by = $this->EE->TMPL->fetch_param('order_by');
			}		
		
			$order_by  = ($order_by == '' OR $order_by == 'date')  ? 'comment_date' : $order_by;
		
			$sql = "SELECT comment_id FROM exp_gallery_comments WHERE status = 'o' ".$w_sql." ORDER BY ".$order_by." ".$sort;		
		}
		else
		{
			$order_by = 'comment_date';
			
			$sql = "SELECT comment_id 
					FROM exp_gallery_comments 
					WHERE entry_id = '$entry_id' 
					AND status = 'o' 
					ORDER BY comment_date ".$sort;
		}
			
		$query = $this->EE->db->query($sql);
				
		if ($query->num_rows() == 0)
		{
			return $this->EE->TMPL->no_results();
		}

		$total_rows = $query->num_rows();
		
		
		/** ---------------------------------
		/**  Do we need pagination?
		/** ---------------------------------*/
				
		if (preg_match("/".LD."paginate".RD."(.+?)".LD.'\/'."paginate".RD."/s", $this->EE->TMPL->tagdata, $match))
		{
			$paginate		= TRUE;
			$paginate_data	= $match['1'];
		
			$this->EE->TMPL->tagdata = preg_replace("/".LD."paginate".RD.".+?".LD.'\/'."paginate".RD."/s", "", $this->EE->TMPL->tagdata);
						
			$current_page = ($current_page == '' OR ($limit > 1 AND $current_page == 1)) ? 0 : $current_page;
			
			if ($current_page > $query->num_rows)
			{
				$current_page = 0;
			}
						
			$t_current_page = floor(($current_page / $limit) + 1);
			$total_pages	= intval(floor($query->num_rows() / $limit));
			
			if ($query->num_rows() % $limit) 
				$total_pages++;
			
			if ($query->num_rows() > $limit)
			{
				if ( ! class_exists('Paginate'))
				{
					require APPPATH.'_to_be_replaced/lib.paginate'.EXT;
				}
				
				$PGR = new Paginate();

				$basepath = $this->EE->functions->create_url($uristr, 1, 0);
				
				$first_url = (substr($basepath, -5) == '.php/') ? substr($basepath, 0, -1) : $basepath;
				
				$PGR->first_url 	= $first_url;
				$PGR->path			= $basepath;
				$PGR->prefix		= ( ! $dynamic) ? 'N' : 'P';
				$PGR->total_count 	= $query->num_rows;
				$PGR->per_page		= $limit;
				$PGR->cur_page		= $current_page;
				
				$pagination_links = $PGR->show_links();
				
				if ((($total_pages * $limit) - $limit) > $current_page)
				{
					$page_next = $basepath.'P'.($current_page + $limit).'/';
				}
				
				if (($current_page - $limit ) >= 0) 
				{						
					$page_previous = $basepath.'P'.($current_page - $limit).'/';
				}
			}
			else
			{
				$current_page = '';
			}
		}


		if (is_numeric($current_page))
		{		
			$sql .= " LIMIT ".$current_page.', '.$limit;
			
			$query = $this->EE->db->query($sql);
		}
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->TMPL->no_results();
		}

		/** -----------------------------------
		/**  Build Final Query
		/** -----------------------------------*/
		
		$result_path = (preg_match("/".LD."member_search_path\s*=(.*?)".RD."/s", $this->EE->TMPL->tagdata, $match)) ? $match['1'] : 'search/results';
		$result_path = str_replace("\"", "", $result_path);
		$result_path = str_replace("'",  "", $result_path);
	
		$search_link = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Search', 'do_search').'&amp;result_path='.$result_path.'&amp;fetch_posts_by=';
	
		$sql = "SELECT 
				exp_gallery_comments.comment_id, exp_gallery_comments.entry_id, exp_gallery_comments.gallery_id, exp_gallery_comments.author_id, exp_gallery_comments.name, exp_gallery_comments.email, exp_gallery_comments.url, exp_gallery_comments.location as c_location, exp_gallery_comments.ip_address, exp_gallery_comments.comment_date, exp_gallery_comments.edit_date, exp_gallery_comments.comment, exp_gallery_comments.notify,
				exp_members.location, exp_members.interests, exp_members.aol_im, exp_members.yahoo_im, exp_members.msn_im, exp_members.icq, exp_members.group_id, exp_members.member_id, exp_members.signature, exp_members.sig_img_filename, exp_members.sig_img_width, exp_members.sig_img_height, exp_members.avatar_filename, exp_members.avatar_width, exp_members.avatar_height, exp_members.photo_filename, exp_members.photo_width, exp_members.photo_height,
				exp_member_data.*,
				exp_gallery_entries.title,
				exp_galleries.gallery_comment_text_formatting, exp_galleries.gallery_comment_html_formatting, exp_galleries.gallery_comment_allow_img_urls, exp_galleries.gallery_comment_auto_link_urls 
				FROM exp_gallery_comments 
				LEFT JOIN exp_galleries ON exp_gallery_comments.gallery_id = exp_galleries.gallery_id 
				LEFT JOIN exp_gallery_entries ON exp_gallery_comments.entry_id = exp_gallery_entries.entry_id 
				LEFT JOIN exp_members ON exp_members.member_id = exp_gallery_comments.author_id 
				LEFT JOIN exp_member_data ON exp_member_data.member_id = exp_members.member_id
				WHERE exp_gallery_comments.comment_id  IN (";
				
		foreach ($query->result_array() as $row)
		{
			$sql .= $row['comment_id'].',';
		}
		
		$sql = substr($sql, 0, -1).")";
		
		$order_by = ($order_by == '' OR $order_by == 'date')  ? 'comment_date' : $order_by;
		
		$sql .= " ORDER BY ".$order_by." ".$sort;	
		
		
		$query = $this->EE->db->query($sql);
		
		
		/** ----------------------------------------
		/**  Fetch custom member field IDs
		/** ----------------------------------------*/
	
		$result = $this->EE->db->query("SELECT m_field_id, m_field_name FROM exp_member_fields");
				
		$mfields = array();
		
		if ($result->num_rows() > 0)
		{
			foreach ($result->result_array() as $row)
			{				
				$mfields[$row['m_field_name']] = $row['m_field_id'];
			}
		}
						

		
		/** ----------------------------------------
		/**  Instantiate Typography class
		/** ----------------------------------------*/
	  
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		$this->EE->typography->parse_images = FALSE;
		
		/** ----------------------------------------
		/**  Fetch all the date-related variables
		/** ----------------------------------------*/
		
		// We do this here to avoid processing cycles in the foreach loop
		
		$date_vars = array('comment_date');
		
		$comment_date = array();

		foreach ($date_vars as $val)
		{					
			if (preg_match_all("/".LD.$val."\s+format=[\"'](.*?)[\"']".RD."/s", $this->EE->TMPL->tagdata, $matches))
			{
				for ($j = 0; $j < count($matches['0']); $j++)
				{
					$matches['0'][$j] = str_replace(LD, '', $matches['0'][$j]);
					$matches['0'][$j] = str_replace(RD, '', $matches['0'][$j]);
					
					switch ($val)
					{
						case 'comment_date' 	: $comment_date[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
							break;
					}
				}
			}
		}
		
		
		
		/** ----------------------------------------
        /**  Protected Variables for Cleanup Routine
        /** ----------------------------------------*/
		
		// Since comments do not necessarily require registration, and since
		// you are allowed to put member variables in comments, we need to kill
		// left-over unparsed junk.  The $member_vars array is all of those
		// member related variables that should be removed.
		
		$member_vars = array('location', 'interests', 'aol_im', 'yahoo_im', 'msn_im', 'icq', 
							 'signature', 'sig_img_filename', 'sig_img_width', 'sig_img_height', 
							 'avatar_filename', 'avatar_width', 'avatar_height', 
							 'photo_filename', 'photo_width', 'photo_height');
							 
		$member_cond_vars = array();
		
		foreach($member_vars as $var)
		{
			$member_cond_vars[$var] = '';
		}
                		
		/** ----------------------------------------
		/**  Start the processing loop
		/** ----------------------------------------*/
		
		$item_count = 0;
        $relative_count = 0;
        $absolute_count = ($current_page == '') ? 0 : $current_page;		
		$total_results = count($query->result_array());
		
		foreach ($query->result_array() as $key => $row)
		{	
        	if ( ! is_array($row))
        		continue;
        		
        	$relative_count++;
        	$absolute_count++;
        	$row['count']			= $relative_count;
            $row['absolute_count']	= $absolute_count;
            $row['total_comments']	= $total_rows;
            $row['total_results']	= $total_results;
        
        	// This lets the {if location} variable work
        	
			if (isset($row['author_id']))
			{
				if ($row['author_id'] == 0)
					$row['location'] = $row['c_location'];
			}
		
			$tagdata = $this->EE->TMPL->tagdata;	 
			
			/** ----------------------------------------
			/**  Conditionals
			/** ----------------------------------------*/
			
			$cond = $row;
			$cond['logged_in']			= ($this->EE->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
			$cond['logged_out']			= ($this->EE->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
			$cond['allow_comments'] 	= (isset($row['allow_comments']) AND $row['allow_comments'] == 'n') ? 'FALSE' : 'TRUE';
			$cond['signature_image']	= ($row['sig_img_filename'] == '' OR $this->EE->config->item('enable_signatures') == 'n' OR $this->EE->session->userdata('display_signatures') == 'n') ? 'FALSE' : 'TRUE';
			$cond['avatar']				= ($row['avatar_filename'] == '' OR $this->EE->config->item('enable_avatars') == 'n' OR $this->EE->session->userdata('display_avatars') == 'n') ? 'FALSE' : 'TRUE';
			$cond['photo']				= ($row['photo_filename'] == '' OR $this->EE->config->item('enable_photos') == 'n' OR $this->EE->session->userdata('display_photos') == 'n') ? 'FALSE' : 'TRUE';
			$cond['is_ignored']			= ( ! isset($row['member_id']) OR ! in_array($row['member_id'], $this->EE->session->userdata['ignore_list'])) ? 'FALSE' : 'TRUE';
			
			if ( isset($mfields) && is_array($mfields) && count($mfields) > 0)
			{
				foreach($mfields as $key => $value)
				{
					if (isset($row['m_field_id_'.$value]))
						$cond[$key] = $row['m_field_id_'.$value];
				}
			}
			
			$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);
			
			
			/* -------------------------------------------
			/* 'gallery_comments_tagdata' hook.
			/*  - Allows parsing of contents in tagdata variable for comments
			/*  - Added 1.4.2
			*/
				if ($this->EE->extensions->active_hook('gallery_comments_tagdata') === TRUE)
				{
					$tagdata = $this->EE->extensions->call('gallery_comments_tagdata', $row, $tagdata);
				}
			/*
			/* -------------------------------------------*/
			
	 
			/** ----------------------------------------
			/**  Parse "single" variables
			/** ----------------------------------------*/
			foreach ($this->EE->TMPL->var_single as $key => $val)
			{ 
			
				/** ----------------------------------------
				/**  parse {switch} variable
				/** ----------------------------------------*/
				
				if (strnmp($key, 'switch', 6) == 0)
				{
					$sparam = $this->EE->functions->assign_parameters($key);
					
					$sw = '';

					if (isset($sparam['switch']))
					{
						$sopt = explode("|", $sparam['switch']);
						
						if (count($sopt) == 2)
						{
							if (isset($switch[$sparam['switch']]) AND $switch[$sparam['switch']] == $sopt['0'])
							{
								$switch[$sparam['switch']] = $sopt['1'];
								
								$sw = $sopt['1'];									
							}
							else
							{
								$switch[$sparam['switch']] = $sopt['0'];
								
								$sw = $sopt['0'];									
							}
						}
					}
					
					$tagdata = $this->EE->TMPL->swap_var_single($key, $sw, $tagdata);
				}
			  
			
			
				/** ----------------------------------------
				/**  parse permalink
				/** ----------------------------------------*/
				
				if (strncmp($key, 'permalink', 9) == 0 && isset($row['comment_id']))
				{					 
						$tagdata = $this->EE->TMPL->swap_var_single(
															$key, 
															$this->EE->functions->create_url($uristr.'#'.$row['comment_id'], 0, 0), 
															$tagdata
														 );
				}
			
			

				/** ----------------------------------------
				/**  parse comment_path 
				/** ----------------------------------------*/
				
				if (strncmp($key, 'entry_id_path', 13) == 0)
				{						
					$tagdata = $this->EE->TMPL->swap_var_single(
														$key, 
														$this->EE->functions->create_url($this->EE->functions->extract_path($key).'/'.$row['entry_id']), 
														$tagdata
													 );
				}

			
				/** ----------------------------------------
				/**  parse comment date
				/** ----------------------------------------*/
				
				if (isset($comment_date[$key]))
				{
					foreach ($comment_date[$key] as $dvar)
						$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $row['comment_date'], TRUE), $val);					

					$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);					
				}
				
				
				/** ----------------------------------------
				/**  parse "last edit" date
				/** ----------------------------------------*/
				
				if (isset($edit_date[$key]))
				{
					if (isset($row['edit_date']))
					{
						foreach ($edit_date[$key] as $dvar)
							$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $this->EE->localize->timestamp_to_gmt($row['edit_date']), TRUE), $val);					
	
						$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);					
					}				
				}

				
				/** ----------------------------------------
				/**  {member_search_path}
				/** ----------------------------------------*/
				
				if (strncmp($key, 'member_search_path', 18) == 0)
				{					
					$tagdata = $this->EE->TMPL->swap_var_single($key, $search_link.urlencode($row['name']), $tagdata);
				}
				
				
				// Prep the URL
				
				if (isset($row['url']))
				{
					$row['url'] = prep_url($row['url']);
				}

				/** ----------------------------------------
				/**  {url_or_email}
				/** ----------------------------------------*/
				
				if ($key == "url_or_email" AND isset($row['url']))
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, ($row['url'] != '') ? $row['url'] : $this->EE->typography->encode_email($row['email'], '', 0), $tagdata);
				}


				/** ----------------------------------------
				/**  {url_or_email_as_author}
				/** ----------------------------------------*/
				
				if ($key == "url_or_email_as_author" AND isset($row['url']))
				{					
					if ($row['url'] != '')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$row['name']."</a>", $tagdata);
					}
					else
					{
						if ($row['email'] != '')
						{
								$tagdata = $this->EE->TMPL->swap_var_single($val, $this->EE->typography->encode_email($row['email'], $row['name']), $tagdata);
						}
						else
						{
								$tagdata = $this->EE->TMPL->swap_var_single($val, $row['name'], $tagdata);
						}
					}
				}
				
				/** ----------------------------------------
				/**  {url_or_email_as_link}
				/** ----------------------------------------*/
				
				if ($key == "url_or_email_as_link" AND isset($row['url']))
				{					
					if ($row['url'] != '')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, "<a href=\"".$row['url']."\">".$row['url']."</a>", $tagdata);
					}
					else
					{  
						if ($row['email'] != '')
						{					
							$tagdata = $this->EE->TMPL->swap_var_single($val, $this->EE->typography->encode_email($row['email']), $tagdata);
						}
						else
						{
							$tagdata = $this->EE->TMPL->swap_var_single($val, $row['name'], $tagdata);
						}
					}
				}
				
				/** ----------------------------------------
				/**  parse comment field
				/** ----------------------------------------*/
				
				if ($key == 'comment' AND isset($row['comment']))
				{
					$comment = $this->EE->typography->parse_type( $row['comment'], 
													array(
															'text_format'	=> $row['gallery_comment_text_formatting'],
															'html_format'	=> $row['gallery_comment_html_formatting'],
															'auto_links'	=> $row['gallery_comment_auto_link_urls'],
															'allow_img_url' => $row['gallery_comment_allow_img_urls']
														)
												);
				
					$tagdata = $this->EE->TMPL->swap_var_single($key, $comment, $tagdata);				
				}
				
				
				/** ----------------------------------------
				/**  {signature}
				/** ----------------------------------------*/
				
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
				
				/** ----------------------------------------
				/**  {location}
				/** ----------------------------------------*/
				
				if ($key == 'location' AND (isset($row['location']) OR isset($row['c_location'])))
				{					
					$tagdata = $this->EE->TMPL->swap_var_single($key, (empty($row['location'])) ? $row['c_location'] : $row['location'], $tagdata);
				}
				
				/** ----------------------------------------
				/**  parse basic fields
				/** ----------------------------------------*/
				 
                if (isset($row[$val]) && $val != 'member_id')
				{					
					$tagdata = $this->EE->TMPL->swap_var_single($val, $row[$val], $tagdata);
				}
				
				/** ----------------------------------------
				/**  parse custom member fields
				/** ----------------------------------------*/

                if ( isset($mfields[$val]))
                {
                	// Since comments do not necessarily require registration, and since
					// you are allowed to put custom member variables in comments, 
					// we delete them if no such row exists
					
                	$return_val = (isset($row['m_field_id_'.$mfields[$val]])) ? $row['m_field_id_'.$mfields[$val]] : '';
                
                	$tagdata = $this->EE->TMPL->swap_var_single(
																	$val, 
																	$return_val, 
																	$tagdata
																  );
                }
				
				/** ----------------------------------------
				/**  Clean up left over variables
				/** ----------------------------------------*/

				if (in_array($val, $member_vars))
				{
					$tagdata = str_replace(LD.$val.RD, '', $tagdata);
				}				
			}
			
			/** ----------------------------------------
			/**  Add Anchor
			/** ----------------------------------------*/
			
			if ($this->show_anchor == TRUE)
			{
				$return .= "<a name=\"".$item_count."\"></a>\n";
			}
			
			$return .= $tagdata;
			
			$item_count++;						
		}
	 
		/** ----------------------------------------
		/**  Parse path variable
		/** ----------------------------------------*/
		
		$return = preg_replace_callback("/".LD."\s*path=(.+?)".RD."/", array(&$this->EE->functions, 'create_url'), $return);

		/** ----------------------------------------
		/**  Add pagination to result
		/** ----------------------------------------*/
		if ($paginate == TRUE)
		{
			$paginate_data = str_replace(LD.'current_page'.RD, 	$t_current_page, 	$paginate_data);
			$paginate_data = str_replace(LD.'total_pages'.RD,		$total_pages,  		$paginate_data);
			$paginate_data = str_replace(LD.'pagination_links'.RD,	$pagination_links,	$paginate_data);
			
			if (preg_match("/".LD."if previous_page".RD."(.+?)".LD.'\/'."if".RD."/s", $paginate_data, $match))
			{
				if ($page_previous == '')
				{
					 $paginate_data = preg_replace("/".LD."if previous_page".RD.".+?".LD.'\/'."if".RD."/s", '', $paginate_data);
				}
				else
				{
					$match['1'] = str_replace(array(LD.'path'.RD, LD.'auto_path'.RD), $page_previous, $match['1']);
				
					$paginate_data = str_replace($match['0'],	$match['1'], $paginate_data);
				}
				}
			
			if (preg_match("/".LD."if next_page".RD."(.+?)".LD.'\/'."if".RD."/s", $paginate_data, $match))
			{
				if ($page_next == '')
				{
					 $paginate_data = preg_replace("/".LD."if next_page".RD.".+?".LD.'\/'."if".RD."/s", '', $paginate_data);
				}
				else
					{
					$match['1'] = str_replace(array(LD.'path'.RD, LD.'auto_path'.RD), $page_next, $match['1']);
				
					$paginate_data = str_replace($match['0'],	$match['1'], $paginate_data);
				}
				}
		
			$position = ( ! $this->EE->TMPL->fetch_param('paginate')) ? '' : $this->EE->TMPL->fetch_param('paginate');
			
			switch ($position)
			{
				case "top"	: $return  = $paginate_data.$return;
					break;
				case "both"	: $return  = $paginate_data.$return.$paginate_data;
					break;
				default		: $return .= $paginate_data;
					break;
			}
		}	
		
		return $return;
	}




	/** ----------------------------------------
	/**  Comment Submission Form
	/** ----------------------------------------*/
	function comment_form($return_form = FALSE, $captcha = '')
	{
		$qstring = $this->EE->uri->query_string;
		/** --------------------------------------
		/**  Remove page number
		/** --------------------------------------*/
		
		if (preg_match("#/P\d+#", $qstring, $match))
		{			
			$qstring = $this->EE->functions->remove_double_slashes(str_replace($match['0'], '', $qstring));
		}
			 
		/** --------------------------------------
		/**  Remove "N" 
		/** --------------------------------------*/
		if (preg_match("#/N(\d+)#", $qstring, $match))
		{			
			$qstring = $this->EE->functions->remove_double_slashes(str_replace($match['0'], '', $qstring));
		}

 		$entry_id = (isset($_POST['entry_id'])) ? $_POST['entry_id'] : $qstring;
 		 		
		// If there is a slash in the entry ID we'll kill everything after it.
 		
 		$entry_id = preg_replace("#/.+#", "", $entry_id);
 		
		/** ----------------------------------------
		/**  Are comments allowed?
		/** ----------------------------------------*/
		
		$sql = "SELECT exp_gallery_entries.entry_id, exp_gallery_entries.entry_date, exp_gallery_entries.comment_expiration_date, exp_gallery_entries.allow_comments, exp_galleries.gallery_comment_use_captcha, exp_galleries.gallery_comment_expiration 
				FROM exp_gallery_entries, exp_galleries
				WHERE exp_gallery_entries.entry_id = '".$this->EE->db->escape_str($entry_id)."' 
				AND exp_gallery_entries.gallery_id = exp_galleries.gallery_id";
								
		
		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
		
		if ($query->row('allow_comments')  == 'n')
		{
			return FALSE;
		}
		
		
		if ($return_form == FALSE)
		{
			if ($query->row('gallery_comment_use_captcha')  == 'n' OR ($this->EE->config->item('captcha_require_members') == 'n' AND $this->EE->session->userdata('member_id') != 0))
			{
				$this->EE->TMPL->tagdata = str_replace(LD.'captcha'.RD, '', $this->EE->TMPL->tagdata);			 
			}	 

			// Load the string helper
			$this->EE->load->helper('string');

			return '{NOCACHE_GALLERY_FORM="'.trim_slashes($this->EE->TMPL->fetch_param('preview')).'"}'.$this->EE->TMPL->tagdata.'{/NOCACHE_FORM}';
		}
		
		/** ----------------------------------------
		/**  Has commenting expired?
		/** ----------------------------------------*/
		
		if ($query->row('comment_expiration_date')  > 0)
		{	
			if ($this->EE->localize->now > $query->row('comment_expiration_date') )
			{
				$this->EE->lang->loadfile('comment');
			
				return $this->EE->lang->line('cmt_commenting_has_expired');
			}
		}		
		
		$tagdata = $this->EE->TMPL->tagdata;	
		
		/** ----------------------------------------
		/**  Conditionals
		/** ----------------------------------------*/
		
		$cond = array();
		$cond['logged_in']			= ($this->EE->session->userdata('member_id') == 0) ? 'FALSE' : 'TRUE';
		$cond['logged_out']			= ($this->EE->session->userdata('member_id') != 0) ? 'FALSE' : 'TRUE';
		
		if ($query->row('gallery_comment_use_captcha')  == 'n')
		{
			$cond['captcha'] = 'FALSE';			
		}
		else
		{
			$cond['captcha'] =  ($this->EE->config->item('captcha_require_members') == 'y'  OR 
								($this->EE->config->item('captcha_require_members') == 'n' AND $this->EE->session->userdata('member_id') == 0)) ? 'TRUE' : 'FALSE';
		}
		
		$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);
		
		// Load the form helper
		$this->EE->load->helper('form');

		/** ----------------------------------------
		/**  Parse Single Variables
		/** ----------------------------------------*/
		foreach ($this->EE->TMPL->var_single as $key => $val)
		{			  
			/** ----------------------------------------
			/**  parse {name}
			/** ----------------------------------------*/
			
			if ($key == 'name')
			{
				$name = ($this->EE->session->userdata['screen_name'] != '') ? $this->EE->session->userdata['screen_name'] : $this->EE->session->userdata['username'];
			
				$name = ( ! isset($_POST['name'])) ? $name : $_POST['name'];
			
				$tagdata = $this->EE->TMPL->swap_var_single($key, form_prep($name), $tagdata);
			}
					
			/** ----------------------------------------
			/**  parse {email}
			/** ----------------------------------------*/
			
			if ($key == 'email')
			{
				$email = ( ! isset($_POST['email'])) ? $this->EE->session->userdata['email'] : $_POST['email'];
			  
				$tagdata = $this->EE->TMPL->swap_var_single($key, form_prep($email), $tagdata);
			}

			/** ----------------------------------------
			/**  parse {url}
			/** ----------------------------------------*/
			
			if ($key == 'url')
			{
				$url = ( ! isset($_POST['url'])) ? $this->EE->session->userdata['url'] : $_POST['url'];
				
				if ($url == '')
					$url = 'http://';

				$tagdata = $this->EE->TMPL->swap_var_single($key, form_prep($url), $tagdata);
			}

			/** ----------------------------------------
			/**  parse {location}
			/** ----------------------------------------*/
			
			if ($key == 'location')
			{ 
				$location = ( ! isset($_POST['location'])) ? $this->EE->session->userdata['location'] : $_POST['location'];

				$tagdata = $this->EE->TMPL->swap_var_single($key, form_prep($location), $tagdata);
			}
		  
			/** ----------------------------------------
			/**  parse {comment}
			/** ----------------------------------------*/
			
			if ($key == 'comment')
			{
				$comment = ( ! isset($_POST['comment'])) ? '' : $_POST['comment'];
			
				$tagdata = $this->EE->TMPL->swap_var_single($key, $comment, $tagdata);
			}
			
			
			/** ----------------------------------------
			/**  parse {captcha_word}
			/** ----------------------------------------*/
			
			if ($key == 'captcha_word')
			{
				$tagdata = $this->EE->TMPL->swap_var_single($key, '', $tagdata);
			}

			/** ----------------------------------------
			/**  parse {save_info}
			/** ----------------------------------------*/
			
			if ($key == 'save_info')
			{
				$save_info = ( ! isset($_POST['save_info'])) ? '' : $_POST['save_info'];
						
				$notify = ( ! isset($this->EE->session->userdata['notify_by_default'])) ? $this->EE->input->cookie('save_info') : $this->EE->session->userdata['notify_by_default'];
						
				$checked	= ( ! isset($_POST['PRV'])) ? $notify : $save_info;
			
				$tagdata = $this->EE->TMPL->swap_var_single($key, ($checked == 'yes') ? "checked=\"checked\"" : '', $tagdata);
			}
			
			/** ----------------------------------------
			/**  parse {notify_me}
			/** ----------------------------------------*/
			
			if ($key == 'notify_me')
			{
					$checked = '';
				
				if ($this->EE->input->cookie('notify_me'))
				{
					$checked = $this->EE->input->cookie('notify_me');
				}
				
				if (isset($this->EE->session->userdata['notify_by_default']))
				{
						$checked = ($this->EE->session->userdata['notify_by_default'] == 'y') ? 'yes' : '';
				}
				
				if (isset($_POST['notify_me']))
				{
						$checked = $_POST['notify_me'];
				}
							
				$tagdata = $this->EE->TMPL->swap_var_single($key, ($checked == 'yes') ? "checked=\"checked\"" : '', $tagdata);
			}
		}

		/** ----------------------------------------
		/**  Create form
		/** ----------------------------------------*/
				
		$RET = (isset($_POST['RET'])) ? $_POST['RET'] : $this->EE->functions->fetch_current_uri();
		$PRV = (isset($_POST['PRV'])) ? $_POST['PRV'] : '{PREVIEW_TEMPLATE}';
		$XID = ( ! isset($_POST['XID'])) ? '' : $_POST['XID'];
				
		$hidden_fields = array(
								'ACT'	  => $this->EE->functions->fetch_action_id('Gallery', 'insert_new_comment'),
								'RET'	  => $RET,
								'URI'	  => ($this->EE->uri->uri_string == '') ? 'index' : $this->EE->uri->uri_string,
								'PRV'	  => $PRV,
								'XID'	  => $XID,
								'entry_id' => $query->row('entry_id') 
							  );

		if ($query->row('gallery_comment_use_captcha')  == 'y')
		{	
			if (preg_match("/({captcha})/", $tagdata))
			{	
				$tagdata = preg_replace("/{captcha}/", $this->EE->functions->create_captcha(), $tagdata);
			}		
		}
		
		$data = array(
						'hidden_fields'	=> $hidden_fields,
						'action'		=> $RET,
						'id'			=> 'comment_form'
					);
		
		if ($this->EE->TMPL->fetch_param('name') !== FALSE && 
			preg_match("#^[a-zA-Z0-9_\-]+$#i", $this->EE->TMPL->fetch_param('name'), $match))
		{
			$data['name'] = $this->EE->TMPL->fetch_param('name');
		}
		
		$res  = $this->EE->functions->form_declaration($data);  
				
		$res .= stripslashes($tagdata);
		$res .= "</form>"; 
		return $res;
	}





	/** ----------------------------------------
	/**  Preview
	/** ----------------------------------------*/
	function comment_preview()
	{
		$entry_id = (isset($_POST['entry_id'])) ? $_POST['entry_id'] : $this->EE->uri->query_string;
		
		if ( ! is_numeric($entry_id))
		{
			return FALSE;
		}
		/** ----------------------------------------
		/**  Instantiate Typography class
		/** ----------------------------------------*/
	  
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();

		$this->EE->typography->encode_email = FALSE;				
		
		$sql = "SELECT exp_galleries.gallery_comment_text_formatting, exp_galleries.gallery_comment_html_formatting, exp_galleries.gallery_comment_allow_img_urls, exp_galleries.gallery_comment_auto_link_urls
				FROM	exp_galleries, exp_gallery_entries
				WHERE  exp_gallery_entries.gallery_id = exp_galleries.gallery_id 
				AND	exp_gallery_entries.entry_id = '$entry_id'";		
		
		$query = $this->EE->db->query($sql);
		
		if ($query->num_rows() == 0)
		{ 
			return '';
		}
		
		if ($query->row('gallery_comment_text_formatting')  == '')
		{
			$formatting = 'none';
		}
		else
		{
			$formatting = $query->row('gallery_comment_text_formatting') ;
		}
		
		$tagdata = $this->EE->TMPL->tagdata; 
		
				
		/** ----------------------------------------
		/**  Fetch all the date-related variables
		/** ----------------------------------------*/
		
		$comment_date = array();
		
		if (preg_match_all("/".LD."comment_date\s+format=[\"'](.*?)[\"']".RD."/s", $tagdata, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$matches['0'][$j] = str_replace(LD, '', $matches['0'][$j]);
				$matches['0'][$j] = str_replace(RD, '', $matches['0'][$j]);
				
				$comment_date[$matches['0'][$j]] = $this->EE->localize->fetch_date_params($matches['1'][$j]);
			}
		}
		
		/** ----------------------------------------
		/**  Conditionals
		/** ----------------------------------------*/
			
		$cond = $_POST; // All POST data is sanitized on input and then we sanitize further in prep_conditionals();
		$cond['logged_in']		= ($this->EE->session->userdata['member_id'] == 0) ? 'FALSE' : 'TRUE';
		$cond['logged_out']		= ($this->EE->session->userdata['member_id'] != 0) ? 'FALSE' : 'TRUE';
			
		$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);
		
		/** ----------------------------------------
		/**  Single Variables
		/** ----------------------------------------*/
		
		foreach ($this->EE->TMPL->var_single as $key => $val)
		{	
			/** ----------------------------------------
			/**  {name}
			/** ----------------------------------------*/
			
			if (isset($_POST['name']) AND $_POST['name'] != '')
			{
				$name = stripslashes($this->EE->input->post('name'));
			}
			elseif ($this->EE->session->userdata['screen_name'] != '')
			{
				$name = $this->EE->session->userdata['screen_name'];
			}
			else
			{
				$name = '';
			}

			if ($key == 'name')
			{
				$tagdata = $this->EE->TMPL->swap_var_single($key, $name, $tagdata);				
			}
		
			/** ----------------------------------------
			/**  {email}
			/** ----------------------------------------*/
			
			if (isset($_POST['email']) AND $_POST['email'] != '')
			{
				$email = stripslashes($this->EE->input->post('email'));
			}
			elseif ($this->EE->session->userdata['email'] != '')
			{
				$email = $this->EE->session->userdata['email'];
			}
			else
			{
				$email = '';
			}
			
			if ($key == 'email')
			{						
				$tagdata = $this->EE->TMPL->swap_var_single($key, $email, $tagdata);				
			}
		
			/** ----------------------------------------
			/**  {url}
			/** ----------------------------------------*/
			
			if (isset($_POST['url']) AND $_POST['url'] != '')
			{
				$url = stripslashes($this->EE->input->post('url'));
			}
			elseif ($this->EE->session->userdata['url'] != '')
			{
				$url = $this->EE->session->userdata['url'];
			}
			else
			{
				$url = '';
			}
			
			if ($key == 'url')
			{
				$tagdata = $this->EE->TMPL->swap_var_single($key, $url, $tagdata);				
			}
		
			/** ----------------------------------------
			/**  {location}
			/** ----------------------------------------*/
			
			if ($key == 'location')
			{						
				if (isset($_POST['location']) AND $_POST['location'] != '')
				{
					$location = stripslashes($this->EE->input->post('location'));
				}
				elseif ($this->EE->session->userdata['location'] != '')
				{
					$location = $this->EE->session->userdata['location'];
				}
				else
				{
					$location = '';
				}

				$tagdata = $this->EE->TMPL->swap_var_single($key, $location, $tagdata);				
			}
						
			// Prep the URL
			
			if ($url != '')
			{
				$url = prep_url($url);
			}

			/** ----------------------------------------
			/**  {url_or_email}
			/** ----------------------------------------*/
			
			if ($key == "url_or_email")
			{
				$temp = $url;
				
				if ($temp == '' AND $email != '')
				{
					$temp = $this->EE->typography->encode_email($email, '', 0);
				}
			
				$tagdata = $this->EE->TMPL->swap_var_single($val, $temp, $tagdata);
			}

			/** ----------------------------------------
			/**  {url_or_email_as_author}
			/** ----------------------------------------*/
			
			if ($key == "url_or_email_as_author")
			{					
				if ($url != '')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, "<a href=\"".$url."\">".$name."</a>", $tagdata);
				}
				else
				{
					if ($email != '')
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, $this->EE->typography->encode_email($email, $name), $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, $name, $tagdata);
					}
				}
			}
			
			/** ----------------------------------------
			/**  {url_or_email_as_link}
			/** ----------------------------------------*/
			
			if ($key == "url_or_email_as_link")
			{					
				if ($url != '')
				{
					$tagdata = $this->EE->TMPL->swap_var_single($val, "<a href=\"".$url."\">".$url."</a>", $tagdata);
				}
				else
				{  
					if ($email != '')
					{					
						$tagdata = $this->EE->TMPL->swap_var_single($val, $this->EE->typography->encode_email($email), $tagdata);
					}
					else
					{
						$tagdata = $this->EE->TMPL->swap_var_single($val, $name, $tagdata);
					}
				}
			}

			/** ----------------------------------------
			/**  parse comment field
			/** ----------------------------------------*/
			
			if ($key == 'comment')
			{ 
				$data = $this->EE->typography->parse_type( stripslashes($this->EE->input->post('comment')), 
											 array(
													'text_format'	=> $query->row('gallery_comment_text_formatting') ,
													'html_format'	=> $query->row('gallery_comment_html_formatting') ,
													'auto_links'	=> $query->row('gallery_comment_auto_link_urls') ,
													'allow_img_url' => $query->row('gallery_comment_allow_img_urls') 
													)
											);

				$tagdata = $this->EE->TMPL->swap_var_single($key, $data, $tagdata);				
			}
					
			/** ----------------------------------------
			/**  parse comment date
			/** ----------------------------------------*/
			
			if (isset($comment_date[$key]))
			{				
				foreach ($comment_date[$key] as $dvar)
				{
					$val = str_replace($dvar, $this->EE->localize->convert_timestamp($dvar, $this->EE->localize->now, TRUE), $val);		
				}

				$tagdata = $this->EE->TMPL->swap_var_single($key, $val, $tagdata);					
			}
			
		}
	
		return $tagdata;
	}




	/** ----------------------------------------
	/**  Preview handler
	/** ----------------------------------------*/
	function preview_handler()
	{
		if ($this->EE->input->post('PRV') == '')
		{
			$this->EE->lang->loadfile('comment');
		
			$error[] = $this->EE->lang->line('cmt_no_preview_template_specified');
			
			return $this->EE->output->show_user_error('general', $error);		
		}
		
		$this->EE->functions->clear_caching('all', $_POST['PRV']);
		
		require APPPATH.'libraries/Template'.EXT;
		
		$T = new EE_Template();	
		$TMPL = $T;
		
		$preview = ( ! $this->EE->input->post('PRV')) ? '' : $this->EE->input->get_post('PRV');
		
		if (strpos($preview, '/') === FALSE)
		{
			$preview = '';			
		}
		else
		{
			$ex = explode("/", $preview);

			if (count($ex) != 2)
			{
				$preview = '';
			}
		}
						
		if ($preview == '')
		{
			$group = 'channel';
			$templ = 'preview';
		}
		else
		{
			$group = $ex['0'];
			$templ = $ex['1'];
		}		
														
		$TMPL->run_template_engine($group, $templ);
	}





	/** ----------------------------------------
	/**  Insert new comment
	/** ----------------------------------------*/
	function insert_new_comment()
	{
		$default = array('name', 'email', 'url', 'comment', 'location');
		
		foreach ($default as $val)
		{
			if ( ! isset($_POST[$val]))
			{
				$_POST[$val] = '';
			}
		}		
				
		// If the comment is empty, bounce them back
		
		if ($_POST['comment'] == '')
		{
			$this->EE->functions->redirect($_POST['RET']);
		}
				
		/** ----------------------------------------
		/**  Fetch the comment language pack
		/** ----------------------------------------*/
		
		$this->EE->lang->loadfile('comment');
		
				
		/** ----------------------------------------
		/**  Is the user banned?
		/** ----------------------------------------*/
		
		if ($this->EE->session->userdata['is_banned'] == TRUE)
		{			
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
		}
		
		/** ----------------------------------------
		/**  Is the IP address and User Agent required?
		/** ----------------------------------------*/
				
		if ($this->EE->config->item('require_ip_for_posting') == 'y')
		{
			if ($this->EE->input->ip_address() == '0.0.0.0' OR $this->EE->session->userdata['user_agent'] == "")
			{			
				return $this->EE->output->show_user_error('general', array($this->EE->lang->line('not_authorized')));
			}
		} 
				
		/** ----------------------------------------
		/**  Can the user post comments?
		/** ----------------------------------------*/
		
		if ($this->EE->session->userdata['can_post_comments'] == 'n')
		{
			$error[] = $this->EE->lang->line('cmt_no_authorized_for_comments');
			
			return $this->EE->output->show_user_error('general', $error);
		}
		
		
		/* -------------------------------------------
		/* 'gallery_insert_new_comment' hook.
		/*  - After security checks but before actual processing, add your own processing
		/*  - Added 1.4.2
		*/
			$edata = $this->EE->extensions->call('gallery_insert_new_comment');
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		/** ----------------------------------------
		/**  Is this a preview request?
		/** ----------------------------------------*/
		
		if (isset($_POST['preview']))
		{
			return $this->preview_handler();
		}
		
		/** ----------------------------------------
		/**  Fetch gallery preferences
		/** ----------------------------------------*/
		
		$sql = "SELECT exp_gallery_entries.title, 
						exp_gallery_entries.gallery_id,
						exp_gallery_entries.cat_id,
						exp_gallery_entries.author_id,
						exp_gallery_entries.total_comments,
						exp_gallery_entries.allow_comments,
						exp_gallery_entries.entry_date,
						exp_gallery_entries.comment_expiration_date,
						exp_galleries.gallery_id,
						exp_galleries.gallery_full_name,
						exp_galleries.gallery_comment_max_chars,
						exp_galleries.gallery_comment_use_captcha,
						exp_galleries.gallery_comment_timelock,
						exp_galleries.gallery_comment_require_membership,
						exp_galleries.gallery_comment_moderate,
						exp_galleries.gallery_comment_require_email,
						exp_galleries.gallery_comment_notify,
						exp_galleries.gallery_comment_notify_authors,
						exp_galleries.gallery_comment_notify_emails,
						exp_galleries.gallery_comment_expiration
				FROM	exp_gallery_entries, exp_galleries
				WHERE  exp_gallery_entries.gallery_id = exp_galleries.gallery_id
				AND	exp_gallery_entries.entry_id = '".$this->EE->db->escape_str($_POST['entry_id'])."'";
				
		$query = $this->EE->db->query($sql);		
		
		unset($sql);
				
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		/** ----------------------------------------
		/**  Are comments allowed?
		/** ----------------------------------------*/
		if ($query->row('allow_comments')  == 'n')
		{
			$error[] = $this->EE->lang->line('cmt_comments_not_allowed');
			
			return $this->EE->output->show_user_error('submission', $error);
		}
		
		/** ----------------------------------------
		/**  Has commenting expired?
		/** ----------------------------------------*/
		
		if ($query->row('comment_expiration_date')  > 0)
		{	
			if ($this->EE->localize->now > $query->row('comment_expiration_date') )
			{
				$error[] = $this->EE->lang->line('cmt_commenting_has_expired');
				
				return $this->EE->output->show_user_error('submission', $error);
			}
		}		
				
		/** ----------------------------------------
		/**  Is there a comment timelock?
		/** ----------------------------------------*/
		if ($query->row('gallery_comment_timelock')  != '' AND $query->row('gallery_comment_timelock')  > 0)
		{
			if ($this->EE->session->userdata['group_id'] != 1)		
			{
				$time = $this->EE->localize->now - $query->row('gallery_comment_timelock') ;
			
				$result = $this->EE->db->query("SELECT count(*) AS count FROM exp_gallery_comments WHERE comment_date > '$time' AND ip_address = '$this->EE->input->ip_address()' ");
			
				if ($result->row('count')  > 0)
				{
					$error[] = str_replace("%s", $query->row('gallery_comment_timelock') , $this->EE->lang->line('cmt_comments_timelock'));
					
					return $this->EE->output->show_user_error('submission', $error);
				}
			}
		}
		
		/** ----------------------------------------
		/**  Do we allow dupllicate data?
		/** ----------------------------------------*/
		if ($this->EE->config->item('deny_duplicate_data') == 'y')
		{
			if ($this->EE->session->userdata['group_id'] != 1)		
			{			
				$result = $this->EE->db->query("SELECT count(*) AS count FROM exp_gallery_comments WHERE comment = '".$this->EE->db->escape_str($_POST['comment'])."' ");
			
				if ($result->row('count')  > 0)
				{					
					return $this->EE->output->show_user_error('submission', $this->EE->lang->line('cmt_duplicate_comment_warning'));
				}
			}
		}		
				
		/** ----------------------------------------
		/**  Assign data
		/** ----------------------------------------*/
		$author_id			= $query->row('author_id') ;
		$entry_title		= $query->row('title') ;
		$gallery_id		 = $query->row('gallery_id') ;
		$gallery_name		= $query->row('gallery_full_name') ;
		$category_id		= $query->row('cat_id') ;
		$total_comments	 = $query->row('total_comments')  + 1;
		$require_membership = $query->row('gallery_comment_require_membership') ;
		$author_notify		= $query->row('gallery_comment_notify_authors') ;

		$notify_address = ($query->row('gallery_comment_notify')  == 'y' AND $query->row('gallery_comment_notify_emails')  != '') ? $query->row('gallery_comment_notify_emails')  : '';

		// Comment moderation.
		// When we enable this feature uncomment this line and delete the next one

		// $comment_moderate	= ($this->EE->session->userdata['group_id'] == 1 OR $this->EE->session->userdata['exclude_from_moderation'] == 'y') ? 'n' : $query->row('comment_moderate') ;
		$comment_moderate	= 'n';

		/** ----------------------------------------
		/**  Start error trapping
		/** ----------------------------------------*/
		
		$error = array();
		
		if ($this->EE->session->userdata('member_id') != 0)		
		{
			// If the user is logged in we'll reassign the POST variables with the user data
			
			 $_POST['name']	 = ($this->EE->session->userdata['screen_name'] != '') ? $this->EE->session->userdata['screen_name'] : $this->EE->session->userdata['username'];
			 $_POST['email']	=  $this->EE->session->userdata['email'];
			 $_POST['url']	  =  $this->EE->session->userdata['url'];
			 $_POST['location'] =  $this->EE->session->userdata['location'];
		}
		
		
		/** ----------------------------------------
		/**  Is membership is required to post...
		/** ----------------------------------------*/
		
		if ($require_membership == 'y')
		{		
			// Not logged in
		
			if ($this->EE->session->userdata('member_id') == 0)
			{
				$error[] = $this->EE->lang->line('cmt_must_be_member');
				
				return $this->EE->output->show_user_error('submission', $error);
			}
			
			// Membership is pending
			
			if ($this->EE->session->userdata['group_id'] == 4)
			{
				$error[] = $this->EE->lang->line('cmt_account_not_active');
				
				return $this->EE->output->show_user_error('general', $error);
			}
						
		}
		else
		{							  
			/** ----------------------------------------
			/**  Missing name?
			/** ----------------------------------------*/
			
			if ($_POST['name'] == '')
			{
				$error[] = $this->EE->lang->line('cmt_missing_name');
			}
			
			/** -------------------------------------
			/**  Is name banned?
			/** -------------------------------------*/
		
			if ($this->EE->session->ban_check('screen_name', $_POST['name']))
			{
				$error[] = $this->EE->lang->line('cmt_name_not_allowed');
			}
			
			/** ----------------------------------------
			/**  Missing or invalid email address
			/** ----------------------------------------*/
	
			if ($query->row('gallery_comment_require_email')  == 'y')
			{
				if ($_POST['email'] == '')
				{
					$error[] = $this->EE->lang->line('cmt_missing_email');
				}

				$this->EE->load->helper('email');

				if ( ! valid_email($_POST['email']))
				{
					$error[] = $this->EE->lang->line('cmt_invalid_email');
				}
			}
		}
		
		/** -------------------------------------
		/**  Is email banned?
		/** -------------------------------------*/
		
		if ($_POST['email'] != '')
		{
			if ($this->EE->session->ban_check('email', $_POST['email']))
			{
				$error[] = $this->EE->lang->line('cmt_banned_email');
			}
		}	
		
		/** ----------------------------------------
		/**  Is comment too big?
		/** ----------------------------------------*/
		
		if ($query->row('gallery_comment_max_chars')  != '' AND $query->row('gallery_comment_max_chars')  != 0)
		{		
			if (strlen($_POST['comment']) > $query->row('gallery_comment_max_chars') )
			{
				$str = str_replace("%n", strlen($_POST['comment']), $this->EE->lang->line('cmt_too_large'));
				
				$str = str_replace("%x", $query->row('gallery_comment_max_chars') , $str);
			
				$error[] = $str;
			}
		}
		
		/** ----------------------------------------
		/**  Do we have errors to display?
		/** ----------------------------------------*/
				
		if (count($error) > 0)
		{
			return $this->EE->output->show_user_error('submission', $error);
		}
				
		
		/** ----------------------------------------
		/**  Do we require CAPTCHA?
		/** ----------------------------------------*/
		
		if ($query->row('gallery_comment_use_captcha')  == 'y')
		{	
			if ($this->EE->config->item('captcha_require_members') == 'y'  OR  ($this->EE->config->item('captcha_require_members') == 'n' AND $this->EE->session->userdata('member_id') == 0))
			{
				if ( ! isset($_POST['captcha']) OR $_POST['captcha'] == '')
				{
					return $this->EE->output->show_user_error('submission', $this->EE->lang->line('captcha_required'));
				}
				else
				{
					$res = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_captcha WHERE word='".$this->EE->db->escape_str($_POST['captcha'])."' AND ip_address = '".$this->EE->input->ip_address()."' AND date > UNIX_TIMESTAMP()-7200");
				
					if ($res->row('count')  == 0)
					{
						return $this->EE->output->show_user_error('submission', array($this->EE->lang->line('captcha_incorrect')));
					}
				
					$this->EE->db->query("DELETE FROM exp_captcha WHERE (word='".$this->EE->db->escape_str($_POST['captcha'])."' AND ip_address = '".$this->EE->input->ip_address()."') OR date < UNIX_TIMESTAMP()-7200");
				}
			}
		}
		
		/** ----------------------------------------
		/**  Build the data array
		/** ----------------------------------------*/
		
		$notify = ($this->EE->input->post('notify_me')) ? 'y' : 'n';

		$_POST['url'] = prep_url($_POST['url']);

		$data = array(
						'gallery_id'	=> $gallery_id,
						'entry_id'		=> $_POST['entry_id'],
						'author_id'		=> $this->EE->session->userdata('member_id'),
						'name'		  	=> $this->EE->security->xss_clean($_POST['name']),
						'email'			=> $_POST['email'],
						'url'			=> $this->EE->security->xss_clean($_POST['url']),
						'location'	  	=> $this->EE->security->xss_clean($_POST['location']),
						'comment'		=> $this->EE->security->xss_clean($_POST['comment']),
						'comment_date'  => $this->EE->localize->now,
						'ip_address'	=> $this->EE->input->ip_address(),
						'notify'		=> $notify,
						'status'		=> ($comment_moderate == 'y') ? 'c' : 'o'
					 );

	  
		/** ----------------------------------------
		/**  Submit data into DB
		/** ----------------------------------------*/
	  
		if ($this->EE->config->item('secure_forms') == 'y')
		{
			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_security_hashes WHERE hash='".$this->EE->db->escape_str($_POST['XID'])."' AND ip_address = '".$this->EE->input->ip_address()."' AND date > UNIX_TIMESTAMP()-7200");
		
			if ($query->row('count')  > 0)
			{
				$sql = $this->EE->db->insert_string('exp_gallery_comments', $data);

				$this->EE->db->query($sql);
				
				$comment_id = $this->EE->db->insert_id();
								
				$this->EE->db->query("DELETE FROM exp_security_hashes WHERE (hash='".$this->EE->db->escape_str($_POST['XID'])."' AND ip_address = '".$this->EE->input->ip_address()."') OR date < UNIX_TIMESTAMP()-7200");
			}
			else
			{
				$this->EE->functions->redirect(stripslashes($_POST['RET']));
			}
		}
		else
		{
			$sql = $this->EE->db->insert_string('exp_gallery_comments', $data);
		
			$this->EE->db->query($sql);
			
			$comment_id = $this->EE->db->insert_id();
		}
		
		if ($comment_moderate == 'n')
		{		
			/** ------------------------------------------------
			/**  Update comment total and "recent comment" date
			/** ------------------------------------------------*/
			
			$this->EE->db->query("UPDATE exp_gallery_entries SET total_comments = '$total_comments', recent_comment_date = '".$this->EE->localize->now."' WHERE entry_id = '".$this->EE->db->escape_str($_POST['entry_id'])."'");
		 
			$query = $this->EE->db->query("SELECT total_comments FROM exp_gallery_categories WHERE cat_id = '{$category_id}'");

			$this->EE->db->query("UPDATE exp_gallery_categories SET total_comments = '".($query->row('total_comments')  + 1)."', recent_comment_date = '".$this->EE->localize->now."' WHERE cat_id = '{$category_id}'");				
		 
		 
			/** ----------------------------------------
			/**  Update member comment total and date
			/** ----------------------------------------*/
			
			if ($this->EE->session->userdata('member_id') != 0)
			{
				$query = $this->EE->db->query("SELECT total_comments FROM exp_members WHERE member_id = '".$this->EE->session->userdata('member_id')."'");
	
				$this->EE->db->query("UPDATE exp_members SET total_comments = '".($query->row('total_comments')  + 1)."', last_comment_date = '".$this->EE->localize->now."' WHERE member_id = '".$this->EE->session->userdata('member_id')."'");				
			}			
			
			/** ----------------------------------------
			/**  Fetch email notification addresses
			/** ----------------------------------------*/
			
			$query = $this->EE->db->query("SELECT DISTINCT(email), name, comment_id, author_id FROM exp_gallery_comments WHERE status = 'o' AND entry_id = '".$this->EE->db->escape_str($_POST['entry_id'])."' AND notify = 'y'");
			
			$recipients = array();
					
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					if ($row['email'] == "" AND $row['author_id'] != 0)
					{
						$result = $this->EE->db->query("SELECT email, screen_name FROM exp_members WHERE member_id = '".$row['author_id']."'");
						
						if ($result->num_rows() == 1)
						{
							$recipients[] = array($result->row('email') , $row['comment_id'], $result->row('screen_name') );
						}
					}
					elseif ($row['email'] != "")
					{
						$recipients[] = array($row['email'], $row['comment_id'], $row['name']);	
					}			
				}
			}
		}
				
		/** ----------------------------------------
		/**  Fetch Author Notification
		/** ----------------------------------------*/
				
		if ($author_notify == 'y')
		{
			$result = $this->EE->db->query("SELECT email FROM exp_members WHERE member_id = '$author_id'");
			$notify_address	.= ','.$result->row('email') ;
		}
		
		/** ----------------------------------------
		/**  Instantiate Typography class
		/** ----------------------------------------*/
	  
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		$this->EE->typography->parse_images = FALSE;
 		
		$comment = $this->EE->security->xss_clean($_POST['comment']);
		$comment = $this->EE->typography->parse_type( $comment, 
										array(
												'text_format'	=> 'none',
												'html_format'	=> 'none',
												'auto_links'	=> 'n',
												'allow_img_url' => 'n'
											)
									);
		
		/** ----------------------------
		/**  Send admin notification
		/** ----------------------------*/
		
		$name_of_commenter = $this->EE->security->xss_clean($_POST['name']);
		
		if ($notify_address != '')
		{		 
			// Deprecate the {name} variable at some point
			$swap = array(
							'name'				=> $name_of_commenter,
							'name_of_commenter'	=> $name_of_commenter,
							'gallery_name'		=> $gallery_name,
							'entry_title'		=> $entry_title,
							'comment_id'		=> $comment_id,
							'comment'			=> $comment,
							'comment_url'		=> $this->remove_session_id($_POST['RET'])
						 );
			
			$template = $this->EE->functions->fetch_email_template('admin_notify_gallery_comment');

			$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
			$email_msg = $this->EE->functions->var_swap($template['data'], $swap);
								
			// We don't want to send an admin notification if the person
			// leaving the comment is an admin in the notification list
			
			if ($_POST['email'] != '' && strpos($notify_address, $_POST['email']) !== FALSE)
			{
				$notify_address = str_replace($_POST['email'], "", $notify_address);				
			}
			
			$this->EE->load->helper('string');
			// Remove multiple commas
			$notify_address = reduce_multiples($notify_address, ',', TRUE);
			
			if ($notify_address != '')
			{			
				/** ----------------------------
				/**  Send email
				/** ----------------------------*/
				
				$this->EE->load->library('email');

				// Load the text helper
				$this->EE->load->helper('text');
				
				$replyto = ($data['email'] == '') ? $this->EE->config->item('webmaster_email') : $data['email'];
					 
				foreach (explode(',', $notify_address) as $addy)
				{	
					$this->EE->email->EE_initialize();	
					$this->EE->email->wordwrap = TRUE;
					$email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
					$email->to($addy);
					$email->reply_to($replyto); 
					$this->EE->email->subject($email_tit);	
					$this->EE->email->message(entities_to_ascii($email_msg));		
					$this->EE->email->send();
				}
			}
		}


		/** ----------------------------------------
		/**  Send user notifications
		/** ----------------------------------------*/
 
		if ($comment_moderate == 'n')
		{		
			$email_msg = '';
					
			if (count($recipients) > 0)
			{
				$action_id  = $this->EE->functions->fetch_action_id('Gallery', 'delete_comment_notification');
			
				$swap = array(
								'name_of_commenter'	=> $name_of_commenter,
								'gallery_name'		=> $gallery_name,
								'entry_title'		=> $entry_title,
								'site_name'			=> stripslashes($this->EE->config->item('site_name')),
								'site_url'			=> $this->EE->config->item('site_url'),
								'comment_url'		=> $this->remove_session_id($_POST['RET']),
								'comment_id'			=> $comment_id,
								'comment'			=> $comment
							 );
				
				$template = $this->EE->functions->fetch_email_template('gallery_comment_notification');
				$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
				$email_msg = $this->EE->functions->var_swap($template['data'], $swap);
	
				/** ----------------------------
				/**  Send email
				/** ----------------------------*/
				
				$this->EE->load->library('email');

				// Load the text helper
				$this->EE->load->helper('text');

				$this->EE->email->wordwrap = TRUE;
				
				$cur_email = ($_POST['email'] == '') ? FALSE : $_POST['email'];
				
				$sent = array();
				
				foreach ($recipients as $val)
				{
					// We don't notify the person currently commenting.  That would be silly.
					
					if ($val['0'] != $cur_email AND ! in_array($val['0'], $sent))
					{
						$title	 = $email_tit;
						$message = $email_msg;
						
						// Deprecate the {name} variable at some point
						$title	 = str_replace('{name}', $val['2'], $title);
						$message = str_replace('{name}', $val['2'], $message);

						$title	 = str_replace('{name_of_recipient}', $val['2'], $title);
						$message = str_replace('{name_of_recipient}', $val['2'], $message);
					
						$title	 = str_replace('{notification_removal_url}', $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$val['1'], $title);
						$message = str_replace('{notification_removal_url}', $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$val['1'], $message);
										
						$this->EE->email->EE_initialize();
						$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));	
						$this->EE->email->to($val['0']); 
						$this->EE->email->subject($title);	
						$this->EE->email->message(entities_to_ascii($message));		
						$this->EE->email->send();
						
						$sent[] = $val['0'];
					}
				}			
			}
		}

		/** ----------------------------------------
		/**  Clear cache files
		/** ----------------------------------------*/
				
		$this->EE->functions->clear_caching('all', $this->EE->functions->fetch_site_index().$_POST['URI']);
				
		/** ----------------------------------------
		/**  Set cookies
		/** ----------------------------------------*/
		
		if ($notify == 'y')
		{		
			$this->EE->functions->set_cookie('notify_me', 'yes', 60*60*24*365);
		}
		else
		{
			$this->EE->functions->set_cookie('notify_me', 'no', 60*60*24*365);
		}

		if ($this->EE->input->post('save_info'))
		{		
			$this->EE->functions->set_cookie('save_info',	'yes',			  60*60*24*365);
			$this->EE->functions->set_cookie('my_name',	 $_POST['name'],	 60*60*24*365);
			$this->EE->functions->set_cookie('my_email',	$_POST['email'],	60*60*24*365);
			$this->EE->functions->set_cookie('my_url',	  $_POST['url'],	  60*60*24*365);
			$this->EE->functions->set_cookie('my_location', $_POST['location'], 60*60*24*365);
		}
		else
		{
			$this->EE->functions->set_cookie('save_info',	'no', 60*60*24*365);
			$this->EE->functions->set_cookie('my_name',	 '');
			$this->EE->functions->set_cookie('my_email',	'');
			$this->EE->functions->set_cookie('my_url',	  '');
			$this->EE->functions->set_cookie('my_location', '');
		}

		/** -------------------------------------------
		/**  Bounce user back to the comment page
		/** -------------------------------------------*/
		
		if ($comment_moderate == 'y')
		{
			$data = array(	'title'	 	=> $this->EE->lang->line('cmt_comment_accepted'),
							'heading'	=> $this->EE->lang->line('thank_you'),
							'content'	=> $this->EE->lang->line('cmt_will_be_reviewed'),
							'redirect'	=> $_POST['RET'],							
							'link'		=> array($_POST['RET'], $this->EE->lang->line('cmt_return_to_comments'))
						 );
					
			$this->EE->output->show_message($data);
		}
		else
		{
				$this->EE->functions->redirect($_POST['RET']);
			}
	}

	
	
	/** --------------------------------
	/**  Delete comment notification
	/** --------------------------------*/
	function delete_comment_notification()
	{
		if ( ! $id = $this->EE->input->get_post('id'))
		{
			return FALSE;
		}
		
		$this->EE->lang->loadfile('comment');
		
		$query = $this->EE->db->query("SELECT entry_id, email FROM exp_gallery_comments WHERE comment_id = '".$this->EE->db->escape_str($id)."'");
		
		if ($query->num_rows() == 1)
		{ 
			$this->EE->db->query("UPDATE exp_gallery_comments SET notify = 'n' WHERE entry_id = '".$query->row('entry_id') ."' AND email = '".$query->row('email') ."'");
		}
				
		$data = array(	'title' 		=> $this->EE->lang->line('cmt_notification_removal'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('cmt_you_have_been_removed'),
						'redirect'	=> '',
						'link'		=> array($this->EE->config->item('site_url'), stripslashes($this->EE->config->item('site_name')))
					 	);
		
		$this->EE->output->show_message($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Remove session ID from string
	 *
	 * This function is used mainly by the Input class to strip
	 * session IDs if they are used in public pages.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function remove_session_id($str)
	{
		return preg_replace("#S=.+?/#", "", $str);
	} 


}
// END CLASS

/* End of file mod.gallery.php */
/* Location: ./system/expressionengine/modules/gallery/mod.gallery.php */