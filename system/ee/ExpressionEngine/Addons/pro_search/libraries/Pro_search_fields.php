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
 * Pro Search Fields class, for getting field info
 */
class Pro_search_fields
{
    const NATIVE_TABLE = 'channel_titles';

    /**
     * Native string fields
     */
    private $_native_strings = array(
        'title',
        'url_title',
        'status'
    );

    /**
     * Native date fields
     */
    private $_native_dates = array(
        'entry_date',
        'expiration_date',
        'comment_expiration_date',
        'recent_comment_date',
        'edit_date' // Which is in YYYYMMDDHHMMSS format. Obviously.
    );

    /**
     * Native numeric fields
     */
    private $_native_numeric = array(
        'view_count_one',
        'view_count_two',
        'view_count_thee',
        'view_count_four',
        'comment_total'
    );

    /**
     * The cache
     */
    protected $cache;

    // --------------------------------------------------------------------

    /**
     * Get fields
     *
     * @access     public
     * @param      string
     * @return     array
     */
    public function get($str = null)
    {
        if (! $this->cache) {
            // Don't use legacy api anymore. It's legacy.
            $this->cache = ee('Model')->get('ChannelField')->all();
        }

        // Get channel field by short name or ID
        if ($str) {
            $fields = $this->cache->filter(function ($field) use ($str) {
                $prop = is_numeric($str) ? 'field_id' : 'field_name';

                return $field->$prop == $str;
            });

            return is_numeric($str) ? $fields->first() : $fields;
        }

        // Return the cached fields
        return $this->cache;
    }

    // --------------------------------------------------------------------

    /**
     * Get field id for given field short name
     *
     * @access      public
     * @param       string
     * @return      int
     */
    public function id($str)
    {
        // Get an array of all matching IDs across all sites
        $it = $this->ids($str);

        // Prefer the current site_id, or else the first one
        $site_id = ee()->config->item('site_id');

        $it = isset($it[$site_id]) ? $it[$site_id] : current($it);

        // Please
        return $it;
    }

    // --------------------------------------------------------------------

    /**
     * Get field ids for given field short name
     *
     * @access      public
     * @param       string
     * @return      array
     */
    public function ids($str)
    {
        // --------------------------------------
        // Get custom channel fields from cache
        // --------------------------------------

        $fields = $this->get($str)->filter(function ($field) {
            return in_array($field->site_id, ee()->pro_search_params->site_ids());
        });

        // Convert to key/val pairs
        $it = $fields->getDictionary('site_id', 'field_id');

        // Please
        return $it;
    }
    // --------------------------------------------------------------------

