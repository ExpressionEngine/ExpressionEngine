<?php

namespace Illuminate\Tests;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Str;

class StrTest extends TestCase
{
    public function testPluralizer()
    {
        $this->assertEquals('houses', Str::plural('house'));

        $this->assertEquals('bison', Str::plural('bison'));

        $this->assertEquals('people', Str::plural('people'));

        $this->assertEquals('feet', Str::plural('foot'));

        $this->assertEquals('foot', Str::singular('feet'));
    }

    public function testStart()
    {
        $this->assertEquals('what house', Str::start('house', 'what '));
    }

    public function testCache()
    {
        $this->assertEquals('my_house', Str::snake('my house'));
        $this->assertEquals('my_house', Str::snake('my house'));

        $this->assertEquals('MyHouse', Str::studly('my house'));
        $this->assertEquals('MyHouse', Str::studly('my house'));

        $this->assertEquals('myHouse', Str::camel('my house'));
        $this->assertEquals('myHouse', Str::camel('my house'));
    }

    public function testLength()
    {
        $this->assertEquals(8, Str::length('my house'));
        $this->assertEquals(8, Str::length('my house', 'UTF-8'));
    }

    public function testLimit()
    {
        $this->assertEquals('my house', Str::limit('my house'));
    }

    public function testIs()
    {
        $this->assertFalse(Str::is([], 'pattern'));
    }
}
