<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Toggle Fieldtype
 */
class Toggle_ft extends EE_Fieldtype
{

    public $info = array(
        'name' => 'Toggle',
        'version' => '1.0.0'
    );

    public $entry_manager_compatible = true;
    public $has_array_data = false;

    public $size = 'small';

    // used in display_field() below to set
    // some defaults for third party usage
    public $settings_vars = array(
        'field_default_value' => '0',
    );

    /**
     * A list of operators that this fieldtype supports
     *
     * @var array
     */
    public $supportedEvaluationRules = ['turnedOn', 'turnedOff'];

    /**
     * Fetch the fieldtype's name and version from it's addon.setup.php file.
     */
    public function __construct()
    {
        $addon = ee('Addon')->get('toggle');
        $this->info = array(
            'name' => $addon->getName(),
            'version' => $addon->getVersion()
        );
    }

    /**
     * @see EE_Fieldtype::validate()
     */
    public function validate($data)
    {
        if ($this->get_setting('yes_no', false)) {
            return in_array($data, ['y', 'n']);
        }

        if ($data === false
            || $data == ''
            || $data == '1'
            || $data == '0') {
            return true;
        }

        return ee()->lang->line('invalid_selection');
    }

    /**
     * @see EE_Fieldtype::save()
     */
    public function save($data)
    {
        if ($this->get_setting('yes_no', false)) {
            return ($data == 'y') ? 'y' : 'n';
        }

        return (int) $data;
    }

    /**
     * :length modifier
     */
    public function replace_length($data, $params = array(), $tagdata = false)
    {
        return (int) $data;
    }

    /**
     * @see EE_Fieldtype::display_field()
     */
    public function display_field($data)
    {
        return $this->_display_field($data);
    }

    /**
     * @see _display_field()
     */
    public function grid_display_field($data)
    {
        return $this->_display_field(form_prep($data), 'grid');
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

        $data = (is_null($data) or $data === '') ? $this->settings['field_default_value'] : $data;

        if (REQ != 'CP') {
            // at this point, Channel Form requires jquery, so we're safe including this
            // will need to be rewritten in vanilla JS however
            ee()->javascript->output("
                $('body').on('click', '.ee-cform button.toggle-btn', function (e) {
                    if ($(this).hasClass('disabled') ||
                        $(this).parents('.toggle-tools').length > 0 ||
                        $(this).parents('[data-reactroot]').length > 0) {
                        return;
                    }
                
                    var input = $(this).find('input[type=hidden]'),
                        yes_no = $(this).hasClass('yes_no'),
                        onOff = $(this).hasClass('off') ? 'on' : 'off',
                        trueFalse = $(this).hasClass('off') ? 'true' : 'false';
                
                    if ($(this).hasClass('off')){
                        $(this).removeClass('off');
                        $(this).addClass('on');
                        $(input).val(yes_no ? 'y' : 1).trigger('change');
                    } else {
                        $(this).removeClass('on');
                        $(this).addClass('off');
                        $(input).val(yes_no ? 'n' : 0).trigger('change');
                    }
                
                    $(this).attr('alt', onOff);
                    $(this).attr('data-state', onOff);
                    $(this).attr('aria-checked', trueFalse);
                
                    e.preventDefault();
                });
            ");
        }

        return ee('View')->make('ee:_shared/form/fields/toggle')->render(array(
            'field_name' => $this->field_name,
            'value' => $data,
            'disabled' => $this->get_setting('field_disabled'),
            'yes_no' => $this->get_setting('yes_no', false)
        ));

        $field_options = array(
            lang('on') => 1,
            lang('off') => 0
        );

        $html = '';
        $class = 'choice mr';

        foreach ($field_options as $key => $value) {
            $selected = ($value == $data);

            $html .= '<label>' . form_radio($this->field_name, $value, $selected) . NBS . $key . '</label>';
        }

        switch ($container) {
            case 'grid':
                $html = $this->grid_padding_container($html);

                break;

            default:
                $html = form_fieldset('') . $html . form_fieldset_close();

                break;
        }

        return $html;
    }

    public function display_settings($data)
    {
        $defaults = array(
            'field_default_value' => 0
        );

        foreach ($defaults as $setting => $value) {
            $data[$setting] = isset($data[$setting]) ? $data[$setting] : $value;
        }

        $this->field_name = 'field_default_value';

        $settings = array(
            array(
                'title' => 'default_value',
                'desc' => 'toggle_default_value_desc',
                'desc_cont' => 'toggle_default_value_desc_cont',
                'fields' => array(
                    'field_default_value' => array(
                        'type' => 'html',
                        'content' => $this->_display_field($data['field_default_value'])
                    )
                )
            ),
        );

        if ($this->content_type() == 'grid') {
            return array('field_options' => $settings);
        }

        return array('field_options_toggle' => array(
            'label' => 'field_options',
            'group' => 'toggle',
            'settings' => $settings
        ));
    }

    public function save_settings($data)
    {
        $all = array_merge($this->settings_vars, $data);

        if (is_null($this->field_id)) {
            ee('CP/Alert')->makeInline('search-reindex')
                ->asImportant()
                ->withTitle(lang('search_reindex_tip'))
                ->addToBody(sprintf(lang('search_reindex_tip_desc'), ee('CP/URL')->make('utilities/reindex')->compile()))
                ->defer();

            ee()->config->update_site_prefs(['search_reindex_needed' => ee()->localize->now], 0);
        }

        return array_intersect_key($all, $this->settings_vars);
    }

    /**
     * Set the column to be TINYINT
     *
     * @param array $data The field data
     * @return array  [column => column_definition]
     */
    public function settings_modify_column($data)
    {
        return $this->get_column_type($data);
    }

    /**
     * Set the grid column to be TINYINT
     *
     * @param array $data The field data
     * @return array  [column => column_definition]
     */
    public function grid_settings_modify_column($data)
    {
        return $this->get_column_type($data, true);
    }

    /**
     * Helper method for column definitions
     *
     * @param array $data The field data
     * @param bool  $grid Is grid field?
     * @return array  [column => column_definition]
     */
    protected function get_column_type($data, $grid = false)
    {
        $id = ($grid) ? 'col_id' : 'field_id';

        if (isset($data['ee_action']) && $data['ee_action'] == 'delete') {
            return [$id . '_' . $data[$id] => []];
        }

        $default_value = ($grid) ? $data['field_default_value'] : $data['field_settings']['field_default_value'];

        return array(
            $id . '_' . $data[$id] => array(
                'type' => 'TINYINT',
                'null' => false,
                'default' => $default_value
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

    /**
     * @param string $data
     * @param integer $field_id
     * @param integer $entry
     * @return string
     */
    public function renderTableCell($data, $field_id, $entry)
    {
        return ee('View')->make('ee:_shared/form/fields/toggle')->render([
            'field_name' => $this->field_name,
            'value' => $data,
            'disabled' => true,
        ]);
    }

    /**
     * @return array
     */
    public function getTableColumnConfig()
    {
        return [
            'encode' => false
        ];
    }
}

// EOF
