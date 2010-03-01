<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      ExpressionEngine Dev Team
 * @copyright   Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license     http://expressionengine.com/docs/license.html
 * @link        http://expressionengine.com
 * @since       Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package     ExpressionEngine
 * @subpackage  Core
 * @category    Core
 * @author      ExpressionEngine Dev Team
 * @link        http://expressionengine.com
 */
class Updater {

	var $version_suffix = 'pb01';
    
    function Updater()
    {
        $this->EE =& get_instance();
    }

    function do_update()
    {
        // Modules now have a tab setting
        $Q[] = "UPDATE `exp_relationships` SET rel_type = 'channel' WHERE rel_type = 'blog'";
        $Q[] = "ALTER TABLE `exp_channels` DROP COLUMN `show_url_title`";
        $Q[] = "ALTER TABLE `exp_channels` DROP COLUMN `show_ping_cluster`";
        $Q[] = "ALTER TABLE `exp_channels` DROP COLUMN `show_options_cluster`";
        $Q[] = "ALTER TABLE `exp_channels` DROP COLUMN `show_forum_cluster`";
        $Q[] = "ALTER TABLE `exp_channels` DROP COLUMN `show_show_all_cluster`";
        $Q[] = "ALTER TABLE `exp_channels` DROP COLUMN `show_status_menu`";
        $Q[] = "ALTER TABLE `exp_channels` DROP COLUMN `show_categories_menu`";
        $Q[] = "ALTER TABLE `exp_channels` DROP COLUMN `show_date_menu`";
        $Q[] = "ALTER TABLE `exp_channels` DROP COLUMN `show_pages_cluster`";
        $Q[] = "ALTER TABLE `exp_channels` DROP COLUMN `show_author_menu`";
		$count = count($Q);
		
		foreach ($Q as $num => $sql)
		{
			$this->EE->progress->update_state("Running Query $num of $count");
	        $this->EE->db->query($sql);
		}
		
		// Finished!
        return TRUE;

    }
}   
/* END CLASS */

/* End of file ud_202.php */
/* Location: ./system/expressionengine/installer/updates/ud_202.php */