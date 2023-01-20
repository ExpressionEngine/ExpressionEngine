<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Cookie;

/**
 * Cookie Service
 */
class Cookie
{
    /**
     * Gets cryptographically-signed cookie data by name
     *
     * @param string $cookie_name Cookie name
     * @param bool $xss_clean Clean the data for XSS or not
     * @return mixed Cookie data, or FALSE if cookie not found or verified
     */
    public function getSignedCookie($cookie_name, $xss_clean = false)
    {
        if ($cookie_data = ee()->input->cookie($cookie_name, $xss_clean)) {
            if ($verified_cookie_data = ee('Encrypt/Cookie')->getVerifiedCookieData($cookie_data)) {
                return $verified_cookie_data;
            }
        }

        return false;
    }

    /**
     * Set cryptographically-signed cookies
     *
     * @param string $cookie_name Cookie name
     * @param string $cookie_data Cookie data
     * @param int $expire Cookie expiration in seconds
     * @return void
     */
    public function setSignedCookie($cookie_name, $cookie_data, $expire = 0)
    {
        $signed_cookie_data = ee('Encrypt/Cookie')->signCookieData($cookie_data);

        ee()->input->set_cookie($cookie_name, $signed_cookie_data, $expire);
    }
}

// EOF
