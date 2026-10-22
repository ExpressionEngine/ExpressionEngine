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
 * Pro Relationship variable type
 */
class Pro_relationship extends Pro_variables_type
{
    public $info = array(
        'name' => 'Relationship',
        'var_requires' => array(
            'relationship' => '1.0.0'
        )
    );

    public $default_settings = array(
        'channels' => array(),  // Empty array means "any channel"
        'categories' => array(), // Empty array means "any category"
        'statuses' => array(),   // Empty array means "any status"
        'authors' => array(),    // Empty array means "any author"
        'limit' => 100,
        'expired' => 'n',
        'future' => 'n',
        'order_field' => 'title',
        'order_dir' => 'asc',
        'allow_multiple' => 'y',
        'rel_min' => '0',
        'rel_max' => '',
        'display_entry_id' => 'n',
        'display_status' => 'n',
        'deferred_loading' => 'n'
    );

    /**
     * Relationship fieldtype instance
     */
    private $_ft;

    /**
     * Get yes/no value, converting boolean to 'y'/'n' string
     * 
     * @param string $key Setting key
     * @param string $default Default value ('y' or 'n')
     * @return string 'y' or 'n'
     */
    private function get_yes_no_value($key, $default = 'n')
    {
        $value = $this->settings($key);
        
        if (is_bool($value)) {
            return $value ? 'y' : 'n';
        }
        
        if ($value === 'y' || $value === 'n') {
            return $value;
        }
        
        if ($value) {
            $bool = get_bool_from_string($value);
            if ($bool === true) {
                return 'y';
            }
        }
        
        return $default;
    }

    private $_table = 'relationships';

    // --------------------------------------------------------------------

    /**
     * Setup relationship fieldtype bridge
     */
    protected function setup_relationship_ft()
    {
        $relationship_path = PATH_ADDONS . 'relationship/';
        ee()->load->add_package_path($relationship_path);
        
        if (empty($this->_ft)) {
            ee()->load->library('api');
            ee()->legacy_api->instantiate('channel_fields');
            ee()->api_channel_fields->include_handler('relationship');
            
            $this->_ft = ee()->api_channel_fields->setup_handler('relationship', true);
        }
        
        $this->_ft->_init(array(
            'id' => $this->id,
            'name' => $this->name,
            'content_id' => $this->id,
            'content_type' => static::CONTENT_TYPE,
            'field_id' => $this->id,
            'field_name' => $this->name,
            'settings' => $this->settings(),
            'row' => $this->row()
        ));
    }

    // --------------------------------------------------------------------

