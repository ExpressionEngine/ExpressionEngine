<?php

require_once __DIR__ . '/../ProSearchTestBase.php';
require_once PATH_ADDONS . 'pro_search/libraries/Pro_multibyte.php';

if (!defined('MB_ENABLED')) {
    define('MB_ENABLED', extension_loaded('mbstring'));
}

class ProMultibyteTest extends ProSearchTestBase
{
    protected $mb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mb = new Pro_multibyte();
    }

    public function testStringFunctions()
    {
        // Test basic proxying
        $this->assertEquals(5, $this->mb->strlen('12345'));
        $this->assertEquals('234', $this->mb->substr('12345', 1, 3));
        $this->assertEquals(1, $this->mb->strpos('12345', '2'));
        $this->assertEquals('abc', $this->mb->strtolower('ABC'));
    }

    public function testStaticCall()
    {
        $this->assertEquals(5, Pro_multibyte::strlen('12345'));
    }

    public function testInvalidFunction()
    {
        $this->expectException(Exception::class);
        $this->mb->nonExistentFunction();
    }
}


