<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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
				'removeMemberHomepageTable',
				'globalizeSave_tmpl_files'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function removeMemberHomepageTable()
	{
		ee()->smartforge->drop_table('member_homepage');
	}

	// -------------------------------------------------------------------------

	/**
	 * Remove save_tmpl_files from exp_sites
	 * If all sites currently set to no, add a config override
	 */
	private function globalizeSave_tmpl_files()
	{
		// Do we need to override?
		$save_as_file = FALSE;
		$msm_config = new MSM_Config();

		$all_site_ids_query = ee()->db->select('site_id')
			->get('sites')
			->result();

		foreach ($all_site_ids_query as $site)
		{
			$config = ee()->config->site_prefs('', $site->site_id, FALSE);

			// If ANY sites save as file, they all must
			if (isset($config['save_tmpl_files']) && $config['save_tmpl_files'] == 'y')
			{
				$save_as_file = TRUE;
				break;
			}

		}

		ee()->config->remove_config_item(array('save_tmpl_files'));

		if ($save_as_file == FALSE)
		{
			// Add config override
			ee()->config->_update_config(array('save_tmpl_files' => 'n'));
		}
	}

}

// EOF
