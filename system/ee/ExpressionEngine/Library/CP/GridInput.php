<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP;

/**
 * CP Grid Input Table
 */
class GridInput extends Table
{
    protected $cp;
    protected $ee_config;
    protected $javascript;

    /**
     * GridInput currently provides these options for configuration:
     *
     * 'field_name' - Name of the field the child fields will live under in POST,
     *     also is set as the table's HTML ID
     * 'reorder' - Whether or not to allow users to reorder the rows in this Grid
     * 'grid_min_rows' - Minimum number of rows this Grid should accept
     * 'grid_max_rows' - Maximum number of rows this Grid should accept
     *
     * The rest of the config items have good defaults for use in Grid, it's
     * probably best not to set any other config items.
     *
     * @param	array 	$config		See Table constructor for options
     * @param	object 	$cp			EE CP library, used for loading assets
     * @param	object 	$ee_config	EE config library, used for loading assets
     * @param	object 	$javascript	EE javascript library, used for loading assets
     */
    public function __construct($config = array(), $cp = null, $ee_config = null, $javascript = null)
    {
        $this->cp = $cp;
        $this->ee_config = $ee_config;
        $this->javascript = $javascript;

        // These should be our default to properly initialize a Table class
        // for use as a Grid input
        $defaults = array(
            'limit' => 0,
            'sortable' => false,
            'grid_input' => true,
            'reorder' => true,
            'field_name' => 'grid',
            'grid_min_rows' => 0,
            'grid_max_rows' => '',
            'show_add_button' => true,
        );

        parent::__construct(array_merge($defaults, $config));
    }

    /**
     * Set the empty row elements for new/empty rows in a Grid input table,
     * this row will be the basis for any new rows added and should contain
     * the HTML of an empty field in each column
     *
     * @param	array	$row	Array of empty field elements to be duplicated
     *                   		for each new row the user creates
     * @return  void
     */
    public function setBlankRow($row)
    {
        if (count($row) != count($this->columns)) {
            throw new \InvalidArgumentException('Grid must have the same number of columns as the set columns. Got ' . count($row) . ' but need ' . count($this->columns) . '.');
        }

        $this->config['grid_blank_row'] = $row;

        // Call this in case blank row is being set after data is set
        $this->setData($this->data);
    }

    /**
     * Set and normalizes the data for the table.
     *
     * Overrides the parent setData to add our blank row to the bottom.
     *
     * @param	array 	$data	Table data
     * @return  void
     */
    public function setData($data)
    {
        if (isset($this->config['grid_blank_row'])) {
            // Prepend blank row to array instead of prepend so that the DOM
            // is consistent when loading new data vs loading new data
            array_unshift($data, array(
                'attrs' => array('class' => 'grid-blank-row hidden'),
                'columns' => $this->config['grid_blank_row']
            ));
        }

        parent::setData($data);
    }

    /**
     * Returns the table configuration and data in a format ready to be
     * processed by the _shared/table view.
     *
     * This method override for Grid also namepaces any form inputs inside
     * the table to nest all the inputs inside a single array for when the
     * form is submitted.
     *
     * @param	URL	$base_url	URL object of the base URL used for setting
     *                      	the search and sort criteria for sorting and
     *                      	pagination URLs
     * @return	array			Array of view variables, structure is below
     */
    public function viewData($base_url = null)
    {
        $view_data = parent::viewData($base_url);

        // We'll use this in our lambda functions below
        $grid = $this;

        // Namespace existing rows in Grid
        foreach ($view_data['data'] as &$row) {
            $row['columns'] = array_map(function ($field) use ($grid, $row) {
                if (isset($row['attrs']['row_id']) && is_numeric($row['attrs']['row_id'])) {
                    $row_id = 'row_id_' . $row['attrs']['row_id'];
                } elseif (! isset($row['attrs']['row_id'])) {
                    $row_id = 'new_row_0';
                } else {
                    $row_id = $row['attrs']['row_id'];
                }

                $field_content_key = isset($field['html']) ? 'html' : 'content';

                $field[$field_content_key] = $grid->namespaceForGrid($field[$field_content_key], $row_id);

                return $field;
            }, $row['columns']);

            // This no longer needs to be here
            unset($row['attrs']['row_id']);
        }

        // Make the field name available so we can set as table's ID
        $view_data['grid_field_name'] = $this->config['field_name'];

        $view_data['validation_errors'] = (isset($this->config['validation_errors'])) ? $this->config['validation_errors'] : array();

        return $view_data;
    }

    /**
     * Performes find and replace for input names in order to namespace them
     * for a POST array
     *
     * @param	string	$search		String to search
     * @param	string	$replace	String to use for replacement
     * @return	string	String with namespaced inputs
     */
    public function namespaceInputs($search, $replace)
    {
        return preg_replace(
            '/(<(?:input|select|textarea)[^>]*)name=["\']([^"\'\[\]]+)([^"\']*)["\']/',
            $replace,
            $search
        );
    }

    /**
     * Namespaces inputs specifically for a Grid field
     *
     * @param	string	$search	String to search
     * @param	string	$row_id	Unique identifier for row
     * @return	string	String with namespaced inputs
     */
    public function namespaceForGrid($search, $row_id = 'new_row_0')
    {
        $str = $this->namespaceInputs(
            $search,
            '$1name="' . $this->config['field_name'] . '[rows][' . $row_id . '][$2]$3"'
        );

        $str = preg_replace(
            '/data-input-value=["\']([^"\'\[\]]+)([^"\']*)["\']/',
            'data-input-value="' . $this->config['field_name'] . '[rows][' . $row_id . '][$1]$2"',
            $str
        );

        return $str;
    }

    /**
     * Loads necessary JS and CSS
     */
    public function loadAssets()
    {
        static $assets_loaded;

        if (! $assets_loaded) {
            $this->cp->add_js_script('ui', 'sortable');
            $this->cp->add_js_script('file', 'cp/sort_helper');
            $this->cp->add_js_script('plugin', 'ui.touch.punch');
            $this->cp->add_js_script('plugin', 'ee_table_reorder');
            $this->cp->add_js_script('file', 'cp/grid');

            $assets_loaded = true;
        }

        $settings = array(
            'grid_min_rows' => $this->config['grid_min_rows'],
            'grid_max_rows' => $this->config['grid_max_rows']
        );

        $name = $this->config['field_name'];

        if (REQ == 'CP') {
            // Set settings as a global for easy reinstantiation of field
            // by third parties
            $this->javascript->set_global('grid_field_settings.' . $name, $settings);

            // getElementById instead of $('#...') for field names that have
            // brackets in them
            $this->javascript->output('EE.grid(document.getElementById("' . $name . '"));');
        }
        // Channel Form
        else {
            $this->javascript->output('EE.grid(document.getElementById("' . $name . '"), ' . json_encode($settings) . ');');
        }
    }
}

// EOF
