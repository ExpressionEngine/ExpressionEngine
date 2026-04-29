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

    /**
     * Assert a model-backed call without resize or crop params still runs both stages with function metadata only.
     *
     * @return void
     */
    public function testReplaceResizeCropRunsBothStagesWhenParamsAreEmpty()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceResizeCropSpy::class);
        $fieldtype->replaceTagReturns = ['/tmp/resized-stage.jpg', 'final-crop-output'];
        $modelObject = new FileFtReplaceResizeCropModelObjectStub(
            'hero.jpg',
            's3-assets',
            '/srv/uploads/hero.jpg',
            true,
            false
        );

        $result = $fieldtype->replace_resize_crop(['model_object' => $modelObject], [], false);

        $this->assertSame('final-crop-output', $result);
        $this->assertCount(2, $fieldtype->replaceTagCalls);
        $this->assertSame(['function' => 'resize_crop'], $fieldtype->replaceTagCalls[0]['params']);
        $this->assertSame([
            'model_object' => $modelObject,
            'fs_filename' => 'hero.jpg',
            'filesystem' => 's3-assets',
            'source_image' => '/tmp/resized-stage.jpg',
        ], $fieldtype->replaceTagCalls[1]['data']);
        $this->assertSame(['function' => 'resize_crop'], $fieldtype->replaceTagCalls[1]['params']);
    }

    /**
     * Assert replace_resize_crop() seeds missing fields, strips wrap from resize, and restores crop params.
     *
     * @return void
     */
    public function testReplaceResizeCropSeedsDefaultsAndSplitsResizeThenCropParams()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceResizeCropSpy::class);
        $fieldtype->replaceTagReturns = ['/tmp/resized-stage.jpg', 'final-crop-output'];
        $modelObject = new FileFtReplaceResizeCropModelObjectStub(
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

        $result = $fieldtype->replace_resize_crop($data, $params, false);

        $this->assertSame('final-crop-output', $result);
        $this->assertCount(2, $fieldtype->replaceTagCalls);
        $this->assertSame([
            'file_id' => 22,
            'model_object' => $modelObject,
            'extra' => 'kept',
            'fs_filename' => 'hero.jpg',
            'filesystem' => 's3-assets',
            'source_image' => '/srv/uploads/hero.jpg',
        ], $fieldtype->replaceTagCalls[0]['data']);
        $this->assertSame([
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
            'focus' => 'center',
            'function' => 'resize_crop',
            'width' => '900',
            'quality' => '80',
            'maintain_ratio' => 'n',
        ], $fieldtype->replaceTagCalls[0]['params']);
        $this->assertFalse($fieldtype->replaceTagCalls[0]['tagdata']);
        $this->assertSame([
            'file_id' => 22,
            'model_object' => $modelObject,
            'extra' => 'kept',
            'fs_filename' => 'hero.jpg',
            'filesystem' => 's3-assets',
            'source_image' => '/tmp/resized-stage.jpg',
        ], $fieldtype->replaceTagCalls[1]['data']);
        $this->assertSame([
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
            'function' => 'resize_crop',
            'width' => '320',
            'height' => '180',
            'x' => '10',
            'y' => '20',
            'quality' => '55',
        ], $fieldtype->replaceTagCalls[1]['params']);
        $this->assertFalse($fieldtype->replaceTagCalls[1]['tagdata']);
        $this->assertArrayNotHasKey('maintain_ratio', $fieldtype->replaceTagCalls[1]['params']);
    }

    /**
     * Assert caller-supplied processing fields survive both stages while source_image advances to the resized output.
     *
     * @return void
     */
    public function testReplaceResizeCropPreservesExplicitProcessingFieldsAcrossBothStages()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceResizeCropSpy::class);
        $fieldtype->replaceTagReturns = ['/tmp/generated-resize.jpg', 'crop-output'];
        $modelObject = new FileFtReplaceResizeCropModelObjectStub(
            'hero.jpg',
            's3-assets',
            '/srv/uploads/hero.jpg',
            true,
            false
        );
        $filesystem = (object) ['adapter' => 'local-cache'];
        $data = [
            'model_object' => $modelObject,
            'fs_filename' => 'cached-name.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/tmp/cached-name.jpg',
        ];

        $result = $fieldtype->replace_resize_crop(
            $data,
            ['resize:height' => '600', 'crop:width' => '320'],
            '{tagdata}'
        );

        $this->assertSame('crop-output', $result);
        $this->assertSame($data, $fieldtype->replaceTagCalls[0]['data']);
        $this->assertSame([
            'resize:height' => '600',
            'crop:width' => '320',
            'function' => 'resize_crop',
            'height' => '600',
        ], $fieldtype->replaceTagCalls[0]['params']);
        $this->assertSame([
            'model_object' => $modelObject,
            'fs_filename' => 'cached-name.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/tmp/generated-resize.jpg',
        ], $fieldtype->replaceTagCalls[1]['data']);
        $this->assertSame([
            'resize:height' => '600',
            'crop:width' => '320',
            'function' => 'resize_crop',
            'width' => '320',
        ], $fieldtype->replaceTagCalls[1]['params']);
        $this->assertSame('{tagdata}', $fieldtype->replaceTagCalls[1]['tagdata']);
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
        $resizePath = '/srv/uploads/gallery/_resize/hero_resize_' . md5(serialize($resizeParams)) . '.jpg';
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
}
