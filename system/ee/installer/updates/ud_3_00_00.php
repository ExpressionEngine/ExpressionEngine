<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_3_0_0;

/**
 * Update
 */
class Updater
{
    public $version_suffix = '';

    /**
     * Do Update
     *
     * @return TRUE
     */
    public function do_update()
    {
        ee()->load->dbforge();

        $steps = new \ProgressIterator(
            array(
                '_move_database_information',
                '_update_email_cache_table',
                '_update_upload_no_access_table',
                '_insert_comment_settings_into_db',
                '_insert_cookie_settings_into_db',
                '_create_plugins_table',
                '_remove_accessories_table',
                '_update_specialty_templates_table',
                '_update_templates_save_as_files',
                '_update_layout_publish_table',
                '_update_entry_edit_date_format',
                '_rename_default_status_groups',
                '_centralize_captcha_settings',
                '_update_members_table',
                '_update_member_fields_table',
                '_update_member_groups_table',
                '_update_html_buttons',
                '_update_files_table',
                '_update_upload_prefs_table',
                '_update_upload_directories',
                '_drop_field_formatting_table',
                '_update_sites_table',
                '_remove_referrer_module_artifacts',
                '_update_channels_table',
                '_update_channel_titles_table',
                '_install_required_modules',
                '_export_mailing_lists',
                '_remove_mailing_list_module_artifacts',
                '_remove_cp_theme_config',
                '_remove_show_button_cluster_column',
                '_add_cp_homepage_columns',
                '_remove_path_configs',
                '_install_plugins',
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * Migrate the database information from database.php to config.php
     *
     * THIS MUST BE THE FIRST UPDATE
     *
     * @return void
     */
    private function _move_database_information()
    {
        $db_config_path = SYSPATH . '/user/config/database.php';
        if (is_file($db_config_path)) {
            require $db_config_path;
            ee()->config->_update_dbconfig($db[$active_group]);

            if (is_writeable($db_config_path)) {
                unlink($db_config_path);
            }
        } elseif (($db_config = ee()->config->item('database'))
            && empty($db_config)) {
            throw new \Exception(lang('database_no_data'));
        }
    }

    /**
     * Ensure filepicker and comment modules are installed
     */
    private function _install_required_modules()
    {
        if (!isset(ee()->addons)) {
            ee()->load->library('addons');
        }

        $installed_modules = ee()->db->select('module_name')->get('modules');
        $required_modules = array('channel', 'comment', 'member', 'stats', 'rte', 'file', 'filepicker', 'search');

        foreach ($installed_modules->result() as $installed_module) {
            $key = array_search(
                strtolower($installed_module->module_name),
                $required_modules
            );

            if ($key !== false) {
                unset($required_modules[$key]);
            }
        }

        ee()->addons->install_modules($required_modules);
    }

    /**
     * Removes 3 columns and adds 1 column to the email_cache table
     *
     * @return void
     */
    private function _update_email_cache_table()
    {
        ee()->smartforge->drop_column('email_cache', 'mailinglist');
        ee()->smartforge->drop_column('email_cache', 'priority');

        ee()->smartforge->add_column(
            'email_cache',
            array(
                'attachments' => array(
                    'type' => 'mediumtext',
                    'null' => true
                )
            )
        );
    }

    /**
     * Removes the upload_loc column from the upload_no_access table.
     *
     * @return void
     */
    private function _update_upload_no_access_table()
    {
        ee()->smartforge->drop_column('upload_no_access', 'upload_loc');
    }

    /**
     * Previously, Comment module settings were stored in config.php. Since the
     * Comment module is more integrated like Channel, let's take the settings
     * out of there and put them in the sites table because it's a better place
     * for them and they can be separated by site.
     *
     * @return void
     */
    private function _insert_comment_settings_into_db()
    {
        $comment_edit_time_limit = ee()->config->item('comment_edit_time_limit');

        $settings = array(
            // This is a new config, default it to y if not set
            'enable_comments' => ee()->config->item('enable_comments') ?: 'y',
            // These next two default to n
            'comment_word_censoring' => (ee()->config->item('comment_word_censoring') == 'y') ? 'y' : 'n',
            'comment_moderation_override' => (ee()->config->item('comment_moderation_override') == 'y') ? 'y' : 'n',
            // Default this to 0
            'comment_edit_time_limit' => ($comment_edit_time_limit && ctype_digit($comment_edit_time_limit))
                ? $comment_edit_time_limit : 0
        );

        ee()->config->update_site_prefs($settings, 'all');
        ee()->config->_update_config(array(), $settings);
    }

    /**
     * cookie_httponly and cookie_secure were only stored in config.php, let's
     * pluck them out into the database.
     *
     * @return void
     */
    private function _insert_cookie_settings_into_db()
    {
        $settings = array(
            // Default cookie_httponly to y
            'cookie_httponly' => ee()->config->item('cookie_httponly') ?: 'y',
            // Default cookie_secure to n
            'cookie_secure' => ee()->config->item('cookie_secure') ?: 'n',
        );

        ee()->config->update_site_prefs($settings, 'all');
        ee()->config->_update_config(array(), $settings);
    }

    /**
     * Creates the new plugins table and adds all the current plugins to the table
     *
     * @return void
     */
    private function _create_plugins_table()
    {
        ee()->dbforge->add_field(
            array(
                'plugin_id' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'null' => false,
                    'unsigned' => true,
                    'auto_increment' => true
                ),
                'plugin_name' => array(
                    'type' => 'varchar',
                    'constraint' => 50,
                    'null' => false
                ),
                'plugin_package' => array(
                    'type' => 'varchar',
                    'constraint' => 50,
                    'null' => false
                ),
                'plugin_version' => array(
                    'type' => 'varchar',
                    'constraint' => 12,
                    'null' => false
                ),
                'is_typography_related' => array(
                    'type' => 'char',
                    'constraint' => 1,
                    'default' => 'n',
                    'null' => false
                )
            )
        );
        ee()->dbforge->add_key('plugin_id', true);
        ee()->smartforge->create_table('plugins');

        ee()->load->model('addons_model');
        $plugins = ee()->addons_model->get_plugins();

        foreach ($plugins as $plugin => $info) {
            $typography = 'n';
            if (array_key_exists('pi_typography', $info) && $info['pi_typography'] == true) {
                $typography = 'y';
            }

            ee()->db->insert('plugins', array(
                'plugin_name' => $info['pi_name'],
                'plugin_package' => $plugin,
                'plugin_version' => $info['pi_version'],
                'is_typography_related' => $typography
            ));
        }
    }

    /**
     * Accessories are going away in 3.0. This removes their table.
     *
     * @return void
     */
    private function _remove_accessories_table()
    {
        ee()->smartforge->drop_table('accessories');
        ee()->smartforge->drop_column('member_groups', 'can_access_accessories');
    }

    /**
     * Adds 4 columns to the specialty_templates table
     *
     * @return void
     */
    private function _update_specialty_templates_table()
    {
        ee()->smartforge->add_column(
            'specialty_templates',
            array(
                'template_notes' => array(
                    'type' => 'text',
                    'null' => true
                ),
                'template_type' => array(
                    'type' => 'varchar',
                    'constraint' => 16,
                    'null' => true
                ),
                'template_subtype' => array(
                    'type' => 'varchar',
                    'constraint' => 16,
                    'null' => true
                ),
                'edit_date' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'null' => false,
                    'default' => 0
                ),
                'last_author_id' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'null' => false,
                    'unsigned' => true,
                    'default' => 0
                ),
            )
        );

        $system = array('offline_template', 'message_template');
        $email = array(
            'admin_notify_comment' => 'comments',
            'admin_notify_entry' => 'content',
            'admin_notify_mailinglist' => 'mailing_lists',
            'admin_notify_reg' => 'members',
            'comments_opened_notification' => 'comments',
            'comment_notification' => 'comments',
            'decline_member_validation' => 'members',
            'forgot_password_instructions' => 'members',
            'mailinglist_activation_instructions' => 'mailing_lists',
            'mbr_activation_instructions' => 'members',
            'pm_inbox_full' => 'private_messages',
            'private_message_notification' => 'private_messages',
            'validated_member_notify' => 'members',
            'admin_notify_forum_post' => 'forums',
            'forum_post_notification' => 'forums',
            'forum_moderation_notification' => 'forums',
            'forum_report_notification' => 'forums'
        );

        // Mark the email templates
        $templates = ee()->db->select('template_id, template_name, template_type, template_subtype, edit_date')
            ->get('specialty_templates')
            ->result_array();

        if (! empty($templates)) {
            foreach ($templates as $index => $template) {
                $templates[$index]['edit_date'] = time();

                if (in_array($template['template_name'], $system)) {
                    $templates[$index]['template_type'] = 'system';
                } elseif (in_array($template['template_name'], array_keys($email))) {
                    $templates[$index]['template_type'] = 'email';
                    $templates[$index]['template_subtype'] = $email[$template['template_name']];
                }
            }

            ee()->db->update_batch('specialty_templates', $templates, 'template_id');
        }
    }

