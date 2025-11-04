<?php

/**
 * ExpressionEngine Version Bumper
 *
 * Bumps the version number across ExpressionEngine core files and creates
 * installer update files as needed.
 *
 * Usage:
 *   php make_version.php [--version=<version>] [--date=<YYYY-MM-DD>] [--skip-update-file] [--root=<path>]
 *
 * Options:
 *   -v, --version       Version to set (e.g., 7.5.18)
 *   -d, --date          Release date in YYYY-MM-DD format (used for build number)
 *   --skip-update-file  Skip creating installer update file
 *   --dry-run           Show what would be changed without making changes
 *   --root              Repository root path (defaults to script dir/../)
 *   -h, --help          Show this help
 */

class VersionBumper
{
    private $version = '';
    private $build = '';
    private $identifier = '';
    private $updateFile = '';
    private $repoRoot = '';
    private $skipUpdateFile = false;
    private $dryRun = false;

    private $filesToUpdate = [
        'system/ee/legacy/libraries/Core.php',
        'system/ee/installer/controllers/wizard.php',
        'system/ee/ExpressionEngine/Tests/bootstrap.php',
        'tests/cypress/support/config/config.php'
    ];

    public function __construct()
    {
        $this->checkSecurity();
        $this->parseArguments();
        $this->validateInputs();
    }

    private function checkSecurity()
    {
        // Prevent running as root or with elevated privileges
        if (function_exists('posix_getuid') && posix_getuid() === 0) {
            throw new Exception("This script should not be run as root");
        }

        // Basic check for safe execution environment
        if (!defined('PHP_SAPI') || PHP_SAPI !== 'cli') {
            throw new Exception("This script must be run from command line");
        }
    }

    private function parseArguments()
    {
        $options = getopt('v:d:r:h', [
            'version:',
            'date:',
            'root:',
            'skip-update-file',
            'dry-run',
            'help'
        ]);

        if (isset($options['h']) || isset($options['help'])) {
            $this->showHelp();
            exit(0);
        }

        // Set repo root
        $this->repoRoot = isset($options['root']) ? $options['root'] : dirname(__DIR__);
        $this->repoRoot = rtrim($this->repoRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Set version
        if (isset($options['v'])) {
            $this->version = $options['v'];
        } elseif (isset($options['version'])) {
            $this->version = $options['version'];
        }

        // Set date
        $date = '';
        if (isset($options['d'])) {
            $date = $options['d'];
        } elseif (isset($options['date'])) {
            $date = $options['date'];
        }

        // Skip update file flag
        $this->skipUpdateFile = isset($options['skip-update-file']);

        // Dry run flag
        $this->dryRun = isset($options['dry-run']);

        // Prompt for missing inputs
        if (empty($this->version)) {
            $this->version = $this->prompt('Enter version (e.g., 7.5.18): ');
        }

        if (empty($date)) {
            $date = $this->prompt('Enter release date (YYYY-MM-DD): ');
        }

        // Parse version and date
        $this->parseVersion($this->version);
        $this->parseDate($date);
        $this->generateUpdateFileName();
    }

    private function parseVersion($version)
    {
        // Limit input length to prevent resource exhaustion
        if (strlen($version) > 50) {
            throw new Exception("Version string too long");
        }

        if (!preg_match('/^(\d+)\.(\d+)\.(\d+)(?:-(.+))?$/', $version, $matches)) {
            throw new Exception("Invalid version format. Expected X.Y.Z or X.Y.Z-identifier");
        }

        // Validate version components are reasonable
        if ($matches[1] > 99 || $matches[2] > 99 || $matches[3] > 99) {
            throw new Exception("Version numbers cannot exceed 99");
        }

        // Validate identifier doesn't contain dangerous characters
        if (isset($matches[4]) && !preg_match('/^[a-zA-Z0-9._-]+$/', $matches[4])) {
            throw new Exception("Version identifier contains invalid characters");
        }

        $this->version = $matches[1] . '.' . $matches[2] . '.' . $matches[3];
        $this->identifier = isset($matches[4]) ? $matches[4] : '';
    }

    private function parseDate($date)
    {
        // Limit input length
        if (strlen($date) > 20) {
            throw new Exception("Date string too long");
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new Exception("Invalid date format. Expected YYYY-MM-DD");
        }

        // Basic date validation (not perfect but better than nothing)
        $parts = explode('-', $date);
        $year = (int)$parts[0];
        $month = (int)$parts[1];
        $day = (int)$parts[2];

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12 || $day < 1 || $day > 31) {
            throw new Exception("Date values out of reasonable range");
        }

        $this->build = str_replace('-', '', $date);
    }

