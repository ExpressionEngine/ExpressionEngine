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

class FileFtPreLoopTest extends FileFtTestBase
{
    /**
     * Assert pre_loop() forwards the full file payload to file_field caching.
     *
     * @return void
     */
    public function testPreLoopDelegatesStructuredFilePayloadToFileFieldCache()
    {
        $fieldtype = $this->makeFieldtype();
        $data = [
            'file_id' => 14,
            'url' => 'https://example.com/images/banner.png',
            'path' => '/var/www/images/',
            'filename' => 'banner',
            'extension' => 'png',
        ];

        $result = $fieldtype->pre_loop($data);

        $this->assertNull($result);
        $this->assertSame([$data], $this->fileFieldMock->cacheDataCalls);
    }

    /**
     * Provide boundary payloads that still must be cached without mutation.
     *
     * @return array<string, array{0: mixed}>
     */
    public function preLoopBoundaryProvider()
    {
        return [
            'empty array' => [[]],
            'false payload' => [false],
        ];
    }

    /**
     * Assert pre_loop() does not short-circuit boundary payloads before caching.
     *
     * @dataProvider preLoopBoundaryProvider
     * @param mixed $data
     * @return void
     */
    public function testPreLoopDelegatesBoundaryPayloadsToFileFieldCache($data)
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->pre_loop($data);

        $this->assertNull($result);
        $this->assertSame([$data], $this->fileFieldMock->cacheDataCalls);
    }
}
