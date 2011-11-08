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
	
	var $paginate				= FALSE;
	var $field_pagination		= FALSE;
	var $paginate_data			= '';
	var $pagination_links		= '';
	var $basepath				= '';
	var $page_next				= '';
	var $page_previous			= '';
	var $total_pages			= 1;
	var $multi_fields			= array();
	var $pager_sql				= '';
	
	
	public function __construct()
	{
		$this->EE =& get_instance();
		parent::__construct();
	}
	
	// ------------------------------------------------------------------------
	
	/**
	  * Fetch pagination data
	  * Determines if {paginate} is in the tagdata, if so flags that. Also 
	  * checks to see if paginate_type is field, if it is, then we look for 
	  * {multi_field="..."} and flag that.
	  * 
	  * The whole goal of this method is to see if we need to paginate and if 
	  * we do, extract the tags within pagination and put them in another variable
	  */
	public function fetch_pagination_data()
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
			
			// TODO: Change hook or check location
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
			
			$this->EE->TMPL->tagdata = preg_replace(
				"/".LD."paginate".RD.".+?".LD.'\/'."paginate".RD."/s", 
				"", 
				$this->EE->TMPL->tagdata
			);
			
			$this->paginate = TRUE;
			$this->paginate_data = $match[1];
		}
		
		return array(
			$this->paginate,
			$this->field_pagination
		);
	}

	// ------------------------------------------------------------------------

	/**
	  * This method takes the formed pagination_links and other sundry 
	  * pagination data and places it into the template
	  * - pagination_links
	  * - current_page
	  * - total_pages
	  * Conditionals
	  * - total_pages
	  * - previous_page
	  * - next_page
	  * Also determines if pagination goes at the top or the bottom, for this,
	  * we need the content to sandwich
	  */
	public function add_pagination_data($current_page)
	{
		if ($this->pagination_links == '')
		{
			return;
		}

		if ($this->paginate == TRUE)
		{
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
			
			// ----------------------------------------------------------------
			
			// Parse current_page and total_pages by default
			$parse_array = array(
				'current_page' => $current_page,
				'total_pages' => $this->total_pages,
			);
			
			// Parse current_page and total_pages
			$this->paginate_data = $this->EE->TMPL->parse_variables(
				$this->paginate_data,
				array($parse_array)
			);
			
			// ----------------------------------------------------------------
			
			// Parse {if previous_page}
			if (preg_match_all("/".LD."if previous_page".RD."(.+?)".LD.'\/'."if".RD."/s", $this->paginate_data, $matches))
			{
				if ($this->page_previous == '')
				{
					 $this->paginate_data = preg_replace(
						"/".LD."if previous_page".RD.".+?".LD.'\/'."if".RD."/s", 
						'', 
						$this->paginate_data
					);
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
			
			// ----------------------------------------------------------------
			
			// Parse {if next_page}
			// TODO: Too similar to {if previous_page}, abstract
			if (preg_match_all("/".LD."if next_page".RD."(.+?)".LD.'\/'."if".RD."/s", $this->paginate_data, $matches))
			{
				if ($this->page_next == '')
				{
					 $this->paginate_data = preg_replace(
						"/".LD."if next_page".RD.".+?".LD.'\/'."if".RD."/s", 
						'', 
						$this->paginate_data
					);
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
			
			$this->paginate_data = $this->EE->functions->prep_conditionals(
				$this->paginate_data, 
				array('total_pages' => $this->total_pages)
			);
			
			// ----------------------------------------------------------------

			// Determine if pagination needs to go at the top or bottom...
			$position = ( ! $this->EE->TMPL->fetch_param('paginate')) ? '' : $this->EE->TMPL->fetch_param('paginate');

			switch ($position)
			{
				case "top":
					return $this->paginate_data.$this->return_data;
					break;
				case "both":
					return $this->paginate_data.$this->return_data.$this->paginate_data;
					break;
				default:
					$this->return_data .= $this->paginate_data;
					break;
			}
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Here's the big kahuna
	 */
	function create_pagination($count = 0, &$basepath, &$per_page, &$current_page, $display_by, $total_rows, $query_string, $dynamic_sql, $query = '')
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
			// If template_group and template are being specified in the 
			// index.php and there's no other URI string, specify the basepath
			if (($this->EE->uri->uri_string == '' OR $this->EE->uri->uri_string == '/') 
				&& $this->EE->config->item('template_group') != '' 
				&& $this->EE->config->item('template') != '')
			{
				$basepath = $this->EE->functions->create_url(
					$this->EE->config->slash_item('template_group').'/'.$this->EE->config->item('template')
				);
			}
		
			// If basepath is still nothing, create the url from the uri_string
			// TODO: Do I need to pass in basepath then?
			if ($basepath == '')
			{
				$basepath = $this->EE->functions->create_url($this->EE->uri->uri_string);

				// Determine current page from the pagination segment
				if (preg_match("#^P(\d+)|/P(\d+)#", $query_string, $match))
				{
					$current_page = (isset($match[2])) ? $match[2] : $match[1];
					$basepath = $this->EE->functions->remove_double_slashes(
						str_replace($match[0], '', $basepath)
					);
				}
			}

			// Are we dealing with standard (not field) pagination?
			if ($this->field_pagination == FALSE)
			{
				// If we're not displaying by something, then we'll need 
				// something to paginate, otherwise if we're displaying by
				// something (week, day) it's okay for it to be empty
				if ($display_by == '')
				{
					if ($count == 0)
					{
						$this->sql = '';
						return;
					}

					$total_rows = $count;
				}

				// TODO: Continue documentation
				if ($dynamic_sql == FALSE)
				{
					$cat_limit = FALSE;
					if ((
							in_array($this->EE->config->item("reserved_category_word"), explode("/", $this->EE->uri->uri_string))
							AND $this->EE->TMPL->fetch_param('dynamic') != 'no'
							AND $this->EE->TMPL->fetch_param('channel')
						) OR (
							preg_match("#(^|\/)C(\d+)#", $this->EE->uri->uri_string, $match) 
							AND $this->EE->TMPL->fetch_param('dynamic') != 'no'
						)
					)
					{
						$cat_limit = TRUE;
					}

					if ($cat_limit AND is_numeric($this->EE->TMPL->fetch_param('cat_limit')))
					{
						$per_page = $this->EE->TMPL->fetch_param('cat_limit');
					}
					else
					{
						$per_page  = ( ! is_numeric($this->EE->TMPL->fetch_param('limit'))) ? 
							$this->limit : 
							$this->EE->TMPL->fetch_param('limit');
					}
				}

				$current_page = ($current_page == '' OR ($per_page > 1 AND $current_page == 1)) ? 0 : $current_page;

				if ($current_page > $total_rows)
				{
					$current_page = 0;
				}
								
				$current_page = floor(($current_page / $per_page) + 1);
				$this->total_pages = intval(floor($total_rows / $per_page));
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

				$per_page = 1;

				$total_rows = count($m_fields);

				$this->total_pages = $total_rows;

				if ($this->total_pages == 0)
				{
					$this->total_pages = 1;
				}

				$current_page = ($current_page == '') ? 0 : $current_page;

				if ($current_page > $total_rows)
				{
					$current_page = 0;
				}

				$current_page = floor(($current_page / $per_page) + 1);

				if (isset($m_fields[$current_page]))
				{
					$this->EE->TMPL->tagdata = preg_replace(
						"/".LD."multi_field\=[\"'].+?[\"']".RD."/s", 
						LD.$m_fields[$current_page].RD, 
						$this->EE->TMPL->tagdata
					);
					$this->EE->TMPL->var_single[$m_fields[$current_page]] = $m_fields[$current_page];
				}
			}

			//  Create the pagination
			if ($total_rows > 0 && $per_page > 0)
			{
				if ($total_rows % $per_page)
				{
					$this->total_pages++;
				}
			}

			// Last check to make sure we actually need to paginate
			if ($total_rows > $per_page)
			{
				if (strpos($basepath, SELF) === FALSE && $this->EE->config->item('site_index') != '')
				{
					$basepath .= SELF;
				}
				
				// Check to see if a paginate_base was provided
				if ($this->EE->TMPL->fetch_param('paginate_base'))
				{
					// Load the string helper
					$this->EE->load->helper('string');

					$basepath = $this->EE->functions->create_url(
						trim_slashes($this->EE->TMPL->fetch_param('paginate_base'))
					);
				}
				
				// Create the pagination!
				$config['first_url'] 	= rtrim($basepath, '/');
				$config['base_url']		= $basepath;
				$config['prefix']		= 'P';
				$config['total_rows'] 	= $total_rows;
				$config['per_page']		= $per_page;
				$config['cur_page']		= $current_page;
				$config['first_link'] 	= lang('pag_first_link');
				$config['last_link'] 	= lang('pag_last_link');
				$config['uri_segment']	= 0; // Allows $config['cur_page'] to override

				$this->initialize($config);
				$this->pagination_links = $this->create_links();
				$this->initialize($config); // Re-initialize to reset config
				$this->pagination_array = $this->create_link_array();
				
				// If a page_next should exist, create it
				if ((($this->total_pages * $per_page) - $per_page) > $current_page)
				{
					$this->page_next = reduce_double_slashes($basepath.'/P'.($current_page + $per_page));
				}

				// If a page_previous should exist, create it
				if (($current_page - $per_page) >= 0)
				{
					$this->page_previous = reduce_double_slashes($basepath.'/P'.($current_page - $per_page));
				}
			}
			else
			{
				$current_page = '';
			}
		}
		
	}
}

// END Pagination class

/* End of file Pagination.php */
/* Location: ./system/expressionengine/libraries/Pagination.php */