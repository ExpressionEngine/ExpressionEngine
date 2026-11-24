<?php

namespace ExpressionEngine\Tests\Service\Updater\Downloader;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Service\Updater\UpdaterException;

require_once __DIR__ . '/PackageTestBase.php';

/**
 * Follows an update package that is already on the server from where it was
 * left, through being copied and verified, to being unpacked.
 */
class LocalPackageTest extends PackageTestBase
{
    private $downloader;
    private $unpacker;

    protected function setUp(): void
    {
        parent::setUp();

        // One filesystem between them, as when both work in the same process.
        // It remembers what it has read, which must not hide a changed file.
        $filesystem = new Filesystem();
        $this->downloader = $this->makeDownloader(null, $filesystem);
        $this->unpacker = $this->makeUnpacker($filesystem);
    }

    public function testASignedPackageIsCopiedAndTheCopyIsWhatGetsUnpacked()
    {
        $release = $this->release('7.5.30');
        $package = $this->downloader->stagePackage($release['zip'], $release['signature']);

        $this->assertStringStartsWith(rtrim($this->working, '/') . '-package-', $package['path'], 'The copy is kept beside the update folder, not in it');

        // Windows reports permissions for every user whatever was asked for
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->assertSame(0, fileperms($package['folder']) & 0077, 'The copy is open to users other than the one who supplied it');
        }

        $this->assertFileEquals($release['zip'], $package['path']);
        $this->assertSame(hash_file('sha384', $release['zip']), $package['hash']);
        $this->assertSame('7.5.30', $this->unpacker->packageVersion($package['path']));
        $this->assertDirectoryDoesNotExist($this->working . 'ExpressionEngine', 'Reading the version extracts nothing');

        $this->unpacker->unzipPackage($package);

