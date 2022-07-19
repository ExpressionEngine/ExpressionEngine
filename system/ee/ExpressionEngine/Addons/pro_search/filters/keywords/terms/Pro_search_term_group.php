<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Grouped search terms
 *
 * @package        pro_search
 * @author         ExpressionEngine
 * @link           https://eeharbor.com/pro-search
 * @copyright      Copyright (c) 2022, ExpressionEngine
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
