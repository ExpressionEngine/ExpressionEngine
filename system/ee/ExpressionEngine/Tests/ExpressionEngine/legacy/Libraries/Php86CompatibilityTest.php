<?php

namespace ExpressionEngine\Tests\legacy\Libraries;

use PHPUnit\Framework\TestCase;

class Php86CompatibilityTest extends TestCase
{
    public function testStandaloneCompatibilitySuite()
    {
        $script = dirname(__DIR__, 3) . '/support/php_compatibility.php';
        $output = array();
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' 2>&1', $output, $status);
        $this->assertSame(0, $status, implode("\n", $output));
        $result = json_decode(implode("\n", $output), true);
        $this->assertIsArray($result);
        $this->assertSame(array(), $result['failed']);
    }
}
