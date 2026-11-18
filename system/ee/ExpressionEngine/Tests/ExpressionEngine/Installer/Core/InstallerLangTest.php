<?php

namespace ExpressionEngine\Tests\ExpressionEngine\Installer\Core;

use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/legacy/core/Lang.php';
require_once SYSPATH . 'ee/installer/core/Installer_Lang.php';

class InstallerLangTest extends TestCase
{
    public function testGetIdiomAlwaysReturnsEnglish()
    {
        $reflection = new \ReflectionClass(\Installer_Lang::class);
        $lang = $reflection->newInstanceWithoutConstructor();

        $this->assertSame('english', $lang->getIdiom());
    }
}
