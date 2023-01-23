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

use ExpressionEngine\Updater\Library\Filesystem\Filesystem;
use ExpressionEngine\Updater\Service\Updater\SteppableTrait;

/**
 * Database updater for one-click updater
 *
 * Runs the ud_x_xx_xx.php files needed to complete the update
 */
class DatabaseUpdater
{
    use SteppableTrait;

    protected $from_version;
    protected $filesystem;
    protected $update_files_path;

    /**
     * Constructor, of course
     *
     * @param	string		$from_version	Version we are updating from
     * @param	Filesystem	$filesystem		Filesystem lib object so we can
     *   traverse the update files directory
     */
    public function __construct($from_version, Filesystem $filesystem)
    {
        $this->from_version = $from_version;
        $this->filesystem = $filesystem;
        $this->update_files_path = SYSPATH . 'ee/installer/updates/';

        $this->setSteps($this->getSteps());
    }

    /**
     * Given the version we are updating from, do we even have any update files
     * to run?
     *
     * @return	boolean	Whether or not there are updates to run
     */
    public function hasUpdatesToRun()
    {
        return ! empty($this->steps);
    }

    /**
     * Loops through update files to compile a list of tables that will be
     * affected by the update so that we can back them up
     *
     * @return	array	Array of table names
     */
    public function getAffectedTables()
    {
        $files = $this->getUpdateFiles();

        $affected_tables = [];
        foreach ($files as $filename) {
            $this->filesystem->include_file($this->update_files_path . $filename);
            $class = $this->getUpdaterClassForFilename($filename);

            $updater = new $class();
            if (isset($updater->affected_tables)) {
                $affected_tables = array_merge($affected_tables, $updater->affected_tables);
            }
            unset($updater);
        }

        return $affected_tables;
    }

    /**
     * Runs a given update file
     *
     * @param	string	$filename	Base file name, no path, e.g. 'ud_4_00_00.php'
     */
    public function runUpdateFile($filename)
    {
        $this->filesystem->include_file($this->update_files_path . $filename);
        $class = $this->getUpdaterClassForFilename($filename);

        $updater = new $class();
        $updater->do_update();
        unset($updater);
    }

    /**
     * Generates an array of Steppable steps
     *
     * @return	array	Array of steps, e.g.
     *   [
     *   	'runUpdateFile[ud_4_00_00.php]',
     *   	...
     *   ]
     */
    protected function getSteps()
    {
        $files = $this->getUpdateFiles();

        return array_map(function ($filename) {
            return sprintf('runUpdateFile[%s]', $filename);
        }, $files);
    }

    /**
     * Given the current version of EE, returns an array of update files we need
     * to run in order to update EE
     *
     * @return	array	Array of files, e.g.
     *   [
     *   	'ud_4_00_00.php',
     *   	...
     *   ]
     */
    protected function getUpdateFiles()
    {
        $files = $this->filesystem->getDirectoryContents($this->update_files_path);

        $update_files = [];
        foreach ($files as $filename) {
            $filename = pathinfo($filename);
            $version = $this->getVersionForFilename($filename['basename']);

            if (version_compare($version, $this->from_version, '>')) {
                $update_files[] = $filename['basename'];
            }
        }

        // Sort based on version, not filesystem order
        usort($update_files, function ($a, $b) {
            $a = $this->getVersionForFilename($a);
            $b = $this->getVersionForFilename($b);

            return version_compare($a, $b, '>') ? 1 : -1;
        });

        return $update_files;
    }

    /**
     * Given a base file name, returns a formatted version
     *
     * @param	string	$filename	Base file name, e.g. 'ud_4_00_00.php'
     * @return	string	Formatted version, e.g. '4.0.0'
     */
    protected function getVersionForFilename($filename)
    {
        $version = '';

        if (preg_match('/^ud_0*(\d+)_0*(\d+)_0*(\d+)(?:_(.*?))?.php$/', $filename, $matches)) {
            $version = "{$matches[1]}.{$matches[2]}.{$matches[3]}";

            // Convert _dp_01 => -dp.1 type suffixes
            if (! empty($matches[4])) {
                $dev_segs = explode('_', $matches[4]);
                $version .= '-' . array_shift($dev_segs);

                foreach ($dev_segs as $seg) {
                    $version .= '.' . trim($seg, '0');
                }
            }
        }

        return $version;
    }

    /**
     * Given a base file name, returns the namespaced class name for the Updater class
     *
     * @param	string	$filename	Base file name, e.g. 'ud_4_00_00.php'
     * @return	string	Class name, e.g. '\ExpressionEngine\Updater\Version_4_0_0\Updater'
     */
    protected function getUpdaterClassForFilename($filename)
    {
        return '\ExpressionEngine\Updater\Version_'
            . str_replace(['.', '-'], '_', $this->getVersionForFilename($filename))
             . '\Updater';
    }
}

// EOF
