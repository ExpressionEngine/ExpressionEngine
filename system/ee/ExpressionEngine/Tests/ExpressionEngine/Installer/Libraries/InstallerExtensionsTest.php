<?php

namespace ExpressionEngine\Tests\ExpressionEngine\Installer\Libraries;

use PHPUnit\Framework\TestCase;

if (! defined('EE_APPPATH')) {
    define('EE_APPPATH', APPPATH);
}

require_once SYSPATH . 'ee/installer/libraries/Extensions.php';

class InstallerExtensionsTest extends TestCase
{
    public function testCallDoesNotRunAnyExtensionHooks()
    {
        $reflection = new \ReflectionClass(\Installer_Extensions::class);
        $extensions = $reflection->newInstanceWithoutConstructor();

        $this->assertNull($extensions->call('any_hook_name'));
    }
}
