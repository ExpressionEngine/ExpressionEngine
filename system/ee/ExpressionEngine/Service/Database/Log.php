<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Database;

/**
 * Database Log
 */
class Log
{
    /**
     * @var String Identifying name in the query log
     */
    protected $name;

    /**
     * @var Int Query count
     */
    protected $count = 0;

    /**
     * @var Array [[Query, location, time]]
     */
    protected $queries = array();

    /**
     * @var Array [[Query, count, location]]
     */
    protected $query_metrics = array();

    /**
     * @var Bool Store queries for debugging?
     */
    protected $save_queries = false;

    /**
     * Create a named log. We have a name so that we can display logs
     * for each connection individually.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Turn on/off saving queries
     *
     * @param bool $save TRUE to save, FALSE to not
     * @return void
     */
    public function saveQueries($save = true)
    {
        $this->save_queries = $save;
    }

    /**
     * Add a query to the log
     *
     * @param string $sql
     * @param foo $time
     * @param foo $memory
     * @return void
     */
    public function addQuery($sql, $time, $memory)
    {
        $this->count++;

        if ($this->save_queries) {
            $query = $sql;
            $location = $this->getTrace();

            $sql_sig = md5($sql);
            if (isset($this->query_metrics[$sql_sig])) {
                $this->query_metrics[$sql_sig]['count']++;
                $this->query_metrics[$sql_sig]['locations'] = array_unique(
                    array_merge($this->query_metrics[$sql_sig]['locations'], array($location))
                );
            } else {
                $this->query_metrics[$sql_sig] = array(
                    'query' => $sql,
                    'count' => 1,
                    'locations' => array($location)
                );
            }

            $this->queries[] = array($query, $location, $time, $memory);
        }
    }

    /**
     * Get all logged queries and their execution times
     *
     * @return array The queries.
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * Get the total query count
     *
     * @return int The number of queries
     */
    public function getQueryCount()
    {
        return $this->count;
    }

    /**
     * Get the query metrics (tracks duplicates)
     *
     * @return
     */
    public function getQueryMetrics()
    {
        return $this->query_metrics;
    }

    /**
     *
     */
    protected function getTrace()
    {
        $source = '';
        $trace = debug_backtrace();

        // Log file the query came from
        if (count($trace) >= 3) {
            $path = BASEPATH;
            require_once $path . 'helpers/array_helper.php';

            $i = 1;
            $frame = null;

            while (isset($trace[$i])) {
                $frame = $trace[$i];

                if (isset($frame['class'])) {
                    $class = $frame['class'];

                    if (strpos($class, 'CI_DB_') !== 0 &&
                        strpos($class, __NAMESPACE__) !== 0 &&
                        strpos($class, 'ExpressionEngine\Service\Model') !== 0) {
                        break;
                    }
                }

                $i++;
            }

            $file = element('file', $frame, '');
            $line = element('line', $frame, '');
            $class = element('class', $frame, '');
            $func = element('function', $frame, '');

            // Replace path with APP or CI to shorten the string
            if ($file != '') {
                $file = 'system/' . str_replace(SYSPATH, '', $file);
            }

            // Build the caller source info
            $source = "\n#" . $file . ' L:' . $line . '  ';
            $source .= ($class != '') ? $class . '::' : '';
            $source .= ($func != '') ? $func . '() ' : '';
        }

        return $source;
    }
}

// EOF
