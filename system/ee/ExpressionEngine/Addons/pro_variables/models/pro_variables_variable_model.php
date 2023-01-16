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

// include super model
if (! class_exists('Pro_variables_model')) {
    require_once(PATH_ADDONS . 'pro_variables/models/pro_variables_model.php');
}

/**
 * Pro Variables Variable Model class
 */
class Pro_variables_variable_model extends Pro_variables_model
{
    /**
     * Native table
     */
    private $ee_table = 'global_variables';

    /**
     * Native attributes
     */
    private $ee_attrs = array(
        'site_id',
        'variable_name',
        'variable_data'
    );

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access      public
     * @return      void
     */
    public function __construct()
    {
        // Call parent constructor
        parent::__construct();

        // Initialize this model
        $this->initialize(
            'pro_variables',
            'variable_id',
            array(
                'group_id'          => 'int(6) unsigned not null default 0',
                'variable_label'    => 'varchar(100)',
                'variable_notes'    => 'text',
                'variable_type'     => 'varchar(50)',
                'variable_settings' => 'text',
                'variable_order'    => 'int(4) unsigned not null default 0',
                'early_parsing'     => 'char(1) not null default "n"',
                'is_hidden'         => 'char(1) not null default "n"',
                'save_as_file'      => 'char(1) not null default "n"',
                'edit_date'         => 'int(10) unsigned'
            )
        );

        // Prefix EE table
        $this->ee_table = ee()->db->dbprefix . $this->ee_table;
    }

    // --------------------------------------------------------------------

