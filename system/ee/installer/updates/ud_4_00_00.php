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

		ee()->smartforge->modify_column('field_groups', array(
			'site_id' => array(
				'type'     => 'int',
				'unsigned' => TRUE,
				'null'     => TRUE,
			),
		));

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
		ee()->smartforge->create_table('channel_field_groups_pivot');

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
		ee()->smartforge->create_table('channel_fields_pivot');

		$channels = ee()->db->select('channel_id, field_group')
			->where('field_group IS NOT NULL')
			->get('channels')
			->result();

		foreach ($channels as $channel)
		{
			ee()->db->insert('channel_field_groups_pivot', array(
				'channel_id' => $channel->channel_id,
				'group_id' => $channel->field_group
			));
		}

		ee()->smartforge->drop_column('channels', 'field_group');
	}

}

// EOF
