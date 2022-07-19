<?php

namespace ExpressionEngine\Dependency\League\Flysystem;

use LogicException;
/**
 * Thrown when the MountManager cannot find a filesystem.
 */
class FilesystemNotFoundException extends LogicException implements FilesystemException
{
}
