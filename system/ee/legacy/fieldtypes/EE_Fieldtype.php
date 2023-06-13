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
 * EE_Fieldtype
 */
abstract class EE_Fieldtype
{
    // bring in the :modifier methods
    use ExpressionEngine\Service\Template\Variables\ModifiableTrait;

    // Old identifiers for backwards compatibility.
    // @deprecated
    public $field_id;
    public $field_name;

    public $row;
    public $var_id;

    // EE super object
    // @deprecated - use ee()
    protected $EE;

    // Field settings as decided by the user.
    public $settings = array();

    public $field_fmt;

    // Field identifiers, new names to differentiate. Also sometimes the field
    // can actually act as an independent content container with no distinct parent.
    // In those cases it's up to the content type implementer to make sure that an
    // content id (see below) exists. It would probably simply be the field_id.
    protected $id;
    protected $name;

    // Content identifiers. The content_type will uniquely identify the type of
    // parent row, such as 'channel' (channel_entry, really). The content_id
    // will be id of the parent after it has been saved. For channels that would
    // be the entry_id. There is no provision for channel_id as that is a level
    // up in abstraction. If you need to manipulate that information your fieldtype
    // may not work with alternate content types.
    protected $content_id = null;
    protected $content_type = 'channel';

    public function __construct()
    {
    }

    /**
     * Re-initialize the class.
     *
     * Friend <Api_channel_fields>
     */
    public function _init($config = array())
    {
        // At first our implementers will probably still have
        // field_id and field_name set, so we'll copy those over.
        $conf_id = null;
        $conf_name = null;

        // Prefer unprefixed over prefixed
        foreach (array('id', 'field_id', 'name', 'field_name') as $key) {
            $name = 'conf_' . str_replace('field_', '', $key);

            if (! isset($$name) && isset($config[$key])) {
                $$name = $config[$key];
            }
        }

        // Only set if the _init call changed it. Otherwise consecutive
        // _init calls might clear it.
        if (isset($conf_id) && $this->id != $conf_id) {
            $config['id'] = $conf_id;
        }

        if (isset($conf_name) && $this->name != $conf_name) {
            $config['name'] = $conf_name;
        }

        foreach ($config as $key => $val) {
            $this->$key = $val;
        }

        // Since this is pretty new, I think the content types will beat
        // the fieldtypes in conversion. Certainly channel will. So we need to
        // support fieldtypes that use the old conventions for a while. Move
        // to __set and __get when we're ready for full deprecation.
        $this->field_id = $this->id;
        $this->field_name = $this->name;

        // Since fieldtypes are currently treated as singletons, we need to make
        // sure if a fieldtype is instantiated without a content_id that it
        // doesn't continue to use the content ID from the previous instantiation
        if (! isset($config['content_id'])) {
            $this->content_id = null;
        }
    }

    /**
     * Field id getter
     *
     * The id of the field in the content of this content type. For
     * channels that would be field_id. Usually this is a good name,
     * but sometimes each field actually presents a type of your content
     * so we call it id.
     *
     * @return int  primary key
     */
    public function id()
    {
        return $this->id;
    }

    public function isNew()
    {
        return is_null($this->id);
    }

    /**
     * Name getter
     *
     * This is the field's short name, which you will want to prefix
     * your form data and to parse your data.
     *
     * @return string  The field short name
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Grab the content id
     *
     * The content id only exists after the field is saved. It will return
     * NULL if it has not been set. For channel, this would be the entry_id.
     *
     * @return int  The id of the parent content if it exists, else NULL
     */
    public function content_id()
    {
        return $this->content_id;
    }

    /**
     * Grab the content type
     *
     * By default, the content type is 'channel' since that is historically
     * correct. Your fieldtype must delineate data between content types.
     *
     * @return string  The type of the field's parent content.
     */
    public function content_type()
    {
        return $this->content_type;
    }

