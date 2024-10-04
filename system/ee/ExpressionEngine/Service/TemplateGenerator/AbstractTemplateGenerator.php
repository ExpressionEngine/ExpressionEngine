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
    protected $input;

    /**
     * Generator name to be displayed in the UI
     *
     * @var string
     */
    protected $name;

    /**
     * List of areas where this generator should be excluded. Possible values are 'CLI', 'CP'
     *
     * @var array
     */
    protected $excludeFrom = [];

    /**
     * The list of templates that this generator can create
     * We expect the array key to be the template name and the value to be the template description
     *
     * @var array
     */
    protected $templates = [];

    /**
     * A list of supporting templates that should be included during generation
     *
     *
     * @var array
     */
    protected $includes = [];

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

    public function __construct()
    {
        $this->input = new Input();
    }

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
        return array_combine(array_keys($this->templates), array_map(function ($data) {
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

    /**
     * Check if the generator is disabled for the current location
     */
    public function generatorDisabledForLocation($location)
    {
        return in_array($location, $this->excludeFrom);
    }

    /**
     * Get a list of includes for given a set of $templates
     * The following formats are supported for $this->includes =
     * [
     *      // Included for ANY template, and template type = 'webpage'
     *      '_layout',
     *      // Included when 'index' or 'single' are chosen, and template type = 'webpage'
     *      'embed' => ['templates' => ['index', 'single']],
     *      // Included for ANY Template, and template type = 'feed'
     *      'rssEmbed' => ['type' => 'feed'],
     *      // Included with the 'sitemap' template, and template type = 'xml'
     *      'xmlEmbed' => ['templates' => ['sitemap'], 'type' => 'xml']
     * ]
     */
    public function getIncludes($templates)
    {
        return array_reduce(array_keys($this->includes), function ($carry, $key) use ($templates) {
            $defaults = ['templates' => null, 'type' => 'webpage'];
            $value = $this->includes[$key];

            if(is_int($key)) {
                $carry[$value] = array_merge($defaults, ['name' => $value]);
            } else {
                $value = array_merge($defaults, $value);
                $value['templates'] = (is_string($value['templates'])) ? explode(',', $value['templates']) : $value['templates'];

                if(empty($value['templates']) || !empty(array_intersect_key($templates, array_flip($value['templates'])))) {
                    $carry[$key] = array_merge($value, ['name' => $key]);
                }
            }

            return $carry;
        }, []);
    }

    public function generate($input, $save = true)
    {
        $validationResult = ($save) ? $this->validate($input) : $this->validatePartial(array_filter($input));

        if (!$validationResult->isValid()) {
            // Flash previous input to the session if it's available
            if(ee()->has('session') && !empty($_POST)) {
                foreach($_POST as $key => $value) {
                    ee()->session->set_flashdata($key, $value);
                }
                ee()->session->_age_flashdata();
            }

            throw new Exceptions\ValidationException('Template Generator validation failed.', $validationResult);
        }

        $this->input = new Input($this->mergeDefaults($input));

        $templates = $this->getTemplates();

        if (!empty($this->input->get('templates', [])) && current($this->input->get('templates')) !== 'all') {
            $templates = array_filter($templates, function ($key) {
                return in_array($key, $this->input->get('templates'));
            }, ARRAY_FILTER_USE_KEY);
        }

        if (empty($templates)) {
            throw new \Exception(lang('generate_templates_no_templates'));
        }

        // Add any includes for the specified templates
        $templates = array_merge($templates, $this->getIncludes($templates));

        // we'll start with index templates
        if (isset($templates['index'])) {
            $indexTmpl = $templates['index'];
            unset($templates['index']);
            $templates = array_merge(['index' => $indexTmpl], $templates); // we want index to be created first
        }

        $site_id = (int) $this->input->get('site_id', 1);

        $group = ($save) ? ee('TemplateGenerator')->createTemplateGroup($this->input->get('template_group'), $site_id) : $this->input->get('template_group');

        foreach ($templates as $templateName => $templateData) {
            $rendered = $this->render($templateName, $templateData['type']);
            $templateInfo = [
                'template_engine' => $rendered['engine'],
                'template_data' => $rendered['data'],
                'template_type' => $templateData['type'],
                'template_notes' => $templateData['description'] ?? $templateData['name']
            ];

            if($save) {
                ee('TemplateGenerator')->createTemplate($group, $templateName, $templateInfo, $site_id);
            }

            $templates[$templateName] = array_merge($templateData, $templateInfo);
        }

        return ['group' => $group, 'templates' => $templates];
    }

    protected function render($template, $type)
    {
        $stub = $this->makeTemplateStub($template)->setTemplateType($type);

        if($this->input->get('theme')) {
            $stub->setTheme($this->input->get('theme'));
        }

        if ($this->input->get('template_engine', 'native') !== 'native') {
            $stub->setTemplateEngine($this->input->get('template_engine'));
        }

        // Parse the stub and all embeds.
        // Use the generator's input as well as any provided variables
        $data = $stub->render(array_merge($this->input->all(), $this->getVariables()));

        return [
            'engine' => $stub->getTemplateEngine(),
            'data' => $data
        ];
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

    abstract public function getVariables(): array;

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
        ee()->legacy_api->instantiate('template_structure');
        $defaults = [
            'template_engine' => [
                'type' => 'select',
                'choices' => 'getTemplateEnginesList',
                'desc' => 'select_template_engine',
                'default' => ee()->api_template_structure->get_default_template_engine(),
                'value' => ee()->api_template_structure->get_default_template_engine()
            ],
            /* 'theme' => [
                'type' => 'select',
                'choices' => 'getThemesList',
                'desc' => 'select_theme',
                'default' => 'none'
            ],*/
            'site_id' => [
                'type' => 'select',
                'choices' => 'getSitesList',
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
                'choices' => 'getTemplatesList',
                'desc' => 'select_templates_to_generate',
                'default' => 'all',
            ],
        ];

        return $this->prepareOptions(array_merge($defaults, $this->options()));
    }

    protected function prepareOptions($options)
    {
        return array_reduce(array_keys($options), function ($carry, $key) use ($options) {
            $value = $options[$key];
            if(isset($value['choices'])) {
                $classes = [null, $this, ee('TemplateGenerator')];

                foreach($classes as $class) {
                    $callable = !empty($class) ? [$class, $value['choices']] : $value['choices'];
                    if(is_callable($callable)) {
                        $value['choices'] = (is_array($callable)) ? call_user_func_array($callable, []) : $callable();
                    }
                }

                if(!is_array($value['choices'])) {
                    throw new \Exception('Option choices must return an array');
                }
            }

            $carry[$key] = $value;

            return $carry;
        }, []);
    }

    /**
     * Add any default values from the generator's options to the provided input
     *
     * @param array $input
     * @return array
     */
    protected function mergeDefaults($input)
    {
        $options = $this->getOptions();
        $defaults = array_reduce(array_keys($options), function($carry, $key) use ($options) {
            if(array_key_exists('default', $options[$key])) {
                $carry[$key] = $options[$key]['default'];
            }

            return $carry;
        }, []);

        return array_merge($defaults, $input);
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
        return ee('Model')->get('Site')
            ->order('site_label', 'asc')
            ->all()
            ->getDictionary('site_id', 'site_label');
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
        $selectedTemplateEngine = (string) $this->input->get('template_engine', 'native'); //empty($this->templateEngine) ? 'native' : $this->templateEngine;
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
        $model = ee('Model')->make('TemplateGroup', ['site_id' => (int) $this->input->get('site_id', 1)]);

        $valid = $model->validateTemplateGroupName('group_name', $value, $params, $rule);

        if ($valid !== true) {
            return $valid;
        }

        return $model->validateUnique('group_name', $value, $params, $rule);
    }

    public function makeField($fieldtype, $field, $settings = [])
    {
        $generator = ee('TemplateGenerator')->makeField($fieldtype, $field, $settings);

        return ($generator) ? $generator->setInput($this->input) : null;
    }
}
