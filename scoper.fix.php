<?php

/**
 *
 * PHP-Scoper also can not handle Composers static file autoloaders.
 * This is due to Composer loading files based on a hash which is generated from package name and relative file path.
 * For a workaround see #298. https://github.com/humbug/php-scoper/blob/master/docs/limitations.md#composer-autoloader
 *
 * Script adapted from https://github.com/humbug/php-scoper/issues/298#issuecomment-525700081
 *
 * This helper is needed to "trick" composer autoloader to load the prefixed files
 * Otherwise if a dependency contains the same libraries ( i.e. guzzle ) it won't
 * load the files, as the file hash is the same and thus composer would think this was already loaded
 *
 * More information also found here: https://github.com/humbug/php-scoper/issues/298
 */
$composer_path = './system/ee/vendor-build/composer';
$static_loader_path = $composer_path . '/autoload_static.php';
$hash_prefix = 'ee';
echo "Fixing $static_loader_path \n";
$static_loader = file_get_contents($static_loader_path);
$static_loader = \preg_replace('/\'([A-Za-z0-9]*?)\' => __DIR__ \. (.*?),/', '\''.$hash_prefix.'$1\' => __DIR__ . $2,', $static_loader);
file_put_contents($static_loader_path, $static_loader);
$files_loader_path = $composer_path . '/autoload_files.php';
echo "Fixing $files_loader_path \n";
$files_loader = file_get_contents($files_loader_path);
$files_loader = \preg_replace('/\'(.*?)\' => (.*?),/', '\'' . $hash_prefix . '$1\' => $2,', $files_loader);
file_put_contents($files_loader_path, $files_loader);
