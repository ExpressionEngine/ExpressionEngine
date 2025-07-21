<?php
/**
 * 
 * # First-party addon
* php eecli.php make:helper "structure" --type="first-party"

* # Third-party addon
*php eecli.php make:helper "my_addon" --type="third-party"
 * 
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
 * Command to generate helper folder for third-party addons
 */
class CommandMakeHelper extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Helper Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:helper';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:helper "my_addon"';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'       => 'command_make_helper_option_addon',
        'description,d:' => 'command_make_helper_option_description',
        'author,au:'     => 'command_make_helper_option_author',
        'version,v:'     => 'command_make_helper_option_version',
        'type,t:'        => 'command_make_helper_option_type',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_helper_lets_build_helper');

        $this->data['addon_name'] = $this->getAddonName();
        $this->data['addon_type'] = $this->getAddonType();

        // Get description
        $this->data['description'] = $this->getOptionOrAsk(
            "--description",
            "Helper " . lang('command_make_helper_description_question'),
            $this->data['addon_name'] . ' helper functions'
        );

        // Get author
        $this->data['author'] = $this->getOptionOrAsk(
            "--author",
            "Helper " . lang('command_make_helper_author_question'),
            ee('Config')->get('cli_default_addon_author'),
            true
        );

        // Get version
        $this->data['version'] = $this->getOptionOrAsk(
            "--version",
            "Helper " . lang('command_make_helper_version_question'),
            '1.0.0',
            true
        );

        $this->info('command_make_helper_lets_build');

        $this->build();

        $this->info('command_make_helper_created_successfully');
    }

    private function build()
    {
        try {
            // Validate addon path exists
            $addon_path = $this->validateAddonPath($this->data['addon_name'], $this->data['addon_type']);
            $helper_path = $addon_path . '/helpers/';

            // Create helpers directory if it doesn't exist
            if (!is_dir($helper_path)) {
                if (!mkdir($helper_path, 0755, true)) {
                    throw new \Exception('Unable to create helpers directory: ' . $helper_path);
                }
            }

            // Create main helper file
            $this->createMainHelperFile($helper_path, $this->data['addon_name'], $this->data['addon_type']);

            // Create helper index file
            $this->createHelperIndexFile($helper_path, $this->data['addon_name']);

            // Create README file
            $this->createReadmeFile($helper_path, $this->data['addon_name'], $this->data['addon_type']);

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
            $addon_name = $this->ask('command_make_helper_what_is_addon_name');
        }

        // Lets filter the name to only allow alphanumerics, "-", "_" and spaces
        $addon_name = preg_replace("/[^A-Za-z0-9 \-_]/", '', $addon_name);

        if (empty(trim($addon_name))) {
            $this->fail('command_make_helper_addon_name_required');
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
            $this->fail('command_make_helper_invalid_addon_type');
        }

        // Normalize type
        if (in_array(strtolower($addon_type), ['first', 'first-party'])) {
            return 'first-party';
        }

        return 'third-party';
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
            $this->fail('command_make_helper_addon_not_found: ' . $addon_name . ' (path: ' . $addon_path . ')');
        }

        return $addon_path;
    }

    /**
     * Create the main helper file
     * @param string $helper_path
     * @param string $addon_name
     * @param string $addon_type
     */
    private function createMainHelperFile($helper_path, $addon_name, $addon_type)
    {
        $helper_class_name = $this->getHelperClassName($addon_name);
        $filename = $helper_path . $addon_name . '_helper.php';

        $content = "<?php\n\n";
        $content .= "/**\n";
        $content .= " * " . $addon_name . " Helper\n";
        $content .= " *\n";
        $content .= " * @package     " . $addon_name . "\n";
        $content .= " * @author      " . $this->data['author'] . "\n";
        $content .= " * @version     " . $this->data['version'] . "\n";
        $content .= " * @description " . $this->data['description'] . "\n";
        $content .= " */\n\n";

        $content .= "if (!defined('BASEPATH')) {\n";
        $content .= "    exit('No direct script access allowed');\n";
        $content .= "}\n\n";

        $content .= "class " . $helper_class_name . "\n";
        $content .= "{\n";
        $content .= "    /**\n";
        $content .= "     * Constructor\n";
        $content .= "     */\n";
        $content .= "    public function __construct()\n";
        $content .= "    {\n";
        $content .= "        // Initialize helper\n";
        $content .= "    }\n\n";

        $content .= "    /**\n";
        $content .= "     * Example helper function\n";
        $content .= "     *\n";
        $content .= "     * @param string \$param\n";
        $content .= "     * @return string\n";
        $content .= "     */\n";
        $content .= "    public function example_function(\$param = '')\n";
        $content .= "    {\n";
        $content .= "        return 'Example: ' . \$param;\n";
        $content .= "    }\n\n";

        $content .= "    /**\n";
        $content .= "     * Get helper version\n";
        $content .= "     *\n";
        $content .= "     * @return string\n";
        $content .= "     */\n";
        $content .= "    public function get_version()\n";
        $content .= "    {\n";
        $content .= "        return '" . $this->data['version'] . "';\n";
        $content .= "    }\n";
        $content .= "}\n\n";

        $content .= "/* End of file " . $addon_name . "_helper.php */\n";
        
        // Set correct location comment based on addon type
        if ($addon_type === 'first-party') {
            $content .= "/* Location: ./system/expressionengine/addons/" . $addon_name . "/helpers/" . $addon_name . "_helper.php */\n";
        } else {
            $content .= "/* Location: ./system/user/addons/" . $addon_name . "/helpers/" . $addon_name . "_helper.php */\n";
        }

        if (!file_put_contents($filename, $content)) {
            throw new \Exception('Unable to create helper file: ' . $filename);
        }
    }

    /**
     * Create helper index file
     * @param string $helper_path
     * @param string $addon_name
     */
    private function createHelperIndexFile($helper_path, $addon_name)
    {
        $filename = $helper_path . 'index.html';

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
     * @param string $helper_path
     * @param string $addon_name
     * @param string $addon_type
     */
    private function createReadmeFile($helper_path, $addon_name, $addon_type)
    {
        $filename = $helper_path . 'README.md';

        $content = "# " . ucfirst($addon_name) . " Helper\n\n";
        $content .= $this->data['description'] . "\n\n";
        $content .= "## Author\n\n";
        $content .= $this->data['author'] . "\n\n";
        $content .= "## Version\n\n";
        $content .= $this->data['version'] . "\n\n";
        $content .= "## Usage\n\n";
        $content .= "To use this helper in your addon:\n\n";
        $content .= "```php\n";
        $content .= "// Load the helper\n";
        $content .= "ee()->load->helper('" . $addon_name . "/" . $addon_name . "_helper');\n\n";
        $content .= "// Create instance\n";
        $content .= "\$helper = new " . $this->getHelperClassName($addon_name) . "();\n\n";
        $content .= "// Use helper functions\n";
        $content .= "echo \$helper->example_function('test');\n";
        $content .= "```\n\n";
        
        // Add addon type information
        $content .= "## Addon Type\n\n";
        $content .= "This helper is for a **" . $addon_type . "** addon.\n\n";
        $content .= "**Path:** ";
        if ($addon_type === 'first-party') {
            $content .= "`system/ee/ExpressionEngine/Addons/" . $addon_name . "/helpers/`\n\n";
        } else {
            $content .= "`system/user/addons/" . $addon_name . "/helpers/`\n\n";
        }
        
        $content .= "## Functions\n\n";
        $content .= "- `example_function(\$param)` - Example helper function\n";
        $content .= "- `get_version()` - Get helper version\n\n";
        $content .= "## Adding New Functions\n\n";
        $content .= "Add new helper functions to the `" . $addon_name . "_helper.php` file.\n";

        if (!file_put_contents($filename, $content)) {
            throw new \Exception('Unable to create README file: ' . $filename);
        }
    }

    /**
     * Get helper class name from addon name
     * @param string $addon_name
     * @return string
     */
    private function getHelperClassName($addon_name)
    {
        // Convert addon name to proper class name format
        $class_name = str_replace(['-', '_'], ' ', $addon_name);
        $class_name = ucwords($class_name);
        $class_name = str_replace(' ', '', $class_name);
        
        return $class_name . '_helper';
    }
} 