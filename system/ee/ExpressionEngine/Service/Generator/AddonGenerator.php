<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Generator;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Service\Generator\Enums\FieldtypeCompatibility;
use ExpressionEngine\Service\Generator\Enums\Hooks;

class AddonGenerator
{
    public $name;
    public $data;
    public $filesystem;
    public $slug;
    public $slug_uc;
    public $namespace;
    public $description;
    public $version;
    public $author;
    public $author_url;
    public $has_settings;
    public $has_cp_backend;
    public $has_publish_fields;
    public $type;
    public $hooks;
    public $compatibility;

    protected $package;
    protected $stubPath;
    protected $tempPath;
    protected $generatorPath;
    protected $addonPath;

    public $fileName;

    public function __construct(Filesystem $filesystem, array $data)
    {
        ee()->load->helper('string');

        $this->filesystem  = $filesystem;
        $this->type = $data['type'];
        $this->name = $data['name'];
        $this->slug = $this->slug($data['name']);
        $this->slug_uc = ucfirst($this->slug);

        $this->init();

        // Catch all, especially for advanced settings
        $this->data = $data;

        $this->namespace = $this->studly($data['author']) . '\\' . $this->studly($data['name']);
        $this->description = $data['description'];
        $this->version = $data['version'];
        $this->author = $data['author'];
        $this->author_url = $data['author_url'];
        $this->has_settings = get_bool_from_string($data['has_settings']);
        $this->has_cp_backend = $data['has_settings'] ? 'y' : 'n';
        $this->has_publish_fields = 'n';
        $this->hooks = isset($data['hooks']) ? $data['hooks'] : null;
        $this->services = isset($data['services']) ? $data['services'] : null;
        $this->compatibility = isset($data['compatibility']) ? $data['compatibility'] : null;
        $this->models = isset($data['models']) ? $data['models'] : null;

        // Make sure we've got an array of hooks
        if (!is_array($this->hooks) && !is_null($this->hooks)) {
            $this->hooks = array_unique(explode(',', $this->hooks));
        }
        // Make sure we've got an array of services
        if (!is_array($this->services) && !is_null($this->services)) {
            $this->services = array_unique(explode(',', $this->services));
        }
    }

    private function init()
    {
        $this->generatorPath = SYSPATH . 'ee/ExpressionEngine/Service/Generator';
        $this->addonPath = SYSPATH . 'user/addons/' . $this->slug . '/';

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs' . '/';

        // Create temp directory
        $tempDir = random_string();

        if (! $this->filesystem->isDir($this->addonPath)) {
            $this->filesystem->mkDir($this->addonPath);
        }
    }

    public function build()
    {
        $this->buildAddonSetup();

        // Now we do the type work
        if ($this->type == 'plugin') {
            $this->buildPlugin();
        }

        if ($this->type == 'module') {
            $this->buildModule();
        }

        if ($this->type == 'extension' || $this->hooks) {
            $this->buildExtension();
        }

        if ($this->type == 'fieldtype') {
            $this->buildFieldtype();
        }

        $this->buildModels();

        return true;
    }

    protected function buildFieldtype()
    {
        $stub = $this->filesystem->read($this->stub('ft.slug.php'));
        $stub = $this->write('slug_uc', $this->slug_uc, $stub);
        $stub = $this->write('version', $this->version, $stub);
        $stub = $this->write('name', $this->name, $stub);
        $this->putFile('ft.' . $this->slug . '.php', $stub);
    }

    protected function buildExtension()
    {
        if (!is_array($this->hooks)) {
            throw new \Exception("Hooks are required to generate extension", 1);
        }

        $stub = $this->filesystem->read($this->stub('ext.slug.php'));
        $stub = $this->write('slug_uc', $this->slug_uc, $stub);
        $stub = $this->write('version', $this->version, $stub);

        $hook_array = '';
        $hook_method = '';
        $extension_settings = '';

        foreach ($this->hooks as $hook) {
            $hookData = Hooks::getByKey(trim(strtoupper($hook)));

            // If we didnt get a real hook, set up a default
            if ($hookData === false) {
                $hookData = [
                    'name' => $hook,
                    'params' => '',
                    'library' => ''
                ];
            }

            $hookArrayStub = $this->filesystem->read($this->stub('hook_array.php'));
            $hookArrayStub = $this->write('hook_name', $hook, $hookArrayStub);
            $hook_array .= "{$hookArrayStub}\n";

            $hookMethodStub = $this->filesystem->read($this->stub('hook_method.php'));
            $hookMethodStub = $this->write('hook_name', $hook, $hookMethodStub);
            $hookMethodStub = $this->write('hook_methods', $hookData['params'], $hookMethodStub);
            $hook_method .= "{$hookMethodStub}\n";
        }

        if ($this->has_settings) {
            $extension_settings = $this->filesystem->read($this->stub('extension_settings.php'));
        }

        $stub = $this->write('extension_settings', $extension_settings, $stub);
        $stub = $this->write('hook_array', $hook_array, $stub);
        $stub = $this->write('hook_methods', $hook_method, $stub);

        $this->putFile('ext.' . $this->slug . '.php', $stub);
        $this->createLangFile();
    }

