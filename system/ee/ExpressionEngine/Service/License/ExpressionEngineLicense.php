<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\License;

/**
 * ExpressionEngine License Service
 */
class ExpressionEngineLicense extends License
{
    /**
     * Overrides the parent isValid check to add an additional check to ensure
     * the license number matches the correct patterns.
     *
     * @see License::isValid()
     * @return bool TRUE if the license is valid, FALSE if not.
     */
    public function isValid()
    {
        if (parent::isValid() === false) {
            return false;
        }

        $valid = $this->validLicenseNumber();

        if (! $valid) {
            $this->errors['invalid_license_number'] = "The license number is invalid";
        }

        return $valid;
    }

    /**
     * Checks the license against the argument to determine if a site can
     * be added.
     *
     * @param int $current_number_of_site The number of defined sites
     * @return bool TRUE if a site can be added, FALSE if not.
     */
    public function canAddSites($current_number_of_sites)
    {
        return true;
    }

    /**
     * Validates the license number in the license file
     *
     * @return bool TRUE if a site can be added, FALSE if not.
     */
    protected function validLicenseNumber()
    {
        $license = $this->getData('license_number');

        if (count(count_chars(str_replace('-', '', $license), 1)) == 1 or $license == '1234-1234-1234-1234') {
            return false;
        }

        if (! preg_match('/^[\d]{4}-[\d]{4}-[\d]{4}-[\d]{4}$/', $license)) {
            return false;
        }

        return true;
    }
}
