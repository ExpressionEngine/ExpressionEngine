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

class FileFtReplaceResizeUploadDestinationStub
{
    /** @var string */
    private $filesystem;

    /**
     * Seed the filesystem returned by the upload destination.
     *
     * @param string $filesystem
     * @return void
     */
    public function __construct($filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Return the configured filesystem identifier.
     *
     * @return string
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }
}

class FileFtReplaceResizeModelObjectStub
{
    /** @var string */
    public $file_name;

    /** @var FileFtReplaceResizeUploadDestinationStub */
    public $UploadDestination;

    /** @var bool */
    private $isImage;

    /** @var bool */
    private $isEditableImage;

    /** @var string */
    private $absolutePath;

    /**
     * Seed the file model data consumed by replace_resize().
     *
     * @param string $fileName
     * @param string $filesystem
     * @param string $absolutePath
     * @param bool $isImage
     * @param bool $isEditableImage
     * @return void
     */
    public function __construct($fileName, $filesystem, $absolutePath, $isImage = true, $isEditableImage = false)
    {
        $this->file_name = $fileName;
        $this->UploadDestination = new FileFtReplaceResizeUploadDestinationStub($filesystem);
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

class FileFtReplaceResizeSpy extends File_ft
{
    /** @var array<int, array<string, mixed>> */
    public $replaceTagCatchallCalls = [];

    /** @var string */
    public $replaceTagCatchallReturn = 'catchall-output';

    /** @var array<int, array<string, mixed>> */
    public $replaceTagCalls = [];

    /** @var string */
    public $replaceTagReturn = 'replace-tag-output';

    /**
     * Capture catchall replacement dispatch.
     *
     * @param mixed $data
     * @param array<string, mixed> $params
     * @param mixed $tagdata
     * @param string $manipulation
     * @return string
     */
    public function replace_tag_catchall($data = array(), $params = array(), $tagdata = false, $modifier = '')
    {
        $this->replaceTagCatchallCalls[] = [
            'data' => $data,
            'params' => $params,
            'tagdata' => $tagdata,
            'manipulation' => $modifier,
        ];

        return $this->replaceTagCatchallReturn;
    }

    /**
     * Capture replace_tag() fallback calls.
     *
     * @param mixed $data
     * @param array<string, mixed> $params
     * @param mixed $tagdata
     * @return string
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        $this->replaceTagCalls[] = [
            'data' => $data,
            'params' => $params,
            'tagdata' => $tagdata,
        ];

        return $this->replaceTagReturn;
    }

}

class FileFtReplaceResizeTest extends FileFtTestBase
{
    /**
     * Assert empty params delegate to the resize catchall path for named manipulations.
     *
     * @return void
     */
    public function testReplaceResizeDelegatesToCatchallWhenParamsAreEmpty()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceResizeSpy::class);

        $result = $fieldtype->replace_resize(['file_id' => 4], [], '{file:url}');

        $this->assertSame('catchall-output', $result);
        $this->assertSame([
            [
                'data' => ['file_id' => 4],
                'params' => [],
                'tagdata' => '{file:url}',
                'manipulation' => 'resize',
            ],
        ], $fieldtype->replaceTagCatchallCalls);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert wrap-only params still resolve through the named resize manipulation catchall.
     *
     * @return void
     */
    public function testReplaceResizeDelegatesToCatchallWhenWrapIsOnlyParam()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceResizeSpy::class);

        $result = $fieldtype->replace_resize(['file_id' => 7], ['wrap' => 'plain'], false);

