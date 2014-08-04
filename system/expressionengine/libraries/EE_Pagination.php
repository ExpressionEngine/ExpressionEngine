<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
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
	public function create()
	{
		return new Pagination_object();
	}
}

/**
 * Pagination object created for each instance of pagination.
 */
class Pagination_object {
	public $paginate				= FALSE;
	public $total_items				= 0;
	public $total_pages				= 1;
	public $per_page				= 0;
	public $offset					= 0;
	public $current_page			= 1;
	public $basepath				= '';
	public $prefix					= "P";

	// Field Pagination specific properties
	public $cfields					= array();
	public $field_pagination		= FALSE;
	public $field_pagination_query	= NULL;

	private $_template_data				= '';
	private $_page_array				= array();
	private $_multi_fields				= '';
	private $_page_next					= '';
	private $_page_previous				= '';
	private $_page_links				= '';
	private $_page_links_limit			= 2;
	private $_type						= '';
	private $_position					= '';
	private $_pagination_marker			= "pagination_marker";
	private $_always_show_first_last	= FALSE;

	public function __construct()
	{
		$stack = debug_backtrace(FALSE);
		$this->_type = $stack[2]['class'];

		ee()->load->library('pagination');
		ee()->load->library('template', NULL, 'TMPL');
	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve non-public properties
	 * @param  string $name  Name of the property
	 * @return mixed         Value of the property
	 */
	public function __get($name)
	{
		if (in_array($name, array('type', 'template_data')))
		{
			return $this->{'_'.$name};
		}
	}

	// -------------------------------------------------------------------------

	/**
	 * Sets non-public properties
	 * @param string $name  Name of the property to set
	 * @param string $value Value of the property
	 */
	public function __set($name, $value)
	{
		// Allow for position overrides.
		// position lets the developer override the position of the pagination
		// (e.g. top, bottom, both, hidden)
		if (in_array($name, array('position', 'template_data')))
		{
			$this->{'_'.$name} = $value;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Prepare the pagination template
	 * Determines if {paginate} is in the tagdata, if so flags that. Also
	 * checks to see if paginate_type is field, if it is, then we look for
	 * {multi_field="..."} and flag that.
	 *
	 * The whole goal of this method is to see if we need to paginate and if
	 * we do, extract the tags within pagination and put them in another variable
	 *
	 * @param String $template The template to prepare, typically
	 *                         ee()->TMPL->tagdata
	 * @return String The template with the pagination removed
	 */
	function prepare($template)
	{
		// Prepare the template
		if (ee()->TMPL->fetch_param('paginate') == 'hidden')
		{
			$this->paginate = TRUE;
		}
		else if (strpos($template, LD.'paginate'.RD) !== FALSE
			&& preg_match("/".LD."paginate".RD."(.+?)".LD.'\/'."paginate".RD."/s", $template, $paginate_match))
		{
			if (ee()->TMPL->fetch_param('paginate_type') == 'field')
			{
				// If we're supposed to paginate over fields, check to see if
				// {multi_field="..."} exists. If it does capture the conetents
				// and flag this as field_pagination.
				if (preg_match("/".LD."multi_field\=[\"'](.+?)[\"']".RD."/s", $template, $multi_field_match))
				{
					$this->_multi_fields = ee()->functions->fetch_simple_conditions($multi_field_match[1]);
					$this->field_pagination	= TRUE;
				}
			}

			// Grab the parameters from {pagination_links}
			if (preg_match("/".LD."pagination_links(.*)".RD."/", $template, $pagination_links_match))
			{
				$parameters = ee()->functions->assign_parameters($pagination_links_match[1]);

				// Check for page_padding
				if (isset($parameters['page_padding']))
				{
					$this->_page_links_limit = $parameters['page_padding'];
				}

				// Check for always_show_first_last
				if (isset($parameters['always_show_first_last'])
					&& substr($parameters['always_show_first_last'], 0, 1) === 'y')
				{
					$this->_always_show_first_last = TRUE;
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
					ee()->load->library('logger');
					ee()->logger->deprecated_hook('channel_module_fetch_pagination_data', '2.8', 'pagination_fetch_data');

					ee()->extensions->universal_call('channel_module_fetch_pagination_data', $this);
					if (ee()->extensions->end_script === TRUE) return;
				}
			//
			// -------------------------------------------

			// -------------------------------------------
			// 'pagination_fetch_data' hook.
			//  - Works with the 'create_pagination' hook
			//  - Developers, if you want to modify the $this object remember
			//	to use a reference on function call.
			//
				if (ee()->extensions->active_hook('pagination_fetch_data') === TRUE)
				{
					ee()->extensions->universal_call('pagination_fetch_data', $this);
					if (ee()->extensions->end_script === TRUE) return;
				}
			//
			// -------------------------------------------

			// If {paginate} exists store the pagination template
			$this->paginate = TRUE;
			$this->_template_data = $paginate_match[1];

			// Determine if pagination needs to go at the top and/or bottom, or inline
			$this->_position = ee()->TMPL->fetch_param('paginate', $this->_position);
		}

		// Create temporary marker for inline position
		$replace_tag = ($this->_position == 'inline') ? LD.$this->_pagination_marker.RD : '';

		// Remove pagination tags from template since we'll just
		// append/prepend it later
		$template = preg_replace(
			"/".LD."paginate".RD.".+?".LD.'\/'."paginate".RD."/s",
			$replace_tag,
			$template
		);

		return $template;
	}

	// ------------------------------------------------------------------------

	/**
	 * Build the pagination out, storing it in the Pagination_object
	 *
	 * @param integer	$total_items	Number of rows we're paginating over
	 * @param integer	$per_page	Number of items per page
	 * @return Boolean TRUE if successful, FALSE otherwise
	 */
	function build($total_items, $per_page)
	{
		$this->total_items = $total_items;
		$this->per_page = $per_page;

		// -------------------------------------------
		// 'channel_module_create_pagination' hook.
		//  - Rewrite the pagination function in the Channel module
		//  - Could be used to expand the kind of pagination available
		//  - Paginate via field length, for example
		//
			if (ee()->extensions->active_hook('channel_module_create_pagination') === TRUE)
			{
				ee()->load->library('logger');
				ee()->logger->deprecated_hook('channel_module_create_pagination', '2.8', 'pagination_create');

				ee()->extensions->universal_call('channel_module_create_pagination', $this, $this->total_items);
				if (ee()->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------

		// -------------------------------------------
		// 'pagination_create' hook.
		//  - Rewrite the pagination function in the Channel module
		//  - Could be used to expand the kind of pagination available
		//  - Paginate via field length, for example
		//
			if (ee()->extensions->active_hook('pagination_create') === TRUE)
			{
				ee()->extensions->universal_call('pagination_create', $this, $this->total_items);
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
				if (preg_match("#^{$this->prefix}(\d+)|/{$this->prefix}(\d+)#", $query_string, $match))
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
				if ($this->_type === "Channel"
					&& ee()->TMPL->fetch_param('display_by') == ''
					&& $this->total_items == 0)
				{
					return FALSE;
				}

				$this->offset = ($this->offset == '' OR ($this->per_page > 1 AND $this->offset == 1)) ? 0 : $this->offset;

				// If we're far beyond where we should be, reset us back to
				// the first page
				if ($this->offset > $this->total_items)
				{
					return ee()->TMPL->no_results();
				}

				$this->current_page	= floor(($this->offset / $this->per_page) + 1);
				$this->total_pages	= intval(floor($this->total_items / $this->per_page));
			}
			// Field pagination - base values
			else
			{
				// If we're doing field pagination and there's not even one
				// entry, then clear out the sql and get out of here
				if ($this->total_items == 0
					OR ! is_object($this->field_pagination_query))
				{
					return FALSE;
				}

				$m_fields = array();
				$row = $this->field_pagination_query->row_array();

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
				$this->total_items = count($m_fields);
				$this->total_pages = $this->total_items;
				if ($this->total_pages == 0)
				{
					$this->total_pages = 1;
				}

				$this->offset = ($this->offset == '') ? 0 : $this->offset;
				if ($this->offset > $this->total_items)
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
			if ($this->total_items > 0 && $this->per_page > 0)
			{
				if ($this->total_items % $this->per_page)
				{
					$this->total_pages++;
				}
			}

			// Last check to make sure we actually need to paginate
			if ($this->total_items > $this->per_page)
			{
				if (strpos($this->basepath, SELF) === FALSE && ee()->config->item('site_index') != '' && strpos($this->basepath, ee()->config->item('site_index')) === FALSE)
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

				$config = array(
					'first_url'		=> rtrim($this->basepath, '/'),
					'base_url'		=> $this->basepath,
					'prefix'		=> $this->prefix,
					'total_rows'	=> $this->total_items,
					'per_page'		=> $this->per_page,
					// cur_page uses the offset because P45 (or similar) is a page
					'cur_page'		=> $this->offset,
					'num_links'		=> $this->_page_links_limit,
					'first_link'	=> lang('pag_first_link'),
					'last_link'		=> lang('pag_last_link'),
					'uri_segment'	=> 0 // Allows $config['cur_page'] to override
				);

				ee()->pagination->initialize($config);
				$this->_page_links = ee()->pagination->create_links();
				ee()->pagination->initialize($config); // Re-initialize to reset config
				$this->_page_array = ee()->pagination->create_link_array();

				// If a page_next should exist, create it
				if ((($this->total_pages * $this->per_page) - $this->per_page) > $this->offset)
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

		return TRUE;
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
		if ($this->_page_links == '' OR $this->paginate === FALSE)
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

		$parse_array = array();

		// Check to see if page_links is being used as a single
		// variable or as a variable pair
		if (strpos($this->_template_data, LD.'/pagination_links'.RD) !== FALSE)
		{
			$parse_array['pagination_links'] = array($this->_page_array);
		}
		else
		{
			$parse_array['pagination_links'] = $this->_page_links;
		}

		// Check to see if we should be showing first/last page or not
		if ($this->_always_show_first_last == FALSE && is_array($parse_array['pagination_links']))
		{
			// Don't show the first
			if ($this->current_page <= ($this->_page_links_limit + 1))
			{
				$parse_array['pagination_links'][0]['first_page'] = array();
			}

			// Don't show the last
			if (($this->current_page + $this->_page_links_limit) >= $this->total_pages)
			{
				$parse_array['pagination_links'][0]['last_page'] = array();
			}
		}

		// Parse current_page and total_pages by default
		$parse_array['current_page']	= $this->current_page;
		$parse_array['total_pages']		= $this->total_pages;

		// Parse current_page and total_pages
		$this->_template_data = ee()->TMPL->parse_variables(
			$this->_template_data,
			array($parse_array),
			FALSE // Disable backspace parameter so pagination markup is protected
		);

		// Parse {if previous_page} and {if next_page}
		$this->_parse_conditional('previous', $this->_page_previous);
		$this->_parse_conditional('next', $this->_page_next);

		// Parse if total_pages conditionals
		$this->_template_data = ee()->functions->prep_conditionals(
			$this->_template_data,
			array('total_pages' => $this->total_pages)
		);

		switch ($this->_position)
		{
			case "top":
				return $this->_template_data.$return_data;
				break;
			case "both":
				return $this->_template_data.$return_data.$this->_template_data;
				break;
			case "inline":
				return ee()->TMPL->swap_var_single(
					$this->_pagination_marker,
					$this->_template_data,
					$return_data
				);
				break;
			return $return_data;
			break;
			case "bottom":
			default:
				return $return_data.$this->_template_data;
				break;
		}
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
		if (preg_match_all("/".LD."if {$type}_page".RD."(.+?)".LD.'\/'."if".RD."/s", $this->_template_data, $matches))
		{
			if ($replacement == '')
			{
				 $this->_template_data = preg_replace("/".LD."if {$type}_page".RD.".+?".LD.'\/'."if".RD."/s", '', $this->_template_data);
			}
			else
			{
				foreach($matches[1] as $count => $match)
				{
					$match = preg_replace("/".LD.'path.*?'.RD."/", $replacement, $match);
					$match = preg_replace("/".LD.'auto_path'.RD."/", $replacement, $match);

					$this->_template_data = str_replace($matches[0][$count], $match, $this->_template_data);
				}
			}
		}
	}
}

// END Pagination class

/* End of file Pagination.php */
/* Location: ./system/expressionengine/libraries/Pagination.php */