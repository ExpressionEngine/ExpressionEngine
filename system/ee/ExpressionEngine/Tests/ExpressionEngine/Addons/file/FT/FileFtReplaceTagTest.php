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

class FileFtReplaceTagTest extends FileFtTestBase
{
    /**
     * Assert empty tag pairs still run through template parsing before falling back to an empty string.
     *
     * @return void
     */
    public function testReplaceTagParsesEmptyTagPairsBeforeReturningEmptyString()
    {
        $fieldtype = $this->makeFieldtype();

        $this->assertSame('', $fieldtype->replace_tag(false, [], '{file:url}'));
        $this->assertSame([
            [
                'tagdata' => '{file:url}',
                'variables' => [],
            ],
        ], $this->templateMock->parseVariablesCalls);
        $this->assertSame([], $this->fileFieldMock->parseStringCalls);
    }

    /**
     * Assert the raw-output flag bypasses the URL parsing paths.
     *
     * @return void
     */
    public function testReplaceTagReturnsRawOutputWhenRequested()
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_tag([
            'raw_output' => '{"file_id":42}',
            'url' => '{filedir_4}ignored.pdf',
        ], [
            'raw_output' => 'yes',
        ]);

        $this->assertSame('{"file_id":42}', $result);
        $this->assertSame([], $this->templateMock->parseVariablesCalls);
        $this->assertSame([], $this->fileFieldMock->parseStringCalls);
    }

    /**
     * Assert tag pairs receive the augmented file variables used by legacy templates.
     *
     * @return void
     */
    public function testReplaceTagParsesTagPairsWithThumbAndIdPathMetadata()
    {
        $fieldtype = $this->makeFieldtype();
        $this->templateMock->parseVariablesReturn = 'parsed-tag-pair';

        $result = $fieldtype->replace_tag([
            'path' => 'https://assets.example.com/files/',
            'filename' => 'banner',
            'extension' => 'png',
            'file_id' => 42,
        ], [], '{url:thumbs}|{id_path}');

        $this->assertSame('parsed-tag-pair', $result);
        $this->assertSame([
            [
                'tagdata' => '{url:thumbs}|{id_path}',
                'variables' => [
                    [
                        'path' => 'https://assets.example.com/files/',
                        'filename' => 'banner',
                        'extension' => 'png',
                        'file_id' => 42,
                        'url:thumbs' => 'https://assets.example.com/files/_thumbs/banner.png',
                        'id_path' => ['/42', ['path_variable' => true]],
                    ],
                ],
            ],
        ], $this->templateMock->parseVariablesCalls);
    }

    /**
     * Assert URL-based file data is expanded through file_field parsing for single tags.
     *
     * @return void
     */
    public function testReplaceTagReturnsParsedUrlForSingleTags()
    {
        $fieldtype = $this->makeFieldtype();
        $this->fileFieldMock->parseStringReturn = 'https://cdn.example.com/files/manual.pdf';

        $result = $fieldtype->replace_tag([
            'url' => '{filedir_3}manual.pdf',
        ]);

        $this->assertSame('https://cdn.example.com/files/manual.pdf', $result);
        $this->assertSame(['{filedir_3}manual.pdf'], $this->fileFieldMock->parseStringCalls);
        $this->assertSame(['file_field', 'file_field'], $this->loadRecorder->libraries);
    }

    /**
     * Assert wrapped URL-based file data still flows through the same parsed URL.
     *
     * @return void
     */
    public function testReplaceTagReturnsWrappedParsedUrlWhenWrapParameterIsPresent()
    {
        $fieldtype = $this->makeFieldtype();
        $this->fileFieldMock->parseStringReturn = 'https://cdn.example.com/files/spec-sheet.pdf';

        $result = $fieldtype->replace_tag([
            'url' => '{filedir_5}spec-sheet.pdf',
        ], [
            'wrap' => 'plain',
        ]);

        $this->assertSame('https://cdn.example.com/files/spec-sheet.pdf', $result);
        $this->assertSame(['{filedir_5}spec-sheet.pdf'], $this->fileFieldMock->parseStringCalls);
    }

    /**
     * Assert legacy file data still builds a path when no parsed URL is available.
     *
     * @return void
     */
    public function testReplaceTagBuildsLegacyPathWhenParsedUrlIsMissing()
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_tag([
            'path' => '/uploads/files/',
            'filename' => 'guide',
            'extension' => 'pdf',
            'file_id' => 77,
        ]);

        $this->assertSame('/uploads/files/guide.pdf', $result);
        $this->assertSame([], $this->fileFieldMock->parseStringCalls);
    }

    /**
     * Assert wrapped legacy file data preserves the composed fallback path.
     *
     * @return void
     */
    public function testReplaceTagWrapsLegacyPathWhenWrapParameterIsPresent()
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_tag([
            'path' => '/uploads/files/',
            'filename' => 'brochure',
            'extension' => 'pdf',
            'file_id' => 88,
        ], [
            'wrap' => 'plain',
        ]);

        $this->assertSame('/uploads/files/brochure.pdf', $result);
    }

    /**
     * Assert unsupported payloads collapse to an empty string instead of leaking partial data.
     *
     * @return void
     */
    public function testReplaceTagReturnsEmptyStringWhenNoRenderableFileDataExists()
    {
        $fieldtype = $this->makeFieldtype();

        $this->assertSame('', $fieldtype->replace_tag([]));
        $this->assertSame([], $this->templateMock->parseVariablesCalls);
        $this->assertSame([], $this->fileFieldMock->parseStringCalls);
    }
}
