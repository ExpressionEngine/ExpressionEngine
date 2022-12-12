<?php

namespace {{namespace}}\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class {{route_uc}} extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = '{{route}}';

    /**
     * @var string
     */
    protected $cp_page_title = '{{route_uc}}';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false)
    {
        $this->addBreadcrumb('{{route}}', '{{route_uc}}');

        $variables = [
            'name' => 'My Name',
            'color' => 'Green'
        ];

        $this->setBody('{{view}}', $variables);

        return $this;
    }
}
