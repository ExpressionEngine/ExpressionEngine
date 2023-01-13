<?php

namespace {{namespace}}\Tags;

use ExpressionEngine\Service\Addon\Controllers\Tag\AbstractRoute;

class {{TagName}} extends AbstractRoute
{
    // Example tag: {exp:{{slug}}:{{tag_name}}}
    public function process()
    {
        return "My tag";
    }
}
