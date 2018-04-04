<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Updater\Version_4_2_0;

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
				'alterFluidFieldToMediumText'
			]
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function alterFluidFieldToMediumText()
	{
		$field_ids = ee('Model')->get('ChannelField')
			->fields('field_id')
			->filter('field_type', 'fluid_field')
			->all()
			->pluck('field_id');

		foreach ($field_ids as $field_id)
		{
			ee()->smartforge->modify_column(
				'channel_data_field_' . $field_id,
				[
					'field_id_' . $field_id => [
						'type' => 'mediumtext'
					]
				]
			);
		}
	}
}

// EOF
