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

class FileFtConstructTest extends FileFtTestBase
{
    /**
     * Assert the lightweight constructor delegates to the parent and loads file_field.
     *
     * @return void
     */
    public function testConstructorCallsParentAndLoadsFileFieldLibrary()
    {
        $fieldtype = new File_ft();

        $this->assertInstanceOf(File_ft::class, $fieldtype);
        $this->assertSame(1, EE_Fieldtype::$constructCount);
        $this->assertSame(['file_field'], $this->loadRecorder->libraries);
    }
}
