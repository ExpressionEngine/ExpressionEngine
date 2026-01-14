<?php

/**
 * ExpressionEngine Copyright Year Updater
 *
 * Updates copyright year ranges in source code headers across the repository.
 *
 * Usage:
 *   php update_copyright.php [--year=<YYYY>] [--path=<path>] [--dry-run] [--help]
 *
 * Options:
 *   -y, --year              Target year (defaults to current system year)
 *   -p, --path              Root directory to scan (defaults to repo root)
 *   --dry-run               Show what would be changed without making changes
 *   -h, --help              Show this help
 */

class CopyrightUpdater
{
    private $targetYear = '';
    private $repoRoot = '';
    private $dryRun = false;

    private $allowedExtensions = [
        'php', 'js', 'ts', 'tsx', 'jsx', 'es6',
        'css', 'scss', 'less', 'html'
    ];

    private $excludedDirs = [
        'vendor',
        'node_modules',
        'vendor-build',
        '.git',
        'build',
        'dist'
    ];

    private $filesUpdated = 0;
    private $filesSkipped = 0;
    private $filesChecked = 0;

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
        $options = getopt('y:p:h', [
            'year:',
            'path:',
            'dry-run',
            'help'
        ]);

        if (isset($options['h']) || isset($options['help'])) {
            $this->showHelp();
            exit(0);
        }

        // Set repo root
        $this->repoRoot = isset($options['path']) ? $options['path'] : dirname(__DIR__);
        if (isset($options['p'])) {
            $this->repoRoot = $options['p'];
        }
        $this->repoRoot = rtrim($this->repoRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Set target year
        if (isset($options['y'])) {
            $this->targetYear = $options['y'];
        } elseif (isset($options['year'])) {
            $this->targetYear = $options['year'];
        } else {
            $this->targetYear = date('Y');
        }

        // Dry run flag
        $this->dryRun = isset($options['dry-run']);
    }

    private function validateInputs()
    {
        if (!is_dir($this->repoRoot)) {
            throw new Exception("Repository root does not exist: {$this->repoRoot}");
        }

        // Prevent path traversal attacks
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

        // Validate year format
        if (!preg_match('/^\d{4}$/', $this->targetYear)) {
            throw new Exception("Invalid year format. Expected YYYY");
        }

        $yearInt = (int)$this->targetYear;
        if ($yearInt < 2000 || $yearInt > 2100) {
            throw new Exception("Year must be between 2000 and 2100");
        }
    }

    public function run()
    {
        echo "ExpressionEngine Copyright Year Updater\n";
        echo "========================================\n\n";

        echo "Target year: {$this->targetYear}\n";
        echo "Repository root: {$this->repoRoot}\n";
        echo "Mode: " . ($this->dryRun ? 'DRY RUN (no changes will be made)' : 'LIVE') . "\n\n";

        if (!$this->confirm("Proceed with copyright update?")) {
            echo "Aborted.\n";
            exit(0);
        }

        echo ($this->dryRun ? "Scanning files (dry run)...\n" : "Scanning and updating files...\n\n");

        $this->scanDirectory($this->repoRoot);

        echo "\n";
        echo "Summary:\n";
        echo "  Files checked: {$this->filesChecked}\n";
        echo "  Files updated: {$this->filesUpdated}\n";
        echo "  Files skipped: {$this->filesSkipped}\n";

        if ($this->dryRun) {
            echo "\nDry run completed. No changes were made.\n";
        } else {
            echo "\nCopyright update completed successfully!\n";
        }
    }

