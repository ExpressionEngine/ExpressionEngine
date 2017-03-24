<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
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
				'emancipate_the_fields',
				'add_field_data_flag',
				'removeMemberHomepageTable',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function emancipate_the_fields()
	{
		// Fields can span Sites and do not need Groups
		ee()->smartforge->modify_column('channel_fields', array(
			'site_id' => array(
				'type'     => 'int',
				'unsigned' => TRUE,
				'null'     => TRUE,
			),
			'group_id' => array(
				'type'     => 'int',
				'unsigned' => TRUE,
				'null'     => TRUE,
			),
		));

		// Field groups can span Sites
		ee()->smartforge->modify_column('field_groups', array(
			'site_id' => array(
				'type'     => 'int',
				'unsigned' => TRUE,
				'null'     => TRUE,
			),
		));

		// Add the Many-to-Many tables
		ee()->dbforge->add_field(
			array(
				'channel_id' => array(
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				),
				'group_id' => array(
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				)
			)
		);
		ee()->dbforge->add_key(array('channel_id', 'group_id'), TRUE);
		ee()->smartforge->create_table('channels_channel_field_groups');

		ee()->dbforge->add_field(
			array(
				'channel_id' => array(
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				),
				'field_id' => array(
					'type'       => 'int',
					'constraint' => 6,
					'unsigned'   => TRUE,
					'null'       => FALSE
				)
			)
		);
		ee()->dbforge->add_key(array('channel_id', 'field_id'), TRUE);
		ee()->smartforge->create_table('channels_channel_fields');

		ee()->dbforge->add_field(
			array(
				'field_id' => array(
					'type'       => 'int',
					'constraint' => 6,
					'unsigned'   => TRUE,
					'null'       => FALSE
				),
				'group_id' => array(
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				)
			)
		);
		ee()->dbforge->add_key(array('field_id', 'group_id'), TRUE);
		ee()->smartforge->create_table('channel_field_groups_fields');

		// Convert the one-to-one channel to field group assignment to the
		// many-to-many structure
		if (ee()->db->field_exists('field_group', 'channels'))
		{
			$channels = ee()->db->select('channel_id, field_group')
				->where('field_group IS NOT NULL')
				->get('channels')
				->result();

			foreach ($channels as $channel)
			{
				ee()->db->insert('channels_channel_field_groups', array(
					'channel_id' => $channel->channel_id,
					'group_id' => $channel->field_group
				));
			}

			ee()->smartforge->drop_column('channels', 'field_group');
		}

		// Convert the one-to-one field to field group assignment to the
		// many-to-many structure
		if (ee()->db->field_exists('group_id', 'channel_fields'))
		{
			$fields = ee()->db->select('field_id, group_id')
				->get('channel_fields')
				->result();

			foreach ($fields as $field)
			{
				ee()->db->insert('channel_field_groups_fields', array(
					'field_id' => $field->field_id,
					'group_id' => $field->group_id
				));
			}

			ee()->smartforge->drop_column('channel_fields', 'group_id');
		}
	}

	/**
	 * Adds a column to exp_channel_fields, exp_member_fields, and
	 * exp_category_fields tables that indicates if the
	 * data is in the legacy data tables or their own table.
	 */
	private function add_field_data_flag()
	{
		if ( ! ee()->db->field_exists('legacy_field_data', 'category_fields'))
		{
			ee()->smartforge->add_column(
				'category_fields',
				array(
					'legacy_field_data' => array(
						'type'    => 'CHAR(1)',
						'null'    => FALSE,
						'default' => 'n'
					)
				)
			);
			ee()->db->update('category_fields', array('legacy_field_data' => 'y'));
		}

		if ( ! ee()->db->field_exists('legacy_field_data', 'channel_fields'))
		{
			ee()->smartforge->add_column(
				'channel_fields',
				array(
					'legacy_field_data' => array(
						'type'    => 'CHAR(1)',
						'null'    => FALSE,
						'default' => 'n'
					)
				)
			);
			ee()->db->update('channel_fields', array('legacy_field_data' => 'y'));
		}

		if ( ! ee()->db->field_exists('m_legacy_field_data', 'member_fields'))
		{
			ee()->smartforge->add_column(
				'member_fields',
				array(
					'm_legacy_field_data' => array(
						'type'    => 'CHAR(1)',
						'null'    => FALSE,
						'default' => 'n'
					)
				)
			);
			ee()->db->update('member_fields', array('m_legacy_field_data' => 'y'));
		}
    }

	private function removeMemberHomepageTable()
	{
		ee()->smartforge->drop_table('member_homepage');
	}

}

// EOF
