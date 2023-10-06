<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\TemplateGenerator;

use ExpressionEngine\Service\Model;
use ExpressionEngine\Core\Provider;
use ExpressionEngine\Model\Template\TemplateGroup;
use ExpressionEngine\Model\Template\Template;

/**
 * Template Generator Factory
 *
 * It will take any generator (built-in or provided by add-on)
 * that is compatible with TemplateGeneratorInterface
 * and turn it into service that we can work with
 *
 * `setGenerator()` method needs to be run before we can do anything -
 * with the only exception of `listGenerators()`
 */
class Factory
{
    /**
     * Template engine to use
     * Defaults to native but can also be set to twig or blade
     *
     * @var string
     */
    public $templateEngine;

    /**
     * The list of extra themes that are available
     *
     * @var array
     */
    protected $themes;

    /**
     * Generator that we want to use
     *
     * @var TemplateGeneratorInterface
     */
    protected $generator;

    /**
     * Options that we want to ask for any generator run
     *
     * @var array
     */
    protected $options = [
        'template_engine' => [
            'type' => 'select',
            'callback' => 'getTemplateEnginesList',
            'desc' => 'select_template_engine',
            'default' => ''
        ],
        'theme' => [
            'type' => 'select',
            'callback' => 'getThemesList',
            'desc' => 'select_theme',
            'default' => 'none'
        ],
        'site_id' => [
            'type' => 'select',
            'callback' => 'getSitesList',
            'default' => '1',
            'required' => true
        ],
        'template_group' => [
            'type' => 'text',
            'default' => '',
            'desc' => 'name_of_template_group',
            'required' => true
        ],
        'templates' => [
            'type' => 'checkbox',
            'callback' => 'getTemplatesList',
            'desc' => 'select_templates_to_generate',
            'default' => 'all',
        ],
    ];

    /**
     * Validation rules for the options
     *
     * @var array
     */
    protected $_validation_rules = [
        'site_id' => 'integer',
        'template_engine' => 'validateTemplateEngine',
        'theme' => 'required|validateTheme',
        'template_group' => 'required|alphaDashPeriodEmoji|validateTemplateGroup[site_id]'
    ];

    /**
     * Values of options passed to this generator
     *
     * @var array
     */
    protected $values = [];

    /**
     * List of all generators available in the system
     *
     * @var array
     */
    protected $registeredGenerators;

    /**
     * Roles that can access the templates
     *
     * @var Model\Collection
     */
    protected $permittedRoles;

    /**
     * Stubs and generators for all installed fieldtypes
     *
     * @var array
     */
    protected $ftStubsAndGenerators;

    /**
     * Templates are MSM specific
     *
     * @var integer
     */
    public $site_id = 1;

    /**
     * A list of shared paths to look for stubs in, priority is given to earlier paths
     *
     * @var array
     */
    protected $sharedStubPaths = [];

    public function __construct()
    {
        ee()->lang->load('cp');
        ee()->lang->load('design');
        $this->values = [];
    }

    /**
     * Sets the values passed as options
     *
     * @param array $values
     * @return void
     */
    public function setOptionValues(array $values): void
    {
        $this->values = $values;
        if (isset($this->values['site_id'])) {
            $this->site_id = (int) $this->values['site_id'];
        }
        if (isset($this->values['template_engine'])) {
            $this->templateEngine = (string) $this->values['template_engine'];
        }
    }

    /**
     * Return values of options passed to generator
     *
     * @return array
     */
    public function getOptionValues(): array
    {
        return $this->values;
    }

