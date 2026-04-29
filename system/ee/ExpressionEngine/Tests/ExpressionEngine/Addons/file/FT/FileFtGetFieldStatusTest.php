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

class FileFtGetFieldStatusTest extends FileFtTestBase
{
    /**
     * Assert unresolved field data keeps the default ok status.
     *
     * @return void
     */
    public function testGetFieldStatusReturnsOkWhenFieldDataDoesNotResolveToAFile()
    {
        $fieldtype = $this->makeFieldtype();

        $this->assertSame('ok', $fieldtype->get_field_status('{filedir_3}missing.pdf'));
        $this->assertSame(['{filedir_3}missing.pdf'], $this->fileFieldMock->calls);
    }

    /**
     * Assert resolved files that still exist report the ok status.
     *
     * @return void
     */
    public function testGetFieldStatusReturnsOkWhenResolvedFileExists()
    {
        $fieldtype = $this->makeFieldtype();
        $file = new class(true) {
            /** @var bool */
            private $existsResult;

            /** @var int */
            public $existsCalls = 0;

            /**
             * Seed the existence result returned to get_field_status().
             *
             * @param bool $existsResult
             * @return void
             */
            public function __construct($existsResult)
            {
                $this->existsResult = $existsResult;
            }

            /**
             * Return the configured file-existence state.
             *
             * @return bool
             */
            public function exists()
            {
                $this->existsCalls++;

                return $this->existsResult;
            }
        };

        $this->setFileModel($file);

        $this->assertSame('ok', $fieldtype->get_field_status('{filedir_9}manual.pdf'));
        $this->assertSame(['{filedir_9}manual.pdf'], $this->fileFieldMock->calls);
        $this->assertSame(1, $file->existsCalls);
    }

    /**
     * Assert resolved files that no longer exist report the warning status.
     *
     * @return void
     */
    public function testGetFieldStatusReturnsWarningWhenResolvedFileIsMissing()
    {
        $fieldtype = $this->makeFieldtype();
        $file = new class(false) {
            /** @var bool */
            private $existsResult;

            /** @var int */
            public $existsCalls = 0;

            /**
             * Seed the existence result returned to get_field_status().
             *
             * @param bool $existsResult
             * @return void
             */
            public function __construct($existsResult)
            {
                $this->existsResult = $existsResult;
            }

            /**
             * Return the configured file-existence state.
             *
             * @return bool
             */
            public function exists()
            {
                $this->existsCalls++;

                return $this->existsResult;
            }
        };

        $this->setFileModel($file);

        $this->assertSame('warning', $fieldtype->get_field_status('{filedir_5}archived.zip'));
        $this->assertSame(['{filedir_5}archived.zip'], $this->fileFieldMock->calls);
        $this->assertSame(1, $file->existsCalls);
    }
}
