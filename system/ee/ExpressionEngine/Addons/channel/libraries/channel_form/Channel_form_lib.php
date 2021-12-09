<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
require_once PATH_ADDONS . 'channel/libraries/channel_form/Channel_form_exception.php';

/**
 * Channel Form Library
 */
class Channel_form_lib
{
    public $initialized = false;
    public $form_error = false;
    public $form_loaded = true;
    public $site_id;

    public $categories;
    public $channel;
    public $checkboxes;
    public $custom_field_conditional_names;
    public $custom_fields;
    public $date_fields;
    public $datepicker;
    public $default_fields;
    public $edit;
    public $entry;
    public $error_handling;
    public $errors;
    public $field_errors;
    public $file;
    public $file_fields;
    public $form_validation_methods;
    public $head;
    public $json;
    public $logged_out_member_id;
    public $logged_out_group_id;
    public $native_option_fields;
    public $native_variables;
    public $option_fields;
    public $parse_variables;
    public $post_error_callbacks;
    public $require_save_call;
    public $settings;
    public $skip_xss_fieldtypes;
    public $skip_xss_field_ids;
    public $statuses;
    public $show_fields;
    public $title_fields;
    public $valid_callbacks;

    public $lang;
    public $api_channel_fields;
    public $form_validation;

    protected $_meta = array();

    protected $_file_enctype = false;
    protected $_hidden_fields = array();
    protected $_form_attributes = array();
    protected $_installed_mods = array('smileys' => false, 'spellcheck' => false);

    protected $member;

    private $all_params = array(
        'allow_comments', 'author_only', 'category', 'channel', 'class', 'datepicker',
        'dynamic_title', 'entry_id', 'error_handling', 'id', 'include_jquery',
        'json', 'logged_out_member_id', 'require_entry', 'return', 'return_X',
        'rules', 'rte_selector', 'rte_toolset_id', 'include_assets',
        'secure_action', 'secure_return', 'site', 'url_title', 'use_live_url'
    );

    /**
     * constructor
     *
     * @return  void
     */
    public function __construct()
    {
        //set a global object
        //  ee()->set('safecracker', $this); // deprecated
        ee()->set('channel_form', $this);
        ee()->lang->loadfile('channel_form');
    }

