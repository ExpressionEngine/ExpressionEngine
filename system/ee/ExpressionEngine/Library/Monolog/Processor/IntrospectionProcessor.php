<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2024, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Monolog\Processor;

use ExpressionEngine\Dependency\Monolog\Processor;
use ExpressionEngine\Dependency\Monolog\Logger;

/**
 * Have to basically redeclare original file from Monolog package.
 * because we need to change private property skipFunctions
 *
 * Injects line/file:class/function where the log message came from
 *
 * Warning: This only works if the handler processes the logs directly.
 * If you put the processor on a handler that is behind a FingersCrossedHandler
 * for example, the processor will only be called once the trigger level is reached,
 * and all the log records will have the same file/line/.. data from the call that
 * triggered the FingersCrossedHandler.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 *
 * @phpstan-import-type Level from \Monolog\Logger
 * @phpstan-import-type LevelName from \Monolog\Logger
 */

class IntrospectionProcessor extends Processor\IntrospectionProcessor
{
    /** @var int */
    private $level;
    /** @var string[] */
    private $skipClassesPartials;
    /** @var int */
    private $skipStackFramesCount;
    /** @var string[] */
    private $skipFunctions = ['call_user_func', 'call_user_func_array', 'log_message', 'show_exception'];
    /**
     * @param string|int $level               The minimum logging level at which this Processor will be triggered
     * @param string[]   $skipClassesPartials
     *
     * @phpstan-param Level|LevelName|LogLevel::* $level
     */
    public function __construct($level = Logger::DEBUG, array $skipClassesPartials = [], int $skipStackFramesCount = 0)
    {
        $this->level = Logger::toMonologLevel($level);
        $this->skipClassesPartials = \array_merge(['Monolog\\', 'EE_Exceptions'], $skipClassesPartials);
        $this->skipStackFramesCount = $skipStackFramesCount;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(array $record) : array
    {
        // return if the level is not high enough
        if ($record['level'] < $this->level) {
            return $record;
        }
        $trace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
        // skip first since it's always the current method
        \array_shift($trace);
        // the call_user_func call is also skipped
        \array_shift($trace);
        $i = 0;
        while ($this->isTraceClassOrSkippedFunction($trace, $i)) {
            if (isset($trace[$i]['class'])) {
                foreach ($this->skipClassesPartials as $part) {
                    if (\strpos($trace[$i]['class'], $part) !== \false) {
                        $i++;
                        continue 2;
                    }
                }
            } elseif (\in_array($trace[$i]['function'], $this->skipFunctions)) {
                $i++;
                continue;
            }
            break;
        }
        $i += $this->skipStackFramesCount;
        // we should have the call source now
        $record['extra'] = \array_merge($record['extra'], ['file' => isset($trace[$i - 1]['file']) ? $trace[$i - 1]['file'] : null, 'line' => isset($trace[$i - 1]['line']) ? $trace[$i - 1]['line'] : null, 'class' => isset($trace[$i]['class']) ? $trace[$i]['class'] : null, 'callType' => isset($trace[$i]['type']) ? $trace[$i]['type'] : null, 'function' => isset($trace[$i]['function']) ? $trace[$i]['function'] : null]);
        return $record;
    }
    /**
     * @param array[] $trace
     */
    private function isTraceClassOrSkippedFunction(array $trace, int $index) : bool
    {
        if (!isset($trace[$index])) {
            return \false;
        }
        return isset($trace[$index]['class']) || \in_array($trace[$index]['function'], $this->skipFunctions);
    }
}