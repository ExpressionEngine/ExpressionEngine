<?php

// Usage: php tests/ci/report-deprecations.php report.md php-errors.log [other-native-php.log ...]
if ($argc < 3) {
    fwrite(STDERR, "Usage: report-deprecations.php OUTPUT INPUT [INPUT ...]\n");
    exit(1);
}

$output = $argv[1];
$root = str_replace('\\', '/', dirname(__DIR__, 2)) . '/';
$categories = ['Application', 'Tests', 'Dependencies', 'Unclassified'];
$files = [];
$missing = [];
foreach (array_slice($argv, 2) as $input) {
    if (is_file($input)) {
        $files[] = $input;
    } else {
        $missing[] = $input;
    }
}

$findings = [];
foreach (array_unique($files) as $file) {
    foreach (new SplFileObject($file) as $line) {
        if (!is_string($line)) {
            continue;
        }
        if (!preg_match('/(?:PHP )?Deprecated:\s*(.+)/', $line, $match)) {
            continue;
        }

        $message = str_replace($root, '', str_replace('\\', '/', trim($match[1])));
        // Classify the reported source file, not class names or paths mentioned in the warning.
        $origin = 'Unclassified';
        if (preg_match('/^.* in (.+) on line \d+$/', $message, $location)) {
            $path = $location[1];
            if (preg_match('~(?:^|/)(?:vendor|vendor-build|vendor-bin)/~', $path)) {
                $origin = 'Dependencies';
            } elseif (preg_match('~(?:^|/)(?:system/ee/ExpressionEngine/Tests|tests)/~', $path)) {
                $origin = 'Tests';
            } elseif (preg_match('~(?:^|/)(?:system|public)/~', $path)
                || preg_match('~(?:^|/)(?:index|admin|wizard)\.php$~', $path)) {
                $origin = 'Application';
            }
        }
        $key = $origin . ': ' . $message;
        if (!isset($findings[$key])) {
            $findings[$key] = ['origin' => $origin, 'message' => $message, 'count' => 0];
        }
        $findings[$key]['count']++;
    }
}

uasort($findings, function ($left, $right) {
    return ($right['count'] <=> $left['count']) ?: strcmp($left['message'], $right['message']);
});

$escape = function ($value) {
    return str_replace('|', '&#124;', htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
};
$groups = array_fill_keys($categories, []);
foreach ($findings as $finding) {
    $groups[$finding['origin']][] = $finding;
}
$report = "## PHP " . PHP_VERSION . " deprecations\n\n";
$report .= "Advisory report; deprecations do not add a CI failure condition.\n\n";
$report .= count($files) . " log file(s) read; " . count($findings) . " distinct finding(s); ";
$report .= array_sum(array_column($findings, 'count')) . " occurrence(s).\n\n";
if ($missing !== []) {
    $report .= "Logs not created or unavailable: " . implode(', ', array_map($escape, $missing)) . ".\n\n";
}
$report .= "| Category | Distinct findings | Occurrences |\n| --- | ---: | ---: |\n";
foreach ($groups as $category => $group) {
    $report .= '| ' . $category . ' | ' . count($group) . ' | ';
    $report .= array_sum(array_column($group, 'count')) . " |\n";
}
foreach ($groups as $category => $group) {
    if ($group === []) {
        continue;
    }
    $report .= "\n### " . $category . "\n\n| Occurrences | Deprecation |\n| ---: | --- |\n";
    foreach ($group as $finding) {
        $report .= '| ' . $finding['count'] . ' | ';
        $report .= $escape($finding['message']) . " |\n";
    }
}
$report .= "\nCategories use PHP's reported source file. Application findings still need triage: ";
$report .= "a test double can trigger a deprecation inside application code. ";
$report .= "Bundled libraries outside vendor directories remain under Application. ";
$report .= "Unrecognized paths and messages without a source location are Unclassified. ";
$report .= "Counts reflect log records, which can repeat across requests or retries. ";
$report .= "See the raw logs for other PHP diagnostics and full context.\n";

if (file_put_contents($output, $report) === false) {
    exit(1);
}
if (getenv('GITHUB_STEP_SUMMARY')) {
    // Keep a large backlog within GitHub's per-step summary limit; the artifact is complete.
    $summary = strlen($report) <= 900000 ? $report
        : substr($report, 0, 900000) . "\n\nReport truncated; download the complete artifact.\n";
    if (file_put_contents(getenv('GITHUB_STEP_SUMMARY'), $summary, FILE_APPEND) === false) {
        exit(1);
    }
}
echo count($findings) . " distinct deprecation(s). Report: " . $output . "\n";
