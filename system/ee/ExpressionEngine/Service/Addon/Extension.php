<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Addon;

use ExpressionEngine\Service\Addon\Controllers\Controller;
use ExpressionEngine\Library\String\Str;
use ExpressionEngine\Service\Addon\Controllers\Extension\AbstractRoute;
use ExpressionEngine\Service\Addon\Exceptions\ControllerException;

class Extension extends Controller
{
    public $version = '';

    /**
     * @param $method
     * @return string
     * @throws ControllerException
     */
    protected function buildObject($method)
    {
        $object = '\\' . $this->getRouteNamespace() . '\\Extensions\\';
        $object .= Str::studly($method);

        return $object;
    }

    /**
     * @param $method
     * @param $params
     * @return mixed
     * @throws ControllerException
     */
    public function __call($method, $params)
    {
        $object = $this->buildObject($method);

        if (class_exists($object)) {
            $controller = new $object();
            if ($controller instanceof AbstractRoute) {
                if (method_exists($controller, 'process')) {
                    return call_user_func_array([$controller, 'process'], $params);
                }
            }
        }

        throw new ControllerException("Invalid Extension request! Are you sure $object is setup properly?");
    }
}
