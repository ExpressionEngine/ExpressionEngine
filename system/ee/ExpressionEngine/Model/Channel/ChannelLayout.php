<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Channel;

use InvalidArgumentException;
use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Model\Content\Display\LayoutDisplay;
use ExpressionEngine\Model\Content\Display\LayoutInterface;
use ExpressionEngine\Model\Content\Display\LayoutTab;

/**
 * Channel Layout Model
 */
class ChannelLayout extends Model implements LayoutInterface
{
    protected static $_primary_key = 'layout_id';
    protected static $_table_name = 'layout_publish';

    protected static $_hook_id = 'channel_layout';

    protected static $_typed_columns = array(
        'field_layout' => 'serialized',
    );

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo'
        ),
        'Channel' => array(
            'type' => 'belongsTo',
            'key' => 'channel_id'
        ),
        'PrimaryRoles' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Role',
            'pivot' => array(
                'table' => 'layout_publish_member_roles',
                'key' => 'role_id',
            )
        ),
    );

    protected static $_validation_rules = array(
        'site_id' => 'required|isNatural',
        'channel_id' => 'required|isNatural',
        'layout_name' => 'required',
    );

    protected $layout_id;
    protected $site_id;
    protected $channel_id;
    protected $layout_name;
    protected $field_layout;

    public function transform(array $fields)
    {
        $display = new LayoutDisplay();

        // Fields known to the layout
        $layout = $this->getProperty('field_layout');
        foreach ($layout as $section) {
            // Tabs have 4 pieces of data: an id, a name, a list of fields,
            // and a visibility flag. If any of them are missing this is not
            // a tab (but visibility is non-essential so...) we'll skip it. This
            // is better than a PHP error.
            if (! (isset($section['id'])
                    && isset($section['name'])
                    && isset($section['fields']))
                ) {
                continue;
            }

            $tab = new LayoutTab($section['id'], $section['name']);

            // If they don't have 'visible' key we'll assume it is visible
            // and just move on.
            if (isset($section['visible']) && ! $section['visible']) {
                $tab->hide();
            }

            foreach ($section['fields'] as $field_info) {
                // If the field_info does'nt have a 'field' key, this isn't
                // really a field: skip it.
                if (empty($field_info) || ! isset($field_info['field'])) {
                    continue;
                }

                $field_id = $field_info['field'];

                // Looking for a field that is not there...skip it for now
                if (! array_key_exists($field_id, $fields)) {
                    continue;
                }

                $field = $fields[$field_id];

                //set the width of a field
                //if width isn't set then default is 100%
                if (isset($field_info['width'])) {
                    $field->setWidth($field_info['width']);
                } else {
                    $field->setWidth(100);
                }

                // Fields can be configured to start collapsed or expaned, but
                // a layout should always override it.
                if (isset($field_info['collapsed'])) {
                    if ($field_info['collapsed']) {
                        $field->collapse();
                    } else {
                        $field->expand();
                    }
                }

                // Visible is "optional" and defaults to "I can see you!"
                if (isset($field_info['visible']) && ! $field_info['visible']) {
                    $field->hide();
                }

                $tab->addField($field);

                unset($fields[$field_id]);
            }
            $display->addTab($tab);
        }

        // "New" (unknown) fields
        try {
            $publish_tab = $display->getTab('publish');
        } catch (InvalidArgumentException $e) {
            $publish_tab = new LayoutTab('publish', 'publish');
            $display->addTab($publish_tab);
        }

        try {
            $categories_tab = $display->getTab('categories');
        } catch (InvalidArgumentException $e) {
            $categories_tab = new LayoutTab('categories', 'categories');
            $display->addTab($categories_tab);
        }

        foreach ($fields as $field_id => $field) {
            if (strpos($field_id, 'categories[') === 0) {
                $tab = $categories_tab;
            } elseif (strpos($field_id, '__') === false) {
                $tab = $publish_tab;
            } else {
                list($tab_id, $garbage) = explode('__', $field_id);

                try {
                    $tab = $display->getTab($tab_id);
                } catch (InvalidArgumentException $e) {
                    $tab = new LayoutTab($tab_id, $tab_id);
                    $display->addTab($tab);
                }
            }

            $tab->addField($field);
        }

        return $display;
    }

    public function synchronize($fields = [])
    {
        $fields = ($fields) ?: $this->Channel->getAllCustomFields();
        $fields = $fields->indexBy('field_id');
        $seen = [];

        $field_layout = $this->field_layout;

        foreach ($field_layout as $i => $section) {
            if (!isset($section['fields']) || empty($section['fields'])) {
                continue;
            }
            foreach ($section['fields'] as $j => $field_info) {
                $field_name = isset($field_info['field']) ? $field_info['field'] : 0;

                if (strpos($field_name, 'field_id_') !== 0) {
                    continue;
                }

                $field_id = str_replace('field_id_', '', $field_name);

                if (array_key_exists($field_id, $fields)) {
                    unset($fields[$field_id]);
                }
                // Remove stale fields
                else {
                    unset($field_layout[$i]['fields'][$j]);
                }

                // Ensure fields are unique in the layout
                if (isset($seen[$field_name])) {
                    unset($field_layout[$i]['fields'][$j]);
                }

                $seen[$field_name] = true;
            }

            // Re-index to ensure flat, zero-indexed array
            $field_layout[$i]['fields'] = array_values($field_layout[$i]['fields']);
        }

        $this->setProperty('field_layout', $field_layout);

        foreach ($fields as $field) {
            $this->addField($field);
        }

        $this->save();
    }

    protected function addField($field)
    {
        $field_layout = $this->field_layout;
        $field_info = array(
            'field' => 'field_id_' . $field->field_id,
            'visible' => true,
            'collapsed' => $field->getProperty('field_is_hidden')
        );
        $field_layout[0]['fields'][] = $field_info;

        $this->setProperty('field_layout', $field_layout);
    }
}
