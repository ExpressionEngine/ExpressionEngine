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
if (! class_exists('Pro_search_model')) {
    require_once(PATH_ADDONS . 'pro_search/model.pro_search.php');
}

/**
 * Pro Search Index Model class
 */
class Pro_search_index_model extends Pro_search_model
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
            'pro_search_indexes',
            array(
                'collection_id',
                'entry_id'
            ),
            array(
                'site_id'    => 'int(4) unsigned NOT NULL',
                'index_text' => 'LONGTEXT NOT NULL',
                'index_date' => 'int(10) unsigned NOT NULL'
            )
        );
    }

    // --------------------------------------------------------------------

    /**
     * Installs given table and adds indexes to it
     *
     * @access      public
     * @return      void
     */
    public function install()
    {
        // Call parent install
        parent::install();

        // Add indexes to table
        ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`collection_id`)");
        ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`site_id`)");
        ee()->db->query("ALTER TABLE {$this->table()} ADD FULLTEXT (`index_text`)");
    }

    // --------------------------------------------------------------

    /**
     * Replace into, rather than insert into
     *
     * @access      public
     * @param       array
     * @return      void
     */
    public function replace($data)
    {
        // --------------------------------------
        // Get insert sql
        // --------------------------------------

        $sql = ee()->db->insert_string($this->table(), $data);

        // --------------------------------------
        // Change insert to replace to update existing entry
        // --------------------------------------

        ee()->db->query(preg_replace('/^INSERT/', 'REPLACE', $sql));
    }

    // --------------------------------------------------------------

    /**
     * Replace into for multiple rows
     *
     * @access      public
     * @param       array
     * @return      void
     */
    public function replace_batch($data)
    {
        // --------------------------------------
        // Get table attributes
        // --------------------------------------

        $attrs = array_keys(current($data));
        $fields = implode(', ', $attrs);
        $values = '';

        // --------------------------------------
        // Collect values
        // --------------------------------------

        foreach ($data as $row) {
            $values .= "\n(";

            foreach ($row as $val) {
                $values .= "'" . ee()->db->escape_str($val) . "',";
            }

            $values = rtrim($values, ',') . '),';
        }

        // --------------------------------------
        // Define SQL
        // --------------------------------------

        $sql = "REPLACE INTO {$this->table()} ({$fields}) VALUES" . rtrim($values, ',');

        ee()->db->query($sql);
    }

    // --------------------------------------------------------------

    /**
     * Get oldest index for given collection or all collections
     *
     * @access      public
     * @param       int
     * @param       array
     * @return      void
     */
    public function get_oldest_index($collection_id = false)
    {
        ee()->db->select('collection_id, MIN(index_date) AS index_date')
            ->from($this->table())
            ->where('site_id', $this->site_id)
            ->group_by('collection_id');

        // Limit by given collection
        if ($collection_id) {
            ee()->db->where('collection_id', $collection_id);
        }

        $query = ee()->db->get();

        // Return array of collection_id => index_date
        return pro_flatten_results($query->result_array(), 'index_date', 'collection_id');
    }

    // --------------------------------------------------------------

    /**
     * Optimize the index table
     *
     * @access     public
     * @return     void
     */
    public function optimize()
    {
        ee()->db->query('OPTIMIZE TABLE ' . $this->table());
    }
}
// End class

/* End of file Pro_search_index_model.php */
