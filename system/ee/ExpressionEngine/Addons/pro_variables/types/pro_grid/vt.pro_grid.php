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
 * Pro Grid variable type
 */
class Pro_grid extends Pro_variables_type
{
    public $info = array(
        'name' => 'Grid',
        'var_requires' => array(
            'ee'   => '2.7.0',
            'grid' => '1.0'
        )
    );
    public $default_settings = array(
        'grid_min_rows' => '',
        'grid_max_rows' => '',
        'allow_reorder' => 'y',
        'vertical_layout' => 'n'
    );
    protected $ft = 'grid';
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
        $data = array();
        // Get the keys
        foreach ($this->default_settings as $key => $default) {
            $data[$key] = ee('Request')->post($key, $default);
        }

        $this->setup_ft();
        $result = $this->call_ft('validate_settings', $data);

        // If the result is not valid, we loop through all the errors and show them
        if ($result->isNotValid()) {
            $errors = [];
            foreach ($result->getAllErrors() as $field => $resultErrors) {
                if (substr($field, 0, strlen('grid[')) === 'grid[') {
                    $errors[] = $field . ': ' . current($result->getErrors($field));
                }
            }

            ee()->output->show_user_error('submission', $errors);
        }

        return $this->call_ft(__FUNCTION__, $data);
    }

    /**
     * post_save_settings
     */
    public function post_save_settings()
    {
        $this->setup_ft();

        return $this->call_ft(__FUNCTION__, $this->settings());
    }

    /**
     * Display Pro Variables field
     */
    public function display_field($var_data)
    {
        // Check if the table exists
        // When duplicating groups, Grid won't create new tables, and throws a hissyfit
        // With this, we avoid that and show an error message instead
        $table = ee()->db->dbprefix . implode('_', array(
            static::CONTENT_TYPE,
            'grid_field',
            $this->id()
        ));
        if (! ee()->db->table_exists($table)) {
            return "Table <code>{$table}</code> was not found. Please check your variable settings.";
        }

        // We're ok? Good!
        $this->setup_ft();

        return $this->call_ft(__FUNCTION__, $var_data);
    }

    /**
     * This field is wide
     */
    public function wide()
    {
        return true;
    }

    /**
     * This field is Grid
     */
    public function grid()
    {
        return true;
    }

    /**
     * Save Pro Variable field
     */
    public function save($var_data)
    {
        $this->setup_ft();
        // Call validate
        $result = $this->call_ft('validate', $var_data);
        // Get the validated results
        if (isset($result['value'])) {
            $var_data = $result['value'];
        }

        // Check for errors
        if (empty($result['error'])) {
            // No error? Call save and return
            $val = $this->call_ft(__FUNCTION__, $var_data);

            return is_null($val) ? '' : $val;
        } else {
            // If there is an error, remember it and return FALSE,
            // so it gets skipped
            $this->error_msg = $result['error'];

            return false;
        }
    }

    /**
     * Post processing after save
     */
    public function post_save($var_data)
    {
        $this->setup_ft();

        return $this->call_ft(__FUNCTION__, $var_data);
    }

    /**
     * Display template tag output
     */
    public function replace_tag($tagdata)
    {
        $this->setup_ft();
        // @TODO: remove, as we shouldn't have to define an entry_id.
        $this->set_ft_property('row', array('entry_id' => $this->id()));
        // Alternative method?
        $fn = 'replace_' . ee()->TMPL->fetch_param('modifier', 'tag');

        return $this->call_ft($fn, $this->data(), ee()->TMPL->tagparams, $tagdata);
    }

    /**
     * Clean up after yourself
     */
    public function delete()
    {
        $this->setup_ft();
        $this->call_ft('settings_modify_column', array(
            'field_id'  => $this->id,
            'ee_action' => 'delete'
        ));
    }
}
// End of vt.pro_grid.php
