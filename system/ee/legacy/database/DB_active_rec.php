<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Active Record Class
 *
 * This is the platform-independent base Active Record implementation class.
 */
class CI_DB_active_record extends CI_DB_driver
{
    public $ar_select = array();
    public $ar_distinct = false;
    public $ar_from = array();
    public $ar_join = array();
    public $ar_where = array();
    public $ar_like = array();
    public $ar_groupby = array();
    public $ar_having = array();
    public $ar_keys = array();
    public $ar_limit = false;
    public $ar_offset = false;
    public $ar_order = false;
    public $ar_orderby = array();
    public $ar_set = array();
    public $ar_wherein = array();
    public $ar_aliased_tables = array();
    public $ar_store_array = array();

    protected $ar_group_count = 0;
    protected $ar_empty_group = true;

    protected $ar_like_group_count = 0;
    protected $ar_empty_like_group = true;

    // Active Record Caching variables
    public $ar_caching = false;
    public $ar_cache_exists = array();
    public $ar_cache_select = array();
    public $ar_cache_from = array();
    public $ar_cache_join = array();
    public $ar_cache_where = array();
    public $ar_cache_like = array();
    public $ar_cache_groupby = array();
    public $ar_cache_having = array();
    public $ar_cache_orderby = array();
    public $ar_cache_set = array();

    public $ar_no_escape = array();
    public $ar_cache_no_escape = array();

    /**
     * Select
     *
     * Generates the SELECT portion of the query
     *
     * @access	public
     * @param	string $select The columns to select
     * @param   boolean $escape Set to FALSE to prevent CI_DB_driver::protect_identifiers() and CI_DB_driver::escape()
     * @return	CI_DB_active_record The active record object
     */
    public function select($select = '*', $escape = null)
    {
        if (is_string($select)) {
            $select = explode(',', $select);
        }

        foreach ($select as $val) {
            $val = trim($val);

            if ($val != '') {
                $this->ar_select[] = $val;
                $this->ar_no_escape[] = $escape;

                if ($this->ar_caching === true) {
                    $this->ar_cache_select[] = $val;
                    $this->ar_cache_exists[] = 'select';
                    $this->ar_cache_no_escape[] = $escape;
                }
            }
        }

        return $this;
    }

    /**
     * Select Max
     *
     * Generates a SELECT MAX(field) portion of a query
     *
     * @access	public
     * @param	string $select The columns to select
     * @param	string $alias The alias for the MAX(field) query
     * @return	CI_DB_active_record The active record object
     */
    public function select_max($select, $alias = '')
    {
        return $this->_max_min_avg_sum($select, $alias, 'MAX');
    }

    /**
     * Select Min
     *
     * Generates a SELECT MIN(field) portion of a query
     *
     * @access	public
     * @param	string $select The columns to select
     * @param	string $alias The alias for the MIN(field) query
     * @return	CI_DB_active_record The active record object
     */
    public function select_min($select, $alias = '')
    {
        return $this->_max_min_avg_sum($select, $alias, 'MIN');
    }

    /**
     * Select Average
     *
     * Generates a SELECT AVG(field) portion of a query
     *
     * @access	public
     * @param	string $select The columns to select
     * @param	string $alias The alias for the AVG(field) query
     * @return	CI_DB_active_record The active record object
     */
    public function select_avg($select, $alias = '')
    {
        return $this->_max_min_avg_sum($select, $alias, 'AVG');
    }

    /**
     * Select Sum
     *
     * Generates a SELECT SUM(field) portion of a query
     *
     * @access	public
     * @param	string $select The columns to select
     * @param	string $alias The alias for the SUM(field) query
     * @return	CI_DB_active_record The active record object
     */
    public function select_sum($select, $alias = '')
    {
        return $this->_max_min_avg_sum($select, $alias, 'SUM');
    }

    /**
     * Processing Function for the four functions above:
     *
     *	select_max()
     *	select_min()
     *	select_avg()
     *  select_sum()
     *
     * @access	public
     * @param	string $select The columns to select
     * @param	string $alias The alias for the SUM(field) query
     * @param   string $type The type of SELECT to perform (MAX, MIN, SUM, AVG)
     * @return	CI_DB_active_record The active record object
     */
    public function _max_min_avg_sum($select, $alias = '', $type = 'MAX')
    {
        if (! is_string($select) or empty($select)) {
            $this->display_error('db_invalid_query');
        }

        $type = strtoupper($type);

        if (! in_array($type, array('MAX', 'MIN', 'AVG', 'SUM'))) {
            show_error('Invalid function type: ' . $type);
        }

        if ($alias == '') {
            $alias = $this->_create_alias_from_table(trim($select));
        }

        $sql = $type . '(' . $this->_protect_identifiers(trim($select)) . ') AS ' . $alias;

        $this->ar_select[] = $sql;
        $this->ar_no_escape[] = null;

        if ($this->ar_caching === true) {
            $this->ar_cache_select[] = $sql;
            $this->ar_cache_exists[] = 'select';
        }

        return $this;
    }

    /**
     * Determines the alias name based on the table
     *
     * @access	private
     * @param	string
     * @return	string
     */
    public function _create_alias_from_table($item)
    {
        if (strpos($item, '.') !== false) {
            $item = explode('.', $item);

            return end($item);
        }

        return $item;
    }

    /**
     * DISTINCT
     *
     * Sets a flag which tells the query string compiler to add DISTINCT
     *
     * @access	public
     * @param	bool $val Set to FALSE to remove DISTINCT from a query
     * @return	CI_DB_active_record The active record object
     */
    public function distinct($val = true)
    {
        if (! is_bool($val)) {
            ee()->load->library('logger');
            ee()->logger->deprecated('3.2.0', 'Use CI_DB_active_rec::distinct() with a boolean parameter only.');
        }

        $this->ar_distinct = (bool) $val;

        return $this;
    }

    /**
     * From
     *
     * Generates the FROM portion of the query
     *
     * @access	public
     * @param	mixed $from Either a table name or an array of table names
     * @return	CI_DB_active_record The active record object
     */
    public function from($from)
    {
        foreach ((array) $from as $val) {
            if (strpos($val, ',') !== false) {
                foreach (explode(',', $val) as $v) {
                    $v = trim($v);
                    $this->_track_aliases($v);

                    $this->ar_from[] = $this->_protect_identifiers($v, true, null, false);

                    if ($this->ar_caching === true) {
                        $this->ar_cache_from[] = $this->_protect_identifiers($v, true, null, false);
                        $this->ar_cache_exists[] = 'from';
                    }
                }
            } else {
                $val = trim($val);

                // Extract any aliases that might exist.  We use this information
                // in the _protect_identifiers to know whether to add a table prefix
                $this->_track_aliases($val);

                $this->ar_from[] = $this->_protect_identifiers($val, true, null, false);

                if ($this->ar_caching === true) {
                    $this->ar_cache_from[] = $this->_protect_identifiers($val, true, null, false);
                    $this->ar_cache_exists[] = 'from';
                }
            }
        }

        return $this;
    }

