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
    protected $description;
    protected $author;
    protected $addonType;
    protected $servicePath;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        // Set FS and String library
        $this->filesystem = $filesystem;
        $this->str = $str;

        $this->addon = $data['addon_name'];
        $this->isSingleton = $data['is_singleton'];
        $this->serviceName = $data['service_name'];

        // Set up addon path, generator path, and stub path
        $this->init();
    }

    private function init()
    {
        $this->initCommon($this->addon);
        $this->servicePath = $this->addonPath . '/Service/';

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/';

        // Determine namespace from addon setup file
        $this->namespace = $this->getNamespaceFromAddon();

        // Set default values
        $this->description = $this->serviceName . ' service';
        $this->author = $this->getAuthorFromAddon();

        if (!$this->filesystem->isDir($this->servicePath)) {
            $this->filesystem->mkDir($this->servicePath);
        }
    }

    public function build()
    {
        // Create main service file
        $this->createMainServiceFile();

        // Update addon.setup.php file
        $this->updateAddonSetupFile();

        return true;
    }

    /**
     * Create the main service file
     */
    private function createMainServiceFile()
    {
        $serviceStub = $this->filesystem->read($this->stub('service.php'));
        $serviceStub = $this->write('namespace', $this->namespace, $serviceStub);
        $serviceStub = $this->write('service_name', $this->serviceName, $serviceStub);
        $serviceStub = $this->write('addon_name', $this->addon, $serviceStub);
        $serviceStub = $this->write('author', $this->author, $serviceStub);
        $serviceStub = $this->write('description', $this->description, $serviceStub);

        $this->putFile($this->serviceName . '.php', $serviceStub, 'Service');
    }


    /**
     * Update addon.setup.php file to register the service we follow as close as we can how ModelGenerator has done this*
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
        } else { // The addon setup does not have the services array
            $servicesStub = $this->filesystem->read($this->stub('services.addon.php'));
            $servicesStub = $this->write('service_data', $serviceRegistration, $servicesStub);
            $pattern = '/(,)([^,]+)$/';
            $addonSetupFile = preg_replace($pattern, ",\n    $servicesStub $2", $addonSetupFile);
            $this->filesystem->write($this->addonPath . 'addon.setup.php', $addonSetupFile, true);
        }
    }

    /**
     * Get namespace from addon setup file
     *
     * @return string
     */
    private function getNamespaceFromAddon()
    {
        $setupFile = $this->addonPath . '/addon.setup.php';
        
        if (!$this->filesystem->exists($setupFile)) {
            // Fallback to generating namespace from addon name
            return $this->generateNamespaceFromAddonName();
        }

        $content = $this->filesystem->read($setupFile);
        
        // Look for namespace in the setup file
        if (preg_match("/'namespace'\s*=>\s*'([^']+)'/", $content, $matches)) {
            $namespace = $matches[1];
            return $namespace . '\\Service';
        }

        // Fallback to generating namespace from addon name
        return $this->generateNamespaceFromAddonName();
    }

    /**
     * Generate namespace from addon name
     *
     * @return string
     */
    private function generateNamespaceFromAddonName()
    {
        $namespace = str_replace(['-', '_'], ' ', $this->addon);
        $namespace = ucwords($namespace);
        $namespace = str_replace(' ', '', $namespace);
        
        return $namespace . '\\Service';
    }

    /**
     * Get author from addon setup file
     *
     * @return string
     */
    private function getAuthorFromAddon()
    {
        $setupFile = $this->addonPath . '/addon.setup.php';
        
        if (!$this->filesystem->exists($setupFile)) {
            return 'Unknown';
        }

        $content = $this->filesystem->read($setupFile);
        
        // Look for author in the setup file
        if (preg_match("/'author'\s*=>\s*'([^']+)'/", $content, $matches)) {
            return $matches[1];
        }

        return 'Unknown';
    }
} 