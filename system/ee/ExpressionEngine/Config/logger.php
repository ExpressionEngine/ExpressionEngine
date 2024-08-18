<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Defines logging handlers & processors for each channel and level
 *
 * The configuration is additive and goes low priority to high priority
 * That means if you have several handler defined for a channel (both in system and user config),
 * all of them will be used
 * If same processor with same parameters is defined twice, it will be used only once,
 * starting with the lowest logging level defined
 *
 * 'handlers' and 'processors' are 3 level deep arrays
 * The first level is array using channel names as keys
 * Channels that are currently built into EE are 'boot', 'cp', 'developer', 'site' and 'cli' (more may be added in the future).
 * Custom add-ons can define their own channels
 * '*' stands for 'all channels'
 * The elements of per-channel array are using custom names keys, so that those could be overridden in user config
 * (e.g. by specifying different processor instead of default)
 * The array value that corresponds to the key
 * The supported levels are: emergency, alert, critical, error, warning, notice, info, debug. No other values accepted
 * '*' stand for 'all levels'
 * The third level contains actual information about handlers in the form of array.
 * The first and required element of the array is fully qualified class name of the handler/processor class
 * The second element is optional and is the minimum level for the handler/processor to be triggered.
 * When omited, the handler/processor is triggered for all levels
 * Also note that not all processors are respecing the levels.
 * The third element is also optional and is an array of parameters to pass to the handler/processor constructor
 * The order of parameters needs to follow the order defined by the handler/processor class constructor (do not include level parameter)
 * If you want to override default handler, use the same key as in the default config, otherwise the key can be omitted
 *
 * You can use handlers & processors provided by ExpressionEngine (found in system/ee/ExpressionEngine/Library/Monolog/)
 * or those provided by Monolog (found in system/ee/vendor-build/monolog/monolog/src/Monolog/)
 *
 * If you want to skip logging for a channel/level combination, use '\ExpressionEngine\Dependency\Monolog\Handler\NoopHandler' as FQCN
 *
 */

return [
    'handlers' => [
        // log to ExpressionEngine database (requires EE to be booted)
        'DatabaseHandler' => [
            'class' => '\ExpressionEngine\Library\Monolog\Handler\DatabaseHandler', //FQCN
            'params' => [] // parameters, in order passed to constructor, level can be omited
        ],
        // send email using ExpressionEngine means (requires EE to be booted)
        'EEMailHandler' => [
            'class' => '\ExpressionEngine\Library\Monolog\Handler\EEMailHandler'
        ],
        // log to file
        'UserLogFileHandler' => [
            'class' => '\ExpressionEngine\Library\Monolog\Handler\UserLogFileHandler',
            'params' => [SYSPATH . 'user/logs/log.php']
        ],
        // system error log
        'ErrorLogHandler' => [
            'class' => '\ExpressionEngine\Dependency\Monolog\Handler\ErrorLogHandler',
        ],
        // all messages after this handler will not be logged
        // that includes the handlers defined for 'all channels'
        'NullHandler' => [
            'class' => '\ExpressionEngine\Dependency\Monolog\Handler\NullHandler',
        ],
        // does not do anything, use for testing
        'NoopHandler' => [
            'class' => '\ExpressionEngine\Dependency\Monolog\Handler\NoopHandler',
        ],
        // send logs to browser console
        'BrowserConsoleHandler' => [
            'class' => '\ExpressionEngine\Dependency\Monolog\Handler\BrowserConsoleHandler',
        ],
    ],
    'processors' => [
        'IntrospectionProcessor' => [
            'class' => '\ExpressionEngine\Library\Monolog\Processor\IntrospectionProcessor'
        ],
        'CurrentMemberProcessor' => [
            'class' => '\ExpressionEngine\Library\Monolog\Processor\CurrentMemberProcessor'
        ],
        'WebProcessor' => [
            'class' => '\ExpressionEngine\Dependency\Monolog\Processor\WebProcessor',
            'params' => [
                null,
                ['url', 'server', 'http_method', 'referrer']
            ]
        ],
        'HostnameProcessor' => [
            'class' => '\ExpressionEngine\Dependency\Monolog\Processor\HostnameProcessor'
        ],
        'MemoryUsageProcessor' => [
            'class' => '\ExpressionEngine\Dependency\Monolog\Processor\MemoryUsageProcessor'
        ],
        'MemoryPeakUsageProcessor' => [
            'class' => '\ExpressionEngine\Dependency\Monolog\Processor\MemoryPeakUsageProcessor'
        ],
    ]
];



 //EOF