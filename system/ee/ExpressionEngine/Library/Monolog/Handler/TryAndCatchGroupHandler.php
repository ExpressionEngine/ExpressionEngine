<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2024, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Monolog\Handler;

use ExpressionEngine\Dependency\Monolog\Handler\WhatFailureGroupHandler;

/**
 * Same as WhatFailureGroupHandler but respects the bubble flag
 * allowing NullHandler to terminate the propagation
 */
class TryAndCatchGroupHandler extends WhatFailureGroupHandler
{
    /**
     * {@inheritDoc}
     */
    public function handle(array $record) : bool
    {
        if ($this->processors) {
            /** @var Record $record */
            $record = $this->processRecord($record);
        }
        foreach ($this->handlers as $handler) {
            try {
                $bubble = $handler->handle($record);
                if ($bubble === true) {
                    break;
                }
            } catch (\Throwable $e) {
                // What failure?
            }
        }
        return \false === $this->bubble;
    }
    /**
     * {@inheritDoc}
     */
    public function handleBatch(array $records) : void
    {
        if ($this->processors) {
            $processed = array();
            foreach ($records as $record) {
                $processed[] = $this->processRecord($record);
            }
            /** @var Record[] $records */
            $records = $processed;
        }
        foreach ($this->handlers as $handler) {
            try {
                $bubble = $handler->handleBatch($records);
                if ($bubble === true) {
                    break;
                }
            } catch (\Throwable $e) {
                // What failure?
            }
        }
    }
    /**
     * {@inheritDoc}
     */
    public function close() : void
    {
        foreach ($this->handlers as $handler) {
            try {
                $handler->close();
            } catch (\Throwable $e) {
                // What failure?
            }
        }
    }
}
