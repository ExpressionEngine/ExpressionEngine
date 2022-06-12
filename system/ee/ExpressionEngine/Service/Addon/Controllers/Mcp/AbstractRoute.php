<?php
namespace ExpressionEngine\Service\Addon\Controllers\Mcp;

use ExpressionEngine\Service\Addon\Controllers\AbstractRoute AS CoreAbstractRoute;
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
        $this->base_url = 'addons/settings/'.$this->getModuleName();

        if($this->sidebar_data) {
            $this->generateSidebar();
        }
    }

    /**
     * @return AbstractRoute
     */
    abstract public function process($id = false): AbstractRoute;

    /**
     * @return string
     */
    public function getHeading(): string
    {
        return $this->heading;
    }

    /**
     * @param string $heading
     * @return $this
     */
    public function setHeading(string $heading): AbstractRoute
    {
        $this->heading = $heading;
        return $this;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $view
     * @param array $variables
     * @return $this
     */
    public function setBody(string $view, array $variables = []): AbstractRoute
    {
        $variables = $this->prepareBodyVars($variables);
        $this->body = ee('View')->make($this->module_name.':'.$view)->render($variables);
        return $this;
    }

    /**
     * Compiles some universal variables for use in views
     * @param array $variables
     */
    protected function prepareBodyVars(array $variables = []): array
    {
        return array_merge([
            'cp_page_title' => $this->getHeading(),
            'base_url' => $this->base_url,
        ], $variables);
    }

    /**
     * @return array
     */
    public function getBreadcrumbs(): array
    {
        return $this->breadcrumbs;
    }

    /**
     * @param string $url
     * @param string $text
     * @return $this
     */
    protected function addBreadcrumb(string $url, string $text): AbstractRoute
    {
        $this->breadcrumbs[$url] = lang($text);
        return $this;
    }

    /**
     * @param array $breadcrumbs
     * @return $this
     */
    protected function setBreadcrumbs(array $breadcrumbs = []): AbstractRoute
    {
        $this->breadcrumbs = $breadcrumbs;
        return $this;
    }

    /**
     * @param $path
     * @param bool $with_base
     * @param array $query
     * @return mixed
     */
    protected function url(string $path, bool $with_base = true, array $query = []): string
    {
        if ($with_base) {
            $path = $this->base_url.'/'.$path;
        }

        return ee('CP/URL')->make($path, $query)->compile();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'heading' => lang($this->getHeading()),
            'breadcrumb' => $this->getBreadcrumbs(),
            'body' => $this->getBody(),
        ];
    }

    /**
     * @param string $id
     * @return string
     * @throws RouteException
     */
    protected function getRoutePath($id = ''): string
    {
        if ($this->route_path == '')
        {
            throw new RouteException("Your route_path property isn't setup in your Route object!");
        }

        return $this->route_path. ($id !== false && $id != '' ? '/'.$id : '');
    }

    /**
     * @throws RouteException
     */
    protected function generateSidebar(): void
    {
        $this->sidebar = ee('CP/Sidebar')->make();
        $active = false;
        foreach($this->sidebar_data AS $title => $sidebar)
        {
            if ($sidebar['path'] != '') {

                $subsHeader = $this->sidebar
                    ->addHeader(lang($title), $this->url($sidebar['path']));
            } else {

                $subsHeader = $this->sidebar
                    ->addHeader(lang($title));
            }
            if(isset($sidebar['list']) && is_array($sidebar['list'])) {
                $subsHeaderList = $subsHeader->addBasicList();
                foreach($sidebar['list'] AS $title => $url)
                {
                    if($this->active_sidebar == $url && !$active) {
                        $subsHeaderList->addItem(lang($title), $this->url($url))->isActive();
                        $active = true;
                    }
                    else if($url == $this->getRoutePath() && !$active) {
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
