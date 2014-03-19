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

	function Updater()
	{
		$this->EE =& get_instance();
	}

	function do_update()
	{
        $Q[] = "INSERT INTO exp_actions (class, method) VALUES ('Channel', 'insert_new_entry')";

        $DB->fetch_fields = TRUE;
        $query = ee()->db->query("SELECT * FROM exp_member_groups");
        $flag = FALSE;

		foreach ($query->fields as $field)
		{
			if ($field == 'can_assign_post_authors')
			{
				$flag = TRUE;
			}
		}

        if ($flag == FALSE)
        {
			$Q[] = "ALTER TABLE exp_member_groups ADD COLUMN can_assign_post_authors char(1) NOT NULL default 'n'";
		}

		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN trackback_system_enabled char(1) NOT NULL default 'y'";

		// Run the queries

		foreach ($Q as $sql)
		{
			ee()->db->query($sql);
		}

		/** -----------------------------------------
		/**  Update config file with new prefs
		/** -----------------------------------------*/

		$data = array(
                    		'max_tmpl_revisions' => '',
                    		'captcha_rand' 		=> 'n',
                    		'remap_pm_urls'		=> 'n',
                    		'remap_pm_dest'		=> '',
                    		'new_version_check'	=> 'y',
                    		'max_referrers'		=> '500'
					);

		ee()->config->_append_config_1x($data);

		return TRUE;
	}


}
// END CLASS




/* End of file ud_121.php */
/* Location: ./system/expressionengine/installer/updates/ud_121.php */