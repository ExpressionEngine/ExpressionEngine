<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
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
 *
 * EXAMPLE:
 *
 * {exp:query sql="select * from exp_members where username = 'joe'"}
 * 		<h1>{username}</h1>
 * 		<p>{email}</p>
 * 		<p>{url}</p>
 * {/exp:query}
 */

class Query {

	var $return_data = '';

	function __construct()
	{
		// Extract the query from the tag chunk
		if (($sql = ee()->TMPL->fetch_param('sql')) === FALSE)
		{
			return FALSE;
		}

		// Rudimentary check to see if it's a SELECT query, most definitely not
		// bulletproof
		if (substr(strtolower(trim($sql)), 0, 6) != 'select')
		{
			return FALSE;
		}

		$query = ee()->db->query($sql);
		$results = $query->result_array();
		if ($query->num_rows() == 0)
		{
			return $this->return_data = ee()->TMPL->no_results();
		}

		// Start up pagination
		ee()->load->library('pagination');
		$pagination = ee()->pagination->create();
		ee()->TMPL->tagdata = $pagination->prepare(ee()->TMPL->tagdata);
		$per_page = ee()->TMPL->fetch_param('limit', 0);

		// Disable pagination if the limit parameter isn't set
		if (empty($per_page))
		{
			$pagination->paginate = FALSE;
		}

		if ($pagination->paginate)
		{
			$pagination->build($query->num_rows(), $per_page);
			$results = array_slice($results, $pagination->offset, $pagination->per_page);
		}

		$this->return_data = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, array_values($results));

		if ($pagination->paginate === TRUE)
		{
			$this->return_data = $pagination->render($this->return_data);
		}
	}
}
// END CLASS

/* End of file mod.query.php */
/* Location: ./system/expressionengine/modules/query/mod.query.php */