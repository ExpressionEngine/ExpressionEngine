<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/FileFtTestBase.php';

class FileFtReplaceResizeCropUploadDestinationStub
{
    /** @var mixed */
    private $filesystem;

    /**
     * Seed the filesystem returned by the upload destination.
     *
     * @param mixed $filesystem
     * @return void
     */
    public function __construct($filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Return the configured filesystem object or identifier.
     *
     * @return mixed
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }
}

class FileFtReplaceResizeCropModelObjectStub
{
    /** @var string */
    public $file_name;

    /** @var FileFtReplaceResizeCropUploadDestinationStub */
    public $UploadDestination;

    /** @var bool */
    private $isImage;

    /** @var bool */
    private $isEditableImage;

    /** @var string */
    private $absolutePath;

    /**
     * Seed the file model data consumed by replace_resize_crop().
     *
     * @param string $fileName
     * @param mixed $filesystem
     * @param string $absolutePath
     * @param bool $isImage
     * @param bool $isEditableImage
     * @return void
     */
    public function __construct($fileName, $filesystem, $absolutePath, $isImage = true, $isEditableImage = false)
    {
        $this->file_name = $fileName;
        $this->UploadDestination = new FileFtReplaceResizeCropUploadDestinationStub($filesystem);
        $this->absolutePath = $absolutePath;
        $this->isImage = $isImage;
        $this->isEditableImage = $isEditableImage;
    }

    /**
     * Return the configured absolute source-image path.
     *
     * @return string
     */
    public function getAbsolutePath()
    {
        return $this->absolutePath;
    }

    /**
     * Return whether the file model should be treated as an image.
     *
     * @return bool
     */
    public function isImage()
    {
        return $this->isImage;
    }

    /**
     * Return whether the image is editable by the image-manipulation pipeline.
     *
     * @return bool
     */
    public function isEditableImage()
    {
        return $this->isEditableImage;
    }
}

class FileFtReplaceResizeCropSpy extends File_ft
{
    /** @var array<int, array<string, mixed>> */
    public $replaceTagCalls = [];

    /** @var array<int, mixed> */
    public $replaceTagReturns = [];

    /** @var mixed */
    public $replaceTagDefaultReturn = 'replace-tag-output';

