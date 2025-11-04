<?php

/**
 * ExpressionEngine Version Bumper
 *
 * Bumps the version number across ExpressionEngine core files and creates
 * installer update files as needed.
 *
 * Usage:
 *   php make_version.php [--version=<version>] [--date=<YYYY-MM-DD>] [--skip-update-file] [--root=<path>]
 *   php make_version.php --update-build-date --date=<YYYY-MM-DD> [other options]
 *
 * Options:
 *   -v, --version           Version to set (e.g., 7.5.18)
 *   -d, --date              Release date in YYYY-MM-DD format (used for build number)
 *   --update-build-date     Update only the build number for current version (reads version from files)
 *   --skip-update-file      Skip creating installer update file
 *   --dry-run               Show what would be changed without making changes
 *   --root                  Repository root path (defaults to script dir/../)
 *   -h, --help              Show this help
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
    private $updateBuildDateOnly = false;

    private $filesToUpdate = [
        'system/ee/legacy/libraries/Core.php',
        'system/ee/installer/controllers/wizard.php',
        'system/ee/ExpressionEngine/Tests/bootstrap.php',
        'tests/cypress/support/config/config.php'
    ];

    public function __construct()
    {
        $this->checkBasicSecurity();
        $this->parseArguments();
        $this->checkRepositorySecurity();
        $this->validateInputs();
    }

    private function checkBasicSecurity()
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

    private function checkRepositorySecurity()
    {
        // Ensure we're in a git repository for safety
        if (!is_dir($this->repoRoot . '/.git')) {
            throw new Exception("This script must be run from within a git repository");
        }

        // Check that we have write permissions to the repository
        if (!is_writable($this->repoRoot)) {
            throw new Exception("No write permissions to repository directory: {$this->repoRoot}");
        }
    }

    private function parseArguments()
    {
        $options = getopt('v:d:r:h', [
            'version:',
            'date:',
            'root:',
            'skip-update-file',
            'update-build-date',
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

        // Update build date only flag
        $this->updateBuildDateOnly = isset($options['update-build-date']);

        // If updating build date only, read current version from files
        if ($this->updateBuildDateOnly) {
            $this->readCurrentVersion();
            $this->skipUpdateFile = true; // Don't create update file for build-only updates
        }

        // Prompt for missing inputs
        if (empty($this->version)) {
            $this->version = $this->prompt('Enter version (e.g., 7.5.18): ');
        }

        if (empty($date)) {
            $date = $this->prompt('Enter release date (YYYY-MM-DD): ');
        }

        // Parse version and date
        if (!$this->updateBuildDateOnly) {
            $this->parseVersion($this->version);
        }
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

    private function readCurrentVersion()
    {
        $coreFile = $this->repoRoot . 'system/ee/legacy/libraries/Core.php';

        if (!file_exists($coreFile)) {
            throw new Exception("Cannot read current version: Core.php not found at {$coreFile}");
        }

        $content = file_get_contents($coreFile);

        // Read current APP_VER
        if (preg_match("/define\('APP_VER',\s+'([^']*)'\);/", $content, $matches)) {
            $this->version = $matches[1];
        } else {
            throw new Exception("Could not find APP_VER in Core.php");
        }

        // Read current APP_VER_ID
        if (preg_match("/define\('APP_VER_ID',\s+'([^']*)'\);/", $content, $matches)) {
            $this->identifier = $matches[1];
        } else {
            $this->identifier = '';
        }

        // Parse the version to extract components
        if (!preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $this->version, $matches)) {
            throw new Exception("Invalid current version format in Core.php: {$this->version}");
        }

        // Validate version components are reasonable
        if ($matches[1] > 99 || $matches[2] > 99 || $matches[3] > 99) {
            throw new Exception("Version numbers in Core.php exceed reasonable range");
        }
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
        if (!$this->updateBuildDateOnly) {
            echo "Update file: {$this->updateFile}\n";
        }
        echo "Repository root: {$this->repoRoot}\n";
        echo "Mode: " . ($this->dryRun ? 'DRY RUN (no changes will be made)' : 'LIVE') . "\n";
        echo "Operation: " . ($this->updateBuildDateOnly ? 'Build date update only' : 'Full version bump') . "\n\n";

        $action = $this->updateBuildDateOnly ? "build date update" : "version bump";
        if (!$this->confirm("Proceed with {$action}?")) {
            echo "Aborted.\n";
            exit(0);
        }

        $this->updateFiles();
        $this->createUpdateFile();

        $action = $this->updateBuildDateOnly ? "build date update" : "version bump";
        echo "\n" . ($this->dryRun ? "Dry run completed. No changes were made." : "{$action} completed successfully!") . "\n";
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

            // Security check: ensure we can write to the file
            if (!$this->dryRun && !is_writable($fullPath)) {
                echo "Error: No write permission for {$file}\n";
                continue;
            }

            echo ($this->dryRun ? "Checking {$file}... " : "Updating {$file}... ");
            $updated = false;
            $changes = [];

            $content = file_get_contents($fullPath);

            // Store original checksum for integrity checking
            $originalChecksum = md5($content);

            // Skip version updates in build-date-only mode
            if (!$this->updateBuildDateOnly) {
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
            }

            // Always update APP_BUILD (this is what build-only mode does)
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

            // Skip version-related updates in build-date-only mode
            if (!$this->updateBuildDateOnly) {
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
            }

            if ($updated) {
                if ($this->dryRun) {
                    echo "Would change:\n";
                    foreach ($changes as $change) {
                        echo "  - {$change}\n";
                    }
                } else {
                    // Atomic file operation: write to temp file then rename
                    $this->writeFileAtomically($fullPath, $content, $originalChecksum);
                    echo "✓\n";
                }
            } else {
                echo "No changes needed\n";
            }
        }
    }


    private function writeFileAtomically($filePath, $content, $originalChecksum)
    {
        // Create backup of original file (only if it exists)
        $fileExists = file_exists($filePath);
        if ($fileExists) {
            $backupPath = $filePath . '.backup.' . date('Y-m-d_H-i-s');
            if (!copy($filePath, $backupPath)) {
                throw new Exception("Failed to create backup of {$filePath}");
            }
        }

        // Write to temporary file first (atomic operation)
        $tempFile = $filePath . '.tmp.' . uniqid();
        $bytesWritten = file_put_contents($tempFile, $content);

        if ($bytesWritten === false) {
            unlink($tempFile); // Clean up temp file
            throw new Exception("Failed to write temporary file for {$filePath}");
        }

        // Verify the written content
        $writtenContent = file_get_contents($tempFile);
        $writtenChecksum = md5($writtenContent);

        if ($writtenChecksum !== md5($content)) {
            unlink($tempFile);
            throw new Exception("File integrity check failed for {$filePath}");
        }

        // Atomic rename (this is atomic on POSIX filesystems)
        if (!rename($tempFile, $filePath)) {
            unlink($tempFile);
            throw new Exception("Failed to rename temporary file to {$filePath}");
        }

        // Verify final file integrity
        $finalContent = file_get_contents($filePath);
        $finalChecksum = md5($finalContent);

        if ($finalChecksum !== md5($content)) {
            // Try to restore from backup (only if backup exists)
            if ($fileExists && isset($backupPath) && file_exists($backupPath)) {
                rename($backupPath, $filePath);
                throw new Exception("Final file integrity check failed for {$filePath}. Backup restored.");
            } else {
                // For new files, just remove the corrupted file
                unlink($filePath);
                throw new Exception("Final file integrity check failed for {$filePath}. File removed.");
            }
        }

        // Note: Backup file is kept for safety - can be cleaned up manually if needed
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
            // Security check: ensure destination directory is writable
            $destDir = dirname($destFile);
            if (!is_writable($destDir)) {
                echo "Error: No write permission for update file directory\n";
                return;
            }

            $content = file_get_contents($sourceFile);

            // Replace version references
            $versionParts = explode('.', $this->version);
            $underscoreVersion = implode('_', $versionParts);
            $content = str_replace('6_2_3', $underscoreVersion, $content);

            // Use atomic write for update file too (no original checksum for new files)
            $this->writeFileAtomically($destFile, $content, null);
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
        echo "  -v, --version <version>         Version to set (e.g., 7.5.18)\n";
        echo "  -d, --date <YYYY-MM-DD>         Release date (used for build number)\n";
        echo "  --update-build-date             Update only the build number for current version\n";
        echo "  --skip-update-file              Skip creating installer update file\n";
        echo "  --dry-run                       Show what would be changed without making changes\n";
        echo "  --root <path>                   Repository root path (defaults to script dir/../)\n";
        echo "  -h, --help                      Show this help\n\n";
        echo "Examples:\n";
        echo "  php make_version.php\n";
        echo "  php make_version.php -v 7.5.18 -d 2025-11-05\n";
        echo "  php make_version.php --version=7.5.18 --date=2025-11-05 --skip-update-file\n";
        echo "  php make_version.php --dry-run -v 7.5.18 -d 2025-11-05\n";
        echo "  php make_version.php --update-build-date -d 2025-12-25\n";
        echo "  php make_version.php --update-build-date --dry-run -d 2025-12-25\n";
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
