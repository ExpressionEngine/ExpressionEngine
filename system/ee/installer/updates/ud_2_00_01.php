<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_2_0_1;

/**
 * Update
 */
class Updater
{
    public $version_suffix = 'pb01';

    public function do_update()
    {
        // Modules now have a tab setting
        ee()->smartforge->add_column(
            'modules',
            array(
                'has_publish_fields' => array(
                    'type' => 'char',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 'n'
                )
            )
        );

        // Everything else is the custom field conversion

        // Rename option groups to checkboxes
        ee()->db->set('field_type', 'checkboxes');
        ee()->db->where('field_type', 'option_group');
        ee()->db->update('channel_fields');

        // Add missing column
        ee()->smartforge->add_column(
            'channel_fields',
            array(
                'field_settings' => array(
                    'type' => 'text',
                    'null' => true
                )
            )
        );

        // Increase fieldtype name length
        ee()->smartforge->modify_column(
            'channel_fields',
            array(
                'field_type' => array(
                    'name' => 'field_type',
                    'type' => 'varchar',
                    'constraint' => 50,
                    'null' => false,
                    'default' => 'text',
                ),
            )
        );

        // Add fieldtype table

        ee()->dbforge->add_field(
            array(
                'fieldtype_id' => array(
                    'type' => 'int',
                    'constraint' => 4,
                    'unsigned' => true,
                    'null' => false,
                    'auto_increment' => true
                ),
                'name' => array(
                    'type' => 'varchar',
                    'constraint' => 50,
                    'null' => false
                ),
                'version' => array(
                    'type' => 'varchar',
                    'constraint' => 12,
                    'null' => false
                ),
                'settings' => array(
                    'type' => 'text',
                    'null' => true
                ),
                'has_global_settings' => array(
                    'type' => 'char',
                    'constraint' => 1,
                    'default' => 'n'
                )
            )
        );

        ee()->dbforge->add_key('fieldtype_id', true);
        ee()->smartforge->create_table('fieldtypes');

        // Install default fieldtypes

        $default_fts = array('select', 'text', 'textarea', 'date', 'file', 'multi_select', 'checkboxes', 'radio', 'rel');

        foreach ($default_fts as $name) {
            $values = array(
                'name' => $name,
                'version' => '1.0',
                'settings' => 'YTowOnt9',
                'has_global_settings' => 'n'
            );

            ee()->smartforge->insert_set('fieldtypes', $values, $values);
        }

        // Remove weblog from specialty_templates
        ee()->db->set('data_title', "REPLACE(`data_title`, 'weblog', 'channel')", false);
        ee()->db->update('specialty_templates');

        ee()->db->set('template_data', "REPLACE(`template_data`, 'weblog_name', 'channel_name')", false);
        ee()->db->update('specialty_templates');

        // Ditch
        ee()->db->where('template_name', 'admin_notify_trackback');
        ee()->db->delete('specialty_templates');

        ee()->db->where('template_name', 'admin_notify_gallery_comment');
        ee()->db->delete('specialty_templates');

        ee()->db->where('template_name', 'gallery_comment_notification');
        ee()->db->delete('specialty_templates');

        // Set settings to yes so nothing disappears

        $set_to_yes = array(
            'text' => array('show_smileys', 'show_glossary', 'show_spellcheck', 'field_show_formatting_btns', 'show_file_selector'),
            'textarea' => array('show_smileys', 'show_glossary', 'show_spellcheck', 'field_show_formatting_btns', 'show_file_selector')
        );

        foreach ($set_to_yes as $fieldtype => $yes_settings) {
            $final_settings = array();

            foreach ($yes_settings as $name) {
                $final_settings['field_' . $name] = 'y';
            }

            ee()->db->set('field_settings', base64_encode(serialize($final_settings)));
            ee()->db->where('field_type', $fieldtype);
            ee()->db->update('channel_fields');
        }

        // Finished!
        return true;
    }
}
/* END CLASS */

// EOF
