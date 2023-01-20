<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\License;

use InvalidArgumentException;

/**
 * License Factory
 */
class LicenseFactory
{
    /**
     * @var string $default_public_key A default public key to use for verifying
     *   signatures.
     */
    protected $default_public_key;

    /**
     * Constructor: sets a default public key
     *
     * @param string $pubkey A public key to use for verifying signatures
     */
    public function __construct($pubkey)
    {
        $this->default_public_key = $pubkey;
    }

    /**
     * Gets a license from the file system and return a License object.
     *
     * @param string $path_to_license The filesystem path to the license file
     * @param string $pubkey A public key to use for verifying signatures (optional)
     *
     * @return License An object representing the license
     */
    public function get($path_to_license, $pubkey = '')
    {
        $key = ($pubkey) ?: $this->default_public_key;

        return new License($path, $key);
    }
    /**
     * Gets the ExpressionEngine license from the file system and returns
     * an ExpressionEngineLicense object.
     *
     * @return ExpressionEngineLicense An object representing the license
     */
    public function getEELicense($path = '')
    {
        // @TODO: Inject the path.
        $path = ($path) ?: SYSPATH . 'user/config/license.key';

        return new ExpressionEngineLicense($path, $this->default_public_key);
    }
}