    /**
     * Get the array of stub paths
     * This would include:
     * - user folder
     * - add-on folder
     * - theming add-on folder
     * - shared stubs folder
     *
     * @param Provider $provider
     * @param string $generatorFolder
     *
     * @return array
     */
    public function getStubPaths(Provider $provider, string $generatorFolder)
    {
        $stubPaths = [];
        $optionValues = ee('TemplateGenerator')->getOptionValues();
        if (is_null($this->themes)) {
            $this->registerThemes();
        }
        if (isset($optionValues['theme']) && !empty($optionValues['theme']) && $optionValues['theme'] != 'none') {
            // if we use a theme, we need to check the path set by theme
            if (!isset($this->themes[$optionValues['theme']])) {
                throw new \Exception('Theme not found');
            }
            $themeProviderPrefix = $this->themes[$optionValues['theme']]['prefix'];
            $themeFolder = $this->themes[$optionValues['theme']]['folder'];
            $themeProvider = ee('App')->get($themeProviderPrefix);
            // user folder first, then own folder of theme add-on
            $stubPaths[] = SYSPATH . 'user/stubs/' . $themeProviderPrefix . '/' . $themeFolder . '/' . $provider->getPrefix() . '/' . $generatorFolder;
            // e.g. system/user/stubs/mytheme/tailwind/channel/entries
            $stubPaths[] = $themeProvider->getPath() . '/stubs/' . $themeFolder . '/' . $provider->getPrefix() . '/' . $generatorFolder;
            // e.g. system/user/addons/mytheme/stubs/tailwind/channel/entries
        }

        //user-provided stubs for this generator
        $stubPaths[] = SYSPATH . 'user/stubs/' . $provider->getPrefix() . '/' . $generatorFolder;
        // e.g. system/user/stubs/channel/entries
        // stubs provided by the generator add-on
        $stubPaths[] = $provider->getPath() . '/stubs/' . $generatorFolder;
        // e.g. system/ee/ExpressionEngine/addons/channel/entries
        // or system/user/addons/channel/entries
        // twig and blade will be taking this from elswehere
        // e.g. 'vendor/coilpack/stubs/' . $provider->getPrefix() . '/' . $generatorFolder;

        $stubPaths = array_merge($stubPaths, $this->getSharedStubPaths());

        return $stubPaths;
    }

    /**
     * Get the stub paths that are in shared folders
     *
     * @return array
     */
    public function getSharedStubPaths()
    {
        if (empty($this->sharedStubPaths)) {
            $optionValues = $this->getOptionValues();
            if (isset($optionValues['theme']) && !empty($optionValues['theme']) && $optionValues['theme'] != 'none') {
                // if we use a theme, we need to check the path set by theme
                $themeProviderPrefix = $this->themes[$optionValues['theme']]['prefix'];
                $themeFolder = $this->themes[$optionValues['theme']]['folder'];
                $themeProvider = ee('App')->get($themeProviderPrefix);
                $this->sharedStubPaths[] = SYSPATH . 'user/stubs/' . $themeProviderPrefix . '/' . $themeFolder;
                $this->sharedStubPaths[] = $themeProvider->getPath() . '/stubs/' . $themeFolder;
            }
            //if specifics not found, fallback to shared stubs (user first)
            $this->sharedStubPaths[] = SYSPATH . 'user/stubs';
            // ee/templates/stubs is shared folder for native template engine
            // twig and blade will have their own folder elsewhere
            $this->sharedStubPaths[] = SYSPATH . 'ee/templates/stubs';
        }

        return $this->sharedStubPaths;
    }

    /**
     * Set the Generator that we want to use
     * If it does not exist, or is not valid, we'll trow an Exception
     *
     * @param mixed $generatorKey prefix:className key from registeredGenerators
     * @return void;
     */
    public function setGenerator($generatorKey)
    {
        if (empty($generatorKey)) {
            throw new \Exception('Template Generator is required');
        }
        if (is_null($this->registeredGenerators)) {
            $this->registerAllTemplateGenerators();
        }
        if (!isset($this->registeredGenerators[$generatorKey])) {
            throw new \Exception('Template Generator could not be found');
        }
        if (! ($this->registeredGenerators[$generatorKey] instanceof RegisteredGenerator)) {
            throw new \Exception('Template Generator is not properly registered');
        }
        $this->generator = $this->registeredGenerators[$generatorKey];
        ee()->lang->loadfile($this->generator->prefix, '', false);
    }

    /**
     * List the generators available in the system
     *
     * @param string $generatorKey prefix:className
     * @return array
     */
    public function listGenerators($generatorKey = '')
    {
        $generators = $this->registerAllTemplateGenerators($generatorKey);

        return $generators;
    }

    /**
     * Ensure all template generators are registered
     *
     * @return array
     */
    public function registerAllTemplateGenerators()
    {
        if (is_null($this->registeredGenerators)) {
            $providers = ee('App')->getProviders();
            foreach ($providers as $provider) {
                if (method_exists($provider, 'registerTemplateGenerators')) {
                    $provider->registerTemplateGenerators();
                }
            }
        }

        return $this->registeredGenerators;
    }

    /**
     * Add generator to registry
     *
     * @param string $name
     * @param Provider $provider
     * @return array all registered generators
     */
    public function register(string $className, Provider $provider)
    {
        if (empty($className) || empty($provider)) {
            return $this->registeredGenerators;
        }

        // get the modifier's FQCN and see if it exists
        $fqcn = trim($provider->getNamespace(), '\\') . '\\TemplateGenerators\\' . ucfirst($className);
        if (! class_exists($fqcn)) {
            return $this->registeredGenerators;
        }

        // does it implement interface?
        $interfaces = class_implements($fqcn);
        if (! isset($interfaces[TemplateGeneratorInterface::class])) {
            return $this->registeredGenerators;
        }

        // register it!
        $this->registeredGenerators[$provider->getPrefix() . ':' . lcfirst($className)] = new RegisteredGenerator(
            $provider->getPrefix(),
            $className,
            $fqcn
        );

        return $this->registeredGenerators;
    }

