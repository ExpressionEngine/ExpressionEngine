<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Pro\Service\Dashboard;

use ExpressionEngine\Addons\Pro\Model\Dashboard\DashboardWidget;

/**
 * Abstract Dashboard Widget class
 */
abstract class AbstractDashboardWidget implements DashboardWidgetInterface
{
    public $title;
    public $right_head = '';
    public $width = 'half';
    public $class = '';
    public $content = '';

    public $vars;

    public function __construct(DashboardWidget $widgetObject, $edit_mode, $enabled)
    {
        ee()->lang->loadfile('homepage');
        $this->vars = [
            'title' => $this->getTitle(),
            'width' => $this->getWidth(),
            'class' => $this->getClass(),
            'right_head' => $this->getRightHead(),
            'edit_mode' => $edit_mode,
            'enabled' => $enabled,
            'widget_id' => $widgetObject->widget_id,
            'widget' => $this->getContent()
        ];
    }

    public function getTitle()// : string
    {
        return $this->title;
    }

    public function getWidth()// : string
    {
        return $this->width;
    }

    public function getClass()// : string
    {
        return $this->class;
    }

    public function getContent()// : string
    {
        return $this->content;
    }

    public function getRightHead()// : string
    {
        return $this->right_head;
    }

    public function getHtml()// : string
    {
        if (!$this->vars['edit_mode'] && empty($this->vars['widget'])) {
            return '';
        }
        return ee('View')->make('pro:dashboard/widget')->render($this->vars);
    }
}
