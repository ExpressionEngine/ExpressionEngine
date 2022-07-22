<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Pro RTE variable type
 */
class Pro_rte extends Pro_variables_type
{
    public $info = array(
        'name' => 'Rich Text Editor',
        'var_requires' => array(
            'rte' => '1.0.0'
        )
    );
    public $default_settings = array(
        'field_ta_rows'        => '10',
        'field_text_direction' => 'ltr',
        'pro_rte_wide'         => 'y',
    );
    protected $ft = 'rte';
    /**
         * Display settings sub-form for this variable type
         */
    public function display_settings()
    {
        $this->setup_ft();
        // Get settings
        $settings = $this->call_ft(__FUNCTION__, $this->settings());

        return $settings;
    }

    /**
     * Return the settings to save
     */
    public function save_settings()
    {
        $data = array();
        // Get the keys
        foreach ($this->default_settings as $key => $default) {
            $data[$key] = ee('Request')->post($key, $default);
        }

        // Don't call the native ft, which fucks up our custom settings
        // $this->setup_ft();
        // $data = $this->call_ft(__FUNCTION__, $data);

        return $data;
    }

    /**
     * Display Pro Variables field
     */
    public function display_field($var_data)
    {
        $this->setup_ft();

        return $this->call_ft(__FUNCTION__, $var_data);
    }

    /**
     * Display this field in a wide format
     */
    public function wide()
    {
        return $this->settings('pro_rte_wide') == 'y';
    }

    /**
     * Save Pro Variable field
     */
    public function save($var_data)
    {
        $this->setup_ft();

        return $this->call_ft(__FUNCTION__, $var_data);
    }

    /**
     * Mimic the replace_tag method from the ft.
     */
    public function replace_tag($tagdata)
    {
        $this->setup_ft();
        // @TODO: remove, as EE should load this
        ee()->load->library('typography');
        // @TODO: remove, as EE should provide fallbacks for this
        $this->set_ft_property('row', array(
            'channel_html_formatting' => 'all',
            'channel_auto_link_urls'  => 'n',
            'channel_allow_img_urls'  => 'y'
        ));

        return $this->call_ft(__FUNCTION__, $this->data(), ee()->TMPL->tagparams, $tagdata);
    }
}
// End of vt.pro_rte.php
