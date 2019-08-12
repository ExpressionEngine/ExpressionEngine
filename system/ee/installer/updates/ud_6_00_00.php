<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_6_0_0;

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
				'removeDefaultAvatars'
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

	private function removeDefaultAvatars()
	{
		ee('Model')->get('UploadDestination')
			->filter('name', 'IN', ['Default Avatars'])
			->all()
			->indexBy('name')
			->delete();


		ee('Model')->get('Config')->filter('key', 'enable_avatars')->delete();
		ee('Model')->get('Config')->filter('key', 'allow_avatar_uploads')->delete();
	}
}

// EOF
