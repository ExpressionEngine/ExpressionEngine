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

class FileFtReplaceRot13Test extends FileFtTestBase
{
    /**
     * Assert replace_rot13() rotates the entire stored url value verbatim.
     *
     * @return void
     */
    public function testReplaceRot13TransformsEntireStoredUrlValue()
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_rot13([
            'url' => '{filedir_8}manual.pdf?dl=Y',
            'raw_output' => '{filedir_8}ignored.pdf',
            'filename' => 'ignored.pdf',
        ], [
            'unused' => 'value',
        ], '{ignored-tagdata}');

        $this->assertSame('{svyrqve_8}znahny.cqs?qy=L', $result);
    }

    /**
     * Assert replace_rot13() keeps the empty-url boundary instead of falling back.
     *
     * @return void
     */
    public function testReplaceRot13ReturnsEmptyStringForEmptyUrl()
    {
        $fieldtype = $this->makeFieldtype();

        $result = $fieldtype->replace_rot13([
            'url' => '',
            'raw_output' => '{filedir_2}fallback.pdf',
            'filename' => 'fallback.pdf',
        ]);

        $this->assertSame('', $result);
    }
}
