<?php

require_once __DIR__ . '/StructureTestBase.php';

class StructurePaginateTest extends StructureTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        ee()->setMock('load', new class {
            public function library($name) {}
        });
        ee()->setMock('logger', new class {
            public $last;
            public function developer($msg) { $this->last = $msg; }
        });
    }

    public function testPaginateReturnsFalseAndLogsMessage()
    {
        $result = $this->structure->paginate();
        $this->assertFalse($result);
        $this->assertNotEmpty(ee()->logger->last);
        $this->assertStringContainsString('deprecated', strtolower(ee()->logger->last));
    }
}



