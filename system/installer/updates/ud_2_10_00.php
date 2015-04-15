<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.10.0
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
 * @link		http://ellislab.com
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
				'_modify_category_data_fields',
				'_date_format_years'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}


	// --------------------------------------------------------------------

	/**
	 * Modify custom fields in exp_category_data.  Again.
	 *
	 * Redo from ud_270 where the wrong table name was used, so the inconsistent
	 * data could still be in there.
	 * Possible mix of column types with regard to allowing NULL due to a bug
	 * in MSM.  Modifying to make sure they all allow NULL for consistency.
	 */
	private function _modify_category_data_fields()
	{
		// Get all fields

		$cat_fields = ee()->db->get('category_fields');

		foreach ($cat_fields->result_array() as $field)
		{
			$field_name = 'field_id_'.$field['field_id'];

			ee()->smartforge->modify_column(
				'category_field_data',
				array(
					$field_name => array(
						'name' 			=> $field_name,
						'type' 			=> 'text',
						'null' 			=> TRUE
					)
				)
			);
		}
	}

	// -------------------------------------------------------------------------

	/**
	 * Change all date formatting columns to show full years again
	 * @return void
	 */
	private function _date_format_years()
	{
		// Update members' date formats
		ee()->db->update(
			'members',
			array('date_format' => '%n/%j/%Y'),
			array('date_format' => '%n/%j/%y')
		);
		ee()->db->update(
			'members',
			array('date_format' => '%j/%n/%Y'),
			array('date_format' => '%j-%n-%y')
		);

		// Update the site preferences
		$sites = ee()->db->select('site_id')->get('sites');
		$msm_config = new MSM_Config();

		if ($sites->num_rows() > 0)
		{
			foreach ($sites->result_array() as $row)
			{
				$msm_config->site_prefs('', $row['site_id']);

				$localization_preferences = array();

				if ($msm_config->item('date_format') == '%n/%j/%y')
				{
					$localization_preferences['date_format'] = '%n/%j/%Y';
				}
				elseif ($msm_config->item('date_format') == '%j-%n-%y')
				{
					$localization_preferences['date_format'] = '%j/%n/%Y';
				}

				if ( ! empty($localization_preferences))
				{
					$msm_config->update_site_prefs(
						$localization_preferences,
						$row['site_id']
					);
				}
			}
		}
	}
}
// EOF