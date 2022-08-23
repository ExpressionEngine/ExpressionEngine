<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Addon;

use ExpressionEngine\Service\Addon\Controllers\Controller;
use ExpressionEngine\Library\String\Str;
use ExpressionEngine\Service\Addon\Exceptions\ControllerException;

class Mcp extends Controller
{
    /**
     * @var string
     */
    protected $action = '';

    /**
     * @var string
     */
    protected $id = '';

    /**
     * @param string $domain
     * @return Controllers\Mcp\AbstractRoute|null
     * @throws ControllerException
     */
    protected function process($domain)
    {
        $object = $this->buildObject($domain);
        if (class_exists($object)) {
            $controller = new $object();
            if ($controller instanceof Controllers\Mcp\AbstractRoute) {
                return $controller->setAddonName($this->getAddonName())->process($this->id);
            }
        }

        return null;
    }

    /**
     * @param string $domain
     * @param array $params
     * @return array|void
     * @throws ControllerException
     */
    public function route($domain, array $params = [])
    {
        $this->parseParams($params);
        $route = $this->process($domain);
        if ($route instanceof Controllers\Mcp\AbstractRoute) {
            return $route->toArray();
        }

        show_404();
    }

    /**
     * @param array $params
     * @return $this
     */
    protected function parseParams(array $params)
    {
        if (!empty($params['0'])) {
            if (!is_numeric($params['0'])) {
                $this->action = $params['0'];
            } else {
                $this->id = $params['0'];
            }
        }

        if (isset($params['1']) && $params['1'] != '') {
            $this->id = $params['1'];
        }

        return $this;
    }

    /**
     * @param string $domain
     * @return string
     * @throws ControllerException
     */
    protected function buildObject($domain)
    {
        $object = '\\' . $this->getRouteNamespace() . '\\Mcp\\' . Str::studly($domain);

        if ($this->action) {
            $stub = '\\' . Str::studly($this->action);
            if (class_exists($object . $stub)) {
                $object = $object . $stub;
            } else {
                $this->id = $this->action;
                $this->action = null;
            }
        }

        return $object;
    }
}
