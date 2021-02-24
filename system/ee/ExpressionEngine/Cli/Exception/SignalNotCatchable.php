<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */

namespace ExpressionEngine\Exception;

use ExpressionEngine\Cli\Exception as CliException;

/**
 *
 * Trying to catch a signal that isn't catchable or that isn't a signal.
 *
 * @package Aura.Cli
 *
 */
class SignalNotCatchable extends CliException
{
}
