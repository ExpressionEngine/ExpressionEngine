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
		$Q[] = "UPDATE exp_channel_fields SET field_type = 'checkboxes' WHERE field_type = 'option_group'";

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
				has_global_settings char(1) default 'n', 
        		PRIMARY KEY `fieldtype_id` (`fieldtype_id`)
		)";
		
		
		// Install default field types
		
		$default_fts = array('select', 'text', 'textarea', 'date', 'file', 'multi_select', 'checkboxes', 'radio', 'rel');
		
		foreach($default_fts as $name)
		{
			$Q[] = "INSERT INTO `exp_fieldtypes` (`name`,`version`,`settings`,`has_global_settings`) VALUES ('".$name."','1.0','YTowOnt9','n')";
		}
		
		// Remove weblog from specialty_templates 
		$Q[] = "UPDATE `exp_specialty_templates` SET `template_data` = REPLACE(`template_data`, 'weblog_name', 'channel_name')";
		
		// Ditch 
		$Q[] = "DELETE FROM `exp_specialty_templates` WHERE `template_name` = 'admin_notify_trackback'";
		$Q[] = "DELETE FROM `exp_specialty_templates` WHERE `template_name` = 'admin_notify_gallery_comment'";
		$Q[] = "DELETE FROM `exp_specialty_templates` WHERE `template_name` = 'gallery_comment_notification'";
		
		foreach ($Q as $num => $sql)
		{
	        $this->EE->db->query($sql);
		}
		
		
		// Set settings to yes so nothing disappears
		
		$set_to_yes = array(
			'text'		=> array('show_smileys', 'show_glossary', 'show_spellcheck', 'field_show_formatting_btns', 'show_file_selector'),
			'textarea'	=> array('show_smileys', 'show_glossary', 'show_spellcheck', 'field_show_formatting_btns', 'show_file_selector')
		);
		
		foreach($set_to_yes as $fieldtype => $yes_settings)
		{
			$final_settings = array();
			
			foreach($yes_settings as $name)
			{
				$final_settings['field_'.$name] = 'y';
			}
			
			$this->EE->db->set('field_settings', base64_encode(serialize($final_settings)));
			$this->EE->db->where('field_type', $fieldtype);
			$this->EE->db->update('channel_fields');
		}

		// Finished!
        return TRUE;

    }
}   
/* END CLASS */

/* End of file ud_201.php */
/* Location: ./system/expressionengine/installer/updates/ud_201.php */