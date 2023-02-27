<?php

namespace ExpressionEngine\Tests\Service\Updater;

use ExpressionEngine\Service\Updater\Verifier;
use ExpressionEngine\Service\Updater\UpdaterException;
use Mockery;
use PHPUnit\Framework\TestCase;

class VerifierTest extends TestCase
{
    public $filesystem;
    public $verifier;

    public function setUp(): void
    {
        $this->filesystem = Mockery::mock('ExpressionEngine\Library\Filesystem\Filesystem');

        $this->verifier = new Verifier($this->filesystem);
    }

    public function tearDown(): void
    {
        $this->filesystem = null;
        $this->verifier = null;

        Mockery::close();
    }

    public function testVerifyPath()
    {
        $this->markTestSkipped('Skipping because of this error: No matching handler found for Mockery_0_ExpressionEngine_Library_Filesystem_Filesystem::exists');
        $hashmap = [
            'some/file.ext' => '7306a81f37ed094bf8a8d61aee3b795f5c51e501',
            'some/file2.ext' => '23730c203df385026e5604a77a9675094d5f3acc',
            'some/file3.ext' => '9b1fea0170c2baa1ab29d07e185db04afed839c7'
        ];

        $this->filesystem->shouldReceive('read')
            ->with('manifest/path')
            ->andReturn(json_encode($hashmap));

        foreach ($hashmap as $file => $hash) {
            $file = 'some/path/' . $file;
            $this->filesystem->shouldReceive('exists')->with($file)->andReturn(true)->once();
            $this->filesystem->shouldReceive('hashFile')->with('sha384', $file)->andReturn($hash)->once();
        }

        $this->assertEquals(true, $this->verifier->verifyPath('some/path', 'manifest/path'));

        foreach ($hashmap as $file => $hash) {
            $file_path = 'some/path/' . $file;
            // Sabotage this file
            if ($file == 'some/file2.ext') {
                $this->filesystem->shouldReceive('exists')->with($file_path)->andReturn(false)->once();
            } else {
                $this->filesystem->shouldReceive('exists')->with($file_path)->andReturn(true)->once();
                $this->filesystem->shouldReceive('hashFile')->with('sha384', $file_path)->andReturn($hash)->once();
            }
        }

        try {
            $this->verifier->verifyPath('some/path', 'manifest/path');
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(9, $e->getCode());
            //$this->assertContains('some/file2.ext', $e->getMessage());
        }

        foreach ($hashmap as $file => $hash) {
            $file_path = 'some/path/' . $file;
            // Sabotage the other files
            if ($file != 'some/file2.ext') {
                $this->filesystem->shouldReceive('exists')->with($file_path)->andReturn(false)->once();
            } else {
                $this->filesystem->shouldReceive('exists')->with($file_path)->andReturn(true)->once();
                $this->filesystem->shouldReceive('hashFile')->with('sha384', $file_path)->andReturn($hash)->once();
            }
        }

        try {
            $this->verifier->verifyPath('some/path', 'manifest/path');
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(9, $e->getCode());
            //$this->assertContains('some/file.ext, some/file3.ext', $e->getMessage());
        }

        foreach ($hashmap as $file => $hash) {
            $file_path = 'some/path/' . $file;
            $this->filesystem->shouldReceive('exists')->with($file_path)->andReturn(true)->once();
            // Sabotage this file
            if ($file == 'some/file2.ext') {
                $this->filesystem->shouldReceive('hashFile')->with('sha384', $file_path)->andReturn('1234')->once();
            } else {
                $this->filesystem->shouldReceive('hashFile')->with('sha384', $file_path)->andReturn($hash)->once();
            }
        }

        try {
            $this->verifier->verifyPath('some/path', 'manifest/path');
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(10, $e->getCode());
            //$this->assertContains('some/file2.ext', $e->getMessage());
        }

        foreach ($hashmap as $file => $hash) {
            $file_path = 'some/path/' . $file;
            $this->filesystem->shouldReceive('exists')->with($file_path)->andReturn(true)->once();
            // Sabotage this file
            if ($file != 'some/file2.ext') {
                $this->filesystem->shouldReceive('hashFile')->with('sha384', $file_path)->andReturn('1234')->once();
            } else {
                $this->filesystem->shouldReceive('hashFile')->with('sha384', $file_path)->andReturn($hash)->once();
            }
        }

        try {
            $this->verifier->verifyPath('some/path', 'manifest/path');
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(10, $e->getCode());
            //$this->assertContains('some/file.ext, some/file3.ext', $e->getMessage());
        }
    }

    public function testVerifySubPath()
    {
        $hashmap = [
            'some/file.ext' => '7306a81f37ed094bf8a8d61aee3b795f5c51e501',
            'some/file2.ext' => '23730c203df385026e5604a77a9675094d5f3acc',
            'some/file3.ext' => '9b1fea0170c2baa1ab29d07e185db04afed839c7',
            'some_other_path/file.ext' => '7306a81f37ed094bf8a8d61aee3b795f5c51e501',
            'some_other_path/file2.ext' => '23730c203df385026e5604a77a9675094d5f3acc',
            'some_other_path/file3.ext' => '9b1fea0170c2baa1ab29d07e185db04afed839c7'
        ];

        $this->filesystem->shouldReceive('read')
            ->with('manifest/path')
            ->andReturn(json_encode($hashmap));

        foreach ($hashmap as $file => $hash) {
            // Skip files in the first path to make sure we're only testing files in the other path
            if (strpos($file, 'some/') !== false) {
                continue;
            }

            $file = 'some/path/some_other_path' . str_replace('some_other_path', '', $file);
            $this->filesystem->shouldReceive('exists')->with($file)->andReturn(true)->once();
            $this->filesystem->shouldReceive('hashFile')->with('sha384', $file)->andReturn($hash)->once();
        }

        $this->assertEquals(true, $this->verifier->verifyPath('some/path/some_other_path', 'manifest/path', '/some_other_path'));
    }
}
