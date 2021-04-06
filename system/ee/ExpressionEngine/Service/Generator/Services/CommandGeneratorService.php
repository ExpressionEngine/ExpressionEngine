<?php

namespace ExpressionEngine\Cli\Generator\Services;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Library\Filesystem\FilesystemException;

class CommandGeneratorService {

	protected $name;
	protected $className;
	protected $signature;
	protected $addon;
	protected $addonClass;
	protected $generatorPath;
	protected $addonPath;
	protected $stubPath;

	public function __construct(array $data)
	{

		ee()->load->helper('string');

		$this->name = $data['name'];
		$this->addon = $data['addon'];
		$this->addonClass = studly($data['addon']);
		$this->className = studly($data['name']);
		$this->fullClass = $this->addonClass . '\\Commands\\' . studly($data['name']);
		$this->signature = $data['signature'];
		$this->description = $data['description'];

		$this->init();
	}

	private function init()
	{

		$this->generatorPath = SYSPATH . 'ee/ExpressionEngine/Cli/Generator';
		$this->addonPath = SYSPATH . 'user/addons/' . $this->addon . '/';
		$this->commandsPath = SYSPATH . 'user/addons/' . $this->addon . '/Commands/';
		$filesystem = new Filesystem;

		// Get stub path
		$this->stubPath = $this->generatorPath . '/stubs' . '/';

		if ( ! $filesystem->isDir($this->commandsPath) ) {
		    $filesystem->mkDir($this->commandsPath);
		}

	}

	public function build()
	{

		$filesystem = new Filesystem;

		$commandStub = $filesystem->read($this->stub('command.php'));
		$commandStub = $this->write('name', $this->name, $commandStub);
		$commandStub = $this->write('class', $this->className, $commandStub);
		$commandStub = $this->write('signature', $this->signature, $commandStub);
		$commandStub = $this->write('description', $this->description, $commandStub);

		$this->putFile(studly('Command' . $this->className) . '.php', $commandStub);

		$this->addCommandToAddonSetup();

		return true;

	}

	private function addCommandToAddonSetup()
	{

		$filesystem = new Filesystem;

		try {
			$addonSetup = $filesystem->read($this->addonPath . 'addon.setup.php');
		try {
			$addonSetup = $filesystem->read($this->addonPath . 'addon.setup.php');
		} catch (FilesystemException $e) {
			return false;
		} catch (\Exception $e) {
			return false;
		}

		$commandString = "    '{$this->signature}' => {$this->fullClass}::class,";
		$useCommandString = "use {$this->fullClass};";

		$filesystem->findAndReplace($this->addonPath . 'addon.setup.php', "<?php", "<?php\n\n{$useCommandString}\n\n");

		if(string_contains($addonSetup, "'commands'") || string_contains($addonSetup, '"commands"')) {
			$commandStub = $filesystem->read($this->stub('command.addon.php'));
			$commandStub = $this->write('command_data', $commandString, $commandStub);

			preg_match('(\]\;|\)\;)', $addonSetup, $matches);

			if(! empty($matches)) {
				$last = array_values(array_slice($matches, -1))[0];

				$addonSetup = $this->write($last, $commandStub . "\n\n" . $last, $addonSetup);

		} else {
			$stringToReplace = string_contains($addonSetup, "'commands'")
								? '"commands"'
								: "'commands'";

			// TODO: Find command array and add $commandString to the array
			preg_match('/(\'|\")commands(\'|\")(\s+)=>(\s+)(\[|array\()/', $addonSetup, $matches);

			if(! empty($matches) && isset($matches[1])) {
				$addonSetup = $this->write($matches[1], $matches[1] . "\n\n" . $commandString, $addonSetup);
			}
		}

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

		$filesystem = new Filesystem;

		if($path) {
			$path = trim($path, '/') . '/';
		} else {
			$path = '';
		}

		if(!$filesystem->exists($this->commandsPath . $path . $name)) {
			$filesystem->write($this->commandsPath . $path . $name, $contents);
		}
	}
}