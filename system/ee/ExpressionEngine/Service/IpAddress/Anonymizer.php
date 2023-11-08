<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\IpAddress;

/**
 * IP Address Anonymizer
 *
 * Anonymizes user-specific information but leaves general geographic information
 */
class Anonymizer
{
    /**
     * @var string IPv4 netmask used to anonymize IPv4 address.
     */
    private $ipv4NetMask = '255.255.255.0';

    /**
     * @var string IPv6 netmask used to anonymize IPv6 address.
     */
    private $ipv6NetMask = 'ffff:ffff:ffff:ffff:0000:0000:0000:0000';

    /**
     * Anonymize an IPv4 or IPv6 address.
     *
     * @param $address string IP address that must be anonymized
     * @return string The anonymized IP address. Returns an empty string when the IP address is invalid.
     */
    public function anonymize($address)
    {
        $packedAddress = inet_pton($address);

        if (strlen($packedAddress) == 4) {
            return $this->anonymizeIPv4($address);
        } elseif (strlen($packedAddress) == 16) {
            return $this->anonymizeIPv6($address);
        }

        return '';
    }

    /**
     * Anonymize an IPv4 address
     *
     * @param $address string IPv4 address
     * @return string Anonymized address
     */
    private function anonymizeIPv4($address)
    {
        return inet_ntop(inet_pton($address) & inet_pton($this->ipv4NetMask));
    }

    /**
     * Anonymize an IPv6 address
     *
     * @param $address string IPv6 address
     * @return string Anonymized address
     */
    private function anonymizeIPv6($address)
    {
        return inet_ntop(inet_pton($address) & inet_pton($this->ipv6NetMask));
    }
}

/*

https://github.com/geertw/php-ip-anonymizer

MIT License

Copyright (c) 2016 Geert Wirken

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/
