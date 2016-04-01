<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Updater\Verifier;
use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use Mockery;

class VerifierTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->filesystem = Mockery::mock('EllisLab\ExpressionEngine\Library\Filesystem\Filesystem');

		$this->verifier = new Verifier($this->filesystem);
	}

	public function tearDown()
	{
		$this->filesystem = NULL;
		$this->verifier = NULL;
	}

	public function testVerifyPath()
	{
		$hashmap = array(
			'some/file.ext' => '7306a81f37ed094bf8a8d61aee3b795f5c51e501',
			'some/file2.ext' => '23730c203df385026e5604a77a9675094d5f3acc',
			'some/file3.ext' => '9b1fea0170c2baa1ab29d07e185db04afed839c7'
		);

		$this->filesystem->shouldReceive('read')
			->with('manifest/path')
			->andReturn($this->createHashmapString($hashmap))
			->once();

		foreach ($hashmap as $file => $hash)
		{
			$file = 'some/path/'.$file;
			$this->filesystem->shouldReceive('exists')->with($file)->andReturn(TRUE)->once();
			$this->filesystem->shouldReceive('sha1File')->with($file)->andReturn($hash)->once();
		}

		$this->assertEquals(TRUE, $this->verifier->verifyPath('some/path', 'manifest/path'));
	}

	public function createHashmapString(Array $hashmap)
	{
		$string = "";
		foreach ($hashmap as $file => $hash)
		{
			$string .= "$hash $file\n";
		}

		return $string;
	}
}
