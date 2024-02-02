<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Logger;

use ExpressionEngine\Dependency\Psr\Log\LoggerInterface;
use ExpressionEngine\Dependency\Psr\Log\AbstractLogger;
use ExpressionEngine\Dependency\Monolog;

/**
 * Return Logger instance that we can work with
 */
class Logger extends AbstractLogger implements LoggerInterface
{
    protected static $factory;
    protected $logger;

    /**
     * Get the Factory to work with
     */
    public function __construct()
    {
        if (is_null(self::$factory)) {
            self::$factory = new Factory();
        }
    }

    /**
     * Get the Logger from factory
     *
     * @param string $channel log channel name
     * @param boolean $forceNew force new logger instance
     * @return self
     */
    public function get(string $channel = null, $forceNew = false): Monolog\Logger
    {
        // if channel not provided, guess based on request type
        if (is_null($channel)) {
            switch (REQ) {
                case 'CP':
                    $channel = 'cp';
                    break;
                case 'CLI':
                    $channel = 'cli';
                    break;
                default:
                    $channel = 'site';
                    break;
            }
        }

        $this->logger = self::$factory->get($channel, $forceNew);
        //no logger? no logging
        if (! $this->logger) {
            throw new \LogicException('There is no logger instance to log to');
        }

        return $this->logger;
    }

    /**
     * Log the message at any level
     */
    public function log($level, $message, array $context = array())
    {
        //no logger? no logging
        if (! $this->logger) {
            throw new \LogicException('There is no logger instance to log to');
        }
        // forward everything to Monolog
        $this->logger->log($level, $message, $context);
        return $this;
    }
}

// EOF
