<?php
namespace ExpressionEngine\Service\Addon;

use ExpressionEngine\Service\Addon\Controllers\Controller;
use ExpressionEngine\Service\Addon\Controllers\Action\AbstractRoute AS ActionRoute;
use ExpressionEngine\Service\Addon\Controllers\Tag\AbstractRoute AS TagRoute;
use ExpressionEngine\Service\Addon\Exceptions\ControllerException;
use ExpressionEngine\Library\String\Str;

class Module extends Controller
{
    /**
     * Checks if we have an Action based request
     * @param string $method
     * @return bool
     */
    protected function isActRequest(string $method): bool
    {
        return substr($method, -6) == 'action' && ee()->input->get_post('ACT');
    }

    /**
     * @param string $method
     * @return mixed
     */
    protected function routeAction(string $method)
    {
        $object = $this->buildObject($method, true);
        return $this->route($object);
    }

    /**
     * @param string $method
     * @return mixed
     * @throws ControllerException
     */
    protected function routeTag(string $method)
    {
        $object = $this->buildObject($method);
        return $this->route($object);
    }

    /**
     * @param string $object
     * @return mixed
     * @throws ControllerException
     */
    protected function route(string $object)
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
    protected function buildObject(string $method, bool $action = false): string
    {
        if(!$this->getRouteNamespace()){
            throw new ControllerException("Your Controller Namespace isn't setup yet!");
        }

        $object = '\\'.$this->getRouteNamespace().'\\Module\\';
        if($action) {
            $object .= 'Actions\\';
        } else {
            $object .= 'Tags\\';
        }

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
        if ($this->isActRequest($method)) {
            return $this->routeAction($method, $params);
        }

        return $this->routeTag($method, $params);
    }
}
