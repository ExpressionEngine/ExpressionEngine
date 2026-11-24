<?php

namespace ExpressionEngine\Tests\Service\Updater\Downloader;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Service\Updater\Downloader\Downloader;
use ExpressionEngine\Service\Updater\Downloader\Preflight;
use ExpressionEngine\Service\Updater\Downloader\Unpacker;
use ExpressionEngine\Service\Updater\PackageSignature;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * For tests that follow an update package through the updater using real
 * files and real zip archives, signed with a key made for the test. Each
 * service made here has a filesystem of its own, as it would in a request of
 * its own, and works in an update folder kept apart from any real one.
 */
abstract class PackageTestBase extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected $supplied;
    protected $working;
    protected $key;
    protected $logger;

    protected function setUp(): void
    {
        if (! class_exists('ZipArchive')) {
            $this->markTestSkipped('This PHP build cannot make zip archives');
        }

        $this->key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

        if ($this->key === false) {
            $this->markTestSkipped('This PHP build cannot generate RSA keys');
        }

        // Where a release is built, which need not be anywhere near the site
        $this->supplied = sys_get_temp_dir() . '/ee-package-' . bin2hex(random_bytes(8)) . '/';
        mkdir($this->supplied, 0700);

        // The update's working folder
        $this->working = PATH_CACHE . 'updater-test-' . bin2hex(random_bytes(8)) . '/';
        mkdir($this->working, 0700, true);

        $this->logger = Mockery::mock('ExpressionEngine\Service\Updater\Logger');
        $this->logger->shouldReceive('log');
    }

    protected function tearDown(): void
    {
        foreach (array_merge([$this->supplied, $this->working], $this->packageCopies()) as $folder) {
            if ($folder && is_link($folder)) {
                // Windows removes a link to a folder the way it removes a folder,
                // even once the folder it pointed to has gone
                @rmdir($folder) || @unlink($folder);

                continue;
            }

            if (! $folder || ! is_dir($folder)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir($folder);
        }
    }

    /**
     * Copies of supplied packages, which are kept beside the working folder
     */
    protected function packageCopies()
    {
        return $this->working ? (glob(rtrim($this->working, '/') . '-package-*') ?: []) : [];
    }

    protected function publicKey()
    {
        return openssl_pkey_get_details($this->key)['key'];
    }

    /**
     * @param object|null $curl Stands in for the update server, for a download
     * @param Filesystem|null $filesystem One to share with another service, as
     *   services working in the same process may
     */
    protected function makeDownloader($curl = null, $filesystem = null)
    {
        $license = Mockery::mock('ExpressionEngine\Service\License\ExpressionEngineLicense');
        $license->shouldReceive('getRawLicense')->andReturn('');
        $config = Mockery::mock('ExpressionEngine\Service\Config\File');
        $config->shouldReceive('get')->andReturn('');

        return new class($license, $curl ?: Mockery::mock('ExpressionEngine\Library\Curl\RequestFactory'), $filesystem ?: new Filesystem(), $this->logger, $config, [$this->publicKey()], $this->working) extends Downloader {
            private $working;

            public function __construct($license, $curl, $filesystem, $logger, $config, $public_keys, $working)
            {
                parent::__construct($license, $curl, $filesystem, $logger, $config, $public_keys);
                $this->working = $working;
            }

            protected function path()
            {
                return $this->working;
            }
        };
    }

    protected function makeUnpacker($filesystem = null)
    {
        return new class($filesystem ?: new Filesystem(), new \ZipArchive(), Mockery::mock('ExpressionEngine\Service\Updater\Verifier'), $this->logger, Mockery::mock('ExpressionEngine\Service\Updater\RequirementsCheckerLoader'), new PackageSignature([$this->publicKey()]), $this->working) extends Unpacker {
            private $working;

            public function __construct($filesystem, $zip_archive, $verifier, $logger, $requirements, $signature, $working)
            {
                parent::__construct($filesystem, $zip_archive, $verifier, $logger, $requirements, $signature);
                $this->working = $working;
            }

            protected function path()
            {
                return $this->working;
            }

            // The site these packages arrive at, whatever version the tests run as
            protected function installedVersion()
            {
                return '7.5.29';
            }
        };
    }

    /**
     * @param object|null $filesystem One that stands in for the real one
     */
    protected function makePreflight($filesystem = null)
    {
        return new class($filesystem ?: new Filesystem(), $this->logger, Mockery::mock('ExpressionEngine\Service\Config\File'), [], $this->working) extends Preflight {
            private $working;

            public function __construct($filesystem, $logger, $config, $theme_paths, $working)
            {
                parent::__construct($filesystem, $logger, $config, $theme_paths);
                $this->working = $working;
            }

            protected function path()
            {
                return $this->working;
            }
        };
    }

    /**
     * Builds a small release and signs it the way release packages are signed
     *
     * @return array Paths to the zip archive and its signature file
     */
    protected function release($version)
    {
        $zip = $this->supplied . 'ExpressionEngine' . $version . '.zip';

        $archive = new \ZipArchive();
        $archive->open($zip, \ZipArchive::CREATE);
        $archive->addFromString('index.php', '<?php // ExpressionEngine ' . $version);
        $archive->addFromString('system/ee/legacy/libraries/Core.php', "<?php\n\n        define('APP_VER', '" . $version . "');\n");
        $archive->close();

        file_put_contents($zip . '.sig', $this->sign($zip, $this->key));

        return ['zip' => $zip, 'signature' => $zip . '.sig'];
    }

    protected function sign($zip, $private_key)
    {
        openssl_sign(hash_file('sha384', $zip), $signature, $private_key, OPENSSL_ALGO_SHA384);

        return base64_encode($signature);
    }
}
