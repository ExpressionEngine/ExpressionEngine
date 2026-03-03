<?php

require_once __DIR__ . '/../StructureTestBase.php';
require_once __DIR__ . '/../../../../../Addons/structure/Conduit/StaticCache.php';

use ExpressionEngine\Structure\Conduit\StaticCache;

class StaticCacheTest extends StructureTestBase
{
    protected function tearDown(): void
    {
        StaticCache::clear();
        parent::tearDown();
    }

    public function testPutAndGetWithStringKey()
    {
        StaticCache::put('alpha', ['value' => 1]);

        $this->assertSame(['value' => 1], StaticCache::get('alpha'));
        $this->assertTrue(StaticCache::has('alpha'));
    }

    public function testPutWithArrayKeyFiltersEmptySegments()
    {
        $expectedKey = sha1('a:b');
        StaticCache::put(['a', '', null, 'b'], 'payload');

        $all = StaticCache::all();

        $this->assertArrayHasKey($expectedKey, $all);
        $this->assertSame('payload', $all[$expectedKey]);
        $this->assertFalse(StaticCache::get('missing'));
    }

    public function testSetDeleteAndClearUsingProcessedKey()
    {
        StaticCache::set('preprocessed-key', 'v1', true);

        $this->assertSame('v1', StaticCache::get('preprocessed-key', true));
        $this->assertTrue(StaticCache::has('preprocessed-key', true));

        // delete() returns whether key is still present after removal.
        $this->assertFalse(StaticCache::delete('preprocessed-key', true));
        $this->assertFalse(StaticCache::has('preprocessed-key', true));
        $this->assertFalse(StaticCache::get('preprocessed-key', true));

        StaticCache::put('to-clear', 'x');
        $this->assertTrue(StaticCache::clear());
        $this->assertSame([], StaticCache::all());
    }
}