    protected function buildModule()
    {
        // Create upd file
        $stub = $this->filesystem->read($this->stub('upd.slug.php'));
        $stub = $this->write('slug_uc', $this->slug_uc, $stub);
        $stub = $this->write('version', $this->version, $stub);
        $stub = $this->write('has_cp_backend', $this->has_cp_backend, $stub);
        $stub = $this->write('has_publish_fields', $this->has_publish_fields, $stub);

        if ($this->hooks) {
            $conditionalHooks = '';

            $hookInstall = $this->filesystem->read($this->stub('hook_install.php'));

            foreach ($this->hooks as $hook) {
                $hookData = Hooks::getByKey(strtoupper($hook));

                $hookArrayStub = $this->filesystem->read($this->stub('hook_array.php'));
                $hookArrayStub = $this->write('hook_name', $hook, $hookArrayStub);
                $conditionalHooks .= "{$hookArrayStub}\n";
            }

            $hookInstall = $this->write('hook_array', $conditionalHooks, $hookInstall);

            $stub = $this->write('conditional_hooks', $hookInstall, $stub);

            $hooksUninstall = $this->filesystem->read($this->stub('hook_uninstall.php'));
            $hooksUninstall = $this->write('slug_uc', $this->slug_uc, $hooksUninstall);

            $stub = $this->write('conditional_hooks_uninstall', $hooksUninstall, $stub);

            $this->buildExtension();
        } else {
            $stub = $this->erase('{{conditional_hooks}}', $stub);
            $stub = $this->erase('{{conditional_hooks_uninstall}}', $stub);
        }

        $this->putFile('upd.' . $this->slug . '.php', $stub);

        // Create module file
        $stub = $this->filesystem->read($this->stub('mod.slug.php'));
        $stub = $this->write('slug_uc', $this->slug_uc, $stub);
        $this->putFile('mod.' . $this->slug . '.php', $stub);

        // Create control panel file
        $stub = $this->filesystem->read($this->stub('mcp.slug.php'));
        $stub = $this->write('slug_uc', $this->slug_uc, $stub);
        $stub = $this->write('slug', $this->slug, $stub);
        $this->putFile('mcp.' . $this->slug . '.php', $stub);

        $this->createLangFile();
    }

    protected function buildPlugin()
    {
        $stub = $this->filesystem->read($this->stub('pi.slug.php'));

        $stub = $this->write('slug_uc', $this->slug_uc, $stub);

        $this->putFile('pi.' . $this->slug . '.php', $stub);
    }

