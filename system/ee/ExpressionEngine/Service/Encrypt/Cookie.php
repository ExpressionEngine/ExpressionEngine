<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Encrypt;

/**
 * ExpressionEngine Encrypt Service Cookie Class
 */
class Cookie
{
    // Length of sha384 hash
    protected $hash_length = 96;

    // Algorithm for hash generation
    protected $hash_algo = 'sha384';

    /**
     * Given raw cookie data appended with a signature, returns the verified,
     * decoded data
     *
     * @param string $cookie Raw cookie data with signature
     * @return mixed Result of json_decoding the data, or NULL if signature or
     *   data invalid
     */
    public function getVerifiedCookieData($cookie)
    {
        if (strlen($cookie) <= $this->hash_length) {
            return null;
        }

        $signature = substr($cookie, -$this->hash_length);
        $payload = substr($cookie, 0, -$this->hash_length);

        if (hash_equals($this->generateHashForCookieData($payload), $signature)) {
            return json_decode(stripslashes($payload), true);
        }

        return null;
    }

    /**
     * Create encoded, signed cookie data
     *
     * @param mixed $data Data to be stored in a cookie
     * @return string json_encoded data with signature of data appended
     */
    public function signCookieData($data)
    {
        $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $payload . $this->generateHashForCookieData($payload);
    }

    /**
     * Creates signature of cookie data
     *
     * @return string Signature
     */
    protected function generateHashForCookieData($data)
    {
        return ee('Encrypt')->sign(
            $data,
            ee()->config->item('session_crypt_key'),
            $this->hash_algo
        );
    }
}

// EOF
