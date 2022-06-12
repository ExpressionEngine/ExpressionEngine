<?php

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
     */
    protected function process(string $domain): ? Controllers\Mcp\AbstractRoute
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
     */
    public function route(string $domain, array $params = [])
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
    protected function parseParams(array $params): Mcp
    {
        if (!empty($params['0'])) {
            if(!is_numeric($params['0'])) {
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
    protected function buildObject(string $domain): string
    {
        if ($this->getRouteNamespace() == '') {
            throw new ControllerException("Your Controller Namespace isn't setup yet!");
        }

        $object = '\\'.$this->getRouteNamespace().'\\Mcp\\' . Str::studly($domain);

        if ($this->action) {
            $stub = '\\' . Str::studly($this->action);
            if(class_exists($object.$stub)) {
                $object = $object.$stub;
            } else {
                $this->id = $this->action;
                $this->action = null;
            }
        }

        return $object;
    }
}
