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
     * The list of extra themes that are available
     *
     * @var array
     */
    protected $themes;

    /**
     * List of all generators available in the system
     *
     * @var array
     */
    protected $generators;

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

    public function __construct()
    {
        ee()->lang->load('cp');
        ee()->lang->load('design');
    }


    public function hasTheme($theme)
    {
        if (is_null($this->themes)) {
            $this->registerThemes();
        }

        return isset($this->themes[$theme]);
    }

    public function getTheme($theme)
    {
        return ($this->hasTheme($theme)) ? $this->themes[$theme] : null;
    }

    public function getThemeProvider($theme)
    {
        $theme = $this->getTheme($theme);

        return ($theme) ? ee('App')->get($theme['prefix']) : null;
    }

    /**
     * Set the Generator that we want to use
     * If it does not exist, or is not valid, we'll trow an Exception
     *
     * @param mixed $generatorKey prefix:className key from registeredGenerators
     * @return RegisteredGenerator
     */
    public function make($generatorKey)
    {
        if (empty($generatorKey)) {
            throw new \Exception('Template Generator is required');
        }
        if (is_null($this->generators)) {
            $this->registerAllTemplateGenerators();
        }
        if (!isset($this->generators[$generatorKey])) {
            throw new \Exception('Template Generator could not be found');
        }

        $generator = $this->generators[$generatorKey];
        ee()->lang->loadfile($generator->getPrefix(), '', false);

        return $generator;
    }

    public function hasFieldtype($fieldtype)
    {
        $generators = $this->getFieldtypeStubsAndGenerators();

        return isset($generators[$fieldtype]);
    }

    public function getFieldtype($fieldtype)
    {
        $generators = $this->getFieldtypeStubsAndGenerators();

        return $this->hasFieldtype($fieldtype) ? $generators[$fieldtype] : null;
    }

    public function makeField($fieldtype, $field, $settings = [])
    {
        $fieldtype = $this->getFieldtype($fieldtype);

        if (is_null($fieldtype) || empty($fieldtype['generator'])) {
            return;
        }

        $instance = new $fieldtype['generator']($field, $settings);

        return ($instance instanceof AbstractFieldTemplateGenerator) ? $instance : null;
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
        $this->getInstalledProviders();
        if (is_null($this->generators)) {
            $providers = $this->getInstalledProviders(); // ee('App')->getProviders();
            foreach ($providers as $provider) {
                if (method_exists($provider, 'registerTemplateGenerators')) {
                    $provider->registerTemplateGenerators();
                }
            }
        }

        return $this->generators;
    }

    protected function getInstalledProviders()
    {
        $providers = ee('App')->getProviders();
        $installed = array_map(function($name) {
            return strtolower($name);
        }, ee('Model')->get('Module')->all(true)->pluck('module_name'));

        return array_intersect_key($providers, array_flip($installed));
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
            return $this->generators;
        }

        // Build the Generator's class name and see if it exists
        $class = $this->getProviderGeneratorClass($provider, ucfirst($className));
        if (! class_exists($class)) {
            return $this->generators;
        }

        // Does it extend the base template generator?
        $instance = new $class;
        if (!$instance instanceof AbstractTemplateGenerator) {
            return $this->generators;
        }

        $key = $provider->getPrefix() . ':' . lcfirst($className);
        $this->generators[$key] = $instance->setProvider($provider);
        ee()->lang->loadfile($provider->getPrefix(), '', false);

        return $this->generators;
    }

    /**
     * Get the list of themes provided by add-ons
     *
     * @return array
     */
    public function registerThemes()
    {
        $this->themes = [];
        ee()->load->library('api');
        ee()->legacy_api->instantiate('template_structure');
        $providers = $this->getInstalledProviders(); // ee('App')->getProviders();
        $templateEngines = ee()->api_template_structure->get_template_engines();
        foreach ($providers as $providerKey => $provider) {
            if ($provider->get('templateThemes')) {
                foreach ($provider->get('templateThemes') as $theme => $themeData) {
                    $themeData['prefix'] = $provider->getPrefix();
                    $themeData['folder'] = $theme;

                    // Only show template_engines that are supported in this site
                    $themeData['template_engines'] = array_intersect(
                        array_merge(array_keys($templateEngines), ['native']),
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
                            $class = $this->getProviderGeneratorClass($provider, $metadata['templateGenerator']);
                            if (class_exists($class)) {
                                $generator = $class;
                            }
                        }
                        $this->ftStubsAndGenerators[$fieldtype] = [
                            'stub' => $provider->getPrefix() . ':' . $stub,
                            'docs_url' => $metadata['docs_url'] ?? ($provider->get('docs_url') ?? $provider->get('author_url')),
                            'generator' => $generator,
                            'is_tag_pair' => (isset($instance->has_array_data) && $instance->has_array_data === true)
                        ];
                    }
                }
            }
        }

        return $this->ftStubsAndGenerators;
    }

    protected function getProviderGeneratorClass(Provider $provider, $class)
    {
        return trim($provider->getNamespace(), '\\') . '\\TemplateGenerators\\' . $class;
    }

    /**
     * Create template group
     *
     * @param string $group_name
     * @return TemplateGroup
     */
    public function createTemplateGroup(string $group_name, $site_id = 1)
    {
        $roles = $this->getPermittedRoles();

        $group = ee('Model')->make('TemplateGroup');
        $group->group_name = $group_name;
        $group->site_id = $site_id;
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
                    'site_id' => $site_id,
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
    public function createTemplate(TemplateGroup $group, string $name, array $data = [], $site_id = 1)
    {
        $template = ee('Model')->make('Template');
        $template->site_id = $site_id;
        $template->template_name = $name;
        $template->template_data = $data['template_data'];
        $template->template_type = $data['template_type'];
        $template->template_notes = $data['template_notes'] ?? '';
        $template->template_engine = ($data['template_engine'] == 'native') ? null : $data['template_engine'];
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
