<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Pro Select Channels variable type
 */
class Pro_select_channels extends Pro_variables_type
{
    public $info = array(
        'name' => 'Select Channels'
    );

    public $default_settings = array(
        'multiple'        => 'y',
        'channel_ids'     => array(),
        'separator'       => 'pipe',
        'multi_interface' => 'select'
    );

    // --------------------------------------------------------------------

    /**
     * Display settings sub-form for this variable type
     */
    public function display_settings()
    {
        // -------------------------------------
        //  Init return value
        // -------------------------------------

        $r = array();

        // -------------------------------------
        //  Build setting: category groups
        //  First, get all groups for this site
        // -------------------------------------

        $channels = ee('Model')
            ->get('Channel')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('channel_title')
            ->all();

        $r[] = array(
            'title' => 'channel_ids',
            //'desc' => 'channel_ids_help',
            'fields' => array(
                $this->setting_name('channel_ids') => array(
                    'type' => 'checkbox',
                    'wrap' => true,
                    'choices' => $channels->getDictionary('channel_id', 'channel_title'),
                    'value' => $this->settings('channel_ids')
                )
            )
        );

        // -------------------------------------
        //  Build setting: multiple?
        // -------------------------------------

        $r[] = PVUI::setting('multiple', $this->setting_name('multiple'), $this->settings('multiple'));

        // -------------------------------------
        //  Build setting: separator
        // -------------------------------------

        $r[] = PVUI::setting('separator', $this->setting_name('separator'), $this->settings('separator'));

        // -------------------------------------
        //  Build setting: multi interface
        // -------------------------------------

        $r[] = PVUI::setting('interface', $this->setting_name('multi_interface'), $this->settings('multi_interface'));

        // -------------------------------------
        //  Return output
        // -------------------------------------

        return $this->settings_form($r);
    }

    // --------------------------------------------------------------------

    /**
     * Display input field for regular user
     */
    public function display_field($var_data)
    {
        // -------------------------------------
        //  Get channel
        // -------------------------------------

        $channels = ee('Model')
            ->get('Channel')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('channel_title');

        // -------------------------------------
        //  Filter by channel ids
        // -------------------------------------

        if ($ids = $this->settings('channel_ids')) {
            $channels->filter('channel_id', 'IN', $ids);
        }

        $channels = $channels->all();
        $choices = $channels->getDictionary('channel_name', 'channel_title');
        $choices = array_map('htmlspecialchars', $choices);

        // -------------------------------------
        //  Single choice
        // -------------------------------------

        if ($this->settings('multiple') != 'y') {
            return array(
                $this->input_name() => array(
                    'type' => 'select',
                    'choices' => array('' => '--') + $choices,
                    'value' => $var_data
                )
            );
        } else {
            //  Multiple choice
            $data = array(
                'name' => $this->input_name(),
                'choices' => $choices,
                'value' => PVUI::explode($this->settings('separator'), $var_data),
                'multiple' => true
            );

            return array(array(
                'type' => 'html',
                'content' => PVUI::view_field($this->settings('multi_interface'), $data)
            ));
        }
    }

    // --------------------------------------------------------------------

    /**
     * Prep variable data for saving
     */
    public function save($var_data)
    {
        return is_array($var_data)
            ? PVUI::implode($this->settings('separator'), $var_data)
            : $var_data;
    }

    // --------------------------------------------------------------------
}
