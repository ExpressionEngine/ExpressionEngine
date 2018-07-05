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
		$sites = ee('Model')->get('Site')->all();

		$prefs = [
			'site_channel_preferences',
			'site_member_preferences',
			'site_system_preferences',
			'site_template_preferences'
		];

		foreach ($sites as $site)
		{
			$site_id = $site->getId();
			foreach ($prefs as $pref)
			{
				foreach ($site->$pref->getValues() as $key => $value)
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
}

// EOF
