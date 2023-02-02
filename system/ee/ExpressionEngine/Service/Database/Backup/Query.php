<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Database\Backup;

use Exception;

/**
 * Supporting query class for database backup
 *
 * Selects and formats data to be inserted into an SQL file
 */
class Query
{
    const BINARY_TYPE = 1;
    const STRING_TYPE = 2;
    const NUMBER_TYPE = 3;

    const TABLE_STRUCTURE = 1;
    const VIEW_STRUCTURE = 2;

    /**
     * @var Database\Query Database Query object
     */
    protected $query;

    /**
     * @var boolean When TRUE, class returns queries with no linebreaks
     */
    protected $compact_queries = false;

    /**
     * @var array Cache array of tables and their corresponding row count estimates and size on disk
     */
    protected $tables = [];

    /**
     * @var array Cache array of tables and their DESCRIBE output
     */
    protected $table_descriptions = [];

    /**
     * @var int Number of bytes to limit INSERT query sizes to
     */
    protected $query_size_limit = 3e+6;

    /**
     * @var array array of columns
     */
    public $columns;

    /**
     * Constructor
     *
     * @param	Database\Query	$query	Database query object
     */
    public function __construct(\ExpressionEngine\Service\Database\Query $query)
    {
        $query->dbprefix = '';
        $this->query = $query;
    }

    /**
     * Makes the class return pretty queries with helpful whitespace formatting
     */
    public function makePrettyQueries()
    {
        $this->compact_queries = false;
    }

    /**
     * Makes the class return queries that have no linebreaks in them
     */
    public function makeCompactQueries()
    {
        $this->compact_queries = true;
    }

    /**
     * Sets the byte limit for INSERT query sizes
     *
     * @param	int	$limit	Number of bytes
     */
    public function setQuerySizeLimit($limit)
    {
        $this->query_size_limit = $limit;
    }

    /**
     * Returns an array of names of tables present in the database
     *
     * @return	array	Associative array of tables to row count and size on disk, e.g.:
     *	[
     *		'table' => [
     *			'rows' => 123,
     *			'size' => 123456,
     *          'type' => self::TABLE_STRUCTURE,
     *		],
     *		...
     *	]
     *	NOTE: The row count may be inaccurate for large InnoDB tables, do not
     *	rely on it for precision work
     */
    public function getTables()
    {
        if (empty($this->tables)) {
            $query = $this->query
                ->query(sprintf('SHOW TABLE STATUS FROM `%s`', $this->query->database));

            foreach ($query->result() as $row) {
                if ($row->Comment === 'VIEW') {
                    $type = self::VIEW_STRUCTURE;
                } else {
                    $type = self::TABLE_STRUCTURE;
                }

                $this->tables[$row->Name] = [
                    'rows' => $row->Rows,
                    'size' => $row->Data_length,
                    'type' => $type
                ];
            }
        }

        return $this->tables;
    }

    /**
     * Get the current Database/Query object's character set
     *
     * @return string database connection char_set
     */
    public function getCharset()
    {
        return $this->query->char_set;
    }

    /**
     * Given a table name, generates a CREATE TABLE statement for it
     *
     * @param	string	$table_name	Table name
     * @return	string	CREATE TABLE statement for the given table
     */
    public function getCreateForTable($table_name)
    {
        $create_result = $this->query
            ->query(sprintf('SHOW CREATE TABLE `%s`;', $table_name))
            ->row_array();

        if (! isset($create_result['Create Table'])) {
            throw new Exception('Could not generate CREATE TABLE statement for table ' . $table_name, 1);
        }

        $create = $create_result['Create Table'] . ';';

        if ($this->compact_queries) {
            $create = str_replace("\n", '', $create);
        }

        return $create;
    }

