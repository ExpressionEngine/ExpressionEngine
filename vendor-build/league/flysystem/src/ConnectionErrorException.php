<?php

namespace ExpressionEngine\Dependency\League\Flysystem;

use ErrorException;
class ConnectionErrorException extends ErrorException implements FilesystemException
{
}
