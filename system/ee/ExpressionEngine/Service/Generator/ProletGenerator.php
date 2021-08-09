<?php

namespace ExpressionEngine\Service\Generator;

use ExpressionEngine\Library\Filesystem\Filesystem;

class ProletGenerator
{
    public $name;
    public $addon;
    protected $filesystem;
    protected $generatorPath;
    protected $addonPath;
    protected $stubPath;

    public function __construct(Filesystem $filesystem, array $data)
    {
        // Set FS
        $this->filesystem = $filesystem;

        // Set required data for generator to use
        $this->name = $data['name'];
        $this->addon = $data['addon'];

        // Set up addon path, generator path, and stub path
        $this->init();
    }

    private function init()
    {
        // Make sure the addon exists
        if (!ee('Addon')->get($this->addon)) {
            throw new \Exception(lang('cli_error_the_specified_addon_does_not_exist'), 1);
        }

        $this->generatorPath = SYSPATH . 'ee/ExpressionEngine/Service/Generator';
        $this->addonPath = SYSPATH . 'user/addons/' . $this->addon . '/';

        // Get stub path
        $this->stubPath = $this->generatorPath . '/stubs/';
    }

    public function build()
    {
        $proletStub = $this->filesystem->read($this->stub('prolet.php'));
        $proletStub = $this->write('addon', ucfirst($this->addon), $proletStub);
        $proletStub = $this->write('name', $this->name, $proletStub);

        $this->putFile('pro.' . $this->addon . '.php', $proletStub);
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

    public function string_contains($textToSearch, $word)
    {
        return (strpos($textToSearch, $word) !== false);
    }
}