    /**
     * Row accessor
     *
     * Provides access to the row variable for an entry. Since not all
     * content types provide a concrete row, and most don't agree on what
     * fields are always available, this method is useful to provide defaults
     * to row data.
     *
     * @param  string  Name of the index to look for
     * @param  string  Value to return of the index doesn't exist
     * @return string  The retrieved content element
     */
    public function row($key, $default = null)
    {
        return (isset($this->row) && array_key_exists($key, $this->row)) ? $this->row[$key] : $default;
    }

    /**
     * Register a new content type
     *
     * The developer may need to add tables or columns to support multiple
     * content types, so we must be able to hook into that event.
     *
     * @param string  The name of the new content type
     */
    public function register_content_type($name)
    {
        return;
    }

    /**
     * Unregister a content type
     *
     * The developer of the fieldtype is responsible for completely
     * clearing the data stored by the fieldtype's custom tables. This
     * method is available to clear everyting of a certain content type.
     *
     * @param string  The name of the content type being removed
     */
    public function unregister_content_type($name)
    {
        return;
    }

    /**
     * Check if the fieldtype will accept a certain content type
     *
     * For backward compatiblity, all fieldtypes will initially only
     * support the channel content type. Override this method for more
     * control.
     *
     * @param string  The name of the content type
     * @return bool   Supports content type?
     */
    public function accepts_content_type($name)
    {
        return ($name == 'channel');
    }

    /**
     * Replace the field tag on the frontend.
     *
     * @param string  Stored data for the field
     * @param array   The tag's parameters
     * @param mixed   If the tag is a pair, the tagdata. Otherwise FALSE.
     * @return string Parsed tagdata
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        if ($tagdata) {
            return $tagdata;
        }

        return $data;
    }

    /**
     * Pre process the stored data.
     *
     * This is called before the field is displayed. It's return will
     * be passed to the display method as the data parameter.
     *
     * @param mixed   stored data
     * @return mixed  processed data
     */
    public function pre_process($data)
    {
        return $data;
    }

    /**
     * Validate the settings
     *
     * This is called before the settings are fully saved
     *
     * @param mixed   settings data
     * @return mixed  validation result
     */
    public function validate_settings($data)
    {
        return;
    }

    /**
     * Validate the field data
     *
     * This is called before the field is stored, so you can sanity check
     * your data.
     *
     * @param mixed   stored data
     * @return mixed  several options:
     *  return TRUE			      - no error, continue
     *  return $string		      - error message
     *  array('value' => $mixed)  - override the value
     *  array('error' => $string) - same as the error options, but can be combined with the value override.
     */
    public function validate($data)
    {
        return true;
    }

    /**
     * Mark the field as having a certain status. Different statuses *may*
     * cause changes in the appearance of the field or it's elements. For
     * example a field in a warning state may be rendered with a yellow flag
     * to alert the user that it requires attention.
     *
     * Statuses are not to be confused with validation. No status will
     * prevent the submission of the form, but it may indicate to the user
     * that validaiton is likely to fail.
     *
     * @return String {ok, invalid, warning, error, failure}
     */
    public function get_field_status($data)
    {
        return 'ok';
    }

    /**
     * Display the field. You *must* implement this method to satisfy the
     * fieldtype protocol. You can leave out everything else, but this is
     * required.
     *
     * @param  string Stored data for the field
     * @return string Field display
     */
    abstract public function display_field($data);

    /**
     * Display the publish field. This is publish specific, it will add
     * the glossary items for the fieldtypes that want them. Could be better?
     *
     * You probably don't want to override this, instead your display_field
     * method should handle this.
     *
     * @param string  Stored data for the field
     * @return string Final field display
     */
    public function display_publish_field($data)
    {
        return $this->display_field($data);
    }

    /**
     * Save the field
     *
     * The data you return will be saved and returned to your field on
     * display on the frontend and when editing the field. This is the
     * field that is processed on search.
     *
     * If you want to store data in your own table, please use post_save
     * when the entry/content id is available.
     *
     * @param   mixed  data submitted with the field_name, arrays allowed
     * @return  string data to store
     */
    public function save($data)
    {
        return $data;
    }

