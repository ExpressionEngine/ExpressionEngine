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

class FileFtReplaceCropUploadDestinationStub
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

class FileFtReplaceCropModelObjectStub
{
    /** @var string */
    public $file_name;

    /** @var FileFtReplaceCropUploadDestinationStub */
    public $UploadDestination;

    /** @var bool */
    private $isImage;

    /** @var bool */
    private $isEditableImage;

    /** @var string */
    private $absolutePath;

    /**
     * Seed the file model data consumed by replace_crop().
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
        $this->UploadDestination = new FileFtReplaceCropUploadDestinationStub($filesystem);
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

class FileFtReplaceCropSpy extends File_ft
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
     * @param string $modifier
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

class FileFtReplaceCropTest extends FileFtTestBase
{
    /**
     * Assert empty params delegate to the crop catchall path for named manipulations.
     *
     * @return void
     */
    public function testReplaceCropDelegatesToCatchallWhenParamsAreEmpty()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceCropSpy::class);

        $result = $fieldtype->replace_crop(['file_id' => 4], [], '{file:url}');

        $this->assertSame('catchall-output', $result);
        $this->assertSame([
            [
                'data' => ['file_id' => 4],
                'params' => [],
                'tagdata' => '{file:url}',
                'manipulation' => 'crop',
            ],
        ], $fieldtype->replaceTagCatchallCalls);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert wrap-only params still resolve through the named crop manipulation catchall.
     *
     * @return void
     */
    public function testReplaceCropDelegatesToCatchallWhenWrapIsOnlyParam()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceCropSpy::class);

        $result = $fieldtype->replace_crop(['file_id' => 7], ['wrap' => 'plain'], false);

        $this->assertSame('catchall-output', $result);
        $this->assertSame([
            [
                'data' => ['file_id' => 7],
                'params' => ['wrap' => 'plain'],
                'tagdata' => false,
                'manipulation' => 'crop',
            ],
        ], $fieldtype->replaceTagCatchallCalls);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert empty payloads fall back to replace_tag() instead of attempting image processing.
     *
     * @return void
     */
    public function testReplaceCropFallsBackToReplaceTagWhenDataIsEmpty()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceCropSpy::class);

        $result = $fieldtype->replace_crop([], ['width' => '1200'], '{file:url}');

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
    public function testReplaceCropFallsBackToReplaceTagWhenModelObjectIsMissing()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceCropSpy::class);
        $data = ['file_id' => 11, 'url' => '{filedir_3}manual.pdf'];

        $result = $fieldtype->replace_crop($data, ['height' => '600'], false);

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
     * Assert non-image files fail fast even after replace_crop() normalizes the processing payload.
     *
     * @return void
     */
    public function testReplaceCropReturnsFalseWhenModelObjectIsNotAnImage()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceCropSpy::class);
        $modelObject = new FileFtReplaceCropModelObjectStub(
            'hero.jpg',
            's3-assets',
            '/srv/uploads/hero.jpg',
            false,
            false
        );
        $data = [
            'model_object' => $modelObject,
        ];

        $result = $fieldtype->replace_crop($data, ['width' => '900'], '{tagdata}');

        $this->assertFalse($result);
        $this->assertSame([], $fieldtype->replaceTagCatchallCalls);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert replace_crop() seeds missing processing fields from the file model before delegating.
     *
     * @return void
     */
    public function testReplaceCropSeedsMissingProcessingFieldsBeforeReplaceTagFallback()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceCropSpy::class);
        $modelObject = new FileFtReplaceCropModelObjectStub(
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

        $result = $fieldtype->replace_crop($data, ['width' => '900', 'quality' => '80'], '{tagdata}');

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
    public function testReplaceCropPreservesExplicitProcessingFieldsForModifierChaining()
    {
        $fieldtype = $this->makeFieldtype([], 0, 'file_field', FileFtReplaceCropSpy::class);
        $modelObject = new FileFtReplaceCropModelObjectStub(
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

        $result = $fieldtype->replace_crop($data, ['width' => '320'], null);

        $this->assertSame([
            'model_object' => $modelObject,
            'fs_filename' => 'cached-name.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/tmp/cached-name.jpg',
        ], $result);
        $this->assertSame([], $fieldtype->replaceTagCatchallCalls);
        $this->assertSame([], $fieldtype->replaceTagCalls);
    }

    /**
     * Assert crop processing centers the requested box and parses tag-pair variables.
     *
     * @return void
     */
    public function testReplaceCropCentersPositionedSelectionsAndParsesTagPairs()
    {
        $fieldtype = $this->makeFieldtype();
        $filesystem = new FileFtProcessImageFilesystemStub();
        $modelObject = new FileFtProcessImageModelObjectStub([
            'filesystem' => $filesystem,
        ]);
        $params = [
            'position' => 'center',
            'width' => '50',
            'height' => '20',
            'x' => '5',
            'y' => '7',
        ];
        $hash = md5(serialize($params));
        $destinationPath = '/srv/uploads/gallery/_crop/hero_crop_' . $hash . '.jpg';
        $destinationUrl = 'https://example.com/uploads/gallery/_crop/hero_crop_' . $hash . '.jpg';
        $data = [
            'model_object' => $modelObject,
            'fs_filename' => 'hero.jpg',
            'filesystem' => $filesystem,
            'source_image' => '/srv/uploads/gallery/hero.jpg',
        ];
        $this->templateMock->parseVariablesReturn = 'cropped-template';

        $result = $fieldtype->replace_crop($data, $params, '{file}{url}:{width}x{height}{/file}');

        $this->assertSame('cropped-template', $result);
        $this->assertSame(['/srv/uploads/gallery/_crop' . DIRECTORY_SEPARATOR], $filesystem->mkdirCalls);
        $this->assertSame(['/srv/uploads/gallery/_crop' . DIRECTORY_SEPARATOR], $filesystem->addIndexHtmlCalls);
        $this->assertCount(1, $this->imageLibMock->initializeCalls);
        $this->assertEquals(80, $this->imageLibMock->initializeCalls[0]['x_axis']);
        $this->assertEquals(47, $this->imageLibMock->initializeCalls[0]['y_axis']);
        $this->assertSame([
            [
                'tagdata' => '{file}{url}:{width}x{height}{/file}',
                'variables' => [[
                    'url' => $destinationUrl,
                    'width' => 200,
                    'height' => 100,
                ]],
            ],
        ], $this->templateMock->parseVariablesCalls);
        $this->assertSame($destinationPath, $filesystem->writeStreamCalls[0]['path']);
    }
}