        $this->assertSame('catchall-output', $result);
        $this->assertSame([
            [
                'data' => ['file_id' => 7],
                'params' => ['wrap' => 'plain'],
                'tagdata' => false,
                'manipulation' => 'resize',
            ],
        ], $fieldtype->replaceTagCatchallCalls);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert empty payloads fall back to replace_tag() instead of attempting image processing.
     *
     * @return void
     */
    public function testReplaceResizeFallsBackToReplaceTagWhenDataIsEmpty()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceResizeSpy::class);

        $result = $fieldtype->replace_resize([], ['width' => '1200'], '{file:url}');

        $this->assertSame('replace-tag-output', $result);
        $this->assertSame([
            [
                'data' => [],
                'params' => ['width' => '1200'],
                'tagdata' => '{file:url}',
            ],
        ], $fieldtype->replaceTagCalls);
        $this->assertSame([], $fieldtype->replaceTagCatchallCalls);
    }

    /**
     * Assert non-empty payloads still fall back when the file model object is unavailable.
     *
     * @return void
     */
    public function testReplaceResizeFallsBackToReplaceTagWhenModelObjectIsMissing()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceResizeSpy::class);
        $data = ['file_id' => 11, 'url' => '{filedir_3}manual.pdf'];

        $result = $fieldtype->replace_resize($data, ['height' => '600'], false);

        $this->assertSame('replace-tag-output', $result);
        $this->assertSame([
            [
                'data' => $data,
                'params' => ['height' => '600'],
                'tagdata' => false,
            ],
        ], $fieldtype->replaceTagCalls);
        $this->assertSame([], $fieldtype->replaceTagCatchallCalls);
    }

    /**
     * Assert non-image files fail fast even after replace_resize() normalizes the processing payload.
     *
     * @return void
     */
    public function testReplaceResizeReturnsFalseWhenModelObjectIsNotAnImage()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceResizeSpy::class);
        $modelObject = new FileFtReplaceResizeModelObjectStub(
            'hero.jpg',
            's3-assets',
            '/srv/uploads/hero.jpg',
            false,
            false
        );
        $data = [
            'model_object' => $modelObject,
        ];

        $result = $fieldtype->replace_resize($data, ['width' => '900'], '{tagdata}');

        $this->assertFalse($result);
        $this->assertSame([], $fieldtype->replaceTagCatchallCalls);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert replace_resize() seeds missing processing fields from the file model before delegating.
     *
     * @return void
     */
    public function testReplaceResizeSeedsMissingProcessingFieldsBeforeReplaceTagFallback()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceResizeSpy::class);
        $modelObject = new FileFtReplaceResizeModelObjectStub(
            'hero.jpg',
            's3-assets',
            '/srv/uploads/hero.jpg',
            true,
            false
        );
        $data = [
            'file_id' => 22,
            'model_object' => $modelObject,
            'extra' => 'kept',
        ];

        $result = $fieldtype->replace_resize($data, ['width' => '900', 'quality' => '80'], '{tagdata}');

        $this->assertSame('replace-tag-output', $result);
        $this->assertSame([
            [
                'data' => [
                    'file_id' => 22,
                    'model_object' => $modelObject,
                    'extra' => 'kept',
                    'fs_filename' => 'hero.jpg',
                    'filesystem' => 's3-assets',
                    'source_image' => '/srv/uploads/hero.jpg',
                ],
                'params' => ['width' => '900', 'quality' => '80'],
                'tagdata' => '{tagdata}',
            ],
        ], $fieldtype->replaceTagCalls);
        $this->assertSame([], $fieldtype->replaceTagCatchallCalls);
    }

    /**
     * Assert caller-supplied processing fields win over model-derived defaults.
     *
     * @return void
     */
    public function testReplaceResizePreservesExplicitProcessingFieldsForModifierChaining()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceResizeSpy::class);
        $modelObject = new FileFtReplaceResizeModelObjectStub(
            'hero.jpg',
            's3-assets',
            '/srv/uploads/hero.jpg',
            true,
            false
        );
        $data = [
            'model_object' => $modelObject,
            'fs_filename' => 'cached-name.jpg',
            'filesystem' => 'local-cache',
            'source_image' => '/tmp/cached-name.jpg',
        ];

        $result = $fieldtype->replace_resize($data, ['width' => '320'], null);

        $this->assertSame([
            'model_object' => $modelObject,
            'fs_filename' => 'cached-name.jpg',
            'filesystem' => 'local-cache',
            'source_image' => '/tmp/cached-name.jpg',
        ], $result);
        $this->assertSame([], $fieldtype->replaceTagCatchallCalls);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert the private process_image() guard rejects unsupported functions.
     *
     * @return void
     */
    public function testProcessImageReturnsFalseForUnsupportedFunction()
    {
        $fieldtype = $this->makeFieldtype();
        $method = new ReflectionMethod(File_ft::class, 'process_image');
        TestReflectionHelper::makeAccessible($method);

        $this->assertFalse($method->invoke($fieldtype, 'sharpen', [], [], false, false));
    }

    /**
     * Assert editable-image processing stops when the manipulation directory is not writable.
     *
     * @return void
     */
    public function testReplaceResizeReturnsFalseWhenManipulationDirectoryIsNotWritable()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $modelObject = new FileFtProcessImageModelObjectStub([
            'filesystem' => $filesystem,
        ]);
        $data = [
            'model_object' => $modelObject,
            'fs_filename' => 'hero.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/srv/uploads/gallery/hero.jpg',
        ];
        $directory = '/srv/uploads/gallery/_resize' . DIRECTORY_SEPARATOR;
        $filesystem->directories[$directory] = true;
        $filesystem->writableDirectories[$directory] = false;

        $result = $fieldtype->replace_resize($data, ['width' => '320'], false);

        $this->assertFalse($result);
        $this->assertSame([$directory], $filesystem->isWritableCalls);
        $this->assertSame([], $filesystem->copyToTempFileCalls);
        $this->assertSame([], $this->imageLibMock->initializeCalls);
    }

    /**
     * Assert missing source files fall back to the original absolute URL instead of erroring.
     *
     * @return void
     */
    public function testReplaceResizeReturnsOriginalUrlWhenSourceCopyFails()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $sourceImage = '/srv/uploads/gallery/hero.jpg';
        $filesystem->copyToTempFileExceptions[$sourceImage] = 'missing source image';
        $modelObject = new FileFtProcessImageModelObjectStub([
            'filesystem' => $filesystem,
            'absoluteUrl' => 'https://example.com/uploads/gallery/hero.jpg',
        ]);
        $data = [
            'model_object' => $modelObject,
            'fs_filename' => 'hero.jpg',
            'filesystem' => $filesystem,
            'source_image' => $sourceImage,
        ];

        $result = $fieldtype->replace_resize($data, ['width' => '320'], false);

        $this->assertSame('https://example.com/uploads/gallery/hero.jpg', $result);
        $this->assertSame([$sourceImage], $filesystem->copyToTempFileCalls);
        $this->assertSame([], $filesystem->writeStreamCalls);
    }

    /**
     * Assert resize failures surface image-lib errors for debug mode and honor config defaults.
     *
     * @return void
     */
    public function testReplaceResizeReturnsDisplayErrorsForDebugFailures()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $modelObject = new FileFtProcessImageModelObjectStub([
            'filesystem' => $filesystem,
        ]);
        $data = [
            'model_object' => $modelObject,
            'fs_filename' => 'hero.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/srv/uploads/gallery/hero.jpg',
        ];
        $imageLib = new FileFtImageLibStub();
        $imageLib->actionResults['resize'] = false;
        $imageLib->displayErrorsReturn = 'resize failed';
        $this->setImageLib($imageLib);
        $this->setConfigItems([
            'image_resize_protocol' => 'imagick',
            'image_library_path' => '/opt/homebrew/lib',
            'image_manipulation_quality' => 92,
            'debug' => 2,
        ]);

        $result = $fieldtype->replace_resize($data, [
            'width' => '320',
            'maintain_ratio' => 'n',
        ], false);

        $this->assertSame('resize failed', $result);
        $this->assertCount(1, $imageLib->initializeCalls);
        $this->assertFalse($imageLib->initializeCalls[0]['maintain_ratio']);
        $this->assertSame('width', $imageLib->initializeCalls[0]['master_dim']);
        $this->assertSame(92, $imageLib->initializeCalls[0]['quality']);
        $this->assertSame(320, $imageLib->initializeCalls[0]['width']);
        $this->assertSame(100, $imageLib->initializeCalls[0]['height']);
        $this->assertSame('imagick', $imageLib->initializeCalls[0]['image_library']);
        $this->assertSame('/opt/homebrew/lib', $imageLib->initializeCalls[0]['library_path']);
        $this->assertSame([], $filesystem->writeStreamCalls);
    }

    /**
     * Assert resize failures return no_results() outside the debug-superadmin path.
     *
     * @return void
     */
    public function testReplaceResizeReturnsNoResultsWhenFailuresAreNotDebugVisible()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $modelObject = new FileFtProcessImageModelObjectStub([
            'filesystem' => $filesystem,
        ]);
        $data = [
            'model_object' => $modelObject,
            'fs_filename' => 'hero.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/srv/uploads/gallery/hero.jpg',
        ];
        $imageLib = new FileFtImageLibStub();
        $imageLib->actionResults['resize'] = false;
        $this->setImageLib($imageLib);
        $this->setConfigItems([
            'image_manipulation_quality' => 120,
            'debug' => 1,
        ]);
        $this->setPermissionIsSuperAdmin(false);

        $result = $fieldtype->replace_resize($data, ['maintain_ratio' => 'y'], false);

        $this->assertSame('NO_RESULTS', $result);
        $this->assertSame(75, $imageLib->initializeCalls[0]['quality']);
        $this->assertTrue($imageLib->initializeCalls[0]['maintain_ratio']);
        $this->assertSame('', $imageLib->width);
        $this->assertSame('', $imageLib->height);
        $this->assertSame([], $filesystem->writeStreamCalls);
    }

    /**
     * Assert cached manipulations reuse the existing file and return chainable metadata.
     *
     * @return void
     */
    public function testReplaceResizeReturnsExistingManipulationDataForModifierChaining()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $modelObject = new FileFtProcessImageModelObjectStub([
            'filesystem' => $filesystem,
        ]);
        $params = ['height' => '240'];
        $hash = md5(serialize($params));
        $destinationPath = '/srv/uploads/gallery/_resize' . DIRECTORY_SEPARATOR . 'hero_resize_' . $hash . '.jpg';
        $destinationUrl = 'https://example.com/uploads/gallery/_resize/hero_resize_' . $hash . '.jpg';
        $filesystem->directories['/srv/uploads/gallery/_resize' . DIRECTORY_SEPARATOR] = true;
        $filesystem->existingPaths[$destinationPath] = true;
        $data = [
            'model_object' => $modelObject,
            'fs_filename' => 'hero.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/srv/uploads/gallery/hero.jpg',
        ];

        $result = $fieldtype->replace_resize($data, $params, null);

        $this->assertSame($destinationPath, $result['source_image']);
        $this->assertSame($destinationUrl, $result['url']);
        $this->assertSame(200, $result['width']);
        $this->assertSame(100, $result['height']);
        $this->assertSame([$destinationPath], $filesystem->copyToTempFileCalls);
        $this->assertSame([], $filesystem->writeStreamCalls);
        $this->assertSame([], $this->imageLibMock->initializeCalls);
    }

    /**
     * Assert height-only resize requests infer the missing width and switch master_dim to height.
     *
     * @return void
     */
    public function testReplaceResizeInfersWidthWhenOnlyHeightIsProvided()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $modelObject = new FileFtProcessImageModelObjectStub([
            'filesystem' => $filesystem,
        ]);
        $data = [
            'model_object' => $modelObject,
            'fs_filename' => 'hero.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/srv/uploads/gallery/hero.jpg',
        ];

        $result = $fieldtype->replace_resize($data, ['height' => '240'], false);

        $this->assertStringContainsString('/_resize/hero_resize_', $result);
        $this->assertSame('height', $this->imageLibMock->initializeCalls[0]['master_dim']);
        $this->assertSame(100, $this->imageLibMock->initializeCalls[0]['width']);
        $this->assertSame(240, $this->imageLibMock->initializeCalls[0]['height']);
    }

    /**
     * Assert successful manipulations can wrap the generated URL as image markup.
     *
     * @return void
     */
    public function testReplaceResizeWrapsGeneratedUrlsWhenRequested()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $modelObject = new FileFtProcessImageModelObjectStub([
            'filesystem' => $filesystem,
        ]);
        $params = [
            'width' => '320',
            'wrap' => 'image',
        ];
        $hash = md5(serialize($params));
        $destinationUrl = 'https://example.com/uploads/gallery/_resize/hero_resize_' . $hash . '.jpg';
        $data = [
            'model_object' => $modelObject,
            'fs_filename' => 'hero.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/srv/uploads/gallery/hero.jpg',
            'filename' => 'Hero Banner',
            'image_pre_format' => '<figure>',
            'image_post_format' => '</figure>',
            'image_properties' => 'class="hero"',
        ];

        $result = $fieldtype->replace_resize($data, $params, false);

        $this->assertSame(
            '<figure><img src="' . $destinationUrl . '" class="hero" alt="Hero Banner" /></figure>',
            $result
        );
    }
}