    private function scanDirectory($dir)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $this->processFile($file->getPathname());
            }
        }
    }

    private function processFile($filePath)
    {
        // Check if file is in excluded directory
        $relativePath = str_replace($this->repoRoot, '', $filePath);
        foreach ($this->excludedDirs as $excludedDir) {
            if (strpos($relativePath, $excludedDir . DIRECTORY_SEPARATOR) === 0 ||
                strpos($relativePath, DIRECTORY_SEPARATOR . $excludedDir . DIRECTORY_SEPARATOR) !== false) {
                return;
            }
        }

        // Check file extension
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return;
        }

        $this->filesChecked++;

        // Read file content
        $content = file_get_contents($filePath);
        if ($content === false) {
            echo "Warning: Could not read file: {$filePath}\n";
            $this->filesSkipped++;
            return;
        }

        // Pattern to match: Copyright (c) YYYY-YYYY, Packet Tide
        // Also handles variations like "Copyright (c) YYYY-YYYY, Packet Tide, LLC (https://...)"
        // Captures everything after "Packet Tide" until end of line or closing parenthesis
        $pattern = '/Copyright\s+\(c\)\s+(\d{4})-(\d{4}),\s+Packet\s+Tide([^\n\)]*)/i';
        
        $updated = false;
        $newContent = preg_replace_callback($pattern, function($matches) use (&$updated) {
            $startYear = $matches[1];
            $endYear = $matches[2];
            $rest = $matches[3]; // Everything after "Packet Tide"
            
            // Only update if the end year is different from target year
            if ($endYear !== $this->targetYear) {
                $updated = true;
                return "Copyright (c) {$startYear}-{$this->targetYear}, Packet Tide{$rest}";
            }
            
            return $matches[0];
        }, $content);

        if ($updated) {
            if ($this->dryRun) {
                echo "Would update: {$relativePath}\n";
            } else {
                // Check write permissions
                if (!is_writable($filePath)) {
                    echo "Warning: No write permission for {$relativePath}\n";
                    $this->filesSkipped++;
                    return;
                }

                // Atomic write
                $this->writeFileAtomically($filePath, $newContent);
                echo "Updated: {$relativePath}\n";
            }
            $this->filesUpdated++;
        } else {
            $this->filesSkipped++;
        }
    }

    private function writeFileAtomically($filePath, $content)
    {
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
            // Since we're in git, suggest using git to restore
            if (file_exists($filePath)) {
                throw new Exception("Final file integrity check failed for {$filePath}. Use 'git checkout {$filePath}' to restore.");
            } else {
                unlink($filePath);
                throw new Exception("Final file integrity check failed for {$filePath}. File removed.");
            }
        }
    }

    private function prompt($message)
    {
        echo $message;
        $handle = fopen('php://stdin', 'r');
        $input = trim(fgets($handle));
        fclose($handle);

        // Basic input validation - remove any control characters
        // FILTER_SANITIZE_STRING is deprecated in PHP 8.1+, use regex instead
        $input = preg_replace('/[\x00-\x1F\x7F]/', '', $input);

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
        echo "ExpressionEngine Copyright Year Updater\n\n";
        echo "Usage:\n";
        echo "  php update_copyright.php [options]\n\n";
        echo "Options:\n";
        echo "  -y, --year <YYYY>         Target year (defaults to current system year)\n";
        echo "  -p, --path <path>         Root directory to scan (defaults to repo root)\n";
        echo "  --dry-run                 Show what would be changed without making changes\n";
        echo "  -h, --help                Show this help\n\n";
        echo "Examples:\n";
        echo "  php update_copyright.php\n";
        echo "  php update_copyright.php -y 2025\n";
        echo "  php update_copyright.php --year=2025 --dry-run\n";
        echo "  php update_copyright.php -p /path/to/repo -y 2025\n";
    }
}

try {
    $updater = new CopyrightUpdater();
    $updater->run();
} catch (Exception $e) {
    // Sanitize error messages to prevent information leakage
    $message = $e->getMessage();

    // Remove any potential file paths or sensitive information from error messages
    $message = preg_replace('/[\/\\\\][^\/\s]*/', '[FILTERED]', $message);

    echo "Error: " . $message . "\n";
    exit(1);
}