    /**
     * Display settings sub-form for this variable type
     */
    public function display_settings()
    {
        ee()->lang->loadfile('fieldtypes');
        
        $relationship_path = PATH_ADDONS . 'relationship/';
        $library_file = $relationship_path . 'libraries/Relationships_ft_cp.php';
        
        if (file_exists($library_file) && !class_exists('Relationships_ft_cp')) {
            require_once($library_file);
        }
        
        $util = new Relationships_ft_cp();

        $r = array();

        $channel_choices = $util->all_channels();
        $channel_value = $this->settings('channels');
        // If empty array or not set, show "Any channel" as selected
        if (empty($channel_value) || (is_array($channel_value) && empty($channel_value))) {
            $channel_value = array('--');
        } else {
            // Ensure it's an array and remove any '--' values for display
            if (!is_array($channel_value)) {
                $channel_value = array();
            }
            // If we have actual channel IDs, use them; otherwise show "Any"
            if (empty($channel_value)) {
                $channel_value = array('--');
            }
        }
        
        $r[] = array(
            'title' => 'rel_ft_channels',
            'desc' => 'rel_ft_channels_desc',
            'fields' => array(
                $this->setting_name('channels') => array(
                    'type' => 'checkbox',
                    'nested' => true,
                    'attrs' => 'data-any="y"',
                    'choices' => $channel_choices,
                    'value' => $channel_value,
                    'toggle_all' => false,
                    'no_results' => array(
                        'text' => sprintf(lang('no_found'), lang('channels'))
                    )
                )
            )
        );

        $expired_value = $this->settings('expired');
        $expired_checked = ($expired_value == 'y' || $expired_value == '1' || (is_array($expired_value) && in_array('1', $expired_value)));
        
        $future_value = $this->settings('future');
        $future_checked = ($future_value == 'y' || $future_value == '1' || (is_array($future_value) && in_array('1', $future_value)));
        
        $r[] = array(
            'title' => 'rel_ft_include',
            'desc' => 'rel_ft_include_desc',
            'fields' => array(
                $this->setting_name('expired') => array(
                    'type' => 'checkbox',
                    'scalar' => true,
                    'choices' => array(
                        '1' => lang('rel_ft_include_expired')
                    ),
                    'value' => $expired_checked ? array('1') : array()
                ),
                $this->setting_name('future') => array(
                    'type' => 'checkbox',
                    'scalar' => true,
                    'choices' => array(
                        '1' => lang('rel_ft_include_future')
                    ),
                    'value' => $future_checked ? array('1') : array()
                )
            )
        );

        $category_choices = $util->all_categories();
        $category_value = $this->settings('categories');
        // If empty array or not set, show "Any category" as selected
        if (empty($category_value) || (is_array($category_value) && empty($category_value))) {
            $category_value = array('--');
        } else {
            // Ensure it's an array
            if (!is_array($category_value)) {
                $category_value = array();
            }
            // If we have actual category IDs, use them; otherwise show "Any"
            if (empty($category_value)) {
                $category_value = array('--');
            }
        }
        
        $r[] = array(
            'title' => 'rel_ft_categories',
            'desc' => 'rel_ft_categories_desc',
            'fields' => array(
                $this->setting_name('categories') => array(
                    'type' => 'checkbox',
                    'nested' => true,
                    'attrs' => 'data-any="y"',
                    'choices' => $category_choices,
                    'value' => $category_value,
                    'toggle_all' => false,
                    'no_results' => array(
                        'text' => sprintf(lang('no_found'), lang('categories'))
                    )
                )
            )
        );

        $author_choices = $util->all_authors();
        $author_value = $this->settings('authors');
        // If empty array or not set, show "Any author" as selected
        if (empty($author_value) || (is_array($author_value) && empty($author_value))) {
            $author_value = array('--');
        } else {
            // Ensure it's an array
            if (!is_array($author_value)) {
                $author_value = array();
            }
            // If we have actual author IDs, use them; otherwise show "Any"
            if (empty($author_value)) {
                $author_value = array('--');
            }
        }
        
        $r[] = array(
            'title' => 'rel_ft_authors',
            'desc' => 'rel_ft_authors_desc',
            'fields' => array(
                $this->setting_name('authors') => array(
                    'type' => 'checkbox',
                    'nested' => true,
                    'attrs' => 'data-any="y"',
                    'choices' => $author_choices,
                    'value' => $author_value,
                    'filter_url' => ee('CP/URL')->make('fields/relationship-member-filter')->compile(),
                    'toggle_all' => false,
                    'no_results' => array(
                        'text' => sprintf(lang('no_found'), lang('authors'))
                    )
                )
            )
        );

        $status_choices = $util->all_statuses();
        $status_value = $this->settings('statuses');
        // If empty array or not set, show "Any status" as selected
        if (empty($status_value) || (is_array($status_value) && empty($status_value))) {
            $status_value = array('--');
        } else {
            // Ensure it's an array
            if (!is_array($status_value)) {
                $status_value = array();
            }
            // If we have actual status IDs, use them; otherwise show "Any"
            if (empty($status_value)) {
                $status_value = array('--');
            }
        }
        
        $r[] = array(
            'title' => 'rel_ft_statuses',
            'desc' => 'rel_ft_statuses_desc',
            'fields' => array(
                $this->setting_name('statuses') => array(
                    'type' => 'checkbox',
                    'nested' => true,
                    'attrs' => 'data-any="y"',
                    'choices' => $status_choices,
                    'value' => $status_value,
                    'toggle_all' => false,
                    'no_results' => array(
                        'text' => sprintf(lang('no_found'), lang('statuses'))
                    )
                )
            )
        );

        $entries_lang = strtolower(lang('entries'));
        $r[] = array(
            'title' => sprintf(lang('rel_ft_limit'), $entries_lang),
            'desc' => sprintf(lang('rel_ft_limit_desc'), $entries_lang, $entries_lang),
            'fields' => array(
                $this->setting_name('limit') => array(
                    'type' => 'text',
                    'value' => $this->settings('limit') ?: 100
                )
            )
        );

        $r[] = array(
            'title' => 'rel_ft_order',
            'desc' => sprintf(lang('rel_ft_order_desc'), $entries_lang),
            'fields' => array(
                $this->setting_name('order_field') => array(
                    'type' => 'radio',
                    'choices' => array(
                        'title' => lang('rel_ft_order_title'),
                        'entry_date' => lang('rel_ft_order_date')
                    ),
                    'value' => $this->settings('order_field') ?: 'title'
                ),
                $this->setting_name('order_dir') => array(
                    'type' => 'radio',
                    'choices' => array(
                        'asc' => lang('rel_ft_order_ascending'),
                        'desc' => lang('rel_ft_order_descending'),
                    ),
                    'value' => $this->settings('order_dir') ?: 'asc'
                )
            )
        );

        $r[] = array(
            'title' => 'rel_ft_allow_multi',
            'desc' => 'rel_ft_allow_multi_desc',
            'fields' => array(
                $this->setting_name('allow_multiple') => array(
                    'type' => 'yes_no',
                    'group_toggle' => array(
                        'y' => 'rel_min_max',
                    ),
                    'value' => $this->get_yes_no_value('allow_multiple', 'y')
                )
            )
        );

        $r[] = array(
            'title' => sprintf(lang('rel_ft_min'), $entries_lang),
            'desc' => sprintf(lang('rel_ft_min_desc'), $entries_lang),
            'group' => array($this->type, 'rel_min_max'),
            'fields' => array(
                $this->setting_name('rel_min') => array(
                    'type' => 'text',
                    'value' => $this->settings('rel_min') ?: ''
                )
            )
        );

        $r[] = array(
            'title' => sprintf(lang('rel_ft_max'), $entries_lang),
            'desc' => sprintf(lang('rel_ft_max_desc'), $entries_lang),
            'group' => array($this->type, 'rel_min_max'),
            'fields' => array(
                $this->setting_name('rel_max') => array(
                    'type' => 'text',
                    'value' => $this->settings('rel_max') ?: ''
                )
            )
        );

        $r[] = array(
            'title' => 'rel_ft_display_entry_id',
            'desc' => 'rel_ft_display_entry_id_desc',
            'fields' => array(
                $this->setting_name('display_entry_id') => array(
                    'type' => 'yes_no',
                    'value' => $this->get_yes_no_value('display_entry_id', 'n')
                )
            )
        );

        $r[] = array(
            'title' => 'rel_ft_display_status',
            'desc' => 'rel_ft_display_status_desc',
            'fields' => array(
                $this->setting_name('display_status') => array(
                    'type' => 'yes_no',
                    'value' => $this->get_yes_no_value('display_status', 'n')
                )
            )
        );

        $r[] = array(
            'title' => 'rel_ft_deferred',
            'desc' => 'rel_ft_deferred_desc',
            'fields' => array(
                $this->setting_name('deferred_loading') => array(
                    'type' => 'yes_no',
                    'value' => $this->get_yes_no_value('deferred_loading', 'n')
                )
            )
        );

        return $this->settings_form($r);
    }

