<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0.0
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
				'_update_templates_save_as_files'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}
		return TRUE;
	}

	// -------------------------------------------------------------------

	/**
	 * We are removing the per-template "save to file" option. Instead it is
	 * an all or nothing proposition based on the global preferences. So we are
	 * removing the column from the database and resyncing the templates.
	 */
	private function _update_templates_save_as_files()
	{
		ee()->smartforge->drop_column('templates', 'save_template_file');

		$installer_config = ee()->config;

		require_once(APPPATH . 'libraries/Extensions.php');
		ee()->extensions = new Installer_Extensions();
		ee()->load->model('template_model');

		$sites = ee()->db->select('site_id')
			->get('sites')
			->result_array();

		// Loop through the sites and save to file any templates that are only
		// in the database
		foreach ($sites as $site)
		{
			ee()->config = new MSM_Config();
			ee()->config->site_prefs('', $site['site_id']);

			if (ee()->config->item('save_tmpl_files') == 'y' AND ee()->config->item('tmpl_file_basepath') != '') {
				$templates = ee()->template_model->fetch_last_edit(array('templates.site_id' => $site['site_id']), TRUE);

				foreach($templates as $template)
				{
					if ( ! $template->loaded_from_file)
					{
						ee()->template_model->save_to_file($template);
					}
				}
			}

		}
		ee()->config = $installer_config;
	}

}
/* END CLASS */

/* End of file ud_300.php */
/* Location: ./system/expressionengine/installer/updates/ud_300.php */