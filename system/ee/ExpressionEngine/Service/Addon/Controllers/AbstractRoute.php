<?php
namespace ExpressionEngine\Service\Addon\Controllers;

use ExpressionEngine\Service\Addon\Exceptions\Controllers\RouteException;

abstract class AbstractRoute
{
    /**
     * The shortname for the add-on this is attached to
     * @var string
     */
    protected $module_name = '';

    /**
     * @return string
     * @throws RouteException
     */
    protected function getModuleName(): string
    {
        if ($this->module_name == '') {
            throw new RouteException("Your `module_name` property hasn't been setup!");
        }

        return $this->module_name;
    }
}
