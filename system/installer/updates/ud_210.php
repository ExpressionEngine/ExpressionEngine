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
		// update docs location
		if ($this->EE->config->item('doc_url') == 'http://expressionengine.com/public_beta/docs/')
		{
			$this->EE->config->update_site_prefs(array('doc_url' => 'http://ellislab.com/expressionengine/user-guide/'), 1);
		}
		
		if ( ! $this->EE->db->field_exists('can_access_fieldtypes', 'member_groups'))
		{
			$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_fieldtypes` char(1) NOT NULL DEFAULT 'n' AFTER `can_access_files`";            
		}
		
		$Q[] = 'UPDATE exp_member_groups SET can_access_fieldtypes = "y" WHERE group_id = 1';
		$Q[] = 'UPDATE exp_actions SET class = "Channel" WHERE class = "channel"';
		
		$count = count($Q);
		
		foreach ($Q as $num => $sql)
		{
			$this->EE->progress->update_state("Running Query $num of $count");
	        $this->EE->db->query($sql);
		}
		
		return TRUE;
	}
}   
/* END CLASS */

/* End of file ud_210.php */
/* Location: ./system/expressionengine/installer/updates/ud_210.php */