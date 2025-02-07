<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Channel\Display;

use ExpressionEngine\Model\Content\Display\DefaultLayout;
use ExpressionEngine\Model\Content\Display\LayoutDisplay;
use ExpressionEngine\Model\Content\Display\LayoutTab;

/**
 * Default Channel Layout
 */
class DefaultChannelLayout extends DefaultLayout
{
    protected $channel_id;
    protected $entry_id;

    public function __construct($channel_id, $entry_id)
    {
        $this->channel_id = $channel_id;
        $this->entry_id = $entry_id;

        parent::__construct();
    }

    public function getDefaultTab()
    {
        return 'publish';
    }

    /**
     * This is what you'll want to be overriding, if anything
     */
    protected function createLayout()
    {
        $layout = array();

        $layout[] = array(
            'id' => 'publish',
            'name' => 'publish',
            'visible' => true,
            'fields' => array(
                array(
                    'field' => 'title',
                    'visible' => true,
                    'collapsed' => false,
                    'width' => 100
                ),
                array(
                    'field' => 'url_title',
                    'visible' => true,
                    'collapsed' => false,
                    'width' => 100
                )
            )
        );

        $channel = ee('Model')->get('Channel', $this->channel_id)->with('CategoryGroups')->all()->first();

        // Date Tab ------------------------------------------------------------

        $date_fields = array(
            array(
                'field' => 'entry_date',
                'visible' => true,
                'collapsed' => false,
                'width' => 100
            ),
            array(
                'field' => 'expiration_date',
                'visible' => true,
                'collapsed' => false,
                'width' => 100
            )
        );

        if (bool_config_item('enable_comments') && $channel->comment_system_enabled) {
            $date_fields[] = array(
                'field' => 'comment_expiration_date',
                'visible' => true,
                'collapsed' => false,
                'width' => 100
            );
        }

        $layout[] = array(
            'id' => 'date',
            'name' => 'date',
            'visible' => true,
            'fields' => $date_fields,
        );

        // Category Tab --------------------------------------------------------

        $category_group_fields = array();
        foreach ($channel->CategoryGroups as $cat_group) {
            $category_group_fields[] = array(
                'field' => 'categories[cat_group_id_' . $cat_group->getId() . ']',
                'visible' => true,
                'collapsed' => false,
                'width' => 100
            );
        }

        $layout[] = array(
            'id' => 'categories',
            'name' => 'categories',
            'visible' => true,
            'fields' => $category_group_fields
        );

        // Options Tab ---------------------------------------------------------

        $option_fields = array(
            array(
                'field' => 'channel_id',
                'visible' => true,
                'collapsed' => false,
                'width' => 100
            ),
            array(
                'field' => 'status',
                'visible' => true,
                'collapsed' => false,
                'width' => 100
            ),
            array(
                'field' => 'author_id',
                'visible' => true,
                'collapsed' => false,
                'width' => 100
            )
        );

        if ($channel->sticky_enabled) {
            $option_fields[] = array(
                'field' => 'sticky',
                'visible' => true,
                'collapsed' => false,
                'width' => 100
            );
        }

        if (bool_config_item('enable_comments') && $channel->comment_system_enabled) {
            $option_fields[] = array(
                'field' => 'allow_comments',
                'visible' => true,
                'collapsed' => false,
                'width' => 100
            );
        }

        $layout[] = array(
            'id' => 'options',
            'name' => 'options',
            'visible' => true,
            'fields' => $option_fields
        );

        if ($this->channel_id) {
            // Here comes the ugly! @TODO don't do this
            ee()->legacy_api->instantiate('channel_fields');

            $module_tabs = ee()->api_channel_fields->get_module_fields(
                $this->channel_id,
                $this->entry_id
            );
            $module_tabs = $module_tabs ?: array();

            foreach ($module_tabs as $tab_id => $fields) {
                $tab = array(
                    'id' => $tab_id,
                    'name' => $tab_id,
                    'visible' => true,
                    'fields' => array()
                );

                foreach ($fields as $key => $field) {
                    $tab['fields'][] = array(
                        'field' => $field['field_id'],
                        'visible' => true,
                        'collapsed' => false,
                        'width' => 100
                    );
                }

                $layout[] = $tab;
            }
        }

        if ($channel->enable_versioning) {
            $layout[] = array(
                'id' => 'revisions',
                'name' => 'revisions',
                'visible' => true,
                'fields' => array(
                    array(
                        'field' => 'versioning_enabled',
                        'visible' => true,
                        'collapsed' => false,
                        'width' => 100
                    ),
                    array(
                        'field' => 'revisions',
                        'visible' => true,
                        'collapsed' => false,
                        'width' => 100
                    )
                )
            );
        }

        return $layout;
    }

    public function transform(array $fields)
    {
        $display = parent::transform($fields);

        // show message if there are no category groups assigned
        $tab = $display->getTab('categories');
        $fields = $tab->getFields();
        if (count($fields) == 0) {
            $url = ee('CP/URL', 'channels/edit/' . $this->channel_id)->compile() . '#tab=t-2';
            $alert = ee('CP/Alert')->makeInline('empty-category-tab')
                ->asWarning()
                ->cannotClose()
                ->withTitle(lang('no_categories_assigned'))
                ->addToBody(sprintf(lang('no_categories_assigned_desc'), $url));

            $tab->setAlert($alert);
        }

        return $display;
    }
}

// EOF
