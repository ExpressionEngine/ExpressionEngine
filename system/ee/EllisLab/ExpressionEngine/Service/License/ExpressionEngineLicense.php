<?php
namespace EllisLab\ExpressionEngine\Service\License;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine ExpressionEngineLicense Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class ExpressionEngineLicense extends License {

	/**
	 * Overrides the parent isValid check to add an additional check to ensure
	 * the license number matches the correct patterns.
	 *
	 * @see License::isValid()
	 * @return bool TRUE if the license is valid, FALSE if not.
	 */
	public function isValid()
	{
		if (parent::isValid() === FALSE)
		{
			return FALSE;
		}

		$valid = $this->validLicenseNumber();

		if ( ! $valid)
		{
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
		if ( ! $this->isValid() || $current_number_of_sites < 1)
		{
			return FALSE;
		}

		return ($current_number_of_sites < $this->getData('sites'));
	}

	/**
	 * Validates the license number in the license file
	 *
	 * @return bool TRUE if a site can be added, FALSE if not.
	 */
	protected function validLicenseNumber()
	{
		$license = $this->getData('license_number');

		if (IS_CORE)
		{
			return ($license == 'CORE LICENSE');
		}

		if (count(count_chars(str_replace('-', '', $license), 1)) == 1 OR $license == '1234-1234-1234-1234')
		{
			return FALSE;
		}

		if ( ! preg_match('/^[\d]{4}-[\d]{4}-[\d]{4}-[\d]{4}$/', $license))
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Parses the license file and if this is a Core build, forces the license
	 * data to be Core regardless of the data in the license file.
	 *
	 * @see License::parseLicenseFile()
	 * @return void
	 */
	protected function parseLicenseFile()
	{
		parent::parseLicenseFile();

		if (IS_CORE)
		{
			$this->data['license_number'] = 'CORE LICENSE';
			$this->data['sites'] = 1;
		}
	}

}