    /**
     * Called after field is saved
     *
     * This will have access to the parent content_id, so if you use your
     * own tables, please use the content_id and content_type to store the
     * data in a uniquely identifiable way ($this->content_id()).
     *
     * @param string  Data returned from save(). You can use session->cache() for other data.
     * @return void
     */
    public function post_save($data)
    {
        return;
    }

    /**
     * Called when entries are deleted.
     *
     * Please be sure to check the content_type when you delete.
     *
     * @param   array  array of id's
     * @return  void
     */
    public function delete($ids)
    {
        return;
    }

    /**
     * Get a given setting for the Fieldtype. Returns TRUE/FALSE for values that
     * use 'y' and 'n'. Returns `FALSE` if no setting is set.
     *
     * @param  string $key The key of the setting
     * @return mixed       The value of the setting
     */
    public function get_setting($key, $default = false)
    {
        if (! isset($this->settings[$key])) {
            return $default;
        }

        $boolean_fields = array(
            'field_disabled',
            'field_is_hidden',
            'field_is_conditional',
            'field_pre_populate',
            'field_required',
            'field_search',
            'field_show_file_selector',
            'field_show_fmt',
            'field_show_formatting_btns',
            'field_show_glossary',
            'field_show_smileys',
            'field_show_spellcheck',
            'field_show_writemode',
        );

        if (in_array($key, $boolean_fields)) {
            return get_bool_from_string($this->settings[$key]);
        }

        return $this->settings[$key];
    }

    /**
     * Display Field Settings
     *
     * @param   array   Currently saved settings for this field
     * @return  string  Settings form display
     */
    public function display_settings($data)
    {
        return '';
    }

    /**
     * Save Settings
     *
     * @param   array  Any settings $_POST'ed with the $field_name.'_' prefix
     * @return  mixed  Settings to store
     */
    public function save_settings($data)
    {
        return array();
    }

    /**
     * Save Global Settings
     *
     * Same as settings(), but saved settings are used as defaults for
     * the settings page.
     *
     * @param   array  Any settings $_POST'ed with the $field_name.'_' prefix
     * @return  mixed  Settings to store
     */
    public function save_global_settings()
    {
        return array();
    }

    /**
     * Post Save Settings
     *
     * Called after the settings are saved. Gives you access to the id
     * for this field.
     *
     * @param   array  Full settings array, including the id()
     * @return  void
     */
    public function post_save_settings($data)
    {
        return;
    }

    /**
     * Settings Modify Column
     *
     * Specify the type of columns you need for the field data and formatting.
     *
     * @param   array
     *      - field_id: id of the current field
     *      - ee_action: add, delete, get_data (asks for information)
     * @return  array
     *      - column_name's => array('type' => 'db_type', 'null' => TRUE/FALSE)
     */
    public function settings_modify_column($data)
    {
        // Default custom field additions to channel_data
        $fields['field_id_' . $data['field_id']] = array(
            'type' => 'text',
            'null' => true
        );

        $fields['field_ft_' . $data['field_id']] = array(
            'type' => 'tinytext',
            'null' => true,
        );

        return $fields;
    }

    /**
     * Grid Settings Modify Column
     *
     * @access  public
     * @param   array
     * @return  array
     */
    public function grid_settings_modify_column($data)
    {
        $fields['col_id_' . $data['col_id']] = array(
            'type' => 'text',
            'null' => true
        );

        return $fields;
    }

    /**
     * Install
     *
     * Do any processing you may need to do to install the fieldtype. You can
     * return an array of global settings to use as setting defaults.
     *
     * Note: The fieldtype table is populated automatically.
     *
     * @return  array   global settings
     */
    public function install()
    {
        return array();
    }

    /**
     * Uninstall
     *
     * Do any processing you need to remove the fieldtype. The fieldtype
     * table is cleaned up automatically.
     *
     * @return  void
     */
    public function uninstall()
    {
        return;
    }

