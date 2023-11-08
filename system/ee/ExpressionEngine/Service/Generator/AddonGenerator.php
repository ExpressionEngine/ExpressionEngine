<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Generator;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Library\String\Str;
use ExpressionEngine\Service\Generator\Enums\FieldtypeCompatibility;
use ExpressionEngine\Service\Generator\Enums\Hooks;

class AddonGenerator
{
    protected $filesystem;
    protected $str;

    public $name;
    public $data;
    public $slug;
    public $slug_uc;
    public $namespace;
    public $description;
    public $version;
    public $author;
    public $author_url;
    public $has_cp_backend;
    public $has_publish_fields;

    protected $stubPath;
    protected $generatorPath;
    protected $addonPath;

    public function __construct(Filesystem $filesystem, Str $str, array $data)
    {
        ee()->load->helper('string');

        // Set FS and String library
        $this->filesystem = $filesystem;
        $this->str = $str;

        $this->name = $data['name'];
        $this->slug = $this->str->snakecase($data['name']);
        $this->slug_uc = ucfirst($this->slug);

        // Setup the generator data
        $this->init();

        $this->namespace = $this->createNamespace($data);
        $this->description = $data['description'];
        $this->version = $data['version'];
        $this->author = $data['author'];
        $this->author_url = $data['author_url'];

        $this->has_cp_backend = isset($data['has_cp_backend']) ? $data['has_cp_backend'] : false;
        $this->has_publish_fields = isset($data['has_publish_fields']) ? $data['has_publish_fields'] : false;
    }

    private function init()
    {
        $this->generatorPath = SYSPATH . 'ee/ExpressionEngine/Service/Generator';
        $this->addonPath = SYSPATH . 'user/addons/' . $this->slug . '/';

        $this->stubPath = $this->generatorPath . '/stubs/MakeAddon/';

        if (! $this->filesystem->isDir($this->addonPath)) {
            $this->filesystem->mkDir($this->addonPath);
        }
    }

    public function build()
    {
        $this->buildAddonSetup();
        $this->buildModule();
        $this->buildUpd();
        $this->createLangFile();
        $this->createDefaultIcon();

        return true;
    }

    protected function createDefaultIcon()
    {
        // Copy the default icon into our addon
        $defaultIcon = PATH_THEMES . 'asset/img/default-addon-icon.svg';
        if ($this->filesystem->exists($defaultIcon)) {
            $this->filesystem->copy($defaultIcon, $this->addonPath . 'icon.svg');
        }
    }

    protected function buildModule()
    {
        // Create module file
        $stub = $this->filesystem->read($this->stub('mod.slug.php'));
        $stub = $this->write('slug_uc', $this->slug_uc, $stub);
        $stub = $this->write('slug', $this->slug, $stub);
        $this->putFile('mod.' . $this->slug . '.php', $stub);
    }

    protected function buildUpd()
    {
        // Create upd file
        $stub = $this->filesystem->read($this->stub('upd.slug.php'));
        $stub = $this->write('slug_uc', $this->slug_uc, $stub);
        $stub = $this->write('slug', $this->slug, $stub);
        $stub = $this->write('version', $this->version, $stub);
        $stub = $this->write('has_cp_backend', $this->has_cp_backend, $stub);
        $stub = $this->write('has_publish_fields', $this->has_publish_fields, $stub);
        $this->putFile('upd.' . $this->slug . '.php', $stub);
    }

    protected function buildAddonSetup()
    {
        $stub = $this->filesystem->read($this->stub('AddonSetup/addon.setup.php'));

        $stub = $this->write('author', $this->author, $stub);
        $stub = $this->write('author_url', $this->author_url, $stub);
        $stub = $this->write('name', $this->name, $stub);
        $stub = $this->write('description', $this->description, $stub);
        $stub = $this->write('version', $this->version, $stub);
        $stub = $this->write('namespace', $this->namespace, $stub);
        $stub = $this->write('settings_exist', 'false', $stub);

        $this->putFile('addon.setup.php', $stub);
    }

    public function createNamespace($data)
    {
        // Make studly case and strip non-alpha characters
        $name = $this->str->alphaFilter($this->str->studly($data['name']));
        $author = $this->str->alphaFilter($this->str->studly($data['author']));

        // Namespace should be the Add-on name
        $namespace = $name;

        // If there is an author, the Author name should preface the namespace
        if (!empty($author)) {
            $namespace = $author . '\\' . $namespace;
        }

        return $namespace;
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
}
