<?php

namespace {{namespace}}\ControlPanel;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractSidebar;

class Sidebar extends AbstractSidebar
{
    // Automatically generate the sidebar using the add-on's Mcp routes
    public $automatic = true;

    // Main sidebar optional header
    public $header = 'Sidebar Title';

    public function process()
    {
        // Get the CP/Sidebar object for manual sidebar adjustments
        // $this->getSidebar();
    }
}
