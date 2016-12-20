<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Encrypt;

use Mockery as m;
use EllisLab\ExpressionEngine\Service\Encrypt;

define('MB_ENABLED', FALSE);

class McryptTest extends \PHPUnit_Framework_TestCase {

	protected $driver;

	public function setUp()
	{
		if ( ! extension_loaded('mcrypt'))
		{
			$this->markTestSkipped('Mcrypt is not available');
		}

		$hashed = sha1('browns');

		$hash = m::mock('hash')
			->shouldReceive('hash')
			->andReturn($hashed)
			->mock();
		$this->driver = new Encrypt\Drivers\Mcrypt();
		$this->driver->setHashObject($hash);
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