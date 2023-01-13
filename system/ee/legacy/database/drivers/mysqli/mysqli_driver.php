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
 * MySQLi Database Driver
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the active record
 * class is being used or not.
 */
class CI_DB_mysqli_driver extends CI_DB
{
    public $dbdriver = 'mysqli';

    // The character used for escaping
    public $_escape_char = '`';

    // clause and character used for LIKE escape sequences - not used in MySQL
    public $_like_escape_str = '';
    public $_like_escape_chr = '';

    /**
     * The syntax to count rows is slightly different across different
     * database engines, so this string appears in each driver and is
     * used for the count_all() and count_all_results() functions.
     */
    public $_count_string = "SELECT COUNT(*) AS ";
    public $_random_keyword = ' RAND()'; // database specific random keyword

    /**
     * Whether to use the MySQL "delete hack" which allows the number
     * of affected rows to be shown. Uses a preg_replace when enabled,
     * adding a bit more processing to all queries.
     */
    public $delete_hack = true;

    // whether SET NAMES must be used to set the character set
    public $use_set_names;

    /**
     * Non-persistent database connection
     *
     * @return	resource
     */
    public function db_connect()
    {
        throw new \Exception('Manual driver connections were removed. Please let us know if you were using them.');
    }

    /**
     * Persistent database connection
     *
     * @return	resource
     */
    public function db_pconnect()
    {
        throw new \Exception('Manual driver connections were removed. Please let us know if you were using them.');
    }

    /**
     * Reconnect
     *
     * Keep / reestablish the db connection if no queries have been
     * sent for a length of time exceeding the server's idle timeout
     *
     * @access	public
     * @return	void
     */
    public function reconnect()
    {
        throw new \Exception('Reconnecting was removed. Please let us know if you were using it.');
    }

    /**
     * Select the database
     *
     * @return	resource
     */
    public function db_select()
    {
        throw new \Exception('DB selecting was removed. Please let us know if you were using it.');
    }

    /**
     * Version number query string
     *
     * @access	public
     * @return	string
     */
    public function _version()
    {
        return "SELECT version() AS ver";
    }

    /**
     * Execute the query
     *
     * @access	private called by the base class
     * @param	string	an SQL query
     * @return	resource
     */
    public function _execute($sql)
    {
        $sql = $this->_prep_query($sql);
        $query = $this->connection->query($sql);

        $this->last_query = $query;

        return $query;
    }

