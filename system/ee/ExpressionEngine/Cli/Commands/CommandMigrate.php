<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Cli\Commands\Migration\MigrationUtility;
use ExpressionEngine\Cli\Commands\Migration\Migrator;

/**
 * Run migrations
 */
class CommandMigrate extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Migrate';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'migrate';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'Run all migrations that have not been run from the migrations folder.';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary = 'Loops through the SYSPATH/user/database/migrations folder and executes all migrations that have not previously been run.';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php migrate';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'steps,s:' => 'Specify the number of migrations to run',
    ];

    /**
     * Command can run without EE Core
     * @var boolean
     */
    public $standalone = false;

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        defined('PATH_THIRD') || define('PATH_THIRD', SYSPATH . 'user/addons/');

        // Specify the number of migrations to run
        $steps = $this->option('-s', -1);

        // Checks for migration folder and creates it if it does not exist
        MigrationUtility::ensureMigrationFolderExists($this->output);

        // Checks for exp_migrations table and creates it if it does not exist
        MigrationUtility::ensureMigrationTableExists($this->output);

        // Get new migrations based on file and presence in the migrations table
        $newMigrations = $this->getNewMigrations();

        if (empty($newMigrations)) {
            $this->complete("All migrations have run. To create a new migration, use the make:migration command.");
        }

        $migrationsCount = 0;
        $migrationGroup = MigrationUtility::getNextMigrationGroup();
        foreach ($newMigrations as $migrationName) {
            $this->info('Migrating: ' . $migrationName);

            $migration = ee('Model')->make('Migration', [
                'migration' => $migrationName,
                'migration_group' => $migrationGroup,
            ]);

            $migration->up();
            $migration->save();

            // Increment the number of migrations that have run
            $migrationsCount++;

            // If the number of migrations run equals our step count, exit with a message
            if ($migrationsCount == $steps) {
                $this->complete("Executed " . $migrationsCount . " migration" . (($steps > 1) ? 's' : '') . " successfully.");
            }
        }

        $this->complete('All migrations completed successfully!');
    }

    private function getNewMigrations()
    {
        $filesystem = new Filesystem();
        $newMigrations = array();
        foreach ($filesystem->getDirectoryContents(MigrationUtility::$migrationsPath) as $file) {
            // If it's not a PHP file, it's not a migration
            if (!$this->endsWith($file, '.php')) {
                continue;
            }

            // Filter out the filepath and extension
            $migrationName = pathinfo($file, PATHINFO_FILENAME);

            // This migration has already run
            if (in_array($migrationName, MigrationUtility::getExecutedMigrations())) {
                continue;
            }

            $newMigrations[] = $migrationName;
        }

        // Make sure they are in the correct order
        sort($newMigrations);

        return $newMigrations;
    }

    public function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }
}
