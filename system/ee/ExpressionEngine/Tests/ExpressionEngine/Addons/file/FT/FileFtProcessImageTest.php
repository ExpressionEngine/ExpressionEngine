<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Dependency\League\Flysystem\FileExistsException;
use ExpressionEngine\Library\Filesystem\Adapter\Local;
use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Library\Filesystem\FilesystemException;

require_once __DIR__ . '/FileFtTestBase.php';

class FileFtProcessImageTest extends FileFtTestBase
{
    /** @dataProvider sourceCallbackCacheStates */
    public function testProcessImageResolvesSourceOnlyOnCacheMiss($cached)
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $params = ['width' => '1600', 'height' => '900'];
        $filename = 'hero_crop_' . md5(serialize($params)) . '.jpg';
        $destination = '/srv/uploads/gallery/_crop' . DIRECTORY_SEPARATOR . $filename;
        $source = '/srv/uploads/gallery/prepared-source.jpg';
        $filesystem->existingPaths[$destination] = $cached;
        $data = [
            'model_object' => new FileFtProcessImageModelObjectStub(['filesystem' => $filesystem]),
            'fs_filename' => 'hero.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/srv/uploads/gallery/hero.jpg',
        ];
        $calls = 0;
        $resolveSource = function () use (&$calls, $cached, $filesystem, $destination, $source) {
            $this->assertFalse($cached, 'A cached output must not resolve its source dependency.');
            $this->assertSame([$destination], $filesystem->existsCalls);
            $calls++;

            return $source;
        };
        $method = new ReflectionMethod(File_ft::class, 'process_image');
        $method->setAccessible(true);

        $result = $method->invoke($fieldtype, 'crop', $data, $params, false, false, $resolveSource);

