<?php

namespace ExpressionEngine\Tests\ExpressionEngine\Installer\Libraries;

use PHPUnit\Framework\TestCase;

class InstallerExtensionsTest extends TestCase
{
    public function testCallDoesNotRunAnyExtensionHooks()
    {
        if (! defined('EE_APPPATH')) {
            define('EE_APPPATH', APPPATH);
        }

        require_once SYSPATH . 'ee/installer/libraries/Extensions.php';

        $reflection = new \ReflectionClass(\Installer_Extensions::class);
        $extensions = $reflection->newInstanceWithoutConstructor();

        $this->assertNull($extensions->call('any_hook_name'));
    }
}
