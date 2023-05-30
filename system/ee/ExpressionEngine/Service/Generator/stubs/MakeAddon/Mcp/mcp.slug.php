<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Service\Addon\Mcp;

class {{slug_uc}}_mcp extends Mcp
{
    protected $addon_name = '{{slug}}';
}
