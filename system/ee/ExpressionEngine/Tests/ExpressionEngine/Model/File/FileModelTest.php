<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Model\File;

use ExpressionEngine\Model\File\File as FileModel;
use PHPUnit\Framework\TestCase;

class FileModelTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testGetValuesDoesNotThrowWhenFileDimensionsCannotBeRead()
    {
        $file = new FileWithUnreadableRootStub();
        $file->setRawProperty('file_name', 'avatar.png');

        $values = $file->getValues();

        $this->assertArrayHasKey('file_hw_original', $values);
        $this->assertNull($values['file_hw_original']);
    }

    /**
     * Assert image dimensions are split into width and height accessors.
     *
     * @return void
     */
    public function testDimensionsAreReadFromOriginalFileDimensions()
    {
        $file = new FileModel();
        $file->setRawProperty('file_hw_original', '480 640');

        $this->assertSame('640', $file->width);
        $this->assertSame('480', $file->height);
    }

    /**
     * Assert unavailable or invalid dimensions return null as an atomic pair.
     *
     * @dataProvider invalidOriginalDimensionsProvider
     * @return void
     */
    public function testInvalidOriginalFileDimensionsReturnNull($dimensions)
    {
        $file = new FileModel();
        $file->setRawProperty('file_hw_original', $dimensions);

        $this->assertNull($file->width);
        $this->assertNull($file->height);
    }

    public static function invalidOriginalDimensionsProvider()
    {
        return array(
            'empty' => array(''),
            'null' => array(null),
            'height only' => array('480'),
            'non-numeric width' => array('480 wide'),
            'extra component' => array('480 640 extra'),
            'zero height' => array('0 640'),
            'zero width' => array('480 0'),
            'negative height' => array('-480 640'),
            'negative width' => array('480 -640'),
        );
    }
    /**
     * Assert dynamic cleanup preserves configured sizes when regeneration stops early.
     *
     * @dataProvider generatedFileCleanupModesProvider
     * @param bool $dynamicOnly Whether to keep thumbnails and configured sizes.
     * @return void
     * @throws \ExpressionEngine\Library\Filesystem\FilesystemException
     */
    public function testGeneratedFileCleanupModes($dynamicOnly)
    {
        $directory = sys_get_temp_dir() . '/file-cleanup-' . bin2hex(random_bytes(8));
        $filesystem = \Mockery::mock(\ExpressionEngine\Library\Filesystem\Filesystem::class, [
            new \ExpressionEngine\Library\Filesystem\Adapter\Local(['path' => sys_get_temp_dir()]),
        ])->makePartial();
        $filesystem->shouldReceive('isWritable')->andReturn(false);
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aSFAAAAAASUVORK5CYII=');
        $dynamicFiles = [
            '_resize/hero_resize_' . str_repeat('a', 32) . '.png',
            '_crop/hero_crop_' . str_repeat('b', 32) . '.png',
        ];
        $configuredFiles = ['_large/hero.png', '_small/hero.png', '_thumbs/hero.png'];
        $unrelatedFile = '_crop/other_crop_' . str_repeat('c', 32) . '.png';

        try {
            $filesystem->mkdir($directory);
            $filesystem->write($directory . '/hero.png', $image);
            foreach (array_merge($dynamicFiles, $configuredFiles, [$unrelatedFile]) as $filename) {
                $path = $directory . '/' . $filename;
                if (! $filesystem->isDir(dirname($path))) {
                    $filesystem->mkdir(dirname($path));
                }
                $filesystem->write($path, $image);
            }

            $destination = \Mockery::mock();
            $destination->server_path = $directory;
            $destination->shouldReceive('getProperty')->with('adapter')->andReturn('local');
            $destination->shouldReceive('getFilesystem')->andReturn($filesystem);
            $destination->FileDimensions = \Mockery::mock();
            $destination->FileDimensions->shouldReceive('pluck')->with('short_name')->andReturn(['small', 'large']);
            $file = new class extends FileModel {
                /** @var object */
                public $UploadDestination;
            };
            $file->UploadDestination = $destination;
            $file->setRawProperty('file_name', 'hero.png');
            $file->setRawProperty('directory_id', 0);

            if ($dynamicOnly) {
                $file->deleteGeneratedFiles(true);
                ee()->setMock('load', \Mockery::mock()->shouldReceive('library', 'helper')->andReturnNull()->getMock());
                ee()->setMock('MimeType', \Mockery::mock()->shouldReceive('isImage')->with('image/png')->andReturn(true)->getMock());
                ee()->setMock('Memory', \Mockery::mock()->shouldReceive('setMemoryForImageManipulation')->andReturnNull()->getMock());
                ee()->setMock('image_lib', \Mockery::mock()->shouldReceive('clear')->andReturnNull()->getMock());
                require_once APPPATH . 'libraries/Filemanager.php';
                $filemanager = (new \ReflectionClass('Filemanager'))->newInstanceWithoutConstructor();
                $dimension = ['width' => 1, 'height' => 1, 'quality' => 75, 'watermark_id' => 0, 'resize_type' => 'resize'];

                $this->assertFalse($filemanager->create_thumb($directory . '/hero.png', [
                    'directory' => $destination,
                    'server_path' => $directory,
                    'file_name' => 'hero.png',
                    'mime_type' => 'image/png',
                    'width' => 1,
                    'height' => 1,
                    'dimensions' => [
                        array_merge($dimension, ['short_name' => 'small']),
                        array_merge($dimension, ['short_name' => 'large']),
                    ],
                ], true, false));
            } else {
                $file->deleteGeneratedFiles();
            }

            clearstatcache();
            foreach ($dynamicFiles as $filename) {
                $this->assertFileDoesNotExist($directory . '/' . $filename);
            }
            foreach ($configuredFiles as $filename) {
                $this->assertSame($dynamicOnly, is_file($directory . '/' . $filename), $filename);
            }
            $this->assertFileExists($directory . '/' . $unrelatedFile);
            $this->assertSame($image, file_get_contents($directory . '/hero.png'));
        } finally {
            if (is_dir($directory)) {
                $filesystem->deleteDir($directory);
            }
        }
    }

    /**
     * Provide opt-in dynamic cleanup and the existing full cleanup default.
     *
     * @return array<string, array<int, bool>>
     */
    public static function generatedFileCleanupModesProvider()
    {
        return [
            'dynamic caches only' => [true],
            'full cleanup default' => [false],
        ];
    }
}

class FileWithUnreadableRootStub extends FileModel
{
    public function actLocally(callable $callback)
    {
        throw new \LogicException('The root path is not readable.');
    }
}

// EOF
