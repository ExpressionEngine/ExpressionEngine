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
		$Q[] = "ALTER TABLE exp_members ADD show_sidebar char(1) NOT NULL default 'y' AFTER quick_tabs";		
		$Q[] = "ALTER TABLE exp_member_fields ADD m_field_cp_reg char(1) NOT NULL default 'n' AFTER m_field_reg";
		$Q[] = "ALTER TABLE exp_accessories CHANGE member_groups member_groups varchar(255) NOT NULL";
		$Q[] = "ALTER TABLE exp_member_groups ADD can_edit_html_buttons char(1) NOT NULL DEFAULT 'n' AFTER can_view_profiles";
		$Q[] = "UPDATE exp_member_groups SET can_edit_html_buttons = 'y' WHERE can_access_cp = 'y'";

		if ($this->EE->db->table_exists('exp_comments'))
		{
			$Q[] = "UPDATE exp_comments SET location = '' WHERE location = '0'";	
		}

		$count = count($Q);
		
		foreach ($Q as $num => $sql)
		{
			$this->EE->progress->update_state("Running Query $num of $count");
	        $this->EE->db->query($sql);
		}
		
		// Remove allow_multi_emails from config
		$this->EE->config->_update_config(array(), array('allow_multi_emails' => ''));
		
		return TRUE;
	}
}   
/* END CLASS */

/* End of file ud_212.php */
/* Location: ./system/expressionengine/installer/updates/ud_212.php */