<?php

namespace ExpressionEngine\Cli\Generator\Services;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Cli\Generator\Enums\FieldtypeCompatibility;
use ExpressionEngine\Cli\Generator\Enums\Hooks;

class AddonGeneratorService
{
    public $name;
    public $data;
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

    public function __construct(array $data)
    {
        ee()->load->helper('string');

        $this->type = $data['type'];
        $this->name = $data['name'];
        $this->slug = slug($data['name'], '_');
        $this->slug_uc = ucfirst($this->slug);

        $this->init();

        // Catch all, especially for advanced settings
        $this->data = $data;

        $this->namespace = studly($data['name']) . '\\\\';
        $this->description = $data['description'];
        $this->version = $data['version'];
        $this->author = $data['author'];
        $this->author_url = $data['author_url'];
        $this->has_settings = isset($data['has_settings']) ? $data['has_settings'] : false;
        $this->has_cp_backend = (isset($data['has_settings']) && $data['has_settings']) ? 'y' : 'n';
        $this->has_publish_fields = (isset($data['has_settings']) && $data['has_settings']) ? 'y' : 'n';
        $this->hooks = isset($data['hooks']) ? $data['hooks'] : null;
        $this->compatibility = isset($data['compatibility']) ? $data['compatibility'] : null;
    }

