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

    /**
     * Migration type. Options are create, update, and generic
     * @var string
     */
    public $migrationType = 'generic';

    /**
     * Template name, generated from info about migration
     * @var string
     */
    public $templateName = 'GenericMigration';

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        defined('PATH_THIRD') || define('PATH_THIRD', SYSPATH . 'user/addons/');

        $this->migration_name = $this->option('--name');

        // Ask for the name if they didnt specify one
        if (empty($this->migration_name)) {
            $this->migration_name = $this->ask('What is the name of your migration?');
        }

        // Name is a required field
        if (empty($this->migration_name)) {
            $this->fail('No migration name specified. For help with this command, use --help');
        }

        // Snakecase the passed in migration name
        $this->migration_name = ee('Migration')->snakeCase($this->migration_name);

        $this->info('Using migration name:      ' . $this->migration_name);

        // Set location
        $this->migration_location = $this->option('--location', 'ExpressionEngine');

        // Set migration type based on create/update flags
        if ($this->option('--create')) {
            $this->migrationType = 'create';
        } elseif ($this->option('--update')) {
            $this->migrationType = 'update';
        } else {
            // No migration flags passed, so first we guess, then we ask,
            // defaulting to the guessed option
            $this->migrationType = $this->guessMigrationType();
            $this->askMigrationType();
        }

        // If tablename is explicitly passed, use that
        if ($this->option('--table')) {
            $this->tableName = $this->option('--table');
        } else {
            $this->tableName = $this->guessTablename();
            $this->askTablename();
        }

        // Generates an instance of a migration model using a timestamped, processed filename
        $this->migration = ee('Migration')->generateMigration($this->migration_name, $this->migration_location);

        $this->setTemplateName();

        // Print out info about generated migration
        $this->info('<<bold>>Creating migration: ' . $this->migration->migration);
        $this->info('  Migration type:  ' . $this->migrationType);
        $this->info('  Table name:      ' . $this->tableName);
        $this->info('  Class name:      ' . $this->migration->getClassname());
        $this->info('  File Location:   ' . $this->migration->getFilepath());

        ee('Migration', $this->migration)->writeMigrationFileFromTemplate($this->templateName, $this->tableName);

        $this->info('<<bold>>Successfully wrote new migration file.');
    }

    public function askTablename()
    {
        $this->tableName = $this->ask('What table is this migration for? [' . $this->tableName . ']', $this->tableName);
    }

    public function guessTablename()
    {
        // Generate tablename since it wasnt passed
        $words = explode('_', $this->migration_name);
        $words = array_diff($words, ['create', 'update', 'table']);

        // Parse key words out of passed string, and what is left we assume is the table name
        $tablename = implode('_', $words);

        if (empty($tablename)) {
            $tablename = 'my_table';
        }

        return $tablename;
    }

    public function guessMigrationType()
    {
        $words = explode('_', $this->migration_name);

        // Guess create and update flags based on keywords
        if (in_array('create', $words)) {
            return 'create';
        } elseif (in_array('update', $words)) {
            return 'update';
        }

        return 'generic';
    }

    public function askMigrationType()
    {
        $type = $this->ask('What is the migration type (generic/create/update)? [' . $this->migrationType . ']', $this->migrationType);

        $type = trim(strtolower($type));

        if (in_array($type, array('create', 'update', 'generic'))) {
            $this->migrationType = $type;
        } else {
            $this->migrationType = 'generic';
        }
    }

    public function setTemplateName()
    {
        $words = explode('_', $this->migration_name);

        if (in_array($this->migrationType, array('create, update'))) {
            $this->templateName = ucfirst($this->migrationType) . 'Table';
        }
    }
}
