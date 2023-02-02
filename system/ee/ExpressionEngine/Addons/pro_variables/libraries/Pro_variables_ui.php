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

use EllisLab\ExpressionEngine\Library\DataStructure\Tree\TreeFactory;

/**
 * Pro Variables UI class
 */
class Pro_variables_ui
{
    /**
     * Alias this class
     */
    public function __construct()
    {
        class_alias('Pro_variables_ui', 'PVUI');
    }

    // --------------------------------------------------------------------

    /**
     * Separators
     */
    public static function separators()
    {
        return array(
            'newline' => "\n",
            'pipe'    => '|',
            'comma'   => ','
        );
    }

    /**
     * Multi interfaces
     */
    public static function interfaces()
    {
        return array(
            'select',
            'drag-list'
        );
    }

    // --------------------------------------------------------------------

    /**
     * Return an array for settings form
     */
    public static function setting($which, $name, $value, $extra = null)
    {
        switch ($which) {
            // -------------------------------------
            //  Options textarea
            // -------------------------------------

            case 'options':
                return array(
                    'title' => 'variable_options',
                    'desc' => 'variable_options_help',
                    'fields' => array(
                        $name => array(
                            'type'  => 'textarea',
                            'value' => $value
                        )
                    )
                );

                break;

                // -------------------------------------
                //  Select Multiple boolean
                // -------------------------------------

            case 'multiple':
                return array(
                    'title' => 'allow_multiple_items',
                    'fields' => array(
                        $name => array(
                            'type'  => 'yes_no',
                            'value' => $value ?: 'n'
                        )
                    )
                );

                break;

                // -------------------------------------
                //  Select Separator for multi-fields
                // -------------------------------------

            case 'separator':
                return array(
                    'title' => 'separator_character',
                    'fields' => array(
                        $name => array(
                            'type'  => 'select',
                            'value' => $value,
                            'choices' => static::separator_options()
                        )
                    )
                );

                break;

                // -------------------------------------
                //  Select Interface for multi-fields
                // -------------------------------------

            case 'interface':
                return array(
                    'title' => 'multi_interface',
                    'fields' => array(
                        $name => array(
                            'type'  => 'select',
                            'value' => $value,
                            'choices' => static::interface_options($extra)
                        )
                    )
                );

                break;

                // -------------------------------------
                //  Select text direction for text fields
                // -------------------------------------

            case 'dir':
                return array(
                    'title' => 'text_direction',
                    'fields' => array(
                        $name => array(
                            'type'  => 'inline_radio',
                            'value' => $value,
                            'choices' => array(
                                'ltr' => lang('text_direction_ltr'),
                                'rtl' => lang('text_direction_rtl')
                            )
                        )
                    )
                );

                break;

                // -------------------------------------
                //  Show wide field boolean
                // -------------------------------------

            case 'wide':
                return array(
                    'title' => 'wide_field',
                    'fields' => array(
                        $name => array(
                            'type'  => 'yes_no',
                            'value' => $value ?: 'n'
                        )
                    )
                );

                break;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Return array for separator choice
     */
    public static function separator_options()
    {
        $options = array_keys(static::separators());
        $options = array_combine($options, array_map('lang', $options));

        return $options;
    }

    /**
     * Return array for interface choice
     */
    public static function interface_options($extra = null)
    {
        $interfaces = static::interfaces();

        if ($extra) {
            $interfaces[] = $extra;
        }

        $options = array();

        foreach ($interfaces as $option) {
            $options[$option] = lang($option);
        }

        return $options;
    }

    // --------------------------------------------------------------------

    /**
     * Return an on/off link
     *
     * @access     private
     * @param      string
     * @param      string
     * @return     string
     */
    public static function onoff($href, $status)
    {
        // Convert status to boolean
        if (is_string($status)) {
            $status = ($status == 'y');
        }

        return array(
            'type' => 'html',
            'content' => sprintf(
                '<a href="%s" class="onoff%s">%s</a>',
                $href,
                $status ? ' on' : '',
                lang($status ? 'yes' : 'no')
            )
        );
    }

    /**
     * Return a text-only row for a form
     */
    public static function text($text)
    {
        $classes = array('pro-text-row');

        return array(
            'wide' => true,
            'fields' => array(array(
                'type' => 'html',
                'content' => sprintf('<div class="%s">%s</div>', implode(' ', $classes), $text)
            ))
        );
    }

    // --------------------------------------------------------------------

    /**
     * Explode data with given separator
     */
    public static function explode($sep, $data)
    {
        // Get separators
        $seps = static::separators();

        // If not already a valid separator
        if (array_key_exists($sep, $seps)) {
            $sep = $seps[$sep];
        }

        return explode($sep, $data);
    }

    /**
     * Implode data with given separator
     */
    public static function implode($sep, $data)
    {
        // Get separators
        $seps = static::separators();

        // If not already a valid separator
        if (array_key_exists($sep, $seps)) {
            $sep = $seps[$sep];
        }

        return implode($sep, $data);
    }

    // --------------------------------------------------------------------

    /**
     * Get choices array from string (options setting)
     */
    public static function choices($str)
    {
        // -------------------------------------
        //  Initiate output
        // -------------------------------------

        $choices = array();

        // -------------------------------------
        //  Explode data on new line
        // -------------------------------------

        if (is_string($str)) {
            foreach (explode("\n", trim($str)) as $option) {
                // Allow for "key : value" options
                $option = explode(' : ', $option, 2);

                if (count($option) == 2) {
                    $key = $option[0];
                    $val = $option[1];
                } else {
                    $key = $val = $option[0];
                }

                // Add item to return data
                $choices[$key] = $val;
            }
        }

        // -------------------------------------
        //  Return exploded data
        // -------------------------------------

        return $choices;
    }

    // --------------------------------------------------------------------

    /**
     * Get a rendered field view
     */
    public static function view_field($field, $data)
    {
        return ee('View')->make('pro_variables:fields/' . $field)->render($data);
    }

    // --------------------------------------------------------------------

    /**
     * Get categories from a given optional group including depth key
     *
     * @access     public
     * @param      mixed
     * @return     array
     */
    public static function get_categories($group_id = null)
    {
        // --------------------------------------
        // Initiate return value
        // --------------------------------------

        $groups = array();

        // --------------------------------------
        // Compose Categories model, joined with groups
        // --------------------------------------

        $categories = ee('Model')
            ->get('Category')
            ->with('CategoryGroup')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('CategoryGroup.group_name')
            ->order('cat_order');

        // --------------------------------------
        // Optionally filter by group
        // --------------------------------------

        if (! empty($group_id)) {
            // Force to array
            if (! is_array($group_id)) {
                $group_id = array($group_id);
            }

            // Filter by group
            $categories->filter('group_id', 'IN', $group_id);
        }

        // --------------------------------------
        // Get 'em boys
        // --------------------------------------

        $categories = $categories->all();

        // --------------------------------------
        // Populates the $groups array into nested array
        // --------------------------------------

        foreach ($categories as $cat) {
            if (! array_key_exists($cat->group_id, $groups)) {
                $groups[$cat->group_id] = array(
                    'id'   => $cat->group_id,
                    'name' => $cat->CategoryGroup->group_name,
                    'categories' => array()
                );
            }

            $groups[$cat->group_id]['categories'][] = array(
                'id' => $cat->cat_id,
                'parent_id' => $cat->parent_id,
                'name' => $cat->cat_name
            );
        }

        // --------------------------------------
        // To add depth, we need the tree factory
        // --------------------------------------

        $tree = new TreeFactory();

        // --------------------------------------
        // Create trees for each category group,
        // and turn back into flat array with added 'depth' key
        // --------------------------------------

        foreach ($groups as &$group) {
            if (empty($group['categories'])) {
                continue;
            }

            // Generate nested tree
            $root = $tree->fromList($group['categories']);

            // Overwrite the categories with added depth
            $group['categories'] = static::add_tree_depth($root->getChildren());
        }

        // --------------------------------------
        // Return the groups
        // --------------------------------------

        return $groups;
    }

    /**
     * Add depth to a Tree
     *
     * @access      private
     * @param       array
     * @param       array
     * @param       int
     * @return      array
     */
    private static function add_tree_depth($array, $result = array(), $depth = 0)
    {
        foreach ($array as $node) {
            // This node's data
            $result[] = array_merge($node->data, array('depth' => $depth));

            // Does this node have children?
            if ($children = $node->getChildren()) {
                // Add those to the result
                $result = static::add_tree_depth($children, $result, $depth + 1);
            }
        }

        // And return it
        return $result;
    }
}
