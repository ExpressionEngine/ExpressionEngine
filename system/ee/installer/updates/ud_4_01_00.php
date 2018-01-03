<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Updater\Version_4_1_0;

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
				'addPasswordChangeNotificationTemplates',
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
			require_once EE_APPPATH.'/language/'.ee()->config->item('language').'/email_data.php';

			$notify_template = ee('Model')->make('SpecialtyTemplate')
				->set([
					'template_name' => 'password_changed_notification',
					'template_type' => 'email',
					'template_subtype' => 'members',
					'data_title' => password_changed_notification_title(),
					'template_data' => password_changed_notification()
				])->save();
		}
	}
}

// EOF