    /**
     * Helper method to show the field formatting row
     *
     * The row is added to the currently active table instance.
     *
     * @param   array   data array passed to display_settings()
     * @param   string  A prefix to use, typically the field name
     * @return  void
     */
    public function field_formatting_row($data, $prefix = false)
    {
        $edit_format_link = $data['edit_format_link'];
        $prefix = ($prefix) ? $prefix . '_' : '';

        $extra = '';

        if ($data['field_id'] != '') {
            $extra .= '<div class="notice update_formatting js_hide">';
            $extra .= '<p>' . lang('fmt_has_changed') . '</p><p>';
            $extra .= form_checkbox($prefix . 'update_formatting', 'y', false, 'id="' . $prefix . 'update_formatting"');
            $extra .= NBS . lang('update_existing_fields', $prefix . 'update_formatting');
            $extra .= '</p></div>';
        }

        // Data from Form Validation
        $show_fmt = set_value($prefix . 'field_show_fmt', $data['field_show_fmt_y']);
        $show_fmt = ($show_fmt == 'y' or $show_fmt == '1');

        ee()->table->add_row(
            lang('deft_field_formatting', $prefix . 'field_fmt'),
            form_dropdown($prefix . 'field_fmt', $data['field_fmt_options'], set_value($prefix . 'field_fmt', $data['field_fmt']), 'id="' . $prefix . 'field_fmt"') .
                NBS . $data['edit_format_link'] . BR . BR .
                '<strong>' . lang('show_formatting_buttons') . '</strong>' . BR .
                form_radio($prefix . 'field_show_fmt', 'y', $show_fmt, 'id="' . $prefix . 'field_show_fmt_y"') . NBS .
                lang('yes', $prefix . 'field_show_fmt_y') . NBS . NBS . NBS . NBS . NBS .
                form_radio($prefix . 'field_show_fmt', 'n', ! $show_fmt, 'id="' . $prefix . 'field_show_fmt_n"') . NBS .
                lang('no', $prefix . 'field_show_fmt_n') .
                $extra
        );

        ee()->javascript->output('
		$("#' . $prefix . 'field_fmt").change(function() {
			$(this).nextAll(".update_formatting").show();
		});
		');
    }

    /**
     * Helper method to show the text direction row
     *
     * The row is added to the currently active table instance.
     *
     * @param   array   data array passed to display_settings()
     * @param   string  A prefix to use, typically the field name
     * @return  void
     */
    public function text_direction_row($data, $prefix = false)
    {
        $prefix = ($prefix) ? $prefix . '_' : '';

        // Data from Form Validation
        $ltr_checked = set_value($prefix . 'field_text_direction', $data['field_text_direction_ltr']);
        $ltr_checked = ($ltr_checked == 'ltr' or $ltr_checked == '1');

        ee()->table->add_row(
            '<strong>' . lang('text_direction') . '</strong>',
            form_radio($prefix . 'field_text_direction', 'ltr', $ltr_checked, 'id="' . $prefix . 'field_text_direction_ltr"') . NBS .
                lang('ltr', $prefix . 'field_text_direction_ltr') . NBS . NBS . NBS . NBS . NBS .
                form_radio($prefix . 'field_text_direction', 'rtl', ! $ltr_checked, 'id="' . $prefix . 'field_text_direction_rtl"') . NBS .
                lang('rtl', $prefix . 'field_text_direction_rtl')
        );
    }

    /**
     * Helper method to show the content type row
     *
     * The row is added to the currently active table instance.
     *
     * @param   array   data array passed to display_settings()
     * @param   string  A prefix to use, typically the field name
     * @return  void
     */
    public function field_content_type_row($data, $prefix = false)
    {
        $suf = $prefix;
        $prefix = ($prefix) ? $prefix . '_' : '';

        $extra = '';

        if ($data['field_id'] != '') {
            $extra .= '<div class="notice update_content_type js_hide">';
            $extra .= '<p>' . sprintf(
                lang('content_type_changed'),
                $data['field_content_' . $suf]
            ) . '</p></div>';
        }

        ee()->table->add_row(
            lang('field_content_' . $suf, 'field_content_' . $suf),
            form_dropdown($prefix . 'field_content_type', $data['field_content_options_' . $suf], set_value($prefix . 'field_content_type', $data['field_content_' . $suf]), 'id="' . $prefix . 'field_content_type"') . $extra
        );

        ee()->javascript->output('
		$("#' . $prefix . 'field_content_type").change(function() {
			$(this).nextAll(".update_content_type").show();
		});
		');
    }

    /**
     * Helper method for fields that request a custom list of options.
     *
     * The row is added to the currently active table instance. The data
     * entered is sent in a field called field_pre_populate.
     *
     * @param   array   data array passed to display_settings()
     * @param   string  A prefix to use, typically the field name
     * @return  void
     */
    public function multi_item_row($data, $prefix = false)
    {
        $prefix = ($prefix) ? $prefix . '_' : '';

        $pre_populate = set_value($prefix . 'field_pre_populate', $data['field_pre_populate']);

        ee()->table->add_row(
            '<p class="field_format_option select_format">' .
                form_radio($prefix . 'field_pre_populate', 'n', ($pre_populate == 'n'), 'id="' . $prefix . 'field_pre_populate_n"') . NBS .
                lang('field_populate_manually', $prefix . 'field_pre_populate_n') . BR .
                form_radio($prefix . 'field_pre_populate', 'y', ($pre_populate == 'y'), 'id="' . $prefix . 'field_pre_populate_y"') . NBS .
                lang('field_populate_from_channel', $prefix . 'field_pre_populate_y') .
            '</p>',
            '<p class="field_format_option select_format_n">' .
                lang('multi_list_items', $prefix . 'field_list_items') . BR .
                lang('field_list_instructions') . BR .
                form_textarea(array('id' => $prefix . 'field_list_items','name' => $prefix . 'field_list_items', 'rows' => 10, 'cols' => 50, 'value' => set_value($prefix . 'field_list_items', $data['field_list_items']))) .
            '</p>
			<p class="field_format_option select_format_y">' .
                lang('select_channel_for_field', $prefix . 'field_pre_populate_id') .
                form_dropdown($prefix . 'field_pre_populate_id', $data['field_pre_populate_id_options'], set_value($prefix . 'field_pre_populate_id', $data['field_pre_populate_id_select']), 'id="' . $prefix . 'field_pre_populate_id"') .
            '</p>'
        );

        ee()->javascript->click('#' . $prefix . 'field_pre_populate_n', '$(".select_format_n").show();$(".select_format_y").hide();', false);
        ee()->javascript->click('#' . $prefix . 'field_pre_populate_y', '$(".select_format_y").show();$(".select_format_n").hide();', false);

        // When this field becomes active for the first time - hit the option we need
        ee()->javascript->output('
			$("#ft_' . rtrim($prefix, '_') . '").one("activate", function() {
				$("#' . $prefix . 'field_pre_populate_' . $pre_populate . '").trigger("click");
			});
		');
    }

    /**
     * Helper methods for our yes/no checkbox rows
     *
     * The row is added to the currently active table instance.
     *
     * @param   array   data array passed to display_settings()
     * @param   string  A prefix to use, typically the field name
     * @return  void
     */
    public function field_show_smileys_row($data, $prefix = false)
    {
        $this->_yes_no_row($data, 'show_smileys', 'field_show_smileys', $prefix);
    }

    /**
     * Helper methods for our yes/no checkbox rows
     *
     * The row is added to the currently active table instance.
     *
     * @deprecated 3.0.0
     * @param   array   data array passed to display_settings()
     * @param   string  A prefix to use, typically the field name
     * @return  void
     */
    public function field_show_spellcheck_row($data, $prefix = false)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('3.0');

        return;
    }

    /**
     * Helper methods for our yes/no checkbox rows
     *
     * The row is added to the currently active table instance.
     *
     * @deprecated 3.0.0
     * @param   array   data array passed to display_settings()
     * @param   string  A prefix to use, typically the field name
     * @return  void
     */
    public function field_show_glossary_row($data, $prefix = false)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('3.0');

        return;
    }

    /**
     * Helper methods for our yes/no checkbox rows
     *
     * The row is added to the currently active table instance.
     *
     * @param   array   data array passed to display_settings()
     * @param   string  A prefix to use, typically the field name
     * @return  void
     */
    public function field_show_file_selector_row($data, $prefix = false)
    {
        $this->_yes_no_row($data, 'show_file_selector', 'field_show_file_selector', $prefix);
    }

    /**
     * Helper methods for our yes/no checkbox rows
     *
     * The row is added to the currently active table instance.
     *
     * @param   array   data array passed to display_settings()
     * @param   string  A prefix to use, typically the field name
     * @return  void
     */
    public function field_show_formatting_btns_row($data, $prefix = false)
    {
        $this->_yes_no_row($data, 'show_formatting_btns', 'field_show_formatting_btns', $prefix);
    }

    /**
     * Helper methods for our yes/no checkbox rows
     *
     * The row is added to the currently active table instance.
     *
     * @deprecated 3.0.0
     * @param   array   data array passed to display_settings()
     * @param   string  A prefix to use, typically the field name
     * @return  void
     */
    public function field_show_writemode_row($data, $prefix = false)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('3.0');

        return;
    }

