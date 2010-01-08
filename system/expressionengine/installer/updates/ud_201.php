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
        $Q[] = "ALTER TABLE `exp_modules` ADD COLUMN `has_publish_fields` char(1) NOT NULL default 'n'";

		// Everything else is the custom field conversion

		// Rename option groups to checkboxes
		$this->EE->db->select('field_id');
		$query = $this->EE->db->get_where('channel_fields', array('field_type' => 'option_group'));

		$ids = array_map('array_pop', $query->result_array());
		
		if (count($ids))
		{
			$this->EE->db->where_in('field_id', $ids);
			$this->EE->db->set('field_type', 'checkboxes');
			$this->EE->db->update('channel_fields');
		}

		// Add missing column
		$Q[] = "ALTER TABLE `exp_channel_fields` ADD `field_settings` TEXT NULL";
	    
		// Increase fieldtype name length
		$Q[] = "ALTER TABLE `exp_channel_fields` CHANGE `field_type` `field_type` VARCHAR(50) NOT NULL default 'text'";         
		
		// Add fieldtype table
		$Q[] = "CREATE TABLE exp_fieldtypes (
				fieldtype_id int(4) unsigned NOT NULL auto_increment, 
				name varchar(50) NOT NULL, 
				version varchar(12) NOT NULL, 
				settings text NULL, 
        		PRIMARY KEY `fieldtype_id` (`fieldtype_id`)
		)";
		
		// @todo install default fieldtypes
		
		foreach ($Q as $num => $sql)
		{
	        $this->EE->db->query($sql);
		}
		
		// Finished!
        return TRUE;

    }
}   
/* END CLASS */

/* End of file ud_201.php */
/* Location: ./system/expressionengine/installer/updates/ud_201.php */