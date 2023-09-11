<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Template Structure API
 */
class Api_template_structure extends Api
{
    /**
     * @php4 -- All of the class properties are protected.
     * When php4 support is deprecated, make them accessible via __get()
     */
    public $template_info = array();				// cache of previously fetched template info
    public $group_info = array();				// cache of previously fetched group info
    public $reserved_names = array('act', 'css');	// array of reserved template group names

    // file extensions used when saving templates as files
    public $file_extensions = array(
        'webpage' => '.html',
        'static' => '.html',
        'feed' => '.feed',
        'css' => '.css',
        'js' => '.js',
        'xml' => '.xml'
    );

    protected $template_engine_file_extensions = [];
    protected $template_engines = array(
        '' => 'Native'
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        ee()->load->model('template_model');

        // initialize the reserved names array
        $this->_load_reserved_groups();
        $this->_setup_template_engine_extensions();
    }

    /**
     * Get Template Group metadata
     *
     * @access	public
     * @param	int
     * @return	object
     */
    public function get_group_info($group_id)
    {
        if ($group_id == '') {
            $this->_set_error('channel_id_required');

            return false;
        }

        // return cached query object if available
        if (isset($this->group_info[$group_id])) {
            return $this->group_info[$group_id];
        }

        $query = ee()->template_model->get_group_info($group_id);

        if ($query->num_rows() == 0) {
            $this->_set_error('invalid_group_id');

            return false;
        }

        $this->group_info[$group_id] = $query;

        return $query;
    }

    /**
     * Create Group
     *
     * Creates a new template group
     *
     * @access	public
     * @param	array
     * @return	int
     */
    public function create_template_group($data, $duplicate_group = false)
    {
        if (! is_array($data) or count($data) == 0) {
            return false;
        }

        $group_name = '';

        // turn our array into variables
        extract($data);

        if ($site_id === null or ! is_numeric($site_id)) {
            $site_id = $this->config->item('site_id');
        }

        // validate group name
        if ($group_name == '') {
            $this->_set_error('group_required');
        }

        if (! ee()->legacy_api->is_url_safe($group_name)) {
            $this->_set_error('illegal_characters');
        }

        if (in_array($group_name, $this->reserved_names)) {
            $this->_set_error('reserved_name');
        }

        // check if it's taken, too
        ee()->load->model('super_model');
        $count = ee()->super_model->count('template_groups', array('site_id' => $site_id, 'group_name' => $group_name));

        if ($count > 0) {
            $this->_set_error('template_group_taken');
        }

        // error trapping is all over, shall we continue?
        if ($this->error_count() > 0) {
            return false;
        }

        $is_site_default = (isset($is_site_default) && $is_site_default == 'y') ? 'y' : 'n';

        $data = array();

        foreach (array('group_name', 'group_order', 'is_site_default', 'site_id') as $field) {
            if (isset($$field)) {
                $data[$field] = $$field;
            }
        }

        $group_id = ee()->template_model->create_group($data);

        $duplicate = false;

        if (is_numeric($duplicate_group)) {
            $fields = array('template_name', 'template_data', 'template_type', 'template_notes', 'cache', 'refresh', 'no_auth_bounce', 'allow_php', 'php_parse_location', 'protect_javascript');
            $query = ee()->template_model->get_templates($site_id, $fields, array('templates.group_id' => $duplicate_group));

            if ($query->num_rows() > 0) {
                $duplicate = true;
            }
        }

        if ($duplicate !== true) {
            // just create the default 'index' template
            $template_data = array(
                'group_id' => $group_id,
                'template_name' => 'index',
                'edit_date' => ee()->localize->now,
                'site_id' => $site_id
            );

            ee()->template_model->create_template($template_data);
        } else {
            foreach ($query->result() as $row) {
                $data = array(
                    'group_id' => $group_id,
                    'template_name' => $row->template_name,
                    'template_notes' => $row->template_notes,
                    'cache' => $row->cache,
                    'refresh' => $row->refresh,
                    'no_auth_bounce' => $row->no_auth_bounce,
                    'php_parse_location' => $row->php_parse_location,
                    'allow_php' => (ee('Config')->getFile()->getBoolean('allow_php') && ee('Permission')->isSuperAdmin()) ? $row->allow_php : 'n',
                    'protect_javascript' => $row->protect_javascript,
                    'template_type' => $row->template_type,
                    'template_data' => $row->template_data,
                    'edit_date' => ee()->localize->now,
                    'site_id' => ee()->config->item('site_id')
                );

                ee()->template_model->create_template($data);
            }
        }

        return $group_id;
    }

