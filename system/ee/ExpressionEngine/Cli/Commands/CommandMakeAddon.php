<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;

/**
 * Command to clear selected caches
 */
class CommandMakeAddon extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Addon Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:addon';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:addon "My Awesome Addon" --extension --hooks=category_save,after_category_field_update';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        // Addon types
        'extension,ext' => 'Create an extension',
        'plugin,pi' => 'Create a plugin',
        'fieldtype,ft' => 'Create a fieldtype',
        'module,mod' => 'Create a module',

        // Addon setup toggles
        'typography,t' => 'Should use plugin typography',
        'has-settings,e:' => 'Add-on has settings (yes/no)',

        // Addon values
        'version,v:' => 'Version of the add-on',
        'description,d:' => 'Description of the add-on',
        'author,a:' => 'Author of the add-on',
        'author-url,u:' => 'Author url of the add-on',

        // Generate things for the addon
        'services,s*:' => 'Services to create. Multi-pass option.',
        'models,m*:' => 'Models to create. Multi-pass option.',
        'commands,c*:' => 'Commands to create. Multi-pass option.',
        'consents,n*:' => 'Consents. Multi-pass option.',
        'cookies,k*:' => 'Cookies to create, with a colon separating name and value (i.e. name:value). Multi-pass option.',
        'hooks,o*:' => 'Hooks in use. Multi-pass option.',
    ];

    /**
     * Command can run without EE Core
     * @var boolean
     */
    public $standalone = true;

    protected $data = [];

    protected $type = [];

    protected $types = [
        'extension',
        'plugin',
        'fieldtype',
        'module',
    ];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_addon_lets_build_addon');

        $this->data['name'] = $this->getName();
        $this->data['type'] = $this->getType();

        // Get description
        $this->data['description']  = $this->getOptionOrAsk(
            "--description",
            "{$this->type['name']} " . lang('command_make_addon_description_question')
        );

        // Get version
        $this->data['version']  = $this->getOptionOrAsk(
            "--version",
            "{$this->type['name']} " . lang('command_make_addon_version_question'),
            '1.0.0',
            true
        );

        // Get author
        $this->data['author']  = $this->getOptionOrAsk(
            "--author",
            "{$this->type['name']} " . lang('command_make_addon_author_question')
        );

        // Get author_url
        $this->data['author_url']  = $this->getOptionOrAsk(
            "--author-url",
            "{$this->type['name']} " . lang('command_make_addon_author_url_question')
        );

        // If they passed the settings flag, always take that
        if ($this->option('--has-settings')) {
            $this->data['has_settings'] = $this->option('--has-settings');
        } elseif ($this->data['type'] == 'plugin') {
            // Default to no if it's a plugin
            $this->data['has_settings'] = 'no';
        } else {
            // Ask if not passed and not a plugin
            $this->data['has_settings']  = $this->confirm("Does your {$this->type['slug']} " . lang('command_make_addon_have_settings_question'));
        }

        $this->getTypeSpecificData();
        $this->getAdvancedSettings();

        $this->info('command_make_addon_lets_build');

        $this->build();

        $this->info('command_make_addon_created_successfully');
    }

    private function getTypeSpecificData()
    {
        // Extension specific options
        if ($this->type['slug'] == 'module' || $this->type['slug'] == 'extension') {
            // No hooks were passed, so we're giving info on the hooks
            if (! $this->option('--hooks')) {
                $this->info('command_make_addon_what_hooks_to_use');
            }

            // Set or ask for what hooks to use
            $this->data['hooks'] = $this->getOptionOrAsk(
                '--hooks',
                'command_make_addon_ext_hooks',
            );
        }

        // Fieldtype specific options
        if ($this->type['slug'] == 'fieldtype') {
            $this->data['compatibility'] = $this->ask('command_make_addon_ft_compatibility');
        }
    }

    private function getAdvancedSettings()
    {
        if ($this->option('--typography')) {
            $this->data['typography'] = $this->option('--typography');
        }
        if ($this->option('--services')) {
            $this->data['services'] = $this->option('--services');
        }
        if ($this->option('--models')) {
            $this->data['models'] = $this->option('--models');
        }
        if ($this->option('--consents')) {
            $this->data['consents'] = $this->option('--consents');
        }
        if ($this->option('--cookies')) {
            $cookies = [];

            foreach ($this->option('--cookies') as $cookie) {
                if (strpos($cookie, ':') === false) {
                    continue;
                }

                $cookieSplit = explode(':', $cookie);
                $cookies[$cookieSplit[0]] = $cookieSplit[1];
            }

            $this->data['cookies'] = $cookies;
        }
    }

    private function build()
    {
        $service = ee('AddonGenerator', $this->data);

        return $service->build();
    }

    private function getType()
    {
        $type = $this->getTypeFromOptions() ?: $this->ask(lang('command_make_addon_what_type_of_addon') . ' [' . implode(', ', $this->types) . ']');

        if (! in_array($type, $this->types)) {
            $this->fail('command_make_addon_select_proper_addon');
        }

        $this->type = [
            'name' => ucfirst($type),
            'slug' => $type,
        ];

        return $type;
    }

    private function getName()
    {
        $name = $this->getFirstUnnamedArgument();

        if (is_null($name)) {
            $name = $this->ask("What is the name of your add-on?");
        }

        if (empty(trim($name))) {
            $this->fail('command_make_addon_addon_name_required');
        }

        return $name;
    }

    private function getTypeFromOptions()
    {
        foreach ($this->types as $type) {
            if ($this->option('--' . $type)) {
                return $type;
            }
        }

        return null;
    }
}
