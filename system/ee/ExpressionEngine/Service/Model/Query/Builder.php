<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model\Query;

use ExpressionEngine\Service\Model\DataStore;
use ExpressionEngine\Service\Model\Query\Result;

/**
 * Query Builder
 */
class Builder
{
    protected $from;
    protected $facade;
    protected $datastore;
    protected $model;

    protected $set = array();
    protected $withs = array();
    protected $fields = array();
    protected $orders = array();
    protected $filters = array();
    protected $search = array();

    protected $filter_stack = array();
    protected $lazy_constraints = array();

    protected $limit = '18446744073709551615'; // 2^64
    protected $offset = 0;

    protected $futile = false;

    /**
     *
     */
    public function __construct($from)
    {
        $this->from = $from;
    }

    /**
     *
     */
    public function first($cache = false)
    {
        $this->limit(1);

        return $this->fetch($cache)->first();
    }

    /**
     *
     */
    public function all($cache = false)
    {
        return $this->fetch($cache)->all();
    }

    /**
     *
     */
    public function update()
    {
        return $this->datastore->updateQuery($this);
    }

    /**
     *
     */
    public function insert()
    {
        return $this->datastore->insertQuery($this);
    }

    /**
     *
     */
    public function delete()
    {
        return $this->datastore->deleteQuery($this);
    }

    /**
     *
     */
    public function count($cache = false)
    {
        if ($cache) {
            $copyOfThis = clone $this;
            unset($copyOfThis->facade);
            unset($copyOfThis->datastore);
            $cacheKey = crc32(serialize($copyOfThis) . '__count');
            unset($copyOfThis);
            $fetched = $this->getFromCache($cacheKey);
            if ($fetched !== false) {
                return $fetched;
            }
        }

        if ($this->isFutile()) {
            return 0;
        }

        $fetched = $this->datastore->countQuery($this);

        if ($cache) {
            $this->saveToCache($cacheKey, $fetched);
        }

        return $fetched;
    }

    /**
     * Run a fetch in batches
     *
     * @param Int     $batch_size Batch size
     * @param \Closure $callback   Closure to run for each result
     *
     * NOTE: The callback can be passed in the first parameter, in
     * which case the default $batch_size (@see Batch) is used.
     */
    public function batch($batch_size, $callback = null)
    {
        $batch = new Batch($this);

        if (! isset($callback)) {
            $callback = $batch_size;
        } else {
            $batch->setBatchSize($batch_size);
        }

        return $batch->process($callback);
    }

    /**
     * Send a fetch to the datastore
     */
    protected function fetch($cache = false)
    {
        if ($cache) {
            $copyOfThis = clone $this;
            unset($copyOfThis->facade);
            unset($copyOfThis->datastore);
            $cacheKey = crc32(serialize($copyOfThis));
            unset($copyOfThis);
            $fetched = $this->getFromCache($cacheKey);
            if ($fetched !== false) {
                return $fetched;
            }
        }

        if (! $this->filterStackIsEmpty()) {
            throw new \Exception('Unclosed filter group.');
        }

        if ($this->isFutile()) {
            return new Result(array(), array(), array());
        }

        $fetched = $this->datastore
            ->selectQuery($this)
            ->setFacade($this->facade);

        if ($cache) {
            $this->saveToCache($cacheKey, $fetched);
        }

        return $fetched;
    }

    /**
    * Mark a fetch as futile
    *
    * Calling this will prevent any fetch from being sent on the wire.
    * Use for impossible filters, such as IN array().
    */
    public function markAsFutile()
    {
        $this->futile = true;
    }

    /**
    * Will fetching do anything?
    *
    * @return bool True if fetching will yield nothing
    */
    protected function isFutile()
    {
        return $this->futile;
    }

    /**
     * Search for a value
     *
     * @param Mixed  $properties  Relationship.columnname [array|string]
     * @param Mixed   $value      Value to search for
     * @return Builder  $this
     */
    public function search($properties, $value)
    {
        if (! is_array($properties)) {
            $properties = array($properties);
        }

        $words = $this->prepSearch($value);

        if (empty($words)) {
            return $this;
        }

        foreach ($properties as $property) {
            $search = isset($this->search[$property]) ? $this->search[$property] : array();

            foreach ($words as $word) {
                if ($word[0] == '-') {
                    $search[substr($word, 1)] = false;
                } else {
                    $search[$word] = true;
                }
            }

            $this->search[$property] = $search;
        }

        return $this;
    }

    /**
     * Get the current search data
     *
     * @return array of search data [field1 => [word1 => include?, ...]]
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * Prepare a value for search
     *
     * Here we handle grouping of:
     *    Single words
     *    Multiple words in quotes
     *    Negation of either of the above
     *
     * @param String $str Search string
     * @return array of search terms, possibly prefixed with a - for negation
     */
    private function prepSearch($str)
    {
        $length = strlen($str);
        $words = array();

        $word = '';
        $quote = '';
        $quoted = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $str[$i];

            if (($quoted == false && $char == ' ') || ($quoted == true && $char == $quote)) {
                if (strlen($word) > 2) {
                    $words[] = $word;
                }

                $quoted = false;
                $quote = '';
                $word = '';

                continue;
            }

            if ($quoted == false && ($char == '"' || $char == "'") && ($word === '' || $word == '-')) {
                $quoted = true;
                $quote = $char;

                continue;
            }

            $word .= $char;
        }

