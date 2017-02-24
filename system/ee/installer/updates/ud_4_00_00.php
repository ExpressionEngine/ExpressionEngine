<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 4.0.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$steps = new ProgressIterator(
			array(
				'removeMemberHomepageTable',
				'moveMemberFields'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function removeMemberHomepageTable()
	{
		ee()->smartforge->drop_table('member_homepage');
	}

	private function moveMemberFields()
	{
		ee()->lang->load('member');
		ee()->load->model('member_model');

		// Do we need a preflight

		$fields = array(
			'url' => array(
				'field_label' => lang('url'),
				'field_description' => lang('url_desc'),
				'field_type' => 'url'
					),
			'location' => array(
				'field_label' => lang('location'),
				'field_description' => lang('location_desc'),
				'field_type' => 'text'
					),
			'occupation' => array(
				'field_label' => lang('occupation'),
				'field_description' => '',
				'field_type' => 'text'
					),
			'interests' => array(
				'field_label' => lang('interests'),
				'field_description' => '',
				'field_type' => 'text'
					),
			'aol_im' => array(
				'field_label' => lang('mbr_aol_im'),
				'field_description' => '',
				'field_type' => 'text'
					),
			'yahoo_im' => array(
				'field_label' => lang('yahoo_im'),
				'field_description' => '',
				'field_type' => 'text'
					),
			'msn_im' => array(
				'field_label' => lang('msn_im'),
				'field_description' => '',
				'field_type' => 'text'
					),
			'icq' => array(
				'field_label' => lang('icq'),
				'field_description' => '',
				'field_type' => 'text'
					),
			'bio' => array(
				'field_label' => lang('biography'),
				'field_description' => lang('biography_desc'),
				'field_type' => 'textarea'
				),
			'bday_d' => array(),
			'bday_m' => array(),
			'bday_y' => array()
			);

		foreach ($fields as $field => $data)
		{
			ee()->db->select_max($field);
		}

		$query = ee()->db->get('members');
		$move = $query->row_array();

		// Removes all false and null, including 0
		$move = array_filter($move);

		if (empty($move))
		{
			return;
		}

		// If they have any birthday fields, we'll create a birthday variable
		$birthday = FALSE;
		foreach (array('bday_d', 'bday_m', 'bday_y') as $bday)
		{
			if (array_key_exists($bday, $move))
			{
				$fields['birthday'] = array(
					'field_label' => lang('birthday'),
					'field_description' => '',
					'field_type' => 'date'
				);

				$move['birthday'] = TRUE;

				$birthday = TRUE;
			}

			unset($move[$bday]);
		}


		// Safety check- does field already exist in exp_member_fields
		$existing = ee('Model')->get('MemberField')->fields('m_field_name')->all()->pluck('m_field_name');


		ee()->load->library('api');

		// Create custom fields
		foreach ($move as $name => $val)
		{
			if (in_array($name, $existing) OR in_array($name, array('bday_d', 'bday_m', 'bday_y')))
			{
				continue;
			}

			$new_fields[] = $name;

			$field = ee('Model')->make('MemberField');

			$field->m_field_type = $fields[$name]['field_type'];


			$field->m_field_label = $fields[$name]['field_label'];
			$field->m_field_name = $name;
			$field->m_field_description = $fields[$name]['field_description'];

			$field->save();
		}


		// Copy custom field data
		// Seth can do this via select into query- need to merge.

		// Drop columns from exp_members
		foreach ($fields as $field => $data)
		{
			ee()->smartforge->drop_column('members', $field);
		}

	}
}

// EOF
