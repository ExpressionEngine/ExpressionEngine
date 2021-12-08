<?php

use ExpressionEngine\Addons\Pro\Service\Prolet\AbstractProlet;
use ExpressionEngine\Addons\Pro\Service\Prolet\ProletInterface;

class {{addon}}_pro extends AbstractProlet implements ProletInterface
{
    protected $name = '{{name}}';

    public function index()
    {
        return 'This is a new prolet generated from the CLI.';
    }
}
