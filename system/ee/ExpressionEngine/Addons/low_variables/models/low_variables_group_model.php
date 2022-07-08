<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// include super model
if (! class_exists('Low_variables_model')) {
    require_once(PATH_ADDONS . 'low_variables/models/low_variables_model.php');
}

/**
 * Low Variables Group Model class
 *
 * @package        low_variables
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-search
 * @copyright      Copyright (c) 2015, Low
 */
class Low_variables_group_model extends Low_variables_model
{
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
            'low_variable_groups',
            'group_id',
            array(
                'site_id'     => 'int(4) unsigned NOT NULL default 1',
                'group_label' => 'varchar(100)',
                'group_notes' => 'TEXT',
                'group_order' => 'int(4) unsigned NOT NULL default 0'
            )
        );
    }

    // --------------------------------------------------------------------

    /**
     * Installs given table
     *
     * @access      public
     * @return      void
     */
    public function install($autoincrement = true)
    {
        // Call parent install
        parent::install($autoincrement);

        // Add indexes to table
        foreach (array('site_id') as $key) {
            ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`{$key}`)");
        }
    }

    // --------------------------------------------------------------------

    /**
     * Get site groups ordered by name
     *
     * @access      public
     * @return      void
     */
    public function get_by_site($site_id = null)
    {
        static $cache = array();

        // Get current site id
        if (empty($site_id)) {
            $site_id = $this->site_id;
        }

        if (! isset($cache[$site_id])) {
            ee()->db->where('site_id', $site_id);
            ee()->db->order_by('group_order');
            $cache[$site_id] = $this->get_all();
        }

        return $cache[$site_id];
    }

    // --------------------------------------------------------------------

    /**
     * Get site groups ordered by name, possibly with specific IDs
     *
     * @access      public
     * @return      void
     */
    public function get_by_ids($ids = null)
    {
        if (is_array($ids)) {
            ee()->db->where_in($this->pk(), $ids);
        }

        return $this->get_by_site();
    }
}
// End class

/* End of file Low_variables_group_model.php */
