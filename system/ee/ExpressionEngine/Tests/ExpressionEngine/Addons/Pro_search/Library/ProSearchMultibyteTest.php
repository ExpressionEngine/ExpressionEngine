<?php

require_once __DIR__ . '/../ProSearchTestBase.php';
require_once __DIR__ . '/../../../../../Addons/pro_search/libraries/Pro_multibyte.php';

class ProSearchMultibyteTest extends ProSearchTestBase
{
    protected $mb;

    protected function setUp(): void
    {
        parent::setUp();
        if (!defined('MB_ENABLED')) {
            define('MB_ENABLED', extension_loaded('mbstring'));
        }
        $this->mb = new Pro_multibyte();
    }

    public function testStrpos()
    {
        // Tests calls to __call / __callStatic
        $this->assertEquals(0, $this->mb->strpos('foobar', 'foo'));
        $this->assertEquals(3, $this->mb->strpos('foobar', 'bar'));
    }

    public function testSubstr()
    {
        $this->assertEquals('foo', $this->mb->substr('foobar', 0, 3));
        $this->assertEquals('bar', $this->mb->substr('foobar', 3));
    }

    public function testStrtolower()
    {
        $this->assertEquals('foo', $this->mb->strtolower('FOO'));
    }
    
    public function testStaticCall()
    {
        // Pro_multibyte can be called statically too?? No, the class methods are not static, but __callStatic is there.
        // wait, __call calls __callStatic.
        // Code: public static function __callStatic($name, $args)
        
        $this->assertEquals('foo', Pro_multibyte::strtolower('FOO'));
    }
}

