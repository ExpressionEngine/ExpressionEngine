<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Encrypt;

use Mockery as m;
use EllisLab\ExpressionEngine\Service\Encrypt;


class OpenSSL extends \PHPUnit_Framework_TestCase {

	protected $driver;

	public function setUp()
	{
		$this->driver = new Encrypt\Drivers\OpenSSL();
	}

	public function tearDown()
	{
		m::close();
	}

	public function testEncode()
	{
		$string = "Plaintext";
		$key    = "skelington";
		$this->assertTrue($this->driver->encode($string, $key) != $string);
	}

	public function testDecode()
	{
		$string  = "Plaintext";
		$key     = "skelington";
		$encoded = $this->driver->encode($string, $key);
		$this->assertEquals($this->driver->decode($encoded, $key), $string);
	}
}