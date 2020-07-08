<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Library\Advisor;

class ChannelLayoutAdvisor
{

    public function getTabs()
    {
        $layouts = ee('Model')->get('ChannelLayout')->all();

        $tabs[] = array();
        foreach ($layouts as $layout) {
            $data = $layout->getValues();
            $tabs[$data['layout_name']] = array();
            foreach ($data['field_layout'] as $f_layout) {
                if (!isset($tabs[$data['layout_name']][$f_layout['id']]['count'])) {
                    $tabs[$data['layout_name']][$f_layout['id']]['count'] = 0;
                }
                $tabs[$data['layout_name']][$f_layout['id']]['count']++;

                foreach ($f_layout['fields'] as $field) {
                    $tabs[$data['layout_name']][$f_layout['id']]['fields'][] = $field['field'];
                }
            }
        }

        return $tabs;
    }

    public function getDuplicateTabs()
    {
        $layouts = $this->getTabs();
        foreach ($layouts as $lk => $layout) {
            foreach ($layout as $tk => $tab) {
                if ($tab['count'] <=1) {
                    unset($layouts[$lk][$tk]);
                }
            }
            if (! $layouts[$lk]) {
                unset($layouts[$lk]);
            }
        }

        return $layouts;
    }

    public function getDuplicateTabCount()
    {
        return count($this->getDuplicateTabs());
    }

}

// EOF
