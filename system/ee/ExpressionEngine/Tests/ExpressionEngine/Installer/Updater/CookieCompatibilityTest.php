<?php

namespace ExpressionEngine\Tests\Installer\Updater;

use ExpressionEngine\Updater\Service\Updater\RequestAuthorization;
use PHPUnit\Framework\TestCase;

class CookieCompatibilityTest extends TestCase
{
    /** @dataProvider runtimeProvider */
    public function testCookieArgumentsPreserveSecurityOptionsOnBothPhpSignatures($version)
    {
        $authorization = new class extends RequestAuthorization {
            public function argumentsForVersion($options, $version)
            {
                return $this->cookieArguments('ee_updater_test', 'test-token', $options, $version);
            }
        };
        $options = ['expires' => 0, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict'];
        $expected = $version < 70300
            ? ['ee_updater_test', 'test-token', 0, '/; SameSite=Strict', '', true, true]
            : ['ee_updater_test', 'test-token', $options];
        $this->assertSame($expected, $authorization->argumentsForVersion($options, $version));
    }

    public function runtimeProvider()
    {
        return [[70205], [70300]];
    }
}
