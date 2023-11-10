<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once SYSPATH . 'ee/ExpressionEngine/Addons/multi_select/ft.multi_select.php';
require_once SYSPATH . 'ee/ExpressionEngine/Addons/select/ft.select.php';

/**
 * Buttons Fieldtype
 */
class Selectable_buttons_ft extends Multi_select_ft
{
    public $info = array(
        'name' => 'Selectable Buttons',
        'version' => '1.0.0'
    );

    public $has_array_data = true;

    public $can_be_cloned = true;

    public $entry_manager_compatible = true;

    /**
     * A list of operators that this fieldtype supports
     *
     * @var array
     */
    public $supportedEvaluationRules = ['matches', 'notMatches', 'contains', 'notContains', 'isEmpty', 'isNotEmpty'];

    public $defaultEvaluationRule = 'matches';

    /**
     * Constructor
     *
     * @access	public
     */
    public function __construct()
    {
        parent::__construct();
        ee()->lang->load('fieldtypes');
    }

    public function validate($data)
    {
        if (!isset($this->settings['allow_multiple']) || !$this->settings['allow_multiple']) {
            if (is_array($data) && count($data) > 1) {
                return ee()->lang->line('ft_multiselect_not_allowed');
            }
        }
        return parent::validate($data);
    }

    public function display_field($data)
    {
        ee()->load->helper('custom_field');

        $values = decode_multi_field($data);

        ee()->javascript->output("
            $('body').on('change','.ee-buttons-field.selectable_buttons .button input[type=checkbox]', function (e) {

                if ( !($(this).parents('.button-group').hasClass('multiple')) ) {
                    var elParent = $(this).parents('.selectable_buttons');
                    $(elParent).find('.button input[type=checkbox]').not(this).prop('checked', false);
                }

                $(this).parents('.button-group').find('.button input[type=checkbox]').each(function () {
                    if ($(this).prop('checked')) {
                        $(this).parent().addClass('active');
                    } else {
                        $(this).parent().removeClass('active')
                    }
                });
            });
        ");

        return ee('View')->make('ee:_shared/form/fields/buttons')->render([
            'field_name' => $this->field_name . '[]',
            'choices' => $this->_get_field_options($data),
            'value' => $values,
            'multi' => isset($this->settings['allow_multiple']) ? $this->settings['allow_multiple'] : false,
            'disabled' => $this->get_setting('field_disabled'),
            'class' => 'ee-buttons-field selectable_buttons'
        ]);

        $extra = ($this->get_setting('field_disabled')) ? 'disabled' : '';
        $extra .= ' dir="' . $this->get_setting('field_text_direction', 'ltr') . '"';

        if (isset($this->settings['allow_multiple']) && $this->settings['allow_multiple']) {
            $extra .= ' class="multiselect_input"';
            return form_multiselect(
                $this->field_name . '[]',
                $this->_get_field_options($data),
                $values,
                $extra
            );
        } else {
            return form_dropdown(
                $this->field_name,
                $this->_get_field_options($data, '--'),
                $data,
                $extra
            );
        }
    }

    /**
     * :value modifier
     */
    public function replace_value($data, $params = array(), $tagdata = false)
    {
        ee()->load->helper('custom_field');
        $data = decode_multi_field($data);

        return $this->_parse_single($data, $params, true);
    }

    /**
     * :label modifier
     */
    public function replace_label($data, $params = array(), $tagdata = false)
    {
        return $this->replace_tag($data, $params, $tagdata);
    }

    public function display_settings($data)
    {
        $settings = $this->getSettingsForm(
            'selectable_buttons',
            $data,
            'selectable_buttons_options',
            lang('options_field_desc') . lang('selectable_buttons_options_desc')
        );

        array_unshift($settings, array(
            'title' => 'ft_allow_multi',
            'desc' => 'ft_allow_multi_desc',
            'fields' => array(
                'allow_multiple' => array(
                    'type' => 'yes_no',
                    'value' => (isset($data['allow_multiple']) && $data['allow_multiple']) ? 'y' : 'n'
                )
            )
        ));

        return array('field_options_selectable_buttons' => array(
            'label' => 'field_options',
            'group' => 'selectable_buttons',
            'settings' => $settings
        ));
    }

    public function save_settings($data)
    {
        $settings = parent::save_settings($data);
        $settings['allow_multiple'] = (isset($data['allow_multiple']) && $data['allow_multiple'] == 'y') ? true : false;
        return $settings;
    }

    public function grid_display_settings($data)
    {
        $gridSettingsForm = $this->getGridSettingsForm(
            'selectable_buttons',
            $data,
            'selectable_buttons_options',
            'grid_selectable_buttons_options_desc'
        );
        array_unshift($gridSettingsForm['field_options'], array(
            'title' => 'ft_allow_multi',
            'desc' => 'ft_allow_multi_desc',
            'fields' => array(
                'allow_multiple' => array(
                    'type' => 'yes_no',
                    'value' => (isset($data['allow_multiple']) && $data['allow_multiple']) ? 'y' : 'n'
                )
            )
        ));
        return $gridSettingsForm;
    }

}

// EOF