    /**
     * Returns the instance of generator that's currently in use
     *
     * @return RegisteredGenerator
     */
    public function getGenerator()
    {
        if (empty($this->generator)) {
            throw new \Exception('Template Generator is required');
        }

        return $this->generator;
    }

    /**
     * Return the list of options that we want to ask for
     * Includes options that are both generic and specific to the generator
     *
     * @return array
     */
    public function getOptions()
    {
        return array_merge($this->options, $this->getGenerator()->getInstance()->getOptions());
    }

    /**
     * Return array of validation rules for factory and current generator
     *
     * @return array
     */
    public function getValidationRules()
    {
        return array_merge($this->_validation_rules, $this->getGenerator()->getInstance()->getValidationRules());
    }

    /**
     * Populates choices for a given option from callback function in generator (or directly in factory)
     * The callback function must return an array of choices
     *
     * @param string $callback function name
     * @param array $options the list of options populated so far (might be not complete)
     * @return array
     */
    public function populateOptionCallback($callback, $options = [])
    {
        if (! method_exists($this->getGenerator()->getInstance(), $callback) && ! method_exists($this, $callback)) {
            throw new \Exception($callback . ' is not callable');
        }
        if (method_exists($this->getGenerator()->getInstance(), $callback)) {
            $result = $this->getGenerator()->getInstance()->{$callback}($options);
        } else {
            $result = $this->{$callback}($options);
        }
        if (!is_array($result)) {
            throw new \Exception($callback . ' must return an array');
        }

        return $result;
    }

    /**
     * Get the list of template engines available
     *
     * @return array
     */
    public function getTemplateEnginesList()
    {
        ee()->load->library('api');
        ee()->legacy_api->instantiate('template_structure');

        return ee()->api_template_structure->get_template_engines();
    }

    /**
     * If this is MSM install, get list of sites
     * If just one site is available, set it's site ID on factory
     *
     * @return array
     */
    public function getSitesList()
    {
        $sites = ee('Model')->get('Site')
            ->order('site_label', 'asc')
            ->all()
            ->getDictionary('site_id', 'site_label');
        if (! bool_config_item('multiple_sites_enabled') || count($sites) == 1) {
            $this->site_id = array_key_first($sites);
        }

        return $sites;
    }

    /**
     * Get the list of templates provided by current generator
     *
     * @return array
     */
    public function getTemplatesList()
    {
        $templates = [
            'all' => 'All templates'
        ];
        foreach ($this->getGenerator()->getInstance()->getTemplates() as $template => $templateInfo) {
            $templates[$template] = $templateInfo;
        }

        return $templates;
    }

    /**
     * Get the themes list for selection
     *
     * @return array
     */
    public function getThemesList()
    {
        $themes = [
            'none' => 'No Theme'
        ];
        if (is_null($this->themes)) {
            $this->registerThemes();
        }
        $selectedTemplateEngine = empty($this->templateEngine) ? 'native' : $this->templateEngine;
        foreach ($this->themes as $theme => $themeInfo) {
            $themes[$theme] = $themeInfo['name'] . ((count($themeInfo['template_engines']) > 1) ? ' (' . implode(', ', $themeInfo['template_engines']) . ')' : '');
        }

        return $themes;
    }

    /**
     * Validate theme
     *
     * @return void
     */
    public function validateTheme($key, $value, $params, $rule)
    {
        if (empty($value) || $value == 'none') {
            //no further validation needed
            return true;
        }
        if (is_null($this->themes)) {
            $this->registerThemes();
        }
        // is the theme registered?
        if (!isset($this->themes[$value])) {
            return lang('invalid_theme');
        }
        // does the theme support the template engine?
        $selectedTemplateEngine = empty($this->templateEngine) ? 'native' : $this->templateEngine;
        if (!in_array($selectedTemplateEngine, $this->themes[$value]['template_engines'])) {
            return sprintf(lang('theme_does_not_support_template_engine'), $selectedTemplateEngine);
        }

        return true;
    }