    private function generateUpdateFileName()
    {
        $normalizedVersion = $this->version;
        if (!empty($this->identifier)) {
            $normalizedVersion = str_replace('-', '.', $normalizedVersion . '-' . $this->identifier);
        }

        $segments = explode('.', $normalizedVersion);
        // Zero-pad minor and patch versions to match existing file format
        $segments[1] = str_pad($segments[1], 2, '0', STR_PAD_LEFT);
        $segments[2] = str_pad($segments[2], 2, '0', STR_PAD_LEFT);
        $this->updateFile = 'ud_' . implode('_', $segments) . '.php';
    }

    private function validateInputs()
    {
        if (!is_dir($this->repoRoot)) {
            throw new Exception("Repository root does not exist: {$this->repoRoot}");
        }

        // Prevent path traversal attacks by ensuring repo root is within expected bounds
        $realRepoRoot = realpath($this->repoRoot);
        $scriptDir = realpath(dirname(__DIR__));

        if ($realRepoRoot === false) {
            throw new Exception("Invalid repository root path: {$this->repoRoot}");
        }

        // Only allow paths that are subdirectories of the script's parent directory
        // or the script's parent directory itself
        if (strpos($realRepoRoot, $scriptDir) !== 0 && $realRepoRoot !== $scriptDir) {
            throw new Exception("Repository root must be within the project directory: {$this->repoRoot}");
        }

        // Validate that the path doesn't contain suspicious characters
        if (preg_match('/[<>|;&$]/', $this->repoRoot)) {
            throw new Exception("Repository root contains invalid characters: {$this->repoRoot}");
        }

        // Check if required files exist (but only warn, don't fail)
        foreach ($this->filesToUpdate as $file) {
            $fullPath = $this->repoRoot . $file;
            $realFullPath = realpath($fullPath);

            // Additional security: ensure the resolved file path is still within repo root
            if ($realFullPath !== false && strpos($realFullPath, $realRepoRoot) !== 0) {
                throw new Exception("File path traversal detected: {$file}");
            }

            if (!file_exists($fullPath)) {
                echo "Warning: File not found: {$fullPath}\n";
            }
        }
    }

    public function run()
    {
        echo "ExpressionEngine Version Bumper\n";
        echo "===============================\n\n";

        echo "Version: {$this->version}\n";
        echo "Build: {$this->build}\n";
        echo "Identifier: " . (empty($this->identifier) ? '(none)' : $this->identifier) . "\n";
        echo "Update file: {$this->updateFile}\n";
        echo "Repository root: {$this->repoRoot}\n";
        echo "Mode: " . ($this->dryRun ? 'DRY RUN (no changes will be made)' : 'LIVE') . "\n\n";

        if (!$this->confirm("Proceed with version bump?")) {
            echo "Aborted.\n";
            exit(0);
        }

        $this->updateFiles();
        $this->createUpdateFile();

        echo "\n" . ($this->dryRun ? "Dry run completed. No changes were made." : "Version bump completed successfully!") . "\n";
    }

    private function updateFiles()
    {
        echo ($this->dryRun ? "Checking files (dry run)...\n" : "Updating files...\n");

        foreach ($this->filesToUpdate as $file) {
            $fullPath = $this->repoRoot . $file;

            if (!file_exists($fullPath)) {
                echo "Skipping missing file: {$file}\n";
                continue;
            }

            echo ($this->dryRun ? "Checking {$file}... " : "Updating {$file}... ");
            $updated = false;
            $changes = [];

            $content = file_get_contents($fullPath);

            // Check/Update APP_VER
            if (strpos($content, "define('APP_VER'") !== false) {
                if (preg_match("/define\('APP_VER',\s+'([^']*)'\);/", $content, $matches)) {
                    if ($matches[1] !== $this->version) {
                        $changes[] = "APP_VER: '{$matches[1]}' → '{$this->version}'";
                        if (!$this->dryRun) {
                            $content = preg_replace(
                                "/define\('APP_VER',(\s+)'[^']*'\);/",
                                "define('APP_VER',$1'{$this->version}');",
                                $content
                            );
                        }
                        $updated = true;
                    }
                }
            }

            // Check/Update APP_BUILD
            if (strpos($content, "define('APP_BUILD'") !== false) {
                if (preg_match("/define\('APP_BUILD',\s+'([^']*)'\);/", $content, $matches)) {
                    if ($matches[1] !== $this->build) {
                        $changes[] = "APP_BUILD: '{$matches[1]}' → '{$this->build}'";
                        if (!$this->dryRun) {
                            $content = preg_replace(
                                "/define\('APP_BUILD',(\s+)'[^']*'\);/",
                                "define('APP_BUILD',$1'{$this->build}');",
                                $content
                            );
                        }
                        $updated = true;
                    }
                }
            }

            // Check/Update APP_VER_ID
            if (strpos($content, "define('APP_VER_ID'") !== false) {
                if (preg_match("/define\('APP_VER_ID',\s+'([^']*)'\);/", $content, $matches)) {
                    if ($matches[1] !== $this->identifier) {
                        $changes[] = "APP_VER_ID: '{$matches[1]}' → '{$this->identifier}'";
                        if (!$this->dryRun) {
                            $content = preg_replace(
                                "/define\('APP_VER_ID',(\s+)'[^']*'\);/",
                                "define('APP_VER_ID',$1'{$this->identifier}');",
                                $content
                            );
                        }
                        $updated = true;
                    }
                }
            }

            // Check/Update wizard version
            if (strpos($file, 'wizard.php') !== false) {
                if (preg_match("/(public\s+)?\$version\s*=\s*'([^']*)';/", $content, $matches)) {
                    if ($matches[2] !== $this->version) {
                        $changes[] = "\$version: '{$matches[2]}' → '{$this->version}'";
                        if (!$this->dryRun) {
                            $content = preg_replace(
                                "/(public\s+)?\$version\s*=\s*'[^']*';/",
                                "\$version = '{$this->version}';",
                                $content
                            );
                        }
                        $updated = true;
                    }
                }
            }

            // Check/Update Cypress config
            if (strpos($file, 'config.php') !== false && strpos($file, 'cypress') !== false) {
                if (preg_match("/\$config\['app_version'\]\s*=\s*'([^']*)';/", $content, $matches)) {
                    if ($matches[1] !== $this->version) {
                        $changes[] = "\$config['app_version']: '{$matches[1]}' → '{$this->version}'";
                        if (!$this->dryRun) {
                            $content = preg_replace(
                                "/\$config\['app_version'\]\s*=\s*'[^']*';/",
                                "\$config['app_version'] = '{$this->version}';",
                                $content
                            );
                        }
                        $updated = true;
                    }
                }
            }

            if ($updated) {
                if ($this->dryRun) {
                    echo "Would change:\n";
                    foreach ($changes as $change) {
                        echo "  - {$change}\n";
                    }
                } else {
                    file_put_contents($fullPath, $content);
                    echo "✓\n";
                }
            } else {
                echo "No changes needed\n";
            }
        }
    }