    /**
     * Helper method to create a yes/no row.
     *
     * The row is added to the currently active table instance.
     *
     * @param   array   data array passed to display_settings()
     * @param   string  Language key to use for the field label
     * @param   string  Name of the setting in the form
     * @param   string  A prefix to use, typically the field name
     * @param   bool    In a grid field? [internal - use grid_yes_no_row()]
     * @return  void
     */
    public function _yes_no_row($data, $lang, $data_key, $prefix = false, $grid = false)
    {
        $prefix = ($prefix) ? $prefix . '_' : '';

        $data = (isset($data[$data_key])) ? $data[$data_key] : '';

        $val_is_y = set_value($prefix . $data_key, $data);
        $val_is_y = ($val_is_y == 'y' or $val_is_y == '1');

        $yes_no_string = form_radio($prefix . $data_key, 'y', $val_is_y, 'id="' . $prefix . $data_key . '_y"') . NBS .
            lang('yes', $prefix . $data_key . '_y') . NBS . NBS . NBS . NBS . NBS .
            form_radio($prefix . $data_key, 'n', (! $val_is_y), 'id="' . $prefix . $data_key . '_n"') . NBS .
            lang('no', $prefix . $data_key . '_n');

        if ($grid) {
            return $this->grid_settings_row(lang($lang), $yes_no_string);
        }

        ee()->table->add_row('<strong>' . lang($lang) . '</strong>', $yes_no_string);
    }

