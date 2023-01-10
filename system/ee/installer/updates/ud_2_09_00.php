<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_2_9_0;

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
                '_update_template_routes_table',
                '_set_hidden_template_indicator',
                '_ensure_channel_combo_loader_action_integrity',
                '_convert_template_conditional_flag',
                '_warn_about_layout_contents'
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * Set the hidden_template_indicator config item to a period if the site has
     * no specific hidden template indicator.
     */
    private function _set_hidden_template_indicator()
    {
        if (ee()->config->item('hidden_template_indicator') === false) {
            ee()->config->_update_config(array(
                'hidden_template_indicator' => '.'
            ));
        }
    }

    /**
     * Add a column to the Template Routes table for storing the parse order
     *
     * @access private
     * @return void
     */
    private function _update_template_routes_table()
    {
        ee()->smartforge->add_column(
            'template_routes',
            array(
                'order' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => true
                )
            )
        );
    }

    /**
     * If this was a pre-2.7 install and never had Safecracker installed,
     * there could be a missing action for the Channel class. So let's
     * make sure it exists and add it if it doesn't.
     *
     * @access private
     * @return void
     **/
    private function _ensure_channel_combo_loader_action_integrity()
    {
        $row_data = array(
            'class' => 'Channel',
            'method' => 'combo_loader'
        );

        ee()->db->where($row_data);
        $count = ee()->db->count_all_results('actions');

        if ($count == 0) {
            ee()->db->insert('actions', $row_data);
        }
    }

    /**
     * Remove the protect_javascript config item and make it a per-template
     * setting.
     *
     * @access private
     * @return void
     **/
    private function _convert_template_conditional_flag()
    {
        ee()->update_notices->setVersion('2.9');

        $changes = false;

        // remove from config, but remember that they disabled it
        // for the template default.
        $old_config_value = ee()->config->item('protect_javascript');

        if ($old_config_value !== false) {
            ee()->config->_update_config(
                array(),
                array('protect_javascript' => '')
            );
        }

        // add a yes/no column, and flip the all to no by default
        ee()->smartforge->add_column(
            'templates',
            array(
                'protect_javascript' => array(
                    'type' => 'char',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 'n'
                )
            )
        );

        // loop through templates

        if (! defined('LD')) {
            define('LD', '{');
        }
        if (! defined('RD')) {
            define('RD', '}');
        }

        // We're gonna need this to be already loaded.
        ee()->remove('functions');
        require_once(APPPATH . 'libraries/Functions.php');
        ee()->set('functions', new \Installer_Functions());

        ee()->remove('extensions');
        require_once(APPPATH . 'libraries/Extensions.php');
        ee()->set('extensions', new \Installer_Extensions());

        ee()->remove('addons');
        require_once(APPPATH . 'libraries/Addons.php');
        ee()->set('addons', new \Installer_Addons());

        $installer_config = ee()->config;
        ee()->remove('config');
        ee()->set('config', new \MSM_Config());

        // We need to figure out which template to load.
        // Need to check the edit date.
        ee()->load->model('template_model');
        $templates = ee()->template_model->fetch_last_edit(array(), true);

        foreach ($templates as $template) {
            // only check in webpages and js
            if ($template->template_type != 'webpage' && $template->template_type == 'js') {
                continue;
            }

            // In webpages, we must have script tags to check
            if ($template->template_type == 'webpage' && strpos($template->template_data, '<script') === false) {
                continue;
            }

            // If there aren't any conditional tags, then we don't need to continue.
            if (strpos($template->template_data, LD . 'if') === false) {
                continue;
            }

            $has_conditional_in_scripts = false;
            $path = $template->get_group()->group_name . '/' . $template->template_name;

            $regex = '/([()]|do|with|)\s*\{if\b/is';

            if ($template->template_type == 'js') {
                if (preg_match($regex, $template->template_data)) {
                    $has_conditional_in_scripts = true;
                }
            } elseif ($template->template_type == 'template_data') {
                if (preg_match('/<script\s+(.*?)<\/script/is', $template->template_data, $matches)) {
                    foreach ($matches as $match) {
                        if (preg_match($regex, $match[0])) {
                            $has_conditional_in_scripts = true;
                        }
                    }
                }
            }

            // if there are conditionals in a js template and they did
            // not disable the protection previously, we're going to flip
            // it for them.
            // regular templates, we will not change automatically since
            // they are more likely to contain simple conditionals
            if ($has_conditional_in_scripts && $template->template_type == 'js' && $old_config_value !== 'n') {
                $template->protect_javascript = 'y';

                ee()->update_notices->item('Automatically protecting JavaScript conditionals in ' . $path);
                $changes = true;
            } elseif ($has_conditional_in_scripts && $template->template_type == 'template_data') {
                ee()->update_notices->item('Conditionals found in JavaScript in ' . $path . '.');
                $changes = true;
            }

            // save the template
            // if saving to file, save the file
            if ($template->loaded_from_file) {
                ee()->template_model->save_to_file($template);
            } else {
                ee()->template_model->save_to_database($template);
            }
        }

        if ($changes) {
            ee()->update_notices->item('Done. Please double check these templates for expected output.');
        }

        ee()->remove('config');
        ee()->set('config', $installer_config);
    }

    /**
     * We are strictly enforcing the reserved variable `layout:contents`,
     * so we need to loop though the templates and warn about any
     * instances where it is being overwritten
     *
     * @access private
     * @return void
     **/
    private function _warn_about_layout_contents()
    {
        ee()->update_notices->setVersion('2.9');

        ee()->remove('template');
        require_once(SYSPATH . 'ee/installer/libraries/Template.php');
        ee()->set('template', new \Installer_Template());

        $installer_config = ee()->config;
        ee()->remove('config');
        ee()->set('config', new \MSM_Config());

        $templates = ee()->template_model->fetch_last_edit(array(), true);

        $warnings = array();
        foreach ($templates as $template) {
            // This catches any {layout=} and {layout:set} tags
            if (preg_match_all('/(' . LD . 'layout\s*)(.*?)' . RD . '/s', $template->template_data, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $params = ee('Variables/Parser')->parseTagParameters($match[2]);

                    // If any of the parameters indicate it's trying to
                    // set the contents variable, log the template name
                    if (isset($params['contents']) or
                        (isset($params['name']) && $params['name'] == 'contents')) {
                        $warnings[] = $template->get_group()->group_name . '/' . $template->template_name;
                    }
                }
            }
        }

        // Output a list of templates that are setting layout:contents
        if (! empty($warnings)) {
            ee()->update_notices->item('The following templates are manually setting the {layout:contents} variable, please use a different variable name.<br>' . implode('<br>', $warnings));
        }

        ee()->remove('config');
        ee()->set('config', $installer_config);
    }
}
/* END CLASS */

// EOF
