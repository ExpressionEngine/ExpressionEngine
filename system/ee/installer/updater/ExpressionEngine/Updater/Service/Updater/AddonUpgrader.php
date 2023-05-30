<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Service\Updater;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Updater\Service\Updater\SteppableTrait;
use Exception;

/**
 * Database updater for one-click updater
 *
 * Runs the ud_x_xx_xx.php files needed to complete the update
 */
class AddonUpgrader
{
    protected $filesystem;

    protected $error;

    private $basepath;

    private $removeFunctions = [
        '__construct',
    ];

    public function __construct($basepath)
    {
        $this->basepath = $basepath;

        $this->filesystem = new Filesystem();
    }

    /**
     * runs all appropriate methods to upgrade an addon
     * @return bool
     */
    public function run()
    {
        $functions = get_class_methods($this);

        foreach ($functions as $fxn) {
            if (! in_array($fxn, $this->removeFunctions)) {
                $result = $this->{$fxn}();

                if (! $result) {
                    throw new Exception('Upgrader failed');
                }
            }
        }

        return true;
    }

    // PROTECTED FUNCTIONS
    // These are the ones that update all of the things in the addon.
    protected function convertThisEEGetInstanceToNull()
    {
        if ($this->filesystem->isDir($this->basepath)) {
            $contents = $this->filesystem->getDirectoryContents($this->basepath, true);

            foreach ($contents as $file) {
                $fileContents = $this->filesystem->read($file);

                $newFileContents = str_replace('$this->EE =& get_instance();', '', $fileContents);

                try {
                    $this->filesystem->write($file, $newFileContents, true);
                } catch (Exception $e) {
                    $this->error = "{$e->getMessage()}\n\n\n{$e->getTraceAsString()}";

                    return false;
                }
            }
        }
    }

    protected function convertThisEEToEEFunction()
    {
        if ($this->filesystem->isDir($this->basepath)) {
            $contents = $this->filesystem->getDirectoryContents($this->basepath, true);

            foreach ($contents as $file) {
                $fileContents = $this->filesystem->read($file);

                $newFileContents = str_replace('$this->EE', 'ee()', $fileContents);

                try {
                    $this->filesystem->write($file, $newFileContents, true);
                } catch (Exception $e) {
                    $this->error = "{$e->getMessage()}\n\n\n{$e->getTraceAsString()}";

                    return false;
                }
            }
        }

        return true;
    }
}
