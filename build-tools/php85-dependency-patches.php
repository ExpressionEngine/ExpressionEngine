<?php

// Keep the pinned EE 7 dependencies compatible without raising their PHP floor.
// These patches are applied to each fresh, prefixed dependency build.
// The EE fork tips are already locked; upstream Flysystem 1.x/cached-adapter
// still need these fixes. BaconQrCode fixed GD cleanup in v3.0.2 (PHP 8.1+):
// https://github.com/Bacon/BaconQrCode/commit/bd2370dfd3cceeee8fe7e83f56b16bd1b2e70f0f
// Remove these backports when equivalent fixes reach the PHP 7-compatible forks.
return function ($filePath, $prefix, $contents) {
    $path = str_replace('\\', '/', $filePath);

    if (substr($path, -strlen('/expressionengine/flysystem/src/Adapter/Ftp.php')) === '/expressionengine/flysystem/src/Adapter/Ftp.php') {
        return str_replace('(boolean)', '(bool)', $contents);
    }

    if (substr($path, -strlen('/expressionengine/bacon-qr-code/src/Renderer/GDLibRenderer.php')) === '/expressionengine/bacon-qr-code/src/Renderer/GDLibRenderer.php') {
        $contents = preg_replace(
            '/\\\\?imagedestroy\(\$this->image\);/',
            "if (\\PHP_VERSION_ID < 80000) {\n            \\imagedestroy(\$this->image);\n        }",
            $contents
        );
        // The three-argument form was added in PHP 8.1; the old form is
        // deprecated on newer PHP versions.
        return preg_replace_callback('/\\\\?imagefilledpolygon\(\$this->image, \$points, (\$\w+)\);/', function ($match) {
            return 'if (\\PHP_VERSION_ID < 80100) { '
                . '\\imagefilledpolygon($this->image, $points, \\intdiv(\\count($points), 2), ' . $match[1] . '); '
                . '} else { \\imagefilledpolygon($this->image, $points, ' . $match[1] . '); }';
        }, $contents);
    }

    if (substr($path, -strlen('/expressionengine/flysystem/src/SafeStorage.php')) === '/expressionengine/flysystem/src/SafeStorage.php') {
        return str_replace('spl_object_hash(', 'spl_object_id(', $contents);
    }

    $nullableFiles = array(
        '/expressionengine/flysystem/src/FileExistsException.php',
        '/expressionengine/flysystem/src/FileNotFoundException.php',
        '/expressionengine/flysystem-cached-adapter/src/Storage/PhpRedis.php',
        '/expressionengine/flysystem-cached-adapter/src/Storage/Predis.php',
    );
    foreach ($nullableFiles as $file) {
        if (substr($path, -strlen($file)) === $file) {
            return strtr($contents, array(
                ', BaseException $previous = null' => ', ?BaseException $previous = null',
                '__construct(Redis $client = null' => '__construct(?Redis $client = null',
                '__construct(Client $client = null' => '__construct(?Client $client = null',
            ));
        }
    }

    return $contents;
};
