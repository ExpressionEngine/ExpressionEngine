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

		$fields = array(
			'url' => array(
				'field_label' => lang('url'),
				'field_description' => lang('url_desc')
					),
			'location' => array(
				'field_label' => lang('location'),
				'field_description' => lang('location_desc')
					),
			'occupation' => array(
				'field_label' => lang('occupation'),
				'field_description' => ''
					),
			'interests' => array(
				'field_label' => lang('interests'),
				'field_description' => ''
					),
			'bday_d' => array(
				'field_label' => lang('birthday'),
				'field_description' => lang('year')
					),
			'bday_m' => array(
				'field_label' => lang('birthday'),
				'field_description' => lang('month')
					),
			'bday_y' => array(
				'field_label' => lang('birthday'),
				'field_description' => lang('day')
					),
			'aol_im' => array(
				'field_label' => lang('mbr_aol_im'),
				'field_description' => ''
					),
			'yahoo_im' => array(
				'field_label' => lang('yahoo_im'),
				'field_description' => ''
					),
			'msn_im' => array(
				'field_label' => lang('msn_im'),
				'field_description' => ''
					),
			'icq' => array(
				'field_label' => lang('icq'),
				'field_description' => ''
					),
			'bio' => array(
				'field_label' => lang('biography'),
				'field_description' => lang('biography_desc')
					),

		// For any fields that have content, create a custom field

		// SELECT MAX(url), MAX(location) etc.
		// OR
		// Loop the array SELECT MAX or SELECT COUNT WHERE url = ''
		// OR
/*
(
  SELECT GROUP_CONCAT(member_id)
     FROM exp_members
    WHERE `url` IS NULL
       OR `url` = ''
) `url`,
(
  SELECT GROUP_CONCAT(member_id)
    FROM exp_members
   WHERE `location` IS NULL
) `location2`,


*/


		// Let's just pick one for now

		foreach ($fields as $field => $data)
		{
			ee()->db->select_max($field);
		}

		$query = ee()->db->get('members');

		// Double check what this does with null
		$move = array_filter($query->result_array());

		if ( ! empty($move))
		{
			foreach ($fields as $name => $data)

			$field = ee('Model')->make('MemberField');
			$field->field_type = 'text';
			$field->field_label = $data['field_label'];
			$field->field_name = $name;
			$field->description = $data['field_description'];

			$field->save();

		}
	}
}

// EOF
