<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Profiler\Section;

use ExpressionEngine\Service\Profiler\ProfilerSection;
use ExpressionEngine\Service\View\View;

/**
 * Database Profiler Section
 */
class Database extends ProfilerSection
{
    /**
     * @var int  total queries
     **/
    protected $total_queries;

    /**
     * @var float  threshold for warnings, in seconds
     **/
    protected $time_threshold = 0.25;

    /**
     * @var float  threshold for warnings, in bytes, default 1MB
     **/
    protected $memory_threshold = 1048576;

    /**
     * @var SQL Keywords we want highlighted
     */
    protected $keywords = array(
        'SELECT',
        'DISTINCT',
        'FROM',
        'WHERE',
        'AND',
        'LEFT&nbsp;JOIN',
        'ORDER&nbsp;BY',
        'GROUP&nbsp;BY',
        'LIMIT',
        'INSERT',
        'INTO',
        'VALUES',
        'UPDATE',
        'OR&nbsp;',
        'HAVING',
        'OFFSET',
        'NOT&nbsp;IN',
        'IN',
        'LIKE',
        'NOT&nbsp;LIKE',
        'COUNT',
        'MAX',
        'MIN',
        'ON',
        'AS',
        'AVG',
        'SUM',
        '(',
        ')'
    );

    /**
     * Get a brief text summary (used for tabs, labels, etc.)
     *
     * @return  string  the section summary
     **/
    public function getSummary()
    {
        return $this->total_queries . ' ' . lang('profiler_queries');
    }

    /**
     * Set the section's data
     *
     * @param  array  Array of Database $db objects
     * @return void
     **/
    public function setData($dbs)
    {
        $count = 0;

        foreach ($dbs as $db) {
            $count++;
            $log = $db->getLog();
            $this->total_queries += $log->getQueryCount();

            $label = $db->getConfig()->get('database');
            $this->data['duplicate_queries'][$label] = $this->getDuplicateQueries($log);
            $this->data['database'][$label] = $this->getQueries($log);
        }
    }

    /**
     * Gets the view name needed to render the section
     *
     * @return string  the view/name
     **/
    public function getViewName()
    {
        return 'profiler/section/database';
    }

    /**
     * Build the data set for duplicate queries
     *
     * @param object	$log	a DB Log object
     * @return array	duplicates [count, query]
     **/
    private function getDuplicateQueries($log)
    {
        $duplicate_queries = array_filter(
            $log->getQueryMetrics(),
            function ($value) {
                return ($value['count'] > 1);
            }
        );

        $duplicates = array();
        foreach ($duplicate_queries as $dupe_query) {
            $duplicates[] = array(
                'count' => $dupe_query['count'],
                'query' => $dupe_query['query'],
                'location' => implode(' ', $dupe_query['locations'])
            );
        }

        return $duplicates;
    }

    /**
     * Build the data set for queries
     *
     * @param object	$log	a DB Log object
     * @return array	queries [time, query]
     **/
    private function getQueries($log)
    {
        if ($log->getQueryCount() == 0) {
            return lang('profiler_no_queries');
        }

        foreach ($log->getQueries() as $query) {
            list($sql, $location, $time, $memory) = $query;

            $data[] = array(
                'time' => number_format($time, 4),
                'memory' => $memory,
                'formatted_memory' => (string) $this->fmt_factory->make('Number', $memory)->bytes(),
                'time_threshold' => $this->time_threshold,
                'memory_threshold' => $this->memory_threshold,
                'query' => $sql,
                'location' => $location
            );
        }

        return $data;
    }
}

// EOF