    /**
     * Get database field name
     *
     * @access      public
     * @param       string
     * @param       string|null
     * @param       string|null
     * @return      string|bool
     */
    public function name($str, $native_prefix = null, $custom_prefix = null)
    {
        if ($this->is_native($str)) {
            return $native_prefix ? $native_prefix . '.' . $str : $str;
        } elseif ($id = $this->id($str)) {
            $str = 'field_id_' . $id;

            return $custom_prefix ? $custom_prefix . '.' . $str : $str;
        } else {
            return false;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Get table for given field
     */
    public function table($str)
    {
        if ($this->is_native($str)) {
            return static::NATIVE_TABLE;
        } elseif ($field = $this->get($str)->first()) {
            return $field->getDataStorageTable();
        } else {
            return false;
        }
    }

    /**
     * Get table for given field
     */
    public function native_table()
    {
        return static::NATIVE_TABLE;
    }

    // --------------------------------------------------------------------

    /**
     * For is_foo() methods
     *
     * @access     public
     * @param      string
     * @param      array
     * @return     bool
     */
    public function __call($fn, $args)
    {
        // Valid calls are is_foo
        if (! preg_match('/^is_([a-z_]+)$/', $fn, $match)) {
            throw new Exception($fn . ' is not a valid method in ' . __CLASS__, 1);
        }

        // We need at least 1 argument
        if (empty($args)) {
            throw new Exception('Too few arguments for ' . $fn, 1);
        }

        // Get our vars to w0rk with
        $what = $match[1];
        $field = $args[0];
        $it = false;

        // Is what, exactly?
        switch ($what) {
            // is_native: Native field?
            case 'native':
                $it = in_array($field, array_merge(
                    $this->_native_strings,
                    $this->_native_dates,
                    $this->_native_numeric
                ));

                break;

                // is_date: Native date field?
            case 'date':
                $it = (in_array($field, $this->_native_dates) || $this->isa($field, 'date'));

                break;

                // is_foo, where foo is a 3rd party custom pair field like matrix of playa
                // Optional second argument for loose matching
            default:
                // Type aliases
                $map = array(
                    'rel'       => 'relationship',
                    'fluid'     => 'fluid_field',
                    'tax_playa' => 'playa'
                );

                // Translate alias
                if (array_key_exists($what, $map)) {
                    $what = $map[$what];
                }

                $it = $this->isa($field, $what);

                break;
        }

        // Please
        return $it;
    }

    // --------------------------------------------------------------------

    /**
     * Check if given field is of a given type
     */
    public function isa($str, $type)
    {
        $fields = $this->get($str)->filter(function ($field) use ($type) {
            return $field->field_type == $type;
        });

        return ($fields->count() > 0);
    }

    // --------------------------------------------------------------------

    /**
     * Get column details from given table, based on field ID and column name
     *
     * @access     private
     * @param      int
     * @param      string
     * @param      string
     * @return     mixed     [int|bool]
     */
    private function _col($field_id, $col_name, $table = 'grid_columns')
    {
        $cols = pro_get_cache(__CLASS__, $table);

        if (! isset($cols[$field_id])) {
            // If $cols is not an array, we need to make it an array first
            if (!is_array($cols)) {
                $cols = [];
            }

            $cols[$field_id] = [];

            // Query all columns for this grid/matrix
            $query = ee()->db
                ->select('col_id, col_name, col_type')
                ->from($table)
                ->where('field_id', $field_id)
                ->get();

            // Add to cache array
            foreach ($query->result() as $row) {
                $cols[$field_id][$row->col_name] = array(
                    'id'   => $row->col_id,
                    'type' => $row->col_type
                );
            }

            pro_set_cache(__CLASS__, $table, $cols);
        }

        return array_key_exists($col_name, $cols[$field_id])
            ? $cols[$field_id][$col_name]
            : false;
    }

    /**
     * Get grid column ID based on field ID and column name
     *
     * @access     public
     * @param      int
     * @param      string
     * @return     mixed     [int|bool]
     */
    public function grid_col_id($field_id, $col_name)
    {
        return ($col = $this->_col($field_id, $col_name))
            ? $col['id']
            : false;
    }

    /**
     * Get matrix column ID based on field ID and column name
     *
     * @access     public
     * @param      int
     * @param      string
     * @return     mixed     [int|bool]
     */
    public function matrix_col_id($field_id, $col_name)
    {
        return ($col = $this->_col($field_id, $col_name, 'matrix_cols'))
            ? $col['id']
            : false;
    }

    /**
     * Get grid column type based on field ID and column name
     *
     * @access     public
     * @param      int
     * @param      string
     * @return     mixed     [string|bool]
     */
    public function grid_col_type($field_id, $col_name)
    {
        return ($col = $this->_col($field_id, $col_name))
            ? $col['type']
            : false;
    }

    /**
     * Get matrix column ID based on field ID and column name
     *
     * @access     public
     * @param      int
     * @param      string
     * @return     mixed     [int|bool]
     */
    public function matrix_col_type($field_id, $col_name)
    {
        return ($col = $this->_col($field_id, $col_name, 'matrix_cols'))
            ? $col['type']
            : false;
    }

    // --------------------------------------------------------------------

    /**
     * Get WHERE clause for given field and value, based on search: field rules
     *
     * @access     public
     * @param      string
     * @param      string
     * @return     string
     */
    public function sql($field, $val)
    {
        // Initiate some vars
        $exact = $all = $starts = $ends = $exclude = false;
        $sep = '|';

        // Exact matches
        if (substr($val, 0, 1) == '=') {
            $val = substr($val, 1);
            $exact = true;
        }

        // Starts with matches
        if (substr($val, 0, 1) == '^') {
            $val = substr($val, 1);
            $starts = true;
        }

        // Ends with matches
        if (substr($val, -1) == '$') {
            $val = rtrim($val, '$');
            $ends = true;
        }

        // All items? -> && instead of |
        if (strpos($val, '&&') !== false) {
            $all = true;
            $sep = '&&';
        }

        // Excluding?
        if (substr($val, 0, 4) == 'not ') {
            $val = substr($val, 4);
            $exclude = true;
        }

        // Explode it
        $items = explode($sep, $val);

        // Init sql for where clause
        $sql = array();

        // SQL template thingie
        $tmpl = '(%s %s %s)';

        // Loop through each sub-item of the filter an create sub-clause
        foreach ($items as $item) {
            // Left hand side of the sql
            $key = $field;

            // whole word? Regexp search
            if (substr($item, -2) == '\W') {
                $operand = $exclude ? 'NOT REGEXP' : 'REGEXP';
                $item = preg_quote(substr($item, 0, -2));
                $item = str_replace("'", "\'", $item);
                $item = "'[[:<:]]{$item}[[:>:]]'";
            } else {
                if (preg_match('/^([<>]=?)([\d\.]+)$/', $item, $match)) {
                    // Numeric operator!
                    $operand = $match[1];
                    $item = $match[2];
                } elseif ($item == 'IS_EMPTY') {
                    // IS_EMPTY should also account for NULL values as well as empty strings
                    $key = sprintf($tmpl, $field, ($exclude ? '!=' : '='), "''");
                    $item = sprintf($tmpl, $field, ($exclude ? 'IS NOT' : 'IS'), 'NULL');
                    $operand = $exclude ? 'AND' : 'OR';
                } elseif ($exact || ($starts && $ends)) {
                    // Use exact operand if empty or = was the first char in param
                    $operand = $exclude ? '!=' : '=';
                    $item = "'" . ee()->db->escape_str($item) . "'";
                } else {
                    // Use like operand in all other cases
                    $operand = $exclude ? 'NOT LIKE' : 'LIKE';
                    $item = '%' . ee()->db->escape_like_str($item) . '%';

                    // Allow for starts/ends with matching
                    if ($starts) {
                        $item = ltrim($item, '%');
                    }
                    if ($ends) {
                        $item = rtrim($item, '%');
                    }

                    $item = "'{$item}'";

                    // Account for `field` NOT LIKE '%foo%' where `field` can be NULL
                    if ($exclude) {
                        $item .= " OR {$key} IS NULL";
                    }
                }
            }

            $sql[] = sprintf($tmpl, $key, $operand, $item);
        }

        // Inclusive or exclusive
        $andor = $all ? ' AND ' : ' OR ';

        // Get complete clause, with parenthesis and everything
        $where = (count($sql) == 1) ? $sql[0] : '(' . implode($andor, $sql) . ')';

        return $where;
    }
}
// End of file Pro_search_fields.php
