<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
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

	function Updater()
	{
		$this->EE =& get_instance();
	}

	function do_update()
	{
		$Q[] = "ALTER TABLE `exp_search` CHANGE `query` `query` MEDIUMTEXT NULL DEFAULT NULL";
		$Q[] = "ALTER TABLE `exp_search` CHANGE `custom_fields` `custom_fields` MEDIUMTEXT NULL DEFAULT NULL";

		$Q[] = "ALTER TABLE `exp_templates` ADD `last_author_id` INT(10) UNSIGNED NOT NULL AFTER `edit_date`";
		$Q[] = "ALTER TABLE `exp_revision_tracker` ADD `item_author_id` INT(10) UNSIGNED NOT NULL AFTER `item_date`";

		$query = ee()->db->query('SHOW FIELDS FROM exp_weblog_data');

		foreach ($query->result_array() as $row)
		{
			if (strncmp($row['Field'], 'field_ft', 8) == 0)
			{
				$Q[] = "ALTER TABLE `exp_weblog_data` CHANGE `{$row['Field']}` `{$row['Field']}` TINYTEXT NULL";
			}
		}

		// run our queries
		foreach ($Q as $sql)
		{
			ee()->db->query($sql);
		}

		ee()->load->helper('string');

		// We need to add a new template preference, so we'll fetch the existing site template prefs
		$query = ee()->db->query("SELECT site_id, site_template_preferences FROM exp_sites");

		foreach ($query->result_array() as $row)
		{
			$prefs = strip_slashes(unserialize($row['site_template_preferences']));

			// Add our new pref to the array
			$prefs['strict_urls'] = ($prefs['site_404'] == FALSE) ? 'n' : 'y';

			// Update the DB
			ee()->db->query(ee()->db->update_string('exp_sites', array('site_template_preferences' => serialize($prefs)), "site_id = '".$row['site_id']."'"));
		}

		return TRUE;
	}
	/* END */

}
/* END CLASS */



/* End of file ud_165.php */
/* Location: ./system/expressionengine/installer/updates/ud_165.php */