<?php
namespace EllisLab\ExpressionEngine\Model\Error;

/**
 * A class representing a validation or execution error that
 * is intended for user consumption.  The class wraps a simple
 * error message that is assigned on construction and may be
 * retrieved for presentation using Error::getMessage().  It
 * does nothing with language files.
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