    /**
     * Creates the entry form
     *
     * @return  string
     */
    public function entry_form()
    {
        // -------------------------------------------
        // 'channel_form_entry_form_tagdata_start' hook.
        //  - Developers, if you want to modify the $this object remember
        //  to use a reference on func call.
        // -------------------------------------------

        if (ee()->extensions->active_hook('channel_form_entry_form_absolute_start') === true) {
            ee()->extensions->call('channel_form_entry_form_absolute_start', $this);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        $this->fetch_site(ee()->TMPL->fetch_param('site'));

        $this->initialize(empty($this->form_error));

        ee()->load->helper('form');
        ee()->router->set_class('cp');
        ee()->load->library('cp');
        // ee()->router->set_class('ee');
        ee()->load->library('javascript');
        ee()->load->library('api');
        ee()->load->library('form_validation');
        // ee()->legacy_api->instantiate('channel_fields');

        ee()->lang->loadfile('content');
        ee()->lang->loadfile('upload');

        ee()->javascript->output('var SafeCracker = {}; SafeCracker.markItUpFields = EE.markItUpFields = {};');

        // Figure out what channel we're working with
        $this->fetch_channel(
            ee()->TMPL->fetch_param('channel_id'),
            ee()->TMPL->fetch_param('channel')
        );

        if (! $this->channel) {
            throw new Channel_form_exception(lang('channel_form_no_channel'));
        }

        //temporarily set the site_id for cross-site channel:form
        $current_site_id = ee()->config->item('site_id');

        $this->switch_site($this->site_id);

        $this->fetch_logged_out_member(ee()->TMPL->fetch_param('logged_out_member_id'));

        $member_id = ee()->session->userdata('member_id') ?: $this->logged_out_member_id;
        $this->member = ee('Model')->get('Member', $member_id)
            ->with('PrimaryRole')
            ->first();

        if (! $this->member) {
            $this->switch_site($current_site_id);

            return ee()->TMPL->no_results();
        }

        $assigned_channels = $this->member->getAssignedChannels()->pluck('channel_id');

        // Can they post?
        if (! in_array($this->channel('channel_id'), $assigned_channels) && ! ee('Permission')->isSuperAdmin()) {
            $this->switch_site($current_site_id);

            return ee()->TMPL->no_results();
        }

        // Get the entry data, if an entry was specified
        // the entry object will already exist if this is a submission error
        if (! is_object($this->entry)
            || $this->entry->entry_id != ee()->TMPL->fetch_param('entry_id')
            || $this->entry->Channel->getId() != $this->channel->getId()) {
            $this->fetch_entry(
                ee()->TMPL->fetch_param('entry_id'),
                ee()->TMPL->fetch_param('url_title')
            );
        }

        $this->entry_match_check(array(
            'entry_id' => ee()->TMPL->fetch_param('entry_id'),
            'url_title' => ee()->TMPL->fetch_param('url_title')
        ));

        // require entry?
        if (! $this->entry('entry_id') && $this->bool_string(ee()->TMPL->fetch_param('require_entry'))) {
            if (ee()->TMPL->no_results()) {
                $this->switch_site($current_site_id);

                return ee()->TMPL->no_results();
            }

            throw new Channel_form_exception(lang('channel_form_require_entry'));
        }

        if ($this->entry('entry_id') && ! $this->form_error) {
            $this->edit = true;
        }

        if ($this->edit && $this->bool_string(ee()->TMPL->fetch_param('author_only')) && $this->entry('author_id') != $member_id) {
            if (! ee('Permission')->isSuperAdmin()) {
                throw new Channel_form_exception(lang('channel_form_author_only'));
            }
        }

        $meta = $this->_build_meta_array();

        //add hidden field data
        $this->form_hidden(
            array(
                'ACT' => ee()->functions->fetch_action_id('Channel', 'submit_entry'),
                'meta' => $meta
            )
        );

        unset(ee()->TMPL->tagparams['allow_comments']);

        if (ee()->TMPL->fetch_param('datepicker')) {
            $this->datepicker = $this->bool_string(ee()->TMPL->fetch_param('datepicker'), $this->datepicker);
        }

        //decide which fields to show, based on pipe delimited list of field id's and/or field short names
        if (ee()->TMPL->fetch_param('show_fields')) {
            if (preg_match('/not (.*)/', ee()->TMPL->fetch_param('show_fields'), $match)) {
                foreach ($this->custom_fields as $field_name => $field) {
                    $this->show_fields[] = $field_name;
                }

                foreach (explode('|', $match[1]) as $field_name) {
                    if (is_numeric($field_name)) {
                        $field_name = $this->get_field_name($field_name);
                    }

                    $index = ($field_name !== false) ? array_search($field_name, $this->show_fields) : false;

                    if ($index !== false) {
                        unset($this->show_fields[$index]);
                    }
                }
            } else {
                foreach (explode('|', ee()->TMPL->fetch_param('show_fields')) as $field_name) {
                    if (is_numeric($field_name)) {
                        $field_name = $this->get_field_name($field_name);
                    }

                    if ($field_name) {
                        $this->show_fields[] = $field_name;
                    }
                }
            }
        }

        // -------------------------------------------
        // 'channel_form_entry_form_tagdata_start' hook.
        //  - Developers, if you want to modify the $this object remember
        //  to use a reference on func call.
        // -------------------------------------------

        if (ee()->extensions->active_hook('channel_form_entry_form_tagdata_start') === true) {
            ee()->TMPL->tagdata = ee()->extensions->call('channel_form_entry_form_tagdata_start', ee()->TMPL->tagdata, $this);
            if (ee()->extensions->end_script === true) {
                $this->switch_site($current_site_id);

                return;
            }
        }

        // build custom field variables
        $custom_field_variables = $this->_build_custom_field_variables();

        // Since empty checkbox arrays don't show up in POST at all, we
        // fill them in with their old values to preserve them in case they
        // weren't on screen at all, in the instance someone is using
        // Channel Form to update a partial entry (not including all fields
        // in the form); but for the cases where the checkbox fields are
        // present on screen and are left blank, we need to keep track of
        // which fields those are here so we don't repopulate them with
        // their old values
        $checkbox_fields = array();

        // parse custom fields loop
        if (preg_match('/' . LD . 'custom_fields' . RD . '(.*)' . LD . '\/custom_fields' . RD . '/s', ee()->TMPL->tagdata, $match)) {
            $custom_field_output = '';

            $tagdata = $match[1];

            $formatting_buttons = (strpos($tagdata, LD . 'formatting_buttons' . RD) !== false);

            foreach ($custom_field_variables as $field_name => $custom_field_variables_row) {
                if ($this->show_fields && ! in_array($field_name, $this->show_fields)) {
                    continue;
                }

                if ($formatting_buttons && $custom_field_variables_row['field_show_formatting_btns']) {
                    $this->markitup = true;
                    ee()->javascript->output('EE.markItUpFields["' . $field_name . '"] = ' . $custom_field_variables_row['field_id'] . ';');
                }

                $temp = $tagdata;

                $temp = ee()->functions->prep_conditionals(
                    $tagdata,
                    $custom_field_variables_row
                );

                if (strpos($temp, LD . 'display_field' . RD) !== false) {
                    $custom_field_variables_row['display_field'] = $this->encode_ee_tags(
                        $this->display_field($field_name)
                    );
                }

                foreach ($custom_field_variables_row as $key => $value) {
                    if (is_array($value)) {
                        $temp = $this->swap_var_pair($key, $value, $temp);
                    } elseif (! is_int($value)) {
                        // don't use our conditionals as vars
                        $temp = ee()->TMPL->swap_var_single($key, $value, $temp);
                    }
                }

                if ($custom_field_variables_row['field_type'] === 'catchall') {
                    $temp = $this->replace_tag($field_name, $this->entry($field_name), array(), $temp);
                }

                if ($custom_field_variables_row['field_type'] === 'checkboxes' or $custom_field_variables_row['field_type'] === 'grid') {
                    $checkbox_fields[] = $field_name;
                }

                $custom_field_output .= $temp;
            }

            ee()->TMPL->tagdata = str_replace(
                $match[0],
                $this->encode_ee_tags($custom_field_output),
                ee()->TMPL->tagdata
            );
        }

        if (! empty($this->markitup)) {
            ee()->javascript->output('$.each(EE.markItUpFields,function(a){$("#"+a).markItUp(mySettings);});');
        }

        foreach (ee()->TMPL->var_pair as $tag_pair_open => $tagparams) {
            $tag_name = current(preg_split('/\s/', $tag_pair_open));

            if ($tag_name == 'categories') {
                $checkbox_fields[] = 'category';

                ee()->TMPL->tagdata = $this->swap_var_pair(
                    $tag_pair_open,
                    $this->categories($tagparams),
                    ee()->TMPL->tagdata,
                    $tag_name,
                    ! empty($tagparams['backspace']) ? $tagparams['backspace'] : false
                );
            } elseif ($tag_name == 'statuses') {
                $this->fetch_statuses();

                $this->parse_variables['statuses'] = $this->statuses;
            } elseif (isset($this->custom_fields[$tag_name])) {
                //custom field pair parsing with replace_tag
                if (preg_match_all('/' . LD . preg_quote($tag_pair_open) . RD . '(.*?)' . LD . '\/' . $tag_name . RD . '/s', ee()->TMPL->tagdata, $matches)) {
                    // Map field short name to field_id_x
                    if (array_key_exists($tag_name, $this->custom_fields)) {
                        $field = $this->custom_fields[$tag_name];
                        $name = 'field_id_' . $field->field_id;
                    }

                    foreach ($matches[1] as $match_index => $var_pair_tagdata) {
                        ee()->TMPL->tagdata = str_replace(
                            $matches[0][$match_index],
                            $this->encode_ee_tags(
                                $this->replace_tag($tag_name, $this->entry($name), $tagparams, $var_pair_tagdata)
                            ),
                            ee()->TMPL->tagdata
                        );
                    }
                }
            } elseif (preg_match('/^options:(.*)/', $tag_name, $match) && ($field_type_match = $this->get_field_type($match[1])) &&
            (in_array($field_type_match, $this->option_fields) or $field_type_match == 'relationship')) {
                //options:field_name tag pair parsing
                $checkbox_fields[] = $match[1];

                $this->parse_variables[$match[0]] = (isset($custom_field_variables[$match[1]]['options'])) ? $custom_field_variables[$match[1]]['options'] : '';
            } elseif ($tag_name == 'category_menu') {
                //parse category menu
                $cats = $this->categories($tagparams);
                $tree = array();
                $checkbox_fields[] = 'category';

                foreach ($cats as $cat_value) {
                    $cat_selected = (in_array($cat_value['category_id'], $this->get_selected_cats())) ? ' selected="selected"' : '';
                    $tree[] = '<option value="' . $cat_value['category_id'] . '"' . $cat_selected . '>' . $cat_value['category_name'] . '</option>';
                }

                $this->parse_variables['category_menu'] = array(
                    array('select_options' => implode("\n", $tree))
                );
            } elseif ($tag_name = 'status_menu') {
                //parse status menu
                $this->fetch_statuses();

                $select_options = '';

                foreach ($this->statuses as $status) {
                    $status['selected'] = ($this->entry('status') == $status['status']) ? ' selected="selected"' : '';

                    $status['checked'] = ($this->entry('status') == $status['status']) ? ' checked="checked"' : '';

                    $status['name'] = (in_array($status['status'], array('open', 'closed'))) ? lang($status['status']) : $status['status'];

                    $select_options .= '<option value="' . $status['status'] . '"' . $status['selected'] . '>' . $status['name'] . '</option>' . "\n";
                }

                $this->parse_variables['status_menu'] = array(array('select_options' => $select_options));
            }
        }

        // edit form or post-error submission
        // check to make sure the POST request is meant for this form
        if (! empty($_POST) && ! is_numeric($this->_hidden_fields['ACT'])) {
            $this->_hidden_fields['ACT'] = ee()->functions->insert_action_ids($this->_hidden_fields['ACT']);
        }

        if ($this->edit or ee()->input->post('ACT') == $this->_hidden_fields['ACT']) {
            //not necessary for edit forms
            ee()->TMPL->tagparams['use_live_url'] = 'no';

            $expiration_date = ($this->entry('expiration_date')) ? $this->entry('expiration_date') * 1000 : ee()->localize->now * 1000;
            $comment_expiration_date = ($this->entry('comment_expiration_date')) ? $this->entry('comment_expiration_date') * 1000 : ee()->localize->now * 1000;

            foreach (ee()->TMPL->var_single as $key) {
                if (preg_match('/entry_id_path=([\042\047])?([^\042\047]*)[\042\047]?/', $key, $match)) {
                    $this->parse_variables[$match[0]] = ee()->functions->create_url($match[2] . '/' . $this->entry('entry_id'));
                } elseif (preg_match('/(url_title_path|title_permalink)=[\042\047]?([^\042\047]*)[\042\047]?/', $key, $match)) {
                    $this->parse_variables[$match[0]] = ee()->functions->create_url($match[2] . '/' . $this->entry('url_title'));
                } elseif (preg_match('/^field:(.*)$/', $key, $match)) {
                    // use fieldtype display_field method
                    if ($this->get_field_type($match[1]) == 'checkboxes' ||
                        $this->get_field_type($match[1]) == 'grid' ||
                        $this->get_field_type($match[1]) == 'file_grid') {
                        $checkbox_fields[] = $match[1];
                    }

                    $this->parse_variables[$match[0]] = (array_key_exists($match[1], $this->custom_fields))
                        ? $this->encode_ee_tags($this->display_field($match[1]))
                        : '';
                } elseif (preg_match('/^label:(.*)$/', $key, $match)) {
                    $this->parse_variables[$match[0]] = (array_key_exists($match[1], $this->custom_fields)) ? $this->custom_fields[$match[1]]->field_label : '';
                } elseif (preg_match('/^selected_option:(.*?)(:label)?$/', $key, $match) && ($field_type_match = $this->get_field_type($match[1])) &&
                            (in_array($field_type_match, $this->option_fields) or $field_type_match == 'relationship')) {
                    $options = (isset($custom_field_variables[$match[1]]['options'])) ? $custom_field_variables[$match[1]]['options'] : array();

                    $selected_option = '';

                    foreach ($options as $option) {
                        if ($field_type_match == "rel") {
                            if (! empty($option['selected']) or ! empty($option['checked'])) {
                                $selected_option = (! empty($match[2])) ? $option['option_name'] : $option['option_value'];
                            }
                        } elseif ($option['option_value'] == $this->entry($match[1])) {
                            $selected_option = (! empty($match[2])) ? $option['option_name'] : $option['option_value'];
                        }
                    }

                    $this->parse_variables[$match[0]] = $selected_option;
                } elseif (preg_match('/^instructions:(.*)$/', $key, $match)) {
                    $this->parse_variables[$match[0]] = (array_key_exists($match[1], $this->custom_fields)) ? $this->custom_fields[$match[1]]->field_instructions : '';
                } elseif (preg_match('/^error:(.*)$/', $key, $match)) {
                    $this->parse_variables[$match[0]] = (! empty($this->field_errors[$match[1]])) ? $this->field_errors[$match[1]] : '';
                } else {
                    $name = $key;

                    if (array_key_exists($key, $this->custom_fields)) {
                        $field = $this->custom_fields[$key];
                        $name = 'field_id_' . $field->field_id;
                    }

                    if (in_array($key, $this->date_fields) || $this->get_field_type($name) == 'date') {
                        if ($this->entry($name)) {
                            $date = $this->entry($name);

                            // most likely a failed submission, and $this->entry->getProperty() will not
                            // return the posted string value
                            if (ee()->input->post($name)) {
                                $date = ee()->localize->string_to_timestamp(ee()->input->post($name));
                            }

                            $this->parse_variables[$key] = ee()->localize->human_time($date);

                            // Default dates need a timestamp variable for the datepicker
                            if (in_array($name, array('entry_date', 'expiration_date', 'comment_expiration_date'))) {
                                $timestamp_name = str_replace('date', 'timestamp', $name);
                                $this->parse_variables[$timestamp_name] = $date;
                            }
                        } else {
                            $this->parse_variables[$key] = '';
                        }
                    } elseif (in_array($key, $this->checkboxes)) {
                        $checkbox_fields[] = $key;
                        $this->parse_variables[$key] = ($this->entry($name) == 'y') ? 'checked="checked"' : '';
                    } elseif (property_exists($this->entry, $name) or $this->entry->hasCustomField($name)) {
                        $this->parse_variables[$key] = $this->encode_ee_tags(
                            form_prep($this->entry($name), $name)
                        );
                    }
                }
            }

            $this->form_hidden(
                array(
                    'entry_id' => $this->entry('entry_id'),
                    'author_id' => $this->entry('author_id')
                )
            );
        } elseif ($this->channel('channel_id')) {
            $this->parse_variables['title'] = $this->channel('default_entry_title');
            $this->parse_variables['url_title'] = $this->channel('url_title_prefix');
            $this->parse_variables['allow_comments'] = ($this->channel('deft_comments') == false or $this->channel('comment_system_enabled') == false) ? '' : "checked='checked'";

            $this->form_hidden('unique_url_title', $this->bool_string(ee()->TMPL->fetch_param('unique_url_title')) ? '1' : '');

            if ($this->datepicker) {
                if (strpos(ee()->TMPL->tagdata, 'entry_date') !== false) {
                    $this->parse_variables['entry_date'] = ee()->localize->human_time();
                    $this->parse_variables['entry_timestamp'] = ee()->localize->now;
                }

                if (strpos(ee()->TMPL->tagdata, 'expiration_date') !== false) {
                    $this->parse_variables['expiration_date'] = '';
                    $this->parse_variables['expiration_timestamp'] = '';
                }

                if (strpos(ee()->TMPL->tagdata, 'comment_expiration_date') !== false) {
                    $comment_expiration_date = '';
                    $comment_expiration_timestamp = '';

                    if ($this->channel('comment_expiration') > 0) {
                        $comment_expiration_date = $this->channel('comment_expiration') * (60 * 60 * 24); // days -> seconds
                        $comment_expiration_date = $comment_expiration_date + ee()->localize->now;
                        $comment_expiration_timestamp = $comment_expiration_date;
                        $comment_expiration_date = ee()->localize->human_time($comment_expiration_date);
                    }

                    $this->parse_variables['comment_expiration_date'] = $comment_expiration_date;
                    $this->parse_variables['comment_expiration_timestamp'] = $comment_expiration_timestamp;
                }
            } else {
                $this->parse_variables['entry_date'] = ee()->localize->human_time();
                $this->parse_variables['expiration_date'] = '';
                $this->parse_variables['comment_expiration_date'] = '';
                $this->parse_variables['entry_timestamp'] = ee()->localize->now;
                $this->parse_variables['expiration_timestamp'] = '';
                $this->parse_variables['comment_expiration_timestamp'] = '';
            }

            foreach ($this->custom_fields as $field) {
                foreach (ee()->TMPL->var_pair as $tag_pair_open => $tagparams) {
                    $tag_name = current(preg_split('/\s/', $tag_pair_open));

                    if ($tag_name == $field->field_name) {
                        //special parsing here for catchall fieldtype, pls keep this in
                        if ($field->field_type === 'catchall') {
                            if (preg_match_all('/' . LD . $tag_pair_open . RD . '(.*)' . LD . '\/' . $field->field_name . RD . '/s', ee()->TMPL->tagdata, $matches)) {
                                foreach ($matches[1] as $match_index => $var_pair_tagdata) {
                                    if (preg_match_all('/' . LD . '([^\s]*)' . RD . '(.*)' . LD . '\/' . '\1' . RD . '/s', $var_pair_tagdata, $submatches)) {
                                        foreach ($submatches[2] as $submatch_index => $sub_var_pair_tagdata) {
                                            $var_pair_tagdata = str_replace($submatches[0][$submatch_index], $sub_var_pair_tagdata, $var_pair_tagdata);
                                        }
                                    }

                                    $var_pair_tagdata = preg_replace('/' . LD . '([^\s]*)' . RD . '/s', '', $var_pair_tagdata);

                                    ee()->TMPL->tagdata = str_replace($matches[0][$match_index], $var_pair_tagdata, ee()->TMPL->tagdata);
                                }
                            }
                        } else {
                            $this->parse_variables[$field->field_name] = '';
                        }
                    } elseif ($tag_name == 'options:' . $field->field_name && ($field_type_match = $this->get_field_type($field->field_name)) &&
                            (in_array($field_type_match, $this->option_fields) or $field_type_match == 'relationship')) {
                        if ($field->field_type == 'checkboxes') {
                            $checkbox_fields[] = $field->field_name;
                        }

                        $this->parse_variables['options:' . $field->field_name] = (isset($custom_field_variables[$field->field_name]['options'])) ? $custom_field_variables[$field->field_name]['options'] : '';
                    }
                }

                $this->parse_variables[$field->field_name] = '';
                $this->parse_variables['label:' . $field->field_name] = $field->field_label;
                $this->parse_variables['selected_option:' . $field->field_name . ':label'] = '';
                $this->parse_variables['selected_option:' . $field->field_name] = '';
                $this->parse_variables['label:' . $field->field_name] = $field->field_label;
                $this->parse_variables['instructions:' . $field->field_name] = $field->field_instructions;
                $this->parse_variables['error:' . $field->field_name] = (! empty($this->field_errors[$field->field_name])) ? $this->field_errors[$field->field_name] : '';

                //let's not needlessly call this, otherwise we could get duplicate fields rendering
                if (strpos(ee()->TMPL->tagdata, LD . 'field:' . $field->field_name . RD) !== false) {
                    if ($field->field_type == 'checkboxes' or $field->field_type == 'grid') {
                        $checkbox_fields[] = $field->field_name;
                    }

                    $this->parse_variables['field:' . $field->field_name] = (array_key_exists($field->field_name, $this->custom_fields))
                        ? $this->encode_ee_tags($this->display_field($field->field_name))
                        : '';
                }
            }

            // We need to know if allow_comments field is present in order to figure out defaults
            foreach ($this->checkboxes as $value) {
                if (isset(ee()->TMPL->var_single[$value])) {
                    $checkbox_fields[] = $value;
                }
            }
        }

        $this->form_hidden('checkbox_fields', implode('|', array_unique($checkbox_fields)));

        $conditional_errors = $this->_add_errors();

        // Parse captcha conditional
        $captcha_conditional = array(
            'captcha' => (
                $this->channel('channel_id') &&
                $this->logged_out_member_id &&
                ee('Captcha')->shouldRequireCaptcha()
            )
        );

        if ($captcha_conditional['captcha'] && ee()->config->item('use_recaptcha') == 'y') {
            ee()->TMPL->tagdata = preg_replace("/{if captcha}.+?{\/if}/s", ee('Captcha')->create(), ee()->TMPL->tagdata);
        }

        $conditionals = array_merge($conditional_errors, $captcha_conditional);

        // Parse conditionals
        ee()->TMPL->tagdata = ee()->functions->prep_conditionals(
            ee()->TMPL->tagdata,
            $conditionals
        );

        $this->parse_variables = array_merge($this->parse_variables, $conditional_errors);

        // Make sure {captcha_word} is blank
        ee()->TMPL->tagdata = ee()->TMPL->swap_var_single('captcha_word', '', ee()->TMPL->tagdata);

        // Replace {captcha} with actual captcha
        ee()->TMPL->tagdata = ee()->TMPL->swap_var_single('captcha', ee('Captcha')->create('', $captcha_conditional['captcha']), ee()->TMPL->tagdata);

        // Parse the variables
        if ($this->parse_variables) {
            ee()->TMPL->tagdata = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, array($this->parse_variables));
        }

        if ($this->file) {
            $this->_file_enctype = true;
        }

        //set group-based return url
        $this->form_hidden('return', (ee()->TMPL->fetch_param('return_' . $this->member->PrimaryRole->getId())) ? ee()->TMPL->fetch_param('return_' . $this->member->PrimaryRole->getId()) : ee()->TMPL->fetch_param('return'));

        // build the form

        $RET = ee()->functions->fetch_current_uri();

        $hidden_fields = array(
            'RET' => $RET,
            'URI' => (ee()->uri->uri_string == '') ? 'index' : ee()->uri->uri_string,
            'return_url' => (isset($_POST['return_url'])) ? $_POST['return_url'] : ee()->TMPL->fetch_param('return'),
            'author_id' => $this->member->getId(),
            'channel_id' => $this->channel('channel_id'),
            'entry_id' => 0
        );

        $hidden_fields = array_merge($hidden_fields, $this->_hidden_fields);

        // If uploading a file keep a copy of all hidden vars in the query string
        // This way we can check if POST data is dropped because of size limits
        if ($this->file) {
            $action = $hidden_fields;
            $action_url = $action['RET'];
            $action['ACT'] = ee()->functions->insert_action_ids($action['ACT']);
            $action = $action_url . '?' . http_build_query($action);
        } else {
            $action = $hidden_fields['RET'];
        }

        $this->form_attribute(
            array(
                'onsubmit' => ee()->TMPL->fetch_param('onsubmit'),
                'name' => ee()->TMPL->fetch_param('name'),
                'id' => ee()->TMPL->fetch_param('id'),
                'class' => ($this->bool_string(ee()->TMPL->fetch_param('include_css'), true) ? 'ee-cform ' : '') . ee()->TMPL->fetch_param('class')
            )
        );

        $form_attributes = array(
            'data-ee-version' => APP_VER,
            'hidden_fields' => $hidden_fields,
            'action' => $action,
            'id' => ee()->TMPL->fetch_param('id', 'cform'),
            'enctype' => $this->_file_enctype ? 'enctype="multipart/form-data"' : 'multi'
        );

        $form_attributes = array_merge($form_attributes, $this->_form_attributes);

        $return = ee()->functions->form_declaration($form_attributes);
        $return .= ee()->TMPL->tagdata;
        $return .= "</form>";

        $this->_build_javascript();

        $this->switch_site($current_site_id);

        //make head appear by default
        if (preg_match('/' . LD . 'channel_form_assets' . RD . '/', $return)) {
            $return = ee()->TMPL->swap_var_single('channel_form_assets', $this->head, $return);
        } elseif ($this->bool_string(ee()->TMPL->fetch_param('include_assets'), true) and $this->member) {
            // Head should only be there if the param is there and there is a valid member_id
            $return .= $this->head;
        }

        //added in 1.0.3
        if ($this->bool_string(ee()->TMPL->fetch_param('secure_action'))) {
            $return = preg_replace('/(<form.*?action=")http:/', '\\1https:', $return);
        }

        $return = ee()->functions->insert_action_ids($return);

        // -------------------------------------------
        // 'channel_form_entry_form_tagdata_end' hook.
        //  - Developers, if you want to modify the $this object remember
        //  to use a reference on func call.
        // -------------------------------------------

        if (ee()->extensions->active_hook('channel_form_entry_form_tagdata_end') === true) {
            $return = ee()->extensions->call('channel_form_entry_form_tagdata_end', $return, $this);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        return $return;
    }

    /**
     * Build the javascript output
     *
     */
    private function _build_javascript()
    {
        // Load some helpers, language files & libraries.
        // Doing this after error checking since it makes no sense
        // To load a bunch of things up if we're just going to error
        ee()->lang->loadfile('channel');
        ee()->load->model('admin_model');

        if (! ee()->session->cache(__CLASS__, 'html_buttons')) {
            ee()->session->set_cache(
                __CLASS__,
                'html_buttons',
                ee()->admin_model->get_html_buttons($this->member->getId())
            );
        }

        $html_buttons = ee()->session->cache(__CLASS__, 'html_buttons');
        $button_js = array();

        foreach ($html_buttons->result() as $button) {
            if (strpos($button->classname, 'btn_img') !== false) {
                // no filebrowser -> no img field
                continue;
            } elseif (strpos($button->classname, 'markItUpSeparator') !== false) {
                // separators are purely presentational
                $button_js[] = array('separator' => '---');
            } else {
                $button_js[] = array(
                    'name' => $button->tag_name,
                    'key' => strtoupper($button->accesskey),
                    'openWith' => $button->tag_open,
                    'closeWith' => $button->tag_close,
                    'className' => $button->classname
                );
            }
        }

        $markItUp = array(
            'nameSpace' => "html",
            'onShiftEnter' => array('keepDefault' => false, 'replaceWith' => "<br />\n"),
            'onCtrlEnter' => array('keepDefault' => false, 'openWith' => "\n<p>", 'closeWith' => "</p>\n"),
            'markupSet' => $button_js,
        );

        /* -------------------------------------------
        /*  Hidden Configuration Variable
        /*  - allow_textarea_tabs => Add tab preservation to all textareas or disable completely
        /* -------------------------------------------*/

        if (ee()->config->item('allow_textarea_tabs') == 'y') {
            $markItUp['onTab'] = array('keepDefault' => false, 'replaceWith' => "\t");
        }

        $this->_installed_mods['smileys'] = array_key_exists('Emoticon', ee()->TMPL->module_data);

        // -------------------------------------------
        //  Publish Page Title Focus - makes the title field gain focus when the page is loaded
        //
        //  Hidden Configuration Variable - publish_page_title_focus => Set focus to the tile? (y/n)
        // -------------------------------------------

        $addt_js = array(
            'publish' => array(
                'title_focus' => ((! $this->edit && ee()->config->item('publish_page_title_focus') !== 'n') ? true : false),
                'smileys' => ($this->_installed_mods['smileys']) ? true : false,
                'url_title_prefix' => $this->channel('url_title_prefix'),
                'default_entry_title' => $this->channel('default_entry_title')
            ),
            'user_id' => $this->member->getId(),
            'lang' => array(
                'confirm_exit' => lang('confirm_exit'),
                'add_new_html_button' => lang('add_new_html_button')
            )
        );

        $this->compile_js($addt_js, $markItUp);
    }

    public function compile_js($addt_js = [], $markItUp = [])
    {
        if ($this->datepicker) {
            $addt_js['date']['date_format'] = ee()->localize->get_date_format();
            $addt_js['lang']['date']['months']['full'] = array(
                lang('january'),
                lang('february'),
                lang('march'),
                lang('april'),
                lang('may'),
                lang('june'),
                lang('july'),
                lang('august'),
                lang('september'),
                lang('october'),
                lang('november'),
                lang('december')
            );
            $addt_js['lang']['date']['months']['abbreviated'] = array(
                lang('jan'),
                lang('feb'),
                lang('mar'),
                lang('apr'),
                lang('may'),
                lang('june'),
                lang('july'),
                lang('aug'),
                lang('sept'),
                lang('oct'),
                lang('nov'),
                lang('dec')
            );
            $addt_js['lang']['date']['days'] = array(
                lang('su'),
                lang('mo'),
                lang('tu'),
                lang('we'),
                lang('th'),
                lang('fr'),
                lang('sa'),
            );
        }

        ee()->lang->loadfile('content');

        $this->output_js['json'] = array(
            'EE' => $addt_js,
            'EE.markitup' => new StdClass(),
            'EE.markitup.settings' => $markItUp,
        );

        $include_jquery = ee()->TMPL->fetch_param('include_jquery');

        $this->head .= '<script type="text/javascript" charset="utf-8">// <![CDATA[ ' . "\n";

        foreach ($this->output_js['json'] as $key => $value) {
            if ($key == 'EE') {
                $value['XID'] = '{csrf_token}';
                $value['CSRF_TOKEN'] = '{csrf_token}';

                $this->head .= 'if (typeof EE == "undefined" || ! EE) { ' . "\n" . 'var EE = ' . json_encode($value) . ';}' . "\n";
                $this->head .= <<<GRID_FALLBACK
EE.grid_cache = [];

window.Grid = {
    bind: function() {
        if (typeof(EE.grid_cache) !== 'undefined') {
            EE.grid_cache.push(arguments);
        }
    }
};

window.FluidField = {
    on: function() {}
};
GRID_FALLBACK;
            } else {
                $this->head .= $key . ' = ' . json_encode($value) . ';' . "\n";
            }

            $first = false;
        }

        $this->head .= "\n" . ' // ]]>' . "\n" . '</script>';
        $this->head .= ee()->javascript->get_global();
        $js_file_strings = array();

        $js_defaults = array(
            'file' => array('vendor/underscore'),
            'ui' => array('core', 'widget', 'button', 'dialog'),
            'plugin' => array('markitup'),
        );

        if ($this->datepicker) {
            $js_defaults['file'][] = 'cp/date_picker';
            $js_defaults['file'][] = 'cp/date';
        }

        foreach ($js_defaults as $type => $files) {
            foreach ($files as $file) {
                if (! isset(ee()->cp->js_files[$type])) {
                    ee()->cp->js_files[$type] = array();
                } elseif (is_string(ee()->cp->js_files[$type])) {
                    ee()->cp->js_files[$type] = explode(',', ee()->cp->js_files[$type]);
                }

                if (! in_array($file, ee()->cp->js_files[$type])) {
                    ee()->cp->js_files[$type][] = $file;
                }
            }
        }

        // Array of possible JS and their requirements
        // If the key is a needed JS file, it should be in ee()->cp->js_files
        // and this just handles pulling in files required by the key.

        $ui = array(
            'core' => false,
            'widget' => array('core'),
            'mouse' => array('core', 'widget'),
            'position' => array('core'),
            'draggable' => array('core', 'widget', 'mouse'),
            'droppable' => array('core', 'widget', 'mouse', 'draggable'),
            'resizable' => array('core', 'widget', 'mouse'),
            'selectable' => array('core', 'widget', 'mouse'),
            'sortable' => array('core', 'widget', 'mouse'),
            'accordion' => array('core', 'widget'),
            'autocomplete' => array('core'),
            'button' => array('core', 'widget', 'position'),
            'dialog' => array('core', 'widget', 'mouse', 'position', 'draggable', 'resizable', 'button'),
            'slider' => array('core', 'widget', 'mouse'),
            'tabs' => array('core', 'widget'),
            'datepicker' => array('core'),
            'progressbar' => array('core', 'widget'),
            'effects' => array('core'),
        );

        foreach (ee()->cp->js_files as $type => $files) {
            //let's get the order right
            if ($type == 'ui') {
                $temp = array();

                foreach ($files as $file) {
                    $temp[] = $file;
                    if (is_array($ui[$file])) {
                        $temp = array_merge($ui[$file], $temp);
                    }
                }

                $files = array();

                foreach (array_keys($ui) as $file) {
                    if (in_array($file, $temp)) {
                        $files[] = $file;
                    }
                }
            }

            if (empty($files)) {
                unset(ee()->cp->js_files[$type]);
            } else {
                $mtime[] = ee()->cp->_get_js_mtime($type, $files);
                ee()->cp->js_files[$type] = $files;
                $js_file_strings[$type] = implode(',', $files);
            }
        }

        if (empty($mtime)) {
            $mtime = array(ee()->localize->now);
        }

        $use_live_url = ($this->bool_string(ee()->TMPL->fetch_param('use_live_url'), true)) ? '&use_live_url=y' : '';

        $include_jquery = ($this->bool_string($include_jquery, true)) ? '&include_jquery=y' : '';

        $this->head .= '<script type="text/javascript" charset="utf-8" src="' . ee()->functions->fetch_site_index() . QUERY_MARKER . 'ACT=' . ee()->functions->fetch_action_id('Channel', 'combo_loader') . '&' . str_replace(array('%2C', '%2F'), array(',', '/'), http_build_query($js_file_strings)) . '&v=' . max($mtime) . $use_live_url . $include_jquery . '"></script>' . "\n";

        if ($this->bool_string(ee()->TMPL->fetch_param('include_css'), true)) {
            $this->head .= '<link rel="stylesheet" type="text/css" media="screen" href="' . URL_THEMES . 'cform/css/eecms-cform.min.css" />';
        }

        //add fieldtype styles
        foreach (ee()->cp->get_head() as $item) {
            $this->head .= $item . "\n";
        }

        //add fieldtype scripts
        foreach (ee()->cp->get_foot() as $item) {
            $this->head .= $item . "\n";
        }

        //add loaded JS
        ee()->jquery->_compile();

        if (! empty(ee()->jquery->jquery_code_for_compile)) {
            $script = '$(document).ready(function() {' . "\n";
            $script .= implode('', ee()->jquery->jquery_code_for_compile);
            $script .= '});';
            $script = preg_replace('/\s*eeSpell\.init\(\);\s*/', '', $script);

            $this->head .= ee()->javascript->inline($script);

            ee()->jquery->jquery_code_for_compile = array();
        }
    }

    /**
     * Create the custom field variables rows
     *
     * @return  array custom field variables
     */
    private function _build_custom_field_variables()
    {
        $custom_field_variables = array();

        $fields_by_shortname = array();
        $fields = $this->entry->getDisplay()->getFields();

        foreach ($fields as $field) {
            $fields_by_shortname[$field->getShortName()] = $field;
        }

        foreach ($fields_by_shortname as $field_name => $field) {
            if (! array_key_exists($field_name, $this->custom_fields)) {
                continue;
            }

            // standard vars/conditionals
            $custom_field_variables_row = array(
                'required' => $field->isRequired(),
                'text_direction' => $field->getSetting('field_text_direction'),
                'field_data' => $this->entry($field->getName()),
                'rows' => $field->getSetting('field_ta_rows'),
                'maxlength' => $field->getSetting('field_maxl'),
                'formatting_buttons' => '',
                'field_show_formatting_btns' => ($field->getSetting('field_show_formatting_btns') == 'y') ? 1 : 0,
                'textinput' => 0,
                'pulldown' => 0,
                'checkbox' => 0,
                'relationship' => 0,
                'relationships' => 0,
                'multiselect' => 0,
                'date' => 0,
                'radio' => 0,
                'display_field' => '',
                'options' => $this->get_field_options($field_name),
                'error' => (! empty($this->field_errors[$field->getName()])) ? lang($this->field_errors[$field->getName()]) : ''
            );

            $custom_field_equivalent = $this->custom_fields[$field_name];
            $custom_field_variables_row = array_merge($custom_field_equivalent->getValues(), $custom_field_variables_row);

            $fieldtypes = ee()->api_channel_fields->fetch_installed_fieldtypes();

            //add a negative conditional based on fieldtype
            foreach ($fieldtypes as $type => $fieldtype) {
                $custom_field_variables_row[$type] = 0;
            }

            // fieldtype conditionals
            $custom_field_variables_row[$field->getType()] = 1;

            if (array_key_exists($field->getType(), $this->custom_field_conditional_names)) {
                $custom_field_variables_row[$this->custom_field_conditional_names[$field->getType()]] = 1;
            }

            if ($field->getType() == 'date') {
                $custom_field_variables_row['field_data'] = ee()->localize->human_time($this->entry($field_name));
            }

            if ($field->getType() == 'relationship') {
                $settings = $this->get_field_settings($field_name);
                $custom_field_variables_row['allow_multiple'] = 0;
                if (isset($settings['allow_multiple'])) {
                    $custom_field_variables_row['allow_multiple'] = ($settings['allow_multiple'] == 0) ? 0 : 1;
                }

                // Also listen for plural form of relationship in conditionals
                // because the field is called "Relationships"
                $custom_field_variables_row['relationships'] = 1;
            }

            $custom_field_variables[$field_name] = $custom_field_variables_row;
        }

        return $custom_field_variables;
    }

    /**
     * Add global and field errors
     *
     * @return  array conditional errors
     */
    private function _add_errors()
    {
        $conditional_errors = array();

        foreach ($this->title_fields as $field) {
            if (isset(ee()->TMPL->var_single['error:' . $field])) {
                $conditional_errors['error:' . $field] = (! empty($this->field_errors[$field])) ? $this->field_errors[$field] : '';
            }
        }

        // Add global errors
        if (count($this->errors) === 0) {
            $conditional_errors['global_errors'] = array(array());
        } else {
            $conditional_errors['global_errors'] = array();

            foreach ($this->errors as $error) {
                $conditional_errors['global_errors'][] = array('error' => $error);
            }
        }

        $conditional_errors['global_errors:count'] = count($this->errors);

        // Add field errors
        if (count($this->field_errors) === 0) {
            $conditional_errors['field_errors'] = array(array());
        } else {
            $conditional_errors['field_errors'] = array();

            foreach ($this->field_errors as $field => $error) {
                $conditional_errors['field_errors'][] = array('field' => $field, 'error' => $error);
            }
        }

        $conditional_errors['field_errors:count'] = count($this->field_errors);

        // Add field errors to conditional parsing
        if (! empty($conditional_errors['field_errors'][0])) {
            foreach ($conditional_errors['field_errors'] as $error) {
                $conditional_errors['error:' . $error['field']] = $error['error'];
            }
        }

        return $conditional_errors;
    }

    /**
     * Creates or edits an entry
     *
     * @return  void
     */
    public function submit_entry()
    {
        $this->initialize();

        // Get hidden meta vars
        if (! isset($_POST['meta'])) {
            if (isset($_GET['meta'])) {
                // If $_POST is empty that means we exceeded PHP's post_max_size
                $_POST['meta'] = $_GET['meta'];
                $dropped = true;
            } else {
                // This should never be valid
                return;
            }
        } else {
            $dropped = false;
        }

        $this->_get_meta_vars();

        $this->fetch_site(false, $this->_meta['site_id']);

        $this->fetch_channel($this->_meta['channel_id']);

        ee()->load->helper(array('url', 'form'));
        ee()->load->library(array('api', 'file_field'));
        //ee()->legacy_api->instantiate('channel_fields');
        ee()->load->library('filemanager');
        ee()->load->library('form_validation');
        ee()->load->library('localize');
        ee()->load->model(array('field_model', 'tools_model'));

        ee()->filemanager->_initialize(array());

        ee()->lang->loadfile('content');

        ee()->router->set_class('cp');
        ee()->load->library('cp');
        ee()->router->set_class('ee');

        $rules = $this->_meta['rules'];

        //just to prevent any errors
        if (! defined('BASE')) {
            $s = (ee()->config->item('cp_session_type') != 'c') ? ee()->session->userdata('session_id') : 0;
            define('BASE', EESELF . '?S=' . $s . '&amp;D=cp');
        }

        $this->json = $this->_meta['json'];
        $this->error_handling = $this->_meta['error_handling'];

        // -------------------------------------------
        // 'channel_form_submit_entry_start' hook.
        //  - Developers, if you want to modify the $this object remember
        //  to use a reference on func call.
        // -------------------------------------------

        if (ee()->extensions->active_hook('channel_form_submit_entry_start') === true) {
            ee()->extensions->call('channel_form_submit_entry_start', $this);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        $logged_out_member_id = false;

        if (! ee()->session->userdata('member_id') && $this->_meta['logged_out_member_id']) {
            if ($logged_out_member_id = $this->_meta['logged_out_member_id']) {
                $this->fetch_logged_out_member($logged_out_member_id);
            }
        } elseif ($this->channel('channel_id') && ! ee()->session->userdata('member_id') && ! empty($this->settings['default_author'][$this->_meta['site_id']][$this->channel('channel_id')])) {
            $this->fetch_logged_out_member($this->settings['default_author'][$this->_meta['site_id']][$this->channel('channel_id')]);
        }

        $member_id = ee()->session->userdata('member_id') ?: $this->logged_out_member_id;
        $this->member = ee('Model')->get('Member', $member_id)->first();

        if (! $this->member) {
            throw new Channel_form_exception(lang('channel_form_invalid_author'));
        }

        //captcha check
        $captcha_required = false;

        if (ee('Captcha')->shouldRequireCaptcha()) {
            $captcha_required = true;

            if (! ee()->input->post('captcha')) {
                $this->field_errors['captcha_word'] = ee()->config->item('use_recaptcha') == 'y' ? ee()->lang->line('recaptcha_required') : ee()->lang->line('captcha_required');
            } else {
                ee()->db->where('word', ee()->input->post('captcha', true));
                ee()->db->where('ip_address', ee()->input->ip_address());
                ee()->db->where('date > ', '(UNIX_TIMESTAMP()-7200)', false);

                if (! ee()->db->count_all_results('captcha')) {
                    $this->field_errors['captcha_word'] = ee()->config->item('use_recaptcha') == 'y' ? ee()->lang->line('recaptcha_required') : ee()->lang->line('captcha_incorrect');
                }

                ee()->db->where('word', ee()->input->post('captcha', true));
                ee()->db->where('ip_address', ee()->input->ip_address());
                ee()->db->where('date < ', '(UNIX_TIMESTAMP()-7200)', false);

                ee()->db->delete('captcha');
            }
        }

        // Status Check to prevent post overrides
        $status = ee()->input->post('status');

        if ($status) {
            $valid_status = false;
            $this->fetch_statuses();

            foreach ($this->statuses as $status_array) {
                if ($status == $status_array['status']) {
                    $valid_status = true;

                    break;
                }
            }

            if (! $valid_status) {
                unset($_POST['status']);
            }
        }

        if ($this->_meta['entry_id']) {
            $this->edit = true;

            $this->fetch_entry($this->_meta['entry_id']);

            // Check for author_only setting
            if ((isset($this->_meta['author_only']) && $this->_meta['author_only'] != false) &&
                $this->entry('author_id') != $this->member->getId()) {
                throw new Channel_form_exception(lang('channel_form_author_only'));
            }
        } else {
            $this->fetch_entry(0);
        }

        ee()->legacy_api->instantiate('channel_fields');

        // If any checkbox fields are missing from the POST array,
        // add them in as blank values for form validation to catch
        if (isset($_POST['checkbox_fields'])) {
            foreach (explode('|', $_POST['checkbox_fields']) as $checkbox) {
                if (! isset($_POST[$checkbox])) {
                    if ($checkbox == 'allow_comments') {
                        $_POST[$checkbox] = 'n';

                        continue;
                    }

                    $_POST[$checkbox] = '';
                }
            }
        }

        // If allow_comments is NOT included in the form and it's not set by parameter, use the default
        if ($this->edit != true && ! isset($_POST['allow_comments']) && $this->_meta['allow_comments'] == false) {
            $_POST['allow_comments'] = ($this->channel('deft_comments') == true && $this->channel('comment_system_enabled') == true) ? 'y' : 'n';
        }

        $spam_content = "";

        // Reset categories if they weren't set above
        if ($this->_meta['entry_id'] &&
            ee()->input->post('category') === false &&
            count($this->entry->Categories)) {
            $_POST['category'] = $this->entry->Categories->pluck('cat_id');
        }

        foreach ($this->custom_fields as $i => $field) {
            if (in_array($field->field_type, $this->file_fields)) {
                // trick validation into calling the file fieldtype
                if (isset($_FILES[$field->field_name]['name'])) {
                    $img = ee()->file_field->validate($_FILES[$field->field_name]['name'], $field->field_name);

                    if (isset($img['value'])) {
                        $_POST[$field->field_name] = $img['value'];
                    } else {
                        $_POST[$field->field_name] = '';
                        $this->field_errors[$field->field_name] = strip_tags($img);
                    }
                }
            }

            $isset = (
                isset($_POST['field_id_' . $field->field_id]) ||
                isset($_POST[$field->field_name]) ||
                // always call the fieldtype if a file field was on the page
                isset($_POST[$field->field_name . '_hidden_file'])
            );

            if ($isset) {
                $field_rules = array();

                if (isset($rules[$field->field_name])) {
                    $field_rules = explode('|', $rules[$field->field_name]);
                }

                ee()->form_validation->set_rules($field->field_name, $field->field_label, implode('|', $field_rules));
            }

            foreach ($_POST as $key => $value) {
                //change field_name'd POSTed keys to field_id's
                if ($key == $field->field_name) {
                    //@TODO what to do about xss_clean and "naughty" html
                    //for now you can crack open this file and manually add fields_ids and/or field types to the respective arrays
                    //to prevent xss_clean
                    //i had some people complain about not being able to submit <object>'s
                    $xss_clean = (! in_array($field->field_id, $this->skip_xss_field_ids) && ! in_array($field->field_type, $this->skip_xss_fieldtypes));

                    $_POST['field_id_' . $field->field_id] = ee()->input->post($key, $xss_clean);

                    if (! is_array($_POST[$key])) {
                        $spam_content .= " " . ee()->input->post($key, true);
                    }

                    //auto set format if not POSTed
                    $fmt = $field->field_fmt;

                    if (ee()->input->post('field_ft_' . $field->field_id) !== false) {
                        $fmt = ee()->input->post('field_ft_' . $field->field_id, true);
                    } elseif (ee()->input->post($field->field_name . '_ft') !== false) {
                        $fmt = ee()->input->post($field->field_name . '_ft', true);
                    }

                    $_POST['field_ft_' . $field->field_id] = $fmt;
                } elseif (preg_match('/^' . $field->field_name . '_(.+)/', $key, $match)) {
                    //also change utility POST fields, ie my_field_field_directory to field_id_X_directory
                    $_POST['field_id_' . $field->field_id . '_' . $match[1]] = ee()->input->post($key, true);
                }
            }
        }

        foreach ($this->title_fields as $field) {
            // Disable default checks if $_POST was dropped
            // The only thing we can validate is filesize
            if (isset($this->default_fields[$field]) && ! $dropped) {
                ee()->api_channel_fields->set_settings($field, $this->default_fields[$field]);
            }

            if (ee()->input->post($field) !== false) {
                $_POST[$field] = ee()->input->post($field, true);
            } else {
                if ($field == 'entry_date') {
                    if ($this->entry($field)) {
                        $_POST[$field] = ee()->localize->human_time($this->entry($field));
                    } else {
                        $_POST[$field] = ee()->localize->human_time();
                    }
                } elseif ($field == 'recent_comment_date') {
                    // Prevent a DateTime object from going into POST
                    if ($this->entry($field) && $this->entry($field)->getTimestamp() !== 1) {
                        $_POST[$field] = $this->entry($field)->getTimestamp();
                    } else {
                        $_POST[$field] = 0;
                    }
                } elseif ($field == 'versioning_enabled' and $this->channel('enable_versioning') == 'y') {
                    $_POST[$field] = 'y';
                } else {
                    if ($this->entry($field) !== false) {
                        $_POST[$field] = $this->entry($field);
                    }
                }
            }
        }

        //don't override status on edit, only on publish
        if (! $this->edit && ! empty($this->settings['default_status'][ee()->config->item('site_id')][ee()->input->post('channel_id')])) {
            $_POST['status'] = $this->settings['default_status'][ee()->config->item('site_id')][$this->_meta['channel_id']];
        }

        if (! $this->edit && is_array($this->_meta['category'])) {
            $_POST['category'] = $this->_meta['category'];
        }

        $_POST['revision_post'] = $_POST;

        $this->_member_group_override();

        //added for EE2.1.2
        ee()->legacy_api->instantiate('channel_categories');
        ee()->load->library('api/api_channel_form_channel_entries');

        foreach ($this->form_validation_methods as $method) {
            ee()->form_validation->set_message($method, lang('channel_form_' . $method));
        }

        if ($this->_meta['dynamic_title']) {
            $dynamic_title = $this->_meta['dynamic_title'];

            foreach ($_POST as $key => $value) {
                if (is_string($value) && strstr($dynamic_title, '[' . $key . ']') !== false) {
                    $dynamic_title = str_replace('[' . $key . ']', $value, $dynamic_title);
                }
            }

            $_POST['title'] = $dynamic_title;
        }

        foreach (ee()->api_channel_fields->settings as $field_id => $settings) {
            if (isset($settings['field_settings'])) {
                $settings = array_merge($settings, $this->unserialize($settings['field_settings'], true));
            }

            ee()->api_channel_fields->settings[$field_id] = $settings;
        }

        // validate the custom validation parameters with the old validation library
        if (! ee()->form_validation->run()) {
            $errors = ee()->form_validation->_error_array;

            if (! is_array($this->field_errors)) {
                $this->field_errors = array();
            }

            foreach ($errors as $key => $message) {
                $this->field_errors[$key] = $message;
            }
        }

        $id_to_name_map = array();

        // CI's form validation rules can either throw an error, or be used as
        // prepping functions. This is also the case for custom fields. Since our
        // rules were set on the field short name and the channel entries api uses
        // the field_id_# value, we need to sync up our data.
        foreach ($this->custom_fields as $i => $field) {
            $field_id = 'field_id_' . $field->field_id;
            $field_name = $field->field_name;

            $id_to_name_map[$field_id] = $field_name;

            if (isset($_POST[$field_id]) && isset($_POST[$field_name])) {
                $_POST[$field_id] = $_POST[$field_name];
            }
        }

        if (! isset($_POST['url_title'])) {
            $_POST['url_title'] = ee('Format')->make('Text', ee()->input->post('title', true))->urlSlug()->compile();
        }

        if (ee()->input->post('unique_url_title', true)) {
            $url_title = $_POST['url_title'];

            // Max URL title length, minus uniqid length, minus separator
            $url_title = substr($url_title, 0, URL_TITLE_MAX_LENGTH - 23 - 1);

            $separator = (ee()->config->item('word_separator') == 'dash') ? '-' : '_';

            $_POST['url_title'] = uniqid($url_title . $separator, true);

            $this->_meta['url_title'] = $_POST['url_title'];
        }

        //temporarily change site_id for cross-site forms
        //channel_entries api doesn't allow you to specifically set site_id
        $current_site_id = ee()->config->item('site_id');

        $this->switch_site($this->site_id);

        // Structure category data the way the ChannelEntry model expects it
        $cat_groups = explode('|', $this->entry->Channel->cat_group);
        if (! empty($cat_groups) && isset($_POST['category'])) {
            $_POST['categories'] = array('cat_group_id_' . $cat_groups[0] => (is_array($_POST['category'])) ? $_POST['category'] : [$_POST['category']]);
        }

        if (in_array($this->channel('channel_id'), $this->member->getAssignedChannels()->pluck('channel_id')) or ee('Permission')->isSuperAdmin()) {
            $entry_data = array_filter(
                $_POST,
                function ($v) {
                    return ! is_null($v);
                }
            );

            $this->entry->set($entry_data);
            $this->entry->edit_date = ee()->localize->now;

            if (! isset($_POST['category']) or empty($_POST['category'])) {
                $this->entry->Categories = null;
            }

            $result = $this->entry->validate();

            if (empty($this->field_errors) && empty($this->errors) && $result->isValid()) {
                // Lastly we check for spam before saving a new entry
                if (! $this->entry('entry_id')) {
                    if (ee('Permission')->isSuperAdmin() || ! ee('Spam')->isSpam($spam_content)) {
                        $this->entry->save();
                    } else {
                        ee('Spam')->moderate('channel', $this->entry, $spam_content, $entry_data);
                    }
                } else {
                    $this->entry->save();
                }
            } else {
                $errors = $result->getAllErrors();

                // only show the first error for each field to match CI's old behavior
                $current_errors = array_map('current', $errors);
                $this->field_errors = array_merge($this->field_errors, $current_errors);
            }
        } else {
            $this->errors[] = lang('unauthorized_for_this_channel');
        }

        $this->switch_site($current_site_id);

        $new_id = $this->entry('entry_id');
        $this->clear_entry();

        //load the just created entry into memory
        $this->fetch_entry($new_id);

        if ($captcha_required && $this->error_handling == 'inline') {
            $this->field_errors = array_merge($this->field_errors, array('captcha_word' => (ee()->config->item('use_recaptcha') == 'y' ? ee()->lang->line('recaptcha_required') : ee()->lang->line('captcha_required'))));
        }

        foreach ($this->field_errors as $field => $error) {
            if (isset($id_to_name_map[$field])) {
                $this->field_errors[$id_to_name_map[$field]] = $error;
            }
        }

        // Reset their group_id back to 0
        $this->_member_group_override(true);

        // -------------------------------------------
        // 'channel_form_submit_entry_end' hook.
        //  - Developers, if you want to modify the $this object remember
        //  to use a reference on func call.
        // -------------------------------------------

        if (ee()->extensions->active_hook('channel_form_submit_entry_end') === true) {
            ee()->extensions->call('channel_form_submit_entry_end', $this);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        if (is_array($this->errors)) {
            //add the field name to custom_field_empty errors
            foreach ($this->errors as $field_name => $error) {
                if ($error == lang('custom_field_empty')) {
                    $this->errors[$field_name] = $error . ' ' . $field_name;
                }
            }
        }

        if (! $this->json && ($this->errors || $this->field_errors) && $this->error_handling == 'inline') {
            $this->entry->set($_POST);

            $this->form_error = true;

            foreach ($this->post_error_callbacks as $field_type => $callbacks) {
                $callbacks = explode('|', $callbacks);

                foreach ($this->custom_fields as $field) {
                    if ($field->field_type == $field_type) {
                        foreach ($callbacks as $callback) {
                            if (in_array($callback, $this->valid_callbacks)) {
                                $this->entry[$field->field_name] = $this->entry['field_id_' . $field->field_id] = call_user_func($callback, $this->entry($field->field_name));
                            }
                        }
                    }
                }
            }

            ee()->core->generate_page();

            return;
        }

        if ($this->json) {
            return $this->send_ajax_response(
                array(
                    'success' => (empty($this->errors) && empty($this->field_errors)) ? 1 : 0,
                    'errors' => (empty($this->errors)) ? array() : $this->errors,
                    'field_errors' => (empty($this->field_errors)) ? array() : $this->field_errors,
                    'entry_id' => $this->entry('entry_id'),
                    'url_title' => $this->entry('url_title'),
                    'channel_id' => $this->entry('channel_id'),
                )
            );
        }

        if ($this->errors or $this->field_errors) {
            $field_errors = array();

            foreach ($this->field_errors as $field => $error) {
                $label = lang($field);

                if ($this->entry->hasCustomField($field)) {
                    $label = $this->entry->getCustomField($field)->getItem('field_label');
                }

                $field_errors[] = "<b>{$label}: </b>{$error}";
            }

            throw new Channel_form_exception(
                array_merge($this->errors, $field_errors)
            );
        }

        $return = ($this->_meta['return'])
            ? (
                (strpos($this->_meta['return'], 'http://') === 0 || strpos($this->_meta['return'], 'https://') === 0) ? $this->_meta['return'] : ee()->functions->create_url($this->_meta['return'])
            )
            : ee()->functions->fetch_site_index();

        if (strpos($return, 'ENTRY_ID') !== false) {
            $return = str_replace('ENTRY_ID', $this->entry('entry_id'), $return);
        }

        if (strpos($return, 'URL_TITLE') !== false) {
            $return = str_replace('URL_TITLE', $this->entry('url_title'), $return);
        }

        if (strpos($return, 'AUTHOR_ID') !== false) {
            $return = str_replace('AUTHOR_ID', $this->entry('author_id'), $return);
        }

        if ($hook_return = ee()->api_channel_form_channel_entries->trigger_hook('entry_submission_redirect', $return)) {
            $return = $hook_return;
        }

        if ($this->_meta['secure_return']) {
            $return = preg_replace('/^http:/', 'https:', $return);
        }

        ee()->functions->redirect($return);
    }

    /**
     * Converts text-based template parameter to boolean
     *
     * @param   string $string
     * @param   bool $default = FALSE
     * @return  bool
     */
    public function bool_string($string, $default = false)
    {
        if (preg_match('/true|t|yes|y|on|1/i', $string)) {
            return true;
        }

        if (preg_match('/false|f|no|n|off|0/i', $string)) {
            return false;
        }

        return $default;
    }

    /**
     * Filters and sorts the categories
     *
     * @param   array $params
     * @return  array
     */
    public function categories($params)
    {
        $this->fetch_categories();

        ee()->load->library('channel_form/channel_form_data_sorter');

        if (! $categories = $this->categories) {
            return array();
        }

        if (! $params) {
            return $categories;
        }

        if (! empty($params['group_id'])) {
            $params['show_group'] = $params['group_id'];

            ee()->load->library('logger');
            ee()->logger->deprecate_template_tag(
                'Using group_id in Channel Form {categories} tag pairs is deprecated. Please use {categories show_group="..."} instead.',
                "/({exp:channel:form.*)({categories(.*?)group_id=(.*?)})(.*)/uis",
                "$1{categories$3show_group=$4}$5"
            );
        }

        if (! empty($params['show'])) {
            ee()->channel_form_data_sorter->filter($categories, 'category_id', $params['show'], 'in_array');
        }

        if (! empty($params['show_group'])) {
            ee()->channel_form_data_sorter->filter($categories, 'category_group_id', $params['show_group'], 'in_array');
        }

        if (! empty($params['order_by'])) {
            ee()->channel_form_data_sorter->sort($categories, $params['order_by'], @$params['sort']);
        }

        //reset array indices
        return array_merge($categories);
    }

    /**
     * Retrieves current channel data
     *
     * @param   mixed $key
     * @return  mixed
     */
    public function channel($key)
    {
        return $this->channel->getProperty($key);
    }

    /**
     * Clears the library's entry
     *
     * @return  void
     */
    public function clear_entry()
    {
        $this->entry = false;
    }

    /**
     * Display a custom field
     *
     * @param   mixed $field_name
     * @return  void
     */
    public function display_field($field_name)
    {
        ee()->load->library('api');
        ee()->load->library('javascript');
        ee()->load->helper('custom_field');

        $field_id = $this->get_field_id($field_name);

        if (isset($this->extra_js[$this->get_field_type($field_name)])) {
            ee()->javascript->output($this->extra_js[$this->get_field_type($field_name)]);
        }

        ee()->legacy_api->instantiate('channel_fields');

        $fieldtype = ee()->api_channel_fields->setup_handler($this->get_field_type($field_name), true);

        $fieldtype->_init(
            array(
                'field_id' => $field_id,
                'field_name' => $field_name,
                'content_id' => $this->entry('entry_id'),
                'content_type' => 'channel'
            )
        );

        $fieldtype->settings = array_merge(
            $this->get_field_settings($field_name),
            $this->get_field_data($field_name),
            ee()->api_channel_fields->get_global_settings(ee()->api_channel_fields->field_type)
        );

        $_GET['entry_id'] = $this->entry('entry_id');
        $_GET['channel_id'] = $this->entry('channel_id');

        return ee()->api_channel_fields->apply('display_field', array('data' => $this->entry('field_id_' . $field_id)));
    }

    /**
     * Retrieves current entry data
     *
     * @param   mixed $key
     * @return  void
     */
    public function entry($key)
    {
        if (isset($this->entry)) {
            return $this->entry->getProperty($key);
        }
    }

    /**
     * Encode EE tags in field contents. Channel Form may output module tags or
     * other when they're in the data of an entry, and since they will be output
     * during module parsing, the template engine will parse any module code
     * that is output. So we'll encode EE tags with a special marker that we can
     * reverse after template parsing has completed so the original field
     * contents aren't altered on edit.
     *
     * @param string $string Field data
     * @return string Encoded string
     */
    private function encode_ee_tags($string)
    {
        return str_replace([LD, RD], ['CFORM-ENCODE-LEFT-BRACKET', 'CFORM-ENCODE-RIGHT-BRACKET'], $string);
    }

    /**
     * Get selected categories
     *
     * @return  array
     */
    public function get_selected_cats()
    {
        $selected = array();

        if ($this->entry->entry_id or ! empty($this->channel->deft_category)) {
            $selected = $this->entry->Categories->pluck('cat_id');
        }

        return $selected;
    }

    /**
     * Load categories
     *
     * @return  void
     */
    public function fetch_categories()
    {
        //exit if already loaded, or if there is no category group
        if ($this->categories || ! $this->channel('cat_group')) {
            return;
        }

        $selected = $this->get_selected_cats();

        // Load up the library and figure out what belongs and what's selected
        ee()->load->library(array('api', 'file_field'));
        ee()->legacy_api->instantiate('channel_categories');
        $category_list = ee()->api_channel_categories->category_tree(
            $this->channel('cat_group'),
            $selected
        );

        $categories = array();

        foreach ($category_list as $category_id => $category_info) {
            // Indent category names
            if ($category_info[5] > 1) {
                $category_info[1] = str_repeat(NBS . NBS . NBS . NBS, $category_info[5] - 1) . $category_info[1];
            }

            $selected = ($category_info[4] === true) ? ' selected="selected"' : '';
            $checked = ($category_info[4] === true) ? ' checked="checked"' : '';

            $category_image = ee()->file_field->parse_field($category_info[7]);

            // Translate response from API to something parse variables can understand
            $categories[$category_id] = array(
                'category_id' => $category_info[0],
                'category_name' => $category_info[1],
                'category_group_id' => $category_info[2],
                'category_group' => $category_info[3],
                'category_parent' => $category_info[6],
                'category_depth' => $category_info[5],
                'category_image' => (isset($category_image['url'])) ? $category_image['url'] : '',
                'category_description' => $category_info[8],
                'selected' => $selected,
                'checked' => $checked
            );
        }

        $this->categories = $categories;
    }

    /**
     * Load channel
     *
     * @param   int $channel_id
     * @param   mixed $channel_name
     * @param   mixed $entry_id
     * @param   mixed $url_title = FALSE
     * @return  void
     */
    public function fetch_channel($channel_id, $channel_name = false)
    {
        //If two forms are on the same template, $this->channel needs to be redefined

        $query = ee('Model')->get('Channel')
            ->with('ChannelFormSettings');

        if ($channel_id) {
            $query->filter('channel_id', $channel_id);
        } elseif ($channel_name) {
            $query->filter('channel_name', $channel_name);
        } else {
            throw new Channel_form_exception(lang('channel_form_no_channel'));
        }

        //get field group and limit
        $query->filter('Channel.site_id', $this->site_id);

        $channel = $query->first();

        if (! isset($channel)) {
            throw new Channel_form_exception(lang('channel_form_unknown_channel'));
        }

        $this->channel = $channel;

        if (! empty(ee()->TMPL)) {
            ee()->TMPL->tagparams['channel'] = $this->channel('channel_name');
        }

        $this->fetch_custom_fields();
    }

    /**
     * Load custom fields
     *
     * @return  void
     */
    public function fetch_custom_fields()
    {
        $fields = $this->channel->getAllCustomFields();

        foreach ($fields as $field) {
            $this->custom_fields[$field->field_name] = $field;
            $this->custom_field_names[$field->field_id] = $field->field_name;

            if (in_array($field->field_type, $this->file_fields)) {
                $this->file = true;
            }
        }
    }

    /**
     * Load entry
     *
     * @param   mixed $entry_id
     * @param   mixed $url_title
     * @return  void
     */
    public function fetch_entry($entry_id, $url_title = false)
    {
        if (! $entry_id && ! $url_title) {
            $this->entry = ee('Model')->make('ChannelEntry');
            $this->entry->Channel = $this->channel;

            $this->entry->ip_address = ee()->session->userdata['ip_address'];

            // Assign defaults based on the channel
            $this->entry->title = $this->channel->default_entry_title;
            $this->entry->versioning_enabled = $this->channel->enable_versioning;
            $this->entry->status = $this->channel->deft_status;
            $this->entry->author_id = ee()->session->userdata('member_id');

            if (! empty($this->channel->deft_category)) {
                $cat = ee('Model')->get('Category', $this->channel->deft_category)->first();

                if ($cat) {
                    // set directly so other categories don't get lazy loaded
                    // along with our default
                    $this->entry->Categories = $cat;
                }
            }

            // Assign defaults based on the ChannelFormSettings
            if ($this->channel->ChannelFormSettings) {
                $this->entry->status = ($this->channel->ChannelFormSettings->default_status) ?: $this->channel->deft_status;

                // only override if user is not logged in, and guest entries are allowed
                if ($this->entry->author_id == 0 && $this->channel->ChannelFormSettings->allow_guest_posts == 'y') {
                    $this->entry->author_id = $this->channel->ChannelFormSettings->default_author;
                }
            }

            return;
        }

        $query = ee('Model')->get('ChannelEntry')->with('Channel');

        if ($entry_id) {
            $query->filter('entry_id', $entry_id);
        } elseif ($url_title) {
            $query->filter('url_title', $url_title);
        }

        $query->filter('ChannelEntry.channel_id', $this->channel->channel_id);
        $query->filter('ChannelEntry.site_id', $this->site_id);

        $entry = $query->first();

        if (isset($entry)) {
            $this->entry = $entry;
        }
    }

    /**
     * Load logged out member data
     *
     * @param   mixed $logged_out_member_id
     * @return  void
     */
    public function fetch_logged_out_member($logged_out_member_id = false)
    {
        if (ee()->session->userdata('member_id') || $this->logged_out_member_id) {
            return;
        }

        $channel_site_id = (isset($this->_meta['site_id'])) ? $this->_meta['site_id'] : $this->site_id;

        if (! $logged_out_member_id && $this->channel('channel_id')
            && ! empty($this->settings['allow_guest_posts'][$channel_site_id][$this->channel('channel_id')])
            && ! empty($this->settings['default_author'][$channel_site_id][$this->channel('channel_id')])) {
            $logged_out_member_id = $this->settings['default_author'][$channel_site_id][$this->channel('channel_id')];
        }

        $logged_out_member_id = $this->sanitize_int($logged_out_member_id);

        if ($logged_out_member_id) {
            $member = ee('Model')->get('Member', $logged_out_member_id)
                ->with('PrimaryRole')
                ->first();

            if (! $member) {
                // Invalid guest member id was specified
                throw new Channel_form_exception(lang('channel_form_invalid_guest_member_id'), 'general');
            }

            $this->logged_out_member_id = $member->getId();
            $this->logged_out_group_id = $member->PrimaryRole->getId();
        }
    }

    /**
     * Load settings
     *
     * @return  void
     */
    public function fetch_settings()
    {
        if ($this->settings === null) {
            $rows = ee()->db->get('channel_form_settings')->result_array();

            $this->settings = array();

            // and now into safecracker legacy format. Good grief, why does it
            // group them by column name?
            foreach ($rows as $row) {
                $site_id = $row['site_id'];
                $channel_id = $row['channel_id'];

                unset(
                    $row['site_id'],
                    $row['channel_id'],
                    $row['channel_form_settings_id']
                );

                foreach ($row as $column => $value) {
                    if (! isset($this->settings[$column])) {
                        $this->settings[$column] = array();
                    }

                    if (! isset($this->settings[$column][$site_id])) {
                        $this->settings[$column][$site_id] = array();
                    }

                    if ($column == 'allow_guest_posts') {
                        $value = $this->bool_string($value);
                    }

                    $this->settings[$column][$site_id][$channel_id] = $value;
                }
            }
        }
    }

    /**
     * Load site
     *
     * @return  void
     */
    public function fetch_site($site_name = false, $site_id = false)
    {
        if ($site_name) {
            $query = ee()->db->select('site_id')->from('sites')->where('site_name', $site_name)->limit(1)->get();

            $this->site_id = ($query->num_rows()) ? $query->row('site_id') : ee()->config->item('site_id');
        } else {
            $this->site_id = ($site_id) ?: ee()->config->item('site_id');
        }
    }

    /**
     * Load statuses
     *
     * @return  void
     */
    public function fetch_statuses()
    {
        //exit if already loaded, or if there is no status group
        if ($this->statuses || ! $this->channel->Statuses->count()) {
            return;
        }

        ee()->lang->loadfile('content');

        $assigned_statuses = $this->member->getAssignedStatuses()->indexBy('status_id');

        foreach ($this->channel->Statuses as $index => $status) {
            if (isset($assigned_statuses[$status->getId()])) {
                $this->statuses[$index]['status_id'] = $status->getId();
                $this->statuses[$index]['status'] = $status->status;
                $this->statuses[$index]['selected'] = ($status->status == $this->entry('status'))
                    ? ' selected="selected"' : '';
                $this->statuses[$index]['checked'] = ($status->status == $this->entry('status'))
                    ? ' checked="checked"' : '';
            }
        }
    }

    /**
     * Add a form attribute to entry form
     *
     * @param   mixed $name
     * @param   mixed $value
     * @return  void
     */
    public function form_attribute($name, $value = '')
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->form_attribute($key, $value);
            }

            return;
        }

        if ($value === false || $value === '') {
            return;
        }

        $this->_form_attributes[$name] = $value;
    }

