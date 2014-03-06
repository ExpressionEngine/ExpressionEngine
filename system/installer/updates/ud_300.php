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

		ee()->load->model('template_model');

		$config_override = (ee()->config->item('save_tmpl_files') == 'y' AND ee()->config->item('tmpl_file_basepath') != '');

        $sites = ee()->db->select('site_id, site_template_preferences')
            ->get('sites')
            ->result_array();

		foreach ($sites as $site)
		{
			$prefs = unserialize(base64_decode($site['site_template_preferences']));

			if ($config_override OR ($prefs['save_tmpl_files'] == 'y' AND $prefs['tmpl_file_basepath'] != ''))
			{
				$templates = ee()->template_model->fetch_last_edit(array('site_id' => $site['site_id']));

				foreach($templates as $template)
				{
					ee()->template_model->save_entity($template);
				}

			}
		}
	}

}

/* END CLASS */

/* End of file ud_300.php */
/* Location: ./system/expressionengine/installer/updates/ud_300.php */