    private function createUpdateFile()
    {
        if ($this->skipUpdateFile) {
            echo "Skipping update file creation (--skip-update-file flag set)\n";
            return;
        }

        $sourceFile = $this->repoRoot . 'system/ee/installer/updates/ud_6_02_03.php';
        $destFile = $this->repoRoot . 'system/ee/installer/updates/' . $this->updateFile;

        if (!file_exists($sourceFile)) {
            echo "Warning: Source update file not found: {$sourceFile}\n";
            return;
        }

        if (file_exists($destFile)) {
            echo "Update file already exists: {$this->updateFile}\n";
            return;
        }

        echo ($this->dryRun ? "Would create update file: {$this->updateFile}\n" : "Creating update file: {$this->updateFile}... ");

        if (!$this->dryRun) {
            $content = file_get_contents($sourceFile);

            // Replace version references
            $versionParts = explode('.', $this->version);
            $underscoreVersion = implode('_', $versionParts);
            $content = str_replace('6_2_3', $underscoreVersion, $content);

            file_put_contents($destFile, $content);
            echo "✓\n";
        } else {
            echo "\n";
        }
    }

    private function prompt($message)
    {
        echo $message;
        $handle = fopen('php://stdin', 'r');
        $input = trim(fgets($handle));
        fclose($handle);

        // Basic input validation - remove any control characters
        $input = filter_var($input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

        // Limit input length to prevent issues
        if (strlen($input) > 100) {
            throw new Exception("Input too long");
        }

        return $input;
    }

    private function confirm($message)
    {
        $response = strtolower($this->prompt($message . ' [y/N]: '));
        return in_array($response, ['y', 'yes']);
    }

    private function showHelp()
    {
        echo "ExpressionEngine Version Bumper\n\n";
        echo "Usage:\n";
        echo "  php make_version.php [options]\n\n";
        echo "Options:\n";
        echo "  -v, --version <version>     Version to set (e.g., 7.5.18)\n";
        echo "  -d, --date <YYYY-MM-DD>     Release date (used for build number)\n";
        echo "  --skip-update-file          Skip creating installer update file\n";
        echo "  --dry-run                   Show what would be changed without making changes\n";
        echo "  --root <path>               Repository root path (defaults to script dir/../)\n";
        echo "  -h, --help                  Show this help\n\n";
        echo "Examples:\n";
        echo "  php make_version.php\n";
        echo "  php make_version.php -v 7.5.18 -d 2025-11-05\n";
        echo "  php make_version.php --version=7.5.18 --date=2025-11-05 --skip-update-file\n";
        echo "  php make_version.php --dry-run -v 7.5.18 -d 2025-11-05\n";
    }
}

try {
    $bumper = new VersionBumper();
    $bumper->run();
} catch (Exception $e) {
    // Sanitize error messages to prevent information leakage
    $message = $e->getMessage();

    // Remove any potential file paths or sensitive information from error messages
    $message = preg_replace('/[\/\\\\][^\/\s]*/', '[FILTERED]', $message);

    echo "Error: " . $message . "\n";
    exit(1);
}
