<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Low Date variable type
 *
 * @package        low_variables
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-variables
 * @copyright      Copyright (c) 2009-2020, Low
 */
class Low_date extends Low_variables_type
{
    public $info = array(
        'name' => 'Date',
        'var_requires' => array(
            'date' => '1.0'
        )
    );
    // Field type to use
    protected $ft = 'date';
    /**
         * Display settings sub-form for this variable type
         */
    public function display_settings()
    {
        $this->setup_ft();

        return $this->call_ft(__FUNCTION__, $this->settings());
    }

    /**
     * Return the settings to save
     */
    public function save_settings()
    {
        $this->setup_ft();

        return $this->call_ft(__FUNCTION__, $this->settings());
    }

    /**
     * Display Low Variables field
     */
    public function display_field($var_data)
    {
        // Set field name to 'entry_date' to fool 'em
        $this->name = 'entry_date';
        // Continue as normal
        $this->setup_ft();
        // Get the field
        $field = $this->call_ft(__FUNCTION__, $var_data);
        // Replace the entry_date back
        $this->name = $this->row('variable_name');
        $field = str_replace('entry_date', $this->input_name(), $field);

        return $field;
    }

    /**
     * Save Low Variable field
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
        // Alternative method?
        $fn = 'replace_' . ee()->TMPL->fetch_param('modifier', 'tag');

        return $this->call_ft($fn, $this->data(), ee()->TMPL->tagparams, $tagdata);
    }
}
// End of vt.low_rte.php
