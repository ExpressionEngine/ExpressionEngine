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
        $this->addonType = $data['addon_type'];
        $this->namespace = $data['namespace'];
        $this->isSingleton = $data['is_singleton'];
        $this->serviceName = $data['service_name'];
        $this->description = $data['description'];
        $this->author = $data['author'];

        // Set up addon path, generator path, and stub path
        $this->init();
    }

    private function init()
    {
        $this->initCommon();
        $this->servicePath = $this->addonPath . '/Service/';

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/';

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
     * Update addon.setup.php file to register the service
     */
    private function updateAddonSetupFile()
    {
        $setupFile = $this->addonPath . '/addon.setup.php';
        
        if (!$this->filesystem->exists($setupFile)) {
            return false;
        }

        $content = $this->filesystem->read($setupFile);
        
        $serviceKey = $this->isSingleton ? 'services.singletons' : 'services';
        
        // Create service registration with proper namespace escaping
        $namespaceParts = explode('\\', $this->namespace);
        $escapedNamespace = implode('\\\\', $namespaceParts);
        
        $serviceRegistration = "        '" . $this->serviceName . "' => function(\$addon)\n";
        $serviceRegistration .= "        {\n";
        $serviceRegistration .= "            return new \\" . $escapedNamespace . "\\" . $this->serviceName . "(\$addon);\n";
        $serviceRegistration .= "        },\n";

        // Check if the services array already exists
        if (strpos($content, "'" . $serviceKey . "'") !== false) {
            // Find the existing services array and add to it
            $pattern = "/('" . preg_quote($serviceKey, '/') . "'\s*=>\s*array\s*\([^)]*)\)/s";
            if (preg_match($pattern, $content, $matches)) {
                $existingServices = $matches[1];
                $newServices = $existingServices . $serviceRegistration . "    ),\n";
                $newContent = preg_replace($pattern, $newServices, $content);
                
                $this->filesystem->write($setupFile, $newContent, true);
                return true;
            }
        } else {
            // Create new services array
            $serviceArray = "    '" . $serviceKey . "' => array(\n";
            $serviceArray .= $serviceRegistration;
            $serviceArray .= "    ),\n";

            // Find the last closing bracket and add services before it
            $lastBracketPos = strrpos($content, ');');
            if ($lastBracketPos !== false) {
                $newContent = substr($content, 0, $lastBracketPos) . $serviceArray . substr($content, $lastBracketPos);
                
                $this->filesystem->write($setupFile, $newContent, true);
                return true;
            }
        }

        return false;
    }
} 