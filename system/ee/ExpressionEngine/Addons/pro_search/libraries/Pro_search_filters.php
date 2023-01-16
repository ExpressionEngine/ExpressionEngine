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
 * Pro Search Filters class, to run all filters
 */
class Pro_search_filters
{
    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Path to keep filter files
     *
     * @access     private
     * @var        string
     */
    private $_filters_path;

    /**
     * Name of filters path, also for 3rd party filters
     *
     * @access     private
     * @var        string
     */
    private $_filters_dir = 'filters';

    /**
     * Filter objects
     *
     * @access     private
     * @var        array
     */
    private $_filters = array();

    /**
     * Entry ids
     *
     * @access     private
     * @var        mixed     [null|array]
     */
    private $_entry_ids;

    /**
     * Are the entry ids in a fixed order?
     *
     * @access     private
     * @var        bool
     */
    private $_fixed_order;

    /**
     * ids to exclude
     *
     * @access     private
     * @var        mixed     [null|array]
     */
    private $_exclude;

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access     public
     * @return     void
     */
    public function __construct()
    {
        // include parent filter class
        if (! class_exists('Pro_search_filter')) {
            require_once(PATH_ADDONS . 'pro_search/filter.pro_search.php');

            // Add an alias so the old filter class name can still work
            class_alias('Pro_search_filter', 'Low_search_filter');
        }

        // Load directory helper
        ee()->load->helper('directory');

        // Loop through all filters dirs found
        foreach ($this->_get_filters_dirs() as $path) {
            // Read filters directory and load up the filters
            foreach (directory_map($path, 1) as $item) {
                // Compose directory
                $dir = $path . $item;

                // Skip if not a dir
                if (! is_dir($dir)) {
                    continue;
                }

                // Skip if we're trying to load something from low search
                if (strpos($path, 'low_search') !== false) {
                    continue;
                }

                $possibleFiles = [
                    $dir . "/lsf.{$item}.php",
                    $dir . "/psf.{$item}.php",
                ];

                $possibleClasses = [
                    'Low_search_filter_' . $item,
                    'Pro_search_filter_' . $item,
                ];

                // Check low search files and pro search files for available filters
                foreach ($possibleFiles as $file) {
                    // Skip if not a file
                    if (! file_exists($file)) {
                        continue;
                    }

                    // Load the class
                    require_once($file);

                    foreach ($possibleClasses as $class) {
                        if (class_exists($class)) {
                            $this->_filters[$item] = new $class();
                        }
                    }
                }
            }
        }

        // Sort by priority
        uasort($this->_filters, array($this, '_by_priority'));
    }

    // --------------------------------------------------------------------

    /**
     * Return the loaded filter names
     *
     * @access     public
     * @return     array
     */
    public function names()
    {
        return array_keys($this->_filters);
    }

    // --------------------------------------------------------------------

