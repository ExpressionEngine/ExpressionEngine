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
}