        if (strlen($word) > 2) {
            $words[] = $word;
        }

        return $words;
    }

    /**
     * Apply a filter
     *
     * @param String  $property  Relationship.columnname
     * @param String  $operator  Comparison operator [default: ==]
     * @param Mixed   $value     Value to compare to
     * @return Builder  $this
     */
    public function filter($property, $operator, $value = false)
    {
        $this->addFilter($property, $operator, $value, 'and');

        return $this;
    }

    /**
     * Same as `filter()`, but creates an OR statement.
     *
     * @param String  $property  Relationship.columnname
     * @param String  $operator  Comparison operator [default: ==]
     * @param Mixed   $value     Value to compare to
     * @return Builder  $this
     */
    public function orFilter($property, $operator, $value = false)
    {
        $this->addFilter($property, $operator, $value, 'or');

        return $this;
    }

    /**
     *
     */
    protected function addFilter($property, $operator, $value, $connective)
    {
        if ($value === false) {
            $value = $operator;
            $operator = '==';
        }

        $this->filters[] = array($property, $operator, $value, $connective);
    }

    /**
     * Open a filter group
     */
    public function filterGroup()
    {
        // open group
        $this->filter_stack[] = $this->filters;
        $this->filter_stack[] = 'and';

        $this->filters = array();

        return $this;
    }

    /**
     * Open a filter group that will be OR'd on the query
     */
    public function orFilterGroup()
    {
        $this->filter_stack[] = $this->filters;
        $this->filter_stack[] = 'or';

        $this->filters = array();

        return $this;
    }

    /**
     * Close a (or)filterGroup
     */
    public function endFilterGroup()
    {
        $filters = $this->filters;
        $connective = array_pop($this->filter_stack);
        $this->filters = array_pop($this->filter_stack);

        $this->filters[] = array(
            $connective,
            $filters
        );

        return $this;
    }

    /**
     * Check if the filter groups have been open and closed correctly
     */
    protected function filterStackIsEmpty()
    {
        return count($this->filter_stack) == 0;
    }

    /**
     * Only select and return a subset of fields.
     */
    public function fields()
    {
        $this->fields = array_merge($this->fields, func_get_args());

        return $this;
    }

    /**
     * Set data for update or insert
     */
    public function set($key, $value = null)
    {
        if (! is_array($key)) {
            $key = array($key => $value);
        }

        $this->set = array_merge($this->set, $key);

        return $this;
    }

    /**
     *
     */
    public function with()
    {
        $relateds = func_get_args();
        $this->withs = $this->addToWith($this->withs, $relateds);

        return $this;
    }

    /**
     *
     */
    protected function addToWith($withs, $relateds)
    {
        foreach ($relateds as $parent => $children) {
            if ($children instanceof \Closure) {
                $children = array();
            }

            if (! is_array($children)) {
                $children = array($children => array());
            }

            if (is_numeric($parent)) {
                $withs = $this->addToWith($withs, $children);
            } else {
                if (! isset($withs[$parent])) {
                    $withs[$parent] = array();
                }

                $withs[$parent] = $this->addToWith($withs[$parent], $children);
            }
        }

        return $withs;
    }

    /**
     *
     */
    public function getWiths()
    {
        return $this->withs;
    }

    /**
     * Add ordering to the query
     */
    public function order($property, $direction = '', $escape = true)
    {
        $this->orders[] = array($property, $direction, $escape);

        return $this;
    }

    /**
     * Limit the result set.
     *
     * @param int Number of elements to limit to
     * @return $this
     */
    public function limit($n = null)
    {
        $this->limit = $n;

        return $this;
    }

    /**
     * Offset the result set.
     *
     * @param int Number of elements to offset to
     * @return $this
     */
    public function offset($n)
    {
        $this->offset = $n;

        return $this;
    }

    /**
     *
     */
    public function setLazyConstraint($relation, $model)
    {
        $this->lazy_constraints[] = array($relation, $model);
    }

    /**
     *
     */
    public function setExisting($model)
    {
        $this->model = $model;
    }

    /**
     *
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     *
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     *
     */
    public function getFields()
    {
        return $this->fields;
    }

    /** Get the values that need to be SET
     *
     */
    public function getSet()
    {
        return $this->set;
    }

    /**
     * Get the query LIMIT
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Get the query OFFSET
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Get the query ORDER
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     *
     */
    public function getLazyConstraints()
    {
        return $this->lazy_constraints;
    }

    /**
     *
     */
    public function getExisting()
    {
        return $this->model;
    }

    /**
     *
     */
    public function setDataStore(DataStore $datastore)
    {
        $this->datastore = $datastore;
    }

    /**
     *
     */
    public function setFacade($facade)
    {
        $this->facade = $facade;
    }

    /**
     *
     */
    public function getFacade()
    {
        return $this->facade;
    }

    protected function saveToCache($key, $data)
    {
        if (isset(ee()->core)) {
            ee()->core->set_cache(__CLASS__, $key, $data);
        }
    }

    protected function getFromCache($key)
    {
        if (isset(ee()->core)) {
            return ee()->core->cache(__CLASS__, $key, false);
        }
        return false;
    }
}

// EOF
