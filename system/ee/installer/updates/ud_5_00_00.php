<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Updater\Version_5_0_0;

/**
 * Update
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
		$steps = new \ProgressIterator(
			[
				'addConfigTable',
				'addQueueTable',
			]
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function addConfigTable()
	{
		if (ee()->db->table_exists('config'))
		{
			return;
		}

		// Create table
		ee()->dbforge->add_field(
			[
				'config_id' => [
					'type'           => 'int',
					'constraint'     => 10,
					'unsigned'       => TRUE,
					'null'           => FALSE,
					'auto_increment' => TRUE
				],
				'site_id'   => [
					'type'       => 'int',
					'constraint' => 10,
					'unsigned'   => TRUE,
					'null'       => FALSE,
					'default'    => 0
				],
				'key'       => [
					'type'       => 'varchar',
					'constraint' => 64,
					'null'       => FALSE,
					'default'    => '',
				],
				'value'     => [
					'type'       => 'text',
				],
			]
		);
		ee()->dbforge->add_key('config_id', TRUE);
		ee()->dbforge->add_key(['site_id', 'key']);
		ee()->smartforge->create_table('config');

		// Populate table with existing config values
		$sites = ee()->db->get('sites');

		$prefs = [
			'site_channel_preferences',
			'site_member_preferences',
			'site_system_preferences',
			'site_template_preferences'
		];

		foreach ($sites->result_array() as $site)
		{
			$site_id = $site['site_id'];
			foreach ($prefs as $pref)
			{
				$data = unserialize(base64_decode($site[$pref]));
				foreach ($data as $key => $value)
				{
					ee('Model')->make('Config', [
						'site_id' => $site_id,
						'key' => $key,
						'value' => $value
					])->save();
				}
			}
		}

		// Drop the columns from the sites table
		foreach ($prefs as $pref)
		{
			ee()->smartforge->drop_column('sites', $pref);
		}

		foreach (ee()->config->divination('install') as $pref)
		{
			$value = ee()->config->item($pref);

			if ($value)
			{
				ee('Model')->make('Config', [
					'site_id' => 0,
					'key' => $pref,
					'value' => $value
				])->save();
			}
		}
	}

	private function addQueueTable()
	{
		ee()->dbforge->add_field(
			[
				'queue_id'   => [
					'type'           => 'int',
					'constraint'     => 10,
					'unsigned'       => TRUE,
					'null'           => FALSE,
					'auto_increment' => TRUE
				],
				'identifier' => [
					'type'       => 'varchar',
					'constraint' => 200,
					'null'       => FALSE
				],
				'step'       => [
					'type'           => 'int',
					'constraint'     => 10,
					'unsigned'       => TRUE,
					'null'           => FALSE,
					'default'        => 1
				],
				'total'      => [
					'type'           => 'int',
					'constraint'     => 10,
					'unsigned'       => TRUE,
					'null'           => FALSE,
					'default'        => 1
				],
				'data'       => [
					'type'       => 'mediumtext',
					'null'       => TRUE,
				],
			]
		);
		ee()->dbforge->add_key('queue_id', TRUE);
		ee()->dbforge->add_key('identifier');
		ee()->smartforge->create_table('queue');
	}
}

// EOF
