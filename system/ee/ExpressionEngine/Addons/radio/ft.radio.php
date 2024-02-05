<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once SYSPATH . 'ee/legacy/fieldtypes/OptionFieldtype.php';

/**
 * Radio Fieldtype
 */
class Radio_ft extends OptionFieldtype
{

    public $info = array(
        'name' => 'Radio Buttons',
        'version' => '1.0.0'
    );

    public $has_array_data = false;

    public $size = 'small';

    // used in display_field() below to set
    // some defaults for third party usage
    public $settings_vars = array(
        'field_text_direction' => 'rtl',
        'field_pre_populate' => 'n',
        'field_list_items' => array(),
        'field_pre_field_id' => '',
        'field_pre_channel_id' => ''
    );

    /**
     * A list of operators that this fieldtype supports
     *
     * @var array
     */
    public $supportedEvaluationRules = ['matches', 'notMatches', 'isEmpty', 'isNotEmpty'];

    public function validate($data)
    {
        $valid = false;
        $field_options = $this->_get_field_options($data);

        if ($data === false or $data == '') {
            return true;
        }

        foreach ($field_options as $key => $val) {
            if (is_array($val)) {
                if (isset($val['value']) && $data == $val['value']) {
                    $valid = true;

                    break;
                } elseif (array_key_exists($data, $val)) {
                    $valid = true;

                    break;
                }
            } elseif ($key == $data) {
                $valid = true;

                break;
            }
        }

        // We can't validate based on the fields original options if they've
        // changed via AJAX, so skip if filter_url is defined
        if (! $valid && ! $this->get_setting('filter_url', null)) {
            return ee()->lang->line('invalid_selection');
        }
    }

    public function display_field($data)
    {
        return $this->_display_field($data);
    }

    public function grid_display_field($data)
    {
        return $this->_display_field($data, 'grid');
    }

    /**
     * Displays the field for the CP or Frontend, and accounts for grid
     *
     * @param string $data Stored data for the field
     * @param string $container What type of container is this field in, 'fieldset' or 'grid'?
     * @return string Field display
     */
    private function _display_field($data, $container = 'fieldset')
    {
        $this->settings = array_merge($this->settings_vars, $this->settings);

        $text_direction = (isset($this->settings['field_text_direction']))
            ? $this->settings['field_text_direction'] : 'ltr';

        $field_options = $this->_get_field_options($data);
        $extra = ($this->get_setting('field_disabled')) ? 'disabled' : '';

        // Is this new entry?  Set a default
        if (! $this->content_id and is_null($data)) {
            reset($field_options);
            $data = key($field_options);
        }

        if (REQ == 'CP') {
            if ($data === true) {
                $data = 'y';
            } elseif ($data === false) {
                $data = 'n';
            }

            return ee('View')->make('ee:_shared/form/fields/select')->render([
                'field_name' => $this->field_name,
                'choices' => $field_options,
                'value' => $data,
                'multi' => false,
                'disabled' => $this->get_setting('field_disabled'),
                'filter_url' => $this->get_setting('filter_url', null),
                'no_results' => $this->get_setting('no_results', null),
                'nested' => $this->get_setting('nested', false),
                'nestable_reorder' => $this->get_setting('nestableReorder', false),
                'force_react' => $this->get_setting('force_react', false),
                'manageable' => $this->get_setting('editable', false)
                    && ! $this->get_setting('in_modal_context'),
                'add_btn_label' => $this->get_setting('add_btn_label', null),
                'editing' => $this->get_setting('editing', false),
                'manage_label' => $this->get_setting('manage_toggle_label', lang('manage')),
                'reorder_ajax_url' => $this->get_setting('reorder_ajax_url', null),
                'auto_select_parents' => false,
            ]);
        }

        $selected = $data;

        $r = '';

        foreach ($field_options as $key => $value) {
            $selected = ($key == $data);

            $r .= '<label>' . form_radio($this->field_name, $key, $selected, $extra) . NBS . $value . '</label>';
        }

        switch ($container) {
            case 'grid':
                $r = $this->grid_padding_container($r);

                break;

            default:
                $r = form_fieldset('', ['class' => 'radio-btn-wrap']) . $r . form_fieldset_close();

                break;
        }

        return $r;
    }

    public function display_settings($data)
    {
        $settings = $this->getSettingsForm(
            'radio',
            $data,
            'radio_options',
            lang('options_field_desc') . lang('radio_options_desc')
        );

        return array('field_options_radio' => array(
            'label' => 'field_options',
            'group' => 'radio',
            'settings' => $settings
        ));
    }

    public function grid_display_settings($data)
    {
        return $this->getGridSettingsForm(
            'radio',
            $data,
            'radio_options',
            'grid_radio_options_desc'
        );
    }

    /**
     * :value modifier
     */
    public function replace_value($data, $params = array(), $tagdata = false)
    {
        return $this->replace_tag($data, $params, $tagdata);
    }

    /**
     * :label modifier
     */
    public function replace_label($data, $params = array(), $tagdata = false)
    {
        $pairs = $this->get_setting('value_label_pairs');
        if (isset($pairs[$data])) {
            $data = $pairs[$data];
        }

        $data = $this->processTypograpghy($data);

        return $this->replace_tag($data, $params, $tagdata);
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
        return $this->_parse_single([$data], []);
    }
}

// END Radio_ft class

// EOF