    // --------------------------------------------------------------------

    /**
     * Save variable settings
     * Process settings from form submission
     */
    public function save_var_settings()
    {
        $settings_key = 'variable_settings[' . $this->type . ']';
        $post_data = ee('Request')->post($settings_key, array());
        
        $data = array();
        
        if (isset($post_data['channels'])) {
            $data['channels'] = $post_data['channels'];
            if (is_array($data['channels']) && in_array('--', $data['channels'])) {
                $data['channels'] = array();
            }
        }
        
        if (isset($post_data['categories'])) {
            $data['categories'] = $post_data['categories'];
            if (is_array($data['categories']) && in_array('--', $data['categories'])) {
                $data['categories'] = array();
            }
        }
        
        if (isset($post_data['expired'])) {
            $data['expired'] = (is_array($post_data['expired']) && in_array('1', $post_data['expired'])) ? 'y' : 'n';
        }
        
        if (isset($post_data['future'])) {
            $data['future'] = (is_array($post_data['future']) && in_array('1', $post_data['future'])) ? 'y' : 'n';
        }

        $mappings = array(
            'statuses' => 'statuses',
            'authors' => 'authors',
            'limit' => 'limit',
            'order_field' => 'order_field',
            'order_dir' => 'order_dir',
            'allow_multiple' => 'allow_multiple',
            'rel_min' => 'rel_min',
            'rel_max' => 'rel_max',
            'display_entry_id' => 'display_entry_id',
            'display_status' => 'display_status',
            'deferred_loading' => 'deferred_loading'
        );

        foreach ($mappings as $key => $map_key) {
            if (isset($post_data[$key])) {
                $value = $post_data[$key];
                
                if (is_array($value) && in_array('--', $value)) {
                    $value = array();
                }
                
                // Convert yes/no fields - keep as 'y'/'n' strings (not boolean)
                // get_bool_from_string converts to boolean, but we need 'y'/'n' for storage
                if (in_array($key, array('allow_multiple', 'display_entry_id', 'display_status', 'deferred_loading'))) {
                    // Convert to boolean first, then back to 'y'/'n' string
                    $bool_value = get_bool_from_string($value);
                    $value = ($bool_value === true) ? 'y' : 'n';
                }
                
                $data[$map_key] = $value;
            }
        }
        
        $settings = array_merge($this->default_settings, $data);

        return $settings;
    }

