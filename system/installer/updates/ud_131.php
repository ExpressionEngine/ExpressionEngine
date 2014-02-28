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

		// Grab the config file
		if ( ! @include(ee()->config->config_path))
		{
			show_error('Your config'.EXT.' file is unreadable. Please make sure the file exists and that the file permissions to 666 on the following file: expressionengine/config/config.php');
		}

		if (isset($conf))
		{
			$config = $conf;
		}

		// Does the config array exist?
		if ( ! isset($config) OR ! is_array($config))
		{
			show_error('Your config'.EXT.' file does not appear to contain any data.');
		}

		$this->config =& $config;
	}

	function do_update()
	{
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN show_forum_cluster char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_weblog_titles ADD COLUMN forum_topic_id int(10) unsigned NOT NULL";
		$Q[] = "ALTER TABLE `exp_members` ADD `accept_messages` CHAR(1) DEFAULT 'y' NOT NULL AFTER `private_messages`";

		// Run the queries

		foreach ($Q as $sql)
		{
			ee()->db->query($sql);
		}


		if ( ! isset($this->config['enable_throttling']))
		{
			$data['enable_throttling'] = "y";
			ee()->config->_append_config_1x($data);
		}

		return TRUE;
	}


}
// END CLASS

/* End of file ud_131.php */
/* Location: ./system/expressionengine/installer/updates/ud_131.php */