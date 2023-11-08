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
 * Pro Variables Type Class
 *
 * The Pro Variables Type base class, to be extended by other classes
 */
class Pro_variables_type
{
    public const CONTENT_TYPE = 'pro_variables';

    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    public $info = array();
    public $default_settings = array();
    public $error_msg;

    // Full var row
    protected $row;

    // Meta shortcuts
    protected $id;
    protected $settings;
    protected $name;
    protected $type;
    protected $data;

    // Fieldtype name
    protected $ft;

    // Fieldtype object and path
    private $_ft;
    private $_path;

    // Bridge
    private $_bridge;

    // Bridge map to ft methods, for legacy (preferred is var_x methods)
    private $_map = array(
        'display_settings'   => 'display_var_settings',
        'validate_settings'  => 'validate_settings',
        'save_settings'      => 'save_var_settings',
        'post_save_settings' => 'post_save_var_settings',
        'display_field'      => 'display_var_field',
        // 'validate'           => 'var_validate', # new, so already with prefix
        'save'               => 'save_var_field',
        'post_save'          => 'post_save_var',
        'replace_tag'        => 'display_var_tag',
        'delete'             => 'delete_var',
    );

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Is this a bridge to a 3rd party fieldtype?
     */
    public function __construct($type = null)
    {
        if (! empty($type)) {
            // Bridge mode
            $this->_bridge = true;
            $this->type = $type['type'];
            $this->ft = $type['type'];
            $this->_path = $type['path'];
        } else {
            // Determine type based on class
            $this->type = strtolower(get_class($this));
        }
    }

    /**
     * Initiate this var
     */
    public function init($var)
    {
        // Keep track of the entire var
        $this->row = $var;

        // Current variable id
        $this->id = $this->row('variable_id');

        // It might be 'new', so make sure it's NULL if not numeric
        if ($this->id && ! is_numeric($this->id)) {
            $this->id = null;
        }

        // Combine settings
        $this->settings = array_merge(
            $this->default_settings ?: array(),
            $this->decode($this->row('variable_settings'))
        );

        // The var data
        $this->data = $this->row('variable_data', '');

        // The variable name
        $this->name = $this->row('variable_name');

        // Setup the fieldtype if in bridge mode
        if ($this->_bridge) {
            $this->setup_ft();
        }
    }

    /**
     * Decode settings
     */
    private function decode($var)
    {
        // No need to decode if array is given
        if (is_array($var)) {
            return $var;
        }

        // Old encoded
        if (substr($var, 0, 3) == 'YTo') {
            $var = str_replace('_', '/', $var);
            $var = @unserialize(base64_decode($var));
        } else {
            $var = json_decode($var, true);
        }

        // Make sure an array is returned
        return is_array($var) ? $var : array();
    }

    /**
     * Setup the fieldtype
     */
    protected function setup_ft()
    {
        // If no fieldtype string is defined, bail out
        if (empty($this->ft)) {
            return;
        }

        // Get fieldtype object
        if (empty($this->_ft)) {
            ee()->load->library('api');
            ee()->legacy_api->instantiate('channel_fields');
            ee()->api_channel_fields->include_handler($this->ft);

            $this->_ft = ee()->api_channel_fields->setup_handler($this->ft, true);
        }

        // Get fieldtype path
        if (empty($this->_path)) {
            $this->_path = PATH_FT . $this->ft;
        }

        // Initiate the fieldtype
        $this->call_ft('_init', array(
            'id'           => $this->id,
            'name'         => $this->name,
            'content_id'   => $this->id,
            'content_type' => static::CONTENT_TYPE,
            'var_id'       => $this->id, // Legacy: fieldtypes can check content type
            'settings'     => $this->settings(),
            'row'          => $this->row()
        ));
    }

    /**
     * Call a method in the fieldtype
     */
    protected function call_ft()
    {
        // This isn't an argument
        $args = func_get_args();
        $fn = array_shift($args);

        // Fieldtype isn't setup or not callable, bail
        if (! is_object($this->_ft) || ! method_exists($this->_ft, $fn)) {
            return;
        }

        // Call the fieldtype in a package path sandwich
        ee()->load->add_package_path($this->_path);
        $response = call_user_func_array(array($this->_ft, $fn), $args);
        ee()->load->remove_package_path($this->_path);

        return $response;
    }

    /**
     * Set FT property
     */
    protected function set_ft_property($key, $val)
    {
        $this->_ft->$key = $val;
    }

    // --------------------------------------------------------------------

    /**
     * Return a specific setting or all settings
     */
    public function settings($which = null, $default = null)
    {
        return ($which)
            ? (array_key_exists($which, $this->settings) ? $this->settings[$which] : $default)
            : $this->settings;
    }

    /**
     * Return the nested array name for setting input fields
     */
    protected function setting_name($name, $multi = false)
    {
        return sprintf(
            'variable_settings[%s][%s]%s',
            $this->type,
            $name,
            $multi ? '[]' : ''
        );
    }

    /**
     * Settings form
     */
    protected function settings_form($fields)
    {
        return array('field_options_' . $this->type => array(
            'group' => $this->type,
            'label' => $this->info['name'],
            'settings' => $fields
        ));
    }

    /**
     * Return the input name
     */
    public function input_name($multi = false)
    {
        return $this->name . ($multi ? '[]' : '');
    }

    // --------------------------------------------------------------------

    /**
     * Return current var ID
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Return current var type
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Return current name
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Return current data
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Return something from the current row
     */
    public function row($key = null, $default = null)
    {
        return is_null($key)
            ? $this->row
            : (array_key_exists($key, $this->row) ? $this->row[$key] : $default);
    }

    // --------------------------------------------------------------------

