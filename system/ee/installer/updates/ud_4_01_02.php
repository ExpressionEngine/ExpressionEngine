<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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
			ee()->update_notices->item(' Please rename the field(s) and update your templates accordingly.');
		}
	}
}

// EOF
