<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Generator;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Library\String\Str;

class ServiceGenerator extends AbstractGenerator
{
    protected $serviceName;
    protected $namespace;
    protected $isSingleton;
    protected $servicePath;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS and String library
        $this->filesystem = $filesystem;
        $this->str = $str;

        // Set required data for generator to use
        $this->serviceName = $this->str->studly($data['name']);
        $this->addon = $data['addon'];
        $this->isSingleton = $data['is_singleton'];

        // Set up addon path, generator path, and stub path
        $this->init();

        $addonSetupArray = require $this->addonPath . 'addon.setup.php';
        $this->namespace = $addonSetupArray['namespace'] . '\\Service';
    }

    private function init()
    {
        $this->initCommon();
        $this->servicePath = $this->addonPath . 'Service/';

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/';

        if (! $this->filesystem->isDir($this->servicePath)) {
            $this->filesystem->mkDir($this->servicePath);
        }
    }

    public function build()
    {
        $serviceStub = $this->filesystem->read($this->stub('service.php'));
        $serviceStub = $this->write('namespace', $this->namespace, $serviceStub);
        $serviceStub = $this->write('service_name', $this->serviceName, $serviceStub);
        $serviceStub = $this->write('addon', $this->addon, $serviceStub);

        $this->putFile($this->serviceName . '.php', $serviceStub, 'Service');

        $this->addServiceToAddonSetup();

        return true;
    }

    private function addServiceToAddonSetup()
    {
        try {
            $addonSetupFile = $this->filesystem->read($this->addonPath . 'addon.setup.php');
        } catch (\Exception $e) {
            return false;
        }

        $addonSetupArray = require $this->addonPath . 'addon.setup.php';

        $serviceKey = $this->isSingleton ? 'services.singletons' : 'services';

        // Parse Service Stubs
        $serviceString = "        '{$this->serviceName}' => 'Service\\{$this->serviceName}',";
        $serviceStub = $this->filesystem->read($this->stub('services.addon.php'));
        $serviceStub = $this->write('service_key', $serviceKey, $serviceStub);
        $serviceStub = $this->write('service_data', $serviceString, $serviceStub);

        // The add-on setup has the services array
        if (array_key_exists($serviceKey, $addonSetupArray)) {
            $escapedKey = preg_quote($serviceKey, '/');
            $pattern = "/($escapedKey)([^=]+)(=>\s)(array\(|\[)([^\S]*)([\s])([\s\S]*)$/";
            $addonSetupFile = preg_replace($pattern, "$1$2$3$4\n$serviceString$5$6$7", $addonSetupFile);
            $this->filesystem->write($this->addonPath . 'addon.setup.php', $addonSetupFile, true);
        } else { // The add-on setup does not have the services array
            $pattern = '/(,)([^,]+)$/';
            $addonSetupFile = preg_replace($pattern, ",\n    $serviceStub $2", $addonSetupFile);
            $this->filesystem->write($this->addonPath . 'addon.setup.php', $addonSetupFile, true);
        }
    }
}
