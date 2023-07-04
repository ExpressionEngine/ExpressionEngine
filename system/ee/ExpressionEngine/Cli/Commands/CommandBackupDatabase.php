<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;

/**
 * Command to update config values
 */
class CommandBackupDatabase extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Backup Database';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'backup:database';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php backup:database';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'relative_path,p:'  => 'command_backup_database_option_relative_path',
        'absolute_path,a:'  => 'command_backup_database_option_absolute_path',
        'file_name,f:'  => 'command_backup_database_option_file_name',
        'speed,s:'  => 'command_backup_database_option_speed',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_backup_database_beginning_database_backup');

        $date = ee()->localize->format_date('%Y-%m-%d_%Hh%im%ss%T');
        $path = PATH_CACHE;
        $file_name = ee()->db->database . '_' . $date . '.sql';

        // Change the file name if its specified
        if ($this->option('--file_name')) {
            $file_name = $this->option('--file_name');
        }

        // Change the backup path if its specified
        if ($this->option('--relative_path')) {
            $path = PATH_CACHE . $this->option('--relative_path');
        } elseif ($this->option('--absolute_path')) {
            $path = $this->option('--absolute_path');
        }

        // Set the database backup speed
        $speed = !is_null($this->option('--speed')) ? (int) $this->option('--speed') : 5;

        // Keep the speed between 0 and 10
        if (!in_array($speed, range(0, 10))) {
            $speed = ($speed > 10) ? 10 : 0;
        }

        // Set the waittime based on the speed
        $waitTime = (10 - $speed) * 10000;

        $path = reduce_double_slashes($path);
        $file_path = reduce_double_slashes($path . '/' . $file_name);

        $this->info(sprintf(lang('command_backup_database_backup_path'), $file_path));

        // Make sure the directory exists
        if (! ee('Filesystem')->exists($path)) {
            $this->fail('Directory does not exist: ' . $path);
        }

        // Make sure the file doesnt already exist:
        if (ee('Filesystem')->exists($file_path)) {
            $this->fail('File already exists; exiting.');
        }

        $backup = ee('Database/Backup', $file_path);

        $this->info('command_backup_database_backing_up_database');

        // Beginning a new backup
        try {
            $backup->startFile();
            $backup->writeDropAndCreateStatements();
        } catch (\Exception $e) {
            $this->error('command_backup_database_failed_with_error');
            $this->fail($e->getMessage());
        }

        $table_name = null;
        $offset = 0;
        $returned = true;

        do {
            try {
                $returned = $backup->writeTableInsertsConservatively($table_name, $offset);
            } catch (Exception $e) {
                $this->error('command_backup_database_failed_with_error');
                $this->fail($e->getMessage());
            }

            if ($returned !== false) {
                // Progress dots
                echo '.';

                // Verbose output:
                // $this->info("Table: $table_name\t\tOffset: $offset");

                // Add a wait so the Database is freed up a bit between requests
                usleep($waitTime);

                $table_name = $returned['table_name'];
                $offset = $returned['offset'];
            }
        } while ($returned !== false);

        $this->write('');

        $backup->endFile();

        $this->complete('command_backup_database_completed_successfully');
    }
}
