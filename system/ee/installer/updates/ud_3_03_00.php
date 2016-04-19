<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.2.1
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
		$steps = new ProgressIterator(
			array(
				'add_can_debug_column',
				'use_site_default_localization_settings',
				'update_doc_url'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Adds the "can_debug" column to the sessions table
	 *
	 * @return void
	 */
	private function add_can_debug_column()
	{
		if ( ! ee()->db->field_exists('can_debug', 'sessions'))
		{
			ee()->smartforge->add_column(
				'sessions',
				array(
					'can_debug' => array(
						'type'       => 'char',
						'constraint' => 1,
						'default'    => 'n',
						'null'       => FALSE
					)
				)
			);
		}
	}

	/**
	 * Allows our localized date and time settings to have NULL values in the
	 * db, and removes the defaults. We also go through all members and for
	 * those whose settings match the configured default we set their values
	 * to NULL, which will cause them to use the system default.
	 *
	 * @return void
	 */
	private function use_site_default_localization_settings()
	{
		// Allow NULL and make that the default
		ee()->smartforge->modify_column(
			'members',
			array(
				'timezone' => array(
					'name'       => 'timezone',
					'type'       => 'varchar',
					'constraint' => 50,
					'null'       => TRUE,
					'default'    => NULL
				),
				'date_format' => array(
					'name'       => 'date_format',
					'type'       => 'varchar',
					'constraint' => 8,
					'null'       => TRUE,
					'default'    => NULL
				),
				'time_format' => array(
					'name'       => 'time_format',
					'type'       => 'char',
					'constraint' => 2,
					'null'       => TRUE,
					'default'    => NULL
				),
				'include_seconds' => array(
					'name'       => 'include_seconds',
					'type'       => 'char',
					'constraint' => 1,
					'null'       => TRUE,
					'default'    => NULL
				)
			)
		);

		$sites = ee()->db->select('site_id')
			->order_by('site_id', 'asc')
			->get('sites')
			->result_array();
		$site_1 = array_shift($sites);
		$msm_config = new MSM_Config();

		$msm_config->site_prefs('', 1);
		$same = TRUE;

		$defaults = array(
			'timezone'        => $msm_config->item('default_site_timezone'),
			'date_format'     => $msm_config->item('date_format'),
			'time_format'     => $msm_config->item('time_format'),
			'include_seconds' => $msm_config->item('include_seconds')
		);

		foreach ($sites as $row)
		{
			$msm_config->site_prefs('', $row['site_id']);

			foreach ($defaults as $key => $value)
			{
				if ($key == 'timezone')
				{
					$key = 'default_site_timezone';
				}

				if ($msm_config->item($key) != $value)
				{
					$same = FALSE;
					break 2;
				}
			}
		}

		if ($same)
		{
			// Update all members that match the system defaults
			ee()->db->set('timezone', NULL);
			ee()->db->set('date_format', NULL);
			ee()->db->set('time_format', NULL);
			ee()->db->set('include_seconds', NULL);

			foreach ($defaults as $key => $value)
			{
				ee()->db->where($key, $value);

			}

			ee()->db->update('members');
		}
	}

	/**
	 * Update doc_url to our current URL
	 */
	public function update_doc_url()
	{
		if (ee()->config->item('doc_url') == 'http://ellislab.com/expressionengine/user-guide/')
		{
			ee()->config->update_site_prefs(array(
				'doc_url' => 'https://docs.expressionengine.com/v3/'
			));
		}
	}
}

// EOF
