<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
require_once PATH_ADDONS . 'structure/addon.setup.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

class Structure_ft extends \EE_Fieldtype
{
    public $structure;
    public $sql;
    public $site_pages;
    public $site_id;

    public $info = array(
        'name'    => 'Structure',
        'version' => STRUCTURE_VERSION
    );
    /**
     * A list of operators that this field type supports
     *
     * @var array
     */
    public $supportedEvaluationRules = ['isEmpty', 'isNotEmpty'];

    public $defaultEvaluationRule = 'isNotEmpty';

    /**
     * Constructor
     *
     * @access  public
     */
    public function __construct()
    {
        parent::__construct();

        $this->sql = new Sql_structure();

        if (! $this->sql->module_is_installed()) {
            return false;
        }

        $this->site_pages = $this->sql->get_site_pages();
        $this->site_id = ee()->config->item('site_id');
    }

    public function install()
    {
        return array(
            'structure_list_type' => 'pages'
        );
    }

    public function update($from = '')
    {
        return true;
    }

    public function accepts_content_type($name)
    {
        return ($name == 'channel' || $name == 'grid' || $name == 'blocks/1');
    }

    /**
     * Normal Fieldtype Display
     */
    public function display_field($data)
    {
        $channel_id = isset($this->settings['structure_list_type']) && is_numeric($this->settings['structure_list_type']) ? $this->settings['structure_list_type'] : false;

        return $this->build_dropdown($data, $this->field_name, $this->field_id, $channel_id);
    }

    /**
     * Matrix Cell Display
     */
    public function display_cell($data)
    {
        return $this->build_dropdown($data, $this->cell_name, $this->field_id);
    }

    public function grid_display_cell($data)
    {
        return $this->display_cell($data);
    }

    public function grid_display_settings($data)
    {
        $html = $this->_get_dropdown($data);

        $settings = $this->display_settings($data);

        $grid_settings = array();

        foreach ($settings as $value) {
            $grid_settings[$value['label']] = $value['settings'];
        }

        return $grid_settings;
    }

    /**
     * Low Variables Fieldtype Display
     *
     * @return int entry_id of selected URL
     */
    public function display_var_field($data)
    {
        return $this->build_dropdown($data, $this->field_name);
    }

    /**
     * Low Variables Fieldtype Var Tag
     *
     * @return string url
     */
    public function display_var_tag($var_data, $tagparams, $tagdata)
    {
        // this is to fix a bug in EE3.4.x. It should not replace anything otherwise
        $ee_url = ee()->functions->create_page_url($this->site_pages['url'], $this->site_pages['uris'][$var_data], false);
        $url = str_replace("{base_url}/", ee()->config->item('base_url'), $ee_url);

        // Hook to override the url we generate for each structure link (ex: Transcribe's multi-lingual language domains).
        if (ee()->extensions->active_hook('structure_generate_page_url_end') === true) {
            $url = ee()->extensions->call('structure_generate_page_url_end', $url);
        }

        return $url;
    }

    public function display_settings($data)
    {
        $html = $this->_get_dropdown($data);

        return $this->_get_ee3_dropdown($html);
    }

    public function _get_dropdown($data)
    {
        $selected = structure_array_get($data, 'structure_list_type', null);

        $rows = array();
        $listing_channels = $this->sql->get_structure_channels('listing');

        $dropdown_options = array('pages' => 'Pages Tree');
        if ($listing_channels) {
            foreach ($listing_channels as $id => $channel) {
                $dropdown_options[$id] = 'Listing Channel: ' . $channel['channel_title'];
            }
        }

        return form_dropdown('structure_list_type', $dropdown_options, $selected);
    }

    public function _get_ee3_dropdown($html)
    {
        $settings = array(
            array(
                'title' => 'Populate selection with...',
                'fields' => array(
                    'structure_list_type' => array(
                        'type' => 'html',
                        'content' => $html,
                    )
                )
            )
        );

        return array('field_options_structure' => array(
            'label' => 'field_options',
            'group' => 'structure',
            'settings' => $settings
        ));
    }

    public function _get_grid_dropdown($data)
    {
        $rows = array();
        $listing_channels = $this->sql->get_structure_channels('listing');

        $dropdown_options = array('pages' => 'Pages Tree');
        if ($listing_channels) {
            foreach ($listing_channels as $id => $channel) {
                $dropdown_options[$id] = 'Listing Channel: ' . $channel['channel_title'];
            }
        }

        return $dropdown_options;
    }

    public function save_settings($data)
    {
        return array(
            'structure_list_type' => ee()->input->post('structure_list_type')
        );
    }

    public function grid_save_settings($data)
    {
        return array(
            'structure_list_type' => current(array_values($data))
        );
    }

    /**
    * Structure Pages Select Dropdown
    *
    * @return string select HTML
    * @access private
    */
    private function build_dropdown($data, $name, $field_id = false, $channel_id = false)
    {
        $structure_data = $channel_id ? $this->sql->get_listing_channel_data($channel_id) : $this->sql->get_data();

        $exclude_status_list[] = "closed";
        $closed_parents = array();

        foreach ($structure_data as $key => $entry_data) {
            if (in_array(strtolower($entry_data['status']), $exclude_status_list) || (isset($entry_data['parent_id']) && in_array($entry_data['parent_id'], $closed_parents))) {
                $closed_parents[] = $entry_data['entry_id'];
                unset($structure_data[$key]);
            }
        }

        $structure_data = array_values($structure_data);

        $options = array();
        $options[''] = "-- None --";

        foreach ($structure_data as $page) {
            if (isset($page['depth'])) {
                $options[$page['entry_id']] = str_repeat('--', $page['depth']) . $page['title'];
            } else {
                $options[$page['entry_id']] = $page['title'];
            }
        }

        return form_dropdown($name, $options, $data);
    }

    public function replace_tag($data, $params = '', $tagdata = '')
    {
        if ($data != "" && is_numeric($data)) {
            $uri = isset($this->site_pages['uris'][$data]) ? $this->site_pages['uris'][$data] : null;

            // RESTORED 2017-04-06
            // This was commented out 2015-07-20 to "remove index.php from FieldType Output" but if you don't
            // want "index.php" there, just change your EE's Site Index Page" setting to nothing.
            return Structure_Helper::remove_double_slashes(trim(ee()->functions->fetch_site_index(0, 0), '/') . $uri);

            // return Structure_Helper::remove_double_slashes("/" . $uri);
        }

        return false;
    }
}

// END Structure_ft class

/* End of file ft.structure.php */
