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
 File: core.paginate.php
-----------------------------------------------------
 Purpose: This class creates links like this: 

	First < 3 4 [5] 6 7 > Last
=====================================================
*/
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}



class Paginate {

		var $base_url	 	= ''; // The page we are linking to (when using this class in the CP)
		var $path			= ''; // The page we are linking to (when using this class in a public page)
		var $prefix			= ''; // A custom prefix added to the path.
		var $suffix			= ''; // A custom suffix added to the path.
		var $qstr_var	 	= ''; // The name of the query string variable containing current page number
		var $cur_page	 	= ''; // The current page being viewed
		var $total_count  	= ''; // Total number of items (database results)
		var $per_page	 	= ''; // Max number of items you want shown per page
		var $max_links		=  2; // Number of "digit" links to show before/after the currently viewed page
		var $first_page		= '';
		var $last_page		= '';
		var $next_link		= '&gt;';
		var $prev_link		= '&lt;';
		var $first_marker	= '&laquo;';
		var $last_marker	= '&raquo;';
		var $first_url		= ''; // Alternative URL for the First Page.
		var $first_div_o	= '';
		var $first_div_c	= '';
		var $next_div_o		= '';
		var $next_div_c		= '';
		var $prev_div_o		= '';
		var $prev_div_c		= '';
		var $num_div_o		= '';
		var $num_div_c		= '';
		var $cur_div_o		= '';
		var $cur_div_c		= '';
		var $last_div_o		= '';
		var $last_div_c		= '';

