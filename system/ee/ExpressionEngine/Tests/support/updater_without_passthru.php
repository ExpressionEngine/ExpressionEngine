<?php

namespace ExpressionEngine\Updater\Service\Updater;

/**
 * Simulate PHP running with passthru disabled.
 *
 * @param string $name The function name to check.
 * @return bool
 */
function function_exists($name)
{
    return $name !== 'passthru' && \function_exists($name);
}

/**
 * Fail if the updater attempts to start a subprocess.
 *
 * @param string $command The command to run.
 * @param int|null $status The subprocess exit status.
 * @return void
 * @throws \LogicException Always, because this fixture disables passthru.
 */
function passthru($command, &$status)
{
    throw new \LogicException('The updater attempted to call disabled passthru.');
}
