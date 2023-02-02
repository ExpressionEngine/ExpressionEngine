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
 * Pro Select Entries variable type
 */
class Pro_select_entries extends Pro_variables_type
{
    public $info = array(
        'name' => 'Select Entries'
    );

    public $default_settings = array(
        'show_future'     => 'y',
        'show_expired'    => 'n',
        'channels'        => array(),
        'categories'      => array(),
        'statuses'        => array(),
        'limit'           => '0',
        'orderby'         => 'title',
        'sort'            => 'asc',
        'multiple'        => 'y',
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
        //  Build setting: Future & Expired entries
        // -------------------------------------

        $r[] = array(
            'title' => 'show_future',
            'fields' => array(
                $this->setting_name('show_future') => array(
                    'type'  => 'yes_no',
                    'value' => $this->settings('show_future') ?: 'n'
                )
            )
        );

        $r[] = array(
            'title' => 'show_expired',
            'fields' => array(
                $this->setting_name('show_expired') => array(
                    'type'  => 'yes_no',
                    'value' => $this->settings('show_expired') ?: 'n'
                )
            )
        );

        // -------------------------------------
        //  Build setting: channels
        // -------------------------------------

        $channels = ee('Model')
            ->get('Channel')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('channel_title')
            ->all();

        $r[] = array(
            'title' => 'channels',
            //'desc' => 'channel_ids_help',
            'fields' => array(
                $this->setting_name('channels') => array(
                    'type' => 'checkbox',
                    'wrap' => true,
                    'choices' => $channels->getDictionary('channel_id', 'channel_title'),
                    'value' => $this->settings('channels')
                )
            )
        );

        // -------------------------------------
        //  Build setting: categories
        // -------------------------------------

        if ($categories = PVUI::get_categories()) {
            // Init category arrays
            $choices = array('' => lang('select_any'));

            // Loop through groups and create category trees for each of those
            foreach ($categories as $group) {
                foreach ($group['categories'] as $cat) {
                    $choices[$group['name']][$cat['id']] = str_repeat('&nbsp;&nbsp;', $cat['depth']) . $cat['name'];
                }
            }

            $r[] = array(
                'title' => 'categories',
                'fields' => array(array(
                    'type' => 'html',
                    'content' => PVUI::view_field('select', array(
                        'name' => $this->setting_name('categories'),
                        'choices' => $choices,
                        'value' => $this->settings('categories'),
                        'multiple' => true
                    ))
                ))
            );
        }

        // -------------------------------------
        //  Build setting: statuses
        // -------------------------------------

        // Initiate status choices
        $choices = array('' => lang('select_any'));

        // Get statuses from DB
        $statuses = ee('Model')
            ->get('Status')
            ->order('Status.status_order')
            ->all();

        // Add statuses to choices
        foreach ($statuses as $status) {
            $choices[$status->status] = $status->status;
        }

        // Add to form
        $r[] = array(
            'title' => 'statuses',
            'fields' => array(array(
                'type' => 'html',
                'content' => PVUI::view_field('select', array(
                    'name' => $this->setting_name('statuses'),
                    'choices' => $choices,
                    'value' => $this->settings('statuses'),
                    'multiple' => true
                ))
            ))
        );

        // -------------------------------------
        //  Build setting: orderby & sort
        // -------------------------------------

        $r[] = array(
            'title' => 'orderby',
            'fields' => array(
                $this->setting_name('orderby') => array(
                    'type' => 'select',
                    'value' => $this->settings('orderby'),
                    'choices' => array(
                        'title'      => lang('title'),
                        'entry_date' => lang('entry_date')
                    )
                ),
                $this->setting_name('sort') => array(
                    'type' => 'select',
                    'value' => $this->settings('sort'),
                    'choices' => array(
                        'asc'  => lang('order_asc'),
                        'desc' => lang('order_desc')
                    )
                )
            )
        );

        // -------------------------------------
        //  Build setting: limit
        // -------------------------------------

        $r[] = array(
            'title' => 'limit',
            'fields' => array(
                $this->setting_name('limit') => array(
                    'type' => 'select',
                    'value' => $this->settings('limit'),
                    'choices' => array(
                        '0'    => lang('all'),
                        '25'   => '25',
                        '50'   => '50',
                        '100'  => '100',
                        '250'  => '250',
                        '500'  => '500',
                        '1000' => '1000'
                    )
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
        //  Prep options
        // -------------------------------------

        $now = ee()->localize->now;

        // -------------------------------------
        //  Get entries
        // -------------------------------------

        $builder = ee('Model')
            ->get('ChannelEntry')
            ->fields('entry_id', 'title');

        // Filter out future entries
        if ($this->settings('show_future') != 'y') {
            $builder->filter('entry_date', '<=', $now);
        }

        // Filter out expired entries
        if ($this->settings('show_expired') != 'y') {
            $builder
                ->filterGroup()
                ->filter('expiration_date', 0)
                ->orFilter('expiration_date', '>', $now)
                ->endFilterGroup();
        }

        // Filter by channel
        if ($channels = array_filter($this->settings('channels'))) {
            $builder->filter('channel_id', 'IN', $channels);
        }

        // Filter by category
        if ($categories = array_filter($this->settings('categories'))) {
            $builder->with('Categories');
            $builder->filter('Categories.cat_id', 'IN', $categories);
        }

        // Filter by status
        if ($statuses = array_filter($this->settings('statuses'))) {
            $builder->filter('status', 'IN', $statuses);
        }

        // Order by custom order
        $builder->order($this->settings('orderby'), $this->settings('sort'));

        // Limit entries
        if ($limit = $this->settings('limit')) {
            $builder->limit($limit);
        }

        $query = $builder->all();
        $choices = $query->getDictionary('entry_id', 'title');
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