    /**
     * Capture replace_tag() fallback calls.
     *
     * @param mixed $data
     * @param array<string, mixed> $params
     * @param mixed $tagdata
     * @return mixed
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        $this->replaceTagCalls[] = [
            'data' => $data,
            'params' => $params,
            'tagdata' => $tagdata,
        ];

        if (count($this->replaceTagReturns) > 0) {
            return array_shift($this->replaceTagReturns);
        }

        return $this->replaceTagDefaultReturn;
    }
}

class FileFtReplaceResizeCropTest extends FileFtTestBase
{
    private const CACHE_PARAMS = [
        'resize:width' => '1600',
        'crop:width' => '1600',
        'crop:height' => '900',
        'position' => 'center',
    ];

    // Existing 7.dev cache names for CACHE_PARAMS, including normalized stage parameters.
    private const RESIZE_NAME = 'hero_resize_e37622790ef9cf209945a731c5d0caf0.jpg';
    private const CROP_NAME = 'hero_crop_9790f66825d63e1923f465e534782612.jpg';
    private const RESIZE_DIRECTORY = '/srv/uploads/gallery/_resize' . DIRECTORY_SEPARATOR;
    private const CROP_DIRECTORY = '/srv/uploads/gallery/_crop' . DIRECTORY_SEPARATOR;

    /**
     * Assert empty payloads fall back to replace_tag() instead of attempting resize-crop processing.
     *
     * @return void
     */
    public function testReplaceResizeCropFallsBackToReplaceTagWhenDataIsEmpty()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceResizeCropSpy::class);

        $result = $fieldtype->replace_resize_crop([], ['resize:width' => '1200'], '{file:url}');

        $this->assertSame('replace-tag-output', $result);
        $this->assertSame([
            [
                'data' => [],
                'params' => ['resize:width' => '1200'],
                'tagdata' => '{file:url}',
            ],
        ], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert non-empty payloads still fall back when the file model object is unavailable.
     *
     * @return void
     */
    public function testReplaceResizeCropFallsBackToReplaceTagWhenModelObjectIsMissing()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceResizeCropSpy::class);
        $data = ['file_id' => 11, 'url' => '{filedir_3}manual.pdf'];

        $result = $fieldtype->replace_resize_crop($data, ['crop:height' => '600'], false);

        $this->assertSame('replace-tag-output', $result);
        $this->assertSame([
            [
                'data' => $data,
                'params' => ['crop:height' => '600'],
                'tagdata' => false,
            ],
        ], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert non-image files fail fast even after resize-crop normalizes the processing payload.
     *
     * @return void
     */
    public function testReplaceResizeCropReturnsFalseWhenModelObjectIsNotAnImage()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceResizeCropSpy::class);
        $modelObject = new FileFtReplaceResizeCropModelObjectStub(
            'hero.jpg',
            's3-assets',
            '/srv/uploads/hero.jpg',
            false,
            false
        );
        $data = [
            'model_object' => $modelObject,
        ];

        $result = $fieldtype->replace_resize_crop($data, ['resize:width' => '900', 'crop:height' => '450'], '{tagdata}');

        $this->assertFalse($result);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }

    public function testReplaceResizeCropRunsBothStagesWhenParamsAreEmpty()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();

        $result = $fieldtype->replace_resize_crop($this->cacheData($filesystem), []);

        $this->assertStringContainsString('/_crop/hero_crop_', $result);
        $this->assertSame(['resize', 'crop'], $this->imageLibMock->actionCalls);
        $this->assertCount(2, $filesystem->writeStreamCalls);
    }

    public function testReplaceResizeCropSeedsDefaultsAndSplitsResizeThenCropParams()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $params = [
            'resize:width' => '900',
            'resize:quality' => '80',
            'resize:maintain_ratio' => 'n',
            'resize' => 'ignored-resize-key',
            'crop:width' => '320',
            'crop:height' => '180',
            'crop:x' => '10',
            'crop:y' => '20',
            'crop:quality' => '55',
            'crop' => 'ignored-crop-key',
            'wrap' => 'plain',
            'focus' => 'center',
        ];
        $resizeParams = $params;
        unset($resizeParams['wrap']);
        $resizeParams += ['function' => 'resize_crop', 'width' => '900', 'quality' => '80', 'maintain_ratio' => 'n'];
        $cropParams = $params + [
            'function' => 'resize_crop', 'width' => '320', 'height' => '180', 'x' => '10', 'y' => '20', 'quality' => '55',
        ];
        $resizePath = self::RESIZE_DIRECTORY . 'hero_resize_' . md5(serialize($resizeParams)) . '.jpg';
        $cropPath = self::CROP_DIRECTORY . 'hero_crop_' . md5(serialize($cropParams)) . '.jpg';

        $result = $fieldtype->replace_resize_crop($this->cacheData($filesystem), $params);

        $this->assertSame(['resize', 'crop'], $this->imageLibMock->actionCalls);
        $this->assertSame([$resizePath, $cropPath], array_column($filesystem->writeStreamCalls, 'path'));
        $this->assertSame(['/srv/uploads/gallery/hero.jpg', $resizePath], $filesystem->copyToTempFileCalls);
        $this->assertSame('https://example.com/uploads/gallery/_crop/' . basename($cropPath), $result);
        list($resizeConfig, $cropConfig) = $this->imageLibMock->initializeCalls;
        $this->assertSame(900, $resizeConfig['width']);
        $this->assertSame(80, $resizeConfig['quality']);
        $this->assertFalse($resizeConfig['maintain_ratio']);
        $this->assertSame(320, $cropConfig['width']);
        $this->assertSame(180, $cropConfig['height']);
        $this->assertSame(55, $cropConfig['quality']);
        $this->assertSame(10, $cropConfig['x_axis']);
        $this->assertSame(20, $cropConfig['y_axis']);
        $this->assertTrue($cropConfig['maintain_ratio']);
    }

    public function testReplaceResizeCropPreservesExplicitProcessingFieldsAcrossBothStages()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $data = [
            'model_object' => new FileFtProcessImageModelObjectStub(),
            'fs_filename' => 'cached-name.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/tmp/cached-name.jpg',
            'extra' => 'kept',
        ];
        $params = ['resize:height' => '600', 'crop:width' => '320'];
        $resizeParams = $params + ['function' => 'resize_crop', 'height' => '600'];
        $cropParams = $params + ['function' => 'resize_crop', 'width' => '320'];
        $resizePath = self::RESIZE_DIRECTORY . 'cached-name_resize_' . md5(serialize($resizeParams)) . '.jpg';
        $cropPath = self::CROP_DIRECTORY . 'cached-name_crop_' . md5(serialize($cropParams)) . '.jpg';

        $result = $fieldtype->replace_resize_crop($data, $params, null);

        $this->assertSame(['/tmp/cached-name.jpg', $resizePath], $filesystem->copyToTempFileCalls);
        $this->assertSame([$resizePath, $cropPath], array_column($filesystem->writeStreamCalls, 'path'));
        $this->assertSame($cropPath, $result['source_image']);
        $this->assertSame('cached-name_crop.jpg', $result['fs_filename']);
        $this->assertSame($filesystem, $result['filesystem']);
        $this->assertSame($data['model_object'], $result['model_object']);
        $this->assertSame('kept', $result['extra']);
        $this->assertSame('hero.jpg', $data['model_object']->file_name);
    }

    public function testReplaceResizeCropFallsBackForNonEditableImages()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $data = [
            'model_object' => new FileFtProcessImageModelObjectStub([
                'filesystem' => $filesystem,
                'isEditableImage' => false,
            ]),
            'url' => 'https://example.com/hero.svg',
        ];
        $this->fileFieldMock->parseStringReturn = $data['url'];

        $result = $fieldtype->replace_resize_crop($data, self::CACHE_PARAMS);

        $this->assertSame($data['url'], $result);
        $this->assertSame([], $this->imageLibMock->actionCalls);
        $this->assertSame([], $filesystem->existsCalls);
        $this->assertSame([], $filesystem->copyToTempFileCalls);
    }

    /**
     * Assert the real resize-crop pipeline feeds the resized path into crop and returns the crop URL.
     *
     * @return void
     */
    public function testReplaceResizeCropUsesResizePathForTheRealCropStage()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $modelObject = new FileFtProcessImageModelObjectStub([
            'filesystem' => $filesystem,
        ]);
        $params = [
            'resize:width' => '320',
            'crop:width' => '150',
            'crop:height' => '60',
        ];
        $resizeParams = [
            'resize:width' => '320',
            'crop:width' => '150',
            'crop:height' => '60',
            'function' => 'resize_crop',
            'width' => '320',
        ];
        $cropParams = [
            'resize:width' => '320',
            'crop:width' => '150',
            'crop:height' => '60',
            'function' => 'resize_crop',
            'width' => '150',
            'height' => '60',
        ];
        $resizePath = self::RESIZE_DIRECTORY . 'hero_resize_' . md5(serialize($resizeParams)) . '.jpg';
        $cropUrl = 'https://example.com/uploads/gallery/_crop/hero_crop_' . md5(serialize($cropParams)) . '.jpg';
        $data = [
            'model_object' => $modelObject,
            'fs_filename' => 'hero.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/srv/uploads/gallery/hero.jpg',
        ];

        $result = $fieldtype->replace_resize_crop($data, $params, false);

        $this->assertSame($cropUrl, $result);
        $this->assertSame([
            '/srv/uploads/gallery/hero.jpg',
            $resizePath,
        ], $filesystem->copyToTempFileCalls);
        $this->assertCount(2, $filesystem->writeStreamCalls);
        $this->assertSame(['resize', 'crop'], $this->imageLibMock->actionCalls);
    }

    /** @dataProvider cacheStates */
    public function testReplaceResizeCropReusesCachedStages($hasResize, $hasCrop, $expectedActions)
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $data = $this->cacheData($filesystem);
        $resizePath = self::RESIZE_DIRECTORY . self::RESIZE_NAME;
        $cropPath = self::CROP_DIRECTORY . self::CROP_NAME;
        $filesystem->existingPaths = [$resizePath => $hasResize, $cropPath => $hasCrop];

        $result = $fieldtype->replace_resize_crop($data, self::CACHE_PARAMS);

        $this->assertSame('https://example.com/uploads/gallery/_crop/' . self::CROP_NAME, $result);
        $this->assertSame($expectedActions, $this->imageLibMock->actionCalls);
        $this->assertCount(count($expectedActions), $filesystem->writeStreamCalls);
        $this->assertSame('hero.jpg', $data['model_object']->file_name);
        if ($hasCrop) {
            $this->assertSame([$cropPath], $filesystem->existsCalls);
            $this->assertSame([$cropPath], $filesystem->copyToTempFileCalls);
            $this->assertSame(['crop'], $data['model_object']->manipulationUrlCalls);
            $this->assertNotContains(self::RESIZE_DIRECTORY, $filesystem->isDirCalls);
        } elseif ($hasResize) {
            $this->assertSame([$cropPath, $resizePath], $filesystem->existsCalls);
            $this->assertSame([$resizePath, $resizePath], $filesystem->copyToTempFileCalls);
            $this->assertSame($cropPath, $filesystem->writeStreamCalls[0]['path']);
        } else {
            $this->assertSame([$cropPath, $resizePath], $filesystem->existsCalls);
            $this->assertSame(['/srv/uploads/gallery/hero.jpg', $resizePath], $filesystem->copyToTempFileCalls);
            $this->assertSame([$resizePath, $cropPath], array_column($filesystem->writeStreamCalls, 'path'));
        }
    }

    public function cacheStates()
    {
        return [
            'cold cache' => [false, false, ['resize', 'crop']],
            'crop only' => [false, true, []],
            'both cached' => [true, true, []],
            'resize only' => [true, false, ['crop']],
        ];
    }

    public function testReplaceResizeCropRetainsResizedFileOnDisk()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $directory = sys_get_temp_dir() . '/file-ft-resize-crop-' . uniqid();
        $resizePath = $directory . '/_resize' . DIRECTORY_SEPARATOR . self::RESIZE_NAME;
        mkdir($directory . '/_resize', 0700, true);
        file_put_contents($resizePath, 'cached resized image');
        $filesystem->existingPaths[$resizePath] = true;
        $data = ['model_object' => new FileFtProcessImageModelObjectStub([
            'filesystem' => $filesystem,
            'baseServerPath' => $directory,
            'subfoldersPath' => '',
        ])];

        try {
            $fieldtype->replace_resize_crop($data, self::CACHE_PARAMS);

            clearstatcache(true, $resizePath);
            $this->assertFileExists($resizePath);
            $this->assertSame('cached resized image', file_get_contents($resizePath));
            $this->assertSame(['crop'], $this->imageLibMock->actionCalls);
        } finally {
            if (is_file($resizePath)) {
                unlink($resizePath);
            }
            rmdir($directory . '/_resize');
            rmdir($directory);
        }
    }

    public function testReplaceResizeCropSkipsResizeOnRepeatedCall()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $data = $this->cacheData($filesystem);
        $first = $fieldtype->replace_resize_crop($data, self::CACHE_PARAMS);
        $filesystem->existsCalls = [];
        $filesystem->copyToTempFileCalls = [];

        $second = $fieldtype->replace_resize_crop($data, self::CACHE_PARAMS);

        $this->assertSame($first, $second);
        $this->assertSame(['resize', 'crop'], $this->imageLibMock->actionCalls);
        $this->assertCount(2, $filesystem->writeStreamCalls);
        $cropPath = self::CROP_DIRECTORY . self::CROP_NAME;
        $this->assertSame([$cropPath], $filesystem->existsCalls);
        $this->assertSame([$cropPath], $filesystem->copyToTempFileCalls);
    }

    public function testReplaceResizeCropCentersCropUsingResizedDimensions()
    {
        $imageLib = new class extends FileFtImageLibStub {
            public function get_image_properties($path, $cached)
            {
                parent::get_image_properties($path, $cached);

                return ['width' => 1600, 'height' => count($this->getImagePropertiesCalls) < 3 ? 1200 : 900];
            }
        };
        $this->setImageLib($imageLib);
        $filesystem = new FileFtProcessImageFilesystemStub();

        $result = $this->makeFieldtype()->replace_resize_crop($this->cacheData($filesystem), self::CACHE_PARAMS, null);

        $this->assertSame(['resize', 'crop'], $imageLib->actionCalls);
        $this->assertEquals(0, $imageLib->initializeCalls[1]['x_axis']);
        $this->assertEquals(150, $imageLib->initializeCalls[1]['y_axis']);
        $this->assertSame(1600, $result['width']);
        $this->assertSame(900, $result['height']);
        $this->assertSame(self::RESIZE_DIRECTORY . self::RESIZE_NAME, $filesystem->copyToTempFileCalls[1]);
    }

    /** @dataProvider changedDimensions */
    public function testReplaceResizeCropDoesNotReuseDifferentParameters($parameter, $value)
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $data = $this->cacheData($filesystem);
        $filesystem->existingPaths = [
            self::RESIZE_DIRECTORY . self::RESIZE_NAME => true,
            self::CROP_DIRECTORY . self::CROP_NAME => true,
        ];
        $params = self::CACHE_PARAMS;
        $params[$parameter] = $value;

        $result = $fieldtype->replace_resize_crop($data, $params);

        $this->assertNotSame('https://example.com/uploads/gallery/_crop/' . self::CROP_NAME, $result);
        $this->assertSame(['resize', 'crop'], $this->imageLibMock->actionCalls);
        $this->assertCount(2, $filesystem->writeStreamCalls);
    }

    public function changedDimensions()
    {
        return [
            'resize width' => ['resize:width', '1800'],
            'crop height' => ['crop:height', '800'],
        ];
    }

    /** @dataProvider cachedOutputModes */
    public function testReplaceResizeCropPreservesCachedOutput($tagdata, $wrap, $cropName)
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $data = $this->cacheData($filesystem) + [
            'filename' => 'hero',
            'image_pre_format' => '<figure>',
            'image_post_format' => '</figure>',
            'image_properties' => 'class="hero"',
        ];
        $params = self::CACHE_PARAMS;
        if ($wrap !== null) {
            $params['wrap'] = $wrap;
        }
        $cropPath = self::CROP_DIRECTORY . $cropName;
        $cropUrl = 'https://example.com/uploads/gallery/_crop/' . $cropName;
        $filesystem->existingPaths[$cropPath] = true;

        $result = $fieldtype->replace_resize_crop($data, $params, $tagdata);

        $this->assertSame([], $this->imageLibMock->actionCalls);
        $this->assertSame([], $filesystem->writeStreamCalls);
        $this->assertSame([$cropPath], $filesystem->copyToTempFileCalls);
        if ($tagdata === null) {
            $this->assertSame($cropUrl, $result['url']);
            $this->assertSame($cropPath, $result['source_image']);
            $this->assertSame('hero_crop.jpg', $result['fs_filename']);
            $this->assertSame($filesystem, $result['filesystem']);
            $this->assertSame($data['model_object'], $result['model_object']);
            $this->assertSame(200, $result['width']);
            $this->assertSame(100, $result['height']);
        } elseif ($tagdata !== false) {
            $this->assertSame('parsed-template', $result);
            $this->assertSame([[
                'tagdata' => $tagdata,
                'variables' => [['url' => $cropUrl, 'width' => 200, 'height' => 100]],
            ]], $this->templateMock->parseVariablesCalls);
        } elseif ($wrap === 'image') {
            $this->assertSame('<figure><img src="' . $cropUrl . '" class="hero" alt="hero" /></figure>', $result);
        } else {
            $this->assertSame($cropUrl, $result);
        }
    }

    public function cachedOutputModes()
    {
        return [
            'URL' => [false, null, self::CROP_NAME],
            'tag pair' => ['{url}:{width}x{height}', null, self::CROP_NAME],
            'chaining' => [null, null, self::CROP_NAME],
            'image wrap' => [false, 'image', 'hero_crop_6d19344cfccf9118784ddd627431ac45.jpg'],
        ];
    }

    private function cacheData($filesystem)
    {
        return ['model_object' => new FileFtProcessImageModelObjectStub(['filesystem' => $filesystem])];
    }
}
