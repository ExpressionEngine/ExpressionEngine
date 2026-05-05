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

if (!function_exists('anchor')) {
    /**
     * Provide the url helper anchor() API needed by File_ft::_wrap_it().
     *
     * @param string|array $url
     * @param string $title
     * @param string|array $attributes
     * @return string
     */
    function anchor($url = '', $title = '', $attributes = '')
    {
        if (is_array($url)) {
            $url = implode('/', $url);
        }

        $attributeMarkup = '';
        if (is_array($attributes)) {
            $pairs = [];
            foreach ($attributes as $key => $value) {
                $pairs[] = $key . '="' . $value . '"';
            }
            $attributeMarkup = ! empty($pairs) ? ' ' . implode(' ', $pairs) : '';
        } elseif ($attributes !== '') {
            $attributeMarkup = ' ' . $attributes;
        }

        return '<a href="' . $url . '"' . $attributeMarkup . '>' . $title . '</a>';
    }
}

class FileFtReplaceTagCatchallLoadRecorder extends FileFtLoadRecorder
{
    /** @var array<int, string> */
    public $helpers = [];

    /**
     * Record requested helpers.
     *
     * @param string $name
     * @return void
     */
    public function helper($name)
    {
        $this->helpers[] = $name;
    }
}

class FileFtReplaceTagCatchallTest extends FileFtTestBase
{
    /**
     * Assert missing modifiers leave the catchall path unresolved.
     *
     * @return void
     */
    public function testReplaceTagCatchallReturnsNullWhenModifierIsMissing()
    {
        $fieldtype = $this->makeFieldtype();

        $this->assertNull($fieldtype->replace_tag_catchall([
            'url' => 'https://cdn.example.com/files/manual.pdf',
        ]));
    }

    /**
     * Assert the frontedit modifier returns the original tagdata without further processing.
     *
     * @return void
     */
    public function testReplaceTagCatchallReturnsTagdataForFronteditModifier()
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_tag_catchall([
            'url' => 'https://cdn.example.com/files/manual.pdf',
        ], [], '{frontedit}', 'frontedit');