    /**
     * Join
     *
     * Generates the JOIN portion of the query
     *
     * @access	public
     * @param	string $table The table to join
     * @param	string $cond The condition to join on
     * @param	string $type the type of join (left, right, outer, inner, left outer, right outer)
     * @param	string $alias give the join an alias
     * @return	CI_DB_active_record The active record object
     */
    public function join($table, $cond, $type = '', $alias = '')
    {
        if ($type != '') {
            $type = strtoupper(trim($type));

            if (! in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'))) {
                $type = '';
            } else {
                $type .= ' ';
            }
        }

        // Extract any aliases that might exist.  We use this information
        // in the _protect_identifiers to know whether to add a table prefix
        $this->_track_aliases($table);

        // Strip apart the condition and protect the identifiers
        if (empty($alias) && preg_match('/([\w\.]+)([\W\s]+)(.+)/', $cond, $match)) {
            $match[1] = $this->_protect_identifiers($match[1]);
            $match[3] = $this->_protect_identifiers($match[3]);

            $cond = $match[1] . $match[2] . $match[3];
        }

        // If a join alias specified, extract it now
        $join_alias = '';
        if (! empty($alias)) {
            $join_alias = ' ' . $alias . ' ';
        }

        // Assemble the JOIN statement
        $join = $type . 'JOIN ' . $this->_protect_identifiers($table, true, null, false) . $join_alias . ' ON ' . $cond;

        $this->ar_join[] = $join;
        if ($this->ar_caching === true) {
            $this->ar_cache_join[] = $join;
            $this->ar_cache_exists[] = 'join';
        }

        return $this;
    }

    /**
     * Where
     *
     * Generates the WHERE portion of the query. Separates
     * multiple calls with AND
     *
     * @access	public
     * @param	mixed $key Either the field to select, or an associative array
     *                     containing fields as keys and values as values. Make
     *                     sure your comparison operators are part of the
     *                     $key/key
     * @param	mixed $value The value to compare to
     * @param   boolean $escape Set to FALSE to prevent
     *                          CI_DB_driver::protect_identifiers() and
     *                          CI_DB_driver::escape()
     * @param	boolean $binary Whether or not to use a binary comparison
     * @return	CI_DB_active_record The active record object
     */
    public function where($key, $value = null, $escape = true, $binary = false)
    {
        return $this->_where($key, $value, 'AND ', $escape, $binary);
    }

    /**
     * OR Where
     *
     * Generates the WHERE portion of the query. Separates
     * multiple calls with OR
     *
     * @access	public
     * @param	mixed $key Either the field to select, or an associative array
     *                     containing fields as keys and values as values. Make
     *                     sure your comparison operators are part of the
     *                     $key/key
     * @param	mixed $value The value to compare to
     * @param   boolean $escape Set to FALSE to prevent
     *                          CI_DB_driver::protect_identifiers() and
     *                          CI_DB_driver::escape()
     * @param	boolean $binary Whether or not to use a binary comparison
     * @return	CI_DB_active_record The active record object
     */
    public function or_where($key, $value = null, $escape = true, $binary = false)
    {
        return $this->_where($key, $value, 'OR ', $escape, $binary);
    }

    /**
     * Where
     *
     * Called by where() or or_where()
     *
     * @access	private
     * @param	mixed $key Either the field to select, or an associative array
     *                     containing fields as keys and values as values. Make
     *                     sure your comparison operators are part of the
     *                     $key/key
     * @param	mixed $value The value to compare to
     * @param	string $boolean_operator The operator to use ('AND'/'OR')
     * @param   boolean $escape Set to FALSE to prevent
     *                          CI_DB_driver::protect_identifiers() and
     *                          CI_DB_driver::escape()
     * @param	boolean $binary Whether or not to use a binary comparison
     * @return	CI_DB_active_record The active record object
     */
    public function _where($key, $value = null, $boolean_operator = 'AND ', $escape = null, $binary = false)
    {
        if (! is_array($key)) {
            $key = array($key => $value);
        }

        // If the escape value was not set will will base it on the global setting
        if (! is_bool($escape)) {
            $escape = $this->_protect_identifiers;
        }

        foreach ($key as $k => $v) {
            // If the group is empty (or we don't have any where statements yet)
            // then ar_empty_group will be true and we don't need the boolean
            // operator.  When we're done, set it to FALSE to indicate that we
            // need boolean operators.  At least until a new group is opened.
            $boolean_operator_prefix = ($this->ar_empty_group) ? '' : $boolean_operator;
            $this->ar_empty_group = false;

            // For case-sensitive searches, prefix the column name with `binary `
            $binary_prefix = $binary ? 'binary ' : '';

            if (is_null($v) && ! $this->_has_operator($k)) {
                // value appears not to have been set, assign the test to IS NULL
                $k .= ' IS NULL';
            }

            if (! is_null($v)) {
                if ($escape === true) {
                    $k = $this->_protect_identifiers($k, false, $escape);

                    $v = ' ' . $this->escape($v);
                }

                if (! $this->_has_operator($k)) {
                    $k .= ' = ';
                }
            } else {
                $k = $this->_protect_identifiers($k, false, $escape);
            }

            $where = $boolean_operator_prefix . $binary_prefix . $k . $v;

            $this->ar_where[] = $where;

            if ($this->ar_caching === true) {
                $this->ar_cache_where[] = $where;
                $this->ar_cache_exists[] = 'where';
            }
        }

        return $this;
    }

    /**
     * Starts a new group by adding an opening parenthesis to the WHERE clause
     * of the query
     *
     * @return CI_DB_active_record The active record object
     */
    public function start_group()
    {
        return $this->_start_group('AND');
    }

    /**
     * Starts a new group by adding an opening parenthesis to the WHERE clause
     * of the query, prefixing it with OR
     *
     * @return CI_DB_active_record The active record object
     */
    public function or_start_group()
    {
        return $this->_start_group('OR');
    }

    /**
     * Start the query group
     *
     * @param string $boolean_operator Either AND or OR
     * @return CI_DB_active_record The active record object
     */
    protected function _start_group($boolean_operator = 'AND')
    {
        // If we're starting with a group, then we don't need the boolean operator.  Any other time, we do.
        $boolean_operator = (count($this->ar_where) == 0 and count($this->ar_cache_where) == 0) ? '' : $boolean_operator;

        // Sometimes you start a group and then immediately start another group, in those cases
        // we do not want an operator
        if ($this->ar_empty_group) {
            $boolean_operator = '';
        }

        // The str_repeat() is just for pretty spacing and readable queries.
        $this->ar_where[] = $boolean_operator . ' ( ';
        $this->ar_group_count++;

        // We now have an empty group again and don't need the next boolean operator.  Used by _where().
        $this->ar_empty_group = true;

        return $this;
    }

