<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.7
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Member Field Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Member_field_model extends CI_Model {

	private $table_data		= 'member_data';
	private $table_fields	= 'member_fields';

	/**
	 * Save (Create/Edit) a Member Field
	 * @param  array  $data Associative array of data matching columns in
	 *                      exp_member_fields
	 * @return Void
	 */
	public function save_field($data = array())
	{
		if ( ! is_array($data))
		{
			throw new Exception('Data passed to Member_field_model::save() must be an array');
		}

		// Sanitize fields allowed
		$fields	= ee()->db->list_fields('member_fields');
		$data	= array_intersect_key($data, array_flip($fields));

		// New Field
		if ( ! isset($data['m_field_id']))
		{
			ee()->db->insert(
				$this->table_fields,
				$data
			);

			// Create the row in member_data
			$name = 'm_field_id_'.ee()->db->insert_id();
			$column_data[$name]['type'] = ('m_field_type' == 'textarea') ? 'text' : 'varchar';

			if ($data['m_field_type'] != 'textarea')
			{
				$column_data[$name]['constraint'] = $data['m_field_maxl'];
			}

			ee()->load->dbforge();
			ee()->dbforge->add_column(
				$this->table_data,
				$column_data
			);
		}
		// Edit existing field
		else
		{
			ee()->db->update(
				$this->table_fields,
				$data,
				array('m_field_id' => $data['m_field_id'])
			);
		}
	}

	// -------------------------------------------------------------------------

	/**
	 * Delete a Member Field
	 * @param  Integer $m_field_id Member Field ID
	 * @return Void
	 */
	public function delete_field($m_field_id)
	{
		ee()->db->select('m_field_id')
			->from($this->table_fields);
		if (ee()->db->count_all_results() == 0)
		{
			throw new Exception("Member field with ID of {$field_id} does not exist.");
		}

		ee()->db->delete($this->table_fields, compact('m_field_id'));
		ee()->load->dbforge();
		ee()->dbforge->drop_column($this->table_data, 'm_field_id_'.$m_field_id);
	}

}