    /**
     * Given a view name, generates a CREATE VIEW statement for it
     *
     * @param   string  $view_name
     * @return  string  CREATE VIEW statement for the given view
     */
    public function getCreateForView($view_name)
    {
        $create_result = $this->query
            ->query(sprintf('SHOW CREATE VIEW `%s`;', $view_name))
            ->row_array();

        if (! isset($create_result['Create View'])) {
            throw new Exception('Could not generate CREATE VIEW statement for view ' . $view_name, 2);
        }

        $create = $create_result['Create View'] . ';';

        if ($this->compact_queries) {
            $create = str_replace("\n", '', $create);
        }

        return $create;
    }

    /**
     * Given a table name, generates a DROP TABLE IF EXISTS statement for it
     *
     * @param	string	$table_name	Table name
     * @return	string	DROP TABLE IF EXISTS statement for the given table
     */
    public function getDropStatement($table_name)
    {
        return sprintf('DROP TABLE IF EXISTS `%s`;', $table_name);
    }

    /**
     * Queries for data given a table name and offset parameters, and generates
     * an array of values for each row to follow a VALUES statement
     *
     * @param	string	$table_name		Table name
     * @param	int		$offset			Query offset
     * @param	int		$limit			Query limit
     * @return	array	Array containing ull, valid INSERT INTO statement for a given
     * range of table data, and also the number of rows that were exported, e.g.:
     *	[
     *		'insert_string' => 'INSERT INTO `table_name` VALUES ... ;',
     *		'rows_exported' => 50
     *	]
     */
    public function getInsertsForTable($table_name, $offset, $limit)
    {
        $data = $this->getTableDescription($table_name);

        // Surround fields with backticks
        $fields = array_map(function ($row) {
            return sprintf('`%s`', $row['Field']);
        }, $data);

        $insert_prepend = sprintf('INSERT INTO `%s` (%s) VALUES ', $table_name, implode(', ', $fields));

        $rows = $this->getValuesForTable($table_name, $offset, $limit);

        if (empty($rows)) {
            return null;
        }

        $row_chunks = $this->makeRowChunks($rows);

        $inserts = '';
        foreach ($row_chunks as $row_chunk) {
            if ($this->compact_queries) {
                $inserts .= $insert_prepend . implode(', ', $row_chunk);
            } else {
                $inserts .= $insert_prepend . "\n\t" . implode(",\n\t", $row_chunk);
            }

            $inserts .= ";\n";
        }

        return [
            'insert_string' => trim($inserts),
            'rows_exported' => count($rows)
        ];
    }

    /**
     * Runs a DESCRIBE query for a given table and returns it
     *
     * @param	string	$table_name	Table name
     * @return	array	Output of DESCRIBE query
     *	[
     *		[
     *			'Field' => 'column1',
     *			'Type' => 'int',
     *			...
     *		],
     *		...
     *	]
     */
    protected function getTableDescription($table_name)
    {
        if (! isset($this->table_descriptions[$table_name])) {
            $this->table_descriptions[$table_name] = $this->query
                ->query(sprintf('DESCRIBE `%s`;', $table_name))
                ->result_array();
        }

        return $this->table_descriptions[$table_name];
    }

    /**
     * We need to balance keeping our INSERT query numbers small (for smaller
     * file sizes and potentially faster imports) while also making sure a
     * single query doesn't get too long, so here we'll break up a given array
     * of rows into chunks that can likely be placed into a single query.
     * MySQL's max_allowed_packet defaults to 4MB, but we'll shoot for under 3MB
     * to be safe.
     *
     * @param	array	$rows	Rows of data pre-formatted for a VALUES string
     * @return	array	Array of groups of rows
     *	[
     *		[ // One query's values
     *			"(1, NULL, 'some value')",
     *			"(2, NULL, 'another value')"
     *		],
     *		[ // Another query's values
     *			"(3, NULL, 'some value')",
     *			"(4, NULL, 'another value')"
     *		],
     *		...
     *	]
     */
    protected function makeRowChunks($rows)
    {
        $row_chunks = [];
        $byte_count = 0;
        $current_chunk = 0;

        foreach ($rows as $row) {
            // We'll assume that each character is roughly a byte
            $row_length = strlen($row) + 2;

            // We check for empty because even if the given row is too large
            // too fit in a query by itself, we have to export it anyway
            if (empty($row_chunks[$current_chunk]) or
                $row_length + $byte_count < $this->query_size_limit) {
                $byte_count += $row_length;
            }
            // Reset the byte count for a new chunk and start a new chunk
            else {
                $current_chunk++;
                $byte_count = $row_length;
            }

            $row_chunks[$current_chunk][] = $row;
        }

        return $row_chunks;
    }

