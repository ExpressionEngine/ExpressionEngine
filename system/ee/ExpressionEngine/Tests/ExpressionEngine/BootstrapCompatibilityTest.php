<?php

namespace ExpressionEngine\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class BootstrapCompatibilityTest extends TestCase
{
    /** @dataProvider discoveryOrders */
    public function testDiscoveryUsesTheRealSharedHelpers($files)
    {
        $this->assertFalse(function_exists('file_exists_safe'));
        $suite = new TestSuite();
        foreach ($files as $file) {
            $suite->addTestFile(__DIR__ . '/' . $file);
        }

        $helper = new \ReflectionFunction('file_exists_safe');
        $this->assertSame(
            realpath(SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php'),
            realpath($helper->getFileName())
        );
        $this->assertTrue(file_exists_safe(__FILE__));
        $this->assertFalse(file_exists_safe(__FILE__ . "\0suffix"));
    }

    public function discoveryOrders()
    {
        $wizard = 'Installer/Controllers/WizardTest.php';
        $mime = 'Library/Mime/MimeTypeTest.php';

        return [
            'installer first' => [[$wizard, $mime]],
            'MIME first' => [[$mime, $wizard]],
        ];
    }
}
