<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Sidebar;

use ExpressionEngine\Service\View\ViewFactory;

class Divider
{
    public function render(ViewFactory $view)
    {
        return $view->make('_shared/sidebar/divider')->render();
    }
}
