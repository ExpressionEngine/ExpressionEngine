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
 * Grouped search terms
 */
class Pro_search_term_group
{
    /**
     * Array of Pro_search_term instances
     */
    public $terms = array();

    /**
     * Construct and set the terms
     */
    public function __construct($terms = array())
    {
        $this->add($terms);
    }

    /**
     * Add term or terms to group
     */
    public function add($terms = array())
    {
        if (! is_array($terms)) {
            $terms = array($terms);
        }
        $this->terms = array_merge($this->terms, $terms);
    }

    /**
     * Return the grouped terms for fulltext search
     */
    public function get_fulltext_sql()
    {
        $sql = array();

        foreach ($this->terms as $term) {
            $sql[] = $term->get_fulltext_sql(false);
        }

        $sql = array_unique($sql);

        return '+(' . implode(' ', $sql) . ')';
    }

    /**
     * Return the grouped terms for fulltext search
     */
    public function get_fallback_sql()
    {
        $sql = array();

        foreach ($this->terms as $term) {
            $sql[] = $term->get_fallback_sql();
        }

        $sql = array_unique($sql);

        return '(' . implode(' OR ', $sql) . ')';
    }
}