    /**
     * Installs given table
     *
     * @access      public
     * @return      void
     */
    public function install($autoincrement = false)
    {
        // Call parent install (no auto-increment)
        parent::install($autoincrement);

        // Add indexes to table
        foreach (array('group_id') as $key) {
            ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`{$key}`)");
        }
    }

    // --------------------------------------------------------------------

    /**
     * Insert into both native and LV table
     */
    public function insert($data)
    {
        // Init return value
        $id = false;

        // Get native and lv data
        list($ee, $lv) = $this->split($data);

        // If there's native stuff, insert that, then insert LV
        if ($ee) {
            $var = ee('Model')->make('GlobalVariable', $ee);
            $var->save();

            $id = $lv[$this->pk()] = $var->variable_id;

            parent::insert($lv);
        }

        return $id;
    }

    /**
     * Update native and LV table
     */
    public function update($id, $data)
    {
        // Get native and lv data
        list($ee, $lv) = $this->split($data);

        // Update native stuff
        if ($ee) {
            $vars = ee('Model')
                ->get('GlobalVariable')
                ->filter('variable_id', 'IN', (array) $id)
                ->all();

            $vars->each(function ($var) use ($ee) {
                $var->set($ee);
                $var->save();
            });
        }

        // Update LV stuff
        if ($lv) {
            parent::update($id, $lv);
        }
    }

    /**
     * Delete a variable or variables from native and LV
     */
    public function delete($id, $attr = false)
    {
        // Force array
        if (! is_array($id)) {
            $id = array($id);
        }

        // Delete from LV
        ee()->db->where_in($this->pk(), $id);
        ee()->db->delete($this->table());

        // Delete from native
        ee('Model')->get('GlobalVariable', $id)->delete();
    }

    // --------------------------------------------------------------------

    /**
     * Update the given variable IDs' order to given one
     */
    public function update_var_order($vars)
    {
        foreach ($vars as $var_order => $var_id) {
            // Set new order for variable
            $this->update($var_id, array(
                'variable_order' => $var_order + 1
            ));
        }
    }

    /**
     * Ungroup vars in given group
     */
    public function ungroup($group_id)
    {
        ee()->db->where('group_id', $group_id);
        ee()->db->update($this->table(), array('group_id' => 0));
    }

    /**
     * Toggle a given boolean for given var ID
     */
    public function toggle($type, $var_id)
    {
        $result = false;

        if ($var = $this->get_one($var_id)) {
            if (array_key_exists($type, $var)) {
                $this->update($var_id, array(
                    $type => ($var[$type] == 'n' ? 'y' : 'n')
                ));

                $result = true;
            }
        }

        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * Create empty row, with defaults
     */
    public function empty_row()
    {
        $row = parent::empty_row();

        // Add EE attrs to the row
        foreach ($this->ee_attrs as $key) {
            $row[$key] = '';
        }

        // Defaults
        $row['site_id'] = $this->site_id;
        $row['group_id'] = 0;
        $row['variable_order'] = 0;
        $row['early_parsing'] = $row['is_hidden'] = $row['save_as_file'] = 'n';

        return $row;
    }

    // --------------------------------------------------------------------

    /**
     * Get vars for given group
     *
     * @access      public
     * @return      void
     */
    public function get_by_group($id)
    {
        ee()->db
            ->from($this->table())
            ->where('group_id', $id)
            ->where('site_id', $this->site_id)
            ->order_by('variable_order', 'asc');

        $this->join();

        return ee()->db->get()->result_array();
    }

    /**
     * Only get meta-data vars for given group
     */
    public function get_meta_by_group($id)
    {
        ee()->db->select($this->select_meta());

        return $this->get_by_group($id);
    }

    // --------------------------------------------------------------------

    /**
     * Get site groups ordered by name
     *
     * @access      public
     * @param       bool
     * @return      array
     */
    public function get_group_count($include_hidden = true)
    {
        ee()->db
            ->select('group_id, count(*) as var_count')
            ->from($this->table())
            ->where('site_id', $this->site_id)
            ->group_by('group_id');

        $this->join();

        if (! $include_hidden) {
            ee()->db->where('is_hidden', 'n');
        }

        $query = ee()->db->get();

        return pro_flatten_results($query->result_array(), 'var_count', 'group_id');
    }

    // --------------------------------------------------------------------

    /**
     * Get max order
     */
    public function max_order($group_id)
    {
        return ee()->db
            ->select("MAX(variable_order) as `max`")
            ->from($this->table())
            ->where('group_id', $group_id)
            ->get()
            ->row('max');
    }

    // --------------------------------------------------------------------

    /**
     * Get one meta
     *
     * @access      private
     * @return      array
     */
    private function get($id = null)
    {
        // Init return value
        $result = 'result_array';

        // Start query
        ee()->db
            ->from($this->table())
            ->where('site_id', $this->site_id)
            ->order_by('variable_name');

        // Join EE table
        $this->join();

        // Limit by given id(s)
        if (! empty($id)) {
            $attr = $this->prefix($this->pk(), $this->table());
            $method = is_array($id) ? 'where_in' : 'where';

            // Add where clause
            ee()->db->$method($attr, $id);

            // Return a row if a single ID is given
            if (! is_array($id)) {
                $result = 'row_array';
            }
        }

        return ee()->db->get()->$result();
    }

    /**
     * Only get meta data
     */
    public function get_meta($id = null)
    {
        ee()->db->select($this->select_meta());

        return $this->get($id);
    }

    /**
     * Get all
     *
     * @access      public
     * @return      array
     */
    public function get_all($id = null)
    {
        return $this->get($id);
    }

    /**
     * Get all
     *
     * @access      public
     * @return      array
     */
    public function get_by_site($id = null)
    {
        $id = $id ?: $site_id;

        // Start query
        ee()->db
            ->from($this->table())
            ->where('site_id', $id);

        // Join EE table
        $this->join();

        return ee()->db->get()->result_array();
    }

    /**
     * Get early parsed vars for current site
     */
    public function get_early()
    {
        ee()->db
            ->select('variable_name, variable_data')
            ->from($this->table())
            ->where('site_id', $this->site_id)
            ->where('early_parsing', 'y')
            ->order_by('group_id')
            ->order_by('variable_order');

        $this->join();

        return ee()->db->get()->result_array();
    }

    /**
     * Get early parsed vars for current site
     */
    public function get_file_vars($ids = array())
    {
        ee()->db
            ->select($this->select_all())
            ->from($this->table())
            ->where('site_id', $this->site_id)
            ->where('save_as_file', 'y');

        if ($ids) {
            ee()->db->where_in($this->prefix($this->pk(), $this->table()), $ids);
        }

        $this->join();

        return ee()->db->get()->result_array();
    }

    /**
     * Get early parsed vars for current site
     */
    public function get_ft($group_ids = array())
    {
        ee()->db
            ->select($this->select_meta())
            ->from($this->table())
            ->where('site_id', $this->site_id)
            ->where('early_parsing', 'n')
            ->where('is_hidden', 'n')
            ->order_by('variable_order');

        $this->join();

        if ($group_ids) {
            ee()->db->where_in('group_id', $group_ids);
        }

        return ee()->db->get()->result_array();
    }

    // --------------------------------------------------------------------

    /**
     * Check if var name exists in current site
     */
    public function var_exists($name, $id = null)
    {
        if ($id) {
            ee()->db->where($this->pk() . ' !=', $id);
        }

        return ee()->db
            ->where('site_id', $this->site_id)
            ->where('variable_name', $name)
            ->count_all_results($this->ee_table);
    }

    // --------------------------------------------------------------------

    /**
     * Split data array into native/lv
     *
     * @access      private
     * @param       array
     * @return      array
     */
    private function split($data)
    {
        $ee = array();
        $lv = array();

        // Split data into native and 3rd party
        foreach ($data as $key => $val) {
            // Check if we have native stuff to save
            if (in_array($key, $this->ee_attrs)) {
                $ee[$key] = $val;
            } else {
                $lv[$key] = $val;
            }
        }

        return array($ee, $lv);
    }

    // --------------------------------------------------------------------

    /**
     * Get select array for meta-data only
     *
     * @access      private
     * @return      array
     */
    private function select_meta()
    {
        $attrs = $this->attributes();
        $attrs[] = $this->pk();
        $attrs = $this->prefix($attrs, $this->table());
        $attrs[] = $this->prefix('variable_name', $this->ee_table);

        return $attrs;
    }

    /**
     * Get select array for all data
     *
     * @access      private
     * @return      array
     */
    private function select_all()
    {
        $attrs = $this->select_meta();
        $attrs[] = $this->prefix('variable_data', $this->ee_table);

        return $attrs;
    }

    // --------------------------------------------------------------------

    /**
     * Joins the native table to current query
     *
     * @access      private
     * @return      void
     */
    private function join()
    {
        $a = $this->prefix($this->pk(), $this->table());
        $b = $this->prefix($this->pk(), $this->ee_table);

        ee()->db->join($this->ee_table, $a . ' = ' . $b, 'inner');
    }

    // --------------------------------------------------------------------

    /**
     * Prefix something with something else, using dot
     */
    private function prefix($input, $pfx)
    {
        if (is_array($input)) {
            foreach ($input as &$in) {
                $in = $this->prefix($in, $pfx);
            }
        } else {
            $input = $pfx . '.' . $input;
        }

        return $input;
    }
}
// End class

/* End of file Pro_variables_variable_model.php */
