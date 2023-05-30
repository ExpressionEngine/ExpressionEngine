<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Memory;

/**
 * Memory Service
 */
class Memory
{
    /**
     * Set memory for image manipulation functions
     *
     *   See http://www.php.net/manual/en/function.imagecreatetruecolor.php#99623
     *   for info about the tweak multiplier. We found 1.8 to work for most images,
     *   for resizing, etc., but especially memory creation on large complex images
     *   you may need to increase this multiplier to give some breathing room.
     *
     * @param string $file_path           path to the image file
     * @param float  $tweak_multiplier    multiplier for estimated memory usage
     * @return void throws exception on failure
     */
    public function setMemoryForImageManipulation($file_path, $tweak_multiplier = 1.8)
    {
        $memory_limit = $this->getMemoryLimitBytes();

        if ($memory_limit === -1) {
            return;
        }

        //do not proceed if the file is not an image
        try {
            $info = getimagesize($file_path);
        } catch (\Exception $e) {
            return false;
        }

        if ($info === false) {
            return false;
        }

        // assume 4 color channels to be safe if we don't have it
        $channels = (isset($info['channels'])) ? $info['channels'] : 4;

        // ((pixels x channels) + 64k padding) * our memory tweak multiplier
        $estimated_memory = round(((($info[0] * $info[1]) * $channels) + 65536) * $tweak_multiplier);
        $memory_needed = memory_get_usage(true) + $estimated_memory;

        if ($memory_needed > $memory_limit) {
            // make sure we use an integer format instead of float/scientific for large numbers
            // we add to the current limit instead of just increasing to what is needed, as each
            // operation will increase the memory usage, e.g. creation, resize, crop.
            $new_memory = number_format(ceil($memory_needed + $memory_limit), 0, '.', '');

            if (! ini_set('memory_limit', $new_memory)) {
                throw new \Exception("Unable to increase memory to {$new_memory} bytes needed to process this image. (Current limit: {$memory_limit} bytes)");
            }
        }
    }

    /**
     * Get Memory Limit in Bytes
     *
     * @return int Current memory limit, in bytes. (int -1 means no limit)
     */
    public function getMemoryLimitBytes()
    {
        $memory_ini_setting = ini_get('memory_limit');

        // this would be odd, but let's set to our minimum sys requirements if ini_get gave us nada
        if (! $memory_ini_setting) {
            $memory_ini_setting = '32M';
        }

        list($memory_limit, $unit) = sscanf($memory_ini_setting, "%d%s");

        switch (strtolower((string) $unit)) {
            // no breaks so it's progressively multiplied as needed
            case 'g':
                $memory_limit *= 1024;
                // no break
            case 'm':
                $memory_limit *= 1024;
                // no break
            case 'k':
                $memory_limit *= 1024;
        }

        return $memory_limit;
    }
}

// EOF
