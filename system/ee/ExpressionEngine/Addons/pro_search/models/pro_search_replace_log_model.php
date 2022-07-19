<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// include super model
if (! class_exists('Pro_search_model')) {
    require_once(PATH_ADDONS . 'pro_search/model.pro_search.php');
}

/**
 * Pro Search Replace Log Model class
 *
 * @package        pro_search
 * @author         ExpressionEngine
 * @link           https://eeharbor.com/pro-search
 * @copyright      Copyright (c) 2022, ExpressionEngine
 */
class Pro_search_replace_log_model extends Pro_search_model
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
            'pro_search_replace_log',
            'log_id',
            array(
                'site_id'      => 'int(4) unsigned NOT NULL',
                'member_id'    => 'int(10) unsigned NOT NULL',
                'replace_date' => 'int(10) unsigned NOT NULL',
                'keywords'     => 'varchar(150) NOT NULL',
                'replacement'  => 'varchar(150) NOT NULL',
                'fields'       => 'TEXT NOT NULL',
                'entries'      => 'TEXT NOT NULL'
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
     * Get row count for this site
     */
    public function get_site_count()
    {
        ee()->db->where('site_id', $this->site_id);

        return ee()->db->count_all_results($this->table());
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
        $query = ee()->db->select($this->pk())
            ->from($this->table())
            ->where('site_id', $site_id)
            ->order_by($this->pk(), 'desc')
            ->limit(1, $keep)
            ->get();

        // That's the one
        $id = $query->row($this->pk());

        // If the id is larger than the amount to keep,
        // go ahead and prune...
        if ($id && $id > $keep) {
            ee()->db->where($this->pk() . ' <=', $id);
            ee()->db->where('site_id', $site_id);
            ee()->db->delete($this->table());
        }
    }

    // --------------------------------------------------------------
}
// End class

/* End of file Pro_search_replace_log_model.php */
