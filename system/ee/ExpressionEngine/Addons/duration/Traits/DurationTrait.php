<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Duration\Traits;

trait DurationTrait
{
    /**
     * Convert from ##:##:## notation
     * @param  string $duration Duration, in ##:##:## notation
     * @return int Duration, in terms of the field's units
     */
    private function convertFromColonNotation($duration, $units = 'minutes')
    {
        $parts = explode(':', $duration);

        switch (count($parts)) {
            // hh:mm:ss
            case 3:
                $seconds = ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];

                break;
            // mm:ss
            case 2:
                $seconds = ($parts[0] * 60) + $parts[1];

                // if they input ##:## with a "minutes" field, the implied format is hh:mm rather than mm:ss
                if ($units == 'minutes') {
                    $seconds = $seconds * 60;
                }

                break;
            // ss
            case 1:
            default:
                $seconds = $parts[0];

                break;
        }

        return $seconds;
    }

    /**
     * Apply a multiplier based on the field's units setting
     *
     * @param  int $number Number to apply the multiplier to
     * @return int Duration, in terms of the field's units
     */
    private function applyMultiplier($number, $units = 'minutes')
    {
        if (!is_numeric($number)) {
            return 0;
        }
        switch ($units) {
            case 'hours':
                $multiplier = 3600;

                break;
            case 'minutes':
                $multiplier = 60;

                break;
            case 'seconds':
            default:
                $multiplier = 1;

                break;
        }

        return $number * $multiplier;
    }

    /**
     * Converts duration to seconds
     *
     * @param  string $duration Duration, in arbitrary notation
     * @return int Duration, in seconds
     */
    private function convertDurationToSeconds($duration, $units = 'minutes')
    {
        if (strpos($duration, ':')) {
            $duration = $this->convertFromColonNotation($duration, $units);
        } else {
            $duration = $this->applyMultiplier($duration, $units);
        }

        return $duration;
    }
}
