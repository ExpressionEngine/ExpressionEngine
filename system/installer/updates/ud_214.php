<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license     http://ellislab.com/expressionengine/user-guide/license.html
 * @link        http://ellislab.com
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
 * @author      EllisLab Dev Team
 * @link        http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

	function Updater()
	{
		$this->EE =& get_instance();
	}

	function do_update() 
	{ 
		// IDEK
		$Q = array();
		
		// Channel Data
		$query = $this->EE->db->query("SHOW INDEX FROM `exp_channel_data`");
		$indexes = array();
		
		foreach ($query->result_array() as $row)
		{
			$indexes[] = $row['Key_name'];
		}
			
		if (in_array('weblog_id', $indexes))
		{
			$Q[] = "ALTER TABLE `exp_channel_data` DROP KEY `weblog_id`";
		}
			
		if ( ! in_array('channel_id', $indexes))
		{
			$Q[] = "ALTER TABLE `exp_channel_data` ADD KEY (`channel_id`)";				
		}
		
		// Channel Titles
		$query = $this->EE->db->query("SHOW INDEX FROM `exp_channel_titles`");
		$indexes = array();
		
		foreach ($query->result_array() as $row)
		{
			$indexes[] = $row['Key_name'];
		}
			
		if (in_array('weblog_id', $indexes))
		{
			$Q[] = "ALTER TABLE `exp_channel_titles` DROP KEY `weblog_id`";
		}
			
		if ( ! in_array('channel_id', $indexes))
		{
			$Q[] = "ALTER TABLE `exp_channel_titles` ADD KEY (`channel_id`)";				
		}

		$count = count($Q);
		
		if ($count > 0)
		{
			foreach ($Q as $num => $sql)
			{
				$this->EE->progress->update_state("Running Query $num of $count");
	        	$this->EE->db->query($sql);
			}
		}
		
		return TRUE;
	}
}   
/* END CLASS */

/* End of file ud_214.php */
/* Location: ./system/expressionengine/installer/updates/ud_214.php */