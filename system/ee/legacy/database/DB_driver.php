<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Database Driver Class
 *
 * This is the platform-independent base DB implementation class.
 * This class will not be called directly. Rather, the adapter
 * class for the specific database will extend and instantiate it.
 */
class CI_DB_driver
{
    public $username;
    public $password;
    public $hostname;
    public $database;
    public $dbdriver = 'mysqli';
    public $dbprefix = '';
    public $char_set = 'utf8';
    public $dbcollat = 'utf8_unicode_ci';
    public $autoinit = true; // Whether to automatically initialize the DB
    public $swap_pre = '';
    public $port = '';
    public $pconnect = false;
    public $conn_id = false;
    public $result_id = false;
    public $db_debug = false;
    public $db_exception = false;
    public $benchmark = 0;
    public $query_count = 0;
    public $bind_marker = '?';
    public $last_query = null;
    public $data_cache = array();
    public $trans_enabled = true;
    public $trans_strict = true;
    public $_trans_depth = 0;
    public $_trans_status = true; // Used with transactions to determine if a rollback should occur
    public $cache_on = false;
    public $cachedir = '';
    public $cache_autodel = false;
    public $CACHE; // The cache class object

    // Private variables
    public $_protect_identifiers = true;
    public $_reserved_identifiers = array('*'); // Identifiers that should NOT be escaped

    // These are use with Oracle
    public $stmt_id;
    public $curs_id;
    public $limit_used;

    protected $_escape_char = '"';

    /**
     * Constructor.  Accepts one parameter containing the database
     * connection settings.
     *
     * @param array
     */
    public function __construct($params)
    {
        if (is_array($params)) {
            foreach ($params as $key => $val) {
                $this->$key = $val;
            }
        }

        log_message('debug', 'Database Driver Class Initialized');
    }

    /**
     * Initialize Database Settings
     *
     * @param	mixed
     * @return	void
     */
    public function initialize()
    {
        $this->connection->open();

        return true;
    }

    /**
     * Set client character set
     *
     * @access	public
     * @param	string
     * @param	string
     * @return	resource
     */
    public function db_set_charset($charset, $collation)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('3.0');

