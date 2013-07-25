<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Query Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

/*
EXAMPLE:

{exp:query sql="select * from exp_members where username = 'joe' "}

 <h1>{username}</h1>
 
 <p>{email}</p>
 
 <p>{url}</p>

{/exp:query}

*/

class Query {

	var $return_data = ''; 
	
	// Pagination variables
	
	var $paginate				= FALSE;
	var $pagination_links		= '';
	var $page_next				= '';
	var $page_previous			= '';
	var $current_page			= 1;
	var $total_pages			= 1;
	var $total_rows				=  0;
	var $p_limit				= '';
	var $p_page					= '';
	var $basepath				= '';
	var $uristr					= '';

	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function Query()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		$this->basic_select();
	}


	/** -------------------------------------
	/**  Basic SQL 'select' query
	/** -------------------------------------*/
	function basic_select()
	{
		// Extract the query from the tag chunk
		
		if (($sql = ee()->TMPL->fetch_param('sql')) === FALSE)
		{
			return FALSE;			
		}
		
		if (substr(strtolower(trim($sql)), 0, 6) != 'select')
		{
			return FALSE;			
		}
		
		/** --------------------------------------
		/**  Pagination checkeroo!
		/** --------------------------------------*/

		if (preg_match("/".LD."paginate".RD."(.+?)".LD.'\/'."paginate".RD."/s", ee()->TMPL->tagdata, $match))
		{ 
			// Run the query
						
			$query = ee()->db->query("SELECT COUNT(*) AS count FROM ({$sql}) AS query");

			if ($query->row('count')  == 0)
			{
				return $this->return_data = ee()->TMPL->no_results();
			}
		
			$this->paginate		 = TRUE;
			$this->paginate_data = $match['1'];
			$this->basepath		 = ee()->functions->create_url(ee()->uri->uri_string, 1);
						
			ee()->TMPL->tagdata = preg_replace("/".LD."paginate".RD.".+?".LD.'\/'."paginate".RD."/s", "", ee()->TMPL->tagdata);
			
			if (ee()->uri->query_string != '' && preg_match("#^P(\d+)|/P(\d+)#", ee()->uri->query_string, $match))
			{					
				$this->p_page = (isset($match['2'])) ? $match['2'] : $match['1'];	
					
				$this->basepath = reduce_double_slashes(str_replace($match['0'], '', $this->basepath));
			}
			
			$this->total_rows = $query->row('count') ;
			$this->p_limit  = ( ! ee()->TMPL->fetch_param('limit'))  ? 50 : ee()->TMPL->fetch_param('limit');
			$this->p_page = ($this->p_page == '' OR ($this->p_limit > 1 AND $this->p_page == 1)) ? 0 : $this->p_page;
				
			if ($this->p_page > $this->total_rows)
			{
				$this->p_page = 0;
			}
								
			$this->current_page = floor(($this->p_page / $this->p_limit) + 1);
				
			$this->total_pages = intval(floor($this->total_rows / $this->p_limit));
			
			/** ----------------------------------------
			/**  Create the pagination
			/** ----------------------------------------*/
			
			if ($this->total_rows % $this->p_limit) 
			{
				$this->total_pages++;
			}
			
			if ($this->total_rows > $this->p_limit)
			{
				ee()->load->library('pagination');
				
				if (strpos($this->basepath, SELF) === FALSE && ee()->config->item('site_index') != '')
				{
					$this->basepath .= SELF;
				}
				
				$this->basepath = rtrim($this->basepath,'/').'/';

				$config['base_url']		= $this->basepath;
				$config['prefix']		= 'P';
				$config['total_rows'] 	= $this->total_rows;
				$config['per_page']		= $this->p_limit;
				$config['cur_page']		= $this->p_page;
				$config['first_link'] 	= ee()->lang->line('pag_first_link');
				$config['last_link'] 	= ee()->lang->line('pag_last_link');
				
				// Allows $config['cur_page'] to override
				$config['uri_segment'] = 0;

				ee()->pagination->initialize($config);
				$this->pagination_links = ee()->pagination->create_links();
				
				if ((($this->total_pages * $this->p_limit) - $this->p_limit) > $this->p_page)
				{
					$this->page_next = $this->basepath.'P'.($this->p_page + $this->p_limit);
				}
				
				if (($this->p_page - $this->p_limit ) >= 0) 
				{						
					$this->page_previous = $this->basepath.'P'.($this->p_page - $this->p_limit);
				}
				
				$sql .= " LIMIT ".$this->p_page.', '.$this->p_limit;
			}
			else
			{
				$this->p_page = '';
			}
		}
		
		$query = ee()->db->query($sql);
		
		if ($query->num_rows() == 0)
		{
			return $this->return_data = ee()->TMPL->no_results();
		}

		$variables = array();
		
		foreach ($query->result_array() as $count => $row)
		{
			$variables[] = $row;
		}
		
		$this->return_data = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $variables);

		
		if ($this->paginate == TRUE)
		{
			$this->paginate_data = str_replace(LD.'current_page'.RD, $this->current_page, $this->paginate_data);
			$this->paginate_data = str_replace(LD.'total_pages'.RD,	$this->total_pages, $this->paginate_data);
			$this->paginate_data = str_replace(LD.'pagination_links'.RD, $this->pagination_links, $this->paginate_data);
			
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

	
	
}
// END CLASS

/* End of file mod.query.php */
/* Location: ./system/expressionengine/modules/query/mod.query.php */