<?php

namespace Illuminate\Tests;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Arr;

class ArrTest extends TestCase
{
    public function testSet()
    {
        $array = ['products' => ['desk' => ['price' => 100]]];

        Arr::set($array, 'products.desk.price', 200);
        $this->assertEquals(
            ['products' => ['desk' => ['price' => 200]]],
            $array
        );

        Arr::set($array, null, 'new-value');
        $this->assertEquals('new-value', $array);

        $array = ['products' => ['desk' => []]];

        Arr::set($array, 'products.desk.price.net', 300);
        $this->assertEquals(
            ['products' => ['desk' => ['price' => ['net' => 300]]]],
            $array
        );
    }

    public function testShuffle()
    {
        $old = $new = ['products' => ['desk' => ['price' => 100]]];

        $count = 0;

        while ($old == $new && $count++ < 10) {
            $new = Arr::shuffle($new);
        }

        $this->assertNotEquals($old, $new);
    }

    public function testCollapse()
    {
        $data = [['foo', 'bar'], ['baz'], 'not-array'];

        $this->assertEquals(['foo', 'bar', 'baz'], Arr::collapse($data));
    }
}
