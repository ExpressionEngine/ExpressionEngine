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
 * Pro Variables Group Model class
 */
class Pro_variables_group_model extends Pro_variables_model
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
            'pro_variable_groups',
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

/* End of file Pro_variables_group_model.php */
