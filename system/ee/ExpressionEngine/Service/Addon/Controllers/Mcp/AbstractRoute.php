<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Addon\Controllers\Mcp;

use ExpressionEngine\Service\Addon\Controllers\AbstractRoute as CoreAbstractRoute;
use ExpressionEngine\Service\Addon\Exceptions\Controllers\Mcp\RouteException;

abstract class AbstractRoute extends CoreAbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = '';

    /**
     * The Control Panel Heading text
     * @var string
     */
    protected $heading = '';

    /**
     * The raw HTML body for the Control Panel view
     * @var string
     */
    protected $body = ' ';

    /**
     * An array of urls => text for breadcrumbs
     * @var array
     */
    protected $breadcrumbs = [];

    /**
     * @var int
     */
    public $per_page = 25;

    /**
     * @var string
     */
    protected $base_url = '';

    /**
     * @var bool
     */
    protected $active_sidebar = false;

    /**
     * @var array
     */
    protected $sidebar_data = [];

    public function __construct()
    {
        if ($this->sidebar_data) {
            $this->generateSidebar();
        }
    }

    /**
     * @return AbstractRoute
     */
    abstract public function process($id = false);

    /**
     * @return string
     */
    public function getHeading()
    {
        return $this->heading;
    }

    /**
     * @param string $heading
     * @return $this
     */
    public function setHeading($heading)
    {
        $this->heading = $heading;
        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $view
     * @param array $variables
     * @return $this
     */
    public function setBody($view, array $variables = [])
    {
        $variables = $this->prepareBodyVars($variables);
        $this->body = ee('View')->make($this->addon_name . ':' . $view)->render($variables);
        return $this;
    }

    /**
     * Compiles some universal variables for use in views
     * @param array $variables
     */
    protected function prepareBodyVars(array $variables = [])
    {
        return array_merge([
            'cp_page_title' => $this->getHeading(),
            'base_url' => $this->getBaseUrl(),
        ], $variables);
    }

    /**
     * @return array
     */
    public function getBreadcrumbs()
    {
        return $this->breadcrumbs;
    }

    /**
     * @param string $url
     * @param string $text
     * @return $this
     */
    protected function addBreadcrumb($url, $text)
    {
        $this->breadcrumbs[$this->url($url, true)] = lang($text);
        return $this;
    }

    /**
     * @param array $breadcrumbs
     * @return $this
     */
    protected function setBreadcrumbs(array $breadcrumbs = [])
    {
        $this->breadcrumbs = $breadcrumbs;
        return $this;
    }

    /**
     * @param string $path
     * @param bool $with_base
     * @param array $query
     * @return mixed
     */
    protected function url($path, $with_base = true, $query = [])
    {
        if ($with_base) {
            $path = $this->getBaseUrl() . '/' . $path;
        }

        return ee('CP/URL')->make($path, $query)->compile();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'heading' => lang($this->getHeading()),
            'breadcrumb' => $this->getBreadcrumbs(),
            'body' => $this->getBody(),
        ];
    }

    /**
     * @param mixed $id
     * @return string
     * @throws RouteException
     */
    protected function getRoutePath($id = '')
    {
        if ($this->route_path == '') {
            throw new RouteException("Your route_path property isn't setup in your Route object!");
        }

        return $this->route_path . ($id !== false && $id != '' ? '/' . $id : '');
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        if ($this->base_url == '') {
            $this->base_url = 'addons/settings/' . $this->getAddonName();
        }

        return $this->base_url;
    }

    /**
     * @throws RouteException
     */
    protected function generateSidebar()
    {
        $this->sidebar = ee('CP/Sidebar')->make();
        $active = false;
        foreach ($this->sidebar_data as $title => $sidebar) {
            if ($sidebar['path'] != '') {

                $subsHeader = $this->sidebar
                    ->addHeader(lang($title), $this->url($sidebar['path']));
            } else {

                $subsHeader = $this->sidebar
                    ->addHeader(lang($title));
            }
            if (isset($sidebar['list']) && is_array($sidebar['list'])) {
                $subsHeaderList = $subsHeader->addBasicList();
                foreach ($sidebar['list'] as $title => $url) {
                    if ($this->active_sidebar == $url && !$active) {
                        $subsHeaderList->addItem(lang($title), $this->url($url))->isActive();
                        $active = true;
                    } else if ($url == $this->getRoutePath() && !$active) {
                        $subsHeaderList->addItem(lang($title), $this->url($url))->isActive();
                        $active = true;
                    } else {
                        $subsHeaderList->addItem(lang($title), $this->url($url));
                    }
                }
            }
        }
    }
}
