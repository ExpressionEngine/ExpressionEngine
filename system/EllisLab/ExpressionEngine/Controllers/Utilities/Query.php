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
		if (isset($_POST['password_auth']))
		{
			unset($_POST['password_auth']);
		}

		// defaults in the house!
		$row_limit	= 100;
		$title		= lang('query_result');
		$vars['write'] = FALSE;
		ee()->db->db_debug = (ee()->input->post('debug') !== FALSE);

		$page = ee()->input->get('page') ? ee()->input->get('page') : 1;
		$page = ($page > 0) ? $page : 1;

		$sort = ee()->input->get('sort');
		$sort_dir = ee()->input->get('sort_dir');
		$search = ee()->input->post('search') ?: ee()->input->get('search');
		$search = empty($search) ? '' : $search;

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
			ee()->view->set_message('issue', lang('sql_not_allowed'), lang('sql_not_allowed_desc'), TRUE);
			return ee()->functions->redirect(cp_url('utilities/query'));
		}

		// If it's a DELETE query, require that a Super Admin be the one submitting it
		if (ee()->session->userdata('group_id') != '1')
		{
			if (strpos(strtoupper($sql), 'DELETE') !== FALSE OR strpos(strtoupper($sql), 'ALTER') !== FALSE OR strpos(strtoupper($sql), 'TRUNCATE') !== FALSE OR strpos(strtoupper($sql), 'DROP') !== FALSE)
			{
				show_error(lang('unauthorized_access'));
			}
		}

		// Search the table after query has ran
		if ( ! empty($search) && $query = ee()->db->query($sql))
		{
			if ($query->num_rows() > 0)
			{
				$data = $query->result_array();
				$keys = array_keys($data[0]);

				$new_sql = 'SELECT * FROM ('.$sql.') AS search WHERE';
				foreach ($keys as $index => $key)
				{
					if ($index > 0)
					{
						$new_sql .= ' OR';
					}
					$new_sql .= ' '.$key.' LIKE \'%'.ee()->db->escape_like_str($search).'%\'';
				}
			}
		}

		// Get the total results on the orignal query before we paginate it
		$query = (isset($new_sql)) ? ee()->db->query($new_sql) : ee()->db->query($sql);
		$total_results = (is_object($query)) ? $query->num_rows() : 0;

		// If it's a SELECT query we'll see if we need to limit
		// the result total and add pagination links
		if (strpos(strtoupper($sql), 'SELECT') !== FALSE)
		{
			if ($sort !== FALSE && $sort_dir !== FALSE)
			{
				$new_sql = ( ! isset($new_sql)) ? '('.$sql.')' : '('.$new_sql.')';
				
				// Wrap query in parenthesis in case query already has a
				// limit on it, we can't put an ORDER BY after a LIMIT
				$new_sql .= ' ORDER BY '.$sort.' '.$sort_dir;
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
				$query = ee()->db->query($new_sql);
			}
		}
		
		if ( ! isset($new_sql))
		{
			$query = ee()->db->query($sql);
		}

		$qtypes = array('INSERT', 'UPDATE', 'DELETE', 'ALTER', 'CREATE', 'DROP', 'TRUNCATE');

		foreach ($qtypes as $type)
		{
			if (strncasecmp($sql, $type, strlen($type)) == 0)
			{
				$vars['affected'] = ee()->db->affected_rows();
				$vars['write'] = TRUE;

				break;
			}
		}

		$vars['thequery'] = ee()->security->xss_clean($sql);
		$vars['total_results'] = (isset($total_results)) ? $total_results : 0;

		$base_url = new CP\URL(
			'utilities/query/run-query',
			ee()->session->session_id(),
			array(
				'thequery' 	=> rawurlencode(base64_encode($sql)),
				'search' 	=> $search
			)
		);

		$data = (is_object($query)) ? $query->result_array() : array();

		$table = array(
			'base_url' 	=> $base_url,
			'sort_dir'	=> ($sort_dir !== FALSE) ? $sort_dir : 'asc',
			'wrap' 		=> TRUE, // Wrap table in scroll view
			'encode' 	=> TRUE, // Encode HTML
			'data' 		=> $data
		);

		if ( ! empty($data))
		{
			$keys = array_keys($data[0]);
			$table['sort'] = ($sort !== FALSE) ? $sort : $keys[0];
		}

		$pagination = new Pagination($row_limit, $vars['total_results'], $page);
		$vars['pagination'] = $pagination->cp_links($base_url);

		// For things like SHOW queries, they show as having zero rows
		// and we can't paginate them
		if ($vars['total_results'] == 0 && count($data) > 0)
		{
			$vars['total_results'] = count($data);
			unset($table['sort']); // These queries aren't sortable
		}

		$vars['base_url'] = $base_url->compile();
		$vars['search'] = $search;
		$vars['table'] = ee()->load->view('_shared/table', $table, TRUE);
		
		ee()->cp->render('utilities/query-results', $vars);
	}
}
// END CLASS

/* End of file Query.php */
/* Location: ./system/expressionengine/controllers/cp/Utilities/Query.php */
