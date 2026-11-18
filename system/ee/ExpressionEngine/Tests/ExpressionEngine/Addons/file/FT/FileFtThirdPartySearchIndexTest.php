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

class FileFtThirdPartySearchIndexTest extends FileFtTestBase
{
    /**
     * Provide non-string payloads that must short-circuit before any lookup.
     *
     * @return array<string, array{0: mixed}>
     */
    public function nonStringSearchIndexDataProvider()
    {
        return [
            'null' => [null],
            'integer' => [17],
            'array' => [['file' => 'value']],
        ];
    }

    /**
     * Assert third_party_search_index() ignores non-string payloads.
     *
     * @dataProvider nonStringSearchIndexDataProvider
     * @param mixed $data
     * @return void
     */
    public function testThirdPartySearchIndexReturnsEmptyStringForNonStringPayloads($data)
    {
        $fieldtype = $this->makeFieldtype();

        $this->assertSame('', $fieldtype->third_party_search_index($data));
        $this->assertSame([], $this->modelService->queries);
    }

    /**
     * Assert third_party_search_index() appends the title when it differs from the filename.
     *
     * @return void
     */
    public function testThirdPartySearchIndexReturnsFileNameAndDistinctTitleForFileTagPayloads()
    {
        $fieldtype = $this->makeFieldtype();
        $this->modelService->setFirstResult('File', 42, (object) [
            'file_name' => 'brochure.pdf',
            'title' => 'Sales Brochure',
        ]);

        $this->assertSame(
            'brochure.pdf Sales Brochure',
            $fieldtype->third_party_search_index('{file:42:url}')
        );
        $this->assertSame([
            [
                'model' => 'File',
                'id' => '42',
            ],
        ], $this->modelService->queries);
    }

    /**
     * Assert third_party_search_index() avoids duplicating identical file titles.
     *
     * @return void
     */
    public function testThirdPartySearchIndexReturnsOnlyFileNameWhenTitleMatchesFileName()
    {
        $fieldtype = $this->makeFieldtype();
        $this->modelService->setFirstResult('File', 99, (object) [
            'file_name' => 'logo.svg',
            'title' => 'logo.svg',
        ]);

        $this->assertSame('logo.svg', $fieldtype->third_party_search_index('{file:99:url}'));
    }

    /**
     * Assert third_party_search_index() returns an empty string when the tagged file is missing.
     *
     * @return void
     */
    public function testThirdPartySearchIndexReturnsEmptyStringWhenTaggedFileLookupFails()
    {
        $fieldtype = $this->makeFieldtype();

        $this->assertSame('', $fieldtype->third_party_search_index('{file:24:url}'));
        $this->assertSame([
            [
                'model' => 'File',
                'id' => '24',
            ],
        ], $this->modelService->queries);
    }

    /**
     * Assert third_party_search_index() strips file directory tags before indexing filenames.
     *
     * @return void
     */
    public function testThirdPartySearchIndexStripsFileDirectoryPrefixFromTaggedPaths()
    {
        $fieldtype = $this->makeFieldtype();

        $this->assertSame('manuals/install-guide.pdf', $fieldtype->third_party_search_index(
            '{filedir_7}manuals/install-guide.pdf'
        ));
        $this->assertSame([], $this->modelService->queries);
    }

    /**
     * Assert third_party_search_index() preserves plain string values verbatim.
     *
     * @return void
     */
    public function testThirdPartySearchIndexReturnsPlainStringsUnchanged()
    {
        $fieldtype = $this->makeFieldtype();

        $this->assertSame('plain search text', $fieldtype->third_party_search_index('plain search text'));
        $this->assertSame([], $this->modelService->queries);
    }
}
