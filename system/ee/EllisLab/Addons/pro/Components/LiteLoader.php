<?php

namespace EllisLab\Addons\Pro\Components;

class LiteLoader
{
    public static function loadIntoNamespace($addon_file, $party='first', $namespace='Lite')
    {
        if ($party == 'first') {
            $file = PATH_ADDONS;
        } else {
            $file = PATH_THIRD;
        }

        $file .= $addon_file;

        if (file_exists($file)) {
            eval('namespace ' . $namespace . '?>; ' . file_get_contents($file));
        }
    }
}
