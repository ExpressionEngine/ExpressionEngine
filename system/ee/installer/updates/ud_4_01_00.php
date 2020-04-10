<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_4_1_0;

/**
 * Update
 */
class Updater {

	var $version_suffix = '';

	public $affected_tables = ['channels'];

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$steps = new \ProgressIterator(
			array(
				'addPasswordChangeNotificationTemplates',
				'addEmailChangeNotificationTemplates',
				'addPreviewURLToChannels',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	protected function addPasswordChangeNotificationTemplates()
	{
		$notify_template = ee('Model')->get('SpecialtyTemplate')
			->filter('template_name', 'password_changed_notification')
			->filter('template_type', 'email')
			->filter('template_subtype', 'members')
			->first();

		if ( ! $notify_template)
		{
			$sites = ee('Model')->get('Site')->all();
			require_once EE_APPPATH.'/language/'.ee()->config->item('language').'/email_data.php';

			foreach ($sites as $site)
			{
				$notify_template = ee('Model')->make('SpecialtyTemplate')
					->set([
						'template_name' => 'password_changed_notification',
						'template_type' => 'email',
						'template_subtype' => 'members',
						'data_title' => password_changed_notification_title(),
						'template_data' => password_changed_notification(),
						'site_id' => $site->site_id,
					])->save();
			}
		}
	}

	protected function addEmailChangeNotificationTemplates()
	{
		$notify_template = ee('Model')->get('SpecialtyTemplate')
			->filter('template_name', 'email_changed_notification')
			->filter('template_type', 'email')
			->filter('template_subtype', 'members')
			->first();

		if ( ! $notify_template)
		{
			$sites = ee('Model')->get('Site')->all();
			require_once EE_APPPATH.'/language/'.ee()->config->item('language').'/email_data.php';

			foreach ($sites as $site)
			{
				$notify_template = ee('Model')->make('SpecialtyTemplate')
					->set([
						'template_name' => 'email_changed_notification',
						'template_type' => 'email',
						'template_subtype' => 'members',
						'data_title' => email_changed_notification_title(),
						'template_data' => email_changed_notification(),
						'site_id' => $site->site_id,
					])->save();
			}
		}
	}

	protected function addPreviewURLToChannels()
	{
		if (ee()->db->field_exists('live_look_template', 'channels'))
		{
			ee()->smartforge->add_column(
				'channels',
				array(
					'preview_url' => array(
						'type'    => 'VARCHAR(100)',
						'null'    => TRUE,
					)
				)
			);

			$templates = ee()->db->select('channel_id, group_name, template_name')
				->from('channels')
				->join('templates', 'channels.live_look_template = templates.template_id')
				->join('template_groups', 'templates.group_id = template_groups.group_id')
				->where('live_look_template <> 0')
				->get()
				->result_array();

			if ( ! empty($templates))
			{
				$update = [];

				foreach ($templates as $index => $template)
				{
					$update[$index] = [
						'channel_id' => $template['channel_id'],
						'preview_url' => $template['group_name'] . '/' . $template['template_name'] . '/{entry_id}'
					];
				}

				ee()->db->update_batch('channels', $update, 'channel_id');
			}

			ee()->smartforge->drop_column('channels', 'live_look_template');
		}
	}
}

// EOF
