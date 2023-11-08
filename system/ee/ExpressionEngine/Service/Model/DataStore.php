<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model;

use Closure;

use ExpressionEngine\Service\Model\Query\Builder;
use ExpressionEngine\Service\Model\Relation\Relation;
use ExpressionEngine\Service\Database\Database;

/**
 * Model Service DataStore
 *
 * This is the backend for all model interactions. It should never be exposed
 * directly to any code outside of this namespace. This includes no access from
 * userspace models. The only way to interact with it should be through the
 * model facade.
 */
class DataStore
{
    /**
     * @var ExpressionEngine\Service\Database\Database
     */
    private $db;

    /**
     * @var ExpressionEngine\Service\Model\RelationGraph
     */
    private $graph;

    /**
     * @var ExpressionEngine\Service\Model\Configuration
     */
    private $config;

    /**
     * @var ExpressionEngine\Service\Model\Registry
     */
    private $registry;

    /**
     * @param $db ExpressionEngine\Service\Database\Database
     * @param $config ExpressionEngine\Service\Model\Configuration
     */
    public function __construct(Database $db, Configuration $config)
    {
        $this->db = $db;
        $this->config = $config;

        $this->registry = new Registry(
            $config->getModelAliases(),
            $config->getDefaultPrefix(),
            $config->getEnabledPrefixes()
        );

        $this->graph = new RelationGraph(
            $this,
            $this->registry,
            $config->getModelDependencies()
        );
    }

    /**
     * @param Object|String $name      The name of a model or an existing
     *                                 model instance
     * @param Facade      $facade      A facade instance. The datastore
     *                                 doesn't care about the facade, but
     *                                 the model and associations need it, so
     *                                 we pass it in to keep things properly
     *                                 isolated.
     * @param Array         $data      The initial data to set on the object.
     *                                 This will be marked as dirty! Use fill()
     *                                 if you need clean data (i.e. from db).
     */
    public function make($name, Facade $facade, array $data = array())
    {
        $is_object = ($name instanceof Model);

        if ($is_object) {
            $model = $name;
            $name = $model->getName();
        } else {
            $model = $this->newModelFromAlias($name);
        }

        $prefix = $this->registry->getPrefix($name);

        if (strpos($name, $prefix) !== 0) {
            $name = $prefix . ':' . $name;
        }

        if (! $is_object) {
            $model->setName($name);
        }

        $model->setFacade($facade);

        if (count($data)) {
            $model->set($data);
        }

        $this->initializeAssociationsOn($model);

        return $model;
    }

    /**
     * Create a query
     *
     * @param String $name  Name of the model to query on
     * @return Object Query\Builder
     */
    public function get($name)
    {
        $object = null;

        if ($name instanceof Model) {
            $object = $name;
            $name = $object->getName();
        }

        $builder = new Builder($name);

        $builder->setExisting($object);
        $builder->setDataStore($this);

        return $builder;
    }

    /**
     * Create a raw query
     *
     * @return Object \CI_DB
     */
    public function rawQuery()
    {
        return $this->db->newQuery();
    }

    public function getGraph()
    {
        return $this->graph;
    }

    /**
     * Create a metaDataReader
     *
     * @param String $name  Model to read metadata from
     * @return Object MetaDataReader
     */
    public function getMetaDataReader($name)
    {
        return $this->registry->getMetaDataReader($name);
    }

    /**
     * Prep the model associations
     *
     * @param Model $model  Model to initialize
     */
    protected function initializeAssociationsOn(Model $model)
    {
        $relations = $this->getAllRelations($model->getName());

        foreach ($relations as $name => $relation) {
            $assoc = $relation->createAssociation();
            $model->setAssociation($name, $assoc);
        }
    }

    /**
     * Get all relations for a model
     *
     * @param String $model_name  Name of the model
     * @return array of relations
     */
    public function getAllRelations($model_name)
    {
        return $this->graph->getAll($model_name);
    }

    /**
     * Get the inverse of a relation. Currently used by the relations
     * themselves to call back into this. Probably won't stay here.
     *
     * @param Relation $relation
     * @return Relation Inverse
     */
    public function getInverseRelation(Relation $relation)
    {
        return $this->graph->getInverse($relation);
    }

    /**
     * Get a relation for a given model. Currently used in query construction.
     *
     * @param Relation $relation
     * @return Relation requested relation
     */
    public function getRelation($model, $name)
    {
        return $this->graph->get($model, $name);
    }

    /**
     * Run a select query
     *
     * @param Builder $qb The query builder describing the query
     */
    public function selectQuery(Builder $qb)
    {
        return $this->runQuery('Select', $qb);
    }

    /**
     * Run an insert query
     *
     * @param Builder $qb The query builder describing the query
     */
    public function insertQuery(Builder $qb)
    {
        return $this->runQuery('Insert', $qb);
    }

    /**
     * Run an update query
     *
     * @param Builder $qb The query builder describing the query
     */
    public function updateQuery(Builder $qb)
    {
        return $this->runQuery('Update', $qb);
    }

    /**
     * Run a delete query
     *
     * @param Builder $qb The query builder describing the query
     */
    public function deleteQuery(Builder $qb)
    {
        return $this->runQuery('Delete', $qb);
    }

    /**
     * Run a count query
     *
     * @param Builder $qb The query builder describing the query
     */
    public function countQuery(Builder $qb)
    {
        return $this->runQuery('Count', $qb);
    }

    /**
     * Run a given query strategy
     *
     * @param String $name The name of the strategy
     * @param Builder $qb  The query builder describing the query
     */
    protected function runQuery($name, Builder $qb)
    {
        $class = __NAMESPACE__ . "\\Query\\{$name}";

        $worker = new $class($this, $qb);

        return $worker->run();
    }

    /**
     * Create a model instance from the di object
     *
     * @param String $name Model name
     * @return
     */
    protected function newModelFromAlias($name)
    {
        $class = $this->registry->expandAlias($name);

        if ($class instanceof Closure) {
            $model = $class();
        } else {
            if (!class_exists($class)) {
                throw new \Exception(
                    'Class "' . $class . '" not found when trying instatiate "' . $name . '" model'
                );
            }
            $model = new $class();
        }

        return $model;
    }
}

// EOF
