<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Library\Filesystem\Filesystem;

/**
 * Generate a new migration using the CLI
 */
class CommandMakeMigration extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Make migration';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:migration';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'Generate a new migration using the CLI';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary = 'This generates a new migration, located in SYSPATH/user/database/migrations folder. Migrations are ordered by timestamp and ran in order.';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:migration --name create_myaddon_table';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'name,n:' => 'Name of migration',
        'table,t:' => 'Table name',
        'location,l:' => 'Migration location. Current options are ExpressionEngine or shortname of an add-on that is currently installed. Defaults to ExpressionEngine.',
        'create,c' => 'Specify command is a create command',
        'update,u' => 'Specify command is an update command',
    ];

    /**
     * Command can run without EE Core
     * @var boolean
     */
    public $standalone = true;

    /**
     * Passed in migration name
     * @var string
     */
    public $migration_name;

    // For finding the right template
    public $is_create = false;
    public $is_update = false;
    public $templateName = 'GenericMigration';

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        defined('PATH_THIRD') || define('PATH_THIRD', SYSPATH . 'user/addons/');

        $this->migration_name = $this->option('--name');
        $this->migration_location = $this->option('--location', 'ExpressionEngine');

        // Name is a required field
        if (is_null($this->migration_name)) {
            $this->fail('No migration name specified. For help with this command, use --help');
        }

        // If tablename is explicitly passed, use that
        if ($this->option('--table')) {
            $this->tableName = $this->option('--table');
        } else {
            // Generate tablename since it wasnt passed
            $name = ee('Migration')->snakeCase($this->migration_name);

            $words = explode('_', $name);
            $words = array_diff($words, ['create', 'update', 'table']);

            // Parse key words out of passed string, and what is left we assume is the table name
            $this->tableName = implode('_', $words);
        }

        // Generates an instance of a migration model using a timestamped, processed filename
        $this->migration = ee('Migration')->generateMigration($this->migration_name, $this->migration_location);

        $this->setTemplateName();
        $this->writeMigrationSummary();

        ee('Migration', $this->migration)->writeMigrationFileFromTemplate($this->templateName, $this->tableName);

        $this->info('<<bold>>Successfully wrote new migration file.');
    }

    public function setTemplateName()
    {
        $migration_name = ee('Migration')->snakeCase($this->migration_name);
        $words = explode('_', $migration_name);

        // Set create and update flags and then find the template we are looking for
        // Flags passed always take precedence and then looking for words in command
        if ($this->option('--create')) {
            $this->is_create = true;
            $this->templateName = 'CreateTable';
        } elseif ($this->option('--update')) {
            $this->is_update = true;
            $this->templateName = 'UpdateTable';
        } elseif (in_array('create', $words)) {
            $this->is_create = true;
            $this->templateName = 'CreateTable';
        } elseif (in_array('update', $words)) {
            $this->is_update = true;
            $this->templateName = 'UpdateTable';
        }
    }

    public function writeMigrationSummary()
    {
        $this->info('<<bold>>Creating migration: ' . $this->migration->migration);

        if ($this->is_create) {
            $this->info('  Migration type:  Create Table');
            $this->info('  Table name:      ' . $this->tableName);
        } elseif ($this->is_update) {
            $this->info('  Migration type:  Update Table');
            $this->info('  Table name:      ' . $this->tableName);
        } else {
            $this->info('  Migration type:  Generic');
        }
        $this->info('  Class name:      ' . $this->migration->getClassname());
        $this->info('  File Location:   ' . $this->migration->getFilepath());
    }
}
