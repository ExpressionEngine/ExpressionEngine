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
    * entry_id="147"
    * entry_id_from="20"
    * entry_id_to="40"
    * gallery="vacations"
    * limit="10"
    * log_views="off"
    * orderby="date" - caption, date, edit_date, entry_id, most_comments, most_recent_comment, most_views, random, screen_name, title, username
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
    * {entry_id}
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
		$return 		= '';
		$current_page	= '';
		$qstring		= $this->EE->uri->query_string;
		$uristr			= $this->EE->uri->uri_string;
		$switch 		= array();
		$search_link	= '';

		// Pagination variables

		$paginate			= FALSE;
		$paginate_data		= '';
		$pagination_links	= '';
		$page_next			= '';
		$page_previous		= '';
		$current_page		= 0;
		$t_current_page		= '';
		$total_pages		= 1;


		$this->fetch_pagination_data();
		//$this->parse_gallery_tag();

		$this->build_sql_query();
		
		if ($this->sql == '')
		{
			return $TMPL->no_results();
		}






// vs separate table for each file


	}
	
	function build_sql_query()
	{
		

		if (preg_match("#(^|/)P(\d+)(/|$)#i", $qstring, $match))
		{				
			$current_page = $match['2'];
			$uristr  = trim($this->EE->functions->remove_double_slashes(str_replace($match['0'], '/', $uristr)), '/');
		}
		
		$this_page = ($current_page == '' OR ($limit > 1 AND $current_page == 1)) ? 0 : $current_page;

		$this->EE->db->select('channel_id');
		$this->EE->db->where_in('site_id', $this->EE->TMPL->site_ids);
		
		$this->EE->db->select('*');
		$this->db->from('files');
		$this->db->join('upload_prefs', 'upload_prefs.id = files.upload_location', LEFT);
		$this->EE->db->limit($this->limit, $this_page);

		$query = $this->EE->db->get();
		
		$result_ids = array();


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
		if (strpos($this->EE->TMPL->tagdata, LD.'paginate'.RD) === FALSE) return;

		if (preg_match("/".LD."paginate".RD."(.+?)".LD.'\/'."paginate".RD."/s", $this->EE->TMPL->tagdata, $match))
		{
			$this->paginate	= TRUE;
			$this->paginate_data = $match[1];

			$this->EE->TMPL->tagdata = preg_replace("/".LD."paginate".RD.".+?".LD.'\/'."paginate".RD."/s", "", $this->EE->TMPL->tagdata);
		}
	}



}
// END CLASS

/* End of file mod.file.php */
/* Location: ./system/expressionengine/modules/file/mod.file.php */