    /**
     * Validate template engine
     *
     * @return void
     */
    public function validateTemplateEngine($key, $value, $params, $rule)
    {
        if (empty($value) || $value == 'native') {
            return true;
        }
        $templateEngines = $this->getTemplateEnginesList();

        if (!isset($templateEngines[$value])) {
            return lang('invalid_template_engine');
        }

        return true;
    }

    /**
     * Validates the template name checking for reserved names.
     * Also checks if it's not already taken
     */
    public function validateTemplateGroup($key, $value, $params, $rule)
    {
        $model = ee('Model')->make('TemplateGroup', ['site_id' => $this->site_id]);

        $valid = $model->validateTemplateGroupName('group_name', $value, $params, $rule);

        if ($valid !== true) {
            return $valid;
        }

        return $model->validateUnique('group_name', $value, $params, $rule);
    }

    /**
     * Get the list of themes provided by add-ons
     *
     * @return array
     */
    public function registerThemes()
    {
        $this->themes = [];
        $providers = ee('App')->getProviders();
        foreach ($providers as $providerKey => $provider) {
            if ($provider->get('templateThemes')) {
                foreach ($provider->get('templateThemes') as $theme => $themeData) {
                    $themeData['prefix'] = $provider->getPrefix();
                    $themeData['folder'] = $theme;

                    // Only show template_engines that are supported in this site
                    $themeData['template_engines'] = array_intersect(
                        array_merge(array_keys($this->getTemplateEnginesList()), ['native']),
                        $themeData['template_engines'] ?? ['native']
                    ) ?: ['native'];

                    $key = $theme;
                    if (isset($this->themes[$key])) {
                        $key = $provider->getPrefix() . ':' . $theme; //ensure uniqueness
                    }
                    $this->themes[$key] = $themeData;
                }
            }
        }

        return $this->themes;
    }

    /**
     * Get Validation service instance
     *
     * @return ExpressionEngine\Service\Validation\Validator
     */
    public function getValidator()
    {
        $rules = $this->getValidationRules();
        $validator = ee('Validation')->make($rules);
        // because the validation rules can be set up both on factory and generator
        // we will need to scan through the code, and if there is a match in names
        // define our custom rule
        $allRuleNames = [];
        foreach ($rules as $rule) {
            $ruleNames = explode('|', $rule);
            foreach ($ruleNames as $ruleName) {
                $bracketPos = strpos($ruleName, '[');
                $allRuleNames[] = $bracketPos !== false ? substr($ruleName, 0, $bracketPos) : $ruleName;
            }
        }
        // is the rule set in generator?
        $methods = array_intersect($allRuleNames, get_class_methods($this->getGenerator()->getInstance()));
        if (!empty($methods)) {
            foreach ($methods as $method) {
                $validator->defineRule($method, [$this->getGenerator()->getInstance(), $method]);
            }
        }
        // is the rule set in factory?
        $methods = array_intersect($allRuleNames, get_class_methods($this));
        if (!empty($methods)) {
            foreach ($methods as $method) {
                $validator->defineRule($method, [$this, $method]);
            }
        }

        return $validator;
    }

    /**
     * Generate the template out of stubs
     *
     * @param string $template template name
     * @param array $options
     * @return void
     */
    public function generate($template, $options = [])
    {
        // we'll be mimicing the View service here

        // get the variables that we'll replace in the template
        // by default, these are just options - the generators will add their variables
        $vars = array_merge($options, $this->getGenerator()->getInstance()->getVariables());

        // find the stub that we should use
        // we expect the stubs to be contained in folder that's named after Generator's class name with first latter lowercase
        $stub = ee('View')->makeStub($this->getGenerator()->prefix . ':' . lcfirst($this->getGenerator()->className) . ':' . $template);
        // parse the stub, including the embeds
        $templateData = $stub->render($vars);

        return $templateData;
    }

    /**
     * Gets the list of roles that can access the templates
     *
     * @return Model\Collection
     */
    protected function getPermittedRoles()
    {
        if (!is_null($this->permittedRoles)) {
            return $this->permittedRoles;
        }
        $this->permittedRoles = ee('Model')->get('Role', ee('Permission')->rolesThatCan('access_design'))
            ->filter('role_id', 'NOT IN', array(1, 2, 4))
            ->order('name', 'asc')
            ->all();

        return $this->permittedRoles;
    }

