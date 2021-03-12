<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Cli\Commands\Migration\MigrationUtility;

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
     * Classname of migration to create
     * @var string
     */
    public $classname;

    /**
     * Filename of migration to create
     * @var string
     */
    public $filename;

    /**
     * Full path to the new migration file
     * @var string
     */
    public $filepath;

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
        $this->migration_name = $this->option('--name', null);

        // Name is a required field
        if (is_null($this->migration_name)) {
            $this->fail('No migration name specified. For help with this command, use --help');
        }

        // Checks for migration folder and creates it if it does not exist
        MigrationUtility::ensureMigrationFolderExists($this->output);

        // Set necessary variables
        $this->classname = MigrationUtility::camelCase($this->migration_name);

        $this->setFileinfo();
        $this->setTableName();
        $this->setTemplateName();

        $this->writeMigrationSummary();
        $this->writeMigrationFileFromTemplate();
        $this->info('<<bold>>Successfully wrote new migration file.');
    }

    public function writeMigrationFileFromTemplate()
    {
        $filesystem = new Filesystem();

        if (! $filesystem->isWritable(MigrationUtility::$migrationsPath)) {
            $this->fail("Error: " . MigrationUtility::$migrationsPath . " is not writable.\n"
                . "Please correct this and then try again.");
        }

        $templateClass = '\ExpressionEngine\Cli\Commands\Migration\Templates\\' . $this->templateName;

        $vars = [
            'classname' => $this->classname,
            'table' => $this->tablename,
        ];
        $template = new $templateClass($vars);
        $filecontents = $template->getParsedTemplate();

        $filesystem->write($this->filepath, $filecontents);
    }

    public function setTableName()
    {
        if ($this->option('--table')) {
            $this->tablename = $this->option('--table');

            return true;
        }

        $name = MigrationUtility::snakeCase($this->migration_name);

        $words = explode('_', $name);
        $words = array_diff($words, ['create', 'update', 'table']);
        $this->tablename = implode('_', $words);

        return true;
    }

    public function setTemplateName()
    {
        $migration_name = MigrationUtility::snakeCase($this->migration_name);
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
        $this->info('<<bold>>Creating migration: ' . $this->filename);

        if ($this->is_create) {
            $this->info('  Migration type:  Create Table');
            $this->info('  Table name:      ' . $this->tablename);
        } elseif ($this->is_update) {
            $this->info('  Migration type:  Update Table');
            $this->info('  Table name:      ' . $this->tablename);
        } else {
            $this->info('  Migration type:  Generic');
        }
        $this->info('  Class name:      ' . $this->classname);
        $this->info('  File Location:   ' . $this->filepath);
    }

    public function setFileinfo()
    {
        $this->filename = date('Y_m_d_His') . '_' . MigrationUtility::snakeCase($this->migration_name);
        $this->filepath = MigrationUtility::$migrationsPath . $this->filename . '.php';
    }
}
