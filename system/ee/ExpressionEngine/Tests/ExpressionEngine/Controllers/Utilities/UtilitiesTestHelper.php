<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Utilities;

/**
 * Raised when a Utilities controller denies a request during a test.
 */
class UtilitiesShowErrorException extends \RuntimeException
{
}

/**
 * Replace the controller error response with an exception.
 *
 * @param string $message
 * @param int $status
 * @return void
 *
 * @throws UtilitiesShowErrorException
 */
function show_error($message, $status = 500)
{
    throw new UtilitiesShowErrorException((string) $message, $status);
}