    /**
     * We are removing the per-template "save to file" option. Instead it is
     * an all or nothing proposition based on the global preferences. So we are
     * removing the column from the database and resyncing the templates.
     *
     * @return void
     */
    private function _update_templates_save_as_files()
    {
        ee()->smartforge->drop_column('templates', 'save_template_file');

        $installer_config = ee()->config;

        ee()->load->model('template_model');

        $sites = ee()->db->select('site_id')
            ->get('sites')
            ->result_array();

        // Loop through the sites and save to file any templates that are only
        // in the database
        foreach ($sites as $site) {
            ee()->remove('config');
            ee()->set('config', new \MSM_Config());

            ee()->config->site_prefs('', $site['site_id']);

            if (ee()->config->item('save_tmpl_files') == 'y') {
                $templates = ee()->template_model->fetch_last_edit(
                    array('templates.site_id' => $site['site_id']),
                    true
                );

                foreach ($templates as $template) {
                    if (! $template->loaded_from_file) {
                        ee()->template_model->save_to_file($template);
                    }
                }
            }
        }

        ee()->remove('config');
        ee()->set('config', $installer_config);
    }

    /**
     * In 3.x Layouts now have names and the data structure for the field layout
     * has changed.
     *
     * @return void
     */
    private function _update_layout_publish_table()
    {
        if (! ee()->db->field_exists('member_group', 'layout_publish')) {
            return;
        }

        ee()->dbforge->add_field(
            array(
                'layout_id' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'null' => false,
                    'unsigned' => true
                ),
                'group_id' => array(
                    'type' => 'int',
                    'constraint' => 4,
                    'null' => false,
                    'unsigned' => true
                )
            )
        );
        ee()->dbforge->add_key(array('layout_id', 'group_id'), true);
        ee()->smartforge->create_table('layout_publish_member_groups');

