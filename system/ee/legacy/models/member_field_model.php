<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Member Field Model
 */
class Member_field_model extends CI_Model {

	private $table_data		= 'member_data';
	private $table_fields	= 'member_fields';

	/**
	 * Save (Create/Edit) a Member Field
	 * @param  array  $data Associative array of data matching columns in
	 *                      exp_member_fields
	 * @return array        Modified $data associative array, containing
	 *                      defaults and sanitized values
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

		// Clean up field list items
		if (isset($data['m_field_list_items']) && $data['m_field_list_items'] != '')
		{
			$data['m_field_list_items'] = quotes_to_entities($data['m_field_list_items']);
		}

		// Determine field order
		if (empty($data['m_field_order'])
			OR ! is_numeric($data['m_field_order']))
		{
			ee()->load->model('member_model');
			$count = ee()->member_model->count_records('member_fields');
			$data['m_field_order'] = $count + 1;
		}

		// Set a default max length
		$data['m_field_maxl'] = (is_numeric($data['m_field_maxl'])) ? $data['m_field_maxl'] : 100;

		// Ensure defaults are there
		$defaults = array(
			'm_field_description'	=> '',
			'm_field_list_items'	=> '',
		);

		$data = array_merge($defaults, $data);

		// New Field
		ee()->load->dbforge();
		if ( ! isset($data['m_field_id']))
		{
			ee()->db->insert(
				$this->table_fields,
				$data
			);

			ee()->dbforge->add_column(
				$this->table_data,
				$this->_field_settings(ee()->db->insert_id(), $data['m_field_type'], $data['m_field_maxl'])
			);
		}
		// Edit existing field
		else
		{
			// Alter column if field type changed
			$previous_data = $this->get_field_information($data['m_field_id']);
			if ($previous_data['m_field_type'] !== $data['m_field_type'])
			{
				ee()->dbforge->modify_column(
					$this->table_data,
					$this->_field_settings($data['m_field_id'], $data['m_field_type'], $data['m_field_maxl'], FALSE)
				);
			}

			ee()->db->update(
				$this->table_fields,
				$data,
				array('m_field_id' => $data['m_field_id'])
			);
		}

		return $data;
	}

	/**
	 * Creates a field settings array to pass to db forge add/modify_column
	 * @param  Integer	$id		ID of the field
	 * @param  String	$type	Field type ('textarea', 'text')
	 * @param  Integer	$maxl	Length/constraint of the field
	 * @param  Bool 	$new	Indicates new vs. modification
	 * @return Array			Array to pass back to dbforge
	 */
	private function _field_settings($id, $type, $maxl, $new = TRUE)
	{
		$column_data = array();

		// Create the row in member_data
		$name = 'm_field_id_'.$id;

		if ( ! $new)
		{
			$column_data[$name]['name'] = $name;
		}

		$column_data[$name]['type'] = ($type == 'textarea') ? 'text' : 'varchar';

		if ($type != 'textarea')
		{
			$column_data[$name]['constraint'] = $maxl;
		}

		return $column_data;
	}

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

	/**
	 * Get field information for one or all fields
	 * @param  Integer $m_field_id	Field ID to retrieve
	 * @return Array				Array from database object
	 */
	public function get_field_information($m_field_id = 0)
	{
		if ( ! empty($m_field_id) && is_numeric($m_field_id))
		{
			ee()->db->where('m_field_id', $m_field_id);
		}

		$query = ee()->db->get($this->table_fields);

		if ( ! empty($m_field_id) && is_numeric($m_field_id))
		{
			return $query->row_array();
		}

		return $query->result_array();
	}

}

// EOF
