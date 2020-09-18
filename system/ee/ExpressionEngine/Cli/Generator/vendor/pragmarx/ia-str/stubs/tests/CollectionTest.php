<?php

namespace Illuminate\Tests;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;

class SupportCollectionTest extends TestCase
{
    public function testGetWithNullReturnsNull()
    {
        $collection = new Collection([1, 2, 3]);

        $this->assertNull($collection->get(null));
    }
}
