<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Library\Filesystem;

use ExpressionEngine\Library\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class FilesystemActLocallyTest extends TestCase
{
    public function testLocalFileIsReadWithoutCreatingATemporaryCopy()
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->disableOriginalConstructor()
            ->onlyMethods(['isLocal', 'copyToTempFile'])->getMock();
        $filesystem->method('isLocal')->willReturn(true);
        $filesystem->expects($this->never())->method('copyToTempFile');
        $source = tmpfile();
        $path = stream_get_meta_data($source)['uri'];
        fwrite($source, 'local image');

        try {
            $result = $filesystem->actLocally($path, function ($localPath) use ($path) {
                $this->assertSame($path, $localPath);
                return file_get_contents($localPath);
            });
            $this->assertSame('local image', $result);
            $this->assertTrue(is_resource($source));
        } finally {
            fclose($source);
        }
    }

    /** @dataProvider callbackOutcomes */
    public function testRemoteTemporaryCopyIsClosedAfterCallback($throws)
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->disableOriginalConstructor()
            ->onlyMethods(['isLocal', 'copyToTempFile'])->getMock();
        $filesystem->method('isLocal')->willReturn(false);
        $file = tmpfile();
        $path = stream_get_meta_data($file)['uri'];
        fwrite($file, 'remote image');
        $filesystem->expects($this->once())->method('copyToTempFile')->with('gallery/hero.jpg')
            ->willReturn(['file' => $file, 'path' => $path]);
        $error = new \RuntimeException('dimension read failed');

        try {
            $result = $filesystem->actLocally('gallery/hero.jpg', function ($localPath) use ($path, $throws, $error) {
                $this->assertSame($path, $localPath);
                $this->assertSame('remote image', file_get_contents($localPath));
                if ($throws) {
                    throw $error;
                }
                return ['width' => 200, 'height' => 100];
            });
            $this->assertFalse($throws);
            $this->assertSame(['width' => 200, 'height' => 100], $result);
        } catch (\RuntimeException $caught) {
            $this->assertTrue($throws);
            $this->assertSame($error, $caught);
        } finally {
            try {
                $this->assertFalse(is_resource($file));
                clearstatcache(true, $path);
                $this->assertFileDoesNotExist($path);
            } finally {
                if (is_resource($file)) {
                    fclose($file);
                }
            }
        }
    }

    public function callbackOutcomes()
    {
        return ['success' => [false], 'exception' => [true]];
    }
}
