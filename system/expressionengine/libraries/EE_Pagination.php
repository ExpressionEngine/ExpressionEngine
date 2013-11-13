<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class EE_Pagination extends CI_Pagination {

	public function create($classname)
	{
		return new Pagination_object($classname);
	}

}

/**
 * Pagination object created for each instance of pagination.
 */
class Pagination_object {
	public $paginate			= FALSE;
	public $field_pagination	= FALSE;
	public $current_page		= 1;
	public $offset				= 0;
	public $total_rows			= 0;
	public $per_page			= 0;
	public $basepath			= '';
	public $cfields				= array();
	public $dynamic_sql			= TRUE;

	private $_page_array			= array();
	private $_total_pages			= 1;
	private $_pagination_template	= '';
	private $_multi_fields			= '';
	private $_page_next				= '';
	private $_page_previous			= '';
	private $_page_links			= '';
	private $_type					= '';
	private $_position				= '';
	private $_pagination_marker		= "pagination_marker";
	private $_template				= '';

	public function __construct($classname)
	{
		$this->_type = $classname;
		ee()->load->library('pagination');
		ee()->load->library('template', NULL, 'TMPL');
		$this->_template = &ee()->TMPL->tagdata;
	}

	// -------------------------------------------------------------------------

	public function __set($name, $value)
	{
		// Keep certain variables private
		if (in_array($name, array('template', 'position')))
		{
			$this->{'_'.$name} = $value;
		}
		else if (strncmp($name, '_', 1) == 0)
		{
			throw new Exception('Can not access private properties.');
		}

		$this->$name = $value;
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
	 *
	 * @return String The template with the pagination removed
	 */
	function get_template()
	{
		// First get the template as we currently know it
		$template = &$this->_template;

		// Quick check to see if {paginate} even exists
		if (strpos($template, LD.'paginate'.RD) === FALSE) return $template;

		if (preg_match("/".LD."paginate".RD."(.+?)".LD.'\/'."paginate".RD."/s", $template, $paginate_match))
		{
			if (ee()->TMPL->fetch_param('paginate_type') == 'field')
			{
				// If we're supposed to paginate over fields, check to see if
				// {multi_field="..."} exists. If it does capture the conetents
				// and flag this as field_pagination.
				if (preg_match("/".LD."multi_field\=[\"'](.+?)[\"']".RD."/s", $template, $multi_field_match))
				{
					$this->_multi_fields		= ee()->functions->fetch_simple_conditions($multi_field_match[1]);
					$this->field_pagination	= TRUE;
				}
			}

			// -------------------------------------------
			// 'channel_module_fetch_pagination_data' hook.
			//  - Works with the 'channel_module_create_pagination' hook
			//  - Developers, if you want to modify the $this object remember
			//	to use a reference on function call.
			//
				if (ee()->extensions->active_hook('channel_module_fetch_pagination_data') === TRUE)
				{
					ee()->extensions->universal_call('channel_module_fetch_pagination_data', $this);
					if (ee()->extensions->end_script === TRUE) return;
				}
			//
			// -------------------------------------------

			// If {paginate} exists, flag for pagination and store the tags
			// within {paginate}
			$this->paginate		= TRUE;
			$this->_pagination_template	= $paginate_match[1];

			// Determine if pagination needs to go at the top and/or bottom, or inline
			$this->_position = ee()->TMPL->fetch_param('paginate', $this->_position);

			// Create temporary marker for inline position
			$replace_tag = ($this->_position == 'inline') ? LD.$this->_pagination_marker.RD : '';

			// Remove pagination tags from template since we'll just
			// append/prepend it later
			$template = preg_replace(
				"/".LD."paginate".RD.".+?".LD.'\/'."paginate".RD."/s",
				$replace_tag,
				$template
			);
		}

		return $template;
	}

	// ------------------------------------------------------------------------

	/**
	 * Build the pagination out, storing it in the Pagination_object
	 *
	 * @param integer $count Number of rows we're paginating over
	 * @param object $query Query object of the post you're field paginating over
	 */
	function build($count = 0, &$main_query = '', $query = '')
	{
		$this->total_rows = $count;

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
			if (ee()->extensions->active_hook('channel_module_create_pagination') === TRUE)
			{
				ee()->extensions->universal_call('channel_module_create_pagination', $this, $count);
				if (ee()->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------

		// Check again to see if we need to paginate
		if ($this->paginate == TRUE)
		{
			// If template_group and template are being specified in the
			// index.php and there's no other URI string, specify the basepath
			if ((ee()->uri->uri_string == '' OR ee()->uri->uri_string == '/')
				&& ee()->config->item('template_group') != ''
				&& ee()->config->item('template') != '')
			{
				$this->basepath = ee()->functions->create_url(
					ee()->config->slash_item('template_group').'/'.ee()->config->item('template')
				);
			}

			// If basepath is still nothing, create the url from the uri_string
			if ($this->basepath == '')
			{
				$this->basepath = ee()->functions->create_url(ee()->uri->uri_string);
			}

			// Determine the offset
			if ($this->offset === 0)
			{
				$query_string = (ee()->uri->page_query_string != '') ? ee()->uri->page_query_string : ee()->uri->query_string;
				if (preg_match("#^P(\d+)|/P(\d+)#", $query_string, $match))
				{
					$this->offset = (isset($match[2])) ? (int) $match[2] : (int) $match[1];
					$this->basepath = reduce_double_slashes(
						str_replace($match[0], '', $this->basepath)
					);
				}
			}

			// Standard pagination, not field_pagination
			if ($this->field_pagination == FALSE)
			{
				// If we're not displaying by something, then we'll need
				// something to paginate, otherwise if we're displaying by
				// something (week, day) it's okay for it to be empty
				if ($this->_type === "Channel" AND ee()->TMPL->fetch_param('display_by') == '')
				{
					// If we're doing standard pagination and not using
					// display_by, clear out the query and get out of here
					if ($count == 0)
					{
						$main_query = '';
						return;
					}

					$this->total_rows = $count;
				}

				// We need to establish the per_page limits if we're using
				// cached SQL because limits are normally created when building
				// the SQL query
				if ($this->dynamic_sql == FALSE)
				{
					// Check to see if we can actually deal with cat_limit. Has
					// to have dynamic != 'no' and channel set with a category
					// in the uri_string somewhere
					$cat_limit = FALSE;
					if (
						(
							in_array(ee()->config->item("reserved_category_word"), explode("/", ee()->uri->uri_string))
							OR preg_match("#(^|\/)C(\d+)#", ee()->uri->uri_string, $match)
						)
						AND ee()->TMPL->fetch_param('dynamic') != 'no'
						AND ee()->TMPL->fetch_param('channel')
					)
					{
						$cat_limit = TRUE;
					}

					if ($cat_limit AND is_numeric(ee()->TMPL->fetch_param('cat_limit')))
					{
						$this->per_page = ee()->TMPL->fetch_param('cat_limit');
					}
					else
					{
						$this->per_page  = ( ! is_numeric(ee()->TMPL->fetch_param('limit')))  ? '100' : ee()->TMPL->fetch_param('limit');
					}
				}

				$this->offset = ($this->offset == '' OR ($this->per_page > 1 AND $this->offset == 1)) ? 0 : $this->offset;

				// If we're far beyond where we should be, reset us back to
				// the first page
				if ($this->offset > $this->total_rows)
				{
					$this->offset = 0;
				}

				$this->current_page	= floor(($this->offset / $this->per_page) + 1);
				$this->_total_pages	= intval(floor($this->total_rows / $this->per_page));
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

				foreach ($this->_multi_fields as $val)
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

				$this->per_page = 1;

				$this->total_rows = count($m_fields);

				$this->_total_pages = $this->total_rows;

				if ($this->_total_pages == 0)
				{
					$this->_total_pages = 1;
				}

				$this->offset = ($this->offset == '') ? 0 : $this->offset;

				if ($this->offset > $this->total_rows)
				{
					$this->offset = 0;
				}

				$this->current_page = floor(($this->offset / $this->per_page) + 1);

				if (isset($m_fields[$this->offset]))
				{
					ee()->TMPL->tagdata = preg_replace("/".LD."multi_field\=[\"'].+?[\"']".RD."/s", LD.$m_fields[$this->offset].RD, ee()->TMPL->tagdata);
					ee()->TMPL->var_single[$m_fields[$this->offset]] = $m_fields[$this->offset];
				}
			}

			//  Create the pagination
			if ($this->total_rows > 0 && $this->per_page > 0)
			{
				if ($this->total_rows % $this->per_page)
				{
					$this->_total_pages++;
				}
			}

			// Last check to make sure we actually need to paginate
			if ($this->total_rows > $this->per_page)
			{
				if (strpos($this->basepath, SELF) === FALSE && ee()->config->item('site_index') != '')
				{
					$this->basepath .= SELF;
				}

				// Check to see if a paginate_base was provided
				if (ee()->TMPL->fetch_param('paginate_base'))
				{
					$this->basepath = ee()->functions->create_url(
						trim_slashes(ee()->TMPL->fetch_param('paginate_base'))
					);
				}

				$config['first_url'] 	= rtrim($this->basepath, '/');
				$config['base_url']		= $this->basepath;
				$config['prefix']		= 'P';
				$config['total_rows'] 	= $this->total_rows;
				$config['per_page']		= $this->per_page;
				// cur_page uses the offset because P45 (or similar) is a page
				$config['cur_page']		= $this->offset;
				$config['first_link'] 	= lang('pag_first_link');
				$config['last_link'] 	= lang('pag_last_link');
				$config['uri_segment']	= 0; // Allows $config['cur_page'] to override

				ee()->pagination->initialize($config);
				$this->_page_links = ee()->pagination->create_links();
				ee()->pagination->initialize($config); // Re-initialize to reset config
				$this->_page_array = ee()->pagination->create_link_array();

				// If a page_next should exist, create it
				if ((($this->_total_pages * $this->per_page) - $this->per_page) > $this->offset)
				{
					$this->_page_next = reduce_double_slashes($this->basepath.'/P'.($this->offset + $this->per_page));
				}

				// If a page_previous should exist, create it
				if (($this->offset - $this->per_page ) >= 0)
				{
					$this->_page_previous = reduce_double_slashes($this->basepath.'/P'.($this->offset - $this->per_page));
				}
			}
			else
			{
				$this->offset = 0;
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Renders all of the pagination data in the current template.
	 *
	 * Variable Pairs:
	 * - page_links
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
	 * @param string $return_data The final template data to wrap the
	 * 		pagination around
	 * @return string The $return_data with the pagination data either above,
	 * 		below or both above and below
	 */
	function render($return_data)
	{
		if ($this->_page_links == '')
		{
			// If there's no paginating to do and we're inline, remove the
			// pagination_marker
			if ($this->_position == 'inline')
			{
				return ee()->TMPL->swap_var_single(
					$this->_pagination_marker,
					'',
					$return_data
				);
			}

			return $return_data;
		}

		if ($this->paginate == TRUE)
		{
			$parse_array = array();

			// Check to see if page_links is being used as a single
			// variable or as a variable pair
			if (strpos($this->_pagination_template, LD.'/pagination_links'.RD) !== FALSE)
			{
				$parse_array['pagination_links'] = array($this->_page_array);
			}
			else
			{
				$parse_array['pagination_links'] = $this->_page_links;
			}

			// ----------------------------------------------------------------

			// Parse current_page and total_pages by default
			$parse_array['current_page']	= $this->current_page;
			$parse_array['total_pages']		= $this->_total_pages;

			// Parse current_page and total_pages
			$this->_pagination_template = ee()->TMPL->parse_variables(
				$this->_pagination_template,
				array($parse_array),
				FALSE // Disable backspace parameter so pagination markup is protected
			);

			// ----------------------------------------------------------------

			// Parse {if previous_page} and {if next_page}
			$this->_parse_conditional('previous', $this->_page_previous);
			$this->_parse_conditional('next', $this->_page_next);

			// ----------------------------------------------------------------

			// Parse if total_pages conditionals
			$this->_pagination_template = ee()->functions->prep_conditionals(
				$this->_pagination_template,
				array('total_pages' => $this->_total_pages)
			);

			// ----------------------------------------------------------------

			switch ($this->_position)
			{
				case "top":
					return $this->_pagination_template.$return_data;
					break;
				case "both":
					return $this->_pagination_template.$return_data.$this->_pagination_template;
					break;
				case "inline":
					return ee()->TMPL->swap_var_single(
						$this->_pagination_marker,
						$this->_pagination_template,
						$return_data
					);
					break;
				return $return_data;
				break;
				case "bottom":
				default:
					return $return_data.$this->_pagination_template;
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
	private function _parse_conditional($type, $replacement)
	{
		if (preg_match_all("/".LD."if {$type}_page".RD."(.+?)".LD.'\/'."if".RD."/s", $this->_pagination_template, $matches))
		{
			if ($replacement == '')
			{
				 $this->_pagination_template = preg_replace("/".LD."if {$type}_page".RD.".+?".LD.'\/'."if".RD."/s", '', $this->_pagination_template);
			}
			else
			{
				foreach($matches[1] as $count => $match)
				{
					$match = preg_replace("/".LD.'path.*?'.RD."/", $replacement, $match);
					$match = preg_replace("/".LD.'auto_path'.RD."/", $replacement, $match);

					$this->_pagination_template = str_replace($matches[0][$count], $match, $this->_pagination_template);
				}
			}
		}
	}
}

// END Pagination class

/* End of file Pagination.php */
/* Location: ./system/expressionengine/libraries/Pagination.php */