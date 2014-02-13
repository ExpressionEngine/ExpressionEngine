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
		$Q[] = "ALTER TABLE `exp_members` ADD INDEX (`group_id`);";
		$Q[] = "ALTER TABLE `exp_members` ADD INDEX (`unique_id`);";
		$Q[] = "ALTER TABLE `exp_members` ADD INDEX (`password`);";
		$Q[] = "ALTER TABLE `exp_sessions` ADD INDEX (`member_id`);";
		$Q[] = "ALTER TABLE `exp_template_no_access` ADD INDEX (`template_id`);";
		$Q[] = "ALTER TABLE `exp_trackbacks` ADD INDEX (`weblog_id`);";

		// pMachine News Feed for Control Panel homepage
		$Q[] = "ALTER TABLE exp_member_homepage ADD `pmachine_news_feed` char(1) NOT NULL default 'l'";
		$Q[] = "ALTER TABLE exp_member_homepage ADD `pmachine_news_feed_order` int(3) NOT NULL default '0'";

		// Run the queries
		foreach ($Q as $sql)
		{
			ee()->db->query($sql);
		}

		return TRUE;
	}

}
// END CLASS



/* End of file ud_151.php */
/* Location: ./system/expressionengine/installer/updates/ud_151.php */