    /**
     * Gets values for a table formatted for a VALUES string
     *
     * @param	string	$table_name		Table name
     * @param	int		$offset			Query offset
     * @param	int		$limit			Query limit
     * @return	array	Array of groups of values to follow an INSERT INTO ... VALUES statement, e.g.
     *	[
     *		'(1, NULL, 'some value')',
     *		'(2, NULL, 'another value')'
     *	]
     */
    protected function getValuesForTable($table_name, $offset, $limit)
    {
        $data = $this->query
            ->offset($offset)
            ->limit($limit)
            ->get($table_name)
            ->result_array();

        $values = [];
        foreach ($data as $row) {
            // Faster than array_map
            foreach ($row as $column_name => &$value) {
                $value = $this->formatValue(
                    $value,
                    $this->getColumnType($table_name, $column_name)
                );
            }

            $values[] = sprintf('(%s)', implode(', ', $row));
        }

        return $values;
    }

    /**
     * Formats a given database value for use in a VALUES string
     *
     * @param	mixed	$value		Database column value
     * @param	boolean	$is_binary	Whether or not the data is binary
     * @return	mixed	Typically either a string or number, but formatted for
     *                  a VALUES string
     */
    protected function formatValue($value, $column_type)
    {
        if (is_null($value)) {
            return 'NULL';
        }

        switch ($column_type) {
            case self::BINARY_TYPE:
                $hex = '';
                foreach (str_split($value) as $char) {
                    $hex .= str_pad(dechex(ord($char)), 2, '0', STR_PAD_LEFT);
                }

                return sprintf("x'%s'", $hex);

            case self::NUMBER_TYPE:
                return $value;

            default:
                return sprintf("'%s'", $this->query->escape_str($value));
        }
    }

    /**
     * Returns a data type for a given column
     *
     * @param	string	$table_name		Table name
     * @param	string	$column_name	Column name
     * @return	const	Constant to define data type
     */
    protected function getColumnType($table_name, $column_name)
    {
        $types = $this->getTypesForTable($table_name);

        if (! isset($types[$column_name])) {
            throw new \Exception('Non-existant column requested: ' . $table_name . '.' . $column_name, 1);
        }

        return $types[$column_name];
    }

    /**
     * Gathers column types for a given table
     *
     * @param	string	$table_name	Table name
     * @return	array	Associative array of tables to columns => type
     */
    protected function getTypesForTable($table_name)
    {
        if (! isset($this->columns[$table_name])) {
            $this->columns[$table_name] = [];

            foreach ($this->getTableDescription($table_name) as $column) {
                $this->columns[$table_name][$column['Field']] = $this->getDataType($column['Type']);
            }
        }

        return $this->columns[$table_name];
    }

    /**
     * Infers a data type for a given column type
     *
     * @param	string	$column_type	Table name
     * @return	const	Constant to define data type
     */
    protected function getDataType($column_type)
    {
        $type = strtolower($column_type);

        if (strpos($type, 'binary') !== false or
            strpos($type, 'blob') !== false) {
            return self::BINARY_TYPE;
        } elseif (strpos($type, 'char') !== false or
            strpos($type, 'text') !== false or
            strpos($type, 'date') !== false or
            strpos($type, 'time') !== false or
            strpos($type, 'enum') !== false) {
            return self::STRING_TYPE;
        } elseif (strpos($type, 'int') !== false or
            strpos($type, 'float') !== false or
            strpos($type, 'double') !== false or
            strpos($type, 'decimal') !== false) {
            return self::NUMBER_TYPE;
        }
    }
}

// EOF
