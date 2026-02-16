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
        $this->createMainServiceFile();

        $this->updateAddonSetupFile();

        return true;
    }

    /**
     * Create the main service file
     */
    private function createMainServiceFile()
    {
        $addonSetupArray = require $this->addonPath . 'addon.setup.php';

        $serviceStub = $this->filesystem->read($this->stub('service.php'));
        $serviceStub = $this->write('namespace', $this->namespace, $serviceStub);
        $serviceStub = $this->write('service_name', $this->serviceName, $serviceStub);
        $serviceStub = $this->write('addon_name', $this->addon, $serviceStub);
        $serviceStub = $this->write('author', $addonSetupArray['author'] ?? 'Unknown', $serviceStub);
        $serviceStub = $this->write('description', $this->serviceName . ' service', $serviceStub);

        $this->putFile($this->serviceName . '.php', $serviceStub, 'Service');
    }

    /**
     * Update addon.setup.php file to register the service
     */
    private function updateAddonSetupFile()
    {
        try {
            $addonSetupFile = $this->filesystem->read($this->addonPath . 'addon.setup.php');
        } catch (\Exception $e) {
            return false;
        }

        $addonSetupArray = require $this->addonPath . 'addon.setup.php';

        $serviceKey = $this->isSingleton ? 'services.singletons' : 'services';

        // Create service registration with proper namespace escaping
        $namespaceParts = explode('\\', $this->namespace);
        $escapedNamespace = implode('\\\\', $namespaceParts);

        $serviceRegistration = "        '" . $this->serviceName . "' => function(\$addon)\n";
        $serviceRegistration .= "        {\n";
        $serviceRegistration .= "            return new \\" . $escapedNamespace . "\\" . $this->serviceName . "(\$addon);\n";
        $serviceRegistration .= "        },\n";

        // The addon setup has the services array
        if (array_key_exists($serviceKey, $addonSetupArray)) {
            $pattern = "/($serviceKey)([^=]+)(=>\s)(array\(|\[)([^\S]*)([\s])([\s\S]*)$/";
            $addonSetupFile = preg_replace($pattern, "$1$2$3$4\n$serviceRegistration$5$6$7", $addonSetupFile);
            $this->filesystem->write($this->addonPath . 'addon.setup.php', $addonSetupFile, true);
        } else {
            $servicesStub = $this->filesystem->read($this->stub('services.addon.php'));
            $servicesStub = $this->write('service_data', $serviceRegistration, $servicesStub);
            $pattern = '/(,)([^,]+)$/';
            $addonSetupFile = preg_replace($pattern, ",\n    $servicesStub $2", $addonSetupFile);
            $this->filesystem->write($this->addonPath . 'addon.setup.php', $addonSetupFile, true);
        }
    }
}