    private function init()
    {
        $this->generatorPath = SYSPATH . 'ee/ExpressionEngine/Cli/Generator';
        $this->addonPath = SYSPATH . 'user/addons/' . $this->slug . '/';
        $filesystem = new Filesystem();

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs' . '/';

        // Create temp directory
        $tempDir = random_string();

        if (! $filesystem->isDir($this->addonPath)) {
            $filesystem->mkDir($this->addonPath);
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

        return true;
    }

    protected function buildFieldtype()
    {
        $filesystem = new Filesystem();

        $stub = $filesystem->read($this->stub('ft.slug.php'));
        $stub = $this->write('slug_uc', $this->slug_uc, $stub);
        $stub = $this->write('version', $this->version, $stub);
        $stub = $this->write('name', $this->name, $stub);
        $this->putFile('ft.' . $this->slug . '.php', $stub);
    }

    protected function buildExtension()
    {
        $filesystem = new Filesystem();

        $stub = $filesystem->read($this->stub('ext.slug.php'));
        $stub = $this->write('slug_uc', $this->slug_uc, $stub);
        $stub = $this->write('version', $this->version, $stub);

        $hook_array = '';
        $hook_method = '';

        if (!$this->hooks) {
            $hook_array = '// Add your hooks here!';
        }

        foreach (array_unique(explode(',', $this->hooks)) as $hook) {
            $hookData = Hooks::getByKey(strtoupper($hook));

            $hookArrayStub = $filesystem->read($this->stub('hook_array.php'));
            $hookArrayStub = $this->write('hook_name', $hook, $hookArrayStub);
            $hook_array .= "{$hookArrayStub}\n";

            $hookMethodStub = $filesystem->read($this->stub('hook_method.php'));
            $hookMethodStub = $this->write('hook_name', $hook, $hookMethodStub);
            $hookMethodStub = $this->write('hook_methods', $hookData['params'], $hookMethodStub);
            $hook_method .= "{$hookMethodStub}\n";
        }

        $stub = $this->write('hook_array', $hook_array, $stub);
        $stub = $this->write('hook_methods', $hook_method, $stub);

        $this->putFile('ext.' . $this->slug . '.php', $stub);
    }

    protected function buildModule()
    {
        $filesystem = new Filesystem();

        // Create upd file
        $stub = $filesystem->read($this->stub('upd.slug.php'));
        $stub = $this->write('slug_uc', $this->slug_uc, $stub);
        $stub = $this->write('version', $this->version, $stub);
        $stub = $this->write('has_cp_backend', $this->has_cp_backend, $stub);
        $stub = $this->write('has_publish_fields', $this->has_publish_fields, $stub);

        if ($this->hooks) {
            $conditionalHooks = '';

            $hookInstall = $filesystem->read($this->stub('hook_install.php'));

            foreach (array_unique(explode(',', $this->hooks)) as $hook) {
                $hookData = Hooks::getByKey(strtoupper($hook));

                $hookArrayStub = $filesystem->read($this->stub('hook_array.php'));
                $hookArrayStub = $this->write('hook_name', $hook, $hookArrayStub);
                $conditionalHooks .= "{$hookArrayStub}\n";
            }

            $hookInstall = $this->write('hook_array', $conditionalHooks, $hookInstall);

            $stub = $this->write('conditional_hooks', $hookInstall, $stub);

            $hooksUninstall = $filesystem->read($this->stub('hook_uninstall.php'));
            $hooksUninstall = $this->write('slug_uc', $this->slug_uc, $hooksUninstall);

            $stub = $this->write('conditional_hooks_uninstall', $hooksUninstall, $stub);

            $this->buildExtension();
        } else {
            $stub = $this->erase('        {{conditional_hooks}}', $stub);
            $stub = $this->erase('        {{conditional_hooks_uninstall}}', $stub);
        }

        $this->putFile('upd.' . $this->slug . '.php', $stub);

        // Create module file
        $stub = $filesystem->read($this->stub('mod.slug.php'));
        $stub = $this->write('slug_uc', $this->slug_uc, $stub);
        $this->putFile('mod.' . $this->slug . '.php', $stub);

        // Create control panel file
        $stub = $filesystem->read($this->stub('mcp.slug.php'));
        $stub = $this->write('slug_uc', $this->slug_uc, $stub);
        $stub = $this->write('slug', $this->slug, $stub);
        $this->putFile('mcp.' . $this->slug . '.php', $stub);

        // Create lang file
        $filesystem->mkDir($this->addonPath . 'language');
        $filesystem->mkDir($this->addonPath . 'language/english');
        $stub = $filesystem->read($this->stub('slug_lang.php'));
        $stub = $this->write('name', $this->name, $stub);
        $stub = $this->write('description', $this->description, $stub);
        $stub = $this->write('slug', $this->slug, $stub);
        $this->putFile($this->slug . '_lang.php', $stub, '/language/english');
    }

    protected function buildPlugin()
    {
        $filesystem = new Filesystem();

        $stub = $filesystem->read($this->stub('pi.slug.php'));

        $stub = $this->write('slug_uc', $this->slug_uc, $stub);

        $this->putFile('pi.' . $this->slug . '.php', $stub);
    }

    private function buildAddonSetup()
    {
        $filesystem = new Filesystem();

        $stub = $filesystem->read($this->stub('addon.setup.php'));

        $stub = $this->write('author', $this->author, $stub);
        $stub = $this->write('author_url', $this->author_url, $stub);
        $stub = $this->write('name', $this->name, $stub);
        $stub = $this->write('description', $this->description, $stub);
        $stub = $this->write('version', $this->version, $stub);
        $stub = $this->write('namespace', $this->namespace, $stub);
        $stub = $this->write('settings_exist', $this->has_settings ? 'true' : 'false', $stub);

        if ($this->type == 'fieldtype') {
            $ftSetup = $filesystem->read($this->stub('fieldtype_setup.php'));
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
        if (array_key_exists('services', $this->data) && ($services = $this->data['services'])) {
            $servicesWriteData = '';

            $filesystem->mkDir($this->addonPath . 'Services');

            foreach (explode(',', $services) as $service) {
                if (!$service || $service == '') {
                    continue;
                }

                $servicesStub = $filesystem->read($this->stub('addon_service.php'));
                $servicesStub = $this->write('service_name', studly($service), $servicesStub);

                $servicesWriteData .= "\n\t\t" . $servicesStub . "\n";

                $serviceStub = $filesystem->read($this->stub('service.php'));
                $serviceStub = $this->write('namespace', $this->namespace, $serviceStub);
                $serviceStub = $this->write('class', studly($service), $serviceStub);

                $this->putFile(studly($service) . '.php', $serviceStub, '/Services');
            }

            $stub = $this->write('services', $servicesWriteData . "\t", $stub);
        } else {
            $stub = $this->clearLine("    'services'          => [{{services}}],", $stub);
        }

        // Models
        if (array_key_exists('models', $this->data) && ($models = $this->data['models'])) {
            $modelsWriteData = '';

            $filesystem->mkDir($this->addonPath . 'Models');

            foreach (explode(',', $models) as $service) {
                if (!$service || $service == '') {
                    continue;
                }

                $modelsStub = $filesystem->read($this->stub('addon_model.php'));
                $modelsStub = $this->write('model_name', studly($service), $modelsStub);

                $modelsWriteData .= "\n" . $modelsStub . "\n";

                $modelStub = $filesystem->read($this->stub('model.php'));
                $modelStub = $this->write('namespace', $this->namespace, $modelStub);
                $modelStub = $this->write('slug', $this->slug, $modelStub);
                $modelStub = $this->write('class', studly($service), $modelStub);

                $this->putFile(studly($service) . '.php', $modelStub, '/Models');
            }

            $stub = $this->write('models', $modelsWriteData . "\t", $stub);
        } else {
            $stub = $this->clearLine("    'models'            => [{{models}}],", $stub);
        }

        // Consents
        if (array_key_exists('consents', $this->data) && ($consents = $this->data['consents'])) {
            $consentsWriteData = '';

            foreach (explode(',', $consents) as $consent) {
                if (!$consent || $consent == '') {
                    continue;
                }

                $consentsStub = $filesystem->read($this->stub('addon_consent.php'));
                $consentsStub = $this->write('consent_name', studly($consent), $consentsStub);
                $consentsStub = $this->write('consent_slug', slug($consent, '_'), $consentsStub);

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
                $cookiesStub = $filesystem->read($this->stub('cookies.php'));
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
        $filesystem = new Filesystem();

        if ($path) {
            $path = trim($path, '/') . '/';
        } else {
            $path = '';
        }

        if (!$filesystem->exists($this->addonPath . $path . $name)) {
            $filesystem->write($this->addonPath . $path . $name, $contents);
        }
    }

    private function erase($string, $contents)
    {
        return str_replace($string, '', $contents);
    }

    private function clearLine($string, $contents)
    {
        return str_replace($string . "\n", '', $contents);
    }

    private function undo($confirm = false)
    {
        $filesystem->delete($this->addonPath);
    }
}
