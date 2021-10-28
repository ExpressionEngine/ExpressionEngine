<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Publish Layout
 */
class Layout
{
    public $custom_layout_fields = array();

    public function duplicate_layout($dupe_id, $channel_id)
    {
        $layouts = ee('Model')->get('ChannelLayout')
            ->filter('channel_id', $dupe_id)
            ->all();

        if (! $layouts) {
            return;
        }

        // open each one
        foreach ($layouts as $layout) {
            $data = $layout->getValues();
            unset($data['layout_id']);

            $data['channel_id'] = $channel_id;

            ee('Model')->make('ChannelLayout', $data)->save();
        }
    }

    public function delete_channel_layouts($channel_id)
    {
        ee('Model')->get('ChannelLayout')
            ->filter('channel_id', $channel_id)
            ->delete();
    }

    public function edit_layout_fields($field_info, $channel_id)
    {
        ee()->load->model('layout_model');

        if (! is_array($channel_id)) {
            $channel_id = array($channel_id);
        }

        ee()->layout_model->edit_layout_fields($field_info, 'edit_fields', $channel_id);
    }

    /**
     * Updates saved publish layouts
     *
     * @access	public
     * @param	array
     * @return	bool
     */
    public function sync_layout($fields = array(), $channel_id = '', $changes_only = true)
    {
        ee()->load->model('layout_model');

        $new_settings = array();
        $changed = array();
        $hide_fields = '';
        $hide_tab_fields = array();
        $show_fields = '';
        $show_tab_fields = array();
        $delete_fields = array();

        $default_settings = array(
            'visible' => true,
            'collapse' => false,
            'htmlbuttons' => false,
            'width' => '100%'
        );

        $layout_fields = array('enable_versioning', 'comment_system_enabled');

        foreach ($layout_fields as $field) {
            if (isset($fields[$field])) {
                $new_settings[$field] = $fields[$field];
            }
        }

        ee()->db->select('enable_versioning, comment_system_enabled');
        ee()->db->where('channel_id', $channel_id);
        $current = ee()->db->get('channels');

        if ($current->num_rows() > 0) {
            $row = $current->row_array();

            foreach ($new_settings as $field => $val) {
                if ($val != $row[$field]) { // Undefined index: show_author_menu
                    $changed[$field] = $val;
                }
            }
        }

        if (! empty($changed)) {
            foreach ($changed as $field => $val) {
                switch ($field) {
                    case 'enable_versioning':

                        if ($val == 'n') {
                            $hide_tab_fields['revisions'] = array('revisions');
                        } else {
                            $show_tab_fields['revisions'] = array('revisions' => $default_settings);
                        }

                        break;
                    case 'comment_system_enabled':

                        if ($val == 'n') {
                            $delete_fields[] = 'comment_expiration_date';
                        } else {
                            $show_tab_fields['date'] = array('comment_expiration_date' => $default_settings);
                        }

                        break;
                    }
            }
        }

        if (! empty($hide_tab_fields)) {
            //ee()->layout_model->edit_layout_fields($hide_tab_fields, 'hide_tab_fields', $channel_id, TRUE);
            ee()->layout_model->update_layouts($hide_tab_fields, 'delete_tabs', $channel_id);
        }

        if (! empty($show_tab_fields)) {
            //ee()->layout_model->edit_layout_fields($show_tab_fields, 'show_tab_fields', $channel_id, TRUE);
            ee()->layout_model->update_layouts($show_tab_fields, 'add_tabs', $channel_id);
        }

        if (! empty($delete_fields)) {
            ee()->layout_model->update_layouts($delete_fields, 'delete_fields', $channel_id);
        }

        return;
    }

    /**
     * Updates saved publish layouts
     *
     * @access	public
     * @param	array
     * @return	bool
     */
    public function delete_layout_tabs($tabs = array(), $namespace = '', $channel_id = array())
    {
        if (! is_array($tabs) or count($tabs) == 0) {
            return false;
        }

        $layouts = ee('Model')->get('ChannelLayout')
            ->filter('site_id', ee()->config->item('site_id'))
            ->all();

        if (! $layouts) {
            return false;
        }

        $tab_ids = array_keys($tabs);

        foreach ($layouts as $layout) {
            $old_field_layout = $layout->field_layout;
            $new_field_layout = array();

            foreach ($old_field_layout as $tab) {
                if (in_array($tab['id'], $tab_ids)) {
                    continue;
                }
                $new_field_layout[] = $tab;
            }

            $layout->field_layout = $new_field_layout;
            $layout->save();
        }

        return true;
    }

