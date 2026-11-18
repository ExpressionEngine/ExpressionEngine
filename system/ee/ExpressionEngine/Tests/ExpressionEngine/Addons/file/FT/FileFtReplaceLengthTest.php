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

class FileFtReplaceLengthTest extends FileFtTestBase
{
    /**
     * Assert replace_length() returns the stored file_size without coercion.
     *
     * @return void
     */
    public function testReplaceLengthReturnsStoredFileSizeVerbatim()
    {
        $fieldtype = $this->makeFieldtype();
        $data = [
            'file_size' => '001024',
            'url' => '{filedir_1}manual.pdf',
        ];

        $result = $fieldtype->replace_length($data, ['format' => 'kb'], '{ignored}');

        $this->assertSame('001024', $result);
    }

    /**
     * Assert replace_length() preserves the zero-byte boundary value.
     *
     * @return void
     */
    public function testReplaceLengthReturnsZeroByteFileSize()
    {
        $fieldtype = $this->makeFieldtype();

        $this->assertSame(0, $fieldtype->replace_length([
            'file_size' => 0,
        ]));
    }
}
