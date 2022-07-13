<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Pro Select Categories variable type
 *
 * @package        pro_variables
 * @author         EEHarbor
 * @link           https://eeharbor.com/pro-variables
 * @copyright      Copyright (c) 2009-2022, EEHarbor
 */
class Pro_select_categories extends Pro_variables_type
{
    public $info = array(
        'name' => 'Select Categories'
    );

    public $default_settings = array(
        'multiple'        => 'y',
        'category_groups' => array(),
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

        $groups = ee('Model')
            ->get('CategoryGroup')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('group_name')
            ->all();

        $r[] = array(
            'title' => 'category_groups',
            //'desc' => 'category_groups_help',
            'fields' => array(
                $this->setting_name('category_groups') => array(
                    'type' => 'checkbox',
                    'wrap' => true,
                    'choices' => $groups->getDictionary('group_id', 'group_name'),
                    'value' => $this->settings('category_groups')
                )
            )
        );

        // -------------------------------------
        //  Build setting: multiple?
        // -------------------------------------

        $r[] = LVUI::setting('multiple', $this->setting_name('multiple'), $this->settings('multiple'));

        // -------------------------------------
        //  Build setting: separator
        // -------------------------------------

        $r[] = LVUI::setting('separator', $this->setting_name('separator'), $this->settings('separator'));

        // -------------------------------------
        //  Build setting: multi interface
        // -------------------------------------

        $r[] = LVUI::setting('interface', $this->setting_name('multi_interface'), $this->settings('multi_interface'));

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
        //  Get category groups
        // -------------------------------------

        if (! ($groups = $this->settings('category_groups'))) {
            return lang('no_category_groups_selected');
        }

        // -------------------------------------
        //  Get categories and generate choices
        // -------------------------------------

        $choices = array();

        if ($cats = LVUI::get_categories($groups)) {
            foreach ($cats as $group) {
                foreach ($group['categories'] as $cat) {
                    $choices[$cat['id']] = str_repeat('&nbsp;&nbsp;', $cat['depth'])
                        . htmlspecialchars($cat['name'], ENT_QUOTES);
                }
            }
        }

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
                'value' => LVUI::explode($this->settings('separator'), $var_data),
                'multiple' => true
            );

            return array(array(
                'type' => 'html',
                'content' => LVUI::view_field($this->settings('multi_interface'), $data)
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
            ? LVUI::implode($this->settings('separator'), $var_data)
            : $var_data;
    }

    // --------------------------------------------------------------------
}
