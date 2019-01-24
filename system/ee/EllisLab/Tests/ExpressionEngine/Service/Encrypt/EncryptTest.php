<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Service\Encrypt;

use EllisLab\ExpressionEngine\Service\Encrypt;
use PHPUnit\Framework\TestCase;

class EncryptTest extends TestCase {

	protected $base64_regex = '#^([A-Za-z0-9+/]{4})*([A-Za-z0-9+/]{4}|[A-Za-z0-9+/]{3}=|[A-Za-z0-9+/]{2}==)$#';

	public function testEncodeWithDefaultKey()
	{
		$text = "ExpressionEngine";
		$key = "EllisLab";

		$encrypt = new Encrypt\Encrypt($key);
		$encoded = $encrypt->encode($text);

		$this->assertTrue($encoded != $text);
		$this->assertTrue(preg_match($this->base64_regex, $encoded) == 1);
	}

	public function testEncodeWithKey()
	{
		$text = "ExpressionEngine";
		$key = "EllisLab";

		$encrypt = new Encrypt\Encrypt("SomeDefaultKey");
		$encoded = $encrypt->encode($text, $key);

		$this->assertTrue($encoded != $text);
		$this->assertTrue(preg_match($this->base64_regex, $encoded) == 1);
	}

	public function testDecodeWithDefaultKey()
	{
		$text = "ExpressionEngine";
		$key = "EllisLab";

		$encrypt = new Encrypt\Encrypt($key);
		$encoded = $encrypt->encode($text);
		$this->assertEquals($encrypt->decode($encoded), $text);
	}

	public function testDecodeWithKey()
	{
		$text = "ExpressionEngine";
		$key = "EllisLab";

		$encrypt = new Encrypt\Encrypt("SomeDefaultKey");
		$encoded = $encrypt->encode($text, $key);
		$this->assertEquals($encrypt->decode($encoded, $key), $text);
	}

	public function testEncryptWithDefaultKey()
	{
		$text = "ExpressionEngine";
		$key = "EllisLab";

		$encrypt = new Encrypt\Encrypt($key);
		$this->assertTrue($encrypt->encrypt($text) != $text);
	}

	public function testEncryptWithKey()
	{
		$text = "ExpressionEngine";
		$key = "EllisLab";

		$encrypt = new Encrypt\Encrypt("SomeDefaultKey");
		$this->assertTrue($encrypt->encrypt($text, $key) != $text);
	}

	public function testDecryptWithDefaultKey()
	{
		$text = "ExpressionEngine";
		$key = "EllisLab";

		$encrypt = new Encrypt\Encrypt($key);
		$encrypted = $encrypt->encrypt($text);
		$this->assertEquals($encrypt->decrypt($encrypted), $text);
	}

	public function testDecryptWithKey()
	{
		$text = "ExpressionEngine";
		$key = "EllisLab";

		$encrypt = new Encrypt\Encrypt("SomeDefaultKey");
		$encrypted = $encrypt->encrypt($text, $key);
		$this->assertEquals($encrypt->decrypt($encrypted, $key), $text);
	}

	public function testDecryptEncodedDataWithDefaultKey()
	{
		$text = "ExpressionEngine";
		$key = "EllisLab";

		$encrypt = new Encrypt\Encrypt($key);
		$encoded = $encrypt->encode($text);
		$this->assertTrue($encrypt->decrypt($encoded) != $text);
	}

	public function testDecryptEncodedDataWithKey()
	{
		$text = "ExpressionEngine";
		$key = "EllisLab";

		$encrypt = new Encrypt\Encrypt("SomeDefaultKey");
		$encoded = $encrypt->encode($text, $key);
		$this->assertTrue($encrypt->decrypt($encoded, $key) != $text);
	}

	public function testKeys()
	{
		$text = "ExpressionEngine";
		$key = "EllisLab";

		$encrypt = new Encrypt\Encrypt("SomeDefaultKey");

		$encrypted = $encrypt->encrypt($text);
		$this->assertTrue($encrypt->decrypt($encrypted, $key) != $text);

		$encrypted = $encrypt->encrypt($text, $key);
		$this->assertTrue($encrypt->decrypt($encrypted) != $text);
	}

	public function testSign()
	{
		$text = "Language";

		$encrypt = new Encrypt\Encrypt("SomeDefaultKey");
		$this->assertTrue($encrypt->sign($text) != $text);
		$this->assertTrue($encrypt->sign($text, "Skelington") != $text);
		$this->assertTrue($encrypt->sign($text, "Skelington", "sha1") != $text);
	}

	public function testSignNoData()
	{
		$encrypt = new Encrypt\Encrypt("SomeDefaultKey");
		$this->assertNull($encrypt->sign(''));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testSignUnknownAlgorithm()
	{
		$encrypt = new Encrypt\Encrypt("SomeDefaultKey");
		$encrypt->sign('Hi', NULL, 'FooBarAlgorithm');
	}

	public function testVerifySignature()
	{
		$encrypt = new Encrypt\Encrypt("SomeDefaultKey");

		$text = "age";

		// Valid
		$signature = $encrypt->sign($text);
		$this->assertTrue($encrypt->verifySignature($text, $signature));

		$signature = $encrypt->sign($text, "Skeleton");
		$this->assertTrue($encrypt->verifySignature($text, $signature, "Skeleton"));

		$signature = $encrypt->sign($text, "Skeleton", "sha1");
		$this->assertTrue($encrypt->verifySignature($text, $signature, "Skeleton", "sha1"));

		// Invalid
		$signature = $encrypt->sign($text);
		$this->assertFalse($encrypt->verifySignature($text, "John Hancock"));
		$this->assertFalse($encrypt->verifySignature("Language", $signature));

		$signature = $encrypt->sign($text, "Skeleton");
		$this->assertFalse($encrypt->verifySignature($text, "John Hancock"));
		$this->assertFalse($encrypt->verifySignature($text, $signature));
		$this->assertFalse($encrypt->verifySignature($text, $signature, "WrongKey"));

		$signature = $encrypt->sign($text, "Skeleton", "sha1");
		$this->assertFalse($encrypt->verifySignature($text, "John Hancock"));
		$this->assertFalse($encrypt->verifySignature($text, $signature));
		$this->assertFalse($encrypt->verifySignature($text, $signature, "WrongKey"));
		$this->assertFalse($encrypt->verifySignature($text, $signature, "Skeleton", "md5"));
	}

	public function testVerifySignatureNoData()
	{
		$encrypt = new Encrypt\Encrypt("SomeDefaultKey");

		$text = "age";
		$signature = $encrypt->sign($text);
		$this->assertNull($encrypt->verifySignature('', $signature));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testVerifySignatureUnknownAlgorithm()
	{
		$encrypt = new Encrypt\Encrypt("SomeDefaultKey");
		$encrypt->verifySignature('Hi', 'John Hancock', NULL, 'FooBarAlgorithm');
	}
}
