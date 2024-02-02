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

use ExpressionEngine\Dependency\Monolog;
use ExpressionEngine\Library\Monolog\Handler\TryAndCatchGroupHandler;

/**
 * Return Logger instance that we can work with
 */
class Factory
{
    // Monolog Logger istances
    protected $loggers = [];
    // configured handlers and processors, out of logger.php
    protected $loggerConfig;
    // logging config, 'logging' property out of config.php
    protected $config;
    // default config when nothing is assigned
    protected $defaultConfig = [
        'DatabaseHandler' => [
            'level' => 'info'
        ]
    ];
    // ensure DatabaseHandler is never re-declared
    protected $defaultLoggerConfig = [
        'handler' => [
            'DatabaseHandler' => [
                'class' => '\ExpressionEngine\Library\Monolog\Handler\DatabaseHandler'
            ],
        ]
    ];
    // if something's wrong, we add message to stack of logs
    protected $selfLogs = [];

    /**
     * Contruct and configure
     */
    public function __construct()
    {
        // not everything might be booted, so we can't use ee()->config->loadFile('logger')
        $this->loggerConfig = array_merge_recursive(get_logger_config(), $this->defaultLoggerConfig);
        $this->config = config_item('logging');
    }

    /**
     * Get logger instance for the channel
     *
     * @param string $channel
     * @param boolean $forceNew
     * @return Monolog\Logger
     */
    public function get(string $channel, $forceNew = false): Monolog\Logger
    {
        //channel can't be empty
        if (empty($channel)) {
            throw new \Exception('Logging channel name can not be empty');
        }
        if (isset($this->loggers[$channel]) && ! $forceNew) {
            return $this->loggers[$channel];
        }

        $logger = new Monolog\Logger($channel);

        $config = [];
        // get the config part for this channel
        if (isset($this->config[$channel])) {
            $config = $this->config[$channel];
        }
        // 'all channels' part goes last, so we could forward it to NullHandler
        if (isset($this->config['*'])) {
            $config = array_merge_recursive($config, $this->config['*']);
        }
        // still nothing set up, log into EE database
        if (empty($config) || empty(reset($config))) {
            $config = $this->defaultConfig;
        }
        // developer logs need to go into EE DB always
        if ($channel == 'developer' && !array_key_exists('DatabaseHandler', $config)) {
            $config = array_merge_recursive($config, $this->defaultConfig);
        }

        // set the handlers (and processors recurovely) according to configuration
        $handlers = $this->getLoggingInstances($config);
        if (empty($handlers)) {
            throw new \LogicException('No handlers defined for this logging channel');
        }

        // the handlers are passed via another handler that does try/catch
        $catcher = new TryAndCatchGroupHandler($handlers);
        $logger->pushHandler($catcher);

        if ($forceNew == false) {
            $this->loggers[$channel] = $logger;
        }

        // any own messages to log?
        if (!empty($this->selfLogs)) {
            foreach ($this->selfLogs as $message) {
                $logger->warning($message);
            }
            $this->selfLogs = [];
        }

        return $logger;
    }

    /**
     * Populate handler/processors based on config
     *
     * @param array $config
     * @param string $what handlers|processors
     * @return array $instances array of class instances
     */
    private function getLoggingInstances($config, $what = 'handler')
    {
        $type = $what . 's'; //plural

        $instances = [];

        // populate config with the handlers / processors and strip the duplicates
        $setup = [];
        foreach ($config as $name => $systemConfig) {
            // if config with this name does not exist, skip it
            // (and log it a bit later)
            if (!isset($this->loggerConfig[$type][$name])) {
                $this->selfLogs[] = 'Logging ' . $what . ' ' . $name . ' is called but not configured';
                continue;
            }
            $loggerConfig = $this->loggerConfig[$type][$name];
            if (!is_array($loggerConfig)) {
                $this->selfLogs[] = 'Logging ' . $what . ' ' . $name . ' is not properly set up';
                continue;
            }
            if (!array_key_exists('class', $loggerConfig)) {
                $this->selfLogs[] = 'Logging ' . $what . ' ' . $name . ' does not point to class name';
                continue;
            }

            //ensure all elements of array are present
            if (!isset($loggerConfig['params'])) {
                $loggerConfig['params'] = [];
            }
            if (isset($systemConfig['level'])) {
                $loggerConfig['level'] = $systemConfig['level'];
            } elseif (!isset($loggerConfig['level'])) {
                $loggerConfig['level'] = 'info';
            }

            if (isset($systemConfig['processors'])) {
                $loggerConfig['processors'] = $systemConfig['processors'];
            } else {
                $loggerConfig['processors'] = [];
            }

            $setup[$name] = $loggerConfig;
        }

        // set up the handlers / processors
        foreach ($setup as $info) {
            extract($info);
            // class, params, level, processors
            $reflection = new \ReflectionClass($class);
            // need to make sure to pass correct number of parameters to the contructor
            $params = array_values($params);
            $contructorParams = [];
            $constructor = $reflection->getConstructor();
            if (!is_null($constructor)) {
                $constructorExpectedParams = $constructor->getParameters();
                // level can be anywhere in constructor :(
                $j = 0;
                foreach ($constructorExpectedParams as $i => $expectedParam) {
                    $paramName = $expectedParam->getName();
                    if ($paramName == 'level') {
                        $contructorParams[$paramName] = $expectedParam->getDefaultValue();
                        // shift to next iteration on params, but keeping the param index the same
                        // that's really needed only if level is first, but there are user-provided parameters
                        continue;
                    }
                    if (isset($params[$j])) {
                        $contructorParams[$paramName] = $params[$j];
                    } else {
                        $contructorParams[$paramName] = $expectedParam->getDefaultValue();
                    }
                    $j++;
                }
                // override the default level with what is set in config
                if (isset($contructorParams['level'])) {
                    $contructorParams['level'] = Monolog\Logger::toMonologLevel($level);
                }
            }

            $instance = $reflection->newInstanceArgs($contructorParams);
            if (!empty($processors)) {
                // recursively add processors
                $processorsConfig = array_fill_keys($processors, ['level' => $contructorParams['level']]);
                $processorInstances = $this->getLoggingInstances($processorsConfig, 'processor');
                foreach ($processorInstances as $processor) {
                    $instance->pushProcessor($processor);
                }
            }
            $instances[] = $instance;
        }

        return $instances;
    }
}

// EOF
