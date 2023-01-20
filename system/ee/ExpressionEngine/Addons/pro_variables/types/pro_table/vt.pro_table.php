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
 * Pro Table variable type
 */
class Pro_table extends Pro_variables_type
{
    public $info = array(
        'name' => 'Table'
    );

    public $default_settings = array(
        'columns' => 'Column 1 | Column 2',
        'wide' => 'n'
    );

    // --------------------------------------------------------------------

    /**
     * Display settings sub-form for this variable type
     */
    public function display_settings()
    {
        $rows = array(
            array(
                'title' => 'columns',
                'desc' => 'columns_help',
                'fields' => array(
                    $this->setting_name('columns') => array(
                        'type' => 'text',
                        'value' => $this->settings('columns')
                    )
                )
            )
        );

        return $this->settings_form($rows);
    }

    /**
     * Display input field for regular user
     */
    public function display_field($var_data)
    {
        // Get current settings for table
        $cols = $this->settings('columns');
        $cols = array_map('trim', explode('|', $cols));

        // Return the view based on these vars
        return PVUI::view_field('table', array(
            'name'   => $this->input_name(),
            'var_id' => $this->id(),
            'cols'   => $cols,
            'rows'   => $this->get_rows()
        ));
    }

    /**
     * Are we displaying a wide field?
     */
    public function wide()
    {
        return ($this->settings('wide') == 'y');
    }

    /**
     * Prep variable data for saving
     */
    public function save($var_data)
    {
        // Initiate rows data
        $rows = array();
        $data = '';

        if (! empty($var_data) && is_array($var_data)) {
            // Loop through posted data and strip out empty rows
            foreach ($var_data as $row) {
                $row = array_filter($row, function ($str) {
                    return (bool) strlen(trim($str));
                });

                if (! empty($row)) {
                    $rows[] = $row;
                }
            }

            // Overwrite data if there are rows present
            if ($rows) {
                $data = '<!--' . $this->encode($rows) . '-->';
            }
        }

        return $data;
    }

    /**
     * Display output, possible formatting
     */
    public function replace_tag($tagdata)
    {
        // Extract array from var data to see if this is valid
        if ($rows = $this->get_rows()) {
            // Do we actually have tagdata? If not, just return the whole table
            if ($tagdata) {
                // Initiate array for the view
                $data = array();

                // Change order of rows if sort is 'desc' or 'random'
                if (($sort = ee()->TMPL->fetch_param('sort', 'asc')) != 'asc') {
                    switch ($sort) {
                        case 'desc':
                            $rows = array_reverse($rows);

                            break;
                        case 'random':
                            shuffle($rows);

                            break;
                    }
                }

                // Limit the rows
                if (($limit = ee()->TMPL->fetch_param('limit')) && is_numeric($limit)) {
                    $rows = array_slice($rows, 0, $limit);
                }

                // Loop through rows
                foreach ($rows as $row_nr => $row) {
                    // Init row cells
                    $cells = array();

                    // For each cell, add {cell_x} to the row cells
                    foreach ($row as $cell_nr => $cell_content) {
                        $cells['cell_' . ($cell_nr + 1)] = $cell_content;
                    }

                    // Add the cells to the view data
                    $data[] = $cells;
                }

                // Return parsed template
                return ee()->TMPL->parse_variables($tagdata, $data);
            } else {
                return $this->data();
            }
        } else {
            return ee()->TMPL->no_results();
        }
    }

    // --------------------------------------------------------------------

    /**
     * Get encoded data from variable contents
     */
    private function get_rows()
    {
        if (preg_match('/<!--(.*?)-->/', $this->data(), $match) && ($rows = $this->decode($match[1]))) {
            return $rows;
        } else {
            return array();
        }
    }

    /**
     * Encode an array
     */
    private function encode($val)
    {
        return is_array($val) ? base64_encode(serialize($val)) : $val;
    }

    /**
     * Decode an array
     */
    private function decode($val)
    {
        return is_array($val) ? $val : unserialize(base64_decode($val));
    }
}
