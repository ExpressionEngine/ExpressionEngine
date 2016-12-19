<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Encrypt;

use Mockery as m;
use EllisLab\ExpressionEngine\Service\Encrypt;

class EncryptTest extends \PHPUnit_Framework_TestCase {

	protected $driver;

	public function setUp()
	{
		$this->driver = m::mock(new EncryptionTestDriver());
	}

	public function tearDown()
	{
		m::close();
	}

	public function testGetDriver()
	{
		$encrypt = new Encrypt\Encrypt($this->driver, "SomeDefaultKey");
		$this->assertEquals($encrypt->getDriver(), $this->driver);
	}

	public function testEncodeWithDefaultKey()
	{
		$text = "ExpressionEngine";
		$key = "EllisLab";
		$return = "3xpr35510n3ng1n3";

		$this->driver->shouldReceive('encode')
			->with($text, $key)
			->andReturn($return);

		$encrypt = new Encrypt\Encrypt($this->driver, $key);
		$this->assertEquals($encrypt->encode($text), $return);
	}

	public function testEncodeWithKey()
	{
		$text = "ExpressionEngine";
		$key = "EllisLab";
		$return = "3xpr35510n3ng1n3";

		$this->driver->shouldReceive('encode')
			->with($text, $key)
			->andReturn($return);

		$encrypt = new Encrypt\Encrypt($this->driver, "SomeDefaultKey");
		$this->assertEquals($encrypt->encode($text, $key), $return);
	}

	public function testDecodeWithDefaultKey()
	{
		$text = "3xpr35510n3ng1n3";
		$key = "EllisLab";
		$return = "ExpressionEngine";

		$this->driver->shouldReceive('decode')
			->with($text, $key)
			->andReturn($return);

		$encrypt = new Encrypt\Encrypt($this->driver, $key);
		$this->assertEquals($encrypt->decode($text), $return);
	}

	public function testDecodeWithKey()
	{
		$text = "3xpr35510n3ng1n3";
		$key = "EllisLab";
		$return = "ExpressionEngine";

		$this->driver->shouldReceive('decode')
			->with($text, $key)
			->andReturn($return);

		$encrypt = new Encrypt\Encrypt($this->driver, "SomeDefaultKey");
		$this->assertEquals($encrypt->decode($text, $key), $return);
	}

	public function testSign()
	{
		$text = "Language";

		$encrypt = new Encrypt\Encrypt($this->driver, "SomeDefaultKey");
		$this->assertTrue($encrypt->sign($text) != $text);
		$this->assertTrue($encrypt->sign($text, "Skelington") != $text);
		$this->assertTrue($encrypt->sign($text, "Skelington", "sha1") != $text);
	}

	public function testSignNoData()
	{
		$encrypt = new Encrypt\Encrypt($this->driver, "SomeDefaultKey");
		$this->assertNull($encrypt->sign(''));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testSignUnknownAlgorithm()
	{
		$encrypt = new Encrypt\Encrypt($this->driver, "SomeDefaultKey");
		$encrypt->sign('Hi', NULL, 'FooBarAlgorithm');
	}

	public function testVerifySignature()
	{
		$encrypt = new Encrypt\Encrypt($this->driver, "SomeDefaultKey");

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
		$encrypt = new Encrypt\Encrypt($this->driver, "SomeDefaultKey");

		$text = "age";
		$signature = $encrypt->sign($text);
		$this->assertNull($encrypt->verifySignature('', $signature));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testVerifySignatureUnknownAlgorithm()
	{
		$encrypt = new Encrypt\Encrypt($this->driver, "SomeDefaultKey");
		$encrypt->verifySignature('Hi', 'John Hancock', NULL, 'FooBarAlgorithm');
	}
}

class EncryptionTestDriver implements Encrypt\Driver {
	public function encode($string, $key) {}
	public function decode($data, $key) {}
	public function setHashObject($obj) {}
}