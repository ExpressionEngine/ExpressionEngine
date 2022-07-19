<?php

namespace ExpressionEngine\Dependency\League\Flysystem\Plugin;

use ExpressionEngine\Dependency\League\Flysystem\FilesystemInterface;
use ExpressionEngine\Dependency\League\Flysystem\PluginInterface;
abstract class AbstractPlugin implements PluginInterface
{
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;
    /**
     * Set the Filesystem object.
     *
     * @param FilesystemInterface $filesystem
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }
}
