<?php

declare (strict_types=1);
namespace ExpressionEngine\Dependency\League\MimeTypeDetection;

interface ExtensionToMimeTypeMap
{
    public function lookupMimeType(string $extension) : ?string;
}