    /**
     * End the current group by adding a closing parenthesis to the WHERE clause
     * of the query
     *
     * @return CI_DB_active_record The active record object
     */
    public function end_group()
    {
        $this->ar_group_count--;
        $this->ar_where[] = ') ';

        // Unless the user opens a group and immediately closes it, this should
        // be superfluous.  ar_empty_group should already be FALSE from a previous
        // call to _where().  But just in case...
        $this->ar_empty_group = false;

        return $this;
    }

    /**
     * Starts a new group by adding an opening parenthesis to the WHERE clause
     * of the query
     *
     * @return CI_DB_active_record The active record object
     */
    public function start_like_group()
    {
        return $this->_start_like_group('AND');
    }

    /**
     * Starts a new group by adding an opening parenthesis to the WHERE clause
     * of the query, prefixing it with OR
     *
     * @return CI_DB_active_record The active record object
     */
    public function or_start_like_group()
    {
        return $this->_start_like_group('OR');
    }

    /**
     * Start the query group
     *
     * @param string $boolean_operator Either AND or OR
     * @return CI_DB_active_record The active record object
     */
    protected function _start_like_group($boolean_operator = 'AND')
    {
        // If we're starting with a group, then we don't need the boolean operator.  Any other time, we do.
        $boolean_operator = (count($this->ar_like) == 0 and count($this->ar_cache_like) == 0) ? '' : $boolean_operator;

        // Sometimes you start a group and then immediately start another group, in those cases
        // we do not want an operator
        if ($this->ar_empty_like_group) {
            $boolean_operator = '';
        }

        // The str_repeat() is just for pretty spacing and readable queries.
        $this->ar_like[] = str_repeat(' ', $this->ar_like_group_count) . $boolean_operator . ' (';
        $this->ar_like_group_count++;

        // We now have an empty group again and don't need the next boolean operator.  Used by _where().
        $this->ar_empty_like_group = true;

        return $this;
    }

    /**
     * End the current group by adding a closing parenthesis to the WHERE clause
     * of the query
     *
     * @return CI_DB_active_record The active record object
     */
    public function end_like_group()
    {
        $this->ar_like_group_count--;
        $this->ar_like[] = str_repeat(' ', $this->ar_like_group_count) . ')';

        // Unless the user opens a group and immediately closes it, this should
        // be superfluous.  ar_empty_group should already be FALSE from a previous
        // call to _where().  But just in case...
        $this->ar_empty_like_group = false;

        return $this;
    }

    /**
     * Where_in
     *
     * Generates a WHERE field IN ('item', 'item') SQL query joined with
     * AND if appropriate
     *
     * @access	public
     * @param	string $key The field for the WHERE IN clause
     * @param	array $values The values to search for
     * @param	boolean $binary Whether or not to use a binary comparison
     * @return	CI_DB_active_record The active record object
     */
    public function where_in($key, $values, $binary = false)
    {
        return $this->_where_in($key, $values, false, 'AND ', $binary);
    }

    /**
     * Where_in_or
     *
     * Generates a WHERE field IN ('item', 'item') SQL query joined with
     * OR if appropriate
     *
     * @access	public
     * @param	string $key The field for the WHERE IN clause
     * @param	array $values The values to search for
     * @param	boolean $binary Whether or not to use a binary comparison
     * @return	CI_DB_active_record The active record object
     */
    public function or_where_in($key, $values, $binary = false)
    {
        return $this->_where_in($key, $values, false, 'OR ', $binary);
    }

    /**
     * Where_not_in
     *
     * Generates a WHERE field NOT IN ('item', 'item') SQL query joined
     * with AND if appropriate
     *
     * @access	public
     * @param	string $key The field for the WHERE IN clause
     * @param	array $values The values to search for
     * @param	boolean $binary Whether or not to use a binary comparison
     * @return	CI_DB_active_record The active record object
     */
    public function where_not_in($key, $values, $binary = false)
    {
        return $this->_where_in($key, $values, true, 'AND ', $binary);
    }

    /**
     * Where_not_in_or
     *
     * Generates a WHERE field NOT IN ('item', 'item') SQL query joined
     * with OR if appropriate
     *
     * @access	public
     * @param	string $key The field for the WHERE IN clause
     * @param	array $values The values to search for
     * @param	boolean $binary Whether or not to use a binary comparison
     * @return	CI_DB_active_record The active record object
     */
    public function or_where_not_in($key, $values, $binary = false)
    {
        return $this->_where_in($key, $values, true, 'OR ', $binary);
    }

    /**
     * Where_in
     *
     * Called by where_in, where_in_or, where_not_in, where_not_in_or
     *
     * @access	public
     * @param	string $key The field for the WHERE IN clause
     * @param	array $values The values to search for
     * @param	boolean $not If the statement would be IN or NOT IN
     * @param	string $boolean_operator The operator to use ('AND'/'OR')
     * @return	CI_DB_active_record The active record object
     */
    public function _where_in($key = null, $values = null, $not = false, $boolean_operator = 'AND ', $binary = false)
    {
        if ($key === null or $values === null) {
            return;
        }

        $emptyArray = is_array($values) && empty($values);

        if (! is_array($values)) {
            $values = array($values);
        }

        $not = ($not) ? ' NOT' : '';

        foreach ($values as $value) {
            $this->ar_wherein[] = $this->escape($value);
        }

        $boolean_operator_prefix = ($this->ar_empty_group) ? '' : $boolean_operator;
        $binary_prefix = $binary ? 'binary ' : '';

        $this->ar_empty_group = false;

        if ($emptyArray) {
            $where_in = $boolean_operator_prefix . $not . ' 1 = 2 ';
        } else {
            $where_in = $boolean_operator_prefix . $binary_prefix . $this->_protect_identifiers($key) . $not . " IN (" . implode(", ", $this->ar_wherein) . ") ";
        }

        $this->ar_where[] = $where_in;

        if ($this->ar_caching === true) {
            $this->ar_cache_where[] = $where_in;
            $this->ar_cache_exists[] = 'where';
        }

        // reset the array for multiple calls
        $this->ar_wherein = array();

        return $this;
    }

