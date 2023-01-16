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

use ExpressionEngine\Service\Model\Collection;

/**
 * Query Result
 */
class Result
{
    protected $facade;

    protected $db_result;

    protected $columns = array();
    protected $aliases = array();
    protected $objects = array();
    protected $relations = array();

    protected $related_ids = array();
    private $primary_keys = array();

    public function __construct($db_result, $aliases, $relations)
    {
        $this->db_result = $db_result;
        $this->aliases = $aliases;
        $this->relations = array_reverse($relations);
    }

    public function first()
    {
        $all = $this->all();

        if (! count($all)) {
            return null;
        }

        return $all->first();
    }

    public function all()
    {
        if (! count($this->db_result)) {
            return new Collection();
        }

        $this->initializeResultArray();

        foreach ($this->db_result as $row) {
            $this->collectColumnsByAliasPrefix($row);
            $this->parseRow($row);
        }

        $this->constructRelationshipTree();

        reset($this->aliases);
        $root = key($this->aliases);

        foreach ($this->objects as $type => $objs) {
            foreach ($objs as $obj) {
                $obj->emit('afterLoad');
            }
        }

        return new Collection($this->objects[$root]);
    }

    /**
     * Take a single database row and turn it into one or more model objects.
     */
    protected function parseRow($row)
    {
        // track ids in this row [alias => id, alias2 => id2]
        // we use this to match relationships later
        $row_object_ids = array();

        // run through the aliases in the query
        foreach ($this->columns as $alias => $columns) {
            $model_data = array();

            // if we know the primary key for this alias, look up the value
            // to see if we've already processed it. This happens when we have
            // joins that pull in a duplicate data (e.g. Templates with TemplateGroup).
            if (isset($this->primary_keys[$alias])) {
                $pkey = $this->primary_keys[$alias];
                $value = $row[$pkey];

                if (isset($this->objects[$alias][$value])) {
                    $object = $this->objects[$alias][$value];
                    $row_object_ids[$alias] = $object->getId();

                    continue;
                }
            }

            // pull out unprefixed properties for this alias
            foreach ($columns as $property) {
                if (! array_key_exists("{$alias}__{$property}", $row)) {
                    throw new \Exception("Unknown model property in query result: `{$alias}.{$property}`");
                }

                $model_data[$property] = $row["{$alias}__{$property}"];
            }

            if (empty($model_data)) {
                continue;
            }

            $name = $this->aliases[$alias];

            // spin up the object
            $object = $this->facade->make($name);
            $object->emit('beforeLoad'); // do not add 'afterLoad' to this method, it must happen *after* relationships are matched
            $object->fill($model_data);

            // store for results and reuse
            $this->objects[$alias][$object->getId()] = $object;

            // on the first pass, memoize primary key names
            if (! isset($this->primary_keys[$alias])) {
                $this->primary_keys[$alias] = $alias . '__' . $object->getPrimaryKey();
            }

            $row_object_ids[$alias] = $object->getId();
        }

        // connect ids
        foreach ($row_object_ids as $alias => $id) {
            $related = $row_object_ids;
            unset($related[$alias]);

            if (! isset($this->related_ids[$alias])) {
                $this->related_ids[$alias] = array();
            }

            if (! isset($this->related_ids[$alias][$id])) {
                $this->related_ids[$alias][$id] = array();
            }

            $this->related_ids[$alias][$id][] = $related;
        }
    }

    /**
     *
     */
    protected function constructRelationshipTree()
    {
        foreach ($this->relations as $to_alias => $lookup) {
            $kids = $this->objects[$to_alias];

            foreach ($lookup as $from_alias => $relation) {
                $parents = $this->objects[$from_alias];

                $related_ids = $this->matchIds($parents, $from_alias, $to_alias);

                $this->matchRelation($parents, $kids, $related_ids, $relation);
            }
        }
    }

    /**
     *
     */
    protected function matchIds($parents, $from_alias, $to_alias)
    {
        $related_ids = array();

        foreach ($parents as $p_id => $parent) {
            $related_ids[$p_id] = array();

            $all_related = $this->related_ids[$from_alias][$p_id];

            foreach ($all_related as $potential) {
                if (isset($potential[$to_alias])) {
                    $related_ids[$p_id][] = $potential[$to_alias];
                }
            }
        }

        return $related_ids;
    }

    /**
     *
     */
    protected function matchRelation($parents, $kids, $related_ids, $relation)
    {
        foreach ($parents as $p_id => $parent) {
            $set = array_unique($related_ids[$p_id]);
            $collection = array();

            foreach ($set as $id) {
                $collection[] = $kids[$id];
            }

            $name = $relation->getName();
            $parent->getAssociation($name)->fill($collection);
        }
    }

    /**
     * Group all columns by their alias prefix.
     */
    protected function collectColumnsByAliasPrefix($row)
    {
        $columns = array();

        foreach (array_keys($row) as $column) {
            list($alias, $property) = explode('__', $column);

            if (! array_key_exists($alias, $columns)) {
                $columns[$alias] = array();
            }

            $columns[$alias][] = $property;
        }

        $this->columns = $columns;
    }

    /**
     * Set up an array to hold all of our temporary data.
     */
    protected function initializeResultArray()
    {
        foreach ($this->aliases as $alias => $model) {
            $this->objects[$alias] = array();
        }
    }

    public function setFacade($facade)
    {
        $this->facade = $facade;

        return $this;
    }
}

// EOF
