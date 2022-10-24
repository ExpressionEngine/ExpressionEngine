<?php

namespace ExpressionEngine\Library\Filesystem\Adapter;

/**
 * Adapter Interface
 *
 * Sets the pattern for filesystem adapters
 */
interface AdapterInterface
{
    /**
     * Get adapter settings
     *
     * @param array $settings saved settings for adapter
     * @return array data in format that can be passed to shared for view
     */
    public static function getSettingsForm($settings);
}
