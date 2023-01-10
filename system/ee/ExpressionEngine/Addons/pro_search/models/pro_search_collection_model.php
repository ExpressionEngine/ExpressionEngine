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
 * Pro Search Collection Model class
 */
class Pro_search_collection_model extends Pro_search_model
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
            'pro_search_collections',
            'collection_id',
            array(
                'site_id'          => 'int(4) unsigned NOT NULL',
                'channel_id'       => 'int(6) unsigned NOT NULL',
                'collection_name'  => 'varchar(40) NOT NULL',
                'collection_label' => 'varchar(100) NOT NULL',
                'language'         => 'varchar(5)',
                'modifier'         => 'decimal(4,1) unsigned NOT NULL default 1.0',
                'excerpt'          => 'int(6) unsigned NOT NULL default 0',
                'settings'         => 'text NOT NULL',
                'edit_date'        => 'int(10) unsigned NOT NULL'
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
        ee()->db->query("ALTER TABLE {$this->table()} ADD INDEX (`channel_id`)");
    }

    // --------------------------------------------------------------

    /**
     * Get all collections and cache them
     *
     * @access      public
     * @param       int      Channel ID
     * @return      array
     */
    public function get_all()
    {
        static $all;

        // Get all from parent class
        if (is_null($all)) {
            ee()->db->order_by('collection_label', 'asc');
            $all = parent::get_all();

            foreach ($all as &$row) {
                $row['settings'] = pro_search_decode($row['settings'], false);
            }

            $all = pro_associate_results($all, 'collection_id');
        }

        return $all;
    }

    // --------------------------------------------------------------

    /**
     * Get collections based on collection ID
     *
     * @access      public
     * @param       int      Collection ID
     * @param       bool     include or exclude collection ID
     * @param       mixed    Limit collections to given ones
     * @return      array
     */
    public function get_by_id($collection_id, $in = true, $cols = null)
    {
        return $this->_get_by_attr($collection_id, $this->pk(), $in, $cols);
    }

    /**
     * Get collections based on a channel ID
     *
     * @access      public
     * @param       int      Channel ID
     * @param       bool     include or exclude channel ID
     * @param       mixed    Limit collections to given ones
     * @return      array
     */
    public function get_by_channel($channel_id, $in = true, $cols = null)
    {
        return $this->_get_by_attr($channel_id, 'channel_id', $in, $cols);
    }

    /**
     * Get collections based on a site ID
     *
     * @access      public
     * @param       int      Site ID
     * @param       bool     include or exclude site ID
     * @param       mixed    Limit collections to given ones
     * @return      array
     */
    public function get_by_site($site_id, $in = true, $cols = null)
    {
        return $this->_get_by_attr($site_id, 'site_id', $in, $cols);
    }

    /**
     * Get collections based on language
     *
     * @access      public
     * @param       string   language
     * @param       bool     include or exclude collection ID
     * @param       mixed    Limit collections to given ones
     * @return      array
     */
    public function get_by_language($language, $in = true, $cols = null)
    {
        return $this->_get_by_attr($language, 'language', $in, $cols);
    }

    /**
     * Get collections by parameter
     *
     * @access      public
     * @param       string   Parameter value
     * @return      array
     */
    public function get_by_param($param, $cols = null)
    {
        list($ids, $in) = ee()->pro_search_params->explode($param);

        $attr = pro_array_is_numeric($ids) ? $this->pk() : 'collection_name';

        return $this->_get_by_attr($ids, $attr, $in, $cols);
    }

    /**
     * Get collections by template parameters
     *
     * @access      public
     * @return      array
     */
    public function get_by_params()
    {
        $rows = null;

        // By collection
        if ($collection = ee()->pro_search_params->get('collection')) {
            $rows = $this->get_by_param($collection, $rows);
        }

        // By collection_lang
        if ($lang = ee()->pro_search_params->get('collection_lang')) {
            list($vals, $in) = ee()->pro_search_params->explode($lang);
            $rows = $this->get_by_language($vals, $in, $rows);
        }

        // By site
        if (! empty($rows)) {
            $rows = $this->get_by_site(ee()->pro_search_params->site_ids(), true, $rows);
        }

        return $rows;
    }

    // --------------------------------------------------------------

    /**
     * Get channel IDs by parameter
     */
    public function get_channel_ids($param = null)
    {
        $channel_ids = array();

        $rows = $param ? $this->get_by_param($param) : $this->get_by_params();

        if ($rows) {
            $channel_ids = pro_flatten_results($rows, 'channel_id');
            $channel_ids = array_unique($channel_ids);
        }

        return $channel_ids;
    }

    // --------------------------------------------------------------

    /**
     * Get all rows based on attr and val
     */
    private function _get_by_attr($val, $attr = 'collection_id', $in = true, $cols = null)
    {
        $all = is_array($cols) ? $cols : $this->get_all();
        $rows = array();

        // Make sure value is in an array
        if (! is_array($val)) {
            $val = array($val);
        }

        // Loop through all and add maching to rows
        foreach ($all as $id => $row) {
            if ($in === in_array($row[$attr], $val)) {
                $rows[$id] = $row;
            }
        }

        return $rows;
    }
}
// End class

/* End of file Pro_search_collection_model.php */
