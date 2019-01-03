<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

use EllisLab\ExpressionEngine\Service\Encrypt;

define('MB_ENABLED', FALSE);
include_once(APPPATH.'libraries/Encrypt.php');

class EncryptTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		if (version_compare(PHP_VERSION, '7.1.0', '>='))
		{
			PHPUnit_Framework_Error_Deprecated::$enabled = FALSE;
		}
	}

	public function testDecodeOfMcryptedData()
	{
		ee()->setMock('Encrypt', new Encrypt\Encrypt('ADefaultKey'));

		$text = "ExpressionEngine";
		$key = "EllisLab";

		$legacy = new EE_Encrypt();

		// deprecations in 7.1.0 will still throw visible errors here, but the test will pass
		$encoded = base64_encode($legacy->mcrypt_encode($text, md5($key)));

		$this->assertEquals($legacy->decode($encoded, $key), $text);
	}

	public function tearDown()
	{
		ee()->resetMocks();

		if (version_compare(PHP_VERSION, '7.1.0', '>='))
		{
			PHPUnit_Framework_Error_Deprecated::$enabled = TRUE;
		}
	}
}
