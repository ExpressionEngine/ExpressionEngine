<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2013, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Pagination Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Pagination
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/pagination.html
 */
class CI_Pagination {

	var $base_url			= ''; // The page we are linking to
	var $prefix				= ''; // A custom prefix added to the path.
	var $suffix				= ''; // A custom suffix added to the path.

	var $total_rows			= ''; // Total number of items (database results)
	var $per_page			= 10; // Max number of items you want shown per page
	var $num_links			=  2; // Number of "digit" links to show before/after the currently viewed page
	var $cur_page			=  0; // The current page being viewed
	var $first_link			= '&lsaquo; First';
	var $next_link			= '&gt;';
	var $prev_link			= '&lt;';
	var $last_link			= 'Last &rsaquo;';
	var $uri_segment		= 3;
	var $full_tag_open		= '';
	var $full_tag_close		= '';
	var $first_tag_open		= '';
	var $first_tag_close	= '&nbsp;';
	var $last_tag_open		= '&nbsp;';
	var $last_tag_close		= '';
	var $first_url			= ''; // Alternative URL for the First Page.
	var $cur_tag_open		= '&nbsp;<strong>';
	var $cur_tag_close		= '</strong>';
	var $next_tag_open		= '&nbsp;';
	var $next_tag_close		= '&nbsp;';
	var $prev_tag_open		= '&nbsp;';
	var $prev_tag_close		= '';
	var $num_tag_open		= '&nbsp;';
	var $num_tag_close		= '';
	var $page_query_string	= FALSE;
	var $query_string_segment = 'per_page';
	var $display_pages		= TRUE;
	var $anchor_class		= '';

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	array	initialization parameters
	 */
	public function __construct($params = array())
	{
		$this->CI =& get_instance();
		
		if (count($params) > 0)
		{
			$this->initialize($params);
		}

		if ($this->anchor_class != '')
		{
			$this->anchor_class = 'class="'.$this->anchor_class.'" ';
		}

		log_message('debug', "Pagination Class Initialized");
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize Preferences
	 *
	 * @access	public
	 * @param	array	initialization parameters
	 * @return	void
	 */
	function initialize($params = array())
	{
		if (count($params) > 0)
		{
			foreach ($params as $key => $val)
			{
				if (isset($this->$key))
				{
					$this->$key = $val;
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Generate the pagination links
	 *
	 * @access	public
	 * @return	string
	 */
	function create_links()
	{
		$link_array = $this->create_link_array();

		// And here we go...
		$output = '';

		// No links to render
		if (empty($link_array))
		{
			return $output;
		}

		// Render the "First" link
		if  ( ! empty($link_array['first_page'][0]))
		{
			$first_page = $link_array['first_page'][0];
			
			$output .= $this->first_tag_open.'<a '.$this->anchor_class.'href="'.$first_page['pagination_url'].'">'.$first_page['text'].'</a>'.$this->first_tag_close;
		}

		// Render the "previous" link
		if  ( ! empty($link_array['previous_page'][0]))
		{
			$previous_page = $link_array['previous_page'][0];
			
			$output .= $this->prev_tag_open.'<a '.$this->anchor_class.'href="'.$previous_page['pagination_url'].'">'.$previous_page['text'].'</a>'.$this->prev_tag_close;
		}

		// Render the pages
		if ($this->display_pages !== FALSE AND ! empty($link_array['page']))
		{
			// Write the digit links
			foreach ($link_array['page'] as $current_page)
			{
				if ($current_page['current_page'])
				{
					$output .= $this->cur_tag_open.$current_page['pagination_page_number'].$this->cur_tag_close; // Current page
				}
				else
				{
					$output .= $this->num_tag_open.'<a '.$this->anchor_class.'href="'.$current_page['pagination_url'].'">'.$current_page['pagination_page_number'].'</a>'.$this->num_tag_close;
				}
			}
		}

		// Render the "next" link
		if ( ! empty($link_array['next_page'][0]))
		{
			$next_page = $link_array['next_page'][0];
			
			$output .= $this->next_tag_open.'<a '.$this->anchor_class.'href="'.$next_page['pagination_url'].'">'.$next_page['text'].'</a>'.$this->next_tag_close;
		}
		
		// Render the "Last" link
		if ( ! empty($link_array['last_page'][0]))
		{
			$last_page = $link_array['last_page'][0];
			
			$output .= $this->last_tag_open.'<a '.$this->anchor_class.'href="'.$last_page['pagination_url'].'">'.$last_page['text'].'</a>'.$this->last_tag_close;
		}

		// Add the wrapper HTML if exists
		$output = $this->full_tag_open.$output.$this->full_tag_close;

		return $output;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Create's an array of pagination links including the first, previous,
	 * next, and last page links
	 * 
	 * @return array Associative array ready to go straight into EE's 
	 * template parser
	 */
	public function create_link_array()
	{
		// If our item count or per-page total is zero there is no need to continue.
		if ($this->total_rows == 0 OR $this->per_page == 0)
		{
			return '';
		}

		// Calculate the total number of pages
		$num_pages = ceil($this->total_rows / $this->per_page);

		// Is there only one page? Hm... nothing more to do here then.
		if ($num_pages == 1)
		{
			return '';
		}
		
		$this->_determine_current_page();
		
		// Figure out the number of links to show
		$this->num_links = (int) $this->num_links;
		
		if ($this->num_links < 1)
		{
			show_error('Your number of links must be a positive number.');
		}

		if ( ! is_numeric($this->cur_page))
		{
			$this->cur_page = 0;
		}
		
		// Is the page number beyond the result range?
		// If so we show the last page
		if ($this->cur_page > $this->total_rows)
		{
			$this->cur_page = ($num_pages - 1) * $this->per_page;
		}
		
		$uri_page_number = $this->cur_page;
		$this->cur_page = floor(($this->cur_page/$this->per_page) + 1);
		
		// Calculate the start and end numbers. These determine
		// which number to start and end the digit links with
		$start = (($this->cur_page - $this->num_links) > 0) ? $this->cur_page - ($this->num_links - 1) : 1;
		$end   = (($this->cur_page + $this->num_links) < $num_pages) ? $this->cur_page + $this->num_links : $num_pages;

		// Is pagination being used over GET or POST?  If get, add a per_page query
		// string. If post, add a trailing slash to the base URL if needed
		if ($this->CI->config->item('enable_query_strings') === TRUE OR $this->page_query_string === TRUE)
		{
			$this->base_url = rtrim($this->base_url).'&amp;'.$this->query_string_segment.'=';
		}
		else
		{
			$this->base_url = rtrim($this->base_url, '/') .'/';
		}

		// And here we go...
		$link_array = array();

		$first_url = ($this->first_url == '') ? $this->base_url : $this->first_url;

		// Render the "First" link
		if  ($this->first_link !== FALSE AND $this->cur_page > ($this->num_links + 1))
		{
			$link_array['first_page'][0] = array(
				'pagination_url'	=> $first_url,
				'text'				=> $this->first_link
			);
		}
		else
		{
			$link_array['first_page'][0] = array();
		}

		// Render the "previous" link
		if  ($this->prev_link !== FALSE AND $this->cur_page != 1)
		{
			$i = $uri_page_number - $this->per_page;
			
			if ($i == 0 && $this->first_url != '')
			{
				$link_array['previous_page'][0] = array(
					'pagination_url'	=> $this->first_url,
					'text'				=> $this->prev_link
				);
			}
			else
			{
				$i = ($i == 0) ? '' : $this->prefix.$i.$this->suffix;
				$link_array['previous_page'][0] = array(
					'pagination_url'	=> $this->base_url.$i,
					'text'				=> $this->prev_link
				);
			}
		}
		else
		{
			$link_array['previous_page'][0] = array();
		}

		// Render the pages
		if ($this->display_pages !== FALSE)
		{
			// Write the digit links
			for ($loop = $start -1; $loop <= $end; $loop++)
			{
				$offset = ($loop * $this->per_page) - $this->per_page;

				if ($offset >= 0)
				{
					$prepped_offset = ($offset == 0) ? '' : $offset;

					if ($this->cur_page == $loop)
					{
						$prepped_offset = ($prepped_offset == '') ? '' : $this->prefix.$prepped_offset.$this->suffix;
						
						$link_array['page'][] = array(
							'pagination_url'			=> ($prepped_offset == '') ? $first_url : $this->base_url.$prepped_offset,
							'pagination_page_number'	=> $loop,
							'current_page'				=> TRUE
						);
					}
					else if ($prepped_offset == '' && $this->first_url != '')
					{
						$link_array['page'][] = array(
							'pagination_url'			=> $first_url,
							'pagination_page_number'	=> $loop,
							'current_page'				=> FALSE
						);
					}
					else
					{
						$prepped_offset = ($prepped_offset == '') ? '' : $this->prefix.$prepped_offset.$this->suffix;
						
						$link_array['page'][] = array(
							'pagination_url'			=> $this->base_url.$prepped_offset,
							'pagination_page_number'	=> $loop,
							'current_page'				=> FALSE
						);
					}
				}
			}
		}

		// Render the "next" link
		if ($this->next_link !== FALSE AND $this->cur_page < $num_pages)
		{
			$link_array['next_page'][0] = array(
				'pagination_url'	=> $this->base_url.$this->prefix.($this->cur_page * $this->per_page).$this->suffix,
				'text'				=> $this->next_link
			);
		}
		else
		{
			$link_array['next_page'][0] = array();
		}

		// Render the "Last" link
		if ($this->last_link !== FALSE AND ($this->cur_page + $this->num_links) < $num_pages)
		{
			$offset = (($num_pages * $this->per_page) - $this->per_page);
			
			$link_array['last_page'][0] = array(
				'pagination_url'	=> $this->base_url.$this->prefix.$offset.$this->suffix,
				'text'				=> $this->last_link
			);
		}
		else
		{
			$link_array['last_page'][0] = array();
		}

		$this->_remove_double_slashes($link_array);

		return $link_array;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Remove doubles lashes from URLs
	 * 
	 * @param array $array (Passed by reference) Array that will be modified
	 * 	and all pagination_url array items will have double slashes removed
	 * 	from the URLs
	 */
	private function _remove_double_slashes(&$array)
	{
		$this->CI->load->helper('string_helper');

		foreach ($array as $key => &$value)
		{
			if (isset($value[0]) AND is_array($value[0]))
			{
				$this->_remove_double_slashes($value);
			}
			else if ( ! empty($value['pagination_url']))
			{
				$value['pagination_url'] = reduce_double_slashes($value['pagination_url']);
			}
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Determine's the current page number using either the query string 
	 * segments or the URI segments
	 */
	private function _determine_current_page()
	{		
		// Determine the current page number.
		if ($this->CI->config->item('enable_query_strings') === TRUE OR $this->page_query_string === TRUE)
		{
			if ($this->CI->input->get($this->query_string_segment) != 0)
			{
				$this->cur_page = $this->CI->input->get($this->query_string_segment);

				// Prep the current page - no funny business!
				$this->cur_page = (int) $this->cur_page;
			}
		}
		else
		{
			if ($this->CI->uri->segment($this->uri_segment) != 0)
			{
				$this->cur_page = $this->CI->uri->segment($this->uri_segment);

				// Prep the current page - no funny business!
				$this->cur_page = (int) $this->cur_page;
			}
		}
	}
}
// END Pagination Class

/* End of file Pagination.php */
/* Location: ./system/libraries/Pagination.php */