    /**
     * Load Reserved Groups
     *
     * Adds all potential reserved names to the reserved groups array
     *
     * @access	private
     * @php4 -- Change to truly private when php support is deprecated.
     * @return	void
     */
    public function _load_reserved_groups()
    {
        if (ee()->config->item("forum_is_installed") == 'y' && ee()->config->item("forum_trigger") != '') {
            $this->reserved_names[] = ee()->config->item("forum_trigger");
        }

        if (ee()->config->item("use_category_name") == 'y' && ee()->config->item("reserved_category_word") != '') {
            $this->reserved_names[] = ee()->config->item("reserved_category_word");
        }

        if (ee()->config->item("forum_is_installed") == 'y' && ee()->config->item("forum_trigger") != '') {
            $this->reserved_names[] = ee()->config->item("forum_trigger");
        }

        if (ee()->config->item("profile_trigger") != '') {
            $this->reserved_names[] = ee()->config->item("profile_trigger");
        }
    }

    /**
     * File Extensions
     *
     * Returns a file extension that corresponds to the template type
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function file_extensions($template_type, $engine = null)
    {
        // Check our standard template types for a file extension
        $engine = ($engine && array_key_exists($engine, $this->template_engines)) ? ".$engine" : null;

        if (isset($this->file_extensions[$template_type])) {
            return implode('', array_filter([$this->file_extensions[$template_type], $engine]));
        } else {
            // Check custom template types for a file extension
            // -------------------------------------------
            // 'template_types' hook.
            //  - Provide information for custom template types.
            //
            if (isset(ee()->extensions)) {
                $template_types = ee()->extensions->call('template_types', array());
            }
            //
            // -------------------------------------------

            if (isset($template_types) && !empty($template_types)) {
                if (isset($template_types[$template_type]['template_file_extension'])) {
                    return implode('', array_filter([$template_types[$template_type]['template_file_extension'], $engine]));
                }
            }
        }

        return '';
    }

    /**
     * Get a list of all available file extensions along with
     * the default template type and template engine
     *
     * @return array
     */
    public function all_file_extensions()
    {
        $result = [];

        foreach ($this->template_engine_file_extensions as $type => $extensions) {
            foreach ($extensions as $extension) {
                if (!array_key_exists($extension['extension'], $result)) {
                    $result[$extension['extension']] = [
                        'type' => $type,
                        'engine' => $extension['engine']
                    ];
                }
            }
        }

        // sort extensions by longest to shortest key
        uksort($result, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        return $result;
    }

    /**
     * Create a mapping of template types and available extensions for each type
     *
     * @return void
     */
    private function _setup_template_engine_extensions()
    {
        $extensions = [];
        $engines = array_keys($this->template_engines);

        foreach ($this->file_extensions as $type => $extension) {
            foreach ($engines as $engine) {
                $ext = implode('.', array_filter([
                    $extension,
                    $engine
                ]));
                $extensions[$type][] = ['extension' => $ext, 'engine' => $engine ?: null];
            }
        }

        // cache this locally
        $this->template_engine_file_extensions = $extensions;
    }

    /**
     * Register new template engine(s)
     *
     * @param array $engines
     * @return void
     */
    public function register_template_engine($engines = array())
    {
        foreach ($engines as $key => $value) {
            if (!array_key_exists($key, $this->template_engines)) {
                $this->template_engines[$key] = $value;
            }
        }

        // Refresh the template engine extension cache
        $this->_setup_template_engine_extensions();
    }

    /**
     * Get a list of template engines
     *
     * @return array
     */
    public function get_template_engines()
    {
        return $this->template_engines;
    }

    /**
     * Retrieve information about a given template file
     *
     * @param string $template
     * @return array|null
     */
    public function get_template_file_info($template)
    {
        foreach ($this->all_file_extensions() as $extension => $info) {
            // If template ends with extension we are done.  This finds the most specific extension first
            // because all_file_extensions returns a sorted list with longest extensions first
            $extensionLength = strlen($extension);
            if (substr_compare($template, $extension, -$extensionLength) === 0) {
                return [
                    'name' => substr($template, 0, -$extensionLength),
                    'type' => $info['type'],
                    'engine' => $info['engine'],
                    'extension' => $extension
                ];
            }
        }

        return null;
    }
}
// END CLASS

// EOF
