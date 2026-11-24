<?php

namespace ExpressionEngine\Tests\Service\Updater\Downloader;

use ExpressionEngine\Service\Updater\Downloader\Downloader;
use ExpressionEngine\Service\Updater\UpdaterException;
use Mockery;
use PHPUnit\Framework\TestCase;

class DownloaderTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public $license;
    public $curl;
    public $filesystem;
    public $logger;
    public $config;
    public $downloader;

    public function setUp(): void
    {
        $this->license = Mockery::mock('ExpressionEngine\Service\License\ExpressionEngineLicense');
        $this->curl = Mockery::mock('ExpressionEngine\Library\Curl\RequestFactory');
        $this->filesystem = Mockery::mock('ExpressionEngine\Library\Filesystem\Filesystem');
        $this->logger = Mockery::mock('ExpressionEngine\Service\Updater\Logger');
        $this->config = Mockery::mock('ExpressionEngine\Service\Config\File');

        $this->logger->shouldReceive('log'); // Logger's gonna log
        $this->filesystem->shouldReceive('mkDir'); // For update path creation

        $this->downloader = new Downloader($this->license, $this->curl, $this->filesystem, $this->logger, $this->config);
    }

    public function tearDown(): void
    {
        $this->license = null;
        $this->curl = null;
        $this->filesystem = null;
        $this->logger = null;
        $this->config = null;
        $this->downloader = null;

        Mockery::close();
    }

    public function testDownloadPackage()
    {
        $request = Mockery::mock('ExpressionEngine\Library\Curl\PostRequest');

        $this->license->shouldReceive('getRawLicense')->andReturn('1234');
        $this->config->shouldReceive('get')->with('app_version')->andReturn('4.0.0');
        $this->config->shouldReceive('get')->with('site_url')->andReturn('my_site_url');

        $this->curl->shouldReceive('post')->with(
            'ee_package_url',
            [
                'action' => 'download_update',
                'license' => '1234',
                'version' => '4.0.0',
                'domain' => 'my_site_url'
            ]
        )
            ->once()
            ->andReturn($request);

        $request->shouldReceive('exec')
            ->once()
            ->andReturn('some data');

        $request->shouldReceive('getHeader')
            ->with('http_code')
            ->once()
            ->andReturn('200');

        $request->shouldReceive('getHeader')
            ->with('Content-Type')
            ->once()
            ->andReturn('application/zip');

        $request->shouldReceive('getHeader')
            ->with('Package-Signature')
            ->andReturn('APMXXgjhZBuapY4NOdWxe7LDylqYueqm9ZPyjlqDTa6mCzwrL1DVSRsAQiqHBndwfXjPrFvQu1IkkKOTQU1GGHEfVcAUQMPttt5UwZsDoyaw/8YP8Xm5bxyvv0WACYDSihKFHsp8ndsqhHp21W2K5dJQVgo1jif+CFObT2ja5c0IK6SjN/dhEXaHZ8m85jqNfePYgqTT+taNbU7IWuFzx49AAe8KI5hDGkKS0a5DhVFdru1duMyLuQAEthw5RHoznDS4u/X48ILqDtaaApXNonD26bzZhxLwNwI9WuUg/1aOHqoiYdPQgWH2GvyxOKy8MmxwDuoZD4XuwaqfTRoYfw==');

        $this->filesystem->shouldReceive('mkDir');

        $this->filesystem->shouldReceive('write')
            ->with(PATH_CACHE . 'ee_update/ExpressionEngine.zip', 'some data', true)
            ->once();

        $this->filesystem->shouldReceive('hashFile')
            ->with('sha384', PATH_CACHE . 'ee_update/ExpressionEngine.zip')
            ->once()
            ->andReturn('fb7cc0d8a9c41a8ddf74bf7bc6f4e487a79c15c07f15116ad1ce3b0da7159577fff365db8ae7fe8cc463f15da7430d02');

        // What the unpack step needs to check the package again is kept with it
        $kept = null;
        $this->filesystem->shouldReceive('write')
            ->with(PATH_CACHE . 'ee_update/ExpressionEngine.zip.sig', Mockery::capture($kept), true)
            ->once();

        $package = $this->downloader->downloadPackage('ee_package_url');

        $this->assertSame(PATH_CACHE . 'ee_update/ExpressionEngine.zip', $package['path']);
        $this->assertSame('fb7cc0d8a9c41a8ddf74bf7bc6f4e487a79c15c07f15116ad1ce3b0da7159577fff365db8ae7fe8cc463f15da7430d02', $package['hash']);

        // A later request, which did not download the package, gets the same details
        $this->filesystem->shouldReceive('isFile')->with(PATH_CACHE . 'ee_update/ExpressionEngine.zip.sig')->andReturn(true);
        $this->filesystem->shouldReceive('read')->with(PATH_CACHE . 'ee_update/ExpressionEngine.zip.sig')->andReturn($kept);

        $this->assertSame($package, $this->downloader->downloadedPackage());
    }

    public function testAPackageWhoseSignatureWasNotKeptIsNotHandedOn()
    {
        $this->filesystem->shouldReceive('isFile')->andReturn(false, true, true);
        $this->filesystem->shouldReceive('read')->andReturn('not what was saved', json_encode(['signature' => 'abc']));

        for ($i = 0; $i < 3; $i++) {
            try {
                $this->downloader->downloadedPackage();
                $this->fail('A package was handed on without the signature and hash it was verified with');
            } catch (UpdaterException $e) {
                $this->assertEquals(6, $e->getCode());
            }
        }
    }

    public function testDownloadPackageExceptions()
    {
        $request = Mockery::mock('ExpressionEngine\Library\Curl\PostRequest');

        $this->license->shouldReceive('getRawLicense')->andReturn('1234');
        $this->config->shouldReceive('get')->with('app_version')->andReturn('4.0.0');
        $this->config->shouldReceive('get')->with('site_url')->andReturn('my_site_url');

        $this->curl->shouldReceive('post')->with(
            'ee_package_url',
            [
                'action' => 'download_update',
                'license' => '1234',
                'version' => '4.0.0',
                'domain' => 'my_site_url'
            ]
        )
            ->andReturn($request);

        $request->shouldReceive('exec')
            ->times(4)
            ->andReturn('some data');

        $request->shouldReceive('getHeader')
            ->with('http_code')
            ->twice()
            ->andReturn('403');

        try {
            $this->downloader->downloadPackage('ee_package_url');
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(4, $e->getCode());
        }

        $request->shouldReceive('getHeader')
            ->with('http_code')
            ->times(3)
            ->andReturn('200');

        $request->shouldReceive('getHeader')
            ->with('Content-Type')
            ->twice()
            ->andReturn('application/pdf');

        try {
            $this->downloader->downloadPackage('ee_package_url');
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(5, $e->getCode());
        }

        $request->shouldReceive('getHeader')
            ->with('Content-Type')
            ->andReturn('"application/zip"');

        $request->shouldReceive('getHeader')
            ->with('Package-Signature')
            ->once()
            ->andReturn(false);

        try {
            $this->downloader->downloadPackage('ee_package_url');
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(6, $e->getCode());
        }

        $request->shouldReceive('getHeader')
            ->with('Package-Signature')
            ->andReturn('APMXXgjhZBuapY4NOdWxe7LDylqYueqm9ZPyjlqDTa6mCzwrL1DVSRsAQiqHBndwfXjPrFvQu1IkkKOTQU1GGHEfVcAUQMPttt5UwZsDoyaw/8YP8Xm5bxyvv0WACYDSihKFHsp8ndsqhHp21W2K5dJQVgo1jif+CFObT2ja5c0IK6SjN/dhEXaHZ8m85jqNfePYgqTT+taNbU7IWuFzx49AAe8KI5hDGkKS0a5DhVFdru1duMyLuQAEthw5RHoznDS4u/X48ILqDtaaApXNonD26bzZhxLwNwI9WuUg/1aOHqoiYdPQgWH2GvyxOKy8MmxwDuoZD4XuwaqfTRoYfw==');

        $this->config->shouldReceive('set')->with('is_site_on', 'n');

        $this->filesystem->shouldReceive('mkDir');

        $this->filesystem->shouldReceive('write')
            ->with(PATH_CACHE . 'ee_update/ExpressionEngine.zip', 'some data', true);

        $this->filesystem->shouldReceive('hashFile')
            ->with('sha384', PATH_CACHE . 'ee_update/ExpressionEngine.zip')
            ->once()
            ->andReturn('bad hash');

        // A download that fails verification is not left behind
        $this->filesystem->shouldReceive('delete')
            ->with(PATH_CACHE . 'ee_update/ExpressionEngine.zip')
            ->once();

        try {
            $this->downloader->downloadPackage('ee_package_url');
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(7, $e->getCode());
        }

        $request->shouldReceive('exec')
            ->andReturn('{"error": "Cannot upgrade"}');

        $request->shouldReceive('getHeader')
            ->with('http_code')
            ->andReturn('500');

        try {
            $this->downloader->downloadPackage('ee_package_url');
            $this->fail();
        } catch (UpdaterException $e) {
            $this->assertEquals(20, $e->getCode());
            $this->assertEquals('Cannot upgrade', $e->getMessage());
        }
    }

    public function testPackageSignedByAReserveKeyIsAccepted()
    {
        $keys = $this->generateKeys(3);
        $downloader = $this->downloaderTrusting([$keys[0]['public'], $keys[1]['public']]);

        $hash = hash('sha384', 'some data');
        $this->expectDownloadOf($hash, $this->sign($hash, $keys[1]['private']));

        $downloader->downloadPackage('ee_package_url');
    }

    public function testPackageSignedByAnUnknownKeyIsRejected()
    {
        $keys = $this->generateKeys(3);
        $downloader = $this->downloaderTrusting([$keys[0]['public'], $keys[1]['public']]);

        $hash = hash('sha384', 'some data');
        $this->expectDownloadOf($hash, $this->sign($hash, $keys[2]['private']));

        try {
            $downloader->downloadPackage('ee_package_url');
            $this->fail('A package signed by a key that is not bundled was accepted');
        } catch (UpdaterException $e) {
            $this->assertEquals(7, $e->getCode());
        }
    }

    public function testSignatureForADifferentPackageIsRejected()
    {
        $keys = $this->generateKeys(1);
        $downloader = $this->downloaderTrusting([$keys[0]['public']]);

        $this->expectDownloadOf(hash('sha384', 'some data'), $this->sign(hash('sha384', 'other data'), $keys[0]['private']));

        try {
            $downloader->downloadPackage('ee_package_url');
            $this->fail('A signature made for another package was accepted');
        } catch (UpdaterException $e) {
            $this->assertEquals(7, $e->getCode());
        }
    }

    private function downloaderTrusting(array $public_keys)
    {
        return new Downloader($this->license, $this->curl, $this->filesystem, $this->logger, $this->config, $public_keys);
    }

    private function generateKeys($count)
    {
        $keys = [];

        for ($i = 0; $i < $count; $i++) {
            $private = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

            if ($private === false) {
                $this->markTestSkipped('This PHP build cannot generate RSA keys');
            }

            $keys[] = ['private' => $private, 'public' => openssl_pkey_get_details($private)['key']];
        }

        return $keys;
    }

    private function sign($hash, $private_key)
    {
        openssl_sign($hash, $signature, $private_key, OPENSSL_ALGO_SHA384);

        return base64_encode($signature);
    }

    private function expectDownloadOf($hash, $signature)
    {
        $request = Mockery::mock('ExpressionEngine\Library\Curl\PostRequest');

        $this->license->shouldReceive('getRawLicense')->andReturn('1234');
        $this->config->shouldReceive('get')->with('app_version')->andReturn('4.0.0');
        $this->config->shouldReceive('get')->with('site_url')->andReturn('my_site_url');

        $this->curl->shouldReceive('post')->andReturn($request);

        $request->shouldReceive('exec')->andReturn('some data');
        $request->shouldReceive('getHeader')->with('http_code')->andReturn('200');
        $request->shouldReceive('getHeader')->with('Content-Type')->andReturn('application/zip');
        $request->shouldReceive('getHeader')->with('Package-Signature')->andReturn($signature);

        $this->filesystem->shouldReceive('write');
        $this->filesystem->shouldReceive('delete');
        $this->filesystem->shouldReceive('hashFile')->once()->andReturn($hash);
    }
}
