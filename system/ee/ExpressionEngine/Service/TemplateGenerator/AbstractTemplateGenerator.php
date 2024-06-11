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

use ExpressionEngine\Core\Provider;

abstract class AbstractTemplateGenerator implements TemplateGeneratorInterface
{

    /**
     * Template engine to use
     *
     * @var string
     */
    protected $templateEngine;

    protected $site_id = 1;

    /**
     * Generator name to be displayed in the UI
     *
     * @var string
     */
    protected $name;

    /**
     * The list of templates that this generator can create
     * We expect the array key to be the template name and the value to be the template description
     *
     * @var array
     */
    protected $templates = [];

    /**
     * Custom options supported by this generator
     *
     * @var array
     */
    protected $options = [];

    /**
     * Validation rules for the options passed to this generator
     *
     * @var array
     */
    protected $_validation_rules = [];

    protected $provider;

    /**
     * Return the name of the generator
     * Allows using lang keys for the name
     *
     * @return string
     */
    public function getName(): string
    {
        return lang($this->name);
    }

    /**
     * Return list of templates provided by this generator
     *
     * @return array
     */
    public function getTemplates(): array
    {
        return array_combine(array_keys($this->templates), array_map(function($data) {
            $data = (is_string($data)) ? ['name' => $data] : $data;
            return array_merge(['type' => 'webpage'], $data);
        }, $this->templates));
    }


    /**
     * Return validation rules for the options passed to this generator
     *
     * @return array
     */
    public function validationRules(): array
    {
        return $this->_validation_rules;
    }

    public function setProvider(Provider $provider)
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function getPrefix()
    {
        return $this->getProvider()->getPrefix();
    }

    public function generate($options, $save = true)
    {
        if(isset($options['site_id'])) {
            $this->site_id = (int) $options['site_id'];
        }

        if (isset($options['template_engine'])) {
            $this->templateEngine = (string) $options['template_engine'];
        }

        $templates = $this->getTemplates();

        if (isset($options['templates']) && !empty($options['templates']) && current($options['templates']) !== 'all') {
            $templates = array_filter($templates, function ($key) use ($options) {
                return in_array($key, $options['templates']);
            }, ARRAY_FILTER_USE_KEY);
        }

        if (empty($templates)) {
            throw new \Exception(lang('generate_templates_no_templates'));
        }

        // we'll start with index templates
        if (isset($templates['index'])) {
            $indexTmpl = $templates['index'];
            unset($templates['index']);
            $templates = array_merge(['index' => $indexTmpl], $templates); // we want index to be created first
        }

        $group = ($save) ? ee('TemplateGenerator')->createTemplateGroup($options['template_group'], $this->site_id) : $options['template_group'];

        foreach ($templates as $templateName => $templateData) {
            $templateInfo = [
                'template_engine' => $options['template_engine'] ?? null,
                'template_data' => $this->render($templateName, $templateData['type'], $options),
                'template_type' => $templateData['type'],
                'template_notes' => $templateData['description'] ?? $templateData['name']
            ];

            if($save) {
                ee('TemplateGenerator')->createTemplate($group, $templateName, $templateInfo, $this->site_id);
            }

            $templates[$templateName] = array_merge($templateData, $templateInfo);
        }

        return ['group' => $group, 'templates' => $templates];
    }

    protected function render($template, $type, $options = [])
    {
        // we'll be mimicing the View service here

        // get the variables that we'll replace in the template
        // by default, these are just options - the generators will add their variables
        $vars = array_merge($options, $this->prepareVariables($options));

        $stub = $this->makeTemplateStub($template);

        $stub->setTemplateType($type);

        if(!empty($options['theme'] ?? null)) {
            $stub->setTheme($options['theme']);
        }

        if (!empty($options['template_engine']) && $options['template_engine'] != 'native') {
            $stub->setTemplateEngine($options['template_engine']);
        }

        // parse the stub, including the embeds
        return $stub->render($vars);
    }

    /**
     * This will make and return a Stub object
     * Unlike Views, Stubs are passed as string with 3 parts separated by colons
     *
     * @param string $name The path to the stub file, prefixed with add-on name and generator folder
     * @return object A ExpressionEngine\Service\View\Stub object
     */
    public function makeTemplateStub($template)
    {
        // find the stub that we should use
        // we expect the stubs to be contained in folder that's named after Generator's class name with first latter lowercase
        $className = (new \ReflectionClass($this))->getShortName();
        $stub = new \ExpressionEngine\Service\View\Stub($template, $this->provider);
        $stub->generatorFolder = lcfirst($className);

        return $stub;
    }

    public function prepareVariables($options): array {
        return $options;
    }

    /**
     * Return list of options provided by this generator
     *
     * @return array
     */
    public function options()
    {
        return $this->options;
    }

    public function getOptions(): array
    {
        $defaults = [
            'template_engine' => [
                'type' => 'select',
                'callback' => 'getTemplateEnginesList',
                'desc' => 'select_template_engine',
                'default' => ''
            ],
            /* 'theme' => [
                'type' => 'select',
                'callback' => 'getThemesList',
                'desc' => 'select_theme',
                'default' => 'none'
            ],*/
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

        return array_merge($defaults, $this->options());
    }

    /**
     * Return array of validation rules for factory and current generator
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        $defaults = [
            'site_id' => 'integer',
            'template_engine' => 'validateTemplateEngine',
            // 'theme' => 'required|validateTheme',
            'template_group' => 'required|alphaDashPeriodEmoji|validateTemplateGroup[site_id]'
        ];

        return array_merge($defaults, $this->validationRules());
    }

    protected function getValidator()
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
        $methods = array_intersect($allRuleNames, get_class_methods($this));

        if (!empty($methods)) {
            foreach ($methods as $method) {
                $validator->defineRule($method, [$this, $method]);
            }
        }

        return $validator;
    }

    public function validate($input)
    {
        return $this->getValidator()->validate($input);
    }

    public function validatePartial($input)
    {
        return $this->getValidator()->validatePartial($input);
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
        if (!method_exists($this, $callback) && !method_exists(ee('TemplateGenerator'), $callback)) {
            throw new \Exception($callback . ' is not callable');
        }

        $result = (method_exists($this, $callback)) ? $this->{$callback}($options) : ee('TemplateGenerator')->{$callback}($options);

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
        if (!bool_config_item('multiple_sites_enabled') || count($sites) == 1) {
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
        foreach ($this->getTemplates() as $template => $templateInfo) {
            $templates[$template] = $templateInfo['name'];
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
        return ee('TemplateGenerator')->getThemesList();
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

        // is the theme registered?
        if (!ee('TemplateGenerator')->hasTheme($value)) {
            return lang('invalid_theme');
        }

        // does the theme support the template engine?
        $theme = ee('TemplateGenerator')->getTheme($value);
        $selectedTemplateEngine = empty($this->templateEngine) ? 'native' : $this->templateEngine;
        if (!in_array($selectedTemplateEngine, $theme['template_engines'])) {
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

    public function makeField($fieldtype, $field, $settings = [])
    {
        $generator = ee('TemplateGenerator')->makeField($fieldtype, $field, $settings);

        return ($generator) ? $generator->setSiteId($this->site_id) : null;
    }
}