	/** ----------------------------------------
	/**  Constructor
	/** ----------------------------------------*/
	function Paginate()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}


	  
	/** ----------------------------------------
	/**  Show links
	/** ----------------------------------------*/
	function show_links()
	{
		/** ----------------------------------------
		/**  Do we have links to show?
		/** ----------------------------------------*/
					
		// If our item count or per-page total is zero there is no need to continue
		
		if ($this->total_count == 0 OR $this->per_page == 0)
		{
			return;
		}
		
		/** ----------------------------------------
		/**  Define the base path
		/** ----------------------------------------*/
		
		// Since we can use this class in the CP or with public pages we need
		// to set up the path formatting a little different for each.  The CP
		// allows normal query strings but page URIs do not.
				
		$path  = ($this->path == '') ? $this->base_url.'&amp;'.$this->qstr_var.'=' : $this->path.'/';
		$slash = ($this->path == '') ? '' : '/';
				
		/** ----------------------------------------
		/**  Determine the total number of pages
		/** ----------------------------------------*/
		
		$num_pages = intval($this->total_count / $this->per_page);
		
		/** ----------------------------------------
		/**  Do we have an odd number of pages?
		/** ----------------------------------------*/
						
		// Use modulus to see if our division has a remainder.
		// If so, add one to our page number
		
		if ($this->total_count % $this->per_page) 
		{
			$num_pages++;
		}
		
		/** ----------------------------------------
		/**  Bail out if we only have one page
		/** ----------------------------------------*/
		
		if ($num_pages == 1)
		{
			return '';
		}
		
		/** ----------------------------------------
		/**  Set the base formatting
		/** ----------------------------------------*/
		
		if ($this->first_page == '')	$this->first_page = $this->first_marker.' '.$this->EE->lang->line('first');	
		if ($this->last_page == '')		$this->last_page  = $this->EE->lang->line('last').' '.$this->last_marker;	
		if ($this->next_div_o == '')	$this->next_div_o = '&nbsp;';
		if ($this->num_div_o == '')		$this->num_div_o = '&nbsp;';
		if ($this->cur_div_o == '')		$this->cur_div_o = '&nbsp;';
		if ($this->last_div_o == '')	$this->last_div_o = '&nbsp;&nbsp;';
		if ($this->prev_div_o == '')	$this->prev_div_o = '&nbsp;';
		if ($this->prev_div_c == '')	$this->prev_div_c = '&nbsp;'.$this->prev_div_c ;
		if ($this->first_div_c == '')	$this->first_div_c = '&nbsp;'.$this->first_div_c ;		
		
		/** ----------------------------------------
		/**  Determine the current page number
		/** ----------------------------------------*/
		
		// We'll round down the result, since certain combinations
		// can produce a fraction, messing up the links.
		
		$uri_page_number = $this->cur_page;
		$this->cur_page = floor(($this->cur_page/$this->per_page) + 1);
				
		/** ----------------------------------------
		/**  Calculate the start and end numbers
		/** ----------------------------------------*/
		// These determine which number to start and end the digit links with.
						
		$start = (($this->cur_page - $this->max_links) > 0) ? $this->cur_page - ($this->max_links - 1) : 1;
		$end	= (($this->cur_page + $this->max_links) < $num_pages) ? $this->cur_page + $this->max_links : $num_pages;
		
		$output = '';
			
		/** ----------------------------------------
		/**  Render the "First" link
		/** ----------------------------------------*/
				
		if  ($this->cur_page > ($this->max_links + 1))
		{
			$first_link = ($this->first_url == '') ? $path : $this->first_url;
			$output .= $this->first_div_o.'<a href="'.$first_link.$this->suffix.'">'.$this->first_page.'</a>'.$this->first_div_c;
		}
		
		/** ----------------------------------------
		/**  Render the "previous" link
		/** ----------------------------------------*/
		if  (($this->cur_page - $this->max_links) >= 0)
		{
			$i = $uri_page_number - $this->per_page;

			if ($this->path != '' AND $i == 0 AND REQ == 'CP') $i = '';
		
			$output .= $this->prev_div_o.'<a href="'.$path.$this->prefix.$i.$slash.$this->suffix.'">'.$this->prev_link.'</a>'.$this->prev_div_c;
		}
		
		/** ----------------------------------------
		/**  Write the digit links
		/** ----------------------------------------*/
		

		for ($loop = $start -1; $loop <= $end; $loop++) 
		{
			$i = ($loop * $this->per_page) - $this->per_page;
			
			if ($this->path != '' AND $i == 0 AND REQ == 'CP') $i = '';
		
			if ($i >= 0)
			{
				if ($this->cur_page == $loop)
				{
					$output .= $this->cur_div_o.'<strong>'.$loop.'</strong>'.$this->cur_div_c; // Current page
				}
				else
				{
					$output .= $this->num_div_o.'<a href="'.$path.$this->prefix.$i.$slash.$this->suffix.'">'.$loop.'</a>'.$this->num_div_c;
				}
			}
		} 
		
		/** ----------------------------------------
		/**  Render the "next" link
		/** ----------------------------------------*/
		if ($this->cur_page < $num_pages)
		{  
			$output .= $this->next_div_o.'<a href="'.$path.$this->prefix.($this->cur_page * $this->per_page).$slash.$this->suffix.'">'.$this->next_link.'</a>'.$this->next_div_c;		
		}
		
		/** ----------------------------------------
		/**  Render the "Last" link
		/** ----------------------------------------*/
		if (($this->cur_page + $this->max_links) < $num_pages)
		{
			$i = (($num_pages * $this->per_page) - $this->per_page);
		
			$output .= $this->last_div_o.'<a href="'.$path.$this->prefix.$i.$slash.$this->suffix.'">'.$this->last_page.'</a>'.$this->last_div_c;
		}
		
		/** ----------------------------------------
		/**  Return the result
		/** ----------------------------------------*/
	
		// Note: when using this class in public pages, the
		// "previous" link can end up with a double slash in the
		// penultimate link.  For that reason we will run the output
		// through the "remove double slashes" function
		
		return $this->EE->functions->remove_double_slashes($output);						
	}

}
// END CLASS

/* End of file lib.paginate.php */
/* Location: ./system/expressionengine/_to_be_replaced/lib.paginate.php */