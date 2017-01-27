<?php
use EllisLab\ExpressionEngine\Service\Encrypt;

define('MB_ENABLED', FALSE);
include_once(APPPATH.'libraries/Encrypt.php');

function ee($str)
{
	return new Encrypt\Encrypt("ADefaultKey");
}

class EncryptTest extends \PHPUnit_Framework_TestCase {

	public function testDecodeOfMcryptedData()
	{
		$text = "ExpressionEngine";
		$key = "EllisLab";

		$legacy = new EE_Encrypt();

		$encoded = base64_encode($legacy->mcrypt_encode($text, md5($key)));
		$this->assertEquals($legacy->decode($encoded, $key), $text);
	}
}