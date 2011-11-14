<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.4
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Pagination Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Pagination
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class EE_Pagination extends CI_Pagination {
	
	public function __construct()
	{
		$this->EE =& get_instance();
		parent::__construct();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Step 1 of Pagination: fetch_pagination_data()
	 * 
	 * Fetch pagination data
	 * Determines if {paginate} is in the tagdata, if so flags that. Also 
	 * checks to see if paginate_type is field, if it is, then we look for 
	 * {multi_field="..."} and flag that.
	 * 
	 * The whole goal of this method is to see if we need to paginate and if 
	 * we do, extract the tags within pagination and put them in another variable
	 */
	function get_template(&$pagination)
	{
		if ( ! is_object($pagination) OR empty($pagination))
		{
			$pagination = new Pagination_object();
		}
		
		// Quick check to see if {paginate} even exists
		if (strpos($this->EE->TMPL->tagdata, LD.'paginate'.RD) === FALSE) return;

		if (preg_match("/".LD."paginate".RD."(.+?)".LD.'\/'."paginate".RD."/s", $this->EE->TMPL->tagdata, $paginate_match))
		{
			if ($this->EE->TMPL->fetch_param('paginate_type') == 'field')
			{
				// If we're supposed to paginate over fields, check to see if
				// {multi_field="..."} exists. If it does capture the conetents
				// and flag this as field_pagination.
				if (preg_match("/".LD."multi_field\=[\"'](.+?)[\"']".RD."/s", $this->EE->TMPL->tagdata, $multi_field_match))
				{
					$pagination->multi_fields		= $this->EE->functions->fetch_simple_conditions($multi_field_match[1]);
					$pagination->field_pagination	= TRUE;
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
			
			// If {paginate} exists, flag for pagination and store the tags
			// within {paginate}
			$pagination->paginate		= TRUE;
			$pagination->paginate_data	= $paginate_match[1];
			
			// Remove pagination tags from template since we'll just 
			// append/prepend it later
			$this->EE->TMPL->tagdata = preg_replace(
				"/".LD."paginate".RD.".+?".LD.'\/'."paginate".RD."/s",
				"",
				$this->EE->TMPL->tagdata
			);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Build the pagination out, storing it in the Pagination_object
	 * 
	 * @param Pagination_object $pagination Pagination_object that has been
	 * 		manipulated by the other pagination methods
	 * @param integer $count Number of rows we're paginating over
	 * @param object $query Query object of the post you're field paginating over
	 */
	function build(&$pagination, $count = 0, &$main_query, $query = '')
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
		
		// Check again to see if we need to paginate
		if ($pagination->paginate == TRUE)
		{
			// If template_group and template are being specified in the 
			// index.php and there's no other URI string, specify the basepath
			if (($this->EE->uri->uri_string == '' OR $this->EE->uri->uri_string == '/') 
				&& $this->EE->config->item('template_group') != '' 
				&& $this->EE->config->item('template') != '')
			{
				$pagination->basepath = $this->EE->functions->create_url(
					$this->EE->config->slash_item('template_group').'/'.$this->EE->config->item('template')
				);
			}
			
			// If basepath is still nothing, create the url from the uri_string
			if ($pagination->basepath == '')
			{
				$pagination->basepath = $this->EE->functions->create_url($this->EE->uri->uri_string);
				$query_string = ($this->EE->uri->page_query_string != '') ? $this->EE->uri->page_query_string : $this->EE->uri->query_string;
				
				if (preg_match("#^P(\d+)|/P(\d+)#", $query_string, $match))
				{
					$pagination->offset = (isset($match[2])) ? $match[2] : $match[1];
					$pagination->basepath = $this->EE->functions->remove_double_slashes(
						str_replace($match[0], '', $pagination->basepath)
					);
				}
			}

			// Standard pagination, not field_pagination
			if ($pagination->field_pagination == FALSE)
			{
				// If we're not displaying by something, then we'll need 
				// something to paginate, otherwise if we're displaying by
				// something (week, day) it's okay for it to be empty
				if ($this->EE->TMPL->fetch_param('display_by') == '')
				{
					// If we're doing standard pagination and not using 
					// display_by, clear out the query and get out of here
					if ($count == 0)
					{
						$main_query = '';
						return;
					}

					$pagination->total_rows = $count;
				}
				
				// We need to establish the per_page limits if we're using 
				// cached SQL because limits are normally created when building
				// the SQL query
				if ($pagination->dynamic_sql == FALSE)
				{
					// Check to see if we can actually deal with cat_limit. Has
					// to have dynamic != 'no' and channel set with a category
					// in the uri_string somewhere
					$cat_limit = FALSE;
					if (
						(
							in_array($this->EE->config->item("reserved_category_word"), explode("/", $this->EE->uri->uri_string)) 
							OR preg_match("#(^|\/)C(\d+)#", $this->EE->uri->uri_string, $match)
						)
						AND $this->EE->TMPL->fetch_param('dynamic') != 'no'
						AND $this->EE->TMPL->fetch_param('channel')
					)
					{
						$cat_limit = TRUE;
					}

					if ($cat_limit AND is_numeric($this->EE->TMPL->fetch_param('cat_limit')))
					{
						$pagination->per_page = $this->EE->TMPL->fetch_param('cat_limit');
					}
					else
					{
						$pagination->per_page  = ( ! is_numeric($this->EE->TMPL->fetch_param('limit')))  ? '100' : $this->EE->TMPL->fetch_param('limit');
					}
				}
				
				$pagination->offset = ($pagination->offset == '' OR ($pagination->per_page > 1 AND $pagination->offset == 1)) ? 0 : $pagination->offset;

				// If we're far beyond where we should be, reset us back to 
				// the first page
				if ($pagination->offset > $pagination->total_rows)
				{
					$pagination->offset = 0;
				}
				
				$pagination->current_page	= floor(($pagination->offset / $pagination->per_page) + 1);
				$pagination->total_pages	= intval(floor($pagination->total_rows / $pagination->per_page));
			}
			else
			{
				//  Field pagination - base values

				// If we're doing field pagination and there's not even one
				// entry, then clear out the sql and get out of here
				if ($count == 0)
				{
					$main_query = '';
					return;
				}

				$m_fields = array();
				
				foreach ($pagination->multi_fields as $val)
				{
					foreach($pagination->cfields as $site_id => $cfields)
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

				$pagination->per_page = 1;

				$pagination->total_rows = count($m_fields);

				$pagination->total_pages = $pagination->total_rows;

				if ($pagination->total_pages == 0)
				{
					$pagination->total_pages = 1;
				}

				$pagination->offset = ($pagination->offset == '') ? 0 : $pagination->offset;

				if ($pagination->offset > $pagination->total_rows)
				{
					$pagination->offset = 0;
				}
				
				$pagination->current_page = floor(($pagination->offset / $pagination->per_page) + 1);

				if (isset($m_fields[$pagination->offset]))
				{
					$this->EE->TMPL->tagdata = preg_replace("/".LD."multi_field\=[\"'].+?[\"']".RD."/s", LD.$m_fields[$pagination->offset].RD, $this->EE->TMPL->tagdata);
					$this->EE->TMPL->var_single[$m_fields[$pagination->offset]] = $m_fields[$pagination->offset];
				}
			}

			//  Create the pagination
			if ($pagination->total_rows > 0 && $pagination->per_page > 0)
			{
				if ($pagination->total_rows % $pagination->per_page)
				{
					$pagination->total_pages++;
				}
			}
			
			// Last check to make sure we actually need to paginate
			if ($pagination->total_rows > $pagination->per_page)
			{
				if (strpos($pagination->basepath, SELF) === FALSE && $this->EE->config->item('site_index') != '')
				{
					$pagination->basepath .= SELF;
				}
				
				// Check to see if a paginate_base was provided
				if ($this->EE->TMPL->fetch_param('paginate_base'))
				{
					$this->EE->load->helper('string');

					$pagination->basepath = $this->EE->functions->create_url(
						trim_slashes($this->EE->TMPL->fetch_param('paginate_base'))
					);
				}
				
				$config['first_url'] 	= rtrim($pagination->basepath, '/');
				$config['base_url']		= $pagination->basepath;
				$config['prefix']		= 'P';
				$config['total_rows'] 	= $pagination->total_rows;
				$config['per_page']		= $pagination->per_page;
				// cur_page uses the offset because P45 (or similar) is a page
				$config['cur_page']		= $pagination->offset;
				$config['first_link'] 	= lang('pag_first_link');
				$config['last_link'] 	= lang('pag_last_link');
				$config['uri_segment']	= 0; // Allows $config['cur_page'] to override

				$this->EE->pagination->initialize($config);
				$pagination->pagination_links = $this->EE->pagination->create_links();
				$this->EE->pagination->initialize($config); // Re-initialize to reset config
				$pagination->pagination_array = $this->EE->pagination->create_link_array();

				// If a page_next should exist, create it
				if ((($pagination->total_pages * $pagination->per_page) - $pagination->per_page) > $pagination->offset)
				{
					$pagination->page_next = reduce_double_slashes($pagination->basepath.'/P'.($pagination->offset + $pagination->per_page));
				}

				// If a page_previous should exist, create it
				if (($pagination->offset - $pagination->per_page ) >= 0)
				{
					$pagination->page_previous = reduce_double_slashes($pagination->basepath.'/P'.($pagination->offset - $pagination->per_page));
				}
			}
			else
			{
				$pagination->offset = '';
			}
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Renders all of the pagination data in the current template.
	 * 
	 * Variable Pairs:
	 * - pagination_links
	 * 
	 * Single Variables:
	 * - current_page
	 * - total_pages
	 * 
	 * Conditionals:
	 * - total_pages
	 * - previous_page
	 * - next_page
	 * 
	 * @param Pagination_object $pagination Pagination_object that has been
	 * 		manipulated by the other pagination methods
	 * @param string $return_data The final template data to wrap the 
	 * 		pagination around
	 * @return string The $return_data with the pagination data either above,
	 * 		below or both above and below
	 */
	function render(&$pagination, $return_data)
	{
		if ($pagination->pagination_links == '')
		{
			return $return_data;
		}
		
		if ($pagination->paginate == TRUE)
		{
			$parse_array = array();
			
			// Check to see if pagination_links is being used as a single 
			// variable or as a variable pair
			if (strpos($pagination->paginate_data, LD.'/pagination_links'.RD) !== FALSE)
			{
				$parse_array['pagination_links'] = array($pagination->pagination_array);
			}
			else
			{
				$parse_array['pagination_links'] = $pagination->pagination_links;
			}
			
			// ----------------------------------------------------------------
			
			// Parse current_page and total_pages by default
			$parse_array['current_page']	= $pagination->current_page;
			$parse_array['total_pages']		= $pagination->total_pages;
			
			// Parse current_page and total_pages
			$pagination->paginate_data = $this->EE->TMPL->parse_variables(
				$pagination->paginate_data,
				array($parse_array)
			);
			
			// ----------------------------------------------------------------
			
			// Parse {if previous_page} and {if next_page}
			$this->_parse_conditional($pagination, 'previous', $pagination->page_previous);
			$this->_parse_conditional($pagination, 'next', $pagination->page_next);
			
			// ----------------------------------------------------------------
			
			// Parse if total_pages conditionals
			$pagination->paginate_data = $this->EE->functions->prep_conditionals(
				$pagination->paginate_data, 
				array('total_pages' => $pagination->total_pages)
			);
			
			// ----------------------------------------------------------------
			
			// Determine if pagination needs to go at the top and/or bottom
			$position = ( ! $this->EE->TMPL->fetch_param('paginate')) ? '' : $this->EE->TMPL->fetch_param('paginate');
			
			switch ($position)
			{
				case "top":
					return $pagination->paginate_data.$return_data;
					break;
				case "both":
					return $pagination->paginate_data.$return_data.$pagination->paginate_data;
					break;
				case "bottom":
				default:
					return $return_data.$pagination->paginate_data;
					break;
			}
		}
		
		return $return_data;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Parse {if previous_page} and {if next_page}
	 * 
	 * @param Pagination_object $pagination Pagination_object that has been
	 * 		manipulated by the other pagination methods
	 * @param string $type Either 'next' or 'previous' depending on the 
	 * 		conditional you're looking for
	 * @param string $replacement What to replace $type_page with
	 */
	private function _parse_conditional(&$pagination, $type, $replacement)
	{
		if (preg_match_all("/".LD."if {$type}_page".RD."(.+?)".LD.'\/'."if".RD."/s", $pagination->paginate_data, $matches))
		{
			if ($replacement == '')
			{
				 $pagination->paginate_data = preg_replace("/".LD."if {$type}_page".RD.".+?".LD.'\/'."if".RD."/s", '', $pagination->paginate_data);
			}
			else
			{
				foreach($matches[1] as $count => $match)
				{
					$match = preg_replace("/".LD.'path.*?'.RD."/", $replacement, $match);
					$match = preg_replace("/".LD.'auto_path'.RD."/", $replacement, $match);

					$pagination->paginate_data = str_replace($matches[0][$count], $match, $pagination->paginate_data);
				}
			}
		}
	}
}

/**
 * 
 */
class Pagination_object {
	public $paginate			= FALSE;
	public $paginate_data		= '';
	public $field_pagination	= FALSE;
	public $multi_fields		= '';
	public $total_pages			= 1;
	public $current_page		= 1;
	public $offset				= '';
	public $page_next			= '';
	public $page_previous		= '';
	public $pagination_links	= '';
	public $pagination_array	= array();
	public $total_rows			= 0;
	public $per_page			= 0;
	public $basepath			= '';
	public $cfields				= array();
	public $type				= '';
	
	public function __construct($classname)
	{
		$this->type = $classname;
	}
}

// END Pagination class

/* End of file Pagination.php */
/* Location: ./system/expressionengine/libraries/Pagination.php */