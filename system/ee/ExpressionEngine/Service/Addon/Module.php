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
use ExpressionEngine\Service\Addon\Controllers\Action\AbstractRoute as ActionRoute;
use ExpressionEngine\Service\Addon\Controllers\Tag\AbstractRoute as TagRoute;
use ExpressionEngine\Service\Addon\Exceptions\ControllerException;
use ExpressionEngine\Library\String\Str;

class Module extends Controller
{
    /**
     * Checks if we have an Action based request
     * @param string $method
     * @return bool
     */
    protected function isActRequest($method)
    {
        return (REQ == 'ACTION') && (ee('LivePreview')->hasEntryData() === false);
    }

    /**
     * @param string $method
     * @return mixed
     */
    protected function routeAction($method)
    {
        $object = $this->buildObject($method, true);

        return $this->route($object);
    }

    /**
     * @param string $method
     * @return mixed
     * @throws ControllerException
     */
    protected function routeTag($method)
    {
        $object = $this->buildObject($method);

        return $this->route($object);
    }

    /**
     * @param string $object
     * @return mixed
     * @throws ControllerException
     */
    protected function route($object)
    {
        if (class_exists($object)) {
            $controller = new $object();
            if ($controller instanceof ActionRoute) {
                return $controller->process();
            }

            if ($controller instanceof TagRoute) {
                return $controller->process();
            }
        }

        throw new ControllerException("Invalid Module request! Are you sure $object is setup properly?");
    }

    /**
     * @param string $method
     * @param bool $action
     * @return string
     * @throws ControllerException
     */
    protected function buildObject($method, $action = false, $useModuleFolder = true)
    {
        $object = '\\' . $this->getRouteNamespace();

        // If we're using the old module folder method
        if ($useModuleFolder) {
            $object .= '\\Module';
        }

        if ($action) {
            $object .= '\\Actions\\';
        } else {
            $object .= '\\Tags\\';
        }
        $object .= Str::studly($method);

        // If we cant find the old location in the modules fodler, try the new location
        // without the modules folder. This is done in this order to the error message shows the new way
        if (! class_exists($object) && $useModuleFolder) {
            return $this->buildObject($method, $action, false);
        }

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
        if ($this->isActRequest($method)) {
            return $this->routeAction($method, $params);
        }

        return $this->routeTag($method, $params);
    }
}
