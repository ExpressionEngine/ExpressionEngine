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
 * Pro Search Log Model class
 */
class Pro_search_log_model extends Pro_search_model
{
    /**
     * Key used for caching/flashdata
     *
     * @var        string
     * @access     public
     */
    public $key = 'pro_search_log_id';

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
            'pro_search_log',
            'log_id',
            array(
                'site_id'      => 'int(4) unsigned NOT NULL',
                'member_id'    => 'int(10) unsigned NOT NULL',
                'search_date'  => 'int(10) unsigned NOT NULL',
                'ip_address'   => 'varchar(46) NOT NULL',
                'keywords'     => 'varchar(150) NOT NULL',
                'parameters'   => 'TEXT NOT NULL',
                'num_results'  => 'int(10) unsigned'
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
    public function install()
    {
        // Call parent install
        parent::install();

        // Add indexes to table
        ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`site_id`)");
    }

    // --------------------------------------------------------------------

    /**
     * Get filtered rows
     *
     * @access      public
     * @return      array
     */
    public function get_filtered_rows($filters = array())
    {
        $this->_set_filters($filters);

        return $this->get_all();
    }

    /**
     * Get row count for filters
     */
    public function get_filtered_count($filters = array())
    {
        $this->_set_filters($filters);

        return ee()->db->count_all_results($this->table());
    }

    /**
     * Get row count for this site
     */
    public function get_site_count()
    {
        $this->_set_filters(array('site_id' => $this->site_id));

        return ee()->db->count_all_results($this->table());
    }

    /**
     * Set the filters
     */
    private function _set_filters($filters = array())
    {
        // Make sure these are
        $filters = (array) $filters;
        $filters = array_map('trim', $filters);
        $filters = array_filter($filters, 'pro_not_empty');

        // Loop through filter options
        foreach ($filters as $key => $val) {
            if (! in_array($key, $this->attributes())) {
                continue;
            }

            switch ($key) {
                case 'keywords':
                case 'ip_address':
                    ee()->db->like($key, $val);

                    break;

                case 'search_date':
                    ee()->db->where("FROM_UNIXTIME(search_date, '%Y-%m-%d') =", $val);

                    break;

                default:
                    ee()->db->where($key, $val);
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Get unique member IDs from log
     *
     * @access      public
     * @return      array
     */
    public function get_member_ids()
    {
        $query = ee()->db
            ->select('DISTINCT(member_id)')
            ->from($this->table())
            ->where('site_id', $this->site_id)
            ->get();

        return pro_flatten_results($query->result_array(), 'member_id');
    }

    /**
     * Get dates from log
     *
     * @access      public
     * @return      array
     */
    public function get_dates()
    {
        $query = ee()->db
            ->select("DISTINCT(FROM_UNIXTIME(search_date, '%Y-%m-%d')) AS search_date", false)
            ->from($this->table())
            ->where('site_id', $this->site_id)
            ->order_by('search_date', 'desc')
            ->get();

        return pro_flatten_results($query->result_array(), 'search_date');
    }

    // --------------------------------------------------------------------

    /**
     * Get popular keywords
     *
     * @access      public
     * @return      array
     */
    public function get_popular_keywords()
    {
        $query = ee()->db
            ->select('keywords, COUNT(*) AS search_count')
            ->from($this->table())
            ->where('keywords !=', '')
            ->group_by('keywords')
            ->order_by('search_count', 'desc')
            //->order_by('search_date', 'desc')
            ->get();

        return $query->result_array();
    }

    // --------------------------------------------------------------------

    /**
     * Prune rows
     *
     * @access      public
     * @param       int
     * @param       int
     * @return      void
     */
    public function prune($site_id, $keep = 500)
    {
        // Get first id after keep-threshold
        $query = ee()->db
            ->select($this->pk())
            ->from($this->table())
            ->where('site_id', $site_id)
            ->order_by($this->pk(), 'desc')
            ->limit(1, $keep)
            ->get();

        // That's the one
        // If the id is larger than the amount to keep,
        // go ahead and prune...
        if ($id = $query->row($this->pk())) {
            ee()->db->where($this->pk() . ' <=', $id);
            ee()->db->where('site_id', $site_id);
            ee()->db->delete($this->table());
        }
    }

    // --------------------------------------------------------------

    /**
     * Update results count
     *
     * @access      public
     * @param       int
     * @param       int
     * @return      void
     */
    public function add_num_results($num, $log_id = false)
    {
        // If not given, try and get the log_id from cache, first
        if (! $log_id) {
            $log_id = pro_get_cache('pro_search', $this->key);
        }

        // Still not given? Try flashdata
        if (! $log_id) {
            $log_id = ee()->session->flashdata($this->key);
        }

        // Update if we have a valid log id
        if ($log_id) {
            ee()->db->query(sprintf(
                'UPDATE `%1$s`
                 SET num_results = IF(num_results IS NULL, %4$d, num_results + %4$d)
                 WHERE %2$s = %3$d',
                $this->table(),
                $this->pk(),
                $log_id,
                $num
            ));
        }
    }
}
// End class

/* End of file Pro_search_log_model.php */
