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
    ];

    /**
     * Command can run without EE Core
     * @var boolean
     */
    public $standalone = true;

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

    public $tablename='my_table';

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $name = $this->option('-n', null);

        // Name is a required field
        if (is_null($name)) {
            $this->fail('No migration name specified. For help with this command, use --help');
        }

        // Checks for migration folder and creates it if it does not exist
        MigrationUtility::ensureMigrationFolderExists($this->output);

        // Set necessary variables
        $this->filename = MigrationUtility::generateFileName($name);
        $this->classname = MigrationUtility::generateClassName($name);
        $this->filepath = MigrationUtility::$migrationsPath . $this->filename;
        $this->tablename = $this->option('-t', MigrationUtility::parseForTablename($name));

        $this->info('<<bold>>Creating migration: ' . $this->classname);
        $this->writeMigrationFileFromTemplate();

        $this->info($name);
        $this->info($this->filename);
        $this->info($this->classname);
    }

    public function writeMigrationFileFromTemplate($templateName='GenericMigration')
    {
        $filesystem = new Filesystem();

        if (! $filesystem->isWritable(MigrationUtility::$migrationsPath)) {
            $this->fail("Error: " . MigrationUtility::$migrationsPath . " is not writable.\n"
                . "Please correct this and then try again.");
        }

        $templateClass = '\ExpressionEngine\Cli\Commands\Migration\Templates\\' . $templateName;

        $vars = [
            'classname' => $this->classname,
            'table' => $this->tablename,
        ];
        $template = new $templateClass($vars);
        $filecontents = $template->getParsedTemplate();

        $filesystem->write($this->filepath, $filecontents);
    }
}