    /**
     * Add a hidden field to entry form
     *
     * @param   mixed $name
     * @param   mixed $value
     * @return  void
     */
    public function form_hidden($name, $value = '')
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->form_hidden($key, $value);
            }

            return;
        }

        if ($value === false || $value === '') {
            return;
        }

        $this->_hidden_fields[$name] = $value;
    }

    protected function entry_match_check($params)
    {
        // Whoa there big conditional, what's going on here?
        // We want to make sure no one's being tricky here and supplying
        // an invalid entry_id or url_title via a segment, so we need to
        // check to see if either exists and if it does make sure that the
        // passed in version is the same as what we find in the database.
        // If they are different (most likely it wasn't found in the
        // database) then don't show them the form.  We also double check it's
        // in the correct channel

        if (($params['entry_id'] != '' && $this->channel('channel_id') != $this->entry('channel_id')) or
            ($params['url_title'] != '' && $this->channel('channel_id') != $this->entry('channel_id')) or
            ($params['entry_id'] != '' && $this->entry('entry_id') != $params['entry_id']) or
            ($params['url_title'] != '' && $this->entry('url_title') != $params['url_title'])
        ) {
            if (ee()->TMPL->no_results()) {
                return ee()->TMPL->no_results();
            }

            throw new Channel_form_exception(lang('channel_form_require_entry'));
        }
    }

    protected function _build_meta_array()
    {
        // channel and site are both valid parameters
        // channel_id and site_id are not
        // channel and site params should override channel_id and site_id

        $bool_variable = array('secure_return', 'json', 'author_only');
        // required, channel, return

        $m_group_id = $this->member->PrimaryRole->getId();

        // We'll just take all of the parameters and put then in an array
        $params = array_merge(array_keys(ee()->TMPL->tagparams), $bool_variable);

        // Add in the rules:
        $meta['rules'] = array();
        $meta['return'] = '';

        foreach ($params as $name) {
            if (preg_match('/^rules:(.+)/', $name, $match)) {
                $meta['rules'][$match[1]] = ee()->TMPL->fetch_param($name);
            } else {
                $meta[$name] = ee()->TMPL->fetch_param($name);
            }
        }

        foreach ($bool_variable as $name) {
            $meta[$name] = $this->bool_string($meta[$name]) ? 1 : false;
        }

        // If url_title is set?  Let's turn it into an entry_id and drop it from meta
        if (isset($meta['url_title'])) {
            $meta['entry_id'] = $this->entry('entry_id');
            unset($meta['url_title']);
        }

        // This will force an edit, and specify which entry_id
        $meta['require_entry'] = (isset($meta['require_entry']) && $meta['require_entry'] == 1) ? $this->entry('entry_id') : false;

        $meta['return'] = (isset($meta['return_' . $m_group_id])) ? $meta['return_' . $m_group_id] : $meta['return'];
        $meta['site_id'] = $this->site_id;  // note- site id for the specified parameter!

        $meta['channel_id'] = $this->channel('channel_id');  // channel_id is for THIS channel- use new_channel to change it
        $meta['decrypt_check'] = true;

        $meta = serialize($meta);

        return ee('Encrypt')->encode($meta, ee()->config->item('session_crypt_key'));
    }

    /**
     * get Meta vars
     *
     * Get the meta variables on the POSTed form.
     *
     */
    protected function _get_meta_vars()
    {
        $meta = $_POST['meta'];

        if (empty($meta)) {
            throw new Channel_form_exception(lang('form_decryption_failed'));
        }

        $meta = ee('Encrypt')->decode($meta, ee()->config->item('session_crypt_key'));

        $this->_meta = unserialize($meta);

        if (! isset($this->_meta['decrypt_check'])) {
            throw new Channel_form_exception(lang('form_decryption_failed'));
        }

        foreach ($this->all_params as $name) {
            $this->_meta[$name] = (isset($this->_meta[$name])) ? $this->_meta[$name] : false;
            // none of these fields are allowed by direct POST

            // url_title in the meta array tells us which entry we're editing, not what
            // to set the url_title to, so allow it to be in POST for editing;
            // Do not allow category or allow_comments to be overridden by POST
            // if set as a parameter
            if ($name == 'url_title' or
                ($name == 'category' && $this->_meta[$name] === false) or
                ($name == 'allow_comments' && $this->_meta[$name] === false)) {
                continue;
            }
            unset($_POST[$name]);
        }

        // Override allow_comments in POST if its set as a param
        if (($allow_comments = $this->bool_string($this->_meta['allow_comments'], null)) !== null) {
            $_POST['allow_comments'] = $allow_comments ? 'y' : 'n';
        }

        if ($this->_meta['category'] !== false) {
            $this->_meta['category'] = array_filter(explode('|', $this->_meta['category']), function ($cat) {
                return is_numeric($cat);
            });
        }

        $this->_meta['channel_id'] = ($this->_meta['channel_id'] != false) ? $this->_meta['channel_id'] : $this->_meta['channel'];

        //is an edit form?  This seems madly overkill
        if ($this->_meta['require_entry']) {
            $this->_meta['entry_id'] = $this->_meta['require_entry'];
        }
    }

    public function get_field($field_name)
    {
        return $this->custom_fields[$field_name];
    }

    /**
     * Retrieve field data
     * Returns array of all field data if no key specified
     *
     * @param   mixed $field_name
     * @param   mixed $key
     * @return  void
     */
    public function get_field_data($field_name, $key = false)
    {
        if (in_array($field_name, $this->title_fields)) {
            return array();
        }

        if (isset($this->custom_fields[$field_name])) {
            $field = $this->custom_fields[$field_name];

            if ($key) {
                return $field->getProperty($key, array());
            } else {
                return $field->getValues();
            }
        }

        return array();
    }

    /**
     * Gets the field id of a field
     *
     * @param   mixed $field_name
     * @return  void
     */
    public function get_field_id($field_name)
    {
        return $this->get_field_data($field_name, 'field_id');
    }

    /**
     * Gets the field name of a field
     *
     * @param   mixed $field_id
     * @return  void
     */
    public function get_field_name($field_id)
    {
        return (isset($this->custom_field_names[$field_id])) ? $this->custom_field_names[$field_id] : false;
    }

    /**
     * Gets a field's options
     *
     * @param   mixed $field_name
     * @return  void
     */
    public function get_field_options($field_name)
    {
        $field = $this->get_field($field_name);
        $options = array();

        $field_data = (is_array($this->entry('field_id_' . $field->field_id)))
            ? $this->entry('field_id_' . $field->field_id) : explode('|', $this->entry('field_id_' . $field->field_id));

        if (in_array($field->field_type, $this->option_fields)) {
            $field_settings = $field->getField()->getItem('field_settings');

            if (isset($field_settings['value_label_pairs']) && ! empty($field_settings['value_label_pairs'])) {
                foreach ($field_settings['value_label_pairs'] as $value => $label) {
                    $options[] = array(
                        'option_value' => $value,
                        'option_name' => $label,
                        'selected' => (in_array($value, $field_data)) ? ' selected="selected"' : '',
                        'checked' => (in_array($value, $field_data)) ? ' checked="checked"' : '',
                    );
                }
            } elseif ($field->field_list_items) {
                foreach (preg_split('/[\r\n]+/', $field->field_list_items) as $row) {
                    $row = trim($row);

                    if ($row == '') {
                        continue;
                    }

                    $options[] = array(
                        'option_value' => $row,
                        'option_name' => $row,
                        'selected' => (in_array($row, $field_data)) ? ' selected="selected"' : '',
                        'checked' => (in_array($row, $field_data)) ? ' checked="checked"' : '',
                    );
                }
            }

            if ($field->field_pre_populate == 'y') {
                $pop_entries = ee('Model')->get('ChannelEntry')
                    ->fields('field_id_' . $field->field_pre_field_id)
                    ->filter('channel_id', $field->field_pre_channel_id)
                    ->filter('field_id_' . $field->field_pre_field_id, '!=', '')
                    ->all();

                if ($pop_entries && $pop_content = $pop_entries->pluck('field_id_' . $field->field_pre_field_id)) {
                    $current = explode('|', $this->entry('field_id_' . $field->field_id));

                    foreach ($pop_content as $content) {
                        $options[] = array(
                            'option_value' => $content,
                            'option_name' => str_replace(array("\r\n", "\r", "\n", "\t"), ' ', substr($content, 0, 110)),
                            'selected' => (in_array($content, $current)) ? ' selected="selected"' : '',
                            'checked' => (in_array($content, $current)) ? ' checked="checked"' : '',
                        );
                    }
                }
            } elseif (! in_array($field->field_type, $this->native_option_fields)) {
                $field_settings = $field->field_settings;

                if (! empty($field_settings['options'])) {
                    foreach ($field_settings['options'] as $option_value => $option_name) {
                        $options[] = array(
                            'option_value' => $option_value,
                            'option_name' => $option_name,
                            'selected' => (in_array($option_value, $field_data)) ? ' selected="selected"' : '',
                            'checked' => (in_array($option_value, $field_data)) ? ' checked="checked"' : ''
                        );
                    }
                }
            }
        } elseif ($field->field_type == 'relationship') {
            $order = array();
            $entries = array();
            $selected = array();

            if ($this->entry('entry_id')) {
                if ($this->form_error) {
                    $related = $this->entry($field_name);
                } else {
                    $related = ee()->db
                        ->select('child_id, order')
                        ->where('parent_id', $this->entry('entry_id'))
                        ->where('field_id', $this->get_field_id($field_name))
                        ->get('relationships')
                        ->result();

                    foreach ($related as $row) {
                        $selected[] = $row->child_id;
                        $order[$row->child_id] = $row->order;
                    }
                }
            }

            $settings = $this->get_field_settings($field_name);

            $limit_channels = $settings['channels'];
            $limit_categories = $settings['categories'];
            $limit_statuses = $settings['statuses'];
            $limit_authors = $settings['authors'];
            $limit = $settings['limit'];

            $show_expired = (bool) $settings['expired'];
            $show_future = (bool) $settings['future'];

            ee()->db
                ->select('channel_titles.entry_id, channel_titles.title, ' . $settings['order_field'])
                ->order_by($settings['order_field'], $settings['order_dir']);

            if ($limit) {
                ee()->db->limit($limit);
            }

            if (count($limit_channels)) {
                ee()->db->where_in('channel_titles.channel_id', $limit_channels);
            }

            if (count($limit_categories)) {
                ee()->db->from('category_posts');
                ee()->db->where('exp_channel_titles.entry_id = exp_category_posts.entry_id', null, false); // todo ick
                ee()->db->where_in('category_posts.cat_id', $limit_categories);
            }

            if (count($limit_statuses)) {
                $limit_statuses = str_replace(
                    array('Open', 'Closed'),
                    array('open', 'closed'),
                    $limit_statuses
                );

                ee()->db->where_in('channel_titles.status', $limit_statuses);
            }

            if (count($limit_authors)) {
                $groups = array();
                $members = array();

                foreach ($limit_authors as $author) {
                    switch ($author[0]) {
                        case 'g':
                            $groups[] = substr($author, 2);

                            break;
                        case 'm':
                            $members[] = substr($author, 2);

                            break;
                    }
                }

                $where = '';

                if (count($members)) {
                    $where .= ee()->db->dbprefix('channel_titles') . '.author_id IN (' . implode(', ', $members) . ')';
                }

                if (count($groups)) {
                    $where .= $where ? ' OR ' : '';
                    $where .= ee()->db->dbprefix('members') . '.group_id IN (' . implode(', ', $groups) . ')';
                    ee()->db->join('members', 'members.member_id = channel_titles.author_id');
                }

                if ($where) {
                    ee()->db->where("({$where})");
                }
            }

            // Limit times
            $now = ee()->localize->now;

            if (! $show_future) {
                ee()->db->where('channel_titles.entry_date < ', $now);
            }

            if (! $show_expired) {
                $t = ee()->db->dbprefix('channel_titles');
                ee()->db->where("(${t}.expiration_date = 0 OR ${t}.expiration_date > ${now})", null, false);
            }

            if ($this->entry('entry_id')) {
                ee()->db->where('channel_titles.entry_id !=', $this->entry('entry_id'));
            }

            if (count($selected)) {
                ee()->db->or_where_in('channel_titles.entry_id', $selected);
            }

            ee()->db->distinct();
            $entries = ee()->db->get('channel_titles')->result_array();

            $options = array();

            if (empty($settings['allow_multiple'])) {
                $options[] = array(
                    'option_value' => '',
                    'option_name' => '--',
                    'option_order' => 0,
                    'selected' => '',
                    'checked' => ''
                );
            }

            foreach ($entries as $entry) {
                $checked = in_array($entry['entry_id'], $selected);
                $sort = $checked ? $order[$entry['entry_id']] : 0;

                $options[] = array(
                    'option_value' => $entry['entry_id'],
                    'option_name' => $entry['title'],
                    'option_order' => $sort,
                    'selected' => $checked ? ' selected="selected"' : '',
                    'checked' => $checked ? ' checked="checked"' : '',
                );
            }
        }

        return $options;
    }

    /**
     * Gets a field's settings
     *
     * @param   mixed $field_name
     * @param   mixed $unserialize = TRUE
     * @return  void
     */
    public function get_field_settings($field_name)
    {
        if (! $field_settings = $this->get_field_data($field_name, 'field_settings')) {
            return array();
        }

        return $field_settings;
    }

    /**
     * Gets the type of a field
     *
     * @param   mixed $field_name
     * @return  void
     */
    public function get_field_type($field_name)
    {
        return $this->get_field_data($field_name, 'field_type');
    }

    /**
     * Initialize the library properties
     *
     * @return  void
     */
    public function initialize($reinitialize = false)
    {
        if ($this->initialized && ! $reinitialize) {
            return;
        }

        $this->initialized = true;

        $this->categories = array();
        $this->checkboxes = array(
            'sticky',
            'allow_comments'
        );

        $this->custom_field_conditional_names = array(
            'rel' => 'relationship',
            'text' => 'textinput',
            'select' => 'pulldown',
            'checkboxes' => 'checkbox',
            'multi_select' => 'multiselect'
        );

        $this->custom_fields = array();

        $this->date_fields = array(
            'comment_expiration_date',
            'expiration_date',
            'entry_date',
            'edit_date',
            'recent_comment_date',
            'recent_trackback_date'
        );

        $this->datepicker = true;

        $this->default_fields = array(
            'title' => array(
                'field_name' => 'title',
                'field_label' => 'lang:title',
                'field_type' => 'text',
                'rules' => 'required|call_field_validation[title]'
            ),
            'url_title' => array(
                'field_name' => 'url_title',
                'field_label' => 'lang:url_title',
                'field_type' => 'text',
                'rules' => 'call_field_validation[url_title]'
            ),
            'entry_date' => array(
                'field_name' => 'entry_date',
                'field_label' => 'lang:entry_date',
                'field_type' => 'date',
                'rules' => 'required|call_field_validation[entry_date]'
            ),
            'expiration_date' => array(
                'field_name' => 'expiration_date',
                'field_label' => 'lang:expiration_date',
                'field_type' => 'date',
                'rules' => 'call_field_validation[expiration_date]'
            ),
            'comment_expiration_date' => array(
                'field_name' => 'comment_expiration_date',
                'field_label' => 'lang:comment_expiration_date',
                'field_type' => 'date',
                'rules' => 'call_field_validation[comment_expiration_date]'
            )
        );

        $this->edit = false;
        $this->error_handling = 'message';
        $this->errors = array();
        $this->field_errors = array();
        $this->file = false;
        $this->file_fields = array(
            'file'
        );

        $this->form_validation_methods = array();
        $this->head = '';
        $this->json = false;
        $this->logged_out_member_id = false;
        $this->logged_out_group_id = false;
        $this->native_option_fields = array(
            'multi_select',
            'select',
            'radio',
            'checkboxes'
        );

        $this->native_variables = array(
            'comment_expiration_date' => 'date',
            'expiration_date' => 'date',
            'entry_date' => 'date',
            'url_title' => 'text',
            'sticky' => false,
            'allow_comments' => false,
            'title' => 'text'
        );

        $this->parse_variables = array();

        $this->post_error_callbacks = array();
        $this->require_save_call = array();
        $this->skip_xss_fieldtypes = array();
        $this->skip_xss_field_ids = array();
        $this->statuses = array();
        $this->show_fields = array();
        $this->title_fields = array(
            'entry_id',
            'site_id',
            'channel_id',
            'author_id',
            'pentry_id',
            'forum_topic_id',
            'ip_address',
            'title',
            'url_title',
            'status',
            'versioning_enabled',
            'view_count_one',
            'view_count_two',
            'view_count_three',
            'view_count_four',
            'allow_comments',
            'sticky',
            'entry_date',
            'year',
            'month',
            'day',
            'expiration_date',
            'comment_expiration_date',
            'recent_comment_date',
            'comment_total',
            'captcha_word'
        );

        $this->valid_callbacks = array(
            'html_entity_decode',
            'htmlentities'
        );

        $this->fetch_settings();

        // Get the list of Fieldtypes that extend OptionFieldtype
        $fieldtypes = ee('Model')->get('Fieldtype')->all()->pluck('name');
        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_fields');
        $this->option_fields = array_filter($fieldtypes, function($fieldtype) {
            ee()->api_channel_fields->include_handler($fieldtype);
            $class = ucfirst($fieldtype) . '_ft';

            return is_subclass_of($class, 'OptionFieldtype');
        });

        /*
            TODO: I think the following code can be removed
            ee()->config->load('config');

            if (is_array(ee()->config->item('safecracker_option_fields')))
            {
                $this->custom_option_fields = ee()->config->item('safecracker_option_fields');

                $this->option_fields = array_merge($this->option_fields, $this->custom_option_fields);
            }

            if (is_array(ee()->config->item('safecracker_post_error_callbacks')))
            {
                $this->post_error_callbacks = array_merge($this->post_error_callbacks, ee()->config->item('safecracker_post_error_callbacks'));
            }

            if (is_array(ee()->config->item('safecracker_file_fields')))
            {
                $this->file_fields = array_merge($this->file_fields, ee()->config->item('safecracker_file_fields'));
            }

            if (is_array(ee()->config->item('safecracker_require_save_call')))
            {
                $this->require_save_call = ee()->config->item('safecracker_require_save_call');
            }

            if (is_array(ee()->config->item('safecracker_field_extra_js')))
            {
                $this->extra_js = ee()->config->item('safecracker_field_extra_js');
            }
        */
    }

    /**
     * Replaces a tag
     *
     * @param   mixed $field_name
     * @param   mixed $data
     * @param   mixed $params = array()
     * @param   mixed $tagdata = FALSE
     * @return  void
     */
    public function replace_tag($field_name, $data, $params = array(), $tagdata = false)
    {
        if (! $params) {
            $params = array();
        }

        if (! isset($this->custom_fields[$field_name])) {
            return $tagdata;
        }

        ee()->load->library('api');

        ee()->load->helper('custom_field');

        ee()->legacy_api->instantiate('channel_fields');

        ee()->api_channel_fields->field_type = $this->get_field_type($field_name);

        if (! is_object(ee()->api_channel_fields->field_types[ee()->api_channel_fields->field_type])) {
            ee()->api_channel_fields->include_handler(ee()->api_channel_fields->field_type);
            $fieldtype = ee()->api_channel_fields->setup_handler(ee()->api_channel_fields->field_type, true);
            ee()->api_channel_fields->field_types[ee()->api_channel_fields->field_type] = $fieldtype;
        }

        ee()->api_channel_fields->field_types[ee()->api_channel_fields->field_type]->settings = array_merge($this->get_field_settings($field_name), $this->get_field_data($field_name), ee()->api_channel_fields->get_global_settings(ee()->api_channel_fields->field_type));

        $_GET['entry_id'] = $this->entry('entry_id');

        ee()->api_channel_fields->apply('_init', array(array(
            'row' => $this->entry,
            'content_id' => $this->entry('entry_id')
        )));

        $data = ee()->api_channel_fields->apply('pre_process', array($data));

        return ee()->api_channel_fields->apply('replace_tag', array('data' => $data, 'params' => $params, 'tagdata' => $tagdata));
    }

    /**
     * Clean an ID
     *
     * @param   mixed $id
     * @return  mixed
     */
    public function sanitize_int($data)
    {
        if (is_int($data + 0)) {
            return $data;
        }

        $data = preg_replace('/[^\d]/', '', $data);

        return ($data) ? $data : false;
    }

    public function send_ajax_response($msg, $error = false)
    {
        if (ee()->config->item('send_headers') == 'y') {
            //so the output class doesn't try to send any headers
            //we are taking over
            ee()->config->config['send_headers'] = null;

            ee()->load->library('user_agent', array(), 'user_agent');

            // many browsers do not consistently like this content type
            if (is_array($msg) && in_array(ee()->user_agent->browser(), array('Safari', 'Chrome'))) {
                @header('Content-Type: application/json; charset=UTF-8');
            } else {
                @header('Content-Type: text/html; charset=UTF-8');
            }
        }

        ee()->output->send_ajax_response($msg, $error);
    }

    /**
     * swap_conditionals
     *
     * @param   mixed $tagdata
     * @param   mixed $conditionals
     * @return  void
     */
    public function swap_conditionals($tagdata, $conditionals)
    {
        $tagdata = ee()->functions->prep_conditionals($tagdata, $conditionals);

        $tagdata = preg_replace('/\{if\s+[\042\047]*0[\042\047]*\}(.+?)\{\/if\}/si', '', $tagdata);

        $tagdata = preg_replace('/\{if\s+[\042\047]*1[\042\047]*\}(.+?)\{\/if\}/si', '\\1', $tagdata);

        return $tagdata;
    }

    /**
     * swap_var_pair
     *
     * @param   mixed $key
     * @param   mixed $rows
     * @param   mixed $tagdata
     * @param   mixed $close_key = ''
     * @param   mixed $backspace = FALSE
     * @return  void
     */
    public function swap_var_pair($key, $rows, $tagdata, $close_key = '', $backspace = false)
    {
        $close_key = ($close_key) ? $close_key : $key;

        if (preg_match_all('/' . LD . preg_quote($key) . RD . '(.*?)' . LD . '\/' . $close_key . RD . '/s', $tagdata, $matches)) {
            foreach ($matches[1] as $match_index => $var_pair_tagdata) {
                $output = '';

                foreach ($rows as $row) {
                    $row_output = $var_pair_tagdata;
                    $row_output = ee()->functions->prep_conditionals($row_output, $row);

                    foreach ($row as $k => $v) {
                        $row_output = ee()->TMPL->swap_var_single($k, $v, $row_output);
                    }

                    $output .= $row_output . "\n";
                }

                if ($backspace && is_numeric($backspace)) {
                    $output = substr($output, 0, -1 * ($backspace + 1));
                }

                $tagdata = str_replace($matches[0][$match_index], $output, $tagdata);
            }
        }

        return $tagdata;
    }

    /**
     * unserialize
     *
     * @param   mixed $data
     * @param   mixed $base64_decode = FALSE
     * @return  void
     */
    public function unserialize($data, $base64_decode = false)
    {
        if ($base64_decode) {
            $data = base64_decode($data);
        }

        $data = @unserialize($data);

        return (is_array($data)) ? $data : array();
    }

    /**
     * SAEF URL Title Javascript
     *
     * This function adds url_title javascript to the js script compiled in saef_javascript()
     * @todo use our jquery plugin
     *
     * @return string
     */
    public function _url_title_js()
    {
        // js for URL Title
        $convert_ascii = (ee()->config->item('auto_convert_high_ascii') == 'y') ? true : false;
        $word_separator = ee()->config->item('word_separator') != "dash" ? '_' : '-';

        // Foreign Character Conversion Javascript
        $foreign_characters = ee()->config->loadFile('foreign_chars');

        /* -------------------------------------
        /*  'foreign_character_conversion_array' hook.
        /*  - Allows you to use your own foreign character conversion array
        /*  - Added 1.6.0
        /*  - Note: in 2.0, you can edit the foreign_chars.php config file as well
        */
        if (isset(ee()->extensions->extensions['foreign_character_conversion_array'])) {
            $foreign_characters = ee()->extensions->call('foreign_character_conversion_array');
        }
        /*
        /* -------------------------------------*/

        $foreign_replace = '';

        foreach ($foreign_characters as $old => $new) {
            $foreign_replace .= "if (c == '$old') {NewTextTemp += '$new'; continue;}\n\t\t\t\t";
        }

        $url_title_js = <<<SCRIPT

function liveUrlTitle(event)
{
    var title_field, url_title_field;

    /* If event is present, we'll try to make sure we're only affecting the URL title field inside this form */
    if (event) {
        title_field = event.target;

        for (var i = 0; i < document.forms.length; i++) {
            if (document.forms[i].contains(title_field)) {
                url_title_field = document.forms[i].querySelector('#url_title');
            }
        }
    } else {
        title_field = document.getElementById("title");
        url_title_field = document.getElementById("url_title");
    }

    var defaultTitle =  EE.publish.default_entry_title;
    var NewText = title_field.value;

    if (defaultTitle != '')
    {
        if (NewText.substr(0, defaultTitle.length) == defaultTitle)
        {
            NewText = NewText.substr(defaultTitle.length);
        }
    }

    NewText = NewText.toLowerCase();
    var separator = "{$word_separator}";

    /* Foreign Character Attempt */

    var NewTextTemp = '';
    for(var pos=0; pos<NewText.length; pos++)
    {
        var c = NewText.charCodeAt(pos);

        if (c >= 32 && c < 128)
        {
            NewTextTemp += NewText.charAt(pos);
        }
        else
        {
            {$foreign_replace}
        }
    }

    var multiReg = new RegExp(separator + '{2,}', 'g');

    NewText = NewTextTemp;

    NewText = NewText.replace('/<(.*?)>/g', '');
    NewText = NewText.replace(/\s+/g, separator);
    NewText = NewText.replace(/\//g, separator);
    NewText = NewText.replace(/[^a-z0-9\-\._]/g,'');
    NewText = NewText.replace(/\+/g, separator);
    NewText = NewText.replace(multiReg, separator);
    NewText = NewText.replace(/-$/g,'');
    NewText = NewText.replace(/_$/g,'');
    NewText = NewText.replace(/^_/g,'');
    NewText = NewText.replace(/^-/g,'');

    if (url_title_field)
    {
        url_title_field.value = EE.publish.url_title_prefix + NewText;
    }
    else
    {
        document.forms['cform'].elements['url_title'].value = EE.publish.url_title_prefix + NewText;
    }
}

SCRIPT;

        $ret = $url_title_js;

        if (PATH_JS !== 'src') {
            return str_replace(array("\n", "\t"), '', $ret);
        }

        return $ret;
    }

    /**
     * Assigns proper group id to logged out users
     *
     * @param   bool $reset Whether to reset to 0 or default member
     *
     * @return  void
     */
    private function _member_group_override($reset = false)
    {
        if (ee()->session->userdata('member_id')) {
            return;
        }

        $id = (! $reset) ? $this->member->PrimaryRole->getId() : 0;

        ee()->session->userdata['group_id'] = $id;
    }

    /**
     * Sets site_id, used to allow across site forms
     *
     * @param   int $site_id The site_id to switch to
     *
     * @return  void
     */
    private function switch_site($site_id)
    {
        ee()->config->set_item('site_id', $site_id);
        ee()->config->get_cached_site_prefs($site_id);
    }
}

// EOF
