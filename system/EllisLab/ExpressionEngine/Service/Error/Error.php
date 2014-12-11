<?php

namespace EllisLab\ExpressionEngine\Service\Error;

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
 * ExpressionEngine Error Class
 *
 * A class representing a validation or execution error that is intended for
 * user consumption.  The class wraps a simple error message that is assigned
 * on construction and may be retrieved for presentation using
 * Error::getMessage().  It does nothing with language files.
 *
 * @package		ExpressionEngine
 * @subpackage	Error
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Error {

	/**
	 * The error message this error wraps.
	 */
	private $message;

	/**
	 * Construct an Error with a message for the user.
	 *
	 * @param 	string 	@message	A language appropriate (IE translated)
	 * 		error message, ready for display to the user.
	 */
	public function __construct($message)
	{
		$this->message = $message;
	}

	/**
	 * Get this Error's message.
	 *
	 * @return	string	A language appropriate (translated)
	 * 		error message, ready for display to the user.
	 */
	public function getMessage()
	{
		return $this->message;
	}

}