    /**
     * Run loaded filters
     *
     * @access     public
     * @return     void
     */
    public function filter($reset = true)
    {
        // Reset first?
        if ($reset) {
            $this->reset();
        }

        // Loop through each filter and run it
        foreach ($this->_filters as $name => $filter) {
            // Skip disabled filters
            if ($this->_disabled($name)) {
                continue;
            }

            // Get IDs from filter
            $ids = $filter->filter($this->_entry_ids);

            // Are these IDs in fixed order?
            $fixed = $filter->fixed_order();

            // Are we excluding entry IDs?
            if ($exclude = $filter->exclude()) {
                // Combine exclusion with existing
                $exclude = $this->exclude($exclude);
            }

            // If we're excluding and we have entry IDs,
            // subtract them from the inclusive IDs.
            if (! empty($exclude) && ! empty($ids)) {
                $ids = array_diff($ids, $exclude);

                // Reset internal exclusion property
                $this->_exclude = null;
            }

            // Keep existing order if there are results
            $this->_entry_ids
                = (! empty($this->_entry_ids) && ! empty($ids) && ! $fixed)
                ? array_values(array_intersect($this->_entry_ids, $ids))
                : $ids;

            // Remember that we're returning a fixed order
            if (! $this->_fixed_order && $fixed) {
                $this->_fixed_order = $fixed;
            }

            // Break out when there aren't any search results
            if (is_array($this->_entry_ids) && empty($this->_entry_ids)) {
                break;
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Run loaded filters
     *
     * @access     public
     * @param      array
     * @return     array
     */
    public function results($rows)
    {
        // Loop through each filter and run it
        foreach ($this->_filters as $name => $filter) {
            // Skip disabled filters
            if ($this->_disabled($name)) {
                continue;
            }

            // Or else, trigger the results method
            $rows = $filter->results($rows);
        }

        return $rows;
    }

    // --------------------------------------------------------------------

    /**
     * Set entry ids
     *
     * @access     public
     * @param      array
     */
    public function set_entry_ids($entry_ids)
    {
        $this->_entry_ids = (array) $entry_ids;
    }

    // --------------------------------------------------------------------

    /**
     * Return the entry ids
     *
     * @access     public
     * @return     mixed     [null|array]
     */
    public function entry_ids()
    {
        return $this->_entry_ids;
    }

    // --------------------------------------------------------------------

    /**
     * Return the fixed order bool
     *
     * @access     public
     * @return     bool
     */
    public function fixed_order()
    {
        return $this->_fixed_order;
    }

    // --------------------------------------------------------------------

    /**
     * Return the entry ids to exclude or add to the existing list
     *
     * @access     public
     * @param      mixed
     * @return     mixed     [null|array]
     */
    public function exclude($ids = null)
    {
        if (! empty($ids)) {
            // Force to array
            if (! is_array($ids)) {
                $ids = array($ids);
            }

            // Add to existing ids
            $this->_exclude = empty($this->_exclude)
                ? $ids
                : array_merge($this->_exclude, $ids);

            // Make sure to clean up
            $this->_exclude = array_filter(array_unique($this->_exclude));
        }

        return $this->_exclude;
    }

    // --------------------------------------------------------------------

    /**
     * Reset IDs and whatnot
     *
     * @access     public
     * @return     void
     */
    public function reset()
    {
        $this->_entry_ids = null;
        $this->_fixed_order = null;
        $this->_exclude = null;
    }

    // --------------------------------------------------------------------

    /**
     * Is filter enabled?
     */
    private function _disabled($name)
    {
        // Disabled in the settings
        if (in_array($name, ee()->pro_search_settings->get('disabled_filters'))) {
            return true;
        }

        // Disabled in the tag (ie. disable="pro_search:categories|pro_search:keywords")
        if (REQ == 'PAGE' && ee()->pro_search_params->in_param('pro_search:' . $name, 'disable')) {
            return true;
        }

        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Order filters by priority
     */
    private function _by_priority($a, $b)
    {
        $a = $a->priority();
        $b = $b->priority();

        if ($a > $b) {
            return 1;
        }
        if ($a == $b) {
            return 0;
        }
        if ($a < $b) {
            return -1;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Get filters directories from all add-ons
     */
    private function _get_filters_dirs()
    {
        // Init directories
        $dirs = array();

        // We need to check in system add-ons and user add-ons
        $addon_paths = [
            'system' => PATH_ADDONS,
            'user' => PATH_THIRD,
        ];

        foreach ($addon_paths as $addon_path) {
            $dir_map = directory_map($addon_path, 1);

            // Read 3rd party dir
            foreach ($dir_map as $item) {
                // The paths we're looking for
                $path = $addon_path . $item;
                $dir = $path . "/{$this->_filters_dir}/";

                // Skip if we're not dealing with dirs
                if (@is_dir($path) && @is_dir($dir)) {
                    $dirs[] = $dir;
                }
            }
        }

        return $dirs;
    }
}
// End of file Pro_search_filters.php
