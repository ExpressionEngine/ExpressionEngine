<?php

use ExpressionEngine\Addons\Pro\Service\Prolet\AbstractProlet;
use ExpressionEngine\Addons\Pro\Service\Prolet\InitializableProletInterface;

class {{addon}}_pro extends AbstractProlet implements InitializableProletInterface
{
    protected $name = '{{name}}';

    public function index()
    {
        return 'This is a new prolet generated from the CLI.';
    }
}
