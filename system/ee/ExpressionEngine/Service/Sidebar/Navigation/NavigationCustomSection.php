<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Sidebar\Navigation;

use ExpressionEngine\Service\View\ViewFactory;

/**
 * Sidebar NavigationCustomSection
 */
class NavigationCustomSection extends NavigationSection
{
    public function __construct()
    {
        parent::__construct(lang('custom'), 'custom');
    }

    public function render(ViewFactory $view)
    {
        $custom = ee('CP/CustomMenu');

        $byclass = array();
        $active = ee()->extensions->active_hook('cp_custom_menu');
        $hooks = ee()->extensions->get_active_hook_info('cp_custom_menu') ?: array();

        foreach ($hooks as $priority => $calls) {
            foreach ($calls as $class => $metadata) {
                $byclass[$class][] = $metadata;
            }
        }

        $args = array($custom);
        $items = ee('Model')->get('MenuItem')
            ->fields('MenuItem.*', 'Children.*')
            ->with(array('Set' => 'RoleSettings'), 'Children')
            ->filter('RoleSettings.role_id', ee()->session->userdata('role_id'))
            ->order('MenuItem.sort')
            ->order('Children.sort')
            ->all();

        foreach ($items as $item) {
            if ($active && $item->type == 'addon' && isset($byclass[$item->data])) { //extension
                foreach ($byclass[$item->data] as $metadata) {
                    ee()->extensions->call_class($item->data, 'cp_custom_menu', $metadata, $args);
                }
            } elseif ($item->type == 'addon') { //module
                $custom->addItem($item->name, ee('CP/URL')->make('addons/settings/' . lcfirst($item->data)));
            } elseif ($item->type == 'submenu') {
                $sub = $custom->addSubmenu($item->name);

                foreach ($item->Children as $child) {
                    $sub->addItem($child->name, $child->data);
                }
            } elseif ($item->parent_id == 0) {
                $custom->addItem($item->name, $item->data);
            }
        }

        return $view->make('_shared/sidebar/navigation/custom')
            ->render(array(
                'custom' => $custom,
                'header' => $this->header,
                'class_suffix' => $this->class_suffix
            ));
    }
}

// EOF