    /**
     * Get the stubs and generators for all installed fieldtypes
     *
     * @return array
     */
    public function getFieldtypeStubsAndGenerators()
    {
        if (is_null($this->ftStubsAndGenerators)) {
            $this->ftStubsAndGenerators = [];
            ee()->legacy_api->instantiate('channel_fields');
            foreach (ee('Addon')->installed() as $addon) {
                if ($addon->hasFieldtype()) {
                    $provider = $addon->getProvider();
                    foreach ($addon->get('fieldtypes', array($provider->getPrefix() => [])) as $fieldtype => $metadata) {
                        $stub = 'field';
                        $generator = null;
                        $ftClassName = ee()->api_channel_fields->include_handler($fieldtype);
                        $reflection = new \ReflectionClass($ftClassName);
                        $instance = $reflection->newInstanceWithoutConstructor();
                        if (isset($instance->stub)) {
                            // grab the stub out of fieldtype property
                            $stub = $instance->stub;
                        }
                        // is a generator set for this field?
                        if (isset($metadata['templateGenerator'])) {
                            $fqcn = trim($provider->getNamespace(), '\\') . '\\TemplateGenerators\\' . $metadata['templateGenerator'];
                            if (class_exists($fqcn)) {
                                $generator = $fqcn;
                            }
                        }
                        $this->ftStubsAndGenerators[$fieldtype] = [
                            'stub' => $provider->getPrefix() . ':' . $stub,
                            'docs_url' => $provider->get('docs_url') ?? $provider->get('author_url'),
                            'generator' => $generator,
                            'is_tag_pair' => (isset($instance->has_array_data) && $instance->has_array_data === true)
                        ];
                    }
                }
            }
        }

        return $this->ftStubsAndGenerators;
    }

    /**
     * Create template group
     *
     * @param string $group_name
     * @return TemplateGroup
     */
    public function createTemplateGroup(string $group_name)
    {
        $roles = $this->getPermittedRoles();

        $group = ee('Model')->make('TemplateGroup');
        $group->group_name = $group_name;
        $group->site_id = $this->site_id;
        $group->Roles = $roles;
        $validationResult = $group->validate();
        if ($validationResult->isNotValid()) {
            // can't use renderErrors() directly here, because we need line view
            $errors = [];
            foreach ($validationResult->getFailed() as $field => $failed) {
                $fieldErrors = $validationResult->getErrors($field);
                array_walk($fieldErrors, function ($message, $key, $field) use (&$fieldErrors) {
                    $fieldErrors[$key] = $field . ': ' . $message;
                }, $field);
                $errors[$field] = implode("\n", $fieldErrors);
            }

            throw new \Exception(implode("\n", $errors));
        }
        $group->save();

        $perms = [
            'can_create_templates_template_group_id_' . $group->getId(),
            'can_edit_templates_template_group_id_' . $group->getId(),
            'can_delete_templates_template_group_id_' . $group->getId(),
            'can_manage_settings_template_group_id_' . $group->getId()
        ];

        foreach ($roles as $role) {
            $role_id = $role->getId();
            foreach ($perms as $perm) {
                ee('Model')->make('Permission', [
                    'role_id' => $role_id,
                    'site_id' => $this->site_id,
                    'permission' => $perm
                ])
                    ->save();
            }
        }

        return $group;
    }

    /**
     * Create template in group
     *
     * @param TemplateGroup $group
     * @param string $name
     * @param array $data
     * @return Template
     */
    public function createTemplate(TemplateGroup $group, string $name, array $data = [])
    {
        $ext = '.html';
        if (!empty($this->templateEngine) && $this->templateEngine != 'native') {
            $ext = '.' . $this->templateEngine;
        }
        $fileName = $name . $ext;
        ee()->load->library('api');
        ee()->legacy_api->instantiate('template_structure');
        $info = ee()->api_template_structure->get_template_file_info($fileName);
        $template = ee('Model')->make('Template');
        $template->site_id = $this->site_id;
        $template->template_name = $name;
        $template->template_data = $data['template_data'];
        $template->template_type = $info['type'];
        $template->template_notes = $data['template_notes'] ?? '';
        $template->template_engine = $this->templateEngine == 'native' ? null : $this->templateEngine;
        $template->TemplateGroup = $group;
        $template->Roles = ee('Model')->get('Role')->all(true);

        $validationResult = $template->validate();
        if ($validationResult->isNotValid()) {
            // can't use renderErrors() directly here, because we need line view
            $errors = [];
            foreach ($validationResult->getFailed() as $field => $failed) {
                $fieldErrors = $validationResult->getErrors($field);
                array_walk($fieldErrors, function ($message, $key, $field) use (&$fieldErrors) {
                    $fieldErrors[$key] = $field . ': ' . $message;
                }, $field);
                $errors[$field] = implode("\n", $fieldErrors);
            }

            throw new \Exception(implode("\n", $errors));
        }

        $template->save();

        return $template;
    }
}