    /**
     * Like
     *
     * Generates a %LIKE% portion of the query. Separates
     * multiple calls with AND
     *
     * @access	public
     * @param	mixed $field Either the field name for the WHERE ... LIKE clause
     *                       or an associative array where the keys are fields
     *                       and the values are the match
     * @param	mixed $match The value to match against
     * @param   string $side Controls where the wildcard (%) is placed, can be
     *                       'both', 'left', or 'right'
     * @return	CI_DB_active_record The active record object
     */
    public function like($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'AND ', $side);
    }

    /**
     * Not Like
     *
     * Generates a NOT LIKE portion of the query. Separates
     * multiple calls with AND
     *
     * @access	public
     * @param	mixed $field Either the field name for the WHERE ... LIKE clause
     *                       or an associative array where the keys are fields
     *                       and the values are the match
     * @param	mixed $match The value to match against
     * @param   string $side Controls where the wildcard (%) is placed, can be
     *                       'both', 'left', or 'right'
     * @return	CI_DB_active_record The active record object
     */
    public function not_like($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'AND ', $side, 'NOT');
    }

    /**
     * OR Like
     *
     * Generates a %LIKE% portion of the query. Separates
     * multiple calls with OR
     *
     * @access	public
     * @param	mixed $field Either the field name for the WHERE ... LIKE clause
     *                       or an associative array where the keys are fields
     *                       and the values are the match
     * @param	mixed $match The value to match against
     * @param   string $side Controls where the wildcard (%) is placed, can be
     *                       'both', 'left', or 'right'
     * @return	CI_DB_active_record The active record object
     */
    public function or_like($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'OR ', $side);
    }

    /**
     * OR Not Like
     *
     * Generates a NOT LIKE portion of the query. Separates
     * multiple calls with OR
     *
     * @access	public
     * @param	mixed $field Either the field name for the WHERE ... LIKE clause
     *                       or an associative array where the keys are fields
     *                       and the values are the match
     * @param	mixed $match The value to match against
     * @param   string $side Controls where the wildcard (%) is placed, can be
     *                       'both', 'left', or 'right'
     * @return	CI_DB_active_record The active record object
     */
    public function or_not_like($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'OR ', $side, 'NOT');
    }

    /**
     * Like
     *
     * Called by like() or orlike()
     *
     * @access	private
     * @param	mixed $field Either the field name for the WHERE ... LIKE clause
     *                       or an associative array where the keys are fields
     *                       and the values are the match
     * @param	mixed $match The value to match against
     * @param   string $type Either 'AND' or 'OR' depending on the clause
     * @param   string $side Controls where the wildcard (%) is placed, can be
     *                       'both', 'left', or 'right'
     * @param	string $not Set as 'NOT' for a NOT LIKE clause
     * @return	CI_DB_active_record The active record object
     */
    public function _like($field, $match = '', $type = 'AND ', $side = 'both', $not = '')
    {
        if (! is_array($field)) {
            $field = array($field => $match);
        }

        foreach ($field as $k => $v) {
            $k = $this->_protect_identifiers($k);

            $prefix = ($this->ar_empty_like_group) ? '' : $type;
            $this->ar_empty_like_group = false;

            $v = $this->escape_like_str($v);

            if ($side == 'before') {
                $like_statement = $prefix . " $k $not LIKE '%{$v}'";
            } elseif ($side == 'after') {
                $like_statement = $prefix . " $k $not LIKE '{$v}%'";
            } else {
                $like_statement = $prefix . " $k $not LIKE '%{$v}%'";
            }

            // some platforms require an escape sequence definition for LIKE wildcards
            if ($this->_like_escape_str != '') {
                $like_statement = $like_statement . sprintf($this->_like_escape_str, $this->_like_escape_chr);
            }

            $this->ar_like[] = str_repeat('  ', $this->ar_like_group_count) . $like_statement;
            if ($this->ar_caching === true) {
                $this->ar_cache_like[] = $like_statement;
                $this->ar_cache_exists[] = 'like';
            }
        }

        return $this;
    }

    /**
     * GROUP BY
     *
     * @access	public
     * @param	mixed $by The field or array of fields to GROUP BY
     * @return	CI_DB_active_record The active record object
     */
    public function group_by($by)
    {
        if (is_string($by)) {
            $by = explode(',', $by);
        }

        foreach ($by as $val) {
            $val = trim($val);

            if ($val != '') {
                $this->ar_groupby[] = $this->_protect_identifiers($val);

                if ($this->ar_caching === true) {
                    $this->ar_cache_groupby[] = $this->_protect_identifiers($val);
                    $this->ar_cache_exists[] = 'groupby';
                }
            }
        }

        return $this;
    }

    /**
     * Sets the HAVING value
     *
     * Separates multiple calls with AND
     *
     * @access	public
     * @param	string $key Either the field for the HAVING clause or an
     *                      associative array containing the fields as keys and
     *                      conditions as values
     * @param	string $value The condition to check for
     * @param   boolean $escape Set to FALSE to prevent CI_DB_driver::protect_identifiers() and CI_DB_driver::escape()
     * @return	CI_DB_active_record The active record object
     */
    public function having($key, $value = '', $escape = true)
    {
        return $this->_having($key, $value, 'AND ', $escape);
    }

    /**
     * Sets the OR HAVING value
     *
     * Separates multiple calls with OR
     *
     * @access	public
     * @param	string $key Either the field for the HAVING clause or an
     *                      associative array containing the fields as keys and
     *                      conditions as values
     * @param	string $value The condition to check for
     * @param   boolean $escape Set to FALSE to prevent CI_DB_driver::protect_identifiers() and CI_DB_driver::escape()
     * @return	CI_DB_active_record The active record object
     */
    public function or_having($key, $value = '', $escape = true)
    {
        return $this->_having($key, $value, 'OR ', $escape);
    }

    /**
     * Sets the HAVING values
     *
     * Called by having() or or_having()
     *
     * @access	private
     * @param	string $key Either the field for the HAVING clause or an
     *                      associative array containing the fields as keys and
     *                      conditions as values
     * @param	string $value The condition to check for
     * @param   string $type Whether this is an 'AND' or 'OR' HAVING
     * @param   boolean $escape Set to FALSE to prevent CI_DB_driver::protect_identifiers() and CI_DB_driver::escape()
     * @return	CI_DB_active_record The active record object
     */
    public function _having($key, $value = '', $type = 'AND ', $escape = true)
    {
        if (! is_array($key)) {
            $key = array($key => $value);
        }

        foreach ($key as $k => $v) {
            $prefix = (count($this->ar_having) == 0) ? '' : $type;

            if ($escape === true) {
                $k = $this->_protect_identifiers($k);
            }

            if (! $this->_has_operator($k)) {
                $k .= ' = ';
            }

            if ($v != '') {
                $v = ' ' . $this->escape_str($v);
            }

            $this->ar_having[] = $prefix . $k . $v;
            if ($this->ar_caching === true) {
                $this->ar_cache_having[] = $prefix . $k . $v;
                $this->ar_cache_exists[] = 'having';
            }
        }

        return $this;
    }

    /**
     * Sets the ORDER BY value
     *
     * @access	public
     * @param	string $orderby The field to ORDER BY
     * @param	string $direction asc, desc, or random
     * @param   boolean $escape Set to FALSE to prevent CI_DB_driver::protect_identifiers() and CI_DB_driver::escape()
     * @return	CI_DB_active_record The active record object
     */
    public function order_by($orderby, $direction = '', $escape = null)
    {
        if (strtolower($direction) == 'random') {
            $orderby = ''; // Random results want or don't need a field name
            $direction = $this->_random_keyword;
        } elseif (trim($direction) != '') {
            $direction = (in_array(strtoupper(trim($direction)), array('ASC', 'DESC'), true)) ? ' ' . $direction : ' ASC';
        }

        if (strpos($orderby, ',') !== false) {
            $temp = array();
            foreach (explode(',', $orderby) as $part) {
                $part = trim($part);
                if (! in_array($part, $this->ar_aliased_tables)) {
                    $part = $this->_protect_identifiers(trim($part), false, $escape);
                }

                $temp[] = $part;
            }

            $orderby = implode(', ', $temp);
        } elseif ($direction != $this->_random_keyword) {
            $orderby = $this->_protect_identifiers($orderby);
        }

        $orderby_statement = $orderby . $direction;

        $this->ar_orderby[] = $orderby_statement;
        if ($this->ar_caching === true) {
            $this->ar_cache_orderby[] = $orderby_statement;
            $this->ar_cache_exists[] = 'orderby';
        }

        return $this;
    }

    /**
     * Sets the LIMIT value
     *
     * @access	public
     * @param	integer $value The number of rows to LIMIT
     * @param   integer $offset The number of rows to offset
     * @param	integer	the offset value
     * @return	CI_DB_active_record The active record object
     */
    public function limit($value, $offset = '')
    {
        $this->ar_limit = $value;

        if ($offset != '') {
            $this->ar_offset = $offset;
        }

        return $this;
    }

    /**
     * Sets the OFFSET value
     *
     * @access	public
     * @param   integer $offset The number of rows to offset
     * @return	CI_DB_active_record The active record object
     */
    public function offset($offset)
    {
        $this->ar_offset = $offset;

        return $this;
    }

    /**
     * The "set" function.  Allows key/value pairs to be set for inserting or updating
     *
     * @access	public
     * @param	mixed $key Either the field for the SET or an associative array
     *                     containing the fields as keys and the values as
     *                     values
     * @param	string $value The value to SET
     * @param   boolean $escape Set to FALSE to prevent CI_DB_driver::protect_identifiers() and CI_DB_driver::escape()
     * @return	CI_DB_active_record The active record object
     */
    public function set($key, $value = '', $escape = true)
    {
        $key = $this->_object_to_array($key);

        if (! is_array($key)) {
            $key = array($key => $value);
        }

        foreach ($key as $k => $v) {
            if ($escape === false) {
                $this->ar_set[$this->_protect_identifiers($k)] = $v;
            } else {
                $this->ar_set[$this->_protect_identifiers($k)] = $this->escape($v);
            }
        }

        return $this;
    }

    /**
     * Get
     *
     * Compiles the select statement based on the other functions called
     * and runs the query
     *
     * @access	public
     * @param   string $table  The table name to pull results from
     * @param   int $limit  The number of rows to pull
     * @param   int $offset The number of rows to offset
     * @return	CI_DB_result The query result object
     */
    public function get($table = '', $limit = null, $offset = null)
    {
        if ($table != '') {
            $this->_track_aliases($table);
            $this->from($table);
        }

        if (! is_null($limit)) {
            $this->limit($limit, $offset);
        }

        $sql = $this->_compile_select();

        $result = $this->query($sql);
        $this->_reset_select();

        return $result;
    }

    /**
     * "Count All Results" query
     *
     * Generates a platform-specific query string that counts all records
     * returned by an Active Record query.
     *
     * @access	public
     * @param	string $table The table to count results from
     * @return	int
     */
    public function count_all_results($table = '')
    {
        if ($table != '') {
            $this->_track_aliases($table);
            $this->from($table);
        }

        $sql = $this->_compile_select($this->_count_string . $this->_protect_identifiers('numrows'));

        $query = $this->query($sql);
        $this->_reset_select();

        if ($query->num_rows() == 0) {
            return 0;
        }

        $row = $query->row();

        return (int) $row->numrows;
    }

    /**
     * Get_Where
     *
     * Allows the where clause, limit and offset to be added directly
     *
     * @access	public
     * @param   string $table The table name to pull results from
     * @param   array $where Where clause as associative array
     * @param   int $limit  The number of rows to pull
     * @param   int $offset The number of rows to offset
     * @return	CI_DB_result The query result object
     */
    public function get_where($table = '', $where = null, $limit = null, $offset = null)
    {
        if ($table != '') {
            $this->from($table);
        }

        if (! is_null($where)) {
            $this->where($where);
        }

        if (! is_null($limit)) {
            $this->limit($limit, $offset);
        }

        $sql = $this->_compile_select();

        $result = $this->query($sql);
        $this->_reset_select();

        return $result;
    }

    /**
     * Insert_Batch
     *
     * Compiles batch insert strings and runs the queries
     *
     * @access	public
     * @param	string $table The table to INSERT INTO
     * @param   array $set An associative array of field names as keys and Values
     *                     as values
     * @return	boolean TRUE if successful, FALSE otherwise
     */
    public function insert_batch($table = '', $set = null)
    {
        if (! is_null($set)) {
            $this->set_insert_batch($set);
        }

        if (count($this->ar_set) == 0) {
            if ($this->db_debug) {
                //No valid data array.  Folds in cases where keys and values did not match up
                return $this->display_error('db_must_use_set');
            }

            return false;
        }

        if ($table == '') {
            if (! isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->display_error('db_must_set_table');
                }

                return false;
            }

            $table = $this->ar_from[0];
        }

        // Batch this baby
        for ($i = 0, $total = count($this->ar_set); $i < $total; $i = $i + 100) {
            $sql = $this->_insert_batch($this->_protect_identifiers($table, true, null, false), $this->ar_keys, array_slice($this->ar_set, $i, 100));

            //echo $sql;

            $this->query($sql);
        }

        $this->_reset_write();

        return true;
    }

    /**
     * The "set_insert_batch" function.  Allows key/value pairs to be set for batch inserts
     *
     * @access	public
     * @param	mixed
     * @param	string
     * @param   boolean $escape Set to FALSE to prevent CI_DB_driver::protect_identifiers() and CI_DB_driver::escape()
     * @return	CI_DB_active_record The active record object
     */
    public function set_insert_batch($key, $value = '', $escape = true)
    {
        $key = $this->_object_to_array_batch($key);

        if (! is_array($key)) {
            $key = array($key => $value);
        }

        $keys = array_keys(current($key));
        sort($keys);

        foreach ($key as $row) {
            if (count(array_diff($keys, array_keys($row))) > 0 or count(array_diff(array_keys($row), $keys)) > 0) {
                // batch function above returns an error on an empty array
                $this->ar_set[] = array();

                return;
            }

            ksort($row); // puts $row in the same order as our keys

            if ($escape === false) {
                $this->ar_set[] = '(' . implode(',', $row) . ')';
            } else {
                $clean = array();

                foreach ($row as $value) {
                    $clean[] = $this->escape($value);
                }

                $this->ar_set[] = '(' . implode(',', $clean) . ')';
            }
        }

        foreach ($keys as $k) {
            $this->ar_keys[] = $this->_protect_identifiers($k);
        }

        return $this;
    }

    /**
     * Insert
     *
     * Compiles an insert string and runs the query
     *
     * @access	public
     * @param	string $table The table to retrieve the results from
     * @param  array $set An associative array of field names as keys and Values
     *                    as values
     * @return	CI_DB_result The query result object
     */
    public function insert($table = '', $set = null)
    {
        if (! is_null($set)) {
            $this->set($set);
        }

        if (count($this->ar_set) == 0) {
            if ($this->db_debug) {
                return $this->display_error('db_must_use_set');
            }

            return false;
        }

        if ($table == '') {
            if (! isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->display_error('db_must_set_table');
                }

                return false;
            }

            $table = $this->ar_from[0];
        }

        $sql = $this->_insert($this->_protect_identifiers($table, true, null, false), array_keys($this->ar_set), array_values($this->ar_set));

        $this->_reset_write();

        return $this->query($sql);
    }

    /**
     * Runs a REPLACE statement
     *
     * @param  string $table The name of the table to ru the REPLACE INTO on
     * @param  array $set An associative array of field names as keys and Values
     *                    as values
     * @return CI_DB_object The query result object
     */
    public function replace($table = '', $set = null)
    {
        if (! is_null($set)) {
            $this->set($set);
        }

        if (count($this->ar_set) == 0) {
            if ($this->db_debug) {
                return $this->display_error('db_must_use_set');
            }

            return false;
        }

        if ($table == '') {
            if (! isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->display_error('db_must_set_table');
                }

                return false;
            }

            $table = $this->ar_from[0];
        }

        $sql = $this->_replace($this->_protect_identifiers($table, true, null, false), array_keys($this->ar_set), array_values($this->ar_set));

        $this->_reset_write();

        return $this->query($sql);
    }

    /**
     * Update
     *
     * Compiles an update string and runs the query
     *
     * @access	public
     * @param	string $table the table to UPDATE
     * @param  array $set An associative array of field names as keys and Values
     *                    as values
     * @param	array $where The where clause as an associative array
     * @param   int $limit The limit for updating records
     * @return	CI_DB_result The query result object
     */
    public function update($table = '', $set = null, $where = null, $limit = null)
    {
        // Combine any cached components with the current statements
        $this->_merge_cache();

        if (! is_null($set)) {
            $this->set($set);
        }

        if (count($this->ar_set) == 0) {
            if ($this->db_debug) {
                return $this->display_error('db_must_use_set');
            }

            return false;
        }

        if ($table == '') {
            if (! isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->display_error('db_must_set_table');
                }

                return false;
            }

            $table = $this->ar_from[0];
        }

        if ($where != null) {
            $this->where($where);
        }

        if ($limit != null) {
            $this->limit($limit);
        }

        $sql = $this->_update($this->_protect_identifiers($table, true, null, false), $this->ar_set, $this->ar_where, $this->ar_orderby, $this->ar_limit);

        $this->_reset_write();

        return $this->query($sql);
    }

    /**
     * Update_Batch
     *
     * Compiles an update string and runs the query
     *
     * @access	public
     * @param	string $table the table to UPDATE
     * @param  array $set An associative array of field names as keys and Values
     *                    as values
     * @param	string $index The field to base the WHERE on
     * @return	boolean TRUE if successful, FALSE otherwise
     */
    public function update_batch($table = '', $set = null, $index = null)
    {
        // Combine any cached components with the current statements
        $this->_merge_cache();

        if (is_null($index)) {
            if ($this->db_debug) {
                return $this->display_error('db_must_use_index');
            }

            return false;
        }

        if (! is_null($set)) {
            $this->set_update_batch($set, $index);
        }

        if (count($this->ar_set) == 0) {
            if ($this->db_debug) {
                return $this->display_error('db_must_use_set');
            }

            return false;
        }

        if ($table == '') {
            if (! isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->display_error('db_must_set_table');
                }

                return false;
            }

            $table = $this->ar_from[0];
        }

        // Batch this baby
        for ($i = 0, $total = count($this->ar_set); $i < $total; $i = $i + 100) {
            $sql = $this->_update_batch($this->_protect_identifiers($table, true, null, false), array_slice($this->ar_set, $i, 100), $this->_protect_identifiers($index), $this->ar_where);

            $this->query($sql);
        }

        $this->_reset_write();
    }

    /**
     * The "set_update_batch" function.  Allows key/value pairs to be set for batch updating
     *
     * @access	public
     * @param	array
     * @param	string
     * @param   boolean $escape Set to FALSE to prevent CI_DB_driver::protect_identifiers() and CI_DB_driver::escape()
     * @return	CI_DB_active_record The active record object
     */
    public function set_update_batch($key, $index = '', $escape = true)
    {
        $key = $this->_object_to_array_batch($key);

        if (! is_array($key)) {
            // @todo error
        }

        foreach ($key as $k => $v) {
            $index_set = false;
            $clean = array();

            foreach ($v as $k2 => $v2) {
                if ($k2 == $index) {
                    $index_set = true;
                }

                if ($escape === false) {
                    $clean[$this->_protect_identifiers($k2)] = $v2;
                } else {
                    $clean[$this->_protect_identifiers($k2)] = $this->escape($v2);
                }
            }

            if ($index_set == false) {
                return $this->display_error('db_batch_missing_index');
            }

            $this->ar_set[] = $clean;
        }

        return $this;
    }

    /**
     * Empty Table
     *
     * Compiles a delete string and runs "DELETE FROM table"
     *
     * @access	public
     * @param	string $table The table to empty
     * @return	CI_DB_result The query result object
     */
    public function empty_table($table = '')
    {
        if ($table == '') {
            if (! isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->display_error('db_must_set_table');
                }

                return false;
            }

            $table = $this->ar_from[0];
        } else {
            $table = $this->_protect_identifiers($table, true, null, false);
        }

        $sql = $this->_delete($table);

        $this->_reset_write();

        return $this->query($sql);
    }

    /**
     * Truncate
     *
     * Compiles a truncate string and runs the query
     * If the database does not support the truncate() command
     * This function maps to "DELETE FROM table"
     *
     * @access	public
     * @param	string $table The table to truncate
     * @return	CI_DB_result The query result object
     */
    public function truncate($table = '')
    {
        if ($table == '') {
            if (! isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->display_error('db_must_set_table');
                }

                return false;
            }

            $table = $this->ar_from[0];
        } else {
            $table = $this->_protect_identifiers($table, true, null, false);
        }

        $sql = $this->_truncate($table);

        $this->_reset_write();

        return $this->query($sql);
    }

    /**
     * Delete
     *
     * Compiles a delete string and runs the query
     *
     * @access	public
     * @param	mixed $table The name of the table or an array of tables to
     *                       DELETE from
     * @param	array $where The where clause as associative array
     * @param	int $limit The LIMIT for the DELETE
     * @param	boolean $reset_data Set to FALSE to not reset
     *                              CI_DB_active_record's write values
     * @return	CI_DB_result The query result object
     */
    public function delete($table = '', $where = '', $limit = null, $reset_data = true)
    {
        // Combine any cached components with the current statements
        $this->_merge_cache();

        if ($table == '') {
            if (! isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->display_error('db_must_set_table');
                }

                return false;
            }

            $table = $this->ar_from[0];
        } elseif (is_array($table)) {
            foreach ($table as $single_table) {
                $this->delete($single_table, $where, $limit, false);
            }

            $this->_reset_write();

            return;
        } else {
            $table = $this->_protect_identifiers($table, true, null, false);
        }

        if ($where != '') {
            $this->where($where);
        }

        if ($limit != null) {
            $this->limit($limit);
        }

        if (count($this->ar_where) == 0 && count($this->ar_wherein) == 0 && count($this->ar_like) == 0) {
            if ($this->db_debug) {
                return $this->display_error('db_del_must_use_where');
            }

            return false;
        }

        $sql = $this->_delete($table, $this->ar_where, $this->ar_like, $this->ar_limit);

        if ($reset_data) {
            $this->_reset_write();
        }

        return $this->query($sql);
    }

    /**
     * DB Prefix
     *
     * Prepends a database prefix if one exists in configuration
     *
     * @access	public
     * @param	string	the table
     * @return	string
     */
    public function dbprefix($table = '')
    {
        if ($table == '') {
            $this->display_error('db_table_name_required');
        }

        return $this->dbprefix . $table;
    }

    /**
     * Track Aliases
     *
     * Used to track SQL statements written with aliased tables.
     *
     * @access	private
     * @param	string	The table to inspect
     * @return	string
     */
    public function _track_aliases($table)
    {
        if (is_array($table)) {
            foreach ($table as $t) {
                $this->_track_aliases($t);
            }

            return;
        }

        // Does the string contain a comma?  If so, we need to separate
        // the string into discreet statements
        if (strpos($table, ',') !== false) {
            return $this->_track_aliases(explode(',', $table));
        }

        // if a table alias is used we can recognize it by a space
        if (strpos($table, " ") !== false) {
            // if the alias is written with the AS keyword, remove it
            $table = preg_replace('/ AS /i', ' ', $table);

            // Grab the alias
            $table = trim(strrchr($table, " "));

            // Store the alias, if it doesn't already exist
            if (! in_array($table, $this->ar_aliased_tables)) {
                $this->ar_aliased_tables[] = $table;
            }
        }

        if (in_array($table, ['member_groups', 'channel_member_groups', 'module_member_groups', 'layout_publish_member_groups', 'template_member_groups', 'upload_no_access', 'template_no_access', 'status_no_access'])) {
            $debug_backtrace = debug_backtrace(false);
            foreach ($debug_backtrace as $trace) {
                $marker = 'user' . DIRECTORY_SEPARATOR . 'addons' . DIRECTORY_SEPARATOR;
                if (isset($trace['file'])
                    && isset($trace['class'])
                    && is_string($trace['class'])
                    && strpos($trace['file'], $marker) !== false
                    && strpos($trace['class'], 'CI_DB_') === false) {
                    $addon_name = explode(DIRECTORY_SEPARATOR, substr($trace['file'], strpos($trace['file'], $marker) + strlen($marker)));
                    $addon = ee('Addon')->get($addon_name[0]);
                    $message = $addon->getName() . ' is making a call to `exp_' . $table . '` database table, which is non-existent in ExpressionEngine 6. If you are site owner, try upgrading ' . $addon->getName() . ' to latest available version. If you are the add-on developer, update your ' . $trace['class'] . ' class to use <a href="https://docs.expressionengine.com/latest/development/v6-add-on-migration.html#required-changes">Role model</a>.';

                    ee()->load->library('logger');
                    ee()->logger->developer($message, true);

                    throw new \Exception($message);
                }
            }
        }
    }

    /**
     * Compile the SELECT statement
     *
     * Generates a query string based on which functions were used.
     * Should not be called directly.  The get() function calls it.
     *
     * @access	private
     * @return	string
     */
    public function _compile_select($select_override = false, $protect_identifiers = true)
    {
        // Combine any cached components with the current statements
        $this->_merge_cache();

        // ----------------------------------------------------------------

        // Write the "select" portion of the query

        if ($select_override !== false) {
            $sql = $select_override;
        } else {
            $sql = (! $this->ar_distinct) ? 'SELECT ' : 'SELECT DISTINCT ';

            if (count($this->ar_select) == 0) {
                $sql .= '*';
            } else {
                // Cycle through the "select" portion of the query and prep each column name.
                // The reason we protect identifiers here rather then in the select() function
                // is because until the user calls the from() function we don't know if there are aliases
                if ($protect_identifiers) {
                    foreach ($this->ar_select as $key => $val) {
                        $this->ar_select[$key] = $this->_protect_identifiers($val, false, $this->ar_no_escape[$key]);
                    }
                }

                $sql .= implode(', ', $this->ar_select);
            }
        }

        // ----------------------------------------------------------------

        // Write the "FROM" portion of the query

        if (count($this->ar_from) > 0) {
            $sql .= "\nFROM ";

            $sql .= $this->_from_tables($this->ar_from);
        }

        // ----------------------------------------------------------------

        // Write the "JOIN" portion of the query

        if (count($this->ar_join) > 0) {
            $sql .= "\n";

            $sql .= implode("\n", $this->ar_join);
        }

        // ----------------------------------------------------------------

        // Write the "WHERE" portion of the query

        if (count($this->ar_where) > 0 or count($this->ar_like) > 0) {
            $sql .= "\nWHERE ";
        }

        $sql .= implode("\n", $this->ar_where);

        // ----------------------------------------------------------------

        // Write the "LIKE" portion of the query

        if (count($this->ar_like) > 0) {
            if (count($this->ar_where) > 0) {
                $sql .= "\nAND ";
            }

            $sql .= implode("\n", $this->ar_like);
        }

        // ----------------------------------------------------------------

        // Write the "GROUP BY" portion of the query

        if (count($this->ar_groupby) > 0) {
            $sql .= "\nGROUP BY ";

            $sql .= implode(', ', $this->ar_groupby);
        }

        // ----------------------------------------------------------------

        // Write the "HAVING" portion of the query

        if (count($this->ar_having) > 0) {
            $sql .= "\nHAVING ";
            $sql .= implode("\n", $this->ar_having);
        }

        // ----------------------------------------------------------------

        // Write the "ORDER BY" portion of the query

        if (count($this->ar_orderby) > 0) {
            $sql .= "\nORDER BY ";
            $sql .= implode(', ', $this->ar_orderby);

            if ($this->ar_order !== false) {
                $sql .= ($this->ar_order == 'desc') ? ' DESC' : ' ASC';
            }
        }

        // ----------------------------------------------------------------

        // Write the "LIMIT" portion of the query

        if (is_numeric($this->ar_limit)) {
            $sql .= "\n";
            $sql = $this->_limit($sql, $this->ar_limit, $this->ar_offset);
        }

        return $sql;
    }

    /**
     * Object to Array
     *
     * Takes an object as input and converts the class variables to array key/vals
     *
     * @access	public
     * @param	object
     * @return	array
     */
    public function _object_to_array($object)
    {
        if (! is_object($object)) {
            return $object;
        }

        $array = array();
        foreach (get_object_vars($object) as $key => $val) {
            // There are some built in keys we need to ignore for this conversion
            if (! is_object($val) && ! is_array($val) && $key != '_parent_name') {
                $array[$key] = $val;
            }
        }

        return $array;
    }

    /**
     * Object to Array
     *
     * Takes an object as input and converts the class variables to array key/vals
     *
     * @access	public
     * @param	object
     * @return	array
     */
    public function _object_to_array_batch($object)
    {
        if (! is_object($object)) {
            return $object;
        }

        $array = array();
        $out = get_object_vars($object);
        $fields = array_keys($out);

        foreach ($fields as $val) {
            // There are some built in keys we need to ignore for this conversion
            if ($val != '_parent_name') {
                $i = 0;
                foreach ($out[$val] as $data) {
                    $array[$i][$val] = $data;
                    $i++;
                }
            }
        }

        return $array;
    }

    /**
     * Start Cache
     *
     * Starts AR caching
     *
     * @access	public
     * @return	void
     */
    public function start_cache()
    {
        $this->ar_caching = true;
    }

    /**
     * Stop Cache
     *
     * Stops AR caching
     *
     * @access	public
     * @return	void
     */
    public function stop_cache()
    {
        $this->ar_caching = false;
    }

    /**
     * Flush Cache
     *
     * Empties the AR cache
     *
     * @access	public
     * @return	void
     */
    public function flush_cache()
    {
        $this->_reset_run(array(
            'ar_cache_select' => array(),
            'ar_cache_from' => array(),
            'ar_cache_join' => array(),
            'ar_cache_where' => array(),
            'ar_cache_like' => array(),
            'ar_cache_groupby' => array(),
            'ar_cache_having' => array(),
            'ar_cache_orderby' => array(),
            'ar_cache_set' => array(),
            'ar_cache_exists' => array(),
            'ar_cache_no_escape' => array(),
            'ar_empty_group' => empty($this->ar_where),
        ));
    }

    /**
     * Merge Cache
     *
     * When called, this function merges any cached AR arrays with
     * locally called ones.
     *
     * @access	private
     * @return	void
     */
    public function _merge_cache()
    {
        if (count($this->ar_cache_exists) == 0) {
            return;
        }

        foreach ($this->ar_cache_exists as $val) {
            $ar_variable = 'ar_' . $val;
            $ar_cache_var = 'ar_cache_' . $val;

            if (count($this->$ar_cache_var) == 0) {
                continue;
            }

            $this->$ar_variable = array_unique(array_merge($this->$ar_cache_var, $this->$ar_variable));
        }

        // If we are "protecting identifiers" we need to examine the "from"
        // portion of the query to determine if there are any aliases
        if ($this->_protect_identifiers === true and count($this->ar_cache_from) > 0) {
            $this->_track_aliases($this->ar_from);
        }

        $this->ar_no_escape = $this->ar_cache_no_escape;
    }

    /**
     * Resets the active record values.  Called by the get() function
     *
     * @access	private
     * @param	array	An array of fields to reset
     * @return	void
     */
    public function _reset_run($ar_reset_items)
    {
        foreach ($ar_reset_items as $item => $default_value) {
            if (! in_array($item, $this->ar_store_array)) {
                $this->$item = $default_value;
            }
        }
    }

    /**
     * Resets the active record values.  Called by the get() function
     *
     * @access	private
     * @return	void
     */
    public function _reset_select()
    {
        $ar_reset_items = array(
            'ar_select' => array(),
            'ar_from' => array(),
            'ar_join' => array(),
            'ar_where' => array(),
            'ar_like' => array(),
            'ar_groupby' => array(),
            'ar_having' => array(),
            'ar_orderby' => array(),
            'ar_wherein' => array(),
            'ar_aliased_tables' => array(),
            'ar_no_escape' => array(),
            'ar_distinct' => false,
            'ar_limit' => false,
            'ar_offset' => false,
            'ar_order' => false,
            'ar_empty_group' => empty($this->ar_cache_where),
            'ar_empty_like_group' => empty($this->ar_cache_like),
            'ar_group_count' => 0,
            'ar_like_group_count' => 0
        );

        $this->_reset_run($ar_reset_items);
    }

    /**
     * Resets the active record "write" values.
     *
     * Called by the insert() update() insert_batch() update_batch() and delete() functions
     *
     * @access	private
     * @return	void
     */
    public function _reset_write()
    {
        $ar_reset_items = array(
            'ar_set' => array(),
            'ar_from' => array(),
            'ar_where' => array(),
            'ar_like' => array(),
            'ar_orderby' => array(),
            'ar_keys' => array(),
            'ar_limit' => false,
            'ar_order' => false,
            'ar_empty_group' => empty($this->ar_cache_where),
            'ar_empty_like_group' => empty($this->ar_cache_like),
            'ar_group_count' => 0,
            'ar_like_group_count' => 0
        );

        $this->_reset_run($ar_reset_items);
    }
}

// EOF
