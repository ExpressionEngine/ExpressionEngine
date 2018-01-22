<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

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
		ee()->load->dbforge();

		$steps = new ProgressIterator(
			array(
				'update_grid_field_search'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}


	// --------------------------------------------------------------------

	private function update_grid_field_search()
	{
		ee()->load->model('grid_model');

		// Get list of grid fields
		$fields = ee()->db->select('field_id')
			->where('field_type', 'grid')
			->where('field_search', 'y')
			->get('channel_fields')
			->result_array();

		if (empty($fields))
		{
			return;
		}

		$fields = array_map(function($element) {
			return $element['field_id'];
		}, $fields);

		ee()->grid_model->update_grid_search($fields);
	}
}

// EOF