    /**
     * Get bridge method
     */
    private function get_bridge_method($fn)
    {
        $it = false;

        // Check only if we're in bridge mode
        if ($this->_bridge && is_object($this->_ft)) {
            // First method: var_x
            if (method_exists($this->_ft, 'var_' . $fn)) {
                $it = 'var_' . $fn;
            } elseif (array_key_exists($fn, $this->_map) && method_exists($this->_ft, $this->_map[$fn])) {
                // Second method: old bridge methods
                $it = $this->_map[$fn];
            } elseif ($this->call_ft('accepts_content_type', static::CONTENT_TYPE)) {
                // Lastly, check if the fieldtype accepts the LV content type
                $it = $fn;
            }
        }

        return $it;
    }

    // --------------------------------------------------------------------

    /**
     * Display settings sub-form for this variable type
     */
    public function display_settings()
    {
        return ($fn = $this->get_bridge_method(__FUNCTION__))
            ? $this->call_ft($fn, $this->settings())
            : array();
    }

    // --------------------------------------------------------------------

    /**
     * Validate var settings
     */
    public function valiate_settings()
    {
        return ($fn = $this->get_bridge_method(__FUNCTION__))
            ? $this->call_ft($fn, $this->settings())
            : true;
    }

    // --------------------------------------------------------------------

    /**
     * Do something with settings before saving them to the DB
     */
    public function save_settings()
    {
        return ($fn = $this->get_bridge_method(__FUNCTION__))
            ? $this->call_ft($fn, $this->settings())
            : $this->settings();
    }

    // --------------------------------------------------------------------

    /**
     * Do something after the variable has been saved
     */
    public function post_save_settings()
    {
        return ($fn = $this->get_bridge_method(__FUNCTION__))
            ? $this->call_ft($fn, $this->settings())
            : null;
    }

    // --------------------------------------------------------------------

    /**
     * Display input field for regular user
     */
    public function display_field($var_data)
    {
        return ($fn = $this->get_bridge_method(__FUNCTION__))
            ? $this->call_ft($fn, $var_data)
            : null;
    }

    // --------------------------------------------------------------------

    /**
     * Validate var input
     */
    public function valiate($var_data)
    {
        return ($fn = $this->get_bridge_method(__FUNCTION__))
            ? $this->call_ft($fn, $var_data)
            : true;
    }

    // --------------------------------------------------------------------

    /**
     * Prep variable data for saving
     */
    public function save($var_data)
    {
        if ($fn = $this->get_bridge_method(__FUNCTION__)) {
            $var_data = $this->call_ft($fn, $var_data);
        }

        if ($var_data === false && is_object($this->_ft)) {
            $this->error_msg = $this->_ft->error_msg;
        }

        return $var_data;
    }

    // --------------------------------------------------------------------

    /**
     * Do something after the variable has been saved to the DB
     */
    public function post_save($var_data)
    {
        return ($fn = $this->get_bridge_method(__FUNCTION__))
            ? $this->call_ft($fn, $var_data)
            : $var_data;
    }

    // --------------------------------------------------------------------

    /**
     * Display template tag output
     */
    public function replace_tag($tagdata)
    {
        return ($fn = $this->get_bridge_method(__FUNCTION__))
            ? $this->call_ft($fn, $this->data, ee()->TMPL->tagparams, $tagdata)
            : $this->_replace_tag($tagdata);
    }

    // --------------------------------------------------------------------

    /**
     * Do stuff after a variable has been deleted
     */
    public function delete()
    {
        return ($fn = $this->get_bridge_method(__FUNCTION__))
            ? $this->call_ft($fn, $this->id)
            : null;
    }

    // --------------------------------------------------------------------

    /**
     * Custom call: Is the input field Wide or not?
     */
    public function wide()
    {
        return ($fn = $this->get_bridge_method(__FUNCTION__))
            ? $this->call_ft($fn)
            : false;
    }

    /**
     * Custom call: Is the input field Grid or not?
     */
    public function grid()
    {
        return ($fn = $this->get_bridge_method(__FUNCTION__))
            ? $this->call_ft($fn)
            : false;
    }

    // --------------------------------------------------------------------

    /**
     * Default replace tag options
     */
    private function _replace_tag($tagdata)
    {
        // If tagdata is empty, just return the var data
        if (empty($tagdata)) {
            return $this->data;
        }

        // This var's name
        $name = $this->name();

        // There is tagdata. Now see what we need to do
        // if (ee()->TMPL->fetch_param('multiple') == 'yes')
        if ($sep = $this->settings('separator', false)) {
            // We need a separator for multi-parsing
            $labels = PVUI::choices($this->settings('options'));

            // Empty data? No results
            if (! strlen($this->data)) {
                return ee()->TMPL->no_results();
            }

            // Get values
            $values = PVUI::explode($sep, $this->data);
            $total = count($values);

            // Limit results?
            if (($limit = ee()->TMPL->fetch_param('limit')) && is_numeric($limit) && $total > $limit) {
                $values = array_slice($values, 0, $limit);
                $total = count($values);
            }

            // Init data
            $data = array();
            $i = 0;

            foreach ($values as $val) {
                $data[] = array(
                    $name . ':data'          => $val,
                    $name . ':label'         => $this->row('variable_label'),
                    $name . ':data_label'    => isset($labels[$val]) ? $labels[$val] : '',
                    $name . ':count'         => ++$i,
                    $name . ':total_results' => $total
                );
            }

            // Parse the vars
            return ee()->TMPL->parse_variables($tagdata, $data);
        } else {
            $data = array(
                $name . ':data' => $this->data,
                $name . ':label' => $this->row('variable_label')
            );

            // Parse var row
            return ee()->TMPL->parse_variables_row($tagdata, $data);
        }
    }

    // --------------------------------------------------------------------
}

// End of file type.pro_variables.php
