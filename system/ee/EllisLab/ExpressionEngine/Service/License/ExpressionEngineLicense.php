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

	protected $data = array();
	protected $signature;
	protected $pubkey;

	public function isValid()
	{
		if ( ! $this->isSigned())
		{
			return FALSE;
		}

		if ( ! $this->signatureIsValid())
		{
			return FALSE;
		}

		return $this->validLicenseNumber();
	}

	public function canAddSites()
	{
		if ( ! $this->isValid())
		{
			return FALSE;
		}

		// @TODO: Inject this!
		$sites = ee('Model')->get('Site')->count();

		return ($sites < $this->data['sites']);
	}

	protected function validLicenseNumber()
	{
		$license = $this->data['license_number'];

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