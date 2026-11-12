<?php

class Cypress_cli_update_ext
{
    public $version = '2.0.0';

    /**
     * Replace the fixture hooks when the version changes.
     *
     * @param string $current
     * @return bool
     */
    public function update_extension($current = '')
    {
        if ($current == $this->version) {
            return false;
        }

        ee('Model')->get('Extension')->filter('class', __CLASS__)->delete();

        foreach (['cypress_cli_new_one', 'cypress_cli_new_two'] as $hook) {
            ee('Model')->make('Extension', [
                'class' => __CLASS__,
                'method' => 'test_hook',
                'hook' => $hook,
                'settings' => [],
                'priority' => 10,
                // Let the updater stamp the version on these replacement rows.
                'version' => $current,
                'enabled' => 'y',
            ])->save();
        }

        return true;
    }
}
