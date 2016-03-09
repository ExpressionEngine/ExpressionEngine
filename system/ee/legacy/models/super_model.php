<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Super Model
 *
 * This model contains some abstracted can-do database functions that
 * are used by a plethora of libraries and controllers to keep database calls
 * out of such code as much as possible
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Super_model extends CI_Model {

	// --------------------------------------------------------------------

	/**
	 * Count All
	 *
	 * Gateway to count all rows in a given table or for quick checks
	 * to see if something exists (or how many times it does)
	 *
	 * @access	public
	 * @param	string
	 * @return	int
	 */
	function count($table, $where = NULL)
	{
		if ( ! is_array($where))
		{
			return $this->db->count_all($table);
		}
		else
		{
			// add WHERE clauses
			foreach ($where as $field => $value)
			{
				if (is_array($value))
				{
					$this->db->where_in($field, $value);
				}
				else
				{
					$this->db->where($field, $value);
				}
			}

			return $this->db->count_all_results($table);
		}
	}

	// --------------------------------------------------------------------
}
// END CLASS

// EOF
