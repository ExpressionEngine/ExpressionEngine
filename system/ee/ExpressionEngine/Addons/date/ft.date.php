<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Library\Date\DateTrait;

/**
 * Date Fieldtype
 */
class Date_ft extends EE_Fieldtype
{
    use DateTrait;

    public $info = array(
        'name' => 'Date',
        'version' => '1.0.0'
    );

    public $has_array_data = false;

    public $size = 'small';

    public $supportedEvaluationRules = ['isEmpty', 'isNotEmpty'];

    public $defaultEvaluationRule = 'isNotEmpty';

    /**
     * Parses the date input, first with the configured date format (as used
     * by the datepicker). If that fails it will try again with a fuzzier
     * conversion, which allows things like "2 weeks".
     *
     * @param string $date A date string for parsing
     * @return mixed Will return a UNIX timestamp or FALSE
     */
    private function _parse_date($date)
    {
        $include_time = true;
        if (isset($this->settings['show_time']) && get_bool_from_string($this->settings['show_time']) === false) {
            $include_time = false;
        }

        // First we try with the configured date format
        $timestamp = ee()->localize->string_to_timestamp($date, true, ee()->localize->get_date_format(false, $include_time));

        // If the date format didn't work, try something more fuzzy
        if ($timestamp === false) {
            $timestamp = ee()->localize->string_to_timestamp($date);
        }

        return $timestamp ?: null;
    }

    public function save($data)
    {
        if (! is_numeric($data)) {
            $data = $this->_parse_date($data);
        }

        return $data;
    }

    public function grid_save($data)
    {
        if (! is_numeric($data)) {
            $data = $this->_parse_date($data);
        }

        if (! empty($data) && $this->settings['localize'] !== true) {
            $data = array($data, ee()->session->userdata('timezone', ee()->config->item('default_site_timezone')));
        }

        return $data;
    }

    /**
     * Validate Field
     *
     * @param  string
     * @return mixed
     */
    public function validate($data)
    {
        if (! is_numeric($data) && ! empty($data) && trim($data)) {
            $data = $this->_parse_date($data);
        }

        if (
            $data === false or is_null($data)
            or (is_numeric($data) && ($data > 2147483647 or $data < -2147483647))
        ) {
            return lang('invalid_date');
        }

        return array('value' => $data);
    }

    /**
     * Display Field
     *
     * @param  array
     */
    public function display_field($data)
    {
        $field_data = $data;

        ee()->lang->loadfile('content');

        $special = array('entry_date', 'expiration_date', 'comment_expiration_date');

        if (! is_numeric($field_data)) {
            ee()->load->helper('custom_field_helper');

            $data = decode_multi_field($field_data);

            // Grid field stores timestamp and timezone in one field
            if (! empty($data) && isset($data[1])) {
                $field_data = $data[0];
                $this->settings['field_dt'] = $data[1];
            }
        }

        $date_field = $this->field_name;
        $date_local = str_replace('field_id_' . $this->field_id, 'field_dt_' . $this->field_id, $date_field);

        $date = ee()->localize->now;
        $custom_date = '';
        $localize = true;

        $include_time = true;
        if (isset($this->settings['show_time']) && get_bool_from_string($this->settings['show_time']) === false) {
            $include_time = false;
        }

        if (
            (isset($_POST[$date_field]) && ! is_numeric($_POST[$date_field]))
            or (! is_numeric($field_data) && ! empty($field_data))
        ) {
            // probably had a validation error so repopulate as-is
            $custom_date = isset($_POST[$date_field]) ? $_POST[$date_field] : $field_data;
        } else {
            // primarily handles default expiration, comment expiration, etc.
            // in this context 'offset' is unrelated to localization.
            $offset = $this->get_setting('default_offset', 0);

            if (! $field_data && ! $offset) {
                $field_data = $date;

                if ($this->get_setting('always_show_date')) {
                    $custom_date = ee()->localize->human_time(null, true, false, $include_time);
                }
            } else {
                // Everything else
                $field_dt = $this->get_setting('field_dt');
                if (! empty($field_dt)) {
                    $localize = $field_dt;
                }

                if (! $field_data && $offset) {
                    $field_data = $date + $offset;
                }

                // doing it in here so that if we don't have field_data
                // the field doesn't get populated, but the calendar still
                // shows the correct default.
                if ($field_data) {
                    $custom_date = ee()->localize->human_time($field_data, $localize, false, $include_time);
                }
            }

            $date = $field_data;
        }

        $include_seconds = ee()->session->userdata('include_seconds', ee()->config->item('include_seconds'));
        $show_time_on_fe = isset($this->settings['localization']) ? get_bool_from_string($this->settings['show_time']) : false;

        ee()->javascript->set_global('date.date_format', ee()->localize->get_date_format(false, $include_time));
        ee()->javascript->set_global('date.include_seconds', $include_seconds);
        ee()->javascript->set_global('date.time_format', ee()->session->userdata('time_format', ee()->config->item('time_format')));

        $this->addDatePickerScript();

        $localized = (! isset($_POST[$date_local])) ? (($localize === true) ? 'y' : 'n') : ee()->input->post($date_local, true);
        $show_localize_options = 'ask';
        if (isset($this->settings['localization']) && $this->settings['localization'] == 'fixed') {
            $show_localize_options = 'fixed';
        } elseif (isset($this->settings['localization']) && $this->settings['localization'] == 'localized') {
            $show_localize_options = 'localized';
        }

        return ee('View')->make('date:publish')->render(array(
            'has_localize_option' => (! in_array($this->field_name, $special) && $this->content_type() != 'grid'),
            'show_localize_options' => $show_localize_options,
            'field_name' => $this->field_name,
            'value' => $custom_date,
            'localize_option_name' => $date_local,
            'localized' => $localized,
            'date_format' => ee()->localize->get_date_format(false, $include_time),
            'disabled' => $this->get_setting('field_disabled'),
            'include_time' => $include_time,
        ));
    }

