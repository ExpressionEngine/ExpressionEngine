<?php

namespace ExpressionEngine\Updater\Service\Updater;

/**
 * Exercise the real web handoff without replacing files or migrating the test database.
 */
class Runner
{
    public function runStep($step)
    {
        if ($step !== 'updateFiles' || ! is_file(SYSPATH . 'ee/updater/.cypress-handoff')) {
            throw new \RuntimeException('Unexpected updater fixture step.');
        }

        file_put_contents(SYSPATH . 'ee/updater/.cypress-step', $step);
    }

    public function getNextStep()
    {
        return false;
    }

    public function getLanguageForStep($step)
    {
        return 'Updater handoff verified';
    }
}
