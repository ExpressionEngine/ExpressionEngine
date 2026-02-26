<?php
/**
 * Test-only namespace overrides for Translate controller function calls.
 */

namespace ExpressionEngine\Controller\Utilities;

class TranslateTestFunctionOverrides
{
    /** @var bool|null */
    public static $isReallyWritable = null;

    /** @var bool|null */
    public static $isReadable = null;

    /** @var bool */
    public static $throwOnForceDownload = false;

    /** @var string|null */
    public static $tempnamPath = null;
}

if (! function_exists(__NAMESPACE__ . '\is_really_writable')) {
    function is_really_writable($path)
    {
        if (TranslateTestFunctionOverrides::$isReallyWritable !== null) {
            return TranslateTestFunctionOverrides::$isReallyWritable;
        }

        return \is_really_writable($path);
    }
}

if (! function_exists(__NAMESPACE__ . '\is_readable')) {
    function is_readable($path)
    {
        if (TranslateTestFunctionOverrides::$isReadable !== null) {
            return TranslateTestFunctionOverrides::$isReadable;
        }

        return \is_readable($path);
    }
}

if (! function_exists(__NAMESPACE__ . '\force_download')) {
    function force_download($filename, $data)
    {
        if (TranslateTestFunctionOverrides::$throwOnForceDownload) {
            throw new \RuntimeException('stop-before-exit');
        }

        return \force_download($filename, $data);
    }
}

if (! function_exists(__NAMESPACE__ . '\tempnam')) {
    function tempnam($directory, $prefix)
    {
        if (TranslateTestFunctionOverrides::$tempnamPath !== null) {
            return TranslateTestFunctionOverrides::$tempnamPath;
        }

        return \tempnam($directory, $prefix);
    }
}