    public function pre_process($data)
    {
        return $data;
    }

    public function replace_tag($date, $params = array(), $tagdata = false)
    {
        $localize = true;
        if (isset($this->row['field_dt_' . $this->name]) and $this->row['field_dt_' . $this->name] != '') {
            $localize = $this->row['field_dt_' . $this->name];
        }

        return ee()->TMPL->process_date($date, $params, false, $localize);
    }

    public function replace_relative($date, $params = array(), $tagdata = false)
    {
        $localize = true;
        if (isset($this->row['field_dt_' . $this->name]) and $this->row['field_dt_' . $this->name] != '') {
            $localize = $this->row['field_dt_' . $this->name];
        }

        return ee()->TMPL->process_date($date, $params, true, $localize);
    }

    public function grid_replace_tag($data, $params = array(), $tagdata = false)
    {
        ee()->load->helper('custom_field_helper');
        $date = decode_multi_field($data);

        if (! isset($date[0])) {
            return '';
        }

        if (isset($params['format'])) {
            $localize = true;

            if ($this->settings['localize'] !== true && isset($date[1])) {
                $localize = $date[1];
            }

            return ee()->TMPL->process_date($date[0], $params, false, $localize);
        }

        return $date[0];
    }

    /**
     * Display Settings
     *
     * @param  array  $data  Field Settings
     * @return array  Field options
     */
    public function display_settings($data)
    {
        ee()->lang->loadfile('fieldtypes');

        $settings = array(
            array(
                'title' => 'date_localization',
                'desc' => 'date_localization_desc',
                'fields' => array(
                    'localization' => array(
                        'type' => 'radio',
                        'choices' => array(
                            'localized' => lang('always_localized'),
                            'fixed' => lang('always_fixed'),
                            'ask' => lang('ask_each_time')
                        ),
                        'value' => (isset($data['localization'])) ? $data['localization'] : 'ask',
                    )
                )
            ),
            array(
                'title' => 'show_time',
                'desc' => 'show_time_desc',
                'fields' => array(
                    'show_time' => array(
                        'type' => 'yes_no',
                        'value' => isset($data['show_time']) ? $data['show_time'] : true,
                    )
                )
            )
        );

        return array('field_options_date' => array(
            'label' => 'field_options',
            'group' => 'date',
            'settings' => $settings
        ));
    }

    /**
     * Save Settings
     *
     * @param  array  $data  Field data
     * @return array  Settings to save
     */
    public function save_settings($data)
    {
        $defaults = array(
            'localization' => 'ask',
            'show_time' => true
        );

        $all = array_merge($defaults, $data);

        return array_intersect_key($all, $defaults);
    }

    public function grid_display_settings($data)
    {
        return array(
            'field_options' => array(
                array(
                    'title' => 'localize_date',
                    'desc' => sprintf(lang('localize_date_desc'), ee('CP/URL')->make('settings/general')),
                    'fields' => array(
                        'localize' => array(
                            'type' => 'yes_no',
                            'value' => isset($data['localize']) ? $data['localize'] : true,
                        )
                    )
                ),
                array(
                    'title' => 'show_time',
                    'desc' => 'show_time_desc',
                    'fields' => array(
                        'show_time' => array(
                            'type' => 'yes_no',
                            'value' => isset($data['show_time']) ? $data['show_time'] : true,
                        )
                    )
                )
            )
        );
    }

    public function grid_save_settings($data)
    {
        return array(
            'localize' => get_bool_from_string($data['localize']),
            'show_time' => get_bool_from_string($data['show_time'])
        );
    }

    public function settings_modify_column($data)
    {
        $fields['field_id_' . $data['field_id']] = array(
            'type' => 'INT',
            'constraint' => 10,
            'default' => 0
        );

        $fields['field_dt_' . $data['field_id']] = array(
            'type' => 'VARCHAR',
            'constraint' => 50
        );

        return $fields;
    }

    public function grid_settings_modify_column($data)
    {
        return array('col_id_' . $data['col_id'] =>
            array(
                'type' => 'VARCHAR',
                'constraint' => 60,
                'default' => null
            )
        );
    }

    /**
     * Accept all content types.
     *
     * @param string  The name of the content type
     * @return bool   Accepts all content types
     */
    public function accepts_content_type($name)
    {
        return true;
    }

    /**
     * Update the fieldtype
     *
     * @param string $version The version being updated to
     * @return boolean TRUE if successful, FALSE otherwise
     */
    public function update($version)
    {
        return true;
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        if ($data == 0) {
            return '';
        }

        return ee()->localize->human_time($data);
    }
}

// END Date_ft class

// EOF
