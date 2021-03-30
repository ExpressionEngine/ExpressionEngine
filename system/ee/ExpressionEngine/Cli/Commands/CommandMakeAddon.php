<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Cli\Generator\Services\AddonGeneratorService;

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
        'type,t:' => 'Type of add-on',
        'extension' => 'Create an extension',
        'plugin' => 'Create a plugin',
        'fieldtype' => 'Create a fieldtype',
        'module' => 'Create a module',
        'typography' => 'Should use plugin typography',
        'services:' => 'Comma-separated names of services to create',
        'models:' => 'Comma-separated names of models to create',
        'consents:' => 'Comma-separated names of consents',
        'cookies:' => 'Comma-separated names of cookies to create, with a colon separating name and value (i.e. name:value)',
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

        $this->data['type'] = $this->getType();
        $this->data['name'] = $this->getName();

        $this->data['description']  = $this->ask("{$this->type['name']} " . lang('command_make_addon_description_question'));
        $this->data['version']      = $this->ask("{$this->type['name']} " . lang('command_make_addon_version_question'));
        $this->data['author']       = $this->ask("{$this->type['name']} " . lang('command_make_addon_author_question'));
        $this->data['author_url']   = $this->ask("{$this->type['name']} " . lang('command_make_addon_author_url_question'));
        $this->data['has_settings'] = $this->confirm("Does your {$this->type['slug']} " . lang('command_make_addon_have_settings_question'));

        $this->getTypeSpecificData();
        $this->getAdvancedSettings();

        $this->info('command_make_addon_lets_build');

        $this->build();

        $this->info('command_make_addon_created_successfully');
    }

    private function getTypeSpecificData()
    {
        if ($this->type['slug'] == 'module' || $this->type['slug'] == 'extension') {
            $this->info('command_make_addon_what_hooks_to_use');
            $this->data['hooks'] = $this->ask('command_make_addon_ext_hooks');
        }

        if ($this->type['slug'] == 'fieldtype') {
            $this->data['compatibility'] = $this->ask('command_make_addon_ft_compatibility');
        }
    }

    private function getAdvancedSettings()
    {
        if ($this->option('--typography')) {
            $this->data['typography'] = $this->option('--typography') ? true : false;
        }

        if ($this->option('--services')) {
            $this->data['services'] = explode(',', $this->option('--services'));
        }
        if ($this->option('--models')) {
            $this->data['models'] = explode(',', $this->option('--models'));
        }
        if ($this->option('--consents')) {
            $this->data['consents'] = explode(',', $this->option('--consents'));
        }
        if ($this->option('--cookies')) {
            $cookies = [];

            foreach (explode(',', $this->option('--cookies')) as $cookie) {
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
        $service = new AddonGeneratorService($this->data);

        return $service->build();
    }

    private function getType()
    {
        $type = $this->getTypeFromOptions() ?: $this->ask(lang('command_make_addon_what_type_of_addon') . '[' . implode(', ', $this->types) . ']');

        if (! in_array($type, $this->types)) {
            $this->error('command_make_addon_select_proper_addon');
            $this->complete();
        }

        $this->type = [
            'name' => ucfirst($type),
            'slug' => $type,
        ];

        return $type;
    }

    private function getName()
    {
        return isset($this->arguments[0]) ? $this->arguments[0] : $this->ask("{$this->type['name']} name?");
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
