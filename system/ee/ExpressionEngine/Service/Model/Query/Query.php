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

/**
 * Query
 */
abstract class Query
{
    protected $store = null;
    protected $builder = null;

    public function __construct(DataStore $store, Builder $builder)
    {
        $this->store = $store;
        $this->builder = $builder;
    }

    abstract public function run();
}
