<?php

namespace EllisLab\ExpressionEngine\Controllers\Utilities;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Library\CP\Pagination;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Query Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Query extends Utilities {

	/**
	 * Query form
	 */
	public function index()
	{
		// Super Admins only, please
		if (ee()->session->userdata('group_id') != '1')
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				 'field'   => 'thequery',
				 'label'   => 'lang:sql_query_to_run',
				 'rules'   => 'required'
			),
			array(
				'field' => 'password_auth',
				'label' => 'lang:current_password',
				'rules' => 'required|auth_password'
			)
		));

		if (ee()->form_validation->run() !== FALSE)
		{
			return $this->runQuery();
		}

		ee()->view->cp_page_title = lang('sql_query_form');
		ee()->cp->render('utilities/query');
	}

	// --------------------------------------------------------------------

	/**
	 * Query handler
	 */
	public function runQuery()
	{
		// defaults in the house!
		$row_limit	= 5;
		$title		= lang('query_result');
		$vars['write'] = FALSE;
		ee()->db->db_debug = (ee()->input->post('debug') !== FALSE);

		$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
		$page = ($page > 0) ? $page : 1;

		$sort = ee()->input->get('sort');
		$sort_dir = ee()->input->get('sort_dir');

		// Fetch the query.  It can either come from a
		// POST request or a url encoded GET request
		if ( ! $sql = ee()->input->post('thequery'))
		{
			if ( ! $sql = ee()->input->get('thequery'))
			{
				return $this->index();
			}
			else
			{
				$sql = base64_decode(rawurldecode($sql));
			}
		}

		ee()->cp->set_breadcrumb(cp_url('utilities/query'), lang('query_form'));
		ee()->view->cp_page_title = lang('query_results');

		$sql = trim(str_replace(";", "", $sql));

		// Determine if the query is one of the non-allowed types
		$qtypes = array('FLUSH', 'REPLACE', 'GRANT', 'REVOKE', 'LOCK', 'UNLOCK');

		if (preg_match("/(^|\s)(".implode('|', $qtypes).")\s/si", $sql))
		{
			show_error(lang('sql_not_allowed'));
		}

		// If it's a DELETE query, require that a Super Admin be the one submitting it
		if (ee()->session->userdata('group_id') != '1')
		{
			if (strpos(strtoupper($sql), 'DELETE') !== FALSE OR strpos(strtoupper($sql), 'ALTER') !== FALSE OR strpos(strtoupper($sql), 'TRUNCATE') !== FALSE OR strpos(strtoupper($sql), 'DROP') !== FALSE)
			{
				show_error(lang('unauthorized_access'));
			}
		}

		// If it's a SELECT query we'll see if we need to limit
		// the result total and add pagination links
		if (strpos(strtoupper($sql), 'SELECT') !== FALSE)
		{
			if ($sort !== FALSE && $sort_dir !== FALSE)
			{
				// Wrap query in parenthesis in case query already has a
				// limit on it, we can't put an ORDER BY after a LIMIT
				$new_sql = '('.$sql.') ORDER BY '.$sort.' '.$sort_dir;
			}

			if ( ! preg_match("/LIMIT\s+[0-9]/i", $sql))
			{
				// Modify the query so we get the total sans LIMIT
				$row = ($page - 1) * $row_limit; // Offset is 0 indexed

				if ( ! isset($new_sql))
				{
					$new_sql = $sql;
				}

				$new_sql .= " LIMIT ".$row.", ".$row_limit;
			}

			if (isset($new_sql))
			{
				if ( ! $query = ee()->db->query($new_sql))
				{
					$vars['no_results'] = lang('sql_no_result');
					ee()->cp->render('utilities/query-results', $vars);
					return;
				}

				// Get total results
				$total_results = ee()->db->query($sql)->num_rows();
			}
		}

		if ( ! isset($new_sql))
		{
			if ( ! $query = ee()->db->query($sql))
			{
				$vars['no_results'] = lang('sql_no_result');
				ee()->cp->render('utilities/query-results', $vars);
				return;
			}
		}

		$qtypes = array('INSERT', 'UPDATE', 'DELETE', 'ALTER', 'CREATE', 'DROP', 'TRUNCATE');

		foreach ($qtypes as $type)
		{
			if (strncasecmp($sql, $type, strlen($type)) == 0)
			{
				$vars['affected'] = (ee()->db->affected_rows() > 0) ? lang('total_affected_rows').NBS.ee()->db->affected_rows() : lang('sql_good_query');
				$vars['thequery'] = ee()->security->xss_clean($sql);
				$vars['write'] = TRUE;

				ee()->cp->render('utilities/query-results', $vars);
				return;
			}
		}

		// no results?  Wasted efforts!
		if ($query->num_rows() == 0)
		{
			$vars['no_results'] = lang('sql_no_result');
			ee()->cp->render('utilities/query-results', $vars);
			return;
		}

		$vars['thequery'] = ee()->security->xss_clean($sql);
		$vars['total_results'] = (isset($total_results)) ? $total_results : 0;

		$base_url = new CP\URL(
			'utilities/query/run-query',
			ee()->session->session_id(),
			array('thequery' => rawurlencode(base64_encode($sql)))
		);

		$data = $query->result_array();
		$keys = array_keys($data[0]);

		$table = array(
			'base_url' 	=> $base_url,
			'sort'		=> ($sort !== FALSE) ? $sort : $keys[0],
			'sort_dir'	=> ($sort_dir !== FALSE) ? $sort_dir : 'asc',
			'wrap' 		=> TRUE, // Wrap table in scroll view
			'encode' 	=> TRUE, // Encode HTML
			'data' 		=> $data
		);

		$pagination = new Pagination($row_limit, $vars['total_results'], $page);
		$vars['pagination'] = $pagination->cp_links($base_url);

		// For things like SHOW queries, they show as having zero rows
		// and we can't paginate them
		if ($vars['total_results'] == 0 && count($query->result_array()) > 0)
		{
			$vars['total_results'] = count($query->result_array());
			unset($table['sort']); // These queries aren't sortable
		}

		$vars['table'] = ee()->load->view('_shared/table', $table, TRUE);
		
		ee()->cp->render('utilities/query-results', $vars);
	}
}
// END CLASS

/* End of file Query.php */
/* Location: ./system/expressionengine/controllers/cp/Utilities/Query.php */
