<?php
namespace EllisLab\ExpressionEngine\Model\Error;

class Error {
	private $message;

	public function __construct($message)
	{
		$this->message = $message;
	}

	public function getMessage()
	{
		return $this->message;
	}

}
