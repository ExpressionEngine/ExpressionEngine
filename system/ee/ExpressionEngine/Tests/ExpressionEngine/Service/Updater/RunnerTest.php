<?php

namespace ExpressionEngine\Tests\Service\Updater;

use ExpressionEngine\Service\Updater\Runner;
use ExpressionEngine\Service\Updater\UpdaterException;
use PHPUnit\Framework\TestCase;

/**
 * The first half of an update: getting ready, fetching the package and
 * unpacking it. The runner changes PHP's limits, so each test has a process
 * of its own.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RunnerTest extends TestCase
{
    private $events;
    private $services;

    protected function setUp(): void
    {
        $this->events = new \ArrayObject();
        $events = $this->events;

        $this->services = [
            'Updater/Logger' => new class($events) {
                private $events;
                public function __construct($events) { $this->events = $events; }
                public function truncate() { $this->events[] = 'log cleared'; }
                public function log($message) {}
            },
            'Updater/Preflight' => new class($events) {
                public $unfinished = false;
                private $events;
                public function __construct($events) { $this->events = $events; }
                public function checkLastUpdateCompleted()
                {
                    $this->events[] = 'last update checked';
                    if ($this->unfinished) {
                        throw new UpdaterException('The last update did not finish', 25);
                    }
                }
                public function checkPermissions() {}
                public function cleanUpOldUpgrades() { $this->events[] = 'old update files removed'; }
                public function checkDiskSpace() {}
                public function stashConfig() {}
            },
            'Updater/PrepMajorUpgrade' => new class($events) {
                private $events;
                public function __construct($events) { $this->events = $events; }
                public function prepMajorIfApplicable($major = null) { $this->events[] = ['prepared for major version', $major]; }
            },
            'Updater/Downloader' => new class($events) {
                public $downloaded = ['path' => 'downloaded.zip', 'signature' => 'download signature', 'hash' => 'download hash'];
                private $events;
                public function __construct($events) { $this->events = $events; }
                public function downloadPackage($url)
                {
                    $this->events[] = 'downloaded';
                    return $this->downloaded;
                }
                public function downloadedPackage()
                {
                    $this->events[] = 'kept details read';
                    return $this->downloaded;
                }
            },
            'Updater/Unpacker' => new class($events) {
                private $events;
                public function __construct($events) { $this->events = $events; }
                public function unzipPackage(array $package) { $this->events[] = ['unpacked', $package]; }
                public function verifyExtractedPackage() {}
                public function checkRequirements() {}
                public function moveUpdater() {}
            },
            'Config' => new class {
                public function getFile()
                {
                    return new class {
                        public function get($key, $default = null) { return $default; }
                        public function set($key, $value, $write = false) {}
                    };
                }
            },
            'extensions' => new class($events) {
                private $events;
                public function __construct($events) { $this->events = $events; }
                public function active_hook($hook)
                {
                    $this->events[] = 'hook checked';
                    return false;
                }
            },
        ];

        foreach ($this->services as $name => $service) {
            ee()->setMock($name, $service);
        }
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testNothingFromAnUnfinishedUpdateIsClearedByTheNextOne()
    {
        $this->services['Updater/Preflight']->unfinished = true;

        try {
            (new Runner())->preflight();
            $this->fail('An update started while the last one was unfinished');
        } catch (UpdaterException $e) {
            $this->assertSame(25, $e->getCode());
        }

        $this->assertSame(['last update checked'], $this->events->getArrayCopy());
    }

    public function testAnUpdateGetsReadyOnceTheLastOneIsKnownToBeFinished()
    {
        (new Runner())->preflight();

        $this->assertSame(
            ['last update checked', 'log cleared', 'hook checked', 'old update files removed'],
            $this->events->getArrayCopy()
        );
    }

    public function testASuppliedPackageIsUsedInPlaceOfADownload()
    {
        $package = ['path' => 'supplied.zip', 'signature' => 'supplied signature', 'hash' => 'supplied hash'];

        $runner = new Runner();
        $runner->usePackage($package, '8.0.1');
        $runner->prepMajorUpgrade();
        $runner->download();
        $runner->unpack();

        $this->assertSame(
            [['prepared for major version', 8], ['unpacked', $package]],
            $this->events->getArrayCopy(),
            'The package\'s own version decides how the site is prepared, and nothing is downloaded'
        );
    }

    public function testADownloadedPackageGoesToUnpackWithTheDetailsItWasVerifiedWith()
    {
        $runner = new Runner();
        $runner->prepMajorUpgrade();
        $runner->download();
        $runner->unpack();

        $this->assertSame(
            [['prepared for major version', null], 'downloaded', ['unpacked', $this->services['Updater/Downloader']->downloaded]],
            $this->events->getArrayCopy()
        );
    }

    public function testUnpackingInALaterRequestUsesWhatTheDownloadKept()
    {
        (new Runner())->unpack();

        $this->assertSame(
            ['kept details read', ['unpacked', $this->services['Updater/Downloader']->downloaded]],
            $this->events->getArrayCopy()
        );
    }
}