        $this->assertSame('https://example.com/uploads/gallery/_crop/' . $filename, $result);
        $this->assertSame($cached ? 0 : 1, $calls);
        $this->assertSame([$destination], $filesystem->existsCalls);
        $this->assertSame($cached ? [] : [$source], $filesystem->copyToTempFileCalls);
        $this->assertSame([], $this->imageLibMock->getImagePropertiesCalls);
        $this->assertSame($cached ? [] : ['crop'], $this->imageLibMock->actionCalls);
        $this->assertSame($cached ? [] : [$destination], array_column($filesystem->writeStreamCalls, 'path'));
    }

    public function sourceCallbackCacheStates()
    {
        return [
            'cache miss' => [false],
            'cache hit' => [true],
        ];
    }

    /** @dataProvider cachedDimensionModes */
    public function testCachedDimensionsUseLocalPathOrRemoteCopy($local, $tagdata)
    {
        $filesystem = new FileFtProcessImageFilesystemStub();
        $filesystem->local = $local;
        $destination = $this->cropDestination();
        $filesystem->existingPaths[$destination] = true;

        $result = $this->makeFieldtype()->replace_crop($this->imageData($filesystem), ['width' => '160'], $tagdata);

        $this->assertSame([$destination], $filesystem->actLocallyCalls);
        $this->assertSame($local ? [] : [$destination], $filesystem->copyToTempFileCalls);
        $propertiesPath = $this->imageLibMock->getImagePropertiesCalls[0][0];
        $this->assertSame($local ? $destination : $filesystem->temporaryFiles[0]['path'], $propertiesPath);
        $this->assertSame([], $this->imageLibMock->actionCalls);
        if ($tagdata === null) {
            $this->assertSame(200, $result['width']);
            $this->assertSame(100, $result['height']);
        } else {
            $this->assertSame(200, $this->templateMock->parseVariablesCalls[0]['variables'][0]['width']);
        }
        $this->assertTemporaryFilesClosed($filesystem);
    }

    public function cachedDimensionModes()
    {
        return [
            'local chain' => [true, null],
            'remote chain' => [false, null],
            'local tag pair' => [true, '{width}x{height}'],
            'remote tag pair' => [false, '{width}x{height}'],
        ];
    }

    /** @dataProvider cachedDimensionModes */
    public function testDisappearingCacheFallsBackWithoutResolvingSource($local, $tagdata)
    {
        $filesystem = new FileFtProcessImageFilesystemStub();
        $filesystem->local = $local;
        $destination = $this->cropDestination();
        $filesystem->existingPaths[$destination] = true;
        if ($local) {
            $this->imageLibMock->imagePropertiesByPath[$destination] = false;
        } else {
            $filesystem->copyToTempFileExceptions[$destination] = 'cached image disappeared';
        }
        $resolveSource = function () {
            $this->fail('A failed cache read must not regenerate against stale filesystem metadata.');
        };
        $method = new ReflectionMethod(File_ft::class, 'process_image');
        $method->setAccessible(true);

        $result = $method->invoke(
            $this->makeFieldtype(), 'crop', $this->imageData($filesystem), ['width' => '160'],
            $tagdata, false, $resolveSource
        );

        $this->assertSame('https://example.com/uploads/gallery/hero.jpg', $result);
        $this->assertSame([], $this->imageLibMock->actionCalls);
        $this->assertSame([], $filesystem->writeStreamCalls);
        $this->assertSame($local ? [] : [$destination], $filesystem->copyToTempFileCalls);
        $this->assertTemporaryFilesClosed($filesystem);
    }

    public function testMissingLocalCacheFallsBackDespiteCachedExistenceMetadata()
    {
        $directory = sys_get_temp_dir() . '/file-ft-disappeared-' . uniqid();
        mkdir($directory . '/_crop', 0700, true);
        $source = $directory . '/hero.jpg';
        $destination = $directory . '/_crop' . DIRECTORY_SEPARATOR . basename($this->cropDestination());
        file_put_contents($source, 'source image');
        file_put_contents($destination, 'cached crop');
        $filesystem = new Filesystem(new Local(['path' => $directory]));
        $model = new FileFtProcessImageModelObjectStub([
            'filesystem' => $filesystem,
            'absolutePath' => $source,
            'baseServerPath' => $directory,
            'subfoldersPath' => '',
        ]);

        try {
            $this->assertTrue($filesystem->exists($destination));
            unlink($destination);
            clearstatcache(true, $destination);
            $this->assertTrue($filesystem->exists($destination), 'The filesystem retains cached existence metadata.');
            $this->imageLibMock->imagePropertiesByPath[$destination] = false;

            $result = $this->makeFieldtype()->replace_crop(['model_object' => $model], ['width' => '160'], null);

            $this->assertSame($model->getAbsoluteURL(), $result);
            $this->assertSame([[$destination, true]], $this->imageLibMock->getImagePropertiesCalls);
            $this->assertSame([], $this->imageLibMock->actionCalls);
        } finally {
            unlink($source);
            if (is_file($destination)) {
                unlink($destination);
            }
            rmdir($directory . '/_crop');
            rmdir($directory);
        }
    }

    public function testNewImageDimensionsUseTheExistingTemporaryOutput()
    {
        $filesystem = new FileFtProcessImageFilesystemStub();

        $result = $this->makeFieldtype()->replace_crop($this->imageData($filesystem), ['width' => '160'], null);

        $this->assertSame(200, $result['width']);
        $this->assertSame(100, $result['height']);
        $this->assertSame([], $filesystem->actLocallyCalls);
        $this->assertSame(['/srv/uploads/gallery/hero.jpg'], $filesystem->copyToTempFileCalls);
        $this->assertSame([
            [$this->imageLibMock->initializeCalls[0]['new_image'], true],
        ], $this->imageLibMock->getImagePropertiesCalls);
        $this->assertTemporaryFilesClosed($filesystem);
    }

    public function testTemporaryOutputCreationFailureClosesSourceCopy()
    {
        $filesystem = new class extends FileFtProcessImageFilesystemStub {
            public function createTempFile()
            {
                throw new FilesystemException('cannot create output');
            }
        };

        try {
            $this->expectException(FilesystemException::class);
            $this->expectExceptionMessage('cannot create output');
            $this->makeFieldtype()->replace_crop($this->imageData($filesystem), ['width' => '160']);
        } finally {
            $this->assertCount(1, $filesystem->temporaryFiles);
            $this->assertTemporaryFilesClosed($filesystem);
        }
    }

    /** @dataProvider competingWrites */
    public function testCompetingCacheWriteIsAcceptedOnlyIfDestinationExists($winnerExists, $tagdata)
    {
        $filesystem = new FileFtProcessImageFilesystemStub();
        $destination = $this->cropDestination();
        $exception = new FileExistsException($destination);
        $writeStream = null;
        $filesystem->writeStreamCallback = function ($path, $stream) use (
            $filesystem, $exception, $winnerExists, &$writeStream
        ) {
            $writeStream = $stream;
            $filesystem->existingPaths[$path] = $winnerExists;
            throw $exception;
        };

        try {
            $result = $this->makeFieldtype()->replace_crop($this->imageData($filesystem), ['width' => '160'], $tagdata);
            $this->assertTrue($winnerExists);
            $url = 'https://example.com/uploads/gallery/_crop/' . basename($destination);
            $this->assertSame($url, $tagdata === null ? $result['url'] : $result);
            if ($tagdata === null) {
                $this->assertSame(200, $result['width']);
            }
        } catch (FileExistsException $caught) {
            $this->assertFalse($winnerExists);
            $this->assertSame($exception, $caught);
        } finally {
            $this->assertFalse(is_resource($writeStream));
            $this->assertTemporaryFilesClosed($filesystem);
        }
        $this->assertSame([], $filesystem->ensureCorrectAccessModeCalls);
    }

    public function competingWrites()
    {
        return [
            'winner URL' => [true, false],
            'winner dimensions' => [true, null],
            'winner disappeared' => [false, false],
        ];
    }

    /** @dataProvider generationFailures */
    public function testGenerationFailuresReleaseTemporaryResources($failure)
    {
        $filesystem = new FileFtProcessImageFilesystemStub();
        $writeStream = null;
        if ($failure === 'operation') {
            $this->imageLibMock->actionResults['crop'] = false;
        } else {
            $filesystem->writeStreamCallback = function ($path, $stream) use (&$writeStream) {
                $writeStream = $stream;
                throw new FilesystemException('write failed');
            };
        }

        try {
            $result = $this->makeFieldtype()->replace_crop($this->imageData($filesystem), ['width' => '160']);
            $this->assertSame('operation', $failure);
            $this->assertSame('NO_RESULTS', $result);
        } catch (FilesystemException $exception) {
            $this->assertSame('write', $failure);
            $this->assertSame('write failed', $exception->getMessage());
        } finally {
            $this->assertFalse(is_resource($writeStream));
            $this->assertTemporaryFilesClosed($filesystem);
        }
    }

    public function generationFailures()
    {
        return ['image operation' => ['operation'], 'storage write' => ['write']];
    }

    private function cropDestination()
    {
        return '/srv/uploads/gallery/_crop' . DIRECTORY_SEPARATOR
            . 'hero_crop_' . md5(serialize(['width' => '160'])) . '.jpg';
    }

    private function imageData($filesystem)
    {
        return [
            'model_object' => new FileFtProcessImageModelObjectStub(['filesystem' => $filesystem]),
            'filesystem' => $filesystem,
            'source_image' => '/srv/uploads/gallery/hero.jpg',
            'fs_filename' => 'hero.jpg',
        ];
    }

    private function assertTemporaryFilesClosed($filesystem)
    {
        foreach ($filesystem->temporaryFiles as $tmp) {
            $this->assertFalse(is_resource($tmp['file']), 'Temporary image handle must be closed.');
        }
    }
}
