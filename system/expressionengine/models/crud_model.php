<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Crud Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Crud_model extends CI_Model {

	/**
	 * Construct
	 */
	function Crud_model()
	{
		parent::CI_Model();
	}

	// ------------------------------------------------------------------------

	/**
	 * Save
	 *
	 * This method will either insert a new row, or update an existing row
	 * depending on if the where param has been specified.
	 *
	 * @param string	Table name
	 * @param array		associative array, eg: array('column' => 'value')
	 * @param array		associative array, eg: array('id' => 1)
	 * @return object
	 */	
	function save($table, $data, $where = array())
	{
		if ( ! empty($where))
		{
			foreach ($where as $key => $val)
			{
				$this->db->where($key, $val);
			}

			return $this->db->update($table, $data);
		}

		return $this->db->insert($table, $data);
	}

	// ------------------------------------------------------------------------	

	/**
	 * Fetch data
	 *
	 * @param string	table being selected from
	 * @param mixed		Array or string of columns to be selected
	 * @param mixed		Associative array of where statement 
	 * @param array		Order, and what to order by, eg:  array('column_name', 'asc')
	 * @param int		Limit
	 * @param int		Query Offset
	 * @return obj		Query object.
	 */
	function fetch($table, $select = NULL, $where = NULL, $order = array(), $limit = NULL, $offset = NULL)
	{
		if ($select)
		{
			if (is_array($select))
			{
				$select = implode(', ', $select);
			}

			$this->db->select($select);
		}

		if ($where)
		{
			foreach ($where as $key => $val)
			{//var_dump($val);
				if (is_array($val))
				{
					$this->db->where_in($key, $val);
				}
				else
				{
					$this->db->where($key, $val);
				}
			}
		}
//exit;
		if ( ! empty($order) AND count($order) == 2)
		{
			$this->db->order_by($order[0], $order[1]);
		}

		if ($limit)
		{
			$this->db->limit($limit, $offset);
		}

		return $this->db->get($table);
	}

	// ------------------------------------------------------------------------	

	/**
	 * Delete
	 *
	 * @param string	Table to delete from
	 * @param array		associative array of what to delete, eg:  array('id' => 1)
	 * @return mixed	FALSE if no where is specified
	 */	
	function delete($table, $where)
	{	
		// It's dangerous to allow a blanket delete with no where
		// So this protects us from ourselves.
		if (empty($where))
		{
			return FALSE;
		}

		$key = array_keys($where);
	
		$values = array_values($where);
		
		foreach ($key as $del_key => $col_name)
		{
			if (is_array($values[$del_key]))
			{
				$this->db->where_in($col_name, $values[$del_key]);
			}
			else
			{
				$this->db->where($col_name, $values[$del_key]);
			}
		}

		return $this->db->delete($table);
	}
	
	// ------------------------------------------------------------------------	

}
// End class Crud_model

/* End of file crud_model.php */
/* Location: ./system/expressionengine/models/crud_model.php */
