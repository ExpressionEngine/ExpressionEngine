<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2003 - 2013, EllisLab, Inc.
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
		$this->EE->smartforge->add_column(
			'modules',
			array(
				'has_publish_fields' => array(
					'type'			=> 'char',
					'constraint'	=> 1,
					'null'			=> FALSE,
					'default'		=> 'n'
				)
			)
		);

		// Everything else is the custom field conversion

		// Rename option groups to checkboxes
		$this->EE->db->set('field_type', 'checkboxes');
		$this->EE->db->where('field_type', 'option_group');	
		$this->EE->db->update('channel_fields');

		// Add missing column
		$this->EE->smartforge->add_column(
			'channel_fields',
			array(
				'field_settings' => array(
					'type'			=> 'text',
					'null'			=> TRUE
				)
			)
		);
	    
		// Increase fieldtype name length
		$this->EE->smartforge->modify_column(
			'channel_fields',
			array(
				'field_type' => array(
					'name'			=> 'field_type',
					'type'			=> 'varchar',
					'constraint'	=> 50,
					'null'			=> FALSE,
					'default'		=> 'text',
				),
			)
		);        
		
		// Add fieldtype table

		$this->EE->dbforge->add_field(
			array(
				'fieldtype_id' => array(
					'type'				=> 'int',
					'constraint'		=> 4,
					'unsigned'			=> TRUE,
					'null'				=> FALSE,
					'auto_increment'	=> TRUE
				),
				'name' => array(
					'type'				=> 'varchar',
					'constraint'		=> 50,
					'null'				=> FALSE
				),
				'version' => array(
					'type'				=> 'varchar',
					'constraint'		=> 12,
					'null'				=> FALSE
				),
				'settings' => array(
					'type'				=> 'text',
					'null'				=> TRUE
				),
				'has_global_settings' => array(
					'type'				=> 'char',
					'constraint'		=> 1,
					'default'			=> 'n'
				)
			)
		);
		
		$this->EE->dbforge->add_key('fieldtype_id', TRUE);
		$this->EE->dbforge->create_table('fieldtypes', TRUE);
		
		// Install default field types
		
		$default_fts = array('select', 'text', 'textarea', 'date', 'file', 'multi_select', 'checkboxes', 'radio', 'rel');
		
		foreach($default_fts as $name)
		{
			$values = array(
						'name'					=> $name,
						'version'				=> '1.0',
						'settings'				=> 'YTowOnt9',
						'has_global_settings'	=> 'n'
						);

			$this->EE->smartforge->insert_set('fieldtypes', $values, $values);
		}
		
		// Remove weblog from specialty_templates 
		$this->EE->db->query("UPDATE `exp_specialty_templates` SET `template_data` = REPLACE(`template_data`, 'weblog_name', 'channel_name')");
		
		// Ditch 
		$this->EE->db->where('template_name', 'admin_notify_trackback');
		$this->EE->db->delete('specialty_templates');

		$this->EE->db->where('template_name', 'admin_notify_gallery_comment');
		$this->EE->db->delete('specialty_templates');

		$this->EE->db->where('template_name', 'gallery_comment_notification');
		$this->EE->db->delete('specialty_templates');
		
		
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