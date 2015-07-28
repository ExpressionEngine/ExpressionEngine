<?php
namespace EllisLab\ExpressionEngine\Service\License;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class ExpressionEngineLicense extends License {

	public function isValid()
	{
		if (parent::isValid() === FALSE)
		{
			return FALSE;
		}

		return $this->validLicenseNumber();
	}

	public function canAddSites($current_number_of_sites)
	{
		if ( ! $this->isValid() || $current_number_of_sites < 1)
		{
			return FALSE;
		}

		return ($current_number_of_sites < $this->getData('sites'));
	}

	protected function validLicenseNumber()
	{
		$license = $this->getData('license_number');

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

}