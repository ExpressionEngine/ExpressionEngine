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
 * Pro Variables Fieldtype class
 *
 * Models, Libraries, and Helpers should be loaded by the extension,
 * which is called on session_end
 */
class Pro_variables_ft extends EE_Fieldtype
{
    // --------------------------------------------------------------------
    //  PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Info array
     *
     * @access     public
     * @var        array
     */
    public $info = array(
        'name' => 'Pro Variables',
        'version' => PRO_VAR_VERSION
    );

    /**
     * Does fieldtype work in var pair
     *
     * @access     public
     * @var        bool
     */
    public $has_array_data = true;

    public $can_be_cloned = true;

    public $entry_manager_compatible = true;

    // --------------------------------------------------------------------

    /**
     * Default settings
     *
     * @access     private
     * @var        array
     */
    private $default_settings = array(
        'lv_ft_multiple' => false,
        'lv_ft_groups'   => array()
    );

    /**
     * Package
     */
    private $package = 'pro_variables';

    // --------------------------------------------------------------------
    //  METHODS
    // --------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
        ee()->load->helper($this->package);
        ee()->load->model(array('pro_variables_group_model', 'pro_variables_variable_model'));
    }

    /**
     * Display field settings
     *
     * @param   array   field settings
     * @return  string
     */
    public function display_settings($settings = array())
    {
        return $this->_display_settings($settings);
    }

    /**
     * Return array with html for setting forms
     *
     * @param   array   field settings
     * @return  string
     */
    private function _display_settings($settings = array())
    {
        // -------------------------------------
        //  Load language file
        // -------------------------------------

        ee()->lang->loadfile($this->package);

        // -------------------------------------
        //  Make sure we have all settings
        // -------------------------------------

        foreach ($this->default_settings as $key => $val) {
            if (! array_key_exists($key, $settings)) {
                $settings[$key] = $val;
            }
        }

        // -------------------------------------
        //  Get variable groups
        // -------------------------------------

        $groups = ee()->pro_variables_group_model->get_by_site();
        $groups = pro_flatten_results($groups, 'group_label', 'group_id');

        // Add Ungrouped items to the bottom
        $groups += array('0' => lang('ungrouped'));

        // -------------------------------------
        //  Build per-setting HTML
        // -------------------------------------

        $output = array($this->package => array(
            'group' => $this->package,
            'label' => 'pro_variables_module_name',
            'settings' => array(
                array(
                    'title' => 'lv_ft_multiple',
                    'fields' => array(
                        'lv_ft_multiple' => array(
                            'type' => 'yes_no',
                            'value' => $settings['lv_ft_multiple'] ?: 'n'
                        )
                    )
                ),
                array(
                    'title' => 'lv_ft_groups',
                    'fields' => array(
                        'lv_ft_groups' => array(
                            'type' => 'checkbox',
                            'choices' => $groups,
                            'value' => $settings['lv_ft_groups'],
                            'wrap' => true
                        )
                    )
                )
            )
        ));

        // Return the settings
        return $output;
    }

    // --------------------------------------------------------------------

    /**
     * Save field settings
     *
     * @access  public
     * @return  array
     */
    public function save_settings($data)
    {
        $settings = array();

        foreach ($this->default_settings as $key => $val) {
            $settings[$key] = ee('Request')->post($key, $val);
        }

        return $settings;
    }

    // --------------------------------------------------------------------

    /**
     * Native display
     */
    public function display_field($data)
    {
        return $this->_display_field($data);
    }

    /**
     * Display field in publish form or Matrix cell
     *
     * @param   string  Current value for field
     * @return  string  HTML containing input field
     */
    private function _display_field($value = '', $cell = false)
    {
        // -------------------------------------
        //  What's the field name?
        // -------------------------------------

        $field_name = $cell ? $this->cell_name : $this->field_name;

        // -------------------------------------
        //  We need groups!
        // -------------------------------------

        if (empty($this->settings['lv_ft_groups'])) {
            return lang('no_variable_group_selected');
        }

        // -------------------------------------
        //  Get all variable groups
        // -------------------------------------

        if (! ($groups = pro_get_cache($this->package, 'groups'))) {
            $groups = ee()->pro_variables_group_model->get_by_site();
            $groups = pro_flatten_results($groups, 'group_label', 'group_id');
            $groups += array('0' => lang('ungrouped'));

            pro_set_cache($this->package, 'groups', $groups);
        }

        // -------------------------------------
        //  Get variables from groups
        // -------------------------------------

        $vars = ee()->pro_variables_variable_model->get_ft($this->settings['lv_ft_groups']);

        $choices = array();

        // Loop through found vars and group by group label
        foreach ($vars as $row) {
            $group = array_key_exists($row['group_id'], $groups)
                ? $groups[$row['group_id']]
                : $groups['0'];

            $choices[$group][$row['variable_name']] =
                $row['variable_label'] ?: $row['variable_name'];
        }

        // Clean up
        unset($vars);

        // Reduce to 1 dimensional array
        if (count($choices) === 1) {
            $choices = current($choices);
        } else {
            // Again, 1 dimentional, so we can use EE3's options
            $flat = array();

            foreach ($choices as $val) {
                $flat = array_merge($flat, $val);
            }

            $choices = $flat;
        }

        // -------------------------------------
        //  Multiple?
        // -------------------------------------

        if (@$this->settings['lv_ft_multiple'] == 'y') {
            if (! is_array($value)) {
                $value = explode("\n", (string) $value);
            }
            $field = array(
                'type'    => 'checkbox',
                'choices' => $choices,
                'value'   => $value,
                'wrap'    => true
            );
        } else {
            $field = array(
                'type'    => 'select',
                'choices' => array('' => '--') + $choices,
                'value'   => $value
            );
        }

        // -------------------------------------
        //  Return a rendered field view
        // -------------------------------------

        return ee('View')
            ->make('ee:_shared/form/field')
            ->render(array(
                'field_name' => $field_name,
                'field'      => $field,
                'grid'       => false
            ));
    }

    // --------------------------------------------------------------------

    /**
     * Return prepped field data to save
     *
     * @param   mixed   Posted data
     * @return  string  Data to save
     */
    public function save($data = '')
    {
        if (is_array($data)) {
            $data = implode("\n", $data);
        }

        return $data;
    }

    // --------------------------------------------------------------------

    /**
     * Display tag in template
     *
     * @param   string  Current value for field
     * @param   array   Tag parameters
     * @param   bool
     * @return  string
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        // -------------------------------------
        //  Init output
        // -------------------------------------

        $it = '';

        // -------------------------------------
        //  Build output depending on tagdata
        // -------------------------------------

        if ($tagdata) {
            foreach (explode("\n", $data) as $var) {
                $it .= str_replace(LD . 'var' . RD, $var, $tagdata);
            }
        } else {
            $it = $data;
        }

        // Please
        return $it;
    }

    /**
     * Display {var_name:var}
     *
     * @param   string  Current value for field
     * @param   array   Tag parameters
     * @return  string
     */
    public function replace_var($data, $params)
    {
        return LD . $data . RD;
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        return $this->replace_tag($data);
    }

    // --------------------------------------------------------------------
}
// END Pro_variables_ft class
