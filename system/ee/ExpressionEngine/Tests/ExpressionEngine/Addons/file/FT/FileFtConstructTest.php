<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\EntryManager {
    if (!interface_exists(ColumnInterface::class, false)) {
        interface ColumnInterface
        {
        }
    }
}

namespace {
    use PHPUnit\Framework\TestCase;

    if (!class_exists('EE_Fieldtype')) {
        abstract class EE_Fieldtype
        {
            public static $constructCount = 0;

            public function __construct()
            {
                self::$constructCount++;
            }
        }
    }

    require_once dirname(__DIR__, 5) . '/Addons/file/ft.file.php';

    class FileFtLoadRecorder
    {
        public $libraries = [];

        public function library($name)
        {
            $this->libraries[] = $name;
        }
    }

    class FileFtConstructTest extends TestCase
    {
        /** @var FileFtLoadRecorder */
        private $loadRecorder;

        protected function setUp(): void
        {
            parent::setUp();

            ee()->resetMocks();

            EE_Fieldtype::$constructCount = 0;
            $this->loadRecorder = new FileFtLoadRecorder();

            ee()->setMock('load', $this->loadRecorder);
        }

        protected function tearDown(): void
        {
            ee()->resetMocks();

            parent::tearDown();
        }

        public function testConstructorCallsParentAndLoadsFileFieldLibrary()
        {
            $fieldtype = new File_ft();

            $this->assertInstanceOf(File_ft::class, $fieldtype);
            $this->assertSame(1, EE_Fieldtype::$constructCount);
            $this->assertSame(['file_field'], $this->loadRecorder->libraries);
        }
    }
}
