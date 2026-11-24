<?php

namespace ExpressionEngine\Tests\Service\Updater\Downloader;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Service\Updater\Downloader\Preflight;
use ExpressionEngine\Service\Updater\UpdaterException;
use Mockery;

require_once __DIR__ . '/PackageTestBase.php';

/**
 * Follows a downloaded update package through the control panel's update,
 * where each step is a request of its own: one downloads and verifies the
 * package, a later one unpacks it. Nothing passes between the two but the
 * files in the update folder, so every "request" here gets new objects.
 */
class DownloadedPackageTest extends PackageTestBase
{
    public function testAPackageDownloadedInOneRequestIsUnpackedInAnother()
    {
        $release = $this->release('7.5.30');

        $downloaded = $this->download($release);

        $this->assertFileEquals($release['zip'], $this->working . 'ExpressionEngine.zip');
        $this->assertSame(hash_file('sha384', $release['zip']), $downloaded['hash']);
        $this->assertDirectoryDoesNotExist($this->working . 'ExpressionEngine');

        $this->unpackInALaterRequest();

        $this->assertStringContainsString('7.5.30', file_get_contents($this->working . 'ExpressionEngine/system/ee/legacy/libraries/Core.php'));
    }

    public function testTheLaterRequestSeesThePackageAsItWasVerified()
    {
        $downloaded = $this->download($this->release('7.5.30'));

        $this->assertSame($downloaded, $this->makeDownloader()->downloadedPackage());
    }

    /** @dataProvider changesBetweenRequests */
    public function testAPackageThatIsNotAsItWasDownloadedIsNotUnpacked($change, $code)
    {
        $this->download($this->release('7.5.30'));
        $another = $this->release('7.5.29');

        $zip = $this->working . 'ExpressionEngine.zip';
        $kept = $zip . '.sig';

        if ($change === 'what was kept to check it with is gone') {
            unlink($kept);
        } elseif ($change === 'what was kept to check it with is unreadable') {
            file_put_contents($kept, 'not what was saved');
        } elseif ($change === 'its kept signature is replaced with another release\'s') {
            $details = json_decode(file_get_contents($kept), true);
            $details['signature'] = file_get_contents($another['signature']);
            file_put_contents($kept, json_encode($details));
        } elseif ($change === 'the package is altered') {
            file_put_contents($zip, 'x', FILE_APPEND);
        } elseif ($change === 'the package is replaced with another signed release') {
            copy($another['zip'], $zip);
        } elseif ($change === 'the package is gone') {
            unlink($zip);
        }

        try {
            $this->unpackInALaterRequest();
            $this->fail('The package was unpacked although ' . $change);
        } catch (UpdaterException $e) {
            $this->assertEquals($code, $e->getCode());
        }

        $this->assertDirectoryDoesNotExist($this->working . 'ExpressionEngine', 'Files were extracted from a package that was not verified');
    }

    public function changesBetweenRequests()
    {
        return [
            ['what was kept to check it with is gone', 6],
            ['what was kept to check it with is unreadable', 6],
            ['its kept signature is replaced with another release\'s', 7],
            ['the package is altered', 7],
            ['the package is replaced with another signed release', 7],
            ['the package is gone', 7],
        ];
    }

    public function testADownloadThatFailsVerificationLeavesNothingToUnpack()
    {
        $release = $this->release('7.5.30');
        $another = $this->release('7.5.29');

        try {
            $this->download($release, file_get_contents($another['signature']));
            $this->fail('A download was accepted with a signature made for another package');
        } catch (UpdaterException $e) {
            $this->assertEquals(7, $e->getCode());
        }

        $this->assertFileDoesNotExist($this->working . 'ExpressionEngine.zip');
        $this->assertFileDoesNotExist($this->working . 'ExpressionEngine.zip.sig');

        try {
            $this->unpackInALaterRequest();
            $this->fail('A later request unpacked a download that had failed verification');
        } catch (UpdaterException $e) {
            $this->assertEquals(6, $e->getCode());
        }

        $this->assertDirectoryDoesNotExist($this->working . 'ExpressionEngine');
    }

    public function testGettingReadyForAnUpdateClearsWhatTheLastDownloadLeft()
    {
        $this->download($this->release('7.5.30'));

        $this->makePreflight()->cleanUpOldUpgrades();

        $this->assertFileDoesNotExist($this->working . 'ExpressionEngine.zip');
        $this->assertFileDoesNotExist($this->working . 'ExpressionEngine.zip.sig');

        $this->expectException(UpdaterException::class);
        $this->expectExceptionCode(6);

        $this->unpackInALaterRequest();
    }

    /**
     * From the command line, download and unpack happen in one process and can
     * share a filesystem, which remembers what it has read. The package is
     * still checked as it is on disk when it is unpacked.
     */
    public function testAPackageChangedOnDiskIsNotUnpackedLaterInTheSameProcess()
    {
        $filesystem = new Filesystem();

        $downloaded = $this->download($this->release('7.5.30'), null, $filesystem);
        copy($this->release('7.5.29')['zip'], $this->working . 'ExpressionEngine.zip');

        try {
            $this->makeUnpacker($filesystem)->unzipPackage($downloaded);
            $this->fail('A package replaced on disk after it was verified was unpacked');
        } catch (UpdaterException $e) {
            $this->assertEquals(7, $e->getCode());
        }

        $this->assertDirectoryDoesNotExist($this->working . 'ExpressionEngine');
    }

