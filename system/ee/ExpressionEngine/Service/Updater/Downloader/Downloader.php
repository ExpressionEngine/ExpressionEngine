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
    protected $public_keys;

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
        $this->public_keys = $public_keys ?: $this->bundledPublicKeys();
    }

    /**
     * The public keys shipped with ExpressionEngine: the one in use, followed
     * by those held in reserve
     *
     * @return array
     */
    private function bundledPublicKeys()
    {
        $keys = [];

        foreach (['ExpressionEngineUpdate.pub', 'ExpressionEngineUpdate1.pub', 'ExpressionEngineUpdate2.pub'] as $file) {
            if (is_file(SYSPATH . 'ee/ExpressionEngine/Keys/' . $file)) {
                $keys[] = 'file://' . SYSPATH . 'ee/ExpressionEngine/Keys/' . $file;
            }
        }

        return $keys;
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

        // Grab the zip's SHA384 hash to verify integrity
        $hash = $this->filesystem->hashFile('sha384', $this->getArchiveFilePath());
        $signature = trim($curl->getHeader('Package-Signature'), '"');

        if (! $this->verifySignature($hash, $signature)) {
            throw new UpdaterException(
                sprintf(
                    lang('could_not_verify_download') . "\n\n" . lang('try_again_later'),
                    $hash
                ),
                7
            );
        }
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

    /**
     * Verifies the signature of the downloaded build against each of the
     * known public keys
     *
     * @param $hash string SHA384 hash of downloaded zip file
     * @param $signature string Base-64 encoded signature
     * @return boolean TRUE if verified
     */
    private function verifySignature($hash, $signature)
    {
        $signature = base64_decode($signature);

        foreach ($this->public_keys as $public_key) {
            $key = openssl_get_publickey($public_key);

            if ($key !== false && openssl_verify($hash, $signature, $key, OPENSSL_ALGO_SHA384) === 1) {
                return true;
            }
        }

        return false;
    }
}

// EOF
