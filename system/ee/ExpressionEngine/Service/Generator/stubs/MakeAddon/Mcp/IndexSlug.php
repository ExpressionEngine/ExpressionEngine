<?php

namespace {{namespace}}\Mcp;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Index extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = 'index';

    /**
     * @var string
     */
    protected $cp_page_title = 'home';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'Home');

        $variables = [
            'name' => 'Matt',
            'color' => 'Green'
        ];

        $this->setBody('McpIndex', $variables);

        return $this;
    }
}