    // --------------------------------------------------------------------

    /**
     * Display input field for regular user
     */
    public function display_field($var_data)
    {
        $this->setup_relationship_ft();

        $data = array();

        $children_cache = ee()->session->cache('Relationship_ft', 'children');
        if (!is_array($children_cache)) {
            $children_cache = array();
        }
        
        // Set empty collection for this variable ID to prevent null error
        if (!isset($children_cache[$this->id])) {
            // Create an empty collection that has indexBy method
            $empty_collection = ee('Model')->get('ChannelEntry')
                ->filter('entry_id', 0)
                ->all();
            $children_cache[$this->id] = $empty_collection;
            ee()->session->set_cache('Relationship_ft', 'children', $children_cache);
        }

        $settings = $this->settings();

        // First, ensure all yes/no settings are 'y'/'n' strings (for storage consistency)
        $yes_no_settings = array('allow_multiple', 'display_entry_id', 'display_status', 'deferred_loading');

        foreach ($yes_no_settings as $key) {
            if (isset($settings[$key])) {
                if (is_bool($settings[$key])) {
                    $settings[$key] = $settings[$key] ? 'y' : 'n';
                } elseif ($settings[$key] !== 'y' && $settings[$key] !== 'n') {
                    $bool = get_bool_from_string($settings[$key]);
                    $settings[$key] = ($bool === true) ? 'y' : 'n';
                }
            }
        }
        
        if (isset($settings['deferred_loading'])) {
            $settings['deferred_loading'] = ($settings['deferred_loading'] === 'y') ? true : false;
        }        
        if (isset($settings['allow_multiple'])) {
            $settings['allow_multiple'] = ($settings['allow_multiple'] === 'y') ? true : false;
        }        
        if (isset($settings['display_entry_id'])) {
            $settings['display_entry_id'] = ($settings['display_entry_id'] === 'y') ? true : false;
        }        
        if (isset($settings['display_status'])) {
            $settings['display_status'] = ($settings['display_status'] === 'y') ? true : false;
        }
        
        $array_settings = array('channels', 'categories', 'statuses', 'authors');
        foreach ($array_settings as $key) {
            if (!isset($settings[$key]) || !is_array($settings[$key])) {
                $settings[$key] = array();
            } else {
                $settings[$key] = array_filter($settings[$key], function($val) {
                    return $val !== '--';
                });
                $settings[$key] = array_values($settings[$key]);
            }
        }
        
        if (isset($settings['expired'])) {
            $settings['expired'] = ($settings['expired'] == 'y' || $settings['expired'] === true || $settings['expired'] === 1) ? 'y' : 'n';
        } else {
            $settings['expired'] = 'n';
        }
        if (isset($settings['future'])) {
            $settings['future'] = ($settings['future'] == 'y' || $settings['future'] === true || $settings['future'] === 1) ? 'y' : 'n';
        } else {
            $settings['future'] = 'n';
        }
        
        if (!isset($settings['limit']) || empty($settings['limit'])) {
            $settings['limit'] = 100;
        }
        
        if (!isset($settings['order_field'])) {
            $settings['order_field'] = 'title';
        }
        if (!isset($settings['order_dir'])) {
            $settings['order_dir'] = 'asc';
        }

        $this->_ft->_init(array(
            'id' => $this->id,
            'name' => $this->name,
            'content_id' => $this->id,
            'content_type' => static::CONTENT_TYPE,
            'field_id' => $this->id,
            'field_name' => $this->input_name(),
            'settings' => $settings,
            'row' => $this->row()
        ));
        
        $this->_ft->settings = $settings;

        $output = $this->_ft->display_field($data);

        return $output;
    }