    /**
     * Prep the query
     *
     * If needed, each database adapter can prep the query string
     *
     * @access	private called by execute()
     * @param	string	an SQL query
     * @return	string
     */
    public function _prep_query($sql)
    {
        // "DELETE FROM TABLE" returns 0 affected rows This hack modifies
        // the query so that it returns the number of affected rows
        if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $sql)) {
            $sql = preg_replace("/^\s*DELETE\s+FROM\s+(\S+)\s*$/", "DELETE FROM \\1 WHERE 1=1", $sql);
        }

        return $sql;
    }

    /**
     * Begin Transaction
     *
     * @access	public
     * @return	bool
     */
    public function trans_begin($test_mode = false)
    {
        if (! $this->trans_enabled) {
            return true;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->_trans_depth > 0) {
            return true;
        }

        // Reset the transaction failure flag.
        // If the $test_mode flag is set to TRUE transactions will be rolled back
        // even if the queries produce a successful result.
        $this->_trans_failure = ($test_mode === true) ? true : false;

        $this->simple_query('SET AUTOCOMMIT=0');
        $this->simple_query('START TRANSACTION'); // can also be BEGIN or BEGIN WORK

        return true;
    }

    /**
     * Commit Transaction
     *
     * @access	public
     * @return	bool
     */
    public function trans_commit()
    {
        if (! $this->trans_enabled) {
            return true;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->_trans_depth > 0) {
            return true;
        }

        $this->simple_query('COMMIT');
        $this->simple_query('SET AUTOCOMMIT=1');

        return true;
    }

    /**
     * Rollback Transaction
     *
     * @access	public
     * @return	bool
     */
    public function trans_rollback()
    {
        if (! $this->trans_enabled) {
            return true;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->_trans_depth > 0) {
            return true;
        }

        $this->simple_query('ROLLBACK');
        $this->simple_query('SET AUTOCOMMIT=1');

        return true;
    }

    /**
     * Escape String
     *
     * @access	public
     * @param	string
     * @param	bool	whether or not the string will be used in a LIKE condition
     * @return	string
     */
    public function escape_str($str, $like = false)
    {
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$key] = $this->escape_str($val, $like);
            }

            return $str;
        }

        $str = $this->connection->escape($str);

        // escape LIKE condition wildcards
        if ($like === true) {
            $str = str_replace(array('%', '_'), array('\\%', '\\_'), $str);
        }

        return $str;
    }

    /**
     * Affected Rows
     *
     * @access	public
     * @return	integer
     */
    public function affected_rows()
    {
        if (! isset($this->last_query)) {
            return 0;
        }

        return $this->last_query->rowCount();
    }

    /**
     * Insert ID
     *
     * @access	public
     * @return	integer
     */
    public function insert_id()
    {
        return $this->connection->getInsertId();
    }

    /**
     * "Count All" query
     *
     * Generates a platform-specific query string that counts all records in
     * the specified database
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function count_all($table)
    {
        if (empty($table)) {
            return 0;
        }

        $query = $this->query($this->_count_string . $this->_protect_identifiers('numrows') . " FROM " . $this->_protect_identifiers($table, true, null, false));

        if ($query->num_rows() == 0) {
            return 0;
        }

        $row = $query->row();

        return (int) $row->numrows;
    }

    /**
     * List table query
     *
     * Generates a platform-specific query string so that the table names can be fetched
     *
     * @access	private
     * @param	boolean
     * @return	string
     */
    public function _list_tables($prefix_limit = false)
    {
        $sql = "SHOW TABLES FROM " . $this->_escape_char . $this->database . $this->_escape_char;

        if ($prefix_limit !== false and $this->dbprefix != '') {
            $sql .= " LIKE '" . $this->escape_like_str($this->dbprefix) . "%'";
        }

        return $sql;
    }

    /**
     * Show column query
     *
     * Generates a platform-specific query string so that the column names can be fetched
     *
     * @access	public
     * @param	string	the table name
     * @return	string
     */
    public function _list_columns($table = '')
    {
        return "SHOW COLUMNS FROM " . $this->_protect_identifiers($table, true, null, false);
    }

    /**
     * Field data query
     *
     * Generates a platform-specific query so that the column data can be retrieved
     *
     * @access	public
     * @param	string	the table name
     * @return	object
     */
    public function _field_data($table)
    {
        return "SELECT * FROM " . $table . " LIMIT 1";
    }

    /**
     * The error message string
     *
     * @access	private
     * @return	string
     */
    public function _error_message()
    {
        return $this->connection->getErrorMessage();
    }

    /**
     * The error message number
     *
     * @access	private
     * @return	integer
     */
    public function _error_number()
    {
        return $this->connection->getErrorNumber();
    }

    /**
     * Escape the SQL Identifiers
     *
     * This function escapes column and table names
     *
     * @access	private
     * @param	string
     * @return	string
     */
    public function escape_identifiers($item)
    {
        if ($this->_escape_char == '') {
            return $item;
        }

        foreach ($this->_reserved_identifiers as $id) {
            if (strpos($item, '.' . $id) !== false) {
                $str = $this->_escape_char . str_replace('.', $this->_escape_char . '.', $item);

                // remove duplicates if the user already included the escape
                return preg_replace('/[' . $this->_escape_char . ']+/', $this->_escape_char, $str);
            }
        }

        if (strpos($item, '.') !== false) {
            $str = $this->_escape_char . str_replace('.', $this->_escape_char . '.' . $this->_escape_char, $item) . $this->_escape_char;
        } else {
            $str = $this->_escape_char . $item . $this->_escape_char;
        }

        // remove duplicates if the user already included the escape
        return preg_replace('/[' . $this->_escape_char . ']+/', $this->_escape_char, $str);
    }

    /**
     * From Tables
     *
     * This function implicitly groups FROM tables so there is no confusion
     * about operator precedence in harmony with SQL standards
     *
     * @access	public
     * @param	type
     * @return	type
     */
    public function _from_tables($tables)
    {
        if (! is_array($tables)) {
            $tables = array($tables);
        }

        return '(' . implode(', ', $tables) . ')';
    }

    /**
     * Insert statement
     *
     * Generates a platform-specific insert string from the supplied data
     *
     * @access	public
     * @param	string	the table name
     * @param	array	the insert keys
     * @param	array	the insert values
     * @return	string
     */
    public function _insert($table, $keys, $values)
    {
        return "INSERT INTO " . $table . " (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ")";
    }

    /**
     * Replace statement
     *
     * Generates a platform-specific replace string from the supplied data
     *
     * @access	public
     * @param	string	the table name
     * @param	array	the insert keys
     * @param	array	the insert values
     * @return	string
     */
    public function _replace($table, $keys, $values)
    {
        return "REPLACE INTO " . $table . " (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ")";
    }

    /**
     * Insert_batch statement
     *
     * Generates a platform-specific insert string from the supplied data
     *
     * @access	public
     * @param	string	the table name
     * @param	array	the insert keys
     * @param	array	the insert values
     * @return	string
     */
    public function _insert_batch($table, $keys, $values)
    {
        return "INSERT INTO " . $table . " (" . implode(', ', $keys) . ") VALUES " . implode(', ', $values);
    }

    /**
     * Update statement
     *
     * Generates a platform-specific update string from the supplied data
     *
     * @access	public
     * @param	string	the table name
     * @param	array	the update data
     * @param	array	the where clause
     * @param	array	the orderby clause
     * @param	array	the limit clause
     * @return	string
     */
    public function _update($table, $values, $where, $orderby = array(), $limit = false)
    {
        foreach ($values as $key => $val) {
            $valstr[] = $key . " = " . $val;
        }

        $limit = (! $limit) ? '' : ' LIMIT ' . $limit;

        $orderby = (count($orderby) >= 1) ? ' ORDER BY ' . implode(", ", $orderby) : '';

        $sql = "UPDATE " . $table . " SET " . implode(', ', $valstr);

        $sql .= ($where != '' and count($where) >= 1) ? " WHERE " . implode(" ", $where) : '';

        $sql .= $orderby . $limit;

        return $sql;
    }

    /**
     * Update_Batch statement
     *
     * Generates a platform-specific batch update string from the supplied data
     *
     * @access	public
     * @param	string	the table name
     * @param	array	the update data
     * @param	array	the where clause
     * @return	string
     */
    public function _update_batch($table, $values, $index, $where = null)
    {
        $ids = array();
        $where = ($where != '' and count($where) >= 1) ? implode(" ", $where) . ' AND ' : '';

        foreach ($values as $key => $val) {
            $ids[] = $val[$index];

            foreach (array_keys($val) as $field) {
                if ($field != $index) {
                    $final[$field][] = 'WHEN ' . $index . ' = ' . $val[$index] . ' THEN ' . $val[$field];
                }
            }
        }

        $sql = "UPDATE " . $table . " SET ";
        $cases = '';

        foreach ($final as $k => $v) {
            $cases .= $k . ' = CASE ' . "\n";
            foreach ($v as $row) {
                $cases .= $row . "\n";
            }

            $cases .= 'ELSE ' . $k . ' END, ';
        }

        $sql .= substr($cases, 0, -2);

        $sql .= ' WHERE ' . $where . $index . ' IN (' . implode(',', $ids) . ')';

        return $sql;
    }

    /**
     * Truncate statement
     *
     * Generates a platform-specific truncate string from the supplied data
     * If the database does not support the truncate() command
     * This function maps to "DELETE FROM table"
     *
     * @access	public
     * @param	string	the table name
     * @return	string
     */
    public function _truncate($table)
    {
        return "TRUNCATE " . $table;
    }

    /**
     * Delete statement
     *
     * Generates a platform-specific delete string from the supplied data
     *
     * @access	public
     * @param	string	the table name
     * @param	array	the where clause
     * @param	string	the limit clause
     * @return	string
     */
    public function _delete($table, $where = array(), $like = array(), $limit = false)
    {
        $conditions = '';

        if (count($where) > 0 or count($like) > 0) {
            $conditions = "\nWHERE ";
            $conditions .= implode("\n", $this->ar_where);

            if (count($where) > 0 && count($like) > 0) {
                $conditions .= " AND ";
            }
            $conditions .= implode("\n", $like);
        }

        $limit = (! $limit) ? '' : ' LIMIT ' . $limit;

        return "DELETE FROM " . $table . $conditions . $limit;
    }

    /**
     * Limit string
     *
     * Generates a platform-specific LIMIT clause
     *
     * @access	public
     * @param	string	the sql query string
     * @param	integer	the number of rows to limit the query to
     * @param	integer	the offset value
     * @return	string
     */
    public function _limit($sql, $limit, $offset)
    {
        $sql .= "LIMIT " . $limit;

        if ($offset > 0) {
            $sql .= " OFFSET " . $offset;
        }

        return $sql;
    }

    /**
     * Close DB Connection
     *
     * @access	public
     * @param	resource
     * @return	void
     */
    public function _close()
    {
        $this->connection->close();
    }
}

// EOF
