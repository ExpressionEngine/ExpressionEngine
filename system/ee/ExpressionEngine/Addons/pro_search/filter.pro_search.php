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
 * Pro Search Filter class, for inheritance
 */
abstract class Pro_search_filter
{
    /**
     * Default priority for this filter
     */
    protected $priority = 5;

    /**
     * Shortcut to Pro_search_params
     */
    protected $params;
    protected $fields;

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
     */
    public function __construct()
    {
        // Set the shortcut
        $this->params = & ee()->pro_search_params;
        $this->fields = & ee()->pro_search_fields;
    }

    // --------------------------------------------------------------------

    /**
     * Return the priority
     */
    public function priority()
    {
        return $this->priority;
    }

    /**
     * The filter method
     */
    public function filter($entry_ids)
    {
        return $entry_ids;
    }

    /**
     * Fixed order?
     */
    public function fixed_order()
    {
        return false;
    }

    /**
     * Exclude IDs?
     */
    public function exclude()
    {
        return null;
    }

    /**
     * The results method for manipulating search results
     */
    public function results($rows)
    {
        return $rows;
    }

    // --------------------------------------------------------------------

    /**
     * Deprecated: use $this->fields->id() instead
     *
     * @see        Pro_search_fields::id()
     */
    protected function _get_field_id($str, $fields = array())
    {
        return $this->fields->id($str, $fields);
    }

    // --------------------------------------------------------------------

    /**
     * Deprecated: use $this->fields->sql() instead
     *
     * @see        Pro_search_fields::sql()
     */
    protected function _get_where_search($field, $val)
    {
        return $this->fields->sql($field, $val);
    }

    // --------------------------------------------------------------------

    /**
     * Remove vars from tagdata
     *
     * @access     protected
     * @param      mixed
     * @return     void
     */
    protected function _remove_rogue_vars($key, $prefix = true)
    {
        // Force array
        if (! is_array($key)) {
            $key = array($key);
        }

        foreach ($key as $pfx) {
            // Append global prefix?
            if ($prefix) {
                $pfx = ee()->pro_search_settings->prefix . $pfx;
            }

            // Escape
            $pfx = preg_quote($pfx);

            // Strip vars from tagdata
            ee()->TMPL->tagdata = preg_replace(
                "/\{{$pfx}[\w\-:]+?\}/",
                '',
                ee()->TMPL->tagdata
            );
        }
    }

    // --------------------------------------------------------------------

    /**
     * Log message to Template Logger
     *
     * @access     protected
     * @param      string
     * @return     void
     */
    protected function _log($msg)
    {
        ee()->TMPL->log_item("Pro Search: {$msg}");
    }
}
// End of file filter.pro_search.php