    // --------------------------------------------------------------------

    /**
     * Get existing relationships for this variable
     */
    protected function get_existing_relationships()
    {
        if (!$this->id) {
            return array();
        }
        
        return ee()->db
            ->select('child_id, `order`')
            ->from($this->_table)
            ->where('parent_id', $this->id)
            ->where('field_id', $this->id)
            ->where('grid_col_id', 0)
            ->where('grid_field_id', 0)
            ->where('grid_row_id', 0)
            ->where('fluid_field_data_id', 0)
            ->order_by('`order`', 'asc')
            ->get()
            ->result_array();
    }

    // --------------------------------------------------------------------

    /**
     * Validate var input
     */
    public function var_validate($var_data)
    {
        $this->setup_relationship_ft();
        
        $data = array(
            'data' => isset($var_data['data']) ? $var_data['data'] : array()
        );
        
        $result = $this->_ft->validate($data);
        
        if ($result !== true) {
            $this->error_msg = $result;
            return false;
        }
        
        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Save variable data
     * Override base class to ensure we never return null
     */
    public function save($var_data)
    {
        $result = $this->save_var_field($var_data);
        
        if ($result === false) {
            return false;
        }

        if ($result === null || $result === '') {
            return '';
        }
        
        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * Do something after the variable has been saved to the DB
     * Override base class to call post_save_var()
     */
    public function post_save($var_data)
    {
        $this->post_save_var($var_data);
        
        return $var_data;
    }

    // --------------------------------------------------------------------

    /**
     * Prep variable data for saving
     */
    public function save_var_field($var_data)
    {
        $this->setup_relationship_ft();
        
        // Extract data array from POST - relationship fieldtype sends data as array with 'data' key
        $data = array(
            'data' => isset($var_data['data']) ? array_filter($var_data['data'], 'is_numeric') : array()
        );

        $cache_name = $this->input_name();
        
        ee()->session->set_cache('Relationship_ft', $cache_name, array(
            'data' => $data['data']
        ));

        return '';
    }

    // --------------------------------------------------------------------

    /**
     * Do something after the variable has been saved to the DB
     */
    public function post_save_var($var_data)
    {
        if (empty($this->id) || !is_numeric($this->id)) {
            return;
        }
        
        $this->setup_relationship_ft();
        
        $cache_name = $this->input_name();
        $post = ee()->session->cache('Relationship_ft', $cache_name);
        
        if ($post === false || !isset($post['data']) || empty($post['data'])) {
            $this->clear_existing_relationships();
            return;
        }
        
        $this->clear_existing_relationships();
        
        $ships = array();
        $order = 1;
        
        foreach ($post['data'] as $child_id) {
            if (!is_numeric($child_id)) {
                continue;
            }
            
            $ships[] = array(
                'parent_id' => $this->id,
                'child_id' => (int) $child_id,
                'field_id' => $this->id,
                'order' => $order++,
                'grid_col_id' => 0,
                'grid_field_id' => 0,
                'grid_row_id' => 0,
                'fluid_field_data_id' => 0
            );
        }
        
        if (count($ships)) {
            ee()->db->insert_batch($this->_table, $ships);
        }
        
        if (isset(ee()->session->cache['Relationship_ft'][$cache_name])) {
            unset(ee()->session->cache['Relationship_ft'][$cache_name]);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Clear existing relationships for this variable
     */
    protected function clear_existing_relationships()
    {
        if (!$this->id) {
            return;
        }
        
        ee()->db
            ->where('parent_id', $this->id)
            ->where('field_id', $this->id)
            ->where('grid_col_id', 0)
            ->where('grid_field_id', 0)
            ->where('grid_row_id', 0)
            ->where('fluid_field_data_id', 0)
            ->delete($this->_table);
    }

    // --------------------------------------------------------------------

    /**
     * Display template tag output
     * Override replace_tag to use our custom implementation
     */
    public function replace_tag($tagdata = '')
    {
        // Get related entries
        $related = $this->get_related_entries();
        
        if (empty($tagdata)) {
            if (empty($related)) {
                return '';
            }
            $entry_ids = array();
            foreach ($related as $row) {
                $entry_ids[] = $row[$this->name . ':entry_id'];
            }
            return implode('|', $entry_ids);
        }
        
        return ee()->TMPL->parse_variables($tagdata, $related);
    }

    // --------------------------------------------------------------------

    /**
     * Get related entries with full data
     */
    protected function get_related_entries()
    {
        $relationships = $this->get_existing_relationships();
        
        if (empty($relationships)) {
            return array();
        }
        
        $child_ids = array_column($relationships, 'child_id');
        $order_map = array();
        foreach ($relationships as $rel) {
            $order_map[$rel['child_id']] = $rel['order'];
        }
        
        $entries = ee('Model')
            ->get('ChannelEntry')
            ->filter('entry_id', 'IN', $child_ids)
            ->with('Channel', 'Author')
            ->all();
        
        $data = array();
        $entries_by_id = array();
        
        foreach ($entries as $entry) {
            $entries_by_id[$entry->entry_id] = $entry;
        }
        
        uasort($order_map, function($a, $b) {
            return $a - $b;
        });
        
        foreach ($order_map as $entry_id => $order) {
            if (isset($entries_by_id[$entry_id])) {
                $entry = $entries_by_id[$entry_id];
                $data[] = array(
                    $this->name . ':entry_id' => $entry->entry_id,
                    $this->name . ':title' => $entry->title,
                    $this->name . ':url_title' => $entry->url_title,
                    $this->name . ':entry_date' => $entry->entry_date,
                    $this->name . ':edit_date' => $entry->edit_date,
                    $this->name . ':channel_id' => $entry->channel_id,
                    $this->name . ':channel_title' => $entry->Channel->channel_title,
                    $this->name . ':author_id' => $entry->author_id,
                    $this->name . ':author' => $entry->Author->screen_name,
                    $this->name . ':status' => $entry->status,
                    $this->name . ':order' => $order,
                    $this->name . ':count' => count($data) + 1,
                    $this->name . ':total_results' => count($order_map)
                );
            }
        }
        
        return $data;
    }

    // --------------------------------------------------------------------

    /**
     * Do stuff after a variable has been deleted
     * Override delete to use our custom implementation
     */
    public function delete()
    {
        // Delete all relationships where this variable is parent
        if ($this->id) {
            ee()->db
                ->where('parent_id', $this->id)
                ->where('field_id', $this->id)
                ->where('grid_col_id', 0)
                ->where('grid_field_id', 0)
                ->where('grid_row_id', 0)
                ->where('fluid_field_data_id', 0)
                ->delete($this->_table);
        }
    }

    // --------------------------------------------------------------------
}

// End of file vt.pro_relationship.php

