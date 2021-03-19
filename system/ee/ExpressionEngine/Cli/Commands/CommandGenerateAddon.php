<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Cli\Generator\Services\AddonGeneratorService;

/**
 * Command to clear selected caches
 */
class CommandGenerateAddon extends Cli
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
    public $signature = 'generate:addon';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'Generates an EE addon of any type';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary;

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli generate:addon "My Awesome Addon" --extension --hooks=category_save,after_category_field_update';

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
     * list of available caches
     * @var array
     */
    private $summaryText = 'This interactively generates an EE addon directly in your user directory.';

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

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('Let\'s build your add-on!');

        $this->data['type'] = $this->getType();

        $this->data['name'] = $this->getName();
        $this->data['description'] = $this->ask("{$this->type['name']} description?");
        $this->data['version'] = $this->ask("{$this->type['name']} version?");
        $this->data['author'] = $this->ask("{$this->type['name']} author?");
        $this->data['author_url'] = $this->ask("{$this->type['name']} author URL?");
        $this->data['has_settings'] = $this->confirm("Does your {$this->type['slug']} have settings?");

        $this->getTypeSpecificData();
        $this->getAdvancedSettings();

        $this->info('Let\'s build!');

        $this->build();

        $this->info('Your add-on has been created successfully!');
    }

    private function getTypeSpecificData()
    {
        if ($this->type['slug'] == 'module' || $this->type['slug'] == 'extension') {
            $this->data['hooks'] = $this->ask('Extension hooks?');
        }

        if ($this->type['slug'] == 'fieldtype') {
            $this->data['compatibility'] = $this->ask('Fieldtype compatibility?');
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
        $type = $this->getTypeFromOptions() ?: $this->ask('What type of addon would you like to create?');

        if (! in_array($type, $this->types)) {
            $this->error('Please select a proper type');
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
