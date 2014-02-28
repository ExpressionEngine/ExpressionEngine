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
		$Q[] = "ALTER TABLE exp_members ADD `ignore_list` text not null AFTER `sig_img_height`";

		/*
		 * ------------------------------------------------------
		 *  Add Edit Date and Attempt to Intelligently Set Values
		 * ------------------------------------------------------
		 */
		require PATH_CORE.'core.localize'.EXT;
		$LOC = new Localize();

		$Q[] = "ALTER TABLE exp_templates ADD `edit_date` int(10) default 0 AFTER `template_notes`";
		$Q[] = "UPDATE exp_templates SET edit_date = '".$LOC->now."'";

		$query = ee()->db->query("SELECT item_id, MAX(item_date) as max_date FROM `exp_revision_tracker` GROUP BY item_id");

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$Q[] = "UPDATE exp_templates SET edit_date = '".$DB->escape_str($row['max_date'])."' WHERE template_id = '".$DB->escape_str($row['item_id'])."'";
			}
		}

		/*
		 * ------------------------------------------------------
		 *  Add Hash for Bulletins and Set For Existing Bulletins
		 * ------------------------------------------------------
		 */

		$Q[] = "ALTER TABLE `exp_member_bulletin_board` ADD `hash` varchar(10) default '' AFTER `bulletin_date`";
		$Q[] = "ALTER TABLE `exp_member_bulletin_board` ADD INDEX (`hash`)";

		$query = ee()->db->query("SELECT DISTINCT bulletin_date, bulletin_message, sender_id FROM `exp_member_bulletin_board`");

		if ($query->num_rows() > 0)
		{
			require PATH_CORE.'core.functions'.EXT;
			$FNS = new Functions();

			foreach($query->result_array() as $row)
			{
				$Q[] = "UPDATE exp_member_bulletin_board SET hash = '".$DB->escape_str($FNS->random('alpha', 10))."'
						WHERE bulletin_date = '".$DB->escape_str($row['bulletin_date'])."'
						AND bulletin_message = '".$DB->escape_str($row['bulletin_message'])."'
						AND sender_id = '".$DB->escape_str($row['sender_id'])."'";
			}
		}

		// run the queries
		foreach ($Q as $sql)
		{
			ee()->db->query($sql);
		}

		return TRUE;
	}
	/* END */

}
// END CLASS



/* End of file ud_152.php */
/* Location: ./system/expressionengine/installer/updates/ud_152.php */