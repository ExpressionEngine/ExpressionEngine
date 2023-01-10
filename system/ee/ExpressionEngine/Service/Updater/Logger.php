<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Updater;

use ExpressionEngine\Service\Logger\File;

/**
 * Updater Logger class
 *
 * Extends the File updater to also send messages to stdout if necessary, and
 * also adds a timestamp to the message
 */
class Logger extends File
{
    /**
     * Formats the log message with pertanent information before
     * sending it to the logger
     *
     * @param	string	$message	Message to log
     */
    public function log($message)
    {
        if (REQ == 'CLI' && CLI_VERBOSE) {
            $this->stdout($message);
        }

        $message = '[' . date('Y-M-d H:i:s O') . '] ' . $message;

        parent::log($message);
    }

    private function stdout($message)
    {
        $text_color = '[1;37m';

        $arrow_color = '[0;34m';
        $text_color = '[1;37m';

        if (REQ == 'CLI' && ! empty($message)) {
            $message = "\033" . $arrow_color . "==> \033" . $text_color . strip_tags($message) . "\033[0m\n";

            $stdout = fopen('php://stdout', 'w');
            fwrite($stdout, $message);
            fclose($stdout);
        }
    }
}

// EOF
