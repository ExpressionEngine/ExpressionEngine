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

class FileFtReplaceRotateUploadDestinationStub
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

class FileFtReplaceRotateModelObjectStub
{
    /** @var string */
    public $file_name;

    /** @var FileFtReplaceRotateUploadDestinationStub */
    public $UploadDestination;

    /** @var bool */
    private $isImage;

    /** @var bool */
    private $isEditableImage;

    /** @var string */
    private $absolutePath;

    /**
     * Seed the file model data consumed by replace_rotate().
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
        $this->UploadDestination = new FileFtReplaceRotateUploadDestinationStub($filesystem);
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

class FileFtReplaceRotateSpy extends File_ft
{
    /** @var array<int, array<string, mixed>> */
    public $replaceTagCalls = [];

    /** @var string */
    public $replaceTagReturn = 'replace-tag-output';

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

class FileFtReplaceRotateTest extends FileFtTestBase
{
    /**
     * Assert empty payloads fall back to replace_tag() instead of attempting image rotation.
     *
     * @return void
     */
    public function testReplaceRotateFallsBackToReplaceTagWhenDataIsEmpty()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceRotateSpy::class);

        $result = $fieldtype->replace_rotate([], ['angle' => '90'], '{file:url}');

        $this->assertSame('replace-tag-output', $result);
        $this->assertSame([
            [
                'data' => [],
                'params' => ['angle' => '90'],
                'tagdata' => '{file:url}',
            ],
        ], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert non-empty payloads still fall back when the file model object is unavailable.
     *
     * @return void
     */
    public function testReplaceRotateFallsBackToReplaceTagWhenModelObjectIsMissing()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceRotateSpy::class);
        $data = ['file_id' => 11, 'url' => '{filedir_3}manual.pdf'];

        $result = $fieldtype->replace_rotate($data, ['angle' => '180'], false);

        $this->assertSame('replace-tag-output', $result);
        $this->assertSame([
            [
                'data' => $data,
                'params' => ['angle' => '180'],
                'tagdata' => false,
            ],
        ], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert non-image files fail fast even after replace_rotate() normalizes the processing payload.
     *
     * @return void
     */
    public function testReplaceRotateReturnsFalseWhenModelObjectIsNotAnImage()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceRotateSpy::class);
        $modelObject = new FileFtReplaceRotateModelObjectStub(
            'hero.jpg',
            's3-assets',
            '/srv/uploads/hero.jpg',
            false,
            false
        );
        $data = [
            'model_object' => $modelObject,
        ];

        $result = $fieldtype->replace_rotate($data, ['angle' => 'vrt'], '{tagdata}');

        $this->assertFalse($result);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert replace_rotate() seeds missing processing fields from the file model before delegating.
     *
     * @return void
     */
    public function testReplaceRotateSeedsMissingProcessingFieldsBeforeReplaceTagFallback()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceRotateSpy::class);
        $modelObject = new FileFtReplaceRotateModelObjectStub(
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

        $result = $fieldtype->replace_rotate($data, ['angle' => '270'], '{tagdata}');

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
                'params' => ['angle' => '270'],
                'tagdata' => '{tagdata}',
            ],
        ], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert caller-supplied processing fields win over model-derived defaults for modifier chaining.
     *
     * @return void
     */
    public function testReplaceRotatePreservesExplicitProcessingFieldsForModifierChaining()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceRotateSpy::class);
        $modelObject = new FileFtReplaceRotateModelObjectStub(
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

        $result = $fieldtype->replace_rotate($data, ['angle' => 'hor'], null);

        $this->assertSame([
            'model_object' => $modelObject,
            'fs_filename' => 'cached-name.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/tmp/cached-name.jpg',
        ], $result);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }
}