        ee()->smartforge->add_column(
            'layout_publish',
            array(
                'layout_name' => array(
                    'type' => 'varchar',
                    'constraint' => 50,
                    'null' => false
                ),
            )
        );

        $layouts = ee()->db->select('layout_id, cat_group, member_group, layout_name, field_layout')
            ->from('layout_publish')
            ->join('channels', 'layout_publish.channel_id = channels.channel_id')
            ->get()
            ->result_array();

        if (! empty($layouts)) {
            foreach ($layouts as $index => $layout) {
                ee()->db->insert('layout_publish_member_groups', array(
                    'layout_id' => $layout['layout_id'],
                    'group_id' => $layout['member_group']
                ));

                $layouts[$index]['layout_name'] = 'Layout ' . $layout['layout_id'];

                $old_field_layout = unserialize($layout['field_layout']);
                $new_field_layout = array();

                foreach ($old_field_layout as $tab_id => $old_tab) {
                    $tab = array(
                        'id' => $tab_id,
                        'name' => isset($old_tab['_tab_label']) ? $old_tab['_tab_label'] : $tab_id,
                        'visible' => true,
                        'fields' => array()
                    );

                    unset($old_tab['_tab_label']);

                    foreach ($old_tab as $field => $info) {
                        if (is_numeric($field)) {
                            $field = 'field_id_' . $field;
                        } elseif ($field == 'category') {
                            foreach (explode('|', $layout['cat_group']) as $cat_group_id) {
                                $tab['fields'][] = array(
                                    'field' => 'categories[cat_group_id_' . $cat_group_id . ']',
                                    'visible' => $info['visible'],
                                    'collapsed' => $info['collapse']
                                );
                            }

                            continue;
                        } elseif ($field == 'new_channel') {
                            $field = 'channel_id';
                        } elseif ($field == 'author') {
                            $field = 'author_id';
                        } elseif ($field == 'options') {
                            $tab['fields'][] = array(
                                'field' => 'sticky',
                                'visible' => $info['visible'],
                                'collapsed' => $info['collapse']
                            );
                            $tab['fields'][] = array(
                                'field' => 'allow_comments',
                                'visible' => $info['visible'],
                                'collapsed' => $info['collapse']
                            );

                            continue;
                        }

                        $tab['fields'][] = array(
                            'field' => $field,
                            'visible' => $info['visible'],
                            'collapsed' => $info['collapse']
                        );
                    }

                    $new_field_layout[] = $tab;
                }

                $layouts[$index]['field_layout'] = serialize($new_field_layout);
                unset($layouts[$index]['cat_group']);
                unset($layouts[$index]['member_group']);
            }

            ee()->db->update_batch('layout_publish', $layouts, 'layout_id');
        }

