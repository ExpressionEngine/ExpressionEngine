<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Dashboard;

use ExpressionEngine\Addons\Pro\Model\Dashboard\DashboardWidget;

/**
 * Dashboard Widget interface
 */
interface DashboardWidgetInterface
{
    public function __construct(DashboardWidget $widgetObject, Bool $edit_mode, Bool $enabled);

    public function getTitle();// : string;

    public function getRightHead();// : string;

    public function getWidth();// : string;

    public function getClass();// : string;

    public function getContent();// : string;

    public function getHtml();// : string;
}
