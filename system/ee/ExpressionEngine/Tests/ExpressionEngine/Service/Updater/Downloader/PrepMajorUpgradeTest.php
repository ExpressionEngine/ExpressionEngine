<?php

namespace ExpressionEngine\Tests\Service\Updater\Downloader;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Service\Updater\Downloader\PrepMajorUpgrade;
use ExpressionEngine\Service\Updater\Logger;
use ExpressionEngine\Service\Updater\UpdaterException;
use PHPUnit\Framework\TestCase;

class PrepMajorUpgradeTest extends TestCase
{
    /**
     * @dataProvider composerManagedCoreTargets
     */
    public function testComposerManagedCore(string $target, ?string $manifest, ?string $error): void
    {
        $path = dirname(SYSPATH) . '/composer.json';
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('isFile')->with($path)->willReturn($manifest !== null);
        $filesystem->method('read')->with($path)->willReturn($manifest);

        if ($error !== null) {
            $this->expectException(UpdaterException::class);
            $this->expectExceptionMessage($error);
        }

        $prep = new PrepMajorUpgrade($filesystem, $this->createMock(Logger::class));
        $prep->checkComposerManagedCore($target);

        // Nothing was refused
        $this->addToAssertionCount(1);
    }

    public function composerManagedCoreTargets(): array
    {
        // Whichever version these tests run as, one release in its major
        // version and one in the next
        $same = (int) APP_VER . '.99.0';
        $major = ((int) APP_VER + 1) . '.0.0';
        $managed = '{"extra":{"expressionengine":{"managed-core":true}}}';

        return [
            'managed major' => [$major, $managed, 'major_upgrade_use_composer'],
            'managed same major' => [$same, $managed, null],
            'no site manifest' => [$major, null, null],
            'core dependency alone' => [$major, '{"require":{"expressionengine/core":"^7.0"}}', null],
            'explicit false' => [$major, '{"extra":{"expressionengine":{"managed-core":false}}}', null],
            'string is not an opt-in' => [$major, '{"extra":{"expressionengine":{"managed-core":"true"}}}', null],
            'unparsable manifest' => [$major, '{"extra":', 'composer_manifest_invalid'],
            'manifest is a list' => [$major, '[]', 'composer_manifest_invalid'],
            'manifest is an empty object' => [$major, '{}', null],
            'unparsable manifest, same major' => [$same, '{"extra":', null],
        ];
    }
}