        ee()->smartforge->drop_column('layout_publish', 'member_group');
    }

    /**
     * Transitioning away from our old MySQL Timestamp format to a Unix epoch
     * for the edit_date column of channel_titles
     *
     * @return void
     */
    private function _update_entry_edit_date_format()
    {
        $fields = ee()->db->field_data('channel_titles');
        foreach ($fields as $field) {
            if ($field->name == 'edit_date') {
                // Prior to 3.0.0 this column is a bigint if it is now an int
                // then this method has already run and we need to return
                if ($field->type == 'int') {
                    return;
                }

                break;
            }
        }

        ee()->db->query("SET time_zone = '+0:00';");
        ee()->db->query("UPDATE exp_channel_titles SET edit_date=UNIX_TIMESTAMP(edit_date);");
        ee()->db->query("SET time_zone = @@global.time_zone;");

        ee()->smartforge->modify_column('channel_titles', array(
            'edit_date' => array(
                'type' => 'int',
                'constraint' => 10,
            )
        ));
    }

    /**
     * Changes default name for status groups from Statuses to Default
     *
     * @return void
     */
    private function _rename_default_status_groups()
    {
        ee()->db->where('group_name', 'Statuses')
            ->set('group_name', 'Default')
            ->update('status_groups');
    }

    /**
     * Combines all CAPTCHA settings into one on/off switch; if a site has
     * CAPTCHA turned on for any form, we'll turn CAPTCHA on for the whole site
     *
     * @return void
     */
    private function _centralize_captcha_settings()
    {
        // Prevent this from running again
        if (! ee()->db->field_exists('comment_use_captcha', 'channels')
            || ! ee()->db->field_exists('require_captcha', 'channel_form_settings')) {
            return;
        }

        // First, let's see which sites have CAPTCHA turned on for Channel Forms
        // or comments, and mark those sites as needing CAPTCHA required
        $site_ids_query = ee()->db->select('channels.site_id')
            ->distinct()
            ->where('channels.comment_use_captcha', 'y')
            ->or_where('channel_form_settings.require_captcha', 'y')
            ->join(
                'channel_form_settings',
                'channels.channel_id = channel_form_settings.channel_id',
                'left'
            )
            ->get('channels')
            ->result();

        $sites_require_captcha = array();

        foreach ($site_ids_query as $site) {
            $sites_require_captcha[] = $site->site_id;
        }

        // Get all site IDs; this is for eventually comparing against the site
        // IDs we have collected to see which sites should have CAPTCHA turned
        // OFF, but we also need to loop through each site to see if any other
        // forms have CAPTCHA turned on
        $all_site_ids_query = ee()->db->select('site_id')
            ->get('sites')
            ->result();

        $all_site_ids = array();
        foreach ($all_site_ids_query as $site) {
            $all_site_ids[] = $site->site_id;
        }

        $msm_config = new \MSM_Config();

        foreach ($all_site_ids as $site_id) {
            // Skip sites we're already requiring CAPTCHA on
            if (in_array($site_id, $sites_require_captcha)) {
                continue;
            }

            $msm_config->site_prefs('', $site_id);

            if ($msm_config->item('use_membership_captcha') == 'y' or
                $msm_config->item('email_module_captchas') == 'y') {
                $sites_require_captcha[] = $site_id;
            }
        }

        // Diff all site IDs against the ones we're requiring CAPTCHA for
        // to get a list of sites we're NOT requiring CAPTCHA for
        $sites_do_not_require_captcha = array_diff($all_site_ids, $sites_require_captcha);

        // Add the new preferences
        // These sites require CAPTCHA
        if (! empty($sites_require_captcha)) {
            ee()->config->update_site_prefs(array('require_captcha' => 'y'), $sites_require_captcha);
        }

        // These sites do NOT require CAPTCHA
        if (! empty($sites_do_not_require_captcha)) {
            ee()->config->update_site_prefs(array('require_captcha' => 'n'), $sites_do_not_require_captcha);
        }

        // And finally, drop the old columns and remove old config items
        ee()->smartforge->drop_column('channels', 'comment_use_captcha');
        ee()->smartforge->drop_column('channel_form_settings', 'require_captcha');

        $msm_config->remove_config_item(array('use_membership_captcha', 'email_module_captchas'));
    }

    /**
     * Updates the member groups table
     *
     * @return void
     */
    private function _update_member_groups_table()
    {
        if (! ee()->db->field_exists('can_access_extensions', 'member_groups')) {
            return;
        }

        // Add footer permissions
        ee()->smartforge->add_column('member_groups', array(
            'can_access_footer_report_bug' => array(
                'type' => 'char',
                'constraint' => 1,
                'default' => 'n',
                'null' => false
            ),
            'can_access_footer_new_ticket' => array(
                'type' => 'char',
                'constraint' => 1,
                'default' => 'n',
                'null' => false
            ),
            'can_access_footer_user_guide' => array(
                'type' => 'char',
                'constraint' => 1,
                'default' => 'n',
                'null' => false
            )
        ));

        ee()->db->update(
            'member_groups',
            array(
                'can_access_footer_report_bug' => 'y',
                'can_access_footer_new_ticket' => 'y',
                'can_access_footer_user_guide' => 'y'
            ),
            array('can_access_cp' => 'y')
        );

        // Add new granular permissions columns
        $columns = array();
        $permissions = array(
            'can_create_entries',
            'can_edit_self_entries',
            'can_upload_new_files',
            'can_edit_files',
            'can_delete_files',
            'can_upload_new_toolsets',
            'can_edit_toolsets',
            'can_delete_toolsets',
            'can_create_upload_directories',
            'can_edit_upload_directories',
            'can_delete_upload_directories',
            'can_create_channels',
            'can_edit_channels',
            'can_delete_channels',
            'can_create_channel_fields',
            'can_edit_channel_fields',
            'can_delete_channel_fields',
            'can_create_statuses',
            'can_delete_statuses',
            'can_edit_statuses',
            'can_create_categories',
            'can_create_member_groups',
            'can_delete_member_groups',
            'can_edit_member_groups',
            'can_create_members',
            'can_edit_members',
            'can_create_new_templates',
            'can_edit_templates',
            'can_delete_templates',
            'can_create_template_groups',
            'can_edit_template_groups',
            'can_delete_template_groups',
            'can_create_template_partials',
            'can_edit_template_partials',
            'can_delete_template_partials',
            'can_create_template_variables',
            'can_delete_template_variables',
            'can_edit_template_variables',
            'can_access_security_settings',
            'can_access_translate',
            'can_access_import',
            'can_access_sql_manager'
        );

        foreach ($permissions as $permission) {
            $columns[$permission] = array(
                'type' => 'char',
                'constraint' => 1,
                'default' => 'n',
                'null' => false
            );
        }

        ee()->smartforge->add_column('member_groups', $columns);
        $groups = ee()->db->get('member_groups');

        // Update addons access
        foreach ($groups->result() as $group) {
            if ($group->can_access_extensions == 'y' ||
                $group->can_access_fieldtypes == 'y' ||
                $group->can_access_modules == 'y' ||
                $group->can_access_plugins == 'y'
            ) {
                ee()->db->update(
                    'member_groups',
                    array('can_access_addons' => 'y'),
                    array(
                        'group_id' => $group->group_id,
                        'site_id' => $group->site_id
                    )
                );
            }
        }

        if (ee()->db->field_exists('can_access_content', 'member_groups')) {
            ee()->db->update(
                'member_groups',
                array(
                    'can_upload_new_files' => 'y',
                    'can_edit_files' => 'y',
                    'can_delete_files' => 'y',
                    'can_upload_new_toolsets' => 'y',
                    'can_edit_toolsets' => 'y',
                    'can_delete_toolsets' => 'y',
                    'can_create_upload_directories' => 'y',
                    'can_edit_upload_directories' => 'y',
                    'can_delete_upload_directories' => 'y'
                ),
                array('can_access_content' => 'y')
            );
        }

        if (ee()->db->field_exists('can_admin_channels', 'member_groups')) {
            ee()->db->update(
                'member_groups',
                array(
                    'can_create_channels' => 'y',
                    'can_edit_channels' => 'y',
                    'can_delete_channels' => 'y',
                    'can_create_channel_fields' => 'y',
                    'can_edit_channel_fields' => 'y',
                    'can_delete_channel_fields' => 'y',
                    'can_create_statuses' => 'y',
                    'can_delete_statuses' => 'y',
                    'can_edit_statuses' => 'y',
                    'can_create_categories' => 'y'
                ),
                array('can_admin_channels' => 'y')
            );
        }

        if (ee()->db->field_exists('can_admin_mbr_groups', 'member_groups')) {
            ee()->db->update(
                'member_groups',
                array(
                    'can_create_member_groups' => 'y',
                    'can_delete_member_groups' => 'y',
                    'can_edit_member_groups' => 'y'
                ),
                array('can_admin_mbr_groups' => 'y')
            );
        }

        if (ee()->db->field_exists('can_admin_members', 'member_groups')) {
            ee()->db->update(
                'member_groups',
                array(
                    'can_create_members' => 'y',
                    'can_edit_members' => 'y'
                ),
                array('can_admin_members' => 'y')
            );
        }

        if (ee()->db->field_exists('can_admin_templates', 'member_groups')) {
            ee()->db->update(
                'member_groups',
                array(
                    'can_create_new_templates' => 'y',
                    'can_edit_templates' => 'y',
                    'can_delete_templates' => 'y',
                    'can_create_template_groups' => 'y',
                    'can_edit_template_groups' => 'y',
                    'can_delete_template_groups' => 'y',
                    'can_create_template_partials' => 'y',
                    'can_edit_template_partials' => 'y',
                    'can_delete_template_partials' => 'y',
                    'can_create_template_variables' => 'y',
                    'can_delete_template_variables' => 'y',
                    'can_edit_template_variables' => 'y'
                ),
                array('can_admin_templates' => 'y')
            );
        }

        if (ee()->db->field_exists('can_access_utilities', 'member_groups')) {
            ee()->db->update(
                'member_groups',
                array(
                    'can_access_translate' => 'y',
                    'can_access_import' => 'y'
                ),
                array('can_access_utilities' => 'y')
            );
        }

        if (ee()->db->field_exists('can_access_data', 'member_groups')) {
            ee()->db->update(
                'member_groups',
                array(
                    'can_access_sql_manager' => 'y'
                ),
                array('can_access_data' => 'y')
            );
        }

        // Rename can_admin_modules to can_admin_addons
        if (ee()->db->field_exists('can_admin_modules', 'member_groups')) {
            $can_admin_addons = array(
                'can_admin_modules' => array(
                    'name' => 'can_admin_addons',
                    'type' => 'CHAR',
                    'constraint' => 1,
                    'default' => 'n'
                ));

            ee()->dbforge->modify_column('member_groups', $can_admin_addons);
        }

        // Drop all superfluous permissions columns
        $old = array(
            'can_send_email',
            'can_access_publish',
            'can_access_edit',
            'can_access_extensions',
            'can_access_fieldtypes',
            'can_access_modules',
            'can_access_plugins',
            'can_access_content',
            'can_admin_members',
            'can_admin_templates',
            'can_access_admin',
            'can_access_content_prefs',
            'can_admin_upload_prefs',
            'can_access_tools'
        );

        foreach ($old as $permission) {
            ee()->smartforge->drop_column('member_groups', $permission);
        }
    }

    /**
     * Adds columns to the member fields table as needed
     *
     * @return void
     */
    private function _update_member_fields_table()
    {
        if (! ee()->db->field_exists('m_field_show_fmt', 'member_fields')) {
            ee()->smartforge->add_column(
                'member_fields',
                array(
                    'm_field_show_fmt' => array(
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'n',
                        'null' => false
                    )
                )
            );
        }
        if (! ee()->db->field_exists('m_field_text_direction', 'member_fields')) {
            ee()->smartforge->add_column(
                'member_fields',
                array(
                    'm_field_text_direction' => array(
                        'type' => 'char',
                        'constraint' => 3,
                        'default' => 'ltr',
                        'null' => false
                    )
                )
            );
        }
    }

    /**
     * Adds columns to the members table as needed
     *
     * @return void
     */
    private function _update_members_table()
    {
        if (! ee()->db->field_exists('bookmarklets', 'members')) {
            ee()->smartforge->add_column(
                'members',
                array(
                    'bookmarklets' => array(
                        'type' => 'TEXT',
                        'null' => true
                    )
                )
            );
        }

        if (! ee()->db->field_exists('rte_enabled', 'members')) {
            ee()->smartforge->add_column(
                'members',
                array(
                    'rte_enabled' => array(
                        'type' => 'CHAR(1)',
                        'null' => false,
                        'default' => 'y'
                    )
                )
            );
        }

        if (! ee()->db->field_exists('rte_toolset_id', 'members')) {
            ee()->smartforge->add_column(
                'members',
                array(
                    'rte_toolset_id' => array(
                        'type' => 'INT(10)',
                        'null' => false,
                        'default' => '0'
                    )
                )
            );
        }
    }

    /**
     * Adjusts the CSS class for some standard buttons
     *
     * @return void
     */
    private function _update_html_buttons()
    {
        $data = array(
            'b' => 'html-bold',
            'i' => 'html-italic',
            'ul' => 'html-order-list',
            'ol' => 'html-order-list',
            'a' => 'html-link',
            'img' => 'html-upload',
            'blockquote' => 'html-quote',
        );

        foreach ($data as $tag => $class) {
            ee()->db->where('tag_name', $tag)
                ->set('classname', $class)
                ->update('html_buttons');
        }
    }

    /**
     * Removes the rel_path column from the exp_files table
     *
     * @return void
     */
    private function _update_files_table()
    {
        ee()->smartforge->drop_column('files', 'rel_path');
    }

    /**
     * Adds columns to the upload prefs table as needed
     *
     * @return void
     */
    private function _update_upload_prefs_table()
    {
        if (! ee()->db->field_exists('module_id', 'upload_prefs')) {
            ee()->smartforge->add_column(
                'upload_prefs',
                array(
                    'module_id' => array(
                        'type' => 'INT(4)',
                        'null' => false,
                        'default' => 0
                    )
                )
            );
        }

        if (! ee()->db->field_exists('default_modal_view', 'upload_prefs')) {
            ee()->smartforge->add_column(
                'upload_prefs',
                array(
                    'default_modal_view' => array(
                        'type' => 'VARCHAR(5)',
                        'null' => false,
                        'default' => 'list',
                    )
                )
            );
        }
    }

    /**
     * Adds member image directories (avatars, photos, etc...) as upload
     * directories
     *
     * @access private
     * @return void
     */
    private function _update_upload_directories()
    {
        $module = ee('Model')->get('Module')->filter('module_name', 'Member')->first();

        // Bail if the member module isn't installed
        if (empty($module)) {
            return true;
        }

        // Install member upload directories
        $site_id = ee()->config->item('site_id');
        $member_directories = array();

        if (bool_config_item('enable_avatars')) {
            $avatar_uploads = ee('db')->from('upload_prefs')->where('name', 'Avatars')->count_all_results();

            if (empty($avatar_uploads)) {
                $member_directories['Avatars'] = array(
                    'server_path' => ee()->config->item('avatar_path'),
                    'url' => ee()->config->item('avatar_url'),
                    'allowed_types' => 'img',
                    'max_width' => ee()->config->item('avatar_max_width'),
                    'max_height' => ee()->config->item('avatar_max_height'),
                    'max_size' => ee()->config->item('avatar_max_kb'),
                );
            }
        }

        if (bool_config_item('enable_photos')) {
            $member_photo_uploads = ee('db')->from('upload_prefs')->where('name', 'Member Photos')->count_all_results();

            if (empty($member_photo_uploads)) {
                $member_directories['Member Photos'] = array(
                    'server_path' => ee()->config->item('photo_path'),
                    'url' => ee()->config->item('photo_url'),
                    'allowed_types' => 'img',
                    'max_width' => ee()->config->item('photo_max_width'),
                    'max_height' => ee()->config->item('photo_max_height'),
                    'max_size' => ee()->config->item('photo_max_kb'),
                );
            }
        }

        if (bool_config_item('allow_signatures')) {
            $signature_uploads = ee('db')->from('upload_prefs')->where('name', 'Signature Attachments')->count_all_results();

            if (empty($signature_uploads)) {
                $member_directories['Signature Attachments'] = array(
                    'server_path' => ee()->config->item('sig_img_path'),
                    'url' => ee()->config->item('sig_img_url'),
                    'allowed_types' => 'img',
                    'max_width' => ee()->config->item('sig_img_max_width'),
                    'max_height' => ee()->config->item('sig_img_max_height'),
                    'max_size' => ee()->config->item('sig_img_max_kb'),
                );
            }
        }

        if (bool_config_item('prv_msg_enabled')
            && bool_config_item('prv_msg_allow_attachments')) {
            $pm_uploads = ee('db')->from('upload_prefs')->where('name', 'PM Attachments')->count_all_results();

            if (empty($pm_uploads)) {
                $member_directories['PM Attachments'] = array(
                    'server_path' => ee()->config->item('prv_msg_upload_path'),
                    'url' => str_replace(
                        'avatars',
                        'pm_attachments',
                        ee()->config->item('avatar_url')
                    ),
                    'allowed_types' => 'img',
                    'max_size' => ee()->config->item('prv_msg_attach_maxsize')
                );
            }
        }

        foreach ($member_directories as $name => $dir) {
            $dir['site_id'] = $site_id;
            $dir['name'] = $name;
            $data['module_id'] = $module->getId(); // this is a terribly named column - should be called `hidden`
            ee()->db->insert('upload_prefs', $dir);
        }

        return true;
    }

    /**
     * Plugins that affect text formatting must now denote they do so,
     * ergo the field_formatting table is no longer needed
     */
    private function _drop_field_formatting_table()
    {
        ee()->smartforge->drop_table('field_formatting');
    }

    /**
     * Adds columns to the sites table as needed
     *
     * @return void
     */
    private function _update_sites_table()
    {
        if (! ee()->db->field_exists('site_pages', 'sites')) {
            ee()->smartforge->add_column(
                'sites',
                array(
                    'site_pages' => array(
                        'type' => 'TEXT',
                        'null' => false
                    )
                )
            );
        }
    }

    /**
     * The Referrer module has been removed, so we need to remove settings
     * related to the module from site config and the referrers table
     */
    private function _remove_referrer_module_artifacts()
    {
        $msm_config = new \MSM_Config();
        $msm_config->remove_config_item(array('log_referrers', 'max_referrers'));

        ee()->smartforge->drop_table('referrers');
    }

    private function _export_mailing_lists()
    {
        // Missing the mailing list tables? Get out of here.
        if (! ee()->db->table_exists('mailing_list')
            || ! ee()->db->table_exists('mailing_lists')) {
            return;
        }

        ee()->load->library('zip');
        $subscribers = array();
        $subscribers_query = ee()->db->select('list_id, email')
            ->get('mailing_list');

        // No subscribers at all? Move on.
        if ($subscribers_query->num_rows() <= 0) {
            return;
        }

        foreach ($subscribers_query->result() as $subscriber) {
            $subscribers[$subscriber->list_id][] = $subscriber->email;
        }

        $mailing_lists = ee()->db->select('list_id, list_name, list_title')
            ->get('mailing_lists');

        foreach ($mailing_lists->result() as $mailing_list) {
            // Empty mailing list? No need to export it.
            if (empty($subscribers[$mailing_list->list_id])) {
                continue;
            }

            $csv = ee('CSV');
            foreach ($subscribers[$mailing_list->list_id] as $subscriber) {
                $csv->addRow(array('email' => $subscriber));
            }
            ee()->zip->add_data(
                'mailing_list-' . $mailing_list->list_name . '.csv',
                (string) $csv
            );
        }

        ee()->zip->archive(SYSPATH . 'user/cache/mailing_list.zip');
    }

    /**
     * Cleans up database for mailing list module remnants
     */
    private function _remove_mailing_list_module_artifacts()
    {
        ee()->smartforge->drop_table('mailing_list');
        ee()->smartforge->drop_table('mailing_lists');
        ee()->smartforge->drop_table('mailing_list_queue');
        ee()->smartforge->drop_table('email_cache_ml');

        $msm_config = new \MSM_Config();
        $msm_config->remove_config_item(array(
            'mailinglist_enabled',
            'mailinglist_notify',
            'mailinglist_notify_emails'
        ));

        ee()->smartforge->drop_column('member_groups', 'can_email_mailinglist');
        ee()->smartforge->drop_column('member_groups', 'include_in_mailinglists');
        ee()->smartforge->drop_column('sites', 'site_mailinglist_preferences');

        ee()->db->where_in(
            'template_name',
            array('admin_notify_mailinglist', 'mailinglist_activation_instructions')
        )->delete('specialty_templates');

        ee()->db->where('module_name', 'Mailinglist')->delete('modules');
        ee()->db->where('class', 'Mailinglist')->delete('actions');
    }

    /**
     * Adds the column "title_field_label" to the channels tabel and sets it's
     * default to lang('title')
     */
    private function _update_channels_table()
    {
        if (! ee()->db->field_exists('title_field_label', 'channels')) {
            ee()->smartforge->add_column(
                'channels',
                array(
                    'title_field_label' => array(
                        'type' => 'CHAR(100)',
                        'null' => false,
                        'default' => 'Title'
                    )
                )
            );
        }

        if (! ee()->db->field_exists('extra_publish_controls', 'channels')) {
            ee()->smartforge->add_column(
                'channels',
                array(
                    'extra_publish_controls' => array(
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'n',
                        'null' => false
                    )
                )
            );
        }
    }

    /**
     * Updates the title and url_title columns to be a max of 200 chars long
     */
    private function _update_channel_titles_table()
    {
        ee()->smartforge->modify_column('channel_titles', array(
            'title' => array(
                'type' => 'char(200)',
            ),
            'url_title' => array(
                'type' => 'char(200)',
            )
        ));
    }

    /**
     * CP themeing is no longer supported, so remove the cp_theme config items
     */
    private function _remove_cp_theme_config()
    {
        $msm_config = new \MSM_Config();
        $msm_config->remove_config_item(array('cp_theme'));

        ee()->smartforge->drop_column('members', 'cp_theme');
    }

    /**
     * The show_button_cluster setting has been removed from channels, drop the column
     */
    private function _remove_show_button_cluster_column()
    {
        ee()->smartforge->drop_column('channels', 'show_button_cluster');
    }

    /**
     * Add columns to store CP homepage redirect information
     */
    private function _add_cp_homepage_columns()
    {
        ee()->smartforge->add_column(
            'member_groups',
            array(
                'cp_homepage' => array(
                    'type' => 'varchar(20)',
                    'null' => true,
                    'default' => null
                ),
                'cp_homepage_channel' => array(
                    'type' => 'int',
                    'unsigned' => true,
                    'null' => false,
                ),
                'cp_homepage_custom' => array(
                    'type' => 'varchar(100)',
                    'null' => true,
                    'default' => null
                )
            )
        );

        ee()->smartforge->add_column(
            'members',
            array(
                'cp_homepage' => array(
                    'type' => 'varchar(20)',
                    'null' => true,
                    'default' => null
                ),
                'cp_homepage_channel' => array(
                    'type' => 'varchar(255)',
                    'null' => true,
                    'default' => null
                ),
                'cp_homepage_custom' => array(
                    'type' => 'varchar(100)',
                    'null' => true,
                    'default' => null
                )
            )
        );
    }

    /**
     * Remove user configurable paths since user-servicable directory covers
     * them now
     * @return void
     */
    private function _remove_path_configs()
    {
        ee()->config->_update_config(array(), array(
            'addons_path' => '',
            'third_party_path' => '',
            'tmpl_file_basepath' => '',
            'cache_path' => '',
            'log_path' => ''
        ));
    }

    /**
     * Install all plugins found
     * @return  void
     */
    private function _install_plugins()
    {
        foreach (ee('Addon')->all() as $name => $info) {
            $info = ee('Addon')->get($name);

            // Check that it's a plugin ONLY
            if ($info->hasInstaller()
                || $info->hasControlPanel()
                || $info->hasModule()
                || $info->hasExtension()
                || $info->hasFieldtype()) {
                continue;
            }

            $model = ee('Model')->make('Plugin');
            $model->plugin_name = $info->getName();
            $model->plugin_package = $name;
            $model->plugin_version = $info->getVersion();
            $model->is_typography_related = ($info->get('plugin.typography')) ? 'y' : 'n';
            $model->save();
        }
    }
}
/* END CLASS */

// EOF