        $this->assertStringContainsString('7.5.30', file_get_contents($this->working . 'ExpressionEngine/system/ee/legacy/libraries/Core.php'));
        $this->assertFileExists($release['zip'], 'The package that was supplied is left where it was');
    }

    public function testASignatureWithSurroundingWhitespaceIsAccepted()
    {
        $release = $this->release('7.5.30');
        file_put_contents($release['signature'], "\n  " . file_get_contents($release['signature']) . "\n");

        $this->assertNotEmpty($this->downloader->stagePackage($release['zip'], $release['signature']));
    }

    /** @dataProvider badSignatures */
    public function testAPackageThatCannotBeVerifiedIsRejectedAndNoCopyIsKept($signature)
    {
        $release = $this->release('7.5.30');
        $other = $this->release('7.5.31');

        $signatures = [
            'made for another package' => file_get_contents($other['signature']),
            'made with an unknown key' => $this->sign($release['zip'], openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA])),
            'empty' => '',
            'not a signature' => 'not a signature',
        ];
        file_put_contents($release['signature'], $signatures[$signature]);

        try {
            $this->downloader->stagePackage($release['zip'], $release['signature']);
            $this->fail('A package was accepted with a signature that was ' . $signature);
        } catch (UpdaterException $e) {
            $this->assertEquals(7, $e->getCode());
        }

        $this->assertSame([], $this->packageCopies(), 'The rejected copy was left behind');
        $this->assertFileExists($release['zip'], 'The package that was supplied is left where it was');
    }

    public function badSignatures()
    {
        return [
            ['made for another package'],
            ['made with an unknown key'],
            ['empty'],
            ['not a signature'],
        ];
    }

    public function testAMissingPackageOrSignatureIsReported()
    {
        $release = $this->release('7.5.30');

        foreach ([[$this->supplied . 'missing.zip', $release['signature']], [$release['zip'], $this->supplied . 'missing.sig']] as $paths) {
            try {
                $this->downloader->stagePackage($paths[0], $paths[1]);
                $this->fail('A missing file was not reported');
            } catch (UpdaterException $e) {
                $this->assertEquals(23, $e->getCode());
            }
        }

        $this->assertSame([], $this->packageCopies());
    }

    public function testEachRunKeepsItsOwnCopy()
    {
        $release = $this->release('7.5.30');

        $first = $this->downloader->stagePackage($release['zip'], $release['signature']);
        $second = $this->downloader->stagePackage($release['zip'], $release['signature']);

        $this->assertNotSame($first['folder'], $second['folder']);
        $this->assertFileExists($first['path']);
        $this->assertFileExists($second['path']);
    }

    public function testSupplyingAPackageRemovesCopiesLeftByRunsThatNeverFinished()
    {
        $release = $this->release('7.5.30');

        $abandoned = $this->downloader->stagePackage($release['zip'], $release['signature']);
        $recent = $this->downloader->stagePackage($release['zip'], $release['signature']);
        touch($abandoned['folder'], time() - 2 * 86400);

        $current = $this->downloader->stagePackage($release['zip'], $release['signature']);

        $this->assertDirectoryDoesNotExist($abandoned['folder']);
        $this->assertFileExists($recent['path'], 'A copy another run may still be using was removed');
        $this->assertFileExists($current['path']);
    }

    public function testALinkIsNotTakenForACopyLeftByAnEarlierRun()
    {
        // Something that only looks like an old copy: a link to a folder elsewhere
        $elsewhere = $this->supplied . 'elsewhere';
        mkdir($elsewhere);
        file_put_contents($elsewhere . '/keep.txt', 'Not a copy of a package');
        touch($elsewhere, time() - 2 * 86400);
        $planted = rtrim($this->working, '/') . '-package-planted';

        if (! @symlink($elsewhere, $planted)) {
            $this->markTestSkipped('This account cannot create links');
        }

        $release = $this->release('7.5.30');
        $this->downloader->stagePackage($release['zip'], $release['signature']);

        $this->assertTrue(is_link($planted), 'A link was treated as an old copy');
        $this->assertFileExists($elsewhere . '/keep.txt', 'Files outside the update were removed through a link');
    }

    /** @dataProvider readableVersions */
    public function testAPackagesVersionIsReadHoweverItIsWritten($core)
    {
        $zip = $this->supplied . 'written-differently.zip';
        $archive = new \ZipArchive();
        $archive->open($zip, \ZipArchive::CREATE);
        $archive->addFromString('system/ee/legacy/libraries/Core.php', $core);
        $archive->close();

        $this->assertSame('7.6.0', $this->unpacker->packageVersion($zip));
    }

    public function readableVersions()
    {
        return [
            'single quotes' => ["<?php define('APP_VER', '7.6.0');"],
            'double quotes' => ['<?php define("APP_VER", "7.6.0");'],
            'stated twice' => ["<?php // define('APP_VER', '7.6.0') is the version\ndefine('APP_VER', '7.6.0');"],
        ];
    }

    /**
     * Both releases here are properly signed. What is unpacked still has to be
     * the one that was verified for this update.
     *
     * @dataProvider swaps
     */
    public function testADifferentSignedReleaseIsNotUnpackedInPlaceOfTheOneVerified($swap)
    {
        $requested = $this->release('7.5.30');
        $another = $this->release('7.5.29');
        $package = $this->downloader->stagePackage($requested['zip'], $requested['signature']);
        $other = $this->downloader->stagePackage($another['zip'], $another['signature']);

        if ($swap === 'the file is replaced') {
            copy($other['path'], $package['path']);
        } elseif ($swap === 'the file and its signature are replaced') {
            copy($other['path'], $package['path']);
            $package['signature'] = $other['signature'];
        } elseif ($swap === 'the file is altered') {
            file_put_contents($package['path'], 'x', FILE_APPEND);
        } elseif ($swap === 'the file is removed') {
            unlink($package['path']);
        }

        try {
            $this->unpacker->unzipPackage($package);
            $this->fail('A package was unpacked although ' . $swap);
        } catch (UpdaterException $e) {
            $this->assertEquals(7, $e->getCode());
        }

        $this->assertDirectoryDoesNotExist($this->working . 'ExpressionEngine', 'Files were extracted from a package that was not verified');
    }

    public function swaps()
    {
        return [
            ['the file is replaced'],
            ['the file and its signature are replaced'],
            ['the file is altered'],
            ['the file is removed'],
        ];
    }

    /** @dataProvider incompleteDetails */
    public function testNothingIsUnpackedWithoutTheSignatureAndHashItWasVerifiedWith(array $missing)
    {
        $release = $this->release('7.5.30');
        $package = array_diff_key($this->downloader->stagePackage($release['zip'], $release['signature']), array_flip($missing));

        try {
            $this->unpacker->unzipPackage($package);
            $this->fail('A package was unpacked without its ' . implode(' and ', $missing));
        } catch (UpdaterException $e) {
            $this->assertEquals(7, $e->getCode());
        }

        $this->assertDirectoryDoesNotExist($this->working . 'ExpressionEngine');
    }

    public function incompleteDetails()
    {
        return [
            'no signature' => [['signature']],
            'no hash' => [['hash']],
            'neither' => [['signature', 'hash']],
            'no path' => [['path']],
        ];
    }

    /** @dataProvider unreadableVersions */
    public function testAPackageThatDoesNotSayWhichVersionItIsIsRejected($core)
    {
        $zip = $this->supplied . 'unclear.zip';
        $archive = new \ZipArchive();
        $archive->open($zip, \ZipArchive::CREATE);
        $archive->addFromString('index.php', '<?php');

        if ($core !== null) {
            $archive->addFromString('system/ee/legacy/libraries/Core.php', $core);
        }

        $archive->close();

        try {
            $this->unpacker->packageVersion($zip);
            $this->fail('A version was read from a package that does not clearly state one');
        } catch (UpdaterException $e) {
            $this->assertEquals(24, $e->getCode());
        }
    }

    public function unreadableVersions()
    {
        return [
            'no core file' => [null],
            'no version in it' => ['<?php // nothing here'],
            'two different versions in it' => ["<?php define('APP_VER', '7.5.30');\ndefine('APP_VER', '6.0.0');"],
        ];
    }

    public function testAFileThatIsNotAZipArchiveHasNoVersion()
    {
        file_put_contents($this->supplied . 'notes.zip', 'This is not a zip archive');

        $this->expectException(UpdaterException::class);
        $this->expectExceptionCode(24);

        $this->unpacker->packageVersion($this->supplied . 'notes.zip');
    }
}
