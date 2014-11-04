<?php
namespace EllisLab\ExpressionEngine\Model\Site;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Site Preferences
 *
 * @package		ExpressionEngine
 * @subpackage	Site
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class Preferences {

	public function compress($preferences)
	{
		return base64_encode(serialize($preferences));
	}

	public function decompress($preferences)
	{
		return base64_decode(unserialize($preferences));
	}

	public abstract function populateFromCompressed($preferences);

	public abstract function getCompressed();


}