        return true;
    }

    /**
     * The name of the platform in use (mysql, mssql, etc...)
     *
     * @access	public
     * @return	string
     */
    public function platform()
    {
        return $this->dbdriver;
    }

    /**
     * Database Version Number.  Returns a string containing the
     * version of the database being used
     *
     * @access	public
     * @return	string
     */
    public function version()
    {
        if (false === ($sql = $this->_version())) {
            if ($this->db_debug) {
                return $this->display_error('db_unsupported_function');
            }

            return false;
        }

        return $this->query($sql)->row('ver');
    }

    /**
     * Execute the query
     *
     * Accepts an SQL string as input and returns a result object upon
     * successful execution of a "read" type query.  Returns boolean TRUE
     * upon successful execution of a "write" type query. Returns boolean
     * FALSE upon failure, and if the $db_debug variable is set to TRUE
     * will raise an error.
     *
     * @access	public
     * @param	string	An SQL query string
     * @param	array	An array of binding data
     * @return	mixed
     */
    public function query($sql, $binds = false, $return_object = true)
    {
        if ($sql == '') {
            if ($this->db_debug) {
                log_message('error', 'Invalid query: ' . $sql);

                return $this->display_error('db_invalid_query');
            }

            return false;
        }

        // Verify table prefix and replace if necessary
        if (($this->dbprefix != '' and $this->swap_pre != '') and ($this->dbprefix != $this->swap_pre)) {
            $sql = preg_replace("/(\W)" . $this->swap_pre . "(\S+?)/", "\${1}" . $this->dbprefix . "\${2}", $sql);
        }

        // Compile binds if needed
        if ($binds !== false) {
            $sql = $this->compile_binds($sql, $binds);
        }

        // Run the Query
        if (false === ($this->result_id = $this->simple_query($sql))) {
            // This will trigger a rollback if transactions are being used
            $this->_trans_status = false;

            if ($this->db_debug) {
                // grab the error number and message now, as we might run some
                // additional queries before displaying the error
                $error_no = $this->_error_number();
                $error_msg = $this->_error_message();

                // We call this function in order to roll-back queries
                // if transactions are enabled.  If we don't call this here
                // the error message will trigger an exit, causing the
                // transactions to remain in limbo.
                $this->trans_complete();

                // Log and display errors
                log_message('error', 'Query error: ' . $error_msg);

                return $this->display_error(array(
                    '<b>Error number</b>: ' . $error_no,
                    $error_msg,
                    htmlentities($sql, ENT_QUOTES, 'UTF-8')
                ));
            }

            return false;
        }

        // Increment the query counter
        $this->query_count++;

        // Was the query a "write" type?
        // If so we'll simply return true
        if ($this->is_write_type($sql) === true) {
            return true;
        }

        // Load and instantiate the result driver
        $class = $this->load_rdriver();
        $result = new $class($this->result_id);
        $result->num_rows = $result->num_rows();

        return $result;
    }

    /**
     * Load the result drivers
     *
     * @access	public
     * @return	string	the name of the result class
     */
    public function load_rdriver()
    {
        $this->dbdriver = 'mysqli';

        $driver = 'CI_DB_' . $this->dbdriver . '_result';

        if (! class_exists($driver)) {
            $path = BASEPATH;
            include_once($path . 'database/DB_result.php');
            include_once($path . 'database/drivers/' . $this->dbdriver . '/' . $this->dbdriver . '_result.php');
        }

        return $driver;
    }

    /**
     * Simple Query
     * This is a simplified version of the query() function.  Internally
     * we only use it when running transaction commands since they do
     * not require all the features of the main query() function.
     *
     * @access	public
     * @param	string	the sql query
     * @return	mixed
     */
    public function simple_query($sql)
    {
        if (! $this->connection->isOpen()) {
            $this->initialize();
        }

        $this->last_query = $sql;

        return $this->_execute($sql);
    }

    /**
     * Disable Transactions
     * This permits transactions to be disabled at run-time.
     *
     * @access	public
     * @return	void
     */
    public function trans_off()
    {
        $this->trans_enabled = false;
    }

    /**
     * Enable/disable Transaction Strict Mode
     * When strict mode is enabled, if you are running multiple groups of
     * transactions, if one group fails all groups will be rolled back.
     * If strict mode is disabled, each group is treated autonomously, meaning
     * a failure of one group will not affect any others
     *
     * @access	public
     * @return	void
     */
    public function trans_strict($mode = true)
    {
        $this->trans_strict = is_bool($mode) ? $mode : true;
    }

    /**
     * Start Transaction
     *
     * @access	public
     * @return	void
     */
    public function trans_start($test_mode = false)
    {
        if (! $this->trans_enabled) {
            return false;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->_trans_depth > 0) {
            $this->_trans_depth += 1;

            return;
        }

        $this->trans_begin($test_mode);
    }

    /**
     * Complete Transaction
     *
     * @access	public
     * @return	bool
     */
    public function trans_complete()
    {
        if (! $this->trans_enabled) {
            return false;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->_trans_depth > 1) {
            $this->_trans_depth -= 1;

            return true;
        }

        // The query() function will set this flag to FALSE in the event that a query failed
        if ($this->_trans_status === false) {
            $this->trans_rollback();

            // If we are NOT running in strict mode, we will reset
            // the _trans_status flag so that subsequent groups of transactions
            // will be permitted.
            if ($this->trans_strict === false) {
                $this->_trans_status = true;
            }

            log_message('debug', 'DB Transaction Failure');

            return false;
        }

        $this->trans_commit();

        return true;
    }

    /**
     * Lets you retrieve the transaction flag to determine if it has failed
     *
     * @access	public
     * @return	bool
     */
    public function trans_status()
    {
        return $this->_trans_status;
    }

    /**
     * Compile Bindings
     *
     * @access	public
     * @param	string	the sql statement
     * @param	array	an array of bind data
     * @return	string
     */
    public function compile_binds($sql, $binds)
    {
        if (strpos($sql, $this->bind_marker) === false) {
            return $sql;
        }

        if (! is_array($binds)) {
            $binds = array($binds);
        }

        // Get the sql segments around the bind markers
        $segments = explode($this->bind_marker, $sql);

        // The count of bind should be 1 less then the count of segments
        // If there are more bind arguments trim it down
        if (count($binds) >= count($segments)) {
            $binds = array_slice($binds, 0, count($segments) - 1);
        }

        // Construct the binded query
        $result = $segments[0];
        $i = 0;
        foreach ($binds as $bind) {
            $result .= $this->escape($bind);
            $result .= $segments[++$i];
        }

        return $result;
    }

    /**
     * Determines if a query is a "write" type.
     *
     * @access	public
     * @param	string	An SQL query string
     * @return	boolean
     */
    public function is_write_type($sql)
    {
        if (! preg_match('/^\s*"?(SET|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|TRUNCATE|LOAD DATA|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK)\s+/i', $sql)) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the aggregate query elapsed time
     *
     * @access	public
     * @param	integer	The number of decimal places
     * @return	integer
     */
    public function elapsed_time($decimals = 6)
    {
        return number_format($this->benchmark, $decimals);
    }

    /**
     * Returns the total number of queries
     *
     * @access	public
     * @return	integer
     */
    public function total_queries()
    {
        return $this->query_count;
    }

    /**
     * Returns the last query that was executed
     *
     * @access	public
     * @return	void
     */
    public function last_query()
    {
        return $this->last_query;
    }

    /**
     * "Smart" Escape String
     *
     * Escapes data based on type
     * Sets boolean and null types
     *
     * @access	public
     * @param	string
     * @return	mixed
     */
    public function escape($str)
    {
        if (is_string($str)) {
            $str = "'" . $this->escape_str($str) . "'";
        } elseif (is_bool($str)) {
            $str = ($str === false) ? 0 : 1;
        } elseif (is_null($str)) {
            $str = 'NULL';
        } elseif (! is_int($str) && ! is_float($str)) {
            $str = 'NULL';
        }

        return $str;
    }

    /**
     * Escape LIKE String
     *
     * Calls the individual driver for platform
     * specific escaping for LIKE conditions
     *
     * @access	public
     * @param	string
     * @return	mixed
     */
    public function escape_like_str($str)
    {
        return $this->escape_str($str, true);
    }

    /**
     * Primary
     *
     * Retrieves the primary key.  It assumes that the row in the first
     * position is the primary key
     *
     * @access	public
     * @param	string	the table name
     * @return	string
     */
    public function primary($table = '')
    {
        $fields = $this->list_fields($table);

        if (! is_array($fields)) {
            return false;
        }

        return current($fields);
    }

    /**
     * Returns an array of table names
     *
     * @access	public
     * @return	array
     */
    public function list_tables($constrain_by_prefix = false)
    {
        // Is there a cached result?
        if (isset($this->data_cache['table_names'])) {
            return $this->data_cache['table_names'];
        }

        if (false === ($sql = $this->_list_tables($constrain_by_prefix))) {
            if ($this->db_debug) {
                return $this->display_error('db_unsupported_function');
            }

            return false;
        }

        $retval = array();
        $query = $this->query($sql);

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                if (isset($row['TABLE_NAME'])) {
                    $retval[] = $row['TABLE_NAME'];
                } else {
                    $retval[] = array_shift($row);
                }
            }
        }

        $this->data_cache['table_names'] = $retval;

        return $this->data_cache['table_names'];
    }

    /**
     * Determine if a particular table exists
     * @access	public
     * @return	boolean
     */
    public function table_exists($table_name)
    {
        return (! in_array($this->_protect_identifiers($table_name, true, false, false), $this->list_tables())) ? false : true;
    }

    /**
     * Fetch MySQL Field Names
     *
     * @access	public
     * @param	string	the table name
     * @return	array
     */
    public function list_fields($table = '')
    {
        // Is there a cached result?
        if (isset($this->data_cache['field_names'][$table])) {
            return $this->data_cache['field_names'][$table];
        }

        if ($table == '') {
            if ($this->db_debug) {
                return $this->display_error('db_field_param_missing');
            }

            return false;
        }

        if (false === ($sql = $this->_list_columns($table))) {
            if ($this->db_debug) {
                return $this->display_error('db_unsupported_function');
            }

            return false;
        }

        $query = $this->query($sql);

        $retval = array();
        foreach ($query->result_array() as $row) {
            if (isset($row['COLUMN_NAME'])) {
                $retval[] = $row['COLUMN_NAME'];
            } else {
                $retval[] = current($row);
            }
        }

        $this->data_cache['field_names'][$table] = $retval;

        return $this->data_cache['field_names'][$table];
    }

    /**
     * Determine if a particular field exists
     * @access	public
     * @param	string
     * @param	string
     * @return	boolean
     */
    public function field_exists($field_name, $table_name)
    {
        return (! in_array($field_name, $this->list_fields($table_name))) ? false : true;
    }

    /**
     * Returns an object with field data
     *
     * @access	public
     * @param	string	the table name
     * @return	object
     */
    public function field_data($table = '')
    {
        if ($table == '') {
            if ($this->db_debug) {
                return $this->display_error('db_field_param_missing');
            }

            return false;
        }

        $query = $this->query($this->_field_data($this->_protect_identifiers($table, true, null, false)));

        return $query->field_data();
    }

    /**
     * Generate an insert string
     *
     * @access	public
     * @param	string	the table upon which the query will be performed
     * @param	array	an associative array data of key/values
     * @return	string
     */
    public function insert_string($table, $data)
    {
        $fields = array();
        $values = array();

        foreach ($data as $key => $val) {
            $fields[] = $this->escape_identifiers($key);
            $values[] = $this->escape($val);
        }

        return $this->_insert($this->_protect_identifiers($table, true, null, false), $fields, $values);
    }

    /**
     * Insert statement
     *
     * Generates a platform-specific insert string from the supplied data
     *
     * @param	string	the table name
     * @param	array	the insert keys
     * @param	array	the insert values
     * @return	string
     */
    protected function _insert($table, $keys, $values)
    {
        return 'INSERT INTO ' . $table . ' (' . implode(', ', $keys) . ') VALUES (' . implode(', ', $values) . ')';
    }

    /**
     * Generate an update string
     *
     * @access	public
     * @param	string	the table upon which the query will be performed
     * @param	array	an associative array data of key/values
     * @param	mixed	the "where" statement
     * @return	string
     */
    public function update_string($table, $data, $where)
    {
        if ($where == '') {
            return false;
        }

        $fields = array();
        foreach ($data as $key => $val) {
            $fields[$this->_protect_identifiers($key)] = $this->escape($val);
        }

        if (! is_array($where)) {
            $dest = array($where);
        } else {
            $dest = array();
            foreach ($where as $key => $val) {
                $prefix = (count($dest) == 0) ? '' : ' AND ';

                if ($val !== '') {
                    if (! $this->_has_operator($key)) {
                        $key .= ' =';
                    }

                    $val = ' ' . $this->escape($val);
                }

                $dest[] = $prefix . $key . $val;
            }
        }

        return $this->_update($this->_protect_identifiers($table, true, null, false), $fields, $dest);
    }

    /**
     * Update statement
     *
     * Generates a platform-specific update string from the supplied data
     *
     * @param	string	the table name
     * @param	array	the update data
     * @return	string
     */
    protected function _update($table, $values, $where, $orderby = array(), $limit = false)
    {
        foreach ($values as $key => $val) {
            $valstr[] = $key . ' = ' . $val;
        }

        return 'UPDATE ' . $table . ' SET ' . implode(', ', $valstr)
            . $this->_compile_wh('ar_where')
            . $this->_compile_order_by()
            . ($this->ar_limit ? ' LIMIT ' . $this->ar_limit : '');
    }

    /**
     * Tests whether the string has an SQL operator
     *
     * @access	private
     * @param	string
     * @return	bool
     */
    public function _has_operator($str)
    {
        $str = trim($str);
        if (! preg_match("/(\s|<|>|!|=|is null|is not null)/i", $str)) {
            return false;
        }

        return true;
    }

    /**
     * Enables a native PHP function to be run, using a platform agnostic wrapper.
     *
     * @access	public
     * @param	string	the function name
     * @param	mixed	any parameters needed by the function
     * @return	mixed
     */
    public function call_function($function)
    {
        $driver = $this->dbdriver . '_';

        if (false === strpos($driver, $function)) {
            $function = $driver . $function;
        }

        if (! function_exists($function)) {
            if ($this->db_debug) {
                return $this->display_error('db_unsupported_function');
            }

            return false;
        } else {
            $args = (func_num_args() > 1) ? array_splice(func_get_args(), 1) : null;

            return call_user_func_array($function, $args);
        }
    }

    /**
     * Enable Query Caching
     *
     * @access	public
     * @return	void
     */
    public function cache_on()
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('3.0');

        return true;
    }

    /**
     * Disable Query Caching
     *
     * @access	public
     * @return	void
     */
    public function cache_off()
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('3.0');

        return false;
    }

    /**
     * Close DB Connection
     *
     * @access	public
     * @return	void
     */
    public function close()
    {
        $this->connection->close();
    }

    /**
     * Display an error message
     *
     * @access	public
     * @param	string	the error message
     * @param	string	any "swap" values
     * @param	boolean	whether to localize the message
     * @return	string	sends the application/error_db.php template
     */
    public function display_error($error = '', $swap = '', $native = false)
    {
        // we can get db errors before the session is loaded, but we
        // need the session for language keys in EE. So skip the language
        // game if session doesn't exist yet.
        if (function_exists('ee') && isset(ee()->session)) {
            $LANG = load_class('Lang', 'core');
            $LANG->load('db');

            $heading = $LANG->line('db_error_heading');
        } else {
            $native = true;
            $heading = 'Database Error';
        }

        if ($native == true) {
            $message = $error;
        } else {
            $message = (! is_array($error)) ? array(str_replace('%s', $swap, $LANG->line($error))) : $error;
        }

        // Find the most likely culprit of the error by going through
        // the backtrace until the source file is no longer in the
        // database folder.

        $trace = debug_backtrace();

        foreach ($trace as $call) {
            if (isset($call['file']) && strpos($call['file'], APPPATH . 'database') === false) {
                // Found it - use a relative path for safety
                $message[] = '<b>File location</b>: ' . str_replace(array(BASEPATH, APPPATH), '', $call['file']);
                $message[] = '<b>Line number</b>: ' . $call['line'];

                break;
            }
        }

        // Optional exception handling for DB errors
        if ($this->db_exception) {
            // Append error code to end for display preferences
            $error_code = array_shift($message);
            $message[] = $error_code;

            throw new Exception(implode('<br>', $message));
        }

        $error = load_class('Exceptions', 'core');
        echo $error->show_error($heading, $message);
        exit;
    }

    /**
     * Protect Identifiers
     *
     * This function adds backticks if appropriate based on db type
     *
     * @access	private
     * @param	mixed	the item to escape
     * @return	mixed	the item with backticks
     */
    public function protect_identifiers($item, $prefix_single = false)
    {
        return $this->_protect_identifiers($item, $prefix_single);
    }

    /**
     * Protect Identifiers
     *
     * This function is used extensively by the Active Record class, and by
     * a couple functions in this class.
     * It takes a column or table name (optionally with an alias) and inserts
     * the table prefix onto it.  Some logic is necessary in order to deal with
     * column names that include the path.  Consider a query like this:
     *
     * SELECT * FROM hostname.database.table.column AS c FROM hostname.database.table
     *
     * Or a query with aliasing:
     *
     * SELECT m.member_id, m.member_name FROM members AS m
     *
     * Since the column name can include up to four segments (host, DB, table, column)
     * or also have an alias prefix, we need to do a bit of work to figure this out and
     * insert the table prefix (if it exists) in the proper position, and escape only
     * the correct identifiers.
     *
     * @access	private
     * @param	string
     * @param	bool
     * @param	mixed
     * @param	bool
     * @return	string
     */
    public function _protect_identifiers($item, $prefix_single = false, $protect_identifiers = null, $field_exists = true)
    {
        if (! is_bool($protect_identifiers)) {
            $protect_identifiers = $this->_protect_identifiers;
        }

        if (is_array($item)) {
            $escaped_array = array();

            foreach ($item as $k => $v) {
                $escaped_array[$this->_protect_identifiers($k)] = $this->_protect_identifiers($v);
            }

            return $escaped_array;
        }

        // Convert tabs or multiple spaces into single spaces
        $item = preg_replace('/[\t ]+/', ' ', $item);

        // If the item has an alias declaration we remove it and set it aside.
        // Basically we remove everything to the right of the first space
        $alias = '';
        if (strpos($item, ' ') !== false) {
            $alias = strstr($item, " ");
            $item = substr($item, 0, - strlen($alias));
        }

        // This is basically a bug fix for queries that use MAX, MIN, etc.
        // If a parenthesis is found we know that we do not need to
        // escape the data or add a prefix.  There's probably a more graceful
        // way to deal with this, but I'm not thinking of it -- Rick
        if (strpos($item, '(') !== false) {
            return $item . $alias;
        }

        // Break the string apart if it contains periods, then insert the table prefix
        // in the correct location, assuming the period doesn't indicate that we're dealing
        // with an alias. While we're at it, we will escape the components
        if (strpos($item, '.') !== false) {
            $parts = explode('.', $item);

            // Does the first segment of the exploded item match
            // one of the aliases previously identified?  If so,
            // we have nothing more to do other than escape the item
            if (in_array($parts[0], $this->ar_aliased_tables)) {
                if ($protect_identifiers === true) {
                    foreach ($parts as $key => $val) {
                        if (! in_array($val, $this->_reserved_identifiers)) {
                            $parts[$key] = $this->escape_identifiers($val);
                        }
                    }

                    $item = implode('.', $parts);
                }

                return $item . $alias;
            }

            // Is there a table prefix defined in the config file?  If not, no need to do anything
            if ($this->dbprefix != '') {
                // We now add the table prefix based on some logic.
                // Do we have 4 segments (hostname.database.table.column)?
                // If so, we add the table prefix to the column name in the 3rd segment.
                if (isset($parts[3])) {
                    $i = 2;
                }
                // Do we have 3 segments (database.table.column)?
                // If so, we add the table prefix to the column name in 2nd position
                elseif (isset($parts[2])) {
                    $i = 1;
                }
                // Do we have 2 segments (table.column)?
                // If so, we add the table prefix to the column name in 1st segment
                else {
                    $i = 0;
                }

                // This flag is set when the supplied $item does not contain a field name.
                // This can happen when this function is being called from a JOIN.
                if ($field_exists == false) {
                    $i++;
                }

                // Verify table prefix and replace if necessary
                if ($this->swap_pre != '' && strncmp($parts[$i], $this->swap_pre, strlen($this->swap_pre)) === 0) {
                    $parts[$i] = preg_replace("/^" . $this->swap_pre . "(\S+?)/", $this->dbprefix . "\\1", $parts[$i]);
                }

                // We only add the table prefix if it does not already exist
                if (substr($parts[$i], 0, strlen($this->dbprefix)) != $this->dbprefix) {
                    $parts[$i] = $this->dbprefix . $parts[$i];
                }

                // Put the parts back together
                $item = implode('.', $parts);
            }

            if ($protect_identifiers === true) {
                $item = $this->escape_identifiers($item);
            }

            return $item . $alias;
        }

        // Is there a table prefix?  If not, no need to insert it
        if ($this->dbprefix != '') {
            // Verify table prefix and replace if necessary
            if ($this->swap_pre != '' && strncmp($item, $this->swap_pre, strlen($this->swap_pre)) === 0) {
                $item = preg_replace("/^" . $this->swap_pre . "(\S+?)/", $this->dbprefix . "\\1", $item);
            }

            // Do we prefix an item with no segments?
            if ($prefix_single == true and substr($item, 0, strlen($this->dbprefix)) != $this->dbprefix) {
                $item = $this->dbprefix . $item;
            }
        }

        if ($protect_identifiers === true and ! in_array($item, $this->_reserved_identifiers)) {
            $item = $this->escape_identifiers($item);
        }

        return $item . $alias;
    }

    /**
     * Escape the SQL Identifiers
     *
     * This function escapes column and table names
     *
     * @param	mixed
     * @return	mixed
     */
    public function escape_identifiers($item)
    {
        if ($this->_escape_char === '' or empty($item) or in_array($item, $this->_reserved_identifiers)) {
            return $item;
        } elseif (is_array($item)) {
            foreach ($item as $key => $value) {
                $item[$key] = $this->escape_identifiers($value);
            }

            return $item;
        }
        // Avoid breaking functions and literal values inside queries
        elseif (ctype_digit($item) or $item[0] === "'" or ($this->_escape_char !== '"' && $item[0] === '"') or strpos($item, '(') !== false) {
            return $item;
        }

        static $preg_ec = array();

        if (empty($preg_ec)) {
            if (is_array($this->_escape_char)) {
                $preg_ec = array(
                    preg_quote($this->_escape_char[0], '/'),
                    preg_quote($this->_escape_char[1], '/'),
                    $this->_escape_char[0],
                    $this->_escape_char[1]
                );
            } else {
                $preg_ec[0] = $preg_ec[1] = preg_quote($this->_escape_char, '/');
                $preg_ec[2] = $preg_ec[3] = $this->_escape_char;
            }
        }

        foreach ($this->_reserved_identifiers as $id) {
            if (strpos($item, '.' . $id) !== false) {
                return preg_replace('/' . $preg_ec[0] . '?([^' . $preg_ec[1] . '\.]+)' . $preg_ec[1] . '?\./i', $preg_ec[2] . '$1' . $preg_ec[3] . '.', $item);
            }
        }

        return preg_replace('/' . $preg_ec[0] . '?([^' . $preg_ec[1] . '\.]+)' . $preg_ec[1] . '?(\.)?/i', $preg_ec[2] . '$1' . $preg_ec[3] . '$2', $item);
    }
}

// EOF