        $this->assertSame('{frontedit}', $result);
    }

    /**
     * Assert the thumbs modifier derives the legacy thumbnail path from file metadata.
     *
     * @return void
     */
    public function testReplaceTagCatchallBuildsThumbPathFromFileMetadata()
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_tag_catchall([
            'url' => 'https://cdn.example.com/files/banner.png',
            'path' => 'https://cdn.example.com/files/',
            'filename' => 'banner',
            'extension' => 'png',
        ], [], false, 'thumbs');

        $this->assertSame('https://cdn.example.com/files/_thumbs/banner.png', $result);
    }

    /**
     * Assert incomplete thumbnail metadata falls back to the original file URL.
     *
     * @return void
     */
    public function testReplaceTagCatchallFallsBackToOriginalUrlWhenThumbMetadataIsIncomplete()
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_tag_catchall([
            'url' => 'https://cdn.example.com/files/banner.png',
            'path' => 'https://cdn.example.com/files/',
            'filename' => 'banner',
        ], [], false, 'thumbs');

        $this->assertSame('https://cdn.example.com/files/banner.png', $result);
    }

    /**
     * Assert missing thumbnail path metadata short-circuits back to the original file URL.
     *
     * @return void
     */
    public function testReplaceTagCatchallFallsBackToOriginalUrlWhenThumbPathIsMissing()
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_tag_catchall([
            'url' => 'https://cdn.example.com/files/banner.png',
            'filename' => 'banner',
            'extension' => 'png',
        ], [], false, 'thumbs');

        $this->assertSame('https://cdn.example.com/files/banner.png', $result);
    }

    /**
     * Assert missing thumbnail filename metadata short-circuits back to the original file URL.
     *
     * @return void
     */
    public function testReplaceTagCatchallFallsBackToOriginalUrlWhenThumbFilenameIsMissing()
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_tag_catchall([
            'url' => 'https://cdn.example.com/files/banner.png',
            'path' => 'https://cdn.example.com/files/',
            'extension' => 'png',
        ], [], false, 'thumbs');

        $this->assertSame('https://cdn.example.com/files/banner.png', $result);
    }

    /**
     * Assert named manipulations prefer the modifier-specific URL when one is present.
     *
     * @return void
     */
    public function testReplaceTagCatchallReturnsNamedModifierUrl()
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_tag_catchall([
            'url' => 'https://cdn.example.com/files/banner.png',
            'url:large' => 'https://cdn.example.com/files/_large/banner.png',
        ], [], false, 'large');

        $this->assertSame('https://cdn.example.com/files/_large/banner.png', $result);
    }

    /**
     * Assert unknown manipulations keep the original file URL instead of returning an empty string.
     *
     * @return void
     */
    public function testReplaceTagCatchallFallsBackToOriginalUrlWhenModifierUrlIsMissing()
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_tag_catchall([
            'url' => 'https://cdn.example.com/files/banner.png',
        ], [], false, 'large');

        $this->assertSame('https://cdn.example.com/files/banner.png', $result);
    }

    /**
     * Assert null tagdata returns the prepared manipulation payload using the cached filename when present.
     *
     * @return void
     */
    public function testReplaceTagCatchallReturnsPreparedManipulationPayloadUsingExistingFilename()
    {
        $fieldtype = $this->makeFieldtype();
        $data = [
            'url' => 'https://cdn.example.com/files/banner.png',
            'url:large' => 'https://cdn.example.com/files/_large/banner.png',
            'path:large' => '/srv/uploads/_large/banner.png',
            'fs_filename' => 'banner.png',
            'model_object' => (object) ['file_name' => 'ignored.png'],
        ];

        $result = $fieldtype->replace_tag_catchall($data, [], null, 'large');

        $this->assertSame([
            'url' => 'https://cdn.example.com/files/_large/banner.png',
            'url:large' => 'https://cdn.example.com/files/_large/banner.png',
            'path:large' => '/srv/uploads/_large/banner.png',
            'fs_filename' => 'large_banner.png',
            'model_object' => $data['model_object'],
            'source_image' => '/srv/uploads/_large/banner.png',
        ], $result);
    }

    /**
     * Assert null tagdata falls back to the model filename when no cached filename exists yet.
     *
     * @return void
     */
    public function testReplaceTagCatchallReturnsPreparedManipulationPayloadUsingModelFilenameFallback()
    {
        $fieldtype = $this->makeFieldtype();
        $modelObject = (object) ['file_name' => 'hero.jpg'];
        $data = [
            'url' => 'https://cdn.example.com/files/hero.jpg',
            'url:small' => 'https://cdn.example.com/files/_small/hero.jpg',
            'path:small' => '/srv/uploads/_small/hero.jpg',
            'model_object' => $modelObject,
        ];

        $result = $fieldtype->replace_tag_catchall($data, [], null, 'small');

        $this->assertSame([
            'url' => 'https://cdn.example.com/files/_small/hero.jpg',
            'url:small' => 'https://cdn.example.com/files/_small/hero.jpg',
            'path:small' => '/srv/uploads/_small/hero.jpg',
            'model_object' => $modelObject,
            'fs_filename' => 'small_hero.jpg',
            'source_image' => '/srv/uploads/_small/hero.jpg',
        ], $result);
    }

    /**
     * Assert null tagdata preserves the incoming payload when no manipulation source path exists.
     *
     * @return void
     */
    public function testReplaceTagCatchallReturnsOriginalPayloadWhenManipulationPathIsMissing()
    {
        $fieldtype = $this->makeFieldtype();
        $data = [
            'url' => 'https://cdn.example.com/files/banner.png',
            'url:large' => 'https://cdn.example.com/files/_large/banner.png',
            'model_object' => (object) ['file_name' => 'banner.png'],
        ];

        $this->assertSame($data, $fieldtype->replace_tag_catchall($data, [], null, 'large'));
    }

    /**
     * Assert null tagdata preserves non-array payloads when no manipulation metadata can be applied.
     *
     * @return void
     */
    public function testReplaceTagCatchallReturnsScalarPayloadWhenNullTagdataReceivesNonArrayData()
    {
        $fieldtype = $this->makeFieldtype();

        $this->assertFalse($fieldtype->replace_tag_catchall(false, [], null, 'large'));
    }

    /**
     * Assert empty payloads preserve tagdata instead of leaking an empty file path.
     *
     * @return void
     */
    public function testReplaceTagCatchallReturnsTagdataWhenPayloadIsEmpty()
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_tag_catchall([], [], '{fallback}', 'large');

        $this->assertSame('{fallback}', $result);
    }

    /**
     * Assert link wrapping uses the modifier URL and preserves the file link metadata.
     *
     * @return void
     */
    public function testReplaceTagCatchallWrapsModifierUrlAsLink()
    {
        $loadRecorder = new FileFtReplaceTagCatchallLoadRecorder();
        ee()->setMock('load', $loadRecorder);
        $this->loadRecorder = $loadRecorder;
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_tag_catchall([
            'filename' => 'Guide Download',
            'file_pre_format' => '<p>',
            'file_post_format' => '</p>',
            'file_properties' => 'class="download" rel="noopener"',
            'url:manual' => 'https://cdn.example.com/files/final-guide.pdf',
        ], [
            'wrap' => 'link',
        ], false, 'manual');

        $this->assertSame(
            '<p><a href="https://cdn.example.com/files/final-guide.pdf" class="download" rel="noopener">Guide Download</a></p>',
            $result
        );
        $this->assertSame(['url_helper'], $loadRecorder->helpers);
    }
}
