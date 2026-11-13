<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Addon;

use ExpressionEngine\Core\Application;
use ExpressionEngine\Core\Provider;

/**
 * Add-on Service Factory
 */
class Factory
{
    /**
     * @var Application object
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get the add-on `$name`
     *
     * @param String $name Add-on short name
     * @return Object Add-on The requested addon
     */
    public function get($name)
    {
        if (isset(ee()->core) && false !== $addon = ee()->core->cache(__CLASS__, $name, false)) {
            return $addon;
        }
        
        if (! $this->app->has($name)) {
            return null;
        }

        $provider = $this->app->get($name);

        if ($this->isAddon($provider)) {
            $addon = new Addon($provider);
            if (isset(ee()->core)) {
                ee()->core->set_cache(__CLASS__, $name, $addon);
            }
            return $addon;
        }

        return null;
    }

    /**
     * Get all addons
     *
     * @return array An array of Add-on objects.
     */
    public function all()
    {
        if (isset(ee()->core) && false !== $all = ee()->core->cache(__CLASS__, '_all', false)) {
            return $all;
        }

        $providers = $this->app->getProviders();

        $all = array();

        foreach ($providers as $key => $obj) {
            if ($this->isAddon($obj)) {
                $all[$key] = new Addon($obj);
            }
        }

        if (isset(ee()->core)) {
            ee()->core->set_cache(__CLASS__, '_all', $all);
        }

        return $all;
    }

    /**
     * Fetch all installed addons
     *
     * @return array An array of Add-on objects.
     */
    public function installed()
    {
        return array_filter($this->all(), function ($addon) {
            return $addon->isInstalled();
        });
    }

    /**
     * Is a given provider an addon?
     *
     * @return bool Is an addon?
     */
    protected function isAddon(Provider $provider)
    {
        $path = $provider->getPath();

        return (strpos($path, PATH_ADDONS) === 0 || strpos($path, PATH_THIRD) === 0);
    }

    /**
     * Get licensed add-ons from cached license response
     *
     * @return array
     */
    public function getLicensedAddons()
    {
        $data = $this->getDataFromCachedLicenseResponse();

        if (is_null($data) || !isset($data['licensedAddons'])) {
            return [];
        }

        return $data['licensedAddons'];
    }

    /**
     * Get data from cached license response
     *
     * @return array|null
     */
    public function getDataFromCachedLicenseResponse()
    {
        // See if we have a cached check.
        $cached = ee()->cache->file->get('/addons-status');

        if (empty($cached)) {
            return null;
        }

        if (!ee()->cache->file->is_writable('/addons-status')) {
            $this->logLicenseError('license_error_file_not_writable');

            return null;
        }

        list($cache, $integrity) = explode('||s=', $cached);

        // Make sure the cache exists and has the proper integrity to use.
        if (empty($cache) || empty($integrity) || hash('sha256', $cache) !== $integrity) {
            $this->logLicenseError('license_error_file_broken');

            return null;
        }

        $json = ee('Encrypt')->decode($cache, ee()->config->item('session_crypt_key'));

        if (empty($json) || !$data = json_decode($json, true)) {
            $this->logLicenseError('license_error_file_broken');

            return null;
        }

        $sha = $data['sha'];
        unset($data['sha']);

        if ($sha !== hash('sha256', json_encode($data))) {
            $this->logLicenseError('license_error_file_broken');

            return null;
        }

        return $data;
    }

    /**
     * Log license error to developer log and display alert in CP
     *
     * @param [type] $message
     * @return void
     */
    private function logLicenseError($message)
    {
        ee()->load->library('logger');
        ee()->logger->developer(lang($message), true);
        if (REQ == 'CP') {
            ee('CP/Alert')->makeBanner('license-error')
                ->asWarning()
                ->canClose()
                ->withTitle(lang('license_error'))
                ->addToBody(lang($message))
                ->now();
        }
    }

}

// EOF