    /**
     * Creates an array of field options
     *
     * Returns an array of field options, either manually populated in the
     * settings or dynamically populated from existing entries.
     *
     * @param   array   $data array passed to display_field()
     * @param   string  optional content to show for a no selection/empty option
     * @return  array   array of field options
     */
    protected function _get_field_options($data, $show_empty = '')
    {
        $field_options = array();

        $pairs = $this->get_setting('value_label_pairs');
        if (! empty($pairs) or $this->get_setting('field_pre_populate') === null) {
            return $pairs;
        } elseif ($this->get_setting('field_pre_populate') === false) {
            if (! is_array($this->settings['field_list_items'])) {
                foreach (explode("\n", $this->settings['field_list_items']) as $v) {
                    $v = trim($v);
                    $field_options[$v] = $v;
                }
            } else {
                $field_options = $this->settings['field_list_items'];
            }
        } elseif ($this->get_setting('field_pre_channel_id') !== 0) {
            $field = 'field_id_' . $this->settings['field_pre_field_id'];

            $data = ee('Model')->get('ChannelEntry')
                ->filter('channel_id', $this->settings['field_pre_channel_id'])
                ->order($field, 'asc')
                ->all()
                ->pluck($field);

            if ($show_empty != '') {
                $field_options[''] = $show_empty;
            }

            foreach ($data as $datum) {
                if (trim($datum) == '') {
                    continue;
                }

                $pretitle = substr($datum, 0, 110);
                $pretitle = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $pretitle);

                $field_options[trim($datum)] = $pretitle;
            }
        }

