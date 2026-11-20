<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Updater;

/**
 * Checks that an update package was signed by ExpressionEngine, however the
 * package reached this server
 */
class PackageSignature
{
    protected $public_keys;

    /**
     * Constructor
     *
     * @param	array $public_keys Public keys a package may be signed with, defaults to the bundled keys
     */
    public function __construct(array $public_keys = [])
    {
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
     * Verifies the signature of a package against each of the known public keys
     *
     * @param	string $hash SHA384 hash of the package's zip file
     * @param	string $signature Base-64 encoded signature
     * @return	boolean TRUE if verified
     */
    public function verify($hash, $signature)
    {
        if (! is_string($hash) || $hash === '' || ! is_string($signature)) {
            return false;
        }

        $signature = base64_decode(trim($signature, " \t\r\n\""));

        if ($signature === false || $signature === '') {
            return false;
        }

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