    /**
     * Add new tabs and associated fields to saved publish layouts
     *
     * @access	public
     * @param	array
     * @return	bool
     */
    public function add_layout_tabs($tabs = array(), $namespace = '', $channel_id = array())
    {
        if (! is_array($tabs) or count($tabs) == 0) {
            return false;
        }

        $layouts = ee('Model')->get('ChannelLayout')
            ->filter('site_id', ee()->config->item('site_id'))
            ->all();

        if (! $layouts) {
            return false;
        }

        $new_tabs = array();

        foreach ($tabs as $key => $val) {
            $tab = array(
                'id' => strtolower($key),
                'name' => $key,
                'visible' => true,
                'fields' => array()
            );

            foreach ($val as $field_name => $data) {
                if (! empty($namespace)) {
                    $field_name = $namespace . '__' . $field_name;
                    $tab['fields'][] = array(
                        'field' => $field_name,
                        'visible' => true,
                        'collapsed' => false
                    );
                }
            }

            $new_tabs[] = $tab;
        }

        foreach ($layouts as $layout) {
            $field_layout = $layout->field_layout;

            foreach ($new_tabs as $tab) {
                $field_layout[] = $tab;
            }

            $layout->field_layout = $field_layout;
            $layout->save();
        }
    }

    /**
     * Adds new fields to the saved publish layouts, creating the default tab if required
     *
     * @access	public
     * @param	array
     * @param	int
     * @return	bool
     */
    public function add_layout_fields($tabs = array(), $channel_id = array())
    {
        if (! is_array($channel_id)) {
            $channel_id = array($channel_id);
        }

        if (! is_array($tabs) or count($tabs) == 0) {
            return false;
        }

        $layouts = ee('Model')->get('ChannelLayout')
            ->filter('site_id', ee()->config->item('site_id'));

        if (count($channel_id) > 0) {
            $layouts->filter('channel_id', $channel_id);
        }

        $layouts = $layouts->all();

        if (! $layouts) {
            return false;
        }

        foreach ($tabs as $key => $fields) {
            $tab_id = strtolower($key);
            $found = false;

            foreach ($layouts as $layout) {
                $field_layout = $layout->field_layout;

                foreach ($field_layout as &$tab) {
                    if ($tab['id'] == $tab_id) {
                        $found = true;
                        foreach ($fields as $name => $info) {
                            $tab['fields'][] = array(
                                'field' => $name,
                                'visible' => true,
                                'collapsed' => false
                            );
                        }

                        $layout->field_layout = $field_layout;
                        $layout->save();
                    }
                }
            }

            if (! $found) {
                $this->add_layout_tabs(array($key => $fields), '', $channel_id);
            }
        }

        return true;
    }

    /**
     * Deletes fields from the saved publish layouts
     *
     * @access	public
     * @param	array or string
     * @param	int
     * @return	bool
     */
    public function delete_layout_fields($tabs, $channel_id = array())
    {
        if (! is_array($channel_id)) {
            $channel_id = array($channel_id);
        }

        $clean_tabs = array();

        // note- this is a simple array of field ids- so let's break them down to that before sending them on

        if (! is_array($tabs)) {
            $clean_tabs = array($tabs);
        } else {
            foreach ($tabs as $key => $val) {
                // We do this in case they sent the full tab array instead of an array of field names
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        if (isset($tabs[$key][$k]['visible'])) {
                            $clean_tabs[] = $k;
                        }
                    }
                } else {
                    $clean_tabs[] = $val;
                }
            }
        }

        ee()->load->model('layout_model');

        return ee()->layout_model->update_layouts($clean_tabs, 'delete_fields', $channel_id);
    }
}
// END CLASS

// EOF
