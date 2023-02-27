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
 * Select Fieldtype
 */
class Select_ft extends OptionFieldtype
{

    public $info = array(
        'name' => 'Select Dropdown',
        'version' => '1.0.0'
    );

    public $has_array_data = true;

    public $can_be_cloned = true;

    public $entry_manager_compatible = true;

    public $size = 'small';

    /**
     * A list of operators that this fieldtype supports
     *
     * @var array
     */
    public $supportedEvaluationRules = ['matches', 'notMatches', 'isEmpty', 'isNotEmpty'];

    public function validate($data)
    {
        $valid = false;
        $field_options = $this->_get_field_options($data, '--');

        if ($data == '') {
            return true;
        }

        foreach ($field_options as $key => $val) {
            if (is_array($val)) {
                if (array_key_exists($data, $val)) {
                    $valid = true;

                    break;
                }
            } elseif ($key == $data) {
                $valid = true;

                break;
            }
        }

        if (! $valid) {
            return ee()->lang->line('invalid_selection');
        }
    }

    public function display_field($data)
    {
        $extra = 'dir="' . $this->get_setting('field_text_direction', 'ltr') . '"';

        if ($this->get_setting('field_disabled')) {
            $extra .= ' disabled';
        }

        // if (REQ == 'CP' && $this->content_type() !== 'grid') {
        if (REQ == 'CP') {
            return ee('View')->make('ee:_shared/form/fields/dropdown')->render([
                'field_name' => $this->field_name,
                'choices' => $this->_get_field_options($data),
                'value' => $data,
                'empty_text' => lang('choose_wisely'),
                'field_disabled' => $this->get_setting('field_disabled'),
                'ignoreSectionLabel' => $this->get_setting('ignore_section_label')
            ]);
        }

        $field = form_dropdown(
            $this->field_name,
            $this->_get_field_options($data, '--'),
            $data,
            $extra
        );

        return $field;
    }

    public function grid_display_field($data)
    {
        return $this->display_field($data);
    }

    public function display_settings($data)
    {
        $settings = $this->getSettingsForm(
            'select',
            $data,
            'select_options',
            lang('options_field_desc') . lang('select_options_desc')
        );

        return array('field_options_select' => array(
            'label' => 'field_options',
            'group' => 'select',
            'settings' => $settings
        ));
    }

    public function grid_display_settings($data)
    {
        return $this->getGridSettingsForm(
            'select',
            $data,
            'select_options',
            'grid_select_options_desc'
        );
    }

    /**
     * replace the {field} tag
     *
     * @param [type] $data
     * @param array $params
     * @param boolean $tagdata
     * @return string
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        if ($tagdata) {
            return $this->_parse_multi([$data], $params, $tagdata);
        }

        return parent::replace_tag($data, $params, $tagdata);
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

// END Select_ft class

// EOF