    /**
     * Whether an update is a major upgrade is settled from the version that
     * was announced. If the update server answers with another major version
     * by the time the package is fetched, or with a release older than the
     * one announced, that package is a different update, however good its
     * signature. With or without an announced version, the site (which runs
     * 7.5.29 here) is not taken back to an older release.
     *
     * @dataProvider packagesAndTheReleaseThatWasAnnounced
     */
    public function testOnlyAPackageThatIsTheUpdateThatWasAnnouncedIsUnpacked($delivered, $announced, $refusal)
    {
        $this->download($this->release($delivered));

        try {
            $this->unpackInALaterRequest($announced);
            $this->assertNull($refusal, 'A package for ' . $delivered . ' was unpacked');
        } catch (UpdaterException $e) {
            $this->assertEquals($refusal, $e->getCode(), $e->getMessage());
        }

        $this->assertSame($refusal === null, is_dir($this->working . 'ExpressionEngine'));
    }

    public function packagesAndTheReleaseThatWasAnnounced()
    {
        return [
            'another major version than announced' => ['8.0.0', '7.5.30', 26],
            'older than announced' => ['7.5.28', '7.5.30', 26],
            'the release that was announced' => ['7.5.30', '7.5.30', null],
            'a later release of the same major version' => ['7.5.31', '7.5.30', null],
            'a preview build, which holds the version it previews' => ['7.5.30', '7.5.30-dp.1', null],
            'nothing announced, older than the site' => ['7.5.28', null, 27],
            'nothing announced, an older major version' => ['6.4.17', null, 27],
            'nothing announced, the version the site runs' => ['7.5.29', null, null],
            'nothing announced, newer than the site' => ['8.0.0', null, null],
        ];
    }

    public function testLaterRequestsGoByTheReleaseTheUpdateWasCheckedForWhenItStarted()
    {
        $this->assertNull(Preflight::keptTarget($this->working), 'Nothing has been checked yet');

        $this->makePreflight()->keepTarget('7.5.30');

        $this->assertSame('7.5.30', Preflight::keptTarget($this->working));

        $this->makePreflight()->keepTarget(null);

        $this->assertNull(Preflight::keptTarget($this->working), 'An update started without a known release does not go by an earlier one\'s');
    }

    /**
     * A write or a removal that fails does not say so. An update that went
     * on regardless would have its later steps work to an earlier update's
     * release.
     *
     * @dataProvider releasesThatCouldNotBePutOnRecord
     */
    public function testAnUpdateDoesNotStartUnlessTheReleaseItIsForIsOnRecord($target)
    {
        $this->makePreflight()->keepTarget('7.5.30');

        $filesystem = Mockery::mock('ExpressionEngine\Library\Filesystem\Filesystem');
        $filesystem->shouldReceive('write');
        $filesystem->shouldReceive('isFile')->andReturn(true);
        $filesystem->shouldReceive('delete');

        try {
            $this->makePreflight($filesystem)->keepTarget($target);
            $this->fail('An update started with an earlier update\'s release still on record');
        } catch (UpdaterException $e) {
            $this->assertEquals(28, $e->getCode());
        }

        $this->assertSame('7.5.30', Preflight::keptTarget($this->working));
    }

    public function releasesThatCouldNotBePutOnRecord()
    {
        return [
            'another release' => ['8.0.0'],
            'no known release' => [null],
        ];
    }

    /**
     * The request that downloads the package, with the update server
     * answering as it does: the zip as the body and its signature in a header
     *
     * @return array What the download step hands on: path, signature and hash
     */
    private function download(array $release, $signature = null, $filesystem = null)
    {
        $signature = ($signature === null) ? file_get_contents($release['signature']) : $signature;

        $response = Mockery::mock('ExpressionEngine\Library\Curl\PostRequest');
        $response->shouldReceive('exec')->andReturn(file_get_contents($release['zip']));
        $response->shouldReceive('getHeader')->with('http_code')->andReturn('200');
        $response->shouldReceive('getHeader')->with('Content-Type')->andReturn('application/zip');
        $response->shouldReceive('getHeader')->with('Package-Signature')->andReturn('"' . $signature . '"');

        $server = Mockery::mock('ExpressionEngine\Library\Curl\RequestFactory');
        $server->shouldReceive('post')->once()->andReturn($response);

        return $this->makeDownloader($server, $filesystem)->downloadPackage('https://update.example.com');
    }

    /**
     * The request that unpacks the package. It did not download the package,
     * and has only what that request left in the update folder to go on.
     *
     * @param string|null $announced Version the update was checked for, when known
     */
    private function unpackInALaterRequest($announced = null)
    {
        $this->makeUnpacker()->unzipPackage($this->makeDownloader()->downloadedPackage(), $announced);
    }
}
