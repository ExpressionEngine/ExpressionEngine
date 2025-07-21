<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;

/**
 * Command to generate service folder for addons
 */
class CommandMakeService extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Service Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:service';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:service "my_addon"';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'       => 'command_make_service_option_addon',
        'description,d:' => 'command_make_service_option_description',
        'author,au:'     => 'command_make_service_option_author',
        'version,v:'     => 'command_make_service_option_version',
        'type,t:'        => 'command_make_service_option_type',
        'namespace,n:'   => 'command_make_service_option_namespace',
        'singleton,s'    => 'command_make_service_option_singleton',
        'service-name,sn:' => 'command_make_service_option_service_name',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_service_lets_build_service');

        $this->data['addon_name'] = $this->getAddonName();
        $this->data['addon_type'] = $this->getAddonType();
        $this->data['namespace'] = $this->getNamespace();
        $this->data['is_singleton'] = $this->getSingletonOption();
        $this->data['service_name'] = $this->getServiceName();

        // Get description
        $this->data['description'] = $this->getOptionOrAsk(
            "--description",
            "Service " . lang('command_make_service_description_question'),
            $this->data['service_name'] . ' service'
        );

        // Get author
        $this->data['author'] = $this->getOptionOrAsk(
            "--author",
            "Service " . lang('command_make_service_author_question'),
            ee('Config')->get('cli_default_addon_author'),
            true
        );

        // Get version
        $this->data['version'] = $this->getOptionOrAsk(
            "--version",
            "Service " . lang('command_make_service_version_question'),
            '1.0.0',
            true
        );

        $this->info('command_make_service_lets_build');

        $this->build();

        $this->info('command_make_service_created_successfully');
    }

    private function build()
    {
        try {
            // Validate addon path exists
            $addon_path = $this->validateAddonPath($this->data['addon_name'], $this->data['addon_type']);
            $service_path = $addon_path . '/Service/';

            // Create Service directory if it doesn't exist
            if (!is_dir($service_path)) {
                if (!mkdir($service_path, 0755, true)) {
                    throw new \Exception('Unable to create Service directory: ' . $service_path);
                }
            }

            // Create main service file
            $this->createMainServiceFile($service_path, $this->data['addon_name'], $this->data['addon_type']);

            // Create service index file
            $this->createServiceIndexFile($service_path, $this->data['addon_name']);

            // Create README file
            $this->createReadmeFile($service_path, $this->data['addon_name'], $this->data['addon_type']);

            // Update addon.setup.php file
            $this->updateAddonSetupFile($addon_path, $this->data['addon_name']);

            return true;
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }
    }

    private function getAddonName()
    {
        // This is the addon name passed to the CLI
        $addon_name = $this->getFirstUnnamedArgument();

        // If no addon name was passed, ask for a name
        if (is_null($addon_name)) {
            $addon_name = $this->ask('command_make_service_what_is_addon_name');
        }

        // Lets filter the name to only allow alphanumerics, "-", "_" and spaces
        $addon_name = preg_replace("/[^A-Za-z0-9 \-_]/", '', $addon_name);

        if (empty(trim($addon_name))) {
            $this->fail('command_make_service_addon_name_required');
        }

        return $addon_name;
    }

    private function getAddonType()
    {
        $addon_type = $this->getOptionOrAsk(
            "--type",
            "Addon type (first-party or third-party)",
            'third-party',
            true
        );

        // Validate addon type
        $valid_types = ['first-party', 'third-party', 'first', 'third'];
        if (!in_array(strtolower($addon_type), $valid_types)) {
            $this->fail('command_make_service_invalid_addon_type');
        }

        // Normalize type
        if (in_array(strtolower($addon_type), ['first', 'first-party'])) {
            return 'first-party';
        }

        return 'third-party';
    }

    private function getNamespace()
    {
        $namespace = $this->getOptionOrAsk(
            "--namespace",
            "Service namespace (e.g., MyAddon\\Service)",
            '',
            true
        );

        if (empty($namespace)) {
            // Generate namespace from addon name
            $namespace = $this->generateNamespace($this->data['addon_name']);
        }

        return $namespace;
    }

    private function generateNamespace($addon_name)
    {
        // Convert addon name to proper namespace format
        $namespace = str_replace(['-', '_'], ' ', $addon_name);
        $namespace = ucwords($namespace);
        $namespace = str_replace(' ', '', $namespace);
        
        return $namespace . '\\Service';
    }

    private function getServiceName()
    {
        $service_name = $this->getOptionOrAsk(
            "--service-name",
            "Service name (e.g., MyService)",
            '',
            true
        );

        if (empty($service_name)) {
            // Generate service name from addon name
            $service_name = $this->generateServiceName($this->data['addon_name']);
        }

        // Validate service name format
        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $service_name)) {
            $this->fail('Service name must start with a letter and contain only letters and numbers');
        }

        return $service_name;
    }

    private function generateServiceName($addon_name)
    {
        // Convert addon name to proper service name format
        $service_name = str_replace(['-', '_'], ' ', $addon_name);
        $service_name = ucwords($service_name);
        $service_name = str_replace(' ', '', $service_name);
        
        return $service_name . 'Service';
    }

    private function getSingletonOption()
    {
        $is_singleton = $this->getOptionOrAsk(
            "--singleton",
            "Should this service be a singleton? (y/n)",
            'n',
            true
        );

        return strtolower($is_singleton) === 'y' || strtolower($is_singleton) === 'yes';
    }

    private function validateAddonPath($addon_name, $addon_type)
    {
        if ($addon_type === 'first-party') {
            // First-party addons are in system/ee/ExpressionEngine/Addons/
            $addon_path = PATH_ADDONS . $addon_name;
        } else {
            // Third-party addons are in system/user/addons/
            $addon_path = PATH_THIRD . $addon_name;
        }

        if (!is_dir($addon_path)) {
            $this->fail('command_make_service_addon_not_found: ' . $addon_name . ' (path: ' . $addon_path . ')');
        }

        return $addon_path;
    }

    /**
     * Create the main service file
     * @param string $service_path
     * @param string $addon_name
     * @param string $addon_type
     */
    private function createMainServiceFile($service_path, $addon_name, $addon_type)
    {
        $service_class_name = $this->data['service_name'];
        $filename = $service_path . $service_class_name . '.php';

        $content = "<?php\n\n";
        $content .= "/**\n";
        $content .= " * " . $addon_name . " Service\n";
        $content .= " *\n";
        $content .= " * @package     " . $addon_name . "\n";
        $content .= " * @author      " . $this->data['author'] . "\n";
        $content .= " * @version     " . $this->data['version'] . "\n";
        $content .= " * @description " . $this->data['description'] . "\n";
        $content .= " */\n\n";

        $content .= "namespace " . $this->data['namespace'] . ";\n\n";

        $content .= "use ExpressionEngine\Service\Addon\Addon;\n\n";

        $content .= "/**\n";
        $content .= " * " . $service_class_name . " Service\n";
        $content .= " */\n";
        $content .= "class " . $service_class_name . "\n";
        $content .= "{\n";
        $content .= "    /**\n";
        $content .= "     * @var Addon\n";
        $content .= "     */\n";
        $content .= "    protected \$addon;\n\n";

        $content .= "    /**\n";
        $content .= "     * Constructor\n";
        $content .= "     *\n";
        $content .= "     * @param Addon \$addon\n";
        $content .= "     */\n";
        $content .= "    public function __construct(Addon \$addon)\n";
        $content .= "    {\n";
        $content .= "        \$this->addon = \$addon;\n";
        $content .= "    }\n\n";

        $content .= "    /**\n";
        $content .= "     * Example service method\n";
        $content .= "     *\n";
        $content .= "     * @param string \$param\n";
        $content .= "     * @return string\n";
        $content .= "     */\n";
        $content .= "    public function exampleMethod(\$param = '')\n";
        $content .= "    {\n";
        $content .= "        return 'Example: ' . \$param;\n";
        $content .= "    }\n\n";

        $content .= "    /**\n";
        $content .= "     * Get service version\n";
        $content .= "     *\n";
        $content .= "     * @return string\n";
        $content .= "     */\n";
        $content .= "    public function getVersion()\n";
        $content .= "    {\n";
        $content .= "        return '" . $this->data['version'] . "';\n";
        $content .= "    }\n\n";

        $content .= "    /**\n";
        $content .= "     * Get addon information\n";
        $content .= "     *\n";
        $content .= "     * @return array\n";
        $content .= "     */\n";
        $content .= "    public function getAddonInfo()\n";
        $content .= "    {\n";
        $content .= "        return \$this->addon->getInfo();\n";
        $content .= "    }\n";
        $content .= "}\n\n";

        $content .= "/* End of file " . $service_class_name . ".php */\n";
        
        // Set correct location comment based on addon type
        if ($addon_type === 'first-party') {
            $content .= "/* Location: ./system/expressionengine/addons/" . $addon_name . "/Service/" . $service_class_name . ".php */\n";
        } else {
            $content .= "/* Location: ./system/user/addons/" . $addon_name . "/Service/" . $service_class_name . ".php */\n";
        }

        if (!file_put_contents($filename, $content)) {
            throw new \Exception('Unable to create service file: ' . $filename);
        }
    }

    /**
     * Create service index file
     * @param string $service_path
     * @param string $addon_name
     */
    private function createServiceIndexFile($service_path, $addon_name)
    {
        $filename = $service_path . 'index.html';

        $content = "<!DOCTYPE html>\n";
        $content .= "<html>\n";
        $content .= "<head>\n";
        $content .= "    <title>403 Forbidden</title>\n";
        $content .= "</head>\n";
        $content .= "<body>\n";
        $content .= "<p>Directory access is forbidden.</p>\n";
        $content .= "</body>\n";
        $content .= "</html>";

        if (!file_put_contents($filename, $content)) {
            throw new \Exception('Unable to create index file: ' . $filename);
        }
    }

    /**
     * Create README file
     * @param string $service_path
     * @param string $addon_name
     * @param string $addon_type
     */
    private function createReadmeFile($service_path, $addon_name, $addon_type)
    {
        $filename = $service_path . 'README.md';

        $content = "# " . $this->data['service_name'] . " Service\n\n";
        $content .= $this->data['description'] . "\n\n";
        $content .= "## Author\n\n";
        $content .= $this->data['author'] . "\n\n";
        $content .= "## Version\n\n";
        $content .= $this->data['version'] . "\n\n";
        $content .= "## Usage\n\n";
        $content .= "This service is automatically registered in the addon.setup.php file and can be accessed using:\n\n";
        $content .= "```php\n";
        $content .= "// Access the service\n";
        $content .= "\$service = ee('" . strtolower($addon_name) . ":" . $this->data['service_name'] . "');\n\n";
        $content .= "// Use service methods\n";
        $content .= "echo \$service->exampleMethod('test');\n";
        $content .= "echo \$service->getVersion();\n";
        $content .= "```\n\n";
        
        // Add singleton information if applicable
        if ($this->data['is_singleton']) {
            $content .= "## Singleton Service\n\n";
            $content .= "This service is registered as a **singleton**, meaning only one instance will be created and reused throughout the application lifecycle.\n\n";
        }
        
        // Add addon type information
        $content .= "## Addon Type\n\n";
        $content .= "This service is for a **" . $addon_type . "** addon.\n\n";
        $content .= "**Path:** ";
        if ($addon_type === 'first-party') {
            $content .= "`system/ee/ExpressionEngine/Addons/" . $addon_name . "/Service/`\n\n";
        } else {
            $content .= "`system/user/addons/" . $addon_name . "/Service/`\n\n";
        }
        
        $content .= "## Methods\n\n";
        $content .= "- `exampleMethod(\$param)` - Example service method\n";
        $content .= "- `getVersion()` - Get service version\n";
        $content .= "- `getAddonInfo()` - Get addon information\n\n";
        $content .= "## Adding New Methods\n\n";
        $content .= "Add new service methods to the `" . $this->data['service_name'] . ".php` file.\n\n";
        $content .= "## Service Registration\n\n";
        $content .= "This service is automatically registered in the addon.setup.php file in the `" . ($this->data['is_singleton'] ? 'services.singletons' : 'services') . "` array.\n";

        if (!file_put_contents($filename, $content)) {
            throw new \Exception('Unable to create README file: ' . $filename);
        }
    }

    /**
     * Update addon.setup.php file to register the service
     * @param string $addon_path
     * @param string $addon_name
     */
    private function updateAddonSetupFile($addon_path, $addon_name)
    {
        $setup_file = $addon_path . '/addon.setup.php';
        
        if (!file_exists($setup_file)) {
            $this->info('addon.setup.php not found. Service registration will need to be added manually.');
            return;
        }

        $content = file_get_contents($setup_file);
        
        $service_class_name = $this->data['service_name'];
        $service_key = $this->data['is_singleton'] ? 'services.singletons' : 'services';
        
        // Create service registration with proper namespace escaping
        $namespace_parts = explode('\\', $this->data['namespace']);
        $escaped_namespace = implode('\\\\', $namespace_parts);
        
        $service_registration = "        '" . $service_class_name . "' => function(\$addon)\n";
        $service_registration .= "        {\n";
        $service_registration .= "            return new \\" . $escaped_namespace . "\\" . $service_class_name . "(\$addon);\n";
        $service_registration .= "        },\n";

        // Check if the services array already exists
        if (strpos($content, "'" . $service_key . "'") !== false) {
            // Find the existing services array and add to it
            $pattern = "/('" . preg_quote($service_key, '/') . "'\s*=>\s*array\s*\([^)]*)\)/s";
            if (preg_match($pattern, $content, $matches)) {
                $existing_services = $matches[1];
                $new_services = $existing_services . $service_registration . "    ),\n";
                $new_content = preg_replace($pattern, $new_services, $content);
                
                if (!file_put_contents($setup_file, $new_content)) {
                    throw new \Exception('Unable to update addon.setup.php file');
                }
                
                $this->info('Service registration added to existing ' . $service_key . ' array in addon.setup.php');
            } else {
                $this->info('Could not parse existing ' . $service_key . ' array. Please add the service registration manually.');
            }
        } else {
            // Create new services array
            $service_array = "    '" . $service_key . "' => array(\n";
            $service_array .= $service_registration;
            $service_array .= "    ),\n";

            // Find the last closing bracket and add services before it
            $last_bracket_pos = strrpos($content, ');');
            if ($last_bracket_pos !== false) {
                $new_content = substr($content, 0, $last_bracket_pos) . $service_array . substr($content, $last_bracket_pos);
                
                if (!file_put_contents($setup_file, $new_content)) {
                    throw new \Exception('Unable to update addon.setup.php file');
                }
                
                $this->info('Service registration added to addon.setup.php as ' . ($this->data['is_singleton'] ? 'singleton' : 'regular service'));
            } else {
                $this->info('Could not automatically update addon.setup.php. Please add the service registration manually.');
            }
        }
    }
} 