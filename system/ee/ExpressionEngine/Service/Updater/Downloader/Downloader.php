<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Updater\Downloader;

use ExpressionEngine\Service\Updater\Downloader\UpdaterPaths;
use ExpressionEngine\Service\Updater\UpdaterException;
use ExpressionEngine\Service\Updater\PackageSignature;
use ExpressionEngine\Service\License\ExpressionEngineLicense;
use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Library\Curl\RequestFactory;
use ExpressionEngine\Service\Updater\Logger;
use ExpressionEngine\Service\Config\File;

/**
 * Updater file downloader
 *
 * Downloads the upgrade package for ExpressionEngine upgrades
 */
class Downloader
{
    use UpdaterPaths;

    protected $license;
    protected $curl;
    protected $filesystem;
    protected $logger;
    protected $config;
    protected $signature;

    /**
     * Constructor
     *
     * @param	ExpressionEngineLicense $license ExpressionEngineLicense object
     * @param	Curl\RequestFactory $curl cURL service object
     * @param	Filesystem $filesystem Filesystem service object
     * @param	Logger $logger Updater logger object
     * @param	File $config Config file object
     * @param	array $public_keys Public keys a package may be signed with, defaults to the bundled keys
     */
    public function __construct(ExpressionEngineLicense $license, RequestFactory $curl, Filesystem $filesystem, Logger $logger, File $config, array $public_keys = [])
    {
        $this->license = $license;
        $this->curl = $curl;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->config = $config;
        $this->signature = new PackageSignature($public_keys);
    }

    /**
     * Performs the actual download of the update package and verifies its integrity
     */
    public function downloadPackage($payload_url)
    {
        $this->logger->log('Downloading update package');

        $curl = $this->curl->post(
            $payload_url,
            [
                'action' => 'download_update',
                'license' => $this->license->getRawLicense(),
                'version' => $this->config->get('app_version'),
                'domain' => $this->config->get('site_url')
            ]
        );

        $data = $curl->exec();

        $this->validateResponse($curl, $data);

        // Write the file
        $this->filesystem->write($this->getArchiveFilePath(), $data, true);

        // Grab the zip's SHA384 hash, which is what the signature is made over
        $package = [
            'path' => $this->getArchiveFilePath(),
            'signature' => trim($curl->getHeader('Package-Signature'), '"'),
            'hash' => $this->filesystem->hashFile('sha384', $this->getArchiveFilePath())
        ];

        if (! $this->signature->verify($package['hash'], $package['signature'])) {
            $this->filesystem->delete($this->getArchiveFilePath());

            throw new UpdaterException(
                sprintf(
                    lang('could_not_verify_download') . "\n\n" . lang('try_again_later'),
                    $package['hash']
                ),
                7
            );
        }

        // Unpacking can happen in a later request, which checks the package
        // again before opening it
        $this->filesystem->write(
            $this->getArchiveSignaturePath(),
            json_encode(['signature' => $package['signature'], 'hash' => $package['hash']]),
            true
        );

        return $package;
    }

    /**
     * The downloaded package along with the signature and hash it had when it
     * was verified, for a request that did not download it itself
     *
     * @return	array	Package path, signature and hash
     */
    public function downloadedPackage()
    {
        $saved = $this->filesystem->isFile($this->getArchiveSignaturePath())
            ? json_decode($this->filesystem->read($this->getArchiveSignaturePath()), true)
            : null;

        if (! is_array($saved) || empty($saved['signature']) || empty($saved['hash'])) {
            throw new UpdaterException(lang('missing_package_signature') . "\n\n" . lang('try_again_later'), 6);
        }

        return [
            'path' => $this->getArchiveFilePath(),
            'signature' => (string) $saved['signature'],
            'hash' => (string) $saved['hash']
        ];
    }

    /**
     * Copies an update package that is already on this server into a folder of
     * its own and verifies the copy, so that what was verified is what gets used.
     * The folder sits beside the update's working folder, not in it: that one
     * is open to whoever runs the next update, and this copy is for the user
     * who supplied it alone.
     *
     * @param	string	$package_path	Path to the package's zip file
     * @param	string	$signature_path	Path to a file holding the package's signature
     * @return	array	Path of the copy, with its signature and hash
     */
    public function stagePackage($package_path, $signature_path)
    {
        // The package can be anywhere on this server, including places the
        // filesystem service does not reach, so it is read with PHP's own functions
        foreach ([$package_path, $signature_path] as $path) {
            if (! is_file($path) || ! is_readable($path)) {
                throw new UpdaterException(sprintf(lang('package_file_not_found'), $path), 23);
            }
        }

        $this->logger->log('Copying update package from ' . $package_path);

        $signature = trim((string) file_get_contents($signature_path));
        $beside = rtrim($this->path(), '/') . '-package-';

        // Copies left by runs that never finished. A link is not one of them,
        // and one this user cannot remove is left for the user who made it.
        foreach (glob($beside . '*', GLOB_ONLYDIR) ?: [] as $stale) {
            if (! is_link($stale) && filemtime($stale) < time() - 86400) {
                try {
                    $this->filesystem->deleteDir($stale);
                } catch (\Exception $e) {
                    $this->logger->log('Could not remove an old package copy: ' . $stale);
                }
            }
        }

        $folder = $beside . bin2hex(random_bytes(8)) . '/';

        $this->filesystem->mkDir($folder, false);

        // Not every filesystem can do this. The copy is checked again before
        // it is unpacked either way.
        if (! @chmod($folder, 0700)) {
            $this->logger->log('Could not make the package copy private: ' . $folder);
        }

        if (! @copy($package_path, $folder . $this->filename)) {
            $this->filesystem->deleteDir($folder);

            throw new UpdaterException(sprintf(lang('package_file_not_copied'), $package_path), 23);
        }

        // Hashed as it is on disk, without holding the whole package in memory
        $package = [
            'path' => $folder . $this->filename,
            'signature' => $signature,
            'hash' => (string) hash_file('sha384', $folder . $this->filename),
            'folder' => $folder
        ];

        if (! $this->signature->verify($package['hash'], $package['signature'])) {
            $this->filesystem->deleteDir($folder);

            throw new UpdaterException(sprintf(lang('could_not_verify_package'), $package['hash']), 7);
        }

        return $package;
    }

    /**
     * Validates the response from requesting the update package
     *
     * @param $curl PostRequest Request object
     * @param $data string Raw response body data
     * @return boolean TRUE if verified
     */
    private function validateResponse($curl, $data)
    {
        // Make sure everything looks normal
        if ($curl->getHeader('http_code') != '200') {
            // Custom message from server delivered
            if (($message = json_decode($data, true)) && isset($message['error'])) {
                throw new UpdaterException($message['error'], 20);
            }

            throw new UpdaterException(
                sprintf(
                    lang('could_not_download') . "\n\n" . lang('try_again_later'),
                    $curl->getHeader('http_code')
                ),
                4
            );
        }

        if (trim($curl->getHeader('Content-Type'), '"') != 'application/zip') {
            throw new UpdaterException(
                sprintf(
                    lang('unexpected_mime') . "\n\n" . lang('try_again_later'),
                    $curl->getHeader('Content-Type')
                ),
                5
            );
        }

        if (! $curl->getHeader('Package-Signature')) {
            throw new UpdaterException(lang('missing_signature_header') . "\n\n" . lang('try_again_later'), 6);
        }
    }
}

// EOF
