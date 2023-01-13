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

use ExpressionEngine\Service\Validation\Factory as ValidationFactory;

/**
 * Model Service Facade
 *
 * This is the only way the datastore should be communicated with. Either via
 * the query builder using get() or by creating new instances via make().
 *
 * Manually working with instances of the datastore is *not* supported.
 * All other public methods on it should be considered internal and
 * subject to change.
 */
class Facade
{
    protected $datastore;
    protected $validation;

    protected $_typed_models = ['File', 'Directory'];

    /**
     * @param $datastore ExpressionEngine\Service\Model\DataStore
     */
    public function __construct(DataStore $datastore)
    {
        $this->datastore = $datastore;
    }

    /**
     * Run a query
     *
     * @param String $name Model to run the query on
     * @param Mixed $default_ids One or more ids to prime the query with [optional]
     */
    public function get($name, $default_ids = null)
    {
        $builder = $this->datastore->get($name);

        if (isset($default_ids)) {
            $shortname = $this->removeAlias($name);

            if (empty($default_ids)) {
                $builder->markAsFutile();
            } elseif (is_array($default_ids)) {
                $builder->filter($shortname, 'IN', $default_ids);
            } else {
                $builder->filter($shortname, $default_ids);
            }
        }

        if (in_array($name, $this->_typed_models)) {
            $builder->filter('model_type', $name);
        }

        $builder->setFacade($this);

        return $builder;
    }

    /**
     * Create a model instance
     *
     * @param String $name Model to create
     * @param Array  $data Initial data
     */
    public function make($name, array $data = array())
    {
        $model = $this->datastore->make($name, $this, $data);

        if ($this->validation) {
            $model->setValidator($this->validation->make());
        }

        return $model;
    }

    /**
     *
     */
    public function setValidationFactory(ValidationFactory $validation)
    {
        $this->validation = $validation;
    }

    /**
     * Remove any aliasing and return the shortname
     *
     * A rather naive function, but reliable unless given a completely
     * garbage model name.
     */
    private function removeAlias($str)
    {
        $str = trim($str);
        $pos = strrpos($str, ' ');

        if ($pos !== false) {
            $str = trim(substr($str, $pos));
        }

        return str_replace(':', '_m_', $str);
    }
}

// EOF
