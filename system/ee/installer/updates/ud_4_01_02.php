<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_4_1_2;

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
			array(
				'warnAboutContentReservedWord',
				'addMissingNotificationTemplates'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function warnAboutContentReservedWord()
	{
		$content_check = ee('Model')->get('ChannelField')
			->filter('field_name', 'content')
			->count();

		if ($content_check)
		{
			ee()->update_notices->setVersion('4.1.2');
			ee()->update_notices->header('"content" is now a reserved word and will conflict with your Fluid Fields');
			ee()->update_notices->item(' Please rename your Channel Field(s) and update your templates accordingly.');
		}
	}

	private function addMissingNotificationTemplates()
	{
		$sites = ee('Model')->get('Site')->all();

		$email_templates = ee('Model')->get('SpecialtyTemplate')
			->filter('template_name', 'email_changed_notification')
			->all()
			->indexBy('site_id');

		$password_templates = ee('Model')->get('SpecialtyTemplate')
			->filter('template_name', 'password_changed_notification')
			->all()
			->indexBy('site_id');

		$email_template_data = $email_templates[1]->getValues();
		$password_template_data = $password_templates[1]->getValues();
		unset($email_template_data['template_id']);
		unset($password_template_data['template_id']);

		foreach ($sites as $site)
		{
			if ( ! array_key_exists($site->site_id, $email_templates))
			{
				$email_template_data['site_id'] = $site->site_id;
				ee('Model')->make('SpecialtyTemplate', $email_template_data)->save();
			}

			if ( ! array_key_exists($site->site_id, $password_templates))
			{
				$password_template_data['site_id'] = $site->site_id;
				ee('Model')->make('SpecialtyTemplate', $password_template_data)->save();
			}
		}
	}
}

// EOF
