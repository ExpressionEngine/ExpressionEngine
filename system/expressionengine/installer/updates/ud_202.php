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
        /*
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

		// Leftover trackback indication can go
		 $Q[] = "DELETE FROM `exp_modules` WHERE `module_name` = 'Trackback'";

		// Email field size consistent with RFC2822 recommended header line limit of 78 (minus "from: ")
        $Q[] = "ALTER TABLE `exp_members` CHANGE `email` `email` varchar(72) NOT NULL";
		$count = count($Q);
		
		// If there is no action id, add it
        $this->EE->db->where('class', 'channel');
        $this->EE->db->where('method', 'filemanager_endpoint');
        $query = $this->EE->db->get('actions');

        if ($query->num_rows() == 0)
        {
			$Q[] = "INSERT INTO exp_actions (class,method) VALUES ('channel','filemanager_endpoint')";
        }

*/
		// If the action id is for the Weblog class, change it
		$Q[] = "UPDATE exp_actions SET class = 'Channel' WHERE class = 'Weblog'";
		

		// If they have existing Pages, saved array needs to be updated to new format
        if ($this->EE->db->field_exists('site_pages', 'sites'))
		{
			$this->EE->db->select('site_id, site_pages, site_system_preferences');
        	$this->EE->db->where('site_pages !=', '');
        	$query = $this->EE->db->get('sites');

        	if ($query->num_rows() > 0)
        	{
				foreach ($query->result_array() as $row)
				{
					$system_prefs =  base64_decode($row['site_system_preferences']);
					$skip = FALSE;
					$encode_only = FALSE;
					
					// Note- to this point, pages may not have been encoded
					$old_pages = strip_slashes($row['site_pages']);

					if ( ! is_string($old_pages) OR substr($old_pages, 0, 2) != 'a:')
					{
						$skip = TRUE;
						
						// Try it base64 encoded
						$old_pages = base64_decode($row['site_pages']);						
					}
					
					if ($skip == TRUE && (is_string($old_pages) && substr($old_pages, 0, 2) == 'a:'))
					{
						$skip = FALSE;
					}
					
					if ($skip)
					{
						continue;
					}
					else
					{
						if (isset($old_pages[$row['site_id']]['url']))
						{
							//  Site pages have already been updated, but may not be encoded
							$new_pages = unserialize($old_pages);
							$encode_only = TRUE;
						}
						else
						{
							$new_pages[$row['site_id']] = unserialize($old_pages);
						}
					}
					
					if ($encode_only == FALSE)
					{
						if ( ! is_string($system_prefs) OR substr($system_prefs, 0, 2) != 'a:')
						{
							$new_pages[$row['site_id']]['url'] = '';
						}
						else
						{
							$prefs = unserialize($system_prefs);
						
							$url = (isset($prefs['site_url'])) ? $prefs['site_url'].'/' : '/';
							$url .= (isset($prefs['site_index'])) ? $prefs['site_index'].'/' : '/';

							$new_pages[$row['site_id']]['url'] = preg_replace("#(^|[^:])//+#", "\\1/", $url);						
						}
					}
					
					$Q[] = "UPDATE exp_sites SET site_pages = '".base64_encode(serialize($new_pages))."' WHERE site_id = '".$row['site_id']."'";

				}
			}
		}

		foreach ($Q as $num => $sql)
		{
			$this->EE->progress->update_state("Running Query $num of $count");
	        $this->EE->db->query($sql);
		}


		// Drop enable image resize configuration option
		$this->EE->db->select('site_channel_preferences');
		$query = $this->EE->db->get('sites');

		foreach ($query->result() as $row)
		{
			$settings = unserialize(base64_decode($row->site_channel_preferences));

			if (isset($settings['enable_image_resizing']))
			{
				unset($settings['enable_image_resizing']);
			}

			$this->EE->db->set('site_channel_preferences', base64_encode(serialize($settings)));
			$this->EE->db->update('sites');
		}

		// Finished!
        return TRUE;
    }
}   
/* END CLASS */

/* End of file ud_202.php */
/* Location: ./system/expressionengine/installer/updates/ud_202.php */
