<?php

namespace ExpressionEngine\Library\Filesystem\Adapter;

use ExpressionEngine\Dependency\League\Flysystem;

class Local extends Flysystem\Adapter\Local {

    /**
     * Constructor.
     *
     * @param string $root
     * @param int    $writeFlags
     * @param int    $linkHandling
     * @param array  $permissions
     *
     * @throws \LogicException
     */
    public function __construct($settings)
    {
        $root = $settings['path'];
        $writeFlags = \LOCK_EX;
        $linkHandling = self::DISALLOW_LINKS;
        $permissions = [];

        $root = \is_link($root) ? \realpath($root) : $root;
        $this->permissionMap = \array_replace_recursive(static::$permissions, $permissions);

        // Overriding parent constructor to remove this behavior of creating the root if it does not exist
        // $this->ensureDirectory($root);

        if (!\is_dir($root) || !\is_readable($root)) {
            throw new \LogicException('The root path ' . $root . ' is not readable.');
        }
        $this->setPathPrefix($root);
        $this->writeFlags = $writeFlags;
        $this->linkHandling = $linkHandling;

    }

}