        return $field_options;
    }

    /**
     * Creates a generic settings row in Grid
     *
     * Same as yes_no_row, but automatically fills in the prefix and grid
     * parameters.
     *
     * @param   string  Language key to use for the field label
     * @param   string  Name of the setting in the form
     * @param   array   data array passed to display_settings()
     * @return  string  Parsed grid row
     */
    public function grid_yes_no_row($label, $name, $data)
    {
        return $this->_yes_no_row($data, $label, $name, false, true);
    }

    /**
     * Creates a generic settings row in Grid
     *
     * @param   string  Left hand label for the row
     * @param   string  Content of the row
     * @param   bool    Wide row?
     * @return  string
     */
    public function grid_settings_row($label, $content, $wide = false)
    {
        $label_class = ($wide)
            ? 'grid_col_setting_label_small_width' : 'grid_col_setting_label_fixed_width';

        return form_label(
            $label,
            null,
            array('class' => $label_class)
        ) . $content;
    }

    /**
     * Creates a dropdown formatted for a Grid columns settings field
     *
     * @return string
     */
    public function grid_dropdown_row($label, $name, $data, $selected = null, $multiple = false, $wide = false, $attributes = null)
    {
        $classes = '';
        $classes .= ($multiple) ? 'grid_settings_multiselect' : 'select';
        $classes .= ($wide) ? ' grid_select_wide' : '';

        $attributes .= 'class="' . $classes . '"';
        $attributes .= ($multiple) ? ' multiple' : '';

        return $this->grid_settings_row(
            $label,
            form_dropdown(
                $name,
                $data,
                $selected,
                $attributes
            ),
            $wide
        );
    }

    /**
     * Creates a checkbox row in a Grid column settings field
     *
     * @return string
     */
    public function grid_checkbox_row($label, $name, $value, $checked)
    {
        return form_label(
            form_checkbox(
                $name,
                $value,
                $checked
            ) . $label
        );
    }

    /**
     * Field formatting row for Grid column settings
     *
     * @return string
     */
    public function grid_field_formatting_row($data)
    {
        return $this->grid_dropdown_row(
            lang('grid_output_format'),
            'field_fmt',
            // TODO: Revisit list of plugin formatting, abstract out
            // existing logic in channel fields API and confirm it's
            // correct, there's a bug report or two about it
            ee()->addons_model->get_plugin_formatting(true),
            (isset($data['field_fmt'])) ? $data['field_fmt'] : 'none'
        );
    }

    /**
     * Text direction row for Grid column settings
     *
     * @param  array  current settings data
     * @return string
     */
    public function grid_text_direction_row($data)
    {
        return $this->grid_dropdown_row(
            lang('grid_text_direction'),
            'field_text_direction',
            array(
                'ltr' => lang('ltr'),
                'rtl' => lang('rtl')
            ),
            (isset($data['field_text_direction'])) ? $data['field_text_direction'] : null
        );
    }

    /**
     * Field max length row for Grid column settings
     *
     * @param  array  current settings data
     * @return string
     */
    public function grid_max_length_row($data)
    {
        return form_label(lang('grid_limit_input')) . NBS . NBS . NBS .
            form_input(array(
                'name' => 'field_maxl',
                'value' => (isset($data['field_maxl'])) ? $data['field_maxl'] : 256,
                'class' => 'grid_input_text_small'
            )) . NBS . NBS . NBS .
            '<i class="instruction_text">' . lang('grid_chars_allowed') . '</i>';
    }

    /**
     * Multiitem row for Grid column settings
     *
     * @param  array  current settings data
     * @return string
     */
    public function grid_multi_item_row($data)
    {
        return form_textarea(array(
            'name' => 'field_list_items',
            'rows' => 10,
            'cols' => 24,
            'value' => isset($data['field_list_items']) ? $data['field_list_items'] : '',
            'class' => 'right'
        )) .
            form_label(lang('multi_list_items')) . '<br>' .
            '<i class="instruction_text">' . lang('field_list_instructions') . '</i>';
    }

    /**
     * Max textarea rows for Grid column settings
     *
     * @param  array  current settings data
     * @param  int    textarea row count [optional]
     * @return string
     */
    public function grid_textarea_max_rows_row($data, $default = 6)
    {
        return form_label(
            lang('textarea_rows'),
            null,
            array('class' => 'grid_col_setting_label_fixed_width')
        ) .
            form_input(array(
                'name' => 'field_ta_rows',
                'size' => 4,
                'value' => isset($data['field_ta_rows']) ? $data['field_ta_rows'] : $default,
                'class' => 'grid_input_text_small'
            ));
    }

    /**
     * Wraps a field in a DIV with a little extra padding rather than a
     * Grid cell's default 5px
     *
     * @return string
     */
    public function grid_padding_container($string)
    {
        return '<div class="grid_padding">' . $string . '</div>';
    }

    /**
     * Wraps a field in a DIV that will ignore default Grid cell padding
     * settings
     *
     * @return string
     */
    public function grid_full_cell_container($string)
    {
        return '<div class="grid_full_cell_container">' . $string . '</div>';
    }

    /**
     * Returns an associative array of channels with their fields
     *
     * @return array An array in the following form:
     *   'channel_title' => array (
     *     '1_1' => 'field_label'
     *   )
     */
    public function get_channel_field_list()
    {
        $channels_options = ee()->cache->get('fieldtype/channel-field-list');

        if ($channels_options !== false) {
            return $channels_options;
        }

        $channels = ee('Model')->get('Channel as C')
            ->with('CustomFields as CF')
            ->fields('C.channel_title', 'C.channel_id', 'CF.field_id', 'CF.field_label', 'CF.field_name', 'CF.field_type')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('channel_title', 'asc')
            ->all();

        $channels_options = array();

        // Make a dummy text fieldtype to get
        $dummy_text = ee('Model')->make('ChannelField');
        $dummy_text->field_type = 'text';
        $text_compatible_fields = $dummy_text->getCompatibleFieldtypes();

        foreach ($channels as $channel) {
            foreach ($channel->getAllCustomFields() as $field) {
                if (isset($text_compatible_fields[$field->field_type])) {
                    $channels_options[$channel->channel_title][$channel->channel_id . '_' . $field->field_id] = htmlentities($field->field_label, ENT_QUOTES, 'UTF-8');
                }
            }
        }

        ee()->cache->save('fieldtype/channel-field-list', $channels_options);

        return $channels_options;
    }

    /**
     * Returns the text format for this field
     */
    protected function get_format()
    {
        $field_fmt = $this->get_setting('field_fmt', 'none');

        // Grid does not allow per-row formats
        if ($this->content_type == 'grid') {
            return $field_fmt;
        }

        return $this->row('field_ft_' . $this->field_id) ?: $field_fmt;
    }

    /**
     * Implements EntryManager\ColumnInterface, but unused
     */
    public function getTableColumnIdentifier()
    {
        return $this->field_name;
    }

    /**
     * Implements EntryManager\ColumnInterface, but unused
     */
    public function getTableColumnLabel()
    {
        return '';
    }

    /**
     * Implements EntryManager\ColumnInterface
     */
    public function renderTableCell($data, $field_id, $entry)
    {
        if (is_null($data)) {
            return '';
        }
        $out = strip_tags($this->replace_tag($data));
        if (strlen($out) > 255) {
            $out = substr($out, 0, min(255, strpos($out, " ", 240))) . '&hellip;';
        }

        return $out;
    }

    /**
     * Implements EntryManager\ColumnInterface
     */
    public function getTableColumnConfig()
    {
        return [];
    }

    public function getEntryManagerColumnModels()
    {
        return [];
    }

    public function getEntryManagerColumnFields()
    {
        return [];
    }

    public function getEntryManagerColumnSortField()
    {
        return '';
    }

    /**
     * Conditional Fields
     */
    public function getPossibleValuesForEvaluation()
    {
        return [];
    }
}
// END EE_Fieldtype class

// EOF
