<?php
namespace EllisLab\ExpressionEngine\Library;

class EmailLibrary {
	protected static $instance = NULL;


	public static function getInstance()
	{
		if ( ! isset(self::$instance))
		{
			self::$instance = new EmailLibrary();
		}
		return self::$instance;
	}

	public function isValidEmail($email)
	{
		return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email)) ? FALSE : TRUE;
	}
}
