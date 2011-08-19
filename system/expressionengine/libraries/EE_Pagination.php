<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Email Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Pagination extends CI_Pagination
{
	public function __construct($params = array())
	{
		parent::__construct($params);
		
		$this->EE =& get_instance();
	}

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
		if ($this->EE->config->item('enable_query_strings') === TRUE OR $this->page_query_string === TRUE)
		{
			$this->base_url = rtrim($this->base_url).'&amp;'.$this->query_string_segment.'=';
		}
		else
		{
			$this->base_url = rtrim($this->base_url, '/') .'/';
		}

		// And here we go...
		$link_array = array();

		// Render the "First" link
		if  ($this->first_link !== FALSE AND $this->cur_page > ($this->num_links + 1))
		{
			$first_url = ($this->first_url == '') ? $this->base_url : $this->first_url;
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
				$i = ($loop * $this->per_page) - $this->per_page;

				if ($i >= 0)
				{
					$n = ($i == 0) ? '' : $i;

					if ($this->cur_page == $loop)
					{
						$link_array['page'][] = array(
							'pagination_url'			=> $this->base_url.$n,
							'pagination_page_number'	=> $loop,
							'current'					=> TRUE
						);
					}
					else if ($n == '' && $this->first_url != '')
					{
						$link_array['page'][] = array(
							'pagination_url'			=> $this->first_url,
							'pagination_page_number'	=> $loop
						);
					}
					else
					{
						$n = ($n == '') ? '' : $this->prefix.$n.$this->suffix;
						
						$link_array['page'][] = array(
							'pagination_url'			=> $this->base_url.$n,
							'pagination_page_number'	=> $loop
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
			$i = (($num_pages * $this->per_page) - $this->per_page);
			
			$link_array['last_page'][0] = array(
				'pagination_url'	=> $this->base_url.$this->prefix.$i.$this->suffix,
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
	
	private function _remove_double_slashes(&$array)
	{
		foreach ($array as $key => &$value)
		{
			if (isset($value[0]) AND is_array($value[0]))
			{
				$this->_remove_double_slashes($value);
			}
			else if ( ! empty($value['pagination_url']))
			{
				$value['pagination_url'] = preg_replace("#([^:])//+#", "\\1/", $value['pagination_url']);
			}
		}
	}
	
	private function _determine_current_page()
	{
		// Determine the current page number.
		if ($this->EE->config->item('enable_query_strings') === TRUE OR $this->page_query_string === TRUE)
		{
			if ($this->EE->input->get($this->query_string_segment) != 0)
			{
				$this->cur_page = $this->EE->input->get($this->query_string_segment);

				// Prep the current page - no funny business!
				$this->cur_page = (int) $this->cur_page;
			}
		}
		else
		{
			if ($this->EE->uri->segment($this->uri_segment) != 0)
			{
				$this->cur_page = $this->EE->uri->segment($this->uri_segment);

				// Prep the current page - no funny business!
				$this->cur_page = (int) $this->cur_page;
			}
		}
	}
	
	function something_else_entirely()
	{

		



	}
	

}
// END CLASS

/* End of file EE_Pagination.php */
/* Location: ./system/expressionengine/libraries/EE_Pagination.php */