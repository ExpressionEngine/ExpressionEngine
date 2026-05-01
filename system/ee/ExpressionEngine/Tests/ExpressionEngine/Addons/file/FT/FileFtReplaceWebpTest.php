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

class FileFtReplaceWebpUploadDestinationStub
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

class FileFtReplaceWebpModelObjectStub
{
    /** @var string */
    public $file_name;

    /** @var FileFtReplaceWebpUploadDestinationStub */
    public $UploadDestination;

    /** @var bool */
    private $isImage;

    /** @var bool */
    private $isEditableImage;

    /** @var string */
    private $absolutePath;

    /**
     * Seed the file model data consumed by replace_webp().
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
        $this->UploadDestination = new FileFtReplaceWebpUploadDestinationStub($filesystem);
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

class FileFtReplaceWebpSpy extends File_ft
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

class FileFtReplaceWebpTest extends FileFtTestBase
{
    /**
     * Assert empty payloads fall back to replace_tag() instead of attempting webp conversion.
     *
     * @return void
     */
    public function testReplaceWebpFallsBackToReplaceTagWhenDataIsEmpty()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceWebpSpy::class);

        $result = $fieldtype->replace_webp([], ['quality' => '80'], '{file:url}');

        $this->assertSame('replace-tag-output', $result);
        $this->assertSame([
            [
                'data' => [],
                'params' => ['quality' => '80'],
                'tagdata' => '{file:url}',
            ],
        ], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert non-empty payloads still fall back when the file model object is unavailable.
     *
     * @return void
     */
    public function testReplaceWebpFallsBackToReplaceTagWhenModelObjectIsMissing()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceWebpSpy::class);
        $data = ['file_id' => 11, 'url' => '{filedir_3}manual.pdf'];

        $result = $fieldtype->replace_webp($data, ['quality' => '65'], false);

        $this->assertSame('replace-tag-output', $result);
        $this->assertSame([
            [
                'data' => $data,
                'params' => ['quality' => '65'],
                'tagdata' => false,
            ],
        ], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert non-image files fail fast even after replace_webp() normalizes the processing payload.
     *
     * @return void
     */
    public function testReplaceWebpReturnsFalseWhenModelObjectIsNotAnImage()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceWebpSpy::class);
        $modelObject = new FileFtReplaceWebpModelObjectStub(
            'hero.jpg',
            's3-assets',
            '/srv/uploads/hero.jpg',
            false,
            false
        );
        $data = [
            'model_object' => $modelObject,
        ];

        $result = $fieldtype->replace_webp($data, ['quality' => '90'], '{tagdata}');

        $this->assertFalse($result);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert replace_webp() seeds missing processing fields from the file model before fallback.
     *
     * @return void
     */
    public function testReplaceWebpSeedsMissingProcessingFieldsBeforeReplaceTagFallback()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceWebpSpy::class);
        $modelObject = new FileFtReplaceWebpModelObjectStub(
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

        $result = $fieldtype->replace_webp($data, ['quality' => '85'], '{tagdata}');

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
                'params' => ['quality' => '85'],
                'tagdata' => '{tagdata}',
            ],
        ], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert caller-supplied processing fields win over model-derived defaults for modifier chaining.
     *
     * @return void
     */
    public function testReplaceWebpPreservesExplicitProcessingFieldsForModifierChaining()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceWebpSpy::class);
        $modelObject = new FileFtReplaceWebpModelObjectStub(
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

        $result = $fieldtype->replace_webp($data, ['quality' => '70'], null);

        $this->assertSame([
            'model_object' => $modelObject,
            'fs_filename' => 'cached-name.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/tmp/cached-name.jpg',
        ], $result);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert successful webp conversions rewrite the filename extension and return the generated URL.
     *
     * @return void
     */
    public function testReplaceWebpReturnsGeneratedUrlForEditableImages()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $modelObject = new FileFtProcessImageModelObjectStub([
            'filesystem' => $filesystem,
        ]);
        $params = ['quality' => '60'];
        $hash = md5(serialize($params));
        $destinationPath = '/srv/uploads/gallery/_webp/hero_.jpg_webp_' . $hash . '.webp';
        $destinationUrl = 'https://example.com/uploads/gallery/_webp/hero_.jpg_webp_' . $hash . '.webp';
        $data = [
            'model_object' => $modelObject,
            'fs_filename' => 'hero.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/srv/uploads/gallery/hero.jpg',
        ];

        $result = $fieldtype->replace_webp($data, $params, false);

        $this->assertSame($destinationUrl, $result);
        $this->assertSame(['webp'], $modelObject->manipulationUrlCalls);
        $this->assertSame($destinationPath, $filesystem->writeStreamCalls[0]['path']);
        $this->assertSame('hero.jpg', $modelObject->file_name);
        $this->assertSame(60, $this->imageLibMock->initializeCalls[0]['quality']);
    }
}
