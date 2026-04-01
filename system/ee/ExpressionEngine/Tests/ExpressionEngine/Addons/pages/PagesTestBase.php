<?php

require_once __DIR__ . '/../../../eeObjectMock.php';

use PHPUnit\Framework\TestCase;

abstract class PagesTestBase extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        $_POST = [];
        $_GET = [];
    }

    protected function invokePrivate($object, string $method, array $args = [])
    {
        $ref = new ReflectionMethod($object, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($object, $args);
    }
}