    private function buildAddonSetup()
    {
        $stub = $this->filesystem->read($this->stub('addon.setup.php'));

        $stub = $this->write('author', $this->author, $stub);
        $stub = $this->write('author_url', $this->author_url, $stub);
        $stub = $this->write('name', $this->name, $stub);
        $stub = $this->write('description', $this->description, $stub);
        $stub = $this->write('version', $this->version, $stub);
        $stub = $this->write('namespace', $this->namespace, $stub);
        $stub = $this->write('settings_exist', $this->has_settings ? 'true' : 'false', $stub);

        if ($this->type == 'fieldtype') {
            $ftSetup = $this->filesystem->read($this->stub('fieldtype_setup.php'));
            $ftSetup = $this->write('fieldtype_slug', $this->slug, $ftSetup);
            $ftSetup = $this->write('fieldtype_name', $this->name, $ftSetup);
            $ftSetup = $this->write('fieldtype_compatibility', $this->compatibility, $ftSetup);
            $stub = $this->write('fieldtypes', $ftSetup, $stub);
        } else {
            $stub = $this->clearLine("    'fieldtypes'        => [{{fieldtypes}}],", $stub);
        }

        // Advanced
        // Typography
        if (array_key_exists('typography', $this->data) && ($typography = $this->data['typography'])) {
            $stub = $this->write('plugin_typography', 'true', $stub);
        } else {
            $stub = $this->clearLine("    'plugin.typography' => {{plugin_typography}},", $stub);
        }

        // Services
        if (!is_null($this->services)) {
            $servicesWriteData = '';

            $this->filesystem->mkDir($this->addonPath . 'Services');

            foreach ($this->services as $service) {
                if (!$service || $service == '') {
                    continue;
                }

                $servicesStub = $this->filesystem->read($this->stub('addon_service.php'));
                $servicesStub = $this->write('service_name', $this->studly($service), $servicesStub);

                $servicesWriteData .= "\n\t\t" . $servicesStub . "\n";

                $serviceStub = $this->filesystem->read($this->stub('service.php'));
                $serviceStub = $this->write('namespace', $this->namespace, $serviceStub);
                $serviceStub = $this->write('class', $this->studly($service), $serviceStub);

                $this->putFile($this->studly($service) . '.php', $serviceStub, '/Services');
            }

            $stub = $this->write('services', $servicesWriteData . "\t", $stub);
        } else {
            $stub = $this->clearLine("    'services'          => [{{services}}],", $stub);
        }

        // Consents
        if (array_key_exists('consents', $this->data) && ($consents = $this->data['consents'])) {
            $consentsWriteData = '';

            foreach (explode(',', $consents) as $consent) {
                if (!$consent || $consent == '') {
                    continue;
                }

                $consentsStub = $this->filesystem->read($this->stub('addon_consent.php'));
                $consentsStub = $this->write('consent_name', $this->studly($consent), $consentsStub);
                $consentsStub = $this->write('consent_slug', ee('Format')->make('Text', $consent)->urlSlug()->compile(), $consentsStub);

                $consentsWriteData .= "\n" . $consentsStub . "\n\t";
            }

            $stub = $this->write('consents', $consentsWriteData, $stub);
        } else {
            $stub = $this->clearLine("    'consent.requests'  => [{{consents}}],", $stub);
        }

        // Cookies
        if (array_key_exists('cookies', $this->data) && ($cookies = $this->data['cookies'])) {
            $cookiesWriteData = '';

            $cookieData = [];

            foreach (explode(',', $cookies) as $cookie) {
                if (! isset($cookie['value']) || $cookie['value'] == '') {
                    continue;
                }

                if (! isset($cookieData[$cookie['type']]) || ! is_array($cookieData[$cookie['type']])) {
                    $cookieData[$cookie['type']] = [];
                }

                $cookieData[$cookie['type']][] = $cookie['value'];
            }

            foreach (explode(':', $cookieData) as $cookieType => $cookieValues) {
                $cookiesStub = $this->filesystem->read($this->stub('cookies.php'));
                $cookiesStub = $this->write('cookies_type', $cookieType, $cookiesStub);

                $valueToWrite = "'" . implode("',\n\t'", $cookieValues) . "',";
                $cookiesStub = $this->write('cookies_value', $valueToWrite, $cookiesStub);

                $cookiesWriteData .= $cookiesStub . "\n";
            }

            $stub = $this->write('cookies', $cookiesWriteData, $stub);
        } else {
            $stub = $this->clearLine("{{cookies}}", $stub);
        }

        $this->putFile('addon.setup.php', $stub);
    }

    private function buildModels()
    {
        // Build all Models
        if (array_key_exists('models', $this->data) && ($models = $this->data['models'])) {
            foreach ($models as $model) {
                $model_data['name'] = $model;
                $model_data['addon'] = $this->slug;
                $modelGenerator = ee('ModelGenerator', $model_data);
                $modelGenerator->build();
            }
        }
    }

    private function createComposerJson()
    {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'version' => $this->version,
            'keywords' => [
                'ExpressionEngine',
            ],
            'support' => [
                'docs' => $this->author_url
            ],
            'authors' => [
                [
                    'name' => $this->author,
                    'homepage' => $this->author_url,
                ]
            ]
        ];

        $this->putFile('composer.json', json_encode($data, JSON_PRETTY_PRINT));
    }

    private function createLangFile()
    {
        // Create lang file
        $this->filesystem->mkDir($this->addonPath . 'language');
        $this->filesystem->mkDir($this->addonPath . 'language/english');
        $stub = $this->filesystem->read($this->stub('slug_lang.php'));
        $stub = $this->write('name', $this->name, $stub);
        $stub = $this->write('description', $this->description, $stub);
        $stub = $this->write('slug', $this->slug, $stub);
        $this->putFile($this->slug . '_lang.php', $stub, '/language/english');
    }

    private function stub($file)
    {
        return $this->stubPath . $file;
    }

    private function write($key, $value, $file)
    {
        return str_replace('{{' . $key . '}}', $value, $file);
    }

    private function putFile($name, $contents, $path = null)
    {
        if ($path) {
            $path = trim($path, '/') . '/';
        } else {
            $path = '';
        }

        if (!$this->filesystem->exists($this->addonPath . $path . $name)) {
            $this->filesystem->write($this->addonPath . $path . $name, $contents);
        }
    }

    private function erase($string, $contents)
    {
        return str_replace($string, '', $contents);
    }

    private function clearLine($string, $contents)
    {
        return preg_replace("/" . preg_quote($string) . "\R/", '', $contents);
    }

    public function slug($word)
    {
        $word = strtolower($word);

        return str_replace(['-', ' ', '.'], '_', $word);
    }

    public function studly($word)
    {
        $word = mb_convert_case($word, MB_CASE_TITLE);

        return  str_replace(['-', '_', ' ', '.'], '', $word);
    }
}
