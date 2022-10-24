<?php

namespace ExpressionEngine\Addons\Pro;

use Composer\Script\Event;
use Composer\Util\Filesystem;
use Composer\InstalledVersions;

class Composer
{
    private static $filesystem;
    
    public static function postAutoloadDump(Event $event)
    {
        self::$filesystem = new Filesystem();
        self::$filesystem->remove('lib/');
        $packages = InstalledVersions::getInstalledPackages();
        foreach ($packages as $package) {
            if ($package == 'thecodingmachine/safe') {
                self::$filesystem->copy('vendor/' . $package . '/lib', 'lib/' . $package . '/lib');
                $files = [
                    'generated/strings.php',
                    'generated/array.php',
                    'generated/url.php',
                    'generated/Exceptions/StringsException.php',
                    'generated/Exceptions/ArrayException.php',
                    'generated/Exceptions/UrlException.php',
                ];
                self::copyFiles($package, $files);
            } elseif (file_exists('vendor/' . $package . '/src')) {
                self::$filesystem->copy('vendor/' . $package . '/src', 'lib/' . $package);
            } elseif (file_exists('vendor/' . $package)) {
                $files = scandir('vendor/' . $package);
                self::copyFiles($package, $files);
            }
        }
    }

    private static function copyFiles($package, $files)
    {
        foreach ($files as $file) {
            if (!in_array($file, ['.', '..', 'composer.json', '.github', 'README.md'])) {
                $newPath = $package . '/' . $file;
                if (!file_exists('lib/' . $newPath)) {
                    $newPathParts = explode('/', $newPath);
                    $path = '';
                    foreach ($newPathParts as $part) {
                        $path .= $part . '/';
                        if (!file_exists('lib/' . $path) && is_dir(('vendor/' . $path))) {
                            mkdir('lib/' . $path, 0775);
                        }
                    }
                }
                self::$filesystem->copy('vendor/' . $package . '/' . $file, 'lib/' . $package . '/' . $file);
            }
        }